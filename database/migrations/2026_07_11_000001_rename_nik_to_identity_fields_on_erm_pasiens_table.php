<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('erm_pasiens', 'nik') && !Schema::hasColumn('erm_pasiens', 'identity_number')) {
            Schema::table('erm_pasiens', function (Blueprint $table) {
                $table->renameColumn('nik', 'identity_number');
            });
        }

        if (!Schema::hasColumn('erm_pasiens', 'identity_document')) {
            Schema::table('erm_pasiens', function (Blueprint $table) {
                $table->string('identity_document', 20)->default('ktp')->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('erm_pasiens', 'identity_document')) {
            Schema::table('erm_pasiens', function (Blueprint $table) {
                $table->dropColumn('identity_document');
            });
        }

        if (Schema::hasColumn('erm_pasiens', 'identity_number') && !Schema::hasColumn('erm_pasiens', 'nik')) {
            Schema::table('erm_pasiens', function (Blueprint $table) {
                $table->renameColumn('identity_number', 'nik');
            });
        }
    }
};