<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('erm_fakturbeli', function (Blueprint $table) {
            if (!Schema::hasColumn('erm_fakturbeli', 'replaced_fakturbeli_id')) {
                $table->unsignedBigInteger('replaced_fakturbeli_id')->nullable()->after('approved_by');
                $table->foreign('replaced_fakturbeli_id')
                    ->references('id')
                    ->on('erm_fakturbeli')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('erm_fakturbeli', 'source_retur_id')) {
                $table->unsignedBigInteger('source_retur_id')->nullable()->after('replaced_fakturbeli_id');
                $table->foreign('source_retur_id')
                    ->references('id')
                    ->on('erm_fakturretur')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('erm_fakturbeli', function (Blueprint $table) {
            if (Schema::hasColumn('erm_fakturbeli', 'source_retur_id')) {
                $table->dropForeign(['source_retur_id']);
                $table->dropColumn('source_retur_id');
            }

            if (Schema::hasColumn('erm_fakturbeli', 'replaced_fakturbeli_id')) {
                $table->dropForeign(['replaced_fakturbeli_id']);
                $table->dropColumn('replaced_fakturbeli_id');
            }
        });
    }
};