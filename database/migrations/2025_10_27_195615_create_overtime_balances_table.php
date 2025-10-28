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
        Schema::create('overtime_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('period', 7); // Formato: YYYY-MM
            $table->decimal('earned_hours', 8, 2)->default(0); // Horas extras acumuladas
            $table->decimal('compensated_hours', 8, 2)->default(0); // Horas compensadas
            $table->decimal('balance_hours', 8, 2)->default(0); // Saldo atual
            $table->enum('status', ['active', 'expired', 'compensated'])->default('active');
            $table->date('expiration_date')->nullable(); // Data de expiração (CLT: 1 ano)
            $table->text('notes')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['employee_id', 'period']);
            $table->index(['tenant_id', 'period']);
            $table->index('status');
            $table->unique(['employee_id', 'period']); // Um registro por funcionário por período
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_balances');
    }
};
