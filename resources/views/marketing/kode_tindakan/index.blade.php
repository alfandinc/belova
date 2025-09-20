@extends('layouts.marketing.app')

@section('title', 'Master Kode Tindakan')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Master Kode Tindakan</h4>
            <button class="btn btn-primary" id="btnAddKodeTindakan"><i class="mdi mdi-plus"></i> Tambah Kode Tindakan</button>
        </div>
        <div class="card-body">
            <table id="kodeTindakanTable" class="table table-bordered table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>HPP</th>
                        <th>Harga Jasmed</th>
                        <th>Harga Jual</th>
                        <th>Harga Bottom</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<!-- Make this modal non-dismissible by backdrop click or Escape; only the header X immediately closes -->
<div class="modal fade" id="kodeTindakanModal" tabindex="-1" role="dialog" aria-labelledby="kodeTindakanModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form id="kodeTindakanForm">
        <div class="modal-header">
          <h5 class="modal-title" id="kodeTindakanModalLabel">Tambah Kode Tindakan</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
                    <input type="hidden" id="kodeTindakanId" name="id">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="kode">Kode</label>
                            <input type="text" class="form-control" id="kode" name="kode" required>
                        </div>
                        <div class="form-group col-md-8">
                            <label for="nama">Nama</label>
                            <input type="text" class="form-control" id="nama" name="nama" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="hpp">HPP</label>
                            <input type="number" step="0.01" class="form-control" id="hpp" name="hpp">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="harga_jual">Harga Jual</label>
                            <input type="number" step="0.01" class="form-control" id="harga_jual" name="harga_jual">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="harga_bottom">Harga Bottom</label>
                            <input type="number" step="0.01" class="form-control" id="harga_bottom" name="harga_bottom">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="harga_jasmed">Harga Jasmed</label>
                            <input type="number" step="0.01" class="form-control" id="harga_jasmed" name="harga_jasmed">
                        </div>
                    </div>
                    <hr>
                    <label>Obat dan BHP</label>
                    <table class="table table-bordered" id="obatTable">
                        <thead>
                            <tr>
                                <th>Obat</th>
                                <th>Qty</th>
                                <th>Dosis</th>
                                <th>Satuan Dosis</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-success btn-sm" id="addObatRow">Tambah Obat</button>
        </div>
                <div class="modal-footer">
                    <!-- Footer cancel does not auto-dismiss; asks for confirmation -->
                    <button type="button" class="btn btn-secondary" id="kodeTindakanModalCancel">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#kodeTindakanTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('marketing.kode_tindakan.data') }}',
            type: 'GET'
        },
        columns: [
            { data: 'kode', name: 'kode' },
            { data: 'nama', name: 'nama' },
            { data: 'hpp', name: 'hpp', render: function(data){ return data === null ? '-' : $.fn.dataTable.render.number(',', '.', 2).display(data); } },
            { data: 'harga_jasmed', name: 'harga_jasmed', render: function(data){ return data === null ? '-' : $.fn.dataTable.render.number(',', '.', 2).display(data); } },
            { data: 'harga_jual', name: 'harga_jual', render: function(data){ return data === null ? '-' : $.fn.dataTable.render.number(',', '.', 2).display(data); } },
            { data: 'harga_bottom', name: 'harga_bottom', render: function(data){ return data === null ? '-' : $.fn.dataTable.render.number(',', '.', 2).display(data); } },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `<button class="btn btn-sm btn-warning btn-edit" data-id="${row.id}">Edit</button>
                            <button class="btn btn-sm btn-danger btn-delete" data-id="${row.id}">Hapus</button>`;
                }
            }
        ]
    });

    // Add Obat Row
    function obatRow(idx, obat = {}) {
        const satuanOptions = [
            'Mg', 'Ml', 'Gram', 'Tablet', 'Kapsul', 'Botol', 'Strip', 'Tube', 'Ampul', 'Sachet', 'Vial', 'Pcs', 'Lainnya'
        ];
        let satuanDropdown = `<select name="obats[${idx}][satuan_dosis]" class="form-control">
            <option value="">Pilih Satuan</option>`;
        satuanOptions.forEach(opt => {
            satuanDropdown += `<option value="${opt}"${obat.satuan_dosis === opt ? ' selected' : ''}>${opt}</option>`;
        });
        satuanDropdown += `</select>`;
        return `<tr>
            <td><select name="obats[${idx}][obat_id]" class="form-control obat-select" required style="width:100%"></select></td>
            <td><input type="number" name="obats[${idx}][qty]" class="form-control" min="1" value="${obat.qty || 1}" required></td>
            <td><input type="text" name="obats[${idx}][dosis]" class="form-control" value="${obat.dosis || ''}"></td>
            <td>${satuanDropdown}</td>
            <td><button type="button" class="btn btn-danger btn-sm remove-obat">Hapus</button></td>
        </tr>`;
    }

    function refreshObatRows() {
        $('#obatTable tbody tr').each(function(i, tr) {
            $(tr).find('select, input').each(function() {
                let name = $(this).attr('name');
                if (name) {
                    let newName = name.replace(/obats\[\d+\]/, `obats[${i}]`);
                    $(this).attr('name', newName);
                }
            });
        });
    }

    function initObatSelect2(context, selected = null) {
        $(context).find('.obat-select').select2({
            placeholder: 'Cari Obat',
            minimumInputLength: 2,
            ajax: {
                url: '/obat/search',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    if (Array.isArray(data.results)) {
                        return { results: data.results };
                    } else {
                        return { results: data };
                    }
                },
                cache: true
            },
            width: '100%',
            allowClear: true
        });
        if (selected) {
            let option = new Option(selected.text, selected.id, true, true);
            $(context).find('.obat-select').append(option).trigger('change');
        }
    }

    // Add new row
    $('#addObatRow').on('click', function() {
        let idx = $('#obatTable tbody tr').length;
        let $row = $(obatRow(idx));
        $('#obatTable tbody').append($row);
        initObatSelect2($row);
    });

    // Remove row
    $(document).on('click', '.remove-obat', function() {
        $(this).closest('tr').remove();
        refreshObatRows();
    });

    // Show modal for add
    $('#btnAddKodeTindakan').click(function() {
        $('#kodeTindakanForm')[0].reset();
        $('#kodeTindakanId').val('');
        $('#obatTable tbody').empty();
        $('#harga_jasmed').val('');
        $('#harga_jual').val('');
        $('#harga_bottom').val('');
        $('#kodeTindakanModalLabel').text('Tambah Kode Tindakan');
        $('#kodeTindakanModal').modal('show');
    });

    // Footer Cancel: ask confirmation before hiding since modal is non-dismissible
    $(document).on('click', '#kodeTindakanModalCancel', function() {
        Swal.fire({
            title: 'Tutup formulir?',
            text: 'Perubahan yang belum disimpan akan hilang. Yakin ingin menutup?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, tutup',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (result.value) {
                $('#kodeTindakanModal').modal('hide');
            }
        });
    });

    // Show modal for edit
    $('#kodeTindakanTable').on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        $.get('/marketing/kodetindakan/' + id, function(data) {
            $('#kodeTindakanId').val(data.id);
            $('#kode').val(data.kode);
            $('#nama').val(data.nama);
            $('#hpp').val(data.hpp);
            $('#harga_jasmed').val(data.harga_jasmed);
            $('#harga_jual').val(data.harga_jual);
            $('#harga_bottom').val(data.harga_bottom);
            $('#obatTable tbody').empty();
            if (data.obats && data.obats.length) {
                data.obats.forEach(function(obat, idx) {
                    let $row = $(obatRow(idx, obat));
                    $('#obatTable tbody').append($row);
                    initObatSelect2($row, {id: obat.obat_id, text: obat.obat_nama});
                });
            }
            $('#kodeTindakanModalLabel').text('Edit Kode Tindakan');
            $('#kodeTindakanModal').modal('show');
        });
    });

    // Save (add/edit)
    $('#kodeTindakanForm').submit(function(e) {
        e.preventDefault();
        var id = $('#kodeTindakanId').val();
        var url = id ? '/marketing/kodetindakan/' + id : '/marketing/kodetindakan';
        var method = id ? 'PUT' : 'POST';
        var formData = $(this).serialize();
        $.ajax({
            url: url,
            type: method,
            data: formData,
            success: function(res) {
                $('#kodeTindakanModal').modal('hide');
                table.ajax.reload();
                Swal.fire('Berhasil', 'Data berhasil disimpan', 'success');
            },
            error: function(xhr) {
                Swal.fire('Gagal', xhr.responseJSON.message || 'Terjadi kesalahan', 'error');
            }
        });
    });

    // Delete
    $('#kodeTindakanTable').on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Kode Tindakan?',
            text: 'Data yang dihapus tidak dapat dikembalikan!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '/marketing/kodetindakan/' + id,
                    type: 'DELETE',
                    data: {_token: '{{ csrf_token() }}'},
                    success: function(res) {
                        table.ajax.reload();
                        Swal.fire('Berhasil', 'Data berhasil dihapus', 'success');
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal', xhr.responseJSON.message || 'Terjadi kesalahan', 'error');
                    }
                });
            }
        });
    });
});

</script>
@endpush
