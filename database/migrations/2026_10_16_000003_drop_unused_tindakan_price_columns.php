<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Only a paket tindakan (erm_tindakan) carries a selling price, and its cost is computed from the obat of its
 * tindakan. The manual price columns on erm_kode_tindakan, erm_tindakan.hpp and the direct obat link of a paket
 * (erm_tindakan_obat) are no longer read or written anywhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_kode_tindakan', function (Blueprint $table) {
            $table->dropColumn(['hpp', 'harga_jasmed', 'harga_jual', 'harga_bottom']);
        });
        Schema::table('erm_tindakan', function (Blueprint $table) {
            $table->dropColumn('hpp');
        });
        Schema::dropIfExists('erm_tindakan_obat');
    }

    public function down(): void
    {
        Schema::create('erm_tindakan_obat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tindakan_id')->constrained('erm_tindakan')->cascadeOnDelete();
            $table->unsignedBigInteger('obat_id');
            $table->timestamps();
        });
        Schema::table('erm_tindakan', function (Blueprint $table) {
            $table->decimal('hpp', 15, 2)->nullable()->after('harga');
        });
        Schema::table('erm_kode_tindakan', function (Blueprint $table) {
            $table->decimal('hpp', 15, 2)->nullable();
            $table->decimal('harga_jasmed', 15, 2)->nullable();
            $table->decimal('harga_jual', 15, 2)->nullable();
            $table->decimal('harga_bottom', 15, 2)->nullable();
        });
    }
};
