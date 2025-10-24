<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('finance_pengajuan_dana', function (Blueprint $table) {
            $table->unsignedBigInteger('rekening_id')->nullable()->after('jenis_pengajuan');
            $table->index('rekening_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('finance_pengajuan_dana', function (Blueprint $table) {
            $table->dropIndex(['rekening_id']);
            $table->dropColumn('rekening_id');
        });
    }
};
