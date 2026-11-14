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

                // Periods: end before start, or a contract still running when the next one starts (renewal overlap)
                $byStart = $employee->contracts->sortBy('start_date')->values();
                foreach ($byStart as $i => $contract) {
                    $next = $byStart->get($i + 1);
                    $period = $contract->start_date->toDateString() . ' – ' . $contract->end_date->toDateString();
                    if ($contract->end_date->lt($contract->start_date)) {
                        $rows[] = [$employee->id, $employee->nama, "Kontrak $period: berakhir sebelum mulai", 'Tanggal berakhir = tanggal mulai'];
                        if ($fix) {
                            $contract->update(['end_date' => $contract->start_date]);
                        }
                    } elseif ($next && $contract->status !== 'active' && $contract->end_date->gte($next->start_date)
                        && $next->start_date->gt($contract->start_date)) {
                        $rows[] = [$employee->id, $employee->nama, "Kontrak $period tumpang tindih dengan kontrak mulai {$next->start_date->toDateString()}",
                            'Tanggal berakhir dipotong ke sehari sebelumnya'];
                        if ($fix) {
                            $contract->update(['end_date' => $next->start_date->copy()->subDay()]);
                        }
                    }
                }

                // Still active with nonaktif data from before scheduling existed: leftover, never carried out
                if ($employee->status !== 'tidak aktif' && ($employee->nonaktif_alasan || $employee->nonaktif_tanggal)
                    && !$employee->nonaktifTerjadwal()) {
                    $rows[] = [$employee->id, $employee->nama, "Status {$employee->status} tetapi data nonaktif lama terisi ("
                        . (optional($employee->nonaktif_tanggal)->toDateString() ?? 'tanpa tanggal') . ')', 'Data nonaktif dikosongkan'];
                    if ($fix) {
                        Employee::withInactive()->whereKey($employee->id)->update(['nonaktif_tanggal' => null, 'nonaktif_alasan' => null, 'nonaktif_keterangan' => null]);
                    }
                }

                if ($employee->status !== 'kontrak') {
                    // Only kontrak employees keep an end date (the model clears it on save from now on)
                    if ($employee->kontrak_berakhir) {
                        $rows[] = [$employee->id, $employee->nama, "Status {$employee->status} tetapi kontrak_berakhir terisi", 'kontrak_berakhir dikosongkan'];
                        if ($fix) {
                            Employee::withInactive()->whereKey($employee->id)->update(['kontrak_berakhir' => null]);
                        }
                    }
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
