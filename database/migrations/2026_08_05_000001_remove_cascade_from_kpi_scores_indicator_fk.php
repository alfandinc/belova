<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kpi_scores', function (Blueprint $table) {
            $table->dropForeign(['indicators_id']);
            $table->foreign('indicators_id')->references('id')->on('kpi_indicators');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_scores', function (Blueprint $table) {
            $table->dropForeign(['indicators_id']);
            $table->foreign('indicators_id')->references('id')->on('kpi_indicators')->onDelete('cascade');
        });
    }
};