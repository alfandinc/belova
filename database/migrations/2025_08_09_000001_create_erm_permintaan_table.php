<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erm_permintaan', function (Blueprint $table) {
            $table->id();
            $table->date('request_date');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_date')->nullable();
            $table->enum('status', ['waiting_approval', 'approved', 'rejected'])->default('waiting_approval');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erm_permintaan');
    }
};
