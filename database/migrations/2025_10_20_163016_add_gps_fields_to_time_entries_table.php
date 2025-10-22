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
            // Coordenadas GPS capturadas no momento do registro
            $table->decimal('gps_latitude', 10, 8)->nullable()->after('location')->comment('Latitude GPS registrada');
            $table->decimal('gps_longitude', 11, 8)->nullable()->after('gps_latitude')->comment('Longitude GPS registrada');
            $table->decimal('gps_accuracy', 10, 2)->nullable()->after('gps_longitude')->comment('Precisão do GPS em metros');
            $table->integer('distance_meters')->nullable()->after('gps_accuracy')->comment('Distância do local permitido em metros');
            $table->boolean('gps_validated')->default(false)->after('distance_meters')->comment('Se a localização GPS foi validada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn(['gps_latitude', 'gps_longitude', 'gps_accuracy', 'distance_meters', 'gps_validated']);
        });
    }
};
