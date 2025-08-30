<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pr_master_gajipokok', function (Blueprint $table) {
            $table->id();
            $table->string('golongan');
            $table->decimal('nominal', 15, 2);
            $table->timestamps();
        });
        Schema::create('pr_master_tunjangan_jabatan', function (Blueprint $table) {
            $table->id();
            $table->string('golongan');
            $table->decimal('nominal', 15, 2);
            $table->timestamps();
        });
        Schema::create('pr_master_tunjangan_lain', function (Blueprint $table) {
            $table->id();
            $table->string('nama_tunjangan');
            $table->decimal('nominal', 15, 2);
            $table->timestamps();
        });
        Schema::create('pr_master_benefit', function (Blueprint $table) {
            $table->id();
            $table->string('nama_benefit');
            $table->decimal('nominal', 15, 2);
            $table->timestamps();
        });
        Schema::create('pr_master_potongan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_potongan');
            $table->decimal('nominal', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pr_master_gajipokok');
        Schema::dropIfExists('pr_master_tunjangan_jabatan');
        Schema::dropIfExists('pr_master_tunjangan_lain');
        Schema::dropIfExists('pr_master_benefit');
        Schema::dropIfExists('pr_master_potongan');
    }
};
