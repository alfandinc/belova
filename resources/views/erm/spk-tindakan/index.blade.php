@extends('layouts.erm.app')

@section('title', 'SPK Tindakan')

@section('navbar')
    @include('layouts.erm.navbar-beautician')
@endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <!-- start page title -->
        <!-- Title and Filter Row -->
        <div class="row mb-3 align-items-center">
            <div class="col-md-12">
                <h4 class="page-title mb-0 font-size-18">SPK Tindakan</h4>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end">
                            <div>
                                <span class="font-weight-bold">Daftar SPK Tindakan</span>
                                <span class="text-muted ml-2">Kelola surat perintah kerja untuk tindakan medis</span>
                            </div>
                            <div class="d-flex flex-row align-items-center mt-3 mt-md-0">
                                <label for="filterKlinik" class="mr-2 font-weight-bold mb-0">Klinik:</label>
                                <select id="filterKlinik" class="form-control mr-3" style="max-width: 180px;">
                                    <option value="">Semua Klinik</option>
                                    @foreach(App\Models\ERM\Klinik::all() as $klinik)
                                        <option value="{{ $klinik->id }}" @if($klinik->id == 2) selected @endif>{{ $klinik->nama }}</option>
                                    @endforeach
                                </select>
                                <label for="filterTanggal" class="mr-2 font-weight-bold mb-0">Tanggal Tindakan:</label>
                                <input type="text" id="filterTanggal" class="form-control" style="max-width: 220px;" />
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="spk-table" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Visitation ID</th>
                                        <th>RM</th>
                                        <th>Pasien</th>
                                        <th>Dokter</th>
                                        <th>Tindakan</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SPK Items Modal -->
