<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('erm_dokters', function (Blueprint $table) {
            $table->date('due_date_sip')->nullable()->after('sip');
            $table->string('photo')->nullable()->after('due_date_sip');
            $table->string('nik', 30)->nullable()->after('photo');
            $table->string('alamat')->nullable()->after('nik');
            $table->string('no_hp', 20)->nullable()->after('alamat');
            $table->string('status', 20)->nullable()->after('no_hp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('erm_dokters', function (Blueprint $table) {
            $table->dropColumn(['due_date_sip', 'photo', 'nik', 'alamat', 'no_hp', 'status']);
        });
    }
};
