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
        Schema::create('report_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('report_type'); // general, exams, services, doctors, etc.
            $table->string('recipient_phone'); // phone number for WhatsApp
            $table->string('file_path'); // path to generated file
            $table->string('file_type'); // pdf, excel
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('message')->nullable(); // custom message
            $table->json('report_params')->nullable(); // filters, dates, etc.
            $table->string('twilio_sid')->nullable(); // Twilio message SID
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Índices para búsquedas eficientes
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_notifications');
    }
};
