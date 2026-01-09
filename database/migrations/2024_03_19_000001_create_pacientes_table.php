<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('dni')->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->date('fecha_nacimiento');
            $table->string('celular')->nullable();
            $table->string('historia_clinica')->unique();
            $table->enum('sexo', ['masculino', 'femenino']);
            $table->unsignedInteger('edad')->nullable();
            $table->unsignedInteger('edad_gestacional')->nullable();
            $table->timestamps();
            $table->dateTime('fecha_registro')->useCurrent();
            $table->unsignedInteger('codigo')->nullable();
            $table->boolean('solicitud_con_datos_completos')->default(false);
        });

        // Crear trigger para generar el código automáticamente
        DB::unprepared('
            CREATE TRIGGER before_paciente_insert
            BEFORE INSERT ON pacientes
            FOR EACH ROW
            BEGIN
                IF NEW.codigo IS NULL THEN
                    SET NEW.codigo = (SELECT IFNULL(MAX(codigo), 0) + 1 FROM pacientes);
                END IF;
                IF NEW.fecha_registro IS NULL THEN
                    SET NEW.fecha_registro = NOW();
                END IF;
            END
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS before_paciente_insert');
        Schema::dropIfExists('pacientes');
    }
};