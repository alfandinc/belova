@extends('layouts.erm.app')
@section('title', 'Farmasi | Mutasi Stok')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Mutasi Stok</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-3 mb-2 mb-md-0">
                            <select class="form-control" id="filter_gudang">
                                <option value="">Semua Gudang</option>
                                @foreach($gudangs as $gudang)
                                    <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <select class="form-control" id="filter_jenis_mutasi">
                                <option value="">Semua Jenis</option>
                                <option value="masuk">Masuk</option>
                                <option value="keluar">Keluar</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <select class="form-control" id="filter_status">
                                <option value="">Semua Status</option>
                                <option value="done">Done</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3 text-md-right">
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalMutasiStok">
                                <i class="fas fa-plus"></i> Buat Mutasi
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered dt-responsive w-100" id="mutasi-stok-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Tanggal Input</th>
                                    <th>Nomor Mutasi</th>
                                    <th>Gudang</th>
                                    <th>Jenis</th>
                                    <th>Item</th>
                                    <th>User</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMutasiStok" tabindex="-1" role="dialog" aria-labelledby="modalMutasiStokLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMutasiStokLabel">Buat Mutasi Stok</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-mutasi-stok" action="{{ route('erm.mutasi-stok.store') }}" method="POST">
                @csrf
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gudang_id">Gudang <span class="text-danger">*</span></label>
                                <select class="form-control" name="gudang_id" id="gudang_id" required>
                                    <option value="">Pilih Gudang</option>
                                    @foreach($gudangs as $gudang)
                                        <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="jenis_mutasi">Jenis Mutasi <span class="text-danger">*</span></label>
                                <select class="form-control" name="jenis_mutasi" id="jenis_mutasi" required>
                                    <option value="">Pilih Jenis Mutasi</option>
                                    <option value="masuk">Masuk</option>
                                    <option value="keluar">Keluar</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tanggal_mutasi">Tanggal Mutasi <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal_mutasi" id="tanggal_mutasi" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tanggal_input_preview">Tanggal Input</label>
                                <input type="text" class="form-control" id="tanggal_input_preview" readonly placeholder="Otomatis saat disimpan">
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mb-3" id="mutasi-info-box">
                        Pilih gudang dan jenis mutasi terlebih dahulu. Setiap item akan langsung memperbarui stok gudang dan tercatat ke kartu stok menggunakan keterangan pada item.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm" id="mutasi-stok-items-table">
                            <thead>
                                <tr>
                                    <th style="width: 42%;">Obat</th>
                                    <th style="width: 18%;">Stok Gudang</th>
                                    <th style="width: 16%;">Jumlah</th>
                                    <th style="width: 18%;">Keterangan</th>
                                    <th style="width: 6%;">
                                        <button type="button" class="btn btn-sm btn-primary" id="add-item-row">Tambah</button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetailMutasiStok" tabindex="-1" role="dialog" aria-labelledby="modalDetailMutasiStokLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetailMutasiStokLabel">Detail Mutasi Stok</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detail-mutasi-stok-body"></div>
            <div class="modal-footer">
                <div class="mr-auto" id="detail-mutasi-stok-actions"></div>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var isEditMode = false;

    function getCurrentTimestampLabel() {
        var now = new Date();
        var day = String(now.getDate()).padStart(2, '0');
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var year = now.getFullYear();
        var hours = String(now.getHours()).padStart(2, '0');
        var minutes = String(now.getMinutes()).padStart(2, '0');
        return day + '/' + month + '/' + year + ' ' + hours + ':' + minutes;
    }

    function getCurrentDateValue() {
        var now = new Date();
        var day = String(now.getDate()).padStart(2, '0');
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var year = now.getFullYear();
        return year + '-' + month + '-' + day;
    }

    var table = $('#mutasi-stok-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('erm.mutasi-stok.data') }}",
            data: function (d) {
                d.gudang_id = $('#filter_gudang').val();
                d.jenis_mutasi = $('#filter_jenis_mutasi').val();
                d.status = $('#filter_status').val();
            }
        },
        pageLength: 25,
        order: [[0, 'desc']],
        drawCallback: function () {
            feather.replace();
        },
        columns: [
            { data: 'tanggal', name: 'tanggal_mutasi' },
            { data: 'tanggal_input', name: 'tanggal_input' },
            { data: 'nomor_mutasi', name: 'nomor_mutasi' },
            { data: 'gudang_nama', name: 'gudang.nama', orderable: false },
            { data: 'jenis_label', name: 'jenis_mutasi', searchable: false },
            { data: 'item_summary', name: 'items.obat.nama', orderable: false, searchable: false },
            { data: 'user_name', name: 'user.name', orderable: false },
            { data: 'status_label', name: 'status', searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    $('#filter_gudang, #filter_jenis_mutasi, #filter_status').on('change', function () {
        table.draw();
    });

    $('#gudang_id, #jenis_mutasi').select2({
        dropdownParent: $('#modalMutasiStok'),
        width: '100%'
    });

    function resetFormState() {
        isEditMode = false;
        $('#form-mutasi-stok')[0].reset();
        $('#edit_id').val('');
        $('#modalMutasiStokLabel').text('Buat Mutasi Stok');
        $('#tanggal_mutasi').val(getCurrentDateValue());
        $('#tanggal_input_preview').val(getCurrentTimestampLabel());
        $('#gudang_id, #jenis_mutasi').val(null).trigger('change');
        $('#mutasi-stok-items-table tbody').empty();
    }

    function updateInfoBox() {
        var jenis = $('#jenis_mutasi').val();
        var message = 'Pilih gudang dan jenis mutasi terlebih dahulu. Setiap item akan langsung memperbarui stok gudang dan tercatat ke kartu stok menggunakan keterangan pada item.';

        if (jenis === 'keluar') {
            message = 'Mutasi keluar akan mengurangi stok gudang dengan urutan batch terdekat kedaluwarsa terlebih dahulu. Keterangan item akan disalin ke kartu stok.';
        }

        if (jenis === 'masuk') {
            message = 'Mutasi masuk akan menambah stok ke gudang yang dipilih. Keterangan item akan disalin ke kartu stok.';
        }

        $('#mutasi-info-box').text(message);
    }

    function getLookupParams(params) {
        return {
            q: params.term,
            gudang_id: $('#gudang_id').val(),
            jenis_mutasi: $('#jenis_mutasi').val()
        };
    }

    function formatObat(obat) {
        if (obat.loading) {
            return obat.text;
        }

        var kode = obat.kode_obat ? '<div class="small text-muted">' + obat.kode_obat + '</div>' : '';
        return $(
            '<div>' +
                '<div>' + obat.nama + '</div>' +
                kode +
                '<div class="small text-muted">Stok gudang: ' + obat.stok_display + '</div>' +
            '</div>'
        );
    }

    function formatObatSelection(obat) {
        if (!obat || !obat.id) {
            return obat && obat.text ? obat.text : 'Cari obat';
        }

        return obat.nama + ' - Stok: ' + obat.stok_display;
    }

    function initItemSelect2($select) {
        $select.select2({
            dropdownParent: $('#modalMutasiStok'),
            width: '100%',
            placeholder: 'Cari obat...',
            minimumInputLength: 1,
            ajax: {
                url: "{{ route('erm.mutasi-stok.obat') }}",
                dataType: 'json',
                delay: 250,
                data: getLookupParams,
                processResults: function (data) {
                    return { results: data.results };
                }
            },
            templateResult: formatObat,
            templateSelection: formatObatSelection
        });

        $select.on('select2:select', function (e) {
            var obat = e.params.data;
            var $row = $(this).closest('tr');
            $row.find('.item-stok').text(obat.stok_display || '0');
            $row.find('.item-satuan').text(obat.satuan || '');
            $row.find('.item-jumlah').attr('data-max-stock', obat.stok || 0);
        });
    }

    function addItemRow(itemData) {
        var gudangId = $('#gudang_id').val();
        var jenisMutasi = $('#jenis_mutasi').val();

        if (!gudangId || !jenisMutasi) {
            alert('Pilih gudang dan jenis mutasi terlebih dahulu.');
            return;
        }

        var rowId = Date.now() + Math.floor(Math.random() * 1000);
        var rowHtml = '' +
            '<tr>' +
                '<td><select class="form-control item-obat" name="items[' + rowId + '][obat_id]" required><option value="">Pilih Obat</option></select></td>' +
                '<td><span class="item-stok text-muted">-</span></td>' +
                '<td>' +
                    '<div class="input-group">' +
                        '<input type="number" step="0.01" min="0.01" class="form-control item-jumlah" name="items[' + rowId + '][jumlah]" required value="1">' +
                        '<div class="input-group-append"><span class="input-group-text item-satuan"></span></div>' +
                    '</div>' +
                '</td>' +
                '<td><input type="text" class="form-control" name="items[' + rowId + '][keterangan]" maxlength="255"></td>' +
                '<td><button type="button" class="btn btn-sm btn-danger btn-remove-item">Hapus</button></td>' +
            '</tr>';

        var $row = $(rowHtml);
        $('#mutasi-stok-items-table tbody').append($row);
        var $select = $row.find('.item-obat');
        initItemSelect2($select);

        if (itemData) {
            var selectedText = itemData.nama || itemData.obat_nama;
            var option = new Option(selectedText, itemData.obat_id, true, true);
            $select.append(option).trigger('change');
            $select.trigger({
                type: 'select2:select',
                params: {
                    data: {
                        id: itemData.obat_id,
                        nama: selectedText,
                        text: selectedText,
                        satuan: itemData.satuan || '',
                        stok: itemData.stok || 0,
                        stok_display: itemData.stok_display || '-'
                    }
                }
            });

            $row.find('.item-jumlah').val(itemData.jumlah_raw || itemData.jumlah || 1);
            $row.find('input[name*="[keterangan]"]').val(itemData.keterangan || '');
            $row.find('.item-jumlah').attr('data-original-qty', itemData.jumlah_raw || itemData.jumlah || 0);
        }
    }

    $('#modalMutasiStok').on('shown.bs.modal', function () {
        if ($('#mutasi-stok-items-table tbody tr').length === 0) {
            addItemRow();
        }
    }).on('hidden.bs.modal', function () {
        resetFormState();
        updateInfoBox();
    });

    $('#add-item-row').on('click', function () {
        addItemRow();
    });

    $(document).on('click', '.btn-remove-item', function () {
        $(this).closest('tr').remove();
    });

    $('#gudang_id, #jenis_mutasi').on('change', function () {
        $('#mutasi-stok-items-table tbody').empty();
        updateInfoBox();
    });

    $('#form-mutasi-stok').on('submit', function (e) {
        e.preventDefault();

        var jenisMutasi = $('#jenis_mutasi').val();
        var gudangId = $('#gudang_id').val();
        var editId = $('#edit_id').val();
        var valid = true;
        var items = [];

        if (!gudangId || !jenisMutasi) {
            alert('Gudang dan jenis mutasi wajib diisi.');
            return;
        }

        $('#mutasi-stok-items-table tbody tr').each(function () {
            var $row = $(this);
            var obatId = $row.find('.item-obat').val();
            var jumlah = parseFloat($row.find('.item-jumlah').val() || '0');
            var maxStock = parseFloat($row.find('.item-jumlah').attr('data-max-stock') || '0');
            var keterangan = $row.find('input[name*="[keterangan]"]').val();

            if (!obatId || jumlah <= 0) {
                valid = false;
                return false;
            }

            if (!isEditMode && jenisMutasi === 'keluar' && jumlah > maxStock) {
                valid = false;
                alert('Jumlah keluar melebihi stok gudang pada salah satu item.');
                return false;
            }

            items.push({
                obat_id: obatId,
                jumlah: jumlah,
                keterangan: keterangan
            });
        });

        if (!valid || items.length === 0) {
            if (items.length === 0) {
                alert('Tambahkan minimal satu item obat.');
            }
            return;
        }

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                gudang_id: gudangId,
                jenis_mutasi: jenisMutasi,
                tanggal_mutasi: $('#tanggal_mutasi').val(),
                edit_id: editId || null,
                items: items
            },
            success: function (response) {
                alert(response.message);
                $('#modalMutasiStok').modal('hide');
                table.draw();
            },
            error: function (xhr) {
                var message = 'Terjadi kesalahan saat menyimpan mutasi stok.';

                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors) {
                        var errors = [];
                        $.each(xhr.responseJSON.errors, function (key, value) {
                            errors = errors.concat(value);
                        });
                        message = errors.join('\n');
                    }
                }

                alert(message);
            }
        });
    });

    $(document).on('click', '.btn-detail', function () {
        var id = $(this).data('id');

        $.get("{{ url('erm/mutasi-stok') }}/" + id, function (response) {
            var jenisBadge = response.jenis_mutasi === 'masuk'
                ? '<span class="badge badge-success">Masuk</span>'
                : '<span class="badge badge-danger">Keluar</span>';

            var itemsHtml = response.items.map(function (item, index) {
                var satuan = item.satuan ? ' ' + item.satuan : '';
                var keterangan = item.keterangan ? item.keterangan : '-';

                return '' +
                    '<tr>' +
                        '<td>' + (index + 1) + '</td>' +
                        '<td>' + item.obat_nama + '</td>' +
                        '<td>' + item.jumlah + satuan + '</td>' +
                        '<td>' + keterangan + '</td>' +
                    '</tr>';
            }).join('');

            var statusLabel = response.status;
            if (response.status === 'cancelled' && response.cancelled_at) {
                statusLabel += ' pada ' + response.cancelled_at;
            }

            var revisionInfo = response.revised_from
                ? '<div class="row mb-3"><div class="col-md-12"><strong>Revisi Dari:</strong><br>' + response.revised_from + '</div></div>'
                : '';
            var cancelInfo = response.cancelled_by
                ? '<div class="row mb-3"><div class="col-md-12"><strong>Dibatalkan Oleh:</strong><br>' + response.cancelled_by + '</div></div>'
                : '';

            var html = '' +
                '<div class="row mb-3">' +
                    '<div class="col-md-4"><strong>Nomor Mutasi:</strong><br>' + response.nomor_mutasi + '</div>' +
                    '<div class="col-md-4"><strong>Tanggal Mutasi:</strong><br>' + (response.tanggal_mutasi_display || '-') + '</div>' +
                    '<div class="col-md-4"><strong>Tanggal Input:</strong><br>' + (response.tanggal_input || '-') + '</div>' +
                '</div>' +
                '<div class="row mb-3">' +
                    '<div class="col-md-4"><strong>Gudang:</strong><br>' + (response.gudang || '-') + '</div>' +
                    '<div class="col-md-4"><strong>Jenis:</strong><br>' + jenisBadge + '</div>' +
                    '<div class="col-md-4"><strong>User:</strong><br>' + (response.user || '-') + '</div>' +
                '</div>' +
                '<div class="row mb-3">' +
                    '<div class="col-md-12"><strong>Status:</strong><br>' + statusLabel + '</div>' +
                '</div>' +
                revisionInfo +
                cancelInfo +
                '<div class="table-responsive">' +
                    '<table class="table table-bordered table-sm mb-0">' +
                        '<thead>' +
                            '<tr><th>#</th><th>Obat</th><th>Jumlah</th><th>Keterangan</th></tr>' +
                        '</thead>' +
                        '<tbody>' + itemsHtml + '</tbody>' +
                    '</table>' +
                '</div>';

            var actionHtml = '<a href="' + response.print_url + '" target="_blank" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-print"></i> Cetak</a>';
            if (response.can_edit) {
                actionHtml += '<button type="button" class="btn btn-warning btn-sm mr-2 btn-edit" data-id="' + response.id + '"><i class="fas fa-edit"></i> Ubah</button>';
            }
            if (response.can_cancel) {
                actionHtml += '<button type="button" class="btn btn-danger btn-sm btn-cancel" data-id="' + response.id + '"><i class="fas fa-ban"></i> Batalkan</button>';
            }

            $('#detail-mutasi-stok-body').html(html);
            $('#detail-mutasi-stok-actions').html(actionHtml);
            $('#modalDetailMutasiStok').modal('show');
        });
    });

    $(document).on('click', '.btn-cancel', function () {
        var id = $(this).data('id');

        if (!confirm('Batalkan mutasi stok ini? Sistem akan membalikkan stok dan kartu stok transaksi ini.')) {
            return;
        }

        $.ajax({
            url: "{{ url('erm/mutasi-stok') }}/" + id + "/cancel",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function (response) {
                alert(response.message);
                $('#modalDetailMutasiStok').modal('hide');
                table.draw();
            },
            error: function (xhr) {
                alert((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Gagal membatalkan mutasi stok.');
            }
        });
    });

    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');

        $.get("{{ url('erm/mutasi-stok') }}/" + id, function (response) {
            isEditMode = true;
            $('#detail-mutasi-stok-actions').empty();
            $('#modalDetailMutasiStok').modal('hide');
            $('#modalMutasiStokLabel').text('Ubah Mutasi Stok');
            $('#edit_id').val(response.id);
            $('#tanggal_mutasi').val(response.tanggal_mutasi || getCurrentDateValue());
            $('#tanggal_input_preview').val(response.tanggal_input || getCurrentTimestampLabel());
            $('#gudang_id').val(response.gudang_id).trigger('change');
            $('#jenis_mutasi').val(response.jenis_mutasi).trigger('change');
            $('#mutasi-stok-items-table tbody').empty();

            response.items.forEach(function (item) {
                addItemRow(item);
            });

            $('#modalMutasiStok').modal('show');
        });
    });

    resetFormState();
    updateInfoBox();
});
</script>
@endpush