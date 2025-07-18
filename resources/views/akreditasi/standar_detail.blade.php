@extends('layouts.akreditasi.app')
@section('title', 'Dashboard | Akreditasi Belova')
@section('navbar')
    @include('layouts.akreditasi.navbar')
@endsection  
@section('content')
<div class="container mt-4">
    <h2>Standar: {{ $standar->name }}</h2>
    <ul class="nav nav-tabs" id="epTab" role="tablist">
        @foreach($standar->eps as $ep)
            <li class="nav-item" role="presentation">
                <a class="nav-link @if($loop->first) active @endif" id="ep-tab-{{ $ep->id }}" data-toggle="tab" href="#ep-{{ $ep->id }}" role="tab" aria-controls="ep-{{ $ep->id }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                    {{ $ep->name }}
                </a>
            </li>
        @endforeach
    </ul>
    <div class="tab-content" id="epTabContent">
        @foreach($standar->eps as $ep)
        <div class="tab-pane fade @if($loop->first) show active @endif" id="ep-{{ $ep->id }}" role="tabpanel" aria-labelledby="ep-tab-{{ $ep->id }}">
            <div class="mt-3">
                <h4>EP: {{ $ep->name }}</h4>
                <p><strong>Elemen Penilaian:</strong> {{ $ep->elemen_penilaian }}</p>
                <p>Kelengkapan Bukti: {{ $ep->kelengkapan_bukti }}</p>
                <p>Skor Maksimal: {{ $ep->skor_maksimal }}</p>
                <hr>
                <button data-ep-id="{{ $ep->id }}" class="btn btn-success mb-2 uploadDocBtn">Upload Document</button>
                <div class="ep-documents" data-ep-id="{{ $ep->id }}">
                    <table class="table table-bordered table-striped docTable" id="docTable-{{ $ep->id }}" data-ajax-url="{{ route('akreditasi.ep', $ep->id) }}">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Filename</th>
                                <th>Preview</th>
                                <th>Created At</th>
                                <th>Updated At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Upload Modal (shared for all EPs) -->
    <div class="modal fade" id="uploadDocModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Upload Document</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form id="uploadDocForm" enctype="multipart/form-data">
            <div class="modal-body">
              @csrf
              <input type="hidden" name="ep_id" id="modalEpId">
              <div class="form-group">
                <label for="customFilename">Custom Filename</label>
                <input type="text" class="form-control" name="custom_filename" id="customFilename" placeholder="Enter filename (optional)">
              </div>
              <div class="form-group">
                <label for="document">File (image, document, or video)</label>
                <input type="file" name="document" id="document" accept="image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,video/*" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-success">Upload</button>
            </div>
          </form>
        </div>
      </div>
    </div>
</div>
@endsection
@section('scripts')
<script>

$(function() {
    // Only initialize DataTable when tab is shown for the first time
    var initializedTables = {};
    function initDocTable(epId, ajaxUrl) {
        initializedTables[epId] = $('#docTable-' + epId).DataTable({
            processing: true,
            serverSide: true,
            ajax: ajaxUrl,
            columns: [
                {
                    data: null,
                    name: 'rownum',
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                { data: 'filename', name: 'filename' },
                { data: 'preview', name: 'preview', orderable: false, searchable: false },
                { data: 'created_at', name: 'created_at' },
                { data: 'updated_at', name: 'updated_at' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });
    }

    // Initialize the first (active) tab's table on page load
    var firstTable = $('.tab-pane.active .docTable');
    if (firstTable.length) {
        var epId = firstTable.attr('id').replace('docTable-', '');
        var ajaxUrl = firstTable.data('ajax-url');
        if (ajaxUrl) {
            initDocTable(epId, ajaxUrl);
        }
    }

    // On tab shown, initialize DataTable if not already
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr('href'); // e.g. #ep-2
        var table = $(target).find('.docTable');
        if (table.length) {
            var epId = table.attr('id').replace('docTable-', '');
            var ajaxUrl = table.data('ajax-url');
            if (!initializedTables[epId] && ajaxUrl) {
                initDocTable(epId, ajaxUrl);
            } else if (initializedTables[epId]) {
                initializedTables[epId].columns.adjust().draw(false);
            }
        }
    });

    // Show upload modal and set EP ID
    $('.uploadDocBtn').click(function() {
        var epId = $(this).data('ep-id');
        $('#uploadDocForm')[0].reset();
        $('#modalEpId').val(epId);
        $('#uploadDocModal').modal('show');
    });

    // Upload document with SweetAlert2
    $('#uploadDocForm').submit(function(e) {
        e.preventDefault();
        var epId = $('#modalEpId').val();
        var formData = new FormData(this);
        formData.append('custom_filename', $('#customFilename').val());
        Swal.fire({
            title: 'Uploading...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        $.ajax({
            url: '/akreditasi/ep/' + epId + '/document',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                $('#uploadDocForm')[0].reset();
                $('#uploadDocModal').modal('hide');
                $('#docTable-' + epId).DataTable().ajax.reload();
                Swal.fire('Success', 'Document uploaded!', 'success');
            },
            error: function() {
                Swal.fire('Error', 'Upload failed!', 'error');
            }
        });
    });

    // Delete document with SweetAlert2 (delegated for all tables)
    $('.docTable').on('click', '.delete-btn', function() {
        var table = $(this).closest('table');
        var epId = table.attr('id').replace('docTable-', '');
        var data = table.DataTable().row($(this).parents('tr')).data();
        Swal.fire({
            title: 'Delete document?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.value) {
                Swal.fire({
                    title: 'Deleting...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                $.ajax({
                    url: '/akreditasi/document/' + data.id,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        table.DataTable().ajax.reload();
                        Swal.fire('Deleted!', 'Document deleted.', 'success');
                    },
                    error: function() {
                        Swal.fire('Error', 'Delete failed!', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endsection
