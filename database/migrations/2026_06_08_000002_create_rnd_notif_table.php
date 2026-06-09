<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rnd_notif')) {
            Schema::create('rnd_notif', function (Blueprint $table) {
                $table->id();
                $table->foreignId('produk_id')->constrained('rnd_produk')->cascadeOnDelete()->cascadeOnUpdate();
                $table->string('doc_path')->nullable();
                $table->date('tanggal_mulai')->nullable();
                $table->date('tanggal_selesai')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rnd_notif')) {
            Schema::dropIfExists('rnd_notif');
        }
    }
};