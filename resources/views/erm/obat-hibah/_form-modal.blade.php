{{-- Input Obat Hibah in a modal. window.HibahForm.open() --}}
<style>
    #modalHibah .modal-dialog { max-width: 1100px; width: 96%; }
    #hibahItems th { font-size: .78rem; white-space: nowrap; }
    #hibahItems td { vertical-align: top; padding: .3rem; }
    #hibahItems .form-control, #hibahItems .custom-select { font-size: .85rem; }
</style>

<div class="modal fade" id="modalHibah" tabindex="-1" role="dialog" aria-labelledby="modalHibahTitle" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <form class="modal-content" id="hibahForm" enctype="multipart/form-data" autocomplete="off">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="modalHibahTitle">Input Obat Hibah</h5>
                    <small class="text-muted">Stok masuk ke gudang setelah disetujui Admin / Farmasi.</small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label class="small font-weight-bold mb-1" for="hb_received_date">Tanggal Terima <span class="text-danger">*</span></label>
                        <input type="date" name="received_date" id="hb_received_date" class="form-control" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="small font-weight-bold mb-1" for="hb_sumber">Sumber Hibah</label>
                        <input type="text" name="sumber" id="hb_sumber" class="form-control" maxlength="255" placeholder="Nama pemberi / sumber">
                    </div>
                    <div class="form-group col-md-5">
                        <label class="small font-weight-bold mb-1" for="hb_bukti">Foto bukti</label>
                        <input type="file" name="bukti" id="hb_bukti" class="form-control-file" accept="image/*">
                    </div>
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold mb-1" for="hb_notes">Catatan</label>
                    <input type="text" name="notes" id="hb_notes" class="form-control" maxlength="1000">
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-end mb-2">
                    <div class="font-weight-bold">Obat yang diterima</div>
                    <div class="d-flex align-items-center" style="gap:6px">
                        <label class="small text-muted mb-0" for="hb_gudang_default">Gudang semua baris</label>
                        <select id="hb_gudang_default" class="custom-select custom-select-sm" style="width:auto">
                            @foreach($gudangs as $gudang)
                                <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-2" id="hibahItems">
                        <thead class="thead-light">
                            <tr>
                                <th style="min-width:280px">Obat <span class="text-danger">*</span></th>
                                <th style="width:170px">Gudang <span class="text-danger">*</span></th>
                                <th style="width:110px">Qty <span class="text-danger">*</span></th>
                                <th style="width:150px">Batch <span class="text-danger">*</span></th>
                                <th style="width:150px">Kadaluarsa <span class="text-danger">*</span></th>
                                <th style="width:34px"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="hb_add"><i class="fas fa-plus"></i> Tambah Obat</button>
                <div class="small text-muted mt-2">Hanya obat aktif. Batch dan tanggal kadaluarsa wajib, sesuai kemasan.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="hb_submit"><i class="fas fa-save"></i> Simpan Obat Hibah</button>
            </div>
        </form>
    </div>
</div>

