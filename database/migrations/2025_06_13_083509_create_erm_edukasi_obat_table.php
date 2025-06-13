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
        Schema::create('erm_edukasi_obat', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id');
            $table->boolean('simpan_etiket_label')->default(false);
            $table->boolean('simpan_suhu_kulkas')->default(false);
            $table->boolean('simpan_tempat_kering')->default(false);
            $table->boolean('hindarkan_jangkauan_anak')->default(false);
            $table->string('insulin_brosur')->nullable();
            $table->string('inhalasi_brosur')->nullable();
            $table->unsignedBigInteger('apoteker_id')->nullable();
            $table->decimal('total_pembayaran', 15, 2)->nullable();
            $table->timestamps();

            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('cascade');
            $table->foreign('apoteker_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_edukasi_obat');
    }
};
