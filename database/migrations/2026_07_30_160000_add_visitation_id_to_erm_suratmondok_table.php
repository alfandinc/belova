<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('erm_suratmondok', function (Blueprint $table) {
            $table->string('visitation_id')->nullable()->after('id');
            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erm_suratmondok', function (Blueprint $table) {
            $table->dropForeign(['visitation_id']);
            $table->dropColumn('visitation_id');
        });
    }
};