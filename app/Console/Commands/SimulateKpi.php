<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HRD\PrOmsetBulanan;
use App\Models\HRD\PrInsentifOmset;
use App\Models\HRD\PrSlipGaji;
use App\Models\HRD\Employee;

class SimulateKpi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simulate:kpi {bulan?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate generateUangKpi for a given month and print per-row and per-employee calculations';

    public function handle()
    {
        $bulan = $this->argument('bulan') ?? date('Y-m');
        $this->info("Simulating KPI generation for bulan: $bulan\n");

        $rows = PrOmsetBulanan::where('bulan', $bulan)->get();
        if ($rows->isEmpty()) {
            $this->warn('No pr_omset_bulanan rows found for this month.');
        }

        $totalOmset = 0;
        $this->info("-- Omset rows and contributions --");
        foreach ($rows as $r) {
            $i = $r->insentifOmset; // relation
            $nom = floatval($r->nominal);
            $ins = 0;
            $note = 'no-insentif';
            if ($i) {
                if ($nom >= $i->omset_min && $nom <= $i->omset_max) {
                    $ins = floatval($i->insentif_normal);
                    $note = 'normal';
                } elseif ($nom > $i->omset_max) {
                    $ins = floatval($i->insentif_up);
                    $note = 'up';
                } else {
                    $ins = 0;
                    $note = 'below-min';
                }
            }
            $kontrib = ($ins / 100) * $nom;
            $totalOmset += $kontrib;
            $this->line(sprintf("id:%s insentif_omset_id:%s nominal:%s insentif%%:%s mode:%s kontribusi:%s",
                $r->id,
                $r->insentif_omset_id,
                number_format($nom, 2, ',', '.'),
                $ins,
                $note,
                number_format($kontrib, 2, ',', '.')
            ));
        }

        $this->info("TOTAL_KONTRIBUSI: " . number_format($totalOmset, 2, ',', '.') . "\n");

        // Get employees and their kpi poin from slips
        $this->info("-- Employee KPI distribution --");
        $employees = Employee::all();
        $employeeKpi = [];
        foreach ($employees as $employee) {
            $kpiPoin = PrSlipGaji::where('employee_id', $employee->id)
                ->where('bulan', $bulan)
                ->value('kpi_poin') ?? 0;
            if ($kpiPoin > 0) {
                $employeeKpi[] = [
                    'id' => $employee->id,
                    'nama' => $employee->nama,
                    'kpi_poin' => floatval($kpiPoin)
                ];
            }
        }

        if (empty($employeeKpi)) {
            $this->warn('No employees with kpi_poin found for this month (slip missing or kpi_poin=0).');
            return 0;
        }

        $totalKpiPoin = array_sum(array_column($employeeKpi, 'kpi_poin'));
        $this->info('Total KPI poin: ' . number_format($totalKpiPoin, 2, ',', '.'));

        $sumUang = 0;
        foreach ($employeeKpi as $e) {
            $uang = ($totalKpiPoin > 0) ? ($e['kpi_poin'] / $totalKpiPoin * $totalOmset) : 0;
            $sumUang += $uang;
            $this->line(sprintf("id:%s nama:%s kpi_poin:%s uang_kpi:%s",
                $e['id'],
                $e['nama'],
                number_format($e['kpi_poin'], 2, ',', '.'),
                number_format($uang, 2, ',', '.')
            ));
        }

        $this->info("SUM UANG_KPI (calculated) : " . number_format($sumUang, 2, ',', '.'));
        if (abs($sumUang - $totalOmset) < 0.5) {
            $this->info("OK: Sum of distributed uang_kpi matches totalOmset\n");
        } else {
            $this->warn("Mismatch: distributed sum differs from totalOmset by " . number_format($sumUang - $totalOmset, 2, ',', '.') . "\n");
        }

        return 0;
    }
}
