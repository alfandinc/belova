@extends('layouts.erm.app')
@section('title', 'ERM | Input Faktur Pembelian')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  
@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">{{ isset($faktur) ? 'Edit Faktur Pembelian' : 'Tambah Faktur Pembelian' }}</h4>
        </div>
        <div class="card-body">
            <form id="fakturbeli-form" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="subtotal" id="input-subtotal" value="{{ isset($faktur) && $faktur->subtotal !== null ? $faktur->subtotal : '' }}">
                <input type="hidden" name="global_diskon" id="input-global-diskon" value="{{ isset($faktur) && $faktur->global_diskon !== null ? $faktur->global_diskon : '' }}">
                <input type="hidden" name="global_pajak" id="input-global-pajak" value="{{ isset($faktur) && $faktur->global_pajak !== null ? $faktur->global_pajak : '' }}">
                <input type="hidden" name="total" id="input-total" value="{{ isset($faktur) && $faktur->total !== null ? $faktur->total : '' }}">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Pemasok</label>
                            <select name="pemasok_id" id="pemasok_id" class="form-control" required style="width:100%">
                                @if(isset($faktur) && isset($faktur->pemasok))
                                    <option value="{{ $faktur->pemasok->id }}" selected>{{ $faktur->pemasok->nama }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Terima</label>
                            <input type="date" name="received_date" class="form-control" required value="{{ isset($faktur) ? $faktur->received_date : '' }}">
                        </div>
                        <div class="form-group">
                            <label>Catatan</label>
                            <input type="text" name="notes" class="form-control" value="{{ $faktur->notes ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>No Faktur</label>
                            <input type="text" name="no_faktur" class="form-control" required value="{{ $faktur->no_faktur ?? '' }}">
                        </div>
                        <div class="form-group">
                            <label>Tanggal Kirim</label>
                            <input type="date" name="ship_date" class="form-control" value="{{ isset($faktur) ? $faktur->ship_date : '' }}">
                        </div>
                        <div class="form-group">
                            <label>Bukti (Foto)</label>
                            <input type="file" name="bukti" class="form-control">
                            @if(isset($faktur) && $faktur->bukti)
                                <a href="{{ asset('storage/'.$faktur->bukti) }}" target="_blank">Lihat Bukti</a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <label>Item Faktur</label>
                    <table class="table table-bordered" id="items-table">
                        <thead>
                            <tr>
                                <th>Obat</th>
                                <th>Qty</th>
                                <th>Harga</th>
                                <th>Diskon</th>
                                <th>Tax</th>
                                <th>Gudang</th>
                                <th>Batch</th>
                                <th>Exp. Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($faktur))
                                @foreach($faktur->items as $i => $item)
                                <tr>
                                    <td>
                                        <select name="items[{{ $i }}][obat_id]" class="form-control obat-select" required style="width:100%">
                                            @if($item->obat)
                                                <option value="{{ $item->obat->id }}" selected>{{ $item->obat->nama }}</option>
                                            @endif
                                        </select>
                                    </td>
                                    <td><input type="number" name="items[{{ $i }}][qty]" class="form-control" min="1" required value="{{ $item->qty }}"></td>
                                    <td><input type="number" name="items[{{ $i }}][harga]" class="form-control" step="0.01" required value="{{ $item->harga }}"></td>
                                    <td><input type="number" name="items[{{ $i }}][diskon]" class="form-control" step="0.01" value="{{ $item->diskon }}"></td>
                                    <td><input type="number" name="items[{{ $i }}][tax]" class="form-control" step="0.01" value="{{ $item->tax }}"></td>
                                    <td>
                                        <select name="items[{{ $i }}][gudang_id]" class="form-control gudang-select" required style="width:100%">
                                            @if($item->gudang)
                                                <option value="{{ $item->gudang->id }}" selected>{{ $item->gudang->nama }}</option>
                                            @endif
                                        </select>
                                    </td>
                                    <td><input type="text" name="items[{{ $i }}][batch]" class="form-control" value="{{ $item->batch ?? '' }}"></td>
                                    <td><input type="date" name="items[{{ $i }}][expiration_date]" class="form-control" value="{{ $item->expiration_date ? \Carbon\Carbon::parse($item->expiration_date)->format('Y-m-d') : '' }}"></td>
                                    <td><button type="button" class="btn btn-danger btn-sm remove-item">Hapus</button></td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-info" id="add-item">Tambah Item</button>
                    <button type="button" class="btn btn-sm btn-warning ml-2" id="debug-hpp">Debug HPP</button>

                    <div class="row justify-content-end mt-3">
                        <div class="col-md-6">
                            <div class="card border-primary shadow-sm">
                                <div class="card-body p-3">
                                    <div class="form-row align-items-center mb-2">
                                        <div class="col-5 text-right pr-0">
                                            <label class="mb-0 font-weight-bold">Subtotal</label>
                                        </div>
                                        <div class="col-7">
                                            <span class="font-weight-bold" id="subtotal-harga" style="font-size:1.1rem;">0</span>
                                        </div>
                                    </div>
                                    <div class="form-row align-items-center mb-2">
                                        <div class="col-5 text-right pr-0">
                                            <label class="mb-0 font-weight-bold">Global Diskon</label>
                                        </div>
                                        <div class="col-7">
                                            <input type="number" id="global-diskon" class="form-control form-control-sm" value="{{ isset($faktur) && $faktur->global_diskon !== null ? $faktur->global_diskon : 0 }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="form-row align-items-center mb-2">
                                        <div class="col-5 text-right pr-0">
                                            <label class="mb-0 font-weight-bold">Global Pajak</label>
                                        </div>
                                        <div class="col-7">
                                            <input type="number" id="global-tax" class="form-control form-control-sm" value="{{ isset($faktur) && $faktur->global_pajak !== null ? $faktur->global_pajak : 0 }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="form-row align-items-center mt-3">
                                        <div class="col-5 text-right pr-0">
                                            <span class="font-weight-bold" style="font-size:1.2rem;">Total Harga :</span>
                                        </div>
                                        <div class="col-7">
                                            <span class="font-weight-bold" id="total-harga" style="font-size:1.3rem;">0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-primary">{{ isset($faktur) ? 'Update' : 'Simpan' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {
    $('#pemasok_id').select2({
        placeholder: 'Cari Pemasok',
        minimumInputLength: 2,
        ajax: {
            url: '/get-pemasok-select2',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data.map(function(item) {
                        return { id: item.id, text: item.nama };
                    })
                };
            },
            cache: true
        }
    });
    // Calculate total on page load (for edit)
    calculateTotalHarga();
});

