<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('finance_pengajuan_dana', function (Blueprint $table) {
            if (!Schema::hasColumn('finance_pengajuan_dana', 'payment_status')) {
                $table->string('payment_status')->default('unpaid')->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('finance_pengajuan_dana', function (Blueprint $table) {
            if (Schema::hasColumn('finance_pengajuan_dana', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });
    }
};
