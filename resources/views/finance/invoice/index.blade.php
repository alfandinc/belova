@extends('layouts.finance.app')
@section('title', 'Finance | Invoice')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection
@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Daftar Invoice</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('finance.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Invoice</li>
            </ol>
        </nav>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="invoiceTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nomor Invoice</th>
                            <th>Tanggal</th>
                            <th>Nama Pasien</th>
                            <th>Klinik</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#invoiceTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('finance.invoice.index') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'invoice_number', name: 'invoice_number'},
                {data: 'invoice_date', name: 'created_at'},
                {data: 'patient_name', name: 'patient_name'},
                {data: 'clinic_name', name: 'clinic_name'},
                {data: 'total_amount', name: 'total_amount', 
                    render: function(data) {
                        return 'Rp ' + numberWithCommas(data);
                    }
                },
                {data: 'status_badge', name: 'status'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ],
            order: [[1, 'desc']]
        });
        
        // Helper function for number formatting
        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
    });
</script>
@endsection