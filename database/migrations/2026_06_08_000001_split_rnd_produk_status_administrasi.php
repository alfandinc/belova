<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rnd_produk', function (Blueprint $table) {
            $table->enum('status_administrasi_fpp', ['review', 'revisi', 'done'])->nullable()->after('netto');
            $table->enum('status_administrasi_spk', ['review', 'revisi', 'done'])->nullable()->after('status_administrasi_fpp');
            $table->enum('status_administrasi_notif', ['review', 'revisi', 'done'])->nullable()->after('status_administrasi_spk');
        });

        if (Schema::hasColumn('rnd_produk', 'status_administrasi')) {
            DB::table('rnd_produk')
                ->select(['id', 'status_administrasi'])
                ->orderBy('id')
                ->chunkById(100, function ($rows) {
                    foreach ($rows as $row) {
                        $updates = match ($row->status_administrasi) {
                            'fpp_progress' => ['status_administrasi_fpp' => 'review'],
                            'fpp_done' => ['status_administrasi_fpp' => 'done'],
                            'spk_progress' => ['status_administrasi_spk' => 'review'],
                            'spk_done' => ['status_administrasi_spk' => 'done'],
                            'notif_progress' => ['status_administrasi_notif' => 'review'],
                            'notif_revisi' => ['status_administrasi_notif' => 'revisi'],
                            'notif_done' => ['status_administrasi_notif' => 'done'],
                            default => [],
                        };

                        if ($updates !== []) {
                            DB::table('rnd_produk')->where('id', $row->id)->update($updates);
                        }
                    }
                }, 'id');

            Schema::table('rnd_produk', function (Blueprint $table) {
                $table->dropColumn('status_administrasi');
            });
        }
    }

    public function down(): void
    {
        Schema::table('rnd_produk', function (Blueprint $table) {
            $table->enum('status_administrasi', ['fpp_progress', 'fpp_done', 'spk_progress', 'spk_done', 'notif_progress', 'notif_revisi', 'notif_done'])->nullable()->after('netto');
        });

        DB::table('rnd_produk')
            ->select(['id', 'status_administrasi_fpp', 'status_administrasi_spk', 'status_administrasi_notif'])
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $statusAdministrasi = null;

                    if ($row->status_administrasi_notif) {
                        $statusAdministrasi = 'notif_' . ($row->status_administrasi_notif === 'review' ? 'progress' : $row->status_administrasi_notif);
                    } elseif ($row->status_administrasi_spk) {
                        $statusAdministrasi = 'spk_' . ($row->status_administrasi_spk === 'review' ? 'progress' : $row->status_administrasi_spk);
                    } elseif ($row->status_administrasi_fpp) {
                        $statusAdministrasi = 'fpp_' . ($row->status_administrasi_fpp === 'review' ? 'progress' : $row->status_administrasi_fpp);
                    }

                    DB::table('rnd_produk')
                        ->where('id', $row->id)
                        ->update(['status_administrasi' => $statusAdministrasi]);
                }
            }, 'id');

        Schema::table('rnd_produk', function (Blueprint $table) {
            $table->dropColumn([
                'status_administrasi_fpp',
                'status_administrasi_spk',
                'status_administrasi_notif',
            ]);
        });
    }
};