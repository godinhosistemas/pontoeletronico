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
        Schema::create('payment_webhooks', function (Blueprint $table) {
            $table->id();

            // Gateway que enviou o webhook
            $table->foreignId('payment_gateway_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');

            // Identificação
            $table->string('event_id')->nullable(); // ID do evento no gateway
            $table->string('event_type'); // payment.created, payment.approved, etc

            // Payload completo (JSON)
            $table->json('payload');

            // Processamento
            $table->enum('status', ['pending', 'processing', 'processed', 'failed'])->default('pending');
            $table->timestamp('processed_at')->nullable();

            // Tentativas de processamento
            $table->integer('attempts')->default(0);
            $table->text('error_message')->nullable();

            // Request info
            $table->string('ip_address')->nullable();
            $table->text('headers')->nullable();

            $table->timestamps();

            // Índices
            $table->index('payment_gateway_id');
            $table->index('payment_id');
            $table->index('event_type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_webhooks');
    }
};
