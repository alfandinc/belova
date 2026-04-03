<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('erm_obat_expired_tindak_lanjut', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obat_id')->constrained('erm_obat')->cascadeOnDelete();
            $table->foreignId('obat_stok_gudang_id')->constrained('erm_obat_stok_gudang')->cascadeOnDelete();
            $table->decimal('jumlah', 10, 2);
            $table->date('expiration_date')->nullable();
            $table->enum('tindak_lanjut', ['diretur', 'dimusnahkan']);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['obat_id', 'tindak_lanjut']);
            $table->index('obat_stok_gudang_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('erm_obat_expired_tindak_lanjut');
    }
};