<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('erm_fakturbeli', function (Blueprint $table) {
            $table->string('no_permintaan')->nullable()->after('no_faktur');
        });
    }
    public function down()
    {
        Schema::table('erm_fakturbeli', function (Blueprint $table) {
            $table->dropColumn('no_permintaan');
        });
    }
};
