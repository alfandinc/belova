<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workdoc_memorandums', function (Blueprint $table) {
            $table->string('nomor_memo', 100)->nullable()->after('perihal');
            $table->index('nomor_memo');
        });
    }

    public function down(): void
    {
        Schema::table('workdoc_memorandums', function (Blueprint $table) {
            $table->dropIndex(['nomor_memo']);
            $table->dropColumn('nomor_memo');
        });
    }
};
