<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pembelian documents record who created / rejected them and why, and document numbers become unique.
 * Columns are only added; existing rows keep their data. A unique index is skipped (with a warning)
 * when old duplicates exist, the application validation still blocks new ones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_permintaan', function (Blueprint $t) {
            $t->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $t->string('notes', 1000)->nullable()->after('created_by');
            $t->foreignId('rejected_by')->nullable()->after('approved_date')->constrained('users')->nullOnDelete();
            $t->timestamp('rejected_at')->nullable()->after('rejected_by');
            $t->string('rejected_reason', 500)->nullable()->after('rejected_at');
        });
        $this->uniqueIfClean('erm_permintaan', ['no_permintaan'], 'erm_permintaan_no_permintaan_unique');

        Schema::table('erm_fakturbeli', function (Blueprint $t) {
            $t->timestamp('approved_at')->nullable()->after('approved_by');
            $t->foreignId('created_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
        });
        $this->uniqueIfClean('erm_fakturbeli', ['pemasok_id', 'no_faktur'], 'erm_fakturbeli_pemasok_no_faktur_unique');

        Schema::table('erm_obat_hibah', function (Blueprint $t) {
            $t->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $t->timestamp('rejected_at')->nullable()->after('rejected_by');
            $t->string('rejected_reason', 500)->nullable()->after('rejected_at');
        });

        Schema::table('erm_fakturretur', function (Blueprint $t) {
            $t->timestamp('approved_at')->nullable()->after('approved_by');
            $t->string('rejected_reason', 500)->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('erm_fakturretur', function (Blueprint $t) {
            $t->dropColumn(['approved_at', 'rejected_reason']);
        });

        Schema::table('erm_obat_hibah', function (Blueprint $t) {
            $t->dropForeign(['rejected_by']);
            $t->dropColumn(['rejected_by', 'rejected_at', 'rejected_reason']);
        });

        if (Schema::hasIndex('erm_fakturbeli', 'erm_fakturbeli_pemasok_no_faktur_unique')) {
            Schema::table('erm_fakturbeli', function (Blueprint $t) {
                $t->dropUnique('erm_fakturbeli_pemasok_no_faktur_unique');
            });
        }
        Schema::table('erm_fakturbeli', function (Blueprint $t) {
            $t->dropForeign(['created_by']);
            $t->dropColumn(['approved_at', 'created_by']);
        });

        if (Schema::hasIndex('erm_permintaan', 'erm_permintaan_no_permintaan_unique')) {
            Schema::table('erm_permintaan', function (Blueprint $t) {
                $t->dropUnique('erm_permintaan_no_permintaan_unique');
            });
        }
        Schema::table('erm_permintaan', function (Blueprint $t) {
            $t->dropForeign(['created_by']);
            $t->dropForeign(['rejected_by']);
            $t->dropColumn(['created_by', 'notes', 'rejected_by', 'rejected_at', 'rejected_reason']);
        });
    }

    // NULL values never collide in a MySQL unique index, so only filled duplicates block it
    private function uniqueIfClean(string $table, array $columns, string $name): void
    {
        $query = DB::table($table)->select($columns)->groupBy($columns)->havingRaw('COUNT(*) > 1');
        foreach ($columns as $column) {
            $query->whereNotNull($column);
        }
        $duplicates = $query->get();

        if ($duplicates->isNotEmpty()) {
            echo "  ! Unique index {$name} dilewati: ada " . $duplicates->count() . ' nomor ganda di ' . $table . ': '
                . $duplicates->take(5)->map(fn ($d) => implode('/', (array) $d))->implode(', ') . PHP_EOL;
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($columns, $name) {
            $t->unique($columns, $name);
        });
    }
};
