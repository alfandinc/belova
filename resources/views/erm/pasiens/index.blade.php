@extends('layouts.erm.app')
@section('title', 'ERM | Data Pasien')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection
@section('content')
<style>
/* Status Pasien styling in DataTable */
.status-pasien-icon {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
}

.status-akses-icon {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
}

.edit-status-btn {
    font-size: 12px;
    padding: 2px;
}

.edit-status-btn:hover {
    background-color: transparent !important;
}

.status-text {
    font-weight: 500;
}
</style>

@include('erm.partials.modal-daftarkunjungan')
@include('erm.partials.modal-daftarkunjunganproduk')
@include('erm.partials.modal-daftarkunjunganlab')
@include('erm.partials.modal-info-pasien')
@include('erm.partials.modal-ic-pendaftaran')

<!-- Unified Manage Pasien Modal: Status Pasien, Status Akses, Status Review, Merchandise -->
<div class="modal fade" id="modalManagePasien" tabindex="-1" role="dialog" aria-labelledby="modalManagePasienLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalManagePasienLabel">Kelola Pasien</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="font-weight-bold" id="managePasienNama">-</div>
                            <div class="text-muted small">No. RM: <span id="managePasienId">-</span></div>
                        </div>
                    </div>
                </div>
                <hr/>
                <div class="row">
                    <div class="col-md-6">
                        <form id="manageStatusForm">
                            <div class="form-group">
                                <label for="manage_status_pasien">Status Pasien</label>
                                <select class="form-control" id="manage_status_pasien" name="status_pasien" required>
                                    <option value="Regular">Regular</option>
                                    <option value="VIP">VIP</option>
                                    <option value="Familia">Familia</option>
                                    <option value="Black Card">Black Card</option>
                                    <option value="Red Flag">Red Flag</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="manage_status_akses">Status Akses</label>
                                <select class="form-control" id="manage_status_akses" name="status_akses" required>
                                    <option value="normal">Normal</option>
                                    <option value="akses cepat">Akses Cepat</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="manage_status_review">Status Review</label>
                                <select class="form-control" id="manage_status_review" name="status_review" required>
                                    <option value="sudah">Sudah</option>
                                    <option value="belum">Belum</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <label class="d-block">Merchandise</label>
                        <div id="unifiedMerchChecklistContainer"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="saveManagePasien">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Status Akses -->
<div class="modal fade" id="modalEditStatusAkses" tabindex="-1" role="dialog" aria-labelledby="modalEditStatusAksesLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditStatusAksesLabel">Edit Status Akses</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editStatusAksesForm">
                    <div class="form-group">
                        <label for="edit_status_akses">Status Akses</label>
                        <select class="form-control" id="edit_status_akses" name="status_akses" required>
                            <option value="normal">Normal</option>
                            <option value="akses cepat">Akses Cepat</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveEditStatusAkses">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Status Review -->
