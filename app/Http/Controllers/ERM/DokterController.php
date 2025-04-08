<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ERM\Dokter;
use App\Models\ERM\Spesialisasi;

class DokterController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $dokters = Dokter::with('user', 'spesialisasi');

            return datatables()->of($dokters)
                ->addColumn('nama_dokter', function ($d) {
                    return $d->user->name;
                })
                ->addColumn('spesialisasi', function ($d) {
                    return $d->spesialisasi->nama;
                })
                ->addColumn('sip', function ($d) {
                    return $d->sip;
                })
                ->addColumn('actions', function ($d) {
                    $editUrl = route('erm.dokters.edit', $d->id);
                    $deleteUrl = route('erm.dokters.destroy', $d->id);

                    return '
                    <a href="' . $editUrl . '" class="btn btn-sm btn-warning">Edit</a>
                    <form method="POST" action="' . $deleteUrl . '" style="display:inline;">
                        ' . csrf_field() . method_field('DELETE') . '
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'Hapus dokter ini?\')">Hapus</button>
                    </form>';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('erm.dokters.index');
    }

    public function create()
    {
        $users = User::role('dokter')->doesntHave('dokter')->get(); // hanya user yang belum punya data dokter
        $spesialisasis = Spesialisasi::all();

        return view('erm.dokters.create', compact('users', 'spesialisasis'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'sip' => 'required|string|max:255',
            'spesialisasi_id' => 'required|exists:erm_spesialisasis,id',
        ]);

        Dokter::create([
            'user_id' => $request->user_id,
            'sip' => $request->sip,
            'spesialisasi_id' => $request->spesialisasi_id,
        ]);

        return redirect()->route('erm.dokters.create')->with('success', 'Data dokter berhasil ditambahkan.');
    }
}
