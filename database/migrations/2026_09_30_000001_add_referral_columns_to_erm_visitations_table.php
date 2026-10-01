<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Visit-level (transaction) referral. erm_pasiens.referral_* stays as the
     * patient's source referral (how the patient first found the clinic).
     */
    public function up(): void
    {
        Schema::table('erm_visitations', function (Blueprint $table) {
            if (!Schema::hasColumn('erm_visitations', 'referral_type')) {
                $table->string('referral_type', 50)->nullable()->after('no_antrian');
            }

            if (!Schema::hasColumn('erm_visitations', 'referral_detail')) {
                $table->string('referral_detail')->nullable()->after('referral_type');
            }

            if (!Schema::hasColumn('erm_visitations', 'referralable_type')) {
                $table->string('referralable_type', 50)->nullable()->after('referral_detail');
            }

            if (!Schema::hasColumn('erm_visitations', 'referralable_id')) {
                $table->string('referralable_id', 50)->nullable()->after('referralable_type');
            }

            $table->index(['referral_type'], 'erm_visitations_referral_type_index');
            $table->index(['referralable_type', 'referralable_id'], 'erm_visitations_referralable_index');
        });

        // Backfill existing visits with the patient's source referral so historical
        // reports keep showing what they showed before this change.
        DB::statement("
            UPDATE erm_visitations v
            INNER JOIN erm_pasiens p ON p.id = v.pasien_id
            SET
                v.referral_type = COALESCE(p.referral_type, 'walk_in'),
                v.referral_detail = p.referral_detail,
                v.referralable_type = p.referralable_type,
                v.referralable_id = p.referralable_id
            WHERE v.referral_type IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('erm_visitations', function (Blueprint $table) {
            $table->dropIndex('erm_visitations_referral_type_index');
            $table->dropIndex('erm_visitations_referralable_index');
            $table->dropColumn(['referral_type', 'referral_detail', 'referralable_type', 'referralable_id']);
        });
    }
};
