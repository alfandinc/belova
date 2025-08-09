@extends('layouts.erm.app')
@section('title', 'ERM | Permintaan Pembelian')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  
@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4>Buat Permintaan Pembelian</h4>
        </div>
        <div class="card-body">
            <form id="permintaan-form" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pemasok_id">Pemasok <span class="text-danger">*</span></label>
                            <select name="pemasok_id" id="pemasok_id" class="form-control select2" required>
                                <option value="">Pilih Pemasok</option>
                                @if(isset($faktur))
                                    <option value="{{ $faktur->pemasok_id }}" selected>{{ $faktur->pemasok->nama }}</option>
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="requested_date">Tanggal Permintaan <span class="text-danger">*</span></label>
                            <input type="date" name="requested_date" id="requested_date" class="form-control" value="{{ isset($faktur) ? $faktur->requested_date : date('Y-m-d') }}" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Catatan</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3">{{ isset($faktur) ? $faktur->notes : '' }}</textarea>
                </div>

                <hr>
                <h5>Daftar Obat</h5>
                <div class="table-responsive">
                    <table class="table table-bordered" id="items-table">
                        <thead>
                            <tr>
                                <th>Obat <span class="text-danger">*</span></th>
                                <th>Jumlah Diminta <span class="text-danger">*</span></th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($faktur))
                                @foreach($faktur->items as $index => $item)
                                <tr>
                                    <td>
                                        <select name="items[{{ $index }}][obat_id]" class="form-control obat-select" required style="width:100%">
                                            <option value="{{ $item->obat_id }}" selected>{{ $item->obat->nama }}</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="items[{{ $index }}][diminta]" class="form-control" min="1" value="{{ $item->diminta }}" required>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm remove-item">Hapus</button>
                                    </td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">
                                    <button type="button" class="btn btn-success btn-sm" id="add-item">Tambah Obat</button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="form-group text-right mt-3">
                    <button type="submit" class="btn btn-primary">Simpan Permintaan</button>
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
            url: '{{ route("get-pemasok-select2") }}',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: $.map(data, function(item) {
                        return {
                            text: item.nama,
                            id: item.id
                        }
                    })
                };
            },
            cache: true
        }
    });
});

function itemRow(idx) {
    return `<tr>
        <td><select name="items[${idx}][obat_id]" class="form-control obat-select" required style="width:100%"></select></td>
        <td><input type="number" name="items[${idx}][diminta]" class="form-control" min="1" required></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-item">Hapus</button></td>
    </tr>`;
}

function refreshItemRows() {
    $('#items-table tbody tr').each(function(i, tr) {
        $(tr).find('select, input').each(function() {
            let name = $(this).attr('name');
            if (name) {
                let newName = name.replace(/items\[\d+\]/, `items[${i}]`);
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
            url: '{{ route("get-obat-select2") }}',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: $.map(data, function(item) {
                        return {
                            text: item.nama,
                            id: item.id
                        }
                    })
                };
            },
            cache: true
        }
    });
}

$('#add-item').on('click', function() {
    let idx = $('#items-table tbody tr').length;
    let $row = $(itemRow(idx));
    $('#items-table tbody').append($row);
    initObatSelect2($row);
});

$(document).on('click', '.remove-item', function() {
    $(this).closest('tr').remove();
    refreshItemRows();
});

// Initialize select2 for existing rows on edit
$(document).ready(function() {
    $('#items-table tbody tr').each(function() {
        initObatSelect2(this);
    });
});

$('#permintaan-form').on('submit', function(e) {
    e.preventDefault();
    refreshItemRows();
    let formData = new FormData(this);
    let isEdit = {{ isset($faktur) ? 'true' : 'false' }};
    let url = isEdit ? '{{ isset($faktur) ? route("erm.fakturbeli.updatePermintaan", $faktur->id) : "" }}' : '{{ route("erm.fakturbeli.storePermintaan") }}';
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            if(res.success) {
                alert(res.message);
                window.location.href = '{{ route("erm.fakturbeli.index") }}';
            }
        },
        error: function(xhr) {
            let errors = xhr.responseJSON.errors;
            let errorMsg = '';
            $.each(errors, function(key, value) {
                errorMsg += value + '\n';
            });
            alert('Error: ' + errorMsg);
        }
    });
});
</script>
@endpush
