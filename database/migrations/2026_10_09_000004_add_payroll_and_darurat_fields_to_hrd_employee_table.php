<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payroll identity of an employee (NPWP, BPJS, rekening gaji, status pernikahan / tanggungan for PTKP) and
 * the name / relation of the emergency contact (the number is the existing no_darurat).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrd_employee', function (Blueprint $t) {
            $t->string('darurat_nama', 100)->nullable()->after('no_darurat');
            $t->string('darurat_hubungan', 50)->nullable()->after('darurat_nama');
            $t->string('npwp', 30)->nullable()->after('gol_tunjangan_jabatan_id');
            $t->string('no_bpjs_kesehatan', 30)->nullable()->after('npwp');
            $t->string('no_bpjs_ketenagakerjaan', 30)->nullable()->after('no_bpjs_kesehatan');
            $t->string('bank_nama', 50)->nullable()->after('no_bpjs_ketenagakerjaan');
            $t->string('bank_no_rekening', 40)->nullable()->after('bank_nama');
            $t->string('bank_atas_nama', 100)->nullable()->after('bank_no_rekening');
            // belum_menikah | menikah | cerai (labels in Employee::STATUS_PERNIKAHAN); with jumlah_tanggungan -> PTKP
            $t->string('status_pernikahan', 20)->nullable()->after('bank_atas_nama');
            $t->unsignedTinyInteger('jumlah_tanggungan')->nullable()->after('status_pernikahan');
        });
    }

    public function down(): void
    {
        Schema::table('hrd_employee', function (Blueprint $t) {
            $t->dropColumn([
                'darurat_nama', 'darurat_hubungan', 'npwp', 'no_bpjs_kesehatan', 'no_bpjs_ketenagakerjaan',
                'bank_nama', 'bank_no_rekening', 'bank_atas_nama', 'status_pernikahan', 'jumlah_tanggungan',
            ]);
        });
    }
};
