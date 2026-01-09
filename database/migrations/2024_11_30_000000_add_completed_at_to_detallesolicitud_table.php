<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompletedAtToDetallesolicitudTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('detallesolicitud', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('fecha_resultado');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('detallesolicitud', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
}
