<?php

namespace App\Http\Controllers\BCL;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BCL\FinJurnalController as ControllersFinJurnalController;
use App\Models\BCL\extra_pricelist;
use App\Models\BCL\Fin_jurnal;
use App\Models\BCL\Pricelist;
use App\Models\BCL\renter;
use App\Models\BCL\Rooms;
use App\Models\BCL\tb_extra_rent;
use App\Models\BCL\tr_renter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class tr_renterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (isset($request->filter)) {
            $start = explode('s/d', $request->filter)[0];
            $end = explode('s/d', $request->filter)[1];
        } else {
            $start = date('Y-m-d', strtotime('first day of january this year'));
            $end = date('Y-m-d', strtotime('last day of december this year'));
        }
        $data = tr_renter::with('renter')->with('room')
            ->with('tambahan')
            ->whereBetween('tanggal', [$start, $end])->get();
        // return response()->json($data);
    $category = DB::table('bcl_room_category')->get();
        // $rooms = Rooms::with('category')->get();
        $rooms = Rooms::with('category')->with('renter')->get();
        // return response()->json($rooms);
        $renter = renter::all();
        $belum_lunas = Fin_jurnal::leftjoin('bcl_tr_renter as tr_renter', 'tr_renter.trans_id', '=', 'bcl_fin_jurnal.doc_id')
            ->leftjoin('bcl_renter as renter', 'renter.id', '=', 'tr_renter.id_renter')
            ->select(
                DB::raw('bcl_fin_jurnal.doc_id as doc_id'),
                DB::raw('MAX(bcl_fin_jurnal.tanggal) as tanggal'),
                DB::raw('ANY_VALUE(bcl_fin_jurnal.identity) as identity'),
                DB::raw('ANY_VALUE(bcl_fin_jurnal.catatan) as catatan'),
                DB::raw('ANY_VALUE(renter.nama) as nama'),
                DB::raw('ANY_VALUE(renter.id) as id'),
                DB::raw('IFNULL(MAX(tr_renter.harga),0) as harga'),
                DB::raw('IFNULL(SUM( kredit ),0) AS dibayar'),
                DB::raw('IFNULL(MAX(tr_renter.harga) - SUM( kredit ),0) AS kurang')
            )->where('bcl_fin_jurnal.identity', 'regexp', 'pemasukan|sewa kamar|upgrade kamar')
            ->groupby('bcl_fin_jurnal.doc_id')
            ->havingRaw('(MAX(tr_renter.harga) - SUM(kredit)) > 0')
            ->orderby(DB::raw('MAX(bcl_fin_jurnal.tanggal)'), 'DESC')
            ->get();
        $belum_lunas_extra = tb_extra_rent::withsum('jurnal as total_kredit', 'kredit')
            ->groupby('id')
            ->get()
            ->filter(function ($item) {
                return ($item->harga * $item->lama_sewa * $item->qty) - $item->total_kredit > 0;
            });

        foreach ($belum_lunas_extra as $val) {
            $detail = tr_renter::where('trans_id', $val->parent_trans)->with('renter')->first();
            $val->renter = $detail->renter;
        }            // return response()->json($belum_lunas_extra);
        $extra_pricelist = extra_pricelist::all();
        // return response()->json($extra_pricelist);
        return view('bcl.transaksi.index', compact(
            'belum_lunas',
            'belum_lunas_extra',
            'data',
            'category',
            'rooms',
            'renter',
            'start',
            'end',
            'extra_pricelist'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     */
    public function show(tr_renter $tr_renter, Request $request)
    {
        $transaksi = tr_renter::with('renter')
            ->with('room')
            ->with('jurnal', function ($query) {
                $query->leftjoin('users', 'users.id', '=', 'bcl_fin_jurnal.user_id');
                $query->orderby('bcl_fin_jurnal.tanggal', 'ASC');
            })->with('tambahan')
            ->where('trans_id', '=', $request->id)->first();
        return response()->json($transaksi);
    }

    public function cetak(tr_renter $tr_renter, Request $request)
    {
        $transaksi = tr_renter::with('renter')
            ->with('room', function ($query) {
                $query->with('category');
            })
            ->with('jurnal', function ($query) {
                $query->leftjoin('users', 'users.id', '=', 'bcl_fin_jurnal.user_id');
                $query->orderby('bcl_fin_jurnal.tanggal', 'ASC');
            })
            ->with('tambahan')
            ->where('trans_id', '=', $request->id)->first();

        return view('bcl.transaksi.cetak', compact('transaksi'));
        // return response()->json($transaksi);
    }

    /**
     * Render refund/credit note print view for downgrade refunds.
     * Accepts route params: doc_id (journal/expense id), optional renter_id.
     */
    public function cetakRefund(Request $request, $doc_id, $renter_id = null)
    {
        // Try to get refund payload from session (redirect-from changeRoom)
        $flash = session('refund_payload');
        $transaksiId = session('transaksi_id') ?? null;

        $transaksi = null;
        if ($transaksiId) {
            $transaksi = tr_renter::with('renter')->with('room')->where('trans_id', $transaksiId)->first();
        } elseif ($renter_id && $doc_id) {
            // fallback: try to find a related transaction by renter and latest journal doc
            $transaksi = tr_renter::with('renter')->with('room')->where('id_renter', $renter_id)->orderByDesc('tanggal')->first();
        }

        $refund = null;
        $journal_lines = [];
        if ($flash) {
            $refund = $flash;
        } else {
            // Minimal fallback: try to compute refund amount from fin_jurnal doc_id
            $sum = Fin_jurnal::where('doc_id', $doc_id)->sum('debet');
            $refund = (object)[
                'doc_id' => $doc_id,
                'amount' => $sum ?: 0,
                'tanggal' => date('Y-m-d'),
                'alasan' => 'Refund'
            ];
        }

        if ($doc_id) {
            $journal_lines = Fin_jurnal::where('doc_id', $doc_id)->orderBy('tanggal', 'asc')->get();
        }

        // If we couldn't find the original transaction, try a best-effort lookup:
        // 1) check if any journal line has kode_subledger that matches a tr_renter.trans_id
        // 2) or matches a renter.id — then load the latest transaction for that renter
        if (!$transaksi && $doc_id && count($journal_lines) > 0) {
            // try trans_id match first
            $kode = $journal_lines->pluck('kode_subledger')->filter()->first();
            if ($kode) {
                // try as trans_id
                $tryTrans = tr_renter::with('renter')->with('room')->where('trans_id', $kode)->first();
                if ($tryTrans) {
                    $transaksi = $tryTrans;
                } else {
                    // try as renter id
                    try {
                        $r = renter::find($kode);
                        if ($r) {
                            $tryTrans = tr_renter::with('renter')->with('room')->where('id_renter', $r->id)->orderByDesc('tanggal')->first();
                            if ($tryTrans) $transaksi = $tryTrans;
                        }
                    } catch (\Throwable $e) {
                        // ignore and continue without transaksi
                    }
                }
            }
        }

        return view('bcl.transaksi.cetak_refund', compact('transaksi', 'refund', 'journal_lines'));
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(tr_renter $tr_renter)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, tr_renter $tr_renter)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(tr_renter $tr_renter, Request $request)
    {

        try {
            DB::beginTransaction();
            $transaksi = tr_renter::where('trans_id', $request->id)->get();
            $extra = tb_extra_rent::where('parent_trans', $request->id)->get();
            $jurnal = Fin_jurnal::where('doc_id', $request->id)->get();
            foreach ($transaksi as $key => $value) {
                $value->delete();
            }
            foreach ($jurnal as $key => $value) {
                $value->delete();
            }
            foreach ($extra as $key => $value) {
                $value->delete();
            }
            DB::commit();
            return back()->with(['success' => 'Transaksi berhasil dihapus']);
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with(['error' => $th->getMessage()]);
        }
    }

    public function refund(tr_renter $tr_renter, Request $request)
    {
        DB::beginTransaction();
        try {
            $transaksi = tr_renter::where('trans_id', $request->kode_trans)->first();
            if ($transaksi->harga < $request->nominal_refund) {
                return back()->with(['error' => 'Nominal refund melebihi harga sewa']);
            }
            $transaksi->update([
                'tgl_selesai' => $request->tgl_refund
            ]);
            $renter = renter::findorfail($transaksi->id_renter);

            $no_exp = app(ControllersFinJurnalController::class)->get_no_exp();
            $no_jurnal = app(ControllersFinJurnalController::class)->get_no_jurnal();
            $data = Fin_jurnal::create([
                'no_jurnal' => $no_jurnal,
                'tanggal' => $request->tgl_refund,
                'kode_akun' => '1-10101',
                'debet' => 0,
                'kredit' => $request->nominal_refund,
                'kode_subledger' => null,
                'catatan' => 'Refund Sewa Kamar kepada ' . $renter->nama . ', dengan alasan: ' . $request->alasan,
                'index_kas' => 0,
                'doc_id' => $no_exp,
                'identity' => 'Refund',
                'pos' => 'K',
                'user_id' => Auth::id(),
                'csrf' => time()
            ]);
            $data = Fin_jurnal::create([
                'no_jurnal' => $no_jurnal,
                'tanggal' => $request->tgl_refund,
                'kode_akun' => '5-10102',
                'debet' => $request->nominal_refund,
                'kredit' => 0,
                'kode_subledger' => null,
                'catatan' => 'Refund Sewa Kamar kepada ' . $renter->nama . ', dengan alasan: ' . $request->alasan,
                'index_kas' => 0,
                'doc_id' => $no_exp,
                'identity' => 'Refund',
                'pos' => 'D',
                'user_id' => Auth::id(),
                'csrf' => time()
            ]);
            DB::commit();
            return back()->with(['success' => 'Refund berhasil dilakukan']);
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with(['error' => $th->getMessage()]);
        }
    }

    public function reschedule(Request $request)
    {
        try {
            $transaksi = tr_renter::where('trans_id', $request->trans_id)->first();
            // determine interval type and compute end date using Carbon for reliability
            $interval = strtolower($transaksi->jangka_sewa ?? 'Hari');
            $amount = intval($transaksi->lama_sewa ?? 0);
            $start = Carbon::parse($request->tgl_rencana_masuk);
            switch ($interval) {
                case 'hari':
                    $tgl_selesai = $start->copy()->addDays($amount)->format('Y-m-d');
                    break;
                case 'minggu':
                    $tgl_selesai = $start->copy()->addWeeks($amount)->format('Y-m-d');
                    break;
                case 'bulan':
                    $tgl_selesai = $start->copy()->addMonths($amount)->format('Y-m-d');
                    break;
                case 'tahun':
                    $tgl_selesai = $start->copy()->addYears($amount)->format('Y-m-d');
                    break;
                default:
                    $tgl_selesai = $start->copy()->addDays($amount)->format('Y-m-d');
                    break;
            }

            $transaksi->update([
                'tgl_mulai' => $start->format('Y-m-d'),
                'tgl_selesai' => $tgl_selesai
            ]);
            return back()->with(['success' => 'Tanggal masuk berhasil diubah']);
        } catch (\Throwable $th) {
            return back()->with(['error' => $th->getMessage()]);
        }
    }
    public function sewa(tr_renter $tr_renter, Request $request)
    {
        $renter = renter::findorfail($request->renter);
        // return response()->json($request);
        $no_trans = $this->get_no_trans();
        DB::beginTransaction();
        try {
            $this->validate($request, [
                'renter' => 'required',
                'kamar' => 'required',
                'renter' => 'required',
                'pricelist' => 'required',
                'tgl_masuk' => 'required',
                'catatan' => 'sometimes',
                'tgl_bayar' => 'sometimes',
                'nominal' => 'required|numeric',
            ]);
            $pl = pricelist::findorfail($request->pricelist);
            switch ($pl->jangka_sewa) {
                case 'Hari':
                    $jangka_sewa = 'days';
                    break;
                case 'Minggu':
                    $jangka_sewa = 'weeks';
                    break;
                case 'Bulan':
                    $jangka_sewa = 'months';
                    break;
                case 'Tahun':
                    $jangka_sewa = 'years';
                    break;
                default:
                    $jangka_sewa = 'days';
                    break;
            }
            $bonus_sewa = 'days';
            if ($pl->bonus_waktu > 0) {
                switch ($pl->bonus_sewa) {
                    case 'Hari':
                        $bonus_sewa = 'days';
                        break;
                    case 'Minggu':
                        $bonus_sewa = 'weeks';
                        break;
                    case 'Bulan':
                        $bonus_sewa = 'months';
                        break;
                    case 'Tahun':
                        $bonus_sewa = 'years';
                        break;
                    default:
                        $bonus_sewa = 'days';
                        break;
                }
            }
            $periode_normal = date('Y-m-d', strtotime("+$pl->jangka_waktu $jangka_sewa", strtotime($request->tgl_masuk)));
            $periode_bonus = date('Y-m-d', strtotime("+$pl->bonus_waktu $bonus_sewa", strtotime($periode_normal)));
            $Kamar = Rooms::findorfail($request->kamar);
            tr_renter::create([
                'trans_id' => $no_trans,
                'identity' => 'Baru',
                'id_renter' => $request->renter,
                'tanggal' => date('Y-m-d'),
                'tgl_mulai' => $request->tgl_masuk,
                'tgl_selesai' =>  $periode_bonus,
                'room_id' => $request->kamar,
                'lama_sewa' => $pl->jangka_waktu,
                'jangka_sewa' => $pl->jangka_sewa,
                'harga' => $pl->price,
                'free_sewa' => $pl->bonus_waktu,
                'free_jangka' => $pl->bonus_sewa,
                'catatan' => $request->catatan
            ]);
            // Determine amounts: split into revenue (price) and optional overpay
            $nominal = floatval($request->nominal);
            $price = floatval($pl->price);
            $over = 0;
            if ($nominal > $price) {
                $over = $nominal - $price;
            }
            $revenue_amount = $nominal - $over; // will be equal to price if nominal > price

            $no_jurnal = app(ControllersFinJurnalController::class)->get_no_jurnal();
            // Record revenue portion
            Fin_jurnal::create([
                'no_jurnal' => $no_jurnal,
                'tanggal' => $request->tgl_bayar,
                'kode_akun' => '4-10101',
                'debet' => 0,
                'kredit' => $revenue_amount,
                'kode_subledger' => $request->renter,
                'catatan' => 'Pendapatan Sewa Kamar dari ' . $renter->nama,
                'index_kas' => 0,
                'doc_id' => $no_trans,
                'identity' => 'Sewa Kamar',
                'pos' => 'K',
                'user_id' => Auth::id(),
                'csrf' => time()
            ]);
            Fin_jurnal::create([
                'no_jurnal' => $no_jurnal,
                'tanggal' => $request->tgl_bayar,
                'kode_akun' => '1-10101',
                'debet' => $revenue_amount,
                'kredit' => 0,
                'kode_subledger' => null,
                'catatan' => 'Pendapatan Sewa Kamar dari ' . $renter->nama,
                'index_kas' => 0,
                'doc_id' => $no_trans,
                'identity' => 'Sewa Kamar',
                'pos' => 'D',
                'user_id' => Auth::id(),
                'csrf' => time()
            ]);
            // If nominal > package price and user asked to add excess to deposit
            try {
                $nominal = floatval($request->nominal);
                $price = floatval($pl->price);
                $over = $nominal - $price;
                if (isset($request->overpay_to_deposit) && $request->overpay_to_deposit && $over > 0) {
                    // record jurnal for deposit top-up: credit cash and debit deposit liability
                    $no_jurnal_dep = app(ControllersFinJurnalController::class)->get_no_jurnal();
                    // Record as topup: debit cash (1-10101) and credit deposit liability (2-99999)
                    Fin_jurnal::create([
                        'no_jurnal' => $no_jurnal_dep,
                        'tanggal' => $request->tgl_bayar,
                        'kode_akun' => '1-10101',
                        'debet' => $over,
                        'kredit' => 0,
                        'kode_subledger' => $request->renter,
                        'catatan' => 'Kelebihan pembayaran disimpan sebagai deposit oleh ' . $renter->nama,
                        'index_kas' => 0,
                        'doc_id' => 'DP' . time(),
                        'identity' => 'Topup Deposit (Overpay)',
                        'pos' => 'D',
                        'user_id' => Auth::id(),
                        'csrf' => time()
                    ]);
                    Fin_jurnal::create([
                        'no_jurnal' => $no_jurnal_dep,
                        'tanggal' => $request->tgl_bayar,
                        'kode_akun' => '2-99999',
                        'debet' => 0,
                        'kredit' => $over,
                        'kode_subledger' => $request->renter,
                        'catatan' => 'Kelebihan pembayaran disimpan sebagai deposit oleh ' . $renter->nama,
                        'index_kas' => 0,
                        'doc_id' => 'DP' . time(),
                        'identity' => 'Topup Deposit (Overpay)',
                        'pos' => 'K',
                        'user_id' => Auth::id(),
                        'csrf' => time()
                    ]);

                    // update model balance
                    try {
                        $renter->creditDeposit($over);
                    } catch (\Throwable $e) {
                        // ignore model error but keep jurnal entries; in future surface this
                    }
                }
            } catch (\Throwable $e) {
                // ignore overpay handling errors to avoid breaking sewa flow
            }
            DB::commit();
            // return response()->json($request);
            return back()->with(['success' => 'Kamar berhasil disewa']);
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with(['error' => $th->getMessage()]);
        }
    }
    public function get_no_trans()
    {
        $data = DB::select("SELECT
        CONCAT(
            'BL-',
            DATE_FORMAT( STR_TO_DATE(now(), '%Y-%m-%d' ), '%m%y' ),
            '',
        LPAD( ifnull(max(RIGHT(trans_id,4)),0) + 1, 4, '0' )) AS no_trans 
        -- LPAD( count(*) + 1, 4, '0' )) AS no_trans
        FROM
        bcl_tr_renter 
        WHERE
        MONTH ( tanggal )= MONTH (
        STR_TO_DATE(now(), '%Y-%m-%d' )) 
        AND YEAR ( tanggal )= YEAR (
        STR_TO_DATE(now(), '%Y-%m-%d' ))");
        $result = $data[0];
        return $result->no_trans;
    }

    public function ranking_penyewa()
    {
        // Build a query that selects only grouped columns and aggregates to satisfy ONLY_FULL_GROUP_BY
        $data = DB::table('bcl_tr_renter')
            ->select(
                'id_renter',
                DB::raw('SUM(DATEDIFF(tgl_selesai, tgl_mulai)) AS total_lama_sewa')
            )
            ->groupBy('id_renter')
            ->orderByDesc('total_lama_sewa')
            ->get();

        // attach renter model (and keep renter_name) so views that expect $data->renter->nama work
        $result = $data->map(function ($row) {
            $renterModel = renter::find($row->id_renter);
            // convert row (stdClass) to array, then add keys
            $arr = (array) $row;
            $arr['renter_name'] = $renterModel?->nama ?? null;
            // Attach renter model object (or null) to mirror older code expectations
            $arr['renter'] = $renterModel ?? null;
            return (object) $arr;
        });

        return response()->json($result);
    }
    
    public function changeRoom(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate request
            $this->validate($request, [
                'trans_id' => 'required',
                'new_room_id' => 'required',
                'effective_date' => 'required|date',
                'payment_date' => 'nullable|date',
                'pay_now' => 'nullable|numeric|min:0',
                'remaining_due' => 'nullable|numeric|min:0',
            ]);
            
            // Get current transaction and room details
            $currentTransaction = tr_renter::where('trans_id', $request->trans_id)->first();
            if (!$currentTransaction) {
                return back()->with(['error' => 'Transaksi tidak ditemukan']);
            }
            
            // Get the new room and price
            $newRoom = Rooms::findOrFail($request->new_room_id);
            
            // Dates
            $effectiveDate = Carbon::parse($request->effective_date);
            $currentEndDate = Carbon::parse($currentTransaction->tgl_selesai);
            $startDateOriginal = Carbon::parse($currentTransaction->tgl_mulai);

            // Total units from original transaction
            $totalUnits = (int) $currentTransaction->lama_sewa; // e.g. 6
            $unitType = strtolower($currentTransaction->jangka_sewa); // Bulan / Minggu / Hari / Tahun

            // Compute elapsed units (floor) based on effectiveDate relative to start
            if ($effectiveDate->lessThanOrEqualTo($startDateOriginal)) {
                $elapsedUnits = 0;
            } else {
                switch ($unitType) {
                    case 'bulan':
                        // Match UI logic exactly: effective.diff(start,'months')
                        $elapsedUnits = $startDateOriginal->diffInMonths($effectiveDate);
                        break;
                    case 'minggu':
                        $elapsedUnits = $startDateOriginal->diffInWeeks($effectiveDate);
                        break;
                    case 'tahun':
                        $elapsedUnits = $startDateOriginal->diffInYears($effectiveDate);
                        break;
                    case 'hari':
                    default:
                        $elapsedUnits = $startDateOriginal->diffInDays($effectiveDate);
                        break;
                }
            }
            if ($elapsedUnits > $totalUnits) $elapsedUnits = $totalUnits; // clamp
            $remainingUnits = $totalUnits - $elapsedUnits;
            if ($remainingUnits < 0) $remainingUnits = 0;
            // Remaining percent of rental period
            $remainingPercent = $totalUnits > 0 ? $remainingUnits / $totalUnits : 0;
            
            // Create a transaction ID for the room change
            $changeTransId = $this->get_no_trans();
            
            // End the current rental on the effective date (one day before the new rental starts)
            $currentTransaction->update([
                'tgl_selesai' => $effectiveDate->copy()->subDay()->format('Y-m-d')
            ]);
            
            // Get the pricelist for the new room with SAME duration (lama_sewa + jangka_sewa)
            $newPricelist = Pricelist::where('room_category', $newRoom->room_category)
                ->where('jangka_waktu', (int)$currentTransaction->lama_sewa)
                ->whereRaw('LOWER(jangka_sewa) = ?', [strtolower($currentTransaction->jangka_sewa)])
                ->first();
            if (!$newPricelist) {
                DB::rollBack();
                return back()->with(['error' => 'Harga kamar baru (durasi sama) tidak ditemukan']);
            }
            
            // Get the pricelist for the current room
            $oldRoom = Rooms::findOrFail($currentTransaction->room_id);
            $oldPricelist = Pricelist::where('room_category', $oldRoom->room_category)
                ->where('jangka_waktu', (int)$currentTransaction->lama_sewa)
                ->whereRaw('LOWER(jangka_sewa) = ?', [strtolower($currentTransaction->jangka_sewa)])
                ->first();
            if (!$oldPricelist) {
                DB::rollBack();
                return back()->with(['error' => 'Harga kamar lama tidak ditemukan']);
            }
            
            // Full-period price difference (e.g. 6-month package new - old)
            $priceDifferenceFull = $newPricelist->price - $oldPricelist->price;
            // Payment only on remaining proportion - use clean fraction to match modal
            $remainingPercentClean = $totalUnits > 0 ? $remainingUnits / $totalUnits : 0;
            $payableRaw = $priceDifferenceFull * $remainingPercentClean; // can be negative for refund
            $paymentType = $payableRaw > 0 ? 'charge' : 'refund';
            $paymentAmount = abs(round($payableRaw, 0)); // full theoretical amount (absolute)

            // Compute actual amounts consistent with modal logic:
            // alreadyPaid = sum of kredit revenue lines for the original transaction
            $alreadyPaid = (float) Fin_jurnal::where('doc_id', $currentTransaction->trans_id)
                ->where(function($q){
                    // match identities similar to UI (Sewa Kamar / Upgrade Kamar) or kode_akun revenue
                    $q->where('identity', 'like', '%Sewa Kamar%')
                      ->orWhere('identity', 'like', '%Upgrade Kamar%')
                      ->orWhere('kode_akun', '4-10101');
                })->sum('kredit');

            // For refunds (downgrades) the UI uses refundBase = min(alreadyPaid, abs(payable)).
            // Use the same rule here so the controller is trustable with the UI.
            if ($payableRaw < 0) {
                $finalPaymentAmount = min($alreadyPaid, abs(round($payableRaw, 0)));
            } else {
                $finalPaymentAmount = $paymentAmount; // upgrades use the full computed amount
            }
            
            // Get payment amounts
            $payNow = floatval($request->pay_now ?? 0);
            $remainingDue = floatval($request->remaining_due ?? 0);
            
            // Create new rental transaction starting from effective date
            $defaultNote = $request->catatan ?? ('Pindah kamar: ' . ($oldRoom->room_name ?? $oldRoom->id) . ' -> ' . ($newRoom->room_name ?? $newRoom->id));
            $newTransaction = tr_renter::create([
                'trans_id' => $changeTransId,
                'identity' => 'Pindah Kamar',
                'id_renter' => $currentTransaction->id_renter,
                'tanggal' => date('Y-m-d'),
                'tgl_mulai' => $effectiveDate->format('Y-m-d'),
                'tgl_selesai' => $currentEndDate->format('Y-m-d'), // keep same overall end date
                'room_id' => $request->new_room_id,
                'lama_sewa' => $remainingUnits,
                'jangka_sewa' => $currentTransaction->jangka_sewa,
                // Store the total upgrade charge as harga (full amount that needs to be paid)
                'harga' => $paymentAmount,
                'catatan' => $defaultNote,
            ]);
            
            // Handle financial adjustments (if any)
            $renter = renter::findorfail($currentTransaction->id_renter);
            
            if ((($paymentType == 'charge') && $paymentAmount > 0) || (($paymentType == 'refund') && $finalPaymentAmount > 0)) {
                $no_jurnal = app(ControllersFinJurnalController::class)->get_no_jurnal();
                
                if ($paymentType == 'charge') {
                    // Determine payment amount and status
                    $actualPayment = $payNow > 0 ? $payNow : 0; // Use actual payment or 0 if nothing specified
                    $paymentStatus = $actualPayment >= $paymentAmount ? 'Lunas' : 'Belum Lunas';
                    $statusNote = $actualPayment >= $paymentAmount ? '' : ' (Pembayaran sebagian)';
                    
                    // First journal entry - Record the actual payment amount in kredit
                    Fin_jurnal::create([
                        'no_jurnal' => $no_jurnal,
                        'tanggal' => $request->payment_date ?? date('Y-m-d'),
                        'kode_akun' => '4-10101',
                        'debet' => 0,
                        'kredit' => $actualPayment, // Only record what's actually being paid
                        'kode_subledger' => $currentTransaction->id_renter,
                        'catatan' => 'Pembayaran tambahan upgrade kamar: ' . $oldRoom->room_name . ' -> ' . $newRoom->room_name . '. Selisih paket penuh Rp ' . number_format($priceDifferenceFull, 0) . ' x ' . number_format($remainingPercentClean*100,1) . '% sisa = Rp ' . number_format($paymentAmount,0) . $statusNote,
                        'doc_id' => $changeTransId,
                        'identity' => 'Upgrade Kamar ' . $paymentStatus,
                        'pos' => 'K',
                        'user_id' => Auth::id(),
                        'csrf' => time()
                    ]);
                    
                    // Second journal entry - Match debet with what's actually being paid
                    Fin_jurnal::create([
                        'no_jurnal' => $no_jurnal,
                        'tanggal' => $request->payment_date ?? date('Y-m-d'),
                        'kode_akun' => '1-10101',
                        'debet' => $actualPayment, // Only record what's being paid now, not the full amount
                        'kredit' => 0,
                        'kode_subledger' => null,
                        'catatan' => 'Pembayaran tambahan upgrade kamar: ' . $oldRoom->room_name . ' -> ' . $newRoom->room_name . '. Selisih paket penuh Rp ' . number_format($priceDifferenceFull, 0) . ' x ' . number_format($remainingPercentClean*100,1) . '% sisa = Rp ' . number_format($paymentAmount,0) . $statusNote,
                        'doc_id' => $changeTransId,
                        'identity' => 'Upgrade Kamar ' . $paymentStatus,
                        'pos' => 'D',
                        'user_id' => Auth::id(),
                        'csrf' => time()
                    ]);
                } else {
                    // Refund (downgrade)
                    // If user chose to put refund into deposit, record as deposit top-up instead of cash refund
                    if (isset($request->refund_to_deposit) && $request->refund_to_deposit) {
                        // create deposit journal entries (debit cash, credit deposit liability)
                        $no_jurnal_dep = app(ControllersFinJurnalController::class)->get_no_jurnal();
                        $doc_dep = 'DP' . time();

                        Fin_jurnal::create([
                            'no_jurnal' => $no_jurnal_dep,
                            'tanggal' => $request->payment_date ?? date('Y-m-d'),
                            'kode_akun' => '1-10101',
                            'debet' => $finalPaymentAmount,
                            'kredit' => 0,
                            'kode_subledger' => $currentTransaction->id_renter,
                            'catatan' => 'Tambah deposit (downgrade kamar): ' . $oldRoom->room_name . ' -> ' . $newRoom->room_name,
                            'index_kas' => 0,
                            'doc_id' => $doc_dep,
                            'identity' => 'Topup Deposit (Downgrade)',
                            'pos' => 'D',
                            'user_id' => Auth::id(),
                            'csrf' => time()
                        ]);

                        Fin_jurnal::create([
                            'no_jurnal' => $no_jurnal_dep,
                            'tanggal' => $request->payment_date ?? date('Y-m-d'),
                            'kode_akun' => '2-99999',
                            'debet' => 0,
                            'kredit' => $finalPaymentAmount,
                            'kode_subledger' => $currentTransaction->id_renter,
                            'catatan' => 'Tambah deposit (downgrade kamar): ' . $oldRoom->room_name . ' -> ' . $newRoom->room_name,
                            'index_kas' => 0,
                            'doc_id' => $doc_dep,
                            'identity' => 'Topup Deposit (Downgrade)',
                            'pos' => 'K',
                            'user_id' => Auth::id(),
                            'csrf' => time()
                        ]);

                        // Update renter deposit balance (best-effort)
                        try {
                            $renter->creditDeposit($finalPaymentAmount);
                        } catch (\Throwable $e) {
                            // ignore model error but keep jurnal entries
                        }

                        // mark that we created a deposit doc id for later use (if needed)
                        $no_exp = $doc_dep;
                    } else {
                        // Cash refund as before
                        $no_exp = app(ControllersFinJurnalController::class)->get_no_exp();

                        Fin_jurnal::create([
                            'no_jurnal' => $no_jurnal,
                            'tanggal' => $request->payment_date ?? date('Y-m-d'),
                            'kode_akun' => '1-10101',
                            'debet' => 0,
                            'kredit' => $finalPaymentAmount,
                            'kode_subledger' => null,
                            'catatan' => 'Refund downgrade kamar: ' . $oldRoom->room_name . ' -> ' . $newRoom->room_name . '. Selisih paket penuh Rp ' . number_format(abs($priceDifferenceFull), 2) . ' x ' . number_format($remainingPercent*100,1) . '% sisa = Rp ' . number_format($finalPaymentAmount,2),
                            'index_kas' => 0,
                            'doc_id' => $no_exp,
                            'identity' => 'Downgrade Kamar',
                            'pos' => 'K',
                            'user_id' => Auth::id(),
                            'csrf' => time()
                        ]);

                        Fin_jurnal::create([
                            'no_jurnal' => $no_jurnal,
                            'tanggal' => $request->payment_date ?? date('Y-m-d'),
                            'kode_akun' => '5-10102',
                            'debet' => $finalPaymentAmount,
                            'kredit' => 0,
                            'kode_subledger' => null,
                            'catatan' => 'Refund downgrade kamar: ' . $oldRoom->room_name . ' -> ' . $newRoom->room_name . '. Selisih paket penuh Rp ' . number_format(abs($priceDifferenceFull), 2) . ' x ' . number_format($remainingPercent*100,1) . '% sisa = Rp ' . number_format($finalPaymentAmount,2),
                            'index_kas' => 0,
                            'doc_id' => $no_exp,
                            'identity' => 'Downgrade Kamar',
                            'pos' => 'D',
                            'user_id' => Auth::id(),
                            'csrf' => time()
                        ]);
                    }
                }
            }
            
            // commit before redirecting to ensure jurnal saved
            DB::commit();

            // If downgrade cash refund occurred, redirect to a refund print page so user can print refund receipt
            if ($paymentType === 'refund' && $paymentAmount > 0 && !($request->refund_to_deposit ?? false)) {
                // Build a minimal refund payload for the print view
                $refundPayload = (object)[
                    'doc_id' => $no_exp ?? null,
                    'amount' => $paymentAmount,
                    'tanggal' => $request->payment_date ?? date('Y-m-d'),
                    'alasan' => $request->alasan ?? ($request->reason ?? 'Downgrade kamar')
                ];
                // reload transaksi to pass to view
                $transaksiForView = tr_renter::with('renter')->with('room')->where('trans_id', $currentTransaction->trans_id)->first();
                return redirect()->route('bcl.transaksi.cetak_refund', ['doc_id' => $no_exp, 'renter_id' => $transaksiForView->id_renter])->with(['refund_payload' => $refundPayload, 'transaksi_id' => $currentTransaction->trans_id]);
            }

            // Success message based on payment status (non-refund path)
            if ($paymentAmount > 0 && $payNow < $paymentAmount) {
                return back()->with(['success' => 'Pindah kamar berhasil dilakukan dengan pembayaran sebagian. Sisa tagihan: Rp ' . number_format($paymentAmount - $payNow, 0)]);
            }
            return back()->with(['success' => 'Pindah kamar berhasil dilakukan']);
            
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with(['error' => $th->getMessage()]);
        }
    }

    /**
     * Provide list of available rooms with price matching the current transaction duration.
     * Route param {id} = trans_id of existing rental.
     * Returns JSON: { current: {...}, rooms: [ {room:{}, price, pricelist_id} ], meta:{lama_sewa, jangka_sewa} }
     */
    public function changeRoomOptions($id)
    {
        $trx = tr_renter::with('room')->where('trans_id', $id)->first();
        if(!$trx){
            return response()->json(['error'=>'Transaksi tidak ditemukan'],404);
        }

        $lama = $trx->lama_sewa;            // e.g. 6
        $jangka = $trx->jangka_sewa;        // e.g. Bulan

        // Map jangka_sewa to pricelist jangka_waktu + jangka_sewa fields
        // Showing all rooms regardless of occupation status
        $rooms = Rooms::with('category')
            ->with(['renter'=>function($q){ /* already filters active renter in model */ }])
            ->get();

        // For each room find pricelist with same duration (lama & jangka)
        $result = $rooms->map(function($room) use ($lama, $jangka, $trx){
            $pl = Pricelist::where('room_category', $room->room_category)
                ->where('jangka_waktu', $lama)
                ->whereRaw('LOWER(jangka_sewa)=?', [strtolower($jangka)])
                ->first();
            if(!$pl){
                return null; // skip if no matching duration price
            }
            
            // Check if room is occupied (has active renter)
            $isOccupied = $room->renter ? true : false;
            // Check if this is the current user's room
            $isCurrentRoom = ($room->id === $trx->room_id);
            
            return [
                'room'=>[
                    'id'=>$room->id,
                    'name'=>$room->room_name,
                    'category_name'=>$room->category->category_name ?? null,
                ],
                'price'=>$pl->price,
                'pricelist_id'=>$pl->id,
                'is_occupied'=>$isOccupied,
                'is_current_room'=>$isCurrentRoom,
            ];
        })->filter()->values();

        return response()->json([
            'current'=>[
                'trans_id'=>$trx->trans_id,
                'room_id'=>$trx->room_id,
                'lama_sewa'=>$lama,
                'jangka_sewa'=>$jangka,
                'harga'=>$trx->harga,
                'tgl_mulai'=>$trx->tgl_mulai,
                'tgl_selesai'=>$trx->tgl_selesai,
            ],
            'rooms'=>$result,
            'meta'=>[
                'lama_sewa'=>$lama,
                'jangka_sewa'=>$jangka
            ]
        ]);
    }
}
