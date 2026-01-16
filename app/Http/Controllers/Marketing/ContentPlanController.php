<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Marketing\ContentPlan;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

class ContentPlanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = ContentPlan::withCount('briefs')->with('assignedTo');
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
            // Platform filter (array)
            if ($request->filled('filter_platform')) {
                $platforms = $request->filter_platform;
                if (is_string($platforms)) {
                    $platforms = [$platforms];
                }
                $data->where(function($q) use ($platforms) {
                    foreach ($platforms as $platform) {
                        $q->orWhereJsonContains('platform', $platform);
                    }
                });
            }
            // Status filter
            if ($request->filled('filter_status')) {
                $data->where('status', $request->filter_status);
            }
            // Konten Pilar filter
            if ($request->filled('filter_konten_pilar')) {
                $data->where('konten_pilar', $request->filter_konten_pilar);
            }
            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    return view('marketing.content_plan.partials.actions', compact('row'))->render();
                })
                    ->addColumn('assigned_to_name', function ($row) {
                        return $row->assignedTo ? $row->assignedTo->name : '';
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
                            case 'dr fika':
                                $color = 'orange'; break;
                                
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
                    // Recreate platform icons HTML similar to the client-side mapping
                    $icons = [
                        'Instagram' => '<i class="fab fa-instagram fa-lg" title="Instagram" style="color:#E4405F; font-size:1.5em;"></i>',
                        'Facebook' => '<i class="fab fa-facebook fa-lg" title="Facebook" style="color:#1877F3; font-size:1.5em;"></i>',
                        'TikTok' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" style="vertical-align:middle;position:relative;top:-2px;" shape-rendering="geometricPrecision" text-rendering="geometricPrecision" image-rendering="optimizeQuality" fill-rule="evenodd" clip-rule="evenodd" viewBox="0 0 1000 1000"><path d="M906.25 0H93.75C42.19 0 0 42.19 0 93.75v812.49c0 51.57 42.19 93.75 93.75 93.75l812.5.01c51.56 0 93.75-42.19 93.75-93.75V93.75C1000 42.19 957.81 0 906.25 0zM684.02 319.72c-32.42-21.13-55.81-54.96-63.11-94.38-1.57-8.51-2.45-17.28-2.45-26.25H515l-.17 414.65c-1.74 46.43-39.96 83.7-86.8 83.7-14.57 0-28.27-3.63-40.35-9.99-27.68-14.57-46.63-43.58-46.63-76.97 0-47.96 39.02-86.98 86.97-86.98 8.95 0 17.54 1.48 25.66 4.01V421.89c-8.41-1.15-16.95-1.86-25.66-1.86-105.01 0-190.43 85.43-190.43 190.45 0 64.42 32.18 121.44 81.3 155.92 30.93 21.72 68.57 34.51 109.14 34.51 105.01 0 190.43-85.43 190.43-190.43V400.21c40.58 29.12 90.3 46.28 143.95 46.28V343.03c-28.89 0-55.8-8.59-78.39-23.31z"/></svg>',
                        'YouTube' => '<i class="fab fa-youtube fa-lg" title="YouTube" style="color:#FF0000; font-size:1.5em;"></i>',
                        'Website' => '<i class="fas fa-globe fa-lg" title="Website" style="color:#28a745; font-size:1.5em;"></i>',
                        'Other' => '<i class="fas fa-ellipsis-h fa-lg" title="Other" style="color:#6c757d; font-size:1.5em;"></i>'
                    ];

                    $arr = [];
                    if (is_array($row->platform)) {
                        $arr = $row->platform;
                    } elseif (is_string($row->platform)) {
                        if (strpos($row->platform, ',') !== false) {
                            $arr = array_map('trim', explode(',', $row->platform));
                        } else {
                            $arr = [trim($row->platform)];
                        }
                    }

                    $htmlParts = [];
                    foreach ($arr as $p) {
                        $icon = $icons[$p] ?? e($p);
                        $htmlParts[] = '<span style="margin-right:8px;">' . $icon . '</span>';
                    }

                    // Build badges for brand and jenis_konten to appear below the icons
                    $badgeParts = [];
                    if ($row->brand && is_array($row->brand)) {
                        $bparts = [];
                        foreach ($row->brand as $brand) {
                            $color = 'secondary';
                            switch (strtolower($brand)) {
                                case 'premiere belova': $color = 'primary'; break;
                                case 'belova skin': $color = 'purple'; break;
                                case 'bcl': $color = 'pink'; break;
                                case 'dr fika': $color = 'orange'; break;
                            }
                            $style = '';
                            if ($color === 'purple') $style = 'background-color:#6f42c1;color:#fff;';
                            if ($color === 'pink') $style = 'background-color:#e83e8c;color:#fff;';
                            $bparts[] = '<span class="badge badge-' . $color . '" style="' . $style . '">' . e($brand) . '</span>';
                        }
                        if (!empty($bparts)) $badgeParts[] = implode(' ', $bparts);
                    }

                    if ($row->jenis_konten && is_array($row->jenis_konten)) {
                        $jparts = [];
                        foreach ($row->jenis_konten as $k) {
                            $color = 'secondary';
                            switch (strtolower($k)) {
                                case 'feed': $color = 'primary'; break;
                                case 'story': $color = 'info'; break;
                                case 'reels': $color = 'danger'; break;
                                case 'artikel': $color = 'success'; break;
                                case 'other': $color = 'secondary'; break;
                            }
                            $jparts[] = '<span class="badge badge-' . $color . '" style="margin-right:4px">' . e($k) . '</span>';
                        }
                        if (!empty($jparts)) $badgeParts[] = implode(' ', $jparts);
                    }

                    $badgesHtml = '';
                    if (!empty($badgeParts)) {
                        $badgesHtml = '<div class="mt-1">' . implode(' ', $badgeParts) . '</div>';
                    }

                    return implode(' ', $htmlParts) . $badgesHtml;
                })
                ->editColumn('jenis_konten', function ($row) {
                    if (!$row->jenis_konten || !is_array($row->jenis_konten)) return '';
                    $badges = collect($row->jenis_konten)->map(function($k) {
                        $color = 'secondary';
                        switch (strtolower($k)) {
                            case 'feed': $color = 'primary'; break;
                            case 'story': $color = 'info'; break;
                            case 'reels': $color = 'danger'; break;
                            case 'artikel': $color = 'success'; break;
                            case 'other': $color = 'secondary'; break;
                        }
                        $class = 'badge badge-' . $color;
                        return '<span class="' . $class . '" style="margin-right:4px">' . e($k) . '</span>';
                    });
                    return $badges->implode(' ');
                })
                ->editColumn('konten_pilar', function ($row) {
                    return $row->konten_pilar ? e($row->konten_pilar) : '';
                })
                ->editColumn('tanggal_publish', function ($row) {
                    return $row->tanggal_publish ? $row->tanggal_publish->toIso8601String() : '';
                })
                ->editColumn('judul', function ($row) {
                    $title = '<strong>' . e($row->judul) . '</strong>';
                    $parts = [];

                    // Keep only konten_pilar under title
                    if ($row->konten_pilar) {
                        $map = [
                            'Edukasi' => ['cls' => 'primary', 'style' => ''],
                            'Awareness' => ['cls' => 'warning', 'style' => ''],
                            'Engagement/Interaktif' => ['cls' => 'success', 'style' => ''],
                            'Promo/Testimoni' => ['cls' => 'danger', 'style' => ''],
                            'Lifestyle/Tips' => ['cls' => 'info', 'style' => '']
                        ];
                        if (is_array($row->konten_pilar)) {
                            $kp = collect($row->konten_pilar)->map(function($d) use ($map) {
                                $m = $map[$d] ?? ['cls' => 'secondary', 'style' => ''];
                                return '<span class="badge badge-' . $m['cls'] . '" style="' . $m['style'] . ';margin-right:6px">' . e($d) . '</span>';
                            })->implode(' ');
                        } else {
                            $v = $row->konten_pilar;
                            $m = $map[$v] ?? ['cls' => 'secondary', 'style' => ''];
                            $kp = '<span class="badge badge-' . $m['cls'] . '" style="' . $m['style'] . '">' . e($v) . '</span>';
                        }
                        $parts[] = $kp;
                    }

                    $badgesHtml = '';
                    if (!empty($parts)) {
                        $badgesHtml = '<div class="mt-1">' . implode(' ', $parts) . '</div>';
                    }

                    return $title . $badgesHtml;
                })
                ->rawColumns(['action', 'konten_pilar', 'judul', 'platform'])
                ->make(true);
        }
        $users = User::orderBy('name')->get(['id','name']);
        return view('marketing.content_plan.index', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul' => 'required|string|max:255',
            'brand' => 'nullable|array',
            'deskripsi' => 'nullable|string',
            'caption' => 'nullable|string',
            'mention' => 'nullable|string',
            'tanggal_publish' => 'required|date',
            'platform' => 'required|array',
            'status' => 'required|string',
            'konten_pilar' => 'nullable|string|in:Edukasi,Awareness,Engagement/Interaktif,Promo/Testimoni,Lifestyle/Tips',
            'jenis_konten' => 'required|array',
            'target_audience' => 'nullable|string',
            'link_asset' => 'nullable|string',
            'link_publikasi' => 'nullable|array',
            'link_publikasi.*' => 'nullable|string',
            'catatan' => 'nullable|string',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'gambar_referensi' => 'nullable|file|image|max:5120',
        ]);
        $data['platform'] = array_values($data['platform']);
        $data['jenis_konten'] = array_values($data['jenis_konten']);
        // Normalize link_publikasi to sequential associative array if provided
        if (isset($data['link_publikasi']) && is_array($data['link_publikasi'])) {
            // remove empty values
            $data['link_publikasi'] = array_filter($data['link_publikasi'], function($v){ return $v !== null && $v !== '' ; });
        } else {
            unset($data['link_publikasi']);
        }
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
            'caption' => 'nullable|string',
            'mention' => 'nullable|string',
            'tanggal_publish' => 'required|date',
            'platform' => 'required|array',
            'status' => 'required|string',
            'konten_pilar' => 'nullable|string|in:Edukasi,Awareness,Engagement/Interaktif,Promo/Testimoni,Lifestyle/Tips',
            'jenis_konten' => 'required|array',
            'target_audience' => 'nullable|string',
            'link_asset' => 'nullable|string',
            'link_publikasi' => 'nullable|array',
            'link_publikasi.*' => 'nullable|string',
            'catatan' => 'nullable|string',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'gambar_referensi' => 'nullable|file|image|max:5120',
        ]);
        $data['platform'] = array_values($data['platform']);
        $data['jenis_konten'] = array_values($data['jenis_konten']);
        if (isset($data['link_publikasi']) && is_array($data['link_publikasi'])) {
            $data['link_publikasi'] = array_filter($data['link_publikasi'], function($v){ return $v !== null && $v !== '' ; });
        } else {
            unset($data['link_publikasi']);
        }
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

    /**
     * Inline update for specific fields. Accepts partial data for
     * 'brand', 'platform', 'jenis_konten', and 'status'.
     */
    public function inlineUpdate(Request $request, $id)
    {
        $plan = ContentPlan::findOrFail($id);
        $data = $request->validate([
            'brand' => 'nullable|array',
            'platform' => 'nullable|array',
            'jenis_konten' => 'nullable|array',
            'status' => 'nullable|string',
        ]);

        // Normalize arrays to sequential arrays for JSON columns
        if (isset($data['brand']) && is_array($data['brand'])) {
            $data['brand'] = array_values($data['brand']);
        }
        if (isset($data['platform']) && is_array($data['platform'])) {
            $data['platform'] = array_values($data['platform']);
        }
        if (isset($data['jenis_konten']) && is_array($data['jenis_konten'])) {
            $data['jenis_konten'] = array_values($data['jenis_konten']);
        }

        $plan->update($data);

        return response()->json(['success' => true, 'data' => $plan]);
    }
}
