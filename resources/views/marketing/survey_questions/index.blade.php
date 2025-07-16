@extends('layouts.marketing.app')

@section('title', 'Pasien Data - Marketing')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection
@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-8">
                            <h4 class="card-title">Manage Survey Questions</h4>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex">
                                <select id="filterKlinik" class="form-control mr-2">
                                    <option value="">All Klinik</option>
                                    <option value="Klinik Pratama Belova Skin">Klinik Pratama Belova Skin</option>
                                    <option value="Klinik Utama Premiere Belova">Klinik Utama Premiere Belova</option>
                                </select>
                                <button class="btn btn-success ml-2" id="addBtn">Add Question</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered dt-responsive nowrap" id="questionsTable" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Question</th>
                                    <th>Type</th>
                                    <th>Options</th>
                                    <th>Order</th>
                                    <th>Klinik</th>
                                    <th style="width:180px;">Average Score</th>
                                    <th style="width:140px;">Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="questionModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLabel">Add/Edit Question</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="questionForm">
        <div class="modal-body">
            <input type="hidden" id="question_id" name="id">
            <div class="form-group">
                <label>Pertanyaan</label>
                <input type="text" class="form-control" name="question_text" required>
            </div>
            <div class="form-group">
                <label>Tipe</label>
                <select class="form-control" name="question_type" required>
                    <option value="emoji_scale">Emoji Scale</option>
                    <option value="multiple_choice">Multiple Choice</option>
                </select>
            </div>
            <div class="form-group">
                <label>Options (comma separated, for multiple choice)</label>
                <input type="text" class="form-control" name="options">
            </div>
            <div class="form-group">
                <label>Order</label>
                <input type="number" class="form-control" name="order">
            </div>
            <div class="form-group">
                <label>Klinik</label>
                <select class="form-control" name="klinik_name" required>
                    <option value="Klinik Utama Premiere Belova">Klinik Utama Premiere Belova</option>
                    <option value="Klinik Pratama Belova Skin">Klinik Pratama Belova Skin</option>
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
@endsection
@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
$(function() {
    var table = $('#questionsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ url('marketing/survey-questions/datatable') }}',
            data: function(d) {
                d.klinik = $('#filterKlinik').val();
            }
        },
        columns: [
            { data: 'id' },
            { data: 'question_text' },
            { data: 'question_type' },
            { data: 'options' },
            { data: 'order' },
            { data: 'klinik_name' },
            { data: 'average_score', searchable: false, orderable: false },
            { data: 'action', orderable: false, searchable: false }
        ],
        rowCallback: function(row, data) {
            if (data.klinik_name === 'Klinik Utama Premiere Belova') {
                $(row).css('background-color', '#e3f2fd'); // light blue
            } else if (data.klinik_name === 'Klinik Pratama Belova Skin') {
                $(row).css('background-color', '#ffe3f3'); // light pink
            } else {
                $(row).css('background-color', '');
            }
        }
    });
    $('#filterKlinik').change(function() {
        table.ajax.reload();
    });
    $('#addBtn').click(function() {
        $('#questionForm')[0].reset();
        $('#question_id').val('');
        $('#questionModal').modal('show');
    });
    $('#questionsTable').on('click', '.edit-btn', function() {
        var id = $(this).data('id');
        $.get('survey-questions/'+id, function(res) {
            var q = res.data;
            $('#question_id').val(q.id);
            $('[name=question_text]').val(q.question_text);
            $('[name=question_type]').val(q.question_type);
            $('[name=options]').val(q.options ? JSON.parse(q.options).join(',') : '');
            $('[name=order]').val(q.order);
            $('[name=klinik_name]').val(q.klinik_name);
            $('#questionModal').modal('show');
        });
    });
    $('#questionsTable').on('click', '.delete-btn', function() {
        if(confirm('Delete this question?')) {
            var id = $(this).data('id');
            $.ajax({
                url: 'survey-questions/'+id,
                type: 'DELETE',
                success: function() { table.ajax.reload(); }
            });
        }
    });
    $('#questionForm').submit(function(e) {
        e.preventDefault();
        var id = $('#question_id').val();
        var data = $(this).serializeArray();
        var options = $('[name=options]').val();
        var typeVal = $('[name=question_type]').val();
        data = data.filter(f => f.name !== 'options');
        if(typeVal === 'multiple_choice' && options) {
            // Send as JSON string array
            data.push({name:'options', value: JSON.stringify(options.split(',').map(s=>s.trim()))});
        } else if(options) {
            data.push({name:'options', value: options});
        }
        var url = 'survey-questions' + (id ? '/' + id : '');
        var type = id ? 'PUT' : 'POST';
        $.ajax({
            url: url,
            type: type,
            data: $.param(data),
            success: function() {
                $('#questionModal').modal('hide');
                table.ajax.reload();
            }
        });
    });
});
</script>
@endpush
