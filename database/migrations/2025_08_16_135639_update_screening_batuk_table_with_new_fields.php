<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('erm_screening_batuk', function (Blueprint $table) {
            // Drop old columns first
            $table->dropColumn([
                'batuk_2minggu', 'demam', 'sesak_napas', 'pilek', 'sakit_tenggorokan', 'kontak_covid'
            ]);
            
            // Add new columns for Sesi Gejala
            $table->enum('demam_badan_panas', ['ya', 'tidak'])->after('visitation_id');
            $table->enum('batuk_pilek', ['ya', 'tidak'])->after('demam_badan_panas');
            $table->enum('sesak_nafas', ['ya', 'tidak'])->after('batuk_pilek');
            $table->enum('kontak_covid', ['ya', 'tidak'])->after('sesak_nafas');
            $table->enum('perjalanan_luar_negeri', ['ya', 'tidak'])->after('kontak_covid');
            
            // Add new columns for Sesi Faktor Resiko
            $table->enum('riwayat_perjalanan', ['ya', 'tidak'])->after('perjalanan_luar_negeri');
            $table->enum('kontak_erat_covid', ['ya', 'tidak'])->after('riwayat_perjalanan');
            $table->enum('faskes_covid', ['ya', 'tidak'])->after('kontak_erat_covid');
            $table->enum('kontak_hewan', ['ya', 'tidak'])->after('faskes_covid');
            $table->enum('riwayat_demam', ['ya', 'tidak'])->after('kontak_hewan');
            $table->enum('riwayat_kontak_luar_negeri', ['ya', 'tidak'])->after('riwayat_demam');
            
            // Add new columns for Sesi Tools Screening Batuk
            $table->enum('riwayat_pengobatan_tb', ['ya', 'tidak'])->after('riwayat_kontak_luar_negeri');
            $table->enum('sedang_pengobatan_tb', ['ya', 'tidak'])->after('riwayat_pengobatan_tb');
            $table->enum('batuk_demam', ['ya', 'tidak'])->after('sedang_pengobatan_tb');
            $table->enum('nafsu_makan_menurun', ['ya', 'tidak'])->after('batuk_demam');
            $table->enum('bb_turun', ['ya', 'tidak'])->after('nafsu_makan_menurun');
            $table->enum('keringat_malam', ['ya', 'tidak'])->after('bb_turun');
            $table->enum('sesak_nafas_tb', ['ya', 'tidak'])->after('keringat_malam');
            $table->enum('kontak_erat_tb', ['ya', 'tidak'])->after('sesak_nafas_tb');
            $table->enum('hasil_rontgen', ['ya', 'tidak'])->after('kontak_erat_tb');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erm_screening_batuk', function (Blueprint $table) {
            // Remove new columns
            $table->dropColumn([
                'demam_badan_panas', 'batuk_pilek', 'sesak_nafas', 'kontak_covid', 'perjalanan_luar_negeri',
                'riwayat_perjalanan', 'kontak_erat_covid', 'faskes_covid', 'kontak_hewan', 'riwayat_demam', 'riwayat_kontak_luar_negeri',
                'riwayat_pengobatan_tb', 'sedang_pengobatan_tb', 'batuk_demam', 'nafsu_makan_menurun', 'bb_turun', 'keringat_malam', 'sesak_nafas_tb', 'kontak_erat_tb', 'hasil_rontgen'
            ]);
            
            // Add back old columns
            $table->enum('batuk_2minggu', ['ya', 'tidak'])->after('visitation_id');
            $table->enum('demam', ['ya', 'tidak'])->after('batuk_2minggu');
            $table->enum('sesak_napas', ['ya', 'tidak'])->after('demam');
            $table->enum('pilek', ['ya', 'tidak'])->after('sesak_napas');
            $table->enum('sakit_tenggorokan', ['ya', 'tidak'])->after('pilek');
            $table->enum('kontak_covid', ['ya', 'tidak'])->after('sakit_tenggorokan');
        });
    }
};
