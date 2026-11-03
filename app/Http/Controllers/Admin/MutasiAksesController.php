<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ERM\Gudang;
use App\Support\Stok\MutasiAkses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Admin Panel > Akses Mutasi Stok: per module, which role may request / approve for which gudang,
 * and whether an approver may decide on their own document. See App\Support\Stok\MutasiAkses.
 */
class MutasiAksesController extends Controller
{
    public function index()
    {
        $migrated = MutasiAkses::migrated();
        $gudangs = Gudang::orderBy('nama')->get(['id', 'nama']);
        $rules = MutasiAkses::rules() ?? [];
        $roles = MutasiAkses::ROLES;

        $moduls = collect(MutasiAkses::MODUL_LABELS)->map(fn ($label, $modul) => (object) [
            'key' => $modul,
            'label' => $label,
            'rules' => $rules[$modul] ?? [],
            'boleh_approve_sendiri' => MutasiAkses::bolehApproveSendiri($modul),
        ]);

        return view('admin.mutasi_akses.index', compact('migrated', 'gudangs', 'roles', 'moduls'));
    }

    public function update(Request $request)
    {
        abort_unless(MutasiAkses::migrated(), 422, 'Tabel akses belum dibuat: jalankan php artisan migrate.');

        $data = $request->validate([
            'modul' => ['required', 'in:' . implode(',', array_keys(MutasiAkses::MODUL_LABELS))],
            'boleh_approve_sendiri' => ['required', 'boolean'],
            'akses' => ['nullable', 'array'],
        ]);
        $modul = $data['modul'];
        $gudangIds = Gudang::pluck('id')->all();
        $akses = $data['akses'] ?? [];

        DB::transaction(function () use ($modul, $gudangIds, $akses, $data) {
            $now = now();
            foreach (MutasiAkses::ROLES as $role) {
                foreach ($gudangIds as $gudangId) {
                    DB::table('stok_mutasi_akses')->updateOrInsert(
                        ['modul' => $modul, 'role' => $role, 'gudang_id' => $gudangId],
                        [
                            'can_request' => !empty($akses[$role][$gudangId]['request']),
                            'can_approve' => !empty($akses[$role][$gudangId]['approve']),
                            'updated_by' => Auth::id(),
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]
                    );
                }
            }
            DB::table('stok_mutasi_akses_modul')->updateOrInsert(
                ['modul' => $modul],
                ['boleh_approve_sendiri' => (bool) $data['boleh_approve_sendiri'], 'updated_by' => Auth::id(), 'updated_at' => $now, 'created_at' => $now]
            );
        });
        MutasiAkses::forget();

        return response()->json(['message' => 'Akses ' . MutasiAkses::MODUL_LABELS[$modul] . ' disimpan.']);
    }
}
