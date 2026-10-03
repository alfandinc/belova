{{--
    Retur Pembelian (Billing page). One modal, three panes:
      list   -> all returs + quick approve/reject for pending ones
      add    -> one screen: find invoice (or pre-selected from a billing row), tick items, reason, save
      detail -> full info, approve / reject / print
    A retur is saved as Pending (no stock / cash effect) unless Admin/Finance use "Simpan & Approve".
--}}
@php($returCanApprove = auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Finance']))
<style>
    #modalReturPembelian .retur-pane { display: none; }
    #modalReturPembelian .retur-pane.active { display: block; }
    #modalReturPembelian .retur-status-tabs .nav-link { padding: 6px 14px; font-size: 0.85rem; }
    #modalReturPembelian .retur-search-results { position: absolute; z-index: 20; left: 0; right: 0; max-height: 300px; overflow-y: auto; background: #fff; border: 1px solid #dbe4f0; border-radius: 6px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12); }
    #modalReturPembelian .retur-search-result { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f1f5f9; }
    #modalReturPembelian .retur-search-result:hover { background: #f1f6ff; }
    #modalReturPembelian .retur-items-table td { vertical-align: middle; }
    #modalReturPembelian .retur-items-table tr.selected { background: #eef5ff; }
    #modalReturPembelian .retur-items-table .retur-item-qty { width: 90px; }
    #modalReturPembelian .retur-reason-chip { margin: 0 6px 6px 0; }
    #modalReturPembelian .retur-detail-label { color: #6b7a90; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em; }
</style>

<div class="modal fade" id="modalReturPembelian" tabindex="-1" role="dialog" aria-labelledby="modalReturPembelianTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalReturPembelianTitle"><i class="fas fa-undo-alt mr-2"></i>Retur Pembelian</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>

            <div class="modal-body">
                {{-- ===== Pane: list ===== --}}
                <div class="retur-pane active" id="retur-pane-list">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:.5rem;">
                        <ul class="nav nav-pills retur-status-tabs" id="retur-status-tabs">
                            <li class="nav-item"><a class="nav-link active" href="#" data-status="">Semua</a></li>
                            <li class="nav-item"><a class="nav-link" href="#" data-status="pending">Menunggu Approval <span class="badge badge-warning ml-1" id="retur-tab-pending-count">0</span></a></li>
                            <li class="nav-item"><a class="nav-link" href="#" data-status="approved">Disetujui</a></li>
                            <li class="nav-item"><a class="nav-link" href="#" data-status="rejected">Ditolak</a></li>
                        </ul>
                        <button type="button" class="btn btn-primary btn-sm" id="btn-retur-add"><i class="fas fa-plus mr-1"></i> Retur Baru</button>
                    </div>
                    <div class="table-responsive">
                        <table id="retur-table" class="table table-bordered table-hover table-sm mb-0" style="width:100%">
                            <thead>
                                <tr>
                                    <th>No. Retur</th>
                                    <th>Tanggal</th>
                                    <th>Pasien / Invoice</th>
                                    <th>Total</th>
                                    <th>Item</th>
                                    <th>Dibuat Oleh</th>
                                    <th>Status</th>
                                    <th style="width:170px;">Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                {{-- ===== Pane: add (single screen) ===== --}}
                <div class="retur-pane" id="retur-pane-add">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Retur Baru</h6>
                        <button type="button" class="btn btn-outline-secondary btn-sm retur-back-to-list"><i class="fas fa-arrow-left mr-1"></i> Daftar retur</button>
                    </div>

                    {{-- 1. Invoice --}}
                    <div class="form-group" id="retur-invoice-picker">
                        <label class="small text-muted mb-1" for="retur-invoice-search">Invoice yang diretur</label>
                        <div class="position-relative">
                            <input type="text" class="form-control" id="retur-invoice-search" placeholder="Ketik nama pasien, No. RM, atau No. Invoice..." autocomplete="off">
                            <div class="retur-search-results" id="retur-invoice-results" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="alert alert-info py-2 d-flex justify-content-between align-items-center" id="retur-selected-invoice" style="display:none !important;">
                        <span id="retur-selected-invoice-info"></span>
                        <button type="button" class="btn btn-sm btn-light" id="retur-change-invoice"><i class="fas fa-exchange-alt mr-1"></i> Ganti</button>
                    </div>

                    {{-- 2. Items + reason (shown once an invoice is chosen) --}}
                    <div id="retur-form-body" style="display:none;">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered retur-items-table mb-2">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width:36px;"><input type="checkbox" id="retur-check-all" title="Pilih semua"></th>
                                        <th>Item</th>
                                        <th class="text-center" style="width:90px;">Sisa</th>
                                        <th class="text-center" style="width:110px;">Qty Retur</th>
                                        <th class="text-right" style="width:130px;">Harga Dibayar</th>
                                        <th class="text-right" style="width:130px;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="retur-items-body"></tbody>
                            </table>
                        </div>

                        <div class="form-group mb-2">
                            <label class="small text-muted mb-1" for="retur-reason">Alasan retur <span class="text-danger">*</span></label>
                            <div>
                                @foreach(['Salah obat / item', 'Kelebihan jumlah', 'Efek samping / tidak cocok', 'Pasien batal', 'Barang rusak / kedaluwarsa'] as $chip)
                                    <button type="button" class="btn btn-sm btn-outline-secondary retur-reason-chip">{{ $chip }}</button>
                                @endforeach
                            </div>
                            <textarea class="form-control" id="retur-reason" rows="2" maxlength="500" placeholder="Pilih alasan di atas atau tulis sendiri"></textarea>
                        </div>

                        <a href="#" class="small" id="retur-toggle-extra"><i class="fas fa-sliders-h mr-1"></i>Potongan harga &amp; catatan (opsional)</a>
                        <div class="form-row mt-2" id="retur-extra" style="display:none;">
                            <div class="col-md-3 form-group">
                                <label class="small text-muted mb-1" for="retur-percentage-cut">Potongan harga (%)</label>
                                <input type="number" class="form-control" id="retur-percentage-cut" min="0" max="100" step="0.01" value="0">
                            </div>
                            <div class="col-md-9 form-group">
                                <label class="small text-muted mb-1" for="retur-notes">Catatan</label>
                                <input type="text" class="form-control" id="retur-notes" maxlength="1000">
                            </div>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3 border-top" style="gap:.5rem;">
                            <div>
                                <div class="text-muted small">Total pengembalian</div>
                                <div class="h4 mb-0 font-weight-bold" id="retur-total-preview">Rp 0</div>
                            </div>
                            <div class="d-flex" style="gap:.5rem;">
                                @if($returCanApprove)
                                    <button type="button" class="btn btn-outline-primary btn-retur-submit" data-approve="0"><i class="fas fa-paper-plane mr-1"></i> Ajukan saja</button>
                                    <button type="button" class="btn btn-success btn-retur-submit" data-approve="1"><i class="fas fa-check mr-1"></i> Simpan &amp; Approve</button>
                                @else
                                    <button type="button" class="btn btn-primary btn-retur-submit" data-approve="0"><i class="fas fa-paper-plane mr-1"></i> Ajukan Retur</button>
                                @endif
                            </div>
                        </div>
                        <small class="text-muted d-block mt-1">
                            @if($returCanApprove)
                                <strong>Simpan &amp; Approve</strong> langsung mengembalikan stok dan mencatat kas keluar. <strong>Ajukan saja</strong> menyimpan sebagai Menunggu Approval.
                            @else
                                Retur akan menunggu approval Admin / Finance. Stok dan kas baru berubah setelah di-approve.
                            @endif
                        </small>
                    </div>
                </div>

                {{-- ===== Pane: detail / approval ===== --}}
                <div class="retur-pane" id="retur-pane-detail">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Detail Retur</h6>
                        <button type="button" class="btn btn-outline-secondary btn-sm retur-back-to-list"><i class="fas fa-arrow-left mr-1"></i> Daftar retur</button>
                    </div>
                    <div id="retur-detail-content"></div>
                    <div class="d-flex justify-content-end mt-3" style="gap:.5rem;" id="retur-detail-actions"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    const RETUR_URLS = {
        list: "{{ route('finance.retur-pembelian.index') }}",
        store: "{{ route('finance.retur-pembelian.store') }}",
        invoices: "{{ route('finance.retur-pembelian.invoices') }}",
        pendingCount: "{{ route('finance.retur-pembelian.pending-count') }}",
        base: "{{ url('finance/retur-pembelian') }}"
    };
    const RETUR_CAN_APPROVE = @json($returCanApprove);
    const RETUR_STATUS = {
        pending: { label: 'Menunggu Approval', badge: 'badge-warning' },
        approved: { label: 'Disetujui', badge: 'badge-success' },
        rejected: { label: 'Ditolak', badge: 'badge-danger' }
    };
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    let returTable = null;
    let returStatusFilter = '';
    let returInvoice = null;
    let returItems = [];
    let returInvoiceHasItems = false;
    let returSubmitting = false;
    let returSearchTimer = null;
    let returSearchSeq = 0;

    function esc(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function rupiah(value) {
        return 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID');
    }

    function qtyText(value) {
        const n = Number(value) || 0;
        return Number.isInteger(n) ? String(n) : n.toLocaleString('id-ID', { maximumFractionDigits: 2 });
    }

    function statusBadge(status) {
        const s = RETUR_STATUS[status] || { label: status || '-', badge: 'badge-secondary' };
        return '<span class="badge ' + s.badge + '">' + esc(s.label) + '</span>';
    }

    function showPane(name) {
        $('#modalReturPembelian .retur-pane').removeClass('active');
        $('#retur-pane-' + name).addClass('active');
    }

    function refreshPendingCount() {
        $.get(RETUR_URLS.pendingCount).done(function (res) {
            const n = Number((res && res.pending) || 0);
            $('#retur-pending-badge').text(n).toggle(n > 0);
            $('#retur-tab-pending-count').text(n);
        });
    }

    function afterReturChanged() {
        refreshPendingCount();
        if (returTable) returTable.ajax.reload(null, false);
    }

    // =====================================================================
    // List
    // =====================================================================
    function initReturTable() {
        if (returTable) {
            returTable.ajax.reload(null, false);
            return;
        }

        returTable = $('#retur-table').DataTable({
            processing: true,
            serverSide: true,
            order: [],
            ajax: {
                url: RETUR_URLS.list,
                data: function (d) { d.status = returStatusFilter; }
            },
            columns: [
                { data: 'retur_number', name: 'retur_number', render: function (data, type) { return type === 'display' ? '<strong>' + esc(data) + '</strong>' : data; } },
                { data: 'tanggal', name: 'created_at', searchable: false },
                { data: 'patient', name: 'patient', orderable: false, searchable: false, render: function (data, type, row) {
                    return type === 'display' ? esc(data) + '<br><small class="text-muted">' + esc(row.invoice_number) + '</small>' : data;
                } },
                { data: 'total_amount', name: 'total_amount', searchable: false, className: 'text-right' },
                { data: 'items_count', name: 'items_count', orderable: false, searchable: false },
                { data: 'user_name', name: 'user_name', orderable: false, searchable: false },
                { data: 'status', name: 'status', searchable: false, render: function (data, type) { return type === 'display' ? statusBadge(data) : data; } },
                { data: null, orderable: false, searchable: false, render: function (row) {
                    let html = '<button type="button" class="btn btn-sm btn-info btn-retur-detail" data-id="' + row.id + '" title="Detail"><i class="fas fa-eye"></i></button>'
                        + ' <a href="' + RETUR_URLS.base + '/' + row.id + '/print" target="_blank" class="btn btn-sm btn-secondary" title="Print"><i class="fas fa-print"></i></a>';
                    // Quick approve / reject without opening the detail.
                    if (RETUR_CAN_APPROVE && row.status === 'pending') {
                        html += ' <button type="button" class="btn btn-sm btn-success btn-retur-approve" data-id="' + row.id + '" data-number="' + esc(row.retur_number) + '" title="Approve"><i class="fas fa-check"></i></button>'
                            + ' <button type="button" class="btn btn-sm btn-outline-danger btn-retur-reject" data-id="' + row.id + '" title="Tolak"><i class="fas fa-times"></i></button>';
                    }
                    return html;
                } }
            ],
            language: { emptyTable: 'Belum ada retur pembelian.' }
        });
    }

    $('#retur-status-tabs').on('click', '.nav-link', function (e) {
        e.preventDefault();
        $('#retur-status-tabs .nav-link').removeClass('active');
        $(this).addClass('active');
        returStatusFilter = $(this).data('status') || '';
        if (returTable) returTable.ajax.reload();
    });

    function openReturModal() {
        showPane('list');
        $('#modalReturPembelian').modal('show');
        initReturTable();
        refreshPendingCount();
    }

    $('#btn-retur-pembelian').on('click', openReturModal);
    $(document).on('click', '.retur-back-to-list', function () {
        showPane('list');
        afterReturChanged();
    });

    // =====================================================================
    // Add (single screen)
    // =====================================================================
    function resetAddForm() {
        returInvoice = null;
        returItems = [];
        $('#retur-invoice-search').val('');
        $('#retur-invoice-results').hide().empty();
        $('#retur-invoice-picker').show();
        $('#retur-selected-invoice').attr('style', 'display:none !important;');
        $('#retur-form-body').hide();
        $('#retur-items-body').empty();
        $('#retur-check-all').prop('checked', false);
        $('#retur-reason, #retur-notes').val('');
        $('#retur-percentage-cut').val('0');
        $('#retur-extra').hide();
        $('#retur-total-preview').text('Rp 0');
    }

    function openAddPane(invoiceId) {
        resetAddForm();
        showPane('add');
        if (invoiceId) {
            selectReturInvoice(invoiceId);
        } else {
            searchReturInvoices('');
            setTimeout(function () { $('#retur-invoice-search').trigger('focus'); }, 200);
        }
    }

    $('#btn-retur-add').on('click', function () { openAddPane(null); });

    // Show the modal, waiting for a closing animation to finish first
    // (Bootstrap ignores modal('show') while the modal is still hiding).
    function showReturModal(callback) {
        const $modal = $('#modalReturPembelian');
        const instance = $modal.data('bs.modal');
        if (instance && instance._isTransitioning && !$modal.hasClass('show')) {
            $modal.one('hidden.bs.modal', function () { showReturModal(callback); });
            return;
        }
        $modal.modal('show');
        if (callback) callback();
    }

    // From a billing row: open the modal directly on that invoice.
    $(document).on('click', '.btn-retur-from-row', function (e) {
        e.preventDefault();
        const invoiceId = $(this).attr('data-invoice-id');
        showReturModal(function () {
            initReturTable();
            refreshPendingCount();
            openAddPane(invoiceId);
        });
    });

    function searchReturInvoices(q) {
        const seq = ++returSearchSeq;
        $('#retur-invoice-results').show().html('<div class="retur-search-result text-muted">Mencari...</div>');
        $.get(RETUR_URLS.invoices, { q: q }).done(function (data) {
            if (seq !== returSearchSeq) return;
            const rows = Array.isArray(data) ? data : [];
            if (!rows.length) {
                $('#retur-invoice-results').html('<div class="retur-search-result text-muted">Tidak ada invoice lunas yang cocok.</div>');
                return;
            }
            $('#retur-invoice-results').html(
                (q ? '' : '<div class="retur-search-result text-muted small" style="cursor:default;">Invoice terbaru — ketik untuk mencari</div>')
                + rows.map(function (inv) {
                    const pasien = (inv.visitation && inv.visitation.pasien) || {};
                    return '<div class="retur-search-result" data-id="' + inv.id + '">'
                        + '<div class="d-flex justify-content-between"><strong>' + esc(pasien.nama || '-') + '</strong><span>' + rupiah(inv.total_amount) + '</span></div>'
                        + '<small class="text-muted">' + esc(inv.invoice_number) + ' · RM ' + esc(pasien.id || '-') + ' · ' + esc(new Date(inv.created_at).toLocaleDateString('id-ID')) + '</small>'
                        + '</div>';
                }).join('')
            );
        }).fail(function () {
            if (seq === returSearchSeq) $('#retur-invoice-results').html('<div class="retur-search-result text-danger">Gagal memuat invoice.</div>');
        });
    }

    $('#retur-invoice-search').on('input', function () {
        const q = ($(this).val() || '').trim();
        clearTimeout(returSearchTimer);
        returSearchTimer = setTimeout(function () { searchReturInvoices(q); }, 300);
    }).on('focus', function () {
        if (!returInvoice) searchReturInvoices(($(this).val() || '').trim());
    });

    $(document).on('click', '#retur-invoice-results .retur-search-result[data-id]', function () {
        selectReturInvoice($(this).data('id'));
    });

    // Close the result list when clicking elsewhere in the modal.
    $('#modalReturPembelian').on('click', function (e) {
        if (!$(e.target).closest('#retur-invoice-picker').length) $('#retur-invoice-results').hide();
    });

    $('#retur-change-invoice').on('click', function () {
        resetAddForm();
        searchReturInvoices('');
        $('#retur-invoice-search').trigger('focus');
    });

    let returSelectSeq = 0;

    function selectReturInvoice(invoiceId) {
        const seq = ++returSelectSeq;
        $('#retur-invoice-results').hide();
        // Immediate feedback while the invoice loads.
        $('#retur-invoice-picker').hide();
        $('#retur-selected-invoice-info').html('<span class="spinner-border spinner-border-sm mr-2"></span>Memuat invoice...');
        $('#retur-selected-invoice').attr('style', '');
        $('#retur-change-invoice').hide();
        $('#retur-form-body').hide();

        $.get(RETUR_URLS.base + '/invoice/' + invoiceId + '/items').done(function (data) {
            if (seq !== returSelectSeq) return; // a newer invoice was picked meanwhile
            returInvoice = data.invoice || {};
            returInvoiceHasItems = (data.items || []).length > 0;
            returItems = (data.items || []).filter(function (item) { return item.can_return; });
            $('#retur-change-invoice').show();

            const pasien = (returInvoice.visitation && returInvoice.visitation.pasien) || {};
            $('#retur-selected-invoice-info').html(
                '<strong>' + esc(pasien.nama || '-') + '</strong> (RM ' + esc(pasien.id || '-') + ') · '
                + esc(returInvoice.invoice_number) + ' · ' + esc(new Date(returInvoice.created_at).toLocaleDateString('id-ID'))
                + ' · Total ' + rupiah(returInvoice.total_amount)
            );
            $('#retur-invoice-picker').hide();
            $('#retur-selected-invoice').attr('style', '');
            $('#retur-form-body').show();
            renderReturItems();
        }).fail(function (xhr) {
            if (seq !== returSelectSeq) return;
            resetAddForm();
            Swal.fire('Gagal', 'Gagal memuat item invoice' + (xhr && xhr.status ? ' (HTTP ' + xhr.status + ')' : '') + '. Silakan coba lagi.', 'error');
        });
    }

    function reducedPrice(unitPrice) {
        const cut = parseFloat($('#retur-percentage-cut').val()) || 0;
        return (Number(unitPrice) || 0) * (1 - cut / 100);
    }

    function renderReturItems() {
        if (!returItems.length) {
            const emptyMessage = returInvoiceHasItems
                ? 'Semua item di invoice ini sudah diretur / sedang diajukan.'
                : 'Invoice ini tidak memiliki item yang bisa diretur.';
            $('#retur-items-body').html('<tr><td colspan="6" class="text-center text-muted py-3">' + emptyMessage + '</td></tr>');
            $('.btn-retur-submit').prop('disabled', true);
            return;
        }
        $('.btn-retur-submit').prop('disabled', false);

        $('#retur-items-body').html(returItems.map(function (item) {
            return '<tr data-item-id="' + item.id + '">'
                + '<td class="text-center"><input type="checkbox" class="retur-item-check" data-item-id="' + item.id + '"></td>'
                + '<td>' + esc(item.name) + (Number(item.returned_quantity) > 0 ? '<br><small class="text-muted">sudah/sedang diretur: ' + qtyText(item.returned_quantity) + '</small>' : '') + '</td>'
                + '<td class="text-center">' + qtyText(item.remaining_quantity) + '</td>'
                + '<td class="text-center"><input type="number" class="form-control form-control-sm retur-item-qty mx-auto" data-item-id="' + item.id + '" step="0.01" min="0.01" max="' + esc(item.remaining_quantity) + '" disabled></td>'
                + '<td class="text-right"><span class="retur-item-price" data-item-id="' + item.id + '">' + rupiah(reducedPrice(item.unit_price)) + '</span></td>'
                + '<td class="text-right font-weight-bold"><span class="retur-item-subtotal" data-item-id="' + item.id + '">Rp 0</span></td>'
                + '</tr>';
        }).join(''));
        updateReturTotals();
    }

    function setItemChecked(itemId, checked) {
        const item = returItems.find(function (i) { return String(i.id) === String(itemId); });
        const $qty = $('.retur-item-qty[data-item-id="' + itemId + '"]');
        $('.retur-item-check[data-item-id="' + itemId + '"]').prop('checked', checked);
        $qty.closest('tr').toggleClass('selected', checked);
        // Ticking an item fills in the full remaining quantity; adjust it only for a partial retur.
        $qty.prop('disabled', !checked).val(checked && item ? item.remaining_quantity : '');
    }

    function updateReturTotals() {
        let total = 0;
        returItems.forEach(function (item) {
            const checked = $('.retur-item-check[data-item-id="' + item.id + '"]').is(':checked');
            const qty = checked ? (parseFloat($('.retur-item-qty[data-item-id="' + item.id + '"]').val()) || 0) : 0;
            const subtotal = qty * reducedPrice(item.unit_price);
            total += subtotal;
            $('.retur-item-price[data-item-id="' + item.id + '"]').text(rupiah(reducedPrice(item.unit_price)));
            $('.retur-item-subtotal[data-item-id="' + item.id + '"]').text(rupiah(subtotal));
        });
        $('#retur-total-preview').text(rupiah(total));
        const all = returItems.length > 0 && $('.retur-item-check:checked').length === returItems.length;
        $('#retur-check-all').prop('checked', all);
    }

    $(document).on('change', '.retur-item-check', function () {
        setItemChecked($(this).data('item-id'), this.checked);
        updateReturTotals();
    });

    // Click anywhere on the row (not on the inputs) toggles it.
    $(document).on('click', '#retur-items-body tr[data-item-id] td', function (e) {
        if ($(e.target).is('input')) return;
        const itemId = $(this).closest('tr').data('item-id');
        const $check = $('.retur-item-check[data-item-id="' + itemId + '"]');
        setItemChecked(itemId, !$check.is(':checked'));
        updateReturTotals();
    });

    $('#retur-check-all').on('change', function () {
        const checked = this.checked;
        returItems.forEach(function (item) { setItemChecked(item.id, checked); });
        updateReturTotals();
    });

    $(document).on('input', '.retur-item-qty', updateReturTotals);
    $('#retur-percentage-cut').on('input', updateReturTotals);

    $(document).on('click', '.retur-reason-chip', function () {
        $('#retur-reason').val($(this).text().trim()).trigger('focus');
    });

    $('#retur-toggle-extra').on('click', function (e) {
        e.preventDefault();
        $('#retur-extra').slideToggle(150);
    });

    $(document).on('click', '.btn-retur-submit', function () {
        if (returSubmitting || !returInvoice) return;
        const approveNow = String($(this).data('approve')) === '1';

        const items = [];
        let invalid = null;
        $('.retur-item-check:checked').each(function () {
            const itemId = $(this).data('item-id');
            const item = returItems.find(function (i) { return String(i.id) === String(itemId); });
            const qty = parseFloat($('.retur-item-qty[data-item-id="' + itemId + '"]').val()) || 0;
            if (qty <= 0 || (item && qty > Number(item.remaining_quantity))) {
                invalid = 'Qty retur untuk ' + (item ? item.name : 'item') + ' harus antara 0 dan ' + (item ? qtyText(item.remaining_quantity) : '-') + '.';
                return false;
            }
            items.push({ invoice_item_id: itemId, quantity_returned: qty });
        });

        if (invalid) return Swal.fire('Periksa qty', invalid, 'warning');
        if (!items.length) return Swal.fire('Pilih item', 'Centang item yang akan diretur.', 'warning');
        if (!($('#retur-reason').val() || '').trim()) {
            $('#retur-reason').trigger('focus');
            return Swal.fire('Alasan wajib', 'Pilih atau tulis alasan retur.', 'warning');
        }

        const send = function () {
            returSubmitting = true;
            $('.btn-retur-submit').prop('disabled', true);
            $.ajax({
                url: RETUR_URLS.store,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                data: {
                    invoice_id: returInvoice.id,
                    reason: $('#retur-reason').val(),
                    notes: $('#retur-notes').val(),
                    percentage_cut: $('#retur-percentage-cut').val() || 0,
                    approve_now: approveNow ? 1 : 0,
                    items: items
                }
            }).done(function (res) {
                Swal.fire({ icon: 'success', title: res.approved ? 'Retur disetujui' : 'Retur diajukan', text: (res.message || '') + ' No: ' + (res.retur_number || '-') });
                resetAddForm();
                showPane('list');
                afterReturChanged();
            }).fail(function (xhr) {
                const json = xhr.responseJSON || {};
                const firstError = json.errors ? Object.values(json.errors)[0][0] : null;
                Swal.fire('Gagal', firstError || json.message || 'Gagal menyimpan retur.', 'error');
            }).always(function () {
                returSubmitting = false;
                $('.btn-retur-submit').prop('disabled', false);
            });
        };

        if (!approveNow) return send();

        Swal.fire({
            icon: 'question',
            title: 'Simpan & approve retur?',
            html: 'Total pengembalian <strong>' + esc($('#retur-total-preview').text()) + '</strong>.<br>Stok akan dikembalikan dan kas keluar dicatat.',
            showCancelButton: true,
            confirmButtonText: 'Ya, simpan & approve',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#28a745'
        }).then(function (result) { if (result.value) send(); });
    });

    // =====================================================================
    // Detail / approval
    // =====================================================================
    function renderReturDetail(data) {
        const items = data.items || [];
        const cut = items.length ? Number(items[0].percentage_cut) || 0 : 0;
        const itemsHtml = items.map(function (item) {
            return '<tr><td>' + esc(item.name) + '</td>'
                + '<td class="text-center">' + qtyText(item.quantity_returned) + '</td>'
                + '<td class="text-right">' + rupiah(item.unit_price) + (cut > 0 ? '<br><small class="text-muted">dari ' + rupiah(item.original_unit_price) + '</small>' : '') + '</td>'
                + '<td class="text-right">' + rupiah(item.total_amount) + '</td></tr>';
        }).join('');

        let approvalHtml;
        if (data.status === 'approved') {
            approvalHtml = '<i class="fas fa-check-circle mr-1"></i>Disetujui oleh <strong>' + esc((data.approver && data.approver.name) || '-') + '</strong>'
                + (data.approved_at ? ' · ' + esc(new Date(data.approved_at).toLocaleString('id-ID')) : '') + '. Stok dan kas sudah disesuaikan.';
        } else if (data.status === 'rejected') {
            approvalHtml = '<i class="fas fa-times-circle mr-1"></i>Ditolak oleh <strong>' + esc((data.approver && data.approver.name) || '-') + '</strong>'
                + (data.approved_at ? ' · ' + esc(new Date(data.approved_at).toLocaleString('id-ID')) : '')
                + '<br>Alasan: ' + esc(data.rejected_reason || '-');
        } else {
            approvalHtml = '<i class="fas fa-hourglass-half mr-1"></i>Menunggu approval Admin / Finance. Stok dan kas belum berubah.';
        }

        $('#retur-detail-content').html(
            '<div class="row">'
            + '<div class="col-md-6 mb-2">'
            + '<div class="retur-detail-label">No. Retur</div><div class="mb-2"><strong>' + esc(data.retur_number) + '</strong> ' + statusBadge(data.status) + '</div>'
            + '<div class="retur-detail-label">Pasien / Invoice</div><div class="mb-2">' + esc(data.patient_name || '-') + ' · ' + esc((data.invoice && data.invoice.invoice_number) || '-') + '</div>'
            + '<div class="retur-detail-label">Diajukan</div><div class="mb-2">' + esc((data.user && data.user.name) || '-') + ' · ' + esc(new Date(data.created_at).toLocaleString('id-ID')) + '</div>'
            + '</div>'
            + '<div class="col-md-6 mb-2">'
            + '<div class="retur-detail-label">Total Pengembalian</div><div class="h5 mb-2">' + rupiah(data.total_amount) + (cut > 0 ? ' <small class="text-muted">(potongan ' + esc(cut) + '%)</small>' : '') + '</div>'
            + '<div class="retur-detail-label">Alasan</div><div class="mb-2">' + esc(data.reason || '-') + '</div>'
            + (data.notes ? '<div class="retur-detail-label">Catatan</div><div class="mb-2">' + esc(data.notes) + '</div>' : '')
            + '</div></div>'
            + '<div class="alert ' + (data.status === 'approved' ? 'alert-success' : (data.status === 'rejected' ? 'alert-danger' : 'alert-warning')) + ' py-2">' + approvalHtml + '</div>'
            + '<table class="table table-sm table-bordered mb-0"><thead class="thead-light"><tr><th>Item</th><th class="text-center">Qty</th><th class="text-right">Harga</th><th class="text-right">Total</th></tr></thead><tbody>' + itemsHtml + '</tbody></table>'
        );

        let actions = '<a href="' + RETUR_URLS.base + '/' + data.id + '/print" target="_blank" class="btn btn-secondary"><i class="fas fa-print mr-1"></i> Print</a>';
        if (data.can_approve) {
            actions += '<button type="button" class="btn btn-outline-danger btn-retur-reject" data-id="' + data.id + '"><i class="fas fa-times mr-1"></i> Tolak</button>'
                + '<button type="button" class="btn btn-success btn-retur-approve" data-id="' + data.id + '" data-number="' + esc(data.retur_number) + '"><i class="fas fa-check mr-1"></i> Approve</button>';
        }
        $('#retur-detail-actions').html(actions);
    }

    function openReturDetail(id) {
        $.get(RETUR_URLS.base + '/' + id).done(function (data) {
            renderReturDetail(data);
            showPane('detail');
        }).fail(function () {
            Swal.fire('Gagal', 'Gagal memuat detail retur.', 'error');
        });
    }

    function refreshAfterDecision(id) {
        afterReturChanged();
        if ($('#retur-pane-detail').hasClass('active')) openReturDetail(id);
    }

    $(document).on('click', '.btn-retur-detail', function () {
        openReturDetail($(this).data('id'));
    });

    $(document).on('click', '.btn-retur-approve', function () {
        const id = $(this).data('id');
        Swal.fire({
            icon: 'question',
            title: 'Approve retur ' + $(this).data('number') + '?',
            text: 'Stok dikembalikan dan pengembalian dana (kas keluar) dicatat.',
            showCancelButton: true,
            confirmButtonText: 'Ya, approve',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#28a745'
        }).then(function (result) {
            if (!result.value) return;
            $.ajax({ url: RETUR_URLS.base + '/' + id + '/approve', method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken } })
                .done(function (res) {
                    Swal.fire({ icon: 'success', title: 'Disetujui', text: res.message, timer: 1800, showConfirmButton: false });
                    refreshAfterDecision(id);
                })
                .fail(function (xhr) {
                    Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal approve retur.', 'error');
                });
        });
    });

    $(document).on('click', '.btn-retur-reject', function () {
        const id = $(this).data('id');
        Swal.fire({
            icon: 'warning',
            title: 'Tolak retur?',
            input: 'text',
            inputPlaceholder: 'Alasan penolakan',
            showCancelButton: true,
            confirmButtonText: 'Tolak',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33',
            preConfirm: function (value) {
                if (!value || !value.trim()) Swal.showValidationMessage('Alasan penolakan wajib diisi');
                return value;
            }
        }).then(function (result) {
            if (!result.value) return;
            $.ajax({ url: RETUR_URLS.base + '/' + id + '/reject', method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken }, data: { rejected_reason: result.value } })
                .done(function (res) {
                    Swal.fire({ icon: 'success', title: 'Ditolak', text: res.message, timer: 1800, showConfirmButton: false });
                    refreshAfterDecision(id);
                })
                .fail(function (xhr) {
                    Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menolak retur.', 'error');
                });
        });
    });

    // Badge on page load + auto-open when redirected from the old Retur Pembelian page (?open=retur).
    refreshPendingCount();
    if (new URLSearchParams(window.location.search).get('open') === 'retur') {
        openReturModal();
    }
});
</script>
@endpush
