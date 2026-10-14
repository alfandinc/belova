<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Primary spesialisasi of a dokter in a klinik: the default choice at pendaftaran.
     * At most one per dokter + klinik; none set means the first one (lowest id).
     */
    public function up(): void
    {
        if (Schema::hasTable('erm_dokter_klinik_spesialisasis') && !Schema::hasColumn('erm_dokter_klinik_spesialisasis', 'is_primary')) {
            Schema::table('erm_dokter_klinik_spesialisasis', function (Blueprint $table) {
                $table->boolean('is_primary')->default(false)->after('spesialisasi_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('erm_dokter_klinik_spesialisasis', 'is_primary')) {
            Schema::table('erm_dokter_klinik_spesialisasis', function (Blueprint $table) {
                $table->dropColumn('is_primary');
            });
        }
    }
};
