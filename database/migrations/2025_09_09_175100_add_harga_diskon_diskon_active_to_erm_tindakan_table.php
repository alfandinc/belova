<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('erm_tindakan', function (Blueprint $table) {
            $table->decimal('harga_diskon', 15, 2)->nullable()->after('harga');
            $table->boolean('diskon_active')->default(false)->after('harga_diskon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erm_tindakan', function (Blueprint $table) {
            $table->dropColumn(['harga_diskon', 'diskon_active']);
        });
    }
};
