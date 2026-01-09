<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'nombre' => 'Doctor',
            'apellido' => 'Ejemplo',
            'email' => 'doctor@ejemplo.com',
            'password' => Hash::make('doctor12345'),
            'role' => 'doctor',
            'especialidad' => 'Medicina General',
            'colegiatura' => 'MED12345',
        ]);
    }
}
