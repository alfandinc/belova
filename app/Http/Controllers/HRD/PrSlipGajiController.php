<?php
namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\PrSlipGaji;
use App\Models\HRD\Employee;
use App\Models\HRD\PengajuanLembur;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Helpers\TerbilangHelper;

class PrSlipGajiController extends Controller
{
    // Get and print current user's latest slip gaji
    public function mySlip(Request $request)
    {
        $user = Auth::user();
        $employee = $user ? $user->employee : null;

        // Check if this is a password verification request
        if ($request->isMethod('post')) {
            // Verify the password
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'type' => 'error',
                    'title' => 'Password Salah',
                    'message' => 'Password yang Anda masukkan tidak sesuai.'
                ], 401);
            }
        }

        if (!$employee) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'title' => 'Error',
                'message' => 'Data karyawan tidak ditemukan.'
            ]);
        }

        // After successful password verification, always redirect user to their slip history page
        if ($request->isMethod('post')) {
            return response()->json([
                'success' => true,
                'url' => route('hrd.payroll.slip_gaji.history')
            ]);
        }

        return response()->json([
            'success' => false,
            'type' => 'error',
            'title' => 'Error',
            'message' => 'Method not allowed'
        ], 405);
    }

    // New method to handle PDF download after verification
    public function downloadSlip($id)
    {
        $user = Auth::user();
        $employee = $user ? $user->employee : null;
        
        if (!$employee) {
            abort(403);
        }

        $slip = PrSlipGaji::where('id', $id)
                         ->where('employee_id', $employee->id)
                         ->first();

        if (!$slip || $slip->status_gaji !== 'paid') {
            abort(403);
        }

        $terbilang = function($angka) { return TerbilangHelper::terbilang($angka); };
        $html = view('hrd.payroll.slip_gaji.print', compact('slip', 'terbilang'))->render();
        
        $mpdf = new \Mpdf\Mpdf(['format' => 'A4-L', 'margin_top' => 5, 'margin_bottom' => 5]);
        $mpdf->WriteHTML($html);
        
        $filename = 'slip-gaji-' . $employee->nama . '-' . $slip->bulan . '.pdf';
        return response($mpdf->Output($filename, 'I'))
               ->header('Content-Type', 'application/pdf');
    }

    /**
     * Serve jasmed image file for a slip gaji.
     * Protected by auth; only the employee owner or users with HRD/Admin/Manager/Ceo roles can access.
     */
    public function serveJasmed($id)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $slip = PrSlipGaji::findOrFail($id);

        // Basic authorization: owner or allowed roles
        $allowed = false;
        if ($slip->employee && $user->employee && $user->employee->id === $slip->employee->id) {
            $allowed = true;
        }
        // If spatie/roles available, use hasAnyRole
        if (!$allowed && method_exists($user, 'hasAnyRole')) {
            if ($user->hasAnyRole(['Hrd', 'Admin', 'Manager', 'Ceo'])) {
                $allowed = true;
            }
        }

        if (!$allowed) {
            abort(403);
        }

        if (!$slip->jasmed_file) {
            abort(404);
        }

        $fullPath = storage_path('app/public/' . $slip->jasmed_file);
        if (!file_exists($fullPath)) {
            abort(404);
        }

        $mime = @mime_content_type($fullPath) ?: 'application/octet-stream';
        return response()->file($fullPath, ['Content-Type' => $mime]);
    }

        // Batch generate uang KPI for all employees in selected month
    public function generateUangKpi(Request $request)
    {
        $bulan = $request->input('bulan') ?? date('Y-m');
        // Calculate total incentive pool by converting each omset entry into its incentive amount
        $omsetRows = \App\Models\HRD\PrOmsetBulanan::where('bulan', $bulan)->get();
        $totalOmset = 0;
        foreach ($omsetRows as $row) {
            $insentif = $row->insentifOmset; // relation to PrInsentifOmset
            if ($insentif) {
                $nominal = floatval($row->nominal);
                $insValue = 0;
                // Determine which incentive percentage applies
                if ($insentif->omset_min !== null && $insentif->omset_max !== null) {
                    if ($nominal >= $insentif->omset_min && $nominal <= $insentif->omset_max) {
                        $insValue = floatval($insentif->insentif_normal);
                    } elseif ($nominal > $insentif->omset_max) {
                        $insValue = floatval($insentif->insentif_up);
                    }
                } else {
                    // Fallback: use normal if defined
                    $insValue = floatval($insentif->insentif_normal ?? 0);
                }
                // insValue is expected to be a percentage (e.g., 10 for 10%)
                $totalOmset += ($insValue / 100) * $nominal;
            } else {
                $totalOmset += floatval($row->nominal);
            }
        }
        $employees = \App\Models\HRD\Employee::all();
        $employeeKpiPoin = [];
        foreach ($employees as $employee) {
            $employeeKpiPoin[$employee->id] = \App\Models\HRD\PrSlipGaji::where('employee_id', $employee->id)
                ->where('bulan', $bulan)
                ->value('kpi_poin') ?? 0;
        }
        $totalKpiPoin = array_sum($employeeKpiPoin);
        $averageKpiPoin = count($employeeKpiPoin) > 0 ? ($totalKpiPoin / count($employeeKpiPoin)) : 0;
        \App\Models\HRD\PrKpiSummary::updateOrCreate(
            ['bulan' => $bulan],
            [
                'total_kpi_poin' => $totalKpiPoin,
                'average_kpi_poin' => $averageKpiPoin
            ]
        );
        foreach ($employees as $employee) {
            $kpiPoin = $employeeKpiPoin[$employee->id];
            $uangKpi = ($totalKpiPoin > 0) ? ($kpiPoin / $totalKpiPoin * $totalOmset) : 0;
            $slip = \App\Models\HRD\PrSlipGaji::where('employee_id', $employee->id)
                ->where('bulan', $bulan)
                ->first();
            if ($slip) {
                $slip->uang_kpi = $uangKpi;
                // Recalculate total_pendapatan and total_gaji
                $slip->total_pendapatan =
                    ($slip->gaji_pokok ?? 0)
                    + ($slip->tunjangan_jabatan ?? 0)
                    + ($slip->tunjangan_masa_kerja ?? 0)
                    + ($slip->uang_makan ?? 0)
                    + ($slip->uang_lembur ?? 0)
                    + ($slip->jasa_medis ?? 0)
                    + ($slip->uang_kpi ?? 0);
                $slip->total_gaji = ($slip->total_pendapatan ?? 0) - ($slip->total_potongan ?? 0);
                $slip->save();
            }
        }
        return response()->json(['success' => true]);
    }

    // AJAX preview + optional confirm for generating uang KPI
    public function simulateKpiPreview(Request $request)
    {
        $bulan = $request->input('bulan') ?? date('Y-m');
        $omsetRows = \App\Models\HRD\PrOmsetBulanan::where('bulan', $bulan)->get();
        $rows = [];
        $totalOmset = 0;
        foreach ($omsetRows as $row) {
            $insentif = $row->insentifOmset;
            $nominal = floatval($row->nominal);
            $insValue = 0;
            $mode = 'no-insentif';
            if ($insentif) {
                if ($insentif->omset_min !== null && $insentif->omset_max !== null) {
                    if ($nominal >= $insentif->omset_min && $nominal <= $insentif->omset_max) {
                        $insValue = floatval($insentif->insentif_normal);
                        $mode = 'normal';
                    } elseif ($nominal > $insentif->omset_max) {
                        $insValue = floatval($insentif->insentif_up);
                        $mode = 'up';
                    } else {
                        $insValue = 0;
                        $mode = 'below-min';
                    }
                } else {
                    $insValue = floatval($insentif->insentif_normal ?? 0);
                    $mode = 'normal-fallback';
                }
            }
            $kontrib = ($insValue / 100) * $nominal;
            $rows[] = [
                'id' => $row->id,
                'insentif_omset_id' => $row->insentif_omset_id,
                'nominal' => number_format($nominal, 2, ',', '.'),
                'insentif_pct' => $insValue,
                'mode' => $mode,
                'kontribusi' => number_format($kontrib, 2, ',', '.')
            ];
            $totalOmset += $kontrib;
        }

        // employees
        $employees = \App\Models\HRD\Employee::all();
        $employeeKpiPoin = [];
        foreach ($employees as $employee) {
            $employeeKpiPoin[$employee->id] = \App\Models\HRD\PrSlipGaji::where('employee_id', $employee->id)
                ->where('bulan', $bulan)
                ->value('kpi_poin') ?? 0;
        }
        $totalKpiPoin = array_sum($employeeKpiPoin);
        $employeesOut = [];
        foreach ($employees as $employee) {
            $kpi = $employeeKpiPoin[$employee->id];
            if ($kpi <= 0) continue;
            $uang = ($totalKpiPoin > 0) ? ($kpi / $totalKpiPoin * $totalOmset) : 0;
            $employeesOut[] = [
                'id' => $employee->id,
                'nama' => $employee->nama,
                'kpi_poin' => number_format($kpi, 2, ',', '.'),
                'uang_kpi' => number_format($uang, 2, ',', '.')
            ];
        }

        // if confirm flag present, call generation logic
        if ($request->input('confirm')) {
            // call existing generator which saves nilai ke database
            return $this->generateUangKpi($request);
        }

        return response()->json([
            'success' => true,
            'rows' => $rows,
            'total' => number_format($totalOmset, 2, ',', '.'),
            'employees' => $employeesOut
        ]);
    }
    // Update slip gaji dari modal detail
    public function update(Request $request, $id)
    {
        $slip = PrSlipGaji::findOrFail($id);
        // Accept basic editable fields
        $slip->status_gaji = $request->input('status_gaji', $slip->status_gaji);
        $slip->total_hari_masuk = $request->input('total_hari_masuk', $slip->total_hari_masuk);
        $slip->kpi_poin = $request->input('kpi_poin', $slip->kpi_poin);
        $slip->gaji_pokok = $request->input('gaji_pokok', $slip->gaji_pokok);
        $slip->tunjangan_jabatan = $request->input('tunjangan_jabatan', $slip->tunjangan_jabatan);
        $slip->tunjangan_masa_kerja = $request->input('tunjangan_masa_kerja', $slip->tunjangan_masa_kerja);
        $slip->uang_makan = $request->input('uang_makan', $slip->uang_makan);
        $slip->poin_marketing = $request->input('poin_marketing', $slip->poin_marketing);
        $slip->poin_penilaian = $request->input('poin_penilaian', $slip->poin_penilaian);
        $slip->poin_kehadiran = $request->input('poin_kehadiran', $slip->poin_kehadiran);
        $slip->uang_kpi = $request->input('uang_kpi', $slip->uang_kpi);
        $slip->jasa_medis = $request->input('jasa_medis', $slip->jasa_medis);
        $slip->total_jam_lembur = $request->input('total_jam_lembur', $slip->total_jam_lembur);
        $slip->uang_lembur = $request->input('uang_lembur', $slip->uang_lembur);
        $slip->potongan_pinjaman = $request->input('potongan_pinjaman', $slip->potongan_pinjaman);
        $slip->potongan_bpjs_kesehatan = $request->input('potongan_bpjs_kesehatan', $slip->potongan_bpjs_kesehatan);
        $slip->potongan_jamsostek = $request->input('potongan_jamsostek', $slip->potongan_jamsostek);
        $slip->potongan_penalty = $request->input('potongan_penalty', $slip->potongan_penalty);
        $slip->potongan_lain = $request->input('potongan_lain', $slip->potongan_lain);
        $slip->benefit_bpjs_kesehatan = $request->input('benefit_bpjs_kesehatan', $slip->benefit_bpjs_kesehatan);
        $slip->benefit_jht = $request->input('benefit_jht', $slip->benefit_jht);
        $slip->benefit_jkk = $request->input('benefit_jkk', $slip->benefit_jkk);
        $slip->benefit_jkm = $request->input('benefit_jkm', $slip->benefit_jkm);
        $slip->total_benefit = $request->input('total_benefit', $slip->total_benefit);

        // Handle jasmed_file upload
        if ($request->hasFile('jasmed_file')) {
            $file = $request->file('jasmed_file');
            $path = $file->store('jasmed_files', 'public');
            $slip->jasmed_file = $path;
        }

        // Process pendapatan_tambahan array (label + amount)
        $tambahan = [];
        $tambahanTotal = 0;
        if ($request->has('pendapatan_tambahan') && is_array($request->input('pendapatan_tambahan'))) {
            foreach ($request->input('pendapatan_tambahan') as $item) {
                $label = isset($item['label']) ? trim($item['label']) : null;
                $amt = isset($item['amount']) ? $item['amount'] : 0;
                $amt = is_string($amt) ? str_replace([',', ' '], ['', ''], $amt) : $amt;
                $amt = is_numeric($amt) ? (float) $amt : 0;
                if ($label && $amt != 0) {
                    $tambahan[] = ['label' => $label, 'amount' => $amt];
                    $tambahanTotal += $amt;
                }
            }
        }
        if ($tambahan) {
            $slip->pendapatan_tambahan = $tambahan;
        }

        // Recalculate totals
        $existingTambahan = is_array($slip->pendapatan_tambahan) ? array_sum(array_column($slip->pendapatan_tambahan, 'amount')) : 0;
        $sumTambahan = $existingTambahan;

        $basePendapatan = (
            ($slip->gaji_pokok ?? 0)
            + ($slip->tunjangan_jabatan ?? 0)
            + ($slip->tunjangan_masa_kerja ?? 0)
            + ($slip->uang_makan ?? 0)
            + ($slip->uang_lembur ?? 0)
            + ($slip->jasa_medis ?? 0)
            + ($slip->uang_kpi ?? 0)
        );

        $slip->total_pendapatan = $basePendapatan + $sumTambahan;

        // Recompute total potongan if not explicitly provided
        if ($request->has('total_potongan')) {
            $slip->total_potongan = $request->input('total_potongan', $slip->total_potongan);
        } else {
            $slip->total_potongan = (
                ($slip->potongan_pinjaman ?? 0)
                + ($slip->potongan_bpjs_kesehatan ?? 0)
                + ($slip->potongan_jamsostek ?? 0)
                + ($slip->potongan_penalty ?? 0)
                + ($slip->potongan_lain ?? 0)
            );
        }

        $slip->total_gaji = ($slip->total_pendapatan ?? 0) - ($slip->total_potongan ?? 0);

        $slip->save();

        return response()->json(['success' => true]);
    }
    // Return omset input fields for all available penghasil omset
    public function getOmsetInputs(Request $request)
    {
        $bulan = $request->get('bulan');
        $insentifOmset = \App\Models\HRD\PrInsentifOmset::all();
        return view('hrd.payroll.slip_gaji.buat._omset_inputs', compact('insentifOmset'))->render();
    }

    // Store omset bulanan (moved from OmsetBulananController)
    public function storeOmsetBulanan(Request $request)
    {
        $bulan = $request->input('bulan');
        $omsetBulanan = $request->input('omset_bulanan', []);
        $totalOmset = 0;
        foreach ($omsetBulanan as $insentifOmsetId => $nominal) {
            \App\Models\HRD\PrOmsetBulanan::updateOrCreate(
                [
                    'bulan' => $bulan,
                    'insentif_omset_id' => $insentifOmsetId
                ],
                [
                    'nominal' => $nominal
                ]
            );
            $totalOmset += floatval($nominal);
        }
        return response()->json(['success' => true, 'total_omset' => number_format($totalOmset, 2)]);
    }

    // Get total omset for a month (route expects getTotal)
    public function getTotal(Request $request)
    {
        $bulan = $request->get('bulan');
        $totalOmset = \App\Models\HRD\PrOmsetBulanan::where('bulan', $bulan)->sum('nominal');
        return response()->json(['total_omset' => number_format($totalOmset, 2)]);
    }

    public function index(Request $request)
    {
        $filterBulan = $request->get('bulan') ?? date('Y-m');
        // Stricter conversion to YYYY-MM
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterBulan)) {
            $bulan = substr($filterBulan, 0, 7);
        } elseif (preg_match('/^\d{4}-\d{2}$/', $filterBulan)) {
            $bulan = $filterBulan;
        } else {
            $bulan = date('Y-m', strtotime($filterBulan));
        }
        Log::info('filterBulan: ' . $filterBulan . ', bulan: ' . $bulan);
        $kpiSummary = \App\Models\HRD\PrKpiSummary::where('bulan', $bulan)->first();
        $totalOmset = \App\Models\HRD\PrOmsetBulanan::where('bulan', $bulan)->sum('nominal');
        return view('hrd.payroll.slip_gaji.index', compact('bulan', 'kpiSummary', 'totalOmset'));
    }

    public function storeAll(Request $request)

    {
        // Validate required inputs for creating slips
        $request->validate([
            'bulan' => 'required|string',
            'periode_penilaian_id' => 'required',
            'omset_bulanan' => 'required|array',
            'omset_bulanan.*' => 'required|numeric'
        ]);
        // Get master potongan values once
        $potonganBpjsKesehatan = \App\Models\HRD\PrMasterPotongan::where('nama_potongan', 'IURAN BPJS KESEHATAN')->value('nominal');
        $potonganJamsostek = \App\Models\HRD\PrMasterPotongan::where('nama_potongan', 'IURAN JAMSOSTEK')->value('nominal');
        // Get master benefit values once
        $benefitBpjsKesehatan = \App\Models\HRD\PrMasterBenefit::where('nama_benefit', 'BPJS KESEHATAN')->value('nominal');
        $benefitJht = \App\Models\HRD\PrMasterBenefit::where('nama_benefit', 'BPJS KETENAGAKERJAAN - JHT')->value('nominal');
        $benefitJkk = \App\Models\HRD\PrMasterBenefit::where('nama_benefit', 'BPJS KETENAGAKERJAAN - JKK')->value('nominal');
        $benefitJkm = \App\Models\HRD\PrMasterBenefit::where('nama_benefit', 'BPJS KETENAGAKERJAAN - JKM')->value('nominal');
        $bulan = $request->input('bulan');
        $omsetBulanan = $request->input('omset_bulanan', []);

        // Save omset bulanan data first
        $totalOmset = 0;
        foreach ($omsetBulanan as $insentifOmsetId => $nominal) {
            \App\Models\HRD\PrOmsetBulanan::updateOrCreate(
                [
                    'bulan' => $bulan,
                    'insentif_omset_id' => $insentifOmsetId
                ],
                [
                    'nominal' => $nominal
                ]
            );
            $totalOmset += floatval($nominal);
        }

        // Parse year and month from 'bulan' (format: YYYY-MM)
        $year = substr($bulan, 0, 4);
        $month = substr($bulan, 5, 2);
        $employees = Employee::all();

        // Get master tunjangan lain values once
        $uangMakanMaster = \App\Models\HRD\PrMasterTunjanganLain::where('nama_tunjangan', 'Uang Makan')->first();
        $tunjanganMasaKerjaMaster = \App\Models\HRD\PrMasterTunjanganLain::where('nama_tunjangan', 'Tunjangan Masa Kerja')->first();

        // Get master benefit values once
        $benefitBpjsKesehatan = \App\Models\HRD\PrMasterBenefit::where('nama_benefit', 'BPJS KESEHATAN')->value('nominal');
        $benefitJht = \App\Models\HRD\PrMasterBenefit::where('nama_benefit', 'BPJS KETENAGAKERJAAN - JHT')->value('nominal');
        $benefitJkk = \App\Models\HRD\PrMasterBenefit::where('nama_benefit', 'BPJS KETENAGAKERJAAN - JKK')->value('nominal');
        $benefitJkm = \App\Models\HRD\PrMasterBenefit::where('nama_benefit', 'BPJS KETENAGAKERJAAN - JKM')->value('nominal');

        $periodePenilaianId = $request->input('periode_penilaian_id');

        foreach ($employees as $employee) {
            // Calculate poin penilaian (average score for selected period)
            $poinPenilaian = 0;
            if ($periodePenilaianId) {
                $evaluations = \App\Models\HRD\PerformanceEvaluation::where('period_id', $periodePenilaianId)
                    ->where('evaluatee_id', $employee->id)
                    ->where('status', 'completed')
                    ->with('scores.question')
                    ->get();
                $allScores = collect();
                foreach ($evaluations as $evaluation) {
                    $allScores = $allScores->concat($evaluation->scores);
                }
                $scoreTypeScores = $allScores->filter(function ($score) {
                    return $score->question &&
                        $score->question->question_type === 'score' &&
                        in_array($score->question->evaluation_type, ['manager_to_employee', 'hrd_to_manager', 'ceo_to_manager']);
                });
                if ($scoreTypeScores->isNotEmpty()) {
                    $poinPenilaian = round($scoreTypeScores->avg('score'), 2);
                }
            }
            // If this employee has kategori_pegawai == 'khusus', they should not receive performance evaluation points
            if (isset($employee->kategori_pegawai) && strtolower($employee->kategori_pegawai) === 'khusus') {
                $poinPenilaian = 0;
            }
            // Count total hari scheduled
            $totalHariScheduled = $employee->schedules()
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->count();

            // Count total hari masuk
            $totalHariMasuk = $employee->attendanceRekap()
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->count();

            // Get gaji pokok nominal from master
            $gajiPokok = $employee->golGajiPokok ? $employee->golGajiPokok->nominal : 0;
            // Get tunjangan jabatan nominal from master
            $tunjanganJabatan = $employee->golTunjanganJabatan ? $employee->golTunjanganJabatan->nominal : 0;

            // Calculate uang makan
            $uangMakanNominal = $uangMakanMaster ? $uangMakanMaster->nominal : 0;
            $uangMakan = $uangMakanNominal * $totalHariMasuk;

            // Calculate tunjangan masa kerja
            $tunjanganMasaKerjaNominal = $tunjanganMasaKerjaMaster ? $tunjanganMasaKerjaMaster->nominal : 0;
            $masaKerjaTahun = 0;
            if ($employee->tanggal_masuk) {
                $tanggalMasuk = \Carbon\Carbon::parse($employee->tanggal_masuk);
                $referenceDate = \Carbon\Carbon::create($year, $month)->endOfMonth();
                $masaKerjaTahun = (int) $tanggalMasuk->diffInYears($referenceDate);
            }
            // Only give tunjangan masa kerja if masa kerja >= 1 year, and only count full years
            $tunjanganMasaKerja = ($masaKerjaTahun >= 1) ? ($tunjanganMasaKerjaNominal * $masaKerjaTahun) : 0;

            // Calculate gaji per jam and per hari
            $gajiPerJam = $gajiPokok > 0 ? ($gajiPokok / 173) : 0;
            $gajiPerHari = ($gajiPokok > 0 && $totalHariMasuk > 0) ? ($gajiPokok / $totalHariMasuk) : 0;


            // Calculate total jam lembur for the employee in the selected month with status_hrd disetujui
            $totalJamLembur = PengajuanLembur::where('employee_id', $employee->id)
                ->whereYear('tanggal', $year)
                ->whereMonth('tanggal', $month)
                ->where('status_hrd', 'disetujui')
                ->sum('total_jam');

            // Calculate uang lembur
            $uangLembur = 0;
            $jamLembur = $totalJamLembur / 60; // convert minutes to hours
            if ($jamLembur > 0) {
                if ($jamLembur <= 6) {
                    // First hour: 1.5 * gaji_perjam
                    $uangLembur += min(1, $jamLembur) * 1.5 * $gajiPerJam;
                    // Next hours (2-6): 2 * gaji_perjam
                    if ($jamLembur > 1) {
                        $uangLembur += min(5, $jamLembur - 1) * 2 * $gajiPerJam;
                    }
                } else {
                    // More than 6 hours: 6 jam = 1x gaji perhari, sisanya: jam pertama *1.5, jam kedua dst sampai 6 *2
                    $uangLembur += $gajiPerHari; // 6 jam pertama
                    $sisaJam = $jamLembur - 6;
                    if ($sisaJam > 0) {
                        // Jam ke-7 (jam pertama setelah 6 jam): 1x gaji perjam * 1.5
                        $uangLembur += min(1, $sisaJam) * 1.5 * $gajiPerJam;
                        // Jam ke-8,9,10 dst (jam kedua sampai keempat): 1x gaji perjam * 2
                        if ($sisaJam > 1) {
                            $uangLembur += min(3, $sisaJam - 1) * 2 * $gajiPerJam;
                        }
                    }
                }
            }

            // Get initial poin kehadiran and other KPI defaults from prkpi
            $initialPoinKehadiran = \App\Models\HRD\PrKpi::where('nama_poin', 'Kehadiran')->value('initial_poin');
            $initialPoinMedsos = \App\Models\HRD\PrKpi::where('nama_poin', 'Medsos')->value('initial_poin');
            $initialPoinMarketing = \App\Models\HRD\PrKpi::where('nama_poin', 'Marketing')->value('initial_poin');

            // If this employee has kategori_pegawai == 'khusus', they should have no initial KPI points
            if (isset($employee->kategori_pegawai) && strtolower($employee->kategori_pegawai) === 'khusus') {
                $initialPoinKehadiran = 0;
                $initialPoinMedsos = 0;
                $initialPoinMarketing = 0;
            }
            // Get lateness recap for the employee in the selected month
            $latenessRecap = \App\Models\AttendanceLatenessRecap::where('employee_id', $employee->id)
                ->where('month', $bulan)
                ->first();
            $latenessMinus = 0;
            if ($latenessRecap && $latenessRecap->total_late_days > 0) {
                $lateMinutes = $latenessRecap->total_late_minutes;
                $lateDays = $latenessRecap->total_late_days;
                // Calculate average lateness per day
                $avgLate = $lateDays > 0 ? ($lateMinutes / $lateDays) : 0;
                for ($i = 0; $i < $lateDays; $i++) {
                    if ($avgLate >= 1 && $avgLate <= 15) {
                        $latenessMinus += 0.2;
                    } elseif ($avgLate >= 16 && $avgLate <= 29) {
                        $latenessMinus += 0.4;
                    } elseif ($avgLate >= 30) {
                        $latenessMinus += 0.6;
                    }
                }
            }


            // Calculate poin kehadiran
            $selisih = $totalHariScheduled - $totalHariMasuk;
            $pengajuanTidakMasuk = \App\Models\HRD\PengajuanTidakMasuk::where('employee_id', $employee->id)
                ->whereYear('tanggal_mulai', $year)
                ->whereMonth('tanggal_mulai', $month)
                ->where('status_hrd', 'disetujui')
                ->sum('total_hari');
            $excused = min($pengajuanTidakMasuk, $selisih);
            $unexcused = max($selisih - $excused, 0);
            $minus = ($unexcused * 1) + ($excused * 0.5);
            $poinKehadiran = max($initialPoinKehadiran - $minus - $latenessMinus, 0);
            $kpiPoin = $initialPoinMarketing + $poinPenilaian + $poinKehadiran;

            // Store slip gaji for each employee, without uang_kpi
            $totalBenefit = ($benefitBpjsKesehatan ?? 0) + ($benefitJht ?? 0) + ($benefitJkk ?? 0) + ($benefitJkm ?? 0);
            PrSlipGaji::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'bulan' => $bulan
                ],
                [
                    'status_gaji' => 'draft',
                    'total_hari_scheduled' => $totalHariScheduled,
                    'total_hari_masuk' => $totalHariMasuk,
                    'gaji_pokok' => $gajiPokok,
                    'tunjangan_jabatan' => $tunjanganJabatan,
                    'uang_makan' => $uangMakan,
                    'tunjangan_masa_kerja' => $tunjanganMasaKerja,
                    'gaji_perjam' => $gajiPerJam,
                    'gaji_perhari' => $gajiPerHari,
                    'benefit_bpjs_kesehatan' => $benefitBpjsKesehatan,
                    'benefit_jht' => $benefitJht,
                    'benefit_jkk' => $benefitJkk,
                    'benefit_jkm' => $benefitJkm,
                    'total_benefit' => $totalBenefit,
                    'potongan_bpjs_kesehatan' => $potonganBpjsKesehatan,
                    'potongan_jamsostek' => $potonganJamsostek,
                    'total_jam_lembur' => $totalJamLembur,
                    'uang_lembur' => $uangLembur,
                    'poin_penilaian' => $poinPenilaian,
                    'poin_kehadiran' => $poinKehadiran,
                    'poin_marketing' => $initialPoinMedsos,
                    'poin_medsos' => $initialPoinMedsos,
                    'kpi_poin' => $kpiPoin
                ]
            );
        }
        return response()->json(['success' => true, 'total_omset' => number_format($totalOmset, 2)]);

        // Parse year and month from 'bulan' (format: YYYY-MM)
        $year = substr($bulan, 0, 4);
        $month = substr($bulan, 5, 2);

        // Then create slip gaji records for all employees
        $employees = Employee::all();
        foreach ($employees as $employee) {
            // Count total hari scheduled
            $totalHariScheduled = $employee->schedules()
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->count();

            // Count total hari masuk
            $totalHariMasuk = $employee->attendanceRekap()
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->count();

            // Get gaji pokok nominal from master
            $gajiPokok = $employee->golGajiPokok ? $employee->golGajiPokok->nominal : 0;
            // Get tunjangan jabatan nominal from master
            $tunjanganJabatan = $employee->golTunjanganJabatan ? $employee->golTunjanganJabatan->nominal : 0;

            PrSlipGaji::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'bulan' => $bulan
                ],
                [
                    'status_gaji' => 'draft',
                    'total_hari_scheduled' => $totalHariScheduled,
                    'total_hari_masuk' => $totalHariMasuk,
                    'gaji_pokok' => $gajiPokok,
                    'tunjangan_jabatan' => $tunjanganJabatan,
                    'tunjangan_masa_kerja' => 0, // Set default or calculate as needed
                    // ...other fields if needed...
                ]
            );
        }
        return response()->json(['success' => true, 'total_omset' => number_format($totalOmset, 2)]);
    }


    public function data(Request $request)
    {
        $bulan = $request->get('bulan');
        $query = PrSlipGaji::with(['employee.division']);
        if ($bulan) {
            $query->where('bulan', $bulan);
        }
        return datatables()->of($query)
            ->addColumn('id', function($row) {
                return $row->id;
            })
            ->addColumn('no_induk', function($row) {
                return $row->employee ? $row->employee->no_induk : '';
            })
            ->addColumn('nama', function($row) {
                return $row->employee ? $row->employee->nama : '';
            })
            ->addColumn('divisi', function($row) {
                return $row->employee && $row->employee->division ? $row->employee->division->name : '';
            })
            ->addColumn('jumlah_hari_masuk', function($row) {
                return $row->total_hari_masuk;
            })
            ->addColumn('kpi_poin', function($row) {
                return $row->kpi_poin;
            })
            ->addColumn('jumlah_pendapatan', function($row) {
                return number_format($row->total_pendapatan, 2);
            })
            ->addColumn('jumlah_potongan', function($row) {
                return number_format($row->total_potongan, 2);
            })
            ->addColumn('total_gaji', function($row) {
                return number_format($row->total_gaji, 2);
            })
            ->addColumn('status', function($row) {
                return $row->status_gaji;
            })
            ->addColumn('action', function($row) {
                return '<button class="btn btn-info btn-sm btn-detail">Detail Slip</button> '
                    . '<button class="btn btn-primary btn-sm btn-print">Print</button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Show history page for the currently authenticated employee.
     */
    public function historyPage()
    {
        return view('hrd.payroll.slip_gaji.history');
    }

    /**
     * Return DataTable JSON for slips belonging to the logged-in employee.
     */
    public function historyData(Request $request)
    {
        $user = Auth::user();
        $employee = $user ? $user->employee : null;

        if (!$employee) {
            return datatables()->of(collect([]))->make(true);
        }

        // Only include slips that have been paid
        $query = PrSlipGaji::where('employee_id', $employee->id)
            ->where('status_gaji', 'paid')
            ->orderBy('bulan', 'desc');

        return datatables()->of($query)
            ->addColumn('bulan_label', function($row) {
                // Expecting bulan in format YYYY-MM or YYYY-MM-DD
                $raw = $row->bulan;
                // Normalize to YYYY-MM
                if (preg_match('/^(\d{4})-(\d{2})/', $raw, $m)) {
                    $year = $m[1];
                    $month = intval($m[2]);
                } else {
                    // fallback to current month
                    $year = date('Y');
                    $month = intval(date('m'));
                }
                $months = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                ];
                $label = (isset($months[$month]) ? $months[$month] : 'Bulan') . ' ' . $year;
                return $label;
            })
            ->addColumn('total_gaji', function($row) {
                return number_format($row->total_gaji ?? 0, 2);
            })
            ->addColumn('status', function($row) {
                return $row->status_gaji;
            })
            ->addColumn('action', function($row) {
                $printUrl = url('hrd/payroll/slip-gaji/print/' . $row->id);
                return '<a href="' . $printUrl . '" class="btn btn-sm btn-primary" target="_blank">Cetak PDF</a>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function detail($id)
    {
        $slip = PrSlipGaji::with(['employee.division'])->findOrFail($id);
        return view('hrd.payroll.slip_gaji._detail', compact('slip'))->render();
    }

    public function changeStatus(Request $request, $id)
    {
    $slip = PrSlipGaji::findOrFail($id);
    $slip->status_gaji = $request->input('status_gaji', $slip->status_gaji == 'draft' ? 'final' : 'draft');
    $slip->save();
    return response()->json(['success' => true]);
    }

    public function getKpiSummary(Request $request)
    {
        $filterBulan = $request->get('bulan') ?? date('Y-m');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterBulan)) {
            $bulan = substr($filterBulan, 0, 7);
        } elseif (preg_match('/^\d{4}-\d{2}$/', $filterBulan)) {
            $bulan = $filterBulan;
        } else {
            $bulan = date('Y-m', strtotime($filterBulan));
        }
        $kpiSummary = \App\Models\HRD\PrKpiSummary::where('bulan', $bulan)->first();
        return response()->json([
            'total_kpi_poin' => $kpiSummary ? number_format($kpiSummary->total_kpi_poin, 2) : '-',
            'average_kpi_poin' => $kpiSummary ? number_format($kpiSummary->average_kpi_poin, 2) : '-',
        ]);
    }

    public function print($id)
    {
    $slip = \App\Models\HRD\PrSlipGaji::with('employee.division')->findOrFail($id);
    $terbilang = function($angka) { return TerbilangHelper::terbilang($angka); };
    $html = view('hrd.payroll.slip_gaji.print', compact('slip', 'terbilang'))->render();
    // Set margin_top to 5mm for minimal gap at the top
    $mpdf = new \Mpdf\Mpdf(['format' => 'A4-L', 'margin_top' => 5, 'margin_bottom' => 5]);
    $mpdf->WriteHTML($html);
    return response($mpdf->Output('slip-gaji.pdf', 'S'))->header('Content-Type', 'application/pdf');
    }
}
