@extends('layouts.hrd.app')
@section('title', 'Riwayat Slip Gaji')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection
@section('content')
@php
    $verified = $verified ?? false;
    $hasEmployee = $hasEmployee ?? true;
@endphp
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="mb-0 font-weight-bold">Riwayat Slip Gaji Saya</h3>
                <div class="text-muted small">Lihat riwayat slip gaji per bulan dan unduh slip jika tersedia.</div>
            </div>
            @if($verified && $hasEmployee)
            <div class="d-flex align-items-center mt-2">
                <div class="text-muted small mr-2">Tahun</div>
                <select id="filterYear" class="form-control" style="width:120px;">
                    @php
                        $yearsList = isset($years) && is_array($years) ? $years : [date('Y')];
                        $selectedYear = isset($currentYear) ? $currentYear : date('Y');
                    @endphp
                    <option value="all" {{ (string)$selectedYear === 'all' ? 'selected' : '' }}>All Time</option>
                    @foreach($yearsList as $y)
                        <option value="{{ $y }}" {{ (string)$selectedYear === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if(!$hasEmployee)
                        <div class="alert alert-warning mb-0">Data karyawan untuk akun ini tidak ditemukan.</div>
                    @elseif(!$verified)
                        <div class="text-center py-4">
                            <p class="mb-3">Untuk melihat slip gaji, silakan verifikasi password Anda terlebih dahulu.</p>
                            <button type="button" class="btn btn-primary" onclick="checkSlipGaji(event)">Verifikasi Password</button>
                        </div>
                    @else
                    <table id="mySlipHistoryTable" class="table table-bordered table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th style="display:none;">Sort</th>
                                <th>Bulan</th>
                                <th>KPI Poin</th>
                                <th>Hari Masuk</th>
                                <th>Total Pendapatan</th>
                                <th>Total Potongan</th>
                                <th>Total Gaji</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@if($verified && $hasEmployee)
<script>
    $(function(){
        function renderTrendIcon(trend) {
            if (!trend) return '';
            if (trend === 'up') return '<span class="badge badge-success ml-2 small" aria-label="increased" title="increased" style="font-size:0.75rem;line-height:1">&#9650;</span>';
            if (trend === 'down') return '<span class="badge badge-danger ml-2 small" aria-label="decreased" title="decreased" style="font-size:0.75rem;line-height:1">&#9660;</span>';
            if (trend === 'same') return '<span class="badge badge-warning ml-2 small" aria-label="no change" title="no change" style="font-size:0.75rem;line-height:1">=</span>';
            return '';
        }

        const table = $('#mySlipHistoryTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('hrd.payroll.slip_gaji.history.data') }}',
                type: 'GET',
                data: function (d) {
                    d.year = $('#filterYear').val();
                },
                error: function (xhr) {
                    // verification session expired -> reload shows the verify prompt again
                    if (xhr.status === 403) {
                        window.location.reload();
                    }
                }
            },
            columns: [
                { data: 'month_key', name: 'month_key', visible: false, searchable: false },
                { data: 'bulan_label', name: 'bulan_label' },
                {
                    data: 'kpi_poin',
                    name: 'kpi_poin',
                    className: 'text-right',
                    render: function (data, type, row) {
                        if (type !== 'display') return data;
                        var n = parseFloat(data);
                        if (isNaN(n)) n = 0;
                        return n.toFixed(2) + renderTrendIcon(row ? row.kpi_trend : null);
                    }
                },
                {
                    data: 'total_hari_masuk',
                    name: 'total_hari_masuk',
                    className: 'text-center',
                    render: function (data, type, row) {
                        if (type !== 'display') return data;
                        var n = parseInt(data, 10);
                        if (isNaN(n)) n = 0;
                        var days = null;
                        if (row && row.month_key) {
                            var parts = (row.month_key || '').toString().split('-');
                            if (parts.length === 2) {
                                var y = parseInt(parts[0], 10);
                                var m = parseInt(parts[1], 10);
                                if (!isNaN(y) && !isNaN(m)) {
                                    days = new Date(y, m, 0).getDate();
                                }
                            }
                        }
                        return n + '/' + (days || '-');
                    }
                },
                { data: 'total_pendapatan', name: 'total_pendapatan', className: 'text-right text-nowrap', orderable: false },
                { data: 'total_potongan', name: 'total_potongan', className: 'text-right text-nowrap', orderable: false },
                {
                    data: 'total_gaji',
                    name: 'total_gaji',
                    className: 'text-right text-nowrap font-weight-bold',
                    orderable: false,
                    render: function (data, type, row) {
                        if (type !== 'display') return data;
                        return (data || 'Rp 0,00') + renderTrendIcon(row ? row.total_gaji_trend : null);
                    }
                },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-nowrap' }
            ],
            order: [[0, 'desc']]
        });

        $('#filterYear').on('change', function(){
            table.ajax.reload();
        });
    });
</script>
@endif
@endsection
