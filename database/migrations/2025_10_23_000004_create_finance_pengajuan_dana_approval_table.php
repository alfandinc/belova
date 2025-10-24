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
        Schema::create('finance_pengajuan_dana_approval', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pengajuan_id');
            $table->unsignedBigInteger('approver_id');
            $table->string('status')->default('pending');
            $table->timestamp('tanggal_approve')->nullable();
            $table->timestamps();

            $table->foreign('pengajuan_id')
                ->references('id')
                ->on('finance_pengajuan_dana')
                ->onDelete('cascade');

            $table->foreign('approver_id')
                ->references('id')
                ->on('finance_dana_approver')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('finance_pengajuan_dana_approval');
    }
};
