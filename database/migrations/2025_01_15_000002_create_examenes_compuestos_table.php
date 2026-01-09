<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examenes_compuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_padre_id')->constrained('examenes')->onDelete('cascade');
            $table->foreignId('examen_hijo_id')->constrained('examenes')->onDelete('cascade');
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->unique(['examen_padre_id', 'examen_hijo_id']);
            $table->index(['examen_padre_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examenes_compuestos');
    }
};
