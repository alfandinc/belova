<?php

namespace App\Http\Controllers\BCL;

use App\Http\Controllers\Controller;
use App\Models\BCL\Fin_jurnal;
use App\Models\BCL\Inventory;
use App\Models\BCL\InventoryMaintenance;
use App\Models\BCL\Rooms;
use App\Services\BCL\RentalService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class InventoriesController extends Controller
{
    protected const CYCLES = ['Minggu', 'Bulan', 'Tahun'];
    protected const EXPENSE_ACCOUNT = '5-10101';

    public function __construct(protected RentalService $rental)
    {
    }

    public function index()
    {
        $rooms = Rooms::orderedForMapping()
            ->leftJoin('bcl_room_category as room_category', 'room_category.id_category', '=', 'bcl_rooms.room_category')
            ->select('bcl_rooms.id', 'bcl_rooms.room_name', 'bcl_rooms.floor', 'room_category.category_name')
            ->get();

        return view('bcl.inventories.index', compact('rooms'));
    }

    /** Server-side table (Yajra). Maintenance status is computed in SQL so it can be filtered and sorted. */
    public function data(Request $request)
    {
        $query = $this->statusQuery()
            ->when($request->filled('room'), fn ($q) => $request->room === 'umum'
                ? $q->where(fn ($w) => $w->whereNull('assigned_to')->orWhere('assigned_to', 0))
                : $q->where('assigned_to', (int) $request->room))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));

        return DataTables::of($query)
            ->orderColumn('next_due', 'next_due IS NULL, next_due $1')
            ->filterColumn('name', fn ($q, $keyword) => $q->where(fn ($w) => $w->where('name', 'like', "%$keyword%")->orWhere('notes', 'like', "%$keyword%")))
            ->filterColumn('room_name', fn ($q, $keyword) => $q->where('room_name', 'like', "%$keyword%"))
            ->editColumn('last_maint', fn ($row) => $row->last_maint)
            ->addColumn('routine', fn ($row) => $row->maintanance_period ? 'Setiap ' . (int) $row->maintanance_period . ' ' . $row->maintanance_cycle : null)
            ->addColumn('days_left', fn ($row) => $row->next_due ? (int) Carbon::today()->diffInDays(Carbon::parse($row->next_due), false) : null)
            ->make(true);
    }

    public function stats()
    {
        $base = $this->statusQuery();
        $counts = DB::query()->fromSub($base, 's')->select('status', DB::raw('COUNT(*) as c'))->groupBy('status')->pluck('c', 'status');
        $monthCost = Fin_jurnal::where('kode_akun', self::EXPENSE_ACCOUNT)->where('pos', 'D')
            ->whereBetween('tanggal', [now()->startOfMonth()->format('Y-m-d'), now()->endOfMonth()->format('Y-m-d')])
            ->sum('debet');

        return response()->json([
            'total' => (int) $counts->sum(),
            'overdue' => (int) ($counts['overdue'] ?? 0),
            'soon' => (int) ($counts['soon'] ?? 0),
            'routine' => (int) ($counts['overdue'] ?? 0) + (int) ($counts['soon'] ?? 0) + (int) ($counts['ok'] ?? 0),
            'month_cost' => (float) $monthCost,
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(['waktu_perawatan' => $request->filled('waktu_perawatan') ? (int) RentalService::amount($request->waktu_perawatan) : null]);
        try {
            $this->validate($request, $this->rules() + [
                'kamar' => 'required_if:tipe_inv,Private/Room|array',
                'kamar.*' => 'exists:bcl_rooms,id',
                'jumlah' => 'nullable|integer|min:1|max:50',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        // One item per selected room (e.g. "AC" for 10 rooms at once), times "jumlah" per room.
        $rooms = $request->tipe_inv === 'Private/Room' ? array_values(array_unique((array) $request->kamar)) : [null];
        $copies = max((int) $request->input('jumlah', 1), 1);
        $numbers = DB::transaction(function () use ($request, $rooms, $copies) {
            $created = [];
            foreach ($rooms as $roomId) {
                for ($i = 0; $i < $copies; $i++) {
                    $created[] = Inventory::create($this->attributes($request) + [
                        'inv_number' => $this->nextNumber(),
                        'assigned_to' => $roomId,
                    ])->inv_number;
                }
            }

            return $created;
        });

        return response()->json(['message' => count($numbers) . ' inventaris ditambahkan (' . $numbers[0] . (count($numbers) > 1 ? ' s/d ' . end($numbers) : '') . ').']);
    }

    public function edit(Request $request)
    {
        return response()->json(Inventory::with('room')->findOrFail($request->id));
    }

    public function update(Request $request)
    {
        $request->merge(['waktu_perawatan' => $request->filled('waktu_perawatan') ? (int) RentalService::amount($request->waktu_perawatan) : null]);
        $inventory = Inventory::find($request->id);
        if (!$inventory) {
            return response()->json(['message' => 'Inventaris tidak ditemukan.'], 404);
        }
        try {
            $this->validate($request, $this->rules() + [
                'inv_number' => ['required', 'max:50', Rule::unique('bcl_inventories', 'inv_number')->ignore($inventory->id)],
                'kamar' => 'required_if:tipe_inv,Private/Room|nullable|exists:bcl_rooms,id',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        DB::transaction(function () use ($request, $inventory) {
            $oldNumber = $inventory->inv_number;
            $inventory->update($this->attributes($request) + [
                'inv_number' => $request->inv_number,
                'assigned_to' => $request->tipe_inv === 'Private/Room' ? (int) $request->kamar : null,
            ]);

            // Maintenance history and expense lines are linked by inventory number.
            if ($oldNumber !== $request->inv_number) {
                InventoryMaintenance::withTrashed()->where('inv_number', $oldNumber)->update(['inv_number' => $request->inv_number]);
                Fin_jurnal::where('kode_akun', self::EXPENSE_ACCOUNT)->whereRaw('FIND_IN_SET(?, kode_subledger) > 0', [$oldNumber])->get()
                    ->each(function ($line) use ($oldNumber, $request) {
                        $codes = array_map(fn ($c) => $c === $oldNumber ? $request->inv_number : $c, explode(',', (string) $line->kode_subledger));
                        $line->update(['kode_subledger' => implode(',', $codes)]);
                    });
            }
        });

        return response()->json(['message' => 'Inventaris ' . $request->inv_number . ' diperbarui.']);
    }

    public function destroy(Request $request)
    {
        $inventory = Inventory::find($request->id);
        if (!$inventory) {
            return $this->respond($request, 'Inventaris tidak ditemukan.', 404);
        }
        if (InventoryMaintenance::where('inv_number', $inventory->inv_number)->exists()) {
            return $this->respond($request, 'Inventaris memiliki riwayat perawatan, tidak dapat dihapus.', 422);
        }
        $this->rental->audit('delete_inventory', $inventory->inv_number, 'Hapus inventaris ' . $inventory->name, $inventory->only(['name', 'type', 'assigned_to', 'notes']));
        $inventory->delete();

        return $this->respond($request, 'Inventaris ' . $inventory->inv_number . ' dihapus.');
    }

    /** Inventory detail + maintenance history (newest first). */
    public function show(Request $request)
    {
        $inventory = Inventory::with('room')->where('inv_number', $request->id)->firstOrFail();
        $status = $this->statusQuery($inventory->id)->first();
        $history = InventoryMaintenance::where('inv_number', $inventory->inv_number)->orderByDesc('tanggal')->orderByDesc('id')->get();
        // The payment method sits on the cash (credit) line of each maintenance expense.
        $methods = Fin_jurnal::whereIn('doc_id', $history->pluck('doc_id')->filter())->where('pos', 'K')->pluck('metode_bayar', 'doc_id');

        return response()->json([
            'id' => $inventory->id,
            'inv_number' => $inventory->inv_number,
            'name' => $inventory->name,
            'type' => $inventory->type,
            'notes' => $inventory->notes,
            'room_name' => optional($inventory->room)->room_name,
            'routine' => $inventory->maintanance_period ? 'Setiap ' . (int) $inventory->maintanance_period . ' ' . $inventory->maintanance_cycle : null,
            'last_maint' => optional($status)->last_maint,
            'next_due' => optional($status)->next_due,
            'history' => $history->map(fn ($m) => [
                    'id' => $m->id,
                    'tanggal' => $m->tanggal,
                    'description' => $m->description,
                    'cost' => (float) $m->cost,
                    'vendor_name' => $m->vendor_name,
                    'doc_id' => $m->doc_id,
                    'metode_bayar' => $methods[$m->doc_id] ?? null,
                ]),
        ]);
    }

    public function storeMaintenance(Request $request)
    {
        $request->merge(['nominal' => RentalService::amount($request->nominal)]);
        try {
            $this->validate($request, $this->maintenanceRules() + ['inv_number' => 'required|exists:bcl_inventories,inv_number']);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        }

        $maint = DB::transaction(function () use ($request) {
            $inventory = Inventory::where('inv_number', $request->inv_number)->firstOrFail();
            $maint = InventoryMaintenance::create([
                'inventory_id' => $inventory->id,
                'inv_number' => $inventory->inv_number,
                'tanggal' => $request->tanggal,
                'description' => $request->catatan,
                'cost' => (float) $request->nominal,
                'vendor_name' => $request->vendor_name,
                'created_by' => auth()->id(),
            ]);
            $this->syncMaintenanceJournal($maint, $request);

            return $maint;
        });

        return response()->json(['success' => true, 'message' => 'Perawatan ' . $request->inv_number . ' dicatat.', 'data' => $maint]);
    }

    public function updateMaintenance(Request $request)
    {
        $request->merge(['nominal' => RentalService::amount($request->nominal)]);
        try {
            $this->validate($request, $this->maintenanceRules() + ['id' => 'required|integer']);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        }

        $maint = DB::transaction(function () use ($request) {
            $maint = InventoryMaintenance::lockForUpdate()->findOrFail($request->id);
            $old = $maint->only(['tanggal', 'description', 'cost', 'vendor_name']);
            $maint->update([
                'tanggal' => $request->tanggal,
                'description' => $request->catatan,
                'cost' => (float) $request->nominal,
                'vendor_name' => $request->vendor_name,
            ]);
            $this->syncMaintenanceJournal($maint, $request);
            $this->rental->audit('update_maintenance', $maint->inv_number, 'Koreksi catatan perawatan ' . $maint->inv_number, ['old' => $old, 'new' => $maint->only(['tanggal', 'description', 'cost', 'vendor_name'])]);

            return $maint;
        });

        return response()->json(['success' => true, 'message' => 'Catatan perawatan diperbarui.', 'data' => $maint]);
    }

    public function deleteMaintenance(Request $request)
    {
        $this->validate($request, ['id' => 'required|integer']);

        DB::transaction(function () use ($request) {
            $maint = InventoryMaintenance::findOrFail($request->id);
            if ($maint->doc_id) {
                Fin_jurnal::where('doc_id', $maint->doc_id)->delete();
            }
            $this->rental->audit('delete_maintenance', $maint->inv_number, 'Hapus catatan perawatan ' . $maint->inv_number, $maint->only(['tanggal', 'description', 'cost', 'vendor_name', 'doc_id']));
            $maint->delete();
        });

        return response()->json(['success' => true, 'message' => 'Catatan perawatan dihapus.']);
    }

    public function exportPdf(Request $request)
    {
        $this->validate($request, [
            'rooms' => 'nullable|array',
            'rooms.*' => 'integer',
        ]);

        $roomsInput = array_map('intval', $request->input('rooms', []));
        $roomsQuery = Rooms::leftJoin('bcl_room_category as room_category', 'room_category.id_category', '=', 'bcl_rooms.room_category')
            ->select('bcl_rooms.id', 'bcl_rooms.room_name', 'bcl_rooms.floor', 'room_category.category_name');
        $roomsModel = $roomsInput
            ? $roomsQuery->whereIn('bcl_rooms.id', $roomsInput)->orderByRaw('FIELD(bcl_rooms.id, ' . implode(',', $roomsInput) . ')')->get()
            : $roomsQuery->orderByRaw('bcl_rooms.floor IS NULL, bcl_rooms.floor')->orderBy('bcl_rooms.room_name')->get();

        $items = Inventory::whereIn('assigned_to', $roomsModel->pluck('id'))->orderBy('inv_number')->get()->groupBy('assigned_to');
        $groups = $roomsModel->map(fn ($r) => [
            'room_id' => $r->id,
            'room_name' => $r->room_name,
            'floor' => $r->floor,
            'category_name' => $r->category_name,
            'items' => $items->get($r->id, collect()),
        ])->all();

        if (!$roomsInput) {
            $unassigned = Inventory::where(fn ($q) => $q->whereNull('assigned_to')->orWhere('assigned_to', 0))->orderBy('inv_number')->get();
            if ($unassigned->count()) {
                $groups[] = ['room_id' => null, 'room_name' => 'Umum', 'floor' => null, 'category_name' => '', 'items' => $unassigned];
            }
        }

        $roomTitle = count($groups) === 1 ? $groups[0]['room_name'] . ($groups[0]['category_name'] ? ' (' . $groups[0]['category_name'] . ')' : '') : null;

        return PDF::loadView('bcl.inventories.export_pdf', compact('groups', 'roomTitle'))->setPaper('a5', 'portrait')->stream('inventories.pdf');
    }

    // ------------------------------------------------------------ helpers

    /**
     * Inventory rows with last maintenance (maintenance log or older expense entries), the next due date
     * (from the last maintenance, or from when the item was registered) and a status:
     * none (no routine) | overdue | soon (within 7 days) | ok. $inventoryId limits it to one item
     * (filtering the outer query still computed every item first).
     */
    public function statusQuery(?int $inventoryId = null)
    {
        // Small derived tables joined once (a correlated FIND_IN_SET per row took ~2 s on 700 items).
        $maintLog = DB::table('bcl_inventory_maintenances')->whereNull('deleted_at')
            ->groupBy('inv_number')->select('inv_number', DB::raw('MAX(tanggal) as tanggal'));
        $expenseLines = DB::table('bcl_fin_jurnal')
            ->where('kode_akun', self::EXPENSE_ACCOUNT)->where('pos', 'D')->where('doc_id', 'not like', 'MAINT%')
            ->whereNotNull('kode_subledger')->where('kode_subledger', '!=', '')
            ->select('kode_subledger', 'tanggal');

        $base = DB::table('bcl_inventories as i')
            ->leftJoin('bcl_rooms as r', 'r.id', '=', 'i.assigned_to')
            ->leftJoinSub($maintLog, 'ml', 'ml.inv_number', '=', 'i.inv_number')
            ->leftJoinSub($expenseLines, 'el', function ($join) {
                $join->whereRaw('FIND_IN_SET(i.inv_number, el.kode_subledger) > 0');
            })
            ->when($inventoryId, fn ($q) => $q->where('i.id', $inventoryId))
            ->groupBy('i.id')
            ->select('i.id')
            ->selectRaw('MAX(i.inv_number) as inv_number, MAX(i.name) as name, MAX(i.notes) as notes, MAX(i.type) as type, MAX(i.assigned_to) as assigned_to')
            ->selectRaw('MAX(i.maintanance_period) as maintanance_period, MAX(i.maintanance_cycle) as maintanance_cycle, MAX(i.created_at) as created_at')
            ->selectRaw('MAX(r.room_name) as room_name, MAX(r.floor) as floor')
            ->selectRaw('MAX(ml.tanggal) as last_maint_log, MAX(el.tanggal) as last_maint_expense');

        $withDue = DB::query()->fromSub($base, 'b')
            ->select('b.*')
            ->selectRaw("NULLIF(GREATEST(COALESCE(last_maint_log, '1000-01-01'), COALESCE(last_maint_expense, '1000-01-01')), '1000-01-01') as last_maint")
            ->selectRaw("CASE WHEN CAST(maintanance_period AS UNSIGNED) > 0 THEN
                CASE maintanance_cycle
                    WHEN 'Minggu' THEN DATE_ADD(COALESCE(NULLIF(GREATEST(COALESCE(last_maint_log, '1000-01-01'), COALESCE(last_maint_expense, '1000-01-01')), '1000-01-01'), DATE(created_at)), INTERVAL CAST(maintanance_period AS UNSIGNED) WEEK)
                    WHEN 'Bulan' THEN DATE_ADD(COALESCE(NULLIF(GREATEST(COALESCE(last_maint_log, '1000-01-01'), COALESCE(last_maint_expense, '1000-01-01')), '1000-01-01'), DATE(created_at)), INTERVAL CAST(maintanance_period AS UNSIGNED) MONTH)
                    WHEN 'Tahun' THEN DATE_ADD(COALESCE(NULLIF(GREATEST(COALESCE(last_maint_log, '1000-01-01'), COALESCE(last_maint_expense, '1000-01-01')), '1000-01-01'), DATE(created_at)), INTERVAL CAST(maintanance_period AS UNSIGNED) YEAR)
                END END as next_due");

        $today = Carbon::today()->format('Y-m-d');
        $soon = Carbon::today()->addDays(7)->format('Y-m-d');

        $withStatus = DB::query()->fromSub($withDue, 'd')
            ->select('d.*')
            ->selectRaw("CASE WHEN next_due IS NULL THEN 'none' WHEN next_due < ? THEN 'overdue' WHEN next_due <= ? THEN 'soon' ELSE 'ok' END as status", [$today, $soon]);

        // Wrapped once more so "status" can be filtered on and sorted by.
        return DB::query()->fromSub($withStatus, 'inv')->select('inv.*');
    }

    protected function rules(): array
    {
        return [
            'nama' => 'required|string|max:150',
            'tipe_inv' => 'required|in:Private/Room,Public',
            'keterangan' => 'nullable|string|max:255',
            'waktu_perawatan' => 'required_if:perawatan_rutin,1|nullable|integer|min:1|max:120',
            'cycle_perawatan' => 'required_if:perawatan_rutin,1|nullable|in:' . implode(',', self::CYCLES),
        ];
    }

    protected function attributes(Request $request): array
    {
        $routine = $request->boolean('perawatan_rutin');

        return [
            'name' => trim($request->nama),
            'notes' => $request->keterangan,
            'type' => $request->tipe_inv,
            'maintanance_period' => $routine ? $request->waktu_perawatan : null,
            'maintanance_cycle' => $routine ? $request->cycle_perawatan : null,
        ];
    }

    protected function maintenanceRules(): array
    {
        return [
            'tanggal' => 'required|date',
            'catatan' => 'required|string|max:255',
            'nominal' => 'nullable|numeric|min:0',
            'vendor_name' => 'nullable|string|max:150',
            'metode_bayar' => 'nullable|in:' . implode(',', RentalService::METHODS),
            'bukti' => 'nullable|image|max:8192',
        ];
    }

    /**
     * Keep the expense journal of a maintenance in line with its cost: lines only exist when there is a
     * cost (no more Rp 0 expenses), and are created / updated / removed as the cost changes.
     */
    protected function syncMaintenanceJournal(InventoryMaintenance $maint, Request $request): void
    {
        $cost = (float) $maint->cost;
        if ($cost <= 0) {
            if ($maint->doc_id) {
                Fin_jurnal::where('doc_id', $maint->doc_id)->delete();
                $maint->update(['doc_id' => null, 'journal_id' => null]);
            }

            return;
        }

        $catatan = 'Perawatan ' . $maint->inv_number . ': ' . $maint->description . ($maint->vendor_name ? ' (' . $maint->vendor_name . ')' : '');
        $method = $request->metode_bayar ?: 'Tunai';
        $proof = $this->rental->storeProof($request->file('bukti'), 'bukti-perawatan');

        if ($maint->doc_id && Fin_jurnal::where('doc_id', $maint->doc_id)->exists()) {
            Fin_jurnal::where('doc_id', $maint->doc_id)->where('pos', 'K')->update(array_filter([
                'tanggal' => $maint->tanggal, 'kredit' => $cost, 'debet' => 0, 'catatan' => $catatan, 'metode_bayar' => $method, 'bukti' => $proof,
            ], fn ($v) => $v !== null));
            Fin_jurnal::where('doc_id', $maint->doc_id)->where('pos', 'D')->update([
                'tanggal' => $maint->tanggal, 'debet' => $cost, 'kredit' => 0, 'catatan' => $catatan, 'kode_subledger' => $maint->inv_number,
            ]);

            return;
        }

        $noJurnal = $this->rental->nextNoJurnal();
        $base = ['no_jurnal' => $noJurnal, 'tanggal' => $maint->tanggal, 'doc_id' => 'MAINT' . $noJurnal, 'identity' => 'Pengeluaran', 'catatan' => $catatan];
        $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_CASH, 'kredit' => $cost, 'pos' => 'K', 'metode_bayar' => $method, 'bukti' => $proof]);
        $debit = $this->rental->journal($base + ['kode_akun' => self::EXPENSE_ACCOUNT, 'debet' => $cost, 'pos' => 'D', 'kode_subledger' => $maint->inv_number]);
        $maint->update(['doc_id' => 'MAINT' . $noJurnal, 'journal_id' => $debit->id]);
    }

    /** Next free number "IN" + mmyy + 4 digits (sequence continues across months). */
    protected function nextNumber(): string
    {
        $last = DB::selectOne("SELECT MAX(CAST(SUBSTR(inv_number, 7, 4) AS UNSIGNED)) AS n FROM bcl_inventories WHERE inv_number REGEXP '^IN[0-9]{8}' FOR UPDATE");

        return 'IN' . now()->format('my') . str_pad((string) (((int) ($last->n ?? 0)) + 1), 4, '0', STR_PAD_LEFT);
    }

    protected function respond(Request $request, string $message, int $status = 200)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return back()->with($status < 300 ? 'success' : 'error', $message);
    }
}
