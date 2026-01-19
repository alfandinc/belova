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
                    // Render platform as colored badges instead of icons
                    $badgeColors = [
                        'Instagram' => ['bg' => '#E4405F', 'color' => '#ffffff'],
                        'TikTok' => ['bg' => '#000000', 'color' => '#ffffff'],
                        'YouTube' => ['bg' => '#FF0000', 'color' => '#ffffff'],
                        'Facebook' => ['bg' => '#1877F3', 'color' => '#ffffff'],
                        'Website' => ['bg' => '#FFC107', 'color' => '#212529'],
                        'Other' => ['bg' => '#6c757d', 'color' => '#ffffff'],
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
                        $c = $badgeColors[$p] ?? ['bg' => '#6c757d', 'color' => '#ffffff'];
                        $style = 'background-color:' . $c['bg'] . ';color:' . $c['color'] . ';margin-right:6px;';
                        $htmlParts[] = '<span class="badge" title="' . e($p) . '" style="' . $style . '">' . e($p) . '</span>';
                    }

                    // do not include brand/jenis_konten here; they are moved into judul
                    return implode(' ', $htmlParts);
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
                    $title = '<strong>' . e(mb_strtoupper($row->judul ?? '', 'UTF-8')) . '</strong>';
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

                    // (brand badges removed — not displayed under title)

                    // Append jenis_konten badges under the title as well
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
                        if (!empty($jparts)) $parts[] = implode(' ', $jparts);
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
        // Only include users with the 'Marketing' role for assignment
        try {
            $users = User::role('Marketing')->orderBy('name')->get(['id','name']);
        } catch (\Throwable $e) {
            // Fallback to all users if role scope isn't available or role missing
            $users = User::orderBy('name')->get(['id','name']);
        }
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
        $plan = ContentPlan::with('briefs')->findOrFail($id);
        // include latest brief data (if any) to make it easier for the front-end to populate the Brief tab
        $latestBrief = $plan->briefs()->orderBy('created_at', 'desc')->first();
        $out = $plan->toArray();
        if ($latestBrief) {
            $out['brief'] = [
                'id' => $latestBrief->id,
                'headline' => $latestBrief->headline,
                'sub_headline' => $latestBrief->sub_headline,
                'isi_konten' => $latestBrief->isi_konten,
                'visual_references' => $latestBrief->visual_references,
            ];
            $out['brief_id'] = $latestBrief->id;
            // also expose top-level keys for backward compatibility
            $out['headline'] = $latestBrief->headline;
            $out['sub_headline'] = $latestBrief->sub_headline;
            $out['isi_konten'] = $latestBrief->isi_konten;
            $out['visual_references'] = $latestBrief->visual_references;
        }
        return response()->json($out);
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

    /**
     * Return all content plans for a week range as grouped JSON (keyed by YYYY-MM-DD).
     * Accepts same filters as index: date_start, date_end, filter_brand, filter_platform, filter_status, filter_konten_pilar
     */
    public function week(Request $request)
    {
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
            if (is_string($brands)) $brands = [$brands];
            $data->where(function($q) use ($brands) {
                foreach ($brands as $brand) {
                    $q->orWhereJsonContains('brand', $brand);
                }
            });
        }
        // Platform filter (array)
        if ($request->filled('filter_platform')) {
            $platforms = $request->filter_platform;
            if (is_string($platforms)) $platforms = [$platforms];
            $data->where(function($q) use ($platforms) {
                foreach ($platforms as $platform) {
                    $q->orWhereJsonContains('platform', $platform);
                }
            });
        }
        if ($request->filled('filter_status')) {
            $data->where('status', $request->filter_status);
        }
        if ($request->filled('filter_konten_pilar')) {
            $data->where('konten_pilar', $request->filter_konten_pilar);
        }

        $items = $data->orderBy('tanggal_publish')->get();

        $rows = $items->map(function($row) {
            // build judul html (keep konten_pilar under title)
            $title = '<strong>' . e(mb_strtoupper($row->judul ?? '', 'UTF-8')) . '</strong>';
            $parts = [];
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
            // Build jenis_konten badges
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
                if (!empty($jparts)) $parts[] = implode(' ', $jparts);
            }

            // delay building judul_html until platform badges are available so all badges can be in one row
            $judul_html = null;

            // platform HTML (render as colored badges)
            $badgeColors = [
                'Instagram' => ['bg' => '#E4405F', 'color' => '#ffffff'],
                'TikTok' => ['bg' => '#000000', 'color' => '#ffffff'],
                'YouTube' => ['bg' => '#FF0000', 'color' => '#ffffff'],
                'Facebook' => ['bg' => '#1877F3', 'color' => '#ffffff'],
                'Website' => ['bg' => '#FFC107', 'color' => '#212529'],
                'Other' => ['bg' => '#6c757d', 'color' => '#ffffff'],
            ];
            $arr = [];
            if (is_array($row->platform)) $arr = $row->platform;
            elseif (is_string($row->platform)) $arr = strpos($row->platform, ',') !== false ? array_map('trim', explode(',', $row->platform)) : [trim($row->platform)];
            $htmlParts = [];
            foreach ($arr as $p) {
                // link mapping
                $link = null;
                if ($row->link_publikasi) {
                    if (is_array($row->link_publikasi) && count($row->link_publikasi) === count($arr)) {
                        $idx = array_search($p, $arr);
                        if ($idx !== false && isset($row->link_publikasi[$idx])) $link = $row->link_publikasi[$idx];
                    } elseif (is_array($row->link_publikasi) && isset($row->link_publikasi[$p])) {
                        $link = $row->link_publikasi[$p];
                    } elseif (is_string($row->link_publikasi)) {
                        $parts = strpos($row->link_publikasi, '||') !== false ? array_map('trim', explode('||', $row->link_publikasi)) : (strpos($row->link_publikasi, ',') !== false ? array_map('trim', explode(',', $row->link_publikasi)) : [$row->link_publikasi]);
                        if (count($parts) === count($arr)) {
                            $idx = array_search($p, $arr);
                            if ($idx !== false) $link = $parts[$idx] ?? null;
                        } elseif (count($parts) > 0) {
                            $link = $parts[0];
                        }
                    }
                }
                $c = $badgeColors[$p] ?? ['bg' => '#6c757d', 'color' => '#ffffff'];
                $style = 'background-color:' . $c['bg'] . ';color:' . $c['color'] . ';margin-right:6px;';
                $badgeHtml = '<span class="badge" title="' . e($p) . '" style="' . $style . '">' . e($p) . '</span>';
                if ($link) $htmlParts[] = '<a href="' . e($link) . '" target="_blank" style="margin-right:6px;display:inline-block">' . $badgeHtml . '</a>';
                else $htmlParts[] = $badgeHtml;
            }
            // platform_html contains only platform badges (we will merge into judul_html)
            $platform_html = implode(' ', $htmlParts);

            // build final judul_html including konten_pilar, jenis_konten, and platform badges in one badge row
            $badgeInner = trim(implode(' ', $parts));
            if ($platform_html) {
                $badgeInner = trim($badgeInner . ' ' . $platform_html);
            }
            if ($badgeInner) {
                $judul_html = $title . '<div class="mt-1">' . $badgeInner . '</div>';
            } else {
                $judul_html = $title;
            }

            // clear platform_html so client doesn't duplicate badges
            $platform_html = '';

            $actionHtml = view('marketing.content_plan.partials.actions', ['row' => $row])->render();

            return [
                'id' => $row->id,
                'judul_html' => $judul_html,
                'platform_html' => $platform_html,
                'status' => $row->status,
                'action_html' => $actionHtml,
                'tanggal_publish' => $row->tanggal_publish ? $row->tanggal_publish->toIso8601String() : null,
                'assigned_to_name' => $row->assignedTo ? $row->assignedTo->name : '',
                'briefs_count' => $row->briefs_count ?? 0,
                'brand' => $row->brand,
                'jenis_konten' => $row->jenis_konten,
                'konten_pilar' => $row->konten_pilar,
                'link_publikasi' => $row->link_publikasi,
            ];
        });

        // group by date (Y-m-d)
        $grouped = $rows->groupBy(function($r){
            return $r['tanggal_publish'] ? \Carbon\Carbon::parse($r['tanggal_publish'])->format('Y-m-d') : 'no_date';
        })->toArray();

        return response()->json(['data' => $grouped]);
    }

    /**
     * Return flat list of items for a given status (or all) to populate the status-list modal.
     * Accepts same filters as week() and optional `status` ('Published','Scheduled','Draft','Cancelled').
     */
    public function statusList(Request $request)
    {
        $data = ContentPlan::with('assignedTo');
        // Date range filter
        if ($request->filled('date_start') && $request->filled('date_end')) {
            $start = \Carbon\Carbon::createFromFormat('d/m/Y', $request->date_start)->startOfDay();
            $end = \Carbon\Carbon::createFromFormat('d/m/Y', $request->date_end)->endOfDay();
            $data->whereBetween('tanggal_publish', [$start, $end]);
        }
        // Status filter
        if ($request->filled('status')) {
            $statusParam = strtolower($request->status);
            if ($statusParam === 'all') {
                // no-op
            } elseif ($statusParam === 'terlewat' || $statusParam === 'overdue') {
                // scheduled or draft and tanggal_publish before now
                $now = \Carbon\Carbon::now();
                $data->where(function($q) use ($now) {
                    $q->whereIn('status', ['Scheduled','Draft'])
                      ->where('tanggal_publish', '<', $now);
                });
            } else {
                $data->where('status', $request->status);
            }
        }
        // Brand filter (array)
        if ($request->filled('filter_brand')) {
            $brands = $request->filter_brand;
            if (is_string($brands)) $brands = [$brands];
            // keep existing brand filtering behavior if needed
            $data->where(function($q) use ($brands) {
                foreach ($brands as $b) {
                    $q->orWhereJsonContains('brand', $b);
                }
            });
        }
        // Platform filter (array)
        if ($request->filled('filter_platform')) {
            $platforms = $request->filter_platform;
            if (is_string($platforms)) $platforms = [$platforms];
            $data->where(function($q) use ($platforms) {
                foreach ($platforms as $p) {
                    $q->orWhereJsonContains('platform', $p);
                }
            });
        }

        $items = $data->orderBy('tanggal_publish')->get();

        $rows = $items->map(function($row) {
            // plain title (remove any appended tokens like konten_pilar or platform names)
            $title = $row->judul ?? '';
            // remove platform/konten_pilar tokens if present
            if ($row->konten_pilar) {
                $title = str_ireplace($row->konten_pilar, '', $title);
            }
            if ($row->platform) {
                if (is_array($row->platform)) {
                    foreach ($row->platform as $p) { $title = str_ireplace($p, '', $title); }
                } else { $title = str_ireplace($row->platform, '', $title); }
            }
            // collapse whitespace
            $title = trim(preg_replace('/\s+/', ' ', $title));

            $day = $row->tanggal_publish ? \Carbon\Carbon::parse($row->tanggal_publish)->format('l, j M Y') : '';
            $time = $row->tanggal_publish ? \Carbon\Carbon::parse($row->tanggal_publish)->format('H.i') : '';
            $brand = '';
            if ($row->brand) {
                if (is_array($row->brand)) $brand = implode(', ', $row->brand);
                else $brand = $row->brand;
            }
            $assigned = $row->assignedTo ? $row->assignedTo->name : ($row->assigned_to_name ?? $row->assigned_to ?? '');

            return [
                'id' => $row->id,
                'day' => $day,
                'time' => $time,
                'judul' => $title,
                'brand' => $brand,
                'status' => $row->status,
                'assigned' => $assigned,
            ];
        })->toArray();

        return response()->json(['data' => $rows]);
    }
}
