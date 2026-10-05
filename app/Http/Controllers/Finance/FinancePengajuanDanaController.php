<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Finance\FinancePengajuanDana;
use App\Models\Finance\FinancePengajuanDanaItem;
use App\Models\Finance\FinancePengajuanDanaApproval;
use App\Models\Finance\FinanceDanaApprover;
use App\Models\Finance\FinancePengajuanDanaPayment;
use App\Models\Finance\FinancePengajuanDanaRealisasi;
use App\Models\ERM\FakturBeli;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class FinancePengajuanDanaController extends Controller
{
    private function getPengajuanVisibilityContext(): array
    {
        $user = Auth::user();
        $isAdmin = $user && method_exists($user, 'hasRole') && $user->hasRole('Admin');
        $isApprover = $user
            ? FinanceDanaApprover::where('user_id', $user->id)->where('aktif', 1)->exists()
            : false;
        $employeeId = ($user && isset($user->employee) && $user->employee)
            ? $user->employee->id
            : null;

        return [
            'user' => $user,
            'is_admin' => $isAdmin,
            'is_approver' => $isApprover,
            'employee_id' => $employeeId,
            'has_global_access' => $isAdmin || $isApprover,
        ];
    }

    private function scopePengajuanVisibility($query)
    {
        $context = $this->getPengajuanVisibilityContext();
        if ($context['has_global_access']) {
            return $query;
        }

        if (!$context['employee_id']) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('employee_id', $context['employee_id']);
    }

    private function authorizePengajuanAccess(FinancePengajuanDana $pengajuan): void
    {
        $context = $this->getPengajuanVisibilityContext();
        if ($context['has_global_access']) {
            return;
        }

        abort_unless(
            $context['employee_id'] && (int) $pengajuan->employee_id === (int) $context['employee_id'],
            403,
            'Unauthorized access to pengajuan dana.'
        );
    }

    /*
     * Tiered approval rules (single source of truth for buttons, filters display, approve/decline/bulk/pay):
     * - the chain = active approvers whose jenis is empty (global) or equals the pengajuan sumber_dana
     * - approval runs from the highest tingkat down to the lowest; one approval per tingkat is enough
     * - any decline stops the flow; a paid pengajuan can no longer be approved/declined
     * - a user may hold several approver rows (e.g. different tingkat/jenis); the row whose turn it is acts
     */
    private function loadActiveApprovers(): Collection
    {
        return FinanceDanaApprover::with('user')->where('aktif', 1)->get();
    }

    private function approverLevel($approver): int
    {
        return (int) ($approver->tingkat ?: 1);
    }

    private function approverChainFor($sumberDana, Collection $activeApprovers): Collection
    {
        $sumber = trim((string) $sumberDana);
        return $activeApprovers->filter(function($a) use ($sumber) {
            $j = trim((string) ($a->jenis ?? ''));
            return $j === '' || ($sumber !== '' && strcasecmp($j, $sumber) === 0);
        })->values();
    }

    private function approverDisplayName($approver): string
    {
        if (!$approver) return '';
        return $approver->user ? (string) ($approver->user->name ?? '') : (string) ($approver->nama ?? '');
    }

    private function isDeclinedApproval($approval): bool
    {
        return in_array($approval->status, ['declined', 'rejected'], true);
    }

    /**
     * Approval progress of a pengajuan: state (approved|pending|declined|unconfigured), per-level steps,
     * counts, who it is waiting for and who declined.
     */
    private function approvalSummary(FinancePengajuanDana $pengajuan, Collection $activeApprovers, ?Collection $approvals = null): array
    {
        $chain = $this->approverChainFor($pengajuan->sumber_dana, $activeApprovers);
        $approvals = $approvals ?? $pengajuan->approvals;
        $approvedIds = $approvals->where('status', 'approved')->pluck('approver_id')->map('intval')->all();
        $declined = $approvals->first(function($ap) { return $this->isDeclinedApproval($ap); });

        $levels = $chain->map(function($a) { return $this->approverLevel($a); })->unique()->sortDesc()->values();
        $steps = [];
        $approvedLevels = 0;
        $nextLevelApprovers = null;
        foreach ($levels as $lvl) {
            $atLevel = $chain->filter(function($a) use ($lvl) { return $this->approverLevel($a) === $lvl; });
            $ids = $atLevel->pluck('id')->map('intval');
            $isApproved = $ids->intersect($approvedIds)->isNotEmpty();
            $isDeclined = $declined && $ids->contains((int) $declined->approver_id);
            if ($isApproved) $approvedLevels++;
            elseif ($nextLevelApprovers === null && !$isDeclined) $nextLevelApprovers = $atLevel;
            $steps[] = $isDeclined ? 'declined' : ($isApproved ? 'approved' : 'pending');
        }
        $totalLevels = $levels->count();

        if ($declined) $state = 'declined';
        elseif ($totalLevels > 0 && $approvedLevels >= $totalLevels) $state = 'approved';
        elseif ($totalLevels > 0) $state = 'pending';
        else $state = 'unconfigured';

        return [
            'state' => $state,
            'steps' => $steps,
            'approved_levels' => $approvedLevels,
            'total_levels' => $totalLevels,
            'waiting_for' => ($state === 'pending' && $nextLevelApprovers)
                ? $nextLevelApprovers->map(function($a) { return $this->approverDisplayName($a); })->filter()->unique()->implode(' / ')
                : '',
            'declined_by' => $declined ? $this->approverDisplayName($declined->approver) : '',
            'decline_note' => $declined ? trim((string) ($declined->note ?? '')) : '',
        ];
    }

    /** Whether this approver row may approve/decline the pengajuan right now. Returns [bool, message]. */
    private function approverTurnCheck(FinancePengajuanDana $pengajuan, FinanceDanaApprover $approver, Collection $chain, Collection $approvals): array
    {
        if ($pengajuan->payment_status === 'paid') {
            return [false, 'Pengajuan sudah dibayar.'];
        }
        if ($approvals->contains(function($ap) { return $this->isDeclinedApproval($ap); })) {
            return [false, 'Pengajuan sudah ditolak.'];
        }
        if ($approvals->contains('approver_id', $approver->id)) {
            return [false, 'Anda sudah memproses pengajuan ini.'];
        }

        $approvedIds = $approvals->where('status', 'approved')->pluck('approver_id')->map('intval')->all();
        $levelIsApproved = function($lvl) use ($chain, $approvedIds) {
            return $chain->filter(function($a) use ($lvl) { return $this->approverLevel($a) === $lvl; })
                ->pluck('id')->map('intval')->intersect($approvedIds)->isNotEmpty();
        };

        $myLevel = $this->approverLevel($approver);
        if ($levelIsApproved($myLevel)) {
            return [false, 'Tingkat Anda sudah disetujui oleh approver lain.'];
        }
        $higherLevels = $chain->map(function($a) { return $this->approverLevel($a); })->unique()
            ->filter(function($lvl) use ($myLevel) { return $lvl > $myLevel; });
        foreach ($higherLevels as $lvl) {
            if (!$levelIsApproved($lvl)) {
                return [false, 'Menunggu persetujuan dari tingkat yang lebih tinggi.'];
            }
        }
        return [true, ''];
    }

    /**
     * Pick the user's approver row that may act on this pengajuan now (highest tingkat first).
     * Returns [FinanceDanaApprover|null, message].
     */
    private function resolveActingApprover(FinancePengajuanDana $pengajuan, $user, Collection $activeApprovers, Collection $approvals): array
    {
        if (!$user) return [null, 'Unauthorized'];
        $chain = $this->approverChainFor($pengajuan->sumber_dana, $activeApprovers);
        $mine = $chain->filter(function($a) use ($user) { return (int) $a->user_id === (int) $user->id; })
            ->sortByDesc(function($a) { return $this->approverLevel($a); });
        if ($mine->isEmpty()) {
            return [null, 'Anda tidak terdaftar sebagai approver aktif untuk sumber dana ini.'];
        }
        $firstMessage = null;
        foreach ($mine as $approver) {
            [$ok, $msg] = $this->approverTurnCheck($pengajuan, $approver, $chain, $approvals);
            if ($ok) return [$approver, ''];
            $firstMessage = $firstMessage ?? $msg;
        }
        return [null, $firstMessage];
    }

    /**
     * Record an approve/decline by the user. Runs under a row lock so concurrent clicks/bulk runs
     * cannot record the same step twice. Returns [bool success, string message].
     */
    private function recordApprovalAction(int $pengajuanId, $user, string $status, ?string $note = null): array
    {
        return DB::transaction(function() use ($pengajuanId, $user, $status, $note) {
            $pengajuan = FinancePengajuanDana::whereKey($pengajuanId)->lockForUpdate()->first();
            if (!$pengajuan) {
                return [false, 'Pengajuan tidak ditemukan.'];
            }
            $approvals = $pengajuan->approvals()->get();
            [$approver, $msg] = $this->resolveActingApprover($pengajuan, $user, $this->loadActiveApprovers(), $approvals);
            if (!$approver) {
                return [false, $msg];
            }

            $record = [
                'pengajuan_id' => $pengajuan->id,
                'approver_id' => $approver->id,
                'status' => $status,
                'tanggal_approve' => Carbon::now(),
            ];
            // note column comes from migration 2026_10_03_000001; skip it if that has not run yet
            if ($note !== null && Schema::hasColumn('finance_pengajuan_dana_approval', 'note')) {
                $record['note'] = $note;
            }
            FinancePengajuanDanaApproval::create($record);

            if ($status === 'declined') {
                $pengajuan->status = 'declined';
                $pengajuan->save();
            }

            return [true, $status === 'declined' ? 'Pengajuan ditolak' : 'Pengajuan disetujui'];
        });
    }
    private function rupiah($n): string
    {
        return 'Rp ' . number_format((float) $n, 0, ',', '.');
    }

    /** Store uploaded files (payment bukti) on the public disk. */
    private function storeFinanceFiles(array $files, string $dir): array
    {
        $paths = [];
        foreach ($files as $file) {
            if ($file && $file->isValid()) {
                $paths[] = $file->store($dir, 'public');
            }
        }
        return $paths;
    }

    // whether a pengajuan has at least one bukti transaksi file (handles array, JSON string or raw path)
    private function pengajuanHasBukti(FinancePengajuanDana $pengajuan): bool
    {
        return count($this->buktiPaths($pengajuan)) > 0;
    }

    private function pengajuanIsDeclined(FinancePengajuanDana $pengajuan): bool
    {
        return $pengajuan->approvals->contains(function($ap) {
            return in_array($ap->status, ['declined', 'rejected'], true);
        });
    }

    // a pengajuan can be edited while no approver has approved it yet, or once it has been declined
    private function pengajuanIsEditable(FinancePengajuanDana $pengajuan): bool
    {
        if ($this->pengajuanIsDeclined($pengajuan)) return true;
        return !$pengajuan->approvals->contains('status', 'approved');
    }

    /**
     * Bukti file paths of a pengajuan. Tolerates every stored format: a raw path (old rows),
     * a JSON array, and a double-encoded JSON array (written via json_encode on an array cast).
     */
    private function buktiPaths(FinancePengajuanDana $pengajuan): array
    {
        $value = $pengajuan->getRawOriginal('bukti_transaksi');
        for ($i = 0; $i < 2 && is_string($value); $i++) {
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) break; // plain path
            $value = $decoded;
        }
        if (is_string($value)) $value = [$value];
        if (!is_array($value)) return [];
        return array_values(array_filter($value, function($p) {
            return is_string($p) && trim($p) !== '';
        }));
    }

    /**
     * Which paid pengajuan need a realisasi (single source for tab, badge, buttons, status and submit):
     * paid on/after REALISASI_START_DATE (older ones were paid before the feature existed),
     * and not a jenis with an exact amount (Inkaso pays a fixed supplier faktur).
     */
    private const REALISASI_START_DATE = '2026-10-05';
    private const REALISASI_EXCLUDED_JENIS = ['Pembayaran Inkaso'];

    private function requiresRealisasi(FinancePengajuanDana $pengajuan): bool
    {
        return $pengajuan->payment_status === 'paid'
            && !in_array(trim((string) $pengajuan->jenis_pengajuan), self::REALISASI_EXCLUDED_JENIS, true)
            && $pengajuan->payment_date
            && Carbon::parse($pengajuan->payment_date)->gte(Carbon::parse(self::REALISASI_START_DATE)->startOfDay());
    }

    /** SQL version of requiresRealisasi() for finance_pengajuan_dana rows. */
    private function requiresRealisasiSql(): string
    {
        $pdo = DB::connection()->getPdo();
        $excluded = implode(', ', array_map(function($j) use ($pdo) { return $pdo->quote($j); }, self::REALISASI_EXCLUDED_JENIS));
        return "finance_pengajuan_dana.payment_status = 'paid'"
            . " AND TRIM(COALESCE(finance_pengajuan_dana.jenis_pengajuan, '')) NOT IN ({$excluded})"
            . " AND finance_pengajuan_dana.payment_date >= " . $pdo->quote(self::REALISASI_START_DATE . ' 00:00:00');
    }

    /** Fields of the pengajuan form that may be written from a request (status/payment are never client-controlled). */
    private const FORM_FIELDS = ['employee_id', 'division_id', 'sumber_dana', 'perusahaan', 'tanggal_pengajuan', 'jenis_pengajuan', 'rekening_id'];

    /** Validator shared by store and update. */
    private function pengajuanValidator(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer',
            'division_id' => 'nullable|integer',
            'sumber_dana' => 'required|string|max:100',
            'perusahaan' => 'required|string|max:150',
            'tanggal_pengajuan' => 'required|date',
            'jenis_pengajuan' => 'required|string|max:100',
            'rekening_id' => 'required|integer|exists:finance_rekening,id',
            'items_json' => 'required|json',
            'bukti_transaksi' => 'nullable|array|max:10',
            'bukti_transaksi.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'employee_id.required' => 'Nama Pengaju wajib diisi',
            'sumber_dana.required' => 'Sumber Dana wajib dipilih',
            'perusahaan.required' => 'Perusahaan wajib dipilih',
            'tanggal_pengajuan.required' => 'Tanggal wajib diisi',
            'jenis_pengajuan.required' => 'Jenis Pengajuan wajib dipilih',
            'rekening_id.required' => 'Rekening Tujuan wajib dipilih',
            'rekening_id.exists' => 'Rekening Tujuan tidak ditemukan',
            'items_json.required' => 'Minimal 1 item',
            'items_json.json' => 'Format items tidak valid',
            'bukti_transaksi.max' => 'Maksimal 10 file bukti',
            'bukti_transaksi.*.image' => 'Bukti harus berupa gambar',
            'bukti_transaksi.*.mimes' => 'Format bukti: jpg, png, gif',
            'bukti_transaksi.*.max' => 'Ukuran bukti maksimal 2MB per file',
        ]);

        $validator->after(function($v) use ($request) {
            if ($v->errors()->has('items_json')) return;
            $items = $this->parseItems($request->input('items_json'));
            if (empty($items)) {
                $v->errors()->add('items_json', 'Minimal 1 item dengan nama dan qty yang valid');
                return;
            }
            foreach ($items as $it) {
                if ($it['price'] < 0) {
                    $v->errors()->add('items_json', 'Harga item tidak boleh negatif');
                    return;
                }
            }
        });

        return $validator;
    }

    /** Normalized items from items_json; rows without a name or a quantity are dropped. */
    private function parseItems($itemsJson): array
    {
        $rows = json_decode((string) $itemsJson, true);
        if (!is_array($rows)) return [];

        $items = [];
        foreach ($rows as $it) {
            if (!is_array($it)) continue;
            $name = trim((string) ($it['desc'] ?? $it['name'] ?? ''));
            $fakturId = !empty($it['fakturbeli_id']) ? (int) $it['fakturbeli_id'] : null;
            $qty = $fakturId ? 1 : (int) round((float) ($it['qty'] ?? 0));
            if ($name === '' || $qty <= 0) continue;
            $items[] = [
                'name' => $name,
                'qty' => $qty,
                'price' => (float) ($it['price'] ?? 0),
                'notes' => trim((string) ($it['notes'] ?? '')) ?: null,
                'employee_id' => !empty($it['employee_id']) ? (int) $it['employee_id'] : null,
                'fakturbeli_id' => $fakturId,
            ];
        }
        return $items;
    }

    /**
     * Replace the items of a pengajuan and return the grand total (computed from what is saved).
     * Faktur rows are locked FOR UPDATE and checked, so one faktur can never be in two active
     * pengajuan, even when two people submit it at the same moment. Must run inside a transaction.
     */
    private function saveItems(FinancePengajuanDana $pengajuan, array $items): float
    {
        $pengajuan->items()->delete();
        $grandTotal = 0;
        $seenFaktur = [];

        foreach ($items as $it) {
            $qty = $it['qty'];
            $price = $it['price'];
            $row = [
                'pengajuan_id' => $pengajuan->id,
                'nama_item' => $it['name'],
                'employee_id' => $it['employee_id'],
                'notes' => $it['notes'],
            ];

            if ($it['fakturbeli_id']) {
                $fakturId = $it['fakturbeli_id'];
                if (isset($seenFaktur[$fakturId])) continue; // same faktur added twice in one form
                $faktur = FakturBeli::whereKey($fakturId)->lockForUpdate()->first();
                if ($faktur) {
                    $usedIn = FinancePengajuanDanaItem::where('fakturbeli_id', $fakturId)
                        ->where('pengajuan_id', '!=', $pengajuan->id)
                        ->whereHas('pengajuan', function($q) {
                            // a declined pengajuan no longer holds its faktur
                            $q->whereDoesntHave('approvals', function($a) {
                                $a->whereIn('status', ['declined', 'rejected']);
                            });
                        })
                        ->with('pengajuan:id,kode_pengajuan')
                        ->first();
                    if ($usedIn) {
                        throw ValidationException::withMessages([
                            'items_json' => 'Faktur ' . ($faktur->no_faktur ?? $fakturId) . ' sudah diajukan di ' . ($usedIn->pengajuan->kode_pengajuan ?? 'pengajuan lain') . '.',
                        ]);
                    }
                    $seenFaktur[$fakturId] = true;
                    // server-side snapshot of the faktur total is authoritative
                    $qty = 1;
                    $price = (float) ($faktur->total ?? $price);
                    $row += ['fakturbeli_id' => $fakturId, 'is_faktur' => true, 'harga_total_snapshot' => $price];
                }
            }

            $row['jumlah'] = $qty;
            $row['harga_satuan'] = $price;
            FinancePengajuanDanaItem::create($row);
            $grandTotal += $qty * $price;
        }

        return $grandTotal;
    }

    /** Store uploaded bukti files and return their paths. */
    private function storeBuktiFiles(Request $request): array
    {
        $paths = [];
        foreach ((array) $request->file('bukti_transaksi', []) as $file) {
            if ($file && $file->isValid()) {
                $paths[] = $file->store('finance/pengajuan', 'public');
            }
        }
        return $paths;
    }

    /** Division of an employee (used when the form does not send one). */
    private function employeeDivisionId($employeeId)
    {
        if (!$employeeId) return null;
        $emp = \App\Models\HRD\Employee::find($employeeId);
        return $emp && !empty($emp->division_id) ? $emp->division_id : null;
    }

    public function index()
    {
        $visibility = $this->getPengajuanVisibilityContext();
        $employee = $visibility['user'] ? $visibility['user']->employee : null;
        return view('finance.pengajuan.index', [
            // admins/approvers may submit on behalf of anyone; others always submit for themselves
            'canChoosePengaju' => $visibility['has_global_access'],
            'currentEmployee' => $employee,
            // user options for the Kelola Approver modal (Admin only)
            'approverUsers' => $visibility['is_admin']
                ? \App\Models\User::orderBy('name')->get(['id', 'name', 'email'])
                : collect(),
        ]);
    }

    // Generate a kode_pengajuan (simple server-side generator)
    public function generateKode()
    {
        // Example format: PJYYYYMMDD0001
        $date = date('Ymd');
        $maxId = FinancePengajuanDana::max('id') ?? 0;
        $next = $maxId + 1;
        $kode = sprintf('PJ%s%04d', $date, $next);
        return response()->json(['kode' => $kode]);
    }

    public function data(Request $request)
    {
        // only the relations the table renders (employee.positions.divisions caused one division query per row)
        $query = FinancePengajuanDana::with(['employee:id,user_id,nama', 'employee.user:id,name', 'approvals.approver.user:id,name', 'rekening', 'items:id,pengajuan_id,nama_item,notes', 'realisasi'])
            ->withCount('approvals')
            ->withSum('payments as total_dibayar', 'nominal');
        $this->scopePengajuanVisibility($query);
        // apply optional date range filter (tanggal_pengajuan)
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if ($startDate || $endDate) {
            try {
                $start = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
                $end = $endDate ? Carbon::parse($endDate)->endOfDay() : null;
                if ($start && $end) {
                    $query->whereBetween('tanggal_pengajuan', [$start, $end]);
                } elseif ($start) {
                    $query->where('tanggal_pengajuan', '>=', $start);
                } elseif ($end) {
                    $query->where('tanggal_pengajuan', '<=', $end);
                }
            } catch (\Exception $e) {
                // if parsing fails, ignore the filter
            }
        }
        // jenis (type) filter: empty or null means show all
        $jenis = $request->input('jenis', null);
        if ($jenis !== null && trim($jenis) !== '') {
            $query->where('jenis_pengajuan', trim($jenis));
        }
        // sumber_dana filter: empty or null means show all
        $sumber = $request->input('sumber_dana', null);
        if ($sumber !== null && trim($sumber) !== '') {
            $query->where('sumber_dana', trim($sumber));
        }
        // Approval status filter: accepted values: 'approved', 'menunggu' (pending), 'declined'
        $approvalStatus = $request->input('approval_status', 'menunggu');
        // build correlated subqueries for level counts and declined checks
        // same rules as approvalSummary(): empty tingkat counts as 1, jenis compared trimmed (global when empty)
        $levelSql = "COALESCE(NULLIF(fda.tingkat, 0), 1)";
        $chainSql = "fda.aktif = 1 AND ( TRIM(COALESCE(fda.jenis, '')) = '' OR TRIM(fda.jenis) = TRIM(finance_pengajuan_dana.sumber_dana) )";
        $totalLevelsSql = "(SELECT COUNT(DISTINCT {$levelSql}) FROM finance_dana_approver fda WHERE {$chainSql})";
        $approvedLevelsSql = "(SELECT COUNT(DISTINCT {$levelSql}) FROM finance_dana_approver fda JOIN finance_pengajuan_dana_approval ap ON ap.approver_id = fda.id AND ap.pengajuan_id = finance_pengajuan_dana.id AND ap.status = 'approved' WHERE {$chainSql})";
        $declinedExistsSql = "(SELECT 1 FROM finance_pengajuan_dana_approval ap2 WHERE ap2.pengajuan_id = finance_pengajuan_dana.id AND (ap2.status = 'declined' OR ap2.status = 'rejected') LIMIT 1)";
        // realisasi to do: needs a realisasi (see requiresRealisasi), and it is missing or not settled yet
        $realisasiSql = $this->requiresRealisasiSql() . " AND NOT EXISTS (SELECT 1 FROM finance_pengajuan_dana_realisasi r WHERE r.pengajuan_id = finance_pengajuan_dana.id AND r.status = 'selesai')";

        // pending (menunggu): not declined and not fully approved
        $pendingSql = "NOT EXISTS {$declinedExistsSql} AND NOT ({$totalLevelsSql} > 0 AND {$approvedLevelsSql} >= {$totalLevelsSql})";
        // fully approved: at least one approver level configured, all levels approved, no decline
        $approvedSql = "{$totalLevelsSql} > 0 AND {$approvedLevelsSql} >= {$totalLevelsSql} AND NOT EXISTS {$declinedExistsSql}";

        // badge counts for the status tabs (same filters except the tab itself), in one query
        $tabCounts = (clone $query)->toBase()
            ->select(DB::raw("SUM(CASE WHEN {$pendingSql} THEN 1 ELSE 0 END) AS menunggu, "
                . "SUM(CASE WHEN {$approvedSql} AND COALESCE(finance_pengajuan_dana.payment_status, '') <> 'paid' THEN 1 ELSE 0 END) AS siap_bayar, "
                . "SUM(CASE WHEN {$realisasiSql} THEN 1 ELSE 0 END) AS realisasi"))
            ->first();

        if ($approvalStatus === 'declined') {
            // only those with at least one declined approval
            $query->whereRaw("EXISTS {$declinedExistsSql}");
        } elseif ($approvalStatus === 'approved') {
            $query->whereRaw($approvedSql);
        } elseif ($approvalStatus === 'realisasi') {
            $query->whereRaw($realisasiSql);
        } else {
            $query->whereRaw($pendingSql);
        }
        // load active approvers once instead of per row
        $activeApprovers = $this->loadActiveApprovers();
        // approval summary per row, shared by the status columns and action buttons (memoized by id)
        $approvalStateCache = [];
        $approvalState = function($row) use ($activeApprovers, &$approvalStateCache) {
            if (!isset($approvalStateCache[$row->id])) {
                $approvalStateCache[$row->id] = $this->approvalSummary($row, $activeApprovers);
            }
            return $approvalStateCache[$row->id];
        };
        $currentUser = Auth::user();
        $currentUserIsApprover = $currentUser && $activeApprovers->contains('user_id', $currentUser->id);
        $currentUserHasGlobalAccess = $this->getPengajuanVisibilityContext()['has_global_access'];

        return DataTables::of($query)
            ->filter(function($q) use ($request) {
                // global search: mirror the visible table content as closely as possible,
                // including detail fields, rekening text, and item-related text.
                $search = $request->input('search.value');
                if ($search && trim($search) !== '') {
                    $s = trim($search);
                    $q->where(function($qq) use ($s) {
                        $qq->where('kode_pengajuan', 'like', "%{$s}%")
                           ->orWhere('jenis_pengajuan', 'like', "%{$s}%")
                           ->orWhere('perusahaan', 'like', "%{$s}%")
                           ->orWhere('sumber_dana', 'like', "%{$s}%")
                           ->orWhereHas('employee', function($qe) use ($s) {
                               $qe->whereHas('user', function($qu) use ($s) {
                                   $qu->where('name', 'like', "%{$s}%");
                               })->orWhere('nama', 'like', "%{$s}%");
                           })
                           ->orWhereHas('division', function($qd) use ($s) {
                               $qd->where('name', 'like', "%{$s}%");
                           })
                           // Search Rekening fields: bank, no_rekening, atas_nama
                           ->orWhereHas('rekening', function($qr) use ($s) {
                               $qr->where('bank', 'like', "%{$s}%")
                                  ->orWhere('no_rekening', 'like', "%{$s}%")
                                  ->orWhere('atas_nama', 'like', "%{$s}%");
                           })
                           ->orWhereHas('items', function($qi) use ($s) {
                               $qi->where('nama_item', 'like', "%{$s}%")
                                  ->orWhere('notes', 'like', "%{$s}%");
                           });
                    });
                }
            })
            ->addColumn('employee_name', function($row) {
                if (!$row->employee) return '';
                return $row->employee->user ? $row->employee->user->name : ($row->employee->nama ?? '');
            })

            ->addColumn('diajukan_ke', function($row) {
                $s = trim($row->sumber_dana ?? '');
                $p = trim($row->perusahaan ?? '');
                $parts = [];
                if ($s !== '') {
                    $parts[] = '<div><strong>' . e($s) . '</strong></div>';
                }
                if ($p !== '') {
                    // text color per perusahaan
                    $lc = strtolower($p);
                    if (strpos($lc, 'belia') !== false) {
                        $color = '#ff69b4'; // pink
                    } elseif (strpos($lc, 'belova') !== false) {
                        $color = '#007bff'; // blue
                    } elseif (strpos($lc, 'grha') !== false) {
                        $color = '#fd7e14'; // orange
                    } else {
                        $color = '#6c757d';
                    }
                    $parts[] = '<div><small style="color:' . $color . ';font-weight:600;">' . e($p) . '</small></div>';
                }
                return implode('', $parts);
            })

            ->addColumn('items_list', function($row) {
                // all item names: plain text for a single item, bullet points when there are several,
                // each with its notes as small text below (prices and bukti are in the Detail modal)
                if ($row->items->isEmpty()) return '<span class="text-muted">-</span>';
                $names = $row->items->map(function($it) {
                    $html = e($it->nama_item ?? ($it->name ?? ''));
                    $notes = trim((string) ($it->notes ?? ''));
                    if ($notes !== '') {
                        $html .= '<div class="item-notes">' . e($notes) . '</div>';
                    }
                    return $html;
                });
                if ($names->count() === 1) {
                    return '<div class="items-summary-single">' . $names->first() . '</div>';
                }
                return '<ul class="items-summary"><li>' . $names->implode('</li><li>') . '</li></ul>';
            })

            ->addColumn('actions', function ($row) use ($approvalState, $activeApprovers, $currentUserIsApprover, $currentUserHasGlobalAccess) {
                $btns = '<div class="btn-group" role="group">';

                // Only the employee who created the pengajuan can edit/delete it
                $user = Auth::user();
                $currentEmployeeId = null;
                if ($user && isset($user->employee) && $user->employee) {
                    $currentEmployeeId = $user->employee->id;
                }
                $isOwner = $currentEmployeeId !== null && $row->employee_id == $currentEmployeeId;

                // Not yet approved / declined -> owner gets Edit + Delete; otherwise Detail only (locked)
                // (Detail = modal with full info, items, prices and bukti photos; PDF is available inside)
                $isPaid = (isset($row->payment_status) && $row->payment_status === 'paid');
                // bukti is viewed/uploaded from the Detail modal (or the Edit form); when missing,
                // flag the main button with a blinking warning badge in its top-right corner
                $noBukti = !$this->pengajuanHasBukti($row);
                $warnBadge = $noBukti
                    ? '<span class="no-bukti-badge" title="Belum lengkap: bukti transaksi / invoice belum diupload">!</span>'
                    : '';
                $warnTitle = $noBukti ? ' (belum ada bukti transaksi)' : '';
                if ($isOwner && !$isPaid && $this->pengajuanIsEditable($row)) {
                    $btns .= '<button type="button" class="btn btn-sm btn-primary edit-pengajuan has-warn-badge" data-id="' . $row->id . '" title="Edit' . $warnTitle . '"><i class="fa fa-edit mr-1"></i>Edit' . $warnBadge . '</button>';
                    $btns .= '<button class="btn btn-sm btn-danger delete-pengajuan" data-id="' . $row->id . '" title="Hapus"><i class="fa fa-trash mr-1"></i>Hapus</button>';
                } else {
                    $btns .= '<button type="button" class="btn btn-sm btn-info show-detail has-warn-badge" data-id="' . $row->id . '" title="Lihat Detail' . $warnTitle . '"><i class="fa fa-eye mr-1"></i>Detail' . $warnBadge . '</button>';
                }

                // Approve / Tolak: only when one of the user's active approver rows has its turn (see approverTurnCheck)
                $state = $approvalState($row)['state'];
                if ($user && $currentUserIsApprover && $state === 'pending') {
                    [$actingApprover] = $this->resolveActingApprover($row, $user, $activeApprovers, $row->approvals);
                    if ($actingApprover) {
                        $btns .= '<button class="btn btn-sm btn-success approve-pengajuan ms-1" data-id="' . $row->id . '" title="Approve"><i class="fa fa-check mr-1"></i>Approve</button>';
                        $btns .= '<button class="btn btn-sm btn-danger decline-pengajuan ms-1" data-id="' . $row->id . '" title="Tolak"><i class="fa fa-times mr-1"></i>Tolak</button>';
                    }
                }

                // Bayar: fully approved, not (fully) paid yet, and the user is an active approver
                if ($state === 'approved' && !$isPaid && $currentUserIsApprover) {
                    $payLabel = $row->payment_status === 'partial' ? 'Bayar Sisa' : 'Bayar';
                    $btns .= '<button class="btn btn-sm btn-success pay-pengajuan ms-1" data-id="' . $row->id . '" title="' . $payLabel . '"><i class="fa fa-wallet mr-1"></i>' . $payLabel . '</button>';
                }

                // Realisasi: once paid, the pengaju (or admin/approver) reports what was actually spent
                $realisasi = $row->realisasi;
                if ($this->requiresRealisasi($row) && ($isOwner || $currentUserHasGlobalAccess) && (!$realisasi || $realisasi->status !== 'selesai')) {
                    $realLabel = $realisasi ? 'Edit Realisasi' : 'Realisasi';
                    $btns .= '<button class="btn btn-sm btn-warning realisasi-pengajuan ms-1" data-id="' . $row->id . '" title="' . $realLabel . '"><i class="fa fa-receipt mr-1"></i>' . $realLabel . '</button>';
                }
                // Konfirmasi: a realisasi with a difference (money returned / overspent) is checked by an approver
                if ($realisasi && $realisasi->status === 'menunggu_konfirmasi' && $currentUserIsApprover) {
                    $btns .= '<button class="btn btn-sm btn-success confirm-realisasi ms-1" data-id="' . $row->id . '" title="Konfirmasi Realisasi"><i class="fa fa-check-double mr-1"></i>Konfirmasi</button>';
                }
                $btns .= '</div>';
                return $btns;
            })
            ->addColumn('approvals_list', function($row) use ($approvalState) {
                // Human-readable approval progress: status line, one dot per level, and who it waits on / who declined.
                $st = $approvalState($row);
                $steps = $st['steps'];

                if ($st['state'] === 'declined') {
                    $color = '#dc3545';
                    $label = '<i class="fa fa-times-circle"></i> Ditolak';
                    $info = $st['declined_by'] !== '' ? 'oleh ' . e($st['declined_by']) : '';
                    if ($st['decline_note'] !== '') {
                        // short reason in the cell; full text in tooltip and approval list modal
                        $info .= ($info !== '' ? '<br>' : '') . '<span class="decline-note" title="' . e($st['decline_note']) . '">Alasan: '
                            . e(\Illuminate\Support\Str::limit($st['decline_note'], 60)) . '</span>';
                    }
                } elseif ($st['state'] === 'approved') {
                    $color = '#28a745';
                    $label = '<i class="fa fa-check-circle"></i> Disetujui';
                    $info = 'Semua tingkat sudah setuju';
                } elseif ($st['state'] === 'pending') {
                    $color = '#d39e00';
                    $label = '<i class="fa fa-clock"></i> Menunggu';
                    // progress is shown by the dots; the text only says who it is waiting for
                    $info = $st['waiting_for'] !== '' ? 'Menunggu: ' . e($st['waiting_for']) : '';
                } else {
                    $color = '#6c757d';
                    $label = '<i class="fa fa-exclamation-circle"></i> Approver belum diatur';
                    $info = '';
                }

                $dots = '';
                if (count($steps) > 1) {
                    $dotColors = ['approved' => '#28a745', 'declined' => '#dc3545', 'pending' => '#ced4da'];
                    foreach ($steps as $s) {
                        $dots .= '<span class="approval-dot" style="background:' . $dotColors[$s] . ';"></span>';
                    }
                    $dots = '<div class="approval-dots">' . $dots . '</div>';
                }

                // clickable block opens modal showing full approval list
                return '<div class="approval-status show-approvals" data-id="' . $row->id . '" role="button" title="Klik untuk lihat detail persetujuan">'
                    // label left, progress dots in the right corner
                    . '<div class="approval-head"><div class="approval-label" style="color:' . $color . ';">' . $label . '</div>' . $dots . '</div>'
                    . ($info !== '' ? '<div class="approval-info">' . $info . '</div>' : '')
                    . '</div>';
            })
            ->addColumn('payment_status_display', function($row) use ($approvalState) {
                // Human-readable payment status, explaining why an unpaid pengajuan is not paid yet
                $isPaid = (isset($row->payment_status) && $row->payment_status === 'paid');
                $dibayar = (float) ($row->total_dibayar ?? 0);
                $grand = (float) $row->grand_total;
                $extra = ''; // already-escaped HTML (paid amount when closed with a difference)
                if ($isPaid) {
                    $color = '#28a745';
                    $label = '<i class="fa fa-check-circle"></i> Sudah Dibayar';
                    $info = '';
                    if (!empty($row->payment_date)) {
                        try {
                            // Format like: 21 Januari 2026 09.00 (Indonesian month, dot as time separator)
                            $info = Carbon::parse($row->payment_date)->locale('id')->translatedFormat('j F Y H.i');
                        } catch (\Exception $e) {
                            $info = (string) $row->payment_date;
                        }
                    }
                    // closed with a smaller amount than requested
                    if ($dibayar > 0 && $dibayar < $grand) {
                        $extra .= '<div class="approval-info text-danger">Dibayar ' . e($this->rupiah($dibayar)) . ' dari ' . e($this->rupiah($grand)) . '</div>';
                    }
                    // old / Inkaso pengajuan without a realisasi: nothing to show
                    if ($row->realisasi || $this->requiresRealisasi($row)) {
                        $extra .= $this->realisasiStatusHtml($row->realisasi);
                    }
                } elseif ($row->payment_status === 'partial') {
                    $color = '#fd7e14';
                    $label = '<i class="fa fa-adjust"></i> Dibayar Sebagian';
                    $info = 'Dibayar ' . $this->rupiah($dibayar) . ', sisa ' . $this->rupiah(max(0, $grand - $dibayar));
                } else {
                    $state = $approvalState($row)['state'];
                    if ($state === 'approved') {
                        $color = '#007bff';
                        $label = '<i class="fa fa-wallet"></i> Siap Dibayar';
                        $info = 'Sudah disetujui, menunggu pembayaran';
                    } elseif ($state === 'declined') {
                        $color = '#6c757d';
                        $label = '<i class="fa fa-ban"></i> Tidak Dibayar';
                        $info = 'Pengajuan ditolak';
                    } else {
                        $color = '#6c757d';
                        $label = '<i class="fa fa-hourglass-half"></i> Belum Dibayar';
                        $info = 'Menunggu persetujuan selesai';
                    }
                }
                return '<div class="approval-label" style="color:' . $color . ';">' . $label . '</div>'
                    . ($info !== '' ? '<div class="approval-info">' . e($info) . '</div>' : '')
                    . $extra;
            })
            ->addColumn('rekening_display', function($row) {
                $rek = $row->rekening;
                if (!$rek) return '<span class="text-muted">-</span>';
                $html = '';
                $main = implode(' - ', array_filter([trim($rek->bank ?? ''), trim($rek->no_rekening ?? '')], 'strlen'));
                if ($main !== '') $html .= '<div class="cell-main">' . e($main) . '</div>';
                if (!empty($rek->atas_nama)) $html .= '<div><small class="text-muted">' . e($rek->atas_nama) . '</small></div>';
                return $html ?: '<span class="text-muted">-</span>';
            })
            // HTML columns, mark them as raw so they are not escaped
            ->rawColumns(['actions', 'approvals_list', 'payment_status_display', 'rekening_display', 'diajukan_ke', 'items_list'])
            ->with('tab_counts', [
                'menunggu' => (int) ($tabCounts->menunggu ?? 0),
                'siap_bayar' => (int) ($tabCounts->siap_bayar ?? 0),
                'realisasi' => (int) ($tabCounts->realisasi ?? 0),
            ])
            // send only what the table renders (raw model fields + relations were ~3KB per row)
            ->only(['id', 'kode_pengajuan', 'jenis_pengajuan', 'tanggal_pengajuan', 'grand_total', 'total_dibayar', 'payment_status', 'employee_name',
                'items_list', 'rekening_display', 'diajukan_ke', 'approvals_list', 'payment_status_display', 'actions'])
            ->make(true);
    }

    /**
     * AJAX: DataTable for paid pengajuan (riwayat pembayaran)
     */
    public function paidData(Request $request)
    {
        // one row per transfer (a pengajuan paid in parts shows several rows)
        $query = FinancePengajuanDanaPayment::with(['pengajuan.items', 'pengajuan.rekening', 'paidBy:id,name'])
            ->whereHas('pengajuan', function($q) { $this->scopePengajuanVisibility($q); });

        // optional date range filter: expect start_date and end_date in request (format: 'DD MMMM YYYY' or 'YYYY-MM-DD')
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if ($startDate || $endDate) {
            try {
                if ($startDate) $sd = Carbon::parse($startDate)->startOfDay();
                if ($endDate) $ed = Carbon::parse($endDate)->endOfDay();
                if (isset($sd) && isset($ed)) {
                    $query->whereBetween('tanggal_bayar', [$sd, $ed]);
                } elseif (isset($sd)) {
                    $query->where('tanggal_bayar', '>=', $sd);
                } elseif (isset($ed)) {
                    $query->where('tanggal_bayar', '<=', $ed);
                }
            } catch (\Exception $e) {
                // ignore parse errors and return unfiltered
            }
        }

        return DataTables::of($query)
            ->addColumn('kode_pengajuan', function($row){ return $row->pengajuan->kode_pengajuan ?? ''; })
            ->addColumn('items_list', function($row){
                try {
                    $items = $row->pengajuan->items ?? [];
                    if (empty($items)) return '';
                    $lines = [];
                    foreach ($items as $it) {
                        // field names in finance_pengajuan_dana_item: nama_item, jumlah, harga_satuan, harga_total_snapshot
                        $desc = trim($it->nama_item ?? $it->nama ?? '');
                        $qty = (isset($it->jumlah) ? $it->jumlah : '');
                        // prefer total snapshot if available, otherwise harga_satuan
                        $rawPrice = $it->harga_total_snapshot ?? $it->harga_satuan ?? null;
                        $price = is_null($rawPrice) ? '' : number_format($rawPrice, 2, ',', '.');
                        $part = e($desc);
                        if ($qty !== '') $part .= ' (' . e($qty) . ')';
                        if ($price !== '') $part .= ' - Rp ' . $price;
                        $lines[] = '<li>' . $part . '</li>';
                    }
                    return '<ul class="mb-0">' . implode('', $lines) . '</ul>';
                } catch (\Exception $e) {
                    return '';
                }
            })
            ->addColumn('rekening', function($row){
                try {
                    $rek = $row->pengajuan->rekening ?? null;
                    if (!$rek) return '';
                    $rekText = '';
                    if (!empty($rek->bank)) $rekText .= $rek->bank;
                    if (!empty($rek->no_rekening)) $rekText .= ($rekText ? ' / ' : '') . $rek->no_rekening;
                    if (!empty($rek->atas_nama)) $rekText .= ($rekText ? ' / ' : '') . $rek->atas_nama;
                    if ($rekText) return '<div class="rekening-badge">' . e($rekText) . '</div>';
                    return '';
                } catch (\Exception $e) { return ''; }
            })
            ->addColumn('payment_date', function($row){
                return $row->tanggal_bayar ? $row->tanggal_bayar->locale('id')->translatedFormat('j F Y H.i') : '';
            })
            ->addColumn('nominal_display', function($row){
                $html = '<div class="text-nowrap font-weight-bold">' . e($this->rupiah($row->nominal)) . '</div>';
                $grand = (float) ($row->pengajuan->grand_total ?? 0);
                if ($grand > 0 && abs($grand - (float) $row->nominal) >= 0.01) {
                    $html .= '<div><small class="text-muted">dari ' . e($this->rupiah($grand)) . '</small></div>';
                }
                if (trim((string) $row->note) !== '') {
                    $html .= '<div><small class="text-muted">' . e($row->note) . '</small></div>';
                }
                if ($row->bukti) {
                    $html .= '<a href="' . e(asset('storage/' . ltrim($row->bukti, '/'))) . '" target="_blank"><small><i class="fa fa-paperclip"></i> Bukti</small></a>';
                }
                return $html;
            })
            ->addColumn('paid_by_name', function($row){ return $row->paidBy->name ?? ''; })
            ->rawColumns(['items_list','rekening','nominal_display'])
            ->make(true);
    }

    // Approve an individual pengajuan (called by approver)
    public function approve(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        [$ok, $msg] = $this->recordApprovalAction((int) $id, $user, 'approved');
        if (!$ok) {
            return response()->json(['success' => false, 'message' => $msg], 403);
        }
        return response()->json(['success' => true, 'message' => $msg]);
    }

    /**
     * Decline pengajuan (same turn rules as approve). Stops the approval flow.
     */
    public function decline(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $validated = $request->validate([
            'note' => 'required|string|max:1000',
        ], [
            'note.required' => 'Alasan penolakan wajib diisi.',
            'note.max' => 'Alasan penolakan maksimal 1000 karakter.',
        ]);
        [$ok, $msg] = $this->recordApprovalAction((int) $id, $user, 'declined', trim($validated['note']));
        if (!$ok) {
            return response()->json(['success' => false, 'message' => $msg], 403);
        }
        return response()->json(['success' => true, 'message' => $msg]);
    }

    /**
     * Upload bukti_transaksi files for an existing pengajuan (available to all users)
     */
    public function uploadBukti(Request $request, $id)
    {
        $this->authorizePengajuanAccess(FinancePengajuanDana::findOrFail($id));

        $request->validate([
            'bukti_transaksi' => 'required|array|max:10',
            'bukti_transaksi.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'bukti_transaksi.required' => 'Pilih minimal 1 file bukti',
            'bukti_transaksi.*.image' => 'Bukti harus berupa gambar',
            'bukti_transaksi.*.max' => 'Ukuran bukti maksimal 2MB per file',
        ]);

        $paths = $this->storeBuktiFiles($request);

        // read-merge-write under a row lock: two uploads at the same moment both keep their files
        $merged = DB::transaction(function() use ($id, $paths) {
            $pengajuan = FinancePengajuanDana::whereKey($id)->lockForUpdate()->firstOrFail();
            $merged = array_values(array_unique(array_merge($this->buktiPaths($pengajuan), $paths)));
            $pengajuan->bukti_transaksi = $merged; // array cast encodes once
            $pengajuan->save();
            return $merged;
        });

        return response()->json(['success' => true, 'message' => 'Bukti transaksi berhasil diupload', 'paths' => $merged]);
    }

    public function store(Request $request)
    {
        $visibility = $this->getPengajuanVisibilityContext();
        if (!$visibility['has_global_access']) {
            if (!$visibility['employee_id']) {
                return response()->json([
                    'message' => 'Akun Anda belum terhubung dengan data karyawan (HRD). Hubungi HRD/Admin untuk menghubungkan akun ini ke data karyawan sebelum membuat pengajuan dana.',
                ], 403);
            }
            // non-admin/approver users always submit for their own employee record
            $request->merge(['employee_id' => $visibility['employee_id']]);
        }

        $validator = $this->pengajuanValidator($request);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }
        $data = Arr::only($validator->validated(), self::FORM_FIELDS);
        if (empty($data['division_id'])) {
            $data['division_id'] = $this->employeeDivisionId($data['employee_id']);
        }
        $items = $this->parseItems($request->input('items_json'));

        // one-time token per opened form: a double click or a retried request cannot create a duplicate
        $token = trim((string) $request->input('submit_token', ''));
        $tokenKey = $token !== '' ? 'pengajuan_submit:' . sha1(Auth::id() . '|' . $token) : null;
        if ($tokenKey && !Cache::add($tokenKey, 1, now()->addMinutes(30))) {
            return response()->json(['message' => 'Pengajuan ini sudah terkirim. Silakan muat ulang tabel.'], 409);
        }

        $paths = $this->storeBuktiFiles($request);

        try {
            $pengajuan = DB::transaction(function() use ($data, $items, $paths) {
                // temporary unique code; replaced below by the final number derived from the row id
                $data['kode_pengajuan'] = 'TMP-' . Str::uuid();
                $data['status'] = 'draft';
                if (!empty($paths)) {
                    $data['bukti_transaksi'] = $paths;
                }
                $pengajuan = FinancePengajuanDana::create($data);

                // the number comes from the auto-increment id, so it is unique even when many
                // people submit at the same time (the old "max(id)+1 when the modal opens" could collide)
                $pengajuan->kode_pengajuan = sprintf('PJ%s%04d', now()->format('Ymd'), $pengajuan->id);
                $pengajuan->grand_total = $this->saveItems($pengajuan, $items);
                $pengajuan->save();

                return $pengajuan;
            });
        } catch (ValidationException $e) {
            Storage::disk('public')->delete($paths);
            if ($tokenKey) Cache::forget($tokenKey); // let the user fix and resubmit the same form
            return response()->json(['message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($paths);
            if ($tokenKey) Cache::forget($tokenKey);
            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan ' . $pengajuan->kode_pengajuan . ' berhasil disimpan',
            'data' => FinancePengajuanDana::with('items')->find($pengajuan->id),
        ]);
    }

    public function show($id)
    {
        $pengajuan = FinancePengajuanDana::with(['items', 'approvals', 'employee.user', 'division', 'rekening',
            'payments.paidBy:id,name', 'realisasi.submittedBy:id,name', 'realisasi.confirmedBy:id,name'])->findOrFail($id);
        $this->authorizePengajuanAccess($pengajuan);
        $data = $pengajuan->toArray();
        $data['total_dibayar'] = (float) $pengajuan->payments->sum('nominal');
        $data['sisa_bayar'] = max(0, (float) $pengajuan->grand_total - $data['total_dibayar']);
        $data['realisasi_required'] = $this->requiresRealisasi($pengajuan);
        // normalized for the frontend: plain Y-m-d for <input type="date">, bukti always an array of paths
        $data['tanggal_pengajuan'] = $pengajuan->tanggal_pengajuan ? $pengajuan->tanggal_pengajuan->format('Y-m-d') : null;
        $data['bukti_transaksi'] = $this->buktiPaths($pengajuan);
        return response()->json($data);
    }

    public function downloadBukti($id, $index)
    {
        $pengajuan = FinancePengajuanDana::findOrFail($id);
        $this->authorizePengajuanAccess($pengajuan);

        $buktiList = $this->buktiPaths($pengajuan);
        $index = (int) $index;

        if (!isset($buktiList[$index])) {
            abort(404, 'Bukti transaksi tidak ditemukan.');
        }

        $path = ltrim($buktiList[$index], '/');
        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'File bukti transaksi tidak ditemukan.');
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = 'bukti-transaksi-' . ($pengajuan->kode_pengajuan ?: $pengajuan->id) . '-' . ($index + 1);
        if ($extension) {
            $filename .= '.' . $extension;
        }

        return response()->download(Storage::disk('public')->path($path), $filename);
    }

    /**
     * Generate PDF view for a pengajuan dana
     */
    public function pdf($id)
    {
        $pengajuan = FinancePengajuanDana::with(['items', 'approvals.approver.user', 'employee.user', 'division', 'rekening'])->findOrFail($id);
        $this->authorizePengajuanAccess($pengajuan);
        // collect linked faktur IDs from items
        $fakturIds = collect($pengajuan->items)->pluck('fakturbeli_id')->filter()->unique()->values()->all();
        $fakturs = [];
        if (!empty($fakturIds)) {
            $fakturs = FakturBeli::with(['items.obat', 'items.gudang', 'pemasok'])->whereIn('id', $fakturIds)->get();
            foreach ($fakturs as $faktur) {
                $faktur->pdf_bukti_image_data = null;
                $buktiPath = $faktur->bukti ?? null;
                if (empty($buktiPath)) {
                    continue;
                }

                try {
                    $publicStoragePath = public_path('storage/' . ltrim($buktiPath, '/'));
                    if (!file_exists($publicStoragePath)) {
                        continue;
                    }

                    $mimeType = @mime_content_type($publicStoragePath);
                    if (!$mimeType || stripos($mimeType, 'image/') !== 0) {
                        continue;
                    }

                    $imageData = @file_get_contents($publicStoragePath);
                    if ($imageData === false || $imageData === '') {
                        continue;
                    }

                    $faktur->pdf_bukti_image_data = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                } catch (\Exception $e) {
                    $faktur->pdf_bukti_image_data = null;
                }
            }
        }
        // locate a logo asset if present
        $logoCandidates = [
            'img/logo-belovacorp.png',
            'img/logo-belova-klinik.png',
            'img/logo-belovaskin.png',
            'img/logo-premiere.png',
            'img/logo-belovacorp-bw.png'
        ];
        $logoPath = null;
        foreach ($logoCandidates as $c) {
            $p = public_path($c);
            if ($p && file_exists($p)) { $logoPath = $p; break; }
        }
        // build signature QR codes for approvals and creator (tanda tangan)
        $signatures = [];
        try {
            // creator / pembuat - determine a robust name fallback
            $creatorUser = ($pengajuan->employee && $pengajuan->employee->user) ? $pengajuan->employee->user : $pengajuan->employee;
            $creatorName = $creatorUser ? ($creatorUser->name ?? $creatorUser->nama ?? '') : '';
            $creatorDate = $pengajuan->tanggal_pengajuan ? Carbon::parse($pengajuan->tanggal_pengajuan)->format('d M Y') : '';
            $creatorQr = null;
            if (!empty($creatorName)) {
                try {
                    $png = QrCode::format('png')->size(160)->generate($creatorName);
                    if (!empty($png)) {
                        $creatorQr = 'data:image/png;base64,' . base64_encode($png);
                    }
                } catch (\Exception $e) {
                    // fallback to svg if png generation fails
                    try {
                        $svg = QrCode::format('svg')->size(160)->generate($creatorName);
                        if (!empty($svg)) {
                            $creatorQr = 'data:image/svg+xml;base64,' . base64_encode($svg);
                        }
                    } catch (\Exception $e) {
                        // ignore, leave qr null
                    }
                }
            }
            // try to get creator jabatan/position
            $creatorJabatan = '';
            if ($pengajuan->employee) {
                if (isset($pengajuan->employee->position) && !empty($pengajuan->employee->position->name)) {
                    $creatorJabatan = $pengajuan->employee->position->name;
                } elseif (!empty($pengajuan->employee->jabatan)) {
                    $creatorJabatan = $pengajuan->employee->jabatan;
                } elseif (!empty($creatorUser->jabatan)) {
                    $creatorJabatan = $creatorUser->jabatan;
                }
            }

            $signatures[] = [
                'label' => 'Dibuat oleh',
                'name' => $creatorName,
                'jabatan' => $creatorJabatan,
                'date' => $creatorDate,
                'qr' => $creatorQr,
            ];

            // approvals
            foreach ($pengajuan->approvals as $ap) {
                $approverName = '';
                if ($ap->approver && $ap->approver->user) {
                    $approverName = $ap->approver->user->name;
                }
                $approveDate = $ap->tanggal_approve ? Carbon::parse($ap->tanggal_approve)->format('d M Y') : '';
                $qr = null;
                if (!empty($approverName)) {
                    try {
                        $png = QrCode::format('png')->size(160)->generate($approverName);
                        if (!empty($png)) {
                            $qr = 'data:image/png;base64,' . base64_encode($png);
                        }
                    } catch (\Exception $e) {
                        // fallback svg
                        try {
                            $svg = QrCode::format('svg')->size(160)->generate($approverName);
                            if (!empty($svg)) {
                                $qr = 'data:image/svg+xml;base64,' . base64_encode($svg);
                            }
                        } catch (\Exception $e) {
                            // ignore
                        }
                    }
                }
                // approver jabatan (from FinanceDanaApprover.jabatan)
                $approverJabatan = '';
                if ($ap->approver) {
                    $approverJabatan = $ap->approver->jabatan ?? '';
                }
                $signatures[] = [
                    'label' => 'Disetujui',
                    'name' => $approverName,
                    'jabatan' => $approverJabatan,
                    'date' => $approveDate,
                    'qr' => $qr,
                ];
            }
        } catch (\Exception $e) {
            // QR generation failed for some reason; proceed without QR images
        }

        // load blade and render to PDF
        try {
            $pdf = PDF::loadView('finance.pengajuan.pdf', compact('pengajuan', 'fakturs', 'logoPath', 'signatures'));
            // Render the PDF in landscape orientation to better fit wide item tables
            $pdf->setPaper('a4', 'landscape');
            return $pdf->stream('pengajuan_' . $pengajuan->id . '.pdf');
        } catch (\Exception $e) {
            // fallback to HTML view if PDF generation fails
            return view('finance.pengajuan.pdf', compact('pengajuan', 'fakturs', 'logoPath', 'signatures'));
        }
    }

    public function update(Request $request, $id)
    {
        $visibility = $this->getPengajuanVisibilityContext();
        if (!$visibility['has_global_access']) {
            // non-admin/approver users always submit for their own employee record
            $request->merge(['employee_id' => $visibility['employee_id']]);
        }

        $validator = $this->pengajuanValidator($request);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }
        $data = Arr::only($validator->validated(), self::FORM_FIELDS);
        if (empty($data['division_id'])) {
            $data['division_id'] = $this->employeeDivisionId($data['employee_id']);
        }
        $items = $this->parseItems($request->input('items_json'));
        $newPaths = $this->storeBuktiFiles($request);

        try {
            $result = DB::transaction(function() use ($id, $data, $items, $newPaths) {
                // row lock: an approval or another edit of this pengajuan waits until we are done
                $pengajuan = FinancePengajuanDana::whereKey($id)->lockForUpdate()->firstOrFail();
                $this->authorizePengajuanAccess($pengajuan);

                // editable only while nobody has approved yet, or after it was declined (resubmission)
                if ($pengajuan->payment_status === 'paid' || !$this->pengajuanIsEditable($pengajuan)) {
                    return [null, 'Pengajuan sudah disetujui dan tidak dapat diedit.'];
                }

                // resubmitting a declined pengajuan: clear all approvals so approval restarts from the first level
                if ($this->pengajuanIsDeclined($pengajuan)) {
                    $pengajuan->approvals()->delete();
                    $data['status'] = 'draft';
                }

                // new bukti files replace the old ones
                $oldPaths = [];
                if (!empty($newPaths)) {
                    $oldPaths = $this->buktiPaths($pengajuan);
                    $data['bukti_transaksi'] = $newPaths;
                }

                $pengajuan->fill($data);
                $pengajuan->grand_total = $this->saveItems($pengajuan, $items);
                $pengajuan->save();

                return [$pengajuan, $oldPaths];
            });
        } catch (ValidationException $e) {
            Storage::disk('public')->delete($newPaths);
            return response()->json(['message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($newPaths);
            throw $e;
        }

        [$pengajuan, $extra] = $result;
        if (!$pengajuan) {
            Storage::disk('public')->delete($newPaths);
            return response()->json(['success' => false, 'message' => $extra], 403);
        }
        // remove replaced bukti files only after the update committed
        if (!empty($extra)) {
            Storage::disk('public')->delete($extra);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan ' . $pengajuan->kode_pengajuan . ' berhasil diperbarui',
            'data' => FinancePengajuanDana::with('items')->find($pengajuan->id),
        ]);
    }

    public function destroy($id)
    {
        return DB::transaction(function() use ($id) {
            $pengajuan = FinancePengajuanDana::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->authorizePengajuanAccess($pengajuan);

            // same lock as edit: deletable only before any approval, or after it was declined; never once paid
            if ($pengajuan->payment_status === 'paid' || !$this->pengajuanIsEditable($pengajuan)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan sudah disetujui/dibayar dan tidak dapat dihapus.',
                ], 403);
            }

            $pengajuan->delete();
            return response()->json(['success' => true]);
        });
    }

    /**
     * Return approvals details for a pengajuan (for modal display)
     */
    public function approvalsDetails($id)
    {
        $pengajuan = FinancePengajuanDana::with('approvals')->findOrFail($id);
        $this->authorizePengajuanAccess($pengajuan);

        // applicable approvers, in approval order (highest tingkat first)
        $chain = $this->approverChainFor($pengajuan->sumber_dana, $this->loadActiveApprovers())
            ->sortByDesc(function($a) { return $this->approverLevel($a); })
            ->values();
        $existingApprovals = $pengajuan->approvals->keyBy('approver_id');
        $approvedLevels = $chain->filter(function($a) use ($existingApprovals) {
            $ap = $existingApprovals->get($a->id);
            return $ap && $ap->status === 'approved';
        })->map(function($a) { return $this->approverLevel($a); })->unique()->all();

        $list = [];
        foreach ($chain as $app) {
            $level = $this->approverLevel($app);
            $status = 'waiting';
            $date = '';
            $ap = $existingApprovals->get($app->id);
            if ($ap) {
                $status = $this->isDeclinedApproval($ap) ? 'declined' : ($ap->status === 'approved' ? 'approved' : 'waiting');
                $date = $ap->tanggal_approve ? Carbon::parse($ap->tanggal_approve)->format('d M Y H:i') : '';
            } elseif (in_array($level, $approvedLevels, true)) {
                // another approver at the same tingkat already approved; this one no longer needs to act
                $status = 'skipped';
            }
            $list[] = [
                'approver_id' => $app->id,
                'name' => $this->approverDisplayName($app),
                'jabatan' => $app->jabatan ?? '',
                'tingkat' => $level,
                'status' => $status,
                'date' => $date,
                'note' => $ap ? trim((string) ($ap->note ?? '')) : '',
            ];
        }

        return response()->json(['success' => true, 'data' => $list]);
    }

    /**
     * Record a payment (transfer) for a pengajuan.
     * Body: nominal, tanggal_bayar, note, bukti (file), mode.
     * - nominal = remaining amount -> payment_status 'paid'
     * - nominal < remaining: mode 'partial' -> 'partial' (rest is paid later with another payment),
     *   mode 'close' -> 'paid' with a difference (the rest is not paid); note is required in both cases
     * Paying more than the approved remaining amount is refused (that needs a revised pengajuan).
     */
    public function markPaid(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $activeApprovers = $this->loadActiveApprovers();
        // Only users registered as active approver can pay
        if (!$activeApprovers->contains('user_id', $user->id)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'nominal' => 'required|numeric|min:1',
            'tanggal_bayar' => 'nullable|date',
            'note' => 'nullable|string|max:1000',
            'mode' => 'nullable|in:partial,close',
            'bukti' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:4096',
        ], [
            'nominal.required' => 'Nominal dibayar wajib diisi',
            'nominal.min' => 'Nominal dibayar harus lebih dari 0',
            'bukti.mimes' => 'Bukti harus berupa gambar (jpg/png) atau PDF',
            'bukti.max' => 'Ukuran bukti maksimal 4MB',
        ]);

        $buktiPath = $request->hasFile('bukti')
            ? ($this->storeFinanceFiles([$request->file('bukti')], 'finance/pengajuan/pembayaran')[0] ?? null)
            : null;

        $response = DB::transaction(function() use ($id, $activeApprovers, $validated, $buktiPath, $user) {
            $pengajuan = FinancePengajuanDana::whereKey($id)->lockForUpdate()->firstOrFail();

            // Only allow payment if fully approved and not declined
            $summary = $this->approvalSummary($pengajuan, $activeApprovers, $pengajuan->approvals()->get());
            if ($summary['state'] !== 'approved') {
                return response()->json(['success' => false, 'message' => 'Pengajuan belum approved lengkap'], 422);
            }
            if ($pengajuan->payment_status === 'paid') {
                return response()->json(['success' => false, 'message' => 'Pengajuan sudah dibayar'], 422);
            }

            $sudahDibayar = (float) $pengajuan->payments()->sum('nominal');
            $sisa = round((float) $pengajuan->grand_total - $sudahDibayar, 2);
            $nominal = round((float) $validated['nominal'], 2);
            if ($nominal - $sisa > 0.009) {
                return response()->json(['success' => false, 'message' => 'Nominal melebihi sisa yang disetujui (' . $this->rupiah($sisa) . '). Untuk menambah dana, revisi pengajuan dan ajukan ulang.'], 422);
            }

            $isFull = ($sisa - $nominal) < 0.01;
            $mode = $validated['mode'] ?? null;
            $note = trim((string) ($validated['note'] ?? ''));
            if (!$isFull) {
                if (!$mode) {
                    return response()->json(['success' => false, 'message' => 'Nominal kurang dari sisa: pilih apakah sisa dibayar menyusul atau pembayaran diselesaikan.'], 422);
                }
                if ($note === '') {
                    return response()->json(['success' => false, 'message' => 'Catatan wajib diisi jika nominal dibayar kurang dari yang diajukan.'], 422);
                }
            }

            $tanggal = !empty($validated['tanggal_bayar']) ? Carbon::parse($validated['tanggal_bayar']) : Carbon::now();
            FinancePengajuanDanaPayment::create([
                'pengajuan_id' => $pengajuan->id,
                'nominal' => $nominal,
                'tanggal_bayar' => $tanggal,
                'bukti' => $buktiPath,
                'note' => $note !== '' ? $note : null,
                'paid_by' => $user->id,
            ]);

            $closed = $isFull || $mode === 'close';
            $pengajuan->payment_status = $closed ? 'paid' : 'partial';
            $pengajuan->payment_date = $tanggal; // date of the latest payment
            $pengajuan->save();

            if ($isFull) {
                $message = 'Pembayaran lunas dicatat';
            } elseif ($closed) {
                $message = 'Pembayaran dicatat dan diselesaikan dengan selisih ' . $this->rupiah($sisa - $nominal);
            } else {
                $message = 'Pembayaran sebagian dicatat, sisa ' . $this->rupiah($sisa - $nominal);
            }
            return response()->json(['success' => true, 'message' => $message]);
        });

        // refused inside the transaction: the uploaded bukti is not referenced anywhere
        if ($buktiPath && $response->getStatusCode() !== 200) {
            Storage::disk('public')->delete($buktiPath);
        }
        return $response;
    }

    /** Realisasi line under the payment status (paid pengajuan only). */
    private function realisasiStatusHtml($realisasi): string
    {
        if (!$realisasi) {
            return '<div class="approval-info" style="color:#d39e00;"><i class="fa fa-receipt"></i> Belum realisasi</div>';
        }
        if ($realisasi->status === 'selesai') {
            return '<div class="approval-info" style="color:#28a745;"><i class="fa fa-receipt"></i> Realisasi selesai</div>';
        }
        $sisa = (float) $realisasi->sisa;
        $diff = $sisa > 0 ? 'dikembalikan ' . $this->rupiah($sisa) : 'kurang ' . $this->rupiah(abs($sisa));
        return '<div class="approval-info" style="color:#fd7e14;"><i class="fa fa-receipt"></i> Realisasi menunggu konfirmasi (' . e($diff) . ')</div>';
    }

    /**
     * Submit (or revise) the realisasi of a paid pengajuan: actual amount spent per item, nota files,
     * and, when money is left over, the bukti pengembalian.
     * Body: items_json [{id, realisasi}], note, nota[] (files), bukti_pengembalian (file).
     * sisa = total dibayar - total realisasi. No difference -> 'selesai';
     * a difference -> 'menunggu_konfirmasi' until an approver confirms it.
     */
    public function submitRealisasi(Request $request, $id)
    {
        $validated = $request->validate([
            'items_json' => 'required|json',
            'note' => 'nullable|string|max:1000',
            'nota' => 'nullable|array|max:10',
            'nota.*' => 'file|mimes:jpeg,png,jpg,pdf|max:4096',
            'bukti_pengembalian' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:4096',
        ], [
            'items_json.required' => 'Isi realisasi per item',
            'nota.max' => 'Maksimal 10 file nota',
            'nota.*.mimes' => 'Nota harus berupa gambar (jpg/png) atau PDF',
            'nota.*.max' => 'Ukuran nota maksimal 4MB per file',
            'bukti_pengembalian.mimes' => 'Bukti pengembalian harus berupa gambar (jpg/png) atau PDF',
            'bukti_pengembalian.max' => 'Ukuran bukti pengembalian maksimal 4MB',
        ]);

        $amounts = [];
        foreach ((array) json_decode($validated['items_json'], true) as $row) {
            if (!is_array($row) || empty($row['id'])) continue;
            $amount = (float) ($row['realisasi'] ?? 0);
            if ($amount < 0) {
                return response()->json(['success' => false, 'message' => 'Realisasi item tidak boleh negatif'], 422);
            }
            $amounts[(int) $row['id']] = round($amount, 2);
        }

        $notaPaths = $this->storeFinanceFiles((array) $request->file('nota', []), 'finance/pengajuan/realisasi');
        $pengembalianPath = $request->hasFile('bukti_pengembalian')
            ? ($this->storeFinanceFiles([$request->file('bukti_pengembalian')], 'finance/pengajuan/realisasi')[0] ?? null)
            : null;
        $newFiles = array_values(array_filter(array_merge($notaPaths, [$pengembalianPath])));

        $user = Auth::user();
        $replacedFiles = [];
        $response = DB::transaction(function() use ($id, $amounts, $validated, $notaPaths, $pengembalianPath, $user, &$replacedFiles) {
            $pengajuan = FinancePengajuanDana::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->authorizePengajuanAccess($pengajuan);

            if ($pengajuan->payment_status !== 'paid') {
                return response()->json(['success' => false, 'message' => 'Realisasi hanya bisa diisi setelah pengajuan dibayar.'], 422);
            }
            $realisasi = $pengajuan->realisasi()->first();
            // an existing (not settled) realisasi may still be revised; otherwise the pengajuan must need one
            if (!$realisasi && !$this->requiresRealisasi($pengajuan)) {
                return response()->json(['success' => false, 'message' => 'Pengajuan ini tidak memerlukan realisasi.'], 422);
            }
            if ($realisasi && $realisasi->status === 'selesai') {
                return response()->json(['success' => false, 'message' => 'Realisasi sudah selesai dan tidak dapat diubah.'], 422);
            }

            $items = $pengajuan->items()->get();
            if ($items->pluck('id')->map('intval')->diff(array_keys($amounts))->isNotEmpty()) {
                return response()->json(['success' => false, 'message' => 'Isi realisasi untuk semua item.'], 422);
            }

            $dibayar = (float) $pengajuan->payments()->sum('nominal');
            $total = round(array_sum(array_intersect_key($amounts, array_flip($items->pluck('id')->all()))), 2);
            $sisa = round($dibayar - $total, 2);
            $hasDiff = abs($sisa) >= 0.01;
            $note = trim((string) ($validated['note'] ?? ''));
            if ($hasDiff && $note === '') {
                return response()->json(['success' => false, 'message' => 'Catatan wajib diisi jika realisasi berbeda dengan yang dibayar.'], 422);
            }

            foreach ($items as $item) {
                $item->realisasi = $amounts[$item->id];
                $item->save();
            }

            $realisasi = $realisasi ?: new FinancePengajuanDanaRealisasi(['pengajuan_id' => $pengajuan->id]);
            // new notas are added to the ones already uploaded; a new bukti pengembalian replaces the old one
            $realisasi->nota = array_values(array_merge((array) ($realisasi->nota ?? []), $notaPaths));
            if ($pengembalianPath) {
                if ($realisasi->bukti_pengembalian) $replacedFiles[] = $realisasi->bukti_pengembalian;
                $realisasi->bukti_pengembalian = $pengembalianPath;
            }
            $realisasi->fill([
                'total_realisasi' => $total,
                'sisa' => $sisa,
                'note' => $note !== '' ? $note : null,
                'status' => $hasDiff ? 'menunggu_konfirmasi' : 'selesai',
                'submitted_by' => $user ? $user->id : null,
                'submitted_at' => Carbon::now(),
                'confirmed_by' => null,
                'confirmed_at' => null,
            ]);
            $realisasi->save();

            if (!$hasDiff) {
                $message = 'Realisasi disimpan dan selesai';
            } elseif ($sisa > 0) {
                $message = 'Realisasi disimpan. Sisa dana ' . $this->rupiah($sisa) . ' dikembalikan, menunggu konfirmasi.';
            } else {
                $message = 'Realisasi disimpan. Kekurangan ' . $this->rupiah(abs($sisa)) . ', menunggu konfirmasi.';
            }
            return response()->json(['success' => true, 'message' => $message]);
        });

        // refused inside the transaction: the uploaded files are not referenced anywhere
        if ($response->getStatusCode() !== 200) {
            Storage::disk('public')->delete($newFiles);
        } elseif (!empty($replacedFiles)) {
            Storage::disk('public')->delete($replacedFiles);
        }
        return $response;
    }

    /** An active approver confirms a realisasi with a difference (money returned / overspent) -> 'selesai'. */
    public function confirmRealisasi($id)
    {
        $user = Auth::user();
        if (!$user || !$this->loadActiveApprovers()->contains('user_id', $user->id)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        return DB::transaction(function() use ($id, $user) {
            $realisasi = FinancePengajuanDanaRealisasi::where('pengajuan_id', $id)->lockForUpdate()->first();
            if (!$realisasi || $realisasi->status !== 'menunggu_konfirmasi') {
                return response()->json(['success' => false, 'message' => 'Tidak ada realisasi yang menunggu konfirmasi.'], 422);
            }
            $realisasi->status = 'selesai';
            $realisasi->confirmed_by = $user->id;
            $realisasi->confirmed_at = Carbon::now();
            $realisasi->save();
            return response()->json(['success' => true, 'message' => 'Realisasi dikonfirmasi']);
        });
    }

    /**
     * Bulk approve multiple pengajuan IDs for the current approver.
     * Body: { ids: [1,2,3,...] }
     */
    public function bulkApprove(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!FinanceDanaApprover::where('user_id', $user->id)->where('aktif', 1)->exists()) {
            return response()->json(['success' => false, 'message' => 'Anda tidak terdaftar sebagai approver aktif.'], 403);
        }

        $ids = $request->input('ids');
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided'], 422);
        }

        $approved = [];
        $skipped = [];
        $errors = [];

        foreach (array_unique(array_map('intval', $ids)) as $id) {
            try {
                [$ok, $msg] = $this->recordApprovalAction($id, $user, 'approved');
                if ($ok) {
                    $approved[] = $id;
                } else {
                    $skipped[] = [ 'id' => $id, 'reason' => $msg ];
                }
            } catch (\Throwable $e) {
                $errors[] = [ 'id' => $id, 'reason' => 'Server error' ];
            }
        }

        return response()->json([
            'success' => true,
            'approved_count' => count($approved),
            'approved' => $approved,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => 'Bulk approval processed',
        ]);
    }
}
