<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados_examen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detalle_solicitud_id')->constrained('detallesolicitud')->onDelete('cascade');
            $table->foreignId('examen_id')->constrained('examenes')->onDelete('cascade');
            $table->string('nombre_parametro');
            $table->string('valor');
            $table->string('unidad')->nullable();
            $table->string('referencia')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_examen');
    }
};