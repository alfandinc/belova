<?php

namespace App\Http\Controllers\Satusehat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\ERM\Dokter;
use App\Models\Satusehat\DokterMapping;

class DokterMappingController extends Controller
{
    public function index()
    {
        // provide klinik list for filter
        $klinikList = \App\Models\ERM\Klinik::orderBy('nama')->get();
        return view('satusehat.dokter_mapping.index', compact('klinikList'));
    }

    public function data(Request $request)
    {
        $query = Dokter::with(['spesialisasi', 'klinik', 'mapping'])->select('erm_dokters.*');

        // Apply klinik filter if provided
        if ($request->filled('klinik')) {
            $query->where('klinik_id', $request->get('klinik'));
        }

        return DataTables::eloquent($query)
            ->addColumn('nama', function (Dokter $d) {
                return $d->user ? $d->user->name : '-';
            })
            ->addColumn('nik', function (Dokter $d) {
                return $d->nik ?? '-';
            })
            ->addColumn('spesialisasi', function (Dokter $d) {
                return $d->spesialisasi ? $d->spesialisasi->nama : '-';
            })
            ->addColumn('klinik', function (Dokter $d) {
                return $d->klinik ? $d->klinik->nama : '-';
            })
            ->addColumn('mapping', function (Dokter $d) {
                return $d->mapping->mapping_code ?? '';
            })
            ->rawColumns(['mapping'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'dokter_id' => 'required|exists:erm_dokters,id',
            'mapping_code' => 'nullable|string|max:255',
        ]);

        $record = DokterMapping::updateOrCreate([
            'dokter_id' => $data['dokter_id']
        ], [
            'mapping_code' => $data['mapping_code'] ?? null
        ]);

        return response()->json(['ok' => true, 'data' => $record]);
    }
}
