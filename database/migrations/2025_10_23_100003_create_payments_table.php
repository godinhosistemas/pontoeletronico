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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Relacionamentos
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_gateway_id')->nullable()->constrained()->onDelete('set null');

            // Identificação
            $table->string('payment_number')->unique(); // Ex: PAY-2025-00001
            $table->string('transaction_id')->nullable(); // ID do gateway
            $table->string('authorization_code')->nullable();

            // Método de pagamento
            $table->enum('payment_method', [
                'boleto',
                'pix',
                'credit_card',
                'debit_card',
                'bank_transfer',
                'other'
            ]);

            // Valores
            $table->decimal('amount', 10, 2); // Valor pago
            $table->decimal('fee', 10, 2)->default(0); // Taxa do gateway
            $table->decimal('net_amount', 10, 2); // Valor líquido

            // Status
            $table->enum('status', [
                'pending',      // Pendente/Aguardando
                'processing',   // Processando
                'approved',     // Aprovado
                'completed',    // Concluído
                'failed',       // Falhou
                'cancelled',    // Cancelado
                'refunded',     // Reembolsado
                'chargeback'    // Estornado
            ])->default('pending');

            // Datas
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            // Informações específicas do método
            // Boleto
            $table->string('boleto_url')->nullable();
            $table->string('boleto_barcode')->nullable();
            $table->string('boleto_digitable_line')->nullable();

            // PIX
            $table->text('pix_qrcode')->nullable(); // QR Code em base64
            $table->string('pix_qrcode_text')->nullable(); // Código copia e cola
            $table->string('pix_txid')->nullable();

            // Cartão
            $table->string('card_brand')->nullable(); // visa, mastercard, etc
            $table->string('card_last4')->nullable(); // Últimos 4 dígitos
            $table->integer('installments')->nullable(); // Parcelas

            // Resposta do gateway (JSON)
            $table->json('gateway_response')->nullable();

            // Mensagens de erro
            $table->text('error_message')->nullable();
            $table->string('error_code')->nullable();

            // Metadados
            $table->json('metadata')->nullable();

            // IP e User Agent (para auditoria)
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('invoice_id');
            $table->index('tenant_id');
            $table->index('payment_gateway_id');
            $table->index('payment_number');
            $table->index('transaction_id');
            $table->index('status');
            $table->index('payment_method');
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
