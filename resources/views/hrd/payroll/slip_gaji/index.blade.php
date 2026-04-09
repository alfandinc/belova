@extends('layouts.hrd.app')
@section('title', 'Slip Payroll')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection
@section('content')
@php
    $isCeoSlipView = auth()->check()
        && method_exists(auth()->user(), 'hasAnyRole')
        && auth()->user()->hasAnyRole(['Ceo', 'CEO']);
@endphp
<div class="container-fluid">
    @unless($isCeoSlipView)
        <link rel="stylesheet" href="{{ asset('dastone/vendor/datatable/FixedColumns-4.3.0/css/fixedColumns.bootstrap4.min.css') }}">
    @endunless
    <style>
        /* Theme sets body overflow-x:hidden!important; override it on this page to prevent cropping */
        body {
            overflow-x: auto !important;
        }
        .page-wrapper,
        .page-content {
            max-width: 100%;
            overflow-x: auto;
        }

        /* Prevent flex layouts from clipping wide children */
        .container-fluid,
        .container-fluid .row,
        .container-fluid .col-12,
        .container-fluid .card,
        .container-fluid .card-body {
            min-width: 0;
        }

        /* Ensure this page can show horizontal scrolling inside the table area */
        .slipgaji-scroll-x {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
        }
        .slipgaji-scroll-x .dataTables_wrapper {
            width: 100%;
        }
        .slipgaji-scroll-x .dataTables_scrollBody {
            overflow-x: auto !important;
        }

        /* Drag/swipe to scroll horizontally inside the DataTable */
        .slipgaji-scroll-x .dataTables_scrollBody {
            cursor: grab;
            touch-action: pan-y;
        }
        .slipgaji-scroll-x .dataTables_scrollBody.dt-dragging {
            cursor: grabbing;
        }
        .slipgaji-scroll-x .dataTables_scrollBody.dt-dragging * {
            user-select: none;
        }

        /* Let dropdown menu from pinned cell display outside cell */
        .dataTables_scrollBody td,
        .dtfc-fixed-right,
        .dtfc-fixed-left {
            overflow: visible;
        }

        /* Ensure dropdown appears above sticky cells (avoid being covered/"overlapped") */
        .dataTables_scrollBody td,
        .dtfc-fixed-right,
        .dtfc-fixed-left {
            z-index: 1;
        }
        .dataTables_scrollBody td.dt-dropdown-open,
        .dtfc-fixed-right.dt-dropdown-open,
        .dtfc-fixed-left.dt-dropdown-open {
            z-index: 3000 !important;
        }
        .dataTables_scrollBody td .btn-group.show,
        .dataTables_scrollBody td .dropdown.show,
        .dtfc-fixed-right .btn-group.show,
        .dtfc-fixed-right .dropdown.show,
        .dtfc-fixed-left .btn-group.show,
        .dtfc-fixed-left .dropdown.show {
            position: relative;
            z-index: 2000;
        }
        .dataTables_scrollBody td .dropdown-menu,
        .dtfc-fixed-right .dropdown-menu,
        .dtfc-fixed-left .dropdown-menu {
            z-index: 2001;
        }

        /* Make money inputs consistent width (fit ~9 digits) */
        #slipGajiTable input.slip-money {
            width: 120px;
            min-width: 120px;
            box-sizing: border-box;
        }

        /* Give Nama column enough room for long names */
        #slipGajiTable th.dt-col-nama,
        #slipGajiTable td.dt-col-nama {
            min-width: 240px;
        }

    </style>
    <div class="row mb-2">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="mb-0 font-weight-bold">Slip Gaji Karyawan</h3>
                <div class="text-muted small">
                    @if($isCeoSlipView)
                        Tinjau total gaji bulan lalu versus bulan ini dan approve slip yang sudah diajukan HRD.
                    @else
                        Kelola slip gaji karyawan per bulan: edit komponen gaji, potongan, benefit, lembur, dan total secara langsung.
                    @endif
                </div>
            </div>
            <div class="d-flex align-items-center mt-2">
                <div class="mr-3 text-right">
                    <div class="text-muted small mb-0">Total Beban Gaji</div>
                    <div class="font-weight-bold" id="slipTotalBeban">Rp 0,00</div>
                </div>
                <input type="month" id="filterBulan" class="form-control mr-2" style="width:180px;" value="{{ date('Y-m') }}">
                @unless($isCeoSlipView)
                    <button class="btn btn-success mr-2" id="btnBuatSlipGaji">Buat Slip Gaji</button>
                @endunless
            </div>
        </div>
    </div>

    <!-- Hidden holder: will be moved next to the DataTables Search box (left side) -->
    <div id="slipGajiToolbarHolder" class="d-none">
        <select id="filterDivision" class="form-control form-control-sm mr-2" style="width:180px;">
            <option value="">All Division</option>
            @foreach(($divisions ?? []) as $div)
                <option value="{{ $div->id }}">{{ $div->name }}</option>
            @endforeach
        </select>
        @unless($isCeoSlipView)
            <select id="filterStatus" class="form-control form-control-sm mr-2" style="width:140px;">
                <option value="">All Status</option>
                <option value="draft">Draft</option>
                <option value="submitted">Submitted</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="paid">Paid</option>
            </select>
        @endunless
        @unless($isCeoSlipView)
            <button class="btn btn-info btn-sm mr-2" id="btnSyncSlipGaji">Sync</button>
            <select id="bulkStatus" class="form-control form-control-sm mr-2" style="width:170px;">
                <option value="">Bulk Status...</option>
                <option value="submitted">Submitted</option>
                <option value="paid">Paid</option>
            </select>
            <button class="btn btn-primary btn-sm" id="btnBulkStatus" disabled>Apply</button>
        @endunless
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive slipgaji-scroll-x" style="overflow-x:auto;">
                        <table id="slipGajiTable" class="table table-bordered table-striped" style="width:100%">
                            <thead>
                                @if($isCeoSlipView)
                                <tr>
                                    <th style="display:none;">ID</th>
                                    <th>Nama Karyawan</th>
                                    <th>Total Gaji Bulan Lalu</th>
                                    <th>Total Gaji Bulan Ini</th>
                                    <th>Aksi</th>
                                </tr>
                                @else
                                <tr>
                                    <th style="display:none;">ID</th>
                                    <th>Nama</th>
                                    <th>Hari Masuk</th>
                                    <th>Gaji Pokok</th>
                                    <th>Tunjangan Jabatan</th>
                                    <th>Tunjangan Masa Kerja</th>
                                    <th>Uang Makan</th>
                                    <th>Uang KPI</th>
                                    <th>Jam Lembur</th>
                                    <th>Uang Lembur</th>
                                    <th>Jasa Medis</th>
                                    <th>Pendapatan Tambahan</th>
                                    <th>Total Pendapatan</th>
                                    <th>Total Potongan</th>
                                    <th>Total Benefit</th>
                                    <th class="font-weight-bold">Total Gaji</th>
                                    <th>Aksi</th>
                                    <th style="width:36px;" class="text-center">
                                        <input type="checkbox" id="slipChkAll">
                                    </th>
                                </tr>
                                @endif
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@include('hrd.payroll.slip_gaji._modal_detail')
@if($isCeoSlipView)
    <div class="modal fade" id="modalSlipGajiPreview" tabindex="-1" role="dialog" aria-labelledby="modalSlipGajiPreviewLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSlipGajiPreviewLabel">Preview Slip Gaji</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="slipGajiPreviewFrame" src="about:blank" style="width:100%; height:75vh; border:0;"></iframe>
                </div>
            </div>
        </div>
    </div>
@endif
@unless($isCeoSlipView)
    @include('hrd.payroll.slip_gaji.buat._modal')
    @include('hrd.payroll.slip_gaji._modal_potongan')
    @include('hrd.payroll.slip_gaji._modal_benefit')
@endunless
@endsection
@section('scripts')
@if($isCeoSlipView)
    @include('hrd.payroll.slip_gaji._scripts_ceo')
@else
    <script src="{{ asset('dastone/vendor/datatable/FixedColumns-4.3.0/js/dataTables.fixedColumns.min.js') }}"></script>
    @include('hrd.payroll.slip_gaji._scripts')
    @include('hrd.payroll.slip_gaji.buat._scripts')
@endif
@endsection
