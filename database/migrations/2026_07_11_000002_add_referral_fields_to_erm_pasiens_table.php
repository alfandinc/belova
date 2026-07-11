<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('erm_pasiens', function (Blueprint $table) {
            if (!Schema::hasColumn('erm_pasiens', 'referral_type')) {
                $table->string('referral_type', 30)->nullable()->after('identity_number');
            }

            if (!Schema::hasColumn('erm_pasiens', 'referral_pasien_id')) {
                $table->string('referral_pasien_id', 6)->nullable()->after('referral_type');
            }

            if (!Schema::hasColumn('erm_pasiens', 'referral_detail')) {
                $table->string('referral_detail')->nullable()->after('referral_pasien_id');
            }
        });

        if (!Schema::hasColumn('erm_pasiens', 'referral_pasien_id')) {
            return;
        }

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

    public function down(): void
    {
        Schema::table('erm_pasiens', function (Blueprint $table) {
            try {
                $table->dropForeign(['referral_pasien_id']);
            } catch (Throwable $e) {
                // Ignore if the foreign key does not exist.
            }

            if (Schema::hasColumn('erm_pasiens', 'referral_detail')) {
                $table->dropColumn('referral_detail');
            }

            if (Schema::hasColumn('erm_pasiens', 'referral_pasien_id')) {
                $table->dropColumn('referral_pasien_id');
            }

            if (Schema::hasColumn('erm_pasiens', 'referral_type')) {
                $table->dropColumn('referral_type');
            }
        });
    }
};