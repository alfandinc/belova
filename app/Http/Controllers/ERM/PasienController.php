<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Pasien;
use Yajra\DataTables\Facades\DataTables;

class PasienController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $pasiens = Pasien::select('id', 'nama', 'nik', 'alamat', 'no_hp');

            return DataTables::of($pasiens)
                ->addColumn('actions', function ($user) {
                    return '
                    <a href="' . route('erm.pasiens.edit', $user->id) . '" class="btn btn-warning btn-sm">Edit</a>
                    <form method="POST" action="' . route('erm.pasiens.destroy', $user->id) . '" style="display:inline;">
                        ' . csrf_field() . '
                        ' . method_field('DELETE') . '
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure?\')">Delete</button>
                    </form>';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('erm.pasiens.index');
    }

    public function create()
    {
        return view('erm.pasiens.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nik' => 'required|unique:erm_pasiens,nik',
            'nama' => 'required',
            'tanggal_lahir' => 'required|date',
            'gender' => 'required',
            'agama' => 'required',
            'marital_status' => 'required',
            'pendidikan' => 'required',
            'pekerjaan' => 'required',
            'gol_darah' => 'required',
            'notes' => 'nullable',
            'alamat' => 'required',
            // 'village_id' => 'required|exists:area_villages,id',
            'no_hp' => 'required',
            'no_hp2' => 'nullable',
            'email' => 'required|email',
            'instagram' => 'nullable',
        ]);
        // dd($validated); // lihat hasil validasi, akan error kalau gagal



        Pasien::create($validated);

        return redirect()->route('erm.pasiens.index')->with('success', 'Pasien created successfully.');
    }

    public function show(Pasien $pasien)
    {
        return view('erm.pasiens.show', compact('pasien'));
    }

    public function edit(Pasien $pasien)
    {
        return view('erm.pasiens.edit', compact('pasien'));
    }

    public function update(Request $request, Pasien $pasien)
    {
        $validated = $request->validate([
            'nik' => 'required|unique:erm_pasiens,nik,' . $pasien->id,
            'nama' => 'required',
            'tanggal_lahir' => 'required|date',
            'gender' => 'required',
            'agama' => 'required',
            'marital_status' => 'required',
            'pendidikan' => 'required',
            'pekerjaan' => 'required',
            'gol_darah' => 'required',
            'notes' => 'nullable',
            'alamat' => 'required',
            'village_id' => 'required|exists:area_villages,id',
            'no_hp' => 'required',
            'no_hp2' => 'nullable',
            'email' => 'required|email',
            'instagram' => 'nullable',
        ]);

        $pasien->update($validated);

        return redirect()->route('erm.pasiens.index')->with('success', 'Pasien updated successfully.');
    }

    public function destroy(Pasien $pasien)
    {
        $pasien->delete();
        return redirect()->route('erm.pasiens.index')->with('success', 'Pasien deleted successfully.');
    }
}
