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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            // Tenant (cliente)
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            // Subscription relacionada
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');

            // Identificação
            $table->string('invoice_number')->unique(); // Ex: INV-2025-00001
            $table->string('reference')->nullable(); // Referência externa

            // Período de cobrança
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();

            // Valores
            $table->decimal('subtotal', 10, 2)->default(0); // Valor base
            $table->decimal('discount', 10, 2)->default(0); // Desconto
            $table->decimal('tax', 10, 2)->default(0); // Impostos
            $table->decimal('total', 10, 2)->default(0); // Total a pagar

            // Datas importantes
            $table->date('issue_date'); // Data de emissão
            $table->date('due_date'); // Data de vencimento
            $table->timestamp('paid_at')->nullable(); // Data de pagamento

            // Status
            $table->enum('status', [
                'draft',      // Rascunho
                'pending',    // Pendente/Em aberto
                'paid',       // Paga
                'overdue',    // Vencida
                'cancelled',  // Cancelada
                'refunded'    // Reembolsada
            ])->default('pending');

            // Detalhes da cobrança (JSON)
            $table->json('items')->nullable(); // Itens da fatura

            // Notas e observações
            $table->text('notes')->nullable();
            $table->text('payment_instructions')->nullable();

            // Metadados
            $table->json('metadata')->nullable();

            // Tentativas de cobrança
            $table->integer('payment_attempts')->default(0);
            $table->timestamp('last_payment_attempt')->nullable();

            // Lembretes enviados
            $table->integer('reminders_sent')->default(0);
            $table->timestamp('last_reminder_sent')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('tenant_id');
            $table->index('subscription_id');
            $table->index('invoice_number');
            $table->index('status');
            $table->index('due_date');
            $table->index(['tenant_id', 'status']);
            $table->index(['due_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
