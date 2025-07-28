@extends('layouts.marketing.app')

@section('title', 'Pasien Data - Marketing')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Pasien Data</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('marketing.dashboard') }}">Marketing</a></li>
                            <li class="breadcrumb-item active">Pasien Data</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-8">
                            <h4 class="card-title">Daftar Pasien</h4>
                        </div>
                        <div class="col-md-4">
                            <div class="row">
                                <!-- Wilayah filter removed -->
                                <div class="col-6">
                                    <select id="last-visit-filter" class="form-control" style="min-width:180px;">
                                        <option value="all">Semua Kunjungan Terakhir</option>
                                        <option value="gt1w">Lebih dari 1 Minggu</option>
                                        <option value="gt1m">Lebih dari 1 Bulan</option>
                                        <option value="gt3m">Lebih dari 3 Bulan</option>
                                        <option value="gt6m">Lebih dari 6 Bulan</option>
                                        <option value="gt1y">Lebih dari 1 Tahun</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <select id="last-visit-klinik-filter" class="form-control" style="min-width:180px;">
                                        <option value="all">Semua Klinik Terakhir</option>
                                        @foreach(\App\Models\ERM\Klinik::all() as $klinik)
                                            <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!--end card-header-->
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="pasien-table" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>Tanggal Lahir</th>
                                    <th>No HP</th>
                                    <th>Kunjungan Terakhir</th>
                                    <th>Gender</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
</div><!-- container -->
<!-- Modal for View Pasien Data -->
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
        <table class="table table-bordered">
          <tbody>
            <tr><th>ID</th><td id="modal-pasien-id"></td></tr>
            <tr><th>Nama</th><td id="modal-pasien-nama"></td></tr>
            <tr><th>NIK</th><td id="modal-pasien-nik"></td></tr>
            <tr><th>Tanggal Lahir</th><td id="modal-pasien-tanggal_lahir"></td></tr>
            <tr><th>Gender</th><td id="modal-pasien-gender"></td></tr>
            <tr><th>Agama</th><td id="modal-pasien-agama"></td></tr>
            <tr><th>Marital Status</th><td id="modal-pasien-marital_status"></td></tr>
            <tr><th>Pendidikan</th><td id="modal-pasien-pendidikan"></td></tr>
            <tr><th>Pekerjaan</th><td id="modal-pasien-pekerjaan"></td></tr>
            <tr><th>Golongan Darah</th><td id="modal-pasien-gol_darah"></td></tr>
            <tr><th>Notes</th><td id="modal-pasien-notes"></td></tr>
            <tr><th>Alamat</th><td id="modal-pasien-alamat"></td></tr>
            <tr><th>No HP</th><td id="modal-pasien-no_hp"></td></tr>
            <tr><th>No HP 2</th><td id="modal-pasien-no_hp2"></td></tr>
            <tr><th>Email</th><td id="modal-pasien-email"></td></tr>
            <tr><th>Instagram</th><td id="modal-pasien-instagram"></td></tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal for Riwayat RM -->
