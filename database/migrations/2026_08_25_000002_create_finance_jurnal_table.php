<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('finance_jurnal', function (Blueprint $table) {
            $table->id();
            $table->string('no_jurnal')->nullable();
            $table->date('tanggal');
            $table->foreignId('akun_id')->constrained('finance_akun')->cascadeOnDelete();
            $table->decimal('debet', 15, 2)->default(0);
            $table->decimal('kredit', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->string('ref_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('pos', ['D','K'])->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('finance_jurnal', function (Blueprint $table) {
            $table->dropForeign(['akun_id']);
            $table->dropForeign(['user_id']);
        });
        Schema::dropIfExists('finance_jurnal');
    }
};
