<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ReportNotification;
use App\Models\TemporaryFile;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ReportNotificationController extends Controller
{
    private $whatsAppService;
    private $reporteController;

    public function __construct(WhatsAppService $whatsAppService, ReporteController $reporteController)
    {
        $this->whatsAppService = $whatsAppService;
        $this->reporteController = $reporteController;
    }

    /**
     * Enviar reporte por WhatsApp
     */
    public function sendWhatsApp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|min:9|max:15',
            'file_type' => 'required|in:pdf,excel',
            'message' => 'nullable|string|max:500',
            'report_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Crear registro de notificación
            $notification = ReportNotification::create([
                'user_id' => $request->user()->id,
                'report_type' => $request->report_type,
                'recipient_phone' => $request->phone,
                'file_type' => $request->file_type,
                'message' => $request->message ?? 'Reporte del Laboratorio Laredo adjunto',
                'report_params' => [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'filters' => $request->only(['examen_ids', 'servicio_ids', 'status'])
                ]
            ]);

            // Generar archivo del reporte
            $filePath = $this->generateReportFile($request, $notification);
            
            if (!$filePath) {
                $notification->markAsFailed('Error generando el archivo del reporte');
                return response()->json([
                    'status' => false,
                    'message' => 'Error generando el archivo del reporte'
                ], 500);
            }

            // Actualizar ruta del archivo
            $notification->update(['file_path' => $filePath]);

            // Crear archivo temporal para descarga
            $temporaryFile = $this->createTemporaryFile($filePath, $request->file_type, $request->user()->id);

            // Enviar por WhatsApp
            $result = $this->whatsAppService->sendReportFile(
                $request->phone,
                $notification->message,
                $temporaryFile->getPublicUrl()
            );

            if ($result['success']) {
                $notification->markAsSent($result['message_sid']);
                
                return response()->json([
                    'status' => true,
                    'message' => 'Reporte enviado por WhatsApp exitosamente',
                    'notification_id' => $notification->id
                ]);
            } else {
                $notification->markAsFailed($result['error']);
                
                return response()->json([
                    'status' => false,
                    'message' => 'Error enviando WhatsApp: ' . $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            if (isset($notification)) {
                $notification->markAsFailed($e->getMessage());
            }

            return response()->json([
                'status' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de notificaciones
     */
    public function getNotificationHistory(Request $request): JsonResponse
    {
        $notifications = ReportNotification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Generar archivo del reporte
     */
    private function generateReportFile(Request $request, ReportNotification $notification): ?string
    {
        try {
            if ($request->file_type === 'pdf') {
                // Crear request simulado para el controlador de reportes
                $reportRequest = new Request([
                    'type' => $request->report_type,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'examen_ids' => $request->examen_ids,
                    'servicio_ids' => $request->servicio_ids,
                    'status' => $request->status
                ]);
                $reportRequest->setUserResolver(function () use ($request) {
                    return $request->user();
                });

                $response = $this->reporteController->generatePDF($reportRequest);
                
                if ($response->getStatusCode() === 200) {
                    $fileName = 'reports/whatsapp/reporte_' . $notification->id . '_' . time() . '.pdf';
                    Storage::put($fileName, $response->getContent());
                    return $fileName;
                }
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Error generando archivo de reporte: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear archivo temporal para descarga
     */
    private function createTemporaryFile(string $filePath, string $fileType, int $userId): TemporaryFile
    {
        $fileSize = Storage::size($filePath);
        $originalName = 'reporte_' . date('Y-m-d_H-i-s') . '.' . $fileType;

        return TemporaryFile::create([
            'token' => Str::random(32),
            'file_path' => $filePath,
            'file_type' => $fileType,
            'original_name' => $originalName,
            'file_size' => $fileSize,
            'expires_at' => now()->addHours(24), // Expira en 24 horas
            'max_downloads' => 10,
            'created_by' => $userId
        ]);
    }
}
