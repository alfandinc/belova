@extends('layouts.erm.app')

@section('title', 'Widgets')

@section('navbar')
    @include('dashboard.navbar')
@endsection

@section('content')
    <div class="container-fluid mt-4">
      <div class="row">
        <div class="col-md-7">
          <div class="card">
            <div class="card-body">
              <div class="d-flex justify-content-between mb-3">
                <h4 class="card-title">Dashboard Widgets</h4>
                <div>
                  <button id="btn-add-widget" class="btn btn-success">Add Widget</button>
                </div>
              </div>

              <table id="widgets-table" class="table table-striped table-bordered w-100">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Component Path</th>
                    <th>Description</th>
                    <th>Active</th>
                    <th>Created</th>
                    <th>Actions</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>
        </div>

        <div class="col-md-5">
          <div class="card">
            <div class="card-body">
              <div class="d-flex justify-content-between mb-3 align-items-center">
                <h4 class="card-title mb-0">Position Mappings</h4>
                <button id="btn-add-mapping" class="btn btn-primary">Add Mapping</button>
              </div>

              <table id="mappings-table" class="table table-sm table-striped table-bordered w-100">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Widget</th>
                    <th>Position</th>
                    <th>Row</th>
                    <th>Order</th>
                    <th>Cols</th>
                    <th>Actions</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="widgetModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Widget</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <form id="widgetForm">
              <input type="hidden" name="id" />
              <div class="form-group">
                <label>Name</label>
                <input type="text" name="widget_name" class="form-control" required />
              </div>
              <div class="form-group">
                <label>Widget Key / Component Path</label>
                <input type="text" name="component_path" class="form-control" placeholder="welcome_widget" />
                <small class="form-text text-muted">
                    Cukup isi nama sederhana seperti <strong>welcome_widget</strong>. Sistem akan otomatis simpan ke folder default widget dashboard.
                    Kalau perlu, tetap bisa isi path lengkap seperti <strong>dashboard.custom_widgets.welcome_widget</strong>.
                </small>
              </div>
              <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control"></textarea>
              </div>
              <div class="form-group form-check">
                <input type="hidden" name="is_active" value="0" />
                <input type="checkbox" name="is_active" class="form-check-input" value="1" checked />
                <label class="form-check-label">Active</label>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="button" id="saveWidgetBtn" class="btn btn-primary">Save</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Map modal -->
    <div class="modal fade" id="mapModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Position Mapping</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <form id="mapForm">
              <input type="hidden" name="mapping_id" />
              <div class="form-group">
                <label>Widget</label>
                <select name="widget_id" class="form-control" required>
                  <option value="">Choose widget</option>
                  @foreach($widgets as $widget)
                    <option value="{{ $widget->id }}">{{ $widget->widget_name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group">
                <label>Position</label>
                <select name="position_id" class="form-control" required>
                  <option value="">Choose position</option>
                  @foreach($positions as $position)
                    <option value="{{ $position->id }}">{{ $position->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-row">
                <div class="form-group col-md-4">
                  <label>Row</label>
                  <input type="number" name="row_index" class="form-control" value="1" min="1" />
                </div>
                <div class="form-group col-md-4">
                  <label>Order Index</label>
                  <input type="number" name="order_index" class="form-control" value="0" />
                </div>
                <div class="form-group col-md-4">
                  <label>Column Span</label>
                  <select name="column_span" class="form-control" required>
                    <option value="12">12 - Full Width</option>
                    <option value="6">6 - Half Width</option>
                    <option value="4">4 - One Third</option>
                  </select>
                </div>
              </div>
              <small class="form-text text-muted">
                Pilih ukuran widget agar layout dashboard lebih mudah diatur per posisi.
              </small>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="button" id="saveMapBtn" class="btn btn-primary">Save Mapping</button>
          </div>
        </div>
      </div>
    </div>

@endsection

@section('scripts')
<script>
$(function(){
    var table = $('#widgets-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{!! route('dashboard.widgets.data') !!}',
        columns: [
            {data: 'id', name: 'id'},
            {data: 'widget_name', name: 'widget_name'},
            {data: 'component_path', name: 'component_path'},
            {data: 'description', name: 'description'},
            {data: 'is_active', name: 'is_active', render: function(d){ return d ? 'Yes' : 'No'; }},
            {data: 'created_at', name: 'created_at'},
            {data: 'actions', orderable:false, searchable:false}
        ]
    });

    var mappingTable = $('#mappings-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{!! route('dashboard.widget-mappings.data') !!}',
        columns: [
            {data: 'id', name: 'id'},
            {data: 'widget_name', name: 'widget_name'},
            {data: 'position_name', name: 'position_name'},
          {data: 'row_index', name: 'row_index'},
            {data: 'order_index', name: 'order_index'},
            {data: 'column_span', name: 'column_span'},
            {data: 'actions', orderable:false, searchable:false}
        ]
    });

    $('#btn-add-widget').on('click', function(){
        $('#widgetForm')[0].reset();
        $('#widgetForm input[name=id]').val('');
        $('#widgetModal').modal('show');
    });

    $('#saveWidgetBtn').on('click', function(){
        var form = $('#widgetForm');
        var id = form.find('input[name=id]').val();
        var url = id ? ('/dashboard/widgets/' + id) : ('/dashboard/widgets');
        var method = id ? 'PUT' : 'POST';
        var data = form.serialize();
        $.ajax({url: url, method: method, data: data, success: function(){
            $('#widgetModal').modal('hide');
            table.ajax.reload(null, false);
        }, error: function(xhr){
          var message = 'Error saving widget';
          if (xhr.responseJSON) {
            if (xhr.responseJSON.message) {
              message = xhr.responseJSON.message;
            }
            if (xhr.responseJSON.errors) {
              message += '\n' + Object.values(xhr.responseJSON.errors).flat().join('\n');
            }
          }
          alert(message);
        }});
    });

    $(document).on('click', '.js-edit-widget', function(){
        var row = table.row($(this).closest('tr')).data();
        if(row){
            var f = $('#widgetForm');
            f.find('input[name=id]').val(row.id);
            f.find('input[name=widget_name]').val(row.widget_name);
            f.find('input[name=component_path]').val(row.component_path);
            f.find('textarea[name=description]').val(row.description);
            f.find('input[name=is_active]').prop('checked', row.is_active);
            $('#widgetModal').modal('show');
        }
    });

    $(document).on('click', '.js-delete-widget', function(){
        if(!confirm('Delete widget?')) return;
        var id = $(this).data('id');
        $.ajax({url: '/dashboard/widgets/' + id, method: 'DELETE', success:function(){ table.ajax.reload(null,false); }, error:function(){ alert('Delete failed'); }});
    });

    $('#btn-add-mapping').on('click', function(){
      resetMapForm();
      $('#mapModal').modal('show');
    });

    $('#saveMapBtn').on('click', function(){
        var form = $('#mapForm');
        var mappingId = form.find('input[name=mapping_id]').val();
        var url = mappingId
            ? '/dashboard/widget-mappings/' + mappingId
            : '/dashboard/widget-mappings';
        var method = mappingId ? 'PUT' : 'POST';

        $.ajax({
          url: url,
          method: method,
          data: form.serialize(),
          success: function(){
            mappingTable.ajax.reload(null, false);
            $('#mapModal').modal('hide');
            resetMapForm();
          },
          error: function(xhr){
            var message = 'Error saving mapping';
            if (xhr.responseJSON) {
              if (xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
              }
              if (xhr.responseJSON.errors) {
                message += '\n' + Object.values(xhr.responseJSON.errors).flat().join('\n');
              }
            }
            alert(message);
          }
        });
    });

    $(document).on('click', '.js-edit-mapping', function(){
      var row = mappingTable.row($(this).closest('tr')).data();
      if (!row) return;
      fillMapForm(row);
      $('#mapModal').modal('show');
    });

    function fillMapForm(mapping) {
      var form = $('#mapForm');
      form.find('input[name=mapping_id]').val(mapping.id);
      form.find('select[name=widget_id]').val(mapping.widget_id);
      form.find('select[name=position_id]').val(mapping.position_id);
      form.find('input[name=row_index]').val(mapping.row_index || 1);
      form.find('input[name=order_index]').val(mapping.order_index);
      form.find('select[name=column_span]').val(String(mapping.column_span));
      $('#saveMapBtn').text('Update Mapping');
    }

    function resetMapForm() {
      var form = $('#mapForm');
      form[0].reset();
      form.find('input[name=mapping_id]').val('');
      form.find('input[name=row_index]').val(1);
      form.find('input[name=order_index]').val(0);
      form.find('select[name=column_span]').val('12');
      $('#saveMapBtn').text('Save Mapping');
    }

    $(document).on('click', '.js-remove-mapping', function(){
      var id = $(this).data('id');
      $.ajax({url: '/dashboard/widget-mappings/' + id, method: 'DELETE', success:function(){
        mappingTable.ajax.reload(null, false);
      }});
    });
});
</script>
@endsection
