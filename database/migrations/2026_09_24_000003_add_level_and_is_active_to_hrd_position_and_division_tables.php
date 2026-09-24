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
        Schema::table('hrd_position', function (Blueprint $table) {
            if (!Schema::hasColumn('hrd_position', 'level')) {
                $table->enum('level', ['Staff', 'Koordinator', 'Penanggung Jawab', 'Manager', 'Head Manager', 'Direktur'])
                    ->default('Staff')
                    ->after('name');
            }

            if (!Schema::hasColumn('hrd_position', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }
        });

        Schema::table('hrd_division', function (Blueprint $table) {
            if (!Schema::hasColumn('hrd_division', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hrd_position', function (Blueprint $table) {
            if (Schema::hasColumn('hrd_position', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if (Schema::hasColumn('hrd_position', 'level')) {
                $table->dropColumn('level');
            }
        });

        Schema::table('hrd_division', function (Blueprint $table) {
            if (Schema::hasColumn('hrd_division', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};