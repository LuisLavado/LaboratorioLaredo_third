<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admin user for the system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Verificar si ya existe un administrador
        $existingAdmin = \App\Models\User::where('role', 'administrador')->first();

        if ($existingAdmin) {
            $this->info('Ya existe un usuario administrador:');
            $this->info('Email: ' . $existingAdmin->email);
            return;
        }

        // Verificar si existe el usuario con el email
        $user = \App\Models\User::where('email', 'admin@laboratorio.com')->first();

        if ($user) {
            // Actualizar usuario existente a administrador
            $user->update([
                'role' => 'administrador',
                'activo' => true
            ]);
            $this->info('Usuario existente actualizado a administrador:');
        } else {
            // Crear nuevo usuario administrador
            $user = \App\Models\User::create([
                'nombre' => 'Admin',
                'apellido' => 'Sistema',
                'email' => 'admin@laboratorio.com',
                'password' => \Hash::make('admin123'),
                'role' => 'administrador',
                'activo' => true,
                'email_verified_at' => now()
            ]);
            $this->info('Nuevo usuario administrador creado:');
        }

        $this->info('Email: admin@laboratorio.com');
        $this->info('Password: admin123');
        $this->warn('¡Recuerda cambiar la contraseña después del primer login!');
    }
}
