<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\Position;
use App\Models\HRD\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;

class PositionMasterController extends Controller
{
    protected function buildFormOptionsPayload(): array
    {
        $divisions = Division::orderBy('name')->get();
        $positions = Position::with('divisions')->orderBy('name')->get();

        return [
            'divisionOptions' => $divisions->map(function (Division $division) {
                return [
                    'id' => $division->id,
                    'name' => $division->name,
                ];
            })->values()->all(),
            'parentPositionOptions' => $positions->map(function (Position $position) {
                return [
                    'id' => $position->id,
                    'name' => $position->name,
                    'level' => $position->level,
                    'division_ids' => $position->division_ids,
                    'division_names' => $position->division_names,
                ];
            })->values()->all(),
        ];
    }

    protected function applyLegacyHierarchyAttributes(Position $position, array $divisionIds, array $parentPositionIds): void
    {
        if (Schema::hasColumn('hrd_position', 'division_id')) {
            $position->setAttribute('division_id', $divisionIds[0] ?? null);
        }

        if (Schema::hasColumn('hrd_position', 'parent_id')) {
            $position->setAttribute('parent_id', $parentPositionIds[0] ?? null);
        }
    }

    protected function normalizeDivisionHierarchyPayload(Request $request, ?int $positionId = null): array
    {
        $organizationUnits = collect($request->input('organization_units', []))
            ->map(function ($unit) use ($positionId) {
                $divisionId = $unit['division_id'] ?? null;
                $parentPositionId = $unit['parent_position_id'] ?? null;

                return [
                    'division_id' => filled($divisionId) ? (int) $divisionId : null,
                    'parent_position_id' => filled($parentPositionId) ? (int) $parentPositionId : null,
                ];
            })
            ->filter(fn (array $unit) => filled($unit['division_id']))
            ->reject(fn (array $unit) => $positionId !== null && ($unit['parent_position_id'] ?? null) === $positionId)
            ->unique(fn (array $unit) => $unit['division_id'] . ':' . ($unit['parent_position_id'] ?? 'null'))
            ->values();

        if ($organizationUnits->isEmpty()) {
            $divisionIds = collect($request->input('division_ids', []));
            if ($divisionIds->isEmpty() && $request->filled('division_id')) {
                $divisionIds = collect([$request->input('division_id')]);
            }

            $parentPositionIds = collect($request->input('parent_position_ids', []));
            if ($parentPositionIds->isEmpty() && $request->filled('parent_id')) {
                $parentPositionIds = collect([$request->input('parent_id')]);
            }

            $divisionIds = $divisionIds
                ->filter(fn ($id) => filled($id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $parentPositionIds = $parentPositionIds
                ->filter(fn ($id) => filled($id))
                ->map(fn ($id) => (int) $id)
                ->reject(fn ($id) => $positionId !== null && $id === $positionId)
                ->unique()
                ->values();

            foreach ($divisionIds as $divisionId) {
                if ($parentPositionIds->isEmpty()) {
                    $organizationUnits->push([
                        'division_id' => $divisionId,
                        'parent_position_id' => null,
                    ]);
                    continue;
                }

                foreach ($parentPositionIds as $parentPositionId) {
                    $organizationUnits->push([
                        'division_id' => $divisionId,
                        'parent_position_id' => $parentPositionId,
                    ]);
                }
            }
        }

        $divisionIds = $organizationUnits->pluck('division_id')->filter()->unique()->values()->all();
        $parentPositionIds = $organizationUnits->pluck('parent_position_id')->filter()->unique()->values()->all();

        return [$divisionIds, $parentPositionIds, $organizationUnits->all()];
    }

    public function index()
    {
        $divisions = Division::all();
        $levelOptions = Position::LEVEL_OPTIONS;
        $formOptions = $this->buildFormOptionsPayload();
        $divisionOptions = $formOptions['divisionOptions'];
        $parentPositionOptions = $formOptions['parentPositionOptions'];

        return view('hrd.master.position.index', compact('divisions', 'levelOptions', 'divisionOptions', 'parentPositionOptions'));
    }

    public function formOptions()
    {
        return response()->json($this->buildFormOptionsPayload());
    }

    public function getData(Request $request)
    {
        $positions = Position::with(['employees', 'divisions', 'parentPositions']);

        // Optional filter by division_id (sent from DataTables ajax)
        $divisionId = $request->input('division_id');
        if ($divisionId) {
            $positions->whereHas('divisions', function ($divisionQuery) use ($divisionId) {
                $divisionQuery->where('hrd_division.id', $divisionId);
            });
        }

        return DataTables::of($positions)
            ->addColumn('level_badge', function ($position) {
                return '<span class="badge badge-info">' . e($position->level ?? 'Staff') . '</span>';
            })
            ->addColumn('status_badge', function ($position) {
                return $position->is_active
                    ? '<span class="badge badge-success">Aktif</span>'
                    : '<span class="badge badge-secondary">Nonaktif</span>';
            })
            ->addColumn('division_name', function ($position) {
                return $position->division_names;
            })
            ->addColumn('parent_name', function ($position) {
                return $position->parent_names;
            })
            ->addColumn('employee_count', function ($position) {
                return $position->employees->count();
            })
            ->addColumn('action', function ($position) {
                return '
                    <button type="button" class="btn btn-sm btn-info edit-position" data-id="'.$position->id.'">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button type="button" class="btn btn-sm btn-danger delete-position" data-id="'.$position->id.'">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                ';
            })
                ->rawColumns(['level_badge', 'status_badge', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'level' => ['required', Rule::in(Position::LEVEL_OPTIONS)],
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'organization_units' => 'nullable|array|min:1',
            'organization_units.*.division_id' => 'nullable|exists:hrd_division,id',
            'organization_units.*.parent_position_id' => 'nullable|exists:hrd_position,id',
            'division_id' => 'nullable|exists:hrd_division,id',
            'division_ids' => 'nullable|array|min:1',
            'division_ids.*' => 'nullable|exists:hrd_division,id',
            'parent_id' => 'nullable|exists:hrd_position,id',
            'parent_position_ids' => 'nullable|array',
            'parent_position_ids.*' => 'nullable|exists:hrd_position,id'
        ]);

        [$divisionIds, $parentPositionIds, $organizationUnits] = $this->normalizeDivisionHierarchyPayload($request);

        if (empty($divisionIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Minimal satu divisi harus dipilih'
            ], 422);
        }

        $position = new Position([
            'name' => $request->name,
            'level' => $request->level,
            'description' => $request->description,
            'is_active' => (bool) $request->is_active,
        ]);

        $this->applyLegacyHierarchyAttributes($position, $divisionIds, $parentPositionIds);
        $position->save();

        $position->syncDivisionHierarchy($divisionIds, $parentPositionIds, $organizationUnits);

        return response()->json([
            'success' => true,
            'message' => 'Posisi/Jabatan berhasil ditambahkan',
            'data' => $position
        ]);
    }

    public function show($id)
    {
        $position = Position::with(['divisions', 'parentPositions', 'divisionMappings'])->findOrFail($id);

        $position->setAttribute('division_ids', $position->divisions->pluck('id')->unique()->values()->all());
        $position->setAttribute('parent_position_ids', $position->parentPositions->pluck('id')->unique()->values()->all());
        $position->setAttribute('organization_units', $position->divisionMappings
            ->map(function ($mapping) {
                return [
                    'division_id' => $mapping->division_id,
                    'parent_position_id' => $mapping->parent_position_id,
                ];
            })
            ->values()
            ->all());

        return response()->json($position);
    }

    public function update(Request $request, $id)
    {
        $position = Position::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'level' => ['required', Rule::in(Position::LEVEL_OPTIONS)],
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'organization_units' => 'nullable|array|min:1',
            'organization_units.*.division_id' => 'nullable|exists:hrd_division,id',
            'organization_units.*.parent_position_id' => ['nullable','exists:hrd_position,id', Rule::notIn([$id])],
            'division_id' => 'nullable|exists:hrd_division,id',
            'division_ids' => 'nullable|array|min:1',
            'division_ids.*' => 'nullable|exists:hrd_division,id',
            'parent_id' => ['nullable','exists:hrd_position,id', Rule::notIn([$id])],
            'parent_position_ids' => 'nullable|array',
            'parent_position_ids.*' => ['nullable','exists:hrd_position,id', Rule::notIn([$id])]
        ]);

        [$divisionIds, $parentPositionIds, $organizationUnits] = $this->normalizeDivisionHierarchyPayload($request, (int) $id);

        if (empty($divisionIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Minimal satu divisi harus dipilih'
            ], 422);
        }

        $position->fill([
            'name' => $request->name,
            'level' => $request->level,
            'description' => $request->description,
            'is_active' => (bool) $request->is_active,
        ]);

        $this->applyLegacyHierarchyAttributes($position, $divisionIds, $parentPositionIds);
        $position->save();

        $position->syncDivisionHierarchy($divisionIds, $parentPositionIds, $organizationUnits);

        return response()->json([
            'success' => true,
            'message' => 'Posisi/Jabatan berhasil diperbarui',
            'data' => $position
        ]);
    }

    public function destroy($id)
    {
        $position = Position::findOrFail($id);
        
        // Check if there are any employees with this position
        if ($position->employees()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus jabatan karena masih digunakan oleh karyawan'
            ], 422);
        }

        $position->delete();

        return response()->json([
            'success' => true,
            'message' => 'Posisi/Jabatan berhasil dihapus'
        ]);
    }
}
