<?php

namespace App\Support\Navigation;

use App\Models\User;
use Carbon\Carbon;

/**
 * Notification counts shown as badges in the main sidebar.
 * Each count is isolated so one failing query never breaks the sidebar.
 */
class NavigationBadges
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function all(): array
    {
        $user = $this->user;
        $counts = [];

        $counts['jadwal_unread'] = $this->safe(fn () => $user->unreadNotifications()
            ->where('type', \App\Notifications\JadwalBerubahNotification::class)
            ->count());

        $counts['daily_journal_deadline'] = $this->safe(fn () => \App\Models\DailyJournalTask::query()
            ->where('user_id', $user->id)
            ->whereNotNull('deadline_date')
            ->whereNotIn('status', ['done', 'skipped'])
            ->count());

        // Request Desain: Marketing = new + waiting review + my jobs in revision; requester = own requests in review
        $counts['design_request_action'] = $this->safe(function () use ($user) {
            $q = \App\Models\Marketing\DesignRequest::query();
            if (\App\Support\Marketing\MarketingAccess::isMarketing($user)) {
                return $q->where(function ($w) use ($user) {
                    // a content job in Review is approved on the Content Plan (counted in its own badge)
                    $w->where('status', 'Baru')
                        ->orWhere(fn ($r) => $r->where('status', 'Review')
                            ->where(fn ($c) => $c->whereNull('content_plan_id')->orWhere('jenis', '!=', 'Konten Sosmed')))
                        ->orWhere(fn ($m) => $m->where('designer_id', $user->id)->where('status', 'Revisi'));
                })->count();
            }

            return $q->where('requester_id', $user->id)->where('status', 'Review')->count();
        });

        if ($user->hasAnyRole(['Farmasi', 'Admin', 'Finance'])) {
            $counts['harga_menunggu'] = $this->safe(fn () => \App\Models\ERM\ObatHargaPengajuan::where('status', 'menunggu')->count());
        }

        if ($user->hasAnyRole(['Marketing', 'Admin', 'Finance'])) {
            $counts['harga_tindakan_menunggu'] = $this->safe(fn () => \App\Models\ERM\TindakanHargaPengajuan::where('status', 'menunggu')->count());
        }

        if ($user->hasAnyRole(['Admin', 'Rnd'])) {
            // Products with a sertifikasi ending within 90 days or already ended (same as the "Perlu perpanjangan" card)
            $counts['rnd_sertifikasi'] = $this->safe(fn () => \App\Models\Rnd\RndProduk::query()
                ->whereHas('sertifikasi', fn ($s) => $s->perluPerpanjangan())
                ->count());
        }

        if ($user->hasAnyRole(['Marketing', 'Admin', 'Finance'])) {
            $today = Carbon::today();
            // content waiting for a Marketing review (approve / minta revisi)
            $counts['content_scheduled'] = $this->safe(fn () => \App\Models\Marketing\ContentPlan::where('status', 'Review')->count());
            $counts['hari_penting_today'] = $this->safe(fn () => \App\Models\Marketing\HariPenting::where(function ($q) use ($today) {
                $q->where(function ($q2) use ($today) {
                    $q2->whereDate('start_date', $today)->whereNull('end_date');
                })->orWhere(function ($q2) use ($today) {
                    $q2->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today);
                });
            })->count());
        }

        if ($user->hasAnyRole(['Hrd', 'Manager', 'Head Manager', 'Employee', 'Admin'])) {
            $counts['kemitraan_soon'] = $this->safe(fn () => \App\Models\Workdoc\Kemitraan::whereNotNull('end_date')
                ->whereDate('end_date', '<=', Carbon::today()->addDays(183))
                ->count());
        }

        if ($user->hasAnyRole(['Hrd', 'Admin', 'Manager', 'Head Manager'])) {
            $counts['catatan_dosa'] = $this->safe(fn () => \App\Models\HRD\CatatanDosa::where('status_tindaklanjut', 'dalam proses')->count());
        }

        if ($user->hasAnyRole(['Hrd', 'Admin', 'Ceo', 'Head Manager'])) {
            $counts['dokter_izin'] = $this->safe(fn () => \App\Http\Controllers\ERM\DokterController::izinAlertCount());
        }

        return array_merge($counts, $this->absensiApprovals());
    }

    /** Pending absensi approvals: for HRD (second step) and for the user as direct approver (first step). */
    private function absensiApprovals(): array
    {
        $counts = [];
        $isHrd = $this->user->hasAnyRole(['Hrd', 'Admin']);
        $employee = $this->user->employee;
        $subordinateIds = [];

        try {
            if ($employee && $employee->hasDirectSubordinates()) {
                $subordinateIds = $employee->directSubordinateEmployeeIds();
            }
        } catch (\Throwable $e) {
            $subordinateIds = [];
        }

        // Sakit/Izin and Lembur: HRD sees new requests too (no manager step needed first)
        $waitingHrdNullable = function ($q) {
            $q->where(function ($q1) {
                $q1->whereNull('status_manager')->whereNull('status_hrd');
            })->orWhere(function ($q2) {
                $q2->where('status_manager', 'disetujui')->whereNull('status_hrd');
            });
        };

        if ($isHrd) {
            $counts['libur_hrd'] = $this->safe(fn () => \App\Models\HRD\PengajuanLibur::where('status_manager', 'disetujui')
                ->where('status_hrd', 'menunggu')->count());
            $counts['tidak_masuk_hrd'] = $this->safe(fn () => \App\Models\HRD\PengajuanTidakMasuk::where($waitingHrdNullable)->count());
            $counts['lembur_hrd'] = $this->safe(fn () => \App\Models\HRD\PengajuanLembur::where($waitingHrdNullable)->count());
            $counts['ganti_shift_hrd'] = $this->safe(fn () => \App\Models\HRD\PengajuanGantiShift::where('status_manager', 'disetujui')
                ->where(function ($q) {
                    $q->where('status_hrd', 'menunggu')->orWhereNull('status_hrd');
                })->count());
        }

        if (!empty($subordinateIds)) {
            $counts['libur_manager'] = $this->safe(fn () => \App\Models\HRD\PengajuanLibur::whereIn('employee_id', $subordinateIds)
                ->where('status_manager', 'menunggu')->count());
            $counts['tidak_masuk_manager'] = $this->safe(fn () => \App\Models\HRD\PengajuanTidakMasuk::whereIn('employee_id', $subordinateIds)
                ->whereNull('status_manager')->count());
            $counts['lembur_manager'] = $this->safe(fn () => \App\Models\HRD\PengajuanLembur::whereIn('employee_id', $subordinateIds)
                ->whereNull('status_manager')->count());
            $counts['ganti_shift_manager'] = $this->safe(fn () => \App\Models\HRD\PengajuanGantiShift::whereIn('employee_id', $subordinateIds)
                ->where(function ($q) {
                    $q->where('status_manager', 'menunggu')->orWhereNull('status_manager');
                })->count());
        }

        return $counts;
    }

    private function safe(callable $query): int
    {
        try {
            return (int) $query();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
