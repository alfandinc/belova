<div class="modal-header bg-primary text-white">
    <h5 class="modal-title" id="contractModalLabel"><i class="fas fa-file-signature mr-2"></i>Perpanjang Kontrak Karyawan</h5>
    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<div class="modal-body">
    <div class="employee-info mb-4 p-3 bg-light rounded">
        <div class="row">
            <div class="col-12 mb-2">
                <h5>{{ $employee->nama }}</h5>
            </div>
            <div class="col-md-4">
                <p class="mb-1"><strong>NIK:</strong> {{ $employee->nik }}</p>
                <p class="mb-1"><strong>No Induk:</strong> {{ $employee->no_induk }}</p>
            </div>
            <div class="col-md-4">
                <p class="mb-1"><strong>Posisi:</strong> {{ optional($employee->position)->name ?? '-' }}</p>
                <p class="mb-1"><strong>Divisi:</strong> {{ optional($employee->division)->name ?? '-' }}</p>
            </div>
            <div class="col-md-4">
                <p class="mb-1"><strong>Status:</strong> 
                    <span class="badge badge-{{ $employee->status == 'tetap' ? 'success' : ($employee->status == 'kontrak' ? 'warning' : 'danger') }}">
                        {{ ucfirst($employee->status) }}
                    </span>
                </p>
            </div>
        </div>
    </div>
    
    @if($lastContract)
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h6 class="font-weight-bold">Informasi Kontrak Terakhir:</h6>
                <div class="row">
                    <div class="col-md-4">
                        <p class="mb-1">Mulai: <strong>{{ $lastContract->start_date->format('d M Y') }}</strong></p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1">Berakhir: <strong>{{ $lastContract->end_date->format('d M Y') }}</strong></p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1">Durasi: <strong>{{ $lastContract->duration_months }} bulan</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <form id="contract-form-modal" enctype="multipart/form-data">
        @csrf
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="start_date_modal">Tanggal Mulai Kontrak <span class="text-danger">*</span></label>
                    <input type="date" id="start_date_modal" name="start_date" class="form-control" 
                        value="{{ $lastContract && $lastContract->end_date ? $lastContract->end_date->copy()->addDay()->format('Y-m-d') : now()->format('Y-m-d') }}" required>
                    <small class="form-text text-muted">Tanggal mulai kontrak baru</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="duration_months_modal">Durasi Kontrak (bulan) <span class="text-danger">*</span></label>
                    <input type="number" id="duration_months_modal" name="duration_months" class="form-control" 
                        value="{{ $lastContract ? $lastContract->duration_months : 12 }}" min="1" max="60" required>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="calculated_end_date_modal">Tanggal Berakhir Kontrak</label>
                    <input type="text" id="calculated_end_date_modal" class="form-control" readonly>
                    <small class="form-text text-muted">Tanggal berakhir akan dihitung otomatis berdasarkan durasi</small>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="contract_document_modal">Dokumen Kontrak</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="contract_document_modal" name="contract_document">
                        <label class="custom-file-label" for="contract_document_modal">Pilih file</label>
                    </div>
                    <small class="form-text text-muted">Dokumen kontrak dalam format PDF (Maks. 2MB)</small>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="notes_modal">Catatan</label>
                    <textarea id="notes_modal" name="notes" class="form-control" rows="3"></textarea>
                </div>
            </div>
        </div>
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-success px-4" id="save-contract-btn">
        <i class="fas fa-save mr-2"></i>Simpan Kontrak Baru
    </button>
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
</div>

<script>
$(function() {
    // Custom file input handling
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
    });
    
    // Calculate and display end date based on start date and duration
    function calculateEndDateModal() {
        var startDate = $('#start_date_modal').val();
        var duration = $('#duration_months_modal').val();
        
        if (startDate && duration) {
            var start = new Date(startDate);
            // Add months to the start date
            var end = new Date(start);
            end.setMonth(end.getMonth() + parseInt(duration));
            
            // Format end date for display
            var options = { day: 'numeric', month: 'short', year: 'numeric' };
            var formattedEndDate = end.toLocaleDateString('id-ID', options);
            
            $('#calculated_end_date_modal').val(formattedEndDate);
        }
    }
    
    // Calculate end date when duration or start date changes
    $('#duration_months_modal, #start_date_modal').on('change', function() {
        calculateEndDateModal();
    });
    
    // Calculate initial end date
    calculateEndDateModal();
    
    // Handle save contract button click
    $('#save-contract-btn').on('click', function() {
        var formData = new FormData($('#contract-form-modal')[0]);
        
        $.ajax({
            url: '{{ route('hrd.employee.contracts.store', $employee->id) }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#save-contract-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
            },
            success: function(response) {
                if (response.success) {
                    $('#contractModal').modal('hide');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message || 'Kontrak berhasil diperpanjang',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(function() {
                        // Reload the page to show the updated contract list
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Terjadi kesalahan saat menyimpan data'
                    });
                }
            },
            error: function(xhr) {
                var errors = xhr.responseJSON.errors;
                var errorMessage = '';
                
                $.each(errors, function(key, value) {
                    errorMessage += value + '<br>';
                });
                
                Swal.fire({
                    icon: 'error',
                    title: 'Validasi Error',
                    html: errorMessage
                });
            },
            complete: function() {
                $('#save-contract-btn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Simpan Kontrak Baru');
            }
        });
    });
});
</script>
