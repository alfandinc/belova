<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates pivot table `hrd_joblist_division` and adds `all_divisions` flag to `hrd_joblists`.
     *
     * NOTE: adjust the table names if your environment uses different names.
     */
    public function up()
    {
        // Add the all_divisions flag to joblists
        if (Schema::hasTable('hrd_joblists')) {
            Schema::table('hrd_joblists', function (Blueprint $table) {
                if (!Schema::hasColumn('hrd_joblists', 'all_divisions')) {
                    $table->boolean('all_divisions')->default(false)->after('created_by');
                }
            });
        }

        // Create pivot table
        if (!Schema::hasTable('hrd_joblist_division')) {
            Schema::create('hrd_joblist_division', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('joblist_id');
                $table->unsignedBigInteger('division_id');
                $table->timestamps();

                $table->foreign('joblist_id')->references('id')->on('hrd_joblists')->onDelete('cascade');
                $table->foreign('division_id')->references('id')->on('hrd_division')->onDelete('cascade');
                $table->unique(['joblist_id', 'division_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (Schema::hasTable('hrd_joblist_division')) {
            Schema::dropIfExists('hrd_joblist_division');
        }

        if (Schema::hasTable('hrd_joblists') && Schema::hasColumn('hrd_joblists', 'all_divisions')) {
            Schema::table('hrd_joblists', function (Blueprint $table) {
                $table->dropColumn('all_divisions');
            });
        }
    }
};
