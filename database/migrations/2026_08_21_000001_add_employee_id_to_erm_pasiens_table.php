<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('erm_pasiens', 'employee_id')) {
            return;
        }

        Schema::table('erm_pasiens', function (Blueprint $table) {
            $table->foreignId('employee_id')
                ->nullable()
                ->after('user_id')
                ->constrained('hrd_employee')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('erm_pasiens', 'employee_id')) {
            return;
        }

        Schema::table('erm_pasiens', function (Blueprint $table) {
            try {
                $table->dropForeign(['employee_id']);
            } catch (\Throwable $e) {
                // Ignore if the foreign key does not exist in this environment.
            }

            $table->dropColumn('employee_id');
        });
    }
};