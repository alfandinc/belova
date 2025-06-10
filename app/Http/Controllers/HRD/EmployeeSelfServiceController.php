<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\Area\Village;
use App\Models\HRD\Employee;
use App\Models\AreaVillage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class EmployeeSelfServiceController extends Controller
{
    public function profile()
    {
        $employee = Auth::user()->employee;

        if (!$employee) {
            return redirect()->route('hrd.dashboard')
                ->with('error', 'Data karyawan tidak ditemukan');
        }

        return view('hrd.employee.profile', compact('employee'));
    }

    public function editProfile()
    {
        $employee = Auth::user()->employee;
        $villages = Village::all();

        if (!$employee) {
            return redirect()->route('hrd.dashboard')
                ->with('error', 'Data karyawan tidak ditemukan');
        }

        return view('hrd.employee.edit-profile', compact('employee', 'villages'));
    }

    public function updateProfile(Request $request)
    {
        $employee = Auth::user()->employee;

        if (!$employee) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data karyawan tidak ditemukan'
                ]);
            }

            return redirect()->route('hrd.dashboard')
                ->with('error', 'Data karyawan tidak ditemukan');
        }

        $data = $request->validate([
            'alamat' => 'required|string',
            'village_id' => 'nullable|exists:area_villages,id',
            'no_hp' => 'required|string',
            'doc_ktp' => 'nullable|file|max:2048',
        ]);

        // Handle KTP document update
        if ($request->hasFile('doc_ktp')) {
            if ($employee->doc_ktp) {
                Storage::delete($employee->doc_ktp);
            }
            $data['doc_ktp'] = $request->file('doc_ktp')->store('documents/employees');
        }

        $employee->update($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Profil berhasil diperbarui',
                'redirect' => route('hrd.employee.profile')
            ]);
        }

        return redirect()->route('hrd.employee.profile')
            ->with('success', 'Profil berhasil diperbarui');
    }

    public function changePassword()
    {
        return view('hrd.employee.change-password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password lama tidak sesuai'
                ]);
            }

            return back()->withErrors(['current_password' => 'Password lama tidak sesuai']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diperbarui'
            ]);
        }

        return redirect()->route('hrd.employee.profile')
            ->with('success', 'Password berhasil diperbarui');
    }
}
