<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link an R&D product to the Master Obat record it was released as (one product = one obat).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rnd_produk', function (Blueprint $table) {
            $table->unsignedBigInteger('obat_id')->nullable()->unique()->after('id');
            $table->foreign('obat_id', 'rnd_produk_obat_id_foreign')
                ->references('id')->on('erm_obat')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rnd_produk', function (Blueprint $table) {
            $table->dropForeign('rnd_produk_obat_id_foreign');
            $table->dropUnique(['obat_id']);
            $table->dropColumn('obat_id');
        });
    }
};
