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
        Schema::create('erm_obat_mappings', function (Blueprint $table) {
            $table->id();
            // visitation_metode_bayar_id: the metode bayar of the visitation
            $table->unsignedBigInteger('visitation_metode_bayar_id');
            // obat_metode_bayar_id: the metode bayar assigned to obat to allow
            $table->unsignedBigInteger('obat_metode_bayar_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('visitation_metode_bayar_id');
            $table->index('obat_metode_bayar_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('erm_obat_mappings');
    }
};
