<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per direct superior's decision on a pengajuan (libur / tidak_masuk / lembur).
 * An employee with 2+ direct superiors needs 2 approvals before the manager step is done;
 * one rejection rejects the request. The pengajuan's status_manager stays the summary column.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('hrd_pengajuan_persetujuan_atasan')) {
            return;
        }
        Schema::create('hrd_pengajuan_persetujuan_atasan', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 20);                       // libur | tidak_masuk | lembur
            $table->unsignedBigInteger('pengajuan_id');
            $table->unsignedBigInteger('approver_employee_id');
            $table->string('status', 20);                      // disetujui | ditolak
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['jenis', 'pengajuan_id', 'approver_employee_id'], 'hrd_ppa_unique');
            $table->index('approver_employee_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hrd_pengajuan_persetujuan_atasan');
    }
};
