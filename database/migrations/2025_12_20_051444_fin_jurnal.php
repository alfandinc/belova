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
        Schema::create('bcl_fin_jurnal', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('no_jurnal');
            $table->date('tanggal');
            $table->string('kode_akun');
            $table->decimal('debet', 10, 2);
            $table->decimal('kredit', 10, 2);
            $table->string('kode_subledger')->nullable();
            $table->string('catatan');
            $table->string('index_kas')->nullable();
            $table->string('doc_id');
            $table->string('identity');
            $table->enum('pos', ['D', 'K']);
            $table->string('csrf');
            $table->integer('user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bcl_fin_jurnal');
    }
};
