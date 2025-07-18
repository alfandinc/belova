@extends('layouts.akreditasi.app')
@section('title', 'Dashboard | Akreditasi Belova')
@section('navbar')
    @include('layouts.akreditasi.navbar')
@endsection  
@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h2 class="font-weight-bold text-dark">Standar: <span class="text-primary">{{ $standar->name }}</span></h2>
    </div>
    <ul class="nav nav-pills mb-4" id="epTab" role="tablist" style="border-radius: 0.5rem; padding: 0.5rem;">
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
            <div class="mb-4 p-4" >
                <div class="row align-itms-center mb-3">
                    <div class="col-md-8">
                        <div class="mb-2">
                            <small class="text-uppercase text-secondary">Elemen Penilaian</small><br>
                            <span class="text-dark">{{ $ep->elemen_penilaian}}</span>
                        </div>
                        <div class="mb-2">
                            <small class="text-uppercase text-secondary">Kelengkapan Bukti</small><br>
                            <span class="text-dark">{{ $ep->kelengkapan_bukti }}</span>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-right">
                        <div class="mb-2">
                            <small class="text-uppercase text-secondary">Skor Maksimal</small><br>
                            <span class="display-4 text-success" style="font-size:2rem;">{{ $ep->skor_maksimal }}</span>
                        </div>
                        <div class="mb-2">
                            <button data-ep-id="{{ $ep->id }}" class="btn btn-primary btn-md rounded-pill font-weight-bold shadow uploadDocBtn mt-2 px-4" style="letter-spacing:0.5px;">
                                <span class="fa fa-cloud-upload-alt mr-2"></span> Upload Document
                            </button>
                        </div>
                    </div>
                </div>
                <div class="ep-documents" data-ep-id="{{ $ep->id }}">
                    <table class="table table-sm table-hover docTable mb-0" id="docTable-{{ $ep->id }}" data-ajax-url="{{ route('akreditasi.ep', $ep->id) }}">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:40px;">No</th>
                                <th>Filename</th>
                                <th>Preview</th>
                                <th>Created At</th>
                                <th>Updated At</th>
                                <th style="width:90px;">Action</th>
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
          <div class="modal-header border-0 pb-2">
            <h5 class="modal-title text-primary"><span class="fa fa-upload mr-1"></span> Upload Document</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form id="uploadDocForm" enctype="multipart/form-data">
            <div class="modal-body pt-0">
              @csrf
              <input type="hidden" name="ep_id" id="modalEpId">
              <div class="form-group mb-2">
                <label for="customFilename" class="small">Custom Filename</label>
                <input type="text" class="form-control form-control-sm" name="custom_filename" id="customFilename" placeholder="Enter filename (optional)">
              </div>
              <div class="form-group mb-2">
                <label for="document" class="small">File (image, document, or video)</label>
                <input type="file" class="form-control-file" name="document" id="document" accept="image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,video/*" required>
              </div>
            </div>
            <div class="modal-footer border-0 pt-0">
              <button type="submit" class="btn btn-primary btn-sm"><span class="fa fa-upload mr-1"></span> Upload</button>
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
