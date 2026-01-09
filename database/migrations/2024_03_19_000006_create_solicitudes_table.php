<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->time('hora');
            $table->unsignedBigInteger('servicio_id')->nullable();
            $table->string('numero_recibo')->nullable();
            $table->boolean('rdr')->default(false);
            $table->boolean('sis')->default(false);
            $table->boolean('exon')->default(false);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('paciente_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // Relación con la tabla de servicios
            $table->foreign('servicio_id')->references('id')->on('servicios')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};