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
            <style>
            .dataTables_wrapper .status-pasien-icon {
                width: 20px;
                height: 20px;
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                vertical-align: middle;
                margin-right: 8px;
                border-radius: 3px;
                font-size: 11px;
                color: #fff;
            }
            .dataTables_wrapper .patient-meta { line-height: 1.05; }
            .dataTables_wrapper .patient-name { font-weight: 700; display:inline-block; }
            .dataTables_wrapper .patient-rm { font-weight: 400; color:#6c757d; display:inline-block; margin-left:6px; font-size: .95rem; }
            .dataTables_wrapper .patient-info { color: #6c757d; font-size: .85rem; margin-top: 3px; }
            </style>
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
                        <th>Detail resep</th> <!-- Date will be shown under this in the cell -->
                        <th class="d-none">Tanggal Kunjungan</th>
                        <th>Detail Pasien</th>
                        <th>Dokter</th>
                        <th>Metode Bayar</th>
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
            { 
                data: 'no_resep', 
                name: 'no_resep', 
                searchable: true, 
                orderable: true,
                render: function(data, type, row) {
                    var rawDate = row.tanggal_visitation_formatted || row.tanggal_visitation || '';
                    if (type === 'display') {
                        var displayDate = rawDate;
                        // Only try to parse ISO-like dates; if parsing fails, use server-provided string
                        var m = moment(rawDate, moment.ISO_8601, true);
                        if (m.isValid()) {
                            displayDate = m.format('D MMMM YYYY');
                        }
                        return '<div>'+ (data || '') +'<br><small class="text-muted"><strong>'+ displayDate +'</strong></small></div>';
                    }
                    if (type === 'sort') {
                        // For sorting, prefer a machine-friendly ISO date if available
                        return row.tanggal_visitation_iso || row.tanggal_visitation || data || '';
                    }
                    if (type === 'filter') {
                        return (data || '') + ' ' + (rawDate || '');
                    }
                    return data;
                }
            },
            { data: 'tanggal_visitation', name: 'tanggal_visitation', visible: false, searchable: true, orderable: true },
            { 
                data: 'nama_pasien', 
                name: 'nama_pasien', 
                searchable: true, 
                orderable: false,
                render: function(data, type, row) {
                    if (type === 'display') {
                        var iconHtml = '';
                        var status = (row.status_pasien || '').toString().trim();
                        if (status === 'VIP') {
                            iconHtml += '<span class="status-pasien-icon" style="background-color:#FFD700;" title="VIP Member"><i class="fas fa-crown" style="font-size:11px;color:#fff;"></i></span>';
                        } else if (status === 'Familia') {
                            iconHtml += '<span class="status-pasien-icon" style="background-color:#32CD32;" title="Familia Member"><i class="fas fa-users" style="font-size:11px;color:#fff;"></i></span>';
                        } else if (status === 'Black Card') {
                            iconHtml += '<span class="status-pasien-icon" style="background-color:#2F2F2F;" title="Black Card Member"><i class="fas fa-credit-card" style="font-size:11px;color:#fff;"></i></span>';
                        }

                        var noRm = row.no_rm || '';
                        var umur = row.pasien_umur ? (row.pasien_umur + ' tahun') : '';
                        var alamat = row.pasien_alamat || '';
                        var info = '';
                        if (umur && alamat) info = umur + ' · ' + alamat;
                        else if (umur) info = umur;
                        else if (alamat) info = alamat;
                        var infoHtml = info ? '<small class="text-muted">' + info + '</small>' : '';
                        var rmHtml = noRm ? ' (' + noRm + ')' : '';
                        var nameHtml = '<strong>' + (data || '') + '</strong>';
                        // Build a two-row layout: top row has icon + name, second row is age/address aligned under the name
                        var topRow = '<div style="display:flex;align-items:flex-start;">' + iconHtml + '<div class="patient-meta" style="margin-left:8px;">' + '<div><span class="patient-name">' + nameHtml + '</span>' + '<span class="patient-rm">' + rmHtml + '</span></div>';
                        var infoRow = info ? '<div class="patient-info">' + infoHtml + '</div>' : '';
                        return '<div>' + topRow + infoRow + '</div></div>';
                    }
                    if (type === 'filter') {
                        return (data || '') + ' ' + (row.no_rm || '') + ' ' + (row.pasien_alamat || '');
                    }
                    return data;
                }
            },
            { 
                data: 'nama_dokter', 
                searchable: false, 
                orderable: false,
                render: function(data, type, row) {
                    if (type === 'display') {
                        var spec = row.spesialisasi || '';
                        var style = '';
                        switch ((spec || '').toString().trim()) {
                            case 'Penyakit Dalam': style = 'background-color:#007bff;color:#fff;'; break; // blue
                            case 'Saraf': style = 'background-color:#5bc0de;color:#fff;'; break; // light blue
                            case 'Estetika': style = 'background-color:#ff69b4;color:#fff;'; break; // pink
                            case 'Gigi': style = 'background-color:#fd7e14;color:#fff;'; break; // orange
                            case 'Anak': style = 'background-color:#28a745;color:#fff;'; break; // green
                            case 'Umum': style = 'background-color:#ffc107;color:#212529;'; break; // yellow (dark text)
                            default: style = 'background-color:#6c757d;color:#fff;';
                        }
                        var badge = spec ? '<span class="badge" style="'+ style +'">'+ spec +'</span>' : '';
                        return '<div>'+ (data || '') +'<br><small>'+ badge +'</small></div>';
                    }
                    if (type === 'filter') {
                        return (data || '') + ' ' + (row.spesialisasi || '');
                    }
                    return data;
                }
            },
            { 
                data: 'metode_bayar', 
                searchable: false, 
                orderable: false,
                render: function(data, type, row) {
                    if (type === 'display') {
                        var name = data || row.metode_bayar || '';
                        var cls = 'badge badge-primary';
                        if ((name || '').toString().trim() === 'Umum') cls = 'badge badge-success';
                        return '<span class="'+ cls +'">'+ name +'</span>';
                    }
                    if (type === 'filter') return data || row.metode_bayar || '';
                    return data;
                }
            },
            { 
                data: 'dokumen', 
                searchable: false, 
                orderable: false,
                render: function(data, type, row) {
                    if (type === 'display') {
                        var btn = data || row.dokumen || '';
                        var time = row.asesmen_selesai || '';
                        var timeHtml = '';
                        if (time && time !== '-') {
                            var displayTime = (time || '').toString().replace(':', '.');
                            timeHtml = '<div class="text-muted small mt-1">Asesmen selesai pada ' + displayTime + '</div>';
                        }
                        return '<div>'+ btn + timeHtml +'</div>';
                    }
                    if (type === 'filter') return (data || row.dokumen || '') + ' ' + (row.asesmen_selesai || '');
                    return data;
                }
            },
            { data: 'status_kunjungan', visible: false, searchable: false } // 🛠️ Sembunyikan
        ],
        columnDefs: [
            // Adjusted widths: No Resep, (hidden date), Nama Pasien, Nama Dokter, Metode Bayar, Resep
            { targets: 0, width: "15%", orderData: [1] },
            { targets: 1, width: "0%" },
            { targets: 2, width: "30%" },
            { targets: 3, width: "35%" },
            { targets: 4, width: "8%", className: 'text-center' },
            { targets: 5, width: "12%", className: 'text-center' },
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

