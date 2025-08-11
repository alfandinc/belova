
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
                        <th>Detail</th>
                    </tr>
                </thead>
            </table>
                        <div class="card mt-4">
                            <div class="card-body text-center">
                                <span id="nilaiObatMasukSection" class="font-weight-bold"></span>
                            </div>
                        </div>

                        <!-- Modal -->
                        <div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="detailModalLabel">Detail Obat Masuk</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div id="detailModalContent">
                                                <div class="text-center"><span class="spinner-border"></span> Loading...</div>
                                        </div>
                                    </div>
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
            { data: 'detail', name: 'detail', orderable: false, searchable: false }
        ],
        drawCallback: function(settings) {
            var response = settings.json || {};
            var totalHpp = response.total_hpp || 0;
            $('#nilaiObatMasukSection').html('<strong>Nilai Obat Masuk: </strong>Rp' + parseFloat(totalHpp).toLocaleString('id-ID'));
        }
    });

    $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
        table.ajax.reload();
    });

    // Handle detail button click
    $('#obatMasukTable').on('click', '.btn-detail', function() {
        var obatId = $(this).data('obat-id');
        var start = $('#dateRange').data('daterangepicker').startDate.format('YYYY-MM-DD');
        var end = $('#dateRange').data('daterangepicker').endDate.format('YYYY-MM-DD');
        $('#detailModal').modal('show');
        $('#detailModalContent').html('<div class="text-center"><span class="spinner-border"></span> Loading...</div>');
        $.get(
            '{{ url('/erm/obat-masuk/detail') }}',
            { obat_id: obatId, start: start, end: end },
            function(res) {
                $('#detailModalContent').html(res);
            }
        );
    });
});
</script>
@endpush
