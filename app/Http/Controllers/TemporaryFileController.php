<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TemporaryFile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class TemporaryFileController extends Controller
{
    /**
     * Descargar archivo temporal
     */
    public function download(Request $request, string $token)
    {
        $temporaryFile = TemporaryFile::where('token', $token)->first();

        if (!$temporaryFile) {
            return response()->json([
                'status' => false,
                'message' => 'Archivo no encontrado'
            ], 404);
        }

        if (!$temporaryFile->isAvailable()) {
            $reason = 'Archivo no disponible';
            
            if ($temporaryFile->isExpired()) {
                $reason = 'El enlace ha expirado';
            } elseif ($temporaryFile->hasReachedDownloadLimit()) {
                $reason = 'Se ha alcanzado el límite de descargas';
            } elseif (!Storage::exists($temporaryFile->file_path)) {
                $reason = 'El archivo ya no existe en el servidor';
            }

            return response()->json([
                'status' => false,
                'message' => $reason
            ], 410); // Gone
        }

        try {
            // Incrementar contador de descargas
            $temporaryFile->incrementDownloadCount();

            // Obtener contenido del archivo
            $fileContent = Storage::get($temporaryFile->file_path);
            
            // Determinar tipo MIME
            $mimeType = $this->getMimeType($temporaryFile->file_type);

            // Retornar archivo
            return response($fileContent)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'attachment; filename="' . $temporaryFile->original_name . '"')
                ->header('Content-Length', $temporaryFile->file_size);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error descargando el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información del archivo temporal
     */
    public function info(Request $request, string $token)
    {
        $temporaryFile = TemporaryFile::where('token', $token)->first();

        if (!$temporaryFile) {
            return response()->json([
                'status' => false,
                'message' => 'Archivo no encontrado'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'original_name' => $temporaryFile->original_name,
                'file_type' => $temporaryFile->file_type,
                'file_size' => $temporaryFile->file_size,
                'expires_at' => $temporaryFile->expires_at,
                'download_count' => $temporaryFile->download_count,
                'max_downloads' => $temporaryFile->max_downloads,
                'is_available' => $temporaryFile->isAvailable(),
                'is_expired' => $temporaryFile->isExpired(),
                'has_reached_limit' => $temporaryFile->hasReachedDownloadLimit()
            ]
        ]);
    }

    /**
     * Obtener tipo MIME según extensión
     */
    private function getMimeType(string $fileType): string
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv'
        ];

        return $mimeTypes[$fileType] ?? 'application/octet-stream';
    }
}
