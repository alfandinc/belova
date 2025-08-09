@extends('layouts.erm.app')

@section('content')
<div class="container">
    <h1>Buat Permintaan Pembelian</h1>
    <form action="{{ route('erm.permintaan.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label>Tanggal Permintaan</label>
            <input type="date" name="request_date" class="form-control" required>
        </div>
        <hr>
        <h5>Item Permintaan</h5>
        <table class="table table-bordered" id="items-table">
            <thead>
                <tr>
                    <th>Obat</th>
                    <th>Pemasok</th>
                    <th>Jumlah Box</th>
                    <th>Qty Total</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <select name="items[0][obat_id]" class="form-control obat-select" required>
                            <option value="">Pilih Obat</option>
                            @foreach($obats as $obat)
                                <option value="{{ $obat->id }}">{{ $obat->nama }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="items[0][pemasok_id]" class="form-control pemasok-select" required>
                            <option value="">Pilih Pemasok</option>
                            @foreach($pemasoks as $pemasok)
                                <option value="{{ $pemasok->id }}">{{ $pemasok->nama }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" name="items[0][jumlah_box]" class="form-control" min="1" required></td>
                    <td><input type="number" name="items[0][qty_total]" class="form-control" min="1" required></td>
                    <td><button type="button" class="btn btn-danger btn-sm remove-row">Hapus</button></td>
                </tr>
                    <tr class="master-faktur-row">
                        <td colspan="5">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Harga (Master)</label>
                                    <input type="text" class="form-control harga-master" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label>Qty/Box (Master)</label>
                                    <input type="text" class="form-control qtybox-master" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label>Diskon (Master)</label>
                                    <input type="text" class="form-control diskon-master" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label>Diskon Type (Master)</label>
                                    <input type="text" class="form-control diskontype-master" readonly>
                                </div>
                            </div>
                        </td>
                    </tr>
            </tbody>
        </table>
        <button type="button" class="btn btn-secondary mb-3" id="add-row">Tambah Item</button>
        <br>
        <button type="submit" class="btn btn-primary">Simpan Permintaan</button>
        <a href="{{ route('erm.permintaan.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>

<script>
let rowIdx = 1;
const obatOptions = `@foreach($obats as $obat)<option value='{{ $obat->id }}'>{{ $obat->nama }}</option>@endforeach`;
const pemasokOptions = `@foreach($pemasoks as $pemasok)<option value='{{ $pemasok->id }}'>{{ $pemasok->nama }}</option>@endforeach`;

function addPermintaanRow() {
    const table = document.getElementById('items-table').getElementsByTagName('tbody')[0];
    const row = table.insertRow();
    row.innerHTML = `
        <td><select name="items[${rowIdx}][obat_id]" class="form-control obat-select" required><option value="">Pilih Obat</option>${obatOptions}</select></td>
        <td><select name="items[${rowIdx}][pemasok_id]" class="form-control pemasok-select" required><option value="">Pilih Pemasok</option>${pemasokOptions}</select></td>
        <td><input type="number" name="items[${rowIdx}][jumlah_box]" class="form-control" min="1" required></td>
        <td><input type="number" name="items[${rowIdx}][qty_total]" class="form-control" min="1" required></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-row">Hapus</button></td>
    `;
    // Add master faktur row
    const masterRow = table.insertRow();
    masterRow.className = 'master-faktur-row';
    masterRow.innerHTML = `
        <td colspan="5">
            <div class="row">
                <div class="col-md-3">
                    <label>Harga (Master)</label>
                    <input type="text" class="form-control harga-master" readonly>
                </div>
                <div class="col-md-3">
                    <label>Qty/Box (Master)</label>
                    <input type="text" class="form-control qtybox-master" readonly>
                </div>
                <div class="col-md-3">
                    <label>Diskon (Master)</label>
                    <input type="text" class="form-control diskon-master" readonly>
                </div>
                <div class="col-md-3">
                    <label>Diskon Type (Master)</label>
                    <input type="text" class="form-control diskontype-master" readonly>
                </div>
            </div>
        </td>
    `;
    rowIdx++;
    attachMasterFakturListeners();
}

document.getElementById('add-row').onclick = addPermintaanRow;

document.getElementById('items-table').addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-row')) {
        const row = e.target.closest('tr');
        // Remove master faktur row as well
        if (row.nextElementSibling && row.nextElementSibling.classList.contains('master-faktur-row')) {
            row.nextElementSibling.remove();
        }
        row.parentNode.removeChild(row);
    }
});

function attachMasterFakturListeners() {
    // Master faktur autofill
    document.querySelectorAll('#items-table .obat-select, #items-table .pemasok-select').forEach(function(select) {
        select.onchange = function() {
            const row = select.closest('tr');
            const obatId = row.querySelector('.obat-select').value;
            const pemasokId = row.querySelector('.pemasok-select').value;
            const masterRow = row.nextElementSibling && row.nextElementSibling.classList.contains('master-faktur-row') ? row.nextElementSibling : null;
            if (obatId && pemasokId && masterRow) {
                fetch(`/erm/permintaan/master-faktur?obat_id=${obatId}&pemasok_id=${pemasokId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.found) {
                            masterRow.querySelector('.harga-master').value = data.harga;
                            masterRow.querySelector('.qtybox-master').value = data.qty_per_box;
                            masterRow.querySelector('.diskon-master').value = data.diskon;
                            masterRow.querySelector('.diskontype-master').value = data.diskon_type;
                        } else {
                            masterRow.querySelector('.harga-master').value = '';
                            masterRow.querySelector('.qtybox-master').value = '';
                            masterRow.querySelector('.diskon-master').value = '';
                            masterRow.querySelector('.diskontype-master').value = '';
                        }
                    });
            }
        };
    });

    // Qty total autofill
    document.querySelectorAll('#items-table input[name*="[jumlah_box]"]').forEach(function(input) {
        input.oninput = function() {
            const row = input.closest('tr');
            const masterRow = row.nextElementSibling && row.nextElementSibling.classList.contains('master-faktur-row') ? row.nextElementSibling : null;
            const qtyBox = masterRow ? parseInt(masterRow.querySelector('.qtybox-master').value) : 0;
            const jumlahBox = parseInt(input.value);
            const qtyTotalInput = row.querySelector('input[name*="[qty_total]"]');
            if (qtyBox > 0 && jumlahBox > 0 && qtyTotalInput) {
                qtyTotalInput.value = qtyBox * jumlahBox;
            } else if (qtyTotalInput) {
                qtyTotalInput.value = '';
            }
        };
    });
}

// Attach listeners on page load
attachMasterFakturListeners();
</script>
@endsection
