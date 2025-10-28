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
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Nome do feriado (ex: "Natal", "Ano Novo")
            $table->date('date'); // Data do feriado
            $table->enum('type', ['national', 'state', 'municipal', 'custom'])->default('municipal');
            $table->string('city')->nullable(); // Cidade (para feriados municipais)
            $table->string('state', 2)->nullable(); // UF (para feriados estaduais)
            $table->boolean('is_recurring')->default(false); // Se repete todo ano
            $table->text('description')->nullable(); // Descrição/observações
            $table->boolean('is_active')->default(true); // Ativo/Inativo
            $table->timestamps();

            // Índices
            $table->index(['tenant_id', 'date']);
            $table->index(['tenant_id', 'is_active']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
