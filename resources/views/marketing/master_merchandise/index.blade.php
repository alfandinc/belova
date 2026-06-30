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
                              <th>Current Stock</th>
                              <th>Monthly Limit</th>
                              <th>Used This Month</th>
                              <th>Remaining</th>
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
            <label>Monthly Limit Stock</label>
            <input type="number" id="monthly_limit_stock" class="form-control" min="0">
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

<div class="modal fade" id="stockModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Stock Merchandise</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <form id="stockForm">
          <input type="hidden" id="stock_merch_id">
          <div class="form-group">
            <label>Merchandise</label>
            <input type="text" id="stock_merch_name" class="form-control" readonly>
          </div>
          <div class="form-group">
            <label>Qty</label>
            <input type="number" id="stock_qty" class="form-control" min="1" value="1" required>
          </div>
          <div class="form-group">
            <label>Notes</label>
            <textarea id="stock_notes" class="form-control" rows="3" placeholder="Optional note"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="button" id="saveStock" class="btn btn-success">Tambah Stock</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="stockHistoryModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Kartu Stok Merchandise</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <strong id="history_merch_name">-</strong>
        </div>
        <div class="table-responsive">
          <table class="table table-bordered table-sm" id="stock-history-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Type</th>
                <th>Qty</th>
                <th>Current Stock</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="5" class="text-center text-muted">Belum ada data.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
$(function(){
    function getAjaxError(xhr, fallback) {
        if (xhr && xhr.responseJSON) {
            return xhr.responseJSON.message || xhr.responseJSON.error || fallback;
        }

        return fallback;
    }

    function renderStockHistory(rows) {
      let $tbody = $('#stock-history-table tbody');
      $tbody.empty();

      if (!rows || !rows.length) {
        $tbody.html('<tr><td colspan="5" class="text-center text-muted">Belum ada data.</td></tr>');
        return;
      }

      rows.forEach(function(row) {
        let badgeClass = row.type === 'in' ? 'success' : 'danger';
        let typeLabel = row.type === 'in' ? 'IN' : 'OUT';
        $tbody.append(
          '<tr>' +
            '<td>' + (row.tanggal || '-') + '</td>' +
            '<td><span class="badge badge-' + badgeClass + '">' + typeLabel + '</span></td>' +
            '<td>' + (row.qty || 0) + '</td>' +
            '<td>' + (row.current_stock || 0) + '</td>' +
            '<td>' + (row.notes || '-') + '</td>' +
          '</tr>'
        );
      });
    }

    let table = $('#merch-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{!! route("marketing.master_merchandise.data") !!}',
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'description' },
            { data: 'price' },
          { data: 'current_stock', defaultContent: 0 },
          { data: 'monthly_limit_stock', render: function(data){ return data === null || data === '' ? 'Unlimited' : data; } },
          { data: 'used_this_month', defaultContent: 0 },
          { data: 'remaining_monthly_stock', render: function(data, type, row){ return row.monthly_limit_stock === null || row.monthly_limit_stock === '' ? 'Unlimited' : data; } },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#btn-add').click(function(){
        $('#merchForm')[0].reset();
        $('#merch_id').val('');
        $('#merchModal').modal('show');
    });

    $('#merch-table').on('click', '.btn-stock', function(){
      $('#stockForm')[0].reset();
      $('#stock_merch_id').val($(this).data('id'));
      $('#stock_merch_name').val($(this).data('name'));
      $('#stock_qty').val(1);
      $('#stockModal').modal('show');
    });

    $('#merch-table').on('click', '.btn-history', function(){
      let id = $(this).data('id');
      let name = $(this).data('name');
      $('#history_merch_name').text(name || '-');
      $('#stock-history-table tbody').html('<tr><td colspan="5" class="text-center text-muted">Memuat...</td></tr>');
      $('#stockHistoryModal').modal('show');

      $.get('/marketing/master-merchandise/' + id + '/stock-history', function(resp){
        $('#history_merch_name').text((resp.merchandise && resp.merchandise.name) || name || '-');
        renderStockHistory(resp.data || []);
      }).fail(function(xhr){
        $('#stock-history-table tbody').html('<tr><td colspan="5" class="text-center text-danger">' + getAjaxError(xhr, 'Gagal memuat kartu stok') + '</td></tr>');
      });
    });

    $('#merch-table').on('click', '.btn-edit', function(){
        let id = $(this).data('id');
        $.get('/marketing/master-merchandise/'+id+'/edit', function(res){
            $('#merch_id').val(res.id);
            $('#name').val(res.name);
            $('#description').val(res.description);
            $('#price').val(res.price);
          $('#monthly_limit_stock').val(res.monthly_limit_stock);
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
                monthly_limit_stock: $('#monthly_limit_stock').val()
            },
            success: function(){
                $('#merchModal').modal('hide');
                table.ajax.reload();
            },
            error: function(xhr){
              alert(getAjaxError(xhr, 'Gagal menyimpan merchandise'));
            }
        });
    });

        $('#saveStock').click(function(){
          let id = $('#stock_merch_id').val();
          $.ajax({
            url: '/marketing/master-merchandise/' + id + '/add-stock',
            type: 'POST',
            data: {
              _token: $('meta[name="csrf-token"]').attr('content'),
              qty: $('#stock_qty').val(),
              notes: $('#stock_notes').val()
            },
            success: function(){
              $('#stockModal').modal('hide');
              table.ajax.reload(null, false);
            },
            error: function(xhr){
              alert(getAjaxError(xhr, 'Gagal menambah stock merchandise'));
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
            success: function(){ table.ajax.reload(); },
            error: function(xhr){ alert(getAjaxError(xhr, 'Gagal menghapus merchandise')); }
        });
    });
});
</script>
@endsection
