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
  function initializeSignaturePads() {
    const patientCanvas = document.getElementById('signatureCanvas');
    const witnessCanvas = document.getElementById('witnessSignatureCanvas');

    if (!patientCanvas || !witnessCanvas) return;

    const scale = window.devicePixelRatio || 1;

    function setupCanvas(canvas) {
        const parent = canvas.parentElement;
        const width = parent.clientWidth;
        const height = parent.clientHeight;

        canvas.width = width * scale;
        canvas.height = height * scale;

        const ctx = canvas.getContext('2d');
        ctx.scale(scale, scale);
    }

    setupCanvas(patientCanvas);
    setupCanvas(witnessCanvas);

    window.patientSignaturePad = new SignaturePad(patientCanvas);
    window.witnessSignaturePad = new SignaturePad(witnessCanvas);

    // Add clear buttons
    document.getElementById('clearSignature')?.addEventListener('click', function () {
        window.patientSignaturePad.clear();
    });

    document.getElementById('clearWitnessSignature')?.addEventListener('click', function () {
        window.witnessSignaturePad.clear();
    });
}

let tindakanData = []; // Declare tindakanData globally
// Call this function after the modal content is loaded
$(document).on('shown.bs.modal', '#modalInformConsent', function () {
    initializeSignaturePads();
});
    $(document).ready(function () {

      
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

        $(document).on('click', '.buat-tindakan', function () {
    const type = $(this).data('type');
    const id = $(this).data('id');
    const visitationId = @json($visitation->id);

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

                // Initialize signature pads after modal content is loaded
                setTimeout(initializeSignaturePads, 300);
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                console.error('AJAX Error:', textStatus, errorThrown);
                alert('Error loading inform consent form');
            });
    }
});

        $(document).on('click', '.buat-paket-tindakan', function () {
    tindakanData = JSON.parse($(this).attr('data-tindakan'));
    const visitationId = @json($visitation->id);

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
        <div class="step-navigation">
            <button class="btn btn-secondary prev-step">Previous</button>
            <button class="btn btn-primary next-step">Next</button>
            <button id="saveInformConsent" class="btn btn-success d-none">Simpan</button>
        </div>
    `);

    let currentStep = 1;

    function showStep(step) {
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
    }

    // Show the first step immediately
    showStep(currentStep);

    $('.next-step').click(function () {
        if (currentStep < tindakanData.length) {
            currentStep++;
            showStep(currentStep);
        }
    });

    $('.prev-step').click(function () {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    });

    tindakanData.forEach((tindakan, index) => {
        $.get(`/erm/tindakan/inform-consent/${tindakan.id}?visitation_id=${visitationId}`)
            .done(function (html) {
                $(`#informConsentStep${index + 1}`).html(html);
                initializeSignaturePads(); // Initialize signature pads for each step
            })
            .fail(function () {
                alert('Error loading inform consent form');
            });
    });

    $('#modalInformConsent').modal('show');
});

        $(document).on('click', '#saveInformConsent', function () {
    const form = $('#informConsentForm');

    // Ensure signature pads are initialized
    const patientSignaturePad = window.patientSignaturePad;
    const witnessSignaturePad = window.witnessSignaturePad;

    if (!patientSignaturePad || !witnessSignaturePad) {
        Swal.fire('Error', 'Signature pads are not initialized.', 'error');
        return;
    }

    // Validate signatures
    if (patientSignaturePad.isEmpty()) {
        Swal.fire('Error', 'Please provide a signature for the patient.', 'error');
        return;
    }

    if (witnessSignaturePad.isEmpty()) {
        Swal.fire('Error', 'Please provide a signature for the witness.', 'error');
        return;
    }

    // Capture signature data
    $('#signatureData').val(patientSignaturePad.toDataURL());
    $('#witnessSignatureData').val(witnessSignaturePad.toDataURL());

    // Show loading indicator
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
                    // Optionally reload the table or update the UI
                });
            } else {
                Swal.fire('Error', 'Failed to save Inform Consent. Please try again.', 'error');
            }
        },
        error: function (xhr, status, error) {
            console.error('Error:', xhr.responseJSON);
            Swal.fire('Error', 'Validation failed. Please check your input.', 'error');
        },
        complete: function () {
            // Close the loading indicator
            Swal.close();
        }
    });
});

    });
</script>

@endsection
