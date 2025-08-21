@extends('layouts.erm.app')

@section('title', 'ERM | Daftar SPK')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Daftar SPK / Riwayat Tindakan</h3>
    </div>
    <div class="row mb-3 mt-3">
        <div class="col-md-3">
            <label for="filterTanggal">Tanggal</label>
            <input type="text" id="filterTanggal" class="form-control" autocomplete="off" />
        </div>
        <div class="col-md-3">
            <label for="filterDokter">Dokter</label>
            <select id="filterDokter" class="form-control select2">
                <option value="">Semua Dokter</option>
                @foreach(\App\Models\ERM\Dokter::with('user')->get() as $dokter)
                    <option value="{{ $dokter->id }}">{{ $dokter->user->name ?? '-' }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-body">
            <div class="table-responsive">
                <table id="spkRiwayatTable" class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jam Kunjungan</th>
                            <th>Pasien</th>
                            <th style="width: 30%;">Tindakan</th>
                            <th>Dokter</th>
                            <th style="width: 20%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via DataTables AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- SPK Modal -->
<div class="modal fade" id="spkModal" tabindex="-1" role="dialog" aria-labelledby="spkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="spkModalLabel">SPK & CUCI TANGAN</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="spkModalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
$(document).ready(function() {
    // Initialize select2 for dokter filter
    $('#filterDokter').select2({
        width: '100%',
        placeholder: 'Pilih Dokter',
        allowClear: true
    });

    // Date range picker
    $('#filterTanggal').daterangepicker({
        locale: { format: 'YYYY-MM-DD' },
        startDate: moment().format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD'),
        autoUpdateInput: true,
        autoApply: true
    });

    var table = $('#spkRiwayatTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/erm/spk',
            data: function(d) {
                var tanggal = $('#filterTanggal').val().split(' - ');
                d.tanggal_start = tanggal[0];
                d.tanggal_end = tanggal[1] || tanggal[0];
                d.dokter_id = $('#filterDokter').val();
            }
        },
        columns: [
            { data: 'tanggal', name: 'tanggal' },
            { data: 'jam_kunjungan', name: 'jam_kunjungan' },
            { data: 'pasien', name: 'pasien' },
            { 
                data: 'tindakan', 
                name: 'tindakan',
                render: function(data, type, row) {
                    if (type === 'display') {
                        return data;
                    }
                    // For sorting/filtering, create a plain text version
                    return $('<div>').html(data).text();
                }
            },
            { data: 'dokter', name: 'dokter' },
            { 
                data: 'aksi', 
                name: 'aksi', 
                orderable: false, 
                searchable: false
            },
        ],
        rowCallback: function(row, data) {
            // Remove previous color classes
            $(row).removeClass('table-success table-warning');
            if (data.spk_status_color === 'green') {
                $(row).addClass('table-success');
            } else if (data.spk_status_color === 'yellow') {
                $(row).addClass('table-warning');
            }
        },
        order: [[0, 'desc'], [1, 'desc']]
    });

    $('#filterTanggal, #filterDokter').on('change', function() {
        table.ajax.reload();
    });

    // Handle SPK Modal
    $(document).on('click', '.open-spk-modal', function(e) {
        e.preventDefault();
        const visitationId = $(this).data('visitation-id');
        const currentIndex = $(this).data('current-index') || 0;
        
        // Show modal immediately with loading state
        $('#spkModal').modal('show');
        $('#spkModalBody').html(`
            <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <div class="mt-2">Memuat data SPK...</div>
                </div>
            </div>
        `);
        
        // Load modal content
        $.get('/erm/spk/modal', {
            visitation_id: visitationId,
            index: currentIndex
        })
        .done(function(response) {
            $('#spkModalBody').html(response);
            // Initialize any necessary plugins in the modal
            initializeModalPlugins();
        })
        .fail(function() {
            $('#spkModalBody').html('<div class="alert alert-danger">Gagal memuat data SPK. Silakan coba lagi.</div>');
        });
    });

    // Handle modal close - clean up
    $('#spkModal').on('hidden.bs.modal', function () {
        // Clear modal content completely
        $('#spkModalBody').empty();
        // Destroy any select2 instances to prevent conflicts
        $('#spkModal .select2-spk').each(function() {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
        });
    });

    function initializeModalPlugins() {
        // Initialize select2 for modal selects
        $('#spkModal .select2-spk').each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({
                    width: '100%',
                    dropdownParent: $('#spkModal')
                });
            }
        });
        
        // Trigger data loading if function exists (small delay to ensure modal content is rendered)
        if (typeof window.loadSpkModalData === 'function') {
            setTimeout(function() {
                window.loadSpkModalData();
            }, 50);
        }
    }
});
</script>
@endsection
