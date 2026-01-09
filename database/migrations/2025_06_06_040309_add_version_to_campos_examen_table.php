<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('campos_examen', function (Blueprint $table) {
            $table->integer('version')->default(1)->after('activo');
            $table->timestamp('fecha_desactivacion')->nullable()->after('version');
            $table->text('motivo_cambio')->nullable()->after('fecha_desactivacion');

            // Índice compuesto para búsquedas eficientes
            $table->index(['examen_id', 'activo', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campos_examen', function (Blueprint $table) {
            $table->dropIndex(['examen_id', 'activo', 'version']);
            $table->dropColumn(['version', 'fecha_desactivacion', 'motivo_cambio']);
        });
    }
};
