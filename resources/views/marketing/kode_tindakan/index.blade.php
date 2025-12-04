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
                        <th style="width:10%">Kode</th>
                        <th style="width:40%">Nama</th>
                        <th style="width:40%">Obat / Jumlah</th>
                        <!-- Removed from UI for now: HPP / Harga Jasmed / Harga Jual / Harga Bottom
                        <th>HPP</th>
                        <th>Harga Jasmed</th>
                        <th>Harga Jual</th>
                        <th>Harga Bottom</th>
                        -->
                        <th style="width:10%">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<!-- Make this modal non-dismissible by backdrop click or Escape; only the header X immediately closes -->
<div class="modal fade" id="kodeTindakanModal" tabindex="-1" role="dialog" aria-labelledby="kodeTindakanModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl" role="document" style="max-width:1100px;">
    <div class="modal-content">
    <form id="kodeTindakanForm" novalidate>
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
                    <!-- HPP and price inputs temporarily removed from UI. Uncomment to restore.
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
                    -->
                    <hr>
                    <label>Obat dan BHP</label>
                    <table class="table table-bordered" id="obatTable">
                        <thead>
                            <tr>
                                <th style="width:60%">Obat dan BHP</th>
                                <th style="width:12%">Jumlah</th>
                                <th style="width:24%">Satuan Dosis</th>
                                <th style="width:4%"></th>
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
        // enforce column widths so Nama and Obat/Jumlah share the same proportion
        columnDefs: [
            { width: '10%', targets: 0 },
            { width: '40%', targets: 1 },
            { width: '40%', targets: 2 },
            { width: '10%', targets: 3 }
        ],
        columns: [
            { data: 'kode', name: 'kode' },
            { data: 'nama', name: 'nama' },
            { data: 'obats_summary', name: 'obats_summary', orderable: false, searchable: false, defaultContent: '-' },
            // HPP / Harga fields removed from UI — keep code here commented for easy restore
            /* { data: 'hpp', name: 'hpp', render: function(data){ return data === null ? '-' : $.fn.dataTable.render.number(',', '.', 2).display(data); } },
            { data: 'harga_jasmed', name: 'harga_jasmed', render: function(data){ return data === null ? '-' : $.fn.dataTable.render.number(',', '.', 2).display(data); } },
            { data: 'harga_jual', name: 'harga_jual', render: function(data){ return data === null ? '-' : $.fn.dataTable.render.number(',', '.', 2).display(data); } },
            { data: 'harga_bottom', name: 'harga_bottom', render: function(data){ return data === null ? '-' : $.fn.dataTable.render.number(',', '.', 2).display(data); } }, */
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
        // Satuan will be shown as read-only text pulled from the selected Obat
        const satuanValue = obat.satuan_dosis || obat.satuan || '';
        let satuanField = `<input type="text" name="obats[${idx}][satuan_dosis]" class="form-control satuan-text" value="${satuanValue}" readonly>`;

            return `<tr>
            <td style="width:60%"><select name="obats[${idx}][obat_id]" class="form-control obat-select" required style="width:100%"></select></td>
            <td style="width:12%"><input type="number" name="obats[${idx}][qty]" class="form-control" min="0.01" step="0.01" inputmode="decimal" pattern="^\\d+(\\.\\d{1,2})?$" data-rule-min="0.01" data-msg-min="Jumlah harus minimal 0.01" value="${obat.qty || 1}" required></td>
            <td style="width:24%">${satuanField}</td>
            <td style="width:4%"><button type="button" class="btn btn-danger btn-sm remove-obat">Hapus</button></td>
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
                    // Normalize results and strip packaging suffixes like " - 14 Pcs"
                    function cleanResult(item) {
                        if (!item) return item;
                        // If server returned label as 'text', use it; otherwise try 'nama' or 'name'
                        let raw = item.text || item.nama || item.name || '';
                        // Remove trailing patterns like " - 14 Pcs", " - 100", etc. (case-insensitive)
                        let display = raw.replace(/\s*-\s*\d+\s*(pcs)?$/i, '').trim();
                        // Preserve original raw text in a separate prop in case we need it
                        item._raw_text = raw;
                        // Overwrite text used by select2 with cleaned display
                        item.text = display || raw;
                        return item;
                    }

                    if (Array.isArray(data.results)) {
                        return { results: data.results.map(cleanResult) };
                    } else if (Array.isArray(data)) {
                        return { results: data.map(cleanResult) };
                    } else if (data && Array.isArray(data.data)) {
                        // In case server returns { data: [...] }
                        return { results: data.data.map(cleanResult) };
                    } else if (data) {
                        return { results: [cleanResult(data)] };
                    } else {
                        return { results: [] };
                    }
                },
                cache: true
            },
            width: '100%',
            allowClear: true
        });
        // When an obat is selected, populate the satuan field from returned data
        $(context).find('.obat-select').on('select2:select', function(e) {
            const data = e.params.data || {};
            const satuan = data.satuan || data.satuan_dosis || '';
            const $row = $(this).closest('tr');
            $row.find('.satuan-text').val(satuan);
        });

        // When cleared, empty the satuan field
        $(context).find('.obat-select').on('select2:clear', function() {
            const $row = $(this).closest('tr');
            $row.find('.satuan-text').val('');
        });

        if (selected) {
            // selected may include satuan property provided by server when editing
            // Clean common packaging suffixes (e.g. " - 14 Pcs") for the displayed text
            let rawText = selected.text || '';
            let displayText = rawText.replace(/\s*-\s*\d+\s*pcs$/i, '');
            let option = new Option(displayText, selected.id, true, true);
            $(context).find('.obat-select').append(option).trigger('change');
            const satuan = selected.satuan || selected.satuan_dosis || '';
            $(context).find('.satuan-text').val(satuan);
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
            // HPP / Harga fields are removed from UI; these were previously set here:
            // $('#hpp').val(data.hpp);
            // $('#harga_jasmed').val(data.harga_jasmed);
            // $('#harga_jual').val(data.harga_jual);
            // $('#harga_bottom').val(data.harga_bottom);
            $('#obatTable tbody').empty();
            if (data.obats && data.obats.length) {
                data.obats.forEach(function(obat, idx) {
                    let $row = $(obatRow(idx, obat));
                    $('#obatTable tbody').append($row);
                    // Pass unit info so the select2 initializer can populate the satuan field
                    initObatSelect2($row, {id: obat.obat_id, text: obat.obat_nama, satuan: (obat.satuan_dosis || obat.satuan || '')});
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
