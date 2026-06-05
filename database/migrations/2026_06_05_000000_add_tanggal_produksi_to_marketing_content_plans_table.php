<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_content_plans', function (Blueprint $table) {
            $table->date('tanggal_produksi')->nullable()->after('mention');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_content_plans', function (Blueprint $table) {
            $table->dropColumn('tanggal_produksi');
        });
    }
};