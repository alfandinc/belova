<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('erm_gudang_mapping', function (Blueprint $table) {
            // Drop old unique index that only enforced uniqueness on transaction_type + is_active
            // Name taken from the database error message; guard with existsIndex when possible.
            try {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = array_map(function($i){ return $i->getName(); }, $sm->listTableIndexes('erm_gudang_mapping'));
            } catch (\Exception $e) {
                $indexes = [];
            }

            if (in_array('erm_gudang_mapping_transaction_type_is_active_unique', $indexes)) {
                $table->dropUnique('erm_gudang_mapping_transaction_type_is_active_unique');
            }

            // Add composite unique index that includes entity scope so different entities can each have active mapping
            if (!in_array('gudang_mapping_unique_active', $indexes)) {
                $table->unique(['transaction_type', 'entity_type', 'entity_id', 'is_active'], 'gudang_mapping_unique_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('erm_gudang_mapping', function (Blueprint $table) {
            try {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = array_map(function($i){ return $i->getName(); }, $sm->listTableIndexes('erm_gudang_mapping'));
            } catch (\Exception $e) {
                $indexes = [];
            }

            if (in_array('gudang_mapping_unique_active', $indexes)) {
                $table->dropUnique('gudang_mapping_unique_active');
            }

            // Recreate the original unique index on transaction_type + is_active
            if (!in_array('erm_gudang_mapping_transaction_type_is_active_unique', $indexes)) {
                $table->unique(['transaction_type', 'is_active'], 'erm_gudang_mapping_transaction_type_is_active_unique');
            }
        });
    }
};
