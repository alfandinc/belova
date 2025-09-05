<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('erm_mutasi_gudang', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_mutasi')->unique();
            $table->foreignId('gudang_asal_id')->constrained('erm_gudang');
            $table->foreignId('gudang_tujuan_id')->constrained('erm_gudang');
            $table->foreignId('obat_id')->constrained('erm_obat');
            $table->integer('jumlah');
            $table->string('batch')->nullable();
            $table->date('tanggal_expired')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('keterangan')->nullable();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['status', 'gudang_asal_id']);
            $table->index(['status', 'gudang_tujuan_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('erm_mutasi_gudang');
    }
};
