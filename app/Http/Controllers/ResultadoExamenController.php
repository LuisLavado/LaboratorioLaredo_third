<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ResultadoExamen;
use Illuminate\Http\Request;

class ResultadoExamenController extends Controller
{
    public function index()
    {
        return ResultadoExamen::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'detalle_solicitud_id' => 'required|exists:detallesolicitud,id',
            'examen_id' => 'required|exists:examenes,id',
            'nombre_parametro' => 'required|string',
            'valor' => 'required|string',
            'unidad' => 'nullable|string',
            'referencia' => 'nullable|string',
        ]);

        $resultado = ResultadoExamen::create($validated);
        return response()->json($resultado, 201);
    }

    public function show($id)
    {
        return ResultadoExamen::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $resultado = ResultadoExamen::findOrFail($id);
        $resultado->update($request->all());
        return response()->json($resultado);
    }

    public function destroy($id)
    {
        ResultadoExamen::destroy($id);
        return response()->json(null, 204);
    }
}