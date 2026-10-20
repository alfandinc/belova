<?php

namespace App\Http\Controllers\BCL;

use App\Http\Controllers\Controller;
use App\Models\BCL\ExtraBedAssignment;
use App\Models\BCL\Fin_jurnal;
use App\Models\BCL\Pricelist;
use App\Models\BCL\renter;
use App\Models\BCL\RoomInspection;
use App\Models\BCL\Rooms;
use App\Models\BCL\tb_extra_rent;
use App\Models\BCL\tr_renter;
use App\Services\BCL\RentalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class tr_renterController extends Controller
{
    public function __construct(protected RentalService $rental)
    {
    }

    public function index(Request $request)
    {
        [$start, $end] = $this->dateRange($request->filter, 'year');

        $data = tr_renter::with(['renter', 'room', 'tambahan'])
            ->whereBetween('tanggal', [$start, $end])
            ->orderByDesc('tanggal')
            ->get();
        $paid = $this->rental->paidByDoc($data->pluck('trans_id'));
        $overdue = $this->rental->overstaysByRoom()->pluck('trans_id')->flip();

        return view('bcl.transaksi.index', compact('data', 'paid', 'overdue', 'start', 'end'));
    }

    public function show(Request $request)
    {
        $transaksi = $this->loadTransaction($request->id, ['tambahan.jurnal', 'tambahan.assetAssignments.asset', 'tambahan.parentTransaction.room']);

        return response()->json($transaksi);
    }

    public function cetak(Request $request)
    {
        $transaksi = $this->loadTransaction($request->id, ['room.category', 'tambahan.jurnal', 'tambahan.assetAssignments.asset']);
        abort_unless($transaksi, 404);

        return view('bcl.transaksi.cetak', compact('transaksi'));
    }

    /**
     * Refund/credit note print view. The payload comes from the session right after
     * a room change; otherwise it is rebuilt from the journal lines of $doc_id.
     */
    public function cetakRefund(Request $request, $doc_id, $renter_id = null)
    {
        $transaksiId = session('transaksi_id');
        $transaksi = null;
        if ($transaksiId) {
            $transaksi = tr_renter::with(['renter', 'room'])->where('trans_id', $transaksiId)->first();
        } elseif ($renter_id) {
            $transaksi = tr_renter::with(['renter', 'room'])->where('id_renter', $renter_id)->orderByDesc('tanggal')->first();
        }

        $journal_lines = Fin_jurnal::where('doc_id', $doc_id)->orderBy('tanggal')->get();
        $refund = session('refund_payload') ?: (object) [
            'doc_id' => $doc_id,
            'amount' => (float) $journal_lines->where('kode_akun', RentalService::ACCOUNT_REFUND)->sum('debet'),
            'tanggal' => optional($journal_lines->first())->tanggal ?? date('Y-m-d'),
            'alasan' => optional($journal_lines->first())->catatan ?? 'Refund',
        ];

        return view('bcl.transaksi.cetak_refund', compact('transaksi', 'refund', 'journal_lines'));
    }

    public function destroy(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $transaksi = tr_renter::where('trans_id', $request->id)->firstOrFail();
                $extras = tb_extra_rent::where('parent_trans', $transaksi->trans_id)->get();

                $lines = Fin_jurnal::whereIn('doc_id', $extras->pluck('kode')->push($transaksi->trans_id))->get();
                $this->rental->revertDepositLines($lines);
                Fin_jurnal::whereIn('id', $lines->pluck('id'))->delete();

                ExtraBedAssignment::whereIn('extra_rent_id', $extras->pluck('id'))->delete();
                tb_extra_rent::whereIn('id', $extras->pluck('id'))->delete();

                if ($transaksi->bukti_transfer) {
                    Storage::disk('public')->delete($transaksi->bukti_transfer);
                }
                $this->rental->audit('delete_transaction', $transaksi->trans_id, 'Hapus transaksi sewa', [
                    'transaksi' => $transaksi->only(['id_renter', 'room_id', 'tgl_mulai', 'tgl_selesai', 'harga', 'catatan']),
                    'extras' => $extras->pluck('kode'),
                    'jurnal' => $lines->map->only(['no_jurnal', 'doc_id', 'kode_akun', 'debet', 'kredit', 'identity'])->values(),
                ]);
                $transaksi->delete();
            });

            return back()->with('success', 'Transaksi berhasil dihapus');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function refund(Request $request)
    {
        $request->merge(['nominal_refund' => RentalService::amount($request->nominal_refund)]);
        if ($error = $this->validationError($request, [
            'kode_trans' => 'required|exists:bcl_tr_renter,trans_id',
            'tgl_refund' => 'required|date',
            'tgl_keluar' => 'nullable|date',
            'nominal_refund' => 'required|numeric|min:0',
        ])) {
            return back()->with('error', $error);
        }

        if ((float) $request->nominal_refund > 0 && Gate::denies('bcl-supervise')) {
            return back()->with('error', 'Refund uang sewa hanya bisa dilakukan supervisor.');
        }

        try {
            DB::transaction(function () use ($request) {
                $transaksi = tr_renter::where('trans_id', $request->kode_trans)->lockForUpdate()->firstOrFail();
                $paid = (float) ($this->rental->paidByDoc([$transaksi->trans_id])[$transaksi->trans_id] ?? 0);
                $nominal = (float) $request->nominal_refund;
                if ($nominal > $paid) {
                    throw new \RuntimeException('Nominal refund melebihi jumlah yang sudah dibayar (Rp ' . number_format($paid, 0, ',', '.') . ')');
                }

                $keluar = Carbon::parse($request->tgl_keluar ?: $request->tgl_refund)->format('Y-m-d');
                if ($keluar < $transaksi->tgl_selesai) {
                    $transaksi->update(['tgl_selesai' => max($keluar, $transaksi->tgl_mulai)]);
                }
                // Leaving was declared, so the room is free once the (new) end date passes.
                $this->rental->markCheckedOut($transaksi);

                if ($nominal > 0) {
                    $renter = renter::findOrFail($transaksi->id_renter);
                    $noExp = $this->rental->nextNoExp();
                    $noJurnal = $this->rental->nextNoJurnal();
                    $catatan = 'Refund sewa ' . $transaksi->trans_id . ' kepada ' . $renter->nama . ($request->alasan ? ', alasan: ' . $request->alasan : '');
                    $this->rental->journal(['no_jurnal' => $noJurnal, 'tanggal' => $request->tgl_refund, 'kode_akun' => RentalService::ACCOUNT_CASH, 'kredit' => $nominal, 'catatan' => $catatan, 'doc_id' => $noExp, 'identity' => 'Refund', 'pos' => 'K', 'metode_bayar' => $request->metode_bayar ?: 'Tunai']);
                    $this->rental->audit('refund', $transaksi->trans_id, $catatan, ['nominal' => $nominal, 'doc' => $noExp]);
                    $this->rental->journal(['no_jurnal' => $noJurnal, 'tanggal' => $request->tgl_refund, 'kode_akun' => RentalService::ACCOUNT_REFUND, 'debet' => $nominal, 'catatan' => $catatan, 'doc_id' => $noExp, 'identity' => 'Refund', 'pos' => 'D']);
                }
            });

            return back()->with('success', 'Refund berhasil dilakukan');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function reschedule(Request $request)
    {
        if ($error = $this->validationError($request, [
            'trans_id' => 'required|exists:bcl_tr_renter,trans_id',
            'tgl_rencana_masuk' => 'required|date',
        ])) {
            return back()->with('error', $error);
        }

        $transaksi = tr_renter::where('trans_id', $request->trans_id)->firstOrFail();
        if ($transaksi->identity === 'Pindah Kamar') {
            return back()->with('error', 'Transaksi pindah kamar tidak bisa di-reschedule. Reschedule transaksi sewa awalnya.');
        }

        $start = Carbon::parse($request->tgl_rencana_masuk)->startOfDay();
        $end = $this->rental->packageEnd($start, (int) $transaksi->lama_sewa, $transaksi->jangka_sewa, (int) $transaksi->free_sewa, $transaksi->free_jangka);

        if ($this->rental->roomHasOverlap((int) $transaksi->room_id, $start, $end, $transaksi->trans_id)) {
            return back()->with('error', 'Kamar sudah terisi atau dibooking pada rentang ' . $start->format('Y-m-d') . ' s/d ' . $end->format('Y-m-d'));
        }

        $transaksi->update([
            'tgl_mulai' => $start->format('Y-m-d'),
            'tgl_selesai' => $end->format('Y-m-d'),
        ]);

        return back()->with('success', 'Tanggal masuk berhasil diubah');
    }

    public function sewa(Request $request)
    {
        $request->merge([
            'nominal' => RentalService::amount($request->nominal),
            'deposit_amount' => RentalService::amount($request->deposit_amount),
            'jaminan' => RentalService::amount($request->jaminan),
            'diskon' => RentalService::amount($request->diskon),
            'harga_custom' => RentalService::amount($request->harga_custom),
        ]);
        $custom = $request->pricelist === 'custom';
        if ($error = $this->validationError($request, [
            'renter' => 'required|exists:bcl_renter,id',
            'kamar' => 'required|exists:bcl_rooms,id',
            'pricelist' => $custom ? 'required' : 'required|exists:bcl_pricelist,id',
            'tgl_selesai_custom' => $custom ? 'required|date|after:tgl_masuk' : 'nullable',
            'harga_custom' => $custom ? 'required|numeric|min:0' : 'nullable',
            'tgl_masuk' => 'required|date',
            'tgl_bayar' => 'required|date',
            'nominal' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'jaminan' => 'nullable|numeric|min:0',
            'diskon' => 'nullable|numeric|min:0',
            'alasan_diskon' => 'nullable|string|max:255',
            'metode_bayar' => 'nullable|in:' . implode(',', RentalService::METHODS),
            'bukti_transfer' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ])) {
            return back()->withInput()->with('error', $error);
        }

        $renter = renter::findOrFail($request->renter);
        $kamar = Rooms::findOrFail($request->kamar);
        $pl = $custom ? null : Pricelist::findOrFail($request->pricelist);
        $cashIn = (float) $request->nominal + (float) $request->jaminan;
        $method = $request->metode_bayar ?: 'Tunai';
        if ($cashIn > 0 && RentalService::methodNeedsProof($method) && !$request->hasFile('bukti_transfer')) {
            return back()->withInput()->with('error', 'Bukti ' . $method . ' wajib dilampirkan (bisa ditempel dengan Ctrl+V).');
        }

        $proofPath = null;
        try {
            $trx = DB::transaction(function () use ($request, $renter, $kamar, $pl, $method, $custom, &$proofPath) {
                $proofPath = $this->rental->storeProof($request->file('bukti_transfer'), 'bukti-sewa');

                return $this->createRental($renter, $kamar, $pl, Carbon::parse($request->tgl_masuk), [
                    'catatan' => (string) $request->catatan,
                    'tgl_bayar' => $request->tgl_bayar,
                    'nominal' => (float) $request->nominal,
                    'use_deposit' => $request->boolean('use_deposit'),
                    'deposit_amount' => (float) $request->deposit_amount,
                    'overpay_to_deposit' => $request->boolean('overpay_to_deposit'),
                    'jaminan' => (float) $request->jaminan,
                    'diskon' => (float) $request->diskon,
                    'alasan_diskon' => (string) $request->alasan_diskon,
                    'custom_end' => $custom ? $request->tgl_selesai_custom : null,
                    'custom_price' => $custom ? (float) $request->harga_custom : null,
                    'metode_bayar' => $method,
                    'bukti' => $proofPath,
                ]);
            });
        } catch (\Throwable $th) {
            if ($proofPath) {
                Storage::disk('public')->delete($proofPath);
            }

            return back()->withInput()->with('error', $th->getMessage());
        }

        return back()->with('success', Carbon::parse($trx->tgl_mulai)->isFuture() ? 'Antrian booking ' . $trx->trans_id . ' berhasil dibuat' : 'Kamar ' . $kamar->room_name . ' berhasil disewa (' . $trx->trans_id . ')');
    }

    /**
     * Rentals that need a renewal decision: ending within $days (or already overdue) and not yet extended.
     */
    public function renewals(Request $request)
    {
        $days = max(1, min((int) $request->query('days', 14), 60));
        $today = Carbon::today();
        $limit = $today->copy()->addDays($days)->format('Y-m-d');

        $active = tr_renter::with(['room.category', 'renter'])
            ->whereNull('cancelled_at')
            ->whereDate('tgl_mulai', '<=', $today->format('Y-m-d'))
            ->whereDate('tgl_selesai', '>', $today->format('Y-m-d'))
            ->whereDate('tgl_selesai', '<=', $limit)
            ->get();
        $candidates = $active->concat($this->rental->overstaysByRoom()->values()->load('room.category'))
            ->unique('id')
            ->filter(fn ($trx) => !tr_renter::where('id_renter', $trx->id_renter)->where('tgl_mulai', $trx->tgl_selesai)->whereNull('cancelled_at')->exists());
        $prices = Pricelist::whereIn('room_category', $candidates->pluck('room.room_category')->filter()->unique())
            ->orderBy('price')->get()->groupBy('room_category');
        $paid = $this->rental->paidByDoc($candidates->pluck('trans_id'));

        return response()->json(['items' => $candidates->sortBy('tgl_selesai')->values()->map(function ($trx) use ($prices, $paid, $today) {
            $options = ($prices[optional($trx->room)->room_category] ?? collect())->map(fn ($pl) => [
                'id' => $pl->id,
                'label' => $pl->jangka_waktu . ' ' . $pl->jangka_sewa . ((int) $pl->bonus_waktu > 0 ? ' + bonus ' . $pl->bonus_waktu . ' ' . $pl->bonus_sewa : ''),
                'price' => (float) $pl->price,
                'same' => (int) $pl->jangka_waktu === (int) $trx->lama_sewa && strtolower($pl->jangka_sewa) === strtolower((string) $trx->jangka_sewa),
            ])->values();

            return [
                'trans_id' => $trx->trans_id,
                'room' => optional($trx->room)->room_name,
                'category' => optional(optional($trx->room)->category)->category_name,
                'renter' => optional($trx->renter)->nama,
                'phone' => optional($trx->renter)->phone,
                'tgl_selesai' => $trx->tgl_selesai,
                'days_left' => (int) $today->diffInDays(Carbon::parse($trx->tgl_selesai), false),
                'kurang' => $paid->has($trx->trans_id) ? max((float) $trx->harga - $paid[$trx->trans_id], 0) : 0,
                'options' => $options,
            ];
        })]);
    }

    /** Extend several rentals at once; each renewal starts when the current one ends and is billed (unpaid). */
    public function bulkRenew(Request $request)
    {
        $items = collect($request->input('items', []))->filter(fn ($i) => !empty($i['trans_id']) && !empty($i['pricelist']));
        if ($items->isEmpty()) {
            return response()->json(['message' => 'Pilih minimal satu kamar untuk diperpanjang.'], 422);
        }

        $done = [];
        $failed = [];
        foreach ($items as $item) {
            try {
                $trx = DB::transaction(function () use ($item) {
                    $current = tr_renter::with(['room', 'renter'])->where('trans_id', $item['trans_id'])->whereNull('cancelled_at')->firstOrFail();
                    if (tr_renter::where('id_renter', $current->id_renter)->where('tgl_mulai', $current->tgl_selesai)->whereNull('cancelled_at')->exists()) {
                        throw new \RuntimeException('sudah diperpanjang');
                    }
                    $pl = Pricelist::findOrFail($item['pricelist']);

                    return $this->createRental($current->renter, $current->room, $pl, Carbon::parse($current->tgl_selesai), [
                        'catatan' => 'Perpanjangan ' . $current->trans_id,
                        'tgl_bayar' => date('Y-m-d'),
                        'nominal' => 0,
                    ]);
                });
                $done[] = $trx->trans_id;
            } catch (\Throwable $th) {
                $failed[] = $item['trans_id'] . ': ' . $th->getMessage();
            }
        }

        $message = count($done) . ' sewa diperpanjang (tagihan masuk Belum Lunas).' . ($failed ? ' Gagal: ' . implode('; ', $failed) : '');

        return response()->json(['message' => $message, 'done' => $done, 'failed' => $failed], $done ? 200 : 422);
    }

    /**
     * Create a rental with its first payment. Shared by the rent form and bulk renewal.
     * Options: catatan, tgl_bayar, nominal (cash/transfer), use_deposit, deposit_amount, overpay_to_deposit,
     * jaminan (security deposit received now), metode_bayar, bukti.
     */
    protected function createRental(renter $renter, Rooms $kamar, ?Pricelist $pl, Carbon $start, array $opt): tr_renter
    {
        if ($pl && (int) $pl->room_category !== (int) $kamar->room_category) {
            throw new \RuntimeException('Harga yang dipilih bukan untuk tipe kamar ' . $kamar->room_name);
        }

        $start = $start->copy()->startOfDay();
        // Custom duration (no package): explicit end date, price suggested from packages + daily rate.
        $suggestion = null;
        if (!$pl) {
            $end = Carbon::parse($opt['custom_end'] ?? null)->startOfDay();
            if ($end->lte($start) || $start->diffInDays($end) > 1100) {
                throw new \RuntimeException('Tanggal selesai durasi custom tidak valid.');
            }
            $suggestion = $this->rental->suggestPrice($kamar->room_category, $start, $end);
        } else {
            $end = $this->rental->packageEnd($start, (int) $pl->jangka_waktu, $pl->jangka_sewa, (int) $pl->bonus_waktu, $pl->bonus_sewa);
        }
        if ($this->rental->roomHasOverlap((int) $kamar->id, $start, $end)) {
            throw new \RuntimeException('Kamar ' . $kamar->room_name . ' sudah memiliki sewa atau antrian booking pada rentang ' . $start->format('Y-m-d') . ' s/d ' . $end->format('Y-m-d') . '.');
        }
        $this->assertNoOverstay($kamar, $renter->id);

        // Special price for this tenant: the package price minus a discount (reason required, audited).
        $listPrice = $pl ? (float) $pl->price : $suggestion['total'];
        // A custom price below the suggestion is a discount (reason required); above it is simply recorded.
        $discount = $pl ? (float) ($opt['diskon'] ?? 0) : max($listPrice - (float) $opt['custom_price'], 0);
        if ($discount > 0 && trim((string) ($opt['alasan_diskon'] ?? '')) === '') {
            throw new \RuntimeException($pl ? 'Isi alasan diskon.' : 'Harga di bawah harga saran Rp ' . number_format($listPrice, 0, ',', '.') . ': isi alasannya.');
        }
        if ($discount > $listPrice) {
            throw new \RuntimeException('Diskon melebihi harga paket Rp ' . number_format($listPrice, 0, ',', '.') . '.');
        }
        $price = $pl ? $listPrice - $discount : (float) $opt['custom_price'];
        $nominal = (float) ($opt['nominal'] ?? 0);
        $depositUse = !empty($opt['use_deposit'])
            ? min(($opt['deposit_amount'] ?? 0) > 0 ? (float) $opt['deposit_amount'] : (float) $renter->deposit_balance, (float) $renter->deposit_balance, $price)
            : 0;
        $cashRevenue = min($nominal, max($price - $depositUse, 0));
        $over = $nominal - $cashRevenue;
        if ($over > 0 && empty($opt['overpay_to_deposit'])) {
            throw new \RuntimeException('Pembayaran melebihi harga paket Rp ' . number_format($price, 0, ',', '.') . '. Centang "Simpan kelebihan ke deposit" atau kurangi nominal.');
        }

        $noTrans = $this->rental->nextTransId();
        $trx = tr_renter::create([
            'trans_id' => $noTrans,
            'identity' => 'Baru',
            'id_renter' => $renter->id,
            'tanggal' => date('Y-m-d'),
            'tgl_mulai' => $start->format('Y-m-d'),
            'tgl_selesai' => $end->format('Y-m-d'),
            'room_id' => $kamar->id,
            'lama_sewa' => $pl ? $pl->jangka_waktu : $suggestion['days'],
            'jangka_sewa' => $pl ? $pl->jangka_sewa : 'Hari',
            'harga' => $price,
            'free_sewa' => $pl ? $pl->bonus_waktu : 0,
            'free_jangka' => $pl ? $pl->bonus_sewa : null,
            'catatan' => trim((string) ($opt['catatan'] ?? '')
                . ($pl ? '' : ' [Durasi custom ' . $suggestion['days'] . ' hari; harga saran Rp ' . number_format($listPrice, 0, ',', '.') . ' = ' . implode(' + ', array_column($suggestion['parts'], 'label')) . ']')
                . ($discount > 0 ? ' [Harga ' . ($pl ? 'paket' : 'saran') . ' Rp ' . number_format($listPrice, 0, ',', '.') . ', diskon Rp ' . number_format($discount, 0, ',', '.') . ': ' . $opt['alasan_diskon'] . ']' : '')),
            'bukti_transfer' => $opt['bukti'] ?? null,
        ]);

        $base = ['no_jurnal' => $this->rental->nextNoJurnal(), 'tanggal' => $opt['tgl_bayar'] ?? date('Y-m-d'), 'doc_id' => $noTrans];
        $cash = ['metode_bayar' => $opt['metode_bayar'] ?? null, 'bukti' => $opt['bukti'] ?? null];
        $catatan = 'Pendapatan Sewa Kamar ' . $kamar->room_name . ' dari ' . $renter->nama;

        // The revenue line always exists so the transaction is tracked as receivable.
        $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_REVENUE, 'kredit' => $cashRevenue, 'kode_subledger' => $renter->id, 'catatan' => $catatan, 'identity' => 'Sewa Kamar', 'pos' => 'K']);
        if ($cashRevenue > 0) {
            $this->rental->journal($base + $cash + ['kode_akun' => RentalService::ACCOUNT_CASH, 'debet' => $cashRevenue, 'catatan' => $catatan, 'identity' => 'Sewa Kamar', 'pos' => 'D']);
        }
        if ($depositUse > 0) {
            $note = 'Pemakaian deposit untuk sewa ' . $noTrans . ' oleh ' . $renter->nama;
            $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_REVENUE, 'kredit' => $depositUse, 'kode_subledger' => $renter->id, 'catatan' => $note, 'identity' => 'Pemakaian Deposit', 'pos' => 'K']);
            $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_DEPOSIT, 'debet' => $depositUse, 'kode_subledger' => $renter->id, 'catatan' => $note, 'identity' => 'Pemakaian Deposit', 'pos' => 'D']);
            $renter->debitDeposit($depositUse);
        }
        if ($over > 0) {
            $note = 'Kelebihan pembayaran sewa ' . $noTrans . ' disimpan sebagai deposit ' . $renter->nama;
            $this->rental->journal($base + $cash + ['kode_akun' => RentalService::ACCOUNT_CASH, 'debet' => $over, 'catatan' => $note, 'identity' => 'Topup Deposit (Overpay)', 'pos' => 'D']);
            $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_DEPOSIT, 'kredit' => $over, 'kode_subledger' => $renter->id, 'catatan' => $note, 'identity' => 'Topup Deposit (Overpay)', 'pos' => 'K']);
            $renter->creditDeposit($over);
        }
        $jaminan = (float) ($opt['jaminan'] ?? 0);
        if ($jaminan > 0) {
            $note = 'Uang jaminan sewa ' . $noTrans . ' dari ' . $renter->nama;
            $this->rental->journal($base + $cash + ['kode_akun' => RentalService::ACCOUNT_CASH, 'debet' => $jaminan, 'catatan' => $note, 'identity' => 'Uang Jaminan', 'pos' => 'D']);
            $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_GUARANTEE, 'kredit' => $jaminan, 'kode_subledger' => $renter->id, 'catatan' => $note, 'identity' => 'Uang Jaminan', 'pos' => 'K']);
        }

        if ($discount > 0) {
            $this->rental->audit('discount', $noTrans, ($pl ? 'Diskon sewa ' : 'Harga custom di bawah saran, sewa ') . $kamar->room_name . ' untuk ' . $renter->nama . ': ' . $opt['alasan_diskon'], [
                'harga_paket' => $listPrice, 'diskon' => $discount, 'harga' => $price,
            ]);
        }

        return $trx;
    }

    public function changeRoom(Request $request)
    {
        $request->merge(['pay_now' => RentalService::amount($request->pay_now)]);
        if ($error = $this->validationError($request, [
            'trans_id' => 'required|exists:bcl_tr_renter,trans_id',
            'new_room_id' => 'required|exists:bcl_rooms,id',
            'effective_date' => 'required|date',
            'payment_date' => 'nullable|date',
            'pay_now' => 'nullable|numeric|min:0',
        ])) {
            return back()->with('error', $error);
        }

        $refundDoc = null;
        $refundAmount = 0;
        $remainingDue = 0;

        try {
            DB::transaction(function () use ($request, &$refundDoc, &$refundAmount, &$remainingDue) {
                $current = tr_renter::with('room')->where('trans_id', $request->trans_id)->lockForUpdate()->firstOrFail();
                $calc = $this->changeRoomCalculation($current, (int) $request->new_room_id, $request->effective_date);
                $effective = $calc['effective'];
                $end = Carbon::parse($current->tgl_selesai);
                $oldRoom = $current->room;
                $newRoom = $calc['new_room'];
                $renter = renter::findOrFail($current->id_renter);
                $paymentDate = $request->payment_date ?: date('Y-m-d');

                if ($this->rental->roomHasOverlap((int) $newRoom->id, $effective, $end)) {
                    throw new \RuntimeException('Kamar ' . $newRoom->room_name . ' sudah terisi atau dibooking pada ' . $effective->format('Y-m-d') . ' s/d ' . $end->format('Y-m-d'));
                }

                $this->assertNoOverstay($newRoom, $current->id_renter);

                $payable = $calc['payable'];
                $current->update(['tgl_selesai' => $effective->format('Y-m-d')]);
                $this->rental->markCheckedOut($current);

                $newTransId = $this->rental->nextTransId();
                $label = ($oldRoom->room_name ?? $current->room_id) . ' -> ' . $newRoom->room_name;
                tr_renter::create([
                    'trans_id' => $newTransId,
                    'identity' => 'Pindah Kamar',
                    'id_renter' => $current->id_renter,
                    'tanggal' => date('Y-m-d'),
                    'tgl_mulai' => $effective->format('Y-m-d'),
                    'tgl_selesai' => $end->format('Y-m-d'),
                    'room_id' => $newRoom->id,
                    // The package is kept so a later room change finds the same pricelist.
                    'lama_sewa' => $current->lama_sewa,
                    'jangka_sewa' => $current->jangka_sewa,
                    'free_sewa' => $current->free_sewa,
                    'free_jangka' => $current->free_jangka,
                    // harga = amount still to be collected on this record (upgrade difference only).
                    'harga' => max($payable, 0),
                    'catatan' => $request->catatan ?: ('Pindah kamar: ' . $label . ' (dari ' . $current->trans_id . ')'),
                ]);

                $detail = 'Selisih paket Rp ' . number_format($calc['diff_full'], 0, ',', '.') . ' x ' . number_format($calc['remaining_percent'] * 100, 1) . '% sisa';

                if ($payable > 0) {
                    $payNow = min((float) $request->pay_now, $payable);
                    $remainingDue = $payable - $payNow;
                    $noJurnal = $this->rental->nextNoJurnal();
                    $catatan = 'Upgrade kamar ' . $label . '. ' . $detail . ' = Rp ' . number_format($payable, 0, ',', '.');
                    $base = ['no_jurnal' => $noJurnal, 'tanggal' => $paymentDate, 'doc_id' => $newTransId, 'identity' => 'Upgrade Kamar', 'catatan' => $catatan];
                    $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_REVENUE, 'kredit' => $payNow, 'kode_subledger' => $renter->id, 'pos' => 'K']);
                    if ($payNow > 0) {
                        $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_CASH, 'debet' => $payNow, 'pos' => 'D', 'metode_bayar' => $request->metode_bayar ?: 'Tunai']);
                    }
                } elseif ($payable < 0 && $calc['refundable'] > 0) {
                    $refundAmount = $calc['refundable'];
                    $refundDoc = $this->rental->nextNoExp();
                    $noJurnal = $this->rental->nextNoJurnal();
                    $toDeposit = $request->boolean('refund_to_deposit');
                    $catatan = 'Refund downgrade kamar ' . $label . '. ' . $detail . ' = Rp ' . number_format($refundAmount, 0, ',', '.') . ($toDeposit ? ' (masuk deposit)' : '');
                    $base = ['no_jurnal' => $noJurnal, 'tanggal' => $paymentDate, 'doc_id' => $refundDoc, 'catatan' => $catatan];

                    $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_REFUND, 'debet' => $refundAmount, 'identity' => 'Downgrade Kamar', 'pos' => 'D']);
                    if ($toDeposit) {
                        $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_DEPOSIT, 'kredit' => $refundAmount, 'kode_subledger' => $renter->id, 'identity' => 'Topup Deposit (Downgrade)', 'pos' => 'K']);
                        $renter->creditDeposit($refundAmount);
                        $refundDoc = null;
                    } else {
                        $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_CASH, 'kredit' => $refundAmount, 'identity' => 'Downgrade Kamar', 'pos' => 'K']);
                    }
                }
            });
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }

        if ($refundDoc) {
            $current = tr_renter::where('trans_id', $request->trans_id)->first();

            return redirect()->route('bcl.transaksi.cetak_refund', ['doc_id' => $refundDoc, 'renter_id' => $current->id_renter])->with([
                'refund_payload' => (object) [
                    'doc_id' => $refundDoc,
                    'amount' => $refundAmount,
                    'tanggal' => $request->payment_date ?: date('Y-m-d'),
                    'alasan' => $request->alasan ?: 'Downgrade kamar',
                ],
                'transaksi_id' => $current->trans_id,
            ]);
        }

        if ($remainingDue > 0) {
            return back()->with('success', 'Pindah kamar berhasil. Sisa tagihan upgrade Rp ' . number_format($remainingDue, 0, ',', '.') . ' tercatat di transaksi belum lunas.');
        }

        return back()->with('success', 'Pindah kamar berhasil dilakukan');
    }

    /**
     * Rooms the renter can move to, priced with the same package as the current transaction,
     * plus the numbers the modal needs to preview the charge or refund.
     */
    public function changeRoomOptions($id)
    {
        $trx = tr_renter::with('room')->where('trans_id', $id)->first();
        if (!$trx) {
            return response()->json(['error' => 'Transaksi tidak ditemukan'], 404);
        }

        $priceOf = [];
        $price = function ($category) use ($trx, &$priceOf) {
            return $priceOf[$category] ??= $this->packagePrice($trx, $category);
        };
        $from = max(Carbon::today()->format('Y-m-d'), $trx->tgl_mulai);
        $paid = (float) ($this->rental->paidByDoc([$trx->trans_id])[$trx->trans_id] ?? 0);

        $overstays = $this->rental->overstaysByRoom();
        $rooms = Rooms::with('category')->orderedForMapping()->get()->map(function ($room) use ($price, $trx, $from, $overstays) {
            $roomPrice = $price($room->room_category);
            if ($roomPrice <= 0) {
                return null;
            }

            return [
                'room' => ['id' => $room->id, 'name' => $room->room_name, 'category_name' => optional($room->category)->category_name],
                'price' => $roomPrice,
                'is_occupied' => $room->id !== $trx->room_id && ($this->rental->roomHasOverlap($room->id, $from, $trx->tgl_selesai)
                    || ($overstays->has($room->id) && $overstays[$room->id]->id_renter != $trx->id_renter)),
                'is_current_room' => $room->id === $trx->room_id,
            ];
        })->filter()->values();

        $currentPrice = $price(optional($trx->room)->room_category) ?: null;

        return response()->json([
            'current' => [
                'trans_id' => $trx->trans_id,
                'identity' => $trx->identity,
                'room_id' => $trx->room_id,
                'lama_sewa' => (int) $trx->lama_sewa,
                'jangka_sewa' => $trx->jangka_sewa,
                'harga' => (float) $trx->harga,
                'paid' => $paid,
                'old_price' => $currentPrice !== null ? (float) $currentPrice : null,
                'package_start' => $this->rental->packageStart($trx)->format('Y-m-d'),
                'tgl_mulai' => $trx->tgl_mulai,
                'tgl_selesai' => $trx->tgl_selesai,
            ],
            'rooms' => $rooms,
        ]);
    }

    /**
     * Proses keluar: one step for a tenant leaving, early or after the lease ended.
     * - tgl_keluar before the end date shortens the lease (optional refund, supervisor only);
     * - tgl_keluar after the end date can bill the extra days (Tambahan Sewa "Tinggal lewat masa sewa");
     * - optional check-out inspection and security deposit settlement (return / deduct / move to balance).
     */
    public function checkout(Request $request)
    {
        $request->merge([
            'refund' => RentalService::amount($request->refund),
            'overstay_rate' => RentalService::amount($request->overstay_rate),
            'jaminan_kembali' => RentalService::amount($request->jaminan_kembali),
            'jaminan_potong' => RentalService::amount($request->jaminan_potong),
        ]);
        if ($error = $this->validationError($request, [
            'trans_id' => 'required|exists:bcl_tr_renter,trans_id',
            'tgl_keluar' => 'nullable|date',
            'refund' => 'nullable|numeric|min:0',
            'overstay_rate' => 'nullable|numeric|min:0',
            'jaminan_kembali' => 'nullable|numeric|min:0',
            'jaminan_potong' => 'nullable|numeric|min:0',
            'metode_bayar' => 'nullable|in:' . implode(',', RentalService::METHODS),
            'foto' => 'nullable|image|max:8192',
        ])) {
            return $this->respondJson($request, $error, 422);
        }

        $trx = tr_renter::with(['room', 'renter'])->where('trans_id', $request->trans_id)->whereNull('cancelled_at')->firstOrFail();
        $today = Carbon::today();
        $keluar = Carbon::parse($request->tgl_keluar ?: $today)->startOfDay();
        $end = Carbon::parse($trx->tgl_selesai)->startOfDay();
        if ($keluar->lt(Carbon::parse($trx->tgl_mulai))) {
            return $this->respondJson($request, 'Tanggal keluar tidak boleh sebelum tanggal masuk (' . $trx->tgl_mulai . ').', 422);
        }
        if ((float) $request->refund > 0 && Gate::denies('bcl-supervise')) {
            return $this->respondJson($request, 'Refund uang sewa hanya bisa dilakukan supervisor.', 403);
        }

        $photo = null;
        try {
            $summary = DB::transaction(function () use ($request, $trx, $keluar, $end, &$photo) {
                $notes = [];
                $renter = $trx->renter;
                $method = $request->metode_bayar ?: 'Tunai';

                if ($keluar->lt($end)) {
                    $trx->update(['tgl_selesai' => $keluar->format('Y-m-d')]);
                    $notes[] = 'sewa diakhiri ' . $keluar->format('Y-m-d');
                    $refund = (float) $request->refund;
                    if ($refund > 0) {
                        $paid = (float) ($this->rental->paidByDoc([$trx->trans_id])[$trx->trans_id] ?? 0);
                        if ($refund > $paid) {
                            throw new \RuntimeException('Refund melebihi yang sudah dibayar (Rp ' . number_format($paid, 0, ',', '.') . ').');
                        }
                        $base = ['no_jurnal' => $this->rental->nextNoJurnal(), 'tanggal' => date('Y-m-d'), 'doc_id' => $this->rental->nextNoExp(), 'identity' => 'Refund',
                            'catatan' => 'Refund sewa ' . $trx->trans_id . ' kepada ' . $renter->nama . ($request->alasan ? ', alasan: ' . $request->alasan : '')];
                        $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_CASH, 'kredit' => $refund, 'pos' => 'K', 'metode_bayar' => $method]);
                        $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_REFUND, 'debet' => $refund, 'pos' => 'D']);
                        $notes[] = 'refund Rp ' . number_format($refund, 0, ',', '.');
                    }
                } elseif ($keluar->gt($end) && $request->boolean('tagih_overstay') && (float) $request->overstay_rate > 0) {
                    $days = (int) $end->diffInDays($keluar);
                    tb_extra_rent::create([
                        'kode' => $this->rental->nextExtraKode(),
                        'parent_trans' => $trx->trans_id,
                        'nama' => 'Tinggal lewat masa sewa',
                        'qty' => 1,
                        'lama_sewa' => $days,
                        'jangka_sewa' => 'Hari',
                        'tgl_mulai' => $end->format('Y-m-d'),
                        'tgl_selesai' => $keluar->format('Y-m-d'),
                        'harga' => (float) $request->overstay_rate,
                    ]);
                    $notes[] = 'tagihan lewat masa sewa ' . $days . ' hari x Rp ' . number_format((float) $request->overstay_rate, 0, ',', '.');
                }
                $this->rental->markCheckedOut($trx);

                if ($request->filled('inspection_items') || $request->filled('catatan_kamar') || $request->hasFile('foto') || $request->filled('jumlah_kunci')) {
                    $photo = $this->storeInspectionPhoto($request->file('foto'), $trx->trans_id);
                    $this->saveInspection($trx, 'checkout', $keluar->format('Y-m-d'), $request, $photo);
                    $notes[] = 'checklist keluar dicatat';
                }

                $notes = array_merge($notes, $this->rental->settleGuarantee($renter, (float) $request->jaminan_kembali, (float) $request->jaminan_potong, $request->boolean('jaminan_ke_saldo'), $method, (string) $request->alasan_potong));

                $this->rental->audit('checkout', $trx->trans_id, $renter->nama . ' keluar dari kamar ' . optional($trx->room)->room_name . ' tanggal ' . $keluar->format('Y-m-d'), ['notes' => $notes, 'tgl_selesai_lama' => $end->format('Y-m-d')]);

                return $notes;
            });
        } catch (\Throwable $th) {
            if ($photo) {
                Storage::disk('public')->delete($photo);
            }

            return $this->respondJson($request, $th->getMessage(), 422);
        }

        return $this->respondJson($request, optional($trx->renter)->nama . ' keluar dari kamar ' . optional($trx->room)->room_name . ($summary ? ' (' . implode(', ', $summary) . ')' : '') . '.');
    }

    /** Cancel a future booking: refund (cash or to deposit) any part of what was paid; the rest is forfeited. */
    public function cancelBooking(Request $request)
    {
        $request->merge(['refund' => RentalService::amount($request->refund)]);
        if ($error = $this->validationError($request, [
            'trans_id' => 'required|exists:bcl_tr_renter,trans_id',
            'refund' => 'nullable|numeric|min:0',
            'refund_mode' => 'nullable|in:tunai,deposit',
            'metode_bayar' => 'nullable|in:' . implode(',', RentalService::METHODS),
            'alasan' => 'required|string|max:255',
        ])) {
            return $this->respondJson($request, $error, 422);
        }

        try {
            $message = DB::transaction(function () use ($request) {
                $trx = tr_renter::with(['room', 'renter'])->where('trans_id', $request->trans_id)->lockForUpdate()->firstOrFail();
                if ($trx->cancelled_at) {
                    throw new \RuntimeException('Booking ini sudah dibatalkan.');
                }
                if ($trx->tgl_mulai <= Carbon::today()->format('Y-m-d')) {
                    throw new \RuntimeException('Sewa sudah berjalan. Gunakan "Proses Keluar" untuk mengakhiri sewa.');
                }
                $extras = tb_extra_rent::where('parent_trans', $trx->trans_id)->get();
                if ($this->rental->paidByDoc($extras->pluck('kode'))->sum() > 0) {
                    throw new \RuntimeException('Ada tambahan sewa yang sudah dibayar. Batalkan tambahan sewanya dulu.');
                }

                $paid = (float) ($this->rental->paidByDoc([$trx->trans_id])[$trx->trans_id] ?? 0);
                $refund = (float) $request->refund;
                if ($refund > $paid) {
                    throw new \RuntimeException('Refund melebihi yang sudah dibayar (Rp ' . number_format($paid, 0, ',', '.') . ').');
                }
                $renter = $trx->renter;
                if ($refund > 0) {
                    $base = ['no_jurnal' => $this->rental->nextNoJurnal(), 'tanggal' => date('Y-m-d'), 'doc_id' => $this->rental->nextNoExp(),
                        'catatan' => 'Pembatalan booking ' . $trx->trans_id . ' ' . $renter->nama . ': ' . $request->alasan];
                    $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_REFUND, 'debet' => $refund, 'pos' => 'D', 'identity' => 'Batal Booking']);
                    if ($request->refund_mode === 'deposit') {
                        $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_DEPOSIT, 'kredit' => $refund, 'pos' => 'K', 'kode_subledger' => $renter->id, 'identity' => 'Topup Deposit (Batal Booking)']);
                        $renter->creditDeposit($refund);
                    } else {
                        $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_CASH, 'kredit' => $refund, 'pos' => 'K', 'identity' => 'Batal Booking', 'metode_bayar' => $request->metode_bayar ?: 'Tunai']);
                    }
                }

                // Unpaid extras of the booking go away with it.
                ExtraBedAssignment::whereIn('extra_rent_id', $extras->pluck('id'))->delete();
                Fin_jurnal::whereIn('doc_id', $extras->pluck('kode'))->delete();
                tb_extra_rent::whereIn('id', $extras->pluck('id'))->delete();

                $old = $trx->only(['harga', 'tgl_selesai']);
                // Nothing is owed any more and the zero-length period frees the room.
                $trx->forceFill([
                    'harga' => $paid,
                    'tgl_selesai' => $trx->tgl_mulai,
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id(),
                    'checked_out_at' => now(),
                    'catatan' => trim($trx->catatan . ' [BATAL: ' . $request->alasan . ']'),
                ])->save();

                $forfeit = $paid - $refund;
                $this->rental->audit('cancel_booking', $trx->trans_id, 'Booking ' . optional($trx->room)->room_name . ' ' . $renter->nama . ' dibatalkan: ' . $request->alasan,
                    $old + ['paid' => $paid, 'refund' => $refund, 'refund_mode' => $request->refund_mode, 'hangus' => $forfeit]);

                return 'Booking ' . $trx->trans_id . ' dibatalkan.' . ($refund > 0 ? ' Refund Rp ' . number_format($refund, 0, ',', '.') . ($request->refund_mode === 'deposit' ? ' ke deposit.' : '.') : '') . ($forfeit > 0 ? ' DP hangus Rp ' . number_format($forfeit, 0, ',', '.') . '.' : '');
            });
        } catch (\Throwable $th) {
            return $this->respondJson($request, $th->getMessage(), 422);
        }

        return $this->respondJson($request, $message);
    }

    /** Correct the agreed price or notes of a rental (supervisor). */
    public function updateTransaction(Request $request)
    {
        $request->merge(['harga' => RentalService::amount($request->harga)]);
        if ($error = $this->validationError($request, [
            'trans_id' => 'required|exists:bcl_tr_renter,trans_id',
            'harga' => 'required|numeric|min:0',
            'catatan' => 'nullable|string|max:500',
            'alasan' => 'required|string|max:255',
        ])) {
            return $this->respondJson($request, $error, 422);
        }

        $trx = tr_renter::where('trans_id', $request->trans_id)->firstOrFail();
        $paid = (float) ($this->rental->paidByDoc([$trx->trans_id])[$trx->trans_id] ?? 0);
        if ((float) $request->harga < $paid) {
            return $this->respondJson($request, 'Harga tidak boleh lebih kecil dari yang sudah dibayar (Rp ' . number_format($paid, 0, ',', '.') . '). Hapus/koreksi pembayarannya dulu.', 422);
        }
        $old = $trx->only(['harga', 'catatan']);
        $trx->update(['harga' => $request->harga, 'catatan' => (string) $request->catatan]);
        $this->rental->audit('update_transaction', $trx->trans_id, 'Koreksi transaksi: ' . $request->alasan, ['old' => $old, 'new' => $trx->only(['harga', 'catatan'])]);

        return $this->respondJson($request, 'Transaksi ' . $trx->trans_id . ' diperbarui.');
    }

    /** Inspections of a rental plus the room's inventory (for the check-in / check-out checklist). */
    public function inspections($transId)
    {
        $trx = tr_renter::with('room')->where('trans_id', $transId)->firstOrFail();

        return response()->json([
            'inventory' => \App\Models\BCL\Inventory::where('assigned_to', $trx->room_id)->orderBy('name')->get(['inv_number', 'name']),
            'inspections' => RoomInspection::with('user:id,name')->where('trans_id', $transId)->orderBy('tanggal')->get()->map(fn ($i) => [
                'type' => $i->type,
                'tanggal' => optional($i->tanggal)->format('Y-m-d'),
                'items' => $i->items ?? [],
                'jumlah_kunci' => $i->jumlah_kunci,
                'meter_listrik' => $i->meter_listrik,
                'catatan' => $i->catatan,
                'foto' => $i->foto ? asset('storage/' . $i->foto) : null,
                'user' => optional($i->user)->name,
            ]),
            'jaminan' => $this->rental->guaranteeBalance($trx->id_renter),
        ]);
    }

    public function storeInspection(Request $request)
    {
        if ($error = $this->validationError($request, [
            'trans_id' => 'required|exists:bcl_tr_renter,trans_id',
            'tanggal' => 'required|date',
            'foto' => 'nullable|image|max:8192',
        ])) {
            return $this->respondJson($request, $error, 422);
        }
        $trx = tr_renter::where('trans_id', $request->trans_id)->firstOrFail();
        $photo = $this->storeInspectionPhoto($request->file('foto'), $trx->trans_id);
        $this->saveInspection($trx, 'checkin', $request->tanggal, $request, $photo);

        return $this->respondJson($request, 'Checklist check-in kamar dicatat.');
    }

    protected function storeInspectionPhoto($file, string $transId): ?string
    {
        return $file ? $file->storeAs('bcl/inspection', $transId . '-' . Str::random(10) . '.' . $file->getClientOriginalExtension(), 'public') : null;
    }

    protected function saveInspection(tr_renter $trx, string $type, string $date, Request $request, ?string $photo): RoomInspection
    {
        $items = collect(json_decode((string) $request->inspection_items, true) ?: [])
            ->map(fn ($i) => [
                'inv_number' => $i['inv_number'] ?? null,
                'name' => $i['name'] ?? null,
                'kondisi' => in_array($i['kondisi'] ?? '', ['Baik', 'Rusak', 'Hilang'], true) ? $i['kondisi'] : 'Baik',
                'catatan' => isset($i['catatan']) ? mb_substr((string) $i['catatan'], 0, 255) : null,
            ])->values()->all();

        return RoomInspection::create([
            'trans_id' => $trx->trans_id,
            'room_id' => $trx->room_id,
            'type' => $type,
            'tanggal' => $date,
            'items' => $items,
            'jumlah_kunci' => $request->filled('jumlah_kunci') ? (int) $request->jumlah_kunci : null,
            'meter_listrik' => $request->meter_listrik,
            'catatan' => $request->catatan_kamar,
            'foto' => $photo,
            'user_id' => auth()->id(),
        ]);
    }

    // ------------------------------------------------------------ helpers

    /** A room whose last tenant overstayed (not extended, not checked out) cannot be given to someone else. */
    protected function assertNoOverstay(Rooms $room, $renterId): void
    {
        $over = $this->rental->overstaysByRoom($room->id)->first();
        if ($over && (int) $over->id_renter !== (int) $renterId) {
            throw new \RuntimeException('Kamar ' . $room->room_name . ' masih dihuni ' . optional($over->renter)->nama . ' (masa sewa habis ' . $over->tgl_selesai . ', belum dikonfirmasi keluar). Perpanjang sewanya atau klik "Sudah Keluar" dulu.');
        }
    }

    protected function respondJson(Request $request, string $message, int $status = 200)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return back()->with($status < 300 ? 'success' : 'error', $message);
    }

    protected function changeRoomCalculation(tr_renter $current, int $newRoomId, $effectiveDate): array
    {
        $newRoom = Rooms::findOrFail($newRoomId);
        if ($newRoom->id === (int) $current->room_id) {
            throw new \RuntimeException('Kamar tujuan sama dengan kamar saat ini.');
        }

        $effective = Carbon::parse($effectiveDate)->startOfDay();
        if ($effective->lt(Carbon::parse($current->tgl_mulai)) || $effective->gte(Carbon::parse($current->tgl_selesai))) {
            throw new \RuntimeException('Tanggal pindah harus di dalam periode sewa (' . $current->tgl_mulai . ' s/d ' . $current->tgl_selesai . ').');
        }

        $lama = (int) $current->lama_sewa;
        $oldPrice = $this->packagePrice($current, optional($current->room)->room_category);
        $newPrice = $this->packagePrice($current, $newRoom->room_category);
        if ($oldPrice <= 0 || $newPrice <= 0) {
            throw new \RuntimeException('Harga untuk ' . ($oldPrice > 0 ? 'kamar baru' : 'kamar lama') . ' tidak bisa dihitung: Daftar Harga kategorinya kosong.');
        }

        $elapsed = min($lama, $this->rental->wholeUnitsBetween($this->rental->packageStart($current), $effective, $current->jangka_sewa));
        $remainingPercent = $lama > 0 ? ($lama - $elapsed) / $lama : 0;
        $diffFull = $newPrice - $oldPrice;
        $payable = round($diffFull * $remainingPercent);

        // A refund never exceeds what was paid on the original package.
        $refundable = 0;
        if ($payable < 0) {
            $refundable = abs($payable);
            if ($current->identity !== 'Pindah Kamar') {
                $paid = (float) ($this->rental->paidByDoc([$current->trans_id])[$current->trans_id] ?? 0);
                $refundable = min($refundable, $paid);
            }
        }

        return [
            'new_room' => $newRoom,
            'effective' => $effective,
            'diff_full' => $diffFull,
            'remaining_percent' => $remainingPercent,
            'payable' => $payable,
            'refundable' => $refundable,
        ];
    }

    /**
     * Full price of a rental's package for a room category: the price list entry with the same duration,
     * or (custom durations) the suggested price for the whole package period.
     */
    protected function packagePrice(tr_renter $trx, $category): float
    {
        if (!$category) {
            return 0;
        }
        $pl = Pricelist::where('room_category', $category)
            ->where('jangka_waktu', (int) $trx->lama_sewa)
            ->whereRaw('LOWER(jangka_sewa) = ?', [strtolower((string) $trx->jangka_sewa)])
            ->first();
        if ($pl) {
            return (float) $pl->price;
        }
        $start = $this->rental->packageStart($trx);
        $end = $this->rental->addUnits($start, (int) $trx->lama_sewa, $trx->jangka_sewa);

        return $this->rental->suggestPrice($category, $start, $end)['total'];
    }

    protected function loadTransaction(?string $transId, array $with = []): ?tr_renter
    {
        return tr_renter::with(array_merge(['renter', 'room'], $with))
            ->with(['jurnal' => function ($query) {
                $query->leftJoin('users', 'users.id', '=', 'bcl_fin_jurnal.user_id')
                    ->select('bcl_fin_jurnal.*', 'users.name')
                    ->orderBy('bcl_fin_jurnal.tanggal');
            }])
            ->where('trans_id', $transId)
            ->first();
    }

    protected function validationError(Request $request, array $rules): ?string
    {
        try {
            $this->validate($request, $rules);

            return null;
        } catch (ValidationException $e) {
            return collect($e->errors())->flatten()->first();
        }
    }

    /** Parse "Y-m-ds/dY-m-d" filters; defaults to the current year or month. */
    protected function dateRange(?string $filter, string $default = 'month'): array
    {
        if ($filter && str_contains($filter, 's/d')) {
            [$start, $end] = array_map('trim', explode('s/d', $filter, 2));

            return [Carbon::parse($start)->format('Y-m-d'), Carbon::parse($end)->format('Y-m-d')];
        }

        return $default === 'year'
            ? [now()->startOfYear()->format('Y-m-d'), now()->endOfYear()->format('Y-m-d')]
            : [now()->startOfMonth()->format('Y-m-d'), now()->endOfMonth()->format('Y-m-d')];
    }
}
