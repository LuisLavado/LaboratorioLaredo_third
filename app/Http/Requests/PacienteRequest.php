<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dni' => 'required|string|max:8|unique:pacientes,dni,' . $this->paciente?->id,
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'fecha_nacimiento' => 'required|date',
            'celular' => 'nullable|string|max:20',
            'historia_clinica' => 'required|string|max:20|unique:pacientes,historia_clinica,' . $this->paciente?->id,
            'sexo' => 'required|in:masculino,femenino',
            'edad_gestacional' => 'nullable|integer|min:0|max:42',
            'solicitud_con_datos_completos' => 'boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'dni.required' => 'El DNI es obligatorio',
            'dni.unique' => 'El DNI ya está registrado',
            'nombres.required' => 'Los nombres son obligatorios',
            'apellidos.required' => 'Los apellidos son obligatorios',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria',
            'historia_clinica.required' => 'La historia clínica es obligatoria',
            'historia_clinica.unique' => 'La historia clínica ya está registrada',
            'sexo.required' => 'El sexo es obligatorio',
            'edad_gestacional.integer' => 'La edad gestacional debe ser un número entero',
            'edad_gestacional.min' => 'La edad gestacional no puede ser menor a 0',
            'edad_gestacional.max' => 'La edad gestacional no puede ser mayor a 42'
        ];
    }
}