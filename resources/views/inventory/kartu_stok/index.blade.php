@extends('layouts.inventory.app')

@section('title', 'Kartu Stok')

@section('navbar')
    @include('layouts.inventory.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Kartu Stok</h4>

                    <div class="mb-3">
                        <label for="barangSelect">Barang</label>
                        <select id="barangSelect" class="form-control select2-ajax" style="width:100%">
                            <option value="">-- Semua --</option>
                        </select>
                    </div>

                    <table id="kartuTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Barang</th>
                                <th>Stok Awal</th>
                                <th>Masuk</th>
                                <th>Keluar</th>
                                <th>Stok Akhir</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function(){
        function loadData(barangId){
            $.getJSON("{{ route('inventory.kartustok.data') }}", { barang_id: barangId }, function(res){
                const tbody = $('#kartuTable tbody').empty();
                res.data.forEach(function(r){
                    tbody.append(`
                        <tr>
                            <td>${new Date(r.tanggal).toLocaleString()}</td>
                            <td>${r.barang ? r.barang.name : ''}</td>
                            <td>${r.stok_awal}</td>
                            <td>${r.stok_masuk}</td>
                            <td>${r.stok_keluar}</td>
                            <td>${r.stok_akhir}</td>
                            <td>${r.keterangan || ''}</td>
                        </tr>
                    `);
                });
            });
        }

        $('#barangSelect').on('change', function(){
            loadData(this.value);
        });

        // Initialize select2 with AJAX search
        $('#barangSelect').select2({
            placeholder: '-- Semua --',
            allowClear: true,
            ajax: {
                url: '{{ route('inventory.barang.search') }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return data;
                },
            },
            minimumInputLength: 1,
            width: '100%'
        });

        // Load initial data (all)
        loadData('');
    });
</script>
@endsection
