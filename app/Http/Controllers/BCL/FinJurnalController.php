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
            ]);

            DB::transaction(function () use ($request) {
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
                    $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_CASH, 'debet' => $nominal, 'catatan' => $catatan, 'identity' => $section, 'pos' => 'D']);
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
            });

            return back()->with('success', 'Pemasukan Berhasil Dihapus');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
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
            ]);

            DB::transaction(function () use ($request) {
                $renter = renter::findOrFail($request->renter);
                $noJurnal = $this->rental->nextNoJurnal();
                $catatan = 'Topup deposit oleh ' . $renter->nama . ($request->note ? '. ' . $request->note : '');
                $base = ['no_jurnal' => $noJurnal, 'tanggal' => $request->tgl_transaksi, 'catatan' => $catatan, 'doc_id' => 'DP' . $noJurnal, 'identity' => 'Topup Deposit', 'kode_subledger' => $renter->id];
                $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_CASH, 'debet' => $request->amount, 'pos' => 'D']);
                $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_DEPOSIT, 'kredit' => $request->amount, 'pos' => 'K']);
                $renter->creditDeposit((float) $request->amount);
            });

            return back()->with('success', 'Deposit berhasil ditambahkan');
        } catch (\Throwable $th) {
            $message = $th instanceof ValidationException ? collect($th->errors())->flatten()->first() : $th->getMessage();

            return back()->with('error', $message);
        }
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
