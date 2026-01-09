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
.blink-new {
        display: inline-block;
        -webkit-animation: blinker 1s linear infinite;
        animation: blinker 1s linear infinite;
}
@-webkit-keyframes blinker {
    50% { opacity: 0; }
}
@keyframes blinker {
    50% { opacity: 0; }
}
/* Price block layout inside Harga column */
.price-block { width: 100%; }
.price-block .row { display: flex; justify-content: space-between; padding: 2px 8px; border-top: 1px solid #e9ecef; }
.price-block .row:first-child { border-top: none; }
.price-block .label { color: #6c757d; }
.price-block .value { font-weight: 600; display: flex; align-items: center; justify-content: flex-end; }
/* Buttons take full column width and stack */
.price-block .value .btn { display: block; width: 100%; margin: 0; padding: .35rem .5rem; line-height: 1; }
.price-block .row + .row .value { margin-top: 6px; }
/* Custom tindakan modal styles removed (feature deleted) */
/* Thicker separators for tindakan rows to improve readability */
#tindakanTable.table-bordered tbody td {
    border-bottom: 1px solid #e9ecef;
    vertical-align: middle;
}
#tindakanTable.table-bordered tbody tr:last-child td {
    border-bottom-width: 1px;
}
/* Make tindakan name link match price font and appearance */
.tindakan-name-link {
    color: inherit;
    text-decoration: none;
    font-weight: 600;
    font-family: inherit;
}
.tindakan-name-link strong { font-weight: 600; }
.tindakan-name-link:hover { text-decoration: underline; }
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
        <!-- Left: Daftar Tindakan Dokter -->
        <div class="col-lg-6 col-md-12 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center">
                    <h5 class="mb-0 text-uppercase font-weight-bold">DAFTAR TINDAKAN</h5>
                    <div class="ml-auto">
                        <!-- Create Custom Tindakan removed -->
                    </div>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table id="tindakanTable" class="table table-bordered w-100">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>List Harga</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Riwayat Tindakan Pasien -->
        <div class="col-lg-6 col-md-12 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center">
                    <h5 class="mb-0 text-uppercase font-weight-bold">RIWAYAT TINDAKAN</h5>
                    <div class="ml-auto">
                        <a href="/erm/tindakan/history/{{ $visitation->id }}/print-detail" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-print mr-1"></i>Print Detail
                        </a>
                    </div>
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
    // Load Qrious via CDN for client-side QR generation
    (function loadQrious(){
        if (window.QRious) return;
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js';
        s.async = false;
        document.head.appendChild(s);
    })();

    // Helper: generate QR data URI in browser (returns base64 data URI)
    function generateQrDataUriBrowser(text, size = 200) {
        try {
            if (!window.QRious) return null;
            var qr = new QRious({
                value: text || '',
                size: size,
                level: 'H'
            });
            return qr.toDataURL();
        } catch (e) {
            console.error('QR generation failed', e);
            return null;
        }
    }
    $(document).ready(function () {
        $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
        let tindakanData = [];
        let currentStep = 1;
        const spesialisasiId = @json($spesialisasiId); 
        const currentVisitationId = @json($visitation->id);
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
                { data: 'nama', name: 'nama', render: function(data, type, row) {
                        var rawName = data ? data : '';
                        var displayName = '<strong>' + rawName + '</strong>';
                        var badge = '';
                        if (row.current) {
                            badge = ' <span class="badge badge-success blink-new ml-2">Current visit</span>';
                        }
                        // Show red blinking NEW badge if tindakan created within last 30 days
                        try {
                            var createdAt = row.created_at || row.createdAt || row.createdAtRaw || null;
                            if (createdAt) {
                                var createdDate = new Date(createdAt);
                                if (!isNaN(createdDate.getTime())) {
                                    var now = new Date();
                                    var diffMs = now - createdDate;
                                    var days = diffMs / (1000 * 60 * 60 * 24);
                                    if (days <= 30) {
                                        badge += ' <span class="badge badge-danger blink-new ml-2">NEW</span>';
                                    }
                                }
                            }
                        } catch (e) {
                            // ignore parse errors
                        }
                        return `<a href="#" class="tindakan-name-link" data-id="${row.id}">${displayName}${badge}</a><div class="multi-usage small text-muted mt-1" data-tid="${row.id}"></div>`;
                    }
                },
                { 
                    data: 'harga', 
                    name: 'harga',
                    render: function (data, type, row) {
                        var harga = data ? formatRupiah(data) : '-';
                        var diskon = row.harga_diskon ? formatRupiah(row.harga_diskon) : '-';
                        var harga3 = row.harga_3_kali ? formatRupiah(row.harga_3_kali) : '-';
                        var firstRowValue = harga;
                        if (row.harga_diskon && row.harga_diskon !== null && row.harga_diskon !== '') {
                            if (row.diskon_active) {
                                firstRowValue = '<span class="text-muted"><s>' + harga + '</s></span> <span class="ml-2">' + diskon + '</span>';
                            } else {
                                firstRowValue = harga + ' <span class="ml-2">' + diskon + '</span>';
                            }
                        }

                        var out = '<div class="price-block">';
                        out += '<div class="row"><div class="label">Normal</div><div class="value">' + firstRowValue + '</div></div>';
                        // only show Harga 3x Visit when harga_3_kali has a value
                        if (typeof row.harga_3_kali !== 'undefined' && row.harga_3_kali !== null && String(row.harga_3_kali).trim() !== '') {
                            out += '<div class="row"><div class="label">3x Visit</div><div class="value">' + harga3 + '</div></div>';
                        }
                        out += '</div>';
                        return out;
                    }
                },
                { 
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                            // Build buttons inside a price-like block so rows align with List Harga
                            let out = '<div class="price-block">';
                            // Normal row: label and button aligned to the right
                            out += '<div class="row"><div class="label"></div><div class="value">';
                            out += `<button class="btn btn-success btn-sm buat-tindakan" title="Buat Tindakan (Normal)" data-id="${row.id}" data-type="tindakan" data-harga-type="normal"><i class="fas fa-plus mr-1"></i>Normal</button>`;
                            out += '</div></div>';
                            // 3x Visit row if available
                            if (typeof row.harga_3_kali !== 'undefined' && row.harga_3_kali !== null && String(row.harga_3_kali).trim() !== '') {
                                out += '<div class="row"><div class="label"></div><div class="value">';
                                out += `<button class="btn btn-primary btn-sm buat-tindakan" title="Buat Tindakan (3x Visit)" data-id="${row.id}" data-type="tindakan" data-harga-type="3x"><i class="fas fa-plus mr-1"></i>3x Visit</button>`;
                                out += '</div></div>';
                            }
                            out += '</div>';
                            return out;
                        }
                },
            ],
        });

        // After table draw, fetch multi-visit usage for visible tindakan rows and render under name
        $('#tindakanTable').on('draw.dt', function() {
                $('#tindakanTable').find('.multi-usage').each(function() {
                const $el = $(this);
                const tid = $el.data('tid');
                if (!tid) return;
                // Avoid refetch if already populated
                if ($el.data('loaded')) return;
                $.get(`/erm/tindakan/${tid}/multi-visit-status`, { visitation_id: @json($visitation->id) })
                    .done(function(res) {
                        if (res && res.success && res.used !== null && res.total !== null && parseInt(res.used) < parseInt(res.total)) {
                            $el.html(`<span class="badge badge-info">${res.used}/${res.total}</span>`);
                        } else {
                            $el.html('');
                        }
                        $el.data('loaded', true);
                    }).fail(function() {
                        $el.html('');
                        $el.data('loaded', true);
                    });
            });
        });

                // Create Custom Tindakan feature removed: modal, UI and handlers deleted

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
                { data: 'tanggal', name: 'tanggal', visible: false },
                { data: 'tindakan', name: 'tindakan', render: function(data, type, row) {
                        var name = data ? data : '';
                        if (row.current) {
                            name += ' <span class="badge badge-success blink-new ml-2">Current visit</span>';
                        }
                        var dateLine = row.tanggal ? '<div class="text-muted small mt-1">' + row.tanggal + '</div>' : '';
                        return '<div>' + name + dateLine + '</div>';
                    }
                },
                { data: 'dokter', name: 'dokter', render: function(data, type, row) {
                        var spec = row.spesialisasi ? row.spesialisasi : '';
                        var specHtml = '';
                        if (spec) {
                            if (typeof spec === 'string' && spec.indexOf('<') !== -1) {
                                specHtml = spec;
                            } else {
                                // choose a badge class based on specialization
                                function hashCode(str) {
                                    var h = 0;
                                    for (var i = 0; i < str.length; i++) {
                                        h = ((h << 5) - h) + str.charCodeAt(i);
                                        h |= 0;
                                    }
                                    return h;
                                }
                                var specClassMap = {
                                    'Gigi': 'badge-info',
                                    'Estetika': 'badge-warning',
                                    'Umum': 'badge-secondary',
                                    'Kecantikan': 'badge-pink',
                                    'Anestesi': 'badge-dark'
                                };
                                var cls = specClassMap[spec] || null;
                                if (!cls) {
                                    var palette = ['badge-primary','badge-success','badge-danger','badge-dark','badge-warning','badge-info'];
                                    cls = palette[Math.abs(hashCode(spec)) % palette.length];
                                }
                                specHtml = '<span class="badge ' + cls + '">' + spec + '</span>';
                            }
                        }
                        var out = '<div>' + (data ? data : '') + '</div>';
                        if (specHtml) out += '<div class="mt-1">' + specHtml + '</div>';
                        return out;
                    }
                },
                { 
                    data: 'dokumen', 
                    name: 'dokumen', 
                    orderable: false, 
                    searchable: false,
                    render: function(data, type, row) {
                        // Ensure Inform Consent link uses /storage/ prefix
                        // Render buttons as a grouped button set for consistent UI
                        let group = '<div class="btn-group" role="group">';
                            if (row.inform_consent) {
                            const fileUrl = `/storage/${row.inform_consent.file_path}`;
                            const hasBefore = row.inform_consent.before_image_path && row.inform_consent.before_image_path.trim() !== '';
                            const hasAfter = row.inform_consent.after_image_path && row.inform_consent.after_image_path.trim() !== '';
                            let fotoBtnText, fotoBtnClass, fotoBtnIcon;
                            if (hasBefore && hasAfter) {
                                fotoBtnText = 'Lihat Foto';
                                fotoBtnClass = 'btn-primary';
                                fotoBtnIcon = '<i class="fas fa-eye"></i>';
                            } else {
                                fotoBtnText = 'Upload Foto';
                                fotoBtnClass = 'btn-success';
                                fotoBtnIcon = '<i class="fas fa-upload"></i>';
                            }
                            group += `<a href="${fileUrl}" target="_blank" class="btn btn-info btn-sm" title="Inform Consent"><i class="fas fa-file-alt"></i></a>`;
                            group += `<button class="btn ${fotoBtnClass} btn-sm foto-hasil-btn" title="${fotoBtnText}" data-id="${row.inform_consent.id}" data-before="${row.inform_consent.before_image_path || ''}" data-after="${row.inform_consent.after_image_path || ''}">${fotoBtnIcon}</button>`;
                            // Per-row Print Detail (by visitation)
                            if (row.visitation_id) {
                                group += `<a href="/erm/tindakan/history/${row.visitation_id}/print-detail" target="_blank" class="btn btn-outline-primary btn-sm" title="Print Detail"><i class="fas fa-print"></i></a>`;
                            }
                            // Detail button removed as requested
                        } else {
                            group += `<button class="btn btn-secondary btn-sm" disabled title="Inform Consent"><i class="fas fa-file-alt"></i></button>`;
                            group += `<button class="btn btn-secondary btn-sm" disabled title="Upload Foto"><i class="fas fa-upload"></i></button>`;
                            // Print Detail still available per visitation even if consent absent
                            if (row.visitation_id) {
                                group += `<a href="/erm/tindakan/history/${row.visitation_id}/print-detail" target="_blank" class="btn btn-outline-primary btn-sm" title="Print Detail"><i class="fas fa-print"></i></a>`;
                            }
                            // disabled Detail button removed
                        }
                        // Batalkan as part of the group (only for current visitation)
                        if (row.current) {
                            group += `<button class="btn btn-danger btn-sm batalkan-tindakan-btn" title="Batalkan" data-id="${row.id}"><i class="fas fa-ban"></i></button>`;
                        }
                        group += '</div>';
                        return group;
                    }
                },
                    // Hidden column for raw date sorting
                    { data: 'tanggal_raw', name: 'tanggal_raw', visible: false },
                ],
                order: [[4, 'desc']] // Sort by hidden raw date column (updated index after moving status)
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
            // Capture preferred harga type (normal / 3x) from clicked button
            window.preferredHargaType = $(this).data('harga-type') || $(this).data('hargaType') || null;
            const type = $(this).data('type');
            const id = $(this).data('id');
            const visitationId = @json($visitation->id);
            
            // Reset signature pads dan tindakan data
            signaturePads = {};
            tindakanData = [];

            if (type === 'tindakan') {
                // Check if tindakan already exists in this visitation
                $.get(`/erm/tindakan/${id}/exists-in-visitation`, { visitation_id: visitationId })
                    .done(function(check) {
                        if (check && check.success && check.exists) {
                            Swal.fire({
                                title: 'Duplicate Tindakan',
                                text: 'Tindakan ini sudah ditambahkan pada kunjungan saat ini.',
                                icon: 'warning'
                            });
                            return;
                        }
                        // Not exists -> proceed to load inform consent form
                        $.get(`/erm/tindakan/inform-consent/${id}?visitation_id=${visitationId}`)
                            .done(function (html) {
                            $('#modalInformConsentBody').html(html);
                            // Inject price selector UI into the loaded inform consent form (if present)
                            (function injectPriceSelector(tid) {
                                $.get(`/erm/tindakan/${tid}/prices`).done(function(res) {
                                    if (!res.success) return;
                                    const harga = res.harga || 0;
                                    const harga3 = res.harga_3_kali || null;
                                    const formattedHarga = new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR'}).format(harga);
                                    const formattedHarga3 = (harga3 !== null && harga3 !== '') ? new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR'}).format(harga3) : '';
                                    const $form = $('#modalInformConsentBody').find('#informConsentForm');
                                    if ($form.length) {
                                        const preferred = window.preferredHargaType || 'normal';
                                        const checkedNormal = preferred === 'normal' ? 'checked' : '';
                                        const checked3x = preferred === '3x' ? 'checked' : '';
                                        let html = `<div class="form-group mb-3"><label class="font-weight-bold">Pilih Jenis Harga</label><div>`;
                                        html += `<div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="harga_type" id="harga_normal" value="normal" ${checkedNormal}><label class="form-check-label" for="harga_normal">Normal - ${formattedHarga}</label></div>`;
                                        if (formattedHarga3) {
                                            html += `<div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="harga_type" id="harga_3x" value="3x" ${checked3x}><label class="form-check-label" for="harga_3x">3x Visit - ${formattedHarga3}</label></div>`;
                                        }
                                        html += '</div></div>';
                                        // Insert at the top of form body
                                        $form.prepend(html);
                                        // Clear the preferred flag after applying
                                        window.preferredHargaType = null;
                                    }
                                }).fail(function(){/* ignore */});
                            })(id);
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
                    })
                    .fail(function() {
                        // If check endpoint failed, be conservative and allow loading (or optionally block).
                        $.get(`/erm/tindakan/inform-consent/${id}?visitation_id=${visitationId}`)
                            .done(function (html) {
                                $('#modalInformConsentBody').html(html);
                                // existing injection and show logic continues below
                                (function injectPriceSelector(tid) {
                                    $.get(`/erm/tindakan/${tid}/prices`).done(function(res) {
                                        if (!res.success) return;
                                        const harga = res.harga || 0;
                                        const harga3 = res.harga_3_kali || null;
                                        const formattedHarga = new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR'}).format(harga);
                                        const formattedHarga3 = (harga3 !== null && harga3 !== '') ? new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR'}).format(harga3) : '';
                                        const $form = $('#modalInformConsentBody').find('#informConsentForm');
                                        if ($form.length) {
                                            const preferred = window.preferredHargaType || 'normal';
                                            const checkedNormal = preferred === 'normal' ? 'checked' : '';
                                            const checked3x = preferred === '3x' ? 'checked' : '';
                                            let html = `<div class="form-group mb-3"><label class="font-weight-bold">Pilih Jenis Harga</label><div>`;
                                            html += `<div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="harga_type" id="harga_normal" value="normal" ${checkedNormal}><label class="form-check-label" for="harga_normal">Normal - ${formattedHarga}</label></div>`;
                                            if (formattedHarga3) {
                                                html += `<div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="harga_type" id="harga_3x" value="3x" ${checked3x}><label class="form-check-label" for="harga_3x">3x Visit - ${formattedHarga3}</label></div>`;
                                            }
                                            html += '</div></div>';
                                            // Insert at the top of form body
                                            $form.prepend(html);
                                            // Clear the preferred flag after applying
                                            window.preferredHargaType = null;
                                        }
                                    }).fail(function(){/* ignore */});
                                })(id);
                                $('#modalInformConsentBody').append(`
                                    <div class="text-center mt-4">
                                        <button id="saveInformConsent" class="btn btn-success">Simpan</button>
                                    </div>
                                `);
                                $('#modalInformConsent').modal('show');
                            })
                            .fail(function () {
                                console.error('AJAX Error: failed to load inform consent after fallback check');
                                alert('Error loading inform consent form');
                            });
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
                        // Inject price selector into each loaded step form
                        (function injectPriceSelectorStep(tid, container, stepIdx) {
                            $.get(`/erm/tindakan/${tid}/prices`).done(function(res) {
                                if (!res.success) return;
                                const harga = res.harga || 0;
                                const harga3 = res.harga_3_kali || null;
                                const formattedHarga = new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR'}).format(harga);
                                const formattedHarga3 = (harga3 !== null && harga3 !== '') ? new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR'}).format(harga3) : '';
                                const $form = $(container).find('#informConsentForm');
                                if ($form.length) {
                                    const preferred = window.preferredHargaType || 'normal';
                                    const checkedNormal = preferred === 'normal' ? 'checked' : '';
                                    const checked3x = preferred === '3x' ? 'checked' : '';
                                    let html2 = `<div class="form-group mb-3"><label class="font-weight-bold">Pilih Jenis Harga</label><div>`;
                                    html2 += `<div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="harga_type" id="harga_normal_step_${stepIdx}" value="normal" ${checkedNormal}><label class="form-check-label" for="harga_normal_step_${stepIdx}">Normal - ${formattedHarga}</label></div>`;
                                    if (formattedHarga3) {
                                        html2 += `<div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="harga_type" id="harga_3x_step_${stepIdx}" value="3x" ${checked3x}><label class="form-check-label" for="harga_3x_step_${stepIdx}">3x Visit - ${formattedHarga3}</label></div>`;
                                    }
                                    html2 += '</div></div>';
                                    $form.prepend(html2);
                                    // Clear preference for subsequent steps/modals
                                    window.preferredHargaType = null;
                                }
                            }).fail(function(){/* ignore */});
                        })(tindakan.id, `#informConsentStep${index + 1}`, index+1);
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
                // Before submission, generate dokter and perawat QR data URIs and add them as hidden fields
                const dokterNameForQr = form.find('input[name="dokter_name"]').val() || $('.dokter-nama').text() || '';
                const perawatNameForQr = '{{ optional(auth()->user())->name ?? '' }}';
                const dokterQrData = generateQrDataUriBrowser('Dokter: ' + dokterNameForQr) || '';
                const perawatQrData = generateQrDataUriBrowser('Perawat: ' + perawatNameForQr) || '';

                // Append hidden fields if not already present
                if (!form.find('input[name="dokter_qr"]').length) {
                    form.append('<input type="hidden" name="dokter_qr" />');
                }
                if (!form.find('input[name="perawat_qr"]').length) {
                    form.append('<input type="hidden" name="perawat_qr" />');
                }
                form.find('input[name="dokter_qr"]').val(dokterQrData);
                form.find('input[name="perawat_qr"]').val(perawatQrData);

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
                    // Generate QR for this step and append
                    const dokterNameForQr = form.find('input[name="dokter_name"]').val() || $('.dokter-nama').text() || '';
                    const perawatNameForQr = '{{ optional(auth()->user())->name ?? '' }}';
                    const dokterQrData = generateQrDataUriBrowser('Dokter: ' + dokterNameForQr) || '';
                    const perawatQrData = generateQrDataUriBrowser('Perawat: ' + perawatNameForQr) || '';
                    formData.append('dokter_qr', dokterQrData);
                    formData.append('perawat_qr', perawatQrData);

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

// Handle click on Detail button or tindakan name
$(document).on('click', '.detail-sop-btn, .tindakan-name-link', function(e) {
    e.preventDefault();
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
