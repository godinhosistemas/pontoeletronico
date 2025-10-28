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
            // Campos para gestão de horas extras
            $table->enum('overtime_type', ['none', 'normal', 'night', 'holiday'])->default('none')->after('type');
            $table->decimal('overtime_hours', 5, 2)->default(0)->after('overtime_type');
            $table->decimal('overtime_percentage', 5, 2)->nullable()->after('overtime_hours');
            $table->boolean('is_night_shift')->default(false)->after('overtime_percentage');
            $table->boolean('clt_limit_validated')->default(false)->after('is_night_shift');
            $table->boolean('clt_limit_exceeded')->default(false)->after('clt_limit_validated');
            $table->text('clt_violation_notes')->nullable()->after('clt_limit_exceeded');

            // Índices
            $table->index('overtime_type');
            $table->index(['employee_id', 'overtime_hours']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropIndex(['overtime_type']);
            $table->dropIndex(['employee_id', 'overtime_hours']);

            $table->dropColumn([
                'overtime_type',
                'overtime_hours',
                'overtime_percentage',
                'is_night_shift',
                'clt_limit_validated',
                'clt_limit_exceeded',
                'clt_violation_notes',
            ]);
        });
    }
};
