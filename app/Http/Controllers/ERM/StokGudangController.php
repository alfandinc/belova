<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\ObatStokGudang;
use App\Models\ERM\Obat;
use App\Models\ERM\Gudang;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StokGudangController extends Controller
{
    public function index()
    {
        $gudangs = Gudang::all();
        $defaultGudang = $gudangs->first(); // Get first warehouse as default
        return view('erm.stok-gudang.index', compact('gudangs', 'defaultGudang'));
    }

    public function getData(Request $request)
    {
        $query = ObatStokGudang::with(['obat', 'gudang'])
            ->select(
                'obat_id',
                'gudang_id',
                DB::raw('SUM(stok) as total_stok'),
                DB::raw('MIN(min_stok) as min_stok'),
                DB::raw('MAX(max_stok) as max_stok')
            )
            ->groupBy('obat_id', 'gudang_id');

        // Filter by gudang
        if ($request->gudang_id) {
            $query->where('gudang_id', $request->gudang_id);
        } else {
            // If no gudang selected, use the first one
            $defaultGudang = Gudang::first();
            if ($defaultGudang) {
                $query->where('gudang_id', $defaultGudang->id);
            }
        }

        // Search obat by name or code
        if ($request->search_obat) {
            $searchTerm = $request->search_obat;
            $query->whereHas('obat', function($q) use ($searchTerm) {
                $q->where('nama', 'like', "%{$searchTerm}%")
                  ->orWhere('kode_obat', 'like', "%{$searchTerm}%");
            });
        }

        return DataTables::of($query)
            ->addColumn('nama_obat', function ($row) {
                return $row->obat->nama ?? '-';
            })
            ->addColumn('kode_obat', function ($row) {
                return $row->obat->kode_obat ?? '-';
            })
            ->addColumn('nama_gudang', function ($row) {
                return $row->gudang->nama ?? '-';
            })
            ->addColumn('actions', function ($row) {
                return '<button class="btn btn-sm btn-info show-batch-details" data-obat-id="'.$row->obat_id.'" data-gudang-id="'.$row->gudang_id.'">
                    <i class="fas fa-list"></i> Detail Batch
                </button>';
            })
            ->addColumn('status_stok', function ($row) {
                $status = '';
                $statusClass = '';
                
                if ($row->total_stok <= $row->min_stok) {
                    $status = 'minimum';
                    $statusClass = '<span class="badge badge-danger">Stok Minimum</span>';
                } elseif ($row->total_stok >= $row->max_stok) {
                    $status = 'maksimum';
                    $statusClass = '<span class="badge badge-warning">Stok Maksimum</span>';
                } else {
                    $status = 'normal';
                    $statusClass = '<span class="badge badge-success">Normal</span>';
                }
                
                return $statusClass;
            })
            ->editColumn('total_stok', function ($row) {
                return number_format($row->total_stok, 0);
            })
            ->filterColumn('status_stok', function($query, $keyword) {
                // Custom filter untuk status stok akan dihandle di client side
            })
            ->rawColumns(['status_stok', 'actions'])
            ->make(true);
    }

    public function getBatchDetails(Request $request)
    {
        $obatId = $request->obat_id;
        $gudangId = $request->gudang_id;

        $stokGudang = ObatStokGudang::with(['obat', 'gudang'])
            ->where('obat_id', $obatId)
            ->where('gudang_id', $gudangId)
            ->get();

        $details = $stokGudang->map(function ($item) {
            return [
                'batch' => $item->batch,
                'stok' => number_format($item->stok, 0),
                'expiration_date' => $item->expiration_date ? Carbon::parse($item->expiration_date)->format('d-m-Y') : '-',
                'status' => $this->getExpirationStatus($item->expiration_date)
            ];
        });

        $first = $stokGudang->first();
        
        return response()->json([
            'data' => $details,
            'obat' => $first ? $first->obat->nama : '',
            'gudang' => $first ? $first->gudang->nama : ''
        ]);
    }

    private function getExpirationStatus($date)
    {
        if (!$date) {
            return '<span class="badge badge-secondary">Tidak Ada Tanggal</span>';
        }
        
        $expDate = Carbon::parse($date);
        $now = Carbon::now();
        
        if ($expDate->isPast()) {
            return '<span class="badge badge-danger">Expired</span>';
        }
        
        if ($expDate->diffInMonths($now) <= 3) {
            return '<span class="badge badge-warning">Hampir Expired</span>';
        }
        
        return '<span class="badge badge-success">Aman</span>';
    }
}
