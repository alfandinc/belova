@extends('layouts.marketing.app')

@section('title', 'Biaya Konsultasi dan Lain Lain')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Biaya Konsultasi dan Lain Lain</h4>
            <button class="btn btn-primary" id="btnAddKonsultasi">
                <i class="mdi mdi-plus"></i> Tambah Data
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="konsultasiTable">
                    <thead>
                        <tr>
                            <th style="width: 6%;">No</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th style="width: 18%;">Harga</th>
                            <th style="width: 16%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($konsultasis as $index => $konsultasi)
                            <tr data-id="{{ $konsultasi->id }}">
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $konsultasi->nama }}</td>
                                <td>{{ $konsultasi->kategori ?: '-' }}</td>
                                <td data-order="{{ $konsultasi->harga }}">Rp {{ number_format($konsultasi->harga, 0, ',', '.') }}</td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-warning btn-edit"
                                        data-id="{{ $konsultasi->id }}"
                                        data-nama="{{ $konsultasi->nama }}"
                                        data-kategori="{{ $konsultasi->kategori }}"
                                        data-harga="{{ $konsultasi->harga }}"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-danger btn-delete"
                                        data-id="{{ $konsultasi->id }}"
                                        data-nama="{{ $konsultasi->nama }}"
                                    >
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="konsultasiModal" tabindex="-1" role="dialog" aria-labelledby="konsultasiModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="konsultasiForm">
                @csrf
                <input type="hidden" id="formMethod" value="POST">
                <input type="hidden" id="konsultasiId">
                <div class="modal-header">
                    <h5 class="modal-title" id="konsultasiModalLabel">Tambah Biaya Konsultasi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <input type="text" class="form-control" id="nama" name="nama" required>
                    </div>
                    <div class="form-group">
                        <label for="kategori">Kategori</label>
                        <input type="text" class="form-control" id="kategori" name="kategori" placeholder="Contoh: Konsultasi, Administrasi, Lain-lain">
                    </div>
                    <div class="form-group mb-0">
                        <label for="harga">Harga</label>
                        <input type="number" min="0" step="0.01" class="form-control" id="harga" name="harga" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#konsultasiTable').DataTable({
        pageLength: 25,
        order: [[2, 'asc'], [1, 'asc']],
    });

    function resetForm() {
        $('#konsultasiForm')[0].reset();
        $('#konsultasiId').val('');
        $('#formMethod').val('POST');
        $('#konsultasiModalLabel').text('Tambah Biaya Konsultasi');
    }

    function showValidationError(xhr, fallbackMessage) {
        let message = fallbackMessage;

        if (xhr.responseJSON) {
            if (xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }

            if (xhr.responseJSON.errors) {
                const firstError = Object.values(xhr.responseJSON.errors)[0];
                if (Array.isArray(firstError) && firstError.length) {
                    message = firstError[0];
                }
            }
        }

        Swal.fire('Gagal', message, 'error');
    }

    $('#btnAddKonsultasi').on('click', function () {
        resetForm();
        $('#konsultasiModal').modal('show');
    });

    $('.btn-edit').on('click', function () {
        resetForm();
        $('#konsultasiId').val($(this).data('id'));
        $('#nama').val($(this).data('nama'));
        $('#kategori').val($(this).data('kategori'));
        $('#harga').val($(this).data('harga'));
        $('#formMethod').val('PUT');
        $('#konsultasiModalLabel').text('Edit Biaya Konsultasi');
        $('#konsultasiModal').modal('show');
    });

    $('#konsultasiForm').on('submit', function (e) {
        e.preventDefault();

        const id = $('#konsultasiId').val();
        const method = $('#formMethod').val();
        const url = method === 'PUT'
            ? '{{ url('/marketing/biaya-konsultasi') }}/' + id
            : '{{ route('marketing.konsultasi.store') }}';
        const payload = {
            _token: '{{ csrf_token() }}',
            nama: $('#nama').val(),
            kategori: $('#kategori').val(),
            harga: $('#harga').val(),
        };

        if (method === 'PUT') {
            payload._method = 'PUT';
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: payload,
            success: function (response) {
                Swal.fire('Berhasil', response.message, 'success').then(function () {
                    window.location.reload();
                });
            },
            error: function (xhr) {
                showValidationError(xhr, 'Data gagal disimpan.');
            }
        });
    });

    $('.btn-delete').on('click', function () {
        const id = $(this).data('id');
        const nama = $(this).data('nama');

        Swal.fire({
            title: 'Hapus data?',
            text: 'Data "' + nama + '" akan dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: '{{ url('/marketing/biaya-konsultasi') }}/' + id,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'DELETE'
                },
                success: function (response) {
                    Swal.fire('Berhasil', response.message, 'success').then(function () {
                        window.location.reload();
                    });
                },
                error: function (xhr) {
                    showValidationError(xhr, 'Data gagal dihapus.');
                }
            });
        });
    });
});
</script>
@endpush