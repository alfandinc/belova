<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rnd_sample_log', function (Blueprint $table) {
            $table->enum('status_sample', ['review', 'revisi', 'done'])->nullable()->after('no_produksi');
        });

        $latestSampleIds = DB::table('rnd_sample_log')
            ->selectRaw('MAX(id) as id')
            ->groupBy('produk_id')
            ->pluck('id');

        if ($latestSampleIds->isNotEmpty()) {
            DB::table('rnd_sample_log')
                ->join('rnd_produk', 'rnd_produk.id', '=', 'rnd_sample_log.produk_id')
                ->whereIn('rnd_sample_log.id', $latestSampleIds->all())
                ->whereNotNull('rnd_produk.status_sample')
                ->update([
                    'rnd_sample_log.status_sample' => DB::raw('rnd_produk.status_sample'),
                ]);
        }

        Schema::table('rnd_produk', function (Blueprint $table) {
            $table->dropColumn('status_sample');
        });
    }

    public function down(): void
    {
        Schema::table('rnd_produk', function (Blueprint $table) {
            $table->enum('status_sample', ['review', 'revisi', 'done'])->nullable()->after('status_administrasi_notif');
        });

        $latestSampleIds = DB::table('rnd_sample_log')
            ->selectRaw('MAX(id) as id')
            ->groupBy('produk_id')
            ->pluck('id');

        if ($latestSampleIds->isNotEmpty()) {
            DB::table('rnd_produk')
                ->join('rnd_sample_log', 'rnd_sample_log.produk_id', '=', 'rnd_produk.id')
                ->whereIn('rnd_sample_log.id', $latestSampleIds->all())
                ->whereNotNull('rnd_sample_log.status_sample')
                ->update([
                    'rnd_produk.status_sample' => DB::raw('rnd_sample_log.status_sample'),
                ]);
        }

        Schema::table('rnd_sample_log', function (Blueprint $table) {
            $table->dropColumn('status_sample');
        });
    }
};