<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valores_resultado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detalle_solicitud_id')->constrained('detallesolicitud')->onDelete('cascade');
            $table->foreignId('campo_examen_id')->constrained('campos_examen')->onDelete('cascade');
            $table->text('valor'); // El valor ingresado
            $table->text('observaciones')->nullable();
            $table->boolean('fuera_rango')->default(false); // Si está fuera del valor de referencia
            $table->timestamps();
            
            $table->unique(['detalle_solicitud_id', 'campo_examen_id']);
            $table->index('detalle_solicitud_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valores_resultado');
    }
};
