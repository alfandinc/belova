<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * erm_obat_principal was never filled by the app. The principal of an obat now comes from
 * Master Pembelian (erm_master_faktur.principal_id), see Obat::principals().
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('erm_obat_principal')) {
            return;
        }
        $rows = DB::table('erm_obat_principal')->count();
        if ($rows > 0) {
            throw new RuntimeException("erm_obat_principal still has {$rows} rows; move them to Master Pembelian before dropping it.");
        }

        Schema::drop('erm_obat_principal');
    }

    public function down(): void
    {
        if (Schema::hasTable('erm_obat_principal')) {
            return;
        }

        Schema::create('erm_obat_principal', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('obat_id')->index();
            $table->unsignedBigInteger('principal_id')->index();
            $table->timestamps();

            $table->foreign('obat_id')->references('id')->on('erm_obat')->onDelete('cascade');
            $table->foreign('principal_id')->references('id')->on('erm_principals')->onDelete('cascade');

            $table->unique(['obat_id', 'principal_id'], 'erm_obat_principal_unique');
        });
    }
};
