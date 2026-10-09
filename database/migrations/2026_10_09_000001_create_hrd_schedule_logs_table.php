<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail of the employee schedule: who changed which employee's day, when, and from what to what
 * (shift names / ganti libur as readable text, so the log stays readable after shifts are renamed or deleted).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrd_schedule_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('employee_id');
            $t->date('date');
            $t->string('sebelum')->nullable();
            $t->string('sesudah')->nullable();
            // jadwal | hapus | copy_minggu | ganti_libur | ganti_shift | hapus_shift
            $t->string('aksi', 30);
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();

            $t->index(['date', 'employee_id']);
            $t->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrd_schedule_logs');
    }
};
