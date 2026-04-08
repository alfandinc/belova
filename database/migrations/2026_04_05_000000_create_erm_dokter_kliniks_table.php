<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('erm_dokter_kliniks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dokter_id')->constrained('erm_dokters')->cascadeOnDelete();
            $table->foreignId('klinik_id')->constrained('erm_klinik')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['dokter_id', 'klinik_id']);
        });

        $rows = DB::table('erm_dokters')
            ->whereNotNull('klinik_id')
            ->select('id as dokter_id', 'klinik_id')
            ->get()
            ->map(function ($row) {
                return [
                    'dokter_id' => $row->dokter_id,
                    'klinik_id' => $row->klinik_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->all();

        if (!empty($rows)) {
            DB::table('erm_dokter_kliniks')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_dokter_kliniks');
    }
};