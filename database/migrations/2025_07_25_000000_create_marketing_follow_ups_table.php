<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('marketing_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasien_id')->constrained('erm_pasiens')->onDelete('cascade');
            $table->string('kategori');
            $table->foreignId('sales_id')->constrained('hrd_employee')->onDelete('set null')->nullable();
            $table->string('status_respon');
            $table->string('bukti_respon')->nullable(); // path to image
            $table->text('rencana_tindak_lanjut')->nullable();
            $table->string('status_booking')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('marketing_follow_ups');
    }
};
