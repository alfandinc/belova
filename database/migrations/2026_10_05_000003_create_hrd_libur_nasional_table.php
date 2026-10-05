<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * National holidays (and cuti bersama, if the company counts them), maintained by HRD.
 * An employee scheduled on one of these dates earns jatah ganti libur, like a scheduled Sunday.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrd_libur_nasional', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->string('nama');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrd_libur_nasional');
    }
};
