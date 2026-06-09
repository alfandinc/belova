<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columnsToAdd = [];

        if (!Schema::hasColumn('rnd_produk', 'produsen_vendor_id')) {
            $columnsToAdd[] = 'produsen_vendor_id';
        }

        if (!Schema::hasColumn('rnd_produk', 'kemasan_primer_vendor_id')) {
            $columnsToAdd[] = 'kemasan_primer_vendor_id';
        }

        if (!Schema::hasColumn('rnd_produk', 'kemasan_sekunder_vendor_id')) {
            $columnsToAdd[] = 'kemasan_sekunder_vendor_id';
        }

        if (!Schema::hasColumn('rnd_produk', 'desain_kemasan_primer_id')) {
            $columnsToAdd[] = 'desain_kemasan_primer_id';
        }

        if (!Schema::hasColumn('rnd_produk', 'desain_kemasan_sekunder_id')) {
            $columnsToAdd[] = 'desain_kemasan_sekunder_id';
        }

        if ($columnsToAdd !== []) {
            Schema::table('rnd_produk', function (Blueprint $table) use ($columnsToAdd) {
                if (in_array('produsen_vendor_id', $columnsToAdd, true)) {
                    $table->foreignId('produsen_vendor_id')->nullable()->after('brand_id')->constrained('rnd_master_vendor')->cascadeOnUpdate()->nullOnDelete();
                }

                if (in_array('kemasan_primer_vendor_id', $columnsToAdd, true)) {
                    $table->foreignId('kemasan_primer_vendor_id')->nullable()->after('kemasan_sekunder_id')->constrained('rnd_master_vendor')->cascadeOnUpdate()->nullOnDelete();
                }

                if (in_array('kemasan_sekunder_vendor_id', $columnsToAdd, true)) {
                    $table->foreignId('kemasan_sekunder_vendor_id')->nullable()->after('kemasan_primer_vendor_id')->constrained('rnd_master_vendor')->cascadeOnUpdate()->nullOnDelete();
                }

                if (in_array('desain_kemasan_primer_id', $columnsToAdd, true)) {
                    $table->foreignId('desain_kemasan_primer_id')->nullable()->after('kemasan_sekunder_vendor_id')->constrained('rnd_master_vendor')->cascadeOnUpdate()->nullOnDelete();
                }

                if (in_array('desain_kemasan_sekunder_id', $columnsToAdd, true)) {
                    $table->foreignId('desain_kemasan_sekunder_id')->nullable()->after('desain_kemasan_primer_id')->constrained('rnd_master_vendor')->cascadeOnUpdate()->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        $columnsToDrop = array_values(array_filter([
            Schema::hasColumn('rnd_produk', 'desain_kemasan_sekunder_id') ? 'desain_kemasan_sekunder_id' : null,
            Schema::hasColumn('rnd_produk', 'desain_kemasan_primer_id') ? 'desain_kemasan_primer_id' : null,
            Schema::hasColumn('rnd_produk', 'kemasan_sekunder_vendor_id') ? 'kemasan_sekunder_vendor_id' : null,
            Schema::hasColumn('rnd_produk', 'kemasan_primer_vendor_id') ? 'kemasan_primer_vendor_id' : null,
            Schema::hasColumn('rnd_produk', 'produsen_vendor_id') ? 'produsen_vendor_id' : null,
        ]));

        if ($columnsToDrop !== []) {
            Schema::table('rnd_produk', function (Blueprint $table) use ($columnsToDrop) {
                foreach ($columnsToDrop as $column) {
                    $table->dropConstrainedForeignId($column);
                }
            });
        }
    }
};