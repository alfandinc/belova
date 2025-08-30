<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pr_insentif_omset', function (Blueprint $table) {
            $table->id();
            $table->string('nama_penghasil');
            $table->decimal('omset_min', 15, 2);
            $table->decimal('omset_max', 15, 2);
            $table->decimal('insentif_normal', 15, 2);
            $table->decimal('insentif_up', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pr_insentif_omset');
    }
};
