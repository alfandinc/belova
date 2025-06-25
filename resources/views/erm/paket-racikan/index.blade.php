@extends('layouts.erm.app')
@section('title', 'Manajemen Paket Racikan')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection
@section('content')

<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Manajemen Paket Racikan</h3>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="paketRacikanManagementTable">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="20%">Nama Paket</th>
                            <th width="25%">Deskripsi</th>
                            <th width="15%">Wadah</th>
                            <th width="10%">Bungkus</th>
                            <th width="15%">Aturan Pakai</th>
                            <th width="10%">Status</th>
                            <th width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('erm.partials.modal-paketracikan')

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#paketRacikanManagementTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: "{{ route('erm.paket-racikan.list') }}",
            dataSrc: 'data'
        },
        columns: [
            { 
                data: null,
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'nama_paket' },
            { 
                data: 'deskripsi',
                render: function(data) {
                    return data || '-';
                }
            },
            { 
                data: 'wadah',
                render: function(data) {
                    return data ? data.nama : '-';
                }
            },
            { data: 'bungkus_default' },
            { 
                data: 'aturan_pakai_default',
                render: function(data) {
                    return data || '-';
                }
            },
            { 
                data: 'is_active',
                render: function(data) {
                    return data ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-secondary">Tidak Aktif</span>';
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    return `
                        <button class="btn btn-info btn-sm detail-paket" data-paket='${JSON.stringify(row)}'>Detail</button>
                        <button class="btn btn-danger btn-sm delete-paket" data-id="${row.id}">Hapus</button>
                    `;
                }
            }
        ]
    });

    // Detail Paket
    $(document).on('click', '.detail-paket', function() {
        let paket = $(this).data('paket');
        let content = `
            <h6><strong>${paket.nama_paket}</strong></h6>
            <p><strong>Deskripsi:</strong> ${paket.deskripsi || '-'}</p>
            <p><strong>Wadah:</strong> ${paket.wadah ? paket.wadah.nama : '-'}</p>
            <p><strong>Bungkus Default:</strong> ${paket.bungkus_default}</p>
            <p><strong>Aturan Pakai Default:</strong> ${paket.aturan_pakai_default || '-'}</p>
            
            <h6><strong>Obat dalam Paket:</strong></h6>
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Obat</th>
                        <th>Dosis</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        paket.details.forEach(function(detail, index) {
            content += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${detail.obat.nama}</td>
                    <td>${detail.dosis}</td>
                </tr>
            `;
        });
        
        content += `
                </tbody>
            </table>
        `;
        
        $('#detailPaketContent').html(content);
        $('#detailPaketModal').modal('show');
    });

    // Delete Paket
    $(document).on('click', '.delete-paket', function() {
        let paketId = $(this).data('id');
        
        if (!confirm('Yakin ingin menghapus paket racikan ini?')) return;
        
        $.ajax({
            url: "{{ route('erm.paket-racikan.delete', '') }}/" + paketId,
            method: 'DELETE',
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    $('#paketRacikanManagementTable').DataTable().ajax.reload();
                }
            },
            error: function(xhr) {
                alert('Gagal menghapus paket racikan: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });
});
</script>
@endsection
