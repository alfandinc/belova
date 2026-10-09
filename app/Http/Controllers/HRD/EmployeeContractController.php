<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\Employee;
use App\Models\HRD\EmployeeContract;
use App\Models\HRD\EmployeeLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Contract history of an employee, managed from a modal on the karyawan list.
 * Non-aktif employees are included: their contract can be renewed (which makes them kontrak again).
 */
class EmployeeContractController extends Controller
{
    /** AJAX: the modal content (history + renew / terminate forms). A direct visit opens the list with the modal. */
    public function index(Request $request, $employeeId)
    {
        if (!$request->ajax()) {
            return redirect()->route('hrd.employee.index', ['kontrak' => $employeeId]);
        }

        $employee = Employee::withInactive()->with('positions.divisions')->findOrFail($employeeId);
        $contracts = $employee->contracts()->with('creator')->orderBy('start_date', 'desc')->get();
        $lastContract = $contracts->sortByDesc('end_date')->first();
        $canManage = EmployeeController::canManage();
        $openRenew = $request->boolean('baru'); // opened right after the status became kontrak

        return response()->json([
            'success' => true,
            'html' => view('hrd.employee.contracts._manage', compact('employee', 'contracts', 'lastContract', 'canManage', 'openRenew'))->render(),
        ]);
    }

    /**
     * New contract (first one or renewal). The previous active contract becomes 'renewed'.
     * End date is inclusive: 1 Jan + 12 bulan = 31 Des (no month overflow, 31 Jan + 1 bulan = 28/29 Feb).
     */
    public function store(Request $request, $employeeId)
    {
        $employee = Employee::withInactive()->findOrFail($employeeId);

        $data = $request->validate([
            'start_date' => 'required|date',
            'duration_months' => 'required|integer|min:1|max:60',
            'notes' => 'nullable|string',
            'contract_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
        ]);

        $startDate = Carbon::parse($data['start_date'])->startOfDay();
        $endDate = $startDate->copy()->addMonthsNoOverflow((int) $data['duration_months'])->subDay();

        $document = $request->hasFile('contract_document')
            ? $request->file('contract_document')->store('documents/employees/contracts', 'public')
            : null;

        DB::transaction(function () use ($employee, $data, $startDate, $endDate, $document) {
            $before = EmployeeLog::snapshot($employee);
            $employee->contracts()->where('status', 'active')->update(['status' => 'renewed']);

            $employee->contracts()->create([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'duration_months' => (int) $data['duration_months'],
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
                'contract_document' => $document,
                'created_by' => Auth::id(),
            ]);

            // Kontrak again (also re-activates a non-aktif employee, whose nonaktif data no longer applies)
            $employee->update([
                'kontrak_berakhir' => $endDate,
                'status' => 'kontrak',
                'nonaktif_tanggal' => null,
                'nonaktif_alasan' => null,
                'nonaktif_keterangan' => null,
            ]);

            EmployeeLog::note($employee->id, 'kontrak', 'Kontrak baru', null,
                $startDate->format('d/m/Y') . ' – ' . $endDate->format('d/m/Y') . ' (' . $data['duration_months'] . ' bulan)');
            $employee->unsetRelations();
            EmployeeLog::recordDiff($employee->id, $before, EmployeeLog::snapshot($employee));
        });

        return response()->json([
            'success' => true,
            'message' => 'Kontrak disimpan, berlaku sampai ' . $endDate->locale('id')->translatedFormat('j F Y') . '.',
        ]);
    }

    /** Terminate the active contract early: the employee becomes non-aktif from the effective date. */
    public function terminate(Request $request, $employeeId, $contractId)
    {
        $contract = EmployeeContract::where('employee_id', $employeeId)->findOrFail($contractId);
        $employee = Employee::withInactive()->findOrFail($employeeId);

        $data = $request->validate([
            'nonaktif_alasan' => ['required', Rule::in(array_keys(Employee::NONAKTIF_ALASAN))],
            'nonaktif_tanggal' => 'required|date',
            'termination_notes' => 'required|string|max:1000',
        ], [
            'termination_notes.required' => 'Keterangan wajib diisi.',
        ]);

        if ($contract->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Hanya kontrak yang aktif yang bisa diputus.'], 422);
        }

        $effective = Carbon::parse($data['nonaktif_tanggal'])->startOfDay();
        $alasan = Employee::NONAKTIF_ALASAN[$data['nonaktif_alasan']];

        DB::transaction(function () use ($contract, $employee, $data, $effective, $alasan) {
            $before = EmployeeLog::snapshot($employee);

            $contract->update([
                'status' => 'terminated',
                'end_date' => $contract->end_date->gt($effective) ? $effective : $contract->end_date,
                'notes' => trim(($contract->notes ? $contract->notes . "\n\n" : '') . 'Diputus ' . $effective->format('d/m/Y') . " ($alasan): " . $data['termination_notes']),
            ]);

            $employee->update([
                'status' => 'tidak aktif',
                'nonaktif_tanggal' => $effective,
                'nonaktif_alasan' => $data['nonaktif_alasan'],
                'nonaktif_keterangan' => $data['termination_notes'],
            ]);

            EmployeeLog::note($employee->id, 'kontrak', 'Kontrak', 'Aktif', "Diputus per {$effective->format('d/m/Y')} ($alasan)");
            $employee->unsetRelations();
            EmployeeLog::recordDiff($employee->id, $before, EmployeeLog::snapshot($employee));
        });

        return response()->json([
            'success' => true,
            'message' => 'Kontrak diputus. Status karyawan menjadi tidak aktif.',
        ]);
    }
}
