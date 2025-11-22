<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\ERM\Obat;
use App\Models\Satusehat\ObatKfa;
use App\Models\ERM\MetodeBayar;
use Illuminate\Support\Facades\Log;

class ObatKfaController extends Controller
{
    public function index()
    {
        // provide filter lists to the view
        $metodeBayarList = MetodeBayar::orderBy('nama')->get();
        $kategoriList = Obat::select('kategori')->distinct()->whereNotNull('kategori')->pluck('kategori')->filter()->values();

        return view('satusehat.obat_kfa.index', compact('metodeBayarList', 'kategoriList'));
    }

    /**
     * Data for Yajra DataTable
     */
    public function data(Request $request)
    {
        $query = Obat::with(['metodeBayar', 'kfa'])->select('erm_obat.*');

        // Log incoming filters for debugging
        Log::debug('ObatKfaController::data filters', $request->only('metode_bayar', 'kategori', 'has_kfa'));
        // Also log all input and raw content to help diagnose why filters may be missing
        Log::debug('ObatKfaController::data request_all', $request->all());
        Log::debug('ObatKfaController::data raw_content', ['content' => $request->getContent()]);

        // Apply filters directly on the query before DataTables as well (robust)
        if ($request->filled('metode_bayar')) {
            $query->where('metode_bayar_id', $request->get('metode_bayar'));
        }
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->get('kategori'));
        }
        // has_kfa: 'with' => whereHas('kfa'); 'without' => whereDoesntHave('kfa')
        if ($request->filled('has_kfa')) {
            $val = $request->get('has_kfa');
            Log::debug('ObatKfaController::data has_kfa value', ['has_kfa' => $val]);
            if ($val === 'with') {
                $query->whereHas('kfa');
            } elseif ($val === 'without') {
                $query->whereDoesntHave('kfa');
            }
        }

        // Use eloquent() to build DataTables response
        return DataTables::eloquent($query)
            ->addColumn('metode_bayar', function (Obat $o) {
                return $o->metodeBayar ? $o->metodeBayar->nama : '-';
            })
            ->addColumn('kategori', function (Obat $o) {
                return $o->kategori ?? '-';
            })
            ->addColumn('kfa', function (Obat $o) {
                return $o->kfa->kfa_code ?? '';
            })
            ->rawColumns(['kfa'])
            ->make(true);
    }

    /**
     * Store or update KFA code via AJAX
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'obat_id' => 'required|exists:erm_obat,id',
            'kfa_code' => 'nullable|string|max:255',
        ]);

        $record = ObatKfa::updateOrCreate(
            ['obat_id' => $data['obat_id']],
            ['kfa_code' => $data['kfa_code'] ?? null]
        );

        return response()->json(['ok' => true, 'data' => $record]);
    }
}
