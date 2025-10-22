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
        Schema::table('employees', function (Blueprint $table) {
            // Coordenadas da empresa/local de trabalho permitido
            $table->decimal('allowed_latitude', 10, 8)->nullable()->after('face_descriptor')->comment('Latitude permitida para registro de ponto');
            $table->decimal('allowed_longitude', 11, 8)->nullable()->after('allowed_latitude')->comment('Longitude permitida para registro de ponto');
            $table->integer('geofence_radius')->default(100)->after('allowed_longitude')->comment('Raio permitido em metros (padrão: 100m)');
            $table->boolean('require_geolocation')->default(false)->after('geofence_radius')->comment('Se true, exige validação de localização');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['allowed_latitude', 'allowed_longitude', 'geofence_radius', 'require_geolocation']);
        });
    }
};
