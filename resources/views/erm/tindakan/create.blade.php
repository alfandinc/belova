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

<!-- Modal for Inform Consent -->
<div class="modal fade" id="modalInformConsent" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Inform Consent Tindakan</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="modalInformConsentBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button id="saveInformConsent" class="btn btn-success d-none">Simpan</button> <!-- Add Simpan button -->
      </div>
    </div>
  </div>
</div>

<!-- Add this new modal after existing modals -->
<div class="modal fade" id="modalFotoHasil" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Foto Hasil Tindakan</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="fotoHasilForm" enctype="multipart/form-data">
          @csrf
          <input type="hidden" id="informConsentId" name="inform_consent_id">
          
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="beforeImage">Foto Before</label>
                <input type="file" class="form-control" id="beforeImage" name="before_image" accept="image/*">
                <div class="mt-2">
                  <img id="beforePreview" style="max-width: 100%; max-height: 200px; display: none;">
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="afterImage">Foto After</label>
                <input type="file" class="form-control" id="afterImage" name="after_image" accept="image/*">
                <div class="mt-2">
                  <img id="afterPreview" style="max-width: 100%; max-height: 200px; display: none;">
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveFotoHasil">Upload</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal for SPK (Read-Only) -->
<div class="modal fade" id="modalSpk" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">SPK & CUCI TANGAN</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="spkForm">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Nama Pasien</label>
                <input type="text" class="form-control" id="spkNamaPasien" readonly>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>No RM</label>
                <input type="text" class="form-control" id="spkNoRm" readonly>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Tanggal Tindakan</label>
                <input type="date" class="form-control" id="spkTanggalTindakan" name="tanggal_tindakan" readonly>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Nama Tindakan</label>
                <input type="text" class="form-control" id="spkNamaTindakan" readonly>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Dokter Penanggung Jawab</label>
                <input type="text" class="form-control" id="spkDokterPJ" readonly>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Harga</label>
                <input type="text" class="form-control" id="spkHarga" readonly>
              </div>
            </div>
          </div>
          
          <div class="table-responsive mt-4">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th style="width: 5%">NO</th>
                  <th style="width: 15%">TINDAKAN</th>
                  <th style="width: 12%">PJ</th>
                  <th style="width: 6%">SBK</th>
                  <th style="width: 6%">SBA</th>
                  <th style="width: 6%">SDC</th>
                  <th style="width: 6%">SDK</th>
                  <th style="width: 6%">SDL</th>
                  <th style="width: 8%">MULAI</th>
                  <th style="width: 8%">SELESAI</th>
                  <th style="width: 22%">NOTES</th>
                </tr>
              </thead>
              <tbody id="spkTableBody">
                <!-- Will be populated dynamically, all fields should be disabled/readonly or plain text -->
              </tbody>
            </table>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


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
        <div class="col-lg-6 col-md-12 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Daftar Tindakan</h5>
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
        <div class="col-lg-6 col-md-12 mb-3">
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
                    data: 'action', 
                    name: 'action', 
                    orderable: false, 
                    searchable: false,
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
                { data: 'tanggal', name: 'tanggal' },
                { data: 'tindakan', name: 'tindakan' },
                { data: 'paket', name: 'paket' },
                { data: 'dokter', name: 'dokter' },
                { data: 'spesialisasi', name: 'spesialisasi' },
                { data: 'status', name: 'status' },
                { 
                    data: 'dokumen', 
                    name: 'dokumen', 
                    orderable: false, 
                    searchable: false 
                }
            ],
            order: [[0, 'desc']] // Sort by date descending
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
            const tindakanId = form.find('input[name="tindakan_id"]').val();
            const visitationId = form.find('input[name="visitation_id"]').val();

            // Validasi
            if (!signaturePads[1] || !signaturePads[1].patient || !signaturePads[1].witness) {
                Swal.fire('Error', 'Signature pads are not initialized.', 'error');
                return;
            }

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

            // Add billing data to the form
            form.append(`<input type="hidden" name="jumlah" value="${form.find('.harga-tindakan').data('harga') || 0}">`);
            form.append(`<input type="hidden" name="keterangan" value="Tindakan: ${form.find('.nama-tindakan').text()}">`);

            // Show loading
            Swal.fire({
                title: 'Saving...',
                text: 'Please wait while the data is being saved.',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
            });

            // Submit the form
            const formData = new FormData(form[0]);

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Success', 'Tindakan dan billing berhasil disimpan!', 'success')
                            .then(() => {
                                $('#modalInformConsent').modal('hide');
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
                        });
                })
                .catch(errors => {
                    console.error('Errors:', errors);
                    Swal.fire('Error', 'Some inform consents could not be saved.', 'error');
                });
        }

        // SPK Functionality
        $(document).on('click', '.spk-btn', function() {
            const informConsentId = $(this).data('id');
            
            // Reset form
            $('#spkForm')[0].reset();
            $('#spkTableBody').empty();
            $('#spkInformConsentId').val(informConsentId);
            
            // Load SPK data
            $.ajax({
                url: `/erm/tindakan/spk/${informConsentId}`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        // Fill form with inform consent data
                        $('#spkNamaPasien').val(data.inform_consent.visitation.pasien.nama);
                        $('#spkNoRm').val(data.inform_consent.visitation.pasien.id);
                        $('#spkNamaTindakan').val(data.inform_consent.tindakan.nama);
                        $('#spkDokterPJ').val(data.inform_consent.visitation.dokter.user.name);
                        $('#spkHarga').val(formatRupiah(data.inform_consent.tindakan.harga));
                        
                        // If SPK exists, fill with existing data
                        if (data.spk) {
                            console.log('SPK data found:', data.spk);
                            console.log('Tanggal tindakan:', data.spk.tanggal_tindakan);
                            // Format date for HTML input (YYYY-MM-DD)
                            let tanggalTindakan = data.spk.tanggal_tindakan;
                            if (tanggalTindakan) {
                                // Convert to YYYY-MM-DD format if needed
                                const date = new Date(tanggalTindakan);
                                tanggalTindakan = date.toISOString().split('T')[0];
                            }
                            $('#spkTanggalTindakan').val(tanggalTindakan);
                        } else {
                            console.log('No existing SPK data found');
                            // Default values
                            $('#spkTanggalTindakan').val(new Date().toISOString().split('T')[0]);
                        }
                        
                        // Populate SOP table
                        let tableHtml = '';
                        data.sop_list.forEach((sop, index) => {
                            const existingDetail = data.spk ? data.spk.details.find(d => d.sop_id == sop.id) : null;
                            
                            // Format time values for HTML time input (HH:MM)
                            let waktuMulai = '';
                            let waktuSelesai = '';
                            if (existingDetail) {
                                waktuMulai = existingDetail.waktu_mulai ? existingDetail.waktu_mulai.substring(0, 5) : '';
                                waktuSelesai = existingDetail.waktu_selesai ? existingDetail.waktu_selesai.substring(0, 5) : '';
                            }
                            
                            tableHtml += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${sop.nama_sop}</td>
                                    <td>${existingDetail && existingDetail.penanggung_jawab ? existingDetail.penanggung_jawab : '-'}</td>
                                    <td><input type="checkbox" ${existingDetail && existingDetail.sbk ? 'checked' : ''} disabled></td>
                                    <td><input type="checkbox" ${existingDetail && existingDetail.sba ? 'checked' : ''} disabled></td>
                                    <td><input type="checkbox" ${existingDetail && existingDetail.sdc ? 'checked' : ''} disabled></td>
                                    <td><input type="checkbox" ${existingDetail && existingDetail.sdk ? 'checked' : ''} disabled></td>
                                    <td><input type="checkbox" ${existingDetail && existingDetail.sdl ? 'checked' : ''} disabled></td>
                                    <td><input type="time" class="form-control" value="${waktuMulai}" readonly></td>
                                    <td><input type="time" class="form-control" value="${waktuSelesai}" readonly></td>
                                    <td><textarea class="form-control" rows="2" readonly>${existingDetail && existingDetail.notes ? existingDetail.notes : ''}</textarea></td>
                                </tr>
                            `;
                        });
                        
                        $('#spkTableBody').html(tableHtml);
                        
                        // Show modal
                        $('#modalSpk').modal('show');
                    }
                },
                error: function(xhr) {
                    console.error('Error loading SPK data:', xhr);
                    Swal.fire('Error', 'Failed to load SPK data', 'error');
                }
            });
        });
        
    });
</script>

@endsection