function itemRow(idx) {
    return `<tr>
        <td><select name="items[${idx}][obat_id]" class="form-control obat-select" required style="width:100%"></select></td>
        <td><input type="number" name="items[${idx}][qty]" class="form-control" min="1" required></td>
        <td><input type="number" name="items[${idx}][harga]" class="form-control" step="0.01" required></td>
        <td><input type="number" name="items[${idx}][diskon]" class="form-control" step="0.01"></td>
        <td><input type="number" name="items[${idx}][tax]" class="form-control" step="0.01"></td>
        <td><select name="items[${idx}][gudang_id]" class="form-control gudang-select" required style="width:100%"></select></td>
        <td><input type="text" name="items[${idx}][batch]" class="form-control"></td>
        <td><input type="date" name="items[${idx}][expiration_date]" class="form-control"></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-item">Hapus</button></td>
    </tr>`;
}

function refreshItemRows() {
    $('#items-table tbody tr').each(function(i, tr) {
        $(tr).find('select, input').each(function() {
            let name = $(this).attr('name');
            if (name) {
                let newName = name.replace(/items\[[0-9]*\]/, `items[${i}]`);
                $(this).attr('name', newName);
            }
        });
    });
}

function initObatSelect2(context) {
    $(context).find('.obat-select').select2({
        placeholder: 'Cari Obat',
        minimumInputLength: 2,
        ajax: {
            url: '/get-obat-select2',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data.map(function(item) {
                        return { id: item.id, text: item.nama };
                    })
                };
            },
            cache: true
        }
    });
    $(context).find('.gudang-select').select2({
        placeholder: 'Cari Gudang',
        minimumInputLength: 2,
        ajax: {
            url: '/get-gudang-select2',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data.map(function(item) {
                        return { id: item.id, text: item.nama };
                    })
                };
            },
            cache: true
        }
    });
}

