<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $table = 'erm_gudang_mapping';
        $oldIndex = 'erm_gudang_mapping_transaction_type_is_active_unique';
        $newIndex = 'gudang_mapping_unique_active';

        // Drop old index if exists
        $existsOld = DB::selectOne("SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?", [$table, $oldIndex]);
        if ($existsOld && $existsOld->cnt > 0) {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$oldIndex}");
        }

        // Check for duplicates that would prevent creating the new composite unique index
        $duplicates = DB::selectOne("SELECT COUNT(*) AS cnt FROM (SELECT transaction_type, entity_type, entity_id, is_active, COUNT(*) AS c FROM {$table} GROUP BY transaction_type, entity_type, entity_id, is_active HAVING c > 1) x");
        if ($duplicates && $duplicates->cnt > 0) {
            throw new \Exception('Cannot create composite unique index; duplicate rows exist for (transaction_type, entity_type, entity_id, is_active). Please resolve duplicates before running this migration.');
        }

        // Create new composite unique index if it doesn't exist
        $existsNew = DB::selectOne("SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?", [$table, $newIndex]);
        if (!($existsNew && $existsNew->cnt > 0)) {
            DB::statement("ALTER TABLE {$table} ADD UNIQUE {$newIndex} (transaction_type, entity_type, entity_id, is_active)");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table = 'erm_gudang_mapping';
        $oldIndex = 'erm_gudang_mapping_transaction_type_is_active_unique';
        $newIndex = 'gudang_mapping_unique_active';

        // Drop new composite index if exists
        $existsNew = DB::selectOne("SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?", [$table, $newIndex]);
        if ($existsNew && $existsNew->cnt > 0) {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$newIndex}");
        }

        // Recreate old unique index if not exists
        $existsOld = DB::selectOne("SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?", [$table, $oldIndex]);
        if (!($existsOld && $existsOld->cnt > 0)) {
            DB::statement("ALTER TABLE {$table} ADD UNIQUE {$oldIndex} (transaction_type, is_active)");
        }
    }
};
