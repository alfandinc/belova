@extends('layouts.erm.app')
@section('title', 'Obat Mapping')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Obat Mapping (Visitation metode bayar → Obat metode bayar)</h4>
                    <button id="addMappingBtn" class="btn btn-primary">Tambah Mapping</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="obatMappingTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Visitation Metode Bayar</th>
                                    <th>Obat Metode Bayar</th>
                                    <th>Status</th>
                                    <th>Dibuat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="mappingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Mapping</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form id="mappingForm">
      <div class="modal-body">
        <input type="hidden" id="mappingId" name="mapping_id">
        <div class="form-group">
            <label>Visitation Metode Bayar</label>
            <select id="visitation_metode_bayar_id" name="visitation_metode_bayar_id" class="form-control">
                <option value="">-- Pilih --</option>
                @foreach($metodeBayars as $mb)
                    <option value="{{ $mb->id }}">{{ $mb->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Obat Metode Bayar</label>
            <select id="obat_metode_bayar_id" name="obat_metode_bayar_id" class="form-control">
                <option value="">-- Pilih --</option>
                @foreach($metodeBayars as $mb)
                    <option value="{{ $mb->id }}">{{ $mb->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group form-check">
            <input type="checkbox" id="is_active" name="is_active" class="form-check-input" checked>
            <label class="form-check-label" for="is_active">Aktif</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function(){
    const table = $('#obatMappingTable').DataTable({
        ajax: {
            url: '{{ route("erm.obat-mapping.index") }}',
            dataSrc: 'data'
        },
        columns: [
            { data: 'visitation_metode_bayar_name' },
            { data: 'obat_metode_bayar_name' },
            { data: 'is_active' },
            { data: 'created_at' },
            { data: 'aksi', orderable: false, searchable: false }
        ]
    });

    $('#addMappingBtn').on('click', function(){
        $('#mappingForm')[0].reset();
        $('#mappingId').val('');
        $('#mappingModal .modal-title').text('Tambah Mapping');
        $('#mappingModal').modal('show');
    });

    window.editMapping = function(id) {
        $.get('{{ url("/erm/obat-mapping") }}/' + id, function(data){
            $('#mappingId').val(data.id);
            $('#visitation_metode_bayar_id').val(data.visitation_metode_bayar_id);
            $('#obat_metode_bayar_id').val(data.obat_metode_bayar_id);
            $('#is_active').prop('checked', data.is_active);
            $('#mappingModal .modal-title').text('Edit Mapping');
            $('#mappingModal').modal('show');
        });
    }

    window.deleteMapping = function(id) {
        Swal.fire({
            title: 'Konfirmasi',
            text: 'Hapus mapping ini?',
            icon: 'warning',
            showCancelButton: true
        }).then((res)=>{
            if (res.value) {
                $.ajax({
                    url: '{{ url("/erm/obat-mapping") }}/' + id,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(){
                        table.ajax.reload();
                        Swal.fire('Dihapus','Mapping telah dihapus','success');
                    }
                });
            }
        });
    }

    $('#mappingForm').on('submit', function(e){
        e.preventDefault();
        const id = $('#mappingId').val();
        const url = id ? '{{ url("/erm/obat-mapping") }}/' + id : '{{ route("erm.obat-mapping.store") }}';
        const method = id ? 'PUT' : 'POST';
        const data = {
            visitation_metode_bayar_id: $('#visitation_metode_bayar_id').val(),
            obat_metode_bayar_id: $('#obat_metode_bayar_id').val(),
            is_active: $('#is_active').is(':checked') ? 1 : 0,
            _token: '{{ csrf_token() }}'
        };
        $.ajax({ url: url, type: method, data: data, success: function(){
            $('#mappingModal').modal('hide');
            table.ajax.reload();
            Swal.fire('Sukses','Mapping tersimpan','success');
        }, error: function(xhr){
            let msg = 'Terjadi kesalahan';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            Swal.fire('Error', msg, 'error');
        }});
    });
});
</script>
@endsection
