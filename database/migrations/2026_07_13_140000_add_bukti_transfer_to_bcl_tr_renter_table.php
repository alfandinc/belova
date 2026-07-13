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
        Schema::table('bcl_tr_renter', function (Blueprint $table) {
            $table->string('bukti_transfer')->nullable()->after('catatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bcl_tr_renter', function (Blueprint $table) {
            $table->dropColumn('bukti_transfer');
        });
    }
};