@extends('layouts.hrd.app')
@section('title', 'Slip Gaji Saya')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="mb-0 font-weight-bold">Riwayat Slip Gaji Saya</h3>
                <div class="text-muted small">Slip gaji dokter yang sudah dibayarkan (Paid). Klik Lihat untuk rincian lengkap.</div>
            </div>
            @if($hasDokter && $verified)
            <div class="d-flex align-items-center mt-2">
                <div class="text-muted small mr-2">Tahun</div>
                <select id="filterYear" class="form-control" style="width:120px;">
                    <option value="all">All Time</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ (string) $currentYear === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
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
                    @if(!$hasDokter)
                        <div class="alert alert-warning mb-0">Data dokter untuk akun ini tidak ditemukan.</div>
                    @elseif(!$verified)
                        <div class="text-center py-4">
                            <p class="mb-3">Untuk melihat slip gaji, silakan verifikasi password Anda terlebih dahulu.</p>
                            <button type="button" class="btn btn-primary" onclick="checkSlipGaji(event)">Verifikasi Password</button>
                        </div>
                    @else
                    <table id="myDokterSlipTable" class="table table-bordered table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th style="display:none;">Sort</th>
                                <th>Bulan</th>
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
@if($hasDokter && $verified)
<script>
    $(function(){
        function formatRupiah(value) {
            var num = parseFloat(value);
            if (isNaN(num)) num = 0;
            return 'Rp ' + num.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function renderTrendIcon(trend) {
            if (trend === 'up') return '<span class="badge badge-success ml-2 small" title="naik dari bulan sebelumnya">&#9650;</span>';
            if (trend === 'down') return '<span class="badge badge-danger ml-2 small" title="turun dari bulan sebelumnya">&#9660;</span>';
            if (trend === 'same') return '<span class="badge badge-warning ml-2 small" title="sama dengan bulan sebelumnya">=</span>';
            return '';
        }

        var money = function(d, type){ return type === 'display' ? formatRupiah(d) : d; };

        var table = $('#myDokterSlipTable').DataTable({
            ajax: {
                url: '{{ route('hrd.payroll.slip_gaji_dokter.my.data') }}',
                data: function(d){ d.year = $('#filterYear').val(); },
                error: function(xhr){
                    // verification session expired -> reload shows the verify prompt again
                    if (xhr.status === 403) window.location.reload();
                }
            },
            order: [[0, 'desc']],
            columns: [
                { data: 'bulan', visible: false },
                { data: 'bulan_label', orderData: [0] },
                { data: 'total_pendapatan', className: 'text-right text-nowrap', render: money },
                { data: 'total_potongan', className: 'text-right text-nowrap', render: money },
                { data: 'total_gaji', className: 'text-right text-nowrap font-weight-bold', render: function(d, type, row){
                    return type === 'display' ? formatRupiah(d) + renderTrendIcon(row.total_gaji_trend) : d;
                } },
                { data: null, orderable: false, searchable: false, className: 'text-nowrap', render: function(row){
                    return '<a href="' + row.view_url + '" class="btn btn-sm btn-primary mr-1" target="_blank">Lihat Slip Gaji</a>'
                        + '<a href="' + row.download_url + '" class="btn btn-sm btn-outline-secondary"><i class="fa fa-download"></i> Download</a>';
                } }
            ]
        });

        $('#filterYear').on('change', function(){ table.ajax.reload(); });
    });
</script>
@endif
@endsection
