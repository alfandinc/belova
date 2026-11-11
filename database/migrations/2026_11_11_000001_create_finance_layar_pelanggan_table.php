<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer-facing display (tablet at the kasir desk). The tablet opens /layar-pelanggan/{token} without login
 * and polls `state`, which the kasir's billing page writes (idle / billing / survey).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finance_layar_pelanggan')) {
            return;
        }

        Schema::create('finance_layar_pelanggan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('token', 64)->unique();
            $table->unsignedBigInteger('klinik_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('state')->nullable();
            $table->unsignedInteger('state_version')->default(0);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_layar_pelanggan');
    }
};
