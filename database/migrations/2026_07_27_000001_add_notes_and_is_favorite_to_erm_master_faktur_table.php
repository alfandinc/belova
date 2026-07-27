<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_master_faktur', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('diskon_type');
            $table->boolean('is_favorite')->default(false)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('erm_master_faktur', function (Blueprint $table) {
            $table->dropColumn(['notes', 'is_favorite']);
        });
    }
};