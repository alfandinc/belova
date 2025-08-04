<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('erm_fakturbeli_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fakturbeli_id')->constrained('erm_fakturbeli')->onDelete('cascade');
            $table->foreignId('obat_id')->constrained('erm_obat')->onDelete('restrict');
            $table->integer('qty');
            $table->decimal('harga', 15, 2);
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->foreignId('gudang_id')->constrained('erm_gudang')->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erm_fakturbeli_items');
    }
};