<div class="modal fade" id="riwayatRMModal" tabindex="-1" role="dialog" aria-labelledby="riwayatRMModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="riwayatRMModalLabel">Riwayat RM</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="riwayatRMModalBody">
        <!-- Content loaded by JS -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    $(function() {
        // Initialize DataTable
        var table = $('#pasien-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('marketing.pasien-data') }}",
                data: function (d) {
                    d.last_visit = $('#last-visit-filter').val();
                    d.last_visit_klinik = $('#last-visit-klinik-filter').val();
                }
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'nama', name: 'nama' },
                { data: 'tanggal_lahir', name: 'tanggal_lahir' },
                { data: 'no_hp', name: 'no_hp' },
                { data: 'kunjungan_terakhir', name: 'kunjungan_terakhir' },
                { data: 'gender_text', name: 'gender_text' },
                {
                    data: null,
                    name: 'aksi',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `
                            <button class=\"btn btn-primary btn-sm view-pasien-btn\" data-pasien='${JSON.stringify(row)}'>View</button>
                            <button class=\"btn btn-success btn-sm add-followup-btn\" data-id='${row.id}'>Add to Follow Up List</button>
                            <button class=\"btn btn-info btn-sm riwayat-rm-btn\" data-id='${row.id}'>Riwayat RM</button>
                        `;
                    }
                }

            ]
    });
        // Add to Follow Up List button handler
    $(document).on('click', '.add-followup-btn', function() {
        var pasienId = $(this).data('id');
        if (!pasienId) return;
        if (!confirm('Tambahkan pasien ini ke Follow Up List?')) return;
        $.ajax({
            url: '/marketing/followup/add-from-pasien',
            method: 'POST',
            data: {
                pasien_id: pasienId,
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                alert('Berhasil ditambahkan ke Follow Up List!');
            },
            error: function(xhr) {
                alert('Gagal menambahkan ke Follow Up List!');
            }
        });
    });

    // Reload table when any filter changes
    $('#last-visit-filter, #last-visit-klinik-filter').change(function() {
        table.draw();
    });

    // Modal for view pasien
    $(document).on('click', '.view-pasien-btn', function() {
        var pasien = $(this).data('pasien');
        // Fill modal fields
        $('#modal-pasien-id').text(pasien.id || '-');
        $('#modal-pasien-nama').text(pasien.nama || '-');
        $('#modal-pasien-nik').text(pasien.nik || '-');
        $('#modal-pasien-tanggal_lahir').text(pasien.tanggal_lahir || '-');
        $('#modal-pasien-gender').text(pasien.gender || '-');
        $('#modal-pasien-agama').text(pasien.agama || '-');
        $('#modal-pasien-marital_status').text(pasien.marital_status || '-');
        $('#modal-pasien-pendidikan').text(pasien.pendidikan || '-');
        $('#modal-pasien-pekerjaan').text(pasien.pekerjaan || '-');
        $('#modal-pasien-gol_darah').text(pasien.gol_darah || '-');
        $('#modal-pasien-notes').text(pasien.notes || '-');
        $('#modal-pasien-alamat').text(pasien.alamat || '-');
        $('#modal-pasien-no_hp').text(pasien.no_hp || '-');
        $('#modal-pasien-no_hp2').text(pasien.no_hp2 || '-');
        $('#modal-pasien-email').text(pasien.email || '-');
        $('#modal-pasien-instagram').text(pasien.instagram || '-');
        $('#viewPasienModal').modal('show');
    });

    // Modal for Riwayat RM
    $(document).on('click', '.riwayat-rm-btn', function() {
        var pasienId = $(this).data('id');
        if (!pasienId) return;
        // Clear modal content
        $('#riwayatRMModalBody').html('<div class="text-center">Loading...</div>');
        $('#riwayatRMModal').modal('show');
        // Fetch data
        $.get('/marketing/pasien/' + pasienId + '/riwayat-rm', function(res) {
            // Helper: format date to 1 Januari 2025
            function formatIndoDate(dateStr) {
                if (!dateStr || dateStr === '-') return '-';
                var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                var d = new Date(dateStr);
                if (isNaN(d)) return dateStr;
                var day = d.getDate();
                var month = months[d.getMonth()];
                var year = d.getFullYear();
                return day + ' ' + month + ' ' + year;
            }
            // Render grouped by visitation
            var html = '';
            if (res && res.length > 0) {
                res.forEach(function(visit) {
                    var visitDate = formatIndoDate(visit.visitation_info);
                    var dokterNama = visit.dokter_nama ? visit.dokter_nama : '-';
                    html += '<div class="card mb-3">';
                    html += '<div class="card-header"><b>Visitation: ' + (visitDate || '-') + ' <span class="text-muted">| Dokter: ' + dokterNama + '</span></b></div>';
                    html += '<div class="card-body">';
                    html += '<h6>Riwayat Resep Dokter</h6>';
                    if (visit.resep_dokter && visit.resep_dokter.length > 0) {
                        html += '<ul>';
                        visit.resep_dokter.forEach(function(r) {
                            html += '<li>' + (r.obat_nama || '-') + ' | Jumlah: ' + (r.jumlah || '-') + '</li>';
                        });
                        html += '</ul>';
                    } else {
                        html += '<div class="text-muted">Tidak ada resep dokter</div>';
                    }
                    html += '<h6 class="mt-2">Riwayat Tindakan</h6>';
                    if (visit.riwayat_tindakan && visit.riwayat_tindakan.length > 0) {
                        html += '<ul>';
                        visit.riwayat_tindakan.forEach(function(t) {
                            html += '<li>' + (t.tindakan_nama || '-') + '</li>';
                        });
                        html += '</ul>';
                    } else {
                        html += '<div class="text-muted">Tidak ada tindakan</div>';
                    }
                    html += '</div></div>';
                });
            } else {
                html = '<div class="text-center text-muted">Tidak ada data riwayat.</div>';
            }
            $('#riwayatRMModalBody').html(html);
        }).fail(function() {
            $('#riwayatRMModalBody').html('<div class="text-danger">Gagal memuat data.</div>');
        });
    });



    });
</script>
@endpush
