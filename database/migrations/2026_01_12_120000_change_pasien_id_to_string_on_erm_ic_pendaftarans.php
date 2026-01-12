<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('erm_ic_pendaftarans', function (Blueprint $table) {
            // Change pasien_id to string to preserve leading zeros
            $table->string('pasien_id', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('erm_ic_pendaftarans', function (Blueprint $table) {
            // Revert back to unsignedBigInteger (will drop leading zeros if any)
            $table->unsignedBigInteger('pasien_id')->change();
        });
    }
};
