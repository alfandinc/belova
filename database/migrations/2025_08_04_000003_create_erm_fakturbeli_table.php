<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('erm_fakturbeli', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemasok_id')->constrained('erm_pemasok')->onDelete('cascade');
            $table->string('no_faktur');
            $table->date('received_date')->nullable();
            $table->date('ship_date')->nullable();
            $table->string('notes')->nullable();
            $table->string('bukti')->nullable(); // path to uploaded photo
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erm_fakturbeli');
    }
};
