{{-- Retur ke Supplier form (loaded into a modal). Obat, gudang, batch and ED are taken from the faktur line on the server. --}}
@php
    $selectedFaktur = $fakturbeli ?? null;
    $bisaRetur = $selectedFaktur && in_array($selectedFaktur->status, ['diapprove', 'diretur_sebagian'], true);
    $fmt = function ($n) { return rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ','); };
@endphp
<form id="formRetur" action="{{ route('erm.fakturretur.store') }}" method="POST">
    @csrf
    <div class="form-row">
        <div class="form-group col-md-8">
            <label for="fakturbeli_id">Faktur Pembelian <small class="text-muted">(hanya yang sudah disetujui)</small></label>
            <select name="fakturbeli_id" id="fakturbeli_id" class="form-control" required style="width:100%">
                @if($selectedFaktur)
                    <option value="{{ $selectedFaktur->id }}" selected>{{ ($selectedFaktur->no_faktur ?: 'Faktur #' . $selectedFaktur->id) . ' — ' . ($selectedFaktur->pemasok->nama ?? '-') }}</option>
                @endif
            </select>
            <small class="text-muted">Ketik nomor faktur, nomor permintaan, nama pemasok, atau nama obat.</small>
        </div>
        <div class="form-group col-md-4">
            <label for="tanggal_retur">Tanggal Retur</label>
            <input type="date" name="tanggal_retur" id="tanggal_retur" class="form-control" value="{{ date('Y-m-d') }}" required>
        </div>
    </div>

    <div id="items-container">
        @if($selectedFaktur && !$bisaRetur)
            <div class="alert alert-warning small">Faktur ini belum disetujui, jadi stoknya belum masuk gudang dan tidak bisa diretur.</div>
        @elseif($selectedFaktur)
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Obat</th>
                            <th>Gudang / Batch</th>
                            <th class="text-right">Diterima</th>
                            <th class="text-right" title="Diterima dikurangi retur sebelumnya">Bisa diretur</th>
                            <th style="width:110px">Qty Retur</th>
                            <th>Alasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($selectedFaktur->items->where('qty', '>', 0) as $item)
                            @php
                                $sisa = $sisaRetur[$item->id] ?? 0;
                                $stok = $stokBatch[$item->id] ?? 0;
                            @endphp
                            <tr class="{{ $sisa <= 0 ? 'text-muted' : '' }}">
                                <td>{{ $item->obat->nama ?? '-' }}</td>
                                <td>
                                    {{ $item->gudang->nama ?? '-' }}<br>
                                    <small><code>{{ $item->batch ?? '-' }}</code>{{ $item->expiration_date ? ' · ED ' . \Carbon\Carbon::parse($item->expiration_date)->format('d/m/Y') : '' }}</small>
                                    @if($sisa > 0 && $stok < $sisa)
                                        <br><small class="text-warning" title="Stok batch ini di gudang saat ini">stok batch tinggal {{ $fmt($stok) }}</small>
                                    @endif
                                </td>
                                <td class="text-right">{{ $fmt($item->qty) }}</td>
                                <td class="text-right font-weight-bold">{{ $fmt($sisa) }}</td>
                                <td>
                                    <input type="number" name="items[{{ $loop->index }}][qty]" min="0" max="{{ (int) $sisa }}" class="form-control form-control-sm retur-qty" value="0" {{ $sisa <= 0 ? 'disabled' : '' }}>
                                    <input type="hidden" name="items[{{ $loop->index }}][fakturbeli_item_id]" value="{{ $item->id }}" {{ $sisa <= 0 ? 'disabled' : '' }}>
                                </td>
                                <td>
                                    <input type="text" name="items[{{ $loop->index }}][alasan]" class="form-control form-control-sm" maxlength="255" placeholder="mis. rusak, salah kirim" {{ $sisa <= 0 ? 'disabled' : '' }}>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <small class="text-muted d-block mb-3">Isi qty retur hanya untuk obat yang dikembalikan; biarkan 0 untuk yang lain. Stok berkurang setelah retur disetujui.</small>
        @endif
    </div>

    <div class="form-group">
        <label for="notes">Catatan</label>
        <textarea name="notes" id="notes" class="form-control" rows="2" maxlength="1000"></textarea>
    </div>
    <div class="text-right">
        <button type="submit" class="btn btn-success" {{ $bisaRetur ? '' : 'disabled' }} id="btn-submit-retur"><i class="fas fa-paper-plane"></i> Ajukan Retur</button>
    </div>
</form>
<script>
(function () {
    var $select = $('#fakturbeli_id');
    var esc = function (v) { return $('<div>').text(v == null ? '' : v).html(); };
    var rupiah = function (v) { return 'Rp ' + Number(v || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 }); };

    // Searched as you type: the list of approved fakturs grows too long for a plain dropdown
    $select.select2({
        dropdownParent: $select.closest('.modal').length ? $select.closest('.modal') : $(document.body),
        placeholder: 'Cari faktur...',
        width: '100%',
        allowClear: true,
        ajax: {
            url: "{{ route('erm.fakturretur.faktur-options') }}",
            dataType: 'json',
            delay: 250,
            data: function (p) { return { q: p.term }; },
            processResults: function (data) { return { results: data.results }; }
        },
        templateResult: function (f) {
            if (f.loading || !f.no_faktur) return f.text;
            return $('<div><div><strong>' + esc(f.no_faktur) + '</strong> — ' + esc(f.pemasok || '-')
                + (f.sebagian ? ' <span class="badge badge-warning">sudah diretur sebagian</span>' : '') + '</div>'
                + '<small class="text-muted">' + esc(f.tanggal || '-') + ' · ' + f.jumlah_item + ' obat · ' + rupiah(f.total)
                + (f.no_permintaan ? ' · ' + esc(f.no_permintaan) : '') + '</small></div>');
        },
        templateSelection: function (f) { return f.text; },
        language: {
            searching: function () { return 'Mencari...'; },
            noResults: function () { return 'Faktur tidak ditemukan (hanya faktur yang sudah disetujui)'; }
        }
    });
    if (!$select.val()) setTimeout(function () { $select.select2('open'); }, 300);

    // Load the faktur lines when another faktur is chosen
    $select.on('change', function () {
        var fakturId = $(this).val();
        if (!fakturId) {
            $('#items-container').html('');
            $('#btn-submit-retur').prop('disabled', true);
            return;
        }
        $('#items-container').html('<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i> Memuat isi faktur...</div>');
        $.get("{{ route('erm.fakturretur.create') }}", { fakturbeli_id: fakturId }, function (res) {
            var $res = $('<div>').html(res);
            $('#items-container').html($res.find('#items-container').html());
            $('#btn-submit-retur').prop('disabled', $res.find('#btn-submit-retur').prop('disabled'));
        });
    });
})();
</script>
