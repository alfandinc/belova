@extends('layouts.erm.app')

@section('title', 'ERM | Slimming')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@php
    $rulerConfigs = [
        'muscle_fat_weight' => [
            'min' => 10,
            'max' => 50,
            'under_max' => 24.3,
            'normal_max' => 30.3,
            'ticks' => [10, 15, 24.3, 25, 30.3, 35, 40, 45, 50],
            'label_offsets' => [],
        ],
        'muscle_fat_muscle' => [
            'min' => 10,
            'max' => 50,
            'under_max' => 24.3,
            'normal_max' => 30.3,
            'segment_widths' => [25, 25, 50],
            'ticks' => [10, 15, 24.3, 25, 30.3, 35, 40, 45, 50],
            'label_offsets' => [],
        ],
        'muscle_fat_body_fat_mass' => [
            'min' => 10,
            'max' => 52,
            'under_max' => 21,
            'normal_max' => 32.9,
            'segment_widths' => [25, 25, 50],
            'ticks' => [10, 15, 21, 25, 32.9, 37, 42, 47, 52],
            'label_offsets' => [],
        ],
        'obesity_bmi' => [
            'min' => 10,
            'max' => 45,
            'under_max' => 18.5,
            'normal_max' => 25,
            'segment_widths' => [25, 25, 50],
            'ticks' => [10, 15, 18.5, 21, 25, 30, 35, 40, 45],
            'label_offsets' => [],
        ],
    ];

    $circumferenceFields = [
        ['name' => 'lingkar_perut', 'label' => 'Lingkar Perut', 'icon' => 'fas fa-ruler-horizontal'],
        ['name' => 'lingkar_lengan_kanan', 'label' => 'Lingkar Lengan Kanan', 'icon' => 'fas fa-hand-paper'],
        ['name' => 'lingkar_lengan_kiri', 'label' => 'Lingkar Lengan Kiri', 'icon' => 'fas fa-hand-paper'],
    ];

    $subcutaneousFields = [
        ['name' => 'subcutaneous_whole_body', 'label' => 'Subcutan Whole Body'],
        ['name' => 'subcutaneous_trunk', 'label' => 'Subcutan Trunk'],
        ['name' => 'subcutaneous_arms', 'label' => 'Subcutan Arms'],
        ['name' => 'subcutaneous_legs', 'label' => 'Subcutan Legs'],
    ];

    $skeletalFields = [
        ['name' => 'skeletal_whole_body', 'label' => 'Skeletal Whole Body'],
        ['name' => 'skeletal_trunk', 'label' => 'Skeletal Trunk'],
        ['name' => 'skeletal_arms', 'label' => 'Skeletal Arms'],
        ['name' => 'skeletal_legs', 'label' => 'Skeletal Legs'],
    ];

    $oldObesityEval = (string) old('obesity_eval');
    preg_match('/BMI:([^;]+)/i', $oldObesityEval, $oldBmiEvalMatch);
    preg_match('/PBF:([^;]+)/i', $oldObesityEval, $oldPbfEvalMatch);
    $oldBmiEvalStatus = strtolower(trim($oldBmiEvalMatch[1] ?? ''));
    $oldPbfEvalStatus = strtolower(trim($oldPbfEvalMatch[1] ?? ''));
    $pasienBirthDate = optional($visitation->pasien)->tanggal_lahir;
    $pasienGender = strtolower(trim((string) optional($visitation->pasien)->gender));
    $pasienAge = $pasienBirthDate ? \Carbon\Carbon::parse($pasienBirthDate)->age : null;
@endphp

