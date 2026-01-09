<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campos_examen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_id')->constrained('examenes')->onDelete('cascade');
            $table->string('nombre'); // Ej: "Hemoglobina", "Colesterol Total"
            $table->string('tipo')->default('text'); // text, number, select, boolean, textarea
            $table->string('unidad')->nullable(); // mg/dL, g/dL, %, etc.
            $table->text('valor_referencia')->nullable(); // Valores normales
            $table->text('opciones')->nullable(); // Para campos select (JSON)
            $table->boolean('requerido')->default(true);
            $table->integer('orden')->default(0); // Para ordenar campos
            $table->string('seccion')->nullable(); // Ej: "SERIE ROJA", "PERFIL LIPÍDICO"
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->index(['examen_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campos_examen');
    }
};
