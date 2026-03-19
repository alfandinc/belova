<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_lebarans', function (Blueprint $table) {
            $table->string('nama_pasien')->nullable()->after('id');
        });

        DB::statement('UPDATE event_lebarans el LEFT JOIN erm_pasiens ep ON el.pasien_id = ep.id SET el.nama_pasien = ep.nama WHERE el.nama_pasien IS NULL');
    }

    public function down(): void
    {
        Schema::table('event_lebarans', function (Blueprint $table) {
            $table->dropColumn('nama_pasien');
        });
    }
};