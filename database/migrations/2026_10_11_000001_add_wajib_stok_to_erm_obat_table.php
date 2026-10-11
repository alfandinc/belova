<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Obat "Wajib Ada" (mis. obat emergency): forecast keeps at least a minimum stock even when
 * the obat is rarely or never used. Replaces per-batch min/max for these exceptions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_obat', function (Blueprint $t) {
            $t->boolean('wajib_stok')->default(false)->after('is_favorite');
        });
    }

    public function down(): void
    {
        Schema::table('erm_obat', function (Blueprint $t) {
            $t->dropColumn('wajib_stok');
        });
    }
};
