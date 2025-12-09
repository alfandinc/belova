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
        if (!Schema::hasTable('finance_pengajuan_dana')) return;

        Schema::table('finance_pengajuan_dana', function (Blueprint $table) {
            if (!Schema::hasColumn('finance_pengajuan_dana', 'sumber_dana')) {
                $table->string('sumber_dana')->nullable()->after('jenis_pengajuan');
            }
            if (!Schema::hasColumn('finance_pengajuan_dana', 'perusahaan')) {
                $table->string('perusahaan')->nullable()->after('sumber_dana');
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
        if (!Schema::hasTable('finance_pengajuan_dana')) return;

        Schema::table('finance_pengajuan_dana', function (Blueprint $table) {
            if (Schema::hasColumn('finance_pengajuan_dana', 'perusahaan')) {
                $table->dropColumn('perusahaan');
            }
            if (Schema::hasColumn('finance_pengajuan_dana', 'sumber_dana')) {
                $table->dropColumn('sumber_dana');
            }
        });
    }
};
