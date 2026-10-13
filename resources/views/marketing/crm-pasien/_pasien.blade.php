{{-- Tab "Pasien": patient list with last visit filters; Follow Up opens the shared follow up form --}}
<div class="card">
    <div class="card-body">
        <div class="crm-toolbar mb-3">
            <div class="crm-field">
                <label for="last-visit-filter">Kunjungan terakhir</label>
                <select id="last-visit-filter" class="form-control">
                    <option value="all">Semua</option>
                    <option value="gt1w">Lebih dari 1 minggu lalu</option>
                    <option value="gt1m">Lebih dari 1 bulan lalu</option>
                    <option value="gt3m">Lebih dari 3 bulan lalu</option>
                    <option value="gt6m">Lebih dari 6 bulan lalu</option>
                    <option value="gt1y">Lebih dari 1 tahun lalu</option>
                </select>
            </div>
            <div class="crm-field">
                <label for="last-visit-klinik-filter">Klinik kunjungan terakhir</label>
                <select id="last-visit-klinik-filter" class="form-control">
                    <option value="all">Semua klinik</option>
                    @foreach($kliniks as $klinik)
                        <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="crm-actions">
                <button type="button" class="btn btn-outline-secondary" id="pasienResetFilter"><i class="fas fa-undo mr-1"></i>Reset</button>
            </div>
        </div>
        <p class="text-muted small mb-2">Cari pasien lewat kotak <b>Search</b> (nama, No RM, atau No HP). Pasien yang lama tidak berkunjung bisa disaring lewat filter di atas.</p>

        <table id="pasien-table" class="table table-hover crm-table w-100">
            <thead>
                <tr>
                    <th>No RM</th>
                    <th>Pasien</th>
                    <th>Tanggal Lahir</th>
                    <th>No HP</th>
                    <th>Kunjungan Terakhir</th>
                    <th title="Sudah di-follow up dalam 7 hari terakhir">FU 7 hari</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@push('crm-modals')
<div class="modal fade" id="viewPasienModal" tabindex="-1" role="dialog" aria-labelledby="viewPasienModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewPasienModalLabel">Detail Pasien</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <table class="table table-sm table-bordered mb-0">
          <tbody id="viewPasienBody"></tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-success" id="viewPasienFollowUp"><i class="fas fa-phone-alt mr-1"></i>Follow Up</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="riwayatRMModal" tabindex="-1" role="dialog" aria-labelledby="riwayatRMModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="riwayatRMModalLabel">Riwayat RM</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="riwayatRMModalBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
@endpush

