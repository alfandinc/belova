<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat tarif jasa medis (also the change log): a tindakan's tarif applies to SPK with a tanggal tindakan
 * on / after berlaku_mulai. Existing tarif are carried over as "berlaku sejak awal".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('erm_jasa_medis_tarif')) {
            Schema::create('erm_jasa_medis_tarif', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kode_tindakan_id')->constrained('erm_kode_tindakan')->cascadeOnDelete();
                $table->date('berlaku_mulai');
                $table->decimal('tarif_dokter', 15, 2)->default(0);
                $table->decimal('tarif_petugas', 15, 2)->default(0);
                $table->decimal('tarif_dokter_lama', 15, 2)->nullable();
                $table->decimal('tarif_petugas_lama', 15, 2)->nullable();
                $table->string('keterangan')->nullable();
                $table->enum('sumber', ['manual', 'import', 'awal'])->default('manual');
                $table->unsignedInteger('spk_diperbarui')->default(0);
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('created_at')->nullable();

                $table->index(['kode_tindakan_id', 'berlaku_mulai']);
            });
        }

        // Tarif set before this table existed: valid since the beginning so no SPK changes
        $existing = DB::table('erm_jasa_medis')
            ->whereNotIn('kode_tindakan_id', DB::table('erm_jasa_medis_tarif')->select('kode_tindakan_id'))
            ->get();
        foreach ($existing as $j) {
            DB::table('erm_jasa_medis_tarif')->insert([
                'kode_tindakan_id' => $j->kode_tindakan_id,
                'berlaku_mulai' => '2000-01-01',
                'tarif_dokter' => $j->tarif_dokter,
                'tarif_petugas' => $j->tarif_employee,
                'keterangan' => $j->keterangan,
                'sumber' => 'awal',
                'user_id' => $j->updated_by,
                'created_at' => $j->updated_at ?? now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('erm_jasa_medis_tarif');
    }
};
