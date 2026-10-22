<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The upload time that was scheduled on the day of a konten-rutin upload, so "tepat waktu" stays
 * as it was when the routine's time is changed later.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('marketing_content_routine_posts') || Schema::hasColumn('marketing_content_routine_posts', 'jam_jadwal')) {
            return;
        }

        Schema::table('marketing_content_routine_posts', function (Blueprint $table) {
            $table->time('jam_jadwal')->nullable()->after('status');
        });

        // existing rows: the routine's current time is the best known schedule
        DB::statement('UPDATE marketing_content_routine_posts p
            JOIN marketing_content_routines r ON r.id = p.routine_id
            SET p.jam_jadwal = r.jam
            WHERE p.jam_jadwal IS NULL');
    }

    public function down(): void
    {
        if (Schema::hasColumn('marketing_content_routine_posts', 'jam_jadwal')) {
            Schema::table('marketing_content_routine_posts', function (Blueprint $table) {
                $table->dropColumn('jam_jadwal');
            });
        }
    }
};
