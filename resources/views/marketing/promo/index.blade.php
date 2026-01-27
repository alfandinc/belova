@extends('layouts.marketing.app')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Promos</h4>
                    <div class="d-flex mb-3 justify-content-end align-items-center">
                        <div class="mr-2">
                            <button id="btn-add" class="btn btn-success">Add Promo</button>
                        </div>
                        <div style="min-width:260px; max-width:40%;">
                            <input type="text" id="filter-periode" class="form-control" />
                        </div>
                    </div>
                    <table id="promo-table" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Name</th>
                                <th>Periode</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="promoModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Promo</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form id="promo-form">
          <div class="modal-body">
                <input type="hidden" name="id" id="promo-id">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="promo-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="promo-description" class="form-control"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Start Date</label>
                        <input type="date" name="start_date" id="promo-start" class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                        <label>End Date</label>
                        <input type="date" name="end_date" id="promo-end" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="promo-status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save</button>
          </div>
          </form>
        </div>
      </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    $(function(){
            var table = $('#promo-table').DataTable({
            processing: true,
            serverSide: true,
                ajax: {
                    url: '{!! route('marketing.promo.data') !!}',
                    data: function(d){
                        d.start_date = $('#filter-periode').data('start') || null;
                        d.end_date = $('#filter-periode').data('end') || null;
                    }
                },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'date_range', name: 'date_range', orderable: false, searchable: false},
                {data: 'status', name: 'status'},
                {data: 'actions', name: 'actions', orderable: false, searchable: false},
            ]
        });

        // initialize daterangepicker with default this month
        var start = moment().startOf('month');
        var end = moment().endOf('month');
        function setPickerRange(s,e){
            $('#filter-periode').val(s.format('DD/MM/YYYY') + ' - ' + e.format('DD/MM/YYYY'));
            $('#filter-periode').data('start', s.format('YYYY-MM-DD'));
            $('#filter-periode').data('end', e.format('YYYY-MM-DD'));
        }

        $('#filter-periode').daterangepicker({
            startDate: start,
            endDate: end,
            locale: { format: 'DD/MM/YYYY' },
            opens: 'left'
        }, function(s,e){
            setPickerRange(s,e);
            table.ajax.reload();
        });

        // set initial values and reload table
        setPickerRange(start,end);
        table.ajax.reload();

        // open add modal
        $('#btn-add').on('click', function(){
            $('#promo-form')[0].reset();
            $('#promo-id').val('');
            $('#promoModal').modal('show');
        });

        // submit form (create or update)
        $('#promo-form').on('submit', function(e){
            e.preventDefault();
            var id = $('#promo-id').val();
            var url = id ? '/marketing/promo/' + id : '/marketing/promo';
            var method = id ? 'PUT' : 'POST';
            $.ajax({
                url: url,
                method: method,
                data: $(this).serialize(),
                success: function(res){
                    $('#promoModal').modal('hide');
                    table.ajax.reload();
                },
                error: function(xhr){
                    alert('Error saving data');
                }
            });
        });

        // edit
        $('#promo-table').on('click', '.btn-edit', function(){
            var id = $(this).data('id');
            $.ajax({
                url: '/marketing/promo/'+id,
                method: 'GET',
                success: function(res){
                    $('#promo-id').val(res.id);
                    $('#promo-name').val(res.name);
                    $('#promo-description').val(res.description);
                    $('#promo-start').val(res.start_date);
                    $('#promo-end').val(res.end_date);
                    $('#promo-status').val(res.status);
                    $('#promoModal').modal('show');
                },
                error: function(){
                    alert('Unable to fetch data for edit');
                }
            });
        });

        // delete
        $('#promo-table').on('click', '.btn-delete', function(){
            if(!confirm('Delete this promo?')) return;
            var id = $(this).data('id');
            $.ajax({url: '/marketing/promo/'+id, method: 'DELETE', success: function(){
                table.ajax.reload();
            }, error: function(){ alert('Delete failed'); }});
        });

        // native date inputs used; no JS datepicker initialization needed
    });
</script>
@endsection
