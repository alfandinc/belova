<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('erm_merchandise_klinik')) {
            Schema::create('erm_merchandise_klinik', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('merchandise_id');
                $table->unsignedBigInteger('klinik_id');
                $table->timestamps();

                $table->unique(['merchandise_id', 'klinik_id']);
                $table->index('klinik_id');
            });
        }

        // Existing items belong to every klinik, so nothing changes until Marketing narrows it down
        $klinikIds = DB::table('erm_klinik')->pluck('id');
        $now = now();

        DB::table('erm_merchandises')->pluck('id')->each(function ($merchandiseId) use ($klinikIds, $now) {
            foreach ($klinikIds as $klinikId) {
                DB::table('erm_merchandise_klinik')->insertOrIgnore([
                    'merchandise_id' => $merchandiseId,
                    'klinik_id' => $klinikId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('erm_merchandise_klinik');
    }
};
