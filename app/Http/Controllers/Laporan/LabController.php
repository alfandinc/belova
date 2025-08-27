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
            $totalPendapatanLab = $all->map(function($row) {
                $invoiceItem = InvoiceItem::where('billable_type', LabPermintaan::class)
                    ->where('billable_id', $row->id)->first();
                return $invoiceItem ? floatval($invoiceItem->final_amount) : 0;
            })->sum();
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
