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
        Schema::table('users', function (Blueprint $table) {
            // Modify the role column to have specific roles
            $table->string('role')->default('laboratorio')->change();
            
            // Add additional fields for doctors
            $table->string('especialidad')->nullable();
            $table->string('colegiatura')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->change();
            $table->dropColumn(['especialidad', 'colegiatura']);
        });
    }
};
