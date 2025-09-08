<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('notulensi_rapat', function (Blueprint $table) {
            $table->longText('memo')->nullable()->after('notulen');
        });
    }

    public function down()
    {
        Schema::table('notulensi_rapat', function (Blueprint $table) {
            $table->dropColumn('memo');
        });
    }
};