<div class="modal fade" id="modalEditStatusReview" tabindex="-1" role="dialog" aria-labelledby="modalEditStatusReviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditStatusReviewLabel">Edit Status Review</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editStatusReviewForm">
                    <div class="form-group">
                        <label for="edit_status_review">Status Review</label>
                        <select class="form-control" id="edit_status_review" name="status_review" required>
                            <option value="sudah">Sudah</option>
                            <option value="belum">Belum</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveEditStatusReview">Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
                    <!-- Page-Title -->
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="page-title-box">
                                <div class="row">
                                    <div class="col">
                                        <ol class="breadcrumb">
                                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                                            <li class="breadcrumb-item active">Data Pasien</li>
                                        </ol>
                                    </div><!--end col-->
                                    <div class="col-auto align-self-center">
                                        <a href="{{ route('erm.pasiens.create') }}" class="btn btn-primary btn-lg">
                                            <i class="fas fa-plus-square mr-2"></i>Pasien Baru
                                        </a>
                                    </div><!--end col-->  
                                </div><!--end row-->                                                              
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div><!--end row-->
                    <!-- end page title end breadcrumb -->

    {{-- Table Pasien --}}
    <div class="card">
        <div class="card-header bg-primary">
            <h4 class="card-title text-white">Daftar Pasien</h4>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3 mb-2">
                    <input type="text" id="filter_no_rm" class="form-control" placeholder="No RM">
                </div>
                <div class="col-md-3 mb-2">
                    <input type="text" id="filter_nama" class="form-control" placeholder="Nama">
                </div>
                <div class="col-md-2 mb-2">
                    <input type="text" id="filter_nik" class="form-control" placeholder="NIK">
                </div>
                <div class="col-md-4 mb-2">
                    <input type="text" id="filter_alamat" class="form-control" placeholder="Alamat">
                </div>
            </div>
            <div class="row mb-3 align-items-center">
                <div class="col-md-3 mb-2">
                    <select id="filter_status_pasien" class="form-control">
                        <option value="">Semua Status Pasien</option>
                        <option value="Regular">Regular</option>
                        <option value="VIP">VIP</option>
                        <option value="Familia">Familia</option>
                        <option value="Black Card">Black Card</option>
                        <option value="Red Flag">Red Flag</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <select id="filter_status_akses" class="form-control">
                        <option value="">Semua Status Akses</option>
                        <option value="normal">Normal</option>
                        <option value="akses cepat">Akses Cepat</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <select id="filter_status_review" class="form-control">
                        <option value="">Semua Status Review</option>
                        <option value="sudah">Sudah</option>
                        <option value="belum">Belum</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2 d-flex">
                    <button id="btn-filter" class="btn btn-primary mr-2"><i class="fas fa-search-plus mr-2"></i>Cari</button>
                    <button id="btn-reset" class="btn btn-secondary"><i class="fas fa-undo mr-2"></i>Reset</button>
                </div>
            </div>
            <table class="table table-bordered table-striped" id="pasiens-table">
                <thead class="text-center font-weight-bold">
                    <tr>
                        <th>No RM</th>
                        <th>Name</th>
                        <th>NIK</th>
                        <th>Alamat</th>
                        <th>No HP</th>
                        <th>Inform Consent</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    let table = $('#pasiens-table').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        deferLoading: 0, // Prevent initial load
        stripe: true,    // Enable row striping
        ajax: {
            url: "{{ route('erm.pasiens.index') }}",
            data: function (d) {
                d.no_rm = $('#filter_no_rm').val();
                d.nama = $('#filter_nama').val();
                d.nik = $('#filter_nik').val();
                d.alamat = $('#filter_alamat').val();
                d.status_pasien = $('#filter_status_pasien').val();
                d.status_akses = $('#filter_status_akses').val();
                d.status_review = $('#filter_status_review').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'nama', name: 'nama' },
            { data: 'nik', name: 'nik' },
            { data: 'alamat', name: 'alamat' },
            { data: 'no_hp', name: 'no_hp' },
            { data: 'ic', name: 'ic', orderable: false, searchable: false, defaultContent: '' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        columnDefs: [
            { targets: 0, width: '50px' },
            {
                // Full alamat renderer (village, district, regency, province)
                targets: 3,
                render: function(data, type, row) {
                    var parts = [];
                    if (row.alamat) parts.push(row.alamat);
                    try {
                        if (row.village && row.village.name) parts.push(row.village.name);
                        if (row.village && row.village.district && row.village.district.name) parts.push(row.village.district.name);
                        if (row.village && row.village.district && row.village.district.regency && row.village.district.regency.name) parts.push(row.village.district.regency.name);
                        if (row.village && row.village.district && row.village.district.regency && row.village.district.regency.province && row.village.district.regency.province.name) parts.push(row.village.district.regency.province.name);
                    } catch (e) {
                        // ignore
                    }
                    return parts.filter(Boolean).join(', ');
                }
            },
            { targets: 5, width: '120px' }, // Inform Consent column
            { targets: 6, width: '300px' }, // Action column
            {
                targets: 1,
                render: function(data, type, row) {
                    function escapeHtml(unsafe){
                        if (!unsafe && unsafe !== 0) return '';
                        return String(unsafe).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
                    }
                    function getTxt(v){ return $('<div>').html(v||'').text().trim(); }
                    var sp = getTxt(row.status_pasien);
                    var sa = getTxt(row.status_akses);
                    var sr = getTxt(row.status_review);
                    function badgePasien(val){
                        var v = (val||'').toLowerCase();
                        if (v.includes('vip')) return '<span class="badge badge-soft-warning badge-pill ml-2"><i class="fas fa-crown mr-1"></i>VIP</span>';
                        if (v.includes('familia')) return '<span class="badge badge-soft-primary badge-pill ml-2"><i class="fas fa-users mr-1"></i>Familia</span>';
                        if (v.includes('black')) return '<span class="badge badge-soft-dark badge-pill ml-2"><i class="fas fa-id-card mr-1"></i>Black</span>';
                        if (v.includes('red')) return '<span class="badge badge-soft-danger badge-pill ml-2"><i class="fas fa-flag mr-1"></i>Red</span>';
                        return '<span class="badge badge-soft-secondary badge-pill ml-2"><i class="fas fa-user mr-1"></i>Regular</span>';
                    }
                    function badgeAkses(val){
                        var v = (val||'').toLowerCase();
                        if (v.includes('akses cepat')) return '<span class="badge badge-soft-primary badge-pill ml-2"><i class="fas fa-wheelchair mr-1"></i>Akses cepat</span>';
                        return '<span class="badge badge-soft-secondary badge-pill ml-2"><i class="fas fa-check-circle mr-1"></i>Normal</span>';
                    }
                    function badgeReview(val){
                        var v = (val||'').toLowerCase();
                        if (v.includes('sudah')) return '<span class="badge badge-soft-success badge-pill ml-2"><i class="fas fa-check mr-1"></i>Sudah</span>';
                        return '<span class="badge badge-soft-secondary badge-pill ml-2"><i class="fas fa-times mr-1"></i>Belum</span>';
                    }
                    var badges = '<div class="mt-2 d-flex flex-wrap" style="gap:6px;">' + badgePasien(sp) + badgeAkses(sa) + badgeReview(sr) + '</div>';
                    var link = '<a href="#" class="open-manage-modal d-block font-weight-bold" data-id="'+ escapeHtml(row.id) +'">'+ escapeHtml(data) +'</a>';
                    return '<div class="d-flex flex-column">'+ link + badges +'</div>';
                }
            },
            {
                // Inform Consent column renderer (index 5)
                targets: 5,
                render: function(data, type, row) {
                    var tgllahir = row.tanggal_lahir || '';
                    var icBtn = ` <span class="ic-action"><button type="button" class="btn btn-sm btn-outline-primary btn-open-ic" 
                                   title="Isi IC Pendaftaran"
                                   data-id="${row.id}"
                                   data-nama="${(row.nama||'').toString().replace(/"/g,'&quot;')}"
                                   data-nik="${row.nik||''}"
                                   data-alamat="${(row.alamat||'').toString().replace(/"/g,'&quot;')}"
                                   data-nohp="${row.no_hp||''}"
                                   data-tgllahir="${tgllahir}">
                                   <i class="fas fa-file-signature mr-1"></i>Isi IC
                                 </button></span>`;
                    return icBtn;
                }
            },
            {
                // Action column (index 6) — server may supply edit/delete HTML
                targets: 6,
                render: function(data, type, row) {
                    return (data || '');
                }
            }
        ]
    });

    $('#btn-filter').click(function () {
        table.ajax.reload();
    });

    // Replace "Isi IC" with "View IC" for rows that already have IC
    function refreshIcButtons() {
        var ids = [];
        table.rows({ page: 'current' }).every(function(){
            var r = this.data();
            if (r && r.id) ids.push(r.id.toString());
        });
        if (!ids.length) return;
        $.ajax({
            url: '{{ route('erm.ic_pendaftaran.check') }}',
            type: 'POST',
            data: { ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }
        }).done(function(resp){
            var map = (resp && resp.mappings) ? resp.mappings : {};
            table.rows({ page: 'current' }).every(function(){
                var d = this.data();
                var has = map[(d.id || '').toString()];
                var $cell = $(this.node()).find('td').eq(5);
                var $holder = $cell.find('.ic-action');
                if (!$holder.length) return;
                if (has) {
                    var pdfUrl = '{{ route('erm.ic_pendaftaran.pdf', ['pasien' => 'PID']) }}'.replace('PID', (d.id || '').toString());
                    $holder.html('<a href="' + pdfUrl + '" target="_blank" class="btn btn-sm btn-outline-secondary" title="Lihat IC (PDF)"><i class="fas fa-file-pdf mr-1"></i>View IC</a>');
                }
            });
        });
    }

    table.on('draw', function(){ refreshIcButtons(); });
    refreshIcButtons();

    // Reset button functionality
    $('#btn-reset').click(function () {
        // Clear all filter inputs
        $('#filter_no_rm').val('');
        $('#filter_nama').val('');
        $('#filter_nik').val('');
        $('#filter_alamat').val('');
        $('#filter_status_pasien').val('');
        $('#filter_status_akses').val('');
        $('#filter_status_review').val('');
        
        // Reload table with cleared filters
        table.ajax.reload();
    });

    // Add Enter key functionality to search fields
    $('#filter_no_rm, #filter_nama, #filter_nik, #filter_alamat').on('keypress', function(e) {
        if (e.which === 13) { // Enter key code
            table.ajax.reload();
        }
    });

    // Add change event for select dropdowns
    $('#filter_status_pasien, #filter_status_akses').on('change', function() {
        table.ajax.reload();
    });

    // Add change event for status_review filter
    $('#filter_status_review').on('change', function () {
        table.ajax.reload();
    });

    // Optional: Add input event for real-time search (search as you type)
    // Uncomment the lines below if you want search-as-you-type functionality
    /*
    $('#filter_no_rm, #filter_nama, #filter_nik, #filter_alamat').on('input', function() {
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(function() {
            table.ajax.reload();
        }, 500); // 500ms delay after user stops typing
    });
    */
