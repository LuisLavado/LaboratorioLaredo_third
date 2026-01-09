<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Crear admin con role 'administrador' para el panel de administración
        User::create([
            'nombre' => 'Administrador',
            'apellido' => 'del Sistema',
            'email' => 'admin@laboratorio.com',
            'password' => Hash::make('admin12345'),
            'role' => 'administrador', // Rol correcto para admin
        ]);
        
        // Crear usuario de laboratorio para pruebas
        User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Laboratorio',
            'email' => 'lab@laboratorio.com',
            'password' => Hash::make('lab12345'),
            'role' => 'laboratorio',
        ]);
    }
}