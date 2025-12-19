@extends('layouts.erm.app')

@section('navbar')
    @include('layouts.satusehat.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Pasien SatuSehat — Kunjungan Hari Ini</h4>
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
    $('#pasiens-table').DataTable({
        ajax: {
            url: "{{ route('satusehat.pasiens.data') }}",
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
