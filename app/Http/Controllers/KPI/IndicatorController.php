<?php

namespace App\Http\Controllers\KPI;

use App\Http\Controllers\Controller;
use App\Models\HRD\Position;
use App\Models\KPI\KpiIndicator;
use App\Models\KPI\KpiPositionIndicator;
use App\Models\KPI\KpiIndicatorCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class IndicatorController extends Controller
{
    public function index()
    {
        return view('kpi.indicator.index', [
            'positions' => Position::orderBy('name')->get(['id', 'name','division_id','parent_id']),
            'divisions' => \App\Models\HRD\Division::orderBy('name')->get(['id','name']),
            'categories' => KpiIndicatorCategory::orderBy('category_name')->get(['id', 'category_name']),
        ]);
    }

    public function categoryData(Request $request)
    {
        $query = KpiIndicatorCategory::query()
            ->withCount('indicators')
            ->with('evaluatorPosition:id,name');

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('evaluator_type_label', function (KpiIndicatorCategory $category) {
                return match ($category->evaluator_type) {
                    'direct_parent' => 'Direct Parent',
                    'specific_position' => 'Specific Position',
                    'bottom_up' => 'Bottom Up',
                    default => $category->evaluator_type,
                };
            })
            ->addColumn('evaluator_position_name', function (KpiIndicatorCategory $category) {
                return optional($category->evaluatorPosition)->name ?? '-';
            })
            ->addColumn('is_active_badge', function (KpiIndicatorCategory $category) {
                return $category->is_active
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-secondary">Inactive</span>';
            })
            ->addColumn('action', function (KpiIndicatorCategory $category) {
                return '<div class="btn-group btn-group-sm" role="group">'
                    . '<button type="button" class="btn btn-info btn-edit-category" data-id="' . $category->id . '"><i class="fas fa-edit"></i></button>'
                    . '<button type="button" class="btn btn-danger btn-delete-category" data-id="' . $category->id . '" data-name="' . e($category->category_name) . '"><i class="fas fa-trash"></i></button>'
                    . '</div>';
            })
            ->rawColumns(['is_active_badge', 'action'])
            ->make(true);
    }

    public function indicatorData(Request $request)
    {
        $query = KpiIndicator::query()->with('category:id,category_name')->withCount('positionIndicators');
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $totalPositions = Position::count();

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('category_name', function (KpiIndicator $indicator) {
                return optional($indicator->category)->category_name ?? '-';
            })
            ->addColumn('position_mapped', function (KpiIndicator $indicator) use ($totalPositions) {
                $count = (int) ($indicator->position_indicators_count ?? 0);
                if ($count > 0 && $totalPositions > 0 && $count >= $totalPositions) {
                    return 'All Position';
                }
                return (string) $count;
            })
            ->addColumn('is_active_badge', function (KpiIndicator $indicator) {
                return $indicator->is_active
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-secondary">Inactive</span>';
            })
            ->addColumn('action', function (KpiIndicator $indicator) {
                return '<div class="btn-group btn-group-sm" role="group">'
                    . '<button type="button" class="btn btn-info btn-edit-indicator" data-id="' . $indicator->id . '"><i class="fas fa-edit"></i></button>'
                    . '<button type="button" class="btn btn-danger btn-delete-indicator" data-id="' . $indicator->id . '" data-name="' . e($indicator->indicator_name) . '"><i class="fas fa-trash"></i></button>'
                    . '</div>';
            })
            ->rawColumns(['is_active_badge', 'action'])
            ->make(true);
    }

    public function meta(): JsonResponse
    {
        return response()->json([
            'positions' => Position::orderBy('name')->get(['id', 'name']),
            'categories' => KpiIndicatorCategory::orderBy('category_name')->get(['id', 'category_name']),
        ]);
    }

    /**
     * Return total weight percentage for all categories.
     */
    public function categoryTotal(): JsonResponse
    {
        $total = (float) KpiIndicatorCategory::sum('weight_percentage');

        return response()->json([
            'success' => true,
            'total' => $total,
        ]);
    }

    /**
     * Return indicators mapped for a given position with weights and total.
     */
    public function positionMappings(Position $position): JsonResponse
    {
        $mappings = KpiPositionIndicator::where('position_id', $position->id)
            ->with(['indicator:id,indicator_name,category_id', 'indicator.category:id,category_name'])
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'indicator_id' => $m->indicator_id,
                    'indicator_name' => optional($m->indicator)->indicator_name,
                    'category_id' => optional($m->indicator)->category_id,
                    'category_name' => optional($m->indicator->category)->category_name,
                    'weight_percentage' => (float) $m->weight_percentage,
                ];
            });

        $total = $mappings->sum('weight_percentage');

        return response()->json([
            'success' => true,
            'data' => $mappings,
            'total' => $total,
        ]);
    }

    /**
     * Update weight percentages for indicators for a given position and category.
     * Expects payload: { mappings: [ { indicator_id, weight_percentage } ], category_id }
     */
    public function positionMappingsUpdate(Request $request, Position $position): JsonResponse
    {
        $validated = $request->validate([
            'mappings' => ['required', 'array'],
            'mappings.*.indicator_id' => ['required', 'integer', 'exists:kpi_indicators,id'],
            'mappings.*.weight_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'category_id' => ['required', 'integer', 'exists:kpi_indicator_categories,id'],
        ]);

        $mappings = collect($validated['mappings']);
        $sum = $mappings->sum(function ($m) { return isset($m['weight_percentage']) ? (float)$m['weight_percentage'] : 0.0; });

        if (abs($sum - 100.0) > 0.001) {
            return response()->json(['success' => false, 'message' => 'Total weight must equal 100% (current: ' . number_format($sum,2) . '%)'], 422);
        }

        // Update each mapping for this position
        foreach ($mappings as $m) {
            $indicatorId = (int) $m['indicator_id'];
            $weight = isset($m['weight_percentage']) ? $m['weight_percentage'] : null;
            KpiPositionIndicator::updateOrCreate(
                ['position_id' => $position->id, 'indicator_id' => $indicatorId],
                ['weight_percentage' => $weight]
            );
        }

        return response()->json(['success' => true, 'message' => 'Mappings updated successfully.']);
    }

    /**
     * Data for positions table showing mapped indicators per position.
     */
    public function positionData(Request $request)
    {
        // include division_id so we can resolve division name later
        $positionsQuery = Position::orderBy('name');
        if ($request->filled('division_id')) {
            $positionsQuery->where('division_id', $request->input('division_id'));
        }

        $positions = $positionsQuery->get(['id', 'name', 'division_id']);

        $categories = KpiIndicatorCategory::where('is_active', 1)->orderBy('category_name')->get(['id', 'category_name']);

        $rows = $positions->map(function ($p) use ($categories) {
            // count active employees for this position (exclude status 'tidak aktif')
            $activeEmployees = $p->employees()->whereRaw('LOWER(status) <> ?', ['tidak aktif'])->count();

            $mappings = KpiPositionIndicator::where('position_id', $p->id)
                ->with(['indicator:id,indicator_name,category_id', 'indicator.category:id,category_name'])
                ->get();

            $parts = [];
            $hasIssue = false;

            foreach ($categories as $cat) {
                $items = $mappings->filter(function ($m) use ($cat) {
                    return optional($m->indicator)->category_id == $cat->id;
                });
                $sum = $items->sum(function ($it) { return (float) $it->weight_percentage; });
                $sumFmt = number_format($sum, 2);

                // choose badge color
                if (abs($sum - 100.0) <= 0.001) {
                    $badgeClass = 'badge-success';
                } elseif ($sum == 0.0) {
                    $badgeClass = 'badge-secondary';
                } else {
                    $badgeClass = 'badge-danger';
                    $hasIssue = true;
                }

                $parts[] = '<span class="badge ' . $badgeClass . ' mr-1 mb-1 category-badge" style="cursor:pointer" data-pos-id="' . $p->id . '" data-cat-id="' . $cat->id . '" data-cat-name="' . e($cat->category_name) . '">'
                    . e($cat->category_name) . ' <strong>' . $sumFmt . '%</strong></span>';
            }

            return [
                'id' => $p->id,
                'name' => $p->name,
                'division_name' => optional($p->division)->name,
                'employee_count' => $activeEmployees,
                'indicators_count' => $mappings->count(),
                'category_percentages' => implode('', $parts),
                'has_issue' => $hasIssue ? 1 : 0,
            ];
        });

        // filter out positions with no active employees
        $rows = $rows->filter(function ($r) {
            return isset($r['employee_count']) && $r['employee_count'] > 0;
        })->values();

        return DataTables::of($rows)->addIndexColumn()->rawColumns(['category_percentages'])->make(true);
    }

    public function importPreview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file'],
            'type' => ['required', Rule::in(['categories', 'indicators'])],
        ]);

        $file = $request->file('file');
        $type = $request->input('type');

        // parse file (CSV fallback)
        $rows = [];
        $ext = strtolower($file->getClientOriginalExtension());

        try {
            if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory') && in_array($ext, ['xlsx','xls','csv'])) {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file->getPathname());
                $spreadsheet = $reader->load($file->getPathname());
                $sheet = $spreadsheet->getActiveSheet();
                $data = $sheet->toArray(null, true, true, true);
                // first row headings
                $headers = array_map(function($h){ return trim((string)$h); }, array_values($data[1] ?? []));
                foreach ($data as $index => $line) {
                    if ($index === 1) continue;
                    $row = [];
                    $i = 0;
                    foreach ($line as $cell) {
                        $row[$headers[$i] ?? 'col' . $i] = $cell;
                        $i++;
                    }
                    $rows[] = $row;
                }
            } else {
                // CSV simple parser
                $content = file_get_contents($file->getPathname());
                $lines = array_map('trim', explode("\n", $content));
                $headers = [];
                foreach ($lines as $idx => $line) {
                    if ($line === '') continue;
                    $cols = str_getcsv($line);
                    if ($idx === 0) {
                        $headers = array_map('trim', $cols);
                        continue;
                    }
                    $row = [];
                    foreach ($cols as $i => $cell) {
                        $row[$headers[$i] ?? 'col' . $i] = $cell;
                    }
                    $rows[] = $row;
                }
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to parse file: ' . $e->getMessage()], 422);
        }

        // normalize keys: lowercase, underscore
        $normalized = array_map(function($r){
            $out = [];
            foreach ($r as $k => $v) {
                $key = Str::of($k)->trim()->lower()->replace(' ', '_')->__toString();
                $out[$key] = is_null($v) ? null : trim((string)$v);
            }
            return $out;
        }, $rows);

        // validate each row depending on type
        $preview = [];
        foreach ($normalized as $i => $row) {
            $item = ['row_number' => $i+1, 'raw' => $row, 'errors' => []];
            if ($type === 'categories') {
                if (empty($row['category_name'])) $item['errors'][] = 'category_name is required';
                if (isset($row['weight_percentage']) && $row['weight_percentage'] !== '' && !is_numeric($row['weight_percentage'])) $item['errors'][] = 'weight_percentage must be numeric';
                // evaluator_position must be provided as ID (evaluator_position_id)
                if (!empty($row['evaluator_position_id'])) {
                    if (!is_numeric($row['evaluator_position_id'])) {
                        $item['errors'][] = 'evaluator_position_id must be numeric';
                    } else {
                        $pos = Position::find((int)$row['evaluator_position_id']);
                        if (!$pos) $item['errors'][] = 'evaluator_position_id not found';
                        else $item['evaluator_position_id'] = $pos->id;
                    }
                }
            } else {
                // indicators: require category_id (numeric)
                if (empty($row['indicator_name'])) $item['errors'][] = 'indicator_name is required';
                if (empty($row['category_id'])) {
                    $item['errors'][] = 'category_id is required';
                } else {
                    if (!is_numeric($row['category_id'])) {
                        $item['errors'][] = 'category_id must be numeric';
                    } else {
                        $cat = KpiIndicatorCategory::find((int)$row['category_id']);
                        if ($cat) $item['category_id'] = $cat->id;
                        else $item['errors'][] = 'category_id not found';
                    }
                }
                if (isset($row['weight_percentage']) && $row['weight_percentage'] !== '' && !is_numeric($row['weight_percentage'])) $item['errors'][] = 'weight_percentage must be numeric';
                // position must be provided as ID (position_id)
                if (!empty($row['position_id'])) {
                    if (!is_numeric($row['position_id'])) {
                        $item['errors'][] = 'position_id must be numeric';
                    } else {
                        $pos = Position::find((int)$row['position_id']);
                        if (!$pos) $item['errors'][] = 'position_id not found';
                        else $item['position_id'] = $pos->id;
                    }
                }
            }
            $preview[] = $item;
        }

        return response()->json(['success' => true, 'data' => $preview]);
    }

    public function importCommit(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', Rule::in(['categories', 'indicators'])],
            'rows' => ['required', 'array'],
        ]);

        $type = $request->input('type');
        $rows = $request->input('rows');
        $created = 0;
        $errors = [];

        foreach ($rows as $i => $item) {
            $raw = $item['raw'] ?? [];
            $rowErrors = $item['errors'] ?? [];
            if (!empty($rowErrors)) {
                $errors[] = ['row' => $i+1, 'errors' => $rowErrors];
                continue;
            }

            try {
                if ($type === 'categories') {
                    $cat = KpiIndicatorCategory::create([
                        'category_name' => $raw['category_name'] ?? null,
                        'weight_percentage' => $raw['weight_percentage'] ?? 0,
                        'evaluator_type' => $raw['evaluator_type'] ?? 'direct_parent',
                        'evaluator_position_id' => $item['evaluator_position_id'] ?? null,
                        'is_active' => isset($raw['is_active']) ? (int)$raw['is_active'] : 1,
                    ]);
                    $created++;
                } else {
                    // indicators
                    $indicator = KpiIndicator::create([
                        'category_id' => $item['category_id'] ?? null,
                        'indicator_name' => $raw['indicator_name'] ?? null,
                        'notes' => $raw['notes'] ?? null,
                        'is_active' => isset($raw['is_active']) ? (int)$raw['is_active'] : 1,
                    ]);
                    $created++;
                    if (!empty($item['position_id']) && isset($raw['weight_percentage']) && $raw['weight_percentage'] !== '') {
                        KpiPositionIndicator::create([
                            'position_id' => $item['position_id'],
                            'indicator_id' => $indicator->id,
                            'weight_percentage' => $raw['weight_percentage'],
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $errors[] = ['row' => $i+1, 'errors' => [$e->getMessage()]];
            }
        }

        return response()->json(['success' => empty($errors), 'created' => $created, 'errors' => $errors]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate($this->categoryRules());
        $validated['is_active'] = $request->boolean('is_active');
        $validated['evaluator_position_id'] = $validated['evaluator_type'] === 'specific_position'
            ? ($validated['evaluator_position_id'] ?? null)
            : null;

        $category = KpiIndicatorCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Indicator category created successfully.',
            'data' => $category,
        ]);
    }

    public function showCategory(KpiIndicatorCategory $category): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }

    public function updateCategory(Request $request, KpiIndicatorCategory $category): JsonResponse
    {
        $validated = $request->validate($this->categoryRules());
        $validated['is_active'] = $request->boolean('is_active');
        $validated['evaluator_position_id'] = $validated['evaluator_type'] === 'specific_position'
            ? ($validated['evaluator_position_id'] ?? null)
            : null;

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Indicator category updated successfully.',
            'data' => $category,
        ]);
    }

    public function destroyCategory(KpiIndicatorCategory $category): JsonResponse
    {
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Indicator category deleted successfully.',
        ]);
    }

    public function storeIndicator(Request $request): JsonResponse
    {

        // if position_mappings comes as JSON string from the form, decode it
        if ($request->filled('position_mappings') && is_string($request->input('position_mappings'))) {
            $decoded = json_decode($request->input('position_mappings'), true);
            if (is_array($decoded)) {
                $request->merge(['position_mappings' => $decoded]);
            }
        }

        $validated = $request->validate($this->indicatorRules());
        $validated['is_active'] = $request->boolean('is_active');

        $indicator = KpiIndicator::create($validated);

        // handle optional position mappings (multiple)
        $mappings = $request->input('position_mappings');
        if ($mappings && is_array($mappings)) {
            $posIds = collect($mappings)->pluck('position_id')->filter()->map(function($v){ return (int)$v; })->unique()->values()->all();
            // exclude top-level positions (no parent)
            $topIds = \App\Models\HRD\Position::whereIn('id', $posIds)->whereNull('parent_id')->pluck('id')->toArray();
            $posIds = array_diff($posIds, $topIds);
            foreach ($mappings as $m) {
                $positionId = isset($m['position_id']) ? (int) $m['position_id'] : null;
                $weight = isset($m['weight_percentage']) ? $m['weight_percentage'] : null;
                if ($positionId && in_array($positionId, $posIds)) {
                    KpiPositionIndicator::updateOrCreate(
                        ['position_id' => $positionId, 'indicator_id' => $indicator->id],
                        ['weight_percentage' => $weight]
                    );
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Indicator created successfully.',
            'data' => $indicator,
        ]);
    }

    public function showIndicator(KpiIndicator $indicator): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $indicator->load(['positionIndicators.position']),
        ]);
    }

    public function updateIndicator(Request $request, KpiIndicator $indicator): JsonResponse
    {

        // if position_mappings comes as JSON string from the form, decode it
        if ($request->filled('position_mappings') && is_string($request->input('position_mappings'))) {
            $decoded = json_decode($request->input('position_mappings'), true);
            if (is_array($decoded)) {
                $request->merge(['position_mappings' => $decoded]);
            }
        }

        $validated = $request->validate($this->indicatorRules());
        $validated['is_active'] = $request->boolean('is_active');

        $indicator->update($validated);

        // update or add multiple position mappings
        $mappings = $request->input('position_mappings');
        $providedIds = [];
        if ($mappings && is_array($mappings)) {
            $providedIds = collect($mappings)->pluck('position_id')->filter()->map(function($v){ return (int)$v; })->unique()->values()->all();
            // exclude top-level positions (no parent)
            $topIds = \App\Models\HRD\Position::whereIn('id', $providedIds)->whereNull('parent_id')->pluck('id')->toArray();
            $providedIds = array_diff($providedIds, $topIds);

            foreach ($mappings as $m) {
                $positionId = isset($m['position_id']) ? (int) $m['position_id'] : null;
                $weight = isset($m['weight_percentage']) ? $m['weight_percentage'] : null;
                if ($positionId && in_array($positionId, $providedIds)) {
                    KpiPositionIndicator::updateOrCreate(
                        ['position_id' => $positionId, 'indicator_id' => $indicator->id],
                        ['weight_percentage' => $weight]
                    );
                }
            }
        }

        // remove any existing mappings for this indicator that are not in providedIds
        if (!empty($providedIds)) {
            KpiPositionIndicator::where('indicator_id', $indicator->id)
                ->whereNotIn('position_id', $providedIds)
                ->delete();
        } else {
            // if no mappings provided, remove all mappings for this indicator
            KpiPositionIndicator::where('indicator_id', $indicator->id)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Indicator updated successfully.',
            'data' => $indicator,
        ]);
    }

    public function destroyIndicator(KpiIndicator $indicator): JsonResponse
    {
        $indicator->delete();

        return response()->json([
            'success' => true,
            'message' => 'Indicator deleted successfully.',
        ]);
    }

    private function categoryRules(): array
    {
        return [
            'category_name' => ['required', 'string', 'max:255'],
            'weight_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'evaluator_type' => ['required', Rule::in(['direct_parent', 'specific_position', 'bottom_up'])],
            'evaluator_position_id' => [
                'nullable',
                'integer',
                'exists:hrd_position,id',
                Rule::requiredIf(request('evaluator_type') === 'specific_position'),
            ],
            'is_active' => ['nullable', 'in:0,1,true,false,on,off'],
        ];
    }

    private function indicatorRules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:kpi_indicator_categories,id'],
            'indicator_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'in:0,1,true,false,on,off'],
            'position_mappings' => ['nullable', 'array'],
            'position_mappings.*.position_id' => ['required_with:position_mappings', 'integer', 'exists:hrd_position,id'],
            'position_mappings.*.weight_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
