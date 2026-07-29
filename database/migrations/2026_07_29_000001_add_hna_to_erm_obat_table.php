<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_obat', function (Blueprint $table) {
            $table->decimal('hna', 15, 2)->nullable()->after('harga_net');
        });
    }

    public function down(): void
    {
        Schema::table('erm_obat', function (Blueprint $table) {
            $table->dropColumn('hna');
        });
    }
};