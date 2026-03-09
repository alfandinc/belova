@extends('layouts.marketing.app')
@section('title', 'Penawaran')
@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Penawaran</h4>
                    <button id="btn-add-penawaran" class="btn btn-primary">Tambah Penawaran</button>
                </div>
                <div class="card-body">
                    <table id="penawaran-table" class="table table-bordered table-striped w-100">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Pasien</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Create -->
<div class="modal fade" id="modalPenawaranCreate" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Penawaran</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-penawaran">
                    @csrf
                    <div class="form-group">
                        <label>Pasien</label>
                        <select id="penawaran-pasien-id" name="pasien_id" class="form-control" style="width:100%"></select>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="mb-0">Items</label>
                        <button type="button" id="btn-add-item" class="btn btn-sm btn-secondary">Tambah Item</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered" id="penawaran-items-table">
                            <thead>
                                <tr>
                                    <th style="width:35%">Obat</th>
                                    <th style="width:10%">Jumlah</th>
                                    <th style="width:10%">Stok Tersedia</th>
                                    <th style="width:15%">Harga</th>
                                    <th style="width:10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </form>
                <div class="text-muted small">Status awal: <strong>ditawarkan</strong></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" id="btn-save-penawaran" class="btn btn-primary">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Detail -->
<div class="modal fade" id="modalPenawaranDetail" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Penawaran</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-2"><strong>Pasien:</strong> <span id="penawaran-detail-pasien">-</span></div>
                <div class="mb-2"><strong>Status:</strong> <span id="penawaran-detail-status">-</span></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="penawaran-detail-items">
                        <thead>
                            <tr>
                                <th>Obat</th>
                                <th>Jumlah</th>
                                <th>Harga</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Submit -->
