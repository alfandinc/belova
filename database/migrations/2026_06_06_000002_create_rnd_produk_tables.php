<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rnd_produk', function (Blueprint $table) {
            $table->id();
            $table->string('nama_produk');
            $table->foreignId('brand_id')->constrained('rnd_master_brand')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('produsen_vendor_id')->nullable()->constrained('rnd_master_vendor')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('kemasan_premier_id')->constrained('rnd_master_kemasan')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('kemasan_sekunder_id')->nullable()->constrained('rnd_master_kemasan')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('kemasan_primer_vendor_id')->nullable()->constrained('rnd_master_vendor')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('kemasan_sekunder_vendor_id')->nullable()->constrained('rnd_master_vendor')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('desain_kemasan_primer_id')->nullable()->constrained('rnd_master_vendor')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('desain_kemasan_sekunder_id')->nullable()->constrained('rnd_master_vendor')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('sediaan_id')->constrained('rnd_master_sediaan')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('netto')->nullable();
            $table->enum('status_administrasi_fpp', ['review', 'revisi', 'done'])->nullable();
            $table->enum('status_administrasi_spk', ['review', 'revisi', 'done'])->nullable();
            $table->enum('status_administrasi_notif', ['review', 'revisi', 'done'])->nullable();
            $table->string('status_kemasan_primer')->nullable();
            $table->string('status_kemasan_sekunder')->nullable();
            $table->string('status_desain_kemasan_primer')->nullable();
            $table->string('status_desain_kemasan_sekunder')->nullable();
            $table->timestamps();
        });

        Schema::create('rnd_sample_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('rnd_produk')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('no_produksi')->nullable();
            $table->enum('status_sample', ['review', 'revisi', 'done'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('rnd_produk_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('rnd_produk')->cascadeOnDelete()->cascadeOnUpdate();
            $table->dateTime('log_date_time');
            $table->string('status_activity');
            $table->text('notes')->nullable();
        });

        Schema::create('rnd_notif', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('rnd_produk')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('doc_path')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rnd_notif');
        Schema::dropIfExists('rnd_produk_log');
        Schema::dropIfExists('rnd_sample_log');
        Schema::dropIfExists('rnd_produk');
    }
};