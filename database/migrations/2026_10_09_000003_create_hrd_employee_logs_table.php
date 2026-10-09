<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail of karyawan data: who changed which field of an employee, when, and from what to what
 * (readable text, e.g. position / golongan names, so the log stays readable after master data changes).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrd_employee_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('employee_id');
            // dibuat | ubah | kontrak
            $t->string('aksi', 30);
            $t->string('kolom', 60)->nullable();
            $t->text('sebelum')->nullable();
            $t->text('sesudah')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();

            $t->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrd_employee_logs');
    }
};
