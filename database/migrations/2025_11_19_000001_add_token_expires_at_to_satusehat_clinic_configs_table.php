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
        Schema::table('satusehat_clinic_configs', function (Blueprint $table) {
            if (!Schema::hasColumn('satusehat_clinic_configs', 'token_expires_at')) {
                $table->timestamp('token_expires_at')->nullable()->after('token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('satusehat_clinic_configs', function (Blueprint $table) {
            if (Schema::hasColumn('satusehat_clinic_configs', 'token_expires_at')) {
                $table->dropColumn('token_expires_at');
            }
        });
    }
};
