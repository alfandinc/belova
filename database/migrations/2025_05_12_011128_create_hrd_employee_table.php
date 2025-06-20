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
        Schema::create('hrd_employee', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('nik')->unique()->nullable();
            $table->text('alamat')->nullable();

            // Village - foreign key
            $table->foreignId('village_id')->nullable()->constrained('area_villages')->nullOnDelete();

            // Position - foreign key
            $table->foreignId('position')->constrained('hrd_position');
            $table->foreignId('division_id')->constrained('hrd_division');

            $table->string('pendidikan')->nullable();
            $table->string('no_hp')->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->enum('status', ['tetap', 'kontrak', 'tidak aktif'])->nullable();
            $table->date('kontrak_berakhir')->nullable();
            $table->date('masa_pensiun')->nullable();

            // Optional document uploads
            $table->string('doc_cv')->nullable();
            $table->string('doc_ktp')->nullable();
            $table->string('doc_kontrak')->nullable();
            $table->string('doc_pendukung')->nullable();

            // User (link to login)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('photo')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hrd_employee');
    }
};
