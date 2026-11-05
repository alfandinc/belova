<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Biaya konsultasi that include radiologi (e.g. "Konsultasi PD + USG"): picking it creates the radiologi permintaan
 * with the harga given here, the konsultasi billing being the rest. erm_radiologi_permintaan.konsultasi_id marks the
 * permintaan made that way, so changing the jenis konsultasi removes only those.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('erm_konsultasi_radiologi')) {
            Schema::create('erm_konsultasi_radiologi', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('konsultasi_id');
                $table->unsignedBigInteger('radiologi_test_id');
                $table->decimal('harga', 15, 2)->default(0);
                $table->timestamps();

                $table->unique(['konsultasi_id', 'radiologi_test_id']);
                $table->foreign('konsultasi_id')->references('id')->on('erm_konsultasi')->cascadeOnDelete();
                $table->foreign('radiologi_test_id')->references('id')->on('erm_radiologi_test')->restrictOnDelete();
            });
        }

        if (!Schema::hasColumn('erm_radiologi_permintaan', 'konsultasi_id')) {
            Schema::table('erm_radiologi_permintaan', function (Blueprint $table) {
                $table->unsignedBigInteger('konsultasi_id')->nullable()->after('radiologi_test_id');
                $table->index('konsultasi_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('erm_radiologi_permintaan', 'konsultasi_id')) {
            Schema::table('erm_radiologi_permintaan', function (Blueprint $table) {
                $table->dropIndex(['konsultasi_id']);
                $table->dropColumn('konsultasi_id');
            });
        }
        Schema::dropIfExists('erm_konsultasi_radiologi');
    }
};
