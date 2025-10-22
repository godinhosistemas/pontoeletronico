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
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Nome da jornada (ex: "Jornada Padrão 8h")
            $table->string('code')->unique(); // Código único (ex: "JOR-001")
            $table->text('description')->nullable(); // Descrição detalhada

            // Configuração semanal
            $table->integer('weekly_hours'); // Carga horária semanal (ex: 44)
            $table->integer('daily_hours'); // Horas diárias padrão (ex: 8)
            $table->integer('break_minutes')->default(60); // Intervalo padrão em minutos

            // Horários padrão (podem ser sobrescritos por dia)
            $table->time('default_start_time')->nullable(); // Horário de entrada padrão
            $table->time('default_end_time')->nullable(); // Horário de saída padrão
            $table->time('default_break_start')->nullable(); // Início do intervalo
            $table->time('default_break_end')->nullable(); // Fim do intervalo

            // Configuração por dia da semana (JSON)
            // Estrutura: {"monday": {"active": true, "start": "08:00", "end": "17:00", ...}, ...}
            $table->json('days_config')->nullable();

            // Tolerâncias
            $table->integer('tolerance_minutes_entry')->default(10); // Tolerância entrada
            $table->integer('tolerance_minutes_exit')->default(10); // Tolerância saída

            // Configurações extras
            $table->boolean('consider_holidays')->default(true); // Considera feriados
            $table->boolean('allow_overtime')->default(true); // Permite horas extras
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
        Schema::dropIfExists('work_schedules');
    }
};
