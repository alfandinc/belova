<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('erm_pasiens', function (Blueprint $table) {
            $table->enum('status_pasien', ['Regular', 'VIP', 'Familia', 'Black Card'])->default('Regular')->after('instagram');
        });
    }

    public function down(): void
    {
        Schema::table('erm_pasiens', function (Blueprint $table) {
            $table->dropColumn('status_pasien');
        });
    }
};
