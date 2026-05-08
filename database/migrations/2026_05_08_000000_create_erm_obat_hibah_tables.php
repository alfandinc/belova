<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('erm_obat_hibah', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_hibah')->unique();
            $table->date('received_date');
            $table->string('sumber')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('erm_obat_hibah_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obat_hibah_id')->constrained('erm_obat_hibah')->onDelete('cascade');
            $table->foreignId('obat_id')->constrained('erm_obat')->onDelete('restrict');
            $table->foreignId('gudang_id')->constrained('erm_gudang')->onDelete('restrict');
            $table->decimal('qty', 14, 4);
            $table->string('batch')->nullable();
            $table->date('expiration_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erm_obat_hibah_items');
        Schema::dropIfExists('erm_obat_hibah');
    }
};