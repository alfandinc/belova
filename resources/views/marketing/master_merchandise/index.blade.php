@extends('layouts.marketing.app')

@section('title', 'Master Merchandise')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@push('styles')
<style>
    /* Theme-neutral pieces (the marketing layout defaults to dark mode) */
    .merch-thumb { width: 48px; height: 48px; display: block; object-fit: cover; border-radius: 8px; border: 1px solid rgba(148, 163, 184, .35); }
    .merch-thumb.is-empty { display: flex; align-items: center; justify-content: center; font-size: 20px; opacity: .55; }
    .merch-preview { width: 96px; height: 96px; flex: 0 0 96px; font-size: 32px; }
    .merch-muted { opacity: .65; }
    .merch-filter-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; opacity: .65; margin-bottom: 4px; }
    .merch-total { font-size: 1.1rem; font-weight: 700; }
    #merch-table td, #purchase-table td, #opname-table td { vertical-align: middle; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Master Merchandise</h4>
                    @if($canManage)
                        <div>
                            <button type="button" id="btn-opname" class="btn btn-outline-primary"><i class="mdi mdi-clipboard-check-outline"></i> Stok Opname</button>
                            <button type="button" id="btn-add" class="btn btn-primary"><i class="mdi mdi-plus"></i> Tambah Merchandise</button>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="form-row mb-3">
                        <div class="col-md-3 col-6">
                            <label for="filter-status" class="merch-filter-label d-block">Status</label>
                            <select id="filter-status" class="form-control">
                                <option value="">Semua</option>
                                <option value="aktif" selected>Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="merch-table" class="table table-hover table-bordered w-100">
                            <thead>
                                <tr>
                                    <th style="width: 64px;">Gambar</th>
                                    <th style="width: 90px;">Kode</th>
                                    <th>Merchandise</th>
                                    <th>Deskripsi</th>
                                    @if($canManage)
                                        <th style="width: 120px;">Harga Beli</th>
                                    @endif
                                    <th style="width: 80px;">Stok</th>
                                    <th style="width: 90px;">Status</th>
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

<div class="modal fade" id="merchModal" tabindex="-1" role="dialog" aria-labelledby="merchModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="merchForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="merchModalLabel">Tambah Merchandise</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="merch_id">
                <input type="hidden" id="remove_image" value="0">
                <div class="form-group d-none" id="kode-group">
                    <label for="kode">Kode</label>
                    <input type="text" id="kode" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label for="name">Nama</label>
                    <input type="text" id="name" class="form-control" maxlength="255" required>
                </div>
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group mb-0">
                    <label for="image">Gambar</label>
                    <div class="d-flex align-items-center">
                        <div id="image-preview" class="merch-thumb merch-preview is-empty mr-3"><i class="mdi mdi-image-outline"></i></div>
                        <div class="flex-grow-1" style="min-width: 0;">
                            <input type="file" id="image" class="form-control-file" accept="image/jpeg,image/png,image/webp">
                            <small class="form-text text-muted">JPG, PNG, atau WEBP. Otomatis diperkecil.</small>
                            <button type="button" id="btn-remove-image" class="btn btn-sm btn-outline-danger mt-1 d-none">Hapus gambar</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" id="saveMerch" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

