<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DniController extends Controller
{
    public function consultar($dni)
    {
        try {
            // Usar la nueva API de consulta RENIEC
            $response = Http::asForm()->post("https://panconqueso.bonelektroniks.com/api/consulta-reniec-simple", [
                'dni' => $dni
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success']) {
                    // La nueva API ya devuelve el formato correcto, solo pasamos los datos
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'nombres' => $data['data']['nombres'],
                            'apellidos' => $data['data']['apellidos'],
                            'sexo' => $data['data']['sexo'],
                            'fecha_nacimiento' => $data['data']['fecha_nacimiento']
                        ]
                    ]);
                }
            }

            // Si la API no devuelve datos o hay un error
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener la información del DNI'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error al consultar DNI: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar el DNI'
            ], 500);
        }
    }
}