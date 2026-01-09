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
        Schema::create('temporary_files', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique(); // token único para descarga
            $table->string('file_path'); // ruta del archivo
            $table->string('file_type'); // pdf, excel
            $table->string('original_name'); // nombre original del archivo
            $table->integer('file_size'); // tamaño en bytes
            $table->timestamp('expires_at'); // cuándo expira el enlace
            $table->integer('download_count')->default(0); // veces descargado
            $table->integer('max_downloads')->default(5); // máximo de descargas
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Índices
            $table->index(['token']);
            $table->index(['expires_at']);
            $table->index(['created_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temporary_files');
    }
};