@push('scripts')
<script>
window.crmPasien.inits.pasien = function () {
    var crm = window.crmPasien;
    // Text fields of the row arrive HTML-escaped from the server

    function statusBadges(row) {
        var statusPasien = (row.status_pasien || '').toString().toLowerCase();
        var badges = [];
        if (statusPasien.includes('vip')) badges.push('<span class="badge badge-soft-warning badge-pill"><i class="fas fa-crown mr-1"></i>VIP</span>');
        else if (statusPasien.includes('familia')) badges.push('<span class="badge badge-soft-primary badge-pill"><i class="fas fa-users mr-1"></i>Familia</span>');
        else if (statusPasien.includes('black')) badges.push('<span class="badge badge-soft-dark badge-pill"><i class="fas fa-id-card mr-1"></i>Black Card</span>');
        else if (statusPasien.includes('red')) badges.push('<span class="badge badge-soft-danger badge-pill"><i class="fas fa-flag mr-1"></i>Red Flag</span>');

        if ((row.status_akses || '').toString().toLowerCase().includes('akses cepat')) {
            badges.push('<span class="badge badge-soft-primary badge-pill"><i class="fas fa-wheelchair mr-1"></i>Akses Cepat</span>');
        }
        if (row.is_birthday_today) {
            badges.push('<span class="badge badge-soft-pink badge-pill" style="background:rgba(232,62,140,.15);color:#e83e8c;"><i class="fas fa-birthday-cake mr-1"></i>Ulang tahun hari ini</span>');
        }
        // Complaints not yet Selesai / Ditolak in Keluhan Customer
        var open = parseInt(row.open_keluhan_count, 10) || 0;
        if (open) {
            badges.push('<a href="{{ route('marketing.catatan-keluhan.index') }}?pasien_id=' + encodeURIComponent(row.id) + '&status=open" class="badge badge-soft-danger badge-pill" title="Lihat keluhan yang belum selesai">'
                + '<i class="fas fa-exclamation-circle mr-1"></i>' + open + ' keluhan terbuka</a>');
        }
        return badges.length ? '<div class="mt-1 d-flex flex-wrap" style="gap:4px;">' + badges.join('') + '</div>' : '';
    }

    var table = $('#pasien-table').DataTable({
        processing: true,
        serverSide: true,
        order: [], // server default: newest visit first
        language: { search: 'Search:', searchPlaceholder: 'Nama / No RM / No HP', processing: 'Memuat...', emptyTable: 'Tidak ada pasien', zeroRecords: 'Pasien tidak ditemukan' },
        ajax: {
            url: "{{ route('marketing.pasien-data') }}",
            data: function (d) {
                d.last_visit = $('#last-visit-filter').val();
                d.last_visit_klinik = $('#last-visit-klinik-filter').val();
            }
        },
        columns: [
            { data: 'id', name: 'id', className: 'crm-muted-id' },
            {
                data: 'nama',
                name: 'nama',
                className: 'crm-wrap',
                render: function (data, type, row) {
                    if (type !== 'display') return data;
                    return '<div class="crm-person-name">' + (data || '-') + '</div>' + statusBadges(row);
                }
            },
            { data: 'tanggal_lahir', name: 'tanggal_lahir', searchable: false },
            {
                data: 'no_hp',
                name: 'no_hp',
                render: function (data, type) {
                    return type === 'display' ? crm.waLink(data) : data;
                }
            },
            { data: 'kunjungan_terakhir', name: 'last_visitation_date', searchable: false },
            {
                data: 'followed_up_7d',
                name: 'followed_up_7d',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function (data) {
                    return data
                        ? '<span class="badge badge-success" title="Sudah di-follow up dalam 7 hari terakhir"><i class="fas fa-check"></i></span>'
                        : '<span class="text-muted">-</span>';
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'crm-actions-cell',
                render: function () {
                    return '<button type="button" class="btn btn-success btn-sm crm-followup-btn"><i class="fas fa-phone-alt mr-1"></i>Follow Up</button> '
                        + '<button type="button" class="btn btn-outline-info btn-sm riwayat-rm-btn"><i class="fas fa-notes-medical mr-1"></i>Riwayat RM</button> '
                        + '<button type="button" class="btn btn-outline-secondary btn-sm view-pasien-btn"><i class="fas fa-user mr-1"></i>Detail</button>';
                }
            }
        ]
    });

    function rowData(el) {
        return table.row($(el).closest('tr')).data() || {};
    }

    // Names come escaped from the server; the follow up form needs plain text
    function plain(html) {
        return $('<div>').html(html || '').text();
    }

    function openFollowUp(pasien) {
        crm.openFollowUp({ id: pasien.id, nama: plain(pasien.nama) });
    }

    $('#last-visit-filter, #last-visit-klinik-filter').on('change', function () {
        table.draw();
    });
    $('#pasienResetFilter').on('click', function () {
        $('#last-visit-filter, #last-visit-klinik-filter').val('all');
        table.search('').order([]).draw();
    });

    $('#pasien-table').on('click', '.crm-followup-btn', function () {
        var pasien = rowData(this);
        if (pasien.id) openFollowUp(pasien);
    });

    var viewing = null;
    $('#pasien-table').on('click', '.view-pasien-btn', function () {
        var p = viewing = rowData(this);
        var fields = [
            ['No RM', p.id],
            ['Nama', p.nama],
            [plain(p.identity_document) ? plain(p.identity_document).toUpperCase() : 'Identitas', p.nik],
            ['Tanggal Lahir', p.tanggal_lahir],
            ['Gender', p.gender_text],
            ['Agama', p.agama],
            ['Status Pernikahan', p.marital_status],
            ['Pendidikan', p.pendidikan],
            ['Pekerjaan', p.pekerjaan],
            ['Golongan Darah', p.gol_darah],
            ['Alamat', p.alamat],
            ['No HP', crm.waLink(p.no_hp)],
            ['No HP 2', p.no_hp2 ? crm.waLink(p.no_hp2) : null],
            ['Email', p.email],
            ['Instagram', p.instagram],
            ['Catatan', p.notes]
        ];
        // Values are server-escaped (or built from escaped values above)
        $('#viewPasienBody').html(fields.map(function (f) {
            return '<tr><th style="width:35%">' + crm.esc(f[0]) + '</th><td>' + (f[1] || '<span class="text-muted">-</span>') + '</td></tr>';
        }).join(''));
        $('#viewPasienModalLabel').text('Detail Pasien: ' + plain(p.nama));
        $('#viewPasienModal').modal('show');
    });
    $('#viewPasienFollowUp').on('click', function () {
        if (!viewing) return;
        $('#viewPasienModal').modal('hide');
        openFollowUp(viewing);
    });

    $('#pasien-table').on('click', '.riwayat-rm-btn', function () {
        var pasien = rowData(this);
        if (!pasien.id) return;
        $('#riwayatRMModalLabel').text('Riwayat RM: ' + plain(pasien.nama));
        $('#riwayatRMModalBody').html('<div class="text-center text-muted py-4">Memuat...</div>');
        $('#riwayatRMModal').modal('show');
        $.get('/marketing/pasien/' + encodeURIComponent(pasien.id) + '/riwayat-rm', function (res) {
            $('#riwayatRMModalBody').html(res);
        }).fail(function () {
            $('#riwayatRMModalBody').html('<div class="text-danger">Gagal memuat data.</div>');
        });
    });

    return table;
};
</script>
@endpush
