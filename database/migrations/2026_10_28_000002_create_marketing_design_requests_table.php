<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Work queue of the graphic designer: social content, packaging (from R&D), print and other jobs.
 * Anyone can request; Marketing works on and approves them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketing_design_requests')) {
            return;
        }

        Schema::create('marketing_design_requests', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 30);
            $table->string('judul');
            $table->text('brief')->nullable();
            // ukuran, bahan, jumlah, catatan cetak/vendor (Cetak & Kemasan)
            $table->json('spesifikasi')->nullable();
            $table->date('deadline')->nullable();
            $table->string('status', 30)->default('Baru');
            $table->unsignedBigInteger('requester_id')->nullable();
            $table->unsignedBigInteger('designer_id')->nullable();
            // optional source records (no FK: other modules' tables may differ per environment)
            $table->unsignedBigInteger('content_plan_id')->nullable();
            $table->unsignedBigInteger('rnd_produk_id')->nullable();
            $table->string('rnd_kemasan_sisi', 10)->nullable(); // primer / sekunder
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('requester_id');
            $table->index('designer_id');
            $table->index('content_plan_id');
            $table->index('rnd_produk_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_design_requests');
    }
};