@section('content')
<style>
    .slimming-sheet {
        border: 1px solid #d8dbe2;
        border-radius: 14px;
        padding: 1.25rem;
        background: #fff;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    }
    .slimming-sheet-title {
        display: flex;
        align-items: center;
        gap: .55rem;
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: .02em;
        margin-bottom: .75rem;
        color: #1f2937;
    }
    .slimming-sheet-title i {
        color: #2563eb;
        font-size: .95rem;
    }
    .slimming-grid-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }
    .slimming-grid-table th,
    .slimming-grid-table td {
        border: 1px solid #2f3542;
        padding: .45rem .55rem;
        vertical-align: middle;
        font-size: .92rem;
    }
    .slimming-grid-table th {
        background: #f8fafc;
        text-align: center;
        font-weight: 700;
    }
    .slimming-grid-table td.label-cell {
        width: 180px;
        font-weight: 600;
        background: #fcfcfd;
        white-space: nowrap;
    }
    .slimming-grid-table td.input-cell {
        min-width: 180px;
        background: #fff;
    }
    .slimming-grid-table td.range-cell {
        width: 140px;
        background: #fff;
    }
    .slimming-ruler-wrapper {
        position: relative;
        min-width: 560px;
        padding: .05rem .1rem 1.5rem;
    }
    .slimming-ruler-sections {
        display: flex;
        align-items: stretch;
        font-size: .8rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: .28rem;
        text-align: center;
        line-height: 1;
    }
    .slimming-ruler-sections span {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        border: 1px solid #2f3542;
        border-bottom: 0;
        min-height: 30px;
        background: #fff;
        transition: background-color .2s ease, color .2s ease;
    }
    .slimming-ruler-sections span + span {
        border-left: 0;
    }
    .slimming-ruler-sections span.is-active {
        color: #111827;
    }
    .slimming-ruler-sections span[data-category="under"].is-active {
        background: #fef3c7;
        color: #92400e;
    }
    .slimming-ruler-sections span[data-category="normal"].is-active {
        background: #dcfce7;
        color: #166534;
    }
    .slimming-ruler-sections span[data-category="over"].is-active {
        background: #fee2e2;
        color: #991b1b;
    }
    .slimming-ruler-track {
        position: relative;
        height: 38px;
        border-top: 2px solid #2f3542;
        margin: 0;
    }
    .slimming-ruler-track::before,
    .slimming-ruler-track::after {
        content: '';
        position: absolute;
        top: -2px;
        width: 2px;
        height: 9px;
        background: #2f3542;
    }
    .slimming-ruler-track::before {
        left: 0;
    }
    .slimming-ruler-track::after {
        right: 0;
    }
    .slimming-ruler-tick {
        position: absolute;
        top: -2px;
        transform: translateX(-50%);
        width: 0;
        pointer-events: none;
    }
    .slimming-ruler-tick::before {
        content: '';
        display: block;
        width: 2px;
        height: 8px;
        background: #2f3542;
        margin: 0 auto;
    }
    .slimming-ruler-tick span {
        position: absolute;
        top: 12px;
        left: 50%;
        transform: translateX(calc(-50% + var(--tick-label-shift, 0px)));
        font-size: .6rem;
        color: #111827;
        white-space: nowrap;
        line-height: 1;
    }
    .slimming-ruler-indicator {
        position: absolute;
        top: -18px;
        transform: translateX(-50%);
        transition: left .2s ease;
        pointer-events: none;
        z-index: 3;
    }
    .slimming-ruler-indicator::before {
        content: '';
        display: block;
        width: 0;
        height: 0;
        border-left: 10px solid transparent;
        border-right: 10px solid transparent;
        border-top: 14px solid #e03131;
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.35));
    }
    .slimming-ruler-indicator::after {
        content: '';
        position: absolute;
        left: 50%;
        top: 12px;
        transform: translateX(-50%);
        width: 2px;
        height: 14px;
        background: #e03131;
        box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.65);
    }
    .slimming-ruler-plain {
        min-width: 460px;
    }
    @media (max-width: 991.98px) {
        .slimming-ruler-wrapper,
        .slimming-ruler-plain {
            min-width: 320px;
        }
        .slimming-grid-table td.input-cell {
            min-width: 150px;
        }
    }
    .slimming-sheet .form-control {
        min-height: 38px;
    }
    .slimming-inline-input {
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .slimming-inline-input .form-control {
        border: 0;
        border-bottom: 1px solid #6b7280;
        border-radius: 0;
        box-shadow: none;
        padding-left: 0;
        padding-right: 0;
        background: transparent;
    }
    .slimming-inline-input .unit-label {
        white-space: nowrap;
        font-weight: 600;
        color: #374151;
    }
    .slimming-check-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .slimming-check-row:last-child {
        margin-bottom: 0;
    }
    .slimming-check-row .row-label {
        min-width: 56px;
        font-weight: 700;
        color: #111827;
    }
    .slimming-check-options {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .slimming-check-options label {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        margin-bottom: 0;
        font-weight: 500;
    }
    .slimming-eval-panel {
        padding: 0;
    }
    .slimming-eval-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }
    .slimming-eval-card {
        padding: 0;
    }
    .slimming-eval-card-inner {
        display: grid;
        grid-template-columns: minmax(170px, 210px) minmax(320px, 1fr);
        gap: 1rem;
        align-items: start;
    }
    .slimming-eval-card label {
        display: block;
        margin-bottom: .45rem;
        font-size: .8rem;
        font-weight: 700;
        letter-spacing: .02em;
        text-transform: uppercase;
        color: #334155;
    }
    .slimming-eval-card .form-control {
        min-height: 44px;
        border-radius: 10px;
        font-size: 1.05rem;
        font-weight: 700;
        background: #f8fafc;
    }
    .slimming-eval-status-title {
        margin-bottom: .7rem;
        font-size: .9rem;
        font-weight: 700;
        color: #0f172a;
    }
    .slimming-eval-options {
        display: flex;
        flex-wrap: nowrap;
        gap: .6rem;
    }
    .slimming-eval-options label {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        margin-bottom: 0;
        padding: .45rem .8rem;
        border: 1px solid #cbd5e1;
        border-radius: 999px;
        background: #fff;
        font-weight: 600;
        color: #334155;
        cursor: pointer;
        transition: border-color .2s ease, background-color .2s ease, color .2s ease;
    }
    .slimming-eval-options label:has(input:checked) {
        border-color: #2563eb;
        background: #eff6ff;
        color: #1d4ed8;
    }
    .slimming-eval-options input[type="radio"] {
        margin: 0;
    }
    @media (max-width: 991.98px) {
        .slimming-eval-grid {
            grid-template-columns: 1fr;
        }
        .slimming-eval-card-inner {
            grid-template-columns: 1fr;
        }
    }
    .slimming-stack-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        height: 100%;
        padding: 1rem;
        background: #fff;
    }
    .slimming-stack-card--auto {
        height: auto;
    }
    .slimming-figure-panel {
        position: relative;
        aspect-ratio: 1618 / 1004;
        min-height: 640px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background-color: #fff;
        background-image: url('{{ asset('img/asesmen/slimming.png') }}');
        background-repeat: no-repeat;
        background-position: center top;
        background-size: 74% auto;
        overflow: hidden;
    }
    .slimming-figure-panel--single {
        aspect-ratio: 387 / 503;
        min-height: 280px;
        background-size: contain;
        background-position: center top;
    }
    .slimming-figure-field {
        position: absolute;
        width: 108px;
        transform: translateX(-50%);
    }
    .slimming-figure-field label {
        display: block;
        margin-bottom: .35rem;
        font-size: .875rem;
        font-weight: 600;
        text-align: left;
        color: #111827;
        text-shadow: none;
    }
    .slimming-figure-field .form-control {
        height: 38px;
        border-radius: .25rem;
        text-align: left;
        background: #fff;
        border-color: #ced4da;
        box-shadow: none;
    }
    .slimming-figure-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .slimming-figure-section-title {
        font-size: .95rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: .75rem;
    }
    .slimming-figure-split {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
    }
    .slimming-figure-field--whole {
        top: 16%;
        left: 55%;
    }
    .slimming-figure-field--trunk {
        top: 42%;
        left: 50%;
    }
    .slimming-figure-field--arms {
        top: 50%;
        left: 22%;
    }
    .slimming-figure-field--legs {
        top: 80%;
        left: 70%;
    }
    @media (max-width: 991.98px) {
        .slimming-grid-table td.label-cell {
            width: auto;
        }
        .slimming-figure-panel {
            min-height: auto;
            padding: 1rem;
            background-size: contain;
            background-position: center top;
        }
        .slimming-figure-grid {
            grid-template-columns: 1fr;
        }
        .slimming-figure-split {
            grid-template-columns: 1fr;
        }
        .slimming-figure-field {
            position: static;
            width: 100%;
            transform: none;
            margin-bottom: 1rem;
        }
        .slimming-figure-field:last-child {
            margin-bottom: 0;
        }
    }
