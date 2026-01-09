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
        Schema::create('detallesolicitud', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes')->onDelete('cascade');
            $table->foreignId('examen_id')->constrained('examenes')->onDelete('cascade');
            $table->string('estado')->default('pendiente'); // pendiente, en_proceso, completado
            
            $table->text('observaciones')->nullable();
            $table->timestamp('fecha_resultado')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users');
            $table->timestamps();
            
            // Evitar duplicados de exámenes en la misma solicitud
            $table->unique(['solicitud_id', 'examen_id']);
        });
        

        Schema::table('solicitudes', function (Blueprint $table) {
         
            if (Schema::hasColumn('solicitudes', 'examen_id')) {
                $table->dropForeign(['examen_id']);
                $table->dropColumn('examen_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    
        
        Schema::dropIfExists('detallesolicitud');
    }
}; 