<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ERM\Dokter;
use App\Models\ERM\Spesialisasi;
use App\Models\ERM\Klinik;

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
                    $deleteUrl = route('hrd.dokters.destroy', $d->id);
                    // Use dokter id for edit
                    return '
                    <button type="button" class="btn btn-sm btn-warning btn-edit-dokter" data-id="' . $d->id . '">Edit</button>
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
        // Only for create, no id
        $dokter = null;
        $users = User::role('dokter')->doesntHave('dokter')->get();
        $spesialisasis = Spesialisasi::all();
        $kliniks = Klinik::all();
        return view('erm.dokters.create', compact('dokter', 'users', 'spesialisasis', 'kliniks'));
    }

    public function edit($id)
    {
        $dokter = Dokter::with(['user', 'spesialisasi', 'klinik'])->findOrFail($id);
        $users = User::role('dokter')->where(function($q) use ($dokter) {
            $q->doesntHave('dokter')->orWhere('id', $dokter->user_id);
        })->get();
        $spesialisasis = Spesialisasi::all();
        $kliniks = Klinik::all();
        return view('erm.dokters.create', compact('dokter', 'users', 'spesialisasis', 'kliniks'));
    }


    /**
     * Store or update dokter data (AJAX only)
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'sip' => 'required|string|max:255',
            'spesialisasi_id' => 'required|exists:erm_spesialisasis,id',
            'klinik_id' => 'required|exists:erm_klinik,id',
            'due_date_sip' => 'nullable|date',
            'photo' => 'nullable|file|image|max:5120',
            'ttd' => 'nullable|file|image|max:5120',
            'nik' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
            'no_hp' => 'nullable|string|max:20',
            'status' => 'nullable|string|max:20',
        ]);

        $data = $request->only([
            'user_id',
            'sip',
            'spesialisasi_id',
            'klinik_id',
            'due_date_sip',
            'nik',
            'alamat',
            'no_hp',
            'status',
        ]);

        // Handle file upload for photo
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $path = $file->store('dokter_photos', 'public');
            $data['photo'] = $path;
        } else if ($request->id) {
            // If updating and no new file, keep old photo
            $dokterOld = Dokter::find($request->id);
            if ($dokterOld && $dokterOld->photo) {
                $data['photo'] = $dokterOld->photo;
            }
        }

        // Handle file upload for ttd (save to public/img/qr)
        if ($request->hasFile('ttd')) {
            $file = $request->file('ttd');
            $filename = uniqid('ttd_') . '.' . $file->getClientOriginalExtension();
            $destination = public_path('img/qr');
            if (!file_exists($destination)) {
                mkdir($destination, 0777, true);
            }
            $file->move($destination, $filename);
            $data['ttd'] = $filename; // Only store the filename in DB
        } else if ($request->id) {
            $dokterOld = isset($dokterOld) ? $dokterOld : Dokter::find($request->id);
            if ($dokterOld && $dokterOld->ttd) {
                $data['ttd'] = $dokterOld->ttd;
            }
        }

        $dokter = Dokter::updateOrCreate(
            ['id' => $request->id],
            $data
        );

        return response()->json([
            'success' => true,
            'message' => $request->id ? 'Data dokter berhasil diupdate.' : 'Data dokter berhasil ditambahkan.',
            'dokter' => $dokter
        ]);
    }

    // Remove edit and update methods, as all handled by store
}
