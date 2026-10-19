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
        ]);

        try {
            $this->validate($request, [
                'trans_id' => 'required|exists:bcl_tr_renter,trans_id',
                'pricelist' => 'required|exists:bcl_extra_pricelist,id',
                'jml_item' => 'required|integer|min:1',
                'lama_sewa' => 'required|integer|min:1',
                'tgl_sewa' => 'required|date',
            ]);
        } catch (ValidationException $e) {
            return back()->withInput()->with('error', collect($e->errors())->flatten()->first());
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
}
