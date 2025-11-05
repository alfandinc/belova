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
        Schema::table('marketing_content_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('marketing_content_reports', 'err')) {
                $table->decimal('err', 8, 4)->default(0)->after('impressions');
            }
            if (!Schema::hasColumn('marketing_content_reports', 'eri')) {
                $table->decimal('eri', 8, 4)->default(0)->after('err');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('marketing_content_reports', function (Blueprint $table) {
            if (Schema::hasColumn('marketing_content_reports', 'eri')) {
                $table->dropColumn('eri');
            }
            if (Schema::hasColumn('marketing_content_reports', 'err')) {
                $table->dropColumn('err');
            }
        });
    }
};
