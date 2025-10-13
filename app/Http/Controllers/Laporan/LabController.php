<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\LabPermintaan;
use App\Models\ERM\Visitation;
use App\Models\ERM\LabTest;
use App\Models\Finance\InvoiceItem;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;

class LabController extends Controller
{
        // Monthly stats for lab requests (for chart)
    public function monthlyStats(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $stats = LabPermintaan::selectRaw('MONTH(created_at) as month, COUNT(*) as jumlah')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $labels = [];
        $values = [];
        for ($m = 1; $m <= 12; $m++) {
            $labels[] = date('M', mktime(0,0,0,$m,1));
            $found = $stats->firstWhere('month', $m);
            $values[] = $found ? $found->jumlah : 0;
        }
        return response()->json(['labels' => $labels, 'values' => $values]);
    }

    // AJAX endpoint for grouped visitation data
    public function groupedData(Request $request)
    {
        $query = Visitation::with([
            'pasien',
            'dokter.user',
            'klinik',
            'invoice',
            'labPermintaan.labTest',
        ])->whereHas('labPermintaan');

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dokterId = $request->input('dokter_id');
        $klinikId = $request->input('klinik_id');
        if ($startDate && $endDate) {
            $query = $query->whereBetween('tanggal_visitation', [$startDate, $endDate]);
        }
        if ($dokterId) {
            $query = $query->where('dokter_id', $dokterId);
        }
        if ($klinikId) {
            $query = $query->where('klinik_id', $klinikId);
        }

        return DataTables::eloquent($query)
            ->addColumn('pasien', function($row) {
                return $row->pasien->nama ?? '-';
            })
            ->addColumn('dokter', function($row) {
                return $row->dokter->user->name ?? $row->dokter->nama ?? '-';
            })
            ->addColumn('klinik', function($row) {
                return $row->klinik->nama ?? '-';
            })
            ->addColumn('invoice', function($row) {
                return $row->invoice ? $row->invoice->invoice_number : '-';
            })
            ->addColumn('total_harga_jual', function($row) {
                $total = 0;
                foreach ($row->labPermintaan as $permintaan) {
                    $invoiceItem = InvoiceItem::where('billable_type', LabPermintaan::class)
                        ->where('billable_id', $permintaan->id)->first();
                    $total += $invoiceItem ? floatval($invoiceItem->final_amount) : 0;
                }
                return 'Rp ' . number_format($total, 0, ',', '.');
            })
            ->addColumn('action', function($row) {
                return '<button class="btn btn-info btn-sm" onclick="showLabDetails(' . htmlspecialchars(json_encode($row->id)) . ')">Detail</button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    // AJAX endpoint for permintaan details modal
    public function permintaanDetails(Request $request, $visitationId)
    {
        $permintaan = LabPermintaan::with(['labTest'])->where('visitation_id', $visitationId)->get();
        \Log::info('Permintaan details for visitation', ['visitationId' => $visitationId, 'permintaan_count' => $permintaan->count()]);
        $details = $permintaan->map(function($row) {
            $invoiceItem = InvoiceItem::where('billable_type', LabPermintaan::class)
                ->where('billable_id', $row->id)->first();
            \Log::info('LabPermintaan detail', [
                'id' => $row->id,
                'nama_test' => $row->labTest->nama ?? '-',
                'harga' => $row->labTest->harga ?? '-',
                'harga_jual' => $invoiceItem ? $invoiceItem->final_amount : '-',
                'status' => $row->status ?? '-',
                'hasil' => $row->hasil ?? '-',
            ]);
            return [
                'nama_test' => $row->labTest->nama ?? '-',
                'harga' => $row->labTest->harga ?? '-',
                'harga_jual' => $invoiceItem ? $invoiceItem->final_amount : '-',
                'status' => $row->status ?? '-',
                'hasil' => $row->hasil ?? '-',
            ];
        });
        return response()->json(['details' => $details]);
    }
    // Export lab report to Excel
    public function exportExcel(Request $request)
    {
        $query = LabPermintaan::with([
            'visitation.pasien',
            'labTest',
            'visitation.dokter',
            'visitation.klinik',
        ]);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dokterId = $request->input('dokter_id');
        $klinikId = $request->input('klinik_id');
        if ($startDate && $endDate) {
            $query = $query->whereHas('visitation', function($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal_visitation', [$startDate, $endDate]);
            });
        }
        if ($dokterId) {
            $query = $query->whereHas('visitation', function($q) use ($dokterId) {
                $q->where('dokter_id', $dokterId);
            });
        }
        if ($klinikId) {
            $query = $query->whereHas('visitation', function($q) use ($klinikId) {
                $q->where('klinik_id', $klinikId);
            });
        }
        $data = $query->get();

        // Prepare array for export
        $rows = [];
        foreach ($data as $row) {
            // Determine harga_jual: prefer InvoiceItem.final_amount, else use labTest.harga
            $ii = InvoiceItem::where('billable_type', LabPermintaan::class)->where('billable_id', $row->id)->first();
            $hargaJual = $ii ? $ii->final_amount : ($row->labTest->harga ?? '-');
            $rows[] = [
                'Tanggal Visit' => $row->visitation->tanggal_visitation ?? '-',
                'Pasien' => $row->visitation->pasien->nama ?? '-',
                'Nama Test' => $row->labTest->nama ?? '-',
                'Dokter' => $row->visitation->dokter->user->name ?? $row->visitation->dokter->nama ?? '-',
                'Klinik' => $row->visitation->klinik->nama ?? '-',
                'Harga' => $row->labTest->harga ?? '-',
                'Harga Jual' => $hargaJual,
            ];
        }

        // Use Laravel Excel if available, else fallback to CSV
        if (class_exists('Maatwebsite\\Excel\\Facades\\Excel')) {
            return \Maatwebsite\Excel\Facades\Excel::download(new class($rows) implements \Maatwebsite\Excel\Concerns\FromArray {
                protected $rows;
                public function __construct($rows) { $this->rows = $rows; }
                public function array(): array { return $this->rows; }
            }, 'laporan-laboratorium.xlsx');
        } else {
            // Fallback: CSV
            $filename = 'laporan-laboratorium.csv';
            $handle = fopen('php://temp', 'r+');
            fputcsv($handle, array_keys($rows[0] ?? []));
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            rewind($handle);
            $csv = stream_get_contents($handle);
            fclose($handle);
            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
        }
    }

    // Print lab report to PDF
    public function printPdf(Request $request)
    {
        $query = LabPermintaan::with([
            'visitation.pasien',
            'labTest',
            'visitation.dokter',
            'visitation.klinik',
        ]);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dokterId = $request->input('dokter_id');
        $klinikId = $request->input('klinik_id');
        if ($startDate && $endDate) {
            $query = $query->whereHas('visitation', function($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal_visitation', [$startDate, $endDate]);
            });
        }
        if ($dokterId) {
            $query = $query->whereHas('visitation', function($q) use ($dokterId) {
                $q->where('dokter_id', $dokterId);
            });
        }
        if ($klinikId) {
            $query = $query->whereHas('visitation', function($q) use ($klinikId) {
                $q->where('klinik_id', $klinikId);
            });
        }
        $data = $query->get();

        $rows = [];
        foreach ($data as $row) {
            // Get invoice number from visitation->invoice
            $invoiceNumber = $row->visitation && $row->visitation->invoice ? $row->visitation->invoice->invoice_number : '-';
            $rows[] = [
                'Tanggal Visit' => $row->visitation->tanggal_visitation ?? '-',
                'Pasien' => $row->visitation->pasien->nama ?? '-',
                'Nama Test' => $row->labTest->nama ?? '-',
                'Dokter' => $row->visitation->dokter->user->name ?? $row->visitation->dokter->nama ?? '-',
                'Harga Jual' => optional($row->invoiceItem)->final_amount ?? '-',
                'Invoice' => $invoiceNumber,
            ];
        }

        // Use DomPDF if available
        if (class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('laporan.laboratorium.pdf', ['rows' => $rows]);
            return $pdf->download('laporan-laboratorium.pdf');
        } else {
            // Fallback: simple HTML
            $html = '<h2>Laporan Laboratorium</h2><table border="1" cellpadding="5" cellspacing="0"><tr>';
            foreach (array_keys($rows[0] ?? []) as $col) {
                $html .= '<th>'.$col.'</th>';
            }
            $html .= '</tr>';
            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($row as $val) {
                    $html .= '<td>'.$val.'</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>';
            return response($html);
        }
    }
    // List all dokters for filter dropdown
    public function listDokters(Request $request)
    {
        $dokters = \App\Models\ERM\Dokter::with('user')->get();
        $result = $dokters->map(function($dokter) {
            return [
                'id' => $dokter->id,
                'nama' => $dokter->nama ?? ($dokter->user->name ?? $dokter->user->username ?? $dokter->id),
            ];
        });
        return response()->json($result);
    }

    // List all kliniks for filter dropdown
    public function listKliniks(Request $request)
    {
        $kliniks = \App\Models\ERM\Klinik::all();
        $result = $kliniks->map(function($klinik) {
            return [
                'id' => $klinik->id,
                'nama' => $klinik->nama,
            ];
        });
        return response()->json($result);
    }
    // Show the lab report page
    public function index()
    {
        return view('laporan.laboratorium.index');
    }

    // AJAX endpoint for DataTable
    public function data(Request $request)
    {
    Log::info('LabController filter request', [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'dokter_id' => $request->input('dokter_id'),
            'klinik_id' => $request->input('klinik_id'),
        ]);
        $query = LabPermintaan::with([
            'visitation.pasien',
            'labTest',
            'visitation.invoice',
            'visitation.dokter',
            'visitation.klinik',
        ]);
        // Log available dokters and kliniks for debugging
        if ($request->has('debug')) {
            $dokters = \App\Models\ERM\Dokter::with('user')->get();
            $kliniks = \App\Models\ERM\Klinik::all();
            Log::info('Available dokters', $dokters->toArray());
            Log::info('Available kliniks', $kliniks->toArray());
        }

        // Filter by date range if provided
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dokterId = $request->input('dokter_id');
        $klinikId = $request->input('klinik_id');
        if ($startDate && $endDate) {
            $query = $query->whereHas('visitation', function($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal_visitation', [$startDate, $endDate]);
            });
        }
        if ($dokterId) {
            $query = $query->whereHas('visitation', function($q) use ($dokterId) {
                $q->where('dokter_id', $dokterId);
            });
        }
        if ($klinikId) {
            $query = $query->whereHas('visitation', function($q) use ($klinikId) {
                $q->where('klinik_id', $klinikId);
            });
        }

        // If stats requested, return stats only
        if ($request->input('stats')) {
            $all = $query->get();
            $jumlahPermintaanLab = $all->count();
            $jumlahPasienLab = $all->pluck('visitation.pasien.id')->unique()->count();
            $totalPendapatanLab = 0;
            foreach ($all as $row) {
                $invoiceItem = InvoiceItem::where('billable_type', LabPermintaan::class)
                    ->where('billable_id', $row->id)->first();
                if ($invoiceItem) $totalPendapatanLab += floatval($invoiceItem->final_amount);
                else $totalPendapatanLab += $row->labTest->harga ? floatval($row->labTest->harga) : 0;
            }
            return response()->json([
                'stats' => [
                    'jumlah_permintaan_lab' => $jumlahPermintaanLab,
                    'jumlah_pasien_lab' => $jumlahPasienLab,
                    'total_pendapatan_lab' => 'Rp ' . number_format($totalPendapatanLab, 0, ',', '.'),
                ]
            ]);
        }
        return DataTables::of($query)
            ->addColumn('pasien', function($row) {
                return $row->visitation && $row->visitation->pasien ? $row->visitation->pasien->nama : '-';
            })
            ->addColumn('tanggal_visit', function($row) {
                return $row->visitation ? $row->visitation->tanggal_visitation : '-';
            })
            ->addColumn('nama_test', function($row) {
                return $row->labTest ? $row->labTest->nama : '-';
            })
            ->addColumn('dokter', function($row) {
                return $row->visitation && $row->visitation->dokter ? $row->visitation->dokter->user->name ?? $row->visitation->dokter->nama ?? '-' : '-';
            })
            ->addColumn('klinik', function($row) {
                return $row->visitation && $row->visitation->klinik ? $row->visitation->klinik->nama : '-';
            })
            ->addColumn('harga', function($row) {
                $invoiceItem = InvoiceItem::where('billable_type', LabPermintaan::class)
                    ->where('billable_id', $row->id)->first();
                return $invoiceItem ? number_format($invoiceItem->unit_price, 0, ',', '.') : ($row->labTest->harga ?? '-');
            })
            ->addColumn('harga_jual', function($row) {
                $invoiceItem = InvoiceItem::where('billable_type', LabPermintaan::class)
                    ->where('billable_id', $row->id)->first();
                return $invoiceItem ? number_format($invoiceItem->final_amount, 0, ',', '.') : '-';
            })
            ->addColumn('invoice', function($row) {
                return $row->visitation && $row->visitation->invoice ? $row->visitation->invoice->invoice_number : '-';
            })
            ->make(true);
    }
}
