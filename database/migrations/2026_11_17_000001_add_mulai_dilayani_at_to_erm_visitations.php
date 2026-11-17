<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the perawat first opened the asesmen perawat / CPPT form of the visit on the visit day
 * ("mulai dilayani"), recorded automatically, no extra button.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('erm_visitations', 'mulai_dilayani_at')) {
            Schema::table('erm_visitations', function (Blueprint $table) {
                $table->timestamp('mulai_dilayani_at')->nullable()->after('waktu_kunjungan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('erm_visitations', 'mulai_dilayani_at')) {
            Schema::table('erm_visitations', function (Blueprint $table) {
                $table->dropColumn('mulai_dilayani_at');
            });
        }
    }
};
