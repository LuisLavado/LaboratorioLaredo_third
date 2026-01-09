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
        Schema::table('servicios', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('parent_id');
            $table->timestamp('fecha_desactivacion')->nullable()->after('activo');
            $table->text('motivo_desactivacion')->nullable()->after('fecha_desactivacion');
            
            // Índice para búsquedas eficientes
            $table->index(['activo', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->dropIndex(['activo', 'parent_id']);
            $table->dropColumn(['activo', 'fecha_desactivacion', 'motivo_desactivacion']);
        });
    }
};
