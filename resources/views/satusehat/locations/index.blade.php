@extends('layouts.erm.app')

@section('navbar')
    @include('layouts.satusehat.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">SatuSehat Locations</h4>
            <p><button id="btnCreate" class="btn btn-success">Create Location</button></p>
            <table id="locations-table" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Klinik</th>
                        <th>Name</th>
                        <th>Identifier</th>
                        <th>Province / City</th>
                        <th>Lat,Lon</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="locationModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Location</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <form id="locationForm">
          <input type="hidden" name="id" id="loc_id" />
          <div class="form-row">
            <div class="form-group col-md-6"><label>Klinik</label>
                <select name="klinik_id" id="klinik_id" class="form-control">
                    <option value="">-- pilih klinik --</option>
                    @foreach($kliniks as $k)
                        <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6"><label>Location ID</label><input name="location_id" id="location_id" class="form-control"/></div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6"><label>Name</label><input name="name" id="name" class="form-control"/></div>
            <div class="form-group col-md-6"><label>Identifier Value</label><input name="identifier_value" id="identifier_value" class="form-control"/></div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-4"><label>Province</label><input name="province" id="province" class="form-control"/></div>
            <div class="form-group col-md-4"><label>City</label><input name="city" id="city" class="form-control"/></div>
            <div class="form-group col-md-4"><label>District</label><input name="district" id="district" class="form-control"/></div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-4"><label>Village</label><input name="village" id="village" class="form-control"/></div>
            <div class="form-group col-md-2"><label>RT</label><input name="rt" id="rt" class="form-control"/></div>
            <div class="form-group col-md-2"><label>RW</label><input name="rw" id="rw" class="form-control"/></div>
            <div class="form-group col-md-4"><label>Postal Code</label><input name="postal_code" id="postal_code" class="form-control"/></div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-12"><label>Line</label><input name="line" id="line" class="form-control"/></div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6"><label>Latitude</label><input name="latitude" id="latitude" class="form-control"/></div>
            <div class="form-group col-md-6"><label>Longitude</label><input name="longitude" id="longitude" class="form-control"/></div>
          </div>
          <div class="form-group"><label>Description</label><textarea name="description" id="description" class="form-control"></textarea></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" id="saveLocation" class="btn btn-primary">Save</button>
      </div>
    </div>
  </div>
</div>

@section('scripts')
<script>
$(function(){
    var table = $('#locations-table').DataTable({
        ajax: { url: '{{ route('satusehat.locations.data') }}', dataSrc: 'data' },
        columns: [
            { data: 'id' },
            { data: 'klinik' },
            { data: 'name' },
            { data: 'identifier_value' },
            { data: function(row){ return row.province + ' / ' + row.city; } },
            { data: 'latlng' },
            { data: 'aksi', orderable:false, searchable:false }
        ]
    });

    $('#btnCreate').on('click', function(){
        $('#locationForm')[0].reset(); $('#loc_id').val(''); $('#locationModal').modal('show');
    });

    $(document).on('click', '.btn-edit', function(){
        var id = $(this).data('id');
        fetch('{{ url('') }}' + '/satusehat/locations/' + id)
            .then(r=>r.json()).then(res=>{
                if(res.ok){
                    var d = res.data;
                    for(var k in d){ if($('#'+k).length) $('#'+k).val(d[k]); }
                    $('#loc_id').val(d.id);
                    $('#locationModal').modal('show');
                }
            });
    });

    $('#saveLocation').on('click', function(){
      var id = $('#loc_id').val();
      var url = '{{ route('satusehat.locations.store') }}';
      var method = 'POST';
      var form = new FormData(document.getElementById('locationForm'));
      if(id){
        // For multipart/form-data, browsers don't reliably send body for PUT
        // so use POST with _method=PUT so Laravel can parse the FormData
        url = '{{ url('') }}' + '/satusehat/locations/' + id;
        form.append('_method','PUT');
      }
      fetch(url, { method: 'POST', headers:{'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':'{{ csrf_token() }}' }, body: form })
        .then(r=>r.json()).then(json=>{
          if(json.ok){ $('#locationModal').modal('hide'); table.ajax.reload(); }
          else alert('Error: ' + (json.error || JSON.stringify(json)) );
        }).catch((err)=>alert('Request failed: ' + err.message));
    });

    $(document).on('click', '.btn-delete', function(){
        if(!confirm('Delete?')) return;
        var id = $(this).data('id');
        fetch('{{ url('') }}' + '/satusehat/locations/' + id, { method: 'DELETE', headers:{'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':'{{ csrf_token() }}' } })
            .then(r=>r.json()).then(j=>{ if(j.ok) table.ajax.reload(); else alert('Delete failed'); });
    });
});
</script>
@endsection

@endsection
