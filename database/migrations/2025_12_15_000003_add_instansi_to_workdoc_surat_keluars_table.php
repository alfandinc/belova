<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInstansiToWorkdocSuratKeluarsTable extends Migration
{
    public function up()
    {
        Schema::table('workdoc_surat_keluars', function (Blueprint $table) {
            if (!Schema::hasColumn('workdoc_surat_keluars', 'instansi')) {
                $table->string('instansi')->nullable()->after('jenis_surat');
            }
        });
    }

    public function down()
    {
        Schema::table('workdoc_surat_keluars', function (Blueprint $table) {
            if (Schema::hasColumn('workdoc_surat_keluars', 'instansi')) {
                $table->dropColumn('instansi');
            }
        });
    }
}
