<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partial approval: approvers may approve less than requested. The main columns always hold the
 * approved (effective) values, so payroll/schedule/reports keep working unchanged; these columns keep
 * what the employee originally requested. NULL means the request was never reduced.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrd_pengajuan_lembur', function (Blueprint $table) {
            $table->time('jam_mulai_diajukan')->nullable()->after('total_jam');
            $table->time('jam_selesai_diajukan')->nullable()->after('jam_mulai_diajukan');
            // minutes, like total_jam
            $table->decimal('total_jam_diajukan', 7, 2)->nullable()->after('jam_selesai_diajukan');
        });

        foreach (['hrd_pengajuan_libur', 'hrd_pengajuan_tidak_masuk'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->date('tanggal_mulai_diajukan')->nullable()->after('total_hari');
                $table->date('tanggal_selesai_diajukan')->nullable()->after('tanggal_mulai_diajukan');
                $table->integer('total_hari_diajukan')->nullable()->after('tanggal_selesai_diajukan');
            });
        }
    }

    public function down(): void
    {
        Schema::table('hrd_pengajuan_lembur', function (Blueprint $table) {
            $table->dropColumn(['jam_mulai_diajukan', 'jam_selesai_diajukan', 'total_jam_diajukan']);
        });

        foreach (['hrd_pengajuan_libur', 'hrd_pengajuan_tidak_masuk'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['tanggal_mulai_diajukan', 'tanggal_selesai_diajukan', 'total_hari_diajukan']);
            });
        }
    }
};
