<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('survey_questions', function (Blueprint $table) {
            $table->string('klinik_name')->nullable()->after('id');
        });
    }
    public function down()
    {
        Schema::table('survey_questions', function (Blueprint $table) {
            $table->dropColumn('klinik_name');
        });
    }
};
