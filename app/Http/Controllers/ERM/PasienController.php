<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Pasien;
use App\Models\Area\Province;
use App\Models\Area\Regency;
use App\Models\Area\District;
use App\Models\Area\Village;
use App\Models\ERM\KelasPasien;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PasienController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Pasien::with(['village.district.regency.province', 'kelasPasien'])->select('erm_pasiens.*');
            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    return '
                        <a href="' . route('erm.pasiens.edit', $row->id) . '" class="btn btn-warning btn-sm">Edit</a>
                        <form action="' . route('erm.pasiens.destroy', $row->id) . '" method="POST" style="display:inline;">
                            ' . csrf_field() . method_field('DELETE') . '
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure?\')">Delete</button>
                        </form>
                    ';
                })
                ->make(true);
        }
        return view('erm.pasiens.index');
    }

    public function create()
    {
        return view('erm.pasiens.create', [
            'villages' => Village::all(),
            'kelasPasiens' => KelasPasien::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nik' => 'required|unique:erm_pasiens,nik',
            'nama' => 'required',
            'tanggal_lahir' => 'required|date',
            'gender' => 'required',
            'marital_status' => 'required',
            'pendidikan' => 'required',
            'agama' => 'required',
            'pekerjaan' => 'required',
            'alamat' => 'required',
            'village_id' => 'required|exists:villages,id',
            'kelas_pasien_id' => 'required|exists:erm_kelas_pasiens,id',
            'penanggung_jawab' => 'required',
            'no_hp_penanggung_jawab' => 'required',
        ]);

        Pasien::create($request->all());

        return redirect()->route('erm.pasiens.index')->with('success', 'Pasien created successfully.');
    }

    public function edit(Pasien $pasien)
    {
        return view('erm.pasiens.edit', [
            'pasien' => $pasien,
            'provinces' => Province::all(),
            'kelas_pasiens' => KelasPasien::all()
        ]);
    }

    public function update(Request $request, Pasien $pasien)
    {
        $request->validate([
            'nama' => 'required',
            'tanggal_lahir' => 'required|date',
            'gender' => 'required',
            'marital_status' => 'required',
            'pendidikan' => 'required',
            'agama' => 'required',
            'pekerjaan' => 'required',
            'alamat' => 'required',
            'village_id' => 'required|exists:villages,id',
            'kelas_pasien_id' => 'required|exists:erm_kelas_pasiens,id',
            'penanggung_jawab' => 'required',
            'no_hp_penanggung_jawab' => 'required',
        ]);

        $pasien->update($request->all());

        return redirect()->route('erm.pasiens.index')->with('success', 'Pasien updated successfully.');
    }

    public function destroy(Pasien $pasien)
    {
        $pasien->delete();
        return redirect()->route('erm.pasiens.index')->with('success', 'Pasien deleted successfully.');
    }
}
