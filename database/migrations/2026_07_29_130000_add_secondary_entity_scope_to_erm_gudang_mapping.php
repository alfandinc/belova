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
        Schema::table('erm_gudang_mapping', function (Blueprint $table) {
            if (!Schema::hasColumn('erm_gudang_mapping', 'secondary_entity_type')) {
                $table->string('secondary_entity_type', 50)->nullable()->after('entity_id');
            }

            if (!Schema::hasColumn('erm_gudang_mapping', 'secondary_entity_id')) {
                $table->unsignedBigInteger('secondary_entity_id')->nullable()->after('secondary_entity_type');
            }
        });

        $table = 'erm_gudang_mapping';
        $indexName = 'gudang_mapping_entity_idx';
        $uniqueName = 'gudang_mapping_unique_active';

        $indexExists = DB::selectOne(
            'SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $indexName]
        );
        if ($indexExists && $indexExists->cnt > 0) {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$indexName}");
        }

        $uniqueExists = DB::selectOne(
            'SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $uniqueName]
        );
        if ($uniqueExists && $uniqueExists->cnt > 0) {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$uniqueName}");
        }

        $duplicates = DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM (SELECT transaction_type, entity_type, entity_id, secondary_entity_type, secondary_entity_id, is_active, COUNT(*) AS c FROM {$table} GROUP BY transaction_type, entity_type, entity_id, secondary_entity_type, secondary_entity_id, is_active HAVING c > 1) x"
        );
        if ($duplicates && $duplicates->cnt > 0) {
            throw new \RuntimeException('Cannot create scoped gudang mapping unique index; duplicate rows exist for the new scope combination.');
        }

        DB::statement("ALTER TABLE {$table} ADD INDEX {$indexName} (transaction_type, entity_type, entity_id, secondary_entity_type, secondary_entity_id, is_active)");
        DB::statement("ALTER TABLE {$table} ADD UNIQUE {$uniqueName} (transaction_type, entity_type, entity_id, secondary_entity_type, secondary_entity_id, is_active)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table = 'erm_gudang_mapping';
        $indexName = 'gudang_mapping_entity_idx';
        $uniqueName = 'gudang_mapping_unique_active';

        $indexExists = DB::selectOne(
            'SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $indexName]
        );
        if ($indexExists && $indexExists->cnt > 0) {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$indexName}");
        }

        $uniqueExists = DB::selectOne(
            'SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $uniqueName]
        );
        if ($uniqueExists && $uniqueExists->cnt > 0) {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$uniqueName}");
        }

        Schema::table('erm_gudang_mapping', function (Blueprint $table) {
            if (Schema::hasColumn('erm_gudang_mapping', 'secondary_entity_id')) {
                $table->dropColumn('secondary_entity_id');
            }

            if (Schema::hasColumn('erm_gudang_mapping', 'secondary_entity_type')) {
                $table->dropColumn('secondary_entity_type');
            }
        });

        DB::statement("ALTER TABLE {$table} ADD INDEX {$indexName} (transaction_type, entity_type, entity_id, is_active)");
        DB::statement("ALTER TABLE {$table} ADD UNIQUE {$uniqueName} (transaction_type, entity_type, entity_id, is_active)");
    }
};