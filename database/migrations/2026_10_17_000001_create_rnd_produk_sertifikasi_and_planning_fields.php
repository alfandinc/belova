<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * R&D legalitas & sertifikasi in one table (Notifikasi BPOM, Uji Cemaran, Halal, DIP, COA, Uji Stabilitas, ...),
 * each with tanggal mulai / berlaku s/d and a document. rnd_notif (Notifikasi BPOM only) is moved into it.
 *
 * Also adds planning fields to rnd_produk (status, PIC, target rilis, target HPP/harga, biaya, masa simpan,
 * netto satuan), sample evaluation fields, and registration numbers on the Brand (HKI) and Vendor (CPKB) masters.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rnd_produk_sertifikasi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_id');
            $table->string('jenis', 40);
            $table->string('nomor')->nullable();
            $table->string('penerbit')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            // Per-batch documents (Uji Cemaran, COA) can point at the sample / no produksi they cover
            $table->unsignedBigInteger('sample_log_id')->nullable();
            $table->string('doc_path')->nullable();
            $table->string('original_name')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['produk_id', 'jenis'], 'rnd_produk_sertifikasi_produk_jenis_idx');
            $table->index('tanggal_selesai', 'rnd_produk_sertifikasi_selesai_idx');
            $table->foreign('produk_id', 'rnd_produk_sertifikasi_produk_fk')
                ->references('id')->on('rnd_produk')->cascadeOnDelete();
            $table->foreign('sample_log_id', 'rnd_produk_sertifikasi_sample_fk')
                ->references('id')->on('rnd_sample_log')->nullOnDelete();
        });

        if (Schema::hasTable('rnd_notif')) {
            $now = now();
            $rows = DB::table('rnd_notif')->orderBy('id')->get()->map(fn ($notif) => [
                'produk_id' => $notif->produk_id,
                'jenis' => 'notif_bpom',
                'penerbit' => 'BPOM',
                // Old rows always had both dates (required by the form); fall back to created_at just in case
                'tanggal_mulai' => $notif->tanggal_mulai ?? substr((string) ($notif->created_at ?? $now), 0, 10),
                'tanggal_selesai' => $notif->tanggal_selesai ?? $notif->tanggal_mulai ?? substr((string) ($notif->created_at ?? $now), 0, 10),
                'doc_path' => $notif->doc_path,
                'original_name' => $notif->doc_path ? basename($notif->doc_path) : null,
                'created_at' => $notif->created_at ?? $now,
                'updated_at' => $notif->updated_at ?? $now,
            ])->all();
            if (!empty($rows)) {
                DB::table('rnd_produk_sertifikasi')->insert($rows);
            }
            Schema::drop('rnd_notif');
        }

        Schema::table('rnd_produk', function (Blueprint $table) {
            $table->string('netto_satuan', 20)->nullable()->after('netto');
            $table->string('status_produk', 20)->default('aktif')->after('nama_produk');
            $table->unsignedBigInteger('pic_user_id')->nullable()->after('status_produk');
            $table->date('target_rilis')->nullable()->after('pic_user_id');
            $table->unsignedSmallInteger('masa_simpan_bulan')->nullable()->after('netto_satuan');
            $table->decimal('target_hpp', 15, 2)->nullable()->after('masa_simpan_bulan');
            $table->decimal('target_harga_jual', 15, 2)->nullable()->after('target_hpp');
            $table->decimal('estimasi_biaya', 15, 2)->nullable()->after('target_harga_jual');

            $table->foreign('pic_user_id', 'rnd_produk_pic_user_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('rnd_sample_log', function (Blueprint $table) {
            $table->date('tanggal_terima')->nullable()->after('no_produksi');
            // {tekstur, aroma, warna, reaksi_kulit}
            $table->text('evaluasi')->nullable()->after('status_sample');
        });

        Schema::table('rnd_master_brand', function (Blueprint $table) {
            $table->string('no_hki')->nullable()->after('nama_brand');
            $table->date('hki_berlaku_sampai')->nullable()->after('no_hki');
        });

        Schema::table('rnd_master_vendor', function (Blueprint $table) {
            $table->string('no_cpkb')->nullable()->after('no_hp');
            $table->date('cpkb_berlaku_sampai')->nullable()->after('no_cpkb');
        });
    }

    public function down(): void
    {
        Schema::table('rnd_master_vendor', function (Blueprint $table) {
            $table->dropColumn(['no_cpkb', 'cpkb_berlaku_sampai']);
        });

        Schema::table('rnd_master_brand', function (Blueprint $table) {
            $table->dropColumn(['no_hki', 'hki_berlaku_sampai']);
        });

        Schema::table('rnd_sample_log', function (Blueprint $table) {
            $table->dropColumn(['tanggal_terima', 'evaluasi']);
        });

        Schema::table('rnd_produk', function (Blueprint $table) {
            $table->dropForeign('rnd_produk_pic_user_fk');
            $table->dropColumn([
                'netto_satuan', 'status_produk', 'pic_user_id', 'target_rilis', 'masa_simpan_bulan',
                'target_hpp', 'target_harga_jual', 'estimasi_biaya',
            ]);
        });

        // Same shape as 2026_06_08_000002_create_rnd_notif_table
        Schema::create('rnd_notif', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('rnd_produk')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('doc_path')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->timestamps();
        });

        // Only Notifikasi BPOM fits rnd_notif; the other sertifikasi are lost on rollback
        $rows = DB::table('rnd_produk_sertifikasi')->where('jenis', 'notif_bpom')->orderBy('id')->get()->map(fn ($s) => [
            'produk_id' => $s->produk_id,
            'doc_path' => $s->doc_path,
            'tanggal_mulai' => $s->tanggal_mulai,
            'tanggal_selesai' => $s->tanggal_selesai,
            'created_at' => $s->created_at,
            'updated_at' => $s->updated_at,
        ])->all();
        if (!empty($rows)) {
            DB::table('rnd_notif')->insert($rows);
        }

        Schema::dropIfExists('rnd_produk_sertifikasi');
    }
};
