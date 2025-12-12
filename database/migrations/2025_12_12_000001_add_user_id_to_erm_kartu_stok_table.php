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
        Schema::table('erm_kartu_stok', function (Blueprint $table) {
            // nullable user_id to record who created/edited the kartu stok entry
            $table->unsignedBigInteger('user_id')->nullable()->after('keterangan');

            // add index and foreign key if users table exists
            if (Schema::hasTable('users')) {
                $table->index('user_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
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
        Schema::table('erm_kartu_stok', function (Blueprint $table) {
            if (Schema::hasTable('users')) {
                // drop foreign key if exists
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $doctrineTable = $sm->listTableDetails($table->getTable());
                if ($doctrineTable->hasForeignKey('erm_kartu_stok_user_id_foreign')) {
                    $table->dropForeign('erm_kartu_stok_user_id_foreign');
                }
            }

            if (Schema::hasColumn('erm_kartu_stok', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};
