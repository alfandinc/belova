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
        Schema::table('erm_hasil_skinchecks', function (Blueprint $table) {
            // pasien_id stored as string (ids are strings in this app)
            $table->string('pasien_id')->nullable()->after('visitation_id');
            $table->index('pasien_id');
            // add foreign key if table exists and column types match
            if (Schema::hasTable('erm_pasiens')) {
                $table->foreign('pasien_id')->references('id')->on('erm_pasiens')->onDelete('set null');
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
        Schema::table('erm_hasil_skinchecks', function (Blueprint $table) {
            if (Schema::hasColumn('erm_hasil_skinchecks', 'pasien_id')) {
                // drop foreign first if exists
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $doctrineTable = $sm->listTableDetails($table->getTable());
                if ($doctrineTable->hasForeignKey('erm_hasil_skinchecks_pasien_id_foreign')) {
                    $table->dropForeign('erm_hasil_skinchecks_pasien_id_foreign');
                }
                $table->dropIndex(['pasien_id']);
                $table->dropColumn('pasien_id');
            }
        });
    }
};
