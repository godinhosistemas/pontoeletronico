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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('cpf', 14)->unique();
            $table->string('registration_number')->unique(); // Matrícula
            $table->string('phone', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('position')->nullable(); // Cargo
            $table->string('department')->nullable(); // Departamento
            $table->date('admission_date'); // Data de admissão
            $table->date('termination_date')->nullable(); // Data de demissão
            $table->decimal('salary', 10, 2)->nullable();
            $table->string('photo')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zip_code', 9)->nullable();
            $table->enum('status', ['active', 'inactive', 'vacation', 'leave'])->default('active');
            $table->json('work_schedule')->nullable(); // Horário de trabalho
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
