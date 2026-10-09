<?php

namespace App\Console\Commands;

use App\Models\HRD\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Checks that employee status, kontrak_berakhir and the contract history agree, and (with --fix) repairs what
 * can be derived from the data. Without --fix it only reports.
 */
class SinkronKontrakKaryawan extends Command
{
    protected $signature = 'hrd:sinkron-kontrak {--fix : Perbaiki data yang bisa diperbaiki otomatis}';

    protected $description = 'Cek & sinkronkan status kontrak karyawan, kontrak_berakhir, dan riwayat kontrak';

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');
        $employees = Employee::withInactive()->with('contracts')->orderBy('nama')->get();
        $rows = [];
        $manual = [];

        DB::transaction(function () use ($employees, $fix, &$rows, &$manual) {
            foreach ($employees as $employee) {
                $active = $employee->contracts->where('status', 'active')->sortByDesc('end_date')->values();

                // More than one active contract: the one ending last stays active, older ones were renewed
                if ($active->count() > 1) {
                    $rows[] = [$employee->id, $employee->nama, 'Lebih dari satu kontrak aktif', 'Kontrak lama ditandai Diperpanjang'];
                    if ($fix) {
                        $active->slice(1)->each->update(['status' => 'renewed']);
                    }
                    $active = $active->take(1);
                }
                $current = $active->first();

                if ($employee->status !== 'kontrak') {
                    // Not kontrak but a contract still runs
                    if ($current) {
                        $rows[] = [$employee->id, $employee->nama, "Status {$employee->status} tetapi ada kontrak aktif", 'Kontrak diakhiri'];
                        if ($fix) {
                            $employee->endActiveContractIfNotKontrak();
                        }
                    }
                    continue;
                }

                if ($current) {
                    if (optional($employee->kontrak_berakhir)->toDateString() !== $current->end_date->toDateString()) {
                        $rows[] = [$employee->id, $employee->nama,
                            'kontrak_berakhir ' . (optional($employee->kontrak_berakhir)->toDateString() ?? 'kosong') . ' ≠ kontrak aktif ' . $current->end_date->toDateString(),
                            'kontrak_berakhir disamakan dengan kontrak aktif'];
                        if ($fix) {
                            $employee->update(['kontrak_berakhir' => $current->end_date]);
                        }
                    }
                    continue;
                }

                // Status kontrak without an active contract: the start date / duration are unknown, HRD must create it
                $manual[] = [$employee->id, $employee->nama,
                    $employee->kontrak_berakhir ? 'berakhir ' . $employee->kontrak_berakhir->toDateString() : 'tanggal berakhir kosong',
                    $employee->contracts->isEmpty() ? 'belum pernah ada kontrak' : 'kontrak terakhir: ' . $employee->contracts->sortByDesc('end_date')->first()->status];
            }
        });

        if ($rows) {
            $this->table(['ID', 'Nama', 'Masalah', $fix ? 'Diperbaiki' : 'Akan diperbaiki (--fix)'], $rows);
        } else {
            $this->info('Tidak ada data yang perlu diperbaiki otomatis.');
        }

        if ($manual) {
            $this->warn('Status kontrak tanpa kontrak aktif — buat kontraknya lewat Data Karyawan > Kontrak:');
            $this->table(['ID', 'Nama', 'kontrak_berakhir', 'Riwayat'], $manual);
        }

        if ($rows && !$fix) {
            $this->line('Jalankan dengan --fix untuk memperbaiki.');
        }

        return self::SUCCESS;
    }
}
