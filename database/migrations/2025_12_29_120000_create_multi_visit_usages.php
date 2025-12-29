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
        Schema::create('erm_multi_visit_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pasien_id');
            $table->unsignedBigInteger('tindakan_id');
            $table->unsignedBigInteger('first_visitation_id')->nullable();
            $table->integer('total')->default(3);
            $table->integer('used')->default(0);
            $table->timestamps();

            $table->index(['pasien_id', 'tindakan_id']);
        });

        Schema::table('erm_riwayat_tindakan', function (Blueprint $table) {
            $table->unsignedBigInteger('multi_visit_usage_id')->nullable()->after('paket_tindakan_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('erm_riwayat_tindakan', function (Blueprint $table) {
            if (Schema::hasColumn('erm_riwayat_tindakan', 'multi_visit_usage_id')) {
                $table->dropColumn('multi_visit_usage_id');
            }
        });
        Schema::dropIfExists('erm_multi_visit_usages');
    }
};
