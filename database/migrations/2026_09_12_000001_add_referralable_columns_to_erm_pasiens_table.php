<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('erm_pasiens', function (Blueprint $table) {
            if (!Schema::hasColumn('erm_pasiens', 'referralable_type')) {
                $table->string('referralable_type', 50)->nullable()->after('referral_detail');
            }

            if (!Schema::hasColumn('erm_pasiens', 'referralable_id')) {
                $table->string('referralable_id', 50)->nullable()->after('referralable_type');
            }

            try {
                $table->index(['referralable_type', 'referralable_id'], 'erm_pasiens_referralable_index');
            } catch (Throwable $e) {
                // Ignore if the index already exists in this environment.
            }
        });

        if (!Schema::hasColumn('erm_pasiens', 'referral_type') || !Schema::hasColumn('erm_pasiens', 'referral_detail')) {
            return;
        }

        if (Schema::hasColumn('erm_pasiens', 'referral_pasien_id')) {
            DB::table('erm_pasiens')
                ->where('referral_type', 'other_pasien')
                ->whereNotNull('referral_pasien_id')
                ->where(function ($query) {
                    $query->whereNull('referral_detail')
                        ->orWhereRaw("TRIM(referral_detail) = ''");
                })
                ->update([
                    'referral_detail' => DB::raw('referral_pasien_id'),
                ]);

            DB::table('erm_pasiens')
                ->where('referral_type', 'other_pasien')
                ->whereNotNull('referral_pasien_id')
                ->update([
                    'referralable_type' => 'pasien',
                    'referralable_id' => DB::raw('referral_pasien_id'),
                ]);
        }

        if (Schema::hasTable('marketing_event')) {
            DB::statement(
                "UPDATE erm_pasiens ep
                INNER JOIN marketing_event me ON me.kode_event = ep.referral_detail
                SET ep.referralable_type = ?, ep.referralable_id = CAST(me.id AS CHAR)
                WHERE ep.referral_type = ?
                  AND ep.referral_detail IS NOT NULL
                  AND TRIM(ep.referral_detail) <> ''
                  AND (ep.referralable_type IS NULL OR ep.referralable_id IS NULL)",
                ['marketing_event', 'event']
            );
        }
    }

    public function down(): void
    {
        Schema::table('erm_pasiens', function (Blueprint $table) {
            try {
                $table->dropIndex('erm_pasiens_referralable_index');
            } catch (Throwable $e) {
                // Ignore if the index does not exist.
            }

            if (Schema::hasColumn('erm_pasiens', 'referralable_id')) {
                $table->dropColumn('referralable_id');
            }

            if (Schema::hasColumn('erm_pasiens', 'referralable_type')) {
                $table->dropColumn('referralable_type');
            }
        });
    }
};