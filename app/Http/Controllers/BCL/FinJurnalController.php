<?php

namespace App\Http\Controllers\BCL;

use App\Http\Controllers\Controller;
use App\Models\BCL\expense_receipt;
use App\Models\BCL\Fin_jurnal;
use App\Models\BCL\Inventory;
use App\Models\BCL\InventoryMaintenance;
use App\Models\BCL\renter;
use App\Models\BCL\tb_extra_rent;
use App\Models\BCL\tr_renter;
use App\Services\BCL\RentalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Laravel\Facades\Image;

class FinJurnalController extends Controller
{
    protected const EXPENSE_TYPES = [
        '5-10101' => 'Perbaikan/perawatan inventaris kamar atau bangunan',
        '5-10102' => 'Biaya Operasional/lain-lain',
    ];

    public function __construct(protected RentalService $rental)
    {
    }

    public function finance()
    {
        return redirect()->route('bcl.income.index');
    }

    public function index(Request $request)
    {
        [$start, $end] = $this->dateRange($request->filter);
        $data = Fin_jurnal::whereBetween('tanggal', [$start, $end])
            ->where('kode_akun', RentalService::ACCOUNT_CASH)
            ->where('pos', 'D')
            ->orderByDesc('tanggal')
            ->get();
        $unpaid = $this->rental->unpaidItems();

        return view('bcl.finance.income', compact('data', 'start', 'end', 'unpaid'));
    }

