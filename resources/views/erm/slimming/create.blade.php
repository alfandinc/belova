@extends('layouts.erm.app')

@section('title', 'ERM | Slimming')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@php
    $pasienBirthDate = optional($visitation->pasien)->tanggal_lahir;
    $pasienAge = $pasienBirthDate ? \Carbon\Carbon::parse($pasienBirthDate)->age : null;
    $pasienGender = strtolower(trim((string) optional($visitation->pasien)->gender));
@endphp

@section('content')
<style>
    .slimming-shell {
        display: grid;
        gap: 1rem;
    }
    .slimming-card {
        border: 1px solid #e5e7eb;
        border-radius: .5rem;
        background: #fff;
        overflow: hidden;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
    }
    .slimming-card-header {
        background: #f8fafc;
        color: #1f2937;
        padding: .85rem 1rem;
        font-size: .92rem;
        font-weight: 700;
        text-transform: uppercase;
        border-bottom: 1px solid #e5e7eb;
    }
    .slimming-card-header--with-action {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }
    .slimming-toolbar {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: 1rem;
    }
    .slimming-toolbar-title {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 700;
        color: #111827;
    }
    .slimming-toolbar-note {
        margin: .25rem 0 0;
        font-size: .9rem;
        color: #6b7280;
    }
    .slimming-analysis-stack {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }
    .slimming-segmental-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }
    .slimming-analysis-table {
        width: 100%;
        border-collapse: collapse;
    }
    .slimming-analysis-table th,
    .slimming-analysis-table td {
        border: 1px solid #e5e7eb;
        padding: .65rem .75rem;
        font-size: .92rem;
    }
    .slimming-analysis-table th {
        background: #f8fafc;
        color: #111827;
        font-weight: 700;
        text-align: center;
    }
    .slimming-analysis-table td:first-child {
        width: 34%;
        font-weight: 600;
    }
    .slimming-analysis-table td:nth-child(2),
    .slimming-analysis-table td:nth-child(3),
    .slimming-analysis-table td:nth-child(4) {
        text-align: center;
    }
    .slimming-analysis-table td.slimming-status-cell {
        font-weight: 700;
        transition: background-color .2s ease, color .2s ease;
    }
    .slimming-analysis-table td.status-ideal,
    .slimming-analysis-table td.status-normal,
    .slimming-analysis-table td.status-baik,
    .slimming-analysis-table td.status-sangat-baik,
    .slimming-analysis-table td.status-atlet {
        background: #dcfce7;
        color: #166534;
    }
    .slimming-analysis-table td.status-borderline,
    .slimming-analysis-table td.status-overweight,
    .slimming-analysis-table td.status-tinggi {
        background: #fef3c7;
        color: #92400e;
    }
    .slimming-analysis-table td.status-obes-1,
    .slimming-analysis-table td.status-obes-2,
    .slimming-analysis-table td.status-obesitas,
    .slimming-analysis-table td.status-sangat-tinggi,
    .slimming-analysis-table td.status-buruk {
        background: #fee2e2;
        color: #991b1b;
    }
    .slimming-analysis-table td.status-rendah,
    .slimming-analysis-table td.status-underweight,
    .slimming-analysis-table td.status-kurang {
        background: #e0f2fe;
        color: #075985;
    }
    .slimming-metric-value {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: .45rem;
    }
    .slimming-trend {
        display: inline-flex;
        align-items: center;
        gap: .2rem;
        padding: .15rem .45rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
    }
    .slimming-trend--up {
        background: #fee2e2;
        color: #b91c1c;
    }
    .slimming-trend--down {
        background: #dcfce7;
        color: #15803d;
    }
    .slimming-trend--same,
    .slimming-trend--none {
        background: #e5e7eb;
        color: #4b5563;
    }
    .slimming-visit-cell {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
    }
    .slimming-current-visit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: .28rem .7rem;
        background: rgba(37, 99, 235, 0.12);
        color: #2563eb;
        font-size: .7rem;
        font-weight: 700;
        line-height: 1;
        text-transform: uppercase;
        letter-spacing: .04em;
        animation: slimming-blink 1.4s ease-in-out infinite;
    }
    .slimming-action-cell {
        white-space: nowrap;
    }
    .slimming-segmental-card {
        position: relative;
        min-height: 29rem;
        padding: 1rem;
        background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
    }
    .slimming-segmental-canvas {
        position: relative;
        min-height: 27rem;
        border-radius: .75rem;
        overflow: hidden;
        background-color: rgba(255, 255, 255, .82);
        background-image: linear-gradient(rgba(255, 255, 255, .18), rgba(255, 255, 255, .18)), url('{{ asset('asesmen/img_slimming.png') }}');
        background-repeat: no-repeat;
        background-position: center;
        background-size: contain;
        border: 1px solid #e5e7eb;
    }
    .slimming-segmental-side {
        position: absolute;
        top: 50%;
        font-size: 2rem;
        font-weight: 800;
        color: rgba(148, 163, 184, .65);
        letter-spacing: .08em;
        transform: translateY(-50%) rotate(-90deg);
        transform-origin: center;
        user-select: none;
    }
    .slimming-segmental-side--left {
        left: -1rem;
    }
    .slimming-segmental-side--right {
        right: -1.5rem;
        transform: translateY(-50%) rotate(90deg);
    }
    .slimming-segmental-marker {
        position: absolute;
        display: grid;
        gap: .2rem;
        max-width: 9rem;
        text-align: center;
    }
    .slimming-segmental-marker strong {
        font-size: .97rem;
        color: #111827;
        font-weight: 700;
    }
    .slimming-segmental-marker span {
        font-size: .86rem;
        color: #1f2937;
    }
    .slimming-segmental-marker--arm {
        top: 4.6rem;
        left: 1rem;
    }
    .slimming-segmental-marker--trunk {
        top: 10rem;
        left: 50%;
        transform: translateX(-50%);
    }
    .slimming-segmental-marker--waist {
        top: 14.2rem;
        left: 50%;
        transform: translateX(-50%);
    }
    .slimming-segmental-marker--leg {
        bottom: 5.4rem;
        left: 50%;
        transform: translateX(-50%);
    }
    .slimming-segmental-circumference {
        position: absolute;
        display: grid;
        gap: .15rem;
        min-width: 7rem;
        padding: .45rem .55rem;
        border-radius: .7rem;
        background: rgba(255, 255, 255, .84);
        border: 1px solid rgba(226, 232, 240, .95);
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.06);
        text-align: center;
    }
    .slimming-segmental-circumference small {
        color: #64748b;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .slimming-segmental-circumference span {
        color: #0f172a;
        font-size: .88rem;
        font-weight: 700;
    }
    .slimming-segmental-circumference--arm-left {
        top: 3.4rem;
        left: 1rem;
    }
    .slimming-segmental-circumference--arm-right {
        top: 3.4rem;
        right: 1rem;
    }
    .slimming-segmental-circumference--leg-left {
        bottom: 2.2rem;
        left: 1rem;
    }
    .slimming-segmental-circumference--leg-right {
        bottom: 2.2rem;
        right: 1rem;
    }
    .slimming-segmental-pill {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        padding: .18rem .55rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        line-height: 1.1;
    }
    .slimming-segmental-pill--normal,
    .slimming-segmental-pill--baik,
    .slimming-segmental-pill--sangat-baik {
        background: #dcfce7;
        color: #166534;
    }
    .slimming-segmental-pill--rendah {
        background: #e0f2fe;
        color: #075985;
    }
    .slimming-segmental-pill--over,
    .slimming-segmental-pill--tinggi,
    .slimming-segmental-pill--borderline {
        background: #fef3c7;
        color: #92400e;
    }
    .slimming-segmental-pill--danger,
    .slimming-segmental-pill--obesitas,
    .slimming-segmental-pill--sangat-tinggi {
        background: #fee2e2;
        color: #991b1b;
    }
    @keyframes slimming-blink {
        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.28);
            opacity: 1;
        }
        50% {
            box-shadow: 0 0 0 .4rem rgba(37, 99, 235, 0);
            opacity: .72;
        }
    }
    .slimming-data-card {
        padding: 1rem;
    }
    .slimming-modal .modal-body {
        padding: 1rem 1.25rem;
    }
    .slimming-modal .form-group label {
        font-weight: 600;
        color: #374151;
        margin-bottom: .35rem;
    }
    .slimming-modal-section + .slimming-modal-section {
        margin-top: 1.25rem;
        padding-top: 1.25rem;
        border-top: 1px solid #e5e7eb;
    }
    @media (max-width: 991.98px) {
        .slimming-analysis-stack {
            grid-template-columns: 1fr;
        }
        .slimming-segmental-grid {
            grid-template-columns: 1fr;
        }
        .slimming-toolbar {
            flex-direction: column;
            align-items: flex-start;
        }
        .slimming-segmental-side {
            display: none;
        }
    }
