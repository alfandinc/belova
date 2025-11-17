@extends('layouts.erm.app')
@section('title', 'ERM | E-Resep Farmasi')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection  

@section('content')

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item active">Farmasi</li>
                            <li class="breadcrumb-item">E-Resep</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    <div class="card">
        <div class="card-header bg-primary">
            <h4 class="card-title text-white">Daftar Resep Kunjungan Rawat Jalan
                <button id="btn-old-notifs" type="button" class="btn btn-light btn-sm float-right ml-2" title="Lihat Notifikasi Lama">
                    <span style="color:#e74c3c; font-size:14px;">&#10084;</span>
                </button>
            </h4>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="filter_tanggal_range">Filter Tanggal Kunjungan</label>
                    <input type="text" id="filter_tanggal_range" class="form-control" placeholder="Pilih Rentang Tanggal">
                    <input type="hidden" id="filter_tanggal_mulai">
                    <input type="hidden" id="filter_tanggal_selesai">
                </div>
                <div class="col-md-3">
                    <label for="filter_dokter">Filter Dokter</label>
                    <select id="filter_dokter" class="form-control select2">
                        <option value="">Semua Dokter</option>
                        @foreach($dokters as $dokter)
                            <option value="{{ $dokter->id }}">{{ $dokter->user->name ?? '-' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_klinik">Filter Klinik</label>
                    <select id="filter_klinik" class="form-control select2">
                        <option value="">Semua Klinik</option>
                        @foreach($kliniks as $klinik)
                            <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_status_resep">Status Resep</label>
                    <select id="filter_status_resep" class="form-control select2">
                        <option value="0" selected>Belum Dilayani</option>
                        <option value="1">Sudah Dilayani</option>
                    </select>
                </div>
            </div>
            <table class="table table-bordered w-100" id="rawatjalan-table">
                <thead>
                    <tr>
                        <th>No Resep</th> <!-- New column -->
                        <th>Tanggal Kunjungan</th>
                        <th>No RM</th>
                        <th>Nama Pasien</th>
                        <th>Nama Dokter</th> <!-- New -->
                        <th>Spesialisasi</th> <!-- New -->
                        <th>Metode Bayar</th>
                        <th>Asesmen Selesai</th> <!-- New column -->
                        <th>Resep</th>
                    </tr>
                </thead>
            </table>
            <!-- Modal: Old Notifications -->
            <div class="modal fade" id="modalOldNotifications" tabindex="-1" role="dialog" aria-labelledby="modalOldNotificationsLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalOldNotificationsLabel">Notifikasi Lama</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div id="old-notifs-loading" style="display:none; text-align:center; padding:20px;">
                                <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>
                            </div>
                            <div id="old-notifs-empty" style="display:none; text-align:center; color:#666;">Belum ada notifikasi lama.</div>
                            <ul class="list-group" id="old-notifs-list"></ul>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function () {
    // Initialize date range picker
    $('#filter_tanggal_range').daterangepicker({
        opens: 'left',
        autoApply: true,
        locale: {
            format: 'DD-MM-YYYY',
            separator: ' s/d ',
            applyLabel: 'Pilih',
            cancelLabel: 'Batal',
            fromLabel: 'Dari',
            toLabel: 'Sampai',
            customRangeLabel: 'Custom',
            weekLabel: 'M',
            daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            firstDay: 1
        },
        startDate: moment(),
        endDate: moment()
    }, function(start, end, label) {
        $('#filter_tanggal_mulai').val(start.format('YYYY-MM-DD'));
        $('#filter_tanggal_selesai').val(end.format('YYYY-MM-DD'));
        table.ajax.reload();
    });
    
    // Set default value tanggal ke hari ini
    $('#filter_tanggal_mulai').val(moment().format('YYYY-MM-DD'));
    $('#filter_tanggal_selesai').val(moment().format('YYYY-MM-DD'));
    
    $('.select2').select2({ width: '100%' });
    $('#filter_status_resep').val('0').trigger('change'); // set default to 0

    let table = $('#rawatjalan-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '{{ route("erm.eresepfarmasi.index") }}',
            data: function(d) {
                d.tanggal_mulai = $('#filter_tanggal_mulai').val();
                d.tanggal_selesai = $('#filter_tanggal_selesai').val();
                d.dokter_id = $('#filter_dokter').val();
                d.klinik_id = $('#filter_klinik').val();
                d.status_resep = $('#filter_status_resep').val(); // add status_resep
            }
        },
        // order: [[5, 'asc'], [0, 'asc']], // Tanggal ASC, Antrian ASC
        columns: [
            { data: 'no_resep', name: 'no_resep', searchable: true, orderable: false }, // Now searchable
            { data: 'tanggal_visitation', name: 'tanggal_visitation' },
            { data: 'no_rm', searchable: false, orderable: false },
            { data: 'nama_pasien', name: 'nama_pasien', searchable: true, orderable: false }, // Now searchable
            { data: 'nama_dokter', searchable: false, orderable: false }, // New
            { data: 'spesialisasi', searchable: false, orderable: false }, // New
            { data: 'metode_bayar', searchable: false, orderable: false },
            { data: 'asesmen_selesai', name: 'asesmen_selesai', searchable: false, orderable: false }, // New column
            { data: 'dokumen', searchable: false, orderable: false },
            { data: 'status_kunjungan', visible: false, searchable: false } // 🛠️ Sembunyikan
        ],
        columnDefs: [
        { targets: 0, width: "15%" },
        { targets: 1, width: "10%" },
        { targets: 3, width: "20%" },
        { targets: 4, width: "20%" },
        { targets: 7, width: "10%" },
    ],
    });

    // Event ganti filter
    $('#filter_dokter, #filter_klinik, #filter_status_resep').on('change', function () {
        table.ajax.reload();
    });

    // ambil no antrian otomatis
    $('#reschedule-dokter-id, #reschedule-tanggal-visitation').on('change', function() {
        let dokterId = $('#reschedule-dokter-id').val();
        let tanggal = $('#reschedule-tanggal-visitation').val();

        if (dokterId && tanggal) {
            $.get('{{ route("erm.rawatjalans.cekAntrian") }}', { dokter_id: dokterId, tanggal: tanggal }, function(res) {
                $('#reschedule-no-antrian').val(res.no_antrian);
            });
        }
    });

    // submit form reschedule
    $('#form-reschedule').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: '{{ route("erm.rawatjalans.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                $('#modalReschedule').modal('hide');
                $('#rawatjalan-table').DataTable().ajax.reload();
                alert(res.message);
            },
            error: function(xhr) {
                alert('Terjadi kesalahan!');
            }
        });
    });
});

