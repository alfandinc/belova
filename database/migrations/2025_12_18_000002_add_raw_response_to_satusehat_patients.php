<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('satusehat_patients', function (Blueprint $table) {
            $table->longText('raw_response')->nullable()->after('satusehat_patient_id');
        });
    }

    public function down()
    {
        Schema::table('satusehat_patients', function (Blueprint $table) {
            $table->dropColumn('raw_response');
        });
    }
};
