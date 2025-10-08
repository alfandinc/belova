<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('erm_lab_permintaan')) {
            return; // safety
        }
        Schema::table('erm_lab_permintaan', function (Blueprint $table) {
            if (!Schema::hasColumn('erm_lab_permintaan','requested_at')) {
                $table->timestamp('requested_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('erm_lab_permintaan','processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('requested_at');
            }
            if (!Schema::hasColumn('erm_lab_permintaan','completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('processed_at');
            }
            if (!Schema::hasColumn('erm_lab_permintaan','cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            }
        });

        // Backfill requested_at with created_at for existing rows where null
        try {
            DB::table('erm_lab_permintaan')
                ->whereNull('requested_at')
                ->update(['requested_at' => DB::raw('created_at')]);
        } catch (\Throwable $e) {
            // swallow if column doesn't exist yet or other issue
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('erm_lab_permintaan')) {
            return;
        }
        Schema::table('erm_lab_permintaan', function (Blueprint $table) {
            foreach (['requested_at','processed_at','completed_at','cancelled_at'] as $col) {
                if (Schema::hasColumn('erm_lab_permintaan',$col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
