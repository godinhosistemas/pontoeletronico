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
        Schema::create('employee_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->enum('event_type', [
                'Admissão', 'Promoção', 'Transferência', 'Mudança de Cargo',
                'Aumento Salarial', 'Advertência', 'Suspensão', 'Férias',
                'Licença', 'Afastamento', 'Retorno', 'Demissão', 'Outros'
            ]);
            $table->string('title'); // Título do evento
            $table->text('description');
            $table->date('event_date');

            // Dados anteriores (para mudanças)
            $table->string('previous_position')->nullable();
            $table->string('previous_department')->nullable();
            $table->decimal('previous_salary', 10, 2)->nullable();

            // Dados novos (para mudanças)
            $table->string('new_position')->nullable();
            $table->string('new_department')->nullable();
            $table->decimal('new_salary', 10, 2)->nullable();

            // Outros dados
            $table->string('document_reference')->nullable(); // Referência de documento (ofício, memo, etc)
            $table->text('justification')->nullable(); // Justificativa
            $table->foreignId('registered_by')->nullable()->constrained('users'); // Quem registrou
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_history');
    }
};
