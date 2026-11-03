<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Who may request / approve Penyesuaian Stok and Transfer Antar Gudang, per role per gudang
 * (Admin Panel > Akses Mutasi Stok, App\Support\Stok\MutasiAkses). Seeded with the rules that were
 * hard-coded before, for every existing gudang, so nothing changes until an admin edits them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stok_mutasi_akses')) {
            Schema::create('stok_mutasi_akses', function (Blueprint $table) {
                $table->id();
                $table->string('modul', 30);
                $table->string('role', 50);
                $table->unsignedBigInteger('gudang_id');
                $table->boolean('can_request')->default(false);
                $table->boolean('can_approve')->default(false);
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['modul', 'role', 'gudang_id'], 'stok_mutasi_akses_unique');
            });

            $now = now();
            $defaults = [
                // modul => [role => [can_request, can_approve]]
                'penyesuaian' => ['Farmasi' => [1, 1], 'Beautician' => [1, 1], 'Lab' => [1, 0], 'Marketing' => [1, 1]],
                'mutasi_gudang' => ['Farmasi' => [1, 1], 'Beautician' => [1, 1], 'Lab' => [1, 1], 'Marketing' => [1, 1]],
            ];
            $rows = [];
            foreach (DB::table('erm_gudang')->pluck('id') as $gudangId) {
                foreach ($defaults as $modul => $roles) {
                    foreach ($roles as $role => [$request, $approve]) {
                        $rows[] = [
                            'modul' => $modul, 'role' => $role, 'gudang_id' => $gudangId,
                            'can_request' => $request, 'can_approve' => $approve,
                            'created_at' => $now, 'updated_at' => $now,
                        ];
                    }
                }
            }
            if ($rows) {
                DB::table('stok_mutasi_akses')->insert($rows);
            }
        }

        if (!Schema::hasTable('stok_mutasi_akses_modul')) {
            Schema::create('stok_mutasi_akses_modul', function (Blueprint $table) {
                $table->id();
                $table->string('modul', 30)->unique();
                $table->boolean('boleh_approve_sendiri')->default(true);
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });

            DB::table('stok_mutasi_akses_modul')->insert([
                ['modul' => 'penyesuaian', 'boleh_approve_sendiri' => (bool) config('stok.penyesuaian.boleh_approve_sendiri', true), 'created_at' => now(), 'updated_at' => now()],
                ['modul' => 'mutasi_gudang', 'boleh_approve_sendiri' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_mutasi_akses_modul');
        Schema::dropIfExists('stok_mutasi_akses');
    }
};
