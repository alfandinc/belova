<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_resepfarmasi', function (Blueprint $table) {
            $table->decimal('jumlah_racikan', 12, 3)->nullable()->after('jumlah');
        });
    }

    public function down(): void
    {
        Schema::table('erm_resepfarmasi', function (Blueprint $table) {
            $table->dropColumn('jumlah_racikan');
        });
    }
};