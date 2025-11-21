<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('hrd_joblists')) {
            Schema::table('hrd_joblists', function (Blueprint $table) {
                if (!Schema::hasColumn('hrd_joblists', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                    // optional: add FK if users table exists
                    if (Schema::hasTable('users')) {
                        $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
                    }
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('hrd_joblists') && Schema::hasColumn('hrd_joblists', 'updated_by')) {
            Schema::table('hrd_joblists', function (Blueprint $table) {
                // drop FK first if exists
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                try {
                    $sm->listTableDetails('hrd_joblists');
                    $table->dropForeign(['updated_by']);
                } catch (\Throwable $e) {
                    // ignore if foreign key not present
                }
                $table->dropColumn('updated_by');
            });
        }
    }
};
