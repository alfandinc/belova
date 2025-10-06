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
        Schema::table('finance_retur_pembelian_items', function (Blueprint $table) {
            $table->decimal('percentage_cut', 5, 2)->default(0)->after('unit_price');
            $table->decimal('original_unit_price', 15, 2)->after('unit_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_retur_pembelian_items', function (Blueprint $table) {
            $table->dropColumn(['percentage_cut', 'original_unit_price']);
        });
    }
};
