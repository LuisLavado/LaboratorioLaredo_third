<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('examenes', function (Blueprint $table) {
            $table->enum('tipo', ['simple', 'compuesto'])->default('simple')->after('activo');
            $table->text('instrucciones_muestra')->nullable()->after('tipo'); // Instrucciones para toma de muestra
            $table->string('metodo_analisis')->nullable()->after('instrucciones_muestra'); // Método de análisis
        });
    }

    public function down(): void
    {
        Schema::table('examenes', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'instrucciones_muestra', 'metodo_analisis']);
        });
    }
};
