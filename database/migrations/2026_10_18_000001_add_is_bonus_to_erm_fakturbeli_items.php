<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Free goods that came with the faktur (outside the permintaan, harga 0)
    public function up(): void
    {
        if (!Schema::hasColumn('erm_fakturbeli_items', 'is_bonus')) {
            Schema::table('erm_fakturbeli_items', function (Blueprint $table) {
                $table->boolean('is_bonus')->default(false)->after('total_amount');
            });
        }

        // Existing lines that were entered as bonus: received, not from a permintaan, no price
        DB::table('erm_fakturbeli_items')
            ->whereNull('permintaan_item_id')
            ->where('qty', '>', 0)
            ->where('harga', '<=', 0)
            ->update(['is_bonus' => true]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('erm_fakturbeli_items', 'is_bonus')) {
            Schema::table('erm_fakturbeli_items', function (Blueprint $table) {
                $table->dropColumn('is_bonus');
            });
        }
    }
};
