<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * stok_fisik NULL = "belum dihitung" (a generated item is no longer pre-filled with 0, which made
 * Submit Stok zero every obat that was forgotten). selisih follows (NULL while not counted).
 * Also aligns the qty columns to decimal(10,2): the original migration made them integer, local was
 * already decimal, production may still be integer. Existing values are kept as they are.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('erm_stok_opname_items')) {
            return;
        }

        Schema::table('erm_stok_opname_items', function (Blueprint $table) {
            if (Schema::hasColumn('erm_stok_opname_items', 'stok_sistem')) {
                $table->decimal('stok_sistem', 10, 2)->change();
            }
            if (Schema::hasColumn('erm_stok_opname_items', 'stok_fisik')) {
                $table->decimal('stok_fisik', 10, 2)->nullable()->change();
            }
            if (Schema::hasColumn('erm_stok_opname_items', 'selisih')) {
                $table->decimal('selisih', 10, 2)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('erm_stok_opname_items')) {
            return;
        }

        // NOT NULL again: an uncounted item goes back to the old meaning (0)
        if (Schema::hasColumn('erm_stok_opname_items', 'stok_fisik')) {
            DB::table('erm_stok_opname_items')->whereNull('stok_fisik')->update(['stok_fisik' => 0]);
        }
        if (Schema::hasColumn('erm_stok_opname_items', 'selisih')) {
            DB::table('erm_stok_opname_items')->whereNull('selisih')->update([
                'selisih' => DB::raw('COALESCE(stok_fisik, 0) - stok_sistem'),
            ]);
        }

        Schema::table('erm_stok_opname_items', function (Blueprint $table) {
            if (Schema::hasColumn('erm_stok_opname_items', 'stok_fisik')) {
                $table->decimal('stok_fisik', 10, 2)->nullable(false)->change();
            }
            if (Schema::hasColumn('erm_stok_opname_items', 'selisih')) {
                $table->decimal('selisih', 10, 2)->nullable(false)->change();
            }
        });
    }
};
