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
    public function profile(Request $request)
{
    $employee = Auth::user()->employee;
    // $villages = \App\Models\Area\Village::all();
    $positions = \App\Models\HRD\Position::all();

    if (!$employee) {
        return redirect()->route('hrd.dashboard')
            ->with('error', 'Data karyawan tidak ditemukan');
    }

    // If you want to start in edit mode, pass ?edit=1 in the URL
    $editMode = $request->query('edit', false);

    return view('hrd.employee.profile', compact('employee', 'positions', 'editMode'));
}

    // Add this new method
public function getEditProfileModal(Request $request)
{
    $employee = Auth::user()->employee;
    
    if (!$employee) {
        return response()->json([
            'success' => false,
            'message' => 'Data karyawan tidak ditemukan'
        ], 404);
    }

    // Check if we're showing the password change modal
    if ($request->query('mode') === 'password') {
        return view('hrd.partials.modal-change-password');
    }

    // Default to profile edit modal
    return view('hrd.partials.modal-edit-profile', compact('employee'));
}

// Update the updateProfile method to handle all fields
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
        'nama' => 'nullable|string',
        'tempat_lahir' => 'nullable|string',
        'tanggal_lahir' => 'nullable|date',
        'pendidikan' => 'nullable|string',
        'alamat' => 'nullable|string',
        'village_id' => 'nullable|exists:area_villages,id',
        'no_hp' => 'nullable|string',
        'nik' => 'nullable|string',
        'tanggal_masuk' => 'nullable|date',
        'photo' => 'nullable|image',
        'doc_cv' => 'nullable|file|max:2048',
        'doc_ktp' => 'nullable|file|max:2048',
        'doc_kontrak' => 'nullable|file|max:2048',
        'doc_pendukung' => 'nullable|file|max:2048',
    ]);

    // Handle photo upload
    if ($request->hasFile('photo')) {
    if ($employee->photo) {
        Storage::disk('public')->delete($employee->photo);
    }
    $data['photo'] = $request->file('photo')->store('employees/photos', 'public');
}

    // Handle document uploads
    foreach (['doc_cv', 'doc_ktp', 'doc_kontrak', 'doc_pendukung'] as $doc) {
    if ($request->hasFile($doc)) {
        if ($employee->$doc) {
            Storage::disk('public')->delete($employee->$doc);
        }
        $data[$doc] = $request->file($doc)->store('documents/employees', 'public');
    }
}

    $employee->update($data);

    // SYNC THE USER NAME with EMPLOYEE NAME
    if (isset($data['nama']) && $employee->user) {
        $employee->user->update([
            'name' => $data['nama']
        ]);
    }

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
