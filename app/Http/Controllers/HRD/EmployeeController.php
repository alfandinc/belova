<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\Area\Village;
use App\Models\HRD\Employee;
use App\Models\HRD\Position;
use App\Models\HRD\Division;
use App\Models\User;
use App\Models\AreaVillage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $employees = Employee::with(['position', 'division', 'user']);

            return DataTables::of($employees)
                ->addColumn('status_label', function ($employee) {
                    $statusColors = [
                        'tetap' => 'success',
                        'kontrak' => 'warning',
                        'tidak aktif' => 'danger'
                    ];
                    return '<span class="badge badge-' . $statusColors[$employee->status] . '">' . ucfirst($employee->status) . '</span>';
                })
                ->addColumn('action', function ($employee) {
                    $viewBtn = '<a href="' . route('hrd.employee.show', $employee->id) . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>';
                    $editBtn = '<a href="' . route('hrd.employee.edit', $employee->id) . '" class="btn btn-sm btn-primary ml-1"><i class="fas fa-edit"></i></a>';
                    $deleteBtn = '<button class="btn btn-sm btn-danger ml-1 delete-employee" data-id="' . $employee->id . '"><i class="fas fa-trash"></i></button>';

                    return $viewBtn . $editBtn . $deleteBtn;
                })
                ->rawColumns(['status_label', 'action'])
                ->make(true);
        }

        return view('hrd.employee.index');
    }

    public function create()
    {
        $positions = Position::all();
        $divisions = Division::all();
        // $villages = Village::all();

        return view('hrd.employee.create', compact(
            'positions',
            'divisions',

        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'tempat_lahir' => 'required|string|max:100',
            'tanggal_lahir' => 'required|date',
            'nik' => 'required|string|unique:hrd_employee',
            'alamat' => 'required|string',
            'village_id' => 'nullable|exists:area_villages,id',
            'position' => 'required|exists:hrd_position,id',
            'division' => 'required|exists:hrd_division,id',
            'pendidikan' => 'required|string',
            'no_hp' => 'required|string',
            'tanggal_masuk' => 'required|date',
            'status' => 'required|in:tetap,kontrak,tidak aktif',
            'kontrak_berakhir' => 'nullable|date',
            'masa_pensiun' => 'nullable|date',
            'doc_cv' => 'nullable|file|max:2048',
            'doc_ktp' => 'nullable|file|max:2048',
            'doc_kontrak' => 'nullable|file|max:2048',
            'doc_pendukung' => 'nullable|file|max:2048',
            'create_account' => 'nullable|boolean',
            'email' => '|unique:users,email',
            'role' => 'required',
        ]);

        // Handle file uploads
        foreach (['doc_cv', 'doc_ktp', 'doc_kontrak', 'doc_pendukung'] as $doc) {
            if ($request->hasFile($doc)) {
                $data[$doc] = $request->file($doc)->store('documents/employees');
            }
        }

        // Create user account if requested
        if ($request->create_account) {
            $password = Str::random(8);
            $user = User::create([
                'name' => $data['nama'],
                'email' => $data['email'],
                'password' => Hash::make($password),
            ]);

            // Assign role
            $user->assignRole($request->role);
            $data['user_id'] = $user->id;

            // Send password to user (in real app, use notification or email)
            session()->flash('generated_password', $password);
        }

        $employee = Employee::create($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Karyawan berhasil ditambahkan',
                'data' => $employee,
                'redirect' => route('hrd.employee.index')
            ]);
        }

        return redirect()->route('hrd.employee.index')
            ->with('success', 'Karyawan berhasil ditambahkan');
    }

    public function show($id)
    {
        $employee = Employee::with(['position', 'division', 'village', 'user'])->findOrFail($id);
        return view('hrd.employee.show', compact('employee'));
    }

    public function edit($id)
    {
        $employee = Employee::findOrFail($id);
        $positions = Position::all();
        $divisions = Division::all();
        $villages = Village::all();
        $roles = Role::whereIn('name', ['employee', 'manager'])->get();

        return view('hrd.employee.edit', compact(
            'employee',
            'positions',
            'divisions',
            'villages',
            'roles'
        ));
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'tempat_lahir' => 'required|string|max:100',
            'tanggal_lahir' => 'required|date',
            'nik' => 'required|string|unique:hrd_employee,nik,' . $employee->id,
            'alamat' => 'required|string',
            'village_id' => 'nullable|exists:area_villages,id',
            'position' => 'required|exists:hrd_position,id',
            'division' => 'required|exists:hrd_division,id',
            'pendidikan' => 'required|string',
            'no_hp' => 'required|string',
            'tanggal_masuk' => 'required|date',
            'status' => 'required|in:tetap,kontrak,tidak aktif',
            'kontrak_berakhir' => 'nullable|date',
            'masa_pensiun' => 'nullable|date',
            'doc_cv' => 'nullable|file|max:2048',
            'doc_ktp' => 'nullable|file|max:2048',
            'doc_kontrak' => 'nullable|file|max:2048',
            'doc_pendukung' => 'nullable|file|max:2048',
        ]);

        // Handle file uploads
        foreach (['doc_cv', 'doc_ktp', 'doc_kontrak', 'doc_pendukung'] as $doc) {
            if ($request->hasFile($doc)) {
                // Delete old file if exists
                if ($employee->{$doc}) {
                    Storage::delete($employee->{$doc});
                }
                $data[$doc] = $request->file($doc)->store('documents/employees');
            }
        }

        // Handle user account
        if ($request->has('create_account') && !$employee->user_id) {
            $request->validate([
                'email' => 'required|email|unique:users,email',
                'role' => 'required|in:employee,manager',
            ]);

            $password = Str::random(8);
            $user = User::create([
                'name' => $data['nama'],
                'email' => $request->email,
                'password' => Hash::make($password),
            ]);

            $user->assignRole($request->role);
            $data['user_id'] = $user->id;

            session()->flash('generated_password', $password);
        } elseif ($employee->user_id && $request->has('role')) {
            $user = $employee->user;
            $user->syncRoles([$request->role]);
        }

        $employee->update($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Data karyawan berhasil diperbarui',
                'redirect' => route('hrd.employee.index')
            ]);
        }

        return redirect()->route('hrd.employee.index')
            ->with('success', 'Data karyawan berhasil diperbarui');
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);

        // Delete associated files
        foreach (['doc_cv', 'doc_ktp', 'doc_kontrak', 'doc_pendukung'] as $doc) {
            if ($employee->{$doc}) {
                Storage::delete($employee->{$doc});
            }
        }

        // Delete the employee
        $employee->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Karyawan berhasil dihapus'
            ]);
        }

        return redirect()->route('hrd.employee.index')
            ->with('success', 'Karyawan berhasil dihapus');
    }
}
