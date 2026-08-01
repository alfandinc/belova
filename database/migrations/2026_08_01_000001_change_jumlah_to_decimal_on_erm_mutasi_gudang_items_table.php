<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE erm_mutasi_gudang_items MODIFY jumlah DECIMAL(12,2) NOT NULL DEFAULT 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE erm_mutasi_gudang_items MODIFY jumlah INT NOT NULL DEFAULT 0');
    }
};