<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('erm_obat', 'stok')) {
            Schema::table('erm_obat', function (Blueprint $table) {
                $table->dropColumn('stok');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('erm_obat', 'stok')) {
            Schema::table('erm_obat', function (Blueprint $table) {
                $table->integer('stok')->default(0);
            });
        }
    }
};
