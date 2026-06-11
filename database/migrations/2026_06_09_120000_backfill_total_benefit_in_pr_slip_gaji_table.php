<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('pr_slip_gaji')
            ->where(function ($query) {
                $query->whereNull('total_benefit')
                    ->orWhere('total_benefit', 0);
            })
            ->update([
                'total_benefit' => DB::raw('COALESCE(benefit_bpjs_kesehatan, 0) + COALESCE(benefit_jht, 0) + COALESCE(benefit_jkk, 0) + COALESCE(benefit_jkm, 0)'),
            ]);
    }

    public function down(): void
    {
        // No-op: this backfill cannot be safely reversed without the prior values.
    }
};