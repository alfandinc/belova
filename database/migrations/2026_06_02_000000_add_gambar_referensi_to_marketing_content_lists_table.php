<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketing_content_lists') && !Schema::hasColumn('marketing_content_lists', 'gambar_referensi')) {
            Schema::table('marketing_content_lists', function (Blueprint $table) {
                $table->string('gambar_referensi')->nullable()->after('link_referensi');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('marketing_content_lists') && Schema::hasColumn('marketing_content_lists', 'gambar_referensi')) {
            Schema::table('marketing_content_lists', function (Blueprint $table) {
                $table->dropColumn('gambar_referensi');
            });
        }
    }
};