@if($canManage)
<div class="modal fade" id="opnameModal" tabindex="-1" role="dialog" aria-labelledby="opnameModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <form id="opnameForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="opnameModalLabel">Stok Opname</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="small merch-muted">Isi stok fisik hasil hitungan. Item yang dikosongkan tidak ikut diproses. Alasan wajib kalau ada selisih.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0" id="opname-table">
                        <thead>
                            <tr>
                                <th>Merchandise</th>
                                <th style="width: 90px;">Sistem</th>
                                <th style="width: 110px;">Fisik</th>
                                <th style="width: 90px;">Selisih</th>
                                <th>Alasan</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" id="saveOpname" class="btn btn-primary">Simpan Opname</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="stockModal" role="dialog" aria-labelledby="stockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form id="stockForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockModalLabel">Stok Masuk</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="stock_merch_name">Merchandise</label>
                        <input type="text" id="stock_merch_name" class="form-control" readonly>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="stock_sumber">Sumber</label>
                        <select id="stock_sumber" class="form-control">
                            <option value="pembelian">Pembelian</option>
                            <option value="hibah">Hibah / sponsor</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="stock_tanggal" id="stock_tanggal_label">Tanggal Beli</label>
                        <input type="date" id="stock_tanggal" class="form-control" max="{{ now()->toDateString() }}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="stock_pemasok" id="stock_pemasok_label">Pemasok</label>
                        <select id="stock_pemasok" class="form-control" style="width: 100%;">
                            <option value=""></option>
                            @foreach($pemasokNames as $pemasokName)
                                <option value="{{ $pemasokName }}">{{ $pemasokName }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Pilih dari daftar atau ketik nama baru.</small>
                    </div>
                    <div class="form-group col-md-3 stock-purchase-only">
                        <label for="stock_nota">No. Nota</label>
                        <input type="text" id="stock_nota" class="form-control" maxlength="100">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="stock_qty">Jumlah</label>
                        <input type="number" id="stock_qty" class="form-control" min="1" max="100000" value="1" required>
                    </div>
                </div>
                <div class="form-row stock-purchase-only">
                    <div class="form-group col-md-3">
                        <label for="stock_harga">Harga Satuan</label>
                        <input type="number" id="stock_harga" class="form-control" min="0" step="0.01">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="stock_ongkir">Ongkir</label>
                        <input type="number" id="stock_ongkir" class="form-control" min="0" step="0.01">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="stock_biaya_lain">Biaya Lain</label>
                        <input type="number" id="stock_biaya_lain" class="form-control" min="0" step="0.01">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="d-block">Total</label>
                        <div id="stock_total" class="merch-total">Rp 0</div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6 mb-md-0 stock-purchase-only">
                        <label for="stock_foto">Foto Nota</label>
                        <input type="file" id="stock_foto" class="form-control-file" accept="image/jpeg,image/png,image/webp">
                        <small class="form-text text-muted" id="stock_foto_help">JPG, PNG, atau WEBP. Otomatis diperkecil.</small>
                    </div>
                    <div class="form-group col-md-6 mb-0">
                        <label for="stock_catatan">Catatan</label>
                        <textarea id="stock_catatan" class="form-control" rows="2" placeholder="Opsional"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" id="saveStock" class="btn btn-success">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endif

<div class="modal fade" id="stockHistoryModal" tabindex="-1" role="dialog" aria-labelledby="stockHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockHistoryModalLabel">Riwayat: <span id="history_merch_name">-</span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @if($canManage)
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#pane-kartu" role="tab">Kartu Stok</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#pane-pembelian" role="tab">Riwayat Pembelian</a></li>
                    </ul>
                @endif
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="pane-kartu" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0" id="stock-history-table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Tipe</th>
                                        <th>Qty</th>
                                        <th>Stok</th>
                                        <th>Catatan</th>
                                        <th>Oleh</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    @if($canManage)
                        <div class="tab-pane fade" id="pane-pembelian" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm mb-0" id="purchase-table">
                                    <thead>
                                        <tr>
                                            <th>Kode</th>
                                            <th>Tgl Beli</th>
                                            <th>Sumber</th>
                                            <th>Pemasok</th>
                                            <th>Nota</th>
                                            <th>Qty</th>
                                            <th>Harga</th>
                                            <th>Total</th>
                                            <th>Catatan</th>
                                            <th>Oleh</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const baseUrl = @json(url('marketing/master-merchandise'));
    const canManage = @json($canManage);

    function escapeText(value) {
        return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
    }

    function escapeAttr(value) {
        return escapeText(value).replace(/"/g, '&quot;');
    }

    function rupiah(value) {
        return value === null || value === undefined ? '-' : 'Rp ' + Number(value).toLocaleString('id-ID', { maximumFractionDigits: 2 });
    }

    function ajaxError(xhr, fallback) {
        const body = xhr && xhr.responseJSON;
        return (body && (body.message || body.error)) || fallback;
    }

    function showError(xhr, fallback) {
        Swal.fire({ icon: 'error', title: 'Gagal', text: ajaxError(xhr, fallback) });
    }

    function showSuccess(text) {
        Swal.fire({ icon: 'success', title: 'Berhasil', text: text, timer: 1500, showConfirmButton: false });
    }

    function askConfirm(title, text, confirmText) {
        return Swal.fire({
            icon: 'warning',
            title: title,
            text: text,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: 'Batal'
        }).then(function (result) { return !!result.value; });
    }

    // Shrinks in the browser first: phone photos often exceed the server's PHP upload limit (2 MB)
    function shrinkImage(file, maxPx) {
        return new Promise(function (resolve) {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = function () {
                const scale = Math.min(1, maxPx / Math.max(img.width, img.height));
                const canvas = document.createElement('canvas');
                canvas.width = Math.round(img.width * scale);
                canvas.height = Math.round(img.height * scale);
                const context = canvas.getContext('2d');
                context.fillStyle = '#fff';
                context.fillRect(0, 0, canvas.width, canvas.height);
                context.drawImage(img, 0, 0, canvas.width, canvas.height);
                URL.revokeObjectURL(url);
                canvas.toBlob(function (blob) {
                    resolve(blob ? new File([blob], 'gambar.jpg', { type: 'image/jpeg' }) : file);
                }, 'image/jpeg', 0.85);
            };
            img.onerror = function () {
                URL.revokeObjectURL(url);
                resolve(file);
            };
            img.src = url;
        });
    }

    const columns = [
        { data: 'thumb', orderable: false, searchable: false },
        { data: 'kode', name: 'kode', className: 'text-nowrap' },
        { data: 'item', name: 'name' },
        { data: 'description', name: 'description', visible: false }
    ];
    if (canManage) {
        columns.push({ data: 'price_label', orderable: false, searchable: false, className: 'text-nowrap' });
    }
    columns.push(
        {
            data: 'current_stock', orderable: false, searchable: false,
            render: function (stock) {
                return stock > 0
                    ? '<span class="badge badge-soft-primary">' + stock + '</span>'
                    : '<span class="badge badge-soft-danger">Habis</span>';
            }
        },
        {
            data: 'is_active', orderable: false, searchable: false,
            render: function (active) {
                return active
                    ? '<span class="badge badge-soft-success">Aktif</span>'
                    : '<span class="badge badge-soft-secondary">Nonaktif</span>';
            }
        },
        { data: 'actions', orderable: false, searchable: false }
    );

    const table = $('#merch-table').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [[1, 'asc']],
        ajax: {
            url: @json(route('marketing.master_merchandise.data')),
            data: function (d) { d.status = $('#filter-status').val(); }
        },
        columns: columns
    });

    $('#filter-status').on('change', function () { table.ajax.reload(); });

    /* Master form */
    function setImagePreview(url) {
        const $preview = $('#image-preview');
        if (url) {
            $preview.removeClass('is-empty').empty().append($('<img>').attr({ src: url, alt: '' }).css({ width: '100%', height: '100%', objectFit: 'cover', borderRadius: '7px' }));
        } else {
            $preview.addClass('is-empty').html('<i class="mdi mdi-image-outline"></i>');
        }
        $('#btn-remove-image').toggleClass('d-none', !url);
    }

    function openMerchForm(item) {
        $('#merchForm')[0].reset();
        $('#merch_id').val(item ? item.id : '');
        $('#remove_image').val('0');
        $('#merchModalLabel').text(item ? 'Edit Merchandise' : 'Tambah Merchandise');
        $('#kode-group').toggleClass('d-none', !item);
        if (item) {
            $('#kode').val(item.kode);
            $('#name').val(item.name);
            $('#description').val(item.description);
        }
        setImagePreview(item ? item.image_url : null);
        $('#merchModal').modal('show');
    }

    $('#btn-add').on('click', function () { openMerchForm(null); });

    $('#merch-table').on('click', '.btn-edit', function () {
        $.get(baseUrl + '/' + $(this).data('id') + '/edit')
            .done(openMerchForm)
            .fail(function (xhr) { showError(xhr, 'Gagal memuat merchandise.'); });
    });

    $('#image').on('change', function () {
        const file = this.files && this.files[0];
        if (file) {
            $('#remove_image').val('0');
            setImagePreview(URL.createObjectURL(file));
        }
    });

    $('#btn-remove-image').on('click', function () {
        $('#image').val('');
        $('#remove_image').val('1');
        setImagePreview(null);
    });

    $('#merchForm').on('submit', function (e) {
        e.preventDefault();
        const id = $('#merch_id').val();
        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('name', $('#name').val());
        formData.append('description', $('#description').val());
        formData.append('remove_image', $('#remove_image').val());
        // PHP only parses multipart bodies on POST, so PUT goes through method spoofing
        if (id) { formData.append('_method', 'PUT'); }

        const $button = $('#saveMerch').prop('disabled', true);
        const file = $('#image')[0].files[0];

        (file ? shrinkImage(file, 600) : Promise.resolve(null)).then(function (upload) {
            if (upload) { formData.append('image', upload); }

            $.ajax({ url: id ? baseUrl + '/' + id : baseUrl, type: 'POST', data: formData, processData: false, contentType: false })
                .done(function () {
                    $('#merchModal').modal('hide');
                    table.ajax.reload(null, false);
                    showSuccess('Merchandise berhasil disimpan.');
                })
                .fail(function (xhr) { showError(xhr, 'Gagal menyimpan merchandise.'); })
                .always(function () { $button.prop('disabled', false); });
        });
    });

    /* Stock in: new entry, edit a purchase, or complete older stock */
    let stockFormUrl = '';
    let stockFormMethod = 'POST';

    if (canManage) {
        $('#stock_pemasok').select2({
            tags: true,
            width: '100%',
            allowClear: true,
            placeholder: 'Pilih atau ketik pemasok baru',
            dropdownParent: $('#stockModal')
        });
    }

    function setPemasok(name) {
        const $select = $('#stock_pemasok');
        if (name && !$select.find('option').filter(function () { return this.value === name; }).length) {
            $select.append(new Option(name, name));
        }
        $select.val(name || '').trigger('change');
    }

    function refreshStockTotal() {
        const qty = Number($('#stock_qty').val()) || 0;
        const price = Number($('#stock_harga').val()) || 0;
        const ongkir = Number($('#stock_ongkir').val()) || 0;
        const other = Number($('#stock_biaya_lain').val()) || 0;
        $('#stock_total').text(rupiah(qty * price + ongkir + other));
    }

    function applySumber() {
        const isPurchase = $('#stock_sumber').val() === 'pembelian';
        $('.stock-purchase-only').toggleClass('d-none', !isPurchase);
        $('#stock_tanggal_label').text(isPurchase ? 'Tanggal Beli' : 'Tanggal Terima');
        $('#stock_pemasok_label').text(isPurchase ? 'Pemasok' : 'Pemberi (opsional)');
    }

    function openStockForm(options) {
        const entry = options.entry || {};
        $('#stockForm')[0].reset();
        stockFormUrl = options.url;
        stockFormMethod = options.method || 'POST';
        $('#stockModalLabel').text(options.title);
        $('#stock_merch_name').val(options.name);
        $('#stock_sumber').val(entry.sumber || 'pembelian').prop('disabled', !!options.lockSumber);
        $('#stock_tanggal').val(entry.tanggal_beli || options.tanggal || new Date().toISOString().slice(0, 10));
        $('#stock_qty').val(entry.qty || 1).prop('readonly', !!options.lockQty);
        $('#stock_nota').val(entry.no_nota || '');
        $('#stock_harga').val(entry.harga_satuan === null || entry.harga_satuan === undefined ? '' : entry.harga_satuan);
        $('#stock_ongkir').val(entry.ongkir || '');
        $('#stock_biaya_lain').val(entry.biaya_lain || '');
        $('#stock_catatan').val(entry.catatan || '');
        $('#stock_foto_help').text(entry.nota_url ? 'Sudah ada foto nota. Pilih file untuk menggantinya.' : 'JPG, PNG, atau WEBP. Otomatis diperkecil.');
        setPemasok(entry.pemasok || '');
        applySumber();
        refreshStockTotal();
        $('#stockModal').modal('show');
    }

    $('#stock_sumber').on('change', applySumber);
    $('#stock_qty, #stock_harga, #stock_ongkir, #stock_biaya_lain').on('input', refreshStockTotal);

    $('#merch-table').on('click', '.btn-stock', function () {
        openStockForm({
            url: baseUrl + '/' + $(this).data('id') + '/pembelian',
            title: 'Stok Masuk',
            name: $(this).data('name')
        });
    });

    $('#stockForm').on('submit', function (e) {
        e.preventDefault();
        const formData = new FormData();
        formData.append('_token', csrfToken);
        if (stockFormMethod !== 'POST') { formData.append('_method', stockFormMethod); }
        formData.append('sumber', $('#stock_sumber').val());
        formData.append('tanggal_beli', $('#stock_tanggal').val());
        formData.append('qty', $('#stock_qty').val());
        formData.append('pemasok', $('#stock_pemasok').val() || '');
        formData.append('no_nota', $('#stock_nota').val());
        formData.append('harga_satuan', $('#stock_harga').val());
        formData.append('ongkir', $('#stock_ongkir').val());
        formData.append('biaya_lain', $('#stock_biaya_lain').val());
        formData.append('catatan', $('#stock_catatan').val());

        const $button = $('#saveStock').prop('disabled', true);
        const file = $('#stock_foto')[0].files[0];

        (file ? shrinkImage(file, 1600) : Promise.resolve(null)).then(function (upload) {
            if (upload) { formData.append('foto_nota', upload); }

            $.ajax({ url: stockFormUrl, type: 'POST', data: formData, processData: false, contentType: false })
                .done(function (response) {
                    $('#stockModal').modal('hide');
                    table.ajax.reload(null, false);
                    showSuccess(response.message);
                })
                .fail(function (xhr) { showError(xhr, 'Gagal menyimpan stok masuk.'); })
                .always(function () { $button.prop('disabled', false); });
        });
    });

    /* History: stock card + purchases */
    let historyItem = { id: null, name: '' };
    let purchaseRows = [];
    let openingRows = [];

    function setTableMessage(selector, colspan, html) {
        $(selector + ' tbody').html('<tr><td colspan="' + colspan + '" class="text-center">' + html + '</td></tr>');
    }

    function renderStockHistory(rows) {
        if (!rows.length) {
            setTableMessage('#stock-history-table', 6, '<span class="text-muted">Belum ada data.</span>');
            return;
        }

        $('#stock-history-table tbody').html(rows.map(function (row) {
            const isIn = row.type === 'in';
            const badge = row.sumber === 'opname'
                ? '<span class="badge badge-soft-warning">Opname</span>'
                : '<span class="badge ' + (isIn ? 'badge-soft-success' : 'badge-soft-danger') + '">' + (isIn ? 'Masuk' : 'Keluar') + '</span>';
            return '<tr>'
                + '<td class="text-nowrap">' + escapeText(row.tanggal || '-') + '</td>'
                + '<td>' + badge + '</td>'
                + '<td>' + escapeText(row.qty) + '</td>'
                + '<td>' + escapeText(row.current_stock) + '</td>'
                + '<td>' + escapeText(row.notes || '-') + '</td>'
                + '<td>' + escapeText(row.user_name || '-') + '</td>'
                + '</tr>';
        }).join(''));
    }

    function sumberLabel(sumber) {
        return { pembelian: 'Pembelian', hibah: 'Hibah / sponsor', stok_awal: 'Stok awal' }[sumber] || sumber;
    }

    function renderPurchases() {
        const purchases = purchaseRows.map(function (row, index) {
            const nota = row.nota_url
                ? '<a href="' + escapeAttr(row.nota_url) + '" target="_blank" rel="noopener">' + escapeText(row.no_nota || 'Lihat foto') + '</a>'
                : escapeText(row.no_nota || '-');
            const isPurchase = row.sumber === 'pembelian';
            return '<tr>'
                + '<td class="text-nowrap">' + escapeText(row.kode) + '</td>'
                + '<td class="text-nowrap">' + escapeText(row.tanggal_beli) + '</td>'
                + '<td>' + escapeText(sumberLabel(row.sumber)) + '</td>'
                + '<td>' + escapeText(row.pemasok || '-') + '</td>'
                + '<td>' + nota + '</td>'
                + '<td>' + escapeText(row.qty) + '</td>'
                + '<td class="text-nowrap">' + (isPurchase ? escapeText(rupiah(row.harga_satuan)) : '-') + '</td>'
                + '<td class="text-nowrap">' + (isPurchase ? escapeText(rupiah(row.total)) : '-') + '</td>'
                + '<td>' + escapeText(row.catatan || '-') + '</td>'
                + '<td>' + escapeText(row.user_name || '-') + '</td>'
                + '<td><button type="button" class="btn btn-sm btn-warning btn-purchase-edit" data-index="' + index + '">Edit</button></td>'
                + '</tr>';
        });
        const openings = openingRows.map(function (row, index) {
            return '<tr>'
                + '<td>-</td>'
                + '<td class="text-nowrap">' + escapeText(row.tanggal || '-') + '</td>'
                + '<td><span class="badge badge-soft-secondary">Stok awal</span></td>'
                + '<td colspan="2" class="merch-muted">Belum ada data pembelian</td>'
                + '<td>' + escapeText(row.qty) + '</td>'
                + '<td>-</td><td>-</td>'
                + '<td>' + escapeText(row.notes || '-') + '</td>'
                + '<td>-</td>'
                + '<td><button type="button" class="btn btn-sm btn-primary btn-opening-complete" data-index="' + index + '">Lengkapi</button></td>'
                + '</tr>';
        });

        if (!purchases.length && !openings.length) {
            setTableMessage('#purchase-table', 11, '<span class="text-muted">Belum ada data.</span>');
            return;
        }

        $('#purchase-table tbody').html(purchases.concat(openings).join(''));
    }

    $('#merch-table').on('click', '.btn-history', function () {
        historyItem = { id: $(this).data('id'), name: $(this).data('name') };
        $('#history_merch_name').text(historyItem.name || '-');
        setTableMessage('#stock-history-table', 6, '<span class="text-muted">Memuat...</span>');
        $('#stockHistoryModal').modal('show');

        $.get(baseUrl + '/' + historyItem.id + '/stock-history')
            .done(function (response) { renderStockHistory(response.data || []); })
            .fail(function (xhr) {
                setTableMessage('#stock-history-table', 6, '<span class="text-danger">' + escapeText(ajaxError(xhr, 'Gagal memuat kartu stok.')) + '</span>');
            });

        if (canManage) {
            setTableMessage('#purchase-table', 11, '<span class="text-muted">Memuat...</span>');
            $.get(baseUrl + '/' + historyItem.id + '/pembelian')
                .done(function (response) {
                    purchaseRows = response.purchases || [];
                    openingRows = response.openings || [];
                    renderPurchases();
                })
                .fail(function (xhr) {
                    setTableMessage('#purchase-table', 11, '<span class="text-danger">' + escapeText(ajaxError(xhr, 'Gagal memuat riwayat pembelian.')) + '</span>');
                });
        }
    });

    // One modal at a time: the form opens after the history modal has closed
    function openFormFromHistory(options) {
        $('#stockHistoryModal').one('hidden.bs.modal', function () { openStockForm(options); }).modal('hide');
    }

    $('#purchase-table').on('click', '.btn-purchase-edit', function () {
        const entry = purchaseRows[$(this).data('index')];
        openFormFromHistory({
            url: baseUrl + '/pembelian/' + entry.id,
            method: 'PUT',
            title: 'Edit ' + entry.kode,
            name: historyItem.name,
            entry: entry,
            lockSumber: true,
            lockQty: true
        });
    });

    $('#purchase-table').on('click', '.btn-opening-complete', function () {
        const opening = openingRows[$(this).data('index')];
        openFormFromHistory({
            url: baseUrl + '/kartu/' + opening.id + '/lengkapi',
            title: 'Lengkapi Data Stok Awal',
            name: historyItem.name,
            tanggal: opening.tanggal,
            entry: { qty: opening.qty },
            lockQty: true
        });
    });

    /* Stock opname */
    function renderOpnameRows(items) {
        if (!items.length) {
            setTableMessage('#opname-table', 5, '<span class="text-muted">Belum ada merchandise.</span>');
            return;
        }

        $('#opname-table tbody').html(items.map(function (item) {
            return '<tr class="opname-row" data-id="' + escapeAttr(item.id) + '" data-stock="' + escapeAttr(item.current_stock) + '">'
                + '<td><strong>' + escapeText(item.name) + '</strong> <small class="merch-muted">' + escapeText(item.kode) + '</small>'
                + (item.is_active === false ? ' <span class="badge badge-soft-secondary">Nonaktif</span>' : '') + '</td>'
                + '<td>' + escapeText(item.current_stock) + '</td>'
                + '<td><input type="number" min="0" max="100000" class="form-control form-control-sm opname-fisik" aria-label="Stok fisik"></td>'
                + '<td class="opname-selisih">-</td>'
                + '<td><input type="text" maxlength="255" class="form-control form-control-sm opname-alasan" aria-label="Alasan" placeholder="Wajib kalau ada selisih"></td>'
                + '</tr>';
        }).join(''));
    }

    $('#btn-opname').on('click', function () {
        setTableMessage('#opname-table', 5, '<span class="text-muted">Memuat...</span>');
        $('#opnameModal').modal('show');
        $.get(@json(route('erm.merchandises.catalog')), { include_inactive: 1 })
            .done(function (response) { renderOpnameRows(response.data || []); })
            .fail(function (xhr) {
                setTableMessage('#opname-table', 5, '<span class="text-danger">' + escapeText(ajaxError(xhr, 'Gagal memuat daftar merchandise.')) + '</span>');
            });
    });

    $('#opname-table').on('input', '.opname-fisik', function () {
        const $row = $(this).closest('tr');
        const value = $(this).val();
        const delta = Number(value) - Number($row.data('stock'));
        const $cell = $row.find('.opname-selisih').removeClass('text-danger text-success');

        if (value === '') {
            $cell.text('-');
        } else if (delta === 0) {
            $cell.text('Cocok');
        } else {
            $cell.text((delta > 0 ? '+' : '') + delta).addClass(delta > 0 ? 'text-success' : 'text-danger');
        }
    });

    $('#opnameForm').on('submit', function (e) {
        e.preventDefault();
        const items = [];
        $('#opname-table .opname-row').each(function () {
            const fisik = $(this).find('.opname-fisik').val();
            if (fisik !== '') {
                items.push({
                    id: $(this).data('id'),
                    fisik: fisik,
                    stok_sistem: $(this).data('stock'),
                    alasan: $(this).find('.opname-alasan').val()
                });
            }
        });

        if (!items.length) {
            Swal.fire({ icon: 'warning', title: 'Belum ada isian', text: 'Isi stok fisik minimal satu merchandise.', target: document.getElementById('opnameModal') });
            return;
        }

        const $button = $('#saveOpname').prop('disabled', true);
        $.post(baseUrl + '/opname', { _token: csrfToken, items: items })
            .done(function (response) {
                $('#opnameModal').modal('hide');
                table.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message });
            })
            .fail(function (xhr) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: ajaxError(xhr, 'Gagal menyimpan opname.'), target: document.getElementById('opnameModal') });
            })
            .always(function () { $button.prop('disabled', false); });
    });

    /* Status + delete */
    $('#merch-table').on('click', '.btn-toggle-active', function () {
        const id = $(this).data('id');
        const isActive = !!$(this).data('active');
        askConfirm(
            isActive ? 'Nonaktifkan merchandise?' : 'Aktifkan merchandise?',
            isActive ? 'Item tidak muncul lagi di pilihan, riwayatnya tetap tersimpan.' : 'Item akan muncul lagi di pilihan.',
            isActive ? 'Nonaktifkan' : 'Aktifkan'
        ).then(function (confirmed) {
            if (!confirmed) return;
            $.post(baseUrl + '/' + id + '/toggle-active', { _token: csrfToken })
                .done(function () { table.ajax.reload(null, false); })
                .fail(function (xhr) { showError(xhr, 'Gagal mengubah status.'); });
        });
    });

    $('#merch-table').on('click', '.btn-delete', function () {
        const id = $(this).data('id');
        askConfirm('Hapus merchandise?', 'Item yang belum punya riwayat akan dihapus permanen.', 'Hapus').then(function (confirmed) {
            if (!confirmed) return;
            $.ajax({ url: baseUrl + '/' + id, type: 'DELETE', data: { _token: csrfToken } })
                .done(function () { table.ajax.reload(null, false); })
                .fail(function (xhr) { showError(xhr, 'Gagal menghapus merchandise.'); });
        });
    });
});
</script>
@endsection
