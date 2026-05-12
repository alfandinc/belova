@extends('layouts.erm.app')
@section('title', 'ERM | Detail Pembelian Principal - ' . $principal->nama)
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row mt-3 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0">Detail Pembelian Principal - {{ $principal->nama }}</h2>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('erm.datapembelian.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/erm">ERM</a></li>
                            <li class="breadcrumb-item"><a href="#" onclick="return false;">Pembelian</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('erm.datapembelian.index') }}">Data Pembelian</a></li>
                            <li class="breadcrumb-item active">Detail Principal {{ $principal->nama }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Informasi Principal</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless mb-0">
                                <tr>
                                    <td width="150"><strong>Nama Principal</strong></td>
                                    <td>: {{ $principal->nama }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Alamat</strong></td>
                                    <td>: {{ $principal->alamat ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless mb-0">
                                <tr>
                                    <td width="150"><strong>Telepon</strong></td>
                                    <td>: {{ $principal->telepon ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email</strong></td>
                                    <td>: {{ $principal->email ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media">
                        <div class="media-body">
                            <p class="text-muted fw-medium">Total Pembelian Principal</p>
                            <h4 class="fw-bold"><span id="totalPembelianValue">Rp {{ number_format($purchaseHistory->sum('total_principal'), 0, ',', '.') }}</span></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="mdi mdi-currency-usd h2 text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media">
                        <div class="media-body">
                            <p class="text-muted fw-medium">Jumlah Faktur</p>
                            <h4 class="fw-bold"><span id="jumlahFakturValue">{{ $purchaseHistory->count() }}</span></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="mdi mdi-file-document h2 text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media">
                        <div class="media-body">
                            <p class="text-muted fw-medium">Jenis Item</p>
                            <h4 class="fw-bold"><span id="jenisItemValue">{{ $purchaseHistory->pluck('obat_ids')->filter()->flatMap(function ($ids) { return collect(explode(',', $ids))->filter(); })->unique()->count() }}</span></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="mdi mdi-package-variant h2 text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media">
                        <div class="media-body">
                            <p class="text-muted fw-medium">Pembelian Terakhir</p>
                            <h4 class="fw-bold">{{ $purchaseHistory->first()['received_date'] ? \Carbon\Carbon::parse($purchaseHistory->first()['received_date'])->format('d/m/Y') : '-' }}</h4>
                        </div>
                        <div class="align-self-center">
                            <i class="mdi mdi-calendar h2 text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="card-title mb-0">Riwayat Pembelian per Principal</h4>
                        </div>
                        <div class="col-md-6">
                            <div class="form-inline justify-content-end">
                                <label for="tanggalTerimaRange" class="mr-2">Filter Tanggal Terima:</label>
                                <input type="text" id="tanggalTerimaRange" class="form-control" style="width:220px;" autocomplete="off" placeholder="Pilih rentang tanggal">
                                <button class="btn btn-secondary btn-sm ml-2" id="resetTanggalTerima">Reset</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="purchase-history-table">
                            <thead>
                                <tr>
                                    <th>No Faktur</th>
                                    <th>Pemasok</th>
                                    <th>Tanggal Terima</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Jumlah Item</th>
                                    <th>Total Qty</th>
                                    <th>Total Principal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="purchase-history-tbody">
                                @forelse($purchaseHistory as $history)
                                <tr class="purchase-row"
                                    data-received-date="{{ $history['received_date'] }}"
                                    data-total="{{ $history['total_principal'] }}"
                                    data-obat-ids="{{ $history['obat_ids'] }}">
                                    <td>{{ $history['no_faktur'] }}</td>
                                    <td>{{ $history['pemasok_nama'] }}</td>
                                    <td>{{ $history['received_date'] ? \Carbon\Carbon::parse($history['received_date'])->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $history['due_date'] ? \Carbon\Carbon::parse($history['due_date'])->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $history['jumlah_item'] }} item</td>
                                    <td>{{ number_format($history['qty_total'], 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($history['total_principal'], 0, ',', '.') }}</td>
                                    <td>
                                        @php
                                            $badgeClass = match($history['status']) {
                                                'diminta' => 'badge-warning',
                                                'diterima' => 'badge-info',
                                                'diapprove' => 'badge-success',
                                                'diretur' => 'badge-danger',
                                                default => 'badge-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $history['status'] }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr id="no-data-row">
                                    <td colspan="8" class="text-center">Tidak ada data pembelian principal</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div id="no-filter-results" class="text-center text-muted mt-3" style="display: none;">
                        <i class="fa fa-calendar fa-2x mb-2"></i>
                        <p>Tidak ada pembelian pada rentang tanggal "<span id="dateRangeTerm"></span>"</p>
                        <button class="btn btn-sm btn-outline-primary" id="showAllPurchases">Tampilkan Semua</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('#tanggalTerimaRange').daterangepicker({
        autoUpdateInput: false,
        locale: {
            format: 'DD/MM/YYYY',
            separator: ' - ',
            applyLabel: 'Terapkan',
            cancelLabel: 'Batal',
            fromLabel: 'Dari',
            toLabel: 'Sampai',
            customRangeLabel: 'Custom',
            weekLabel: 'W',
            daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            firstDay: 1
        }
    });

    $('#tanggalTerimaRange').on('apply.daterangepicker', function(ev, picker) {
        var startDate = picker.startDate.format('DD/MM/YYYY');
        var endDate = picker.endDate.format('DD/MM/YYYY');
        $(this).val(startDate + ' - ' + endDate);
        filterPurchaseHistory(picker.startDate, picker.endDate);
        updateSummary();
    });

    $('#tanggalTerimaRange').on('cancel.daterangepicker', function() {
        $(this).val('');
        showAllPurchases();
    });

    $('#resetTanggalTerima, #showAllPurchases').on('click', function() {
        $('#tanggalTerimaRange').val('');
        showAllPurchases();
    });

    function filterPurchaseHistory(startDate, endDate) {
        var visibleRows = 0;

        $('.purchase-row').each(function() {
            var receivedDate = $(this).data('received-date');

            if (receivedDate && receivedDate !== '-') {
                var rowDate = moment(receivedDate, 'YYYY-MM-DD');

                if (rowDate.isSameOrAfter(startDate, 'day') && rowDate.isSameOrBefore(endDate, 'day')) {
                    $(this).show();
                    visibleRows++;
                } else {
                    $(this).hide();
                }
            } else {
                $(this).hide();
            }
        });

        if (visibleRows === 0) {
            $('#dateRangeTerm').text($('#tanggalTerimaRange').val());
            $('#no-filter-results').show();
            $('#no-data-row').hide();
        } else {
            $('#no-filter-results').hide();
            $('#no-data-row').hide();
        }
    }

    function showAllPurchases() {
        $('.purchase-row').show();
        $('#no-filter-results').hide();

        if ($('.purchase-row').length === 0) {
            $('#no-data-row').show();
        } else {
            $('#no-data-row').hide();
        }

        updateSummary();
    }

    function formatRp(amount) {
        var num = Number(amount) || 0;
        return 'Rp ' + num.toLocaleString('id-ID');
    }

    function updateSummary() {
        var visible = $('.purchase-row:visible');
        var total = 0;
        var fakturCount = visible.length;
        var obatSet = {};

        visible.each(function() {
            total += Number($(this).data('total')) || 0;

            var ids = String($(this).data('obat-ids') || '');
            if (ids.length) {
                ids.split(',').forEach(function(id) {
                    if (id !== '') {
                        obatSet[id] = true;
                    }
                });
            }
        });

        $('#totalPembelianValue').text(formatRp(total));
        $('#jumlahFakturValue').text(fakturCount);
        $('#jenisItemValue').text(Object.keys(obatSet).length);
    }

    showAllPurchases();
    updateSummary();
});
</script>
@endpush