@php
    $periodEnd = ($dashboardFilter['period_end'] ?? \Carbon\Carbon::now())->copy()->endOfDay();
    $periodStart = ($dashboardFilter['period_start'] ?? $periodEnd->copy()->startOfMonth())->copy()->startOfDay();

    $selectedClinicId = (int) request()->query('klinik_id', 0);
    $clinicOptions = \Illuminate\Support\Facades\DB::table('erm_klinik')
        ->select('id', 'nama')
        ->orderBy('nama')
        ->get();

    $firstVisitSubquery = \Illuminate\Support\Facades\DB::table('erm_visitations')
        ->select('pasien_id', \Illuminate\Support\Facades\DB::raw('MIN(tanggal_visitation) as first_visit_date'))
        ->whereNotNull('pasien_id')
        ->where('status_kunjungan', 2)
        ->when($selectedClinicId > 0, function ($query) use ($selectedClinicId) {
            $query->where('klinik_id', $selectedClinicId);
        })
        ->groupBy('pasien_id');

    $periodPatientVisitSummary = \Illuminate\Support\Facades\DB::table('erm_visitations as v')
        ->joinSub($firstVisitSubquery, 'fv', function ($join) {
            $join->on('fv.pasien_id', '=', 'v.pasien_id');
        })
        ->leftJoin('finance_invoices as inv', 'inv.visitation_id', '=', 'v.id')
        ->whereNotNull('v.pasien_id')
        ->where('v.status_kunjungan', 2)
        ->when($selectedClinicId > 0, function ($query) use ($selectedClinicId) {
            $query->where('v.klinik_id', $selectedClinicId);
        })
        ->whereBetween('v.tanggal_visitation', [$periodStart->toDateString(), $periodEnd->toDateString()])
        ->groupBy('v.pasien_id', 'fv.first_visit_date')
        ->selectRaw("v.pasien_id,
            COUNT(DISTINCT v.id) as visit_count,
            MAX(v.tanggal_visitation) as last_visit_date,
            fv.first_visit_date,
            COALESCE(SUM(CASE
                WHEN inv.payment_date IS NOT NULL AND inv.status IN ('paid', 'partial')
                THEN inv.total_amount
                ELSE 0
            END), 0) as revenue_total")
        ->get()
        ->keyBy('pasien_id');

    $periodPatientIds = $periodPatientVisitSummary->keys()->filter()->values();

    $periodPatients = $periodPatientIds->isEmpty()
        ? collect()
        : \App\Models\ERM\Pasien::query()
            ->whereIn('id', $periodPatientIds)
            ->get(['id', 'nama', 'tanggal_lahir', 'gender', 'status_pasien', 'pekerjaan', 'created_at']);

    $totalPatients = $periodPatientVisitSummary->count();
    $totalVisits = (int) $periodPatientVisitSummary->sum('visit_count');
    $averageVisits = $totalPatients > 0 ? round($totalVisits / $totalPatients, 1) : 0;
    $totalRevenue = (float) $periodPatientVisitSummary->sum('revenue_total');
    $averageRevenuePerPatient = $totalPatients > 0 ? ($totalRevenue / $totalPatients) : 0;

    $newPatientIds = $periodPatientVisitSummary
        ->filter(function ($summary) use ($periodStart, $periodEnd) {
            if (empty($summary->first_visit_date)) {
                return false;
            }

            $firstVisitDate = \Carbon\Carbon::parse($summary->first_visit_date);
            return $firstVisitDate->between($periodStart, $periodEnd);
        })
        ->keys();

    $newPatientsCount = $newPatientIds->count();
    $returningPatientsCount = max(0, $totalPatients - $newPatientsCount);
    $retentionRate = $totalPatients > 0 ? round(($returningPatientsCount / $totalPatients) * 100, 1) : 0;

    $ageBuckets = [
        ['label' => '< 18', 'min' => 0, 'max' => 17],
        ['label' => '18 - 24', 'min' => 18, 'max' => 24],
        ['label' => '25 - 34', 'min' => 25, 'max' => 34],
        ['label' => '35 - 44', 'min' => 35, 'max' => 44],
        ['label' => '45 - 54', 'min' => 45, 'max' => 54],
        ['label' => '55+', 'min' => 55, 'max' => null],
    ];

    $resolveAge = function ($tanggalLahir) use ($periodEnd) {
        if (empty($tanggalLahir)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($tanggalLahir)->diffInYears($periodEnd);
        } catch (\Throwable $exception) {
            return null;
        }
    };

    $resolveAgeBucket = function ($age) use ($ageBuckets) {
        if ($age === null) {
            return 'Tidak diketahui';
        }

        foreach ($ageBuckets as $bucket) {
            $min = $bucket['min'];
            $max = $bucket['max'];

            if ($age >= $min && ($max === null || $age <= $max)) {
                return $bucket['label'];
            }
        }

        return 'Tidak diketahui';
    };

    $normalizeGender = function ($gender) {
        return match (strtolower(trim((string) $gender))) {
            'l', 'male', 'laki-laki', 'lakilaki' => 'Laki-laki',
            'p', 'female', 'perempuan' => 'Perempuan',
            default => 'Tidak diketahui',
        };
    };

    $normalizeStatus = function ($status) {
        $status = trim((string) $status);
        return $status !== '' ? $status : 'Regular';
    };

    $normalizeOccupation = function ($occupation) {
        $occupation = trim((string) $occupation);
        return $occupation !== '' ? $occupation : 'Tidak diketahui';
    };

    $patientProfiles = $periodPatients
        ->map(function ($patient) use ($periodPatientVisitSummary, $resolveAge, $resolveAgeBucket, $normalizeGender, $normalizeStatus, $normalizeOccupation, $newPatientIds) {
            $summary = $periodPatientVisitSummary->get($patient->id);
            $age = $resolveAge($patient->tanggal_lahir);

            return (object) [
                'id' => $patient->id,
                'nama' => $patient->nama,
                'age' => $age,
                'age_bucket' => $resolveAgeBucket($age),
                'gender_label' => $normalizeGender($patient->gender),
                'status_pasien' => $normalizeStatus($patient->status_pasien),
                'pekerjaan' => $normalizeOccupation($patient->pekerjaan),
                'visit_count' => (int) ($summary->visit_count ?? 0),
                'revenue_total' => (float) ($summary->revenue_total ?? 0),
                'is_new' => $newPatientIds->contains($patient->id),
            ];
        })
        ->values();

    $knownAgeProfiles = $patientProfiles->filter(function ($patient) {
        return $patient->age !== null;
    });

    $averageAge = $knownAgeProfiles->isNotEmpty()
        ? round((float) $knownAgeProfiles->avg('age'), 1)
        : null;

    $genderCounts = $patientProfiles
        ->groupBy('gender_label')
        ->map(function ($items) {
            return $items->count();
        });

    $maleCount = (int) $genderCounts->get('Laki-laki', 0);
    $femaleCount = (int) $genderCounts->get('Perempuan', 0);
    $unknownGenderCount = (int) $genderCounts->get('Tidak diketahui', 0);
    $genderTotalForChart = max(1, $maleCount + $femaleCount + $unknownGenderCount);
    $malePercent = round(($maleCount / $genderTotalForChart) * 100, 1);
    $femalePercent = round(($femaleCount / $genderTotalForChart) * 100, 1);
    $unknownGenderPercent = round(($unknownGenderCount / $genderTotalForChart) * 100, 1);
    $maleSegmentEnd = $malePercent;
    $femaleSegmentEnd = $malePercent + $femalePercent;
    $genderPieBackground = sprintf(
        'conic-gradient(#2563eb 0%% %.1f%%, #ec4899 %.1f%% %.1f%%, #cbd5e1 %.1f%% 100%%)',
        $maleSegmentEnd,
        $maleSegmentEnd,
        $femaleSegmentEnd,
        $femaleSegmentEnd
    );

    $statusCounts = $patientProfiles
        ->groupBy('status_pasien')
        ->map(function ($items) {
            return $items->count();
        })
        ->sortDesc();

    $ageRangeStats = collect($ageBuckets)
        ->map(function ($bucket) use ($patientProfiles, $totalPatients) {
            $bucketPatients = $patientProfiles->filter(function ($patient) use ($bucket) {
                return $patient->age_bucket === $bucket['label'];
            });

            $patientCount = $bucketPatients->count();
            $revenue = (float) $bucketPatients->sum('revenue_total');

            return (object) [
                'label' => $bucket['label'],
                'patient_count' => $patientCount,
                'patient_percentage' => $totalPatients > 0 ? round(($patientCount / $totalPatients) * 100, 1) : 0,
                'revenue_total' => $revenue,
                'average_revenue' => $patientCount > 0 ? ($revenue / $patientCount) : 0,
            ];
        })
        ->values();

    $unknownAgeCount = $patientProfiles->where('age_bucket', 'Tidak diketahui')->count();
    $unknownOccupationCount = $patientProfiles->where('pekerjaan', 'Tidak diketahui')->count();
    $incompletePatientCount = $patientProfiles->filter(function ($patient) {
        return $patient->age_bucket === 'Tidak diketahui'
            || $patient->gender_label === 'Tidak diketahui'
            || $patient->pekerjaan === 'Tidak diketahui';
    })->count();

    if ($unknownAgeCount > 0) {
        $unknownAgeRevenue = (float) $patientProfiles
            ->where('age_bucket', 'Tidak diketahui')
            ->sum('revenue_total');

        $ageRangeStats->push((object) [
            'label' => 'Tidak diketahui',
            'patient_count' => $unknownAgeCount,
            'patient_percentage' => $totalPatients > 0 ? round(($unknownAgeCount / $totalPatients) * 100, 1) : 0,
            'revenue_total' => $unknownAgeRevenue,
            'average_revenue' => $unknownAgeCount > 0 ? ($unknownAgeRevenue / $unknownAgeCount) : 0,
        ]);
    }

    $topAgeRevenue = $ageRangeStats->sortByDesc('revenue_total')->first();

    $occupationStats = $patientProfiles
        ->groupBy('pekerjaan')
        ->map(function ($items, $occupation) {
            $patientCount = $items->count();
            $revenue = (float) $items->sum('revenue_total');

            return (object) [
                'occupation' => $occupation,
                'patient_count' => $patientCount,
                'revenue_total' => $revenue,
                'average_revenue' => $patientCount > 0 ? ($revenue / $patientCount) : 0,
            ];
        })
        ->sortByDesc(function ($item) {
            return $item->patient_count;
        })
        ->take(6)
        ->values();

    $formatNumber = function ($value) {
        return number_format((float) $value, 0, ',', '.');
    };

    $formatDecimal = function ($value) {
        return number_format((float) $value, 1, ',', '.');
    };

    $formatCurrency = function ($value) {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    };

    $renderStatusBadgeClass = function ($status) {
        return match (strtolower(trim((string) $status))) {
            'vip' => 'badge-warning',
            'familia' => 'badge-primary',
            'black card' => 'badge-dark',
            'red flag' => 'badge-danger',
            default => 'badge-secondary',
        };
    };

    $modalId = 'pasienStatsModal' . ($widget->id ?? uniqid());
@endphp

<div class="card h-100 border-0 shadow-sm dashboard-widget-card" style="border-radius: 18px; overflow: hidden;">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3" style="gap: 12px;">
            <div>
                <h5 class="card-title mb-1">{{ $widget->widget_name ?? 'Pasien Widget' }}</h5>
            </div>
            <div class="mt-2 mt-md-0 d-flex align-items-start align-items-md-center flex-wrap ml-md-auto" style="gap: 12px;">
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <select name="klinik_id" class="form-control form-control-sm" data-dashboard-filter-input onchange="$('#dashboard-filter-apply').trigger('click');" style="min-width: 180px; border-radius: 10px;">
                        <option value="0">Semua Klinik</option>
                        @foreach ($clinicOptions as $clinic)
                            <option value="{{ $clinic->id }}" {{ $selectedClinicId === (int) $clinic->id ? 'selected' : '' }}>{{ $clinic->nama }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#{{ $modalId }}" style="white-space: nowrap; min-width: 132px;">See all pasien data</button>
                </div>
                <div class="text-md-right">
                <div>
                    <div class="small text-muted">Periode aktif</div>
                    <div class="font-weight-bold text-dark">{{ $periodStart->translatedFormat('d M Y') }} - {{ $periodEnd->translatedFormat('d M Y') }}</div>
                </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6 col-xl-3 mb-3">
                <div class="h-100 p-3" style="border-radius: 16px; background: linear-gradient(135deg, #2563eb 0%, #60a5fa 100%); color: #fff;">
                    <div class="small text-uppercase" style="opacity: 0.82; letter-spacing: 0.04em;">Total Pasien</div>
                    <div class="font-weight-bold" style="font-size: 2rem; line-height: 1.1;">{{ $formatNumber($totalPatients) }}</div>
                    <div class="small" style="opacity: 0.88;">{{ $formatNumber($totalVisits) }} visit pada periode aktif</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3 mb-3">
                <div class="h-100 p-3" style="border-radius: 16px; background: linear-gradient(135deg, #16a34a 0%, #4ade80 100%); color: #fff;">
                    <div class="small text-uppercase" style="opacity: 0.82; letter-spacing: 0.04em;">Pasien Baru</div>
                    <div class="font-weight-bold" style="font-size: 2rem; line-height: 1.1;">{{ $formatNumber($newPatientsCount) }}</div>
                    <div class="small" style="opacity: 0.88;">Returning {{ $formatNumber($returningPatientsCount) }} pasien</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3 mb-3">
                <div class="h-100 p-3" style="border-radius: 16px; background: linear-gradient(135deg, #d97706 0%, #fbbf24 100%); color: #fff;">
                    <div class="small text-uppercase" style="opacity: 0.82; letter-spacing: 0.04em;">Retention Rate</div>
                    <div class="font-weight-bold" style="font-size: 2rem; line-height: 1.1;">{{ $formatDecimal($retentionRate) }}%</div>
                    <div class="small" style="opacity: 0.88;">Pasien kembali di periode ini</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3 mb-3">
                <div class="h-100 p-3" style="border-radius: 16px; background: linear-gradient(135deg, #7c3aed 0%, #c084fc 100%); color: #fff;">
                    <div class="small text-uppercase mb-3" style="opacity: 0.82; letter-spacing: 0.04em;">Gender Pasien</div>
                    <div class="d-flex flex-column align-items-center text-center" style="min-height: 120px;">
                        <div style="width: 96px; height: 96px; border-radius: 999px; background: {{ $genderPieBackground }}; position: relative; box-shadow: inset 0 0 0 1px rgba(255,255,255,0.12); margin-bottom: 14px;">
                            <div style="position: absolute; inset: 16px; border-radius: 999px; background: rgba(124, 58, 237, 0.94);"></div>
                            <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; line-height: 1;">
                                <span style="font-size: 22px; font-weight: 700;">{{ $formatNumber($totalPatients) }}</span>
                                <span style="font-size: 10px; opacity: 0.85; margin-top: 4px;">pasien</span>
                            </div>
                        </div>
                        <div class="w-100">
                            <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                                <span class="small d-flex align-items-center" style="opacity: 0.92;"><span style="width: 10px; height: 10px; border-radius: 999px; background: #2563eb; display: inline-block; margin-right: 8px;"></span>Laki-laki</span>
                                <span class="small font-weight-bold">{{ $formatNumber($maleCount) }} <span style="opacity: 0.8;">{{ $formatDecimal($malePercent) }}%</span></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                                <span class="small d-flex align-items-center" style="opacity: 0.92;"><span style="width: 10px; height: 10px; border-radius: 999px; background: #ec4899; display: inline-block; margin-right: 8px;"></span>Perempuan</span>
                                <span class="small font-weight-bold">{{ $formatNumber($femaleCount) }} <span style="opacity: 0.8;">{{ $formatDecimal($femalePercent) }}%</span></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center px-1">
                                <span class="small d-flex align-items-center" style="opacity: 0.92;"><span style="width: 10px; height: 10px; border-radius: 999px; background: #cbd5e1; display: inline-block; margin-right: 8px;"></span>Tidak diketahui</span>
                                <span class="small font-weight-bold">{{ $formatNumber($unknownGenderCount) }} <span style="opacity: 0.8;">{{ $formatDecimal($unknownGenderPercent) }}%</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content" style="border-radius: 18px; overflow: hidden;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title" id="{{ $modalId }}Label">Statistik Pasien Lengkap</h5>
                    <div class="text-muted small">{{ $periodStart->translatedFormat('d M Y') }} - {{ $periodEnd->translatedFormat('d M Y') }}</div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body pt-3">
                <div class="row">
                    <div class="col-sm-6 col-xl-3 mb-3">
                        <div class="h-100 p-3" style="border-radius: 16px; background: linear-gradient(135deg, #2563eb 0%, #60a5fa 100%); color: #fff;">
                            <div class="small text-uppercase" style="opacity: 0.82; letter-spacing: 0.04em;">Total Pasien</div>
                            <div class="font-weight-bold" style="font-size: 2rem; line-height: 1.1;">{{ $formatNumber($totalPatients) }}</div>
                            <div class="small" style="opacity: 0.88;">{{ $formatNumber($totalVisits) }} visit, avg {{ $formatDecimal($averageVisits) }} visit/pasien</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3 mb-3">
                        <div class="h-100 p-3" style="border-radius: 16px; background: linear-gradient(135deg, #16a34a 0%, #4ade80 100%); color: #fff;">
                            <div class="small text-uppercase" style="opacity: 0.82; letter-spacing: 0.04em;">Pasien Baru</div>
                            <div class="font-weight-bold" style="font-size: 2rem; line-height: 1.1;">{{ $formatNumber($newPatientsCount) }}</div>
                            <div class="small" style="opacity: 0.88;">Returning {{ $formatNumber($returningPatientsCount) }} pasien</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3 mb-3">
                        <div class="h-100 p-3" style="border-radius: 16px; background: linear-gradient(135deg, #d97706 0%, #fbbf24 100%); color: #fff;">
                            <div class="small text-uppercase" style="opacity: 0.82; letter-spacing: 0.04em;">Retention Rate</div>
                            <div class="font-weight-bold" style="font-size: 2rem; line-height: 1.1;">{{ $formatDecimal($retentionRate) }}%</div>
                            <div class="small" style="opacity: 0.88;">Pasien kembali di periode ini</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3 mb-3">
                        <div class="h-100 p-3" style="border-radius: 16px; background: linear-gradient(135deg, #7c3aed 0%, #c084fc 100%); color: #fff;">
                            <div class="small text-uppercase" style="opacity: 0.82; letter-spacing: 0.04em;">Revenue Pasien</div>
                            <div class="font-weight-bold" style="font-size: 1.55rem; line-height: 1.2;">{{ $formatCurrency($totalRevenue) }}</div>
                            <div class="small" style="opacity: 0.88;">Avg {{ $formatCurrency($averageRevenuePerPatient) }}/pasien</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-4 mb-3 mb-xl-0">
                        <div class="h-100 p-3" style="border-radius: 16px; border: 1px solid rgba(148, 163, 184, 0.18); background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="text-uppercase text-muted font-weight-bold" style="font-size: 10px; letter-spacing: 0.04em;">Demografi</div>
                                <div class="small text-muted">Avg usia {{ $averageAge !== null ? $formatDecimal($averageAge) . ' thn' : '-' }}</div>
                            </div>

                            <div class="mb-3">
                                <div class="small text-muted mb-2">Gender</div>
                                @forelse ($genderCounts->sortDesc() as $label => $count)
                                    <div class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-2' : '' }}">
                                        <span class="text-dark small">{{ $label }}</span>
                                        <span class="font-weight-bold">{{ $formatNumber($count) }}</span>
                                    </div>
                                @empty
                                    <div class="text-muted small">Belum ada data gender.</div>
                                @endforelse
                            </div>

                            <div>
                                <div class="small text-muted mb-2">Status Pasien</div>
                                @forelse ($statusCounts->take(5) as $status => $count)
                                    <div class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-2' : '' }}">
                                        <span class="small"><span class="badge {{ $renderStatusBadgeClass($status) }} mr-2">{{ $status }}</span></span>
                                        <span class="font-weight-bold">{{ $formatNumber($count) }}</span>
                                    </div>
                                @empty
                                    <div class="text-muted small">Belum ada data status pasien.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-8">
                        <div class="h-100 p-3" style="border-radius: 16px; border: 1px solid rgba(148, 163, 184, 0.18); background: #ffffff;">
                            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                                <div class="text-uppercase text-muted font-weight-bold" style="font-size: 10px; letter-spacing: 0.04em;">Age Range & Revenue</div>
                                <div class="small text-muted">Top revenue age range: {{ $topAgeRevenue?->label ?? '-' }} {{ $topAgeRevenue ? '(' . $formatCurrency($topAgeRevenue->revenue_total) . ')' : '' }}</div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr class="text-muted small">
                                            <th style="border-top: 0;">Usia</th>
                                            <th class="text-right" style="border-top: 0;">Pasien</th>
                                            <th class="text-right" style="border-top: 0;">Share</th>
                                            <th class="text-right" style="border-top: 0;">Revenue</th>
                                            <th class="text-right" style="border-top: 0;">Avg / Pasien</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($ageRangeStats as $bucket)
                                            <tr>
                                                <td class="align-middle text-dark">{{ $bucket->label }}</td>
                                                <td class="align-middle text-right font-weight-bold">{{ $formatNumber($bucket->patient_count) }}</td>
                                                <td class="align-middle text-right">{{ $formatDecimal($bucket->patient_percentage) }}%</td>
                                                <td class="align-middle text-right text-dark">{{ $formatCurrency($bucket->revenue_total) }}</td>
                                                <td class="align-middle text-right text-muted">{{ $formatCurrency($bucket->average_revenue) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">Belum ada data usia pasien pada periode aktif.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-xl-7 mb-3 mb-xl-0">
                        <div class="h-100 p-3" style="border-radius: 16px; border: 1px solid rgba(148, 163, 184, 0.18); background: #ffffff;">
                            <div class="text-uppercase text-muted font-weight-bold mb-3" style="font-size: 10px; letter-spacing: 0.04em;">Pekerjaan Pasien</div>

                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr class="text-muted small">
                                            <th style="border-top: 0; width: 48%;">Pekerjaan</th>
                                            <th class="text-right" style="border-top: 0;">Pasien</th>
                                            <th class="text-right" style="border-top: 0;">Revenue</th>
                                            <th class="text-right" style="border-top: 0;">Avg / Pasien</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($occupationStats as $occupation)
                                            <tr>
                                                <td class="align-middle text-dark">{{ $occupation->occupation }}</td>
                                                <td class="align-middle text-right font-weight-bold">{{ $formatNumber($occupation->patient_count) }}</td>
                                                <td class="align-middle text-right text-dark">{{ $formatCurrency($occupation->revenue_total) }}</td>
                                                <td class="align-middle text-right text-muted">{{ $formatCurrency($occupation->average_revenue) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">Belum ada data pekerjaan pasien.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-5">
                        <div class="h-100 p-3" style="border-radius: 16px; border: 1px solid rgba(148, 163, 184, 0.18); background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);">
                            <div class="text-uppercase text-muted font-weight-bold mb-3" style="font-size: 10px; letter-spacing: 0.04em;">Highlight</div>

                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom" style="border-color: rgba(148, 163, 184, 0.18) !important;">
                                <span class="small text-muted">Unknown age profile</span>
                                <span class="font-weight-bold text-dark">{{ $formatNumber($unknownAgeCount) }} pasien</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom" style="border-color: rgba(148, 163, 184, 0.18) !important;">
                                <span class="small text-muted">Unknown gender profile</span>
                                <span class="font-weight-bold text-dark">{{ $formatNumber($unknownGenderCount) }} pasien</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom" style="border-color: rgba(148, 163, 184, 0.18) !important;">
                                <span class="small text-muted">Unknown occupation profile</span>
                                <span class="font-weight-bold text-dark">{{ $formatNumber($unknownOccupationCount) }} pasien</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom" style="border-color: rgba(148, 163, 184, 0.18) !important;">
                                <span class="small text-muted">Incomplete patient data</span>
                                <span class="font-weight-bold text-dark">{{ $formatNumber($incompletePatientCount) }} pasien</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted">Complete demographic data</span>
                                <span class="font-weight-bold text-dark">{{ $formatNumber(max(0, $totalPatients - $incompletePatientCount)) }} pasien</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>