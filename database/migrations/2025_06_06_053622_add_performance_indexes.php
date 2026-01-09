<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar índices para mejorar el rendimiento de las consultas más frecuentes
        // Solo agregar si no existen ya

        // Índices para la tabla detallesolicitud (los más importantes para el rendimiento)
        Schema::table('detallesolicitud', function (Blueprint $table) {
            // Verificar si el índice no existe antes de crearlo
            $indexes = DB::select("SHOW INDEX FROM detallesolicitud WHERE Key_name = 'idx_detalles_solicitud_estado'");
            if (empty($indexes)) {
                $table->index(['solicitud_id', 'estado'], 'idx_detalles_solicitud_estado');
            }

            $indexes = DB::select("SHOW INDEX FROM detallesolicitud WHERE Key_name = 'idx_detalles_examen_estado'");
            if (empty($indexes)) {
                $table->index(['examen_id', 'estado'], 'idx_detalles_examen_estado');
            }
        });

        // Índices para la tabla resultados_examen
        Schema::table('resultados_examen', function (Blueprint $table) {
            $indexes = DB::select("SHOW INDEX FROM resultados_examen WHERE Key_name = 'idx_resultados_detalle_solicitud'");
            if (empty($indexes)) {
                $table->index('detalle_solicitud_id', 'idx_resultados_detalle_solicitud');
            }
        });

        // Índices para la tabla valores_resultado
        Schema::table('valores_resultado', function (Blueprint $table) {
            $indexes = DB::select("SHOW INDEX FROM valores_resultado WHERE Key_name = 'idx_valores_detalle_solicitud'");
            if (empty($indexes)) {
                $table->index('detalle_solicitud_id', 'idx_valores_detalle_solicitud');
            }
        });

        // Índices para la tabla pacientes (importante para búsquedas)
        Schema::table('pacientes', function (Blueprint $table) {
            $indexes = DB::select("SHOW INDEX FROM pacientes WHERE Key_name = 'idx_pacientes_dni'");
            if (empty($indexes)) {
                $table->index('dni', 'idx_pacientes_dni');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar índices creados
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropIndex('idx_pacientes_dni');
        });

        Schema::table('valores_resultado', function (Blueprint $table) {
            $table->dropIndex('idx_valores_detalle_solicitud');
        });

        Schema::table('resultados_examen', function (Blueprint $table) {
            $table->dropIndex('idx_resultados_detalle_solicitud');
        });

        Schema::table('detallesolicitud', function (Blueprint $table) {
            $table->dropIndex('idx_detalles_solicitud_estado');
            $table->dropIndex('idx_detalles_examen_estado');
        });
    }
};
