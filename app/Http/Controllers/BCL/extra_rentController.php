<?php

namespace App\Http\Controllers\BCL;

use App\Http\Controllers\Controller;
use App\Models\BCL\ExtraBedAsset;
use App\Models\BCL\ExtraBedAssignment;
use App\Models\BCL\extra_pricelist;
use App\Models\BCL\tb_extra_rent;
use App\Services\BCL\RentalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class extra_rentController extends Controller
{
    public function __construct(protected RentalService $rental)
    {
    }

    public function availability(Request $request)
    {
        $this->validate($request, [
            'pricelist' => 'required|exists:bcl_extra_pricelist,id',
            'tgl_sewa' => 'required|date',
            'lama_sewa' => 'required|integer|min:1',
        ]);

        $extraPricelist = extra_pricelist::findOrFail($request->pricelist);
        $startDate = Carbon::parse($request->tgl_sewa)->format('Y-m-d');
        $endDate = $this->rental->addUnits($startDate, (int) $request->lama_sewa, $extraPricelist->jangka_sewa)->format('Y-m-d');

        if (!$extraPricelist->requiresExtraBedTracking()) {
            return response()->json(['tracked' => false, 'start_date' => $startDate, 'end_date' => $endDate]);
        }

        ExtraBedAsset::ensureDefaultAssets();
        $availableAssets = ExtraBedAsset::availableBetween($startDate, $endDate)->orderBy('asset_code')->get();

        return response()->json([
            'tracked' => true,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_count' => ExtraBedAsset::active()->count(),
            'available_count' => $availableAssets->count(),
            'available_asset_codes' => $availableAssets->pluck('asset_code')->values()->all(),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'jml_item' => (int) RentalService::amount($request->jml_item),
            'lama_sewa' => (int) RentalService::amount($request->lama_sewa),
            // Only a custom charge has a price field; parsing an empty one would give 0 and fail min:1.
            'harga_custom' => $request->pricelist === 'custom' ? RentalService::amount($request->harga_custom) : null,
        ]);

        try {
            $this->validate($request, [
                'trans_id' => 'required|exists:bcl_tr_renter,trans_id',
                'pricelist' => $request->pricelist === 'custom' ? 'required' : 'required|exists:bcl_extra_pricelist,id',
                'jml_item' => 'required|integer|min:1',
                'lama_sewa' => 'required|integer|min:1',
                'tgl_sewa' => 'required|date',
                'nama_custom' => 'required_if:pricelist,custom|nullable|string|max:150',
                'harga_custom' => 'required_if:pricelist,custom|nullable|numeric|min:1',
            ]);
        } catch (ValidationException $e) {
            return back()->withInput()->with('error', collect($e->errors())->flatten()->first());
        }

        if ($request->pricelist === 'custom') {
            return $this->storeCustomCharge($request);
        }

        try {
            $assigned = DB::transaction(function () use ($request) {
                $extraPl = extra_pricelist::findOrFail($request->pricelist);
                $qty = (int) $request->jml_item;
                $tglMulai = Carbon::parse($request->tgl_sewa)->format('Y-m-d');
                $tglSelesai = $this->rental->addUnits($tglMulai, (int) $request->lama_sewa, $extraPl->jangka_sewa)->format('Y-m-d');

                $assets = collect();
                if ($extraPl->requiresExtraBedTracking()) {
                    ExtraBedAsset::ensureDefaultAssets();
                    $available = ExtraBedAsset::availableBetween($tglMulai, $tglSelesai)->orderBy('asset_code')->lockForUpdate()->get();
                    if ($available->count() < $qty) {
                        throw new \RuntimeException('Extra bed tidak mencukupi. Tersedia ' . $available->count() . ' dari ' . ExtraBedAsset::active()->count() . ' unit untuk periode tersebut.');
                    }
                    $assets = $available->take($qty)->values();
                }

                $extra = tb_extra_rent::create([
                    'kode' => $this->rental->nextExtraKode(),
                    'parent_trans' => $request->trans_id,
                    'nama' => $extraPl->nama,
                    'qty' => $qty,
                    'lama_sewa' => (int) $request->lama_sewa,
                    'jangka_sewa' => $extraPl->jangka_sewa,
                    'tgl_mulai' => $tglMulai,
                    'tgl_selesai' => $tglSelesai,
                    'harga' => $extraPl->harga,
                ]);

                foreach ($assets as $asset) {
                    ExtraBedAssignment::create([
                        'extra_bed_asset_id' => $asset->id,
                        'extra_rent_id' => $extra->id,
                        'assigned_from' => $tglMulai,
                        'assigned_until' => $tglSelesai,
                    ]);
                }

                return $assets;
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $notice = $assigned->isEmpty() ? '' : ' Unit: ' . $assigned->pluck('asset_code')->implode(', ');

        return back()->with('success', 'Tambahan sewa berhasil disimpan.' . $notice);
    }

    /**
     * A one-off charge outside the price lists (electricity, laundry, repair, ...): free name and amount,
     * billed on the renter's rental like any extra rent (shows in Belum Lunas until paid).
     */
    protected function storeCustomCharge(Request $request)
    {
        try {
            $extra = DB::transaction(function () use ($request) {
                $date = Carbon::parse($request->tgl_sewa)->format('Y-m-d');
                $extra = tb_extra_rent::create([
                    'kode' => $this->rental->nextExtraKode(),
                    'parent_trans' => $request->trans_id,
                    'nama' => trim($request->nama_custom),
                    'qty' => (int) $request->jml_item,
                    'lama_sewa' => 1,
                    'jangka_sewa' => 'Kali',
                    // Same start and end date: not a stay, so it never blocks extra beds or rooms.
                    'tgl_mulai' => $date,
                    'tgl_selesai' => $date,
                    'harga' => (float) $request->harga_custom,
                ]);
                $this->rental->audit('custom_charge', $extra->kode, 'Tagihan lain "' . $extra->nama . '" untuk ' . $request->trans_id, [
                    'qty' => $extra->qty, 'harga' => (float) $extra->harga, 'total' => $this->rental->extraTotal($extra),
                ]);

                return $extra;
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Tagihan "' . $extra->nama . '" Rp ' . number_format($this->rental->extraTotal($extra), 0, ',', '.') . ' ditambahkan (' . $extra->kode . '), masuk Belum Lunas.');
    }

    /** Cancel an extra rent (supervisor): its payments are removed (deposit use reverted) and extra beds released. */
    public function destroy(Request $request, $kode)
    {
        try {
            DB::transaction(function () use ($kode) {
                $extra = tb_extra_rent::where('kode', $kode)->lockForUpdate()->firstOrFail();
                $lines = \App\Models\BCL\Fin_jurnal::where('doc_id', $extra->kode)->get();
                $deposit = $lines->where('kode_akun', RentalService::ACCOUNT_DEPOSIT)->groupBy('kode_subledger');
                foreach ($deposit as $renterId => $rows) {
                    $renter = \App\Models\BCL\renter::find($renterId);
                    $net = (float) $rows->sum('kredit') - (float) $rows->sum('debet');
                    if ($renter && $net > 0 && (float) $renter->deposit_balance < $net) {
                        throw new \RuntimeException('Deposit penyewa sudah terpakai, tambahan sewa ini tidak dapat dibatalkan.');
                    }
                }
                $this->rental->revertDepositLines($lines);
                \App\Models\BCL\Fin_jurnal::whereIn('id', $lines->pluck('id'))->delete();
                ExtraBedAssignment::where('extra_rent_id', $extra->id)->delete();
                $this->rental->audit('cancel_extra', $extra->kode, 'Batal tambahan sewa ' . $extra->nama . ' (' . $extra->parent_trans . ')', [
                    'extra' => $extra->only(['nama', 'qty', 'lama_sewa', 'jangka_sewa', 'tgl_mulai', 'tgl_selesai', 'harga']),
                    'jurnal' => $lines->map->only(['tanggal', 'kode_akun', 'debet', 'kredit', 'identity'])->values(),
                ]);
                $extra->delete();
            });
        } catch (\Throwable $th) {
            return $request->wantsJson() || $request->ajax()
                ? response()->json(['message' => $th->getMessage()], 422)
                : back()->with('error', $th->getMessage());
        }

        return $request->wantsJson() || $request->ajax()
            ? response()->json(['message' => 'Tambahan sewa ' . $kode . ' dibatalkan.'])
            : back()->with('success', 'Tambahan sewa ' . $kode . ' dibatalkan.');
    }
}
