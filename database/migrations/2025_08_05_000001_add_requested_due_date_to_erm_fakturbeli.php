<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('erm_fakturbeli', function (Blueprint $table) {
            $table->date('requested_date')->nullable()->after('received_date');
            $table->date('due_date')->nullable()->after('requested_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erm_fakturbeli', function (Blueprint $table) {
            $table->dropColumn(['requested_date', 'due_date']);
        });
    }
};
