<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

// Roles are managed from the side panel on the Users page
class RoleController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('admin.users.index');
        }

        $roles = Role::withCount([
            'users',
            'users as active_users_count' => fn ($q) => $q->where('is_active', true),
        ])->orderBy('name')->get(['id', 'name']);

        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $request->validate([
            'name' => 'required|string|max:50|unique:roles,name',
        ], [
            'name.unique' => 'Role dengan nama ini sudah ada.',
        ]);

        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);

        return response()->json(['success' => true, 'message' => 'Role "' . $role->name . '" ditambahkan.']);
    }

    public function destroy(Role $role)
    {
        $count = $role->users()->count();
        if ($count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Role "' . $role->name . '" masih dipakai oleh ' . $count . ' user. Lepaskan role ini dari user tersebut terlebih dahulu.',
            ], 422);
        }

        $name = $role->name;
        $role->delete();

        return response()->json(['success' => true, 'message' => 'Role "' . $name . '" dihapus.']);
    }
}
