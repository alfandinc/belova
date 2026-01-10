<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workdoc_disposisi', function (Blueprint $table) {
            $table->foreignId('memorandum_id')
                ->nullable()
                ->after('id')
                ->constrained('workdoc_memorandums')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('workdoc_disposisi', function (Blueprint $table) {
            $table->dropForeign(['memorandum_id']);
            $table->dropColumn('memorandum_id');
        });
    }
};
