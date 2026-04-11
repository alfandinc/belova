@extends('layouts.erm.app')

@section('title', 'Belova Skin - Statistik Kunjungan')

@section('navbar')
    @include('layouts.ceodashboard.navbar')
@endsection

@section('content')
    @php
        $initialFilters = $initial['filters'] ?? [
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->toDateString(),
        ];
    @endphp
    <div class="container-fluid mt-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap:12px;">
                    <div>
                        <h4 class="card-title mb-1">Belova Skin</h4>
                        <p class="text-muted mb-0">Statistik kunjungan untuk Klinik ID = 2 / Klinik Pratama Belova Skin &amp; Beauty Center (status_kunjungan = 2).</p>
                    </div>
                    <div class="d-flex flex-wrap align-items-end" style="gap:8px;">
                        <div>
                            <label class="mb-1 small text-muted d-block">Date Range</label>
                            <input
                                type="text"
                                id="filter-daterange"
                                class="form-control form-control-sm"
                                style="min-width: 250px;"
                                value="{{ ($initialFilters['start_date'] ?? now()->startOfYear()->toDateString()) . ' - ' . ($initialFilters['end_date'] ?? now()->toDateString()) }}"
                            >
                        </div>

                        <button type="button" id="reset-zoom" class="btn btn-sm btn-outline-secondary">Reset View</button>
                    </div>
                </div>

                <ul class="nav nav-tabs mb-3" id="premiereBelovaTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-visitation-link" data-toggle="tab" href="#tab-visitation" role="tab">Visitation</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-revenue-link" data-toggle="tab" href="#tab-revenue" role="tab">Revenue</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-doctor-link" data-toggle="tab" href="#tab-doctor" role="tab">Doctor</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-patient-link" data-toggle="tab" href="#tab-patient" role="tab">Patient</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-medicine-link" data-toggle="tab" href="#tab-medicine" role="tab">Medicine</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-treatment-link" data-toggle="tab" href="#tab-treatment" role="tab">Treatment</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-laboratorium-link" data-toggle="tab" href="#tab-laboratorium" role="tab">Laboratorium</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-social-media-link" data-toggle="tab" href="#tab-social-media" role="tab">Social Media</a>
                    </li>
                </ul>

                <div class="tab-content" id="premiereBelovaTabContent">
                    <div class="tab-pane fade show active" id="tab-visitation" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-4 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Total Visits</div>
                                    <div class="h4 mb-0" id="stat-total-visits">-</div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Avg Visits / Day</div>
                                    <div class="h4 mb-0" id="stat-avg-day">-</div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Avg Visits / Week</div>
                                    <div class="h4 mb-0" id="stat-avg-week">-</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-9 mb-3">
                                <div id="visitationChartArea">
                                    <div id="visitationChart"></div>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="border rounded p-3 mb-3">
                                    <h6 class="mb-3">Jenis Kunjungan</h6>
                                    <table class="table table-sm mb-0">
                                        <tbody>
                                        <tr>
                                            <th>Konsultasi</th>
                                            <td id="jenis-konsultasi">-</td>
                                        </tr>
                                        <tr>
                                            <th>Beli Produk</th>
                                            <td id="jenis-beli">-</td>
                                        </tr>
                                        <tr>
                                            <th>Lab</th>
                                            <td id="jenis-lab">-</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="border rounded p-3">
                                    <h6 class="mb-3">Peak Visits</h6>
                                    <table class="table table-sm mb-0">
                                        <tbody>
                                        <tr>
                                            <th>Peak Day</th>
                                            <td id="peak-day-label">-</td>
                                            <td id="peak-day-count">-</td>
                                        </tr>
                                        <tr>
                                            <th>Peak Week</th>
                                            <td id="peak-week-label">-</td>
                                            <td id="peak-week-count">-</td>
                                        </tr>
                                        <tr>
                                            <th>Peak Month</th>
                                            <td id="peak-month-label">-</td>
                                            <td id="peak-month-count">-</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Metode Pembayaran</h6>
                                    <table class="table table-sm mb-0">
                                        <tbody id="payment-method-body">
                                        <tr>
                                            <td colspan="2" class="text-muted">-</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-revenue" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-6 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Total Revenue</div>
                                    <div class="h4 mb-0" id="stat-revenue-total">-</div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Avg Revenue / Visit</div>
                                    <div class="h4 mb-0" id="stat-avg-revenue-per-visit">-</div>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded p-3">
                            <h6 class="mb-3">Revenue Trend</h6>
                            <div id="revenueChart"></div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-patient" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-4 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">New Patients</div>
                                    <div class="h4 mb-0" id="stat-new">-</div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Returning Patients</div>
                                    <div class="h4 mb-0" id="stat-returning">-</div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Retention Rate</div>
                                    <div class="h4 mb-0" id="stat-retention">-</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">Gender Distribution</h6>
                                        <span class="small text-muted">Total Patients: <span id="stat-total-patients">-</span></span>
                                    </div>
                                    <div id="patientGenderChart"></div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">Age Distribution</h6>
                                        <span class="small text-muted">Average Age: <span id="stat-average-age">-</span></span>
                                    </div>
                                    <div id="patientAgeChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-medicine" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-6 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Total Resep Items</div>
                                    <div class="h4 mb-0" id="stat-total-prescription-items">-</div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Total Obat Sold</div>
                                    <div class="h4 mb-0" id="stat-total-medicine-qty">-</div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-8 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Best Selling Medicines</h6>
                                    <div id="medicineChart"></div>
                                </div>
                            </div>
                            <div class="col-lg-4 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Most Obat Sold</h6>
                                    <table class="table table-sm mb-0">
                                        <tbody id="medicine-top-body">
                                        <tr>
                                            <td colspan="2" class="text-muted">-</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-treatment" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-12 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Total Treatments</div>
                                    <div class="h4 mb-0" id="stat-total-tindakan">-</div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-8 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Best Selling Treatments</h6>
                                    <div id="tindakanChart"></div>
                                </div>
                            </div>
                            <div class="col-lg-4 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Top Treatments</h6>
                                    <table class="table table-sm mb-0">
                                        <tbody id="tindakan-top-body">
                                        <tr>
                                            <td colspan="2" class="text-muted">-</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-doctor" role="tabpanel">
                        <div class="mb-3 d-flex justify-content-between align-items-end flex-wrap" style="gap:12px;">
                            <div style="flex:1; min-width:280px;">
                                <label for="premiereDoctorSelect" class="form-label">Pilih Dokter</label>
                                <select id="premiereDoctorSelect" class="form-control">
                                    <option value="0">-- Pilih Dokter --</option>
                                    @foreach($dokterList as $d)
                                        <option value="{{ $d->id }}">{{ $d->user->name ?? ('Dokter ' . $d->id) }}@if($d->spesialisasi) - {{ $d->spesialisasi->nama }}@endif</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="text-muted small">Menggunakan date range filter Belova Skin.</div>
                        </div>

                        <div class="row">
                            <div class="col-lg-4 mb-3">
                                <div class="card shadow-sm h-100" style="border-radius:12px; overflow:hidden;">
                                    <div style="position:relative;">
                                        <div id="doctorTabSpecBadge" style="position:absolute; right:12px; top:12px; background:#fff; border:2px solid #4f7df0; color:#111; padding:6px 12px; border-radius:999px; font-weight:700; box-shadow:0 4px 10px rgba(79,125,240,0.12); z-index:5; display:none;"></div>
                                        <img id="doctorTabPhoto" src="{{ asset('img/avatar.png') }}" alt="foto dokter" class="card-img-top" style="height:300px; object-fit:cover; display:block; background:#f7f7f7;">
                                    </div>
                                    <div id="doctorTabNameBar" style="background:#4f7df0; padding:14px 18px; color:#fff; font-weight:700; font-size:1.25rem;">-</div>
                                    <div class="card-body">
                                        <ul id="doctorTabMeta" class="list-unstyled mb-0">
                                            <li><span class="text-muted">NIK:</span> <strong>-</strong></li>
                                            <li class="mt-2"><span class="text-muted">SIP:</span> <strong>-</strong></li>
                                            <li class="mt-2"><span class="text-muted">STR:</span> <strong>-</strong></li>
                                            <li class="mt-2"><span class="text-muted">Klinik:</span> <strong>-</strong></li>
                                            <li class="mt-2"><span class="text-muted">No HP:</span> <strong>-</strong></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-8 mb-3">
                                <div class="card shadow-sm h-100" style="border-radius:10px;">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div id="doctorTabHeading" style="font-weight:700; color:#2c2f45; font-size:1.05rem;">Visitation</div>
                                            <div class="text-muted" style="font-size:0.9rem;">Filter mengikuti date range Belova Skin</div>
                                        </div>
                                        <div id="doctorTabVisitationChart"></div>

                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th style="width:70%">Jenis Kunjungan</th>
                                                                <th class="text-end" style="width:30%">Jumlah</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td><strong>Total</strong></td>
                                                                <td class="text-end"><span id="doctor-totalVisits">-</span></td>
                                                            </tr>
                                                            <tr>
                                                                <td>Konsultasi</td>
                                                                <td class="text-end"><span id="doctor-kunjungan1">-</span></td>
                                                            </tr>
                                                            <tr>
                                                                <td style="padding-left:18px;">Konsultasi (Tanpa Lab)</td>
                                                                <td class="text-end"><span id="doctor-kunjungan1_nolab">-</span></td>
                                                            </tr>
                                                            <tr>
                                                                <td style="padding-left:18px;">Konsultasi dengan Lab</td>
                                                                <td class="text-end"><span id="doctor-kunjungan1_withlab">-</span></td>
                                                            </tr>
                                                            <tr>
                                                                <td>Beli Produk</td>
                                                                <td class="text-end"><span id="doctor-kunjungan2">-</span></td>
                                                            </tr>
                                                            <tr>
                                                                <td>Lab</td>
                                                                <td class="text-end"><span id="doctor-kunjungan3">-</span></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Keterangan</th>
                                                                <th class="text-end">Jumlah</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td><strong>Total Pasien</strong></td>
                                                                <td class="text-end"><span id="doctor-ret-total">-</span></td>
                                                            </tr>
                                                            <tr>
                                                                <td>Pasien Baru</td>
                                                                <td class="text-end"><span id="doctor-ret-new">-</span></td>
                                                            </tr>
                                                            <tr>
                                                                <td>Pasien Kembali</td>
                                                                <td class="text-end"><span id="doctor-ret-returning">-</span></td>
                                                            </tr>
                                                            <tr>
                                                                <td>Retention Rate</td>
                                                                <td class="text-end"><span id="doctor-ret-rate">-</span></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-4 mb-3">
                                <div class="card shadow-sm h-100" style="border-radius:10px;">
                                    <div class="card-body">
                                        <h5 class="card-title">Treatment</h5>
                                        <p class="text-muted mb-2">Top treatment untuk dokter pada periode aktif.</p>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped">
                                                <thead>
                                                    <tr>
                                                        <th style="width:6%">#</th>
                                                        <th>Treatment</th>
                                                        <th style="width:18%">Kunjungan</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="doctor-treatment-body">
                                                    <tr><td colspan="3" class="text-muted text-center">Memuat...</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card shadow-sm h-100" style="border-radius:10px;">
                                    <div class="card-body">
                                        <h5 class="card-title">Obat</h5>
                                        <p class="text-muted mb-2">Obat yang diresepkan oleh dokter pada periode aktif.</p>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped">
                                                <thead>
                                                    <tr>
                                                        <th style="width:6%">#</th>
                                                        <th>Obat</th>
                                                        <th style="width:18%">Jumlah</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="doctor-obat-body">
                                                    <tr><td colspan="3" class="text-muted text-center">Memuat...</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card shadow-sm h-100" style="border-radius:10px;">
                                    <div class="card-body">
                                        <h5 class="card-title">Lab</h5>
                                        <p class="text-muted mb-2">Permintaan lab yang selesai untuk dokter pada periode aktif.</p>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped">
                                                <thead>
                                                    <tr>
                                                        <th style="width:6%">#</th>
                                                        <th>Tes Lab</th>
                                                        <th style="width:18%">Jumlah</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="doctor-lab-body">
                                                    <tr><td colspan="3" class="text-muted text-center">Memuat...</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card shadow-sm" style="border-radius:10px;">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h4 class="mb-0" style="font-weight:700; color:#2c2f45;">Statistik Pasien Dokter</h4>
                                            <div class="text-muted" style="font-size:0.9rem;">Periode mengikuti filter Belova Skin.</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <div class="card p-2 h-100">
                                                    <div class="text-muted">Gender</div>
                                                    <div id="doctorGenderChart" style="height:360px;"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="card p-2 h-100">
                                                    <div class="text-muted">Kelompok Usia</div>
                                                    <div id="doctorAgeChart" style="height:360px;"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="card p-2 h-100">
                                                    <div class="text-muted">Top Pasien</div>
                                                    <div class="table-responsive mt-2">
                                                        <table class="table table-sm">
                                                            <thead>
                                                                <tr>
                                                                    <th style="width:5%">No</th>
                                                                    <th>Nama Pasien</th>
                                                                    <th style="width:20%" class="text-end">Spend</th>
                                                                    <th style="width:20%" class="text-end">Kunjungan</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="doctor-top-patients-body">
                                                                <tr><td colspan="4" class="text-muted text-center">Memuat...</td></tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-laboratorium" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-6 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Total Permintaan</div>
                                    <div class="h4 mb-0" id="stat-total-lab-requests">-</div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Completed Requests</div>
                                    <div class="h4 mb-0" id="stat-completed-lab-requests">-</div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-8 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Best Selling Lab Tests</h6>
                                    <div id="labChart"></div>
                                </div>
                            </div>
                            <div class="col-lg-4 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Top Lab Tests</h6>
                                    <table class="table table-sm mb-0">
                                        <tbody id="lab-top-body">
                                        <tr>
                                            <td colspan="2" class="text-muted">-</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-social-media" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Total Content Plans</div>
                                    <div class="h4 mb-0" id="sm-total-plans">-</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Published Plans</div>
                                    <div class="h4 mb-0" id="sm-published-plans">-</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Scheduled Plans</div>
                                    <div class="h4 mb-0" id="sm-scheduled-plans">-</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Reports Logged</div>
                                    <div class="h4 mb-0" id="sm-total-reports">-</div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Total Interactions</div>
                                    <div class="h4 mb-0" id="sm-total-interactions">-</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Total Reach</div>
                                    <div class="h4 mb-0" id="sm-total-reach">-</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Avg ERI</div>
                                    <div class="h4 mb-0" id="sm-avg-eri">-</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Avg ERR</div>
                                    <div class="h4 mb-0" id="sm-avg-err">-</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Publish Trend</h6>
                                    <div id="socialPublishChart"></div>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Engagement Trend</h6>
                                    <div id="socialInteractionChart"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-4 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Status Breakdown</h6>
                                    <div id="socialStatusChart"></div>
                                </div>
                            </div>
                            <div class="col-lg-4 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Platform Breakdown</h6>
                                    <div id="socialPlatformChart"></div>
                                </div>
                            </div>
                            <div class="col-lg-4 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Content Type Breakdown</h6>
                                    <div id="socialJenisChart"></div>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded p-3">
                            <h6 class="mb-3">Top Content Plans</h6>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:32%">Title</th>
                                            <th style="width:12%">Status</th>
                                            <th style="width:16%">Publish</th>
                                            <th style="width:16%">Platforms</th>
                                            <th class="text-end" style="width:10%">Reports</th>
                                            <th class="text-end" style="width:14%">Interactions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="social-top-content-body">
                                        <tr>
                                            <td colspan="6" class="text-muted">-</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.3"></script>
    @if(!empty($initial) && is_array($initial))
    <script>
        window.INIT_VISITS = {!! json_encode($initial) !!};
    </script>
    @endif
    <script>
        (function(){
            if (!window.jQuery) return console.error('jQuery is required for this chart to load');
            if (!window.ApexCharts) return console.error('ApexCharts missing');
            if (typeof moment === 'undefined') return console.error('moment.js is required for the date range picker');

            var colors = ['#1f77b4','#ff7f0e','#2ca02c','#d62728','#9467bd'];
            var chart = null;
            var revenueChart = null;
            var genderChart = null;
            var ageChart = null;
            var medicineChart = null;
            var tindakanChart = null;
            var labChart = null;
            var socialPublishChart = null;
            var socialInteractionChart = null;
            var socialStatusChart = null;
            var socialPlatformChart = null;
            var socialJenisChart = null;
            var doctorDetailVisitationChart = null;
            var doctorDetailGenderChart = null;
            var doctorDetailAgeChart = null;
            var initialData = window.INIT_VISITS || null;
            var latestData = initialData;
            var selectedStart = "{{ $initialFilters['start_date'] ?? now()->startOfYear()->toDateString() }}";
            var selectedEnd = "{{ $initialFilters['end_date'] ?? now()->toDateString() }}";
            var baseStart = selectedStart;
            var baseEnd = selectedEnd;
            var suppressZoomFetch = false;

            function formatRangeLabel(startDate, endDate) {
                return startDate + ' - ' + endDate;
            }

            function toIsoDate(input) {
                return moment(input).format('YYYY-MM-DD');
            }

            function formatCurrency(value) {
                try {
                    return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
                } catch (e) {
                    return 'Rp ' + (value || 0);
                }
            }

            function formatNumber(value) {
                try {
                    return Number(value || 0).toLocaleString('id-ID');
                } catch (e) {
                    return value || 0;
                }
            }

            function renderRevenueChart(data) {
                var bucketLabels = data.bucket_labels || [];
                var revenues = (data.revenues && data.revenues[0]) ? data.revenues[0] : [];
                var revenueEl = document.getElementById('revenueChart');
                if (!revenueEl) return;

                var opts = {
                    chart: { type: 'bar', height: 420, toolbar: { show: false } },
                    series: [{ name: 'Revenue', data: revenues }],
                    colors: ['#198754'],
                    plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                    dataLabels: { enabled: false },
                    xaxis: {
                        categories: bucketLabels,
                        labels: { rotate: 0, hideOverlappingLabels: false }
                    },
                    yaxis: {
                        labels: {
                            formatter: function(value) { return formatCurrency(value); }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function(value) { return formatCurrency(value); }
                        }
                    },
                    grid: { padding: { top: 12, right: 12, bottom: 0, left: 12 } }
                };

                try {
                    if (revenueChart) { try { revenueChart.destroy(); } catch(e){} revenueChart = null; }
                    revenueChart = new ApexCharts(revenueEl, opts);
                    revenueChart.render();
                } catch (e) { console.error(e); }
            }

            function renderRankedBarChart(chartInstance, elementId, seriesName, items, valueKey, color) {
                var chartEl = document.getElementById(elementId);
                if (!chartEl) return chartInstance;

                var categories = (items || []).map(function(item) { return item.name || '-'; });
                var values = (items || []).map(function(item) { return item[valueKey] || 0; });
                var opts = {
                    chart: { type: 'bar', height: 360, toolbar: { show: false } },
                    series: [{ name: seriesName, data: values }],
                    colors: [color],
                    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '65%' } },
                    dataLabels: { enabled: false },
                    xaxis: { categories: categories },
                    legend: { show: false },
                    grid: { padding: { top: 0, right: 12, bottom: 0, left: 12 } }
                };

                try {
                    if (chartInstance) { try { chartInstance.destroy(); } catch(e){} chartInstance = null; }
                    chartInstance = new ApexCharts(chartEl, opts);
                    chartInstance.render();
                } catch (e) { console.error(e); }

                return chartInstance;
            }

            function renderSimpleRankingTable(bodyId, items, valueKey, emptyText) {
                var body = document.getElementById(bodyId);
                if (!body) return;
                body.innerHTML = '';

                if (!Array.isArray(items) || !items.length) {
                    body.innerHTML = '<tr><td colspan="2" class="text-muted">' + emptyText + '</td></tr>';
                    return;
                }

                items.forEach(function(item) {
                    var row = document.createElement('tr');
                    var th = document.createElement('th');
                    var td = document.createElement('td');
                    th.textContent = item.name || '-';
                    td.textContent = typeof item[valueKey] !== 'undefined' ? item[valueKey] : 0;
                    row.appendChild(th);
                    row.appendChild(td);
                    body.appendChild(row);
                });
            }

            function renderPatientCharts(stats) {
                var demographics = stats && stats.patient_demographics ? stats.patient_demographics : {};
                var gender = demographics.gender || { male: 0, female: 0, other: 0 };
                var ageBuckets = demographics.age && demographics.age.buckets ? demographics.age.buckets : {};

                var genderEl = document.getElementById('patientGenderChart');
                if (genderEl) {
                    var genderOpts = {
                        chart: { type: 'donut', height: 320 },
                        series: [gender.male || 0, gender.female || 0, gender.other || 0],
                        labels: ['Male', 'Female', 'Other'],
                        colors: ['#0d6efd', '#e83e8c', '#6c757d'],
                        legend: { position: 'bottom' },
                        dataLabels: { enabled: true }
                    };

                    try {
                        if (genderChart) { try { genderChart.destroy(); } catch(e){} genderChart = null; }
                        genderChart = new ApexCharts(genderEl, genderOpts);
                        genderChart.render();
                    } catch (e) { console.error(e); }
                }

                var ageEl = document.getElementById('patientAgeChart');
                if (ageEl) {
                    var ageLabels = Object.keys(ageBuckets);
                    var ageValues = ageLabels.map(function(label) { return ageBuckets[label] || 0; });
                    var ageOpts = {
                        chart: { type: 'bar', height: 320, toolbar: { show: false } },
                        series: [{ name: 'Patients', data: ageValues }],
                        colors: ['#fd7e14'],
                        plotOptions: { bar: { borderRadius: 4, distributed: true } },
                        xaxis: { categories: ageLabels },
                        dataLabels: { enabled: false },
                        legend: { show: false }
                    };

                    try {
                        if (ageChart) { try { ageChart.destroy(); } catch(e){} ageChart = null; }
                        ageChart = new ApexCharts(ageEl, ageOpts);
                        ageChart.render();
                    } catch (e) { console.error(e); }
                }
            }

            function renderSpecialtyTabs(stats) {
                var medicine = stats && stats.medicine ? stats.medicine : { total_prescription_items: 0, total_medicine_qty: 0, top_obats: [] };
                var tindakan = stats && stats.tindakan ? stats.tindakan : { total_tindakan: 0, top_tindakans: [] };
                var laboratorium = stats && stats.laboratorium ? stats.laboratorium : { total_requests: 0, completed_requests: 0, top_labs: [] };

                document.getElementById('stat-total-prescription-items').textContent = medicine.total_prescription_items ?? 0;
                document.getElementById('stat-total-medicine-qty').textContent = medicine.total_medicine_qty ?? 0;
                document.getElementById('stat-total-tindakan').textContent = tindakan.total_tindakan ?? 0;
                document.getElementById('stat-total-lab-requests').textContent = laboratorium.total_requests ?? 0;
                document.getElementById('stat-completed-lab-requests').textContent = laboratorium.completed_requests ?? 0;

                medicineChart = renderRankedBarChart(medicineChart, 'medicineChart', 'Qty', medicine.top_obats || [], 'qty', '#0d6efd');
                tindakanChart = renderRankedBarChart(tindakanChart, 'tindakanChart', 'Count', tindakan.top_tindakans || [], 'count', '#6f42c1');
                labChart = renderRankedBarChart(labChart, 'labChart', 'Count', laboratorium.top_labs || [], 'count', '#20c997');

                renderSimpleRankingTable('medicine-top-body', medicine.top_obats || [], 'qty', 'Tidak ada data');
                renderSimpleRankingTable('tindakan-top-body', tindakan.top_tindakans || [], 'count', 'Tidak ada data');
                renderSimpleRankingTable('lab-top-body', laboratorium.top_labs || [], 'count', 'Tidak ada data');
            }

            function renderSocialMediaTab(stats) {
                var social = stats && stats.social_media ? stats.social_media : {
                    total_plans: 0,
                    published_plans: 0,
                    scheduled_plans: 0,
                    total_reports: 0,
                    total_interactions: 0,
                    total_reach: 0,
                    avg_eri: 0,
                    avg_err: 0,
                    status_breakdown: [],
                    platform_breakdown: [],
                    jenis_breakdown: [],
                    publish_trend: { labels: [], counts: [] },
                    interaction_trend: { labels: [], interactions: [], reach: [] },
                    top_content: []
                };

                document.getElementById('sm-total-plans').textContent = formatNumber(social.total_plans || 0);
                document.getElementById('sm-published-plans').textContent = formatNumber(social.published_plans || 0);
                document.getElementById('sm-scheduled-plans').textContent = formatNumber(social.scheduled_plans || 0);
                document.getElementById('sm-total-reports').textContent = formatNumber(social.total_reports || 0);
                document.getElementById('sm-total-interactions').textContent = formatNumber(social.total_interactions || 0);
                document.getElementById('sm-total-reach').textContent = formatNumber(social.total_reach || 0);
                document.getElementById('sm-avg-eri').textContent = Number(social.avg_eri || 0).toFixed(4);
                document.getElementById('sm-avg-err').textContent = Number(social.avg_err || 0).toFixed(4);

                var publishLabels = (social.publish_trend && social.publish_trend.labels ? social.publish_trend.labels : []).map(function(label) {
                    return /^\d{4}-\d{2}$/.test(label) ? moment(label + '-01').format('MMM YYYY') : label;
                });
                var publishOpts = {
                    chart: { type: 'bar', height: 320, toolbar: { show: false } },
                    series: [{ name: 'Plans', data: (social.publish_trend && social.publish_trend.counts) ? social.publish_trend.counts : [] }],
                    colors: ['#0d6efd'],
                    plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
                    dataLabels: { enabled: false },
                    xaxis: { categories: publishLabels },
                    legend: { show: false }
                };
                try {
                    if (socialPublishChart) { try { socialPublishChart.destroy(); } catch(e){} socialPublishChart = null; }
                    socialPublishChart = new ApexCharts(document.getElementById('socialPublishChart'), publishOpts);
                    socialPublishChart.render();
                } catch (e) { console.error(e); }

                var interactionLabels = (social.interaction_trend && social.interaction_trend.labels ? social.interaction_trend.labels : []).map(function(label) {
                    return /^\d{4}-\d{2}$/.test(label) ? moment(label + '-01').format('MMM YYYY') : label;
                });
                var interactionOpts = {
                    chart: { type: 'line', height: 320, toolbar: { show: false } },
                    series: [
                        { name: 'Interactions', data: (social.interaction_trend && social.interaction_trend.interactions) ? social.interaction_trend.interactions : [] },
                        { name: 'Reach', data: (social.interaction_trend && social.interaction_trend.reach) ? social.interaction_trend.reach : [] }
                    ],
                    colors: ['#fd7e14', '#20c997'],
                    stroke: { curve: 'smooth', width: 3 },
                    dataLabels: { enabled: false },
                    xaxis: { categories: interactionLabels },
                    yaxis: { labels: { formatter: function(value) { return formatNumber(Math.round(value || 0)); } } }
                };
                try {
                    if (socialInteractionChart) { try { socialInteractionChart.destroy(); } catch(e){} socialInteractionChart = null; }
                    socialInteractionChart = new ApexCharts(document.getElementById('socialInteractionChart'), interactionOpts);
                    socialInteractionChart.render();
                } catch (e) { console.error(e); }

                socialStatusChart = renderRankedBarChart(socialStatusChart, 'socialStatusChart', 'Plans', social.status_breakdown || [], 'count', '#6f42c1');
                socialPlatformChart = renderRankedBarChart(socialPlatformChart, 'socialPlatformChart', 'Plans', social.platform_breakdown || [], 'count', '#e83e8c');
                socialJenisChart = renderRankedBarChart(socialJenisChart, 'socialJenisChart', 'Plans', social.jenis_breakdown || [], 'count', '#198754');

                var topBody = document.getElementById('social-top-content-body');
                if (topBody) {
                    topBody.innerHTML = '';
                    var items = Array.isArray(social.top_content) ? social.top_content : [];
                    if (!items.length) {
                        topBody.innerHTML = '<tr><td colspan="6" class="text-muted">Tidak ada data</td></tr>';
                    } else {
                        items.forEach(function(item) {
                            var row = document.createElement('tr');
                            row.innerHTML = '' +
                                '<td>' + (item.title || '-') + '</td>' +
                                '<td>' + (item.status || '-') + '</td>' +
                                '<td>' + (item.publish_date ? moment(item.publish_date).format('DD MMM YYYY') : '-') + '</td>' +
                                '<td>' + ((item.platforms || []).join(', ') || '-') + '</td>' +
                                '<td class="text-end">' + formatNumber(item.reports_count || 0) + '</td>' +
                                '<td class="text-end">' + formatNumber(item.interactions || 0) + '</td>';
                            topBody.appendChild(row);
                        });
                    }
                }
            }

            function getPremiereDoctorId() {
                var sel = document.getElementById('premiereDoctorSelect');
                if (!sel) return null;
                if (!sel.value || sel.value === '0') {
                    var firstDoctor = sel.querySelector('option[value]:not([value="0"])');
                    if (firstDoctor) {
                        sel.value = firstDoctor.value;
                    }
                }
                return (sel.value && sel.value !== '0') ? sel.value : null;
            }

            function buildDoctorQuery(extra) {
                var params = [];
                if (selectedStart && selectedEnd) {
                    params.push('start=' + encodeURIComponent(selectedStart));
                    params.push('end=' + encodeURIComponent(selectedEnd));
                } else {
                    params.push('all=1');
                }
                if (extra) params.push(extra);
                return params.length ? ('?' + params.join('&')) : '';
            }

            function populateDoctorCard(data) {
                var img = document.getElementById('doctorTabPhoto');
                if (img) img.src = data.photo || img.src;

                var nameBar = document.getElementById('doctorTabNameBar');
                if (nameBar) nameBar.textContent = data.name || '-';

                var heading = document.getElementById('doctorTabHeading');
                if (heading) heading.textContent = 'Visitation - ' + (data.name || '-');

                var specEl = document.getElementById('doctorTabSpecBadge');
                if (specEl) {
                    var specName = data.spesialisasi || '';
                    specEl.textContent = specName;
                    specEl.style.display = specName ? 'block' : 'none';
                }

                var list = document.getElementById('doctorTabMeta');
                if (list) {
                    list.innerHTML = '';
                    [
                        ['NIK', data.nik],
                        ['SIP', data.sip],
                        ['STR', data.str],
                        ['Klinik', data.klinik],
                        ['No HP', data.no_hp]
                    ].forEach(function(item, idx) {
                        var li = document.createElement('li');
                        li.className = idx === 0 ? '' : 'mt-2';
                        li.innerHTML = '<span class="text-muted">' + item[0] + ':</span> <strong>' + (item[1] || '-') + '</strong>';
                        list.appendChild(li);
                    });
                }
            }

            function renderDoctorVisitationChart(labels, seriesData) {
                var chartEl = document.getElementById('doctorTabVisitationChart');
                if (!chartEl) return;

                var categories = (labels || []).map(function(label) {
                    if (/^\d{4}-\d{2}-\d{2}$/.test(label)) return moment(label).format('DD MMM');
                    if (/^\d{4}-\d{2}$/.test(label)) return moment(label + '-01').format('MMM YYYY');
                    return label;
                });

                var series = [];
                if (Array.isArray(seriesData) && seriesData.length && typeof seriesData[0] === 'number') {
                    series = [{ name: 'Kunjungan', data: seriesData }];
                } else if (Array.isArray(seriesData)) {
                    series = seriesData;
                }

                var options = {
                    chart: { type: 'line', height: 340, toolbar: { show: false } },
                    stroke: { curve: 'smooth', width: 2 },
                    series: series,
                    colors: ['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b'],
                    xaxis: { categories: categories, labels: { rotate: -30 } },
                    dataLabels: { enabled: false },
                    yaxis: { labels: { formatter: function(v){ return Math.round(v); } } },
                    tooltip: { shared: true, intersect: false }
                };

                try {
                    if (doctorDetailVisitationChart) { try { doctorDetailVisitationChart.destroy(); } catch(e){} doctorDetailVisitationChart = null; }
                    doctorDetailVisitationChart = new ApexCharts(chartEl, options);
                    doctorDetailVisitationChart.render();
                } catch (e) { console.error(e); }
            }

            function renderDoctorGenderChart(genderObj) {
                var el = document.getElementById('doctorGenderChart');
                if (!el) return;
                var opts = {
                    chart: { type: 'pie', height: 340, toolbar: { show: false } },
                    series: [genderObj.male || 0, genderObj.female || 0, genderObj.other || 0],
                    labels: ['Laki-laki', 'Perempuan', 'Other'],
                    colors: ['#4f7df0', '#f06f6f', '#9aa0ff'],
                    legend: { position: 'bottom' },
                    dataLabels: { enabled: true, formatter: function(val){ return val.toFixed(1) + '%'; } }
                };
                try {
                    if (doctorDetailGenderChart) { try { doctorDetailGenderChart.destroy(); } catch(e){} doctorDetailGenderChart = null; }
                    doctorDetailGenderChart = new ApexCharts(el, opts);
                    doctorDetailGenderChart.render();
                } catch (e) { console.error(e); }
            }

            function renderDoctorAgeChart(buckets) {
                var el = document.getElementById('doctorAgeChart');
                if (!el) return;
                var cats = ['0-17','18-30','31-45','46-60','61+'];
                var data = cats.map(function(k){ return (buckets && buckets[k]) ? buckets[k] : 0; });
                var opts = {
                    chart: { type: 'pie', height: 340, toolbar: { show: false } },
                    series: data,
                    labels: cats,
                    colors: ['#4f7df0','#6fcf97','#f6c85f','#f6a6a6','#9aa0ff'],
                    legend: { position: 'bottom' },
                    dataLabels: { enabled: true, formatter: function(val){ return val.toFixed(1) + '%'; } }
                };
                try {
                    if (doctorDetailAgeChart) { try { doctorDetailAgeChart.destroy(); } catch(e){} doctorDetailAgeChart = null; }
                    doctorDetailAgeChart = new ApexCharts(el, opts);
                    doctorDetailAgeChart.render();
                } catch (e) { console.error(e); }
            }

            function renderDoctorThreeColTable(bodyId, list, nameBuilder, valueBuilder, emptyText) {
                var body = document.getElementById(bodyId);
                if (!body) return;
                body.innerHTML = '';
                if (!Array.isArray(list) || !list.length) {
                    body.innerHTML = '<tr><td colspan="3" class="text-muted text-center">' + emptyText + '</td></tr>';
                    return;
                }
                list.forEach(function(item, idx) {
                    var tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + (idx + 1) + '</td><td>' + nameBuilder(item) + '</td><td class="text-end">' + valueBuilder(item) + '</td>';
                    body.appendChild(tr);
                });
            }

            function renderDoctorTopPatients(list) {
                var body = document.getElementById('doctor-top-patients-body');
                if (!body) return;
                body.innerHTML = '';
                if (!Array.isArray(list) || !list.length) {
                    body.innerHTML = '<tr><td colspan="4" class="text-muted text-center">Tidak ada data</td></tr>';
                    return;
                }
                var nf = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
                list.forEach(function(item, idx) {
                    var tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + (idx + 1) + '</td><td>' + (item.name || ('Pasien ' + item.pasien_id)) + '</td><td class="text-end">' + nf.format(Number(item.spend || 0)) + '</td><td class="text-end">' + (item.visits || 0) + '</td>';
                    body.appendChild(tr);
                });
            }

            function populateDoctorBreakdown(payload) {
                var breakdown = payload.breakdown || {};
                document.getElementById('doctor-totalVisits').textContent = payload.total || 0;
                document.getElementById('doctor-kunjungan1').textContent = breakdown[1] || 0;
                document.getElementById('doctor-kunjungan1_nolab').textContent = breakdown.konsultasi_no_lab || 0;
                document.getElementById('doctor-kunjungan1_withlab').textContent = breakdown.konsultasi_with_lab || 0;
                document.getElementById('doctor-kunjungan2').textContent = breakdown[2] || 0;
                document.getElementById('doctor-kunjungan3').textContent = breakdown[3] || 0;
            }

            function populateDoctorRetention(payload) {
                document.getElementById('doctor-ret-total').textContent = payload.total || 0;
                document.getElementById('doctor-ret-new').textContent = payload.new || 0;
                document.getElementById('doctor-ret-returning').textContent = payload.returning || 0;
                document.getElementById('doctor-ret-rate').textContent = (typeof payload.retention_rate !== 'undefined') ? (payload.retention_rate + ' %') : '-';
            }

            function fetchDoctorProfile(dokterId) {
                fetch('/ceo-dashboard/dokter/' + dokterId + '/data')
                    .then(function(res){ if (!res.ok) throw res; return res.json(); })
                    .then(function(payload){ if (payload && payload.ok && payload.data) populateDoctorCard(payload.data); })
                    .catch(function(err){ console.error('Failed to load doctor profile', err); });
            }

            function fetchDoctorVisitationStats(dokterId) {
                fetch('/ceo-dashboard/dokter/' + dokterId + '/visitation-stats' + buildDoctorQuery())
                    .then(function(res){ if (!res.ok) throw res; return res.json(); })
                    .then(function(payload){ if (payload && payload.ok) renderDoctorVisitationChart(payload.labels || [], payload.series || []); })
                    .catch(function(err){ console.error('Failed to load doctor visitation stats', err); });
            }

            function fetchDoctorBreakdown(dokterId) {
                fetch('/ceo-dashboard/dokter/' + dokterId + '/visitation-breakdown' + buildDoctorQuery())
                    .then(function(res){ if (!res.ok) throw res; return res.json(); })
                    .then(function(payload){ if (payload && payload.ok) populateDoctorBreakdown(payload); })
                    .catch(function(err){ console.error('Failed to load doctor breakdown', err); });
            }

            function fetchDoctorRetention(dokterId) {
                fetch('/ceo-dashboard/dokter/' + dokterId + '/retention-stats' + buildDoctorQuery())
                    .then(function(res){ if (!res.ok) throw res; return res.json(); })
                    .then(function(payload){ if (payload && payload.ok) populateDoctorRetention(payload); })
                    .catch(function(err){ console.error('Failed to load doctor retention', err); });
            }

            function fetchDoctorPatientStats(dokterId) {
                fetch('/ceo-dashboard/dokter/' + dokterId + '/patient-stats' + buildDoctorQuery())
                    .then(function(res){ if (!res.ok) throw res; return res.json(); })
                    .then(function(payload){ if (payload && payload.ok) { renderDoctorGenderChart(payload.gender || {}); renderDoctorAgeChart((payload.age && payload.age.buckets) ? payload.age.buckets : {}); } })
                    .catch(function(err){ console.error('Failed to load doctor patient stats', err); });
            }

            function fetchDoctorTopPatients(dokterId) {
                fetch('/ceo-dashboard/dokter/' + dokterId + '/top-patients' + buildDoctorQuery())
                    .then(function(res){ if (!res.ok) throw res; return res.json(); })
                    .then(function(payload){ if (payload && payload.ok) renderDoctorTopPatients(payload.tops || []); })
                    .catch(function(err){ console.error('Failed to load doctor top patients', err); });
            }

            function fetchDoctorTindakanStats(dokterId) {
                fetch('/ceo-dashboard/dokter/' + dokterId + '/tindakan-stats' + buildDoctorQuery())
                    .then(function(res){ if (!res.ok) throw res; return res.json(); })
                    .then(function(payload){ renderDoctorThreeColTable('doctor-treatment-body', (payload && payload.ok) ? (payload.tops || []) : [], function(item){ return item.name || ('Treatment ' + item.tindakan_id); }, function(item){ return item.count || 0; }, 'Tidak ada data.'); })
                    .catch(function(err){ console.error('Failed to load doctor treatment stats', err); renderDoctorThreeColTable('doctor-treatment-body', [], function(){ return ''; }, function(){ return 0; }, 'Tidak ada data.'); });
            }

            function fetchDoctorObatStats(dokterId) {
                fetch('/ceo-dashboard/dokter/' + dokterId + '/obat-stats' + buildDoctorQuery())
                    .then(function(res){ if (!res.ok) throw res; return res.json(); })
                    .then(function(payload){ renderDoctorThreeColTable('doctor-obat-body', (payload && payload.ok) ? (payload.tops || []) : [], function(item){ return item.name || ('Obat ' + item.obat_id); }, function(item){ return item.jumlah || 0; }, 'Tidak ada data.'); })
                    .catch(function(err){ console.error('Failed to load doctor obat stats', err); renderDoctorThreeColTable('doctor-obat-body', [], function(){ return ''; }, function(){ return 0; }, 'Tidak ada data.'); });
            }

            function fetchDoctorLabStats(dokterId) {
                fetch('/ceo-dashboard/dokter/' + dokterId + '/lab-stats' + buildDoctorQuery())
                    .then(function(res){ if (!res.ok) throw res; return res.json(); })
                    .then(function(payload){ renderDoctorThreeColTable('doctor-lab-body', (payload && payload.ok) ? (payload.tops || []) : [], function(item){ return item.name || ('Tes ' + item.lab_test_id); }, function(item){ return item.count || 0; }, 'Tidak ada data.'); })
                    .catch(function(err){ console.error('Failed to load doctor lab stats', err); renderDoctorThreeColTable('doctor-lab-body', [], function(){ return ''; }, function(){ return 0; }, 'Tidak ada data.'); });
            }

            function refreshDoctorTab() {
                var dokterId = getPremiereDoctorId();
                if (!dokterId) return;
                fetchDoctorProfile(dokterId);
                fetchDoctorVisitationStats(dokterId);
                fetchDoctorBreakdown(dokterId);
                fetchDoctorRetention(dokterId);
                fetchDoctorPatientStats(dokterId);
                fetchDoctorTopPatients(dokterId);
                fetchDoctorTindakanStats(dokterId);
                fetchDoctorObatStats(dokterId);
                fetchDoctorLabStats(dokterId);
            }

            function renderAllPanels(data) {
                renderChart(data);
                renderRevenueChart(data);
                if (data.stats) {
                    renderStats(data.stats);
                    renderPatientCharts(data.stats);
                    renderSpecialtyTabs(data.stats);
                    renderSocialMediaTab(data.stats);
                }
            }

            function renderChart(data) {
                var lastIndex = (data.series || []).length - 1;

                // build annotations for latest series points: top = count + previous-bucket change
                var annotationsPoints = [];
                var seriesAll = data.series || [];
                var bucketLabels = data.bucket_labels || [];
                var bucketRanges = data.bucket_ranges || [];
                var currentGroupBy = (data.filters && data.filters.group_by) ? data.filters.group_by : 'month';
                var currIdx = seriesAll.length - 1;
                var currSeriesData = (currIdx >= 0 && seriesAll[currIdx].data) ? seriesAll[currIdx].data : [];

                if (currIdx >= 0) {
                    for (var i = 0; i < currSeriesData.length; i++) {
                        var curr = typeof currSeriesData[i] !== 'undefined' ? currSeriesData[i] : 0;
                        var prev = i > 0 && typeof currSeriesData[i - 1] !== 'undefined' ? currSeriesData[i - 1] : null;
                        var arrow = '';
                        if (prev !== null) {
                            if (curr > prev) arrow = '▲';
                            else if (curr < prev) arrow = '▼';
                        }

                        var pctText = '';
                        if (prev !== null && prev !== 0) {
                            var change = Math.round(((curr - prev) / prev) * 100);
                            pctText = ' (' + (change > 0 ? '+' : '') + change + '%)';
                        }

                        var topText = String(curr) + (pctText || '') + (arrow ? (' ' + arrow) : '');
                        var clr = '#6c757d';
                        if (arrow === '▲') clr = '#28a745';
                        else if (arrow === '▼') clr = '#dc3545';

                        // top annotation (above point)
                        annotationsPoints.push({
                            x: bucketLabels[i],
                            y: curr,
                            marker: { size: 0 },
                            label: { text: topText, borderColor: clr, style: { color: '#ffffff', background: clr, fontSize: '12px' }, offsetY: -22 }
                        });
                    }
                }

                // determine colors per-series: latest (this year) = blue, previous (last year) = grey
                var seriesCount = (data.series || []).length;
                var seriesColors = [];
                for (var i = 0; i < seriesCount; i++) {
                    if (i === lastIndex) seriesColors.push('#1f77b4');
                    else if (i === lastIndex - 1) seriesColors.push('#6c757d');
                    else seriesColors.push(colors[i % colors.length]);
                }

                // opacity and stroke per series: make latest series bold/filled, others subtle
                var seriesOpacities = [];
                var strokeWidths = [];
                var markerSizes = [];
                for (var i = 0; i < seriesCount; i++) {
                    if (i === lastIndex) { seriesOpacities.push(0.85); strokeWidths.push(3); markerSizes.push(5); }
                    else if (i === lastIndex - 1) { seriesOpacities.push(0.12); strokeWidths.push(2); markerSizes.push(4); }
                    else { seriesOpacities.push(0.08); strokeWidths.push(2); markerSizes.push(3); }
                }

                var opts = {
                    chart: {
                        type: 'area',
                        height: 460,
                        parentHeightOffset: 0,
                        zoom: { enabled: true, type: 'x', autoScaleYaxis: true },
                        toolbar: { show: true, tools: { download: false, selection: true, zoom: true, zoomin: false, zoomout: false, pan: false, reset: true } },
                        events: {
                            zoomed: function(chartContext, payload) {
                                if (suppressZoomFetch) {
                                    suppressZoomFetch = false;
                                    return;
                                }

                                if (!payload || !payload.xaxis || payload.xaxis.min == null || payload.xaxis.max == null) return;

                                var minIndex = Math.max(0, Math.floor(payload.xaxis.min));
                                var maxIndex = Math.min(bucketRanges.length - 1, Math.ceil(payload.xaxis.max));
                                if (!bucketRanges[minIndex] || !bucketRanges[maxIndex]) return;

                                var zoomStart = bucketRanges[minIndex].start;
                                var zoomEnd = bucketRanges[maxIndex].end;
                                if (zoomStart === selectedStart && zoomEnd === selectedEnd) return;

                                selectedStart = zoomStart;
                                selectedEnd = zoomEnd;
                                $('#filter-daterange').val(formatRangeLabel(selectedStart, selectedEnd));
                                loadData(buildRequestParams());
                            },
                            beforeResetZoom: function() {
                                selectedStart = baseStart;
                                selectedEnd = baseEnd;
                                $('#filter-daterange').val(formatRangeLabel(selectedStart, selectedEnd));
                                loadData(buildRequestParams(), { skipZoomSync: true });

                                return {
                                    xaxis: {
                                        min: 0,
                                        max: Math.max(bucketLabels.length - 1, 0)
                                    }
                                };
                            }
                        }
                    },
                    stroke: { curve: 'smooth', width: strokeWidths },
                    series: data.series || [],
                    colors: seriesColors,
                    fill: { type: 'solid', opacity: seriesOpacities },
                    grid: { padding: { top: 32, right: 12, bottom: 0, left: 12 } },
                    xaxis: {
                        type: 'category',
                        categories: bucketLabels,
                        tickPlacement: 'on',
                        tickAmount: bucketLabels.length > 1 ? bucketLabels.length - 1 : 1,
                        labels: {
                            rotate: 0,
                            hideOverlappingLabels: false,
                            formatter: function(value) {
                                return value;
                            }
                        }
                    },
                    dataLabels: { enabled: false },
                    annotations: { points: annotationsPoints },
                    yaxis: { labels: { formatter: function(v){ return Math.round(v); } }, min: 0 },
                    tooltip: {
                        shared: true,
                        intersect: false,
                        x: {
                            formatter: function(value, opts) {
                                return bucketLabels[opts.dataPointIndex] || moment(value).format('DD MMM YYYY');
                            }
                        },
                        y: { formatter: function(v){ return Math.round(v); } }
                    },
                    legend: { position: 'top' },
                    markers: { size: markerSizes, hover: { size: 6 } }
                };

                var chartEl = document.getElementById('visitationChart');
                if (!chartEl) return;

                try {
                    if (chart) { try { chart.destroy(); } catch(e){} chart = null; }
                    chart = new ApexCharts(chartEl, opts);
                    chart.render();
                } catch(e) { console.error(e); }
            }

            function renderStats(stats) {
                try {
                    if (!stats) return;
                    document.getElementById('stat-total-visits').textContent = (typeof stats.total_visits !== 'undefined') ? stats.total_visits : '-';
                    document.getElementById('stat-avg-day').textContent = (typeof stats.avg_per_day !== 'undefined') ? stats.avg_per_day : '-';
                    document.getElementById('stat-avg-week').textContent = (typeof stats.avg_per_week !== 'undefined') ? stats.avg_per_week : '-';
                    document.getElementById('stat-revenue-total').textContent = formatCurrency(stats.revenue_total || 0);
                    document.getElementById('stat-avg-revenue-per-visit').textContent = formatCurrency(stats.avg_revenue_per_visit || 0);
                    document.getElementById('stat-new').textContent = (typeof stats.new !== 'undefined') ? stats.new : '-';
                    document.getElementById('stat-returning').textContent = (typeof stats.returning !== 'undefined') ? stats.returning : '-';
                    document.getElementById('stat-retention').textContent = (typeof stats.retention_rate !== 'undefined') ? (stats.retention_rate + '%') : '-';
                    document.getElementById('stat-total-patients').textContent = (typeof stats.total_patients !== 'undefined') ? stats.total_patients : '-';
                    var averageAge = stats.patient_demographics && stats.patient_demographics.age ? stats.patient_demographics.age.average : null;
                    document.getElementById('stat-average-age').textContent = averageAge !== null && typeof averageAge !== 'undefined' ? averageAge : '-';
                    // render jenis kunjungan if present
                    if (stats.jenis) {
                        document.getElementById('jenis-konsultasi').textContent = stats.jenis.konsultasi ?? 0;
                        document.getElementById('jenis-beli').textContent = stats.jenis.beli_produk ?? 0;
                        document.getElementById('jenis-lab').textContent = stats.jenis.lab ?? 0;
                    }

                    var paymentBody = document.getElementById('payment-method-body');
                    if (paymentBody) {
                        paymentBody.innerHTML = '';
                        var methods = Array.isArray(stats.payment_methods) ? stats.payment_methods : [];

                        if (!methods.length) {
                            paymentBody.innerHTML = '<tr><td colspan="2" class="text-muted">Tidak ada data</td></tr>';
                        } else {
                            methods.forEach(function(method) {
                                var row = document.createElement('tr');

                                var th = document.createElement('th');
                                th.textContent = method.name || 'Tanpa Metode';

                                var td = document.createElement('td');
                                td.textContent = typeof method.count !== 'undefined' ? method.count : 0;

                                row.appendChild(th);
                                row.appendChild(td);
                                paymentBody.appendChild(row);
                            });
                        }
                    }

                    document.getElementById('peak-day-label').textContent = stats.peak_day ? stats.peak_day.label : '-';
                    document.getElementById('peak-day-count').textContent = stats.peak_day ? stats.peak_day.count : '-';
                    document.getElementById('peak-week-label').textContent = stats.peak_week ? stats.peak_week.label : '-';
                    document.getElementById('peak-week-count').textContent = stats.peak_week ? stats.peak_week.count : '-';
                    document.getElementById('peak-month-label').textContent = stats.peak_month ? stats.peak_month.label : '-';
                    document.getElementById('peak-month-count').textContent = stats.peak_month ? stats.peak_month.count : '-';

                    var topDoctorsBody = document.getElementById('top-doctors-body');
                    if (topDoctorsBody) {
                        topDoctorsBody.innerHTML = '';
                        var doctors = Array.isArray(stats.top_doctors) ? stats.top_doctors : [];

                        if (!doctors.length) {
                            topDoctorsBody.innerHTML = '<tr><td colspan="2" class="text-muted">Tidak ada data</td></tr>';
                        } else {
                            doctors.forEach(function(doctor) {
                                var row = document.createElement('tr');

                                var th = document.createElement('th');
                                th.textContent = doctor.name || 'Dokter Tidak Diketahui';

                                var td = document.createElement('td');
                                td.textContent = typeof doctor.count !== 'undefined' ? doctor.count : 0;

                                row.appendChild(th);
                                row.appendChild(td);
                                topDoctorsBody.appendChild(row);
                            });
                        }
                    }
                } catch(e) { console.error(e); }
            }

            function loadData(params, options) {
                options = options || {};
                var url = "{{ route('ceo-dashboard.belova_skin.index') }}";
                $.getJSON(url, params)
                    .done(function(resp){
                        if (!options.skipZoomSync && resp.filters) {
                            selectedStart = resp.filters.start_date || selectedStart;
                            selectedEnd = resp.filters.end_date || selectedEnd;
                        }

                        suppressZoomFetch = !!options.skipZoomSync;
                        latestData = resp;
                        renderAllPanels(resp);
                        refreshDoctorTab();
                    })
                    .fail(function(xhr){
                        console.error('Failed to load belova skin data', xhr);
                    });
            }

            function buildRequestParams() {
                return {
                    start_date: selectedStart,
                    end_date: selectedEnd
                };
            }

            $(function(){
                if ($.fn.daterangepicker) {
                    $('#filter-daterange').daterangepicker({
                        startDate: moment(baseStart, 'YYYY-MM-DD'),
                        endDate: moment(baseEnd, 'YYYY-MM-DD'),
                        autoApply: true,
                        locale: { format: 'YYYY-MM-DD', cancelLabel: 'Clear' },
                        opens: 'left',
                        ranges: {
                            'This Month': [moment().startOf('month'), moment().endOf('month')],
                            'Last 3 Months': [moment().subtract(2, 'months').startOf('month'), moment().endOf('month')],
                            'Last 6 Months': [moment().subtract(5, 'months').startOf('month'), moment().endOf('month')],
                            'This Year': [moment().startOf('year'), moment()],
                            'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
                        }
                    });

                    $('#filter-daterange').on('apply.daterangepicker', function(ev, picker){
                        baseStart = picker.startDate.format('YYYY-MM-DD');
                        baseEnd = picker.endDate.format('YYYY-MM-DD');
                        selectedStart = baseStart;
                        selectedEnd = baseEnd;
                        $(this).val(selectedStart + ' - ' + selectedEnd);
                        loadData(buildRequestParams());
                    });

                    $('#filter-daterange').on('cancel.daterangepicker', function(){
                        baseStart = moment().startOf('year').format('YYYY-MM-DD');
                        baseEnd = moment().format('YYYY-MM-DD');
                        selectedStart = baseStart;
                        selectedEnd = baseEnd;
                        $(this).val(selectedStart + ' - ' + selectedEnd);
                        loadData(buildRequestParams());
                    });
                }

                if (initialData) {
                    renderAllPanels(initialData);
                    refreshDoctorTab();
                } else {
                    loadData(buildRequestParams());
                }

                $('#premiereDoctorSelect').on('change', function(){
                    refreshDoctorTab();
                });

                $('#reset-zoom').on('click', function(){
                    selectedStart = baseStart;
                    selectedEnd = baseEnd;
                    $('#filter-daterange').val(formatRangeLabel(selectedStart, selectedEnd));
                    loadData(buildRequestParams());
                });

                $('a[data-toggle="tab"]').on('shown.bs.tab', function(){
                    if (latestData) {
                        renderAllPanels(latestData);
                    }
                    if ($(this).attr('href') === '#premiere-doctor') {
                        refreshDoctorTab();
                    }
                });
            });
        })();
    </script>
@endsection
