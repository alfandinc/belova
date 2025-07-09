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
        Schema::create('inv_maintenance_barang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('inv_barang')->onDelete('cascade');
            $table->date('tanggal_maintenance');
            $table->decimal('biaya_maintenance', 15, 2)->default(0);
            $table->string('nama_vendor')->nullable();
            $table->string('no_faktur')->nullable();
            $table->text('keterangan')->nullable();
            $table->date('tanggal_next_maintenance')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inv_maintenance_barang');
    }
};
