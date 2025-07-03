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
        Schema::table('hrd_employee', function (Blueprint $table) {
            $table->string('no_induk')->nullable()->unique()->after('nik');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hrd_employee', function (Blueprint $table) {
            $table->dropColumn('no_induk');
        });
    }
};
