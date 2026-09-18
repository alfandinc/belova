<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('erm_pasiens')
            ->where('referral_type', 'other_pasien')
            ->update(['referral_type' => 'pasien']);

        DB::table('erm_pasiens')
            ->where('referral_type', 'lainnya')
            ->update(['referral_type' => 'partnership']);

        DB::table('erm_pasiens')
            ->whereNull('referral_type')
            ->orWhere('referral_type', '')
            ->update(['referral_type' => 'walk_in']);

        DB::table('erm_pasiens')
            ->where('referral_type', 'marketplace')
            ->whereNotNull('referral_detail')
            ->update(['referral_detail' => DB::raw('LOWER(TRIM(referral_detail))')]);

        DB::table('erm_pasiens')
            ->where('referral_type', 'social_media')
            ->whereNotNull('referral_detail')
            ->update(['referral_detail' => DB::raw('LOWER(TRIM(referral_detail))')]);
    }

    public function down(): void
    {
        DB::table('erm_pasiens')
            ->where('referral_type', 'pasien')
            ->update(['referral_type' => 'other_pasien']);

        DB::table('erm_pasiens')
            ->where('referral_type', 'partnership')
            ->update(['referral_type' => 'lainnya']);
    }
};