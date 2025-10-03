@extends('layouts.marketing.app')
@section('title', 'Master Merchandise')
@section('navbar')
    @include('layouts.marketing.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Master Merchandise</h4>
                    <button id="btn-add" class="btn btn-primary">Tambah Merchandise</button>
                </div>
                <div class="card-body">
                    <table id="merch-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="merchModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Merchandise</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <form id="merchForm">
          <input type="hidden" id="merch_id">
          <div class="form-group">
            <label>Name</label>
            <input type="text" id="name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea id="description" class="form-control"></textarea>
          </div>
          <div class="form-group">
            <label>Price</label>
            <input type="number" step="0.01" id="price" class="form-control">
          </div>
          <div class="form-group">
            <label>Stock</label>
            <input type="number" id="stock" class="form-control">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="button" id="saveMerch" class="btn btn-primary">Simpan</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
$(function(){
    let table = $('#merch-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{!! route("marketing.master_merchandise.data") !!}',
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'description' },
            { data: 'price' },
            { data: 'stock' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#btn-add').click(function(){
        $('#merchForm')[0].reset();
        $('#merch_id').val('');
        $('#merchModal').modal('show');
    });

    $('#merch-table').on('click', '.btn-edit', function(){
        let id = $(this).data('id');
        $.get('/marketing/master-merchandise/'+id+'/edit', function(res){
            $('#merch_id').val(res.id);
            $('#name').val(res.name);
            $('#description').val(res.description);
            $('#price').val(res.price);
            $('#stock').val(res.stock);
            $('#merchModal').modal('show');
        });
    });

    $('#saveMerch').click(function(){
        let id = $('#merch_id').val();
        let url = id ? '/marketing/master-merchandise/'+id : '/marketing/master-merchandise';
        let type = id ? 'PUT' : 'POST';
        $.ajax({
            url: url,
            type: type,
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                name: $('#name').val(),
                description: $('#description').val(),
                price: $('#price').val(),
                stock: $('#stock').val()
            },
            success: function(){
                $('#merchModal').modal('hide');
                table.ajax.reload();
            }
        });
    });

    $('#merch-table').on('click', '.btn-delete', function(){
        if(!confirm('Hapus?')) return;
        let id = $(this).data('id');
        $.ajax({
            url: '/marketing/master-merchandise/'+id,
            type: 'DELETE',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function(){ table.ajax.reload(); }
        });
    });
});
</script>
@endsection
