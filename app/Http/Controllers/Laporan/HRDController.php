<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\Employee;
use App\Models\HRD\PengajuanLibur;
use App\Models\HRD\PengajuanTidakMasuk;
use App\Models\HRD\JatahLibur;

class HRDController extends Controller
    // --- Helper functions copied from AbsensiRekapController ---
{
    public function rekapKehadiran(Request $request)
    {
        // Get all employees
        $employees = Employee::with(['jatahLibur', 'pengajuanLibur', 'pengajuanTidakMasuk'])->get();

        $data = $employees->map(function ($employee) {
            $sakit = $employee->pengajuanTidakMasuk->where('jenis', 'sakit')->sum('total_hari');
            $izin = $employee->pengajuanTidakMasuk->where('jenis', 'izin')->sum('total_hari');
            $cuti = $employee->pengajuanLibur->where('jenis_libur', 'cuti')->sum('total_hari');
            $jatahCuti = $employee->jatahLibur ? $employee->jatahLibur->jatah_cuti_tahunan : 0;
            $sisaCuti = $jatahCuti - $cuti;

            // Attendance aggregates
            $attendance = $employee->attendanceRekap;
            $on_time = 0;
            $overtime = 0;
            $terlambat = 0;
            $menit_terlambat = 0;
            $jumlah_hari_masuk = 0;
            foreach ($attendance as $a) {
                $jamMasuk = $a->jam_masuk ? (explode(' ', $a->jam_masuk)[1] ?? $a->jam_masuk) : null;
                $jamKeluar = $a->jam_keluar ? (explode(' ', $a->jam_keluar)[1] ?? $a->jam_keluar) : null;
                $shiftStart = $a->shift_start ?? null;
                $shiftEnd = $a->shift_end ?? null;
                $date = $a->date ?? null;
                if ($jamMasuk) {
                    $jumlah_hari_masuk++;
                }
                if ($this->isLate($jamMasuk, $shiftStart, $date)) {
                    $terlambat++;
                    $menit_terlambat += $this->calculateMinutesLate($jamMasuk, $shiftStart, $date);
                } else if ($jamMasuk && $shiftStart) {
                    $on_time++;
                }
                if ($this->hasOvertime($jamKeluar, $shiftEnd, $date)) {
                    $overtime += $this->calculateMinutesOvertime($jamKeluar, $shiftEnd, $date);
                }
            }

            return [
                'no_induk' => $employee->no_induk,
                'nama' => $employee->nama,
                'sakit' => $sakit,
                'izin' => $izin,
                'cuti' => $cuti,
                'sisa_cuti' => $sisaCuti,
                'jumlah_hari_masuk' => $jumlah_hari_masuk,
                'on_time' => $on_time,
                'overtime' => $overtime,
                'terlambat' => $terlambat,
                'menit_terlambat' => $menit_terlambat,
            ];
        });

        return view('laporan.hrd.rekap_kehadiran', [
            'data' => $data
        ]);
    }

    // --- Helper functions copied from AbsensiRekapController ---
    private function isLate($jamMasuk, $shiftStart, $date)
    {
        if (!$jamMasuk || !$shiftStart) {
            return false;
        }
        $jamMasukTime = strpos($jamMasuk, ' ') !== false ? strtotime($jamMasuk) : strtotime($date . ' ' . $jamMasuk);
        $shiftStartTime = strtotime($date . ' ' . $shiftStart);
        return $jamMasukTime > $shiftStartTime;
    }

    private function calculateMinutesLate($jamMasuk, $shiftStart, $date)
    {
        if (!$jamMasuk || !$shiftStart) {
            return 0;
        }
        $jamMasukTime = strpos($jamMasuk, ' ') !== false ? strtotime($jamMasuk) : strtotime($date . ' ' . $jamMasuk);
        $shiftStartTime = strtotime($date . ' ' . $shiftStart);
        if ($jamMasukTime > $shiftStartTime) {
            return round(($jamMasukTime - $shiftStartTime) / 60);
        }
        return 0;
    }

    private function hasOvertime($jamKeluar, $shiftEnd, $date)
    {
        if (!$jamKeluar || !$shiftEnd) {
            return false;
        }
        $jamKeluarTime = strpos($jamKeluar, ' ') !== false ? strtotime($jamKeluar) : strtotime($date . ' ' . $jamKeluar);
        $shiftEndTime = strtotime($date . ' ' . $shiftEnd);
        // For overnight shifts (ending at 00:00:00 or early hours), shift end is next day
        if ($shiftEnd === '00:00:00' || ($shiftEnd < '12:00:00' && $shiftEnd !== '00:00:00')) {
            $shiftEndTime += 24 * 3600; // Add 24 hours for next day
        }
        $jamKeluarTimeOnly = date('H:i:s', $jamKeluarTime);
        $shiftEndTimeOnly = $shiftEnd;
        if ($jamKeluarTimeOnly < '12:00:00' && $shiftEndTimeOnly > '12:00:00') {
            $jamKeluarTime += 24 * 3600; // jam_keluar is next day
        } elseif ($shiftEnd === '00:00:00' && $jamKeluarTimeOnly > '00:00:00' && $jamKeluarTimeOnly < '12:00:00') {
            $jamKeluarTime += 24 * 3600; // jam_keluar is next day
        }
        return $jamKeluarTime > $shiftEndTime;
    }

    private function calculateMinutesOvertime($jamKeluar, $shiftEnd, $date)
    {
        if (!$jamKeluar || !$shiftEnd) {
            return 0;
        }
        $jamKeluarTime = strpos($jamKeluar, ' ') !== false ? strtotime($jamKeluar) : strtotime($date . ' ' . $jamKeluar);
        $shiftEndTime = strtotime($date . ' ' . $shiftEnd);
        // For overnight shifts (ending at 00:00:00 or early hours), shift end is next day
        if ($shiftEnd === '00:00:00' || ($shiftEnd < '12:00:00' && $shiftEnd !== '00:00:00')) {
            $shiftEndTime += 24 * 3600; // Add 24 hours for next day
        }
        $jamKeluarTimeOnly = date('H:i:s', $jamKeluarTime);
        $shiftEndTimeOnly = $shiftEnd;
        if ($jamKeluarTimeOnly < '12:00:00' && $shiftEndTimeOnly > '12:00:00') {
            $jamKeluarTime += 24 * 3600; // jam_keluar is next day
        } elseif ($shiftEnd === '00:00:00' && $jamKeluarTimeOnly > '00:00:00' && $jamKeluarTimeOnly < '12:00:00') {
            $jamKeluarTime += 24 * 3600; // jam_keluar is next day
        }
        if ($jamKeluarTime > $shiftEndTime) {
            return round(($jamKeluarTime - $shiftEndTime) / 60);
        }
        return 0;
    }

    public function exportExcel(Request $request)
    {
        $employees = Employee::with(['jatahLibur', 'pengajuanLibur', 'pengajuanTidakMasuk'])->get();
        $data = $employees->map(function ($employee) {
            $sakit = $employee->pengajuanTidakMasuk->where('jenis', 'sakit')->sum('total_hari');
            $izin = $employee->pengajuanTidakMasuk->where('jenis', 'izin')->sum('total_hari');
            $cuti = $employee->pengajuanLibur->where('jenis_libur', 'cuti')->sum('total_hari');
            $jatahCuti = $employee->jatahLibur ? $employee->jatahLibur->jatah_cuti_tahunan : 0;
            $sisaCuti = $jatahCuti - $cuti;

            $attendance = $employee->attendanceRekap;
            $on_time = 0;
            $overtime = 0;
            $terlambat = 0;
            $menit_terlambat = 0;
            foreach ($attendance as $a) {
                $jamMasuk = $a->jam_masuk ? (explode(' ', $a->jam_masuk)[1] ?? $a->jam_masuk) : null;
                $jamKeluar = $a->jam_keluar ? (explode(' ', $a->jam_keluar)[1] ?? $a->jam_keluar) : null;
                $shiftStart = $a->shift_start ?? null;
                $shiftEnd = $a->shift_end ?? null;
                $date = $a->date ?? null;
                if ($this->isLate($jamMasuk, $shiftStart, $date)) {
                    $terlambat++;
                    $menit_terlambat += $this->calculateMinutesLate($jamMasuk, $shiftStart, $date);
                } else if ($jamMasuk && $shiftStart) {
                    $on_time++;
                }
                if ($this->hasOvertime($jamKeluar, $shiftEnd, $date)) {
                    $overtime += $this->calculateMinutesOvertime($jamKeluar, $shiftEnd, $date);
                }
            }

            return [
                'no_induk' => $employee->no_induk,
                'nama' => $employee->nama,
                'sakit' => $sakit,
                'izin' => $izin,
                'cuti' => $cuti,
                'sisa_cuti' => $sisaCuti,
                'on_time' => $on_time,
                'overtime' => $overtime,
                'terlambat' => $terlambat,
                'menit_terlambat' => $menit_terlambat,
            ];
        });
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\RekapKehadiranExport($data), 'rekap_kehadiran.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $employees = Employee::with(['jatahLibur', 'pengajuanLibur', 'pengajuanTidakMasuk'])->get();
        $data = $employees->map(function ($employee) {
            $sakit = $employee->pengajuanTidakMasuk->where('jenis', 'sakit')->sum('total_hari');
            $izin = $employee->pengajuanTidakMasuk->where('jenis', 'izin')->sum('total_hari');
            $cuti = $employee->pengajuanLibur->where('jenis_libur', 'cuti')->sum('total_hari');
            $jatahCuti = $employee->jatahLibur ? $employee->jatahLibur->jatah_cuti_tahunan : 0;
            $sisaCuti = $jatahCuti - $cuti;

            $attendance = $employee->attendanceRekap;
            $on_time = 0;
            $overtime = 0;
            $terlambat = 0;
            $menit_terlambat = 0;
            foreach ($attendance as $a) {
                $jamMasuk = $a->jam_masuk ? (explode(' ', $a->jam_masuk)[1] ?? $a->jam_masuk) : null;
                $jamKeluar = $a->jam_keluar ? (explode(' ', $a->jam_keluar)[1] ?? $a->jam_keluar) : null;
                $shiftStart = $a->shift_start ?? null;
                $shiftEnd = $a->shift_end ?? null;
                $date = $a->date ?? null;
                if ($this->isLate($jamMasuk, $shiftStart, $date)) {
                    $terlambat++;
                    $menit_terlambat += $this->calculateMinutesLate($jamMasuk, $shiftStart, $date);
                } else if ($jamMasuk && $shiftStart) {
                    $on_time++;
                }
                if ($this->hasOvertime($jamKeluar, $shiftEnd, $date)) {
                    $overtime += $this->calculateMinutesOvertime($jamKeluar, $shiftEnd, $date);
                }
            }

            return [
                'no_induk' => $employee->no_induk,
                'nama' => $employee->nama,
                'sakit' => $sakit,
                'izin' => $izin,
                'cuti' => $cuti,
                'sisa_cuti' => $sisaCuti,
                'on_time' => $on_time,
                'overtime' => $overtime,
                'terlambat' => $terlambat,
                'menit_terlambat' => $menit_terlambat,
            ];
        });
    $pdf = \PDF::loadView('laporan.hrd.rekap_kehadiran_pdf', ['data' => $data]);
    $pdf->setPaper('A4', 'landscape');
    return $pdf->download('rekap_kehadiran.pdf');
    }
}
