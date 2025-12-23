<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Marketing\KunjunganMarketing;

class KunjunganMarketingController extends Controller
{
    public function index()
    {
        return view('marketing.kunjungan.index');
    }

    public function data()
    {
        $query = KunjunganMarketing::query();
        $rows = $query->orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $rows]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'instansi_tujuan' => 'nullable|string|max:255',
            'tanggal_kunjungan' => 'nullable|date',
            'pic' => 'nullable|string|max:255',
            'no_hp' => 'nullable|string|max:50',
            'instansi' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:100',
            'hasil_kunjungan' => 'nullable|string',
            'bukti_kunjungan' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120',
        ]);

        if ($request->hasFile('bukti_kunjungan')) {
            $path = $request->file('bukti_kunjungan')->store('marketing/kunjungan', 'public');
            $data['bukti_kunjungan'] = $path;
        }

        $item = KunjunganMarketing::create($data);
        return response()->json(['success' => true, 'item' => $item]);
    }

    public function show($id)
    {
        $item = KunjunganMarketing::findOrFail($id);
        return response()->json(['item' => $item]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'instansi_tujuan' => 'nullable|string|max:255',
            'tanggal_kunjungan' => 'nullable|date',
            'pic' => 'nullable|string|max:255',
            'no_hp' => 'nullable|string|max:50',
            'instansi' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:100',
            'hasil_kunjungan' => 'nullable|string',
            'bukti_kunjungan' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120',
        ]);

        $item = KunjunganMarketing::findOrFail($id);

        if ($request->hasFile('bukti_kunjungan')) {
            if ($item->bukti_kunjungan) {
                Storage::disk('public')->delete($item->bukti_kunjungan);
            }
            $path = $request->file('bukti_kunjungan')->store('marketing/kunjungan', 'public');
            $data['bukti_kunjungan'] = $path;
        }

        $item->update($data);
        return response()->json(['success' => true, 'item' => $item]);
    }

    public function destroy($id)
    {
        $item = KunjunganMarketing::findOrFail($id);
        if ($item->bukti_kunjungan) {
            Storage::disk('public')->delete($item->bukti_kunjungan);
        }
        $item->delete();
        return response()->json(['success' => true]);
    }
}
