<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SOP per paket tindakan (erm_sop) and the per-SOP SPK checklist (erm_spk_details) are no longer used.
 * The SPK keeps its header; the start/finish time that the form already entered once for the whole SPK
 * moves onto the header (copied from the old details), so SPK status keeps working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_spk', function (Blueprint $table) {
            $table->time('waktu_mulai')->nullable()->after('tanggal_tindakan');
            $table->time('waktu_selesai')->nullable()->after('waktu_mulai');
        });

        DB::statement('UPDATE erm_spk s JOIN (
                SELECT spk_id, MIN(NULLIF(waktu_mulai, \'\')) AS mulai, MAX(NULLIF(waktu_selesai, \'\')) AS selesai
                FROM erm_spk_details GROUP BY spk_id
            ) d ON d.spk_id = s.id
            SET s.waktu_mulai = d.mulai, s.waktu_selesai = d.selesai');

        Schema::dropIfExists('erm_spk_details');
        Schema::dropIfExists('erm_sop');
    }

    public function down(): void
    {
        Schema::create('erm_sop', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tindakan_id')->constrained('erm_tindakan')->cascadeOnDelete();
            $table->string('nama_sop');
            $table->text('deskripsi')->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });
        Schema::create('erm_spk_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spk_id')->constrained('erm_spk')->cascadeOnDelete();
            $table->foreignId('sop_id')->constrained('erm_sop')->cascadeOnDelete();
            $table->string('penanggung_jawab')->nullable();
            $table->boolean('sbk')->default(false);
            $table->boolean('sba')->default(false);
            $table->boolean('sdc')->default(false);
            $table->boolean('sdk')->default(false);
            $table->boolean('sdl')->default(false);
            $table->time('waktu_mulai')->nullable();
            $table->time('waktu_selesai')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::table('erm_spk', function (Blueprint $table) {
            $table->dropColumn(['waktu_mulai', 'waktu_selesai']);
        });
    }
};
