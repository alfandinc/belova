<?php
namespace App\Http\Controllers\Laporan;

use App\Exports\Laporan\RekapPembelianExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ERM\FakturBeliItem;
use Barryvdh\DomPDF\Facade\Pdf;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exports\Laporan\PenjualanObatExport;
use App\Exports\Laporan\StokTanggalExport;

class FarmasiController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = FakturBeliItem::with(['fakturbeli.pemasok', 'obat.principals'])
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
                ->addColumn('principal', function($item) {
                    if (!$item->obat) return '';
                    // obat->principals is a relation (many-to-many)
                    $names = $item->obat->principals->pluck('nama')->filter()->values()->all();
                    return is_array($names) ? implode(', ', $names) : '';
                })
                ->addColumn('nama_pemasok', function($item) {
                    return optional($item->fakturbeli->pemasok)->nama;
                })
                ->addColumn('nama_obat', function($item) {
                    return optional($item->obat)->nama;
                })
                ->addColumn('harga_beli', function($item) {
                    return number_format($item->harga, 2);
                })
                ->addColumn('quantity', function($item) {
                    return $item->qty ?? 1;
                })
                ->addColumn('diskon_persen', function($item) {
                    $diskon = $item->diskon ?? 0;
                    $diskonType = $item->diskon_type ?? 'nominal';
                    $dt = strtolower(trim((string) $diskonType));
                    $isPercent = in_array($dt, ['persen', 'percent', '%', 'pct', 'pc', 'per']);
                    return $isPercent ? $diskon : '';
                })
                ->addColumn('diskon_nominal', function($item) {
                    $diskon = $item->diskon ?? 0;
                    $diskonType = $item->diskon_type ?? 'nominal';
                    $qty = $item->qty ?? 1;
                    $base = ($item->harga ?? 0) * $qty;
                    $dt = strtolower(trim((string) $diskonType));
                    $isPercent = in_array($dt, ['persen', 'percent', '%', 'pct', 'pc', 'per']);
                    $diskonValue = $isPercent ? ($base * $diskon / 100) : $diskon;
                    return number_format($diskonValue, 2);
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
                ->rawColumns(['nama_pemasok', 'nama_obat', 'harga_beli', 'quantity', 'diskon_persen', 'diskon_nominal', 'harga_jadi'])
                ->make(true);
        }
        
        // Get available categories for filter
        $kategoris = \App\Models\ERM\Obat::withInactive()
            ->select('kategori')
            ->distinct()
            ->whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->pluck('kategori')
            ->sort();
            
        return view('laporan.farmasi.index', compact('kategoris'));
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
            $query = \App\Models\Finance\InvoiceItem::with([
                'billable', 
                'billable.obat', 
                'invoice', 
                'invoice.visitation',
                'invoice.visitation.pasien',
                'billable.resepDetail'
            ])
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
                ->addColumn('invoice_number', function($item) {
                    return $item->invoice ? $item->invoice->invoice_number : '-';
                })
                ->addColumn('nama_pasien', function($item) {
                    return $item->invoice && $item->invoice->visitation && $item->invoice->visitation->pasien 
                        ? $item->invoice->visitation->pasien->nama 
                        : '-';
                })
                ->addColumn('no_resep', function($item) {
                    if ($item->billable && $item->billable->visitation_id) {
                        $resepDetail = \App\Models\ERM\ResepDetail::where('visitation_id', $item->billable->visitation_id)->first();
                        return $resepDetail ? $resepDetail->no_resep : '-';
                    }
                    return '-';
                })
                ->addColumn('nama_obat', function($item) {
                    return $item->billable->obat->nama;
                })
                ->addColumn('harga_jual', function($item) {
                    return number_format($item->unit_price, 2);
                })
                ->addColumn('quantity', function($item) {
                    return $item->quantity ?? 1;
                })
                ->addColumn('diskon_persen', function($item) {
                    $discount = $item->discount ?? 0;
                    $discountType = $item->discount_type ?? 'nominal';
                    $dt = strtolower(trim((string) $discountType));
                    $isPercent = in_array($dt, ['persen', 'percent', '%', 'pct', 'pc', 'per']);
                    return $isPercent ? $discount : '';
                })
                ->addColumn('diskon_nominal', function($item) {
                    $discount = $item->discount ?? 0;
                    $discountType = $item->discount_type ?? 'nominal';
                    $qty = $item->quantity ?? 1;
                    $base = ($item->unit_price ?? 0) * $qty;
                    $dt = strtolower(trim((string) $discountType));
                    $isPercent = in_array($dt, ['persen', 'percent', '%', 'pct', 'pc', 'per']);
                    $discountValue = $isPercent ? ($base * $discount / 100) : $discount;
                    return number_format($discountValue, 2);
                })
                ->addColumn('diskon_pelayanan', function($item) {
                    return ($item->discount ?? 0) > 0 ? 'Ada' : 'Tidak';
                })
                ->rawColumns(['invoice_number', 'nama_pasien', 'no_resep', 'nama_obat', 'harga_jual', 'quantity', 'diskon_pelayanan', 'diskon_persen', 'diskon_nominal'])
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
        $query = \App\Models\Finance\InvoiceItem::with([
            'billable', 
            'billable.obat', 
            'invoice', 
            'invoice.visitation',
            'invoice.visitation.pasien',
            'billable.resepDetail'
        ])
            ->where('billable_type', 'App\\Models\\ERM\\ResepFarmasi');
        if ($dateRange) {
            $dates = explode(' - ', $dateRange);
            if (count($dates) === 2) {
                $start = $dates[0] . ' 00:00:00';
                $end = $dates[1] . ' 23:59:59';
                $query->whereHas('invoice', function($q) use ($start, $end) {
                    $q->whereBetween('payment_date', [$start, $end]);
                });
            }
        }
        $items = $query->get()->filter(function($item) {
            return $item->billable && $item->billable->obat;
        })->map(function($item) {
            // compute discount fields for the pdf view convenience
            $discount = $item->discount ?? 0;
            $discountType = $item->discount_type ?? 'nominal';
            $qty = $item->quantity ?? 1;
            $base = ($item->unit_price ?? 0) * $qty;
            $dt = strtolower(trim((string) $discountType));
            $isPercent = in_array($dt, ['persen', 'percent', '%', 'pct', 'pc', 'per']);
            $discountValue = $isPercent ? ($base * $discount / 100) : $discount;
            
            // Get additional data
            $invoiceNumber = $item->invoice ? $item->invoice->invoice_number : '-';
            $namaPasien = $item->invoice && $item->invoice->visitation && $item->invoice->visitation->pasien 
                ? $item->invoice->visitation->pasien->nama 
                : '-';
            
            $noResep = '-';
            if ($item->billable && $item->billable->visitation_id) {
                $resepDetail = \App\Models\ERM\ResepDetail::where('visitation_id', $item->billable->visitation_id)->first();
                $noResep = $resepDetail ? $resepDetail->no_resep : '-';
            }
            
            return (object) array_merge($item->toArray(), [
                'invoice_number' => $invoiceNumber,
                'nama_pasien' => $namaPasien,
                'no_resep' => $noResep,
                'diskon_nominal' => number_format($discountValue, 2),
                'diskon_persen' => $isPercent ? $discount : '',
            ]);
        });
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('laporan.farmasi.penjualan_pdf', compact('items'));
        return $pdf->download('rekap_penjualan_obat.pdf');
    }

    public function stokTanggal(Request $request)
    {
        // Get selected date from request - no default, return empty if not provided
        $selectedDate = $request->input('selected_date');
        if (!$selectedDate) {
            return \Yajra\DataTables\DataTables::of(collect([]))->make(true);
        }
        
        $kategori = $request->input('kategori');
        $today = now()->format('Y-m-d');
        
        // Get all obat with their current stock from ObatStokGudang
        $query = \App\Models\ERM\ObatStokGudang::with(['obat', 'gudang'])
            ->select(
                'obat_id',
                \Illuminate\Support\Facades\DB::raw('SUM(stok) as current_total_stock')
            )
            ->whereHas('obat', function($q) {
                $q->where('status_aktif', 1); // Only active medicines
            })
            ->groupBy('obat_id');

        // Add kategori filter if provided
        if ($kategori) {
            $query->whereHas('obat', function($q) use ($kategori) {
                $q->where('kategori', $kategori)
                  ->where('status_aktif', 1); // Ensure still only active
            });
        }

        $stokData = $query->get();
        
        $results = [];
        
        foreach ($stokData as $item) {
            if (!$item->obat) continue;
            
            $currentStock = $item->current_total_stock ?? 0;
            
            // If selected date is today or in the future, use current stock
            if ($selectedDate >= $today) {
                $stockOnDate = $currentStock;
            } else {
                // Calculate stock on selected date by subtracting outgoing stock
                // from the day after selected date until today
                $startDate = \Carbon\Carbon::parse($selectedDate)->addDay()->format('Y-m-d H:i:s');
                $endDate = \Carbon\Carbon::parse($today)->endOfDay()->format('Y-m-d H:i:s');
                
                $outgoingStock = \App\Models\ERM\KartuStok::where('obat_id', $item->obat_id)
                    ->where('tipe', 'keluar')
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->sum('qty');
                
                $stockOnDate = $currentStock + $outgoingStock;
            }
            
            $results[] = [
                'obat_id' => $item->obat_id,
                'nama_obat' => $item->obat->nama,
                'kode_obat' => $item->obat->kode_obat ?? '',
                'kategori' => $item->obat->kategori ?? '',
                'satuan' => $item->obat->satuan ?? '',
                'stok_current' => $currentStock,
                'stok_on_date' => max(0, $stockOnDate), // Ensure non-negative stock
                'selected_date' => $selectedDate
            ];
        }
        
        // Sort by obat name
        usort($results, function($a, $b) {
            return strcmp($a['nama_obat'], $b['nama_obat']);
        });
        
        return \Yajra\DataTables\DataTables::of(collect($results))
            ->addColumn('nama_obat', function($item) {
                return $item['nama_obat'];
            })
            ->addColumn('kode_obat', function($item) {
                return $item['kode_obat'];
            })
            ->addColumn('kategori', function($item) {
                return $item['kategori'];
            })
            ->addColumn('satuan', function($item) {
                return $item['satuan'];
            })
            ->addColumn('stok_on_date', function($item) {
                return number_format($item['stok_on_date'], 0);
            })
            ->addColumn('stok_current', function($item) {
                return number_format($item['stok_current'], 0);
            })
            ->addColumn('status_stok', function($item) {
                $stock = $item['stok_on_date'];
                if ($stock <= 0) {
                    return '<span class="badge badge-danger">Kosong</span>';
                } elseif ($stock < 10) {
                    return '<span class="badge badge-warning">Rendah</span>';
                } else {
                    return '<span class="badge badge-success">Tersedia</span>';
                }
            })
            ->rawColumns(['status_stok'])
            ->make(true);
    }

    public function exportStokTanggalExcel(Request $request)
    {
        $selectedDate = $request->input('selected_date');
        if (!$selectedDate) {
            return back()->with('error', 'Tanggal harus dipilih untuk export data.');
        }
        
        $kategori = $request->input('kategori');
        $today = now()->format('Y-m-d');
        
        // Get all obat with their current stock from ObatStokGudang
        $query = \App\Models\ERM\ObatStokGudang::with(['obat', 'gudang'])
            ->select(
                'obat_id',
                \Illuminate\Support\Facades\DB::raw('SUM(stok) as current_total_stock')
            )
            ->whereHas('obat', function($q) {
                $q->where('status_aktif', 1); // Only active medicines
            })
            ->groupBy('obat_id');

        // Add kategori filter if provided
        if ($kategori) {
            $query->whereHas('obat', function($q) use ($kategori) {
                $q->where('kategori', $kategori)
                  ->where('status_aktif', 1); // Ensure still only active
            });
        }

        $stokData = $query->get();
        
        $results = [];
        
        foreach ($stokData as $item) {
            if (!$item->obat) continue;
            
            $currentStock = $item->current_total_stock ?? 0;
            
            // If selected date is today or in the future, use current stock
            if ($selectedDate >= $today) {
                $stockOnDate = $currentStock;
            } else {
                // Calculate stock on selected date by subtracting outgoing stock
                // from the day after selected date until today
                $startDate = \Carbon\Carbon::parse($selectedDate)->addDay()->format('Y-m-d H:i:s');
                $endDate = \Carbon\Carbon::parse($today)->endOfDay()->format('Y-m-d H:i:s');
                
                $outgoingStock = \App\Models\ERM\KartuStok::where('obat_id', $item->obat_id)
                    ->where('tipe', 'keluar')
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->sum('qty');
                
                $stockOnDate = $currentStock + $outgoingStock;
            }
            
            $results[] = [
                'obat_id' => $item->obat_id,
                'nama_obat' => $item->obat->nama,
                'kode_obat' => $item->obat->kode_obat ?? '',
                'kategori' => $item->obat->kategori ?? '',
                'satuan' => $item->obat->satuan ?? '',
                'stok_current' => $currentStock,
                'stok_on_date' => max(0, $stockOnDate), // Ensure non-negative stock
                'selected_date' => $selectedDate
            ];
        }
        
        // Sort by obat name
        usort($results, function($a, $b) {
            return strcmp($a['nama_obat'], $b['nama_obat']);
        });
        
        $filename = 'stok_obat_' . $selectedDate;
        if ($kategori) {
            $filename .= '_' . $kategori;
        }
        $filename .= '.xlsx';
        
        return Excel::download(new StokTanggalExport($results, $selectedDate), $filename);
    }

}
