<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si ya existe un administrador
        $adminExists = \App\Models\User::where('role', 'administrador')->exists();

        if (!$adminExists) {
            \App\Models\User::create([
                'nombre' => 'Admin',
                'apellido' => 'Sistema',
                'email' => 'admin@laboratorio.com',
                'password' => \Hash::make('admin123'),
                'role' => 'administrador',
                'activo' => true,
                'email_verified_at' => now()
            ]);

            $this->command->info('Usuario administrador creado:');
            $this->command->info('Email: admin@laboratorio.com');
            $this->command->info('Password: admin123');
        } else {
            $this->command->info('Ya existe un usuario administrador en el sistema.');
        }
    }
}
