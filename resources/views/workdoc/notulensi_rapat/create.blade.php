@extends('layouts.workdoc.app')
@section('title', 'Tambah Notulensi Rapat')
@section('navbar')
    @include('layouts.workdoc.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    @if(isset($notulensi))
                        Detail Notulensi Rapat
                    @else
                        Tambah Notulensi Rapat
                    @endif
                </div>
                <div class="card-body">
                    <form id="notulensi-form">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="title">Judul</label>
                                <input type="text" name="title" id="title" class="form-control" required
                                    value="{{ isset($notulensi) ? $notulensi->title : '' }}" {{ isset($notulensi) ? 'readonly' : '' }}>
                            </div>
                            <div class="col-md-4">
                                <label for="date">Tanggal</label>
                                <input type="date" name="date" id="date" class="form-control" required
                                    value="{{ isset($notulensi) ? $notulensi->date : '' }}" {{ isset($notulensi) ? 'readonly' : '' }}>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="notulen">Notulen</label>
                                <small class="text-muted d-block mb-1">Notulen adalah informasi yang dapat dilihat oleh semua karyawan.</small>
                                <textarea name="notulen" id="notulen" class="form-control summernote" required {{ isset($notulensi) ? 'readonly' : '' }}>{{ isset($notulensi) ? $notulensi->notulen : '' }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="memo">Memo</label>
                                <small class="text-muted d-block mb-1">Memo hanya dapat dilihat oleh manager.</small>
                                <textarea name="memo" id="memo" class="form-control summernote" {{ isset($notulensi) ? 'readonly' : '' }}>{{ isset($notulensi) ? $notulensi->memo : '' }}</textarea>
                            </div>
                        </div>
                        @if(!isset($notulensi))
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
    @if(isset($notulensi))
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">To-Do List</div>
                <div class="card-body">
                    @if(!isset($notulensi) || !isset($readonly))
                    <form id="todo-form" class="mb-3">
                        <div class="row align-items-end">
                            <div class="col-md-6">
                                <input type="text" name="task" class="form-control" placeholder="Task" required>
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-control">
                                    <option value="pending">Pending</option>
                                    <option value="done">Done</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="due_date" class="form-control" placeholder="Due Date">
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-success w-100">Add To-Do</button>
                            </div>
                        </div>
                    </form>
                    @endif
                    <table id="todos-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Task</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-bs4.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-bs4.min.css" rel="stylesheet">
<script>
$(document).ready(function() {
    $('.summernote').summernote({
        height: 200
    });
    $('#notulensi-form').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route('workdoc.notulensi-rapat.store') }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    window.location.href = '{{ route('workdoc.notulensi-rapat.index') }}';
                }
            },
            error: function(xhr) {
                alert('Gagal menyimpan data!');
            }
        });
    });

    @if(isset($notulensi))
    // To-Do DataTable
    $('#todos-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('workdoc.notulensi-rapat.todos', $notulensi->id) }}',
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'task', name: 'task' },
            { data: 'status', name: 'status' },
            { data: 'due_date', name: 'due_date' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });

    // Add To-Do
    $('#todo-form').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route('workdoc.notulensi-rapat.todos.store', $notulensi->id) }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    $('#todos-table').DataTable().ajax.reload();
                    $('#todo-form')[0].reset();
                }
            },
            error: function(xhr) {
                alert('Gagal menambah to-do!');
            }
        });
    });

    // Approve To-Do
    $('#todos-table').on('click', '.approve-todo', function() {
        var todoId = $(this).data('id');
        $.ajax({
            url: '{{ url('workdoc/notulensi-rapat/'.$notulensi->id.'/todos') }}/' + todoId + '/approve',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                if (res.success) {
                    $('#todos-table').DataTable().ajax.reload();
                }
            },
            error: function(xhr) {
                alert('Gagal approve to-do!');
            }
        });
    });

    // Delete To-Do
    $('#todos-table').on('click', '.delete-todo', function() {
        var todoId = $(this).data('id');
        $.ajax({
            url: '{{ url('workdoc/notulensi-rapat/'.$notulensi->id.'/todos') }}/' + todoId,
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                if (res.success) {
                    $('#todos-table').DataTable().ajax.reload();
                }
            },
            error: function(xhr) {
                alert('Gagal menghapus to-do!');
            }
        });
    });
    @endif
});
</script>
@endsection
