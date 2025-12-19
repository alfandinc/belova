@extends('layouts.erm.app')

@section('navbar')
    @include('layouts.satusehat.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Pasien SatuSehat — Kunjungan Hari Ini</h4>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="filterDate">Tanggal</label>
                    <input type="text" id="filterDate" class="form-control" />
                </div>
            </div>
            <table id="pasiens-table" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Pasien</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function(){
    // Initialize daterangepicker (default to this week)
    $('#filterDate').daterangepicker({
        locale: { format: 'YYYY-MM-DD' },
        startDate: moment().startOf('week'),
        endDate: moment().endOf('week'),
        opens: 'left'
    }, function(start, end, label){
        // reload table when date range changes
        if(window.pasiensTable){ window.pasiensTable.ajax.reload(); }
    });

    // create DataTable after daterangepicker so initial load uses the week range
    window.pasiensTable = $('#pasiens-table').DataTable({
        ajax: {
            url: "{{ route('satusehat.pasiens.data') }}",
            data: function(d){
                // DataTables passes d; attach start/end from daterangepicker
                var range = $('#filterDate').data('daterangepicker');
                if(range){
                    d.start = range.startDate.format('YYYY-MM-DD');
                    d.end = range.endDate.format('YYYY-MM-DD');
                } else {
                    // fallback to today
                    var today = moment().format('YYYY-MM-DD');
                    d.start = today; d.end = today;
                }
            },
            dataSrc: 'data'
        },
        columns: [
            { data: 'tanggal_visitation' },
            { data: 'pasien' },
            { data: 'aksi', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        responsive: true
    });

    // Handle Get Data button click
    $(document).on('click', '.btn-get-data', function(e){
        e.preventDefault();
        var $btn = $(this);
        var visitationId = $btn.data('visitation-id');
        $btn.prop('disabled', true).text('Loading...');
        var url = "{{ url('/satusehat/pasiens') }}" + '/' + visitationId + '/get-data';
        fetch(url, { headers:{'X-Requested-With':'XMLHttpRequest'} })
            .then(r => r.json())
            .then(json => {
                if(json.ok){
                    var pretty = JSON.stringify(json.data, null, 2);
                    // Put the pretty JSON into the modal pre element (use text to avoid HTML injection)
                    $('#kemkesWarning').hide();
                    if(json.warning){
                        $('#kemkesWarning').text(json.warning).show();
                    }
                    $('#kemkesContent').text(pretty);
                    $('#kemkesModal').modal('show');
                } else {
                    Swal.fire('Error', json.error || JSON.stringify(json), 'error');
                }
            }).catch(err => {
                Swal.fire('Error', err.message || 'Request failed', 'error');
            }).finally(()=>{
                $btn.prop('disabled', false).text('Get Data');
            });
    });
});
</script>
<!-- Kemkes result modal -->
<div class="modal fade" id="kemkesModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kemkes Patient</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="kemkesWarning" class="alert alert-warning" style="display:none;"></div>
                <pre id="kemkesContent" style="white-space:pre-wrap; word-wrap:break-word; max-height:60vh; overflow:auto;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection
