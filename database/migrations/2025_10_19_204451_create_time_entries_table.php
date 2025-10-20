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
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->date('date'); // Data do registro
            $table->time('clock_in')->nullable(); // Entrada
            $table->time('clock_out')->nullable(); // Saída
            $table->time('lunch_start')->nullable(); // Início do almoço
            $table->time('lunch_end')->nullable(); // Fim do almoço
            $table->integer('total_minutes')->nullable(); // Total de minutos trabalhados
            $table->integer('total_hours')->nullable(); // Total de horas trabalhadas
            $table->enum('type', ['normal', 'overtime', 'absence', 'holiday', 'vacation'])->default('normal');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable(); // Observações
            $table->string('ip_address')->nullable(); // IP do registro
            $table->string('location')->nullable(); // Localização (geolocalização)
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // Índices para otimizar consultas
            $table->index(['employee_id', 'date']);
            $table->index(['tenant_id', 'date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
