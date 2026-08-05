<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rnd_produk_timeline')) {
            return;
        }

        Schema::create('rnd_produk_timeline', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('rnd_produk')->cascadeOnDelete()->cascadeOnUpdate();
            $table->date('timeline_date');
            $table->text('notes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rnd_produk_timeline');
    }
};