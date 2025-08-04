<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('erm_fakturbeli_items', function (Blueprint $table) {
            $table->string('batch')->nullable()->after('gudang_id');
            $table->date('expiration_date')->nullable()->after('batch');
        });
    }

    public function down(): void
    {
        Schema::table('erm_fakturbeli_items', function (Blueprint $table) {
            $table->dropColumn(['batch', 'expiration_date']);
        });
    }
};
