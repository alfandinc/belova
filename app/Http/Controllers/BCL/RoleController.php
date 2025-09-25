<?php

namespace App\Http\Controllers\BCL;
use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role as ModelsRole;
use Spatie\Permission\Models\Permission as ModelsPermission;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::all();
        $permission = Permission::all();
        return view('permission.index')->with('role', $roles)->with('permission', $permission);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $role = ModelsRole::create(['name' => $request->name]);
            $last_id = $role->id;
            foreach ($request->permissions as $value) {
                $perm = ModelsPermission::findorfail($value);
                $role->givePermissionTo($perm);
            }
            return redirect()->back()->with('success', 'Role ' . $role->name . ' berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role, Request $request)
    {
        try {
            if ($request->id == 1) {
                return redirect()->back()->with('error', 'Role ' . $role->name . ' tidak dapat dihapus');
            }
            $role = ModelsRole::findorfail($request->id)->delete();
            $result = DB::raw('delete from role_has_permissions where role_id = ' . $request->id . ';');
            return redirect()->back()->with('success', 'Role berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
