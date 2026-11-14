<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\Employee;
use App\Models\HRD\EmployeeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Profil Saya: one page to view and edit one's own data (topbar). Every account may open it; the karyawan
 * part only shows when the account is linked to an employee, ganti password works for all.
 * NIK, tanggal masuk, status, posisi, perusahaan, BPJS, rekening ... are maintained by HRD (read-only here).
 */
class EmployeeSelfServiceController extends Controller
{
    private const DOCUMENTS = ['doc_cv' => 'CV', 'doc_ktp' => 'KTP', 'doc_pendukung' => 'Dokumen Pendukung'];

    public function profile()
    {
        $user = Auth::user();
        $employee = $user->employee; // null: account without karyawan data (dokter, ...)

        return view('hrd.employee.profile', [
            'user' => $user,
            'employee' => $employee?->loadMissing('positions.divisions'),
            'contracts' => $employee ? $employee->contracts()->orderByDesc('start_date')->get() : collect(),
            'documents' => self::DOCUMENTS,
            'jurusanList' => $employee ? $this->jurusanList() : [],
        ]);
    }

    /** The employee's own contract document (HRD opens it from Data Karyawan). */
    public function contractDocument($contractId)
    {
        $employee = Auth::user()->employee;
        abort_unless($employee, 404);

        return EmployeeContractController::documentResponse($employee->contracts()->findOrFail($contractId));
    }

    public function updateProfile(Request $request)
    {
        $employee = Auth::user()->employee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Akun ini belum terhubung dengan data karyawan.'], 404);
        }

        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date|before:today',
            'pendidikan_jenjang' => 'nullable|in:' . implode(',', Employee::PENDIDIKAN),
            'pendidikan_jurusan' => 'nullable|string|max:80',
            'alamat' => 'nullable|string|max:500',
            'gol_darah' => 'nullable|string|max:5', // the select also offers an older value such as "A+"
            'no_hp' => 'nullable|string|max:30',
            'darurat_nama' => 'nullable|string|max:100',
            'darurat_hubungan' => 'nullable|string|max:50',
            'no_darurat' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255|unique:hrd_employee,email,' . $employee->id,
            'instagram' => 'nullable|string|max:255',
            // Public disk: only images / documents, never a script the web server could run
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            // 2MB: same as Data Karyawan and the server's upload_max_filesize
            'doc_cv' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
            'doc_ktp' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'doc_pendukung' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,zip|max:2048',
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            'photo.max' => 'Foto maksimal 2MB.',
            'mimes' => 'Format file tidak didukung.',
            'uploaded' => 'File gagal diunggah, kemungkinan lebih dari 2MB.',
            'max' => ':attribute terlalu besar / panjang.',
        ]);

        // Same format as Data Karyawan: "S1 Ilmu Komunikasi"
        $data['pendidikan'] = Employee::joinPendidikan($data['pendidikan_jenjang'] ?? null, $data['pendidikan_jurusan'] ?? null);
        unset($data['pendidikan_jenjang'], $data['pendidikan_jurusan']);
        $data['instagram'] = array_values(array_filter(array_map(fn($i) => ltrim(trim($i), '@'),
            explode(',', (string) ($data['instagram'] ?? ''))), 'strlen'));

        $old = [];
        $folders = ['photo' => 'employees/photos'] + array_fill_keys(array_keys(self::DOCUMENTS), 'documents/employees');
        foreach ($folders as $field => $folder) {
            unset($data[$field]);
            if ($request->hasFile($field)) {
                $data[$field] = $request->file($field)->store($folder, 'public');
                $old[] = $employee->{$field};
            }
        }

        DB::transaction(function () use ($employee, $data) {
            $before = EmployeeLog::snapshot($employee);
            $employee->update($data);
            if ($employee->user && $employee->user->name !== $data['nama']) {
                $employee->user->update(['name' => $data['nama']]); // the account shows the same name
            }
            $employee->unsetRelations();
            EmployeeLog::recordDiff($employee->id, $before, EmployeeLog::snapshot($employee)); // HRD sees what changed
        });

        // Replaced files go only after the new data is saved
        foreach (array_filter($old) as $path) {
            Storage::disk('public')->delete($path);
        }

        return response()->json(['success' => true, 'message' => 'Profil berhasil diperbarui.']);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed|different:current_password',
        ], [
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak sama.',
            'password.different' => 'Password baru harus berbeda dari password saat ini.',
        ]);

        $user = Auth::user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Password saat ini tidak sesuai.',
                'errors' => ['current_password' => ['Password saat ini tidak sesuai.']]], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['success' => true, 'message' => 'Password berhasil diperbarui.']);
    }

    /** Jurusan already used (datalist), same source as Data Karyawan. */
    private function jurusanList(): array
    {
        return Employee::withInactive()->whereNotNull('pendidikan')->distinct()->pluck('pendidikan')
            ->map(fn($p) => Employee::splitPendidikan($p)[1])->filter()->unique()->sort()->values()->all();
    }
}
