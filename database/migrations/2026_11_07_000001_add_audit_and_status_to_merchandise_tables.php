<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('erm_merchandises', 'is_active')) {
            Schema::table('erm_merchandises', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('monthly_limit_stock');
            });
        }

        if (!Schema::hasColumn('erm_merchandise_kartu_stok', 'user_id')) {
            Schema::table('erm_merchandise_kartu_stok', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('notes');
            });
        }

        Schema::table('erm_pasien_merchandises', function (Blueprint $table) {
            if (!Schema::hasColumn('erm_pasien_merchandises', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('given_at');
            }
            if (!Schema::hasColumn('erm_pasien_merchandises', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
            }
            if (!Schema::hasColumn('erm_pasien_merchandises', 'cancel_reason')) {
                $table->string('cancel_reason')->nullable()->after('cancelled_by');
            }
        });

        // Deleting a master item must never take its stock card and patient history with it.
        $this->replaceMerchandiseForeignKey('erm_merchandise_kartu_stok', 'restrict');
        $this->replaceMerchandiseForeignKey('erm_pasien_merchandises', 'restrict');
    }

    public function down()
    {
        $this->replaceMerchandiseForeignKey('erm_merchandise_kartu_stok', 'cascade');
        $this->replaceMerchandiseForeignKey('erm_pasien_merchandises', 'cascade');

        foreach (['cancel_reason', 'cancelled_by', 'cancelled_at'] as $column) {
            if (Schema::hasColumn('erm_pasien_merchandises', $column)) {
                Schema::table('erm_pasien_merchandises', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        if (Schema::hasColumn('erm_merchandise_kartu_stok', 'user_id')) {
            Schema::table('erm_merchandise_kartu_stok', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }

        if (Schema::hasColumn('erm_merchandises', 'is_active')) {
            Schema::table('erm_merchandises', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }

    /**
     * Only rewrites a foreign key that exists, because production schema can drift from local.
     */
    private function replaceMerchandiseForeignKey(string $tableName, string $onDelete): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $constraints = DB::select(
            "SELECT CONSTRAINT_NAME AS name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = 'merchandise_id'
               AND REFERENCED_TABLE_NAME = 'erm_merchandises'",
            [$tableName]
        );

        foreach ($constraints as $constraint) {
            Schema::table($tableName, function (Blueprint $table) use ($constraint) {
                $table->dropForeign($constraint->name);
            });

            Schema::table($tableName, function (Blueprint $table) use ($constraint, $onDelete) {
                $table->foreign('merchandise_id', $constraint->name)
                    ->references('id')
                    ->on('erm_merchandises')
                    ->onDelete($onDelete);
            });
        }
    }
};
