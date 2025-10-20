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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('max_users')->default(10);
            $table->integer('max_employees')->default(50);
            $table->integer('billing_cycle_days')->default(30); // Ciclo de cobrança em dias
            $table->json('features')->nullable(); // Recursos do plano em JSON
            $table->boolean('is_active')->default(true);
            $table->integer('trial_days')->default(7); // Dias de teste grátis
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
