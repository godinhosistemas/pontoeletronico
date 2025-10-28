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
        Schema::create('employee_compliance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->enum('compliance_type', [
                'Exame Médico', 'Treinamento', 'Certificação', 'Renovação de Documento',
                'Renovação de CNH', 'Vacina', 'Outros'
            ]);
            $table->string('title'); // Ex: "Exame Periódico", "Treinamento NR-10"
            $table->text('description')->nullable();
            $table->date('due_date'); // Data de vencimento
            $table->date('completion_date')->nullable(); // Data de conclusão
            $table->enum('status', ['Pendente', 'Em Dia', 'Vencido', 'Dispensado'])->default('Pendente');
            $table->enum('priority', ['Baixa', 'Normal', 'Alta', 'Urgente'])->default('Normal');
            $table->boolean('send_notification')->default(true); // Enviar notificação de vencimento
            $table->integer('notification_days_before')->default(30); // Dias antes para notificar
            $table->text('notes')->nullable();
            $table->string('document_reference')->nullable(); // Referência do documento relacionado
            $table->foreignId('registered_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_compliance');
    }
};
