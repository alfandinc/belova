<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Clinic the follow up is for. Nullable: old rows are filled by `php artisan followup:backfill-klinik`,
    // the ones it cannot decide stay empty ("Belum ditentukan").
    public function up(): void
    {
        if (Schema::hasColumn('marketing_follow_ups', 'klinik_id')) {
            return;
        }
        Schema::table('marketing_follow_ups', function (Blueprint $table) {
            $table->unsignedBigInteger('klinik_id')->nullable()->after('pasien_id');
            $table->index('klinik_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('marketing_follow_ups', 'klinik_id')) {
            return;
        }
        Schema::table('marketing_follow_ups', function (Blueprint $table) {
            $table->dropIndex(['klinik_id']);
            $table->dropColumn('klinik_id');
        });
    }
};
