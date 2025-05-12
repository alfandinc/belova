<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\Employee;
use App\Models\HRD\Position;
// use App\Models\AreaVillage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with(['position'])->latest()->get();
        return view('hrd.employee.index', compact('employees'));
    }

    public function create()
    {
        $positions = Position::all();
        // $villages = AreaVillage::all();
        return view('hrd.employee.create', compact(
            'positions',
            // 'villages',
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
            // 'village_id' => 'required|exists:area_villages,id',
            'position' => 'required|exists:hrd_position,id',
            'pendidikan' => 'required|string',
            'no_hp' => 'required|string',
            'tanggal_masuk' => 'required|date',
            'status' => 'required|in:tetap,kontrak,tidak aktif',
            'kontrak_berakhir' => 'nullable|date',
            'masa_pensiun' => 'nullable|date',
            'doc_cv' => 'nullable|file',
            'doc_ktp' => 'nullable|file',
            'doc_kontrak' => 'nullable|file',
            'doc_pendukung' => 'nullable|file',
        ]);

        foreach (['doc_cv', 'doc_ktp', 'doc_kontrak', 'doc_pendukung'] as $doc) {
            if ($request->hasFile($doc)) {
                $data[$doc] = $request->file($doc)->store('documents');
            }
        }

        Employee::create($data);

        return redirect()->route('hrd.employee.index')->with('success', 'Karyawan ditambahkan.');
    }

    public function edit(Employee $hrd_employee)
    {
        $positions = Position::all();
        // $villages = AreaVillage::all();
        return view('hrd.employee.edit', compact(
            'hrd_employee',
            'positions',
            // 'villages',
        ));
    }

    public function update(Request $request, Employee $hrd_employee)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'tempat_lahir' => 'required|string|max:100',
            'tanggal_lahir' => 'required|date',
            'nik' => 'required|string|unique:hrd_employee,nik,' . $hrd_employee->id,
            'alamat' => 'required|string',
            // 'village_id' => 'required|exists:area_villages,id',
            'position' => 'required|exists:hrd_position,id',
            'pendidikan' => 'required|string',
            'no_hp' => 'required|string',
            'tanggal_masuk' => 'required|date',
            'status' => 'required|in:tetap,kontrak,tidak aktif',
            'kontrak_berakhir' => 'nullable|date',
            'masa_pensiun' => 'nullable|date',
            'doc_cv' => 'nullable|file',
            'doc_ktp' => 'nullable|file',
            'doc_kontrak' => 'nullable|file',
            'doc_pendukung' => 'nullable|file',
        ]);

        foreach (['doc_cv', 'doc_ktp', 'doc_kontrak', 'doc_pendukung'] as $doc) {
            if ($request->hasFile($doc)) {
                $data[$doc] = $request->file($doc)->store('documents');
            }
        }

        $hrd_employee->update($data);

        return redirect()->route('hrd.employee.index')->with('success', 'Data karyawan diperbarui.');
    }

    public function destroy(Employee $hrd_employee)
    {
        $hrd_employee->delete();
        return redirect()->route('hrd.employee.index')->with('success', 'Karyawan dihapus.');
    }
}
