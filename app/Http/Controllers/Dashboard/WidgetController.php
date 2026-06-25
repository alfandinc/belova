<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sys\DashboardWidget;
use App\Models\Sys\PositionWidget;
use App\Models\HRD\Position;
use Yajra\DataTables\Facades\DataTables;

class WidgetController extends Controller
{
    private const DEFAULT_WIDGET_VIEW_PREFIX = 'dashboard.custom_widgets.';

    public function index()
    {
        return view('dashboard.widgets.index', [
            'widgets' => DashboardWidget::query()->orderBy('widget_name')->get(),
            'positions' => Position::query()->orderBy('name')->get(),
        ]);
    }

    public function data(Request $request)
    {
        $query = DashboardWidget::query();
        return DataTables::of($query)
            ->addColumn('actions', function ($row) {
                return view('dashboard.widgets._actions', compact('row'))->render();
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'widget_name' => 'required|string|max:255',
            'component_path' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable',
        ]);

        $data['is_active'] = $request->boolean('is_active');
    $data['component_path'] = $this->normalizeStoredComponentPath($data['component_path'] ?? null);

        $w = DashboardWidget::create($data);
        return response()->json(['success' => true, 'data' => $w]);
    }

    public function update(Request $request, $id)
    {
        $w = DashboardWidget::findOrFail($id);
        $data = $request->validate([
            'widget_name' => 'required|string|max:255',
            'component_path' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['component_path'] = $this->normalizeStoredComponentPath($data['component_path'] ?? null);
        $w->update($data);
        return response()->json(['success' => true, 'data' => $w]);
    }

    public function destroy($id)
    {
        $w = DashboardWidget::findOrFail($id);
        $w->delete();
        return response()->json(['success' => true]);
    }

    // Position mappings
    public function positions(Request $request, $id)
    {
        $widget = DashboardWidget::findOrFail($id);
        $mappings = $widget->positionMappings()->with(['widget', 'position'])->get();
        return response()->json(['data' => $mappings]);
    }

    public function positionsData(Request $request, $id)
    {
        $query = PositionWidget::with('position')->where('widget_id', $id);
        return DataTables::of($query)
            ->addColumn('position_name', function ($row) {
                return $row->position->name ?? $row->position_id;
            })
            ->addColumn('actions', function ($row) {
                return '<div class="btn-group btn-group-sm" role="group">'
                    . '<button class="btn btn-primary js-edit-mapping" data-id="'.$row->id.'">Edit</button>'
                    . '<button class="btn btn-danger js-remove-mapping" data-id="'.$row->id.'">Remove</button>'
                    . '</div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function mappingsData(Request $request)
    {
        $query = PositionWidget::with(['widget', 'position']);

        return DataTables::of($query)
            ->addColumn('widget_name', function ($row) {
                return $row->widget->widget_name ?? $row->widget_id;
            })
            ->addColumn('position_name', function ($row) {
                return $row->position->name ?? $row->position_id;
            })
            ->addColumn('actions', function ($row) {
                return '<div class="btn-group btn-group-sm" role="group">'
                    . '<button class="btn btn-primary js-edit-mapping" data-id="'.$row->id.'">Edit</button>'
                    . '<button class="btn btn-danger js-remove-mapping" data-id="'.$row->id.'">Remove</button>'
                    . '</div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function addPosition(Request $request, $id)
    {
        $data = $request->validate([
            'position_id' => 'required|integer|exists:hrd_position,id',
            'order_index' => 'nullable|integer|min:0',
            'column_span' => 'nullable|integer|min:1|max:12',
        ]);

        $map = PositionWidget::updateOrCreate([
            'widget_id' => $id,
            'position_id' => $data['position_id'],
        ], [
            'order_index' => $data['order_index'] ?? 0,
            'column_span' => $data['column_span'] ?? 12,
        ]);

        return response()->json(['success' => true, 'data' => $map]);
    }

    public function updatePosition(Request $request, $id, $mapId)
    {
        $map = PositionWidget::where('widget_id', $id)->where('id', $mapId)->firstOrFail();

        $data = $request->validate([
            'position_id' => 'required|integer|exists:hrd_position,id',
            'order_index' => 'nullable|integer|min:0',
            'column_span' => 'nullable|integer|min:1|max:12',
        ]);

        $map->update([
            'position_id' => $data['position_id'],
            'order_index' => $data['order_index'] ?? 0,
            'column_span' => $data['column_span'] ?? 12,
        ]);

        return response()->json(['success' => true, 'data' => $map->fresh('position')]);
    }

    public function storeMapping(Request $request)
    {
        $data = $request->validate([
            'widget_id' => 'required|integer|exists:sys_dashboard_widgets,id',
            'position_id' => 'required|integer|exists:hrd_position,id',
            'order_index' => 'nullable|integer|min:0',
            'column_span' => 'required|integer|in:12,6,4',
        ]);

        $mapping = PositionWidget::updateOrCreate([
            'widget_id' => $data['widget_id'],
            'position_id' => $data['position_id'],
        ], [
            'order_index' => $data['order_index'] ?? 0,
            'column_span' => $data['column_span'],
        ]);

        return response()->json(['success' => true, 'data' => $mapping->fresh(['widget', 'position'])]);
    }

    public function updateMapping(Request $request, $mapId)
    {
        $mapping = PositionWidget::findOrFail($mapId);

        $data = $request->validate([
            'widget_id' => 'required|integer|exists:sys_dashboard_widgets,id',
            'position_id' => 'required|integer|exists:hrd_position,id',
            'order_index' => 'nullable|integer|min:0',
            'column_span' => 'required|integer|in:12,6,4',
        ]);

        $duplicate = PositionWidget::query()
            ->where('id', '!=', $mapping->id)
            ->where('widget_id', $data['widget_id'])
            ->where('position_id', $data['position_id'])
            ->first();

        if ($duplicate) {
            return response()->json([
                'message' => 'Widget ini sudah dipetakan ke posisi tersebut.',
                'errors' => [
                    'widget_id' => ['Widget ini sudah dipetakan ke posisi tersebut.'],
                ],
            ], 422);
        }

        $mapping->update([
            'widget_id' => $data['widget_id'],
            'position_id' => $data['position_id'],
            'order_index' => $data['order_index'] ?? 0,
            'column_span' => $data['column_span'],
        ]);

        return response()->json(['success' => true, 'data' => $mapping->fresh(['widget', 'position'])]);
    }

    public function destroyMapping($mapId)
    {
        $mapping = PositionWidget::findOrFail($mapId);
        $mapping->delete();

        return response()->json(['success' => true]);
    }

    public function removePosition($id, $mapId)
    {
        $map = PositionWidget::where('widget_id', $id)->where('id', $mapId)->firstOrFail();
        $map->delete();
        return response()->json(['success' => true]);
    }

    private function normalizeStoredComponentPath(?string $componentPath): ?string
    {
        if (! $componentPath) {
            return null;
        }

        $path = trim($componentPath);
        $path = str_replace('\\', '.', $path);
        $path = str_replace('/', '.', $path);

        if (str_starts_with($path, 'resources.views.')) {
            $path = substr($path, strlen('resources.views.'));
        }

        if (str_ends_with($path, '.blade.php')) {
            $path = substr($path, 0, -10);
        } elseif (str_ends_with($path, '.php')) {
            $path = substr($path, 0, -4);
        }

        $path = trim($path, '.');

        if ($path !== '' && ! str_contains($path, '.')) {
            return self::DEFAULT_WIDGET_VIEW_PREFIX . $path;
        }

        return $path !== '' ? $path : null;
    }
}
