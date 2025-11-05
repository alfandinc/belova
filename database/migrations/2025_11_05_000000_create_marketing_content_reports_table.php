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
        Schema::create('marketing_content_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('content_plan_id')->nullable();
            $table->unsignedInteger('likes')->default(0);
            $table->unsignedInteger('comments')->default(0);
            $table->unsignedInteger('saves')->default(0);
            $table->unsignedInteger('shares')->default(0);
            $table->unsignedInteger('reach')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->decimal('engagement_rate', 8, 4)->default(0);
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->foreign('content_plan_id')
                ->references('id')
                ->on('marketing_content_plans')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('marketing_content_reports');
    }
};
