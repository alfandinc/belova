<?php

namespace App\Console\Commands;

use App\Models\HRD\Employee;
use Illuminate\Console\Command;

/**
 * Daily: employees whose scheduled last working day (nonaktif_tanggal) has passed become tidak aktif,
 * and their running contract ends on that day. Scheduled from Data Karyawan (edit status / Akhiri Kontrak).
 */
class ProsesNonaktifKaryawan extends Command
{
    protected $signature = 'hrd:proses-nonaktif {--dry-run : Hanya tampilkan, tanpa mengubah data}';

    protected $description = 'Jadikan tidak aktif karyawan yang hari terakhir terjadwalnya sudah lewat';

    public function handle(): int
    {
        $employees = Employee::nonaktifJatuhTempo() // the global scope already leaves out tidak aktif
            ->orderBy('nonaktif_tanggal')
            ->get();

        if ($employees->isEmpty()) {
            $this->info('Tidak ada nonaktif terjadwal yang jatuh tempo.');
            return self::SUCCESS;
        }

        foreach ($employees as $employee) {
            $line = "{$employee->id} {$employee->nama}: hari terakhir {$employee->nonaktif_tanggal->format('d/m/Y')} ("
                . (Employee::NONAKTIF_ALASAN[$employee->nonaktif_alasan] ?? $employee->nonaktif_alasan) . ')';
            if ($this->option('dry-run')) {
                $this->line('[dry-run] ' . $line);
                continue;
            }
            $employee->prosesNonaktifTerjadwal();
            $this->info('Nonaktif: ' . $line);
        }

        return self::SUCCESS;
    }
}
