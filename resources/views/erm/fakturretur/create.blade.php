@php
    // Faktur yang dipilih
    $selectedFaktur = isset($fakturbeli) ? $fakturbeli : null;
@endphp
<form id="formRetur" action="{{ route('erm.fakturretur.store') }}" method="POST">
    @csrf
    <div class="form-group">
        <label for="fakturbeli_id">Faktur Pembelian</label>
        <select name="fakturbeli_id" id="fakturbeli_id" class="form-control" required>
            <option value="">- Pilih Faktur -</option>
            @foreach($fakturbelis as $fb)
                <option value="{{ $fb->id }}" {{ $selectedFaktur && $selectedFaktur->id == $fb->id ? 'selected' : '' }}>{{ $fb->no_faktur }} - {{ $fb->pemasok->nama ?? '' }}</option>
            @endforeach
        </select>
    </div>
    <div id="items-container">
        @if($selectedFaktur)
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Obat</th>
                        <th>Gudang</th>
                        <th>Batch</th>
                        <th>Qty Diterima</th>
                        <th>Qty Retur</th>
                        <th>Alasan</th>
                        <th>Hapus</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($selectedFaktur->items as $item)
                        <tr>
                            <td>{{ $item->obat->nama ?? '-' }}</td>
                            <td>{{ $item->gudang->nama ?? '-' }}</td>
                            <td>{{ $item->batch ?? '-' }}</td>
                            <td>{{ $item->qty }}</td>
                            <td>
                                <input type="number" name="items[{{ $loop->index }}][qty]" min="0" max="{{ $item->qty }}" class="form-control" value="0" required>
                                <input type="hidden" name="items[{{ $loop->index }}][fakturbeli_item_id]" value="{{ $item->id }}">
                                <input type="hidden" name="items[{{ $loop->index }}][obat_id]" value="{{ $item->obat_id }}">
                                <input type="hidden" name="items[{{ $loop->index }}][gudang_id]" value="{{ $item->gudang_id }}">
                                <input type="hidden" name="items[{{ $loop->index }}][batch]" value="{{ $item->batch }}">
                                <input type="hidden" name="items[{{ $loop->index }}][expiration_date]" value="{{ $item->expiration_date }}">
                            </td>
                            <td>
                                <input type="text" name="items[{{ $loop->index }}][alasan]" class="form-control" placeholder="Alasan retur">
                            </td>
                            <td class="text-center" style="width:60px;">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" title="Hapus item">&times;</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div class="form-group">
        <label for="tanggal_retur">Tanggal Retur</label>
        <input type="date" name="tanggal_retur" id="tanggal_retur" class="form-control" value="{{ date('Y-m-d') }}" required>
    </div>
    <div class="form-group">
        <label for="notes">Catatan</label>
        <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
    </div>
    <button type="submit" class="btn btn-success">Ajukan Retur</button>
</form>
<script>
// Load item faktur secara dinamis saat faktur dipilih
$('#fakturbeli_id').on('change', function() {
    var fakturId = $(this).val();
    if(fakturId) {
        $.get("{{ route('erm.fakturretur.create') }}", {fakturbeli_id: fakturId}, function(res) {
            var html = $(res).find('#items-container').html();
            $('#items-container').html(html);
        });
    } else {
        $('#items-container').html('');
    }
});

// Hapus baris item saat tombol X diklik
$(document).on('click', '.btn-remove-item', function() {
    $(this).closest('tr').remove();
});

// Info: isi 0 pada qty untuk item yang tidak diretur
</script>
</script>
