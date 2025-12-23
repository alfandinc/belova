<?php

namespace App\Http\Controllers\Workdoc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Workdoc\Kemitraan;

class KemitraanController extends Controller
{
    public function index()
    {
        return view('workdoc.kemitraan.index');
    }

    // Return JSON for DataTables (simple server-side: all rows)
    public function data()
    {
        $query = Kemitraan::query();
        // optional category filter from request
        if(request()->has('category') && !empty(request('category'))){
            $query->where('category', request('category'));
        }
        // optional instansi filter from request
        if(request()->has('instansi') && !empty(request('instansi'))){
            $query->where('instansi', request('instansi'));
        }
        $rows = $query->orderBy('end_date', 'asc')->get();
        return response()->json(['data' => $rows]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'partner_name' => 'required|string|max:255',
            'category' => 'nullable|string|in:asuransi,operasional,marketing',
            'instansi' => 'nullable|string|in:Premiere Belova,Belova Skincare,BCL',
            'perihal' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|string|in:on going,terminated,posponed',
            'notes' => 'nullable|string',
            'dokumen_pks' => 'nullable|file|mimes:pdf|max:25600',
        ]);

        if ($request->hasFile('dokumen_pks')) {
            $path = $request->file('dokumen_pks')->store('workdoc/kemitraans', 'public');
            $data['dokumen_pks'] = $path;
        }

        // normalize partner_name to uppercase and default status if not provided
        if (!empty($data['partner_name'])) {
            $data['partner_name'] = mb_strtoupper($data['partner_name']);
        }
        if (empty($data['status'])) {
            $data['status'] = 'on going';
        }

        $item = Kemitraan::create($data);
        return response()->json(['success' => true, 'item' => $item]);
    }

    public function show($id)
    {
        $item = Kemitraan::findOrFail($id);
        return response()->json(['item' => $item]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'partner_name' => 'required|string|max:255',
            'category' => 'nullable|string|in:asuransi,operasional,marketing',
            'instansi' => 'nullable|string|in:Premiere Belova,Belova Skincare,BCL',
            'perihal' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|string|in:on going,terminated,posponed',
            'notes' => 'nullable|string',
            'dokumen_pks' => 'nullable|file|mimes:pdf|max:25600',
        ]);

        $item = Kemitraan::findOrFail($id);

        if ($request->hasFile('dokumen_pks')) {
            // delete old
            if ($item->dokumen_pks) {
                Storage::disk('public')->delete($item->dokumen_pks);
            }
            $path = $request->file('dokumen_pks')->store('workdoc/kemitraans', 'public');
            $data['dokumen_pks'] = $path;
        }

        // normalize partner_name to uppercase and default status if not provided
        if (!empty($data['partner_name'])) {
            $data['partner_name'] = mb_strtoupper($data['partner_name']);
        }
        if (empty($data['status'])) {
            $data['status'] = 'on going';
        }

        $item->update($data);
        return response()->json(['success' => true, 'item' => $item]);
    }

    public function destroy($id)
    {
        $item = Kemitraan::findOrFail($id);
        $item->delete();
        return response()->json(['success' => true]);
    }
}
