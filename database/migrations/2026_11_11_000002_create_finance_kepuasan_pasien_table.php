<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Patient satisfaction survey filled on the customer display after payment: one row per invoice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finance_kepuasan_pasien')) {
            return;
        }

        Schema::create('finance_kepuasan_pasien', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->unique();
            $table->string('visitation_id', 191)->nullable()->index();
            $table->string('pasien_id', 20)->nullable()->index();
            $table->unsignedBigInteger('klinik_id')->nullable()->index();
            $table->unsignedBigInteger('dokter_id')->nullable()->index();
            $table->unsignedBigInteger('layar_pelanggan_id')->nullable();
            $table->unsignedBigInteger('kasir_user_id')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('keluhan')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_kepuasan_pasien');
    }
};
