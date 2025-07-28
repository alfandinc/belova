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
            $table->string('gol_darah', 5)->nullable()->after('alamat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hrd_employee', function (Blueprint $table) {
            $table->dropColumn('gol_darah');
        });
    }
};
