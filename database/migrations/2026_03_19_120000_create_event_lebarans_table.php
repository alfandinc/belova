<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_lebarans', function (Blueprint $table) {
            $table->id();
            $table->string('pasien_id')->nullable()->index();
            $table->string('nohp')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->foreign('pasien_id')
                ->references('id')
                ->on('erm_pasiens')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_lebarans');
    }
};