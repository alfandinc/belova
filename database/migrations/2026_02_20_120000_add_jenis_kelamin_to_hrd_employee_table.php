<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJenisKelaminToHrdEmployeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('hrd_employee')) {
            Schema::table('hrd_employee', function (Blueprint $table) {
                if (!Schema::hasColumn('hrd_employee', 'jenis_kelamin')) {
                    $table->string('jenis_kelamin')->nullable()->after('tanggal_lahir');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('hrd_employee')) {
            Schema::table('hrd_employee', function (Blueprint $table) {
                if (Schema::hasColumn('hrd_employee', 'jenis_kelamin')) {
                    $table->dropColumn('jenis_kelamin');
                }
            });
        }
    }
}