</style>

<div class="container-fluid slimming-shell">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Slimming</h3>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Rawat Jalan</li>
                            <li class="breadcrumb-item active">Slimming</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('erm.partials.card-identitaspasien')

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-0">
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- <div class="slimming-card">
        <div class="slimming-toolbar">
            <div>
                <h4 class="slimming-toolbar-title">Slimming Analysis</h4>
                <p class="slimming-toolbar-note">Ringkasan data slimming pasien dan histori input.</p>
            </div>
        </div>
    </div> --}}

    <div class="slimming-card">
        <div class="slimming-card-header slimming-card-header--with-action">
            <span>Riwayat Slimming</span>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#slimmingInputModal">
                Input Data
            </button>
        </div>
        <div class="slimming-data-card">
            <div class="table-responsive">
                <table id="slimmingTable" class="table table-bordered table-striped w-100 mb-0">
                    <thead>
                        <tr>
                            <th>Kunjungan</th>
                            <th>Usia</th>
                            <th>TB</th>
                            <th>BB</th>
                            <th>Base Weight</th>
                            <th>Base BMI</th>
                            <th>Base Fat</th>
                            <th>Visceral Fat</th>
                            <th>Lingkar Perut</th>
                            <th>Input At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="slimming-analysis-stack">
        <div class="slimming-card">
            <div class="slimming-card-header">Body Composition Analysis</div>
            <table class="slimming-analysis-table">
                <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Hasil</th>
                        <th>Normal</th>
                        <th>Interpretasi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Berat Badan</td>
                        <td id="summary_base_weight">-</td>
                        <td id="summary_base_weight_normal">-</td>
                        <td id="summary_base_weight_interpretation" class="slimming-status-cell">-</td>
                    </tr>
                    <tr>
                        <td>BMI</td>
                        <td id="summary_base_bmi">-</td>
                        <td id="summary_base_bmi_normal">18,5 - 22,9</td>
                        <td id="summary_base_bmi_interpretation" class="slimming-status-cell">-</td>
                    </tr>
                    <tr>
                        <td>Body Age</td>
                        <td id="summary_base_body_age">-</td>
                        <td id="summary_base_body_age_normal">&le; 29 Tahun</td>
                        <td id="summary_base_body_age_interpretation" class="slimming-status-cell">-</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="slimming-card">
            <div class="slimming-card-header">Body Index Analysis</div>
            <table class="slimming-analysis-table">
                <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Hasil</th>
                        <th>Normal</th>
                        <th>Interpretasi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Whole Body Fat</td>
                        <td id="summary_base_fat">-</td>
                        <td id="summary_base_fat_normal">-</td>
                        <td id="summary_base_fat_interpretation" class="slimming-status-cell">-</td>
                    </tr>
                    <tr>
                        <td>Subcutaneous Body Fat</td>
                        <td id="summary_subcutaneous_whole_body">-</td>
                        <td id="summary_subcutaneous_whole_body_normal">-</td>
                        <td id="summary_subcutaneous_whole_body_interpretation" class="slimming-status-cell">-</td>
                    </tr>
                    <tr>
                        <td>Visceral Fat</td>
                        <td id="summary_base_visceral_fat">-</td>
                        <td id="summary_base_visceral_fat_normal">1 - 9</td>
                        <td id="summary_base_visceral_fat_interpretation" class="slimming-status-cell">-</td>
                    </tr>
                    <tr>
                        <td>Skeletal Whole Body</td>
                        <td id="summary_skeletal_whole_body">-</td>
                        <td id="summary_skeletal_whole_body_normal">-</td>
                        <td id="summary_skeletal_whole_body_interpretation" class="slimming-status-cell">-</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="slimming-segmental-grid">
        <div class="slimming-card">
            <div class="slimming-card-header">Segmental Fat Analysis</div>
            <div class="slimming-segmental-card">
                <div class="slimming-segmental-canvas">
                    <span class="slimming-segmental-side slimming-segmental-side--left">LEFT</span>
                    <span class="slimming-segmental-side slimming-segmental-side--right">RIGHT</span>

                    <div class="slimming-segmental-circumference slimming-segmental-circumference--arm-left">
                        <small>Lengan Kiri</small>
                        <span id="segmental_arm_left_size">-</span>
                    </div>
                    <div class="slimming-segmental-circumference slimming-segmental-circumference--arm-right">
                        <small>Lengan Kanan</small>
                        <span id="segmental_arm_right_size">-</span>
                    </div>
                    <div class="slimming-segmental-circumference slimming-segmental-circumference--leg-left">
                        <small>Paha Kiri</small>
                        <span id="segmental_leg_left_size">-</span>
                    </div>
                    <div class="slimming-segmental-circumference slimming-segmental-circumference--leg-right">
                        <small>Paha Kanan</small>
                        <span id="segmental_leg_right_size">-</span>
                    </div>

                    <div class="slimming-segmental-marker slimming-segmental-marker--arm">
                        <strong id="segmental_fat_arm_value">Arm: -</strong>
                        <span id="segmental_fat_arm_status">-</span>
                    </div>
                    <div class="slimming-segmental-marker slimming-segmental-marker--trunk">
                        <strong id="segmental_fat_trunk_value">Trunk: -</strong>
                        <span id="segmental_fat_trunk_status">-</span>
                    </div>
                    <div class="slimming-segmental-marker slimming-segmental-marker--waist">
                        <strong id="segmental_waist_value">Lingkar Perut: -</strong>
                    </div>
                    <div class="slimming-segmental-marker slimming-segmental-marker--leg">
                        <strong id="segmental_fat_leg_value">Legs: -</strong>
                        <span id="segmental_fat_leg_status">-</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="slimming-card">
            <div class="slimming-card-header">Segmental Skeletal Analysis</div>
            <div class="slimming-segmental-card">
                <div class="slimming-segmental-canvas">
                    <span class="slimming-segmental-side slimming-segmental-side--left">LEFT</span>
                    <span class="slimming-segmental-side slimming-segmental-side--right">RIGHT</span>

                    <div class="slimming-segmental-marker slimming-segmental-marker--arm">
                        <strong id="segmental_skeletal_arm_value">Arm: -</strong>
                        <span id="segmental_skeletal_arm_status">-</span>
                    </div>
                    <div class="slimming-segmental-marker slimming-segmental-marker--trunk">
                        <strong id="segmental_skeletal_trunk_value">Trunk: -</strong>
                        <span id="segmental_skeletal_trunk_status">-</span>
                    </div>
                    <div class="slimming-segmental-marker slimming-segmental-marker--leg">
                        <strong id="segmental_skeletal_leg_value">Legs: -</strong>
                        <span id="segmental_skeletal_leg_status">-</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade slimming-modal" id="slimmingInputModal" tabindex="-1" role="dialog" aria-labelledby="slimmingInputModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('erm.slimming.store') }}">
                @csrf
                <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="slimmingInputModalLabel">Input Data Slimming</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="slimming-modal-section">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="usia">Usia</label>
                                    <input type="number" min="0" class="form-control" id="usia" name="usia" value="{{ old('usia', $pasienAge) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tb">TB</label>
                                    <input type="number" step="0.01" class="form-control" id="tb" name="tb" value="{{ old('tb') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="bb">BB</label>
                                    <input type="number" step="0.01" class="form-control" id="bb" name="bb" value="{{ old('bb') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="slimming-modal-section">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="base_weight">Base Weight</label>
                                    <input type="number" step="0.01" class="form-control" id="base_weight" name="base_weight" value="{{ old('base_weight') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="base_fat">Base Fat</label>
                                    <input type="number" step="0.01" class="form-control" id="base_fat" name="base_fat" value="{{ old('base_fat') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="base_visceral_fat">Base Visceral Fat</label>
                                    <input type="number" step="0.01" class="form-control" id="base_visceral_fat" name="base_visceral_fat" value="{{ old('base_visceral_fat') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-md-0">
                                    <label for="base_kcal">Base Kcal</label>
                                    <input type="number" step="0.01" class="form-control" id="base_kcal" name="base_kcal" value="{{ old('base_kcal') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-md-0">
                                    <label for="base_bmi">Base BMI</label>
                                    <input type="number" step="0.01" class="form-control" id="base_bmi" name="base_bmi" value="{{ old('base_bmi') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-0">
                                    <label for="base_body_age">Base Body Age</label>
                                    <input type="number" min="0" class="form-control" id="base_body_age" name="base_body_age" value="{{ old('base_body_age') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="slimming-modal-section">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="lingkar_perut">Lingkar Perut</label>
                                    <input type="number" step="0.01" class="form-control" id="lingkar_perut" name="lingkar_perut" value="{{ old('lingkar_perut') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="lingkar_lengan_kanan">Lingkar Lengan Kanan</label>
                                    <input type="number" step="0.01" class="form-control" id="lingkar_lengan_kanan" name="lingkar_lengan_kanan" value="{{ old('lingkar_lengan_kanan') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="lingkar_lengan_kiri">Lingkar Lengan Kiri</label>
                                    <input type="number" step="0.01" class="form-control" id="lingkar_lengan_kiri" name="lingkar_lengan_kiri" value="{{ old('lingkar_lengan_kiri') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="lingkar_paha_kanan">Lingkar Paha Kanan</label>
                                    <input type="number" step="0.01" class="form-control" id="lingkar_paha_kanan" name="lingkar_paha_kanan" value="{{ old('lingkar_paha_kanan') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-0">
                                    <label for="lingkar_paha_kiri">Lingkar Paha Kiri</label>
                                    <input type="number" step="0.01" class="form-control" id="lingkar_paha_kiri" name="lingkar_paha_kiri" value="{{ old('lingkar_paha_kiri') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="slimming-modal-section">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="subcutaneous_whole_body">Subcutaneous Whole Body</label>
                                    <input type="number" step="0.01" class="form-control" id="subcutaneous_whole_body" name="subcutaneous_whole_body" value="{{ old('subcutaneous_whole_body') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="subcutaneous_trunk">Subcutaneous Trunk</label>
                                    <input type="number" step="0.01" class="form-control" id="subcutaneous_trunk" name="subcutaneous_trunk" value="{{ old('subcutaneous_trunk') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="subcutaneous_arms">Subcutaneous Arms</label>
                                    <input type="number" step="0.01" class="form-control" id="subcutaneous_arms" name="subcutaneous_arms" value="{{ old('subcutaneous_arms') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="subcutaneous_legs">Subcutaneous Legs</label>
                                    <input type="number" step="0.01" class="form-control" id="subcutaneous_legs" name="subcutaneous_legs" value="{{ old('subcutaneous_legs') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-md-0">
                                    <label for="skeletal_whole_body">Skeletal Whole Body</label>
                                    <input type="number" step="0.01" class="form-control" id="skeletal_whole_body" name="skeletal_whole_body" value="{{ old('skeletal_whole_body') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-md-0">
                                    <label for="skeletal_trunk">Skeletal Trunk</label>
                                    <input type="number" step="0.01" class="form-control" id="skeletal_trunk" name="skeletal_trunk" value="{{ old('skeletal_trunk') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-md-0">
                                    <label for="skeletal_arms">Skeletal Arms</label>
                                    <input type="number" step="0.01" class="form-control" id="skeletal_arms" name="skeletal_arms" value="{{ old('skeletal_arms') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-0">
                                    <label for="skeletal_legs">Skeletal Legs</label>
                                    <input type="number" step="0.01" class="form-control" id="skeletal_legs" name="skeletal_legs" value="{{ old('skeletal_legs') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        const slimmingDataUrl = '{{ route('erm.slimming.data', $visitation->id) }}';
        const currentVisitationId = @json((string) $visitation->id);
        const pasienGender = @json($pasienGender);
        const summaryUnits = {
            base_weight: ' kg',
            base_bmi: '',
            base_body_age: '',
            base_fat: '',
            base_visceral_fat: '',
            skeletal_whole_body: ''
        };
        let recordsCache = [];
        let activeSlimmingIndex = null;

        function formatDecimal(value) {
            if (value === null || value === undefined || value === '' || Number.isNaN(Number(value))) {
                return '-';
            }

            return Number(value).toFixed(2).replace('.', ',');
        }

        function formatRange(min, max, unit) {
            return formatDecimal(min) + ' - ' + formatDecimal(max) + unit;
        }

        function formatSummaryValue(value, unit) {
            if (value === null || value === undefined || value === '') {
                return '-';
            }

            return formatDecimal(value) + unit;
        }

        function setInterpretationCell(selector, label) {
            const cell = $(selector);
            const statusClass = 'status-' + String(label || '-')
                .toLowerCase()
                .replace(/\s+/g, '-')
                .replace(/[^a-z0-9-]/g, '');

            cell.removeClass(function (index, className) {
                return (className.match(/(^|\s)status-[^\s]+/g) || []).join(' ');
            });

            cell.text(label || '-');

            if (label && label !== '-') {
                cell.addClass(statusClass);
            }
        }

        function buildTrendMarkup(currentValue, previousValue) {
            if (currentValue === null || previousValue === null || Number.isNaN(currentValue) || Number.isNaN(previousValue)) {
                return '';
            }

            const difference = currentValue - previousValue;
            if (difference === 0) {
                return '<span class="slimming-trend slimming-trend--same">= 0,00</span>';
            }

            const direction = difference > 0 ? 'up' : 'down';
            const arrow = difference > 0 ? '▲' : '▼';
            return '<span class="slimming-trend slimming-trend--' + direction + '">' + arrow + ' ' + formatDecimal(Math.abs(difference)) + '</span>';
        }

        function isCurrentVisit(record) {
            return record && String(record.visitation_id) === currentVisitationId;
        }

        function resolveActiveIndex(records) {
            if (!Array.isArray(records) || records.length === 0) {
                return null;
            }

            if (activeSlimmingIndex !== null && records[activeSlimmingIndex]) {
                return activeSlimmingIndex;
            }

            const currentVisitIndex = records.findIndex(function (record) {
                return isCurrentVisit(record);
            });

            return currentVisitIndex >= 0 ? currentVisitIndex : 0;
        }

        function renderVisitCell(record) {
            const formattedVisitDate = formatVisitDate(record.visitation_date);
            const currentVisitBadge = isCurrentVisit(record)
                ? '<span class="slimming-current-visit" title="Current visit">Current Visit</span>'
                : '';

            return '<span class="slimming-visit-cell">' +
                '<span>' + formattedVisitDate + '</span>' +
                currentVisitBadge +
            '</span>';
        }

        function formatVisitDate(value) {
            if (!value || value === '-') {
                return '-';
            }

            const parsedDate = new Date(value);
            if (Number.isNaN(parsedDate.getTime())) {
                return value;
            }

            return new Intl.DateTimeFormat('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            }).format(parsedDate);
        }

        function setMetricCell(selector, value, unit, previousValue) {
            const cell = $(selector);
            const hasValue = value !== null && value !== undefined && value !== '' && !Number.isNaN(Number(value));

            if (!hasValue) {
                cell.html('-');
                return;
            }

            const currentNumber = Number(value);
            const previousNumber = previousValue !== null && previousValue !== undefined && previousValue !== '' && !Number.isNaN(Number(previousValue))
                ? Number(previousValue)
                : null;

            cell.html(
                '<span class="slimming-metric-value">' +
                    '<span>' + formatDecimal(currentNumber) + unit + '</span>' +
                    buildTrendMarkup(currentNumber, previousNumber) +
                '</span>'
            );
        }

        function formatSegmentalValue(value, unit, fallbackLabel) {
            if (value === null || value === undefined || value === '' || Number.isNaN(Number(value))) {
                return fallbackLabel || '-';
            }

            return formatDecimal(value) + unit;
        }

        function buildSegmentalStatus(label) {
            if (!label || label === '-') {
                return '-';
            }

            const statusClass = 'slimming-segmental-pill--' + String(label)
                .toLowerCase()
                .replace(/\s+/g, '-')
                .replace(/[^a-z0-9-]/g, '');

            return '<span class="slimming-segmental-pill ' + statusClass + '">' + label + '</span>';
        }

        function setSegmentalMetric(valueSelector, statusSelector, label, value, unit, interpretation) {
            $(valueSelector).text(label + ': ' + formatSegmentalValue(value, unit, '-'));
            if (statusSelector) {
                $(statusSelector).html(buildSegmentalStatus(interpretation));
            }
        }

        function setSegmentalSingle(selector, label, value, unit) {
            $(selector).text(label + ': ' + formatSegmentalValue(value, unit, '-'));
        }

        function genderProfile() {
            if (['l', 'male', 'man', 'pria', 'laki-laki', 'lakilaki'].includes(String(pasienGender).toLowerCase())) {
                return {
                    bodyFatNormal: '10-20%',
                    subcutaneousNormal: '10-22%',
                    skeletalNormal: '33-39%',
                    bodyFatInterpretation: function (value) {
                        if (value === null) {
                            return '-';
                        }
                        if (value < 8) {
                            return 'Rendah';
                        }
                        if (value <= 12) {
                            return 'Atlet';
                        }
                        if (value <= 20) {
                            return 'Normal';
                        }
                        if (value <= 25) {
                            return 'Tinggi';
                        }
                        return 'Obesitas';
                    },
                    subcutaneousInterpretation: function (value) {
                        if (value === null) {
                            return '-';
                        }
                        if (value < 10) {
                            return 'Rendah';
                        }
                        if (value <= 22) {
                            return 'Normal';
                        }
                        if (value <= 27) {
                            return 'Tinggi';
                        }
                        return 'Obesitas';
                    },
                    skeletalInterpretation: function (value) {
                        if (value === null) {
                            return '-';
                        }
                        if (value < 33) {
                            return 'Rendah';
                        }
                        if (value <= 39) {
                            return 'Normal';
                        }
                        return 'Sangat baik';
                    },
                    segmentalFatThresholds: {
                        arm: { normalMin: 12, overMin: 21 },
                        trunk: { normalMin: 12, overMin: 23 },
                        leg: { normalMin: 15, overMin: 26 }
                    },
                    segmentalSkeletalThresholds: {
                        arm: { normalMin: 34, veryGoodMin: 43 },
                        trunk: { normalMin: 30, veryGoodMin: 39 },
                        leg: { normalMin: 38, veryGoodMin: 47 }
                    }
                };
            }

            return {
                bodyFatNormal: '21-33%',
                subcutaneousNormal: '20-32%',
                skeletalNormal: '24-30%',
                bodyFatInterpretation: function (value) {
                    if (value === null) {
                        return '-';
                    }
                    if (value < 18) {
                        return 'Rendah';
                    }
                    if (value <= 20) {
                        return 'Atlet';
                    }
                    if (value <= 33) {
                        return 'Normal';
                    }
                    if (value <= 39) {
                        return 'Tinggi';
                    }
                    return 'Obesitas';
                },
                subcutaneousInterpretation: function (value) {
                    if (value === null) {
                        return '-';
                    }
                    if (value < 20) {
                        return 'Rendah';
                    }
                    if (value <= 32) {
                        return 'Normal';
                    }
                    if (value <= 38) {
                        return 'Tinggi';
                    }
                    return 'Obesitas';
                },
                skeletalInterpretation: function (value) {
                    if (value === null) {
                        return '-';
                    }
                    if (value < 24) {
                        return 'Rendah';
                    }
                    if (value <= 30) {
                        return 'Normal';
                    }
                    return 'Baik';
                    },
                    segmentalFatThresholds: {
                        arm: { normalMin: 22, overMin: 33 },
                        trunk: { normalMin: 20, overMin: 31 },
                        leg: { normalMin: 25, overMin: 36 }
                    },
                    segmentalSkeletalThresholds: {
                        arm: { normalMin: 26, veryGoodMin: 35 },
                        trunk: { normalMin: 22, veryGoodMin: 31 },
                        leg: { normalMin: 30, veryGoodMin: 39 }
                }
            };
        }


        function segmentalFatInterpretation(value, thresholds) {
            if (value === null || !thresholds) {
                return '-';
            }
            if (value < thresholds.normalMin) {
                return 'Rendah';
            }
            if (value < thresholds.overMin) {
                return 'Normal';
            }
            return 'Over';
        }

        function segmentalSkeletalInterpretation(value, thresholds) {
            if (value === null || !thresholds) {
                return '-';
            }
            if (value < thresholds.normalMin) {
                return 'Rendah';
            }
            if (value < thresholds.veryGoodMin) {
                return 'Normal';
            }
            return 'Sangat Baik';
        }

        function updateSegmentalAnalysis(record, profile) {
            record = record || {};

            const subcutaneousArm = record.subcutaneous_arms !== undefined && record.subcutaneous_arms !== null ? parseFloat(record.subcutaneous_arms) : null;
            const subcutaneousTrunk = record.subcutaneous_trunk !== undefined && record.subcutaneous_trunk !== null ? parseFloat(record.subcutaneous_trunk) : null;
            const subcutaneousLeg = record.subcutaneous_legs !== undefined && record.subcutaneous_legs !== null ? parseFloat(record.subcutaneous_legs) : null;
            const skeletalArm = record.skeletal_arms !== undefined && record.skeletal_arms !== null ? parseFloat(record.skeletal_arms) : null;
            const skeletalTrunk = record.skeletal_trunk !== undefined && record.skeletal_trunk !== null ? parseFloat(record.skeletal_trunk) : null;
            const skeletalLeg = record.skeletal_legs !== undefined && record.skeletal_legs !== null ? parseFloat(record.skeletal_legs) : null;

            setSegmentalMetric('#segmental_fat_arm_value', '#segmental_fat_arm_status', 'Arm', subcutaneousArm, '%', segmentalFatInterpretation(subcutaneousArm, profile.segmentalFatThresholds.arm));
            setSegmentalMetric('#segmental_fat_trunk_value', '#segmental_fat_trunk_status', 'Trunk', subcutaneousTrunk, '%', segmentalFatInterpretation(subcutaneousTrunk, profile.segmentalFatThresholds.trunk));
            setSegmentalMetric('#segmental_fat_leg_value', '#segmental_fat_leg_status', 'Legs', subcutaneousLeg, '%', segmentalFatInterpretation(subcutaneousLeg, profile.segmentalFatThresholds.leg));
            setSegmentalSingle('#segmental_waist_value', 'Lingkar Perut', record.lingkar_perut, ' cm');
            $('#segmental_arm_left_size').text(formatSegmentalValue(record.lingkar_lengan_kiri, ' cm', '-'));
            $('#segmental_arm_right_size').text(formatSegmentalValue(record.lingkar_lengan_kanan, ' cm', '-'));
            $('#segmental_leg_left_size').text(formatSegmentalValue(record.lingkar_paha_kiri, ' cm', '-'));
            $('#segmental_leg_right_size').text(formatSegmentalValue(record.lingkar_paha_kanan, ' cm', '-'));

            setSegmentalMetric('#segmental_skeletal_arm_value', '#segmental_skeletal_arm_status', 'Arm', skeletalArm, '%', segmentalSkeletalInterpretation(skeletalArm, profile.segmentalSkeletalThresholds.arm));
            setSegmentalMetric('#segmental_skeletal_trunk_value', '#segmental_skeletal_trunk_status', 'Trunk', skeletalTrunk, '%', segmentalSkeletalInterpretation(skeletalTrunk, profile.segmentalSkeletalThresholds.trunk));
            setSegmentalMetric('#segmental_skeletal_leg_value', '#segmental_skeletal_leg_status', 'Legs', skeletalLeg, '%', segmentalSkeletalInterpretation(skeletalLeg, profile.segmentalSkeletalThresholds.leg));
        }
        function bmiInterpretation(value) {
            if (value === null) {
                return '-';
            }
            if (value >= 30) {
                return 'Obes 2';
            }
            if (value >= 25) {
                return 'Obes 1';
            }
            if (value >= 23) {
                return 'Overweight';
            }
            if (value >= 18.5) {
                return 'Normal';
            }
            return 'Underweight';
        }

        function bodyAgeInterpretation(value) {
            if (value === null) {
                return '-';
            }
            return value <= 29 ? 'Normal' : 'Buruk';
        }

        function visceralFatInterpretation(value) {
            if (value === null) {
                return '-';
            }
            if (value <= 9) {
                return 'Ideal';
            }
            if (value <= 12) {
                return 'Borderline';
            }
            if (value <= 15) {
                return 'Tinggi';
            }
            return 'Sangat Tinggi';
        }

        function weightRange(heightCm) {
            if (heightCm === null || Number.isNaN(heightCm) || heightCm <= 0) {
                return '-';
            }

            const heightMeter = heightCm / 100;
            const min = 18.5 * heightMeter * heightMeter;
            const max = 22.9 * heightMeter * heightMeter;

            return formatRange(min, max, ' kg');
        }

        function weightInterpretation(weight, heightCm) {
            if (weight === null || heightCm === null || Number.isNaN(heightCm) || heightCm <= 0) {
                return '-';
            }

            const heightMeter = heightCm / 100;
            const min = 18.5 * heightMeter * heightMeter;
            const max = 22.9 * heightMeter * heightMeter;

            if (weight < min) {
                return 'Kurang';
            }
            if (weight <= max) {
                return 'Normal';
            }
            return 'Overweight';
        }

        function updateSummary(records, selectedIndex) {
            records = Array.isArray(records) ? records : [];
            const profile = genderProfile();
            const activeIndex = selectedIndex !== null && records[selectedIndex] ? selectedIndex : resolveActiveIndex(records);
            const selectedRecord = activeIndex !== null ? records[activeIndex] : null;
            const previousRecord = activeIndex !== null && records[activeIndex + 1] ? records[activeIndex + 1] : null;
            const heightCm = selectedRecord && selectedRecord.tb !== undefined && selectedRecord.tb !== null ? parseFloat(selectedRecord.tb) : null;
            const baseWeight = selectedRecord && selectedRecord.base_weight !== undefined && selectedRecord.base_weight !== null ? parseFloat(selectedRecord.base_weight) : null;
            const baseBmi = selectedRecord && selectedRecord.base_bmi !== undefined && selectedRecord.base_bmi !== null ? parseFloat(selectedRecord.base_bmi) : null;
            const baseBodyAge = selectedRecord && selectedRecord.base_body_age !== undefined && selectedRecord.base_body_age !== null ? parseFloat(selectedRecord.base_body_age) : null;
            const baseFat = selectedRecord && selectedRecord.base_fat !== undefined && selectedRecord.base_fat !== null ? parseFloat(selectedRecord.base_fat) : null;
            const baseVisceralFat = selectedRecord && selectedRecord.base_visceral_fat !== undefined && selectedRecord.base_visceral_fat !== null ? parseFloat(selectedRecord.base_visceral_fat) : null;
            const subcutaneousWholeBody = selectedRecord && selectedRecord.subcutaneous_whole_body !== undefined && selectedRecord.subcutaneous_whole_body !== null ? parseFloat(selectedRecord.subcutaneous_whole_body) : null;
            const skeletalWholeBody = selectedRecord && selectedRecord.skeletal_whole_body !== undefined && selectedRecord.skeletal_whole_body !== null ? parseFloat(selectedRecord.skeletal_whole_body) : null;

            activeSlimmingIndex = activeIndex;

            setMetricCell('#summary_base_weight', selectedRecord ? selectedRecord.base_weight : null, summaryUnits.base_weight, previousRecord ? previousRecord.base_weight : null);
            setMetricCell('#summary_base_bmi', selectedRecord ? selectedRecord.base_bmi : null, summaryUnits.base_bmi, previousRecord ? previousRecord.base_bmi : null);
            setMetricCell('#summary_base_body_age', selectedRecord ? selectedRecord.base_body_age : null, summaryUnits.base_body_age, previousRecord ? previousRecord.base_body_age : null);
            setMetricCell('#summary_base_fat', selectedRecord ? selectedRecord.base_fat : null, '%', previousRecord ? previousRecord.base_fat : null);
            setMetricCell('#summary_base_visceral_fat', selectedRecord ? selectedRecord.base_visceral_fat : null, '', previousRecord ? previousRecord.base_visceral_fat : null);
            setMetricCell('#summary_subcutaneous_whole_body', selectedRecord ? selectedRecord.subcutaneous_whole_body : null, '%', previousRecord ? previousRecord.subcutaneous_whole_body : null);
            setMetricCell('#summary_skeletal_whole_body', selectedRecord ? selectedRecord.skeletal_whole_body : null, '%', previousRecord ? previousRecord.skeletal_whole_body : null);
            $('#summary_base_weight_normal').text(weightRange(heightCm));
            setInterpretationCell('#summary_base_weight_interpretation', weightInterpretation(baseWeight, heightCm));
            setInterpretationCell('#summary_base_bmi_interpretation', bmiInterpretation(baseBmi));
            setInterpretationCell('#summary_base_body_age_interpretation', bodyAgeInterpretation(baseBodyAge));
            $('#summary_base_fat_normal').text(profile.bodyFatNormal);
            setInterpretationCell('#summary_base_fat_interpretation', profile.bodyFatInterpretation(baseFat));
            $('#summary_subcutaneous_whole_body_normal').text(profile.subcutaneousNormal);
            setInterpretationCell('#summary_subcutaneous_whole_body_interpretation', profile.subcutaneousInterpretation(subcutaneousWholeBody));
            setInterpretationCell('#summary_base_visceral_fat_interpretation', visceralFatInterpretation(baseVisceralFat));
            $('#summary_skeletal_whole_body_normal').text(profile.skeletalNormal);
            setInterpretationCell('#summary_skeletal_whole_body_interpretation', profile.skeletalInterpretation(skeletalWholeBody));
            updateSegmentalAnalysis(selectedRecord, profile);
        }

        const slimmingTable = $('#slimmingTable').DataTable({
            processing: true,
            ajax: {
                url: slimmingDataUrl,
                dataSrc: function (json) {
                    recordsCache = json.records || [];
                    updateSummary(recordsCache, activeSlimmingIndex);
                    return recordsCache;
                }
            },
            columns: [
                {
                    data: null,
                    defaultContent: '-',
                    render: function (data, type, row) {
                        if (type !== 'display') {
                            return formatVisitDate(row.visitation_date);
                        }

                        return renderVisitCell(row);
                    }
                },
                { data: 'usia', defaultContent: '-' },
                { data: 'tb', defaultContent: '-' },
                { data: 'bb', defaultContent: '-' },
                { data: 'base_weight', defaultContent: '-' },
                { data: 'base_bmi', defaultContent: '-' },
                { data: 'base_fat', defaultContent: '-' },
                { data: 'base_visceral_fat', defaultContent: '-' },
                { data: 'lingkar_perut', defaultContent: '-' },
                { data: 'created_at', defaultContent: '-' },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'slimming-action-cell',
                    render: function (data, type, row, meta) {
                        if (type !== 'display') {
                            return 'Lihat';
                        }

                        const buttonClass = meta.row === activeSlimmingIndex ? 'btn-primary' : 'btn-outline-primary';
                        return '<button type="button" class="btn btn-sm ' + buttonClass + ' js-view-slimming" data-index="' + meta.row + '">Lihat</button>';
                    }
                }
            ],
            order: [[9, 'desc']]
        });

        $('#slimmingTable').on('click', '.js-view-slimming', function () {
            const selectedIndex = Number($(this).data('index'));

            if (Number.isNaN(selectedIndex) || !recordsCache[selectedIndex]) {
                return;
            }

            updateSummary(recordsCache, selectedIndex);
            slimmingTable.rows().invalidate().draw(false);
        });

        @if($errors->any())
            $('#slimmingInputModal').modal('show');
        @endif

        $('#tb, #bb, #base_weight').on('input', function () {
            const heightCm = parseFloat($('#tb').val());
            const baseWeightInput = parseFloat($('#base_weight').val());
            const bodyWeightInput = parseFloat($('#bb').val());
            const usedWeight = !Number.isNaN(baseWeightInput) ? baseWeightInput : bodyWeightInput;

            if (Number.isNaN(heightCm) || Number.isNaN(usedWeight) || heightCm <= 0 || usedWeight <= 0) {
                return;
            }

            const heightMeters = heightCm / 100;
            if (heightMeters <= 0) {
                return;
            }

            $('#base_bmi').val((usedWeight / (heightMeters * heightMeters)).toFixed(2));
        });
    });
</script>
@endsection
