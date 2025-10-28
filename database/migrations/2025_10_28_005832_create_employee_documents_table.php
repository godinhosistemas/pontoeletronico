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
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->enum('document_type', [
                'RG', 'CPF', 'CTPS', 'CNH', 'Título de Eleitor',
                'Certificado Militar', 'Comprovante de Residência',
                'Certidão de Nascimento', 'Certidão de Casamento',
                'Diploma', 'Certificado', 'Exame Admissional', 'ASO',
                'Atestado Médico', 'Contrato de Trabalho', 'Termo de Rescisão',
                'Foto', 'Outros'
            ]);
            $table->string('document_name');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_extension');
            $table->integer('file_size'); // em bytes
            $table->date('issue_date')->nullable(); // Data de emissão
            $table->date('expiry_date')->nullable(); // Data de validade
            $table->text('description')->nullable();
            $table->boolean('is_verified')->default(false); // Documento verificado
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
