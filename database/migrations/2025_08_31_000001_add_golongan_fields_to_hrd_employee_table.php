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
        Schema::table('hrd_employee', function (Blueprint $table) {
            $table->unsignedBigInteger('gol_gaji_pokok_id')->nullable()->after('finger_id');
            $table->unsignedBigInteger('gol_tunjangan_jabatan_id')->nullable()->after('gol_gaji_pokok_id');

            $table->foreign('gol_gaji_pokok_id')->references('id')->on('pr_master_gajipokok')->onDelete('set null');
            $table->foreign('gol_tunjangan_jabatan_id')->references('id')->on('pr_master_tunjangan_jabatan')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hrd_employee', function (Blueprint $table) {
            $table->dropForeign(['gol_gaji_pokok_id']);
            $table->dropForeign(['gol_tunjangan_jabatan_id']);
            $table->dropColumn(['gol_gaji_pokok_id', 'gol_tunjangan_jabatan_id']);
        });
    }
};
