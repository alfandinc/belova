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
        Schema::table('erm_klinik', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('nama');
            $table->string('color', 20)->nullable()->after('logo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erm_klinik', function (Blueprint $table) {
            $table->dropColumn(['logo', 'color']);
        });
    }
};
