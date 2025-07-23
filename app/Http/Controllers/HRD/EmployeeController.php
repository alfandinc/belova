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
        $employees = Employee::with(['division', 'user'])
            ->select('hrd_employee.*'); // Explicitly select all employee columns

        $dataTable = DataTables::of($employees)
            ->addColumn('position_name', function ($employee) {
                // Get position name from the Position model
                $position = Position::find($employee->position);
                return $position ? $position->name : '-';
            })
            // No longer need to add status_label column, handling it client-side
            ->addColumn('action', function ($employee) {
                $viewBtn = '<a href="' . route('hrd.employee.show', $employee->id) . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>';
                $editBtn = '<a href="' . route('hrd.employee.edit', $employee->id) . '" class="btn btn-sm btn-primary ml-1"><i class="fas fa-edit"></i></a>';
                $deleteBtn = '<button class="btn btn-sm btn-danger ml-1 delete-employee" data-id="' . $employee->id . '"><i class="fas fa-trash"></i></button>';

                return $viewBtn . $editBtn . $deleteBtn;
            })
            ->rawColumns(['position_name', 'action']);
            
        // Add custom sorting for kontrak_berakhir column    
        $dataTable->order(function ($query) use ($request) {
            if ($request->has('order') && $request->input('order.0.column') == 6) { // Sisa Kontrak column
                $direction = $request->input('order.0.dir');
                // For ascending order: show employees with the least time left first
                // For descending order: show employees with the most time left first
                if ($direction == 'asc') {
                    // Show contract employees first, ordered by nearest end date
                    // Then show non-contract employees
                    $query->orderByRaw("
                        CASE 
                            WHEN status = 'kontrak' THEN 0 
                            ELSE 1 
                        END,
                        CASE 
                            WHEN status = 'kontrak' THEN kontrak_berakhir
                            ELSE NULL
                        END " . $direction);
                } else {
                    // For descending order: employees with longest contract time first,
                    // then permanent employees, then inactive employees
                    $query->orderByRaw("
                        CASE 
                            WHEN status = 'kontrak' THEN 0
                            WHEN status = 'tetap' THEN 1
                            ELSE 2
                        END,
                        CASE
                            WHEN status = 'kontrak' THEN kontrak_berakhir
                            ELSE NULL
                        END " . $direction);
                }
            }
        });
        
        return $dataTable->make(true);
    }

    return view('hrd.employee.index');
}

    public function create()
    {
        $positions = Position::all();
        $divisions = Division::all();
        
        return view('hrd.employee.form', compact(
            'positions',
            'divisions'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'nullable|string|max:255',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'nik' => 'nullable|string|unique:hrd_employee',
            'no_induk' => 'nullable|string|unique:hrd_employee,no_induk',
            'alamat' => 'nullable|string',
            'village_id' => 'nullable|exists:area_villages,id',
            'position_id' => 'nullable|exists:hrd_position,id',
            'division_id' => 'nullable|exists:hrd_division,id',
            'pendidikan' => 'nullable|string',
            'no_hp' => 'nullable|string',
            'tanggal_masuk' => 'nullable|date',
            'status' => 'nullable|in:tetap,kontrak,tidak aktif',
            'kontrak_berakhir' => 'nullable|date',
            'masa_pensiun' => 'nullable|date',
            'durasi_kontrak' => 'nullable|integer|min:1',
            'doc_cv' => 'nullable|file|max:2048',
            'doc_ktp' => 'nullable|file|max:2048',
            'doc_kontrak' => 'nullable|file|max:2048',
            'doc_pendukung' => 'nullable|file|max:2048',
            'create_account' => 'nullable|boolean',
            'email' => 'nullable|unique:users,email',
            'role' => 'nullable',
        ]);

        // Handle file uploads
        foreach (['doc_cv', 'doc_ktp', 'doc_kontrak', 'doc_pendukung'] as $doc) {
            if ($request->hasFile($doc)) {
                $data[$doc] = $request->file($doc)->store('documents/employees', 'public');
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

        return view('hrd.employee.form', compact(
            'employee',
            'positions',
            'divisions'
        ));
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $data = $request->validate([
            'nama' => 'nullable|string|max:255',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'nik' => 'nullable|string|unique:hrd_employee,nik,' . $employee->id,
            'no_induk' => 'nullable|string|unique:hrd_employee,no_induk,' . $employee->id,
            'alamat' => 'nullable|string',
            'position_id' => 'nullable|exists:hrd_position,id',
            'division_id' => 'nullable|exists:hrd_division,id',
            'pendidikan' => 'nullable|string',
            'no_hp' => 'nullable|string',
            'tanggal_masuk' => 'nullable|date',
            'status' => 'nullable|in:tetap,kontrak,tidak aktif',
            'kontrak_berakhir' => 'nullable|date',
            'masa_pensiun' => 'nullable|date',
            'doc_cv' => 'nullable|file|max:2048',
            'doc_ktp' => 'nullable|file|max:2048',
            'doc_kontrak' => 'nullable|file|max:2048',
            'doc_pendukung' => 'nullable|file|max:2048',
            'durasi_kontrak' => 'nullable|integer|min:1',
        ]);

        // Handle file uploads
        foreach (['doc_cv', 'doc_ktp', 'doc_kontrak', 'doc_pendukung'] as $doc) {
            if ($request->hasFile($doc)) {
                // Delete old file if exists
                if ($employee->{$doc}) {
                    Storage::disk('public')->delete($employee->{$doc});
                }
                $data[$doc] = $request->file($doc)->store('documents/employees', 'public');
            }
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

    public function getDetails($id)
    {
        try {
            $employee = Employee::with(['position', 'division', 'village', 'user'])->findOrFail($id);
            
            // Convert document paths to public URLs if they exist
            foreach (['doc_cv', 'doc_ktp', 'doc_kontrak', 'doc_pendukung'] as $doc) {
                if ($employee->{$doc}) {
                    // If the file doesn't begin with 'public/', add it to ensure correct path
                    if (strpos($employee->{$doc}, 'public/') !== 0) {
                        // Just keep the path as is since asset('storage') will be used in frontend
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $employee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load employee details',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
