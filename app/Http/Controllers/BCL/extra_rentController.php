<?php

namespace App\Http\Controllers\BCL;

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
        tb_extra_rent 
    WHERE
        MONTH ( tgl_mulai )= MONTH (
        STR_TO_DATE( now(), '%Y-%m-%d' )) 
        AND YEAR ( tgl_mulai )= YEAR (
        STR_TO_DATE( now(), '%Y-%m-%d' ))");
        $result = $data[0];
        return $result->kode;
    }
    public function store(Request $request)
    {
        $kode = $this->get_kode();
        try {
            $this->validate($request, [
                'trans_id' => 'required',
                'pricelist' => 'required',
                'jml_item' => 'required',
                'lama_sewa' => 'required',
                'tgl_sewa' => 'required',
            ]);
            $extra_pl = extra_pricelist::find($request->pricelist);
            switch ($extra_pl->jangka_sewa) {
                case 'Hari':
                    $tgl_selesai = Carbon::parse($request->tgl_sewa)->addDays($request->lama_sewa)->format('Y-m-d');
                    break;
                case 'Minggu':
                    $tgl_selesai = Carbon::parse($request->tgl_sewa)->addWeeks($request->lama_sewa)->format('Y-m-d');
                    break;
                case 'Bulan':
                    $tgl_selesai = Carbon::parse($request->tgl_sewa)->addMonths($request->lama_sewa)->format('Y-m-d');
                    break;
                case 'Tahun':
                    $tgl_selesai = Carbon::parse($request->tgl_sewa)->addYears($request->lama_sewa)->format('Y-m-d');
                    break;
                default:
                    $tgl_selesai = Carbon::parse($request->tgl_sewa)->addDays($request->lama_sewa)->format('Y-m-d');
                    break;
            }
            $store = tb_extra_rent::create([
                'kode' => $kode,
                'parent_trans' => $request->trans_id,
                'nama' => $extra_pl->nama,
                'qty' => $request->jml_item,
                'lama_sewa' => $request->lama_sewa,
                'jangka_sewa' => $extra_pl->jangka_sewa,
                'tgl_mulai' => $request->tgl_sewa,
                'tgl_selesai' => $tgl_selesai,
                'harga' => $extra_pl->harga,
            ]);
            return back()->with(['success' => 'Data berhasil disimpan']);
        } catch (\Exception $e) {
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
