<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hrd_position_division', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained('hrd_position')->cascadeOnDelete();
            $table->foreignId('division_id')->constrained('hrd_division')->cascadeOnDelete();
            $table->foreignId('parent_position_id')->nullable()->constrained('hrd_position')->nullOnDelete();
            $table->timestamps();

            $table->index('position_id');
            $table->index('division_id');
            $table->index('parent_position_id');
        });

        $rows = DB::table('hrd_position')
            ->select('id as position_id', 'division_id', 'parent_id as parent_position_id')
            ->whereNotNull('division_id')
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $timestamp = now();

        DB::table('hrd_position_division')->insert(
            $rows->map(function ($row) use ($timestamp) {
                return [
                    'position_id' => $row->position_id,
                    'division_id' => $row->division_id,
                    'parent_position_id' => $row->parent_position_id,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })->all()
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hrd_position_division');
    }
};