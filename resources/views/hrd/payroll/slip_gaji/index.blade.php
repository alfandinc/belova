@extends('layouts.hrd.app')
@section('title', 'Slip Payroll')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection
@section('content')
<div class="container-fluid">
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

        /* Keep right columns pinned during horizontal scroll */
        .dataTables_scrollBody td.dt-sticky-right,
        .dataTables_scrollHead th.dt-sticky-right,
        .dataTables_scrollHeadInner th.dt-sticky-right {
            position: sticky !important;
            z-index: 2;
            background-color: inherit;
        }
        .dataTables_scrollHead th.dt-sticky-right,
        .dataTables_scrollHeadInner th.dt-sticky-right {
            z-index: 5;
        }

        /* Right offsets (checkbox is the last column) */
        .dataTables_scrollBody td.dt-sticky-right-0,
        .dataTables_scrollHead th.dt-sticky-right-0,
        .dataTables_scrollHeadInner th.dt-sticky-right-0 {
            right: 0;
        }
        .dataTables_scrollBody td.dt-sticky-right-36,
        .dataTables_scrollHead th.dt-sticky-right-36,
        .dataTables_scrollHeadInner th.dt-sticky-right-36 {
            right: 36px;
        }

        /* DataTables scroll head clone sometimes introduces a 1px seam; compensate on header only */
        .dataTables_scrollHead th.dt-sticky-right-36,
        .dataTables_scrollHeadInner th.dt-sticky-right-36 {
            right: 35px;
        }

        /* Make the rightmost checkbox column exactly 36px (no padding) so it aligns with Total Gaji offset */
        .dataTables_scrollBody td.dt-sticky-right-0,
        .dataTables_scrollHead th.dt-sticky-right-0,
        .dataTables_scrollHeadInner th.dt-sticky-right-0 {
            width: 36px !important;
            min-width: 36px !important;
            max-width: 36px !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .dataTables_scrollBody td.dt-sticky-right-0 {
            text-align: center;
        }
        .dataTables_scrollBody td.dt-sticky-right-0 input[type="checkbox"] {
            margin: 0;
        }

        /* Let dropdown menu from pinned cell display outside cell */
        .dataTables_scrollBody td.dt-sticky-right {
            overflow: visible;
        }

        /* Ensure dropdown appears above sticky cells (avoid being covered/"overlapped") */
        .dataTables_scrollBody td.dt-sticky-right {
            z-index: 1;
        }
        .dataTables_scrollBody td.dt-sticky-right.dt-dropdown-open {
            z-index: 3000 !important;
        }
        .dataTables_scrollBody td.dt-sticky-right .btn-group.show,
        .dataTables_scrollBody td.dt-sticky-right .dropdown.show {
            position: relative;
            z-index: 2000;
        }
        .dataTables_scrollBody td.dt-sticky-right .dropdown-menu {
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

        /* Keep left column (Nama) pinned during horizontal scroll */
        .dataTables_scrollBody td.dt-sticky-left,
        .dataTables_scrollHead th.dt-sticky-left,
        .dataTables_scrollHeadInner th.dt-sticky-left {
            position: sticky !important;
            left: 0;
            z-index: 3;
            background-color: inherit;
        }
        .dataTables_scrollHead th.dt-sticky-left,
        .dataTables_scrollHeadInner th.dt-sticky-left {
            z-index: 6;
        }
    </style>
    <div class="row mb-2">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="mb-0 font-weight-bold">Slip Gaji Karyawan</h3>
                <div class="text-muted small">Kelola slip gaji karyawan per bulan: edit komponen gaji, potongan, benefit, lembur, dan total secara langsung.</div>
            </div>
            <div class="d-flex align-items-center mt-2">
                <div class="mr-3 text-right">
                    <div class="text-muted small mb-0">Total Beban Gaji</div>
                    <div class="font-weight-bold" id="slipTotalBeban">Rp 0,00</div>
                </div>
                <input type="month" id="filterBulan" class="form-control mr-2" style="width:180px;" value="{{ date('Y-m') }}">
                <button class="btn btn-success mr-2" id="btnBuatSlipGaji">Buat Slip Gaji</button>
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
        <select id="filterStatus" class="form-control form-control-sm mr-2" style="width:140px;">
            <option value="">All Status</option>
            <option value="draft">Draft</option>
            <option value="diapprove">Diapprove</option>
            <option value="paid">Paid</option>
        </select>
        <button class="btn btn-info btn-sm mr-2" id="btnSyncSlipGaji">Sync</button>
        <select id="bulkStatus" class="form-control form-control-sm mr-2" style="width:170px;">
            <option value="">Bulk Status...</option>
            <option value="diapprove">Diapprove</option>
            <option value="paid">Paid</option>
        </select>
        <button class="btn btn-primary btn-sm" id="btnBulkStatus" disabled>Apply</button>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive slipgaji-scroll-x" style="overflow-x:auto;">
                        <table id="slipGajiTable" class="table table-bordered table-striped" style="width:100%">
                            <thead>
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
                                    <th style="width:36px;" class="text-center">
                                        <input type="checkbox" id="slipChkAll">
                                    </th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@include('hrd.payroll.slip_gaji._modal_detail')
@include('hrd.payroll.slip_gaji.buat._modal')
@include('hrd.payroll.slip_gaji._modal_potongan')
@include('hrd.payroll.slip_gaji._modal_benefit')
@endsection
@section('scripts')
@include('hrd.payroll.slip_gaji._scripts')
@include('hrd.payroll.slip_gaji.buat._scripts')
@endsection
