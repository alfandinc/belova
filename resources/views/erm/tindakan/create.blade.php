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

<script>
    $(document).ready(function () {
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
            const paketId = $(this).data('id'); // Capture paket_id
            window.currentPaketId = paketId; // Store for later use
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
                        Swal.fire('Success', 'Inform Consent saved successfully!', 'success').then(() => {
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

                // Jika ada paket_id, tambahkan ke formData
                if (window.currentPaketId) {
                    formData.append('paket_id', window.currentPaketId);
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
                    Swal.fire('Success', 'All inform consents saved successfully!', 'success')
                        .then(() => {
                            $('#modalInformConsent').modal('hide');
                        });
                })
                .catch(errors => {
                    console.error('Errors:', errors);
                    Swal.fire('Error', 'Some inform consents could not be saved.', 'error');
                });
        }
    });
</script>

@endsection
