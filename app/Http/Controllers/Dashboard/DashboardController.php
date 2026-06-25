<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Sys\PositionWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $employee = $user?->employee;
        $position = $employee?->primaryPosition();

        $widgets = collect();

        if ($position) {
            $widgets = PositionWidget::query()
                ->with('widget')
                ->where('position_id', $position->id)
                ->orderBy('order_index')
                ->get()
                ->filter(function ($mapping) {
                    return $mapping->widget && $mapping->widget->is_active;
                })
                ->map(function ($mapping) {
                    $widget = $mapping->widget;
                    $widget->resolved_view = $this->normalizeWidgetViewPath($widget->component_path);
                    $widget->view_exists = $widget->resolved_view
                        ? View::exists($widget->resolved_view)
                        : false;
                    $widget->column_span = max(1, min(12, (int) $mapping->column_span));

                    return $widget;
                })
                ->values();
        }

        return view('dashboard.index', [
            'dashboardWidgets' => $widgets,
            'employeePosition' => $position,
        ]);
    }

    private function normalizeWidgetViewPath(?string $componentPath): ?string
    {
        if (! $componentPath) {
            return null;
        }

        $path = trim($componentPath);
        $path = str_replace('\\', '/', $path);

        if (str_starts_with($path, 'resources/views/')) {
            $path = substr($path, strlen('resources/views/'));
        }

        if (str_ends_with($path, '.blade.php')) {
            $path = substr($path, 0, -10);
        } elseif (str_ends_with($path, '.php')) {
            $path = substr($path, 0, -4);
        }

        return str_replace('/', '.', trim($path, './'));
    }
}
