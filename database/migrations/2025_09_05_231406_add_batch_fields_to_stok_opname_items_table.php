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
        Schema::table('erm_stok_opname_items', function (Blueprint $table) {
            $table->unsignedBigInteger('batch_id')->nullable()->after('obat_id');
            $table->string('batch_name')->nullable()->after('batch_id');
            $table->date('expiration_date')->nullable()->after('batch_name');
            
            // Add foreign key constraint to erm_obat_stok_gudang
            $table->foreign('batch_id')->references('id')->on('erm_obat_stok_gudang')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erm_stok_opname_items', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropColumn(['batch_id', 'batch_name', 'expiration_date']);
        });
    }
};
