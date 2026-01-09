<?php

namespace App\Http\Controllers;

use App\Models\CampoExamen;
use App\Models\Examen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampoExamenController extends Controller
{
    /**
     * Obtener campos de un examen
     */
    public function index(Request $request): JsonResponse
    {
        $examenId = $request->query('examen_id');
        $incluirInactivos = $request->boolean('incluir_inactivos');

        if ($examenId) {
            $query = CampoExamen::where('examen_id', $examenId);

            if (!$incluirInactivos) {
                $query->activos();
            }

            $campos = $query->get()->map(function($campo) {
                return [
                    'id' => $campo->id,
                    'nombre' => $campo->nombre,
                    'tipo' => $campo->tipo,
                    'unidad' => $campo->unidad,
                    'valor_referencia' => $campo->valor_referencia,
                    'seccion' => $campo->seccion,
                    'activo' => $campo->activo,
                    'version' => $campo->version,
                    'tiene_resultados' => $campo->tieneResultados(),
                    'fecha_desactivacion' => $campo->fecha_desactivacion,
                    'motivo_cambio' => $campo->motivo_cambio
                ];
            })->groupBy('seccion');
        } else {
            $campos = CampoExamen::with('examen')->activos()->get();
        }

        return response()->json($campos);
    }

    /**
     * Crear un nuevo campo para un examen
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'examen_id' => 'required|exists:examenes,id',
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|in:text,number,select,boolean,textarea',
            'unidad' => 'nullable|string|max:50',
            'valor_referencia' => 'nullable|string',
            'opciones' => 'nullable|array',
            'requerido' => 'boolean',
            'orden' => 'integer|min:0',
            'seccion' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string'
        ]);

        $campo = CampoExamen::create($request->all());

        return response()->json($campo->load('examen'), 201);
    }

    /**
     * Mostrar un campo específico
     */
    public function show(CampoExamen $campoExamen): JsonResponse
    {
        return response()->json($campoExamen->load('examen'));
    }

    /**
     * Actualizar un campo
     */
    public function update(Request $request, CampoExamen $campoExamen): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'tipo' => 'sometimes|in:text,number,select,boolean,textarea',
            'unidad' => 'nullable|string|max:50',
            'valor_referencia' => 'nullable|string',
            'opciones' => 'nullable|array',
            'requerido' => 'boolean',
            'orden' => 'integer|min:0',
            'seccion' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'motivo_cambio' => 'nullable|string'
        ]);

        // Si el campo tiene resultados, crear nueva versión
        if ($campoExamen->tieneResultados()) {
            $motivo = $validated['motivo_cambio'] ?? 'Actualización de campo con resultados existentes';
            unset($validated['motivo_cambio']);

            $nuevoCampo = $campoExamen->crearNuevaVersion($validated, $motivo);

            return response()->json([
                'message' => 'Nueva versión del campo creada exitosamente',
                'campo' => $nuevoCampo->load('examen'),
                'version_anterior_desactivada' => true
            ]);
        } else {
            // Si no tiene resultados, actualizar directamente
            $campoExamen->update($validated);

            return response()->json([
                'message' => 'Campo actualizado exitosamente',
                'campo' => $campoExamen->fresh()->load('examen'),
                'version_anterior_desactivada' => false
            ]);
        }
    }

    /**
     * Eliminar un campo (solo si no tiene resultados)
     */
    public function destroy(CampoExamen $campoExamen): JsonResponse
    {
        if ($campoExamen->tieneResultados()) {
            return response()->json([
                'message' => 'No se puede eliminar un campo que tiene resultados registrados. Use desactivar en su lugar.',
                'error' => 'CAMPO_CON_RESULTADOS'
            ], 400);
        }

        $nombre = $campoExamen->nombre;
        $campoExamen->delete();

        return response()->json([
            'message' => "Campo '{$nombre}' eliminado exitosamente"
        ]);
    }

    /**
     * Desactivar campo
     */
    public function desactivar(Request $request, CampoExamen $campoExamen): JsonResponse
    {
        if (!$campoExamen->activo) {
            return response()->json([
                'message' => 'El campo ya está desactivado'
            ], 400);
        }

        $request->validate([
            'motivo' => 'nullable|string'
        ]);

        $motivo = $request->motivo ?? 'Desactivado desde la interfaz';
        $campoExamen->desactivar($motivo);

        return response()->json([
            'message' => 'Campo desactivado exitosamente',
            'tiene_resultados' => $campoExamen->tieneResultados()
        ]);
    }

    /**
     * Reactivar campo
     */
    public function reactivar(CampoExamen $campoExamen): JsonResponse
    {
        if ($campoExamen->activo) {
            return response()->json([
                'message' => 'El campo ya está activo'
            ], 400);
        }

        $campoExamen->update([
            'activo' => true,
            'fecha_desactivacion' => null
        ]);

        return response()->json([
            'message' => 'Campo reactivado exitosamente',
            'campo' => $campoExamen->fresh()
        ]);
    }

    /**
     * Crear múltiples campos para un examen
     */
    public function storeMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'examen_id' => 'required|exists:examenes,id',
            'campos' => 'required|array',
            'campos.*.nombre' => 'required|string|max:255',
            'campos.*.tipo' => 'required|in:text,number,select,boolean,textarea',
            'campos.*.unidad' => 'nullable|string|max:50',
            'campos.*.valor_referencia' => 'nullable|string',
            'campos.*.opciones' => 'nullable|array',
            'campos.*.requerido' => 'boolean',
            'campos.*.orden' => 'integer|min:0',
            'campos.*.seccion' => 'nullable|string|max:255',
            'campos.*.descripcion' => 'nullable|string'
        ]);

        $campos = [];
        foreach ($request->campos as $campoData) {
            $campoData['examen_id'] = $request->examen_id;
            $campos[] = CampoExamen::create($campoData);
        }

        return response()->json($campos, 201);
    }

    /**
     * Reordenar campos de un examen
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'campos' => 'required|array',
            'campos.*.id' => 'required|exists:campos_examen,id',
            'campos.*.orden' => 'required|integer|min:0'
        ]);

        foreach ($request->campos as $campoData) {
            CampoExamen::where('id', $campoData['id'])
                ->update(['orden' => $campoData['orden']]);
        }

        return response()->json(['message' => 'Campos reordenados correctamente']);
    }
}