// 🛠️ Fungsi openRescheduleModal dibuat di luar $(document).ready supaya global
function openRescheduleModal(visitationId, namaPasien, pasienId) {
    $('#modalReschedule').modal('show');
    $('#reschedule-visitation-id').val(visitationId);
    $('#reschedule-pasien-id').val(pasienId);
    $('#reschedule-nama-pasien').val(namaPasien);
}

// Notification polling moved to global partial (partials.farmasi-notif)

// URL to fetch old notifications and to mark them read
var oldNotifsUrl = '{{ route("erm.farmasi.notifications.old") }}';
var markReadBase = '{{ url("erm/farmasi/notifications") }}';
var csrfToken = '{{ csrf_token() }}';

// Open modal and load notifications when button is clicked
$(document).on('click', '#btn-old-notifs', function () {
    $('#modalOldNotifications').modal('show');
    loadOldNotifications();
});

function loadOldNotifications() {
    $('#old-notifs-list').empty();
    $('#old-notifs-empty').hide();
    $('#old-notifs-loading').show();

    $.get(oldNotifsUrl, function (res) {
        $('#old-notifs-loading').hide();

        var items = [];
        if (Array.isArray(res)) {
            items = res;
        } else if (res && Array.isArray(res.data)) {
            items = res.data;
        }

        if (!items || items.length === 0) {
            $('#old-notifs-empty').show();
            return;
        }

        items.forEach(function (n) {
            var message = n.message || n.title || n.text || JSON.stringify(n);
            var time = n.created_at || n.time || n.tanggal || '';

            var $li = $("<li class='list-group-item d-flex justify-content-between align-items-start'></li>");
            var left = '<div class="notif-content">';
            if (n.read) {
                left += '<div class="text-muted">' + escapeHtml(message) + '</div>';
            } else {
                left += '<div class="font-weight-bold">' + escapeHtml(message) + '</div>';
            }
            if (time) left += '<small class="text-muted">' + escapeHtml(time) + '</small>';
            left += '</div>';

            var right = '<div class="notif-actions">';
            if (!n.read) {
                right += '<button class="btn btn-sm btn-primary btn-mark-read" data-id="' + n.id + '">Tandai sudah dibaca</button>';
            } else {
                right += '<span class="badge badge-secondary">Sudah dibaca</span>';
            }
            right += '</div>';

            $li.html(left + right);
            $('#old-notifs-list').append($li);
        });
    }).fail(function () {
        $('#old-notifs-loading').hide();
        $('#old-notifs-empty').text('Gagal memuat notifikasi.').show();
    });
}

// Handle mark-as-read click
$(document).on('click', '.btn-mark-read', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var id = $btn.data('id');
    if (!id) return;

    $btn.prop('disabled', true).text('Memproses...');

    $.ajax({
        url: markReadBase + '/' + id + '/mark-read',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        success: function (res) {
            if (res && res.success) {
                // reload list to reflect change
                loadOldNotifications();
            } else {
                alert('Gagal menandai notifikasi.');
                $btn.prop('disabled', false).text('Tandai sudah dibaca');
            }
        },
        error: function () {
            alert('Gagal menandai notifikasi.');
            $btn.prop('disabled', false).text('Tandai sudah dibaca');
        }
    });
});

function escapeHtml(unsafe) {
    return String(unsafe)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

</script>


@endsection

