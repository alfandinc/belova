@extends('layouts.erm.app')

@section('title', 'ERM | Tindakan & Inform Consent')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')
<style>
.step {
    display: none;
}
.step-navigation {
    margin-top: 20px;
    text-align: center;
}
/* Ensure custom modal content scrolls when tall */
#modalCustomTindakan .modal-body {
    max-height: calc(100vh - 200px);
    overflow-y: auto;
}
/* Tweak column widths inside the custom tindakan modal */
#modalCustomTindakan .obat-list table td,
#modalCustomTindakan .obat-list table th {
    vertical-align: middle;
}
#modalCustomTindakan .obat-qty {
    max-width: 90px;
}
#modalCustomTindakan .obat-dosis {
    max-width: 140px;
}
#modalCustomTindakan .obat-satuan-dosis {
    max-width: 140px;
}
#modalCustomTindakan .hpp-td {
    min-width: 160px;
    width: 160px;
}
#modalCustomTindakan .obat-list table td .form-control {
    display: block;
    width: 100%;
}
</style>

@include('erm.partials.modal-alergipasien')

@include('erm.partials.modal-tindakan-informconsent')
@include('erm.partials.modal-tindakan-fotohasil')
@include('erm.partials.modal-tindakan-spk')


<div class="container-fluid">
    <div class="d-flex  align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Tindakan & Inform Consent</h3>
    </div>
       <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Rawat Jalan</li>
                            <li class="breadcrumb-item active">Tindakan & Inform Consent</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->  
    <!-- end page title end breadcrumb -->
    @include('erm.partials.card-identitaspasien')

    <div class="row gx-0">
        <div class="col-lg-12 col-md-12 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Riwayat Tindakan Pasien</h5>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <!-- Inside the history tindakan table -->
                        <table id="historyTindakanTable" class="table table-bordered w-100">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Tindakan</th>
                                    
                                    <th>Dokter</th>
                                    <th>Spesialisasi</th>
                                    <th>Status</th>
                                    <th>Dokumen</th>
                                   
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tindakan DataTable -->
        <div class="col-lg-12 col-md-12 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center">
                    <h5 class="mb-0">Daftar Tindakan Dokter</h5>
                    <div class="ml-auto">
                        <button id="openCustomTindakanModal" class="btn btn-outline-primary btn-sm">Create Custom Tindakan</button>
                    </div>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table id="tindakanTable" class="table table-bordered w-100">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Harga</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Paket Tindakan DataTable -->
        {{-- <div class="col-lg-6 col-md-12 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Daftar Paket Tindakan</h5>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table id="paketTindakanTable" class="table table-bordered w-100">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Harga Paket</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div> --}}
    </div>
</div>
<!-- Riwayat Tindakan Detail Modal -->
<div class="modal fade" id="modalRiwayatDetail" tabindex="-1" aria-labelledby="modalRiwayatDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRiwayatDetailLabel">Detail Riwayat Tindakan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="riwayatDetailContent">
                <!-- Kode Tindakan and Obat list will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <!-- Removed Save Changes button, use Simpan Perubahan in modal body -->
            </div>
        </div>
    </div>
</div>

<!-- SOP Detail Modal -->
<div class="modal fade" id="modalSopDetail" tabindex="-1" aria-labelledby="modalSopDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
    <h5 class="modal-title" id="modalSopDetailLabel">Detail Kode Tindakan</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered" id="sopTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Obat (Bundled)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Kode tindakan rows will be injected here -->
                    </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
{{-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}

<script>
    $(document).ready(function () {
        $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
        let tindakanData = [];
        let currentStep = 1;
        const spesialisasiId = @json($spesialisasiId); 
        // Function to format numbers as Rupiah
        function formatRupiah(value) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
        }

        // Initialize Tindakan DataTable
        $('#tindakanTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true, // Enable responsiveness
            pageLength: 10, // Show 10 rows per page
            ajax: `/erm/tindakan/data/${spesialisasiId}`,
            columns: [
                { data: 'id', name: 'id' },
                { data: 'nama', name: 'nama' },
                { 
                    data: 'harga', 
                    name: 'harga',
                    render: function (data) {
                        return formatRupiah(data); // Format harga as Rupiah
                    }
                },
                { 
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        return `
                            <button class="btn btn-success btn-sm buat-tindakan" data-id="${row.id}" data-type="tindakan">Buat Tindakan</button>
                            <button class="btn btn-info btn-sm detail-sop-btn" data-id="${row.id}">Detail</button>
                        `;
                    }
                },
            ],
        });

                // Add Custom Tindakan modal HTML and handlers
                const customModalHtml = `
        <div class="modal fade" id="modalCustomTindakan" tabindex="-1" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create Custom Tindakan</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="customTindakanForm">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nama Tindakan</label>
                                    <input id="customTindakanName" name="nama_tindakan" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Harga Tindakan (input)</label>
                                    <input id="customTindakanHargaInput" name="harga" type="number" min="0" step="0.01" class="form-control" required value="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Rekomendasi Harga</label>
                                    <input id="customTindakanHarga" type="text" class="form-control" readonly value="0">
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div id="customKodeTindakanContainer"></div>
                        <div class="text-right mt-2">
                                <button type="button" id="addKodeTindakanBtn" class="btn btn-primary btn-sm">Tambah Kode Tindakan</button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Simpan Custom Tindakan</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>`;

                // append modal to body once
                if ($('#modalCustomTindakan').length === 0) {
                    $('body').append(customModalHtml);
                }

                // small handler to add initial kode tindakan block and via button
                $(document).on('click', '#addKodeTindakanBtn', function(){ addKodeTindakanBlock(); });
                // add one on load to ensure UI isn't empty
                $(document).ready(function(){ if ($('#customKodeTindakanContainer').children().length === 0) addKodeTindakanBlock(); });

                // --- Custom Tindakan modal handlers ---
                // Open modal
                $(document).on('click', '#openCustomTindakanModal', function() {
                    $('#customTindakanForm')[0].reset();
                    $('#customKodeTindakanContainer').html('');
                    addKodeTindakanBlock();
                    $('#modalCustomTindakan').modal('show');
                });

                // Add kode tindakan block; kode chosen via Select2 (search existing kode tindakan)
                function addKodeTindakanBlock(data = {}) {
                    const index = Date.now();
                    const html = `
                        <div class="card mb-2 kode-tindakan-block" data-index="${index}">
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="col-md-6 mb-2">
                                        <label> Pilih Kode Tindakan</label>
                                        <select name="kode_tindakan_id[]" class="form-control kode-tindakan-select" style="width:100%"></select>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label>HPP</label>
                                        <input type="number" name="kode_tindakan_hpp[]" class="form-control kode-tindakan-hpp" value="${data.hpp||''}" step="0.01" readonly>
                                    </div>
                                    <div class="col-md-2 mb-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-danger remove-kode-tindakan ml-auto" title="Hapus"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                                <div class="obat-list">
                                    <table class="table table-sm mb-0">
                                                <thead>
                                                    <tr><th>Obat/BHP</th><th>Qty</th><th>Dosis</th><th>Satuan</th><th>HPP Jual</th><th>Aksi</th></tr>
                                                </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-secondary btn-sm add-obat-row">Tambah Obat/BHP</button>
                                </div>
                            </div>
                        </div>`;
                    $('#customKodeTindakanContainer').append(html);

                    // initialize select2 on the newly added select
                    const $select = $('#customKodeTindakanContainer').find('.kode-tindakan-block').last().find('.kode-tindakan-select');
                    $select.select2({
                        placeholder: 'Cari kode tindakan...',
                        ajax: {
                            url: '/erm/kodetindakan/search',
                            dataType: 'json',
                            delay: 250,
                            data: function(params) { return { q: params.term }; },
                            processResults: function(data) {
                                return { results: data.results.map(r => ({ id: r.id, text: r.text })) };
                            }
                        },
                        minimumInputLength: 1,
                        width: '100%'
                    });

                    // whenever a kode hpp changes later, update the total harga
                    updateModalHarga();

                    // If data contains kode_id preselect it
                    if (data.kode_id) {
                        const option = new Option(data.text || data.name || data.kode_id, data.kode_id, true, true);
                        $select.append(option).trigger('change');
                        // load its obats
                        loadKodeObatsIntoBlock(data.kode_id, $select.closest('.kode-tindakan-block'));
                        // fetch kode details to fill hpp
                        $.get(`/erm/kodetindakan/${data.kode_id}`, function(kd) {
                            $select.closest('.kode-tindakan-block').find('.kode-tindakan-hpp').val(kd.hpp || '');
                        });
                    }
                }

                // When kode tindakan selected, load its obats and populate rows
                $(document).on('change', '.kode-tindakan-select', function() {
                    const kodeId = $(this).val();
                    const block = $(this).closest('.kode-tindakan-block');
                    if (kodeId) loadKodeObatsIntoBlock(kodeId, block);
                    // also load kode details for HPP
                    if (kodeId) {
                        $.get(`/erm/kodetindakan/${kodeId}`, function(kd) {
                            block.find('.kode-tindakan-hpp').val(kd.hpp || '');
                            updateModalHarga();
                        }).fail(function() {
                            block.find('.kode-tindakan-hpp').val('');
                            updateModalHarga();
                        });
                    } else {
                        block.find('.kode-tindakan-hpp').val('');
                        updateModalHarga();
                    }
                });

                function loadKodeObatsIntoBlock(kodeId, $block) {
                    const tbody = $block.find('tbody');
                    tbody.html('<tr><td colspan="5" class="text-center">Loading...</td></tr>');
                    $.get(`/erm/kodetindakan/${kodeId}/obats`, function(data) {
                        tbody.html('');
                        if (!data || data.length === 0) {
                            tbody.html('<tr><td colspan="5" class="text-center text-muted">No obats</td></tr>');
                            return;
                        }
                        data.forEach(function(o) {
                            // combine dosis and satuan_dosis for display in the input
                            const hppJual = o.hpp_jual ? Number(o.hpp_jual).toLocaleString('id-ID') : '-';
                            const row = $(`<tr>
                                <td><select class="form-control obat-select" style="width:100%"></select></td>
                                <td><input type="number" name="obat_qty[]" class="form-control obat-qty" value="${o.qty||1}" min="1"></td>
                                <td><input type="text" name="obat_dosis[]" class="form-control obat-dosis" value="${o.dosis || ''}"></td>
                                <td><input type="text" name="obat_satuan_dosis[]" class="form-control obat-satuan-dosis" value="${o.satuan_dosis || ''}"></td>
                                <td class="hpp-td text-right">${hppJual}</td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-obat-row" title="Hapus"><i class="fas fa-trash"></i></button></td>
                            </tr>`);
                            tbody.append(row);
                            // init obat select with preselected value
                            const $sel = row.find('.obat-select');
                            const option = new Option(o.nama, o.id, true, true);
                            $sel.append(option).trigger('change');
                            // init select2 with obat search endpoint
                            $sel.select2({
                                placeholder: 'Cari obat...',
                                ajax: {
                                    url: '/obat/search',
                                    dataType: 'json',
                                    delay: 250,
                                    data: function(params) { return { q: params.term }; },
                                    processResults: function(data) { return data.results ? { results: data.results } : { results: data }; }
                                },
                                minimumInputLength: 1,
                                width: '100%'
                            });
                            // set hpp cell for preselected
                            const hppCell = row.find('.hpp-td');
                            if (o.hpp_jual) {
                                hppCell.text(Number(o.hpp_jual).toLocaleString('id-ID'));
                            }
                        });
                    }).fail(function() {
                        tbody.html('<tr><td colspan="5" class="text-center text-danger">Failed loading obats</td></tr>');
                    });
                }

                // Remove kode tindakan block
                $(document).on('click', '.remove-kode-tindakan', function() {
                    $(this).closest('.kode-tindakan-block').remove();
                    updateModalHarga();
                });

                // Update modal harga by summing kode-tindakan-hpp inputs
                function updateModalHarga() {
                    let total = 0;
                    $('#customKodeTindakanContainer').find('.kode-tindakan-hpp').each(function() {
                        const v = parseFloat($(this).val());
                        if (!isNaN(v)) total += v;
                    });
                    $('#customTindakanHarga').val(new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(total));
                }

                // Add obat row (with Select2 obat lookup)
                $(document).on('click', '.add-obat-row', function() {
                    const tbody = $(this).closest('.kode-tindakan-block').find('tbody');
                    const row = $(`<tr>
                        <td><select class="form-control obat-select" style="width:100%"></select></td>
                        <td><input type="number" name="obat_qty[]" class="form-control obat-qty" value="1" min="1"></td>
                        <td><input type="text" name="obat_dosis[]" class="form-control obat-dosis" placeholder=""></td>
                        <td><input type="text" name="obat_satuan_dosis[]" class="form-control obat-satuan-dosis" placeholder=""></td>
                        <td class="hpp-td text-right">-</td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-obat-row" title="Hapus"><i class="fas fa-trash"></i></button></td>
                    </tr>`);
                    tbody.append(row);
                    // init select2 for obat with ajax to existing endpoint
                    row.find('.obat-select').select2({
                        placeholder: 'Cari obat...',
                        ajax: {
                            url: '/obat/search',
                            dataType: 'json',
                            delay: 250,
                            data: function(params) { return { q: params.term }; },
                            processResults: function(data) { return data.results ? { results: data.results } : { results: data }; }
                        },
                        minimumInputLength: 1,
                        width: '100%'
                    });
                    // when user selects an obat, update the HPP cell
                    row.find('.obat-select').on('select2:select', function(e) {
                        const selected = e.params.data;
                        const hppCell = $(this).closest('tr').find('.hpp-td');
                        if (selected && selected.hpp_jual) {
                            hppCell.text(Number(selected.hpp_jual).toLocaleString('id-ID'));
                        } else {
                            // try fetching full obat detail if not provided
                            const obatId = selected.id;
                            if (obatId) {
                                $.get('/erm/ajax/obat/' + obatId, function(d) {
                                    if (d && d.hpp_jual) hppCell.text(Number(d.hpp_jual).toLocaleString('id-ID'));
                                }).fail(function() { hppCell.text('-'); });
                            } else {
                                hppCell.text('-');
                            }
                        }
                    });
                });

                // Remove obat row
                $(document).on('click', '.remove-obat-row', function() {
                    $(this).closest('tr').remove();
                });

                // Submit custom tindakan via AJAX
                $(document).on('submit', '#customTindakanForm', function(e) {
                    e.preventDefault();
                    const form = $(this);
                    const payload = { kode_tindakans: [] };
                    $('#customKodeTindakanContainer .kode-tindakan-block').each(function() {
                        const block = $(this);
                        const kodeId = block.find('.kode-tindakan-select').val();
                        const kodeHarga = block.find('.kode-tindakan-hpp').val() || null;
                        const obats = [];
                        block.find('tbody tr').each(function() {
                            const r = $(this);
                            const obatId = r.find('.obat-select').val();
                            const qty = r.find('.obat-qty').val() || 1;
                            const dosis = r.find('.obat-dosis').val() || null;
                            const satuan = r.find('.obat-satuan-dosis').val() || null;
                            if (obatId) {
                                obats.push({ obat_id: obatId, qty: qty, dosis: dosis, satuan_dosis: satuan });
                            }
                        });
                        // include current display text for kode so we can show it in confirmation form
                        const kodeText = block.find('.kode-tindakan-select').find('option:selected').text() || '';
                        payload.kode_tindakans.push({ kode_id: kodeId, hpp: kodeHarga, obats: obats, kode_text: kodeText });
                    });
                    // Basic validation
                    if (payload.kode_tindakans.length === 0) {
                        Swal.fire('Error', 'Tambahkan minimal 1 kode tindakan', 'error');
                        return;
                    }

                    Swal.fire({ title: 'Menyimpan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                    // Determine which kode entries had their obat lists modified compared to original template
                    // We'll compare qty, dosis and satuan_dosis as well (not just IDs)
                    const createNewKodeFor = [];
                    // payload.kode_tindakans corresponds to blocks; use it to compare with server canonical
                    for (let i = 0; i < payload.kode_tindakans.length; i++) {
                        const entry = payload.kode_tindakans[i];
                        const kodeId = entry.kode_id;
                        if (!kodeId) continue;

                        // fetch canonical obats for this kode synchronously (we need result before prompting)
                        let canonical = [];
                        $.ajax({ url: `/erm/kodetindakan/${kodeId}/obats`, method: 'GET', async: false })
                        .done(function(kodeObats) {
                            canonical = kodeObats || [];
                        }).fail(function() {
                            canonical = [];
                        });

                        // normalize canonical into map by obat_id
                        const canonicalMap = {};
                        canonical.forEach(function(o) {
                            canonicalMap['' + o.id] = {
                                qty: (o.qty !== undefined && o.qty !== null) ? (''+o.qty) : '1',
                                dosis: (o.dosis !== undefined && o.dosis !== null) ? (''+o.dosis) : '',
                                satuan_dosis: (o.satuan_dosis !== undefined && o.satuan_dosis !== null) ? (''+o.satuan_dosis) : ''
                            };
                        });

                        // normalize UI/provided obats into map
                        const uiMap = {};
                        (entry.obats || []).forEach(function(o) {
                            uiMap['' + o.obat_id] = {
                                qty: (o.qty !== undefined && o.qty !== null) ? (''+o.qty) : '1',
                                dosis: (o.dosis !== undefined && o.dosis !== null) ? (''+o.dosis) : '',
                                satuan_dosis: (o.satuan_dosis !== undefined && o.satuan_dosis !== null) ? (''+o.satuan_dosis) : ''
                            };
                        });

                        // quick comparisons: length / keys
                        const canonicalIds = Object.keys(canonicalMap).sort();
                        const uiIds = Object.keys(uiMap).sort();
                        let edited = false;
                        if (canonicalIds.length !== uiIds.length) edited = true;
                        else {
                            for (let k = 0; k < canonicalIds.length; k++) {
                                if (canonicalIds[k] !== uiIds[k]) { edited = true; break; }
                                const id = canonicalIds[k];
                                const c = canonicalMap[id];
                                const u = uiMap[id];
                                if (!u || c.qty !== u.qty || c.dosis !== u.dosis || c.satuan_dosis !== u.satuan_dosis) {
                                    edited = true; break;
                                }
                            }
                        }

                        if (edited) createNewKodeFor.push(kodeId);
                    }

                    const finalPayload = {
                        nama_tindakan: $('#customTindakanName').val(),
                        harga: parseFloat($('#customTindakanHargaInput').val()) || 0,
                        spesialis_id: spesialisasiId,
                        kode_tindakans: payload.kode_tindakans,
                        visitation_id: {{ $visitation->id }},
                        create_new_kode_for: createNewKodeFor,
                    };

                    function doSubmit(createNew) {
                        Swal.fire({ title: 'Menyimpan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                        $.ajax({
                            url: '/erm/tindakan/custom',
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify(finalPayload),
                            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                            success: function(res) {
                                Swal.close();
                                if (res.success) {
                                    Swal.fire('Berhasil', res.message || 'Custom tindakan disimpan', 'success')
                                        .then(() => { $('#modalCustomTindakan').modal('hide'); $('#tindakanTable').DataTable().ajax.reload(); });
                                } else {
                                    Swal.fire('Error', res.message || 'Gagal menyimpan', 'error');
                                }
                            },
                            error: function(xhr) {
                                Swal.close();
                                console.error(xhr);
                                Swal.fire('Error', 'Terjadi kesalahan server', 'error');
                            }
                        });
                    }

                    if (createNewKodeFor.length > 0) {
                        // build HTML inputs for new kode names
                        let html = `<p>Anda telah mengubah bundel obat untuk ${createNewKodeFor.length} kode tindakan.</p>`;
                        html += `<p>Masukkan nama baru untuk setiap kode (kosongkan untuk pakai nama default):</p>`;
                        createNewKodeFor.forEach(function(kid) {
                            // find payload entry for this kode
                            const p = payload.kode_tindakans.find(e => (''+e.kode_id) === (''+kid));
                            const display = p ? (p.kode_text || '') : '';
                            const inputId = `new_kode_name_${kid}`;
                            html += `<div style="margin-bottom:8px"><label style="font-weight:600">${display}</label><input id="${inputId}" class="swal2-input" placeholder="Nama kode baru" value="${display} (salin)"></div>`;
                        });

                        // hide bootstrap modal so SweetAlert can accept focus/input
                        $('#modalCustomTindakan').modal('hide');
                        Swal.fire({
                            title: 'Buat Kode Tindakan Baru?',
                            html: html,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, buat kode baru',
                            cancelButtonText: 'Tidak, pakai kode lama',
                            preConfirm: () => {
                                const results = [];
                                createNewKodeFor.forEach(function(kid) {
                                    const input = document.getElementById(`new_kode_name_${kid}`);
                                    const val = input ? input.value.trim() : '';
                                    results.push({ kode_id: kid, new_name: val });
                                });
                                return results;
                            }
                        }).then((result) => {
                            if (result.value) {
                                finalPayload.create_new_kode_for = result.value || [];
                                doSubmit(true);
                            } else {
                                // user cancelled the Swal -> reopen modal for further edits
                                finalPayload.create_new_kode_for = [];
                                $('#modalCustomTindakan').modal('show');
                            }
                        });
                    } else {
                        doSubmit(false);
                    }
                });

        // Initialize Paket Tindakan DataTable
        $('#paketTindakanTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: 10,
            ajax: `/erm/paket-tindakan/data/${spesialisasiId}`,
            columns: [
                { data: 'id', name: 'id' },
                { data: 'nama', name: 'nama' },
                { 
                    data: 'harga_paket', 
                    name: 'harga_paket',
                    render: function (data) {
                        return formatRupiah(data);
                    }
                },
                { 
                    data: 'action', 
                    name: 'action', 
                    orderable: false, 
                    searchable: false,
                },
            ],
        });

        // Initialize History Tindakan DataTable
        $('#historyTindakanTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: 5,
            ajax: `/erm/tindakan/history/${@json($visitation->id)}`,
            columns: [
                { data: 'tanggal', name: 'tanggal', render: function(data, type, row) {
                    return data;
                } },
                { data: 'tindakan', name: 'tindakan' },
                { data: 'dokter', name: 'dokter' },
                { data: 'spesialisasi', name: 'spesialisasi' },
                { data: 'status', name: 'status' },
                { 
                    data: 'dokumen', 
                    name: 'dokumen', 
                    orderable: false, 
                    searchable: false,
                    render: function(data, type, row) {
                        // Ensure Inform Consent link uses /storage/ prefix
                        let buttons = '';
                        if (row.inform_consent) {
                            const fileUrl = `/storage/${row.inform_consent.file_path}`;
                            const hasBefore = row.inform_consent.before_image_path && row.inform_consent.before_image_path.trim() !== '';
                            const hasAfter = row.inform_consent.after_image_path && row.inform_consent.after_image_path.trim() !== '';
                            let fotoBtnText, fotoBtnClass, fotoBtnIcon;
                            if (hasBefore && hasAfter) {
                                fotoBtnText = 'Lihat Foto';
                                fotoBtnClass = 'btn-primary';
                                fotoBtnIcon = '<i class="fas fa-eye mr-1"></i>';
                            } else {
                                fotoBtnText = 'Upload Foto';
                                fotoBtnClass = 'btn-success';
                                fotoBtnIcon = '<i class="fas fa-upload mr-1"></i>';
                            }
                            buttons += `
                                <a href="${fileUrl}" target="_blank" class="btn btn-info btn-sm mr-1">Inform Consent</a>
                                <button class="btn ${fotoBtnClass} btn-sm foto-hasil-btn mr-1" data-id="${row.inform_consent.id}" data-before="${row.inform_consent.before_image_path || ''}" data-after="${row.inform_consent.after_image_path || ''}">${fotoBtnIcon}${fotoBtnText}</button>
                                <button class="btn btn-info btn-sm detail-riwayat-btn mr-1" data-id="${row.id}"><i class="fas fa-list mr-1"></i>Detail</button>
                            `;
                        } else {
                            buttons += '<span class="text-muted">Belum ada inform consent</span>';
                        }
                        // Add Batalkan button for all rows
                        buttons += `<button class="btn btn-danger btn-sm batalkan-tindakan-btn" data-id="${row.id}">Batalkan</button>`;
                        return buttons;
                    }
                },
                // Hidden column for raw date sorting
                { data: 'tanggal_raw', name: 'tanggal_raw', visible: false },
            ],
            order: [[6, 'desc']] // Sort by hidden raw date column
        });

        // Handle click on "Foto Hasil" button
    $(document).on('click', '.foto-hasil-btn', function() {
        const id = $(this).data('id');
        const beforePath = $(this).data('before');
        const afterPath = $(this).data('after');
        // Reset form
        $('#informConsentId').val(id);
        $('#beforeImage').val('');
        $('#afterImage').val('');
        $('#beforePreview').hide();
        $('#afterPreview').hide();
        // Reset allow_post checkbox
        $('#allowPost').prop('checked', false);
        // Show existing images if available
        if (beforePath) {
            $('#beforePreview').attr('src', `/storage/${beforePath}`).show();
        }
        if (afterPath) {
            $('#afterPreview').attr('src', `/storage/${afterPath}`).show();
        }
        // Fetch allow_post value via AJAX and set checkbox
        $.get(`/erm/inform-consent/${id}/get`, function(response) {
            if (response && typeof response.allow_post !== 'undefined') {
                $('#allowPost').prop('checked', !!response.allow_post);
            }
        });
        // Show modal
        $('#modalFotoHasil').modal('show');
    });
    
    // Preview images before upload
    $('#beforeImage').change(function() {
        previewImage(this, '#beforePreview');
    });
    
    $('#afterImage').change(function() {
        previewImage(this, '#afterPreview');
    });
    
    // Function to preview images
    function previewImage(input, previewSelector) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $(previewSelector).attr('src', e.target.result).show();
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Handle saving photos
    $('#saveFotoHasil').click(function() {
    const id = $('#informConsentId').val();
    
    // Get form data directly from the form element
    const formData = new FormData($('#fotoHasilForm')[0]);
    
    // Show loading indicator
    Swal.fire({
        title: 'Uploading...',
        text: 'Please wait while images are being uploaded',
        icon: 'info',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
    });
    
    // Submit form via AJAX
    $.ajax({
        url: `/erm/tindakan/upload-foto/${id}`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                Swal.fire('Success', 'Foto hasil berhasil diupload', 'success');
                
                // Close modal and refresh table
                $('#modalFotoHasil').modal('hide');
                $('#historyTindakanTable').DataTable().ajax.reload();
            } else {
                Swal.fire('Error', 'Failed to upload images', 'error');
            }
        },
        error: function(xhr) {
            console.error('Error:', xhr);
            Swal.fire('Error', 'Failed to upload images. Please try again.', 'error');
        }
    });
});
    
       // Definisi fungsi untuk inisialisasi signature pad
        function initializeSignaturePads(step) {
            console.log(`Initializing signature pads for step ${step}`);
            
            // Selector yang lebih spesifik untuk mendapatkan canvas pada langkah yang aktif
            const stepSelector = tindakanData.length > 0 ? `.step[data-step="${step}"] ` : "";
            const patientCanvas = $(`${stepSelector}#signatureCanvas`).get(0);
            const witnessCanvas = $(`${stepSelector}#witnessSignatureCanvas`).get(0);

            if (!patientCanvas || !witnessCanvas) {
                console.log(`Canvas elements not found for step ${step}`);
                return false;
            }

            const scale = window.devicePixelRatio || 1;

            function setupCanvas(canvas) {
                const parent = canvas.parentElement;
                const width = parent.clientWidth;
                const height = parent.clientHeight;

                canvas.width = width * scale;
                canvas.height = height * scale;

                const ctx = canvas.getContext('2d');
                ctx.scale(scale, scale);
                return canvas;
            }

            if (!signaturePads[step]) {
                setupCanvas(patientCanvas);
                setupCanvas(witnessCanvas);

                signaturePads[step] = {
                    patient: new SignaturePad(patientCanvas),
                    witness: new SignaturePad(witnessCanvas)
                };

                // Bind clear buttons untuk langkah ini
                $(document).off('click', `${stepSelector}#clearSignature`).on('click', `${stepSelector}#clearSignature`, function() {
                    if (signaturePads[step] && signaturePads[step].patient) {
                        signaturePads[step].patient.clear();
                    }
                });

                $(document).off('click', `${stepSelector}#clearWitnessSignature`).on('click', `${stepSelector}#clearWitnessSignature`, function() {
                    if (signaturePads[step] && signaturePads[step].witness) {
                        signaturePads[step].witness.clear();
                    }
                });
            }
            
            return true;
        }

        // Event handler untuk modal show event
        $(document).on('shown.bs.modal', '#modalInformConsent', function () {
            console.log("Modal fully shown");
            setTimeout(function() {
                if (tindakanData.length > 0) {
                    // Paket tindakan - tampilkan langkah pertama
                    showStep(1);
                } else {
                    // Tindakan tunggal
                    initializeSignaturePads(1);
                }
            }, 300); // Sedikit delay untuk memastikan DOM selesai render
        });

        // Menggunakan delegate untuk menangkap klik pada tombol next/prev
        $(document).on('click', '.next-step', function () {
            console.log('Next button clicked, current step:', currentStep);
            if (currentStep < tindakanData.length) {
                currentStep++;
                showStep(currentStep);
            }
        });

        $(document).on('click', '.prev-step', function () {
            console.log('Previous button clicked, current step:', currentStep);
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        });

            // Implementasi showStep yang lebih baik
        function showStep(step) {
            console.log(`Showing step ${step} of ${tindakanData.length}`);
            
            // Hide all steps and show only the current one
            $('.step').hide();
            $(`.step[data-step="${step}"]`).show();

            // Show "Simpan" button only on the last step
            if (step === tindakanData.length) {
                $('#saveInformConsent').removeClass('d-none');
                $('.next-step').addClass('d-none');
            } else {
                $('#saveInformConsent').addClass('d-none');
                $('.next-step').removeClass('d-none');
            }

            // Beri waktu untuk DOM update sebelum inisialisasi signature
            setTimeout(function() {
                initializeSignaturePads(step);
            }, 100);
        };

            // Fungsi buat-tindakan
        $(document).on('click', '.buat-tindakan', function () {
            window.lastTindakanIdClicked = $(this).data('id'); // Always set this first!
            const type = $(this).data('type');
            const id = $(this).data('id');
            const visitationId = @json($visitation->id);
            
            // Reset signature pads dan tindakan data
            signaturePads = {};
            tindakanData = [];

            if (type === 'tindakan') {
                $.get(`/erm/tindakan/inform-consent/${id}?visitation_id=${visitationId}`)
                    .done(function (html) {
                        $('#modalInformConsentBody').html(html);
                        $('#modalInformConsentBody').append(`
                            <div class="text-center mt-4">
                                <button id="saveInformConsent" class="btn btn-success">Simpan</button>
                            </div>
                        `);
                        $('#modalInformConsent').modal('show');
                    })
                    .fail(function (jqXHR, textStatus, errorThrown) {
                        console.error('AJAX Error:', textStatus, errorThrown);
                        alert('Error loading inform consent form');
                    });
            }
        });

            // Fungsi buat-paket-tindakan dengan tracking status load
        $(document).on('click', '.buat-paket-tindakan', function () {
            tindakanData = JSON.parse($(this).attr('data-tindakan'));
            const paketId = $(this).data('id');
            window.currentPaketId = paketId;
            
            // FIXED PRICE EXTRACTION
            const row = $(this).closest('tr');
            const priceText = row.find('td:eq(2)').text().trim();
            
            // Properly handle Indonesian currency format (Rp 750.000,00)
            let priceDigitsOnly = priceText
                .replace(/[^\d,\.]/g, '') // Remove everything except digits, comma and dot
                .replace(/\./g, '')       // Remove thousand separators (dots)
                .replace(',', '.');       // Replace decimal comma with dot
            
            window.paketHarga = parseFloat(priceDigitsOnly);
            window.paketNama = row.find('td:eq(1)').text().trim();
            
            console.log('Extracted price text:', priceText);
            console.log('Extracted digits:', priceDigitsOnly);
            console.log('Paket price (parsed):', window.paketHarga);
            console.log('Paket name:', window.paketNama);
            
            const visitationId = @json($visitation->id);

            currentStep = 1;
            signaturePads = {}; // Reset signature pads
            
            console.log(`Building steps for ${tindakanData.length} tindakan`);

            let stepsHtml = '';
            tindakanData.forEach((tindakan, index) => {
                stepsHtml += `<div class="step" data-step="${index + 1}">
                    <h5>Inform Consent for ${tindakan.nama}</h5>
                    <div id="informConsentStep${index + 1}"></div>
                </div>`;
            });

            $('#modalInformConsentBody').html(`
                <div id="stepsContainer">
                    ${stepsHtml}
                </div>
                <div class="step-navigation mt-3">
                    <button class="btn btn-secondary prev-step">Previous</button>
                    <button class="btn btn-primary next-step">Next</button>
                    <button id="saveInformConsent" class="btn btn-success d-none">Simpan</button>
                </div>
            `);

            // Memuat konten untuk setiap langkah
            let loadedSteps = 0;
            tindakanData.forEach((tindakan, index) => {
                $.get(`/erm/tindakan/inform-consent/${tindakan.id}?visitation_id=${visitationId}`)
                    .done(function (html) {
                        $(`#informConsentStep${index + 1}`).html(html);
                        loadedSteps++;
                        
                        // Ketika semua langkah dimuat, tampilkan modal
                        if (loadedSteps === tindakanData.length) {
                            $('#modalInformConsent').modal('show');
                        }
                    })
                    .fail(function () {
                        alert('Error loading inform consent form');
                    });
            });
        });

        // Handler untuk menyimpan semua inform consent di paket
        $(document).on('click', '#saveInformConsent', function () {
            if (tindakanData.length === 0) {
                // Tindakan tunggal - gunakan fungsi simpan yang sudah ada
                saveSingleInformConsent();
            } else {
                // Paket tindakan - simpan semua tanda tangan
                saveAllInformConsents();
            }
        });
        
        // Fungsi untuk menyimpan satu inform consent
        function saveSingleInformConsent() {
            const form = $('#informConsentForm');
            // Get tindakanId from form or fallback to last clicked button
            let tindakanId = form.find('input[name="tindakan_id"]').val();
            if (!tindakanId) {
                // Try to get from last clicked .buat-tindakan button
                tindakanId = window.lastTindakanIdClicked || null;
            }
            // Get visitationId from form or from PHP context
            let visitationId = form.find('input[name="visitation_id"]').val();
            if (!visitationId) {
                visitationId = @json($visitation->id);
            }
            // If there is no signature pad (no form fields for signature), skip signature validation
            const hasSignaturePad = signaturePads[1] && signaturePads[1].patient && signaturePads[1].witness;
            if (form.length && hasSignaturePad) {
                if (signaturePads[1].patient.isEmpty()) {
                    Swal.fire('Error', 'Please provide a signature for the patient.', 'error');
                    return;
                }
                if (signaturePads[1].witness.isEmpty()) {
                    Swal.fire('Error', 'Please provide a signature for the witness.', 'error');
                    return;
                }
                // Capture signature data
                $('#signatureData').val(signaturePads[1].patient.toDataURL());
                $('#witnessSignatureData').val(signaturePads[1].witness.toDataURL());
            }
            // Add billing data to the form if present
            if (form.length) {
                if (!form.find('input[name="jumlah"]').length) {
                    form.append(`<input type="hidden" name="jumlah" value="${form.find('.harga-tindakan').data('harga') || 0}">`);
                }
                if (!form.find('input[name="keterangan"]').length) {
                    form.append(`<input type="hidden" name="keterangan" value="Tindakan: ${form.find('.nama-tindakan').text()}">`);
                }
            }
            // Show loading
            Swal.fire({
                title: 'Saving...',
                text: 'Please wait while the data is being saved.',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
            });
            // Submit the form (or just send minimal data if no form)
            let formData;
            let url;
            if (form.length) {
                formData = new FormData(form[0]);
                url = form.attr('action');
            } else {
                // No form, send minimal data
                formData = new FormData();
                formData.append('tindakan_id', tindakanId);
                formData.append('visitation_id', visitationId);
                formData.append('tanggal', new Date().toISOString().split('T')[0]);
                console.log('DEBUG: Saving without form', { tindakanId, visitationId });
                url = '/erm/tindakan/inform-consent/save';
            }
            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Success', 'Tindakan dan billing berhasil disimpan!', 'success')
                            .then(() => {
                                $('#modalInformConsent').modal('hide');
                                // Reload riwayat tindakan table after saving
                                $('#historyTindakanTable').DataTable().ajax.reload();
                            });
                    } else {
                        Swal.fire('Error', 'Failed to save Inform Consent.', 'error');
                    }
                },
                error: function (xhr) {
                    console.error('Error:', xhr.responseJSON);
                    Swal.fire('Error', 'Failed to save. Please try again.', 'error');
                }
            });
        }
        
        // Fungsi untuk menyimpan semua inform consent di paket
        function saveAllInformConsents() {
            // Validasi semua tanda tangan
            let valid = true;
            let missingSignatures = [];
            
            for (let i = 1; i <= tindakanData.length; i++) {
                if (!signaturePads[i] || !signaturePads[i].patient || !signaturePads[i].witness) {
                    missingSignatures.push(`Step ${i}: Signature pads not initialized`);
                    valid = false;
                    continue;
                }
                
                if (signaturePads[i].patient.isEmpty()) {
                    missingSignatures.push(`Step ${i}: Patient signature missing`);
                    valid = false;
                }
                
                if (signaturePads[i].witness.isEmpty()) {
                    missingSignatures.push(`Step ${i}: Witness signature missing`);
                    valid = false;
                }
            }
            
            if (!valid) {
                Swal.fire('Error', 'Please complete all signatures: ' + missingSignatures.join(', '), 'error');
                return;
            }
            
            // Show loading
            Swal.fire({
                title: 'Saving...',
                text: 'Please wait while all inform consents are being saved.',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
            });
            
            // Buat array untuk menyimpan promises semua request ajax
            const savePromises = [];
            
            // Loop melalui setiap tindakan dan simpan form-nya
            for (let i = 1; i <= tindakanData.length; i++) {
                const step = i;
                const form = $(`.step[data-step="${step}"] #informConsentForm`);
                
                if (!form.length) {
                    console.error(`Form not found for step ${step}`);
                    continue;
                }
                
                // Clone form untuk menghindari konflik
                const clonedForm = form.clone();
                
                // Tambahkan data tanda tangan ke form
                const formData = new FormData(form[0]);
                formData.append('signature', signaturePads[step].patient.toDataURL());
                formData.append('witness_signature', signaturePads[step].witness.toDataURL());
                formData.append('tindakan_id', tindakanData[step-1].id);

               // Add paket_id if exists
                if (window.currentPaketId) {
                    formData.append('paket_id', window.currentPaketId);
                    
                    // Always include the price/name data - the server will only use it once
                    formData.append('jumlah', window.paketHarga || 0);
                    formData.append('keterangan', `Paket Tindakan: ${window.paketNama || 'Unknown'}`);
                }

                // Juga pastikan semua field yang dibutuhkan tersedia
                if (!formData.has('tanggal')) {
                    formData.append('tanggal', new Date().toISOString().split('T')[0]);
                }

                if (!formData.has('nama_pasien') && $('#namaPasien').length) {
                    formData.append('nama_pasien', $('#namaPasien').text().trim());
                }

                if (!formData.has('nama_saksi') && $('#namaSaksi').length) {
                    formData.append('nama_saksi', $('#namaSaksi').val() || 'Saksi');
                }

                if (!formData.has('notes')) {
                    formData.append('notes', '');
                }
                
                // Buat promise untuk request ajax
                const savePromise = new Promise((resolve, reject) => {
                    $.ajax({
                        url: form.attr('action'),
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            resolve(response);
                        },
                        error: function(xhr) {
                            reject(xhr);
                        }
                    });
                });
                
                savePromises.push(savePromise);
            }
            
            // Jalankan semua promises
            Promise.all(savePromises)
                .then(responses => {
                    Swal.fire('Success', 'Semua Tindakan dan billing berhasil disimpan!', 'success')
                        .then(() => {
                            $('#modalInformConsent').modal('hide');
                            // Reload riwayat tindakan table after saving paket
                            $('#historyTindakanTable').DataTable().ajax.reload();
                        });
                })
                .catch(errors => {
                    console.error('Errors:', errors);
                    Swal.fire('Error', 'Some inform consents could not be saved.', 'error');
                });
        }
        
        // SPK Read-Only Functionality
