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
        Schema::table('tenants', function (Blueprint $table) {
            // Certificado Digital ICP-Brasil
            $table->string('certificate_path')->nullable()->after('status');
            $table->string('certificate_password_encrypted')->nullable()->after('certificate_path');
            $table->string('certificate_type')->nullable()->after('certificate_password_encrypted'); // A1 ou A3
            $table->string('certificate_issuer')->nullable()->after('certificate_type');
            $table->string('certificate_subject')->nullable()->after('certificate_issuer');
            $table->string('certificate_serial_number')->nullable()->after('certificate_subject');
            $table->dateTime('certificate_valid_from')->nullable()->after('certificate_serial_number');
            $table->dateTime('certificate_valid_until')->nullable()->after('certificate_valid_from');
            $table->json('certificate_metadata')->nullable()->after('certificate_valid_until');
            $table->boolean('certificate_active')->default(false)->after('certificate_metadata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'certificate_path',
                'certificate_password_encrypted',
                'certificate_type',
                'certificate_issuer',
                'certificate_subject',
                'certificate_serial_number',
                'certificate_valid_from',
                'certificate_valid_until',
                'certificate_metadata',
                'certificate_active',
            ]);
        });
    }
};
