<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examenes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            // Relación con la tabla de categorías
            $table->foreign('categoria_id')->references('id')->on('categorias')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examenes');
        
        // No eliminamos categorías aquí para evitar conflictos con la migración específica de categorías
    }
};