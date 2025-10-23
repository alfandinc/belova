@extends('layouts.erm.app')
@section('title', 'ERM | Add Permintaan Pembelian')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  

@section('content')
<div class="container-fluid">
        <!-- Page-Title -->
    <!-- Title and Button Row -->
    <div class="row mt-3 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0">Buat Permintaan Pembelian</h2>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Farmasi</li>
                            <li class="breadcrumb-item ">Permintaan Pembelian</li>
                            <li class="breadcrumb-item active">Create</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->
    <form id="permintaanForm">
        @csrf
        @if(isset($permintaan))
            <input type="hidden" name="id" value="{{ $permintaan->id }}">
        @endif
        <div class="mb-3">
            <label>Tanggal Permintaan</label>
            <input type="date" name="request_date" id="request_date" class="form-control" required value="{{ isset($permintaan) ? $permintaan->request_date : '' }}">
        </div>
        <hr>
        <h5>Item Permintaan</h5>
        <table class="table table-bordered" id="items-table">
            <colgroup>
                <col style="width: 20%;">
                <col style="width: 18%;">
                <col style="width: 18%;">
                <col style="width: 8%;">
                <col style="width: 8%;">
                <col style="width: 8%;">
                <col style="width: 8%;">
                <col style="width: 8%;">
                <col style="width: 4%;">
            </colgroup>
            <thead>
                <tr>
                    <th>Obat</th>
                    <th>Pemasok</th>
                    <th>Principal</th>
                    <th>Jumlah Box</th>
                    <th>Qty Total</th>
                    <th>Harga</th>
                    <th>Qty/Box</th>
                    <th>Diskon</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    <button type="button" class="btn btn-success btn-sm mb-3" id="add-row">Tambah Item</button>
        <br>
        <div class="text-right">
            <button type="submit" class="btn btn-primary">Simpan Permintaan</button>
            <a href="{{ route('erm.permintaan.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
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
                'principal_id' => $item->principal_id,
                'principal_nama' => optional($item->principal)->nama,
                'jumlah_box' => $item->jumlah_box,
                'qty_total' => $item->qty_total
            ];
        })->toArray();
    }
@endphp

@section('scripts')
{{-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}
<script>
let rowIdx = 0;
let oldItems = @json($oldItems);

function addPermintaanRow(item = null) {
    rowIdx++;
    let row = `<tr>
        <td><select name="items[${rowIdx}][obat_id]" class="form-control obat-select" required style="min-width:400px; width:100%"></select></td>
        <td><select name="items[${rowIdx}][pemasok_id]" class="form-control pemasok-select" required style="min-width:400px; width:100%"></select></td>
        <td><select name="items[${rowIdx}][principal_id]" class="form-control principal-select" style="min-width:300px; width:100%"></select></td>
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
    initSelect2($row.find('.principal-select'), '/erm/ajax/principal', item ? {id: item.principal_id, text: item.principal_nama} : null);
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
        width: '100%',
        allowClear: true
    });
    $el.next('.select2-container').css('width', '100%');
    setTimeout(function() {
        $el.next('.select2-container').css('width', '100%');
    }, 0);
    if (selected) {
        // Set initial value for edit
        let option = new Option(selected.text, selected.id, true, true);
        $el.append(option).trigger('change');
    }
}

$(document).ready(function() {
    // Set default date to today if not editing
    if (!$("input[name='id']").length) {
        let today = new Date();
        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        let formatted = yyyy + '-' + mm + '-' + dd;
        $('#request_date').val(formatted);
    }
    if (oldItems.length > 0) {
        oldItems.forEach(function(item) { addPermintaanRow(item); });
        // Autofill master faktur fields for each row
        $('#items-table tbody tr').each(function() {
            let $row = $(this);
            let obatId = $row.find('.obat-select').val();
            let pemasokId = $row.find('.pemasok-select').val();
            let principalId = $row.find('.principal-select').val();
            if (obatId && pemasokId && principalId) {
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
    $('#items-table').on('change', '.obat-select, .pemasok-select, .principal-select', function() {
        let $row = $(this).closest('tr');
        let obatId = $row.find('.obat-select').val();
        let pemasokId = $row.find('.pemasok-select').val();
        let principalId = $row.find('.principal-select').val();
        if (obatId && pemasokId && principalId) {
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
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Permintaan berhasil disimpan!',
                    timer: 1200,
                    showConfirmButton: false
                });
                setTimeout(function(){ window.location = '{{ route('erm.permintaan.index') }}'; }, 1200);
            },
            error: function(xhr) {
                let msg = 'Gagal menyimpan permintaan!';
                if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: msg,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    });
});
</script>
@endsection