</style>

<div class="container-fluid">
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
        <div class="alert alert-danger">
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-12 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 text-uppercase font-weight-bold">Input Slimming</h5>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#riwayatSlimmingModal">
                            Riwayat Slimming
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('erm.slimming.store') }}">
                        @csrf
                        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
                        <input type="hidden" name="obesity_eval" id="obesity_eval" value="{{ old('obesity_eval') }}">

                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="form-group mb-0">
                                    <label for="riwayat_tindakan_id">Riwayat Tindakan ID</label>
                                    <select name="riwayat_tindakan_id" id="riwayat_tindakan_id" class="form-control">
                                        <option value="">Pilih riwayat tindakan</option>
                                        @foreach($riwayatTindakanOptions as $riwayatTindakan)
                                            <option value="{{ $riwayatTindakan->id }}" {{ old('riwayat_tindakan_id') == $riwayatTindakan->id ? 'selected' : '' }}>
                                                {{ $riwayatTindakan->id }} - {{ optional($riwayatTindakan->tindakan)->nama ?? 'Tanpa tindakan' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="slimming-sheet mb-4">
                            <div class="row mb-4">
                                <div class="col-lg-6">
                                    <div class="slimming-stack-card h-100">
                                        <div class="slimming-sheet-title"><i class="fas fa-weight"></i><span>Body Measurement</span></div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-md-0">
                                                    <label for="tb"><i class="fas fa-ruler-vertical text-muted mr-2"></i>Height</label>
                                                    <div class="slimming-inline-input">
                                                        <input type="number" step="0.01" class="form-control" id="tb" name="tb" value="{{ old('tb') }}">
                                                        <span class="unit-label">cm</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mt-3 mt-md-0">
                                                <div class="form-group mb-0">
                                                    <label for="bb"><i class="fas fa-balance-scale text-muted mr-2"></i>Weight</label>
                                                    <div class="slimming-inline-input">
                                                        <input type="number" step="0.01" class="form-control" id="bb" name="bb" value="{{ old('bb') }}">
                                                        <span class="unit-label">kg</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mt-4 mt-lg-0">
                                    <div class="slimming-stack-card h-100">
                                        <div class="slimming-sheet-title"><i class="fas fa-bullseye"></i><span>Weight Control</span></div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-md-0">
                                                    <label for="target_weight"><i class="fas fa-crosshairs text-muted mr-2"></i>Target Weight</label>
                                                    <div class="slimming-inline-input">
                                                        <input type="number" step="0.01" class="form-control" id="target_weight" name="target_weight" value="{{ old('target_weight') }}">
                                                        <span class="unit-label">kg</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mt-3 mt-md-0">
                                                <div class="form-group mb-0">
                                                    <label for="weight_control"><i class="fas fa-sliders-h text-muted mr-2"></i>Weight Control</label>
                                                    <div class="slimming-inline-input">
                                                        <input type="number" step="0.01" class="form-control" id="weight_control" name="weight_control" value="{{ old('weight_control') }}">
                                                        <span class="unit-label">kg</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="slimming-sheet-title">Muscle-Fat Analysis</div>
                                    <div class="table-responsive mb-4">
                                        <table class="slimming-grid-table">
                                            <tbody>
                                                <tr>
                                                    <td class="label-cell">Muscle (%)</td>
                                                    <td class="input-cell">
                                                        <input type="number" step="0.01" class="form-control" id="muscle_fat_muscle" name="muscle_fat_muscle" value="{{ old('muscle_fat_muscle') }}">
                                                    </td>
                                                    <td class="range-cell" colspan="3">
                                                        <div class="slimming-ruler-wrapper" data-ruler-for="muscle_fat_muscle" data-min="{{ $rulerConfigs['muscle_fat_muscle']['min'] }}" data-max="{{ $rulerConfigs['muscle_fat_muscle']['max'] }}" data-under-max="{{ $rulerConfigs['muscle_fat_muscle']['under_max'] }}" data-normal-max="{{ $rulerConfigs['muscle_fat_muscle']['normal_max'] }}" data-segment-widths="{{ implode(',', $rulerConfigs['muscle_fat_muscle']['segment_widths']) }}" data-ticks="{{ implode(',', $rulerConfigs['muscle_fat_muscle']['ticks']) }}">
                                                            <div class="slimming-ruler-sections">
                                                                <span data-category="under" style="width: {{ $rulerConfigs['muscle_fat_muscle']['segment_widths'][0] }}%;">Under</span>
                                                                <span data-category="normal" style="width: {{ $rulerConfigs['muscle_fat_muscle']['segment_widths'][1] }}%;">Normal</span>
                                                                <span data-category="over" style="width: {{ $rulerConfigs['muscle_fat_muscle']['segment_widths'][2] }}%;">Over</span>
                                                            </div>
                                                            <div class="slimming-ruler-track">
                                                                @php
                                                                    $tickCount = max(count($rulerConfigs['muscle_fat_muscle']['ticks']) - 1, 1);
                                                                @endphp
                                                                @foreach($rulerConfigs['muscle_fat_muscle']['ticks'] as $tickIndex => $tick)
                                                                    @php
                                                                        $left = ($tickIndex / $tickCount) * 100;
                                                                        $labelShift = $rulerConfigs['muscle_fat_muscle']['label_offsets'][(string) $tick] ?? 0;
                                                                    @endphp
                                                                    <div class="slimming-ruler-tick" style="left: {{ $left }}%; --tick-label-shift: {{ $labelShift }}px;">
                                                                        <span>{{ $tick }}</span>
                                                                    </div>
                                                                @endforeach
                                                                <div class="slimming-ruler-indicator" data-indicator-for="muscle_fat_muscle" style="left: 0%;"></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="label-cell">Body Fat Mass (%)</td>
                                                    <td class="input-cell">
                                                        <input type="number" step="0.01" class="form-control" id="muscle_fat_body_fat_mass" name="muscle_fat_body_fat_mass" value="{{ old('muscle_fat_body_fat_mass') }}">
                                                    </td>
                                                    <td class="range-cell" colspan="3">
                                                        <div class="slimming-ruler-wrapper" data-ruler-for="muscle_fat_body_fat_mass" data-min="{{ $rulerConfigs['muscle_fat_body_fat_mass']['min'] }}" data-max="{{ $rulerConfigs['muscle_fat_body_fat_mass']['max'] }}" data-under-max="{{ $rulerConfigs['muscle_fat_body_fat_mass']['under_max'] }}" data-normal-max="{{ $rulerConfigs['muscle_fat_body_fat_mass']['normal_max'] }}" data-segment-widths="{{ implode(',', $rulerConfigs['muscle_fat_body_fat_mass']['segment_widths']) }}" data-ticks="{{ implode(',', $rulerConfigs['muscle_fat_body_fat_mass']['ticks']) }}">
                                                            <div class="slimming-ruler-sections">
                                                                <span data-category="under" style="width: {{ $rulerConfigs['muscle_fat_body_fat_mass']['segment_widths'][0] }}%;">Under</span>
                                                                <span data-category="normal" style="width: {{ $rulerConfigs['muscle_fat_body_fat_mass']['segment_widths'][1] }}%;">Normal</span>
                                                                <span data-category="over" style="width: {{ $rulerConfigs['muscle_fat_body_fat_mass']['segment_widths'][2] }}%;">Over</span>
                                                            </div>
                                                            <div class="slimming-ruler-track">
                                                                @php
                                                                    $tickCount = max(count($rulerConfigs['muscle_fat_body_fat_mass']['ticks']) - 1, 1);
                                                                @endphp
                                                                @foreach($rulerConfigs['muscle_fat_body_fat_mass']['ticks'] as $tickIndex => $tick)
                                                                    @php
                                                                        $left = ($tickIndex / $tickCount) * 100;
                                                                        $labelShift = $rulerConfigs['muscle_fat_body_fat_mass']['label_offsets'][(string) $tick] ?? 0;
                                                                    @endphp
                                                                    <div class="slimming-ruler-tick" style="left: {{ $left }}%; --tick-label-shift: {{ $labelShift }}px;">
                                                                        <span>{{ $tick }}</span>
                                                                    </div>
                                                                @endforeach
                                                                <div class="slimming-ruler-indicator" data-indicator-for="muscle_fat_body_fat_mass" style="left: 0%;"></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="slimming-sheet-title">Obesity Analysis</div>
                                    <div class="table-responsive">
                                        <table class="slimming-grid-table">
                                            <tbody>
                                                <tr>
                                                    <td class="label-cell">BMI (kg/m²)</td>
                                                    <td class="input-cell">
                                                        <input type="number" step="0.01" class="form-control" id="obesity_bmi" name="obesity_bmi" value="{{ old('obesity_bmi') }}" readonly>
                                                    </td>
                                                    <td class="range-cell" colspan="3">
                                                        <div class="slimming-ruler-wrapper" data-ruler-for="obesity_bmi" data-min="{{ $rulerConfigs['obesity_bmi']['min'] }}" data-max="{{ $rulerConfigs['obesity_bmi']['max'] }}" data-under-max="{{ $rulerConfigs['obesity_bmi']['under_max'] }}" data-normal-max="{{ $rulerConfigs['obesity_bmi']['normal_max'] }}" data-segment-widths="{{ implode(',', $rulerConfigs['obesity_bmi']['segment_widths']) }}" data-ticks="{{ implode(',', $rulerConfigs['obesity_bmi']['ticks']) }}">
                                                            <div class="slimming-ruler-sections">
                                                                <span data-category="under" style="width: {{ $rulerConfigs['obesity_bmi']['segment_widths'][0] }}%;">Under</span>
                                                                <span data-category="normal" style="width: {{ $rulerConfigs['obesity_bmi']['segment_widths'][1] }}%;">Normal</span>
                                                                <span data-category="over" style="width: {{ $rulerConfigs['obesity_bmi']['segment_widths'][2] }}%;">Over</span>
                                                            </div>
                                                            <div class="slimming-ruler-track">
                                                                @php
                                                                    $tickCount = max(count($rulerConfigs['obesity_bmi']['ticks']) - 1, 1);
                                                                @endphp
                                                                @foreach($rulerConfigs['obesity_bmi']['ticks'] as $tickIndex => $tick)
                                                                    @php
                                                                        $left = ($tickIndex / $tickCount) * 100;
                                                                        $labelShift = $rulerConfigs['obesity_bmi']['label_offsets'][(string) $tick] ?? 0;
                                                                    @endphp
                                                                    <div class="slimming-ruler-tick" style="left: {{ $left }}%; --tick-label-shift: {{ $labelShift }}px;">
                                                                        <span>{{ $tick }}</span>
                                                                    </div>
                                                                @endforeach
                                                                <div class="slimming-ruler-indicator" data-indicator-for="obesity_bmi" style="left: 0%;"></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="label-cell">Analysis (%)</td>
                                                    <td class="input-cell">
                                                        <input type="text" class="form-control" id="obesity_analysis" name="obesity_analysis" value="{{ old('obesity_analysis') }}">
                                                    </td>
                                                    <td class="range-cell" colspan="3">
                                                        <div class="slimming-ruler-plain"></div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <div class="slimming-stack-card">
                                        <div class="slimming-sheet-title">Obesity Evaluation</div>
                                        <div class="slimming-eval-panel">
                                            <div class="slimming-eval-grid">
                                                <div class="slimming-eval-card">
                                                    <div class="slimming-eval-card-inner">
                                                        <div>
                                                            <label for="obesity_eval_bmi">BMI Score</label>
                                                            <input type="number" step="0.01" class="form-control" id="obesity_eval_bmi" name="obesity_eval_bmi" value="{{ old('obesity_eval_bmi') }}" readonly>
                                                        </div>
                                                        <div>
                                                            <div class="slimming-eval-status-title">BMI Classification</div>
                                                            <div class="slimming-eval-options">
                                                                <label><input type="radio" name="obesity_eval_bmi_status" value="normal" {{ $oldBmiEvalStatus === 'normal' ? 'checked' : '' }}> Normal</label>
                                                                <label><input type="radio" name="obesity_eval_bmi_status" value="under" {{ $oldBmiEvalStatus === 'under' ? 'checked' : '' }}> Under</label>
                                                                <label><input type="radio" name="obesity_eval_bmi_status" value="over" {{ $oldBmiEvalStatus === 'over' ? 'checked' : '' }}> Over</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="slimming-eval-card">
                                                    <div class="slimming-eval-card-inner">
                                                        <div>
                                                            <label for="pbf">PBF</label>
                                                            <input type="number" step="0.01" class="form-control" id="pbf" name="pbf" value="{{ old('pbf') }}" readonly>
                                                        </div>
                                                        <div>
                                                            <div class="slimming-eval-status-title">PBF Classification</div>
                                                            <div class="slimming-eval-options">
                                                                <label><input type="radio" name="obesity_eval_pbf_status" value="normal" {{ $oldPbfEvalStatus === 'normal' ? 'checked' : '' }}> Normal</label>
                                                                <label><input type="radio" name="obesity_eval_pbf_status" value="under" {{ $oldPbfEvalStatus === 'under' ? 'checked' : '' }}> Under</label>
                                                                <label><input type="radio" name="obesity_eval_pbf_status" value="over" {{ $oldPbfEvalStatus === 'over' ? 'checked' : '' }}> Over</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-xl-9 col-lg-8 mb-3">
                                <div class="slimming-stack-card">
                                    <div class="slimming-sheet-title">Subcutaneous Fat & Skeletal Muscle</div>
                                    <div class="slimming-figure-grid">
                                        <div>
                                            <div class="slimming-figure-section-title">Subcutaneous Fat</div>
                                        </div>
                                        <div>
                                            <div class="slimming-figure-section-title">Skeletal Muscle</div>
                                        </div>
                                    </div>

                                    <div class="slimming-figure-split">
                                        <div class="slimming-figure-panel slimming-figure-panel--single">
                                            <div class="slimming-figure-field slimming-figure-field--whole">
                                                <label for="subcutaneous_whole_body">Subcutan Whole Body</label>
                                                <input type="number" step="0.01" class="form-control" id="subcutaneous_whole_body" name="subcutaneous_whole_body" value="{{ old('subcutaneous_whole_body') }}">
                                            </div>
                                            <div class="slimming-figure-field slimming-figure-field--trunk">
                                                <label for="subcutaneous_trunk">Subcutan Trunk</label>
                                                <input type="number" step="0.01" class="form-control" id="subcutaneous_trunk" name="subcutaneous_trunk" value="{{ old('subcutaneous_trunk') }}">
                                            </div>
                                            <div class="slimming-figure-field slimming-figure-field--arms">
                                                <label for="subcutaneous_arms">Subcutan Arms</label>
                                                <input type="number" step="0.01" class="form-control" id="subcutaneous_arms" name="subcutaneous_arms" value="{{ old('subcutaneous_arms') }}">
                                            </div>
                                            <div class="slimming-figure-field slimming-figure-field--legs">
                                                <label for="subcutaneous_legs">Subcutan Legs</label>
                                                <input type="number" step="0.01" class="form-control" id="subcutaneous_legs" name="subcutaneous_legs" value="{{ old('subcutaneous_legs') }}">
                                            </div>
                                        </div>
                                        <div class="slimming-figure-panel slimming-figure-panel--single">
                                            <div class="slimming-figure-field slimming-figure-field--whole">
                                                <label for="skeletal_whole_body">Skeletal Whole Body</label>
                                                <input type="number" step="0.01" class="form-control" id="skeletal_whole_body" name="skeletal_whole_body" value="{{ old('skeletal_whole_body') }}">
                                            </div>
                                            <div class="slimming-figure-field slimming-figure-field--trunk">
                                                <label for="skeletal_trunk">Skeletal Trunk</label>
                                                <input type="number" step="0.01" class="form-control" id="skeletal_trunk" name="skeletal_trunk" value="{{ old('skeletal_trunk') }}">
                                            </div>
                                            <div class="slimming-figure-field slimming-figure-field--arms">
                                                <label for="skeletal_arms">Skeletal Arms</label>
                                                <input type="number" step="0.01" class="form-control" id="skeletal_arms" name="skeletal_arms" value="{{ old('skeletal_arms') }}">
                                            </div>
                                            <div class="slimming-figure-field slimming-figure-field--legs">
                                                <label for="skeletal_legs">Skeletal Legs</label>
                                                <input type="number" step="0.01" class="form-control" id="skeletal_legs" name="skeletal_legs" value="{{ old('skeletal_legs') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-lg-4 mb-3">
                                <div class="slimming-stack-card slimming-stack-card--auto mb-3">
                                    <div class="slimming-sheet-title"><i class="fas fa-flask"></i><span>Research Parameters</span></div>
                                    <div class="form-group">
                                        <label for="research_basal_metabolic_rate"><i class="fas fa-bolt text-muted mr-2"></i>Research Basal Metabolic Rate</label>
                                        <input type="number" step="0.01" class="form-control" id="research_basal_metabolic_rate" name="research_basal_metabolic_rate" value="{{ old('research_basal_metabolic_rate') }}">
                                    </div>
                                    <div class="form-group mb-0">
                                        <label for="visceral_fat_level"><i class="fas fa-heartbeat text-muted mr-2"></i>Research Visceral Fat Level</label>
                                        <input type="number" step="0.01" class="form-control" id="visceral_fat_level" name="visceral_fat_level" value="{{ old('visceral_fat_level') }}">
                                    </div>
                                </div>

                                <div class="slimming-stack-card slimming-stack-card--auto">
                                    <div class="slimming-sheet-title"><i class="fas fa-ruler-combined"></i><span>Body Circumference</span></div>
                                    @foreach($circumferenceFields as $field)
                                        <div class="form-group {{ $loop->last ? 'mb-0' : '' }}">
                                            <label for="{{ $field['name'] }}"><i class="{{ $field['icon'] }} text-muted mr-2"></i>{{ $field['label'] }}</label>
                                            <input type="number" step="0.01" class="form-control" id="{{ $field['name'] }}" name="{{ $field['name'] }}" value="{{ old($field['name']) }}">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="submit" class="btn btn-primary">Simpan Slimming</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="riwayatSlimmingModal" tabindex="-1" aria-labelledby="riwayatSlimmingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="riwayatSlimmingModalLabel">Riwayat Slimming Pasien</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-2">
                <div class="table-responsive">
                    <table id="slimmingTable" class="table table-bordered table-sm w-100">
                        <thead></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        let slimmingHistoryLoaded = false;
        const pasienAge = {{ $pasienAge ?? 'null' }};
        const pasienGender = @json($pasienGender);
        const slimmingHistoryUrl = '{{ route('erm.slimming.data', $visitation->id) }}';

        function updateRuler(inputId) {
            const inputEl = document.getElementById(inputId);
            const rulerEl = document.querySelector('[data-ruler-for="' + inputId + '"]');
            const indicatorEl = document.querySelector('[data-indicator-for="' + inputId + '"]');

            if (!inputEl || !rulerEl || !indicatorEl) {
                return;
            }

            const min = parseFloat(rulerEl.dataset.min || '0');
            const max = parseFloat(rulerEl.dataset.max || '100');
            const rawValue = parseFloat(inputEl.value);
            const sectionEls = rulerEl.querySelectorAll('.slimming-ruler-sections [data-category]');
            const underMax = parseFloat(rulerEl.dataset.underMax || '0');
            const normalMax = parseFloat(rulerEl.dataset.normalMax || '0');
            const segmentWidths = (rulerEl.dataset.segmentWidths || '25,25,50').split(',').map(function (value) {
                return parseFloat(value);
            });
            const ticks = (rulerEl.dataset.ticks || '').split(',').map(function (value) {
                return parseFloat(value);
            }).filter(function (value) {
                return !Number.isNaN(value);
            });

            if (Number.isNaN(rawValue)) {
                indicatorEl.style.left = '0%';
                sectionEls.forEach(function (sectionEl) {
                    sectionEl.classList.remove('is-active');
                });
                return;
            }

            const clampedValue = Math.min(Math.max(rawValue, min), max);
            let percentage = 0;

            if (ticks.length >= 2) {
                const lastIndex = ticks.length - 1;

                if (clampedValue <= ticks[0]) {
                    percentage = 0;
                } else if (clampedValue >= ticks[lastIndex]) {
                    percentage = 100;
                } else {
                        const startTick = ticks[index];
                        const endTick = ticks[index + 1];

                        { data: 'visitation_date', name: 'visitation_date', defaultContent: '-' },
                    }
                    const normalRange = Math.max(normalMax - underMax, 0.0001);
                    percentage = segmentWidths[0] + (((clampedValue - underMax) / normalRange) * segmentWidths[1]);
            });
        }

        ['muscle_fat_weight', 'muscle_fat_muscle', 'muscle_fat_body_fat_mass', 'obesity_bmi'].forEach(function (inputId) {
            const inputEl = document.getElementById(inputId);
            if (!inputEl) {
                return;
            }

            inputEl.addEventListener('input', function () {
                updateRuler(inputId);
            });

            updateRuler(inputId);
        });

        function syncObesityEval() {
            const bmiStatus = $('input[name="obesity_eval_bmi_status"]:checked').val() || '';
            const pbfStatus = $('input[name="obesity_eval_pbf_status"]:checked').val() || '';
            const parts = [];

            if (bmiStatus) {
                parts.push('BMI:' + bmiStatus);
            }

            if (pbfStatus) {
                parts.push('PBF:' + pbfStatus);
            }

            $('#obesity_eval').val(parts.join(';'));
        }

        function classifyEvalStatus(value, underMax, normalMax) {
            if (Number.isNaN(value)) {
                return '';
            }

            if (value < underMax) {
                return 'under';
            }

            if (value <= normalMax) {
                return 'normal';
            }

            return 'over';
        }

        function genderFactor(gender) {
            if (!gender) {
                return null;
            }

            const normalizedGender = String(gender).trim().toLowerCase();

            if (['l', 'male', 'man', 'pria', 'laki-laki', 'lakilaki'].includes(normalizedGender)) {
                return 1;
            }

            if (['p', 'f', 'female', 'woman', 'wanita', 'perempuan'].includes(normalizedGender)) {
                return 0;
            }

            return null;
        }

        function calculateBmi(heightCm, weightKg) {
            if (Number.isNaN(heightCm) || Number.isNaN(weightKg) || heightCm <= 0 || weightKg <= 0) {
                return null;
            }

            const heightMeters = heightCm / 100;

            if (heightMeters <= 0) {
                return null;
            }

            return Number((weightKg / (heightMeters * heightMeters)).toFixed(2));
        }

        function calculatePbf(bmiValue, age, gender) {
            const factor = genderFactor(gender);

            if (Number.isNaN(bmiValue) || bmiValue === null || age === null || factor === null) {
                return null;
            }

            return Number(((1.20 * bmiValue) + (0.23 * age) - (10.8 * factor) - 5.4).toFixed(2));
        }

        function pbfThresholds(gender) {
            const factor = genderFactor(gender);

            if (factor === 1) {
                return { under: 10, normal: 20 };
            }

            if (factor === 0) {
                return { under: 18, normal: 28 };
            }

            return { under: 18, normal: 28 };
        }

        function setEvalRadio(groupName, status) {
            const radioEls = $('input[name="' + groupName + '"]');

            if (!status) {
                radioEls.prop('checked', false);
                return;
            }

            radioEls.prop('checked', false);
            $('input[name="' + groupName + '"][value="' + status + '"]').prop('checked', true);
        }

        function syncObesityEvalAutoFill() {
            const heightCm = parseFloat($('#tb').val());
            const weightKg = parseFloat($('#bb').val());
            const bmiValue = calculateBmi(heightCm, weightKg);
            const pbfValue = calculatePbf(bmiValue, pasienAge, pasienGender);
            const currentPbfThresholds = pbfThresholds(pasienGender);

            if (bmiValue !== null) {
                $('#obesity_bmi').val(bmiValue);
            } else {
                $('#obesity_bmi').val('');
            }

            if (pbfValue !== null) {
                $('#pbf').val(pbfValue);
            } else {
                $('#pbf').val('');
            }

            if (bmiValue !== null) {
                $('#obesity_eval_bmi').val(bmiValue);
            } else {
                $('#obesity_eval_bmi').val('');
            }

            setEvalRadio('obesity_eval_bmi_status', classifyEvalStatus(bmiValue, 18.5, 25));
            setEvalRadio('obesity_eval_pbf_status', classifyEvalStatus(pbfValue, currentPbfThresholds.under, currentPbfThresholds.normal));
            updateRuler('obesity_bmi');
            syncObesityEval();
        }

        $('input[name="obesity_eval_bmi_status"], input[name="obesity_eval_pbf_status"]').on('change', syncObesityEval);
        $('#tb, #bb').on('input', syncObesityEvalAutoFill);
        syncObesityEvalAutoFill();
        syncObesityEval();

        function formatHistoryValue(value) {
            return value === null || value === undefined || value === '' ? '-' : value;
        }

        function renderSlimmingHistoryComparison(records) {
            const safeRecords = Array.isArray(records) ? records : [];
            const metrics = [
                { key: 'weight', label: 'Weight' },
                { key: 'muscle_mass', label: 'Muscle Mass' },
                { key: 'body_fat', label: 'Body Fat' }
            ];
            const headerHtml = '<tr><th>Metric</th>' + safeRecords.map(function (record) {
                return '<th>' + formatHistoryValue(record.visitation_date) + '</th>';
            }).join('') + '</tr>';

            const bodyHtml = metrics.map(function (metric) {
                return '<tr><th>' + metric.label + '</th>' + safeRecords.map(function (record) {
                    return '<td>' + formatHistoryValue(record[metric.key]) + '</td>';
                }).join('') + '</tr>';
            }).join('');

            if (!safeRecords.length) {
                $('#slimmingTable thead').html('<tr><th>Metric</th></tr>');
                $('#slimmingTable tbody').html('<tr><td>No slimming history found.</td></tr>');
                return;
            }

            $('#slimmingTable thead').html(headerHtml);
            $('#slimmingTable tbody').html(bodyHtml);
        }

        $('#riwayatSlimmingModal').on('shown.bs.modal', function () {
            if (slimmingHistoryLoaded) {
                return;
            }

            $('#slimmingTable thead').html('<tr><th>Loading...</th></tr>');
            $('#slimmingTable tbody').empty();

            $.getJSON(slimmingHistoryUrl)
                .done(function (response) {
                    renderSlimmingHistoryComparison(response.records || []);
                    slimmingHistoryLoaded = true;
                })
                .fail(function () {
                    $('#slimmingTable thead').html('<tr><th>Metric</th></tr>');
                    $('#slimmingTable tbody').html('<tr><td>Failed to load slimming history.</td></tr>');
                });
        });
    });
</script>
@endsection
