@extends('layouts.erm.app')
@section('title', 'ERM | Detail Pembelian - ' . $pemasok->nama)
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection  
@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row mt-3 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0">Detail Pembelian - {{ $pemasok->nama }}</h2>
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
                            <li class="breadcrumb-item active">Detail {{ $pemasok->nama }}</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->
    
    <!-- Supplier Info -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Informasi Pemasok</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>Nama Pemasok</strong></td>
                                    <td>: {{ $pemasok->nama }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Alamat</strong></td>
                                    <td>: {{ $pemasok->alamat ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>Telepon</strong></td>
                                    <td>: {{ $pemasok->telepon ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email</strong></td>
                                    <td>: {{ $pemasok->email ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Summary -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media">
                                        <div class="media-body">
                                            <p class="text-muted fw-medium">Total Pembelian</p>
                                            <h4 class="fw-bold"><span id="totalPembelianValue">Rp {{ number_format($pemasok->fakturBeli->sum('total'), 0, ',', '.') }}</span></h4>
                                        </div>
                        <div class="align-self-center">
                            <i class="mdi mdi-currency-usd h2 text-muted"></i>
                        </div>
                    </div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media">
                        <div class="media-body">
                            <p class="text-muted fw-medium">Jumlah Faktur</p>
                            <h4 class="fw-bold"><span id="jumlahFakturValue">{{ $pemasok->fakturBeli->count() }}</span></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="mdi mdi-file-document h2 text-muted"></i>
                        </div>
                    </div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media">
                        <div class="media-body">
                            <p class="text-muted fw-medium">Jenis Item</p>
                            <h4 class="fw-bold"><span id="jenisItemValue">{{ $pemasok->fakturBeli->flatMap(function($f) { return $f->items->pluck('obat_id'); })->unique()->count() }}</span></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="mdi mdi-package-variant h2 text-muted"></i>
                        </div>
                    </div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media">
                        <div class="media-body">
                            <p class="text-muted fw-medium">Pembelian Terakhir</p>
                            <h4 class="fw-bold">{{ $pemasok->fakturBeli->first()?->received_date ? \Carbon\Carbon::parse($pemasok->fakturBeli->first()->received_date)->format('d/m/Y') : '-' }}</h4>
                        </div>
                        <div class="align-self-center">
                            <i class="mdi mdi-calendar h2 text-muted"></i>
                        </div>
                    </div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div>
    </div>

    <!-- Purchase History -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="card-title mb-0">Riwayat Pembelian</h4>
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
                                    <th>Tanggal Terima</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Jumlah Item</th>
                                    <th>Subtotal</th>
                                    <th>Diskon</th>
                                    <th>Pajak</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="purchase-history-tbody">
                                @forelse($pemasok->fakturBeli as $faktur)
                                <tr class="purchase-row" 
                                    data-received-date="{{ $faktur->received_date }}"
                                    data-original-index="{{ $loop->iteration }}"
                                    data-total="{{ $faktur->total ?: 0 }}"
                                    data-obat-ids="{{ $faktur->items->pluck('obat_id')->join(',') }}">
                                    <td>{{ $faktur->no_faktur ?: '-' }}</td>
                                    <td>{{ $faktur->received_date ? \Carbon\Carbon::parse($faktur->received_date)->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $faktur->due_date ? \Carbon\Carbon::parse($faktur->due_date)->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $faktur->items->count() }} item</td>
                                    <td>Rp {{ number_format($faktur->subtotal ?: 0, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($faktur->global_diskon ?: 0, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($faktur->global_pajak ?: 0, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($faktur->total ?: 0, 0, ',', '.') }}</td>
                                    <td>
                                        @php
                                            $badgeClass = match($faktur->status) {
                                                'diminta' => 'badge-warning',
                                                'diterima' => 'badge-info',
                                                'diapprove' => 'badge-success',
                                                'diretur' => 'badge-danger',
                                                default => 'badge-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $faktur->status }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr id="no-data-row">
                                    <td colspan="9" class="text-center">Tidak ada data pembelian</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- No results message for filtering -->
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
    // Date Range Picker for Tanggal Terima
    $('#tanggalTerimaRange').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear',
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
        
        // Filter the table
        filterPurchaseHistory(picker.startDate, picker.endDate);
        updateSummary();
    });

    $('#tanggalTerimaRange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        showAllPurchases();
    });

    $('#resetTanggalTerima').on('click', function() {
        $('#tanggalTerimaRange').val('');
        showAllPurchases();
    });

    $('#showAllPurchases').on('click', function() {
        $('#tanggalTerimaRange').val('');
        showAllPurchases();
    });

    function filterPurchaseHistory(startDate, endDate) {
        var visibleRows = 0;
        var rowCounter = 1;
        
        $('.purchase-row').each(function() {
            var receivedDate = $(this).data('received-date');
            
            if (receivedDate && receivedDate !== '-') {
                var rowDate = moment(receivedDate, 'YYYY-MM-DD');
                
                if (rowDate.isSameOrAfter(startDate, 'day') && rowDate.isSameOrBefore(endDate, 'day')) {
                    $(this).show();
                    visibleRows++;
                    rowCounter++;
                } else {
                    $(this).hide();
                }
            } else {
                // Hide rows without received_date when filtering
                $(this).hide();
            }
        });

        // Show/hide no results message
        if (visibleRows === 0) {
            var dateRangeText = $('#tanggalTerimaRange').val();
            $('#dateRangeTerm').text(dateRangeText);
            $('#no-filter-results').show();
            $('#no-data-row').hide();
        } else {
            $('#no-filter-results').hide();
            $('#no-data-row').hide();
        }
            updateSummary();
    }

    function showAllPurchases() {
        $('.purchase-row').show();
        $('#no-filter-results').hide();
        
        // Show original no-data row if no purchases exist
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
            var t = Number($(this).data('total')) || 0;
            total += t;
            var ids = String($(this).data('obat-ids') || '');
            if (ids.length) {
                ids.split(',').forEach(function(id) {
                    if (id !== '') obatSet[id] = true;
                });
            }
        });

        $('#totalPembelianValue').text(formatRp(total));
        $('#jumlahFakturValue').text(fakturCount);
        $('#jenisItemValue').text(Object.keys(obatSet).length);
    }

    // Initialize: show all purchases
    showAllPurchases();
    updateSummary();
});
</script>
@endpush