<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, clean up existing data - convert any non-empty strings to 1, empty/null to 0
        DB::table('erm_spk_tindakan_items')->update([
            'sbk' => DB::raw("CASE WHEN sbk IS NOT NULL AND sbk != '' THEN 1 ELSE 0 END"),
            'sba' => DB::raw("CASE WHEN sba IS NOT NULL AND sba != '' THEN 1 ELSE 0 END"),
            'sdc' => DB::raw("CASE WHEN sdc IS NOT NULL AND sdc != '' THEN 1 ELSE 0 END"),
            'sdk' => DB::raw("CASE WHEN sdk IS NOT NULL AND sdk != '' THEN 1 ELSE 0 END"),
            'sdl' => DB::raw("CASE WHEN sdl IS NOT NULL AND sdl != '' THEN 1 ELSE 0 END"),
        ]);

        Schema::table('erm_spk_tindakan_items', function (Blueprint $table) {
            // Modify columns from string to boolean
            $table->boolean('sbk')->default(false)->change(); // Sebelum Kalibrasi
            $table->boolean('sba')->default(false)->change(); // Sebelum Antisepsi
            $table->boolean('sdc')->default(false)->change(); // Sebelum Desinfeksi Cuci
            $table->boolean('sdk')->default(false)->change(); // Sebelum Desinfeksi Kering
            $table->boolean('sdl')->default(false)->change(); // Sebelum Desinfeksi Lain
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erm_spk_tindakan_items', function (Blueprint $table) {
            // Revert columns back to string
            $table->string('sbk')->nullable()->change(); // Sebelum Kalibrasi
            $table->string('sba')->nullable()->change(); // Sebelum Antisepsi
            $table->string('sdc')->nullable()->change(); // Sebelum Desinfeksi Cuci
            $table->string('sdk')->nullable()->change(); // Sebelum Desinfeksi Kering
            $table->string('sdl')->nullable()->change(); // Sebelum Desinfeksi Lain
        });
    }
};
