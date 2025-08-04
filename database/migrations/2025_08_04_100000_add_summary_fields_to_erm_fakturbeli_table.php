<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('erm_fakturbeli', function (Blueprint $table) {
            $table->decimal('subtotal', 20, 2)->nullable()->after('bukti');
            $table->decimal('global_diskon', 20, 2)->nullable()->after('subtotal');
            $table->decimal('global_pajak', 20, 2)->nullable()->after('global_diskon');
            $table->decimal('total', 20, 2)->nullable()->after('global_pajak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erm_fakturbeli', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'global_diskon', 'global_pajak', 'total']);
        });
    }
};
