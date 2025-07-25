<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Marketing\ContentPlan;
use Yajra\DataTables\DataTables;

class ContentPlanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = ContentPlan::query();
            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    return view('marketing.content_plan.partials.actions', compact('row'))->render();
                })
                ->editColumn('platform', function ($row) {
                    return collect($row->platform)->join(', ');
                })
                ->editColumn('jenis_konten', function ($row) {
                    return collect($row->jenis_konten)->join(', ');
                })
                ->editColumn('tanggal_publish', function ($row) {
                    return $row->tanggal_publish ? $row->tanggal_publish->format('Y-m-d H:i') : '';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('marketing.content_plan.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tanggal_publish' => 'required|date',
            'platform' => 'required|array',
            'status' => 'required|string',
            'jenis_konten' => 'required|array',
            'target_audience' => 'nullable|string',
            'link_asset' => 'nullable|string',
            'link_publikasi' => 'nullable|string',
            'catatan' => 'nullable|string',
            'gambar_referensi' => 'nullable|file|image|max:5120',
        ]);
        $data['platform'] = array_values($data['platform']);
        $data['jenis_konten'] = array_values($data['jenis_konten']);
        if ($request->hasFile('gambar_referensi')) {
            $file = $request->file('gambar_referensi');
            $path = $file->store('uploads/gambar_referensi', 'public');
            $data['gambar_referensi'] = $path;
        } else {
            unset($data['gambar_referensi']);
        }
        $plan = ContentPlan::create($data);
        return response()->json(['success' => true, 'data' => $plan]);
    }

    public function show($id)
    {
        $plan = ContentPlan::findOrFail($id);
        return response()->json($plan);
    }

    public function update(Request $request, $id)
    {
        $plan = ContentPlan::findOrFail($id);
        $data = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tanggal_publish' => 'required|date',
            'platform' => 'required|array',
            'status' => 'required|string',
            'jenis_konten' => 'required|array',
            'target_audience' => 'nullable|string',
            'link_asset' => 'nullable|string',
            'link_publikasi' => 'nullable|string',
            'catatan' => 'nullable|string',
            'gambar_referensi' => 'nullable|file|image|max:5120',
        ]);
        $data['platform'] = array_values($data['platform']);
        $data['jenis_konten'] = array_values($data['jenis_konten']);
        if ($request->hasFile('gambar_referensi')) {
            $file = $request->file('gambar_referensi');
            $path = $file->store('uploads/gambar_referensi', 'public');
            $data['gambar_referensi'] = $path;
        } else {
            unset($data['gambar_referensi']);
        }
        $plan->update($data);
        return response()->json(['success' => true, 'data' => $plan]);
    }

    public function destroy($id)
    {
        $plan = ContentPlan::findOrFail($id);
        $plan->delete();
        return response()->json(['success' => true]);
    }
}
