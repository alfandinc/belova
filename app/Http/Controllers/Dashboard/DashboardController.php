<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Sys\PositionWidget;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $employee = $user?->employee;
        $position = $employee?->primaryPosition();
        $now = Carbon::now();

        $selectedPeriodStart = $this->parseStartDate($request->input('start_date'), $now);
        $selectedPeriodEnd = $this->parseEndDate($request->input('end_date'), $selectedPeriodStart, $now);

        if ($selectedPeriodEnd->lt($selectedPeriodStart)) {
            [$selectedPeriodStart, $selectedPeriodEnd] = [$selectedPeriodEnd->copy()->startOfDay(), $selectedPeriodStart->copy()->endOfDay()];
        }

        $dashboardFilter = [
            'year' => (int) $selectedPeriodEnd->year,
            'month' => (int) $selectedPeriodStart->month,
            'period_start' => $selectedPeriodStart,
            'period_end' => $selectedPeriodEnd,
            'start_date' => $selectedPeriodStart->format('Y-m-d'),
            'end_date' => $selectedPeriodEnd->format('Y-m-d'),
            'label' => $selectedPeriodStart->format('Y-m-d') . ' - ' . $selectedPeriodEnd->format('Y-m-d'),
        ];

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

        $viewData = [
            'dashboardWidgets' => $widgets,
            'employeePosition' => $position,
            'dashboardFilter' => $dashboardFilter,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('dashboard.partials.widgets_grid', $viewData)->render(),
                'hasEmployeePosition' => (bool) $position,
                'hasWidgets' => $widgets->isNotEmpty(),
            ]);
        }

        return view('dashboard.index', $viewData);
    }

    private function parseStartDate(?string $value, Carbon $now): Carbon
    {
        try {
            return $value ? Carbon::parse($value)->startOfDay() : $now->copy()->startOfMonth();
        } catch (\Throwable $e) {
            return $now->copy()->startOfMonth();
        }
    }

    private function parseEndDate(?string $value, Carbon $startDate, Carbon $now): Carbon
    {
        try {
            if (! $value) {
                return $now->copy();
            }

            $parsed = Carbon::parse($value)->endOfDay();

            if ($parsed->isSameMonth($now) && $parsed->year === $now->year && $parsed->greaterThan($now)) {
                return $now->copy();
            }

            return $parsed;
        } catch (\Throwable $e) {
            return $startDate->copy()->endOfMonth();
        }
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
