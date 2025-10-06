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
        Schema::table('finance_retur_pembelian_items', function (Blueprint $table) {
            $table->dropIndex(['billable_type', 'billable_id']);
            $table->string('billable_id')->nullable()->change();
            $table->index(['billable_type', 'billable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_retur_pembelian_items', function (Blueprint $table) {
            $table->dropIndex(['billable_type', 'billable_id']);
            $table->unsignedBigInteger('billable_id')->nullable()->change();
            $table->index(['billable_type', 'billable_id']);
        });
    }
};
