<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateExistingCompletedAtValues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Actualizar los registros existentes que tienen estado 'completado'
        // Si tienen fecha_resultado, usar esa fecha, de lo contrario usar la fecha de actualización
        DB::statement("
            UPDATE detallesolicitud 
            SET completed_at = COALESCE(fecha_resultado, updated_at) 
            WHERE estado = 'completado' AND completed_at IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No es necesario revertir esta migración
    }
}
