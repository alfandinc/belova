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
        Schema::table('bcl_renter', function (Blueprint $table) {
            if (!Schema::hasColumn('bcl_renter', 'deposit_balance')) {
                $table->decimal('deposit_balance', 15, 2)->default(0)->after('birthday');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bcl_renter', function (Blueprint $table) {
            if (Schema::hasColumn('bcl_renter', 'deposit_balance')) {
                $table->dropColumn('deposit_balance');
            }
        });
    }
};
