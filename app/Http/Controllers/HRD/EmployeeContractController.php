<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\ERM\Klinik;
use App\Models\HRD\Employee;
use App\Models\HRD\EmployeeContract;
use App\Models\HRD\EmployeeLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Contract history of an employee, managed from a modal on the karyawan list.
 * Non-aktif employees are included: their contract can be renewed (which makes them kontrak again).
 */
class EmployeeContractController extends Controller
{
    private const DOC_RULE = 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048';

    /** AJAX: the modal content (history + forms). A direct visit opens the list with the modal. */
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
     * New contract (first one or renewal). The previous active contract becomes 'renewed' and, when the new one
     * starts before it ends, is cut to the day before so periods never overlap.
     * End date is inclusive: 1 Jan + 12 bulan = 31 Des (no month overflow, 31 Jan + 1 bulan = 28/29 Feb).
     */
    public function store(Request $request, $employeeId)
    {
        $employee = Employee::withInactive()->findOrFail($employeeId);

        $data = $request->validate([
            'start_date' => 'required|date',
            'duration_months' => 'required|integer|min:1|max:60',
            'notes' => 'nullable|string|max:2000',
            'contract_document' => self::DOC_RULE,
        ]);

        $startDate = Carbon::parse($data['start_date'])->startOfDay();
        $endDate = self::endDate($startDate, (int) $data['duration_months']);

        $last = $employee->contracts()->orderByDesc('end_date')->first();
        if ($last) {
            // A renewal may overlap the running contract (it is cut), but never start on / before its start.
            // A finished contract (Diputus / Selesai / Diperpanjang) is history: the new one starts after it.
            $running = $last->status === 'active';
            $min = ($running ? $last->start_date : $last->end_date)->copy()->addDay();
            if ($startDate->lt($min)) {
                throw ValidationException::withMessages(['start_date' => 'Tanggal mulai paling cepat ' . $min->format('d/m/Y')
                    . ($running ? ' (setelah tanggal mulai kontrak berjalan).' : ' (setelah kontrak terakhir berakhir).')]);
            }
        }

        $document = $this->storeDocument($request);

        $this->withDocument($document, function () use ($employee, $data, $startDate, $endDate, $document) {
            $before = EmployeeLog::snapshot($employee);

            foreach ($employee->contracts()->where('status', 'active')->get() as $old) {
                $old->update([
                    'status' => 'renewed',
                    'end_date' => $old->end_date->gte($startDate) ? $startDate->copy()->subDay() : $old->end_date,
                ]);
            }

            $employee->contracts()->create([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'duration_months' => (int) $data['duration_months'],
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
                'contract_document' => $document,
                'created_by' => Auth::id(),
            ]);

            // Kontrak again (also re-activates a non-aktif employee and cancels a scheduled nonaktif)
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

        $total = $employee->fresh()->totalHariKontrak();

        return response()->json([
            'success' => true,
            'message' => 'Kontrak disimpan, berlaku sampai ' . $endDate->locale('id')->translatedFormat('j F Y') . '.'
                . ($total > Employee::PKWT_MAKS_HARI ? ' Perhatian: total masa kontrak melewati batas PKWT 5 tahun.' : ''),
        ]);
    }

    /**
     * Fix a contract entered wrongly. The running contract: period, notes and document; an older one: notes and
     * document only (its period is history). A correction reason is required and logged.
     */
    public function correct(Request $request, $employeeId, $contractId)
    {
        $employee = Employee::withInactive()->findOrFail($employeeId);
        $contract = $employee->contracts()->findOrFail($contractId);
        $running = $contract->status === 'active';

        $data = $request->validate([
            'start_date' => ($running ? 'required' : 'nullable') . '|date',
            'duration_months' => ($running ? 'required' : 'nullable') . '|integer|min:1|max:60',
            'notes' => 'nullable|string|max:2000',
            'contract_document' => self::DOC_RULE,
            'alasan_koreksi' => 'required|string|max:500',
        ], ['alasan_koreksi.required' => 'Alasan koreksi wajib diisi.']);

        // Browsers send textarea line breaks as \r\n: compare and store as \n
        $data['notes'] = isset($data['notes']) ? str_replace("\r\n", "\n", trim($data['notes'])) : null;
        $values = ['notes' => $data['notes'] ?: null];
        $changes = [];
        // Period only when start / duration really changed: older contracts may end on a date computed by another
        // rule, which must not shift when only notes or the document are corrected
        $periodChanged = $running && (Carbon::parse($data['start_date'])->toDateString() !== $contract->start_date->toDateString()
            || (int) $data['duration_months'] !== (int) $contract->duration_months);
        if ($periodChanged) {
            $start = Carbon::parse($data['start_date'])->startOfDay();
            $end = self::endDate($start, (int) $data['duration_months']);
            // Must stay after the contract before it
            $prev = $employee->contracts()->whereKeyNot($contract->id)
                ->where('start_date', '<', $contract->start_date)->orderByDesc('end_date')->first();
            if ($prev && $start->lte($prev->end_date)) {
                throw ValidationException::withMessages(['start_date' => 'Tanggal mulai harus setelah kontrak sebelumnya berakhir ('
                    . $prev->end_date->format('d/m/Y') . ').']);
            }
            $old = $contract->start_date->format('d/m/Y') . ' – ' . $contract->end_date->format('d/m/Y') . " ({$contract->duration_months} bln)";
            $new = $start->format('d/m/Y') . ' – ' . $end->format('d/m/Y') . " ({$data['duration_months']} bln)";
            if ($old !== $new) {
                $changes[] = "periode $old → $new";
            }
            $values += ['start_date' => $start, 'end_date' => $end, 'duration_months' => (int) $data['duration_months']];
        }
        if (($data['notes'] ?: null) !== ($contract->notes ? str_replace("\r\n", "\n", trim($contract->notes)) : null)) {
            $changes[] = 'catatan';
        }

        $document = $this->storeDocument($request);
        if ($document) {
            $values['contract_document'] = $document;
            $changes[] = $contract->contract_document ? 'dokumen diganti' : 'dokumen diunggah';
        }
        if (!$changes) {
            return response()->json(['success' => false, 'message' => 'Tidak ada yang diubah.'], 422);
        }
        $oldDocument = $contract->contract_document;

        $this->withDocument($document, function () use ($employee, $contract, $values, $changes, $data, $running) {
            $before = EmployeeLog::snapshot($employee);
            $contract->update($values);
            if (isset($values['end_date']) && $employee->status === 'kontrak') {
                $employee->update(['kontrak_berakhir' => $contract->end_date]);
            }
            EmployeeLog::note($employee->id, 'kontrak', 'Koreksi kontrak', null, implode('; ', $changes) . '. Alasan: ' . $data['alasan_koreksi']);
            $employee->unsetRelations();
            EmployeeLog::recordDiff($employee->id, $before, EmployeeLog::snapshot($employee));
        });

        if ($document && $oldDocument) {
            $this->deleteDocument($oldDocument);
        }

        return response()->json(['success' => true, 'message' => 'Kontrak dikoreksi: ' . implode('; ', $changes) . '.']);
    }

    /**
     * End the active contract. Last working day up to today: the employee becomes tidak aktif now.
     * A day in the future (resign with notice, kontrak tidak diperpanjang): scheduled, hrd:proses-nonaktif ends it
     * the day after. Alasan "Kontrak habis" ends it as Selesai, any other alasan as Diputus.
     */
    public function terminate(Request $request, $employeeId, $contractId)
    {
        $contract = EmployeeContract::where('employee_id', $employeeId)->findOrFail($contractId);
        $employee = Employee::withInactive()->findOrFail($employeeId);

        // A contract that has not started yet can still be cancelled today; scheduling goes up to the contract end
        $earliest = $contract->start_date->lt(today()) ? $contract->start_date : today();
        $latest = $contract->end_date->gt(today()) ? $contract->end_date : today();
        $data = $request->validate([
            'nonaktif_alasan' => ['required', Rule::in(array_keys(Employee::NONAKTIF_ALASAN))],
            'nonaktif_tanggal' => ['required', 'date', 'after_or_equal:' . $earliest->toDateString(), 'before_or_equal:' . $latest->toDateString()],
            'termination_notes' => 'required|string|max:1000',
        ], [
            'termination_notes.required' => 'Keterangan wajib diisi.',
            'nonaktif_tanggal.before_or_equal' => 'Hari terakhir paling lambat ' . $latest->format('d/m/Y') . ' (akhir kontrak).',
            'nonaktif_tanggal.after_or_equal' => 'Tanggal tidak boleh sebelum ' . $earliest->format('d/m/Y') . ' (mulai kontrak).',
        ]);

        if ($contract->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Hanya kontrak yang aktif yang bisa diakhiri.'], 422);
        }

        $effective = Carbon::parse($data['nonaktif_tanggal'])->startOfDay();
        $alasan = Employee::NONAKTIF_ALASAN[$data['nonaktif_alasan']];
        $selesai = $data['nonaktif_alasan'] === 'kontrak_habis';
        $jadwal = $effective->gt(today());

        DB::transaction(function () use ($contract, $employee, $data, $effective, $alasan, $selesai, $jadwal) {
            $before = EmployeeLog::snapshot($employee);
            $nonaktif = [
                'nonaktif_tanggal' => $effective,
                'nonaktif_alasan' => $data['nonaktif_alasan'],
                'nonaktif_keterangan' => $data['termination_notes'],
            ];

            if ($jadwal) {
                $employee->update($nonaktif); // status stays: still working until the last day
                EmployeeLog::note($employee->id, 'kontrak', 'Kontrak', null,
                    "Dijadwalkan " . ($selesai ? 'selesai' : 'diputus') . " – hari terakhir {$effective->format('d/m/Y')} ($alasan)");
            } else {
                $end = $contract->end_date->gt($effective) ? $effective : $contract->end_date;
                $contract->update([
                    'status' => $selesai ? 'expired' : 'terminated',
                    'end_date' => $end->lt($contract->start_date) ? $contract->start_date : $end, // not-started contract
                    'notes' => trim(($contract->notes ? $contract->notes . "\n\n" : '') . ($selesai ? 'Berakhir ' : 'Diputus ')
                        . $effective->format('d/m/Y') . " ($alasan): " . $data['termination_notes']),
                ]);
                $employee->update(['status' => 'tidak aktif'] + $nonaktif);
                EmployeeLog::note($employee->id, 'kontrak', 'Kontrak', 'Aktif',
                    ($selesai ? 'Selesai' : 'Diputus') . " per {$effective->format('d/m/Y')} ($alasan)");
            }

            $employee->unsetRelations();
            EmployeeLog::recordDiff($employee->id, $before, EmployeeLog::snapshot($employee));
        });

        return response()->json([
            'success' => true,
            'message' => $jadwal
                ? 'Dijadwalkan: karyawan tetap aktif sampai ' . $effective->locale('id')->translatedFormat('j F Y') . ', lalu otomatis tidak aktif.'
                : ($selesai ? 'Kontrak selesai' : 'Kontrak diputus') . '. Status karyawan menjadi tidak aktif.',
        ]);
    }

    /** Cancel a scheduled nonaktif (the employee keeps working). */
    public function cancelSchedule($employeeId)
    {
        $employee = Employee::findOrFail($employeeId); // tidak aktif employees have nothing scheduled
        if (!$employee->nonaktifTerjadwal()) {
            return response()->json(['success' => false, 'message' => 'Tidak ada nonaktif terjadwal.'], 422);
        }

        DB::transaction(function () use ($employee) {
            $before = EmployeeLog::snapshot($employee);
            $tanggal = $employee->nonaktif_tanggal->format('d/m/Y');
            $employee->update(['nonaktif_tanggal' => null, 'nonaktif_alasan' => null, 'nonaktif_keterangan' => null]);
            EmployeeLog::note($employee->id, 'kontrak', 'Nonaktif terjadwal', $tanggal, 'Dibatalkan');
            $employee->unsetRelations();
            EmployeeLog::recordDiff($employee->id, $before, EmployeeLog::snapshot($employee));
        });

        return response()->json(['success' => true, 'message' => 'Jadwal nonaktif dibatalkan.']);
    }

    /**
     * Kontrak → karyawan tetap from the effective date (up to today). The running contract ends the day before
     * as Selesai; a scheduled nonaktif is cancelled.
     */
    public function makePermanent(Request $request, $employeeId)
    {
        $employee = Employee::findOrFail($employeeId);
        if ($employee->status !== 'kontrak') {
            return response()->json(['success' => false, 'message' => 'Hanya karyawan berstatus Kontrak yang bisa diangkat tetap.'], 422);
        }
        $active = $employee->contracts()->where('status', 'active')->orderByDesc('end_date')->first();
        $data = $request->validate([
            'tanggal_tetap' => array_filter(['required', 'date', 'before_or_equal:today',
                $active ? 'after:' . $active->start_date->toDateString() : null]),
            'catatan_tetap' => 'nullable|string|max:1000',
        ], [
            'tanggal_tetap.before_or_equal' => 'Tanggal efektif paling lambat hari ini: angkat tetap pada tanggal efektifnya.',
            'tanggal_tetap.after' => 'Tanggal efektif harus setelah kontrak berjalan dimulai (' . optional($active?->start_date)->format('d/m/Y') . ').',
        ]);

        $effective = Carbon::parse($data['tanggal_tetap'])->startOfDay();

        DB::transaction(function () use ($employee, $effective, $data) {
            $before = EmployeeLog::snapshot($employee);
            $employee->update([
                'status' => 'tetap',
                'nonaktif_tanggal' => null,
                'nonaktif_alasan' => null,
                'nonaktif_keterangan' => null,
            ]);
            $employee->endActiveContractIfNotKontrak($effective->copy()->subDay());
            EmployeeLog::note($employee->id, 'kontrak', 'Status', 'Kontrak', 'Diangkat karyawan tetap per ' . $effective->format('d/m/Y')
                . (!empty($data['catatan_tetap']) ? ': ' . $data['catatan_tetap'] : ''));
            $employee->unsetRelations();
            EmployeeLog::recordDiff($employee->id, $before, EmployeeLog::snapshot($employee));
        });

        return response()->json(['success' => true, 'message' => $employee->nama . ' diangkat menjadi karyawan tetap.']);
    }

    /** Draft PKWT letter (PDF) of a contract, filled with the employee's data. */
    public function printLetter($employeeId, $contractId)
    {
        $employee = Employee::withInactive()->with('positions.divisions')->findOrFail($employeeId);
        $contract = $employee->contracts()->findOrFail($contractId);
        $klinik = Klinik::where('nama', $employee->perusahaan)->first();
        $ke = $employee->contracts()->where('start_date', '<=', $contract->start_date)->count(); // kontrak ke-

        return Pdf::loadView('hrd.employee.contracts.pkwt', compact('employee', 'contract', 'klinik', 'ke'))
            ->setPaper('a4')
            ->stream('PKWT ' . $employee->nama . ' ' . $contract->start_date->format('Y-m-d') . '.pdf');
    }

    /** Contract document, for the roles that may view the contract list. Uploads before 2026-11-14 are on the public disk. */
    public function document($employeeId, $contractId)
    {
        $contract = EmployeeContract::where('employee_id', $employeeId)->findOrFail($contractId);

        return self::documentResponse($contract);
    }

    /** Shared with the employee's own profile page. */
    public static function documentResponse(EmployeeContract $contract)
    {
        $path = $contract->contract_document;
        abort_unless($path, 404);

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return response()->file(Storage::disk($disk)->path($path));
            }
        }
        abort(404, 'File dokumen kontrak tidak ditemukan.');
    }

    /** Inclusive end: 1 Jan + 12 bulan = 31 Des, no month overflow. Same rule as the modal script. */
    private static function endDate(Carbon $start, int $months): Carbon
    {
        return $start->copy()->addMonthsNoOverflow($months)->subDay();
    }

    /** Private disk: contract documents are opened through document(), which checks the role. */
    private function storeDocument(Request $request): ?string
    {
        return $request->hasFile('contract_document')
            ? $request->file('contract_document')->store('hrd/contracts', 'local')
            : null;
    }

    private function deleteDocument(string $path): void
    {
        foreach (['local', 'public'] as $disk) {
            Storage::disk($disk)->delete($path);
        }
    }

    /** Run the transaction; a just-uploaded document is removed again when it fails (no row points to it). */
    private function withDocument(?string $document, \Closure $work): void
    {
        try {
            DB::transaction($work);
        } catch (\Throwable $e) {
            if ($document) {
                Storage::disk('local')->delete($document);
            }
            throw $e;
        }
    }
}
