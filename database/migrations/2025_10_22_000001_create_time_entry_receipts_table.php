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
        Schema::create('time_entry_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('time_entry_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('action'); // clock_in, clock_out, lunch_start, lunch_end
            $table->dateTime('marked_at');
            $table->string('pdf_path')->nullable();
            $table->string('authenticator_code', 32); // Código autenticador único
            $table->ipAddress('ip_address')->nullable();
            $table->decimal('gps_latitude', 10, 8)->nullable();
            $table->decimal('gps_longitude', 11, 8)->nullable();
            $table->integer('gps_accuracy')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamp('available_until'); // 48 horas de disponibilidade
            $table->timestamps();

            $table->index(['employee_id', 'marked_at']);
            $table->index('authenticator_code');
            $table->index('available_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entry_receipts');
    }
};
