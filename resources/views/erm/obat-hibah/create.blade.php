@extends('layouts.erm.app')

@section('title', 'ERM | Input Obat Hibah')

@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection

@php
    $oldItems = old('items', [
        ['obat_id' => '', 'gudang_id' => '', 'qty' => '', 'batch' => '', 'expiration_date' => '']
    ]);
@endphp

@section('content')
<div class="container-fluid">
    <div class="row mt-3 align-items-center mb-2">
        <div class="col-md-6">
            <h2 class="mb-0">Input Obat Hibah</h2>
        </div>
        <div class="col-md-6 text-md-right mt-2 mt-md-0">
            <a href="{{ route('erm.obat-hibah.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box py-1 mb-3">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Farmasi</li>
                            <li class="breadcrumb-item"><a href="{{ route('erm.obat-hibah.index') }}">Obat Hibah</a></li>
                            <li class="breadcrumb-item active">Input</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('erm.obat-hibah.store') }}">
                @csrf

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="received_date">Tanggal Terima</label>
                            <input type="date" class="form-control" id="received_date" name="received_date" value="{{ old('received_date', now()->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="sumber">Sumber Hibah</label>
                            <input type="text" class="form-control" id="sumber" name="sumber" value="{{ old('sumber') }}" placeholder="Nama pemberi / sumber hibah">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="notes">Catatan</label>
                            <input type="text" class="form-control" id="notes" name="notes" value="{{ old('notes') }}" placeholder="Catatan tambahan">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Item Hibah</h5>
                    <button type="button" class="btn btn-success btn-sm" id="add-item-row">
                        <i class="fa fa-plus"></i> Tambah Item
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered" id="hibah-items-table">
                        <thead>
                            <tr>
                                <th style="width: 28%;">Obat</th>
                                <th style="width: 20%;">Gudang</th>
                                <th style="width: 12%;">Qty</th>
                                <th style="width: 16%;">Batch</th>
                                <th style="width: 16%;">Expired</th>
                                <th style="width: 8%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($oldItems as $index => $item)
                                <tr>
                                    <td>
                                        <select class="form-control" name="items[{{ $index }}][obat_id]" required>
                                            <option value="">Pilih obat</option>
                                            @foreach($obats as $obat)
                                                <option value="{{ $obat->id }}" @selected(($item['obat_id'] ?? '') == $obat->id)>
                                                    {{ $obat->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" name="items[{{ $index }}][gudang_id]" required>
                                            <option value="">Pilih gudang</option>
                                            @foreach($gudangs as $gudang)
                                                <option value="{{ $gudang->id }}" @selected(($item['gudang_id'] ?? '') == $gudang->id)>
                                                    {{ $gudang->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.0001" min="0.0001" class="form-control" name="items[{{ $index }}][qty]" value="{{ $item['qty'] ?? '' }}" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="items[{{ $index }}][batch]" value="{{ $item['batch'] ?? '' }}">
                                    </td>
                                    <td>
                                        <input type="date" class="form-control" name="items[{{ $index }}][expiration_date]" value="{{ $item['expiration_date'] ?? '' }}">
                                    </td>
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-danger btn-sm remove-item-row">Hapus</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="text-right">
                    <button type="submit" class="btn btn-primary">Simpan Obat Hibah</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        function reindexRows() {
            $('#hibah-items-table tbody tr').each(function (index, row) {
                $(row).find('select, input').each(function () {
                    var name = $(this).attr('name');
                    if (!name) {
                        return;
                    }
                    $(this).attr('name', name.replace(/items\[\d+\]/, 'items[' + index + ']'));
                });
            });
        }

        $('#add-item-row').on('click', function () {
            var $tbody = $('#hibah-items-table tbody');
            var nextIndex = $tbody.find('tr').length;
            var rowHtml = `
                <tr>
                    <td>
                        <select class="form-control" name="items[${nextIndex}][obat_id]" required>
                            <option value="">Pilih obat</option>
                            @foreach($obats as $obat)
                                <option value="{{ $obat->id }}">{{ $obat->nama }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select class="form-control" name="items[${nextIndex}][gudang_id]" required>
                            <option value="">Pilih gudang</option>
                            @foreach($gudangs as $gudang)
                                <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="number" step="0.0001" min="0.0001" class="form-control" name="items[${nextIndex}][qty]" required>
                    </td>
                    <td>
                        <input type="text" class="form-control" name="items[${nextIndex}][batch]">
                    </td>
                    <td>
                        <input type="date" class="form-control" name="items[${nextIndex}][expiration_date]">
                    </td>
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-danger btn-sm remove-item-row">Hapus</button>
                    </td>
                </tr>`;

            $tbody.append(rowHtml);
        });

        $(document).on('click', '.remove-item-row', function () {
            var $rows = $('#hibah-items-table tbody tr');
            if ($rows.length === 1) {
                $rows.find('input').not('[type=date]').val('');
                $rows.find('input[type=date], select').val('');
                return;
            }

            $(this).closest('tr').remove();
            reindexRows();
        });
    });
</script>
@endpush