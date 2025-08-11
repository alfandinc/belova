
@extends('layouts.erm.app')

@section('title', 'Obat Masuk')

@section('navbar')
    @include('layouts.erm.navbar')
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
            <h4>Obat Masuk</h4>
            <div>
                <input type="text" id="dateRange" class="form-control" style="min-width:220px;display:inline-block;" readonly />
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered" id="obatMasukTable">
                <thead>
                    <tr>
                        <th>Nama Obat</th>
                        <th>Jumlah Masuk</th>
                        <th>Faktur No</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    var today = moment().format('YYYY-MM-DD');
    $('#dateRange').daterangepicker({
        locale: { format: 'YYYY-MM-DD' },
        startDate: today,
        endDate: today,
        autoUpdateInput: true,
        opens: 'left',
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });

    var table = $('#obatMasukTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: -1,
        lengthMenu: [[-1, 10, 25, 50, 100], ["All", 10, 25, 50, 100]],
        ajax: {
            url: '{{ route('erm.obatmasuk.data') }}',
            data: function(d) {
                var drp = $('#dateRange').data('daterangepicker');
                d.start = drp.startDate.format('YYYY-MM-DD');
                d.end = drp.endDate.format('YYYY-MM-DD');
            }
        },
        columns: [
            { data: 'nama_obat', name: 'nama_obat' },
            { data: 'qty', name: 'qty' },
            { data: 'no_faktur', name: 'no_faktur' }
        ]
    });

    $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
        table.ajax.reload();
    });
});
</script>
@endpush
