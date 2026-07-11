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
        Schema::create('erm_screening_vaksin', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id');
            $table->enum('sakit_hari_ini', ['ya', 'tidak']);
            $table->enum('alergi_obat_makanan_vaksin', ['ya', 'tidak']);
            $table->enum('efek_samping_vaksin_berat', ['ya', 'tidak']);
            $table->enum('gangguan_kekebalan_tubuh', ['ya', 'tidak']);
            $table->enum('obat_steroid_atau_terapi', ['ya', 'tidak']);
            $table->enum('transfusi_darah_atau_imunoglobulin', ['ya', 'tidak']);
            $table->enum('hamil_atau_rencana_hamil', ['ya', 'tidak']);
            $table->enum('vaksinasi_4_minggu_terakhir', ['ya', 'tidak']);
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_screening_vaksin');
    }
};