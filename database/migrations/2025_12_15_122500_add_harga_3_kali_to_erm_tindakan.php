<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHarga3KaliToErmTindakan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('erm_tindakan', function (Blueprint $table) {
            if (!Schema::hasColumn('erm_tindakan', 'harga_3_kali')) {
                $table->decimal('harga_3_kali', 12, 2)->nullable()->after('harga_diskon');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('erm_tindakan', function (Blueprint $table) {
            if (Schema::hasColumn('erm_tindakan', 'harga_3_kali')) {
                $table->dropColumn('harga_3_kali');
            }
        });
    }
}
