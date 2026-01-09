<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Examen;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class ExamenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $search = $request->input('search', '');
        $categoriaId = $request->input('categoria_id');
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'asc');
        $all = $request->input('all', false);

        $cacheKey = "examenes:{$perPage}:{$search}:{$categoriaId}:{$sortBy}:{$sortDir}:{$all}";

        return Cache::remember($cacheKey, 300, function() use ($request, $perPage, $search, $categoriaId, $sortBy, $sortDir, $all) {
            $query = Examen::with('categoria');

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('codigo', 'like', "%{$search}%");
                });
            }

            if (!empty($categoriaId)) {
                $query->where('categoria_id', $categoriaId);
            }

            \Log::info('Consulta de exámenes', [
                'search' => $search,
                'categoria_id' => $categoriaId,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
                'all' => $all
            ]);

            $query->orderBy($sortBy, $sortDir);

            if ($all) {
                $examenes = $query->get();
            } else {
                $examenes = $query->paginate($perPage);
            }

            return response()->json([
                'status' => true,
                'examenes' => $examenes
            ]);
        });
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'codigo' => 'required|string|max:20|unique:examenes',
            'nombre' => 'required|string|max:255',
            'categoria_id' => 'required|exists:categorias,id',
            'activo' => 'boolean',
            'tipo' => 'required|in:simple,compuesto,hibrido',
            'es_perfil' => 'boolean',
            'instrucciones_muestra' => 'nullable|string',
            'metodo_analisis' => 'nullable|string|max:255',
            'campos' => 'nullable|array',
            'campos.*.nombre' => 'required_with:campos|string|max:255',
            'campos.*.tipo' => 'required_with:campos|in:text,number,select,boolean,textarea',
            'campos.*.unidad' => 'nullable|string|max:50',
            'campos.*.valor_referencia' => 'nullable|string',
            'campos.*.opciones' => 'nullable|array',
            'campos.*.requerido' => 'boolean',
            'campos.*.orden' => 'integer|min:0',
            'campos.*.seccion' => 'nullable|string|max:255',
            'examenes_hijos' => 'nullable|array',
            'examenes_hijos.*' => 'exists:examenes,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $examen = Examen::create($request->only([
            'codigo', 'nombre', 'categoria_id', 'activo', 'tipo', 'es_perfil',
            'instrucciones_muestra', 'metodo_analisis'
        ]));

        // Crear campos si se proporcionaron
        if ($request->has('campos') && is_array($request->campos)) {
            foreach ($request->campos as $campoData) {
                $campoData['examen_id'] = $examen->id;
                \App\Models\CampoExamen::create($campoData);
            }
        }

        // Agregar exámenes hijos si es compuesto o híbrido
        if (in_array($examen->tipo, ['compuesto', 'hibrido']) && $request->has('examenes_hijos')) {
            foreach ($request->examenes_hijos as $index => $examenHijoId) {
                \App\Models\ExamenCompuesto::create([
                    'examen_padre_id' => $examen->id,
                    'examen_hijo_id' => $examenHijoId,
                    'orden' => $index,
                    'activo' => true
                ]);
            }
        }

        $this->forceCleanCache();

        return response()->json([
            'status' => true,
            'message' => 'Examen creado correctamente',
            'examen' => $examen->load(['categoria', 'campos', 'examenesHijos'])
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
            $examen = Examen::with([
                'categoria',
                'campos' => function($query) {
                    $query->activos();
                },
                'examenesHijos.categoria',
                'examenesHijos.campos'
            ])->find($id);

            if (!$examen) {
                return response()->json([
                    'status' => false,
                    'message' => 'Examen no encontrado'
                ], 404);
            }

            // Agrupar campos por sección
            $camposPorSeccion = $examen->todosLosCampos()->groupBy('seccion');

            return response()->json([
                'status' => true,
                'examen' => $examen,
                'campos_por_seccion' => $camposPorSeccion
            ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $examen = Examen::find($id);

        if (!$examen) {
            return response()->json([
                'status' => false,
                'message' => 'Examen no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'codigo' => 'sometimes|required|string|max:20|unique:examenes,codigo,' . $id,
            'nombre' => 'sometimes|required|string|max:255',
            'categoria_id' => 'sometimes|required|exists:categorias,id',
            'activo' => 'sometimes|boolean',
            'tipo' => 'sometimes|required|in:simple,compuesto,hibrido',
            'es_perfil' => 'sometimes|boolean',
            'instrucciones_muestra' => 'sometimes|nullable|string',
            'metodo_analisis' => 'sometimes|nullable|string|max:255',
            'campos' => 'sometimes|nullable|array',
            'campos.*.nombre' => 'required_with:campos|string|max:255',
            'campos.*.tipo' => 'required_with:campos|in:text,number,select,boolean,textarea',
            'campos.*.unidad' => 'nullable|string|max:50',
            'campos.*.valor_referencia' => 'nullable|string',
            'campos.*.opciones' => 'nullable|array',
            'campos.*.requerido' => 'boolean',
            'campos.*.orden' => 'integer|min:0',
            'campos.*.seccion' => 'nullable|string|max:255',
            'examenes_hijos' => 'sometimes|nullable|array',
            'examenes_hijos.*' => 'exists:examenes,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $cambioEstado = isset($request->activo) && $examen->activo != $request->activo;

        $examen->update($request->only([
            'codigo', 'nombre', 'categoria_id', 'activo', 'tipo', 'es_perfil',
            'instrucciones_muestra', 'metodo_analisis'
        ]));

        // Actualizar campos si se proporcionaron
        if ($request->has('campos')) {
            // Eliminar campos existentes
            $examen->campos()->delete();

            // Crear nuevos campos
            if (is_array($request->campos)) {
                foreach ($request->campos as $campoData) {
                    $campoData['examen_id'] = $examen->id;
                    \App\Models\CampoExamen::create($campoData);
                }
            }
        }

        // Actualizar exámenes hijos si es compuesto
        if ($request->has('examenes_hijos')) {
            // Eliminar relaciones existentes
            \App\Models\ExamenCompuesto::where('examen_padre_id', $examen->id)->delete();

            // Crear nuevas relaciones
            if (in_array($examen->tipo, ['compuesto', 'hibrido']) && is_array($request->examenes_hijos)) {
                foreach ($request->examenes_hijos as $index => $examenHijoId) {
                    \App\Models\ExamenCompuesto::create([
                        'examen_padre_id' => $examen->id,
                        'examen_hijo_id' => $examenHijoId,
                        'orden' => $index,
                        'activo' => true
                    ]);
                }
            }
        }

        if ($cambioEstado) {
            $this->forceCleanCache();
            \Log::info('Estado de examen cambiado: ID=' . $id . ', Activo=' . $request->activo);
        } else {
            Cache::forget("examen:{$id}");
        }

        return response()->json([
            'status' => true,
            'message' => 'Examen actualizado correctamente',
            'examen' => $examen->load(['categoria', 'campos', 'examenesHijos'])
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $examen = Examen::find($id);

        if (!$examen) {
            return response()->json([
                'status' => false,
                'message' => 'Examen no encontrado'
            ], 404);
        }

        $examen->activo = false;
        $examen->save();

        $this->forceCleanCache();
        \Log::info('Examen desactivado: ID=' . $id);

        return response()->json([
            'status' => true,
            'message' => 'Examen desactivado correctamente'
        ]);
    }

    public function reactivar(string $id): JsonResponse
    {
        $examen = Examen::find($id);

        if (!$examen) {
            return response()->json([
                'status' => false,
                'message' => 'Examen no encontrado'
            ], 404);
        }

        $examen->activo = true;
        $examen->save();

        $this->forceCleanCache();
        \Log::info('Examen reactivado: ID=' . $id);

        return response()->json([
            'status' => true,
            'message' => 'Examen reactivado correctamente',
            'examen' => $examen
        ]);
    }

    public function getByCategoria(string $categoriaId): JsonResponse
    {
        $categoria = Categoria::find($categoriaId);

        if (!$categoria) {
            return response()->json([
                'status' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        $examenes = Examen::where('categoria_id', $categoriaId)
            ->where('activo', true)
            ->get();

        return response()->json([
            'status' => true,
            'categoria' => $categoria->nombre,
            'examenes' => $examenes
        ]);
    }

    public function getInactivos(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $search = $request->input('search', '');
        $categoriaId = $request->input('categoria_id');
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'asc');
        $all = $request->input('all', false);

        $query = Examen::with('categoria')
                ->where('activo', false);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        if (!empty($categoriaId)) {
            $query->where('categoria_id', $categoriaId);
        }

        $query->orderBy($sortBy, $sortDir);

        if ($all) {
            $examenes = $query->get();
        } else {
            $examenes = $query->paginate($perPage);
        }

        return response()->json([
            'status' => true,
            'examenes' => $examenes
        ]);
    }

    private function clearExamenesCache(): void
    {
        $this->forceCleanCache();
    }

    /**
     * Obtener exámenes simples que NO son perfiles (para usar en exámenes compuestos)
     */
    public function getSimplesSinPerfiles(Request $request): JsonResponse
    {
        $search = $request->input('search', '');
        $categoriaId = $request->input('categoria_id');

        $query = Examen::with('categoria')
                ->where('activo', true)
                ->where('tipo', 'simple')
                ->where('es_perfil', false); // Excluir perfiles

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        if (!empty($categoriaId)) {
            $query->where('categoria_id', $categoriaId);
        }

        $examenes = $query->orderBy('nombre')->get();

        return response()->json([
            'status' => true,
            'examenes' => $examenes
        ]);
    }

    private function forceCleanCache(): void
    {
        try {
            Cache::flush();

            \Log::info('Caché limpiada forzosamente');
        } catch (\Exception $e) {
            \Log::error('Error al limpiar caché: ' . $e->getMessage());
        }
    }
}