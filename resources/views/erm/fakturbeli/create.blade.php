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
                                <label>Pemasok <span class="text-danger">*</span></label>
                            <select name="pemasok_id" id="pemasok_id" class="form-control" required style="width:100%" @if(isset($faktur)) disabled @endif>
                                @if(isset($faktur) && isset($faktur->pemasok))
                                    <option value="{{ $faktur->pemasok->id }}" selected>{{ $faktur->pemasok->nama }}</option>
                                @endif
                            </select>
                            @if(isset($faktur))
                                <input type="hidden" name="pemasok_id" value="{{ $faktur->pemasok->id }}">
                            @endif
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Tanggal Permintaan</label>
                                <input type="date" name="requested_date" class="form-control" value="{{ isset($faktur) ? $faktur->requested_date : '' }}" readonly>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Tanggal Kirim</label>
                                <input type="date" name="ship_date" class="form-control" value="{{ isset($faktur) ? $faktur->ship_date : '' }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Tanggal Terima <span class="text-danger">*</span></label>
                                <input type="date" name="received_date" class="form-control" required value="{{ old('received_date', isset($faktur) && $faktur->received_date ? $faktur->received_date : date('Y-m-d')) }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Jatuh Tempo</label>
                                <input type="date" name="due_date" class="form-control" value="{{ isset($faktur) ? $faktur->due_date : '' }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Catatan</label>
                            <input type="text" name="notes" class="form-control" value="{{ $faktur->notes ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                                <label>No Faktur <span class="text-danger">*</span></label>
                                <input type="text" name="no_faktur" class="form-control" required value="{{ $faktur->no_faktur ?? '' }}">
                        </div>
                        <!-- Tanggal Kirim now moved to the row above -->
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
                                <th>Diminta</th>
                                <th>Qty</th>
                                <th>Harga</th>
                                <th>Total Amount</th>
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
                                <tr data-item-index="{{ $i }}">
                                    <td>
                                        <select name="items[{{ $i }}][obat_id]" class="form-control obat-select" required style="width:100%" @if(isset($faktur)) disabled @endif>
                                            @if($item->obat)
                                                <option value="{{ $item->obat->id }}" selected>{{ $item->obat->nama }}</option>
                                            @endif
                                        </select>
                                        @if(isset($faktur))
                                            <input type="hidden" name="items[{{ $i }}][obat_id]" value="{{ $item->obat->id }}">
                                        @endif
                                    </td>
                                    <td>
                                        <input type="number" name="items[{{ $i }}][diminta]" class="form-control diminta-field" readonly value="{{ $item->diminta }}" {{ isset($faktur) && $faktur->status == 'diminta' ? 'readonly' : '' }}>
                                    </td>
                                    <td><input type="number" name="items[{{ $i }}][qty]" class="form-control item-qty" min="1" required value="{{ $item->qty }}"></td>
                                    <td><input type="number" name="items[{{ $i }}][harga]" class="form-control item-harga" step="0.01" required value="{{ $item->harga }}" placeholder="Fill"></td>
                                    <td><input type="number" name="items[{{ $i }}][total]" class="form-control item-total" step="0.01" placeholder="Fill" value="{{ $item->qty * $item->harga }}"></td>
                                    <td>
                                        <div class="input-group">
                                            <input type="number" name="items[{{ $i }}][diskon]" class="form-control" step="0.01" value="{{ $item->diskon }}">
                                            <select name="items[{{ $i }}][diskon_type]" class="form-control" style="max-width:60px">
                                                <option value="nominal" {{ isset($item->diskon_type) && $item->diskon_type == 'nominal' ? 'selected' : '' }}>Rp</option>
                                                <option value="persen" {{ isset($item->diskon_type) && $item->diskon_type == 'persen' ? 'selected' : '' }}>%</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <input type="number" name="items[{{ $i }}][tax]" class="form-control" step="0.01" value="{{ $item->tax }}">
                                            <select name="items[{{ $i }}][tax_type]" class="form-control" style="max-width:60px">
                                                <option value="nominal" {{ isset($item->tax_type) && $item->tax_type == 'nominal' ? 'selected' : '' }}>Rp</option>
                                                <option value="persen" {{ isset($item->tax_type) && $item->tax_type == 'persen' ? 'selected' : '' }}>%</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td>
                                        <select name="items[{{ $i }}][gudang_id]" class="form-control gudang-select" required style="width:100%">
                                            @if($item->gudang)
                                                <option value="{{ $item->gudang->id }}" selected>{{ $item->gudang->nama }}</option>
                                            @else
                                                <option value="1" selected>Gudang Utama</option>
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
                    @if(!isset($faktur))
                    <button type="button" class="btn btn-sm btn-info" id="add-item">Tambah Item</button>
                    @endif
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
                                            <div class="input-group">
                                                <input type="number" id="global-diskon" class="form-control form-control-sm" value="{{ isset($faktur) && $faktur->global_diskon !== null ? $faktur->global_diskon : 0 }}" step="0.01" min="0">
                                                <select id="global-diskon-type" class="form-control form-control-sm" style="max-width:60px">
                                                    <option value="nominal">Rp</option>
                                                    <option value="persen">%</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row align-items-center mb-2">
                                        <div class="col-5 text-right pr-0">
                                            <label class="mb-0 font-weight-bold">Global Pajak</label>
                                        </div>
                                        <div class="col-7">
                                            <div class="input-group">
                                                <input type="number" id="global-tax" class="form-control form-control-sm" value="{{ isset($faktur) && $faktur->global_pajak !== null ? $faktur->global_pajak : 11 }}" step="0.01" min="0">
                                                <select id="global-tax-type" class="form-control form-control-sm" style="max-width:60px">
                                                    <option value="nominal" {{ (isset($faktur) && isset($faktur->global_pajak_type) && $faktur->global_pajak_type == 'nominal') ? 'selected' : '' }}>Rp</option>
                                                    <option value="persen" {{ (!isset($faktur) || (isset($faktur->global_pajak_type) && $faktur->global_pajak_type == 'persen') || !isset($faktur->global_pajak_type)) ? 'selected' : '' }}>%</option>
                                                </select>
                                            </div>
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
                <!-- Removed duplicate global diskon and pajak input row -->
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
    return `<tr data-item-index="${idx}">
        <td><select name="items[${idx}][obat_id]" class="form-control obat-select" required style="width:100%"></select><span class="text-danger">*</span></td>
        <td><input type="number" name="items[${idx}][diminta]" class="form-control diminta-field" readonly value="0"></td>
        <td><input type="number" name="items[${idx}][qty]" class="form-control item-qty" min="1" required><span class="text-danger">*</span></td>
        <td><input type="number" name="items[${idx}][harga]" class="form-control item-harga" step="0.01" required placeholder="Fill"><span class="text-danger">*</span></td>
        <td><input type="number" name="items[${idx}][total]" class="form-control item-total" step="0.01" placeholder="Fill"></td>
        <td>
            <div class="input-group">
                <input type="number" name="items[${idx}][diskon]" class="form-control" step="0.01">
                <select name="items[${idx}][diskon_type]" class="form-control" style="max-width:60px">
                    <option value="nominal">Rp</option>
                    <option value="persen">%</option>
                </select>
            </div>
        </td>
        <td>
            <div class="input-group">
                <input type="number" name="items[${idx}][tax]" class="form-control" step="0.01">
                <select name="items[${idx}][tax_type]" class="form-control" style="max-width:60px">
                    <option value="nominal">Rp</option>
                    <option value="persen">%</option>
                </select>
            </div>
        </td>
    <td><select name="items[${idx}][gudang_id]" class="form-control gudang-select" required style="width:100%"><option value="1" selected>Gudang Utama</option></select><span class="text-danger">*</span></td>
        <td><input type="text" name="items[${idx}][batch]" class="form-control"></td>
        <td><input type="date" name="items[${idx}][expiration_date]" class="form-control"></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-item">Hapus</button></td>
    </tr>`;
}

