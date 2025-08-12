<?php
namespace App\Http\Controllers\Laporan;

use App\Exports\Laporan\RekapPembelianExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ERM\FakturBeliItem;
use Barryvdh\DomPDF\Facade\Pdf;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exports\Laporan\PenjualanObatExport;

class FarmasiController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = FakturBeliItem::with(['fakturbeli.pemasok', 'obat'])
                ->whereHas('fakturbeli', function($q) use ($request) {
                    $q->where('status', 'diapprove');
                    if ($request->filled('date_range')) {
                        $dates = explode(' - ', $request->input('date_range'));
                        if (count($dates) === 2) {
                            $start = $dates[0] . ' 00:00:00';
                            $end = $dates[1] . ' 23:59:59';
                            $q->whereBetween('received_date', [$start, $end]);
                        }
                    }
                });
            return \Yajra\DataTables\DataTables::of($query)
                ->addColumn('nama_pemasok', function($item) {
                    return optional($item->fakturbeli->pemasok)->nama;
                })
                ->addColumn('nama_obat', function($item) {
                    return optional($item->obat)->nama;
                })
                ->addColumn('harga_beli', function($item) {
                    return number_format($item->harga, 2);
                })
                ->addColumn('diskon', function($item) {
                    $diskon = $item->diskon ?? 0;
                    $diskonType = $item->diskon_type ?? 'nominal';
                    return $diskon . ($diskonType === 'persen' ? '%' : '');
                })
                ->addColumn('harga_jadi', function($item) {
                    $harga = $item->harga;
                    $diskon = $item->diskon ?? 0;
                    $diskonType = $item->diskon_type ?? 'nominal';
                    $tax = $item->tax ?? 0;
                    $taxType = $item->tax_type ?? 'nominal';
                    $qty = $item->qty ?? 1;
                    $base = $harga * $qty;
                    $diskonValue = $diskonType === 'persen' ? ($base * $diskon / 100) : $diskon;
                    $taxValue = $taxType === 'persen' ? ($base * $tax / 100) : $tax;
                    $hargaJadi = $base - $diskonValue + $taxValue;
                    return number_format($hargaJadi, 2);
                })
                ->rawColumns(['nama_pemasok', 'nama_obat', 'harga_beli', 'diskon', 'harga_jadi'])
                ->make(true);
        }
        return view('laporan.farmasi.index');
    }

    public function exportExcel()
    {
        return Excel::download(new RekapPembelianExport, 'rekap_pembelian.xlsx');
    }

    public function exportPdf()
    {
        $items = FakturBeliItem::with(['fakturbeli.pemasok', 'obat'])
            ->whereHas('fakturbeli', function($q) {
                $q->where('status', 'diapprove');
            })
            ->get();
        $pdf = Pdf::loadView('laporan.farmasi.rekap_pdf', compact('items'));
        return $pdf->download('rekap_pembelian.pdf');
    }

        public function penjualanObat(Request $request)
        {
            $query = \App\Models\Finance\InvoiceItem::with(['billable', 'billable.obat', 'invoice'])
                ->where('billable_type', 'App\\Models\\ERM\\ResepFarmasi');
            if ($request->filled('date_range')) {
                $dates = explode(' - ', $request->input('date_range'));
                if (count($dates) === 2) {
                    $start = $dates[0] . ' 00:00:00';
                    $end = $dates[1] . ' 23:59:59';
                    $query->whereHas('invoice', function($q) use ($start, $end) {
                        $q->whereBetween('payment_date', [$start, $end]);
                    });
                }
            }
            $collection = $query->get()->filter(function($item) {
                return $item->billable && $item->billable->obat;
            });
            return \Yajra\DataTables\DataTables::of($collection)
                ->addColumn('nama_obat', function($item) {
                    return $item->billable->obat->nama;
                })
                ->addColumn('harga_jual', function($item) {
                    return number_format($item->unit_price, 2);
                })
                ->addColumn('diskon_pelayanan', function($item) {
                    return ($item->discount ?? 0) > 0 ? 'Ada' : 'Tidak';
                })
                ->rawColumns(['nama_obat', 'harga_jual', 'diskon_pelayanan'])
                ->make(true);
        }

    
    public function exportPenjualanExcel(Request $request)
    {
        $dateRange = $request->input('date_range');
        return \Maatwebsite\Excel\Facades\Excel::download(new PenjualanObatExport($dateRange), 'rekap_penjualan_obat.xlsx');
    }

    public function exportPenjualanPdf(Request $request)
    {
        $dateRange = $request->input('date_range');
        $query = \App\Models\ERM\FakturBeliItem::with(['obat', 'fakturbeli'])
            ->whereHas('fakturbeli', function($q) {
                $q->where('status', 'diapprove');
            });
        if ($dateRange) {
            $dates = explode(' - ', $dateRange);
            if (count($dates) === 2) {
                $start = $dates[0] . ' 00:00:00';
                $end = $dates[1] . ' 23:59:59';
                $query->whereHas('fakturbeli', function($q) use ($start, $end) {
                    $q->whereBetween('received_date', [$start, $end]);
                });
            }
        }
        $items = $query->get();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('laporan.farmasi.penjualan_pdf', compact('items'));
        return $pdf->download('rekap_penjualan_obat.pdf');
    }

}
