<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
// Eliminamos la dependencia de SimpleSoftwareIO\QrCode\Facades\QrCode

class QrController extends Controller
{
    /**
     * Generate a QR code for a solicitud
     *
     * @param Solicitud $solicitud
     * @return JsonResponse
     */
    public function generate(Solicitud $solicitud): JsonResponse
    {
        // Load the solicitud with its relationships
        $solicitud->load(['paciente', 'examenes', 'user', 'servicio']);

        // Generate a unique verification code
        $verificationCode = md5($solicitud->id . $solicitud->created_at);

        // Create the data to be encoded in the QR code
        $data = [
            'solicitud_id' => $solicitud->id,
            'paciente' => $solicitud->paciente->nombres . ' ' . $solicitud->paciente->apellidos,
            'dni' => $solicitud->paciente->dni,
            'fecha' => $solicitud->fecha,
            'hora' => $solicitud->hora,
            'examenes' => $solicitud->examenes->pluck('nombre')->toArray(),
            'verification_code' => $verificationCode,
        ];

        // Convert the data to JSON
        $jsonData = json_encode($data);

        // Generamos un QR code usando una API externa en lugar de la librería local
        // Usamos la API de QR Code Generator
        $size = 300;
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($jsonData);

        // Obtenemos la imagen
        $qrCode = file_get_contents($qrCodeUrl);

        // Convertimos a base64
        $qrCode = base64_encode($qrCode);

        return response()->json([
            'qr_code' => 'data:image/png;base64,' . $qrCode,
            'verification_code' => $verificationCode,
        ]);
    }

    /**
     * Verify a solicitud using its verification code
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'solicitud_id' => ['required', 'exists:solicitudes,id'],
            'verification_code' => ['required', 'string'],
        ]);

        $solicitud = Solicitud::findOrFail($request->solicitud_id);
        $expectedCode = md5($solicitud->id . $solicitud->created_at);

        if ($request->verification_code !== $expectedCode) {
            return response()->json([
                'valid' => false,
                'message' => 'Código de verificación inválido',
            ], 400);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Código de verificación válido',
            'solicitud' => $solicitud->load(['paciente', 'examenes', 'user', 'servicio']),
        ]);
    }
}
