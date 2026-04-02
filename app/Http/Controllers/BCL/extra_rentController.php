<?php

namespace App\Http\Controllers\BCL;

use App\Models\BCL\ExtraBedAsset;
use App\Models\BCL\ExtraBedAssignment;
use App\Models\BCL\extra_pricelist;
use App\Models\BCL\tb_extra_rent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class extra_rentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }
    public function extra_rent()
    {
    }
    /**
     * Store a newly created resource in storage.
     */

    public function get_kode()
    {
        $data = DB::select("SELECT
        CONCAT(
            'TBH-',
            DATE_FORMAT( STR_TO_DATE( now(), '%Y-%m-%d' ), '%m%y' ),
            '',
        LPAD( count(*) + 1, 4, '0' )) AS kode 
    FROM
        bcl_extra_rent 
    WHERE
        MONTH ( tgl_mulai )= MONTH (
        STR_TO_DATE( now(), '%Y-%m-%d' )) 
        AND YEAR ( tgl_mulai )= YEAR (
        STR_TO_DATE( now(), '%Y-%m-%d' ))");
        $result = $data[0];
        return $result->kode;
    }

    protected function calculateEndDate(extra_pricelist $extraPricelist, string $startDate, int $duration): string
    {
        switch ($extraPricelist->jangka_sewa) {
            case 'Hari':
                return Carbon::parse($startDate)->addDays($duration)->format('Y-m-d');
            case 'Minggu':
                return Carbon::parse($startDate)->addWeeks($duration)->format('Y-m-d');
            case 'Bulan':
                return Carbon::parse($startDate)->addMonths($duration)->format('Y-m-d');
            case 'Tahun':
                return Carbon::parse($startDate)->addYears($duration)->format('Y-m-d');
            default:
                return Carbon::parse($startDate)->addDays($duration)->format('Y-m-d');
        }
    }

    protected function buildAvailabilityPayload(extra_pricelist $extraPricelist, string $startDate, int $duration): array
    {
        $endDate = $this->calculateEndDate($extraPricelist, $startDate, $duration);

        if (!$extraPricelist->requiresExtraBedTracking()) {
            return [
                'tracked' => false,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        }

        ExtraBedAsset::ensureDefaultAssets();

        $availableAssets = ExtraBedAsset::availableBetween($startDate, $endDate)
            ->orderBy('asset_code')
            ->get();

        return [
            'tracked' => true,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_count' => ExtraBedAsset::active()->count(),
            'available_count' => $availableAssets->count(),
            'available_asset_codes' => $availableAssets->pluck('asset_code')->values()->all(),
        ];
    }

    public function availability(Request $request)
    {
        $this->validate($request, [
            'pricelist' => 'required|exists:bcl_extra_pricelist,id',
            'tgl_sewa' => 'required|date',
            'lama_sewa' => 'required|integer|min:1',
        ]);

        $extraPricelist = extra_pricelist::findOrFail($request->pricelist);

        return response()->json($this->buildAvailabilityPayload(
            $extraPricelist,
            Carbon::parse($request->tgl_sewa)->format('Y-m-d'),
            (int) $request->lama_sewa
        ));
    }

    public function store(Request $request)
    {
        $kode = $this->get_kode();
        DB::beginTransaction();
        try {
            $this->validate($request, [
                'trans_id' => 'required',
                'pricelist' => 'required|exists:bcl_extra_pricelist,id',
                'jml_item' => 'required|integer|min:1',
                'lama_sewa' => 'required|integer|min:1',
                'tgl_sewa' => 'required|date',
            ]);

            $extra_pl = extra_pricelist::findOrFail($request->pricelist);
            $lama = (int) $request->lama_sewa;
            $qty = (int) $request->jml_item;
            $tglMulai = Carbon::parse($request->tgl_sewa)->format('Y-m-d');
            $tglSelesai = $this->calculateEndDate($extra_pl, $tglMulai, $lama);

            $assignedAssets = collect();
            if ($extra_pl->requiresExtraBedTracking()) {
                ExtraBedAsset::ensureDefaultAssets();

                $availableAssets = ExtraBedAsset::availableBetween($tglMulai, $tglSelesai)
                    ->orderBy('asset_code')
                    ->lockForUpdate()
                    ->get();

                if ($availableAssets->count() < $qty) {
                    DB::rollBack();

                    return back()->withInput()->with([
                        'error' => 'Extra bed tidak mencukupi. Tersedia ' . $availableAssets->count() . ' dari ' . ExtraBedAsset::active()->count() . ' unit untuk periode tersebut.',
                    ]);
                }

                $assignedAssets = $availableAssets->take($qty)->values();
            }

            $store = tb_extra_rent::create([
                'kode' => $kode,
                'parent_trans' => $request->trans_id,
                'nama' => $extra_pl->nama,
                'qty' => $qty,
                'lama_sewa' => $lama,
                'jangka_sewa' => $extra_pl->jangka_sewa,
                'tgl_mulai' => $tglMulai,
                'tgl_selesai' => $tglSelesai,
                'harga' => $extra_pl->harga,
            ]);

            foreach ($assignedAssets as $asset) {
                ExtraBedAssignment::create([
                    'extra_bed_asset_id' => $asset->id,
                    'extra_rent_id' => $store->id,
                    'assigned_from' => $tglMulai,
                    'assigned_until' => $tglSelesai,
                ]);
            }

            DB::commit();

            $assetNotice = $assignedAssets->isEmpty()
                ? ''
                : ' Unit: ' . $assignedAssets->pluck('asset_code')->implode(', ');

            return back()->with(['success' => 'Data berhasil disimpan.' . $assetNotice]);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(tb_extra_rent $tb_extra_rent)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(tb_extra_rent $tb_extra_rent, Request $request)
    {
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, tb_extra_rent $tb_extra_rent)
    {
       
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(tb_extra_rent $tb_extra_rent)
    {
        //
    }
}
