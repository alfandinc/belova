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
                                    <th>Paket Tindakan</th>
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
                <div class="card-header">
                    <h5 class="mb-0">Daftar Tindakan Dokter</h5>
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

<!-- SOP Detail Modal -->
<div class="modal fade" id="modalSopDetail" tabindex="-1" aria-labelledby="modalSopDetailLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalSopDetailLabel">SOP Tindakan</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered" id="sopTable">
          <thead>
            <tr>
              <th>No</th>
              <th>SOP</th>
            </tr>
          </thead>
          <tbody>
            <!-- SOP rows will be injected here -->
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
                                <button class="btn btn-warning btn-sm spk-btn mr-1" data-riwayat-id="${row.id}">SPK</button>
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
        
        // Show existing images if available
        if (beforePath) {
            $('#beforePreview').attr('src', `/storage/${beforePath}`).show();
        }
        
        if (afterPath) {
            $('#afterPreview').attr('src', `/storage/${afterPath}`).show();
        }
        
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
            $('#modalSopDetailLabel').text('SOP Tindakan: ' + response.tindakan);
            let rows = '';
            if (response.sop.length > 0) {
                response.sop.forEach(function(item) {
                    rows += `<tr><td>${item.no}</td><td>${item.nama_sop}</td></tr>`;
                });
            } else {
                rows = '<tr><td colspan="2" class="text-center">Tidak ada SOP</td></tr>';
            }
            $('#sopTable tbody').html(rows);
        } else {
            $('#sopTable tbody').html('<tr><td colspan="2" class="text-center">Gagal memuat SOP</td></tr>');
        }
    }).fail(function() {
        $('#sopTable tbody').html('<tr><td colspan="2" class="text-center">Gagal memuat SOP</td></tr>');
    });
});
    });
</script>

@endsection
