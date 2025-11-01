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
        Schema::create('pr_slip_gaji_dokter', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dokter_id')->nullable()->comment('Reference to erm_dokters.id (optional)');
            $table->string('bulan', 7)->comment('YYYY-MM');

            $table->decimal('jasa_konsultasi', 15, 2)->default(0);
            $table->decimal('jasa_tindakan', 15, 2)->default(0);
            $table->decimal('uang_duduk', 15, 2)->default(0);
            $table->decimal('bagi_hasil', 15, 2)->default(0);
            $table->decimal('pot_pajak', 15, 2)->default(0);

            $table->decimal('total_pendapatan', 15, 2)->default(0);
            $table->decimal('total_potongan', 15, 2)->default(0);
            $table->decimal('total_gaji', 15, 2)->default(0);

            $table->string('status_gaji', 50)->default('draft');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pr_slip_gaji_dokter');
    }
};
