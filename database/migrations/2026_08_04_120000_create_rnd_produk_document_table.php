<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rnd_produk_document')) {
            return;
        }

        Schema::create('rnd_produk_document', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('rnd_produk')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rnd_produk_document');
    }
};