$(document).on('click', '.spk-btn', function() {
    const riwayatId = $(this).data('riwayat-id') || $(this).data('id');
    $('#modalSpkReadOnlyBody').html('<div class="text-center py-4">Loading...</div>');
    $('#modalSpkReadOnly').modal('show');
    $.get(`/erm/tindakan/spk/by-riwayat/${riwayatId}`, function(response) {
        if (response.success) {
            const data = response.data;
            
            // Format tanggal tindakan ke format lokal yang lebih manusiawi
            let tanggalTindakan = data.spk?.tanggal_tindakan || '-';
            if (tanggalTindakan && tanggalTindakan !== '-') {
                // Format ke YYYY-MM-DD (tanggal lokal)
                const d = new Date(tanggalTindakan);
                tanggalTindakan = !isNaN(d) ? d.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) : tanggalTindakan;
            }
            let html = `<div class='row mb-2'>
                <div class='col-md-4'><b>Nama Pasien:</b> ${data.pasien_nama}</div>
                <div class='col-md-4'><b>No RM:</b> ${data.pasien_id}</div>
                <div class='col-md-4'><b>Dokter PJ:</b> ${data.dokter_nama}</div>
            </div>
            <div class='row mb-2'>
                <div class='col-md-4'><b>Tanggal Tindakan:</b> ${tanggalTindakan}</div>
                <div class='col-md-4'><b>Nama Tindakan:</b> ${data.tindakan_nama}</div>
                <div class='col-md-4'><b>Harga:</b> ${data.harga}</div>
            </div>`;
            html += `<div class='table-responsive'><table class='table table-bordered'><thead><tr>
                <th>NO</th><th>TINDAKAN</th><th>PJ</th><th>SBK</th><th>SBA</th><th>SDC</th><th>SDK</th><th>SDL</th><th>MULAI</th><th>SELESAI</th><th>NOTES</th>
            </tr></thead><tbody>`;
            data.sop_list.forEach((sop, idx) => {
                const detail = data.spk?.details?.find(d => d.sop_id == sop.id) || {};
                html += `<tr>
                    <td>${idx+1}</td>
                    <td>${sop.nama_sop}</td>
                    <td>${detail.penanggung_jawab || '-'}</td>
                    <td><input type='checkbox' disabled ${detail.sbk ? 'checked' : ''}></td>
                    <td><input type='checkbox' disabled ${detail.sba ? 'checked' : ''}></td>
                    <td><input type='checkbox' disabled ${detail.sdc ? 'checked' : ''}></td>
                    <td><input type='checkbox' disabled ${detail.sdk ? 'checked' : ''}></td>
                    <td><input type='checkbox' disabled ${detail.sdl ? 'checked' : ''}></td>
                    <td>${detail.waktu_mulai || '-'}</td>
                    <td>${detail.waktu_selesai || '-'}</td>
                    <td>${detail.notes || ''}</td>
                </tr>`;
            });
            html += '</tbody></table></div>';
            $('#modalSpkReadOnlyBody').html(html);
        } else {
            $('#modalSpkReadOnlyBody').html('<div class="alert alert-danger">SPK data not found.</div>');
        }
    }).fail(function() {
        $('#modalSpkReadOnlyBody').html('<div class="alert alert-danger">Failed to load SPK data.</div>');
    });
});

// Add Batalkan handler
$(document).on('click', '.batalkan-tindakan-btn', function() {
    const id = $(this).data('id');
    Swal.fire({
        title: 'Batalkan Tindakan?',
        text: 'Tindakan dan billing terkait akan dihapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, batalkan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: `/erm/tindakan/riwayat/${id}`,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Berhasil', response.message, 'success');
                        $('#historyTindakanTable').DataTable().ajax.reload();
                    } else {
                        Swal.fire('Gagal', 'Tidak dapat membatalkan tindakan.', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Gagal', 'Terjadi kesalahan server.', 'error');
                }
            });
        }
    });
});

// Handle click on Detail button
$(document).on('click', '.detail-sop-btn', function() {
    const tindakanId = $(this).data('id');
    $('#modalSopDetailLabel').text('SOP Tindakan');
    $('#sopTable tbody').html('<tr><td colspan="2" class="text-center">Loading...</td></tr>');
    $('#modalSopDetail').modal('show');
    $.get(`/erm/tindakan/${tindakanId}/sop-list`, function(response) {
        if (response.success) {
            $('#modalSopDetailLabel').text('Detail Kode Tindakan: ' + response.tindakan);
            let rows = '';
            if (response.kode_tindakans && response.kode_tindakans.length > 0) {
                response.kode_tindakans.forEach(function(item) {
                    let obatList = '-';
                    if (item.obats && item.obats.length > 0) {
                        obatList = '<ul class="mb-0 pl-3">';
                        item.obats.forEach(function(o) {
                            const jumlah = o.jumlah !== null ? (' x ' + o.jumlah) : '';
                            const dosis = o.dosis ? (' | ' + o.dosis + (o.satuan_dosis ? (' ' + o.satuan_dosis) : '')) : '';
                            obatList += `<li>${o.nama}${jumlah}${dosis}</li>`;
                        });
                        obatList += '</ul>';
                    }
                    rows += `<tr>
                        <td>${item.no}</td>
                        <td>${item.kode}</td>
                        <td>${item.nama}</td>
                        <td>${obatList}</td>
                    </tr>`;
                });
            } else {
                rows = '<tr><td colspan="4" class="text-center">Tidak ada kode tindakan</td></tr>';
            }
            $('#sopTable tbody').html(rows);
        } else {
            $('#sopTable tbody').html('<tr><td colspan="4" class="text-center">Gagal memuat data</td></tr>');
        }
    }).fail(function() {
        $('#sopTable tbody').html('<tr><td colspan="4" class="text-center">Gagal memuat data</td></tr>');
    });
});


    // Handler for detail button in riwayat tindakan datatable
    $(document).on('click', '.detail-riwayat-btn', function() {
        var riwayatId = $(this).data('id');
        $('#riwayatDetailContent').html('<div class="text-center py-4">Loading...</div>');
        $('#modalRiwayatDetail').modal('show');
        $.get(`/erm/riwayat-tindakan/${riwayatId}/detail`, function(response) {
            // response should contain kode tindakan and obat list
            $('#riwayatDetailContent').html(response.html);
        }).fail(function() {
            $('#riwayatDetailContent').html('<div class="alert alert-danger">Failed to load detail.</div>');
        });
    });

    });
</script>

@endsection
