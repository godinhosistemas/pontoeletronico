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
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();

            // Informações básicas
            $table->string('name'); // Ex: "Mercado Pago", "Asaas", "Pagarme"
            $table->string('slug')->unique(); // Ex: "mercadopago", "asaas"
            $table->text('description')->nullable();

            // Provider/Driver
            $table->string('provider'); // mercadopago, asaas, pagarme, stripe, etc

            // Status
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false); // Gateway padrão

            // Configurações (JSON criptografado)
            $table->text('api_key')->nullable(); // Criptografado
            $table->text('api_secret')->nullable(); // Criptografado
            $table->text('public_key')->nullable(); // Para chaves públicas
            $table->json('settings')->nullable(); // Configurações extras

            // Métodos de pagamento suportados
            $table->json('supported_methods')->nullable(); // ['boleto', 'pix', 'credit_card']

            // Ambiente
            $table->enum('environment', ['sandbox', 'production'])->default('sandbox');

            // URLs de callback/webhook
            $table->string('webhook_url')->nullable();
            $table->string('webhook_secret')->nullable();

            // Taxas e limites
            $table->decimal('fee_percentage', 5, 2)->nullable(); // Ex: 3.99%
            $table->decimal('fee_fixed', 10, 2)->nullable(); // Taxa fixa por transação
            $table->decimal('min_amount', 10, 2)->nullable(); // Valor mínimo
            $table->decimal('max_amount', 10, 2)->nullable(); // Valor máximo

            // Metadados
            $table->json('metadata')->nullable();

            // Auditoria
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('provider');
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
