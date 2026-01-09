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
        // Modificar el ENUM para incluir 'hibrido'
        DB::statement("ALTER TABLE examenes MODIFY COLUMN tipo ENUM('simple', 'compuesto', 'hibrido') DEFAULT 'simple'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Volver al ENUM original
        DB::statement("ALTER TABLE examenes MODIFY COLUMN tipo ENUM('simple', 'compuesto') DEFAULT 'simple'");
    }
};
