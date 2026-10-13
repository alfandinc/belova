<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The old "paket tindakan" (bundle of erm_tindakan with its own price) was never used: erm_tindakan itself
 * is now the paket that gets sold. Drops its tables and the two columns that pointed at it.
 * Refuses to run if any of it holds data, so nothing is lost silently.
 */
return new class extends Migration
{
    public function up(): void
    {
        $inUse = [
            'erm_paket_tindakan' => DB::table('erm_paket_tindakan')->count(),
            'erm_paket_tindakan_detail' => DB::table('erm_paket_tindakan_detail')->count(),
            'erm_riwayat_tindakan.paket_tindakan_id' => DB::table('erm_riwayat_tindakan')->whereNotNull('paket_tindakan_id')->count(),
            'erm_inform_consent.paket_id' => DB::table('erm_inform_consent')->whereNotNull('paket_id')->count(),
        ];
        $inUse = array_filter($inUse);
        if ($inUse) {
            throw new RuntimeException('Paket tindakan lama masih berisi data, migration dibatalkan: ' . json_encode($inUse));
        }

        Schema::table('erm_riwayat_tindakan', function (Blueprint $table) {
            $table->dropForeign(['paket_tindakan_id']);
            $table->dropColumn('paket_tindakan_id');
        });
        Schema::table('erm_inform_consent', function (Blueprint $table) {
            $table->dropForeign(['paket_id']);
            $table->dropColumn('paket_id');
        });
        Schema::dropIfExists('erm_paket_tindakan_detail');
        Schema::dropIfExists('erm_paket_tindakan');
    }

    public function down(): void
    {
        Schema::create('erm_paket_tindakan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->decimal('harga_paket', 15, 2);
            $table->timestamps();
        });
        Schema::create('erm_paket_tindakan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paket_tindakan_id')->constrained('erm_paket_tindakan')->cascadeOnDelete();
            $table->foreignId('tindakan_id')->constrained('erm_tindakan')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::table('erm_inform_consent', function (Blueprint $table) {
            $table->unsignedBigInteger('paket_id')->nullable()->after('tindakan_id');
            $table->foreign('paket_id')->references('id')->on('erm_paket_tindakan')->onDelete('cascade');
        });
        Schema::table('erm_riwayat_tindakan', function (Blueprint $table) {
            $table->unsignedBigInteger('paket_tindakan_id')->nullable()->after('tindakan_id');
            $table->foreign('paket_tindakan_id')->references('id')->on('erm_paket_tindakan')->onDelete('cascade');
        });
    }
};
