<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ERM\Obat;
use App\Models\ERM\Tindakan;
use App\Models\ERM\LabTest;
use Yajra\DataTables\Facades\DataTables;

class BukuMenuController extends Controller
{
    public function index()
    {
        return view('buku_menu.index');
    }

    public function data(Request $request)
    {
        // Obat model has a global scope to show only active (`status_aktif = 1`).
        $query = Obat::query()
            ->select(['id', 'nama', 'satuan', 'harga_nonfornas'])
            ->withSum('stokGudang as total_stok', 'stok');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('total_stok', function ($row) {
                $val = (float) ($row->total_stok ?? 0);
                // keep 2 decimals only if needed
                if (abs($val - round($val)) < 0.00001) {
                    return number_format($val, 0, ',', '.');
                }
                return number_format($val, 2, ',', '.');
            })
            ->editColumn('harga_nonfornas', function ($row) {
                $val = $row->harga_nonfornas;
                if ($val === null || $val === '') {
                    return '-';
                }
                return 'Rp ' . number_format((float) $val, 0, ',', '.');
            })
            ->make(true);
    }

    public function tindakanData(Request $request)
    {
        $query = Tindakan::query()
            ->select(['id', 'nama', 'harga', 'harga_diskon', 'diskon_active', 'harga_3_kali'])
            ->where('is_active', 1)
            ->with('kodeTindakans:id,nama');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('kode_tindakan_names', function($row){
                if ($row->kodeTindakans && $row->kodeTindakans->count()) {
                    $items = $row->kodeTindakans->pluck('nama')->map(function($n){ return '<li>' . e($n) . '</li>'; })->implode('');
                    return '<ul class="kode-list">' . $items . '</ul>';
                }
                return '-';
            })
            ->addColumn('jenis_harga', function ($row) {
                $lines = [];
                $lines[] = '<div class="row"><div class="label">Normal</div></div>';
                if ($row->harga_3_kali !== null && $row->harga_3_kali !== '') {
                    $lines[] = '<div class="row"><div class="label">3x Visit</div></div>';
                }
                return '<div class="price-block jenis-harga">' . implode('', $lines) . '</div>';
            })
            ->addColumn('list_harga', function ($row) {
                $fmt = function($v){ return 'Rp ' . number_format((float)$v, 0, ',', '.'); };

                $harga = $row->harga ?? 0;
                $harga_diskon = $row->harga_diskon;
                $harga_3 = $row->harga_3_kali;

                // Build normal price row: original (crossed) on the left, active price on the right
                $original = '';
                $active = '';
                if (!empty($row->diskon_active) && $harga_diskon !== null && $harga_diskon !== '' && (float)$harga_diskon < (float)$harga) {
                    $original = $fmt($harga);
                    $active = $fmt($harga_diskon);
                } else {
                    $original = '';
                    $active = $fmt($harga);
                }

                $rowsHtml = '';
                $rowsHtml .= '<div class="price-row"><div class="original-price">' . ($original ? $fmt($harga) : '') . '</div><div class="active-price">' . $active . '</div></div>';

                if ($harga_3 !== null && $harga_3 !== '') {
                    $rowsHtml .= '<div class="price-row"><div class="original-price"></div><div class="active-price">' . $fmt($harga_3) . '</div></div>';
                }

                return '<div class="price-block list-harga">' . $rowsHtml . '</div>';
            })
            ->rawColumns(['jenis_harga','list_harga','kode_tindakan_names'])
            ->make(true);
    }

        public function labtestData(Request $request)
        {
            $query = LabTest::query()->with('labKategori')->select(['id', 'nama', 'lab_kategori_id', 'harga']);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('kategori', function ($row) {
                    return $row->labKategori ? $row->labKategori->nama : '-';
                })
                ->editColumn('harga', function ($row) {
                    return $row->harga ? 'Rp ' . number_format((float) $row->harga, 0, ',', '.') : '-';
                })
                ->rawColumns([])
                ->make(true);
        }
}
