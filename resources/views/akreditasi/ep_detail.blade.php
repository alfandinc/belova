@extends('layouts.akreditasi.app')
@section('title', 'Dashboard | Akreditasi Belova')
@section('navbar')
    @include('layouts.akreditasi.navbar')
@endsection  
@section('content')
<div class="container mt-4">
    <h2>EP Detail: {{ $ep->name }}</h2>
    <p><strong>Elemen Penilaian:</strong> {{ $ep->elemen_penilaian }}</p>
    <p>Kelengkapan Bukti: {{ $ep->kelengkapan_bukti }}</p>
    <p>Skor Maksimal: {{ $ep->skor_maksimal }}</p>
    <hr>
    <button id="uploadDocBtn" class="btn btn-success mb-2">Upload Document</button>
    <!-- Upload Modal -->
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
    <hr>
    <h4>Documents</h4>
    <table id="docTable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Filename</th>
                <th>Preview</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th>Action</th>
            </tr>
        </thead>
    </table>
</div>
@endsection
@section('scripts')
<script>
$(function() {
    var docTable = $('#docTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('akreditasi.ep', $ep->id) }}',
        columns: [
            { data: 'id', name: 'id' },
            { data: 'filename', name: 'filename' },
            { data: 'preview', name: 'preview', orderable: false, searchable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'updated_at', name: 'updated_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    // Show upload modal
    $('#uploadDocBtn').click(function() {
        $('#uploadDocForm')[0].reset();
        $('#uploadDocModal').modal('show');
    });

    // Upload document with SweetAlert2
    $('#uploadDocForm').submit(function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        // Add custom filename to FormData
        formData.append('custom_filename', $('#customFilename').val());
        Swal.fire({
            title: 'Uploading...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        $.ajax({
            url: '{{ route('akreditasi.ep.document.upload', $ep->id) }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                $('#uploadDocForm')[0].reset();
                $('#uploadDocModal').modal('hide');
                docTable.ajax.reload();
                Swal.fire('Success', 'Document uploaded!', 'success');
            },
            error: function() {
                Swal.fire('Error', 'Upload failed!', 'error');
            }
        });
    });

    // Delete document with SweetAlert2
    $('#docTable').on('click', '.delete-btn', function() {
        var data = docTable.row($(this).parents('tr')).data();
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
                    url: '{{ url('akreditasi/document') }}/' + data.id,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        docTable.ajax.reload();
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
