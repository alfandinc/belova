<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the Content Plan board: every refresh filters on the schedule dates (week range) and on
 * status (terlewat counter). Index only, no data change. Short explicit names; safe to run again and
 * skips a column the (drifted) production schema doesn't have.
 */
return new class extends Migration
{
    private const TABLE = 'marketing_content_plans';

    private const INDEXES = [
        'mcp_tanggal_publish_index' => 'tanggal_publish',
        'mcp_tanggal_produksi_index' => 'tanggal_produksi',
        'mcp_status_index' => 'status',
    ];

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }
        foreach (self::INDEXES as $name => $column) {
            if (Schema::hasColumn(self::TABLE, $column) && !Schema::hasIndex(self::TABLE, $name)) {
                Schema::table(self::TABLE, fn (Blueprint $table) => $table->index($column, $name));
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }
        foreach (array_keys(self::INDEXES) as $name) {
            if (Schema::hasIndex(self::TABLE, $name)) {
                Schema::table(self::TABLE, fn (Blueprint $table) => $table->dropIndex($name));
            }
        }
    }
};