    /** Receive a payment for a rent, upgrade or extra rent document. */
    public function store(Request $request)
    {
        $request->merge([
            'nominal' => RentalService::amount($request->nominal),
            'deposit_amount' => RentalService::amount($request->deposit_amount),
        ]);

        try {
            $this->validate($request, [
                'transaksi' => 'required',
                'tgl_transaksi' => 'required|date',
                'nominal' => 'required|numeric|min:0',
                'deposit_amount' => 'nullable|numeric|min:0',
                'metode_bayar' => 'nullable|in:' . implode(',', RentalService::METHODS),
                'bukti' => 'nullable|image|max:8192',
            ]);
            $method = $request->metode_bayar ?: 'Tunai';
            if ((float) $request->nominal > 0 && RentalService::methodNeedsProof($method) && !$request->hasFile('bukti')) {
                throw new \RuntimeException('Bukti ' . $method . ' wajib dilampirkan (bisa ditempel dengan Ctrl+V).');
            }

            DB::transaction(function () use ($request, $method) {
                [$renter, $label, $outstanding] = $this->paymentTarget($request->transaksi);
                $nominal = (float) $request->nominal;
                $depositUse = $request->boolean('use_deposit')
                    ? min($request->deposit_amount > 0 ? (float) $request->deposit_amount : (float) $renter->deposit_balance, (float) $renter->deposit_balance, max($outstanding - $nominal, 0))
                    : 0;

                if ($nominal + $depositUse <= 0) {
                    throw new \RuntimeException('Nominal pembayaran harus lebih dari 0.');
                }
                if ($nominal + $depositUse > $outstanding + 0.009) {
                    throw new \RuntimeException('Pembayaran melebihi sisa tagihan Rp ' . number_format($outstanding, 0, ',', '.'));
                }

                $section = $request->section ?: 'Sewa Kamar';
                $catatan = 'Pembayaran ' . $label . ' oleh ' . $renter->nama . ($request->keterangan ? '. Catatan: ' . $request->keterangan : '');
                $base = ['no_jurnal' => $this->rental->nextNoJurnal(), 'tanggal' => $request->tgl_transaksi, 'doc_id' => $request->transaksi];

                if ($nominal > 0) {
                    $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_REVENUE, 'kredit' => $nominal, 'kode_subledger' => $renter->id, 'catatan' => $catatan, 'identity' => $section, 'pos' => 'K']);
                    $proof = $this->rental->storeProof($request->file('bukti'), 'bukti-bayar');
                    $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_CASH, 'debet' => $nominal, 'catatan' => $catatan, 'identity' => $section, 'pos' => 'D', 'metode_bayar' => $method, 'bukti' => $proof]);
                }
                if ($depositUse > 0) {
                    $note = 'Pemakaian deposit untuk ' . $label . ' oleh ' . $renter->nama;
                    $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_REVENUE, 'kredit' => $depositUse, 'kode_subledger' => $renter->id, 'catatan' => $note, 'identity' => 'Pemakaian Deposit', 'pos' => 'K']);
                    $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_DEPOSIT, 'debet' => $depositUse, 'kode_subledger' => $renter->id, 'catatan' => $note, 'identity' => 'Pemakaian Deposit', 'pos' => 'D']);
                    $renter->debitDeposit($depositUse);
                }
            });
        } catch (\Throwable $th) {
            $message = $th instanceof ValidationException ? collect($th->errors())->flatten()->first() : $th->getMessage();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Pembayaran Berhasil']);
        }

        return back()->with('success', 'Pembayaran Berhasil');
    }

    /**
     * Delete one payment (all lines sharing a no_jurnal). The first payment of a rent
     * belongs to the rent itself and is removed by deleting the transaction.
     */
    public function income_delete(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $lines = Fin_jurnal::where('no_jurnal', $request->id)->get();
                if ($lines->isEmpty()) {
                    throw new \RuntimeException('Data pemasukan tidak ditemukan.');
                }

                $docId = $lines->first()->doc_id;
                $isRent = tr_renter::where('trans_id', $docId)->exists();
                $firstJurnal = Fin_jurnal::where('doc_id', $docId)->orderBy('id')->value('no_jurnal');
                if ($isRent && $firstJurnal === $request->id) {
                    throw new \RuntimeException('Pembayaran awal sewa tidak dapat dihapus. Hapus transaksi sewa untuk membatalkannya.');
                }

                $this->assertDepositReversible($lines);
                $this->rental->revertDepositLines($lines);
                Fin_jurnal::whereIn('id', $lines->pluck('id'))->delete();
                $this->rental->audit('delete_payment', $docId, 'Hapus pembayaran no. jurnal ' . $request->id, ['jurnal' => $lines->map->only(['tanggal', 'kode_akun', 'debet', 'kredit', 'identity', 'metode_bayar', 'catatan'])->values()]);
            });

            return $this->done($request, 'Pemasukan berhasil dihapus');
        } catch (\Throwable $th) {
            return $this->done($request, $th->getMessage(), 422);
        }
    }

    public function expense(Request $request)
    {
        [$start, $end] = $this->dateRange($request->filter);
        $inventory = Inventory::leftJoin('bcl_rooms as rooms', 'rooms.id', '=', 'bcl_inventories.assigned_to')
            ->select('bcl_inventories.*', 'rooms.room_name as nama_kamar')
            ->get();
        $data = $this->withExpenseDetails(
            Fin_jurnal::whereBetween('tanggal', [$start, $end])
                ->whereIn('kode_akun', array_keys(self::EXPENSE_TYPES))
                ->where('pos', 'D')
                ->orderByDesc('tanggal')
                ->get()
        );

        return view('bcl.finance.expense', compact('data', 'start', 'end', 'inventory'));
    }

    public function expense_show(Request $request)
    {
        $data = $this->withExpenseDetails(
            Fin_jurnal::with(['user', 'receipt'])
                ->where('doc_id', $request->id)
                ->whereIn('kode_akun', array_keys(self::EXPENSE_TYPES))
                ->where('pos', 'D')
                ->get()
        );

        return response()->json($data);
    }

    public function expense_delete(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $lines = Fin_jurnal::where('doc_id', $request->id)->get();
                $this->assertDepositReversible($lines);
                $this->rental->revertDepositLines($lines);
                Fin_jurnal::whereIn('id', $lines->pluck('id'))->delete();
                InventoryMaintenance::where('doc_id', $request->id)->delete();
                $this->rental->audit('delete_expense', $request->id, 'Hapus pengeluaran ' . $request->id, ['jurnal' => $lines->map->only(['tanggal', 'kode_akun', 'debet', 'kredit', 'identity', 'catatan'])->values()]);

                foreach (expense_receipt::where('trans_id', $request->id)->get() as $receipt) {
                    $path = public_path('assets/images/receipt/' . $receipt->img);
                    if (is_file($path)) {
                        unlink($path);
                    }
                    $receipt->delete();
                }
            });

            return back()->with('success', 'Pengeluaran Berhasil Dihapus');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function store_expense(Request $request)
    {
        $request->merge(['jumlah' => array_map([RentalService::class, 'amount'], (array) $request->jumlah)]);

        try {
            $this->validate($request, [
                'tgl_transaksi' => 'required|date',
                'akun' => 'required|array|min:1',
                'akun.*' => 'required|in:' . implode(',', array_keys(self::EXPENSE_TYPES)),
                'jumlah.*' => 'required|numeric|min:1',
                'deskripsi.*' => 'nullable|string|max:255',
                'receipt.*' => 'nullable|image|max:8192',
            ]);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        $saved = [];
        try {
            DB::transaction(function () use ($request, &$saved) {
                $noExp = $this->rental->nextNoExp();
                $noJurnal = $this->rental->nextNoJurnal();

                foreach ($request->akun as $i => $akun) {
                    $subledger = collect($request->input("akun_subledger.$i", []))->filter()->implode(',') ?: null;
                    $base = ['no_jurnal' => $noJurnal, 'tanggal' => $request->tgl_transaksi, 'catatan' => (string) ($request->deskripsi[$i] ?? ''), 'doc_id' => $noExp, 'identity' => 'Pengeluaran'];
                    $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_CASH, 'kredit' => $request->jumlah[$i], 'pos' => 'K']);
                    $this->rental->journal($base + ['kode_akun' => $akun, 'debet' => $request->jumlah[$i], 'kode_subledger' => $subledger, 'pos' => 'D']);
                }

                foreach ((array) $request->file('receipt', []) as $file) {
                    $filename = 'bclReceipt_' . Carbon::now()->format('Y-m-d') . '_' . Str::random(12) . '.' . $file->getClientOriginalExtension();
                    Image::read($file)->scaleDown(600, 600)->save(public_path('assets/images/receipt/' . $filename));
                    $saved[] = $filename;
                    expense_receipt::create(['trans_id' => $noExp, 'img' => $filename]);
                }
            });

            return back()->with('success', 'Pengeluaran Berhasil Dibuat');
        } catch (\Throwable $th) {
            foreach ($saved as $filename) {
                @unlink(public_path('assets/images/receipt/' . $filename));
            }

            return back()->with('error', $th->getMessage());
        }
    }

    public function topup_deposit(Request $request)
    {
        $request->merge(['amount' => RentalService::amount($request->amount)]);

        try {
            $this->validate($request, [
                'renter' => 'required|exists:bcl_renter,id',
                'amount' => 'required|numeric|min:1',
                'tgl_transaksi' => 'required|date',
                'metode_bayar' => 'nullable|in:' . implode(',', RentalService::METHODS),
                'bukti' => 'nullable|image|max:8192',
            ]);
            $method = $request->metode_bayar ?: 'Tunai';
            if (RentalService::methodNeedsProof($method) && !$request->hasFile('bukti')) {
                throw new \RuntimeException('Bukti ' . $method . ' wajib dilampirkan.');
            }

            DB::transaction(function () use ($request, $method) {
                $renter = renter::findOrFail($request->renter);
                $noJurnal = $this->rental->nextNoJurnal();
                $catatan = 'Topup deposit oleh ' . $renter->nama . ($request->note ? '. ' . $request->note : '');
                $base = ['no_jurnal' => $noJurnal, 'tanggal' => $request->tgl_transaksi, 'catatan' => $catatan, 'doc_id' => 'DP' . $noJurnal, 'identity' => 'Topup Deposit', 'kode_subledger' => $renter->id];
                $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_CASH, 'debet' => $request->amount, 'pos' => 'D', 'metode_bayar' => $method, 'bukti' => $this->rental->storeProof($request->file('bukti'), 'bukti-deposit')]);
                $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_DEPOSIT, 'kredit' => $request->amount, 'pos' => 'K']);
                $renter->creditDeposit((float) $request->amount);
            });

            return back()->with('success', 'Deposit berhasil ditambahkan');
        } catch (\Throwable $th) {
            $message = $th instanceof ValidationException ? collect($th->errors())->flatten()->first() : $th->getMessage();

            return back()->with('error', $message);
        }
    }

    /** Correct date, amount, method or note of one payment (supervisor). Payments using deposit must be deleted and re-entered. */
    public function updatePayment(Request $request)
    {
        $request->merge(['nominal' => RentalService::amount($request->nominal)]);
        try {
            $this->validate($request, [
                'no_jurnal' => 'required',
                'tanggal' => 'required|date',
                'nominal' => 'required|numeric|min:1',
                'metode_bayar' => 'nullable|in:' . implode(',', RentalService::METHODS),
                'alasan' => 'required|string|max:255',
            ]);

            $message = DB::transaction(function () use ($request) {
                $lines = Fin_jurnal::where('no_jurnal', $request->no_jurnal)->lockForUpdate()->get();
                $revenue = $lines->where('kode_akun', RentalService::ACCOUNT_REVENUE)->where('pos', 'K');
                $cash = $lines->where('kode_akun', RentalService::ACCOUNT_CASH)->where('pos', 'D');
                if ($revenue->count() !== 1 || $cash->count() !== 1 || $lines->count() !== 2) {
                    throw new \RuntimeException('Pembayaran ini tidak bisa dikoreksi langsung (memakai deposit / bagian dari transaksi lain). Hapus lalu input ulang.');
                }
                $revenue = $revenue->first();
                $cash = $cash->first();
                [, , $outstanding] = $this->paymentTarget($revenue->doc_id);
                $max = $outstanding + (float) $revenue->kredit;
                if ((float) $request->nominal > $max + 0.009) {
                    throw new \RuntimeException('Nominal melebihi tagihan (maks Rp ' . number_format($max, 0, ',', '.') . ').');
                }

                $old = ['tanggal' => $cash->tanggal, 'nominal' => (float) $cash->debet, 'metode_bayar' => $cash->metode_bayar, 'catatan' => $cash->catatan];
                $catatan = $request->filled('catatan') ? $request->catatan : $cash->catatan;
                $revenue->update(['tanggal' => $request->tanggal, 'kredit' => $request->nominal, 'catatan' => $catatan]);
                $cash->update(['tanggal' => $request->tanggal, 'debet' => $request->nominal, 'catatan' => $catatan, 'metode_bayar' => $request->metode_bayar ?: $cash->metode_bayar]);
                $this->rental->audit('update_payment', $revenue->doc_id, 'Koreksi pembayaran: ' . $request->alasan, ['no_jurnal' => $request->no_jurnal, 'old' => $old, 'new' => $request->only(['tanggal', 'nominal', 'metode_bayar'])]);

                return 'Pembayaran ' . $revenue->doc_id . ' dikoreksi.';
            });
        } catch (\Throwable $th) {
            $message = $th instanceof ValidationException ? collect($th->errors())->flatten()->first() : $th->getMessage();

            return $this->done($request, $message, 422);
        }

        return $this->done($request, $message);
    }

    /** Security deposit (uang jaminan): receive, return, deduct for damages or move to the prepaid balance. */
    public function guarantee(Request $request)
    {
        $request->merge(['amount' => RentalService::amount($request->amount)]);
        try {
            $this->validate($request, [
                'renter' => 'required|exists:bcl_renter,id',
                'mode' => 'required|in:terima,kembalikan,potong,ke_saldo',
                'amount' => 'required|numeric|min:1',
                'metode_bayar' => 'nullable|in:' . implode(',', RentalService::METHODS),
                'tanggal' => 'nullable|date',
                'catatan' => 'nullable|string|max:255',
                'bukti' => 'nullable|image|max:8192',
            ]);
            if ($request->mode === 'potong' && !$request->filled('catatan')) {
                throw new \RuntimeException('Isi alasan potongan jaminan (mis. kerusakan apa).');
            }
            $renter = renter::findOrFail($request->renter);
            $method = $request->metode_bayar ?: 'Tunai';
            $amount = (float) $request->amount;

            $notes = DB::transaction(function () use ($request, $renter, $method, $amount) {
                switch ($request->mode) {
                    case 'terima':
                        $this->rental->receiveGuarantee($renter, $amount, $method, $this->rental->storeProof($request->file('bukti'), 'bukti-jaminan'), $request->tanggal ?: date('Y-m-d'), (string) $request->catatan);

                        return ['jaminan diterima Rp ' . number_format($amount, 0, ',', '.')];
                    case 'kembalikan':
                        return $this->rental->settleGuarantee($renter, $amount, 0, false, $method, '');
                    case 'ke_saldo':
                        return $this->rental->settleGuarantee($renter, $amount, 0, true, $method, '');
                    default:
                        return $this->rental->settleGuarantee($renter, 0, $amount, false, $method, (string) $request->catatan);
                }
            });
        } catch (\Throwable $th) {
            $message = $th instanceof ValidationException ? collect($th->errors())->flatten()->first() : $th->getMessage();

            return $this->done($request, $message, 422);
        }

        return $this->done($request, 'Uang jaminan ' . $renter->nama . ': ' . implode(', ', $notes) . '. Saldo jaminan Rp ' . number_format($this->rental->guaranteeBalance($renter->id), 0, ',', '.'));
    }

    /** Monthly report: occupancy, cash by method, revenue by room/category, receivable aging, move-ins/outs. */
    public function report(Request $request)
    {
        $month = Carbon::createFromFormat('Y-m', $request->query('bulan', now()->format('Y-m')))->startOfMonth();
        $start = $month->copy();
        $end = $month->copy()->endOfMonth();
        $days = $start->daysInMonth;
        $from = $start->format('Y-m-d');
        $to = $end->format('Y-m-d');

        $rooms = \App\Models\BCL\Rooms::with('category')->get();
        $rentals = tr_renter::with(['room.category', 'renter'])
            ->whereNull('cancelled_at')
            ->whereDate('tgl_mulai', '<=', $to)
            ->whereDate('tgl_selesai', '>', $from)
            ->get();
        $nights = $rentals->sum(function ($trx) use ($start, $end) {
            $a = Carbon::parse($trx->tgl_mulai)->max($start);
            $b = Carbon::parse($trx->tgl_selesai)->min($end->copy()->addDay()->startOfDay());

            return max((int) $a->diffInDays($b, false), 0);
        });
        $capacity = max($rooms->count() * $days, 1);

        $cashIn = Fin_jurnal::whereBetween('tanggal', [$from, $to])->where('kode_akun', RentalService::ACCOUNT_CASH)->where('pos', 'D')->get();
        $cashOut = Fin_jurnal::whereBetween('tanggal', [$from, $to])->where('kode_akun', RentalService::ACCOUNT_CASH)->where('pos', 'K')->get();
        $byMethod = $cashIn->groupBy(fn ($l) => $l->metode_bayar ?: 'Tidak dicatat')->map(fn ($g) => (float) $g->sum('debet'))->sortDesc();
        $byIdentity = $cashIn->groupBy('identity')->map(fn ($g) => (float) $g->sum('debet'))->sortDesc();
        $outByIdentity = $cashOut->groupBy('identity')->map(fn ($g) => (float) $g->sum('kredit'))->sortDesc();

        // Revenue received this month per room: payments on rent documents + extras of those rents.
        $revenue = Fin_jurnal::whereBetween('tanggal', [$from, $to])->where('kode_akun', RentalService::ACCOUNT_REVENUE)->where('pos', 'K')->get();
        $docRoom = tr_renter::with('room.category')->whereIn('trans_id', $revenue->pluck('doc_id'))->get()->keyBy('trans_id');
        $extraParent = tb_extra_rent::whereIn('kode', $revenue->pluck('doc_id'))->pluck('parent_trans', 'kode');
        $parents = tr_renter::with('room.category')->whereIn('trans_id', $extraParent->values())->get()->keyBy('trans_id');
        $perRoom = [];
        $perCategory = [];
        foreach ($revenue as $line) {
            $trx = $docRoom[$line->doc_id] ?? ($parents[$extraParent[$line->doc_id] ?? null] ?? null);
            $roomName = optional(optional($trx)->room)->room_name ?? 'Lainnya';
            $category = optional(optional(optional($trx)->room)->category)->category_name ?? 'Lainnya';
            $perRoom[$roomName] = ($perRoom[$roomName] ?? 0) + (float) $line->kredit;
            $perCategory[$category] = ($perCategory[$category] ?? 0) + (float) $line->kredit;
        }
        arsort($perRoom);
        arsort($perCategory);

        $aging = ['0-30 hari' => 0, '31-60 hari' => 0, '61-90 hari' => 0, '> 90 hari' => 0];
        $agingCount = $aging;
        foreach ($this->rental->unpaidItems() as $item) {
            $age = Carbon::parse($item['tanggal'])->diffInDays(Carbon::today());
            $bucket = $age <= 30 ? '0-30 hari' : ($age <= 60 ? '31-60 hari' : ($age <= 90 ? '61-90 hari' : '> 90 hari'));
            $aging[$bucket] += $item['kurang'];
            $agingCount[$bucket]++;
        }

        // Move-ins: rentals starting this month that do not continue the same renter's previous rental.
        $starting = tr_renter::with(['room', 'renter'])->whereNull('cancelled_at')->whereBetween('tgl_mulai', [$from, $to])->get();
        $moveIns = $starting->filter(fn ($t) => !tr_renter::where('id_renter', $t->id_renter)->where('id', '!=', $t->id)->whereNull('cancelled_at')
            ->whereBetween('tgl_selesai', [Carbon::parse($t->tgl_mulai)->subDay()->format('Y-m-d'), $t->tgl_mulai])->exists());
        // Move-outs: rentals ending this month without a continuation.
        $ending = tr_renter::with(['room', 'renter'])->whereNull('cancelled_at')->whereBetween('tgl_selesai', [$from, $to])->whereNotNull('checked_out_at')->get();
        $moveOuts = $ending->filter(fn ($t) => !tr_renter::where('id_renter', $t->id_renter)->where('id', '!=', $t->id)->whereNull('cancelled_at')
            ->whereBetween('tgl_mulai', [$t->tgl_selesai, Carbon::parse($t->tgl_selesai)->addDay()->format('Y-m-d')])->exists());

        $expenses = Fin_jurnal::whereBetween('tanggal', [$from, $to])->whereIn('kode_akun', array_keys(self::EXPENSE_TYPES))->where('pos', 'D')
            ->get()->groupBy(fn ($l) => self::EXPENSE_TYPES[$l->kode_akun] ?? $l->kode_akun)->map(fn ($g) => (float) $g->sum('debet'));

        return view('bcl.finance.report', [
            'month' => $month,
            'occupancy' => round($nights / $capacity * 100, 1),
            'nights' => $nights,
            'capacity' => $capacity,
            'roomCount' => $rooms->count(),
            'cashInTotal' => (float) $cashIn->sum('debet'),
            'cashOutTotal' => (float) $cashOut->sum('kredit'),
            'byMethod' => $byMethod,
            'byIdentity' => $byIdentity,
            'outByIdentity' => $outByIdentity,
            'perRoom' => $perRoom,
            'perCategory' => $perCategory,
            'revenueTotal' => (float) $revenue->sum('kredit'),
            'aging' => $aging,
            'agingCount' => $agingCount,
            'moveIns' => $moveIns,
            'moveOuts' => $moveOuts,
            'expenses' => $expenses,
            'guaranteeHeld' => (float) $this->rental->guaranteeBalances()->sum(),
            'depositHeld' => (float) renter::sum('deposit_balance'),
        ]);
    }

    /** Who corrected, cancelled or deleted what. */
    public function auditLog(Request $request)
    {
        $logs = \App\Models\BCL\AuditLog::with('user:id,name')
            ->when($request->q, fn ($q, $term) => $q->where(fn ($w) => $w->where('subject', 'like', "%$term%")->orWhere('description', 'like', "%$term%")))
            ->latest('id')
            ->limit(500)
            ->get();

        return view('bcl.audit.index', compact('logs'));
    }

    protected function done(Request $request, string $message, int $status = 200)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return back()->with($status < 300 ? 'success' : 'error', $message);
    }

    // ------------------------------------------------------------ helpers

    /** @return array{0: renter, 1: string, 2: float} renter, label and outstanding amount */
    protected function paymentTarget(string $docId): array
    {
        $extra = tb_extra_rent::where('kode', $docId)->first();
        $trx = $extra
            ? tr_renter::where('trans_id', $extra->parent_trans)->first()
            : tr_renter::where('trans_id', $docId)->first();
        if (!$trx) {
            throw new \RuntimeException('Transaksi ' . $docId . ' tidak ditemukan.');
        }

        $renter = renter::findOrFail($trx->id_renter);
        $total = $extra ? $this->rental->extraTotal($extra) : (float) $trx->harga;
        $paid = (float) ($this->rental->paidByDoc([$docId])[$docId] ?? 0);
        $label = $extra
            ? 'tambahan sewa ' . $extra->qty . ' ' . $extra->nama . ' ' . $extra->lama_sewa . ' ' . $extra->jangka_sewa . ' (' . $docId . ')'
            : ($trx->identity === 'Pindah Kamar' ? 'upgrade kamar ' : 'sewa kamar ') . $docId;

        return [$renter, $label, max($total - $paid, 0)];
    }

    /** Refuse deleting lines that would push a renter deposit below zero. */
    protected function assertDepositReversible(Collection $lines): void
    {
        $lines->where('kode_akun', RentalService::ACCOUNT_DEPOSIT)
            ->groupBy('kode_subledger')
            ->each(function (Collection $rows, $renterId) {
                $net = (float) $rows->sum('kredit') - (float) $rows->sum('debet');
                $renter = renter::find($renterId);
                if ($renter && $net > 0 && (float) $renter->deposit_balance < $net) {
                    throw new \RuntimeException('Deposit ' . $renter->nama . ' sudah terpakai, data ini tidak dapat dihapus.');
                }
            });
    }

    protected function withExpenseDetails(Collection $lines): Collection
    {
        $codes = $lines->flatMap(fn ($l) => explode(',', (string) $l->kode_subledger))->filter()->unique();
        $inventories = Inventory::whereIn('inv_number', $codes)->get()->keyBy('inv_number');

        return $lines->each(function ($line) use ($inventories) {
            $line->tipe_pengeluaran = self::EXPENSE_TYPES[$line->kode_akun] ?? $line->kode_akun;
            $line->arr_inventory = collect(explode(',', (string) $line->kode_subledger))
                ->map(fn ($code) => $inventories->get($code))
                ->filter()
                ->values();
        });
    }

    protected function dateRange(?string $filter): array
    {
        if ($filter && str_contains($filter, 's/d')) {
            [$start, $end] = array_map('trim', explode('s/d', $filter, 2));

            return [Carbon::parse($start)->format('Y-m-d'), Carbon::parse($end)->format('Y-m-d')];
        }

        return [now()->startOfMonth()->format('Y-m-d'), now()->endOfMonth()->format('Y-m-d')];
    }
}
