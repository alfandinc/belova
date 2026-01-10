<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workdoc_surat_keluars', function (Blueprint $table) {
            if (!Schema::hasColumn('workdoc_surat_keluars', 'jenis_tujuan')) {
                $table->string('jenis_tujuan', 20)->nullable()->after('status');
            }
            if (!Schema::hasColumn('workdoc_surat_keluars', 'kepada')) {
                $table->string('kepada')->nullable()->after('jenis_tujuan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workdoc_surat_keluars', function (Blueprint $table) {
            if (Schema::hasColumn('workdoc_surat_keluars', 'kepada')) {
                $table->dropColumn('kepada');
            }
            if (Schema::hasColumn('workdoc_surat_keluars', 'jenis_tujuan')) {
                $table->dropColumn('jenis_tujuan');
            }
        });
    }
};
