<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('erm_permintaan_items')) {
            Schema::table('erm_permintaan_items', function (Blueprint $table) {
                if (!Schema::hasColumn('erm_permintaan_items', 'principal_id')) {
                    $table->unsignedBigInteger('principal_id')->nullable()->after('pemasok_id');
                    $table->foreign('principal_id')->references('id')->on('erm_principals')->onDelete('set null');
                }
            });
        }

        if (Schema::hasTable('erm_fakturbeli_items')) {
            Schema::table('erm_fakturbeli_items', function (Blueprint $table) {
                if (!Schema::hasColumn('erm_fakturbeli_items', 'principal_id')) {
                    $table->unsignedBigInteger('principal_id')->nullable()->after('obat_id');
                    $table->foreign('principal_id')->references('id')->on('erm_principals')->onDelete('set null');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('erm_permintaan_items')) {
            Schema::table('erm_permintaan_items', function (Blueprint $table) {
                if (Schema::hasColumn('erm_permintaan_items', 'principal_id')) {
                    $table->dropForeign(['principal_id']);
                    $table->dropColumn('principal_id');
                }
            });
        }

        if (Schema::hasTable('erm_fakturbeli_items')) {
            Schema::table('erm_fakturbeli_items', function (Blueprint $table) {
                if (Schema::hasColumn('erm_fakturbeli_items', 'principal_id')) {
                    $table->dropForeign(['principal_id']);
                    $table->dropColumn('principal_id');
                }
            });
        }
    }
};
