<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_tindakan', function (Blueprint $table) {
            if (!Schema::hasColumn('erm_tindakan', 'is_vaksin')) {
                $table->boolean('is_vaksin')->default(false)->after('is_slimming');
            }
        });
    }

    public function down(): void
    {
        Schema::table('erm_tindakan', function (Blueprint $table) {
            if (Schema::hasColumn('erm_tindakan', 'is_vaksin')) {
                $table->dropColumn('is_vaksin');
            }
        });
    }
};