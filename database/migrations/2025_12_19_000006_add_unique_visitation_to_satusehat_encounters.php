<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('satusehat_encounters', function (Blueprint $table) {
            $table->unique('visitation_id', 'satusehat_encounters_visitation_unique');
        });
    }

    public function down()
    {
        Schema::table('satusehat_encounters', function (Blueprint $table) {
            $table->dropUnique('satusehat_encounters_visitation_unique');
        });
    }
};
