@extends('layouts.bcl.app')

@section('content')
<!-- Page-Title -->
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="row">
                <div class="col">
                    <h4 class="page-title">Wifi Kamar</h4>
                    <span>{{ config('app.name') }}</span>
                </div><!--end col-->
                <div class="col-auto align-self-center">
                    <button class="btn btn-sm btn-dark waves-effect waves-light" id="btn-add">
                        <i class="mdi mdi-plus"></i> Tambah Wifi
                    </button>
                </div><!--end col-->
            </div><!--end row-->
        </div><!--end page-title-box-->
    </div><!--end col-->
</div><!--end row-->

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive-sm">
                    <div id="tb_wifi_wrapper" class="dataTables_wrapper dt-bootstrap4 no-footer">
                        <div class="row">
                            <div class="col-sm-12">
                                <table class="table table-sm table-hover mb-0 dataTable no-footer" id="wifi-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Room</th>
                                            <th>SSID</th>
                                            <th>Password</th>
                                            <th>Active</th>
                                            <th>Notes</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5"></div>
                            <div class="col-sm-12 col-md-7"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="wifiModal" tabindex="-1" role="dialog" aria-labelledby="wifiModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-dark">
        <h6 class="modal-title m-0 text-white" id="modalTitle">Tambah Wifi</h6>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="wifiForm">
            @csrf
            <input type="hidden" name="id" id="wifi-id">
            <div class="row">
                <div class="col-md-6 col-sm-12">
                    <label>Room</label>
                    <select name="room_id" id="room_id" class="form-control" required>
                        <option value="">-- Pilih Room --</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}">{{ $room->room_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-sm-12">
                    <label>SSID</label>
                    <input type="text" name="ssid" id="ssid" class="form-control" required>
                </div>
                <div class="col-md-6 col-sm-12 mt-2">
                    <label>Password</label>
                    <input type="text" name="password" id="password" class="form-control">
                </div>
                <div class="col-md-6 col-sm-12 mt-2">
                    <label>Active</label>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="active" name="active" checked>
                        <label class="form-check-label" for="active">Active</label>
                    </div>
                </div>
                <div class="col-12 mt-2">
                    <label>Notes</label>
                    <textarea name="notes" id="notes" class="form-control"></textarea>
                </div>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
        <button type="button" id="saveBtn" class="btn btn-primary btn-sm">Simpan</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('pagescript')
<script>
    $(function(){
        var table = $('#wifi-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{!! route('bcl.roomwifi.data') !!}',
            columns: [
                { data: 'id', name: 'id' },
                { data: 'room_name', name: 'room_name' },
                { data: 'ssid', name: 'ssid' },
                { data: 'password', name: 'password' },
                { data: 'active', name: 'active', render: function(data){ return data ? 'Yes' : 'No'; } },
                { data: 'notes', name: 'notes' },
                { data: 'actions', name: 'actions', orderable:false, searchable:false }
            ]
        });

        $('#btn-add').on('click', function(){
            $('#wifiForm')[0].reset();
            $('#wifi-id').val('');
            $('#modalTitle').text('Tambah Wifi');
            $('#wifiModal').modal('show');
        });

        $('#saveBtn').on('click', function(){
            var id = $('#wifi-id').val();
            var url = id ? '{!! url('bcl/wifi/update') !!}/'+id : '{!! route('bcl.roomwifi.store') !!}';

            // Build data object and ensure checkbox is sent as 1/0
            var array = $('#wifiForm').serializeArray();
            var data = {};
            $.each(array, function() {
                data[this.name] = this.value;
            });
            data.active = $('#active').is(':checked') ? 1 : 0;

            $.post(url, data).done(function(res){
                $('#wifiModal').modal('hide');
                table.ajax.reload();
            }).fail(function(xhr){
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : xhr.responseText;
                alert('Error: '+msg);
            });
        });

        $('#wifi-table').on('click', '.btn-edit', function(){
            var id = $(this).data('id');
            $.get('{!! url('bcl/wifi/edit') !!}/'+id).done(function(res){
                var d = res.data;
                $('#wifi-id').val(d.id);
                $('#room_id').val(d.room_id);
                $('#ssid').val(d.ssid);
                $('#password').val(d.password);
                $('#notes').val(d.notes);
                $('#active').prop('checked', d.active == 1);
                $('#modalTitle').text('Edit Wifi');
                $('#wifiModal').modal('show');
            });
        });

        $('#wifi-table').on('click', '.btn-delete', function(){
            if(!confirm('Hapus record ini?')) return;
            var id = $(this).data('id');
            $.get('{!! url('bcl/wifi/delete') !!}/'+id).done(function(){
                table.ajax.reload();
            });
        });
    });
</script>
@stop
