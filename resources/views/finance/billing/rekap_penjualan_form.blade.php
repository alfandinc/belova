@extends('layouts.finance.app')
@section('title', 'Finance | Billing')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection

{{-- Statistik script removed --}}

@section('content')

<div class="container-fluid py-4">

    <div class="mb-5">
        <h2 class="fw-bold text-primary mb-3">Rekap Penjualan</h2>
        <form method="GET" action="{{ route('finance.rekap-penjualan.download') }}" class="mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" required value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <label for="end_date" class="form-label">Tanggal Selesai</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" required value="{{ request('end_date') }}">
                </div>
                <div class="col-md-3">
                    <label for="filter_klinik" class="form-label">Klinik</label>
                    <select name="klinik_id" id="filter_klinik" class="form-control select2">
                        <option value="">Semua Klinik</option>
                        @foreach($kliniks as $klinik)
                            <option value="{{ $klinik->id }}" {{ request('klinik_id') == $klinik->id ? 'selected' : '' }}>{{ $klinik->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_dokter" class="form-label">Dokter</label>
                    <select name="dokter_id" id="filter_dokter" class="form-control select2">
                        <option value="">Semua Dokter</option>
                        @foreach($dokters as $dokter)
                            <option value="{{ $dokter->id }}" {{ request('dokter_id') == $dokter->id ? 'selected' : '' }}>{{ $dokter->user->name ?? $dokter->id }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100 shadow-sm rounded-pill py-2">Download Rekap Penjualan</button>
                </div>
            </div>
        </form>
    </div>
    <!-- Rekap Penjualan Preview inserted below Rekap form -->
    <div class="mb-4">
        <div class="card shadow rounded mb-4 p-3">
            <h5>Rekap Penjualan Preview</h5>
            <div class="table-responsive">
                <table id="rekap-preview-table" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>Tanggal Visit</th>
                            <th>No RM</th>
                            <th>Nama Pasien</th>
                            <th>Nama Dokter</th>
                            <th>Nama Klinik</th>
                            <th>Jenis</th>
                            <th>Nama Item</th>
                            <th>Qty</th>
                            <th>Harga</th>
                            <th>Harga Sebelum Diskon</th>
                            <th>Diskon Nominal</th>
                            <th>Diskon</th>
                            <th>Harga Setelah Diskon</th>
                            <th>Status</th>
                            <th>Payment Method</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mb-5">
        <h2 class="fw-bold text-primary mb-3">Export Invoice</h2>
        <form method="GET" action="{{ route('finance.invoice.export.download') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label for="invoice_start_date" class="form-label">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="invoice_start_date" class="form-control" required value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <label for="invoice_end_date" class="form-label">Tanggal Selesai</label>
                    <input type="date" name="end_date" id="invoice_end_date" class="form-control" required value="{{ request('end_date') }}">
                </div>
                <div class="col-md-3">
                    <label for="filter_klinik_invoice" class="form-label">Klinik</label>
                    <select name="klinik_id" id="filter_klinik_invoice" class="form-control select2">
                        <option value="">Semua Klinik</option>
                        @foreach($kliniks as $klinik)
                            <option value="{{ $klinik->id }}" {{ request('klinik_id') == $klinik->id ? 'selected' : '' }}>{{ $klinik->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_dokter_invoice" class="form-label">Dokter</label>
                    <select name="dokter_id" id="filter_dokter_invoice" class="form-control select2">
                        <option value="">Semua Dokter</option>
                        @foreach($dokters as $dokter)
                            <option value="{{ $dokter->id }}" {{ request('dokter_id') == $dokter->id ? 'selected' : '' }}>{{ $dokter->user->name ?? $dokter->id }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm rounded-pill py-2">Download Invoice Excel</button>
                </div>
            </div>
        </form>
    </div>
        </form>
    </div>

    <!-- Invoice Preview inserted below Invoice form -->
    <div class="mb-4">
        <div class="card shadow rounded mb-4 p-3">
            <h5>Invoice Preview</h5>
            <div class="table-responsive">
                <table id="invoice-preview-table" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>Tanggal Visit</th>
                            <th>Tanggal Dibayar</th>
                            <th>No RM</th>
                            <th>Nama Pasien</th>
                            <th>Nama Dokter</th>
                            <th>Nama Klinik</th>
                            <th>Subtotal</th>
                            <th>Discount</th>
                            <th>Tax</th>
                            <th>Total Amount</th>
                            <th>Amount Paid</th>
                            <th>Change Amount</th>
                            <th>Payment Method</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
$(function(){
    $('#filter_klinik, #filter_klinik_invoice, #filter_dokter, #filter_dokter_invoice').select2({width: '100%'});

    var rekapTable = $('#rekap-preview-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('finance.rekap-penjualan.preview') }}',
            data: function(d){
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.klinik_id = $('#filter_klinik').val();
                d.dokter_id = $('#filter_dokter').val();
            }
        },
        columns: [
            { data: 'tanggal_visit' },{ data: 'no_rm' },{ data: 'nama_pasien' },{ data: 'nama_dokter' },{ data: 'nama_klinik' },{ data: 'jenis' },{ data: 'nama_item' },{ data: 'qty' },{ data: 'harga' },{ data: 'harga_sebelum_diskon' },{ data: 'diskon_nominal' },{ data: 'diskon' },{ data: 'harga_setelah_diskon' },{ data: 'status' },{ data: 'payment_method' },{ data: 'notes' }
        ],
        pageLength: 10
    });

    var invoiceTable = $('#invoice-preview-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('finance.invoice.export.preview') }}',
            data: function(d){
                d.start_date = $('#invoice_start_date').val();
                d.end_date = $('#invoice_end_date').val();
                d.klinik_id = $('#filter_klinik_invoice').val();
                d.dokter_id = $('#filter_dokter_invoice').val();
            }
        },
        columns: [
            { data: 'tanggal_visit' },{ data: 'tanggal_dibayar' },{ data: 'no_rm' },{ data: 'nama_pasien' },{ data: 'nama_dokter' },{ data: 'nama_klinik' },{ data: 'subtotal' },{ data: 'discount' },{ data: 'tax' },{ data: 'total_amount' },{ data: 'amount_paid' },{ data: 'change_amount' },{ data: 'payment_method' }
        ],
        pageLength: 10
    });

    // Reload previews when filters change
    $('#start_date, #end_date, #filter_klinik, #filter_dokter').on('change', function(){ rekapTable.ajax.reload(); });
    $('#invoice_start_date, #invoice_end_date, #filter_klinik_invoice, #filter_dokter_invoice').on('change', function(){ invoiceTable.ajax.reload(); });
});
</script>
@endsection

<style>
    .stat-card {
        transition: box-shadow 0.2s;
    }
    .stat-card:hover {
        box-shadow: 0 0 0 0.2rem #28a74533;
    }
    .btn-success, .btn-primary {
        font-weight: 500;
        letter-spacing: 0.5px;
        transition: background 0.2s, box-shadow 0.2s;
    }
    .btn-success:hover {
        background: #218838;
        box-shadow: 0 2px 8px #28a74533;
    }
    .btn-primary:hover {
        background: #0056b3;
        box-shadow: 0 2px 8px #007bff33;
    }
    h2 {
        font-size: 1.5rem;
    }
</style>
@endsection
