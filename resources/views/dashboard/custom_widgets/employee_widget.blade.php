@php
    $periodEnd = ($dashboardFilter['period_end'] ?? \Carbon\Carbon::now())->copy()->endOfDay();
    $periodStart = ($dashboardFilter['period_start'] ?? $periodEnd->copy()->startOfMonth())->copy()->startOfDay();

    $employees = \App\Models\HRD\Employee::query()
        ->select([
            'id',
            'nama',
            'status',
            'jenis_kelamin',
            'tanggal_lahir',
            'tanggal_masuk',
            'perusahaan',
        ])
        ->get();

    $activeEmployees = $employees->filter(function ($employee) {
        return strtolower((string) ($employee->status ?? '')) !== 'tidak aktif';
    })->values();

    $activeCount = $activeEmployees->count();
    $inactiveCount = $employees->count() - $activeCount;

    $statusCounts = [
        'tetap' => $activeEmployees->filter(function ($employee) {
            return strtolower((string) ($employee->status ?? '')) === 'tetap';
        })->count(),
        'kontrak' => $activeEmployees->filter(function ($employee) {
            return strtolower((string) ($employee->status ?? '')) === 'kontrak';
        })->count(),
        'freelance' => $activeEmployees->filter(function ($employee) {
            return strtolower((string) ($employee->status ?? '')) === 'freelance';
        })->count(),
    ];

    $genderCounts = [
        'male' => $activeEmployees->filter(function ($employee) {
            return strtolower((string) ($employee->jenis_kelamin ?? '')) === 'l';
        })->count(),
        'female' => $activeEmployees->filter(function ($employee) {
            return strtolower((string) ($employee->jenis_kelamin ?? '')) === 'p';
        })->count(),
    ];

    $companyCounts = [
        'premiere' => $activeEmployees->filter(function ($employee) {
            return str_contains(strtolower((string) ($employee->perusahaan ?? '')), 'premiere');
        })->count(),
        'skin' => $activeEmployees->filter(function ($employee) {
            $company = strtolower((string) ($employee->perusahaan ?? ''));
            return str_contains($company, 'pratama') || str_contains($company, 'skin') || str_contains($company, 'belova');
        })->count(),
        'dental' => $activeEmployees->filter(function ($employee) {
            return str_contains(strtolower((string) ($employee->perusahaan ?? '')), 'dental');
        })->count(),
    ];

    $averageAge = round((float) $activeEmployees
        ->filter(function ($employee) {
            return !empty($employee->tanggal_lahir);
        })
        ->map(function ($employee) use ($periodEnd) {
            return $employee->tanggal_lahir->diffInYears($periodEnd);
        })
        ->avg(), 1);

    $joinedInPeriod = $employees->filter(function ($employee) use ($periodStart, $periodEnd) {
        if (empty($employee->tanggal_masuk)) {
            return false;
        }

        return $employee->tanggal_masuk->between($periodStart, $periodEnd);
    })->count();

    $recentJoiners = $employees
        ->filter(function ($employee) use ($periodStart, $periodEnd) {
            if (empty($employee->tanggal_masuk)) {
                return false;
            }

            return $employee->tanggal_masuk->between($periodStart, $periodEnd);
        })
        ->sortByDesc('tanggal_masuk')
        ->take(4)
        ->values();

    $formatNumber = function ($value) {
        return number_format((float) $value, 0, ',', '.');
    };
@endphp

