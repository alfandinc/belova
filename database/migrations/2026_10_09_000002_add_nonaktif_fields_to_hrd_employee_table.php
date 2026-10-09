<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Why and since when an employee is tidak aktif (resign, PHK, kontrak habis, ...). Filled when the status
 * becomes tidak aktif (edit form or putus kontrak), cleared when the employee becomes active again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrd_employee', function (Blueprint $t) {
            $t->date('nonaktif_tanggal')->nullable()->after('status');
            // resign | phk | kontrak_habis | pensiun | meninggal | lainnya (labels in Employee::NONAKTIF_ALASAN)
            $t->string('nonaktif_alasan', 30)->nullable()->after('nonaktif_tanggal');
            $t->text('nonaktif_keterangan')->nullable()->after('nonaktif_alasan');
        });
    }

    public function down(): void
    {
        Schema::table('hrd_employee', function (Blueprint $t) {
            $t->dropColumn(['nonaktif_tanggal', 'nonaktif_alasan', 'nonaktif_keterangan']);
        });
    }
};
