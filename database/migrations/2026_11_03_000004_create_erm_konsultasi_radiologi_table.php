<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Radiologi picked together with the biaya konsultasi (one click for the dokter):
 * - tambahan: radiologi tests offered as optional add-ons in the "Pilih Biaya Konsultasi" popup, per spesialisasi
 *   (erm_radiologi_test.tambahan_konsultasi + erm_radiologi_test_spesialisasi), billed at harga_tambahan (the
 *   add-on price, cheaper than the harga of a request on its own); dikerjakan_dokter = done by the dokter himself
 *   during the konsultasi (USG penyakit dalam): selesai at once, not sent to Lab;
 * - paket: a biaya konsultasi that includes radiologi (old "+ USG" items), pivot harga = its part of the harga, so
 *   the old invoices of those items still count their USG.
 * erm_radiologi_permintaan.konsultasi_id / sumber_konsultasi mark the permintaan made that way, so changing the
 * choice removes only those.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('erm_konsultasi_radiologi')) {
            Schema::create('erm_konsultasi_radiologi', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('konsultasi_id');
                $table->unsignedBigInteger('radiologi_test_id');
                $table->decimal('harga', 15, 2)->default(0);
                $table->timestamps();

                $table->unique(['konsultasi_id', 'radiologi_test_id']);
                $table->foreign('konsultasi_id')->references('id')->on('erm_konsultasi')->cascadeOnDelete();
                $table->foreign('radiologi_test_id')->references('id')->on('erm_radiologi_test')->restrictOnDelete();
            });
        }

        if (!Schema::hasColumn('erm_radiologi_test', 'tambahan_konsultasi')) {
            Schema::table('erm_radiologi_test', function (Blueprint $table) {
                $table->boolean('tambahan_konsultasi')->default(false)->after('harga');
            });
        }
        if (!Schema::hasColumn('erm_radiologi_test', 'harga_tambahan')) {
            Schema::table('erm_radiologi_test', function (Blueprint $table) {
                $table->decimal('harga_tambahan', 15, 2)->nullable()->after('tambahan_konsultasi');
                $table->boolean('dikerjakan_dokter')->default(false)->after('harga_tambahan');
            });
        }

        if (!Schema::hasTable('erm_radiologi_test_spesialisasi')) {
            Schema::create('erm_radiologi_test_spesialisasi', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('radiologi_test_id');
                $table->unsignedBigInteger('spesialisasi_id');
                $table->timestamps();

                $table->unique(['radiologi_test_id', 'spesialisasi_id'], 'radiologi_test_spesialisasi_unique');
                $table->foreign('radiologi_test_id')->references('id')->on('erm_radiologi_test')->cascadeOnDelete();
                $table->foreign('spesialisasi_id')->references('id')->on('erm_spesialisasis')->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('erm_radiologi_permintaan', 'konsultasi_id')) {
            Schema::table('erm_radiologi_permintaan', function (Blueprint $table) {
                $table->unsignedBigInteger('konsultasi_id')->nullable()->after('radiologi_test_id');
                $table->enum('sumber_konsultasi', ['paket', 'tambahan'])->nullable()->after('konsultasi_id');
                $table->index('konsultasi_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('erm_radiologi_permintaan', 'konsultasi_id')) {
            Schema::table('erm_radiologi_permintaan', function (Blueprint $table) {
                $table->dropIndex(['konsultasi_id']);
                $table->dropColumn(['konsultasi_id', 'sumber_konsultasi']);
            });
        }
        Schema::dropIfExists('erm_radiologi_test_spesialisasi');
        if (Schema::hasColumn('erm_radiologi_test', 'harga_tambahan')) {
            Schema::table('erm_radiologi_test', function (Blueprint $table) {
                $table->dropColumn(['harga_tambahan', 'dikerjakan_dokter']);
            });
        }
        if (Schema::hasColumn('erm_radiologi_test', 'tambahan_konsultasi')) {
            Schema::table('erm_radiologi_test', function (Blueprint $table) {
                $table->dropColumn('tambahan_konsultasi');
            });
        }
        Schema::dropIfExists('erm_konsultasi_radiologi');
    }
};
