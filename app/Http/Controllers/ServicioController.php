<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Servicio;
use Illuminate\Support\Facades\Validator;

class ServicioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $servicios = Servicio::active()
            ->with('parent')
            ->orderBy('parent_id')
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'status' => true,
            'servicios' => $servicios
        ]);
    }

    /**
     * Get services with statistics (count of requests)
     */
    public function getWithStats()
    {
        // Obtener servicios activos con estadísticas
        $servicios = Servicio::leftJoin('solicitudes', 'servicios.id', '=', 'solicitudes.servicio_id')
            ->select(
                'servicios.id',
                'servicios.nombre',
                'servicios.parent_id',
                'servicios.activo',
                'servicios.created_at',
                'servicios.updated_at',
                \DB::raw('COUNT(solicitudes.id) as solicitudes_count')
            )
            ->where('servicios.activo', true)
            ->groupBy('servicios.id', 'servicios.nombre', 'servicios.parent_id', 'servicios.activo', 'servicios.created_at', 'servicios.updated_at')
            ->get();

        // Agregar información jerárquica y contar sub-servicios
        foreach ($servicios as $servicio) {
            if ($servicio->parent_id) {
                $parent = Servicio::find($servicio->parent_id);
                $servicio->full_name = $parent ? $parent->nombre . ' - ' . $servicio->nombre : $servicio->nombre;
                $servicio->is_child = true;
                $servicio->children_count = 0; // Los hijos no tienen sub-servicios
            } else {
                $servicio->full_name = $servicio->nombre;
                $servicio->is_child = false;
                // Contar cuántos sub-servicios tiene este servicio padre
                $servicio->children_count = Servicio::where('parent_id', $servicio->id)->count();
            }
        }

        // Separar servicios padre e hijos
        $parentServices = $servicios->where('parent_id', null);
        $childServices = $servicios->where('parent_id', '!=', null);

        // Ordenar servicios padre por cantidad de sub-servicios (ascendente) y luego por nombre
        $parentServices = $parentServices->sortBy([
            ['children_count', 'asc'],  // Primero los que no tienen sub-servicios, luego los que tienen pocos
            ['nombre', 'asc']           // Dentro del mismo grupo, ordenar alfabéticamente
        ]);

        // Reorganizar manteniendo jerarquía
        $organized = collect();

        foreach ($parentServices as $parent) {
            $organized->push($parent);
            // Agregar hijos de este padre inmediatamente después, ordenados alfabéticamente
            $children = $childServices->where('parent_id', $parent->id)->sortBy('nombre');
            foreach ($children as $child) {
                $organized->push($child);
            }
        }

        // Agregar hijos huérfanos (si los hay)
        $orphanChildren = $childServices->filter(function ($child) use ($parentServices) {
            return !$parentServices->contains('id', $child->parent_id);
        });
        foreach ($orphanChildren as $orphan) {
            $organized->push($orphan);
        }

        return response()->json([
            'status' => true,
            'servicios' => $organized->values()->all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100|unique:servicios,nombre,NULL,id,activo,1',
            'parent_id' => 'nullable|exists:servicios,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar que el parent_id sea de un servicio activo si se proporciona
        if ($request->parent_id) {
            $parent = Servicio::find($request->parent_id);
            if (!$parent || !$parent->activo) {
                return response()->json([
                    'status' => false,
                    'message' => 'El servicio padre debe estar activo'
                ], 422);
            }
        }

        $servicio = Servicio::create([
            'nombre' => $request->nombre,
            'parent_id' => $request->parent_id,
            'activo' => true
        ]);

        // Cargar la relación parent para la respuesta
        $servicio->load('parent');

        return response()->json([
            'status' => true,
            'message' => 'Servicio creado correctamente',
            'servicio' => $servicio
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $servicio = Servicio::find($id);
        
        if (!$servicio) {
            return response()->json([
                'status' => false,
                'message' => 'Servicio no encontrado'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'servicio' => $servicio
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $servicio = Servicio::find($id);

        if (!$servicio) {
            return response()->json([
                'status' => false,
                'message' => 'Servicio no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100|unique:servicios,nombre,' . $id . ',id,activo,1',
            'parent_id' => 'nullable|exists:servicios,id|different:id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar que el parent_id sea de un servicio activo si se proporciona
        if ($request->parent_id) {
            $parent = Servicio::find($request->parent_id);
            if (!$parent || !$parent->activo) {
                return response()->json([
                    'status' => false,
                    'message' => 'El servicio padre debe estar activo'
                ], 422);
            }

            // Verificar que no se cree una referencia circular
            if ($this->wouldCreateCircularReference($id, $request->parent_id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No se puede crear una referencia circular'
                ], 422);
            }
        }

        $servicio->update([
            'nombre' => $request->nombre,
            'parent_id' => $request->parent_id
        ]);

        // Cargar la relación parent para la respuesta
        $servicio->load('parent');

        return response()->json([
            'status' => true,
            'message' => 'Servicio actualizado correctamente',
            'servicio' => $servicio
        ]);
    }

    /**
     * Deactivate the specified resource (soft delete).
     */
    public function destroy(Request $request, string $id)
    {
        $servicio = Servicio::find($id);

        if (!$servicio) {
            return response()->json([
                'status' => false,
                'message' => 'Servicio no encontrado'
            ], 404);
        }

        if (!$servicio->activo) {
            return response()->json([
                'status' => false,
                'message' => 'El servicio ya está inactivo'
            ], 422);
        }

        // Verificar si tiene servicios hijos activos
        $childrenCount = $servicio->children()->where('activo', true)->count();
        if ($childrenCount > 0) {
            return response()->json([
                'status' => false,
                'message' => 'No se puede desactivar el servicio porque tiene sub-servicios activos'
            ], 422);
        }

        $servicio->deactivate($request->motivo);

        return response()->json([
            'status' => true,
            'message' => 'Servicio desactivado correctamente'
        ]);
    }

    /**
     * Get inactive services
     */
    public function getInactive()
    {
        $servicios = Servicio::inactive()
            ->with('parent')
            ->orderBy('fecha_desactivacion', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'servicios' => $servicios
        ]);
    }

    /**
     * Activate an inactive service
     */
    public function activate(string $id)
    {
        $servicio = Servicio::find($id);

        if (!$servicio) {
            return response()->json([
                'status' => false,
                'message' => 'Servicio no encontrado'
            ], 404);
        }

        if ($servicio->activo) {
            return response()->json([
                'status' => false,
                'message' => 'El servicio ya está activo'
            ], 422);
        }

        // Si tiene un padre, verificar que esté activo
        if ($servicio->parent_id) {
            $parent = Servicio::find($servicio->parent_id);
            if (!$parent || !$parent->activo) {
                return response()->json([
                    'status' => false,
                    'message' => 'No se puede activar el servicio porque su servicio padre está inactivo'
                ], 422);
            }
        }

        $servicio->activate();

        return response()->json([
            'status' => true,
            'message' => 'Servicio activado correctamente'
        ]);
    }

    /**
     * Check for circular reference
     */
    private function wouldCreateCircularReference($serviceId, $parentId)
    {
        if ($serviceId == $parentId) {
            return true;
        }

        $parent = Servicio::find($parentId);
        while ($parent && $parent->parent_id) {
            if ($parent->parent_id == $serviceId) {
                return true;
            }
            $parent = Servicio::find($parent->parent_id);
        }

        return false;
    }

    /**
     * Obtener detalle de servicios por paciente para exportar a Excel
     */
    public function getDetalleServiciosPorPaciente(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $query = \DB::table('solicitudes')
            ->join('pacientes', 'solicitudes.paciente_id', '=', 'pacientes.id')
            ->join('servicios', 'solicitudes.servicio_id', '=', 'servicios.id')
            ->select(
                'servicios.nombre as servicio',
                'pacientes.nombres as paciente',
                'pacientes.dni',
                'solicitudes.fecha',
                'solicitudes.estado',
                'solicitudes.id',
                'solicitudes.completado as completado'
            );
        if ($startDate && $endDate) {
            $query->whereBetween('solicitudes.fecha', [$startDate, $endDate]);
        }
        $result = $query->orderBy('servicios.nombre')->orderBy('pacientes.nombres')->get();
        // Formatear para el Excel
        $detalle = [];
        foreach ($result as $row) {
            $detalle[] = [
                'servicio' => $row->servicio,
                'paciente' => $row->paciente,
                'dni' => $row->dni,
                'fecha' => $row->fecha,
                'estado' => $row->estado,
                'completado' => $row->completado ? 'Sí' : 'No',
            ];
        }
        return response()->json([
            'status' => true,
            'detalle_servicio_paciente' => $detalle
        ]);
    }
}
