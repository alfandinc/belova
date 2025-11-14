<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketingContentBriefsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketing_content_briefs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('content_plan_id');
            $table->string('headline')->nullable();
            $table->string('sub_headline')->nullable();
            $table->text('isi_konten')->nullable();
            // Store multiple image paths as JSON array
            $table->json('visual_references')->nullable();
            $table->timestamps();

            $table->foreign('content_plan_id')
                ->references('id')
                ->on('marketing_content_plans')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('marketing_content_briefs');
    }
}