<script>
window.HibahForm = (function () {
    var STORE_URL = '{{ route('erm.obat-hibah.store') }}';
    var OBAT_URL = '{{ url('/erm/ajax/obat') }}';
    var CSRF = '{{ csrf_token() }}';
    var savedCallback = function () {};
    var $modal, $tbody;

    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }

    function addRow() {
        var $tr = $('<tr>'
            + '<td><select class="hb-obat" style="width:100%"></select></td>'
            + '<td><select class="custom-select custom-select-sm hb-gudang">' + $('#hb_gudang_default').html() + '</select></td>'
            + '<td><input type="number" class="form-control form-control-sm hb-qty" min="0.0001" step="0.0001"></td>'
            + '<td><input type="text" class="form-control form-control-sm hb-batch" maxlength="255"></td>'
            + '<td><input type="date" class="form-control form-control-sm hb-ed"></td>'
            + '<td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger p-0 hb-remove" title="Hapus baris"><i class="fas fa-times"></i></button></td>'
            + '</tr>');
        $tbody.append($tr);
        $tr.find('.hb-gudang').val($('#hb_gudang_default').val());
        $tr.find('.hb-obat').select2({
            dropdownParent: $modal,
            placeholder: 'Cari obat aktif...',
            minimumInputLength: 2,
            width: '100%',
            ajax: { url: OBAT_URL, dataType: 'json', delay: 250, data: function (p) { return { q: p.term }; }, processResults: function (d) { return { results: d }; } },
            language: { inputTooShort: function () { return 'Ketik minimal 2 huruf'; }, noResults: function () { return 'Obat tidak ditemukan'; } }
        }).on('select2:select', function () { $tr.find('.hb-qty').trigger('focus'); });
        return $tr;
    }

    function reset() {
        $('#hibahForm')[0].reset();
        $('#hb_received_date').val(moment().format('YYYY-MM-DD'));
        $tbody.find('.hb-obat').each(function () { if ($(this).data('select2')) $(this).select2('destroy'); });
        $tbody.empty();
        addRow();
    }

    function open() {
        reset();
        $modal.modal('show');
    }

    function validate() {
        var problems = [];
        var count = 0;
        $tbody.find('tr').each(function () {
            var $tr = $(this);
            var nama = $tr.find('.hb-obat option:selected').text();
            if (!$tr.find('.hb-obat').val()) {
                if ($tr.find('.hb-qty').val() || $tr.find('.hb-batch').val()) problems.push('Ada baris tanpa obat.');
                return;
            }
            count++;
            if (!(parseFloat($tr.find('.hb-qty').val()) > 0)) problems.push(nama + ': qty harus lebih dari 0.');
            if (!$.trim($tr.find('.hb-batch').val()) || !$tr.find('.hb-ed').val()) problems.push(nama + ': batch dan kadaluarsa wajib diisi.');
        });
        if (!count) problems.push('Tambahkan minimal satu obat.');
        return problems.filter(function (v, i, a) { return a.indexOf(v) === i; });
    }

    function init() {
        $modal = $('#modalHibah');
        $tbody = $('#hibahItems tbody');

        $('#hb_add').on('click', function () { addRow().find('.hb-obat').select2('open'); });
        $tbody.on('click', '.hb-remove', function () {
            if ($tbody.find('tr').length <= 1) return;
            $(this).closest('tr').remove();
        });
        $('#hb_gudang_default').on('change', function () { $tbody.find('.hb-gudang').val($(this).val()); });

        $('#hibahForm').on('submit', function (e) {
            e.preventDefault();
            var problems = validate();
            if (problems.length) {
                Swal.fire({ icon: 'warning', title: 'Periksa lagi', html: '<div class="text-left small">' + problems.map(esc).join('<br>') + '</div>' });
                return;
            }
            var fd = new FormData(this);
            fd.set('_token', CSRF);
            var i = 0;
            $tbody.find('tr').each(function () {
                var $tr = $(this);
                if (!$tr.find('.hb-obat').val()) return;
                fd.append('items[' + i + '][obat_id]', $tr.find('.hb-obat').val());
                fd.append('items[' + i + '][gudang_id]', $tr.find('.hb-gudang').val());
                fd.append('items[' + i + '][qty]', $tr.find('.hb-qty').val());
                fd.append('items[' + i + '][batch]', $.trim($tr.find('.hb-batch').val()));
                fd.append('items[' + i + '][expiration_date]', $tr.find('.hb-ed').val());
                i++;
            });
            var $btn = $('#hb_submit').prop('disabled', true);
            $.ajax({ url: STORE_URL, method: 'POST', data: fd, processData: false, contentType: false, headers: { Accept: 'application/json' } })
                .done(function (res) {
                    $modal.modal('hide');
                    savedCallback(res);
                })
                .fail(function (xhr) {
                    var json = xhr.responseJSON || {};
                    var list = json.errors ? [].concat.apply([], Object.values(json.errors)) : [json.message || 'Obat hibah gagal disimpan.'];
                    Swal.fire({ icon: 'warning', title: 'Belum tersimpan', html: '<div class="text-left small">' + list.filter(function (v, k, a) { return a.indexOf(v) === k; }).map(esc).join('<br>') + '</div>' });
                })
                .always(function () { $btn.prop('disabled', false); });
        });
    }

    $(init);

    return {
        open: open,
        onSaved: function (fn) { savedCallback = fn; }
    };
})();
</script>