<div class="card h-100 border-0 shadow-sm dashboard-widget-card" style="border-radius: 18px; overflow: hidden;">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
            <div>
                <h5 class="card-title mb-1">{{ $widget->widget_name ?? 'Employee Widget' }}</h5>
                <div class="text-muted small">Ringkasan karyawan aktif, status kerja, gender, dan karyawan masuk pada periode filter.</div>
            </div>
            <div class="text-md-right mt-2 mt-md-0">
                <div class="small text-muted">Periode aktif</div>
                <div class="font-weight-bold text-dark">{{ $periodStart->translatedFormat('d M Y') }} - {{ $periodEnd->translatedFormat('d M Y') }}</div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-7 mb-4 mb-xl-0">
                <div class="row">
                    <div class="col-sm-6 col-xl-4 mb-3">
                        <div class="h-100 p-3" style="border-radius: 16px; background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%); color: #fff;">
                            <div class="small text-uppercase" style="opacity: 0.8; letter-spacing: 0.04em;">Karyawan Aktif</div>
                            <div class="font-weight-bold" style="font-size: 2rem; line-height: 1.1;">{{ $formatNumber($activeCount) }}</div>
                            <div class="small" style="opacity: 0.85;">Tidak aktif: {{ $formatNumber($inactiveCount) }}</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4 mb-3">
                        <div class="h-100 p-3" style="border-radius: 16px; background: linear-gradient(135deg, #1d4ed8 0%, #60a5fa 100%); color: #fff;">
                            <div class="small text-uppercase" style="opacity: 0.8; letter-spacing: 0.04em;">Masuk Periode Ini</div>
                            <div class="font-weight-bold" style="font-size: 2rem; line-height: 1.1;">{{ $formatNumber($joinedInPeriod) }}</div>
                            <div class="small" style="opacity: 0.85;">Berdasarkan tanggal masuk</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4 mb-3">
                        <div class="h-100 p-3" style="border-radius: 16px; background: linear-gradient(135deg, #7c3aed 0%, #c084fc 100%); color: #fff;">
                            <div class="small text-uppercase" style="opacity: 0.8; letter-spacing: 0.04em;">Rata-rata Usia</div>
                            <div class="font-weight-bold" style="font-size: 2rem; line-height: 1.1;">{{ $averageAge > 0 ? number_format($averageAge, 1, ',', '.') : '-' }}</div>
                            <div class="small" style="opacity: 0.85;">Tahun</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-3 mb-lg-0">
                        <div class="h-100 p-3" style="border-radius: 16px; border: 1px solid rgba(148, 163, 184, 0.18); background: #ffffff;">
                            <div class="text-uppercase text-muted font-weight-bold mb-3" style="font-size: 10px; letter-spacing: 0.04em;">Status Karyawan</div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small">Tetap</span>
                                <span class="font-weight-bold text-dark">{{ $formatNumber($statusCounts['tetap']) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small">Kontrak</span>
                                <span class="font-weight-bold text-dark">{{ $formatNumber($statusCounts['kontrak']) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Freelance</span>
                                <span class="font-weight-bold text-dark">{{ $formatNumber($statusCounts['freelance']) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="h-100 p-3" style="border-radius: 16px; border: 1px solid rgba(148, 163, 184, 0.18); background: #ffffff;">
                            <div class="text-uppercase text-muted font-weight-bold mb-3" style="font-size: 10px; letter-spacing: 0.04em;">Gender Aktif</div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small">Laki-laki</span>
                                <span class="font-weight-bold text-dark">{{ $formatNumber($genderCounts['male']) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Perempuan</span>
                                <span class="font-weight-bold text-dark">{{ $formatNumber($genderCounts['female']) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="h-100 p-3" style="border-radius: 16px; border: 1px solid rgba(148, 163, 184, 0.18); background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);">
                    <div class="text-uppercase text-muted font-weight-bold mb-3" style="font-size: 10px; letter-spacing: 0.04em;">Distribusi Perusahaan</div>
                    <div class="mb-3 p-3" style="border-radius: 14px; background: rgba(241, 245, 249, 0.75);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-muted">Klinik Utama Premiere Belova</span>
                            <span class="font-weight-bold text-dark">{{ $formatNumber($companyCounts['premiere']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-muted">Klinik Pratama Belova Skin & Beauty Center</span>
                            <span class="font-weight-bold text-dark">{{ $formatNumber($companyCounts['skin']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-muted">Belova Dental Care</span>
                            <span class="font-weight-bold text-dark">{{ $formatNumber($companyCounts['dental']) }}</span>
                        </div>
                    </div>

                    <div class="text-uppercase text-muted font-weight-bold mb-3" style="font-size: 10px; letter-spacing: 0.04em;">Karyawan Masuk Periode Ini</div>

                    @if ($recentJoiners->isEmpty())
                        <div class="alert alert-light border mb-0">
                            Tidak ada karyawan baru pada periode aktif.
                        </div>
                    @else
                        @foreach ($recentJoiners as $employee)
                            <div class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-2 pb-2 border-bottom' : '' }}" style="border-color: rgba(148, 163, 184, 0.18) !important;">
                                <div class="pr-3">
                                    <div class="font-weight-bold text-dark" style="font-size: 13px;">{{ $employee->nama }}</div>
                                    <div class="small text-muted">{{ $employee->perusahaan ?: '-' }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="small font-weight-bold text-dark">{{ optional($employee->tanggal_masuk)->translatedFormat('d M Y') ?: '-' }}</div>
                                    <div class="small text-muted text-capitalize">{{ $employee->status ?: '-' }}</div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>