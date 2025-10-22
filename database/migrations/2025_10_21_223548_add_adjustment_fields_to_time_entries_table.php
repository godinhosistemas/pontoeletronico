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
        Schema::table('time_entries', function (Blueprint $table) {
            // Indica se houve ajuste manual
            $table->boolean('has_adjustment')->default(false)->after('approved_at');

            // Horários originais (antes do ajuste)
            $table->time('original_clock_in')->nullable()->after('has_adjustment');
            $table->time('original_clock_out')->nullable()->after('original_clock_in');
            $table->time('original_lunch_start')->nullable()->after('original_clock_out');
            $table->time('original_lunch_end')->nullable()->after('original_lunch_start');

            // Horários ajustados (após ajuste manual)
            $table->time('adjusted_clock_in')->nullable()->after('original_lunch_end');
            $table->time('adjusted_clock_out')->nullable()->after('adjusted_clock_in');
            $table->time('adjusted_lunch_start')->nullable()->after('adjusted_clock_out');
            $table->time('adjusted_lunch_end')->nullable()->after('adjusted_lunch_start');

            // Justificativa do ajuste
            $table->text('adjustment_reason')->nullable()->after('adjusted_lunch_end');

            // Quem fez o ajuste
            $table->foreignId('adjusted_by')->nullable()->after('adjustment_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('adjusted_at')->nullable()->after('adjusted_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropForeign(['adjusted_by']);
            $table->dropColumn([
                'has_adjustment',
                'original_clock_in',
                'original_clock_out',
                'original_lunch_start',
                'original_lunch_end',
                'adjusted_clock_in',
                'adjusted_clock_out',
                'adjusted_lunch_start',
                'adjusted_lunch_end',
                'adjustment_reason',
                'adjusted_by',
                'adjusted_at',
            ]);
        });
    }
};
