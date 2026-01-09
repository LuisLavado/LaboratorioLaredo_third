<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Http\Requests\PacienteRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PacienteController extends Controller
{
    /**
     * Obtener listado de pacientes
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Registrar la solicitud para depuración
            \Log::info('Solicitud de pacientes recibida', [
                'incluir_eliminados' => $request->has('incluir_eliminados') ? $request->incluir_eliminados : false,
                'timestamp' => now()->toDateTimeString()
            ]);

            // Por defecto, solo obtener pacientes activos (no eliminados)
            if ($request->has('incluir_eliminados') && $request->incluir_eliminados) {
                // Si se solicita incluir eliminados, usar withTrashed
                $pacientes = Paciente::withTrashed()->orderBy('created_at', 'desc')->get();
                \Log::info('Devolviendo pacientes con eliminados', ['count' => count($pacientes)]);
            } else {
                // Usar consulta SQL directa para asegurarnos de que solo se obtengan los activos
                $pacientes = \DB::select('SELECT * FROM pacientes WHERE deleted_at IS NULL ORDER BY created_at DESC');
                \Log::info('Devolviendo solo pacientes activos', ['count' => count($pacientes)]);
            }

            return response()->json(['pacientes' => $pacientes]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener pacientes: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'message' => 'Error al obtener pacientes',
                'error' => $e->getMessage(),
                'pacientes' => []
            ], 200);
        }
    }

    /**
     * Guardar nuevo paciente
     */
    public function store(Request $request): JsonResponse
    {
        // Verificar si ya existe un paciente con el mismo DNI
        $existingPatient = Paciente::where('dni', $request->dni)->first();
        if ($existingPatient) {
            return response()->json([
                'message' => 'Ya existe un paciente con este DNI',
                'errors' => [
                    'dni' => ['El DNI ya está registrado en el sistema']
                ]
            ], 422);
        }

        $validatedData = $request->validate([
            'dni' => 'required|string|size:8|regex:/^[0-9]+$/',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'fecha_nacimiento' => 'nullable|date|before_or_equal:today',
            'celular' => 'nullable|string|max:20',
            'historia_clinica' => 'nullable|string|max:20',
            'sexo' => 'nullable|string|in:masculino,femenino',
            'edad' => 'nullable|integer|min:0|max:120',
            'edad_gestacional' => 'nullable|string|max:20'
        ], [
            'dni.required' => 'El DNI es obligatorio',
            'dni.size' => 'El DNI debe tener exactamente 8 dígitos',
            'dni.regex' => 'El DNI debe contener solo números',
            'nombres.required' => 'El nombre es obligatorio',
            'apellidos.required' => 'Los apellidos son obligatorios',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura',
            'edad.min' => 'La edad no puede ser negativa',
            'edad.max' => 'La edad no puede ser mayor a 120 años',
            'sexo.in' => 'El sexo debe ser masculino o femenino'
        ]);

        // Usar el DNI como historia clínica automáticamente
        $validatedData['historia_clinica'] = $validatedData['dni'];

        try {
            // Verificar que la historia clínica (DNI) no exista ya en otro paciente
            // Esto es redundante ya que verificamos el DNI al inicio, pero lo mantenemos por seguridad
            $exists = Paciente::where('historia_clinica', $validatedData['historia_clinica'])->exists();
            if ($exists) {
                return response()->json([
                    'message' => 'La historia clínica ya está registrada en el sistema',
                    'errors' => [
                        'historia_clinica' => ['Esta historia clínica ya está asignada a otro paciente']
                    ]
                ], 422);
            }

            $paciente = Paciente::create($validatedData);
            return response()->json(['paciente' => $paciente, 'message' => 'Paciente creado con éxito'], 201);

        } catch (\Illuminate\Database\QueryException $e) {
            // Capturar errores de base de datos
            $errorCode = $e->errorInfo[1];

            // 1062 es el código de error para violación de clave única en MySQL
            if ($errorCode == 1062) {
                // Extraer el valor duplicado del mensaje de error
                preg_match("/Duplicate entry '(.+)' for key/", $e->getMessage(), $matches);
                $duplicateValue = $matches[1] ?? 'un valor';

                if (strpos($e->getMessage(), 'historia_clinica') !== false) {
                    return response()->json([
                        'message' => 'Error al crear el paciente',
                        'errors' => [
                            'historia_clinica' => ["La historia clínica '{$duplicateValue}' ya está registrada en el sistema"]
                        ]
                    ], 422);
                } elseif (strpos($e->getMessage(), 'dni') !== false) {
                    return response()->json([
                        'message' => 'Error al crear el paciente',
                        'errors' => [
                            'dni' => ["El DNI '{$duplicateValue}' ya está registrado en el sistema"]
                        ]
                    ], 422);
                } else {
                    return response()->json([
                        'message' => 'Error al crear el paciente',
                        'errors' => [
                            'general' => ["El valor '{$duplicateValue}' ya está registrado en el sistema"]
                        ]
                    ], 422);
                }
            }

            // Otros errores de base de datos
            \Log::error('Error al crear paciente: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al crear el paciente',
                'errors' => [
                    'general' => ['Ocurrió un error en la base de datos. Por favor, contacte al administrador.']
                ]
            ], 500);
        } catch (\Exception $e) {
            // Capturar cualquier otro tipo de error
            \Log::error('Error al crear paciente: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al crear el paciente',
                'errors' => [
                    'general' => ['Ocurrió un error inesperado. Por favor, inténtelo de nuevo más tarde.']
                ]
            ], 500);
        }
    }

    /**
     * Obtener paciente específico
     */
    public function show(Paciente $paciente): JsonResponse
    {
        return response()->json(['paciente' => $paciente]);
    }

    /**
     * Actualizar paciente existente
     */
    public function update(Request $request, Paciente $paciente): JsonResponse
    {
        try {
            // Verificar si el DNI ya existe en otro paciente
            if ($request->has('dni') && $request->dni != $paciente->dni) {
                $existingPatient = Paciente::where('dni', $request->dni)
                    ->where('id', '!=', $paciente->id)
                    ->first();

                if ($existingPatient) {
                    return response()->json([
                        'message' => 'Ya existe otro paciente con este DNI',
                        'errors' => [
                            'dni' => ['El DNI ya está registrado en otro paciente']
                        ]
                    ], 422);
                }
            }

            // Verificar si la historia clínica ya existe en otro paciente
            if ($request->has('historia_clinica') && $request->historia_clinica != $paciente->historia_clinica) {
                $existingPatient = Paciente::where('historia_clinica', $request->historia_clinica)
                    ->where('id', '!=', $paciente->id)
                    ->first();

                if ($existingPatient) {
                    return response()->json([
                        'message' => 'Ya existe otro paciente con esta historia clínica',
                        'errors' => [
                            'historia_clinica' => ['La historia clínica ya está asignada a otro paciente']
                        ]
                    ], 422);
                }
            }

            $validatedData = $request->validate([
                'dni' => 'sometimes|required|string|size:8|regex:/^[0-9]+$/',
                'nombres' => 'sometimes|required|string|max:100',
                'apellidos' => 'sometimes|required|string|max:100',
                'fecha_nacimiento' => 'nullable|date|before_or_equal:today',
                'celular' => 'nullable|string|max:20',
                'sexo' => 'nullable|string|in:masculino,femenino',
                'edad' => 'nullable|integer|min:0|max:120',
                'edad_gestacional' => 'nullable|string|max:20'
            ], [
                'dni.required' => 'El DNI es obligatorio',
                'dni.size' => 'El DNI debe tener exactamente 8 dígitos',
                'dni.regex' => 'El DNI debe contener solo números',
                'nombres.required' => 'El nombre es obligatorio',
                'apellidos.required' => 'Los apellidos son obligatorios',
                'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura',
                'edad.min' => 'La edad no puede ser negativa',
                'edad.max' => 'La edad no puede ser mayor a 120 años',
                'sexo.in' => 'El sexo debe ser masculino o femenino'
            ]);

            // Si se actualizó el DNI, actualizar también la historia clínica
            if (isset($validatedData['dni'])) {
                $validatedData['historia_clinica'] = $validatedData['dni'];
            }

            $paciente->update($validatedData);
            return response()->json(['paciente' => $paciente, 'message' => 'Paciente actualizado con éxito']);

        } catch (\Illuminate\Database\QueryException $e) {
            // Capturar errores de base de datos
            $errorCode = $e->errorInfo[1];

            // 1062 es el código de error para violación de clave única en MySQL
            if ($errorCode == 1062) {
                // Extraer el valor duplicado del mensaje de error
                preg_match("/Duplicate entry '(.+)' for key/", $e->getMessage(), $matches);
                $duplicateValue = $matches[1] ?? 'un valor';

                if (strpos($e->getMessage(), 'historia_clinica') !== false) {
                    return response()->json([
                        'message' => 'Error al actualizar el paciente',
                        'errors' => [
                            'historia_clinica' => ["La historia clínica '{$duplicateValue}' ya está registrada en el sistema"]
                        ]
                    ], 422);
                } elseif (strpos($e->getMessage(), 'dni') !== false) {
                    return response()->json([
                        'message' => 'Error al actualizar el paciente',
                        'errors' => [
                            'dni' => ["El DNI '{$duplicateValue}' ya está registrado en el sistema"]
                        ]
                    ], 422);
                } else {
                    return response()->json([
                        'message' => 'Error al actualizar el paciente',
                        'errors' => [
                            'general' => ["El valor '{$duplicateValue}' ya está registrado en el sistema"]
                        ]
                    ], 422);
                }
            }

            // Otros errores de base de datos
            \Log::error('Error al actualizar paciente: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al actualizar el paciente',
                'errors' => [
                    'general' => ['Ocurrió un error en la base de datos. Por favor, contacte al administrador.']
                ]
            ], 500);
        } catch (\Exception $e) {
            // Capturar cualquier otro tipo de error
            \Log::error('Error al actualizar paciente: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al actualizar el paciente',
                'errors' => [
                    'general' => ['Ocurrió un error inesperado. Por favor, inténtelo de nuevo más tarde.']
                ]
            ], 500);
        }
    }

    /**
     * Eliminar un paciente
     */
    public function destroy(Paciente $paciente): JsonResponse
    {
        $paciente->delete();
        return response()->json(['message' => 'Paciente eliminado con éxito']);
    }

    /**
     * Buscar paciente por DNI
     */
    public function searchByDNI(string $dni): JsonResponse
    {
        $paciente = Paciente::where('dni', $dni)->first();

        if (!$paciente) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }

        return response()->json(['paciente' => $paciente]);
    }

    /**
     * Buscar pacientes por nombre o DNI
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query');

        if (empty($query)) {
            return response()->json(['pacientes' => []]);
        }

        $pacientes = Paciente::where('dni', 'LIKE', "%{$query}%")
            ->orWhere('nombres', 'LIKE', "%{$query}%")
            ->orWhere('apellidos', 'LIKE', "%{$query}%")
            ->orWhereRaw("CONCAT(nombres, ' ', apellidos) LIKE ?", ["%{$query}%"])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json(['pacientes' => $pacientes]);
    }

    /**
     * Obtener pacientes eliminados
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function trashed(): JsonResponse
    {
        try {
            // Verificar si la tabla existe
            if (!\Schema::hasTable('pacientes')) {
                return response()->json([
                    'message' => 'La tabla pacientes no existe',
                    'pacientes' => []
                ], 200);
            }

            // Verificar si la columna deleted_at existe
            if (!\Schema::hasColumn('pacientes', 'deleted_at')) {
                return response()->json([
                    'message' => 'La columna deleted_at no existe en la tabla pacientes',
                    'pacientes' => []
                ], 200);
            }

            // Obtener pacientes eliminados directamente de la base de datos
            $pacientes = \DB::table('pacientes')
                ->whereNotNull('deleted_at')
                ->get();

            // Devolver una respuesta exitosa incluso si no hay pacientes eliminados
            return response()->json(['pacientes' => $pacientes]);
        } catch (\Exception $e) {
            // Registrar el error para depuración
            \Log::error('Error al obtener pacientes eliminados: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            // Devolver una respuesta con información detallada del error
            return response()->json([
                'message' => 'Error al obtener pacientes eliminados',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'pacientes' => []
            ], 200); // Devolver 200 en lugar de 500 para evitar errores en el frontend
        }
    }

    /**
     * Obtener pacientes eliminados (método alternativo sin usar modelo)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPacientesEliminados(): JsonResponse
    {
        try {
            // Consulta SQL directa para obtener pacientes eliminados
            $pacientes = \DB::select('SELECT * FROM pacientes WHERE deleted_at IS NOT NULL');

            return response()->json(['pacientes' => $pacientes]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener pacientes eliminados (método alternativo): ' . $e->getMessage());

            return response()->json([
                'message' => 'Error al obtener pacientes eliminados',
                'error' => $e->getMessage(),
                'pacientes' => []
            ], 200);
        }
    }

    /**
     * Restaurar un paciente eliminado
     *
     * @param int $paciente_id ID del paciente a restaurar
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($paciente_id): JsonResponse
    {
        try {
            // Verificar si el paciente existe y está eliminado
            $pacienteExiste = \DB::table('pacientes')
                ->where('id', $paciente_id)
                ->whereNotNull('deleted_at')
                ->exists();

            if (!$pacienteExiste) {
                return response()->json([
                    'message' => 'No se encontró el paciente eliminado con ID: ' . $paciente_id,
                    'success' => false
                ], 200); // Usar 200 para evitar errores en el frontend
            }

            // Usar consulta SQL directa para restaurar
            \DB::statement("UPDATE pacientes SET deleted_at = NULL WHERE id = ?;", [$paciente_id]);

            // Obtener el paciente restaurado
            $paciente = \DB::table('pacientes')->where('id', $paciente_id)->first();

            return response()->json([
                'paciente' => $paciente,
                'message' => 'Paciente restaurado con éxito',
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al restaurar paciente: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'message' => 'No se pudo restaurar el paciente',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'success' => false
            ], 200); // Usar 200 para evitar errores en el frontend
        }
    }

    /**
     * Eliminar permanentemente un paciente
     *
     * @param int $paciente_id ID del paciente a eliminar permanentemente
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceDelete($paciente_id): JsonResponse
    {
        try {
            // Verificar si el paciente existe y está eliminado
            $exists = \DB::table('pacientes')
                ->where('id', $paciente_id)
                ->whereNotNull('deleted_at')
                ->exists();

            if (!$exists) {
                return response()->json([
                    'message' => 'No se encontró el paciente eliminado con ID: ' . $paciente_id,
                    'success' => false
                ], 200); // Usar 200 para evitar errores en el frontend
            }

            // Eliminar permanentemente el paciente usando SQL directo
            \DB::statement("DELETE FROM pacientes WHERE id = ?;", [$paciente_id]);

            return response()->json([
                'message' => 'Paciente eliminado permanentemente',
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar permanentemente paciente: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'message' => 'No se pudo eliminar permanentemente el paciente',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'success' => false
            ], 200); // Usar 200 para evitar errores en el frontend
        }
    }
}
