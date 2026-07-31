<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('erm_fakturbeli', function (Blueprint $table) {
            if (!Schema::hasColumn('erm_fakturbeli', 'permintaan_id')) {
                $table->unsignedBigInteger('permintaan_id')->nullable()->after('id');
                $table->foreign('permintaan_id')->references('id')->on('erm_permintaan')->nullOnDelete();
            }
        });

        Schema::table('erm_fakturbeli_items', function (Blueprint $table) {
            if (!Schema::hasColumn('erm_fakturbeli_items', 'permintaan_item_id')) {
                $table->unsignedBigInteger('permintaan_item_id')->nullable()->after('fakturbeli_id');
                $table->foreign('permintaan_item_id')->references('id')->on('erm_permintaan_items')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('erm_fakturbeli_items', function (Blueprint $table) {
            if (Schema::hasColumn('erm_fakturbeli_items', 'permintaan_item_id')) {
                $table->dropForeign(['permintaan_item_id']);
                $table->dropColumn('permintaan_item_id');
            }
        });

        Schema::table('erm_fakturbeli', function (Blueprint $table) {
            if (Schema::hasColumn('erm_fakturbeli', 'permintaan_id')) {
                $table->dropForeign(['permintaan_id']);
                $table->dropColumn('permintaan_id');
            }
        });
    }
};