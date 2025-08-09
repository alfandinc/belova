@extends('layouts.erm.app')
@section('title', 'ERM | Add Permintaan Pembelian')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  

@section('content')
<div class="container">
    <h1>{{ isset($permintaan) ? 'Edit' : 'Buat' }} Permintaan Pembelian</h1>
    <form id="permintaanForm">
        @csrf
        @if(isset($permintaan))
            <input type="hidden" name="id" value="{{ $permintaan->id }}">
        @endif
        <div class="mb-3">
            <label>Tanggal Permintaan</label>
            <input type="date" name="request_date" class="form-control" required value="{{ isset($permintaan) ? $permintaan->request_date : '' }}">
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
                    <th>Harga (Master)</th>
                    <th>Qty/Box (Master)</th>
                    <th>Diskon (Master)</th>
                    <th>Diskon Type (Master)</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <button type="button" class="btn btn-secondary mb-3" id="add-row">Tambah Item</button>
        <br>
        <button type="submit" class="btn btn-primary">Simpan Permintaan</button>
        <a href="{{ route('erm.permintaan.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
    <div id="formAlert" style="display:none;" class="alert mt-3"></div>
@endsection

@php
    $oldItems = [];
    if (isset($permintaan)) {
        $oldItems = $permintaan->items->map(function($item) {
            return [
                'obat_id' => $item->obat_id,
                'obat_nama' => optional($item->obat)->nama,
                'pemasok_id' => $item->pemasok_id,
                'pemasok_nama' => optional($item->pemasok)->nama,
                'jumlah_box' => $item->jumlah_box,
                'qty_total' => $item->qty_total
            ];
        })->toArray();
    }
@endphp

@section('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
let rowIdx = 0;
let oldItems = @json($oldItems);

function addPermintaanRow(item = null) {
    rowIdx++;
    let row = `<tr>
        <td><select name="items[${rowIdx}][obat_id]" class="form-control obat-select" required style="width:100%"></select></td>
        <td><select name="items[${rowIdx}][pemasok_id]" class="form-control pemasok-select" required style="width:100%"></select></td>
        <td><input type="number" name="items[${rowIdx}][jumlah_box]" class="form-control jumlah-box" min="1" required value="${item ? item.jumlah_box : ''}"></td>
        <td><input type="number" name="items[${rowIdx}][qty_total]" class="form-control qty-total" min="1" required value="${item ? item.qty_total : ''}"></td>
        <td><input type="text" class="form-control harga-master" readonly></td>
        <td><input type="text" class="form-control qtybox-master" readonly></td>
        <td><input type="text" class="form-control diskon-master" readonly></td>
        <td><input type="text" class="form-control diskontype-master" readonly></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-row">Hapus</button></td>
    </tr>`;
    $('#items-table tbody').append(row);
    let $row = $('#items-table tbody tr:last');
    // Init select2 and set value if editing
    initSelect2($row.find('.obat-select'), '/erm/ajax/obat', item ? {id: item.obat_id, text: item.obat_nama} : null);
    initSelect2($row.find('.pemasok-select'), '/erm/ajax/pemasok', item ? {id: item.pemasok_id, text: item.pemasok_nama} : null);
}

function initSelect2($el, url, selected = null) {
    $el.select2({
        placeholder: 'Pilih',
        minimumInputLength: 2,
        ajax: {
            url: url,
            dataType: 'json',
            delay: 250,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) { return { results: data }; },
            cache: true
        },
        width: 'resolve',
        allowClear: true
    });
    if (selected) {
        // Set initial value for edit
        let option = new Option(selected.text, selected.id, true, true);
        $el.append(option).trigger('change');
    }
}

$(document).ready(function() {
    if (oldItems.length > 0) {
        oldItems.forEach(function(item) { addPermintaanRow(item); });
        // Autofill master faktur fields for each row
        $('#items-table tbody tr').each(function() {
            let $row = $(this);
            let obatId = $row.find('.obat-select').val();
            let pemasokId = $row.find('.pemasok-select').val();
            if (obatId && pemasokId) {
                $.get('/erm/permintaan/master-faktur', { obat_id: obatId, pemasok_id: pemasokId }, function(data) {
                    if (data.found) {
                        $row.find('.harga-master').val(data.harga);
                        $row.find('.qtybox-master').val(data.qty_per_box);
                        $row.find('.diskon-master').val(data.diskon);
                        $row.find('.diskontype-master').val(data.diskon_type);
                    } else {
                        $row.find('.harga-master, .qtybox-master, .diskon-master, .diskontype-master').val('');
                    }
                });
            }
        });
    } else {
        addPermintaanRow();
    }
    $('#add-row').on('click', function() { addPermintaanRow(); });
    $('#items-table').on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
    });

    // Autofill master faktur fields
    $('#items-table').on('change', '.obat-select, .pemasok-select', function() {
        let $row = $(this).closest('tr');
        let obatId = $row.find('.obat-select').val();
        let pemasokId = $row.find('.pemasok-select').val();
        if (obatId && pemasokId) {
            $.get('/erm/permintaan/master-faktur', { obat_id: obatId, pemasok_id: pemasokId }, function(data) {
                if (data.found) {
                    $row.find('.harga-master').val(data.harga);
                    $row.find('.qtybox-master').val(data.qty_per_box);
                    $row.find('.diskon-master').val(data.diskon);
                    $row.find('.diskontype-master').val(data.diskon_type);
                } else {
                    $row.find('.harga-master, .qtybox-master, .diskon-master, .diskontype-master').val('');
                }
            });
        }
    });

    // Qty total autofill
    $('#items-table').on('input', '.jumlah-box', function() {
        let $row = $(this).closest('tr');
        let qtyBox = parseInt($row.find('.qtybox-master').val());
        let jumlahBox = parseInt($(this).val());
        let $qtyTotal = $row.find('.qty-total');
        if (qtyBox > 0 && jumlahBox > 0) {
            $qtyTotal.val(qtyBox * jumlahBox);
        } else {
            $qtyTotal.val('');
        }
    });

    // AJAX form submit
    $('#permintaanForm').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let data = form.serialize();
        let isEdit = $('input[name="id"]').length > 0;
        let url = isEdit ? '/erm/permintaan/' + $('input[name="id"]').val() : '{{ route('erm.permintaan.store') }}';
        let method = isEdit ? 'PUT' : 'POST';
        $.ajax({
            url: url,
            type: method,
            data: data,
            success: function(res) {
                $('#formAlert').removeClass('alert-danger').addClass('alert-success').text('Permintaan berhasil disimpan!').show();
                setTimeout(function(){ window.location = '{{ route('erm.permintaan.index') }}'; }, 1200);
            },
            error: function(xhr) {
                let msg = 'Gagal menyimpan permintaan!';
                if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                $('#formAlert').removeClass('alert-success').addClass('alert-danger').text(msg).show();
            }
        });
    });
});
</script>
@endsection
