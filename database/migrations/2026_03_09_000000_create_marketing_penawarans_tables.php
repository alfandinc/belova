<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_penawarans', function (Blueprint $table) {
            $table->id();
            $table->string('pasien_id', 6)->nullable();
            $table->string('visitation_id')->nullable(); // filled when processed into ERM visitation
            $table->enum('status', ['ditawarkan', 'disetujui', 'diproses', 'selesai'])->default('ditawarkan');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('pasien_id')->references('id')->on('erm_pasiens')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->nullOnDelete();
        });

        Schema::create('marketing_penawaran_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penawaran_id')->constrained('marketing_penawarans')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('erm_obat')->cascadeOnDelete();

            // match resepfarmasi fields (mostly nullable)
            $table->integer('jumlah')->nullable();
            $table->string('dosis')->nullable();
            $table->integer('bungkus')->nullable();
            $table->string('racikan_ke')->nullable();
            $table->string('aturan_pakai')->nullable();
            $table->foreignId('wadah_id')->nullable()->constrained('erm_wadah_obat')->nullOnDelete();
            $table->decimal('harga', 15, 2)->nullable();
            $table->integer('diskon')->nullable();
            $table->decimal('total', 15, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_penawaran_items');
        Schema::dropIfExists('marketing_penawarans');
    }
};
