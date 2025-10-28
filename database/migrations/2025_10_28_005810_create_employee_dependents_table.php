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
        Schema::create('employee_dependents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('relationship', ['Cônjuge', 'Filho(a)', 'Pai', 'Mãe', 'Enteado(a)', 'Tutelado(a)', 'Outro']);
            $table->string('cpf', 14)->nullable();
            $table->date('birth_date');
            $table->enum('gender', ['M', 'F', 'Outro'])->nullable();
            $table->boolean('is_dependent_ir')->default(false); // Dependente IR
            $table->boolean('has_health_insurance')->default(false);
            $table->string('health_insurance_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_dependents');
    }
};
