<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('hrd_position_division') || !Schema::hasTable('hrd_position')) {
            return;
        }

        $rows = DB::table('hrd_position')
            ->select('id as position_id', 'division_id', 'parent_id as parent_position_id')
            ->whereNotNull('division_id')
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $timestamp = now();

        foreach ($rows as $row) {
            $exists = DB::table('hrd_position_division')
                ->where('position_id', $row->position_id)
                ->where('division_id', $row->division_id)
                ->when(
                    $row->parent_position_id,
                    fn ($query) => $query->where('parent_position_id', $row->parent_position_id),
                    fn ($query) => $query->whereNull('parent_position_id')
                )
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('hrd_position_division')->insert([
                'position_id' => $row->position_id,
                'division_id' => $row->division_id,
                'parent_position_id' => $row->parent_position_id,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op. This migration only ensures legacy hrd_position values exist in the pivot before old columns are removed.
    }
};