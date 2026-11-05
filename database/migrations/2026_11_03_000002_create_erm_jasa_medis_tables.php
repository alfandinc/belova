<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jasa medis: tarif per tindakan (erm_kode_tindakan) for the dokter and for employees, and who did each
 * tindakan of a riwayat (SPK petugas) with the tarif snapshot used for the monthly rekap.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('erm_jasa_medis')) {
            Schema::create('erm_jasa_medis', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kode_tindakan_id')->unique()->constrained('erm_kode_tindakan')->cascadeOnDelete();
                $table->decimal('tarif_dokter', 15, 2)->default(0);
                $table->decimal('tarif_employee', 15, 2)->default(0);
                $table->string('keterangan')->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('erm_spk_petugas')) {
            Schema::create('erm_spk_petugas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('riwayat_tindakan_id')->constrained('erm_riwayat_tindakan')->cascadeOnDelete();
                $table->foreignId('kode_tindakan_id')->constrained('erm_kode_tindakan')->restrictOnDelete();
                $table->enum('tipe', ['dokter', 'employee']);
                $table->foreignId('dokter_id')->nullable()->constrained('erm_dokters')->restrictOnDelete();
                $table->foreignId('employee_id')->nullable()->constrained('hrd_employee')->restrictOnDelete();
                // Snapshot: tarif of the tipe when first saved, split evenly over the petugas of that tipe
                $table->decimal('tarif', 15, 2)->default(0);
                $table->unsignedSmallInteger('jumlah_petugas')->default(1);
                $table->decimal('nominal', 15, 2)->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tipe', 'dokter_id']);
                $table->index(['tipe', 'employee_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('erm_spk_petugas');
        Schema::dropIfExists('erm_jasa_medis');
    }
};
