<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('erm_pasiens', 'referral_pasien_id')) {
            return;
        }

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
            ->where(function ($query) {
                $query->whereNull('referralable_type')
                    ->orWhereNull('referralable_id');
            })
            ->update([
                'referralable_type' => 'pasien',
                'referralable_id' => DB::raw('referral_pasien_id'),
            ]);

        Schema::table('erm_pasiens', function (Blueprint $table) {
            try {
                $table->dropForeign(['referral_pasien_id']);
            } catch (Throwable $e) {
                // Ignore if the foreign key does not exist.
            }

            $table->dropColumn('referral_pasien_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('erm_pasiens', 'referral_pasien_id')) {
            return;
        }

        Schema::table('erm_pasiens', function (Blueprint $table) {
            $table->string('referral_pasien_id', 6)->nullable()->after('referral_type');
        });

        DB::table('erm_pasiens')
            ->where('referral_type', 'other_pasien')
            ->where('referralable_type', 'pasien')
            ->whereNotNull('referralable_id')
            ->update([
                'referral_pasien_id' => DB::raw('referralable_id'),
            ]);

        Schema::table('erm_pasiens', function (Blueprint $table) {
            try {
                $table->foreign('referral_pasien_id')
                    ->references('id')
                    ->on('erm_pasiens')
                    ->nullOnDelete();
            } catch (Throwable $e) {
                // Ignore if the foreign key already exists in this environment.
            }
        });
    }
};