@extends('layouts.hrd.app')
@section('title', 'Riwayat Slip Gaji')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="card-title">Riwayat Slip Gaji Saya</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="mySlipHistoryTable" class="table table-bordered table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>Bulan</th>
                                <th>Total Gaji</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function(){
        const table = $('#mySlipHistoryTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('hrd.payroll.slip_gaji.history.data') }}',
                type: 'GET'
            },
            columns: [
                { data: 'bulan', name: 'bulan' },
                { data: 'total_gaji', name: 'total_gaji', className: 'text-right' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [[0, 'desc']]
        });
    });
</script>
@endsection
