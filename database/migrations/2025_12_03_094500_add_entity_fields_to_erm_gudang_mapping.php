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
            if (!Schema::hasColumn('erm_gudang_mapping', 'entity_type')) {
                $table->string('entity_type', 50)->nullable()->after('transaction_type');
            }
            if (!Schema::hasColumn('erm_gudang_mapping', 'entity_id')) {
                $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type');
            }

            // Add composite index for faster lookups
            $table->index(['transaction_type', 'entity_type', 'entity_id', 'is_active'], 'gudang_mapping_entity_idx');
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
            if (Schema::hasColumn('erm_gudang_mapping', 'entity_type')) {
                $table->dropColumn('entity_type');
            }
            if (Schema::hasColumn('erm_gudang_mapping', 'entity_id')) {
                $table->dropColumn('entity_id');
            }
            $table->dropIndex('gudang_mapping_entity_idx');
        });
    }
};
