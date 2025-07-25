<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Marketing\ContentPlan;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

class ContentPlanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = ContentPlan::query();
            // Date range filter
            if ($request->filled('date_start') && $request->filled('date_end')) {
                $start = \Carbon\Carbon::createFromFormat('d/m/Y', $request->date_start)->startOfDay();
                $end = \Carbon\Carbon::createFromFormat('d/m/Y', $request->date_end)->endOfDay();
                $data->whereBetween('tanggal_publish', [$start, $end]);
            }
            // Brand filter (array)
            if ($request->filled('filter_brand')) {
                $brands = $request->filter_brand;
                if (is_string($brands)) {
                    $brands = [$brands];
                }
                $data->where(function($q) use ($brands) {
                    foreach ($brands as $brand) {
                        $q->orWhereJsonContains('brand', $brand);
                    }
                });
            }
            // Status filter
            if ($request->filled('filter_status')) {
                $data->where('status', $request->filter_status);
            }
            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    return view('marketing.content_plan.partials.actions', compact('row'))->render();
                })
                ->editColumn('brand', function ($row) {
                    if (!$row->brand || !is_array($row->brand)) return '';
                    $badges = collect($row->brand)->map(function($brand) {
                        $color = 'secondary';
                        switch (strtolower($brand)) {
                            case 'premiere belova':
                                $color = 'primary'; break;
                            case 'belova skin':
                                $color = 'purple'; break;
                            case 'bcl':
                                $color = 'pink'; break;
                        }
                        $class = 'badge badge-' . $color;
                        $style = '';
                        if ($color === 'purple') {
                            $style = 'background-color:#6f42c1;color:#fff;';
                        } elseif ($color === 'pink') {
                            $style = 'background-color:#e83e8c;color:#fff;';
                        }
                        return '<span class="' . $class . '" style="' . $style . '">' . e($brand) . '</span>';
                    });
                    return $badges->implode(' ');
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
                ->rawColumns(['action', 'brand'])
                ->make(true);
        }
        return view('marketing.content_plan.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul' => 'required|string|max:255',
            'brand' => 'nullable|array',
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
            'brand' => 'nullable|array',
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
                    // This line is misplaced, remove it (already returned in editColumn)
        $plan->delete();
        return response()->json(['success' => true]);
    }
}