<div class="modal fade" id="modalPenawaranSubmit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit Penawaran</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="penawaran-submit-id" />

                <div class="form-group">
                    <label>Klinik</label>
                    <select id="penawaran-submit-klinik" class="form-control" style="width:100%"></select>
                </div>

                <div class="form-group">
                    <label>Dokter</label>
                    <select id="penawaran-submit-dokter" class="form-control" style="width:100%"></select>
                </div>

                <div class="form-group">
                    <label>Metode Bayar</label>
                    <select id="penawaran-submit-metode" class="form-control" style="width:100%"></select>
                </div>

                <div class="text-muted small">Setelah submit, status menjadi <strong>diproses</strong>.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" id="btn-save-submit" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function() {
    function showSwal(icon, title, text) {
        if (window.Swal && typeof Swal.fire === 'function') {
            return Swal.fire(title || '', text || '', icon || 'info');
        }
        alert((title ? (title + ': ') : '') + (text || ''));
    }

    var csrfToken = '{{ csrf_token() }}';
    var tableUrl = '{{ route('marketing.penawaran.data') }}';
    var storeUrl = '{{ route('marketing.penawaran.store') }}';
    var itemsBaseUrl = '{{ url('marketing/penawaran') }}';
    var pasienSearchUrl = '{{ route('marketing.penawaran.pasien.search') }}';
    var obatSearchUrl = '{{ route('marketing.penawaran.obat.search') }}';
    var klinikSearchUrl = '{{ route('marketing.penawaran.klinik.search') }}';
    var dokterSearchUrl = '{{ route('marketing.penawaran.dokter.search') }}';
    var metodeBayarSearchUrl = '{{ route('marketing.penawaran.metode_bayar.search') }}';
    var submitBaseUrl = '{{ url('marketing/penawaran') }}';

    var table = $('#penawaran-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: tableUrl,
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'pasien_info', name: 'pasien.nama', orderable: false, searchable: false },
            { data: 'items_list', name: 'items_list', orderable: false, searchable: false },
            { data: 'status', name: 'status', orderable: true, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
    });

    function initPasienSelect2() {
        $('#penawaran-pasien-id').select2({
            width: '100%',
            dropdownParent: $('#modalPenawaranCreate'),
            minimumInputLength: 2,
            ajax: {
                url: pasienSearchUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return data;
                },
                cache: true
            },
            placeholder: 'Ketik minimal 2 huruf...'
        });
    }

    function initObatSelect2($el) {
        $el.select2({
            width: '100%',
            dropdownParent: $('#modalPenawaranCreate'),
            allowClear: true,
            minimumInputLength: 2,
            ajax: {
                url: obatSearchUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return data;
                },
                cache: true
            },
            placeholder: 'Ketik minimal 2 huruf...'
        });

        // Auto-fill harga from obat.harga_nonfornas
        $el.off('select2:select.penawaranHarga').on('select2:select.penawaranHarga', function (e) {
            var selected = (e && e.params && e.params.data) ? e.params.data : null;
            if (!selected) return;

            var $tr = $(this).closest('tr');

            // stok tersedia (from mapped gudang resep)
            var stok = selected.stok_tersedia;
            if (stok === null || stok === undefined || stok === '') {
                $tr.find('.stok-tersedia').text('-');
            } else {
                $tr.find('.stok-tersedia').text(stok);
            }

            var harga = selected.harga_nonfornas;
            if (harga === null || harga === undefined || harga === '') return;

            $tr.find('.input-harga').val(harga).trigger('change');
        });

        $el.off('select2:clear.penawaranHarga').on('select2:clear.penawaranHarga', function () {
            var $tr = $(this).closest('tr');
            $tr.find('.input-harga').val('').trigger('change');
            $tr.find('.stok-tersedia').text('-');
        });
    }

    function addItemRow() {
        var rowId = 'row-' + Math.random().toString(36).slice(2);
        var $tr = $('<tr id="'+ rowId +'">\n' +
            '  <td><select class="form-control select-obat" style="width:100%"></select></td>\n' +
            '  <td><input type="number" min="1" class="form-control input-jumlah" value="1" /></td>\n' +
            '  <td class="text-center"><span class="stok-tersedia">-</span></td>\n' +
            '  <td><input type="number" min="0" step="0.01" class="form-control input-harga" /></td>\n' +
            '  <td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-remove-item">Hapus</button></td>\n' +
            '</tr>');

        $('#penawaran-items-table tbody').append($tr);
        initObatSelect2($tr.find('.select-obat'));
    }

    function initSubmitSelect2() {
        $('#penawaran-submit-klinik').select2({
            width: '100%',
            dropdownParent: $('#modalPenawaranSubmit'),
            ajax: {
                url: klinikSearchUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return data;
                },
                cache: true
            },
            placeholder: 'Pilih klinik...'
        });

        $('#penawaran-submit-dokter').select2({
            width: '100%',
            dropdownParent: $('#modalPenawaranSubmit'),
            ajax: {
                url: dokterSearchUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return data;
                },
                cache: true
            },
            placeholder: 'Pilih dokter...'
        });

        $('#penawaran-submit-metode').select2({
            width: '100%',
            dropdownParent: $('#modalPenawaranSubmit'),
            ajax: {
                url: metodeBayarSearchUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return data;
                },
                cache: true
            },
            placeholder: 'Pilih metode bayar...'
        });
    }

    $(document).on('click', '#btn-add-penawaran', function() {
        $('#form-penawaran')[0].reset();
        $('#penawaran-items-table tbody').empty();

        // reset select2 pasien
        $('#penawaran-pasien-id').val(null).trigger('change');

        addItemRow();
        $('#modalPenawaranCreate').modal('show');
    });

    $(document).on('click', '#btn-add-item', function() {
        addItemRow();
    });

    $(document).on('click', '.btn-penawaran-submit', function() {
        var id = $(this).data('id');

        $('#penawaran-submit-id').val(id);

        // reset select2 values
        $('#penawaran-submit-klinik').val(null).trigger('change');
        $('#penawaran-submit-dokter').val(null).trigger('change');
        $('#penawaran-submit-metode').val(null).trigger('change');

        // prefill if exists
        var klinikId = $(this).data('klinik-id');
        var klinikText = $(this).data('klinik-text');
        if (klinikId) {
            var optK = new Option(klinikText || ('#' + klinikId), klinikId, true, true);
            $('#penawaran-submit-klinik').append(optK).trigger('change');
        }

        var dokterId = $(this).data('dokter-id');
        var dokterText = $(this).data('dokter-text');
        if (dokterId) {
            var optD = new Option(dokterText || ('#' + dokterId), dokterId, true, true);
            $('#penawaran-submit-dokter').append(optD).trigger('change');
        }

        var metodeId = $(this).data('metode-id');
        var metodeText = $(this).data('metode-text');
        if (metodeId) {
            var optM = new Option(metodeText || ('#' + metodeId), metodeId, true, true);
            $('#penawaran-submit-metode').append(optM).trigger('change');
        }

        $('#modalPenawaranSubmit').modal('show');
    });

    $(document).on('click', '.btn-remove-item', function() {
        $(this).closest('tr').remove();
    });

    $(document).on('click', '#btn-save-penawaran', function() {
        var pasienId = $('#penawaran-pasien-id').val();
        if (!pasienId) {
            showSwal('warning', 'Validasi', 'Pasien wajib dipilih.');
            return;
        }

        var items = [];
        $('#penawaran-items-table tbody tr').each(function() {
            var obatId = $(this).find('.select-obat').val();
            if (!obatId) return;

            items.push({
                obat_id: obatId,
                jumlah: $(this).find('.input-jumlah').val(),
                harga: $(this).find('.input-harga').val(),
            });
        });

        if (items.length === 0) {
            showSwal('warning', 'Validasi', 'Minimal 1 item obat.');
            return;
        }

        $.ajax({
            url: storeUrl,
            method: 'POST',
            data: {
                _token: csrfToken,
                pasien_id: pasienId,
                items: items,
            },
            success: function(res) {
                if (res && res.success) {
                    $('#modalPenawaranCreate').modal('hide');
                    table.ajax.reload(null, false);
                    showSwal('success', 'Sukses', res.message || 'Berhasil');
                } else {
                    showSwal('error', 'Gagal', (res && res.message) ? res.message : 'Gagal');
                }
            },
            error: function(xhr) {
                var msg = 'Gagal menyimpan.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                showSwal('error', 'Gagal', msg);
            }
        });
    });

    $(document).on('click', '.btn-penawaran-detail', function() {
        var id = $(this).data('id');
        $.get(itemsBaseUrl + '/' + id + '/items', function(res) {
            $('#penawaran-detail-pasien').text(res.nama_pasien || '-');
            $('#penawaran-detail-status').text(res.status || '-');

            var $tbody = $('#penawaran-detail-items tbody');
            $tbody.empty();
            (res.items || []).forEach(function(it) {
                var harga = (it.harga !== null && it.harga !== undefined && it.harga !== '') ? it.harga : '-';
                var total = (it.total !== null && it.total !== undefined && it.total !== '') ? it.total : '-';
                $tbody.append('<tr>' +
                    '<td>' + (it.obat_nama || '-') + '</td>' +
                    '<td>' + (it.jumlah || '-') + '</td>' +
                    '<td>' + harga + '</td>' +
                    '<td>' + total + '</td>' +
                '</tr>');
            });

            $('#modalPenawaranDetail').modal('show');
        });
    });

    $(document).on('click', '#btn-save-submit', function() {
        var id = $('#penawaran-submit-id').val();
        var klinikId = $('#penawaran-submit-klinik').val();
        var dokterId = $('#penawaran-submit-dokter').val();
        var metodeId = $('#penawaran-submit-metode').val();

        if (!klinikId || !dokterId || !metodeId) {
            showSwal('warning', 'Validasi', 'Klinik, dokter, dan metode bayar wajib diisi.');
            return;
        }

        $.ajax({
            url: submitBaseUrl + '/' + id + '/submit',
            method: 'POST',
            data: {
                _token: csrfToken,
                klinik_id: klinikId,
                dokter_id: dokterId,
                metode_bayar_id: metodeId,
            },
            success: function(res) {
                if (res && res.success) {
                    $('#modalPenawaranSubmit').modal('hide');
                    table.ajax.reload(null, false);
                    showSwal('success', 'Sukses', res.message || 'Berhasil');
                } else {
                    showSwal('error', 'Gagal', (res && res.message) ? res.message : 'Gagal');
                }
            },
            error: function(xhr) {
                var msg = 'Gagal submit.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                showSwal('error', 'Gagal', msg);
            }
        });
    });

    initPasienSelect2();
    initSubmitSelect2();
});
</script>
@endsection
