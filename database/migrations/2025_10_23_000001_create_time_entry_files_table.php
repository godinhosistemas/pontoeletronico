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
        Schema::create('time_entry_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('generated_by')->nullable()->constrained('users')->onDelete('set null');

            // Tipo de arquivo: 'AFD' ou 'AEJ'
            $table->enum('file_type', ['AFD', 'AEJ']);

            // Período de apuração
            $table->date('period_start');
            $table->date('period_end');

            // Para AEJ: pode ser específico de um funcionário
            $table->foreignId('employee_id')->nullable()->constrained()->onDelete('cascade');

            // Caminhos dos arquivos
            $table->string('file_path'); // Arquivo principal (.txt)
            $table->string('signature_path')->nullable(); // Arquivo de assinatura (.p7s)

            // Metadados do arquivo
            $table->integer('total_records')->default(0); // Total de registros no arquivo
            $table->bigInteger('file_size')->default(0); // Tamanho em bytes
            $table->string('file_hash')->nullable(); // Hash SHA-256 do arquivo

            // Status da assinatura digital
            $table->boolean('is_signed')->default(false);
            $table->timestamp('signed_at')->nullable();
            $table->string('certificate_serial')->nullable(); // Número de série do certificado usado
            $table->string('certificate_issuer')->nullable(); // Emissor do certificado

            // Estatísticas (apenas para referência)
            $table->json('statistics')->nullable(); // JSON com estatísticas do período

            // Controle de downloads
            $table->integer('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['tenant_id', 'file_type', 'period_start', 'period_end']);
            $table->index(['employee_id', 'file_type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entry_files');
    }
};
