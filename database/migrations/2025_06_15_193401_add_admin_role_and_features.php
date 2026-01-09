<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar rol de administrador si no existe
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('doctor', 'laboratorio', 'administrador') NOT NULL DEFAULT 'doctor'");

        // Crear tabla para solicitudes de cambios
        Schema::create('solicitudes_cambio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_solicitante_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('usuario_aprobador_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('tipo_cambio', ['solicitud', 'resultado', 'paciente', 'examen', 'otro']);
            $table->string('entidad_tipo'); // Modelo afectado (Solicitud, Paciente, etc.)
            $table->unsignedBigInteger('entidad_id'); // ID del registro afectado
            $table->json('datos_originales'); // Datos antes del cambio
            $table->json('datos_propuestos'); // Datos propuestos
            $table->text('motivo'); // Motivo del cambio
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->text('comentario_aprobador')->nullable();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->timestamps();

            $table->index(['estado', 'tipo_cambio']);
            $table->index(['entidad_tipo', 'entidad_id']);
        });

        // Crear tabla para sesiones de usuarios (tracking online)
        Schema::create('sesiones_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique();
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->timestamp('ultima_actividad');
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'activa']);
            $table->index('ultima_actividad');
        });

        // Agregar campos de auditoría a usuarios
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('ultimo_acceso')->nullable()->after('updated_at');
            $table->boolean('activo')->default(true)->after('ultimo_acceso');
            $table->timestamp('fecha_desactivacion')->nullable()->after('activo');
            $table->text('motivo_desactivacion')->nullable()->after('fecha_desactivacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ultimo_acceso', 'activo', 'fecha_desactivacion', 'motivo_desactivacion']);
        });

        Schema::dropIfExists('sesiones_usuario');
        Schema::dropIfExists('solicitudes_cambio');

        // Revertir enum de roles
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('doctor', 'laboratorio') NOT NULL DEFAULT 'doctor'");
    }
};