function refreshItemRows() {
    $('#items-table tbody tr').each(function(i, tr) {
        // Update the data-item-index attribute
        $(tr).attr('data-item-index', i);
        // Update input names
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
    $(context).find('.gudang-select').each(function() {
        var select = $(this);
        // If no value is set, set to 1 and trigger change for select2
        if (!select.val()) {
            select.val('1').trigger('change');
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
    // Sum all total amount fields for subtotal
    let subtotal = 0;
    $('#items-table tbody tr').each(function() {
        let totalAmount = parseFloat($(this).find('input[name*="[total]"]').val()) || 0;
        subtotal += totalAmount;
    });
    $('#subtotal-harga').text(subtotal.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    let globalDiskon = parseFloat($('#global-diskon').val()) || 0;
    let globalDiskonType = $('#global-diskon-type').val() || 'nominal';
    let globalTax = parseFloat($('#global-tax').val()) || 0;
    let globalTaxType = $('#global-tax-type').val() || 'nominal';
    let globalDiskonValue = globalDiskonType === 'persen' ? (subtotal * globalDiskon / 100) : globalDiskon;
    let globalTaxValue = globalTaxType === 'persen' ? (subtotal * globalTax / 100) : globalTax;
    let grandTotal = subtotal - globalDiskonValue + globalTaxValue;
    if (grandTotal < 0) grandTotal = 0;
    $('#total-harga').text(grandTotal.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    // Update hidden inputs for backend
    $('#input-subtotal').val(subtotal.toFixed(2));
    $('#input-global-diskon').val(globalDiskonValue.toFixed(2));
    $('#input-global-pajak').val(globalTaxValue.toFixed(2));
    $('#input-total').val(grandTotal.toFixed(2));
}

$('#add-item').on('click', function() {
    let idx = $('#items-table tbody tr').length;
    let $row = $(itemRow(idx));
    $('#items-table tbody').append($row);
    
    // Add calculation button to the new row using the shared function
    // ...existing code...
    
    initObatSelect2($row);
    calculateTotalHarga();
});

$(document).on('click', '.remove-item', function() {
    $(this).closest('tr').remove();
    refreshItemRows();
    calculateTotalHarga();
});

// Recalculate total when qty, harga, diskon, tax, or global discount/tax changes
$(document).on('input change', 'input[name*="[qty]"], input[name*="[harga]"], input[name*="[diskon]"], input[name*="[tax]"], select[name*="[diskon_type]"], select[name*="[tax_type]"], #global-diskon, #global-tax, #global-diskon-type, #global-tax-type', function() {
    calculateTotalHarga();
});

// Recalculate subtotal and total when Total Amount changes
$(document).on('input change', 'input[name*="[total]"]', function() {
    calculateTotalHarga();
});

// --- Enhanced automatic harga calculation for all relevant fields (handles all persen/nominal combinations) ---
function recalculateHargaForRow(row) {
    var qty = parseFloat(row.find('input[name*="[qty]"]').val()) || 0;
    var total = parseFloat(row.find('input[name*="[total]"]').val()) || 0;
    var diskon = parseFloat(row.find('input[name*="[diskon]"]').val()) || 0;
    var diskonType = row.find('select[name*="[diskon_type]"]').val();
    var tax = parseFloat(row.find('input[name*="[tax]"]').val()) || 0;
    var taxType = row.find('select[name*="[tax_type]"]').val();
    if (qty > 0 && total > 0) {
        var unitPrice = total / qty;
        // Apply percentage/nominal logic for diskon and tax
        if (diskonType === 'persen') {
            unitPrice = unitPrice / ((100 - diskon) / 100);
        } else if (diskonType === 'nominal') {
            unitPrice = unitPrice + (diskon / qty);
        }
        if (taxType === 'persen') {
            unitPrice = unitPrice / ((100 + tax) / 100);
        } else if (taxType === 'nominal') {
            unitPrice = unitPrice - (tax / qty);
        }
        row.find('input[name*="[harga]"]').val(unitPrice.toFixed(2));
    } else {
        row.find('input[name*="[harga]"]').val('');
    }
}

$(document).on('input change', 'input[name*="[qty]"], input[name*="[total]"], input[name*="[diskon]"], input[name*="[tax]"], select[name*="[diskon_type]"], select[name*="[tax_type]"]', function() {
    var row = $(this).closest('tr');
    recalculateHargaForRow(row);
});
// --- End enhanced automatic harga calculation ---

// Initialize select2 for existing rows on edit
$(document).ready(function() {
    $('#items-table tbody tr').each(function() {
        initObatSelect2(this);
    });
    calculateTotalHarga();
});

$('#fakturbeli-form').on('submit', function(e) {
    e.preventDefault();
    
    // Make sure harga fields are not readonly during submission
    $('.item-harga').prop('readonly', false);
    
    // Remove all calculate buttons before submitting
    $('.calculate-harga').remove();
    
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
            let msg = 'Gagal menyimpan faktur!';
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.no_faktur) {
                msg = xhr.responseJSON.errors.no_faktur[0];
            }
            Swal.fire({
                icon: 'warning',
                title: 'Peringatan',
                text: msg
            });
        }
    });
});

// Debug HPP calculation for each item
$('#debug-hpp').on('click', function() {
    let globalDiskon = parseFloat($('#global-diskon').val()) || 0;
    let globalDiskonType = $('#global-diskon-type').val() || 'nominal';
    let globalPajak = parseFloat($('#global-tax').val()) || 0;
    let globalPajakType = $('#global-tax-type').val() || 'nominal';
    let items = [];
    let totalItemSubtotal = 0;
    
    // First, collect all item subtotals (with per-item diskon/pajak type)
    $('#items-table tbody tr').each(function(idx) {
        let qty = parseFloat($(this).find('input[name*="[qty]"]').val()) || 0;
        let harga = parseFloat($(this).find('input[name*="[harga]"]').val()) || 0;
        let diskon = parseFloat($(this).find('input[name*="[diskon]"]').val()) || 0;
        let diskonType = $(this).find('select[name*="[diskon_type]"]').val() || 'nominal';
        let tax = parseFloat($(this).find('input[name*="[tax]"]').val()) || 0;
        let taxType = $(this).find('select[name*="[tax_type]"]').val() || 'nominal';
        let base = qty * harga;
        let diskonValue = diskonType === 'persen' ? (base * diskon / 100) : diskon;
        let taxValue = taxType === 'persen' ? (base * tax / 100) : tax;
        let subtotal = base - diskonValue + taxValue;
        items.push({ idx, qty, harga, diskon, diskonType, tax, taxType, base, diskonValue, taxValue, subtotal });
        totalItemSubtotal += subtotal;
    });
    
    // Calculate global diskon/pajak value
    let globalDiskonValue = globalDiskonType === 'persen' ? (totalItemSubtotal * globalDiskon / 100) : globalDiskon;
    let globalPajakValue = globalPajakType === 'persen' ? (totalItemSubtotal * globalPajak / 100) : globalPajak;
    
    // For global tax distribution
    let hppList = [];
    items.forEach(function(item, i) {
        // Calculate item's proportional share of global tax
        let prop = totalItemSubtotal > 0 ? item.subtotal / totalItemSubtotal : 0;
        let globalPajakItem = globalPajakValue * prop;

        // Get assumed old HPP (just for simulation)
        let assumedOldHpp = 0; // We don't know the real old HPP here, just simulating

        // Add proportional global pajak to harga for HPP calculation
        let hargaWithGlobalPajak = item.harga + (item.qty > 0 ? globalPajakItem / item.qty : 0);
        let newHpp = (hargaWithGlobalPajak + assumedOldHpp) / 2;
        hppList.push(`Item ${i+1}: HPP = ${newHpp.toFixed(2)}\n  - Harga (incl. per-item tax): ${item.harga}\n  - Global pajak per unit: ${(item.qty > 0 ? (globalPajakItem / item.qty).toFixed(2) : '0.00')}\n  - Harga + global pajak: ${hargaWithGlobalPajak.toFixed(2)}\n  - Old HPP: ${assumedOldHpp} (simulated)\n  - New HPP = (${hargaWithGlobalPajak.toFixed(2)} + ${assumedOldHpp}) / 2 = ${newHpp.toFixed(2)}`);
    });
    
    // Create a modal to display the HPP calculations
    let debugContent = '<div class="p-3">';
    debugContent += '<h4>HPP Calculation Preview</h4>';
    debugContent += '<p class="text-muted">This simulates how HPP will be calculated when this invoice is approved.</p>';
    debugContent += '<div class="alert alert-info">Note: Old HPP is simulated as 0 for preview purposes. The actual calculation will use the current HPP value from the database.</div>';
    
    hppList.forEach(calcInfo => {
        debugContent += `<div class="card mb-3">
            <div class="card-body">
                <pre style="white-space: pre-wrap; font-family: monospace;">${calcInfo}</pre>
            </div>
        </div>`;
    });
    
    debugContent += '</div>';
    
    // Create Bootstrap modal
    let modal = $(`
        <div class="modal fade" id="hppDebugModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">HPP Calculation Debug</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        ${debugContent}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `);
    
    $('body').append(modal);
    modal.modal('show');
    modal.on('hidden.bs.modal', function() {
        $(this).remove();
    });
});
</script>
@endpush
