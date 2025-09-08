@extends('layouts.workdoc.app')
@section('title', 'Tambah Notulensi Rapat')
@section('navbar')
    @include('layouts.workdoc.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
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
                        <div class="form-group mb-3">
                            <label for="title">Judul</label>
                            <input type="text" name="title" id="title" class="form-control" required
                                value="{{ isset($notulensi) ? $notulensi->title : '' }}" {{ isset($notulensi) ? 'readonly' : '' }}>
                        </div>
                        <div class="form-group mb-3">
                            <label for="date">Tanggal</label>
                            <input type="date" name="date" id="date" class="form-control" required
                                value="{{ isset($notulensi) ? $notulensi->date : '' }}" {{ isset($notulensi) ? 'readonly' : '' }}>
                        </div>
                        <div class="form-group mb-3">
                            <label for="notulen">Notulen</label>
                            <textarea name="notulen" id="notulen" class="form-control summernote" required {{ isset($notulensi) ? 'readonly' : '' }}>{{ isset($notulensi) ? $notulensi->notulen : '' }}</textarea>
                        </div>
                        @if(!isset($notulensi))
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
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
});
</script>
@endsection
