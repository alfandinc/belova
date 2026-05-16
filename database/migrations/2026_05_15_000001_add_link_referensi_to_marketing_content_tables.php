<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketing_content_plans') && !Schema::hasColumn('marketing_content_plans', 'link_referensi')) {
            Schema::table('marketing_content_plans', function (Blueprint $table) {
                $table->string('link_referensi')->nullable()->after('link_asset');
            });
        }

        if (Schema::hasTable('marketing_content_lists') && !Schema::hasColumn('marketing_content_lists', 'link_referensi')) {
            Schema::table('marketing_content_lists', function (Blueprint $table) {
                $table->string('link_referensi')->nullable()->after('konten_pilar');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('marketing_content_plans') && Schema::hasColumn('marketing_content_plans', 'link_referensi')) {
            Schema::table('marketing_content_plans', function (Blueprint $table) {
                $table->dropColumn('link_referensi');
            });
        }

        if (Schema::hasTable('marketing_content_lists') && Schema::hasColumn('marketing_content_lists', 'link_referensi')) {
            Schema::table('marketing_content_lists', function (Blueprint $table) {
                $table->dropColumn('link_referensi');
            });
        }
    }
};