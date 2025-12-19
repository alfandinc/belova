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
                <div class="col-md-3">
                    <label for="filterKlinik">Klinik</label>
                    <select id="filterKlinik" class="form-control">
                        <option value="">-- Semua Klinik --</option>
                        @foreach($kliniks as $kl)
                            <option value="{{ $kl->id }}">{{ $kl->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filterEncounterStatus">Encounter Status</label>
                    <select id="filterEncounterStatus" class="form-control">
                        <option value="">-- Semua Status --</option>
                        @foreach($statuses as $k => $lab)
                            <option value="{{ $k }}">{{ $lab }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <table id="pasiens-table" class="table table-striped table-bordered" style="width:100%">
                <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Pasien</th>
                                <th>Dokter</th>
                                <th>Encounter Status</th>
                                <th>Diagnosa Kerja</th>
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

    // reload table when filters change
    $(document).on('change', '#filterKlinik, #filterEncounterStatus', function(){ if(window.pasiensTable){ window.pasiensTable.ajax.reload(); } });

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
                // attach selected klinik and encounter status filters
                d.klinik_id = $('#filterKlinik').val();
                d.encounter_status = $('#filterEncounterStatus').val();
            },
            dataSrc: 'data'
        },
        columns: [
            { data: 'tanggal_visitation' },
            { data: 'pasien' },
            { data: 'dokter' },
            { data: 'encounter_status' },
            { data: 'diagnosa' },
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
                    // set encounter id on buttons
                    var encId = (json.data && json.data.id) ? json.data.id : (json.data && json.data.resource && json.data.resource.id ? json.data.resource.id : null);
                    if(encId){ $btn.data('encounter-id', encId); $btn.closest('td').find('.btn-send-condition').data('encounter-id', encId); $btn.closest('td').find('.btn-finish-encounter').data('encounter-id', encId); }
                } else {
                    Swal.fire('Error', json.error || JSON.stringify(json), 'error');
                }
            }).catch(err => {
                Swal.fire('Error', err.message || 'Request failed', 'error');
            }).finally(()=>{
                $btn.prop('disabled', false).text('Get Data');
            });
    });

    // Handle Create Encounter
    $(document).on('click', '.btn-create-encounter', function(e){
        e.preventDefault();
        var $btn = $(this);
        var visitationId = $btn.data('visitation-id');
        $btn.prop('disabled', true).text('Creating...');
        var url = "{{ url('/satusehat/pasiens') }}" + '/' + visitationId + '/create-encounter';
        fetch(url, { method: 'POST', headers: {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'} })
            .then(r => r.json())
            .then(json => {
                    if(json.ok){
                        var prettySent = JSON.stringify(json.payload_sent || {}, null, 2);
                        var pretty = JSON.stringify(json.data || json.body || {}, null, 2);
                        $('#kemkesWarning').hide();
                        if(json.warning) { $('#kemkesWarning').text(json.warning).show(); }
                        $('#kemkesContent').text('SENT:\n' + prettySent + '\n\nRESPONSE:\n' + pretty);
                        $('#kemkesModal').modal('show');

                        // extract created encounter id and store on the button so Send Condition can use it
                        var encId = (json.data && json.data.id) ? json.data.id : (json.data && json.data.resource && json.data.resource.id ? json.data.resource.id : null);
                        if(encId){ $btn.data('encounter-id', encId); $btn.closest('td').find('.btn-send-condition').data('encounter-id', encId); }
                    } else {
                    Swal.fire('Error', json.error || JSON.stringify(json), 'error');
                }
            }).catch(err => {
                Swal.fire('Error', err.message || 'Request failed', 'error');
            }).finally(()=>{ $btn.prop('disabled', false).text('Create Encounter'); });
    });

    // Handle Update Encounter
    $(document).on('click', '.btn-update-encounter', function(e){
        e.preventDefault();
        var $btn = $(this);
        var visitationId = $btn.data('visitation-id');
        $btn.prop('disabled', true).text('Updating...');
        var url = "{{ url('/satusehat/pasiens') }}" + '/' + visitationId + '/update-encounter';
        fetch(url, { method: 'POST', headers: {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'} })
            .then(r => r.json())
            .then(json => {
                if(json.ok){
                    var prettySent = JSON.stringify(json.payload_sent || {}, null, 2);
                    var pretty = JSON.stringify(json.data || json.body || {}, null, 2);
                    $('#kemkesWarning').hide();
                    if(json.warning) { $('#kemkesWarning').text(json.warning).show(); }
                    $('#kemkesContent').text('SENT:\n' + prettySent + '\n\nRESPONSE:\n' + pretty);
                    $('#kemkesModal').modal('show');
                } else {
                    Swal.fire('Error', json.error || JSON.stringify(json), 'error');
                }
            }).catch(err => {
                Swal.fire('Error', err.message || 'Request failed', 'error');
            }).finally(()=>{ $btn.prop('disabled', false).text('Update Encounter'); });
    });

    // Handle Finish Encounter
    $(document).on('click', '.btn-finish-encounter', function(e){
        e.preventDefault();
        var $btn = $(this);
        var visitationId = $btn.data('visitation-id');
        $btn.prop('disabled', true).text('Finishing...');
        var url = "{{ url('/satusehat/pasiens') }}" + '/' + visitationId + '/finish-encounter';
        fetch(url, { method: 'POST', headers: {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'} })
            .then(r => r.json())
            .then(json => {
                if(json.ok){
                    var prettySent = JSON.stringify(json.payload_sent || {}, null, 2);
                    var pretty = JSON.stringify(json.data || json.body || {}, null, 2);
                    $('#kemkesWarning').hide();
                    if(json.warning) { $('#kemkesWarning').text(json.warning).show(); }
                    $('#kemkesContent').text('SENT:\n' + prettySent + '\n\nRESPONSE:\n' + pretty + (json.condition_response ? '\n\nCONDITION RESPONSE:\n' + JSON.stringify(json.condition_response, null, 2) : ''));
                    $('#kemkesModal').modal('show');

                    // set encounter id on buttons
                    var encId = (json.data && json.data.id) ? json.data.id : (json.data && json.data.resource && json.data.resource.id ? json.data.resource.id : null);
                    if(encId){ $btn.data('encounter-id', encId); $btn.closest('td').find('.btn-send-condition').data('encounter-id', encId); $btn.closest('td').find('.btn-update-encounter').data('encounter-id', encId); }
                } else {
                    Swal.fire('Error', json.error || JSON.stringify(json), 'error');
                }
            }).catch(err => {
                Swal.fire('Error', err.message || 'Request failed', 'error');
            }).finally(()=>{ $btn.prop('disabled', false).text('Finish Encounter'); });
    });

    // Handle Send Medication
    $(document).on('click', '.btn-send-medication', function(e){
        e.preventDefault();
        var $btn = $(this);
        var visitationId = $btn.data('visitation-id');
        $btn.prop('disabled', true).text('Sending...');
        var url = "{{ url('/satusehat/pasiens') }}" + '/' + visitationId + '/send-medication';
        fetch(url, { method: 'POST', headers: {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'} })
            .then(r => r.json())
            .then(json => {
                if(json.ok){
                    var pretty = JSON.stringify(json.results || {}, null, 2);
                    $('#kemkesWarning').hide();
                    $('#kemkesContent').text('RESULTS:\n' + pretty);
                    $('#kemkesModal').modal('show');
                } else {
                    Swal.fire('Error', json.error || JSON.stringify(json), 'error');
                }
            }).catch(err => {
                Swal.fire('Error', err.message || 'Request failed', 'error');
            }).finally(()=>{ $btn.prop('disabled', false).text('Send Medication'); });
    });

    // Handle Send Condition button click
    $(document).on('click', '.btn-send-condition', function(e){
        e.preventDefault();
        var $btn = $(this);
        var visitationId = $btn.data('visitation-id');
        var encounterId = $btn.data('encounter-id') || $btn.closest('td').find('.btn-create-encounter').data('encounter-id');
        if(!encounterId){
            Swal.fire('Error', 'No encounter id found. Please create an encounter first.', 'error');
            return;
        }
        $btn.prop('disabled', true).text('Sending...');
        var url = "{{ url('/satusehat/pasiens') }}" + '/' + visitationId + '/send-condition';
        fetch(url, { method: 'POST', headers: {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json','Accept':'application/json'}, body: JSON.stringify({ encounter_id: encounterId }) })
            .then(r=>r.json()).then(json=>{
                if(json.ok){
                    var prettySent = JSON.stringify(json.payload_sent || {}, null, 2);
                    var pretty = JSON.stringify(json.data || json.body || {}, null, 2);
                    $('#kemkesWarning').hide();
                    if(json.warning) { $('#kemkesWarning').text(json.warning).show(); }
                    $('#kemkesContent').text('SENT:\n' + prettySent + '\n\nRESPONSE:\n' + pretty);
                    $('#kemkesModal').modal('show');
                } else {
                    Swal.fire('Error', json.error || JSON.stringify(json), 'error');
                }
            }).catch(err=>{ Swal.fire('Error', err.message || 'Request failed', 'error'); })
            .finally(()=>{ $btn.prop('disabled', false).text('Send Condition'); });
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
