<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erm_jasa_farmasi', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->decimal('harga', 10, 2);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        // Insert default data
        DB::table('erm_jasa_farmasi')->insert([
            ['nama' => 'Tuslah Racikan', 'harga' => 2000, 'keterangan' => 'Biaya tuslah untuk resep racikan', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Tuslah Non Racikan', 'harga' => 1500, 'keterangan' => 'Biaya tuslah untuk resep non racikan', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Embalase', 'harga' => 1000, 'keterangan' => 'Biaya embalase', 'created_at' => now(), 'updated_at' => now()]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('erm_jasa_farmasi');
    }
};