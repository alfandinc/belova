<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_tindakan', function (Blueprint $table) {
            if (Schema::hasColumn('erm_tindakan', 'harga_3_kali') && !Schema::hasColumn('erm_tindakan', 'harga_paket_visit')) {
                $table->renameColumn('harga_3_kali', 'harga_paket_visit');
            }

            if (!Schema::hasColumn('erm_tindakan', 'is_slimming')) {
                $table->boolean('is_slimming')->default(false)->after('is_active');
            }

            if (!Schema::hasColumn('erm_tindakan', 'multi_visit_total')) {
                $table->unsignedInteger('multi_visit_total')->nullable()->after('harga_paket_visit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('erm_tindakan', function (Blueprint $table) {
            if (Schema::hasColumn('erm_tindakan', 'multi_visit_total')) {
                $table->dropColumn('multi_visit_total');
            }

            if (Schema::hasColumn('erm_tindakan', 'harga_paket_visit') && !Schema::hasColumn('erm_tindakan', 'harga_3_kali')) {
                $table->renameColumn('harga_paket_visit', 'harga_3_kali');
            }

            if (Schema::hasColumn('erm_tindakan', 'is_slimming')) {
                $table->dropColumn('is_slimming');
            }
        });
    }
};