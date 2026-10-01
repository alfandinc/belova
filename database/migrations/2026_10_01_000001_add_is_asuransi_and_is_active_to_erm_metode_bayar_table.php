<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_metode_bayar', function (Blueprint $table) {
            if (!Schema::hasColumn('erm_metode_bayar', 'is_asuransi')) {
                // false = Umum (bayar sendiri), true = Asuransi / penjamin
                $table->boolean('is_asuransi')->default(false)->after('nama');
            }

            if (!Schema::hasColumn('erm_metode_bayar', 'is_active')) {
                // Inactive metode bayar are hidden from dropdowns but kept for old visits
                $table->boolean('is_active')->default(true)->after('is_asuransi');
            }
        });

        // Billing used to treat everything except "Umum" as Asuransi; keep that split.
        DB::table('erm_metode_bayar')
            ->whereRaw('LOWER(TRIM(nama)) != ?', ['umum'])
            ->update(['is_asuransi' => true]);
    }

    public function down(): void
    {
        Schema::table('erm_metode_bayar', function (Blueprint $table) {
            $table->dropColumn(['is_asuransi', 'is_active']);
        });
    }
};
