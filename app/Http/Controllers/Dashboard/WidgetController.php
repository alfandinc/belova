<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\Dashboard\DashboardWidgets;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Admin Panel > Widget Dashboard: which roles see which home dashboard widget. Widgets themselves are
 * defined in config/dashboard_widgets.php; access is the "dashboard.widget.{key}" permission on a role.
 */
class WidgetController extends Controller
{
    public function index()
    {
        // New widgets in the config get their permission here, so they can be ticked right away
        DashboardWidgets::syncPermissions();

        $widgets = DashboardWidgets::all()->values();
        $roles = Role::query()
            ->with('permissions:id,name')
            ->withCount('users')
            ->orderBy('name')
            ->get(['id', 'name']);

        $granted = [];
        foreach ($roles as $role) {
            foreach ($role->permissions as $permission) {
                $granted[$role->id][$permission->name] = true;
            }
        }

        return view('dashboard.widgets.index', compact('widgets', 'roles', 'granted'));
    }

    public function toggle(Request $request)
    {
        $data = $request->validate([
            'widget' => ['required', 'string'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'granted' => ['required', 'boolean'],
        ]);

        $widget = DashboardWidgets::all()->get($data['widget']);
        abort_unless($widget, 404, 'Widget tidak ditemukan.');

        $role = Role::findOrFail($data['role_id']);
        $permission = Permission::findOrCreate($widget->permission, 'web');

        // Spatie clears its permission cache on give/revoke
        $data['granted'] ? $role->givePermissionTo($permission) : $role->revokePermissionTo($permission);

        return response()->json([
            'message' => ($data['granted'] ? 'Akses diberikan: ' : 'Akses dicabut: ') . $widget->widget_name . ' → ' . $role->name,
        ]);
    }
}
