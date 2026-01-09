<?php

namespace App\Http\Controllers;

use App\Models\Examen;
use App\Models\ExamenCompuesto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamenCompuestoController extends Controller
{
    /**
     * Obtener exámenes hijos de un examen compuesto
     */
    public function index(Request $request): JsonResponse
    {
        $examenPadreId = $request->query('examen_padre_id');
        
        if ($examenPadreId) {
            $examen = Examen::with(['examenesHijos.categoria', 'examenesHijos.campos'])
                ->findOrFail($examenPadreId);
            
            return response()->json($examen->examenesHijos);
        }

        $examenes = ExamenCompuesto::with(['examenPadre', 'examenHijo'])
            ->activos()
            ->get();

        return response()->json($examenes);
    }

    /**
     * Agregar exámenes hijos a un examen compuesto
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'examen_padre_id' => 'required|exists:examenes,id',
            'examenes_hijos' => 'required|array',
            'examenes_hijos.*.examen_id' => 'required|exists:examenes,id',
            'examenes_hijos.*.orden' => 'integer|min:0'
        ]);

        // Verificar que el examen padre sea de tipo compuesto
        $examenPadre = Examen::findOrFail($request->examen_padre_id);
        if ($examenPadre->tipo !== 'compuesto') {
            return response()->json([
                'message' => 'El examen debe ser de tipo compuesto para agregar exámenes hijos'
            ], 422);
        }

        $relaciones = [];
        foreach ($request->examenes_hijos as $index => $examenHijo) {
            // Verificar que no sea una relación circular
            if ($examenHijo['examen_id'] == $request->examen_padre_id) {
                continue;
            }

            $relacion = ExamenCompuesto::updateOrCreate(
                [
                    'examen_padre_id' => $request->examen_padre_id,
                    'examen_hijo_id' => $examenHijo['examen_id']
                ],
                [
                    'orden' => $examenHijo['orden'] ?? $index,
                    'activo' => true
                ]
            );

            $relaciones[] = $relacion->load(['examenPadre', 'examenHijo']);
        }

        return response()->json($relaciones, 201);
    }

    /**
     * Mostrar una relación específica
     */
    public function show(ExamenCompuesto $examenCompuesto): JsonResponse
    {
        return response()->json($examenCompuesto->load(['examenPadre', 'examenHijo']));
    }

    /**
     * Actualizar una relación específica
     */
    public function update(Request $request, ExamenCompuesto $examenCompuesto): JsonResponse
    {
        $request->validate([
            'orden' => 'integer|min:0',
            'activo' => 'boolean'
        ]);

        $examenCompuesto->update($request->only(['orden', 'activo']));

        return response()->json($examenCompuesto->load(['examenPadre', 'examenHijo']));
    }

    /**
     * Eliminar una relación
     */
    public function destroy(ExamenCompuesto $examenCompuesto): JsonResponse
    {
        $examenCompuesto->delete();
        return response()->json(null, 204);
    }

    /**
     * Reordenar exámenes hijos
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'examen_padre_id' => 'required|exists:examenes,id',
            'examenes' => 'required|array',
            'examenes.*.examen_hijo_id' => 'required|exists:examenes,id',
            'examenes.*.orden' => 'required|integer|min:0'
        ]);

        foreach ($request->examenes as $examenData) {
            ExamenCompuesto::where('examen_padre_id', $request->examen_padre_id)
                ->where('examen_hijo_id', $examenData['examen_hijo_id'])
                ->update(['orden' => $examenData['orden']]);
        }

        return response()->json(['message' => 'Exámenes reordenados correctamente']);
    }


}
