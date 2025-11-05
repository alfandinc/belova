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
        if (Schema::hasTable('marketing_content_reports')) {
            Schema::table('marketing_content_reports', function (Blueprint $table) {
                if (Schema::hasColumn('marketing_content_reports', 'engagement_rate')) {
                    $table->dropColumn('engagement_rate');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('marketing_content_reports')) {
            Schema::table('marketing_content_reports', function (Blueprint $table) {
                if (!Schema::hasColumn('marketing_content_reports', 'engagement_rate')) {
                    $table->decimal('engagement_rate', 8, 4)->default(0)->after('impressions');
                }
            });
        }
    }
};
