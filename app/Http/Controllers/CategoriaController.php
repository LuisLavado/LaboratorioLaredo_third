<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoriaController extends Controller
{
    /**
     * Obtener listado de categorías
     */
    public function index(): JsonResponse
    {
        $categorias = Categoria::orderBy('created_at', 'desc')->get();
        return response()->json(['categorias' => $categorias]);
    }

    /**
     * Guardar nueva categoría
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:100',
        ]);

        $categoria = Categoria::create($validatedData);
        return response()->json(['categoria' => $categoria, 'message' => 'Categoría creada con éxito'], 201);
    }

    /**
     * Obtener categoría específica
     */
    public function show(Categoria $categoria): JsonResponse
    {
        return response()->json(['categoria' => $categoria]);
    }

    /**
     * Actualizar categoría existente
     */
    public function update(Request $request, Categoria $categoria): JsonResponse
    {
        $validatedData = $request->validate([
            'nombre' => 'sometimes|required|string|max:100',
        ]);

        $categoria->update($validatedData);
        return response()->json(['categoria' => $categoria, 'message' => 'Categoría actualizada con éxito']);
    }

    /**
     * Eliminar una categoría
     */
    public function destroy(Categoria $categoria): JsonResponse
    {
        try {
            // Verificar si la categoría tiene exámenes asociados
            if ($categoria->examenes()->count() > 0) {
                return response()->json(
                    ['message' => 'No se puede eliminar la categoría porque está siendo utilizada por uno o más exámenes.'], 
                    409 // Conflict status code
                );
            }
            
            $categoria->delete();
            return response()->json(['message' => 'Categoría eliminada con éxito']);
        } catch (\Exception $e) {
            return response()->json(
                ['message' => 'Error al eliminar la categoría: ' . $e->getMessage()], 
                500
            );
        }
    }
} 