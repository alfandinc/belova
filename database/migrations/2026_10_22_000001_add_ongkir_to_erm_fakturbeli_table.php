<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ongkir / biaya lain on a faktur (Rupiah). Part of the faktur total, and spread over the items by
     * value on approval: HNA = harga per unit + its share of the ongkir, HPP = HNA + PPN.
     */
    public function up(): void
    {
        if (Schema::hasTable('erm_fakturbeli') && !Schema::hasColumn('erm_fakturbeli', 'ongkir')) {
            Schema::table('erm_fakturbeli', function (Blueprint $table) {
                $table->decimal('ongkir', 15, 2)->default(0)->after('global_pajak');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('erm_fakturbeli', 'ongkir')) {
            Schema::table('erm_fakturbeli', function (Blueprint $table) {
                $table->dropColumn('ongkir');
            });
        }
    }
};