function calculateTotalHarga() {
    let subtotal = 0;
    $('#items-table tbody tr').each(function() {
        let qty = parseFloat($(this).find('input[name*="[qty]"]').val()) || 0;
        let harga = parseFloat($(this).find('input[name*="[harga]"]').val()) || 0;
        let diskon = parseFloat($(this).find('input[name*="[diskon]"]').val()) || 0;
        let tax = parseFloat($(this).find('input[name*="[tax]"]').val()) || 0;
        let itemSubtotal = (qty * harga) - diskon + tax;
        subtotal += itemSubtotal;
    });
    $('#subtotal-harga').text(subtotal.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    let globalDiskon = parseFloat($('#global-diskon').val()) || 0;
    let globalTax = parseFloat($('#global-tax').val()) || 0;
    let grandTotal = subtotal - globalDiskon + globalTax;
    if (grandTotal < 0) grandTotal = 0;
    $('#total-harga').text(grandTotal.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    // Update hidden inputs for backend
    $('#input-subtotal').val(subtotal.toFixed(2));
    $('#input-global-diskon').val(globalDiskon.toFixed(2));
    $('#input-global-pajak').val(globalTax.toFixed(2));
    $('#input-total').val(grandTotal.toFixed(2));
}

$('#add-item').on('click', function() {
    let idx = $('#items-table tbody tr').length;
    let $row = $(itemRow(idx));
    $('#items-table tbody').append($row);
    initObatSelect2($row);
    calculateTotalHarga();
});

$(document).on('click', '.remove-item', function() {
    $(this).closest('tr').remove();
    refreshItemRows();
    calculateTotalHarga();
});

// Recalculate total when qty, harga, diskon, tax, or global discount/tax changes
$(document).on('input', 'input[name*="[qty]"], input[name*="[harga]"], input[name*="[diskon]"], input[name*="[tax]"], #global-diskon, #global-tax', function() {
    calculateTotalHarga();
});

// Initialize select2 for existing rows on edit
$(document).ready(function() {
    $('#items-table tbody tr').each(function() {
        initObatSelect2(this);
    });
    calculateTotalHarga();
});

$('#fakturbeli-form').on('submit', function(e) {
    e.preventDefault();
    refreshItemRows();
    let formData = new FormData(this);
    let isEdit = {{ isset($faktur) ? 'true' : 'false' }};
    let url = isEdit ? '{{ isset($faktur) ? route('erm.fakturbeli.update', $faktur->id) : '' }}' : '{{ route('erm.fakturbeli.store') }}';
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            if(res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                setTimeout(function() {
                    window.location.href = '{{ route('erm.fakturbeli.index') }}';
                }, 1500);
            }
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Gagal menyimpan faktur!'
            });
        }
    });
});

// Debug HPP calculation for each item
$('#debug-hpp').on('click', function() {
    let globalDiskon = parseFloat($('#global-diskon').val()) || 0;
    let globalPajak = parseFloat($('#global-tax').val()) || 0;
    let items = [];
    let totalItemSubtotal = 0;
    // First, collect all item subtotals
    $('#items-table tbody tr').each(function(idx) {
        let qty = parseFloat($(this).find('input[name*="[qty]"]').val()) || 0;
        let harga = parseFloat($(this).find('input[name*="[harga]"]').val()) || 0;
        let diskon = parseFloat($(this).find('input[name*="[diskon]"]').val()) || 0;
        let tax = parseFloat($(this).find('input[name*="[tax]"]').val()) || 0;
        let subtotal = (qty * harga) - diskon + tax;
        items.push({ idx, qty, harga, diskon, tax, subtotal });
        totalItemSubtotal += subtotal;
    });
    let hppList = [];
    if (globalDiskon === 0 && globalPajak === 0) {
        // HPP is just harga item
        hppList = items.map((item, i) => `Item ${i+1}: HPP = ${item.harga}`);
    } else {
        // Distribute global diskon and pajak proportionally
        items.forEach(function(item, i) {
            let prop = totalItemSubtotal > 0 ? item.subtotal / totalItemSubtotal : 0;
            let hpp = item.harga;
            // Apply proportional global diskon and pajak to this item's subtotal
            let globalDiskonItem = globalDiskon * prop;
            let globalPajakItem = globalPajak * prop;
            let hppFinal = (item.subtotal - globalDiskonItem + globalPajakItem) / (item.qty || 1);
            hppList.push(`Item ${i+1}: HPP = ${hppFinal.toFixed(2)} (harga: ${item.harga}, subtotal: ${item.subtotal.toFixed(2)}, prop: ${prop.toFixed(4)})`);
        });
    }
    alert(hppList.join('\n'));
});
</script>
@endpush
