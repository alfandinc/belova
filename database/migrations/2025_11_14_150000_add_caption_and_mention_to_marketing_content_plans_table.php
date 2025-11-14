<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCaptionAndMentionToMarketingContentPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('marketing_content_plans', function (Blueprint $table) {
            $table->text('caption')->nullable()->after('deskripsi');
            $table->text('mention')->nullable()->after('caption');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('marketing_content_plans', function (Blueprint $table) {
            $table->dropColumn(['caption','mention']);
        });
    }
}