let currentPasienId;
    $(document).on('click', '.btn-info-pasien', function () {
        let pasienId = $(this).data('id');
        currentPasienId = pasienId;

        $.ajax({
            url: "{{ route('erm.pasien.show', '') }}/" + pasienId, // Fetch patient info
            type: "GET",
            success: function (response) {
                // Populate table cells with response data
                $('#info-no-rm').text(response.id);
                $('#info-nama').text(response.nama);
                $('#info-nik').text(response.nik);
                // Build combined address: alamat, desa, kecamatan, kabupaten, provinsi
                const alamat = response.alamat || '';
                const villageName = response.village && response.village.name ? response.village.name : '';
                const districtName = response.village && response.village.district && response.village.district.name ? response.village.district.name : '';
                const regencyName = response.village && response.village.district && response.village.district.regency && response.village.district.regency.name ? response.village.district.regency.name : '';
                const provinceName = response.village && response.village.district && response.village.district.regency && response.village.district.regency.province && response.village.district.regency.province.name ? response.village.district.regency.province.name : '';

                // Collect non-empty parts and join with comma
                const parts = [];
                if (alamat) parts.push(alamat);
                if (villageName) parts.push(villageName);
                if (districtName) parts.push(districtName);
                if (regencyName) parts.push(regencyName);
                if (provinceName) parts.push(provinceName);

                const fullAddress = parts.join(', ');
                $('#info-alamat').text(fullAddress);
                $('#info-tanggal-lahir').text(response.tanggal_lahir);
                $('#info-jenis-kelamin').text(response.gender);
                $('#info-agama').text(response.agama);
                $('#info-marital-status').text(response.martial_status);
                $('#info-pendidikan').text(response.pendidikan);
                $('#info-pekerjaan').text(response.pekerjaan);
                $('#info-golongan-darah').text(response.gol_darah);
                $('#info-no-hp').text(response.no_hp);
                $('#info-email').text(response.email);
                $('#info-instagram').text(response.instagram);
                // clear any leftover area spans if present
                $('#info-village').text('');
                $('#info-district').text('');
                $('#info-regency').text('');
                $('#info-province').text('');
                
                // Show the modal
                $('#modalInfoPasien').modal('show');
            },
            error: function () {
                alert("Terjadi kesalahan saat mengambil data pasien.");
            }
        });
    });    $(document).on('click', '#btn-edit-pasien', function() {
        if (currentPasienId) {
            window.location.href = "{{ route('erm.pasiens.create') }}?edit_id=" + currentPasienId;
        }
    });

    // Open IC modal from index actions
    $(document).on('click', '.btn-open-ic', function() {
        // Use attr() to preserve leading zeros
        const id = ($(this).attr('data-id') || '').toString();
        const fallback = {
            id: id,
            nama: ($(this).attr('data-nama') || ''),
            nik: ($(this).attr('data-nik') || ''),
            alamat: ($(this).attr('data-alamat') || ''),
            no_hp: ($(this).attr('data-nohp') || ''),
            tanggal_lahir: ($(this).attr('data-tgllahir') || '')
        };

        $.ajax({
            url: "{{ route('erm.pasien.show', '') }}/" + id,
            type: 'GET'
        }).done(function(resp){
            const pasien = {
                id: (resp.id || fallback.id).toString(),
                nama: resp.nama || fallback.nama,
                nik: (resp.nik || fallback.nik).toString(),
                alamat: resp.alamat || fallback.alamat,
                no_hp: (resp.no_hp || fallback.no_hp).toString(),
                tanggal_lahir: resp.tanggal_lahir || fallback.tanggal_lahir
            };
            $('#icModal').data('pasien', pasien).modal('show');
        }).fail(function(){
            // Use fallback if detail endpoint is unavailable
            $('#icModal').data('pasien', fallback).modal('show');
        });
    });

    // Open unified manage modal helper
    let manageOriginal = { pasien: '', akses: '', review: '' };
    function openManageModal(pasienId){
        if (!pasienId) return;
        $('#modalManagePasien').data('pasien-id', pasienId);
        // Load patient raw values
        $.get("{{ route('erm.pasien.show', '') }}/" + pasienId, function(resp){
            manageOriginal.pasien = resp.status_pasien || 'Regular';
            manageOriginal.akses = resp.status_akses || 'normal';
            manageOriginal.review = resp.status_review || 'belum';
            $('#manage_status_pasien').val(manageOriginal.pasien);
            $('#manage_status_akses').val(manageOriginal.akses);
            $('#manage_status_review').val(manageOriginal.review);
            $('#managePasienNama').text(resp.nama || '-');
            $('#managePasienId').text(resp.id || pasienId);
        }).always(function(){
            // Load merchandise data in parallel
            let pid = $('#modalManagePasien').data('pasien-id');
            $('#unifiedMerchChecklistContainer').html('<p class="text-muted">Memuat...</p>');
            $.when(
                $.get('/marketing/master-merchandise/data').fail(()=>{}),
                $.get('/erm/pasiens/' + pid + '/merchandises').fail(()=>{})
            ).done(function(masterResp, pasienResp){
                let masterData = masterResp && masterResp[0] ? (masterResp[0].data || masterResp[0]) : [];
                let pasienData = pasienResp && pasienResp[0] ? (pasienResp[0].data || pasienResp[0]) : [];
                renderMerchChecklist(masterData, pasienData);
            }).fail(function(){
                $('#unifiedMerchChecklistContainer').html('<p class="text-danger">Gagal memuat data.</p>');
            });
            $('#modalManagePasien').modal('show');
        });
    }
    // Trigger by clicking patient name
    $(document).on('click', '.open-manage-modal', function(e){ e.preventDefault(); openManageModal($(this).data('id')); });
    // Also trigger when clicking existing merchandise "Lihat" buttons
    $(document).on('click', '.btn-merch-checklist', function(){ openManageModal($(this).data('id')); });

    // Save all statuses from unified modal
    $('#saveManagePasien').on('click', function(){
        let pasienId = $('#modalManagePasien').data('pasien-id');
        let p = $('#manage_status_pasien').val();
        let a = $('#manage_status_akses').val();
        let r = $('#manage_status_review').val();
        let reqs = [];
        reqs.push($.post('/erm/pasiens/' + pasienId + '/update-status', { _token: $('meta[name="csrf-token"]').attr('content'), status_pasien: p }));
        reqs.push($.post('/erm/pasiens/' + pasienId + '/update-status-akses', { _token: $('meta[name="csrf-token"]').attr('content'), status_akses: a }));
        reqs.push($.post('/erm/pasiens/' + pasienId + '/update-status-review', { _token: $('meta[name="csrf-token"]').attr('content'), status_review: r }));
        $.when.apply($, reqs).done(function(){
            Swal.fire({ icon: 'success', title: 'Tersimpan', text: 'Status pasien diperbarui.', timer: 1500, showConfirmButton: false });
            table.ajax.reload(null, false);
        }).fail(function(){
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Tidak dapat menyimpan status.' });
        });
    });

    // Handle edit status akses button click
    $(document).on('click', '.edit-status-akses-btn', function() {
        let pasienId = $(this).data('pasien-id');
        let currentStatus = $(this).data('current-status');
        
        $('#edit_status_akses').val(currentStatus);
        $('#modalEditStatusAkses').data('pasien-id', pasienId);
        $('#modalEditStatusAkses').modal('show');
    });
    
    // Handle save status akses
    $('#saveEditStatusAkses').on('click', function() {
        let pasienId = $('#modalEditStatusAkses').data('pasien-id');
        let newStatus = $('#edit_status_akses').val();
        
        $.ajax({
            url: '/erm/pasiens/' + pasienId + '/update-status-akses',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                status_akses: newStatus
            },
            success: function(response) {
                if(response.success) {
                    $('#modalEditStatusAkses').modal('hide');
                    table.ajax.reload(); // Reload the DataTable
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Status akses pasien berhasil diperbarui.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Gagal memperbarui status akses pasien.',
                });
            }
        });

    });

    // Handle edit status review button click
    $(document).on('click', '.edit-status-review-btn', function() {
        let pasienId = $(this).data('pasien-id');
        let currentStatus = $(this).data('current-status');
        
        $('#edit_status_review').val(currentStatus);
        $('#modalEditStatusReview').data('pasien-id', pasienId);
        $('#modalEditStatusReview').modal('show');
    });
    
    // Handle save status review
    $('#saveEditStatusReview').on('click', function() {
        let pasienId = $('#modalEditStatusReview').data('pasien-id');
        let newStatus = $('#edit_status_review').val();
        
        $.ajax({
            url: '/erm/pasiens/' + pasienId + '/update-status-review',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                status_review: newStatus
            },
            success: function(response) {
                if(response.success) {
                    $('#modalEditStatusReview').modal('hide');
                    table.ajax.reload(); // Reload the DataTable
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Status review pasien berhasil diperbarui.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Gagal memperbarui status review pasien.',
                });
            }
        });

    });

    // Merchandise checklist logic (used inside unified modal)
        function renderMerchChecklist(masterList, pasienReceipts) {
            let receivedIds = (pasienReceipts || []).map(r => (r.merchandise_id || r.merchandise_id === 0) ? r.merchandise_id : null).filter(Boolean);
            let $container = $('#unifiedMerchChecklistContainer');
            $container.empty();

            if (!masterList.length) {
                $container.html('<p class="text-muted">No merchandise items available.</p>');
                return;
            }

            let $form = $('<div class="list-group"></div>');
            // Build a map of received quantities by merchandise id
            let qtyMap = {};
            (pasienReceipts || []).forEach(r => {
                if (r.merchandise_id) qtyMap[r.merchandise_id] = r.quantity || 1;
            });

            masterList.forEach(item => {
                let received = receivedIds.includes(item.id);
                let checked = received ? 'checked' : '';
                let qty = received ? (qtyMap[item.id] || 1) : 1;
                let stock = item.stock || 0;

                // If stock is zero, disable the checkbox and show 'Habis' badge
                let disabledAttr = stock <= 0 ? 'disabled' : '';
                let stockBadge = stock <= 0 ? '<span class="badge badge-danger ml-2">Habis</span>' : `<small class="text-muted ml-2">Stok: ${stock}</small>`;

                let $row = $(
                    `<label class="list-group-item d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <input type="checkbox" class="merch-checkbox mr-3" data-id="${item.id}" data-stock="${stock}" ${checked} ${disabledAttr}>
                            <div>
                                <div><strong>${item.name}</strong> ${stockBadge}</div>
                                <div class="small text-muted">${item.description || ''}</div>
                            </div>
                        </div>
                        <div class="ml-3">
                            <input type="number" min="1" class="form-control form-control-sm merch-qty" data-id="${item.id}" data-stock="${stock}" value="${qty}" style="width:80px;" ${received ? '' : 'disabled'} ${stock <= 0 ? 'disabled' : ''}>
                        </div>
                    </label>`
                );
                $form.append($row);
            });

            $container.append($form);
        }

        // No dedicated open handler; merchandise loads inside unified modal open

        // Handle checkbox toggle: add or remove receipt
        $(document).on('change', '.merch-checkbox', function(){
            let checked = $(this).is(':checked');
            let merchId = $(this).data('id');
            let pasienId = $('#modalManagePasien').data('pasien-id');
            if (!pasienId) return alert('Pasien ID missing');

            // find the qty input for this merch and stock
            let $qtyInput = $('.merch-qty[data-id="' + merchId + '"]');
            let stock = parseInt($qtyInput.data('stock') || 0, 10);
            let qty = parseInt($qtyInput.val() || 1, 10);

            // validate against stock
            if (stock <= 0) {
                Swal.fire({ icon: 'warning', title: 'Stok habis', text: 'Stok item ini habis dan tidak dapat ditambahkan.' });
                // ensure unchecked
                $(this).prop('checked', false);
                return;
            }
            if (qty > stock) {
                Swal.fire({ icon: 'warning', title: 'Stok tidak cukup', text: `Permintaan qty (${qty}) melebihi stok (${stock}).` });
                // restore qty to stock
                $qtyInput.val(stock);
                return;
            }

            if (checked) {
                let $cb = $(this);
                // enable qty input
                $qtyInput.prop('disabled', false);

                $.post('/erm/pasiens/' + pasienId + '/merchandises', {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    merchandise_id: merchId,
                    quantity: qty
                }, function(resp){
                    // success - if response contains created id, we could store it on the row for faster updates
                    if (resp && resp.id) {
                        $qtyInput.data('pm-id', resp.id);
                    }
                }).fail(function(){
                    alert('Failed to add merchandise');
                    $cb.prop('checked', false);
                    $qtyInput.prop('disabled', true);
                });
            } else {
                // Need to find the pmId for this pasien+merch combination; call the pasien merch list and find id
                $.get('/erm/pasiens/' + pasienId + '/merchandises', function(resp){
                    let rec = (resp.data || []).find(r => r.merchandise_id == merchId);
                    if (!rec) return; // nothing to delete
                    $.ajax({
                        url: '/erm/pasiens/' + pasienId + '/merchandises/' + rec.id,
                        type: 'DELETE',
                        data: { _token: $('meta[name="csrf-token"]').attr('content') },
                        success: function(){
                            // removed - disable qty input
                            $qtyInput.prop('disabled', true);
                            $qtyInput.removeData('pm-id');
                        },
                        error: function(){
                            alert('Failed to remove merchandise');
                        }
                    });
                });
            }
        });

        // Handle qty changes for already-checked items
        $(document).on('change', '.merch-qty', function(){
            let $input = $(this);
            let merchId = $input.data('id');
            let pasienId = $('#modalManagePasien').data('pasien-id');
            if (!pasienId) return alert('Pasien ID missing');

            let qty = parseInt($input.val() || 1, 10);
            if (qty < 1) { qty = 1; $input.val(1); }
            let stock = parseInt($input.data('stock') || 0, 10);
            if (stock <= 0) {
                Swal.fire({ icon: 'warning', title: 'Stok habis', text: 'Stok item ini habis dan tidak dapat diubah.' });
                $input.val(1);
                return;
            }

            if (qty > stock) {
                Swal.fire({ icon: 'warning', title: 'Stok tidak cukup', text: `Permintaan qty (${qty}) melebihi stok (${stock}).` });
                $input.val(stock);
                qty = stock;
            }

            // If checkbox isn't checked, just return (qty changes only for checked items)
            let $checkbox = $('.merch-checkbox[data-id="' + merchId + '"]');
            if (!$checkbox.is(':checked')) return;

            // Try to use stored pm-id first
            let pmId = $input.data('pm-id');
            if (pmId) {
                // send update request (PUT)
                $.ajax({
                    url: '/erm/pasiens/' + pasienId + '/merchandises/' + pmId,
                    type: 'PUT',
                    data: { _token: $('meta[name="csrf-token"]').attr('content'), quantity: qty },
                    success: function(resp){
                        // updated
                    },
                    error: function(){
                        alert('Failed to update quantity');
                    }
                });
                return;
            }

            // fallback: fetch pasien receipts to find pm id and then update
            $.get('/erm/pasiens/' + pasienId + '/merchandises', function(resp){
                let rec = (resp.data || []).find(r => r.merchandise_id == merchId);
                if (!rec) return; // nothing to update
                $.ajax({
                    url: '/erm/pasiens/' + pasienId + '/merchandises/' + rec.id,
                    type: 'PUT',
                    data: { _token: $('meta[name="csrf-token"]').attr('content'), quantity: qty },
                    success: function(){
                        $input.data('pm-id', rec.id);
                    },
                    error: function(){
                        alert('Failed to update quantity');
                    }
                });
            });
        });
    });
</script>
@endsection
