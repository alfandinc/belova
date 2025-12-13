@extends('layouts.erm.app')
@section('title','Aturan Pakai')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title">Master Aturan Pakai</h4>
            <button id="addAturanBtn" class="btn btn-primary">Tambah</button>
        </div>
        <div class="card-body">
            <table id="aturanTable" class="table table-bordered">
                <thead>
                    <tr><th>Template</th><th>Status</th><th>Dibuat</th><th>Aksi</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="aturanModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Aturan</h5><button class="close" data-dismiss="modal">&times;</button></div>
      <form id="aturanForm">
      <div class="modal-body">
        <input type="hidden" id="aturanId">
        <div class="form-group">
            <label>Template</label>
            <textarea id="template" class="form-control" rows="4" required></textarea>
        </div>
        <div class="form-group form-check"><input type="checkbox" id="is_active" class="form-check-input" checked><label class="form-check-label">Aktif</label></div>
      </div>
      <div class="modal-footer"><button class="btn btn-secondary" data-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan</button></div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
$(function(){
    var table = $('#aturanTable');
    var dt = table.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("erm.aturan-pakai.index") }}',
            type: 'GET'
        },
        columns: [
            { data: 'template', name: 'template' },
            { data: 'is_active', name: 'is_active' },
            { data: 'created_at', name: 'created_at' },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
        ],
        order: [[2, 'desc']]
    });

    $('#addAturanBtn').click(function(){ $('#aturanForm')[0].reset(); $('#aturanId').val(''); $('#aturanModal').modal('show'); });

    window.editAturan = function(id){ $.get('{{ url('/erm/aturan-pakai') }}/'+id, function(data){ $('#aturanId').val(data.id); $('#template').val(data.template); $('#is_active').prop('checked', data.is_active); $('#aturanModal').modal('show'); }); }
    window.deleteAturan = function(id){ if(!confirm('Hapus?')) return; $.ajax({url: '{{ url('/erm/aturan-pakai') }}/'+id, type: 'DELETE', data:{_token:'{{ csrf_token() }}'}, success:function(){ dt.ajax.reload(); alert('Dihapus');}}); }

    $('#aturanForm').submit(function(e){
        e.preventDefault();
        const id = $('#aturanId').val();
        const url = id ? '{{ url('/erm/aturan-pakai') }}/'+id : '{{ route('erm.aturan-pakai.store') }}';
        const method = id ? 'PUT' : 'POST';
        $.ajax({
            url: url,
            type: method,
            data: { template: $('#template').val(), is_active: $('#is_active').is(':checked')?1:0, _token: '{{ csrf_token() }}' },
            success:function(){ $('#aturanModal').modal('hide'); dt.ajax.reload(); } ,
            error:function(xhr){ alert('Error'); }
        });
    });
});
</script>
@endsection
