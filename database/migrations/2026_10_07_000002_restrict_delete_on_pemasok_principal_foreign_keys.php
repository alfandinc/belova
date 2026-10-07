<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deleting a pemasok used to delete its faktur beli, master faktur and permintaan items (CASCADE),
 * and deleting a principal / pemasok blanked it on retur and purchase rows (SET NULL).
 * Purchase history must survive, so a used pemasok / principal can no longer be deleted (RESTRICT):
 * the Pemasok & Principal modal offers Gabungkan for duplicates instead.
 */
return new class extends Migration
{
    /** [table, column, referenced table, previous ON DELETE rule] */
    private const KEYS = [
        ['erm_fakturbeli', 'pemasok_id', 'erm_pemasok', 'cascade'],
        ['erm_master_faktur', 'pemasok_id', 'erm_pemasok', 'cascade'],
        ['erm_permintaan_items', 'pemasok_id', 'erm_pemasok', 'cascade'],
        ['erm_fakturretur', 'pemasok_id', 'erm_pemasok', 'set null'],
        ['erm_master_faktur', 'principal_id', 'erm_principals', 'set null'],
        ['erm_fakturbeli_items', 'principal_id', 'erm_principals', 'set null'],
        ['erm_permintaan_items', 'principal_id', 'erm_principals', 'set null'],
    ];

    public function up(): void
    {
        $this->setRules(fn ($old) => 'restrict');
    }

    public function down(): void
    {
        $this->setRules(fn ($old) => $old);
    }

    private function setRules(callable $rule): void
    {
        foreach (self::KEYS as [$tableName, $column, $references, $old]) {
            Schema::table($tableName, function (Blueprint $table) use ($column, $references, $rule, $old) {
                $table->dropForeign([$column]);
                $table->foreign($column)->references('id')->on($references)->onDelete($rule($old));
            });
        }
    }
};
