<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_mutasi_stok', function (Blueprint $table) {
            $table->foreignId('cancelled_by')->nullable()->after('user_id')->constrained('users');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            $table->foreignId('revised_from_id')->nullable()->after('cancelled_at')->constrained('erm_mutasi_stok');
        });
    }

    public function down(): void
    {
        Schema::table('erm_mutasi_stok', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revised_from_id');
            $table->dropColumn('cancelled_at');
            $table->dropConstrainedForeignId('cancelled_by');
        });
    }
};