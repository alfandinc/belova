<?php

namespace App\Http\Controllers\BCL;

use App\Http\Controllers\Controller;
use App\Models\BCL\ExtraBedAssignment;
use App\Models\BCL\Fin_jurnal;
use App\Models\BCL\Pricelist;
use App\Models\BCL\renter;
use App\Models\BCL\Rooms;
use App\Models\BCL\tb_extra_rent;
use App\Models\BCL\tr_renter;
use App\Services\BCL\RentalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                    $this->rental->journal(['no_jurnal' => $noJurnal, 'tanggal' => $request->tgl_refund, 'kode_akun' => RentalService::ACCOUNT_CASH, 'kredit' => $nominal, 'catatan' => $catatan, 'doc_id' => $noExp, 'identity' => 'Refund', 'pos' => 'K']);
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
        ]);
        if ($error = $this->validationError($request, [
            'renter' => 'required|exists:bcl_renter,id',
            'kamar' => 'required|exists:bcl_rooms,id',
            'pricelist' => 'required|exists:bcl_pricelist,id',
            'tgl_masuk' => 'required|date',
            'tgl_bayar' => 'required|date',
            'nominal' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'bukti_transfer' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ])) {
            return back()->withInput()->with('error', $error);
        }

        $renter = renter::findOrFail($request->renter);
        $kamar = Rooms::findOrFail($request->kamar);
        $pl = Pricelist::findOrFail($request->pricelist);
        if ((int) $pl->room_category !== (int) $kamar->room_category) {
            return back()->withInput()->with('error', 'Harga yang dipilih bukan untuk tipe kamar ' . $kamar->room_name);
        }

        $price = (float) $pl->price;
        $nominal = (float) $request->nominal;
        $depositUse = $request->boolean('use_deposit')
            ? min($request->deposit_amount > 0 ? (float) $request->deposit_amount : (float) $renter->deposit_balance, (float) $renter->deposit_balance, $price)
            : 0;
        $cashRevenue = min($nominal, max($price - $depositUse, 0));
        $over = $nominal - $cashRevenue;

        if ($over > 0 && !$request->boolean('overpay_to_deposit')) {
            return back()->withInput()->with('error', 'Pembayaran melebihi harga paket Rp ' . number_format($price, 0, ',', '.') . '. Centang "Simpan kelebihan ke deposit" atau kurangi nominal.');
        }
        if ($nominal > 0 && !$request->hasFile('bukti_transfer')) {
            return back()->withInput()->with('error', 'Bukti transfer wajib diupload jika ada pembayaran.');
        }

        $start = Carbon::parse($request->tgl_masuk)->startOfDay();
        $end = $this->rental->packageEnd($start, (int) $pl->jangka_waktu, $pl->jangka_sewa, (int) $pl->bonus_waktu, $pl->bonus_sewa);
        $proofPath = null;

        try {
            DB::transaction(function () use ($request, $renter, $kamar, $pl, $start, $end, $price, $nominal, $depositUse, $cashRevenue, $over, &$proofPath) {
                if ($this->rental->roomHasOverlap((int) $kamar->id, $start, $end)) {
                    throw new \RuntimeException('Kamar ' . $kamar->room_name . ' sudah memiliki sewa atau antrian booking pada rentang ' . $start->format('Y-m-d') . ' s/d ' . $end->format('Y-m-d') . '.');
                }
                $this->assertNoOverstay($kamar, $renter->id);

                $noTrans = $this->rental->nextTransId();
                if ($request->hasFile('bukti_transfer')) {
                    $file = $request->file('bukti_transfer');
                    $proofPath = $file->storeAs('bcl/transfer-proof', 'bukti-transfer-' . $noTrans . '-' . Str::random(10) . '.' . $file->getClientOriginalExtension(), 'public');
                }

                tr_renter::create([
                    'trans_id' => $noTrans,
                    'identity' => 'Baru',
                    'id_renter' => $renter->id,
                    'tanggal' => date('Y-m-d'),
                    'tgl_mulai' => $start->format('Y-m-d'),
                    'tgl_selesai' => $end->format('Y-m-d'),
                    'room_id' => $kamar->id,
                    'lama_sewa' => $pl->jangka_waktu,
                    'jangka_sewa' => $pl->jangka_sewa,
                    'harga' => $price,
                    'free_sewa' => $pl->bonus_waktu,
                    'free_jangka' => $pl->bonus_sewa,
                    'catatan' => (string) $request->catatan,
                    'bukti_transfer' => $proofPath,
                ]);

                $noJurnal = $this->rental->nextNoJurnal();
                $base = ['no_jurnal' => $noJurnal, 'tanggal' => $request->tgl_bayar, 'doc_id' => $noTrans];
                $catatan = 'Pendapatan Sewa Kamar ' . $kamar->room_name . ' dari ' . $renter->nama;

                // The revenue line always exists so the transaction is tracked as receivable.
                $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_REVENUE, 'kredit' => $cashRevenue, 'kode_subledger' => $renter->id, 'catatan' => $catatan, 'identity' => 'Sewa Kamar', 'pos' => 'K']);
                if ($cashRevenue > 0) {
                    $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_CASH, 'debet' => $cashRevenue, 'catatan' => $catatan, 'identity' => 'Sewa Kamar', 'pos' => 'D']);
                }

                if ($depositUse > 0) {
                    $note = 'Pemakaian deposit untuk sewa ' . $noTrans . ' oleh ' . $renter->nama;
                    $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_REVENUE, 'kredit' => $depositUse, 'kode_subledger' => $renter->id, 'catatan' => $note, 'identity' => 'Pemakaian Deposit', 'pos' => 'K']);
                    $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_DEPOSIT, 'debet' => $depositUse, 'kode_subledger' => $renter->id, 'catatan' => $note, 'identity' => 'Pemakaian Deposit', 'pos' => 'D']);
                    $renter->debitDeposit($depositUse);
                }

                if ($over > 0) {
                    $note = 'Kelebihan pembayaran sewa ' . $noTrans . ' disimpan sebagai deposit ' . $renter->nama;
                    $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_CASH, 'debet' => $over, 'catatan' => $note, 'identity' => 'Topup Deposit (Overpay)', 'pos' => 'D']);
                    $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_DEPOSIT, 'kredit' => $over, 'kode_subledger' => $renter->id, 'catatan' => $note, 'identity' => 'Topup Deposit (Overpay)', 'pos' => 'K']);
                    $renter->creditDeposit($over);
                }
            });
        } catch (\Throwable $th) {
            if ($proofPath) {
                Storage::disk('public')->delete($proofPath);
            }

            return back()->withInput()->with('error', $th->getMessage());
        }

        return back()->with('success', $start->isFuture() ? 'Antrian booking berhasil dibuat' : 'Kamar berhasil disewa');
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
                        $this->rental->journal($base + ['kode_akun' => RentalService::ACCOUNT_CASH, 'debet' => $payNow, 'pos' => 'D']);
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

        $prices = Pricelist::where('jangka_waktu', (int) $trx->lama_sewa)
            ->whereRaw('LOWER(jangka_sewa) = ?', [strtolower((string) $trx->jangka_sewa)])
            ->get()
            ->keyBy('room_category');
        $from = max(Carbon::today()->format('Y-m-d'), $trx->tgl_mulai);
        $paid = (float) ($this->rental->paidByDoc([$trx->trans_id])[$trx->trans_id] ?? 0);

        $overstays = $this->rental->overstaysByRoom();
        $rooms = Rooms::with('category')->orderedForMapping()->get()->map(function ($room) use ($prices, $trx, $from, $overstays) {
            $pl = $prices->get($room->room_category);
            if (!$pl) {
                return null;
            }

            return [
                'room' => ['id' => $room->id, 'name' => $room->room_name, 'category_name' => optional($room->category)->category_name],
                'price' => (float) $pl->price,
                'pricelist_id' => $pl->id,
                'is_occupied' => $room->id !== $trx->room_id && ($this->rental->roomHasOverlap($room->id, $from, $trx->tgl_selesai)
                    || ($overstays->has($room->id) && $overstays[$room->id]->id_renter != $trx->id_renter)),
                'is_current_room' => $room->id === $trx->room_id,
            ];
        })->filter()->values();

        $currentPrice = optional($prices->get(optional($trx->room)->room_category))->price;

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

    /** Confirm the tenant of an ended rental has left, so the room shows as vacant. */
    public function checkout(Request $request)
    {
        $trx = tr_renter::with(['room', 'renter'])->where('trans_id', $request->trans_id)->first();
        if (!$trx) {
            return $this->respondJson($request, 'Transaksi tidak ditemukan.', 404);
        }
        if ($trx->tgl_selesai > Carbon::today()->format('Y-m-d')) {
            return $this->respondJson($request, 'Masa sewa belum habis. Untuk keluar lebih awal gunakan "Keluar / Refund".', 422);
        }
        $this->rental->markCheckedOut($trx);

        return $this->respondJson($request, optional($trx->renter)->nama . ' dikonfirmasi sudah keluar dari kamar ' . optional($trx->room)->room_name . '.');
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
        $pricelist = fn ($category) => Pricelist::where('room_category', $category)
            ->where('jangka_waktu', $lama)
            ->whereRaw('LOWER(jangka_sewa) = ?', [strtolower((string) $current->jangka_sewa)])
            ->first();
        $oldPl = $pricelist(optional($current->room)->room_category);
        $newPl = $pricelist($newRoom->room_category);
        if (!$oldPl || !$newPl) {
            throw new \RuntimeException('Harga paket ' . $lama . ' ' . $current->jangka_sewa . ' untuk ' . ($oldPl ? 'kamar baru' : 'kamar lama') . ' tidak ditemukan di Daftar Harga.');
        }

        $elapsed = min($lama, $this->rental->wholeUnitsBetween($this->rental->packageStart($current), $effective, $current->jangka_sewa));
        $remainingPercent = $lama > 0 ? ($lama - $elapsed) / $lama : 0;
        $diffFull = (float) $newPl->price - (float) $oldPl->price;
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
