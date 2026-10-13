<?php

namespace App\Support\Dashboard;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Permission;

/**
 * Registry of the home dashboard widgets (config/dashboard_widgets.php). Visibility is the Spatie
 * permission "dashboard.widget.{key}", given to roles on the Widget Dashboard admin page.
 */
class DashboardWidgets
{
    public const PERMISSION_PREFIX = 'dashboard.widget.';

    /** @return Collection<string, object> keyed by widget key, ordered for display */
    public static function all(): Collection
    {
        return collect(config('dashboard_widgets', []))
            ->map(function (array $config, string $key) {
                $view = $config['view'] ?? null;

                return (object) [
                    'id' => $key,
                    'key' => $key,
                    'widget_name' => $config['name'] ?? $key,
                    'description' => $config['description'] ?? null,
                    'icon' => $config['icon'] ?? 'grid',
                    'class' => $config['class'] ?? null,
                    'component_path' => $view,
                    'resolved_view' => $view,
                    'view_exists' => $view ? View::exists($view) : false,
                    'column_span' => max(1, min(12, (int) ($config['span'] ?? 6))),
                    'order' => (int) ($config['order'] ?? 0),
                    'uses_period' => (bool) ($config['uses_period'] ?? false),
                    'permission' => self::permissionName($key),
                    'data' => [],
                    'error' => null,
                ];
            })
            ->sortBy('order');
    }

    public static function permissionName(string $key): string
    {
        return self::PERMISSION_PREFIX . $key;
    }

    /** Widgets the user may see through any of their roles (or a direct permission). */
    public static function forUser(?User $user): Collection
    {
        if (!$user) {
            return collect();
        }

        // checkPermissionTo returns false (instead of throwing) for a permission not created yet
        return self::all()
            ->filter(fn ($widget) => $user->checkPermissionTo($widget->permission))
            ->values();
    }

    /** Fills ->data from the widget's class; a failing widget gets ->error instead of breaking the page. */
    public static function load(Collection $widgets, User $user, array $filter): Collection
    {
        return $widgets->each(function ($widget) use ($user, $filter) {
            if (!$widget->class) {
                return;
            }

            try {
                $widget->data = app($widget->class)->data($user, $filter);
            } catch (\Throwable $e) {
                report($e);
                $widget->error = 'Data widget gagal dimuat.';
            }
        });
    }

    /** Creates the permission of every configured widget, so new widgets show up on the admin page. */
    public static function syncPermissions(): void
    {
        foreach (self::all() as $widget) {
            Permission::findOrCreate($widget->permission, 'web');
        }
    }
}