<div class="modal fade" id="spkItemsModal" tabindex="-1" role="dialog" aria-labelledby="spkItemsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="spkItemsModalLabel">Detail SPK Tindakan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="spkItemsModalContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="saveSpkItems()">Simpan</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
    <!-- DataTables JS -->
    <script src="{{ asset('dastone/plugins/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/select2/js/select2.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Set default date range to today
            var today = moment().format('YYYY-MM-DD');
            $('#filterTanggal').val(today + ' - ' + today);

            // Initialize daterangepicker
            $('#filterTanggal').daterangepicker({
                locale: {
                    format: 'YYYY-MM-DD',
                    separator: ' - ',
                    applyLabel: 'Terapkan',
                    cancelLabel: 'Batal',
                    fromLabel: 'Dari',
                    toLabel: 'Sampai',
                    customRangeLabel: 'Custom',
                    daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                    monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                    firstDay: 1
                },
                startDate: today,
                endDate: today,
                autoUpdateInput: true,
                opens: 'left'
            });

            // Initialize DataTable
            var spkTable = $('#spk-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('erm.spktindakan.index') }}",
                    data: function(d) {
                        var tanggal = $('#filterTanggal').val().split(' - ');
                        d.tanggal_start = tanggal[0];
                        d.tanggal_end = tanggal[1];
                        d.klinik_id = $('#filterKlinik').val();
                    }
                },
                columns: [
                    { data: 'visitation_id', name: 'visitation_id' },
                    { data: 'rm', name: 'rm', orderable: false },
                    { data: 'pasien_nama', name: 'pasien_nama', orderable: false },
                    { data: 'dokter_nama', name: 'dokter_nama', orderable: false },
                    { data: 'tindakan_nama', name: 'tindakan_nama', orderable: false },
                    { data: 'tanggal_tindakan', name: 'tanggal_tindakan' },
                    { data: 'status_badge', name: 'status', orderable: false, searchable: false, render: function(data, type, row) {
                        // Normalize status label: replace 'pending' with localized label
                        try {
                            // If server already sent HTML badge, replace inside it
                            if (typeof data === 'string') {
                                return data.replace(/pending/gi, 'Belum Dikerjakan');
                            }
                        } catch (e) {
                            console.error('Error rendering status_badge', e);
                        }
                        return data;
                    } },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[0, 'desc']],
                pageLength: 25,
                responsive: true,
                language: {
                    processing: "Memproses...",
                    loadingRecords: "Memuat...",
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ entri",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                    infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
                    infoFiltered: "(disaring dari _MAX_ entri keseluruhan)",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Selanjutnya",
                        previous: "Sebelumnya"
                    }
                }
            });

            // Reload table when date range or klinik changes
            $('#filterTanggal').on('apply.daterangepicker change', function() {
                spkTable.ajax.reload();
            });
            $('#filterKlinik').on('change', function() {
                spkTable.ajax.reload();
            });
        });

        function showSpkItems(spkIds) {
            // Handle both single ID and array of IDs
            const idsString = Array.isArray(spkIds) ? spkIds.join(',') : spkIds;
            
            // Show loading in modal
            $('#spkItemsModalContent').html(`
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat data SPK...</p>
                </div>
            `);
            
            $('#spkItemsModal').modal('show');
            
            // Load SPK items via AJAX
            $.get(`{{ url('/erm/spktindakan') }}/${idsString}/items`)
                .done(function(data) {
                    $('#spkItemsModalContent').html(data);
                    
                    // Initialize Select2 for dropdowns in modal
                    $('.select2-modal').select2({
                        dropdownParent: $('#spkItemsModal'),
                        width: '100%'
                    });
                    
                    // Initialize navigation if multiple SPK
                    if (window.spkData && window.spkData.length > 1) {
                        updateSpkDisplay();
                    }
                })
                .fail(function() {
                    $('#spkItemsModalContent').html(`
                        <div class="alert alert-danger">
                            <i class="mdi mdi-alert"></i> Gagal memuat data SPK. Silakan coba lagi.
                        </div>
                    `);
                });
        }

        function saveSpkItems() {
            // Get all SPK forms in the modal
            const allSpkForms = $('.spk-form');
            
            if (!allSpkForms.length) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Tidak ada form SPK yang ditemukan',
                    icon: 'error'
                });
                return;
            }
            
            console.log(`Saving ${allSpkForms.length} SPK forms`);
            
            // Show loading
            const saveBtn = $('[onclick="saveSpkItems()"]');
            const originalText = saveBtn.text();
            saveBtn.prop('disabled', true).text('Menyimpan...');
            
            // Array to store all save promises
            const savePromises = [];
            const saveResults = [];
            
            // Process each SPK form
            allSpkForms.each(function() {
                const form = $(this);
                const spkId = form.data('spk-id');
                const formData = new FormData(form[0]);
                
                // Add time fields to form data
                const waktuMulai = $('#waktuMulai').val();
                const waktuSelesai = $('#waktuSelesai').val();
                
                if (waktuMulai) {
                    formData.append('waktu_mulai', waktuMulai);
                }
                if (waktuSelesai) {
                    formData.append('waktu_selesai', waktuSelesai);
                }
                
                console.log(`Processing SPK ID: ${spkId}`);
                console.log(`Waktu Mulai: ${waktuMulai}, Waktu Selesai: ${waktuSelesai}`);
                
                // Debug: log form data
                console.log(`Form data for SPK ${spkId}:`);
                for (let [key, value] of formData.entries()) {
                    console.log(`  ${key}: ${value}`);
                }
                
                const savePromise = $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                }).then(function(response) {
                    saveResults.push({
                        spkId: spkId,
                        success: true,
                        response: response
                    });
                    console.log(`SPK ${spkId} saved successfully`);
                    return response;
                }).catch(function(xhr) {
                    saveResults.push({
                        spkId: spkId,
                        success: false,
                        error: xhr
                    });
                    console.error(`SPK ${spkId} save failed:`, xhr);
                    throw xhr;
                });
                
                savePromises.push(savePromise);
            });
            
            // Wait for all saves to complete
            Promise.allSettled(savePromises)
                .then(function(results) {
                    const successCount = saveResults.filter(r => r.success).length;
                    const failedCount = saveResults.filter(r => !r.success).length;
                    
                    console.log(`Save results: ${successCount} success, ${failedCount} failed`);
                    
                    if (failedCount === 0) {
                        // All saves successful
                        let message = `Berhasil menyimpan ${successCount} SPK tindakan`;
                        
                        // Check if any had status updates
                        const statusUpdates = saveResults
                            .filter(r => r.success && r.response.new_status)
                            .map(r => {
                                // Localize 'pending' to 'Belum Dikerjakan' and format
                                var s = (r.response.new_status || '').replace(/pending/gi, 'Belum Dikerjakan');
                                s = s.replace(/_/g, ' ').toUpperCase();
                                return `SPK ${r.spkId}: ${s}`;
                            });
                        
                        if (statusUpdates.length > 0) {
                            message += `\n\nStatus otomatis diubah:\n${statusUpdates.join('\n')}`;
                        }
                        
                        Swal.fire({
                            title: 'Berhasil!',
                            text: message,
                            icon: 'success',
                            timer: 4000,
                            showConfirmButton: false
                        });
                        
                        // Update status dropdowns and data
                        saveResults.forEach(function(result) {
                            if (result.success && result.response.new_status) {
                                // Update status dropdown
                                $('#spkStatus').val(result.response.new_status);
                                
                                // Update spkData array
                                if (window.spkData) {
                                    const spkIndex = window.spkData.findIndex(spk => spk.id == result.spkId);
                                    if (spkIndex >= 0) {
                                        window.spkData[spkIndex].status = result.response.new_status;
                                    }
                                }
                            }
                        });
                        
                    } else if (successCount > 0) {
                        // Partial success
                        Swal.fire({
                            title: 'Sebagian Berhasil!',
                            text: `${successCount} SPK berhasil disimpan, ${failedCount} SPK gagal`,
                            icon: 'warning',
                            timer: 4000,
                            showConfirmButton: false
                        });
                    } else {
                        // All failed
                        const firstError = saveResults.find(r => !r.success);
                        let errorMsg = 'Gagal menyimpan semua SPK';
                        
                        if (firstError && firstError.error.status === 422) {
                            const errors = firstError.error.responseJSON?.errors;
                            if (errors) {
                                errorMsg += ':\n';
                                Object.keys(errors).forEach(key => {
                                    errorMsg += `• ${errors[key][0]}\n`;
                                });
                            }
                        }
                        
                        Swal.fire({
                            title: 'Error!',
                            text: errorMsg,
                            icon: 'error'
                        });
                    }
                    
                    // Refresh the main table
                    $('#spk-table').DataTable().ajax.reload();
                })
                .finally(function() {
                    saveBtn.prop('disabled', false).text(originalText);
                });
        }

        function updateSpkStatus(spkId, status) {
            // Localize status for confirmation message (pending -> Belum Dikerjakan)
            var localizedStatus = (status || '').replace(/pending/gi, 'Belum Dikerjakan').replace(/_/g, ' ').toUpperCase();

            Swal.fire({
                title: 'Konfirmasi',
                text: `Apakah Anda yakin ingin mengubah status SPK menjadi "${localizedStatus}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Ubah!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `{{ url('/erm/spktindakan') }}/${spkId}/status`,
                        method: 'POST',
                        data: {
                            status: status,
                            _token: '{{ csrf_token() }}'
                        }
                    })
                    .done(function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            $('#spk-table').DataTable().ajax.reload();
                            $('#spkItemsModal').modal('hide');
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Gagal mengubah status',
                                icon: 'error'
                            });
                        }
                    })
                    .fail(function() {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat mengubah status',
                            icon: 'error'
                        });
                    });
                }
            });
        }
    </script>
@endsection
