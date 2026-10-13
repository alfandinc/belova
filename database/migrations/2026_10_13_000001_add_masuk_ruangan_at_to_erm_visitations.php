<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the perawat sent the patient into the dokter's room (Rawat Jalan "Masuk" button).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('erm_visitations', 'masuk_ruangan_at')) {
            Schema::table('erm_visitations', function (Blueprint $table) {
                $table->timestamp('masuk_ruangan_at')->nullable()->after('waktu_kunjungan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('erm_visitations', 'masuk_ruangan_at')) {
            Schema::table('erm_visitations', function (Blueprint $table) {
                $table->dropColumn('masuk_ruangan_at');
            });
        }
    }
};
