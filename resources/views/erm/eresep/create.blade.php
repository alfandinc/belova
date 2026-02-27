@extends('layouts.erm.app')
@section('title', 'E-Resep')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection
@section('content')

<style>
    /* Custom styling for medications that exist in both doctor and pharmacy prescriptions */
    .text-success {
        color: #28a745 !important;
        font-weight: bold;
    }

    .row-in-farmasi {
        background-color: rgba(40, 167, 69, 0.1) !important;
    }
    
    /* Enhance the existing success color */
    tr.text-success td {
        color: #28a745 !important;
    }

    /* Apply styling to racikan tables as well */
    #racikan-container .resep-table-body tr.text-success td {
        color: #28a745 !important;
    }
    
    #racikan-container .resep-table-body tr.row-in-farmasi {
        background-color: rgba(40, 167, 69, 0.1) !important;
    }

    /* Small badge used for section titles */
    .eresep-badge {
        display: inline-block;
        padding: .25rem .5rem;
        font-size: 0.9rem;
        font-weight: 700;
        color: #212529;
        background-color: #ffc107; /* yellow */
        border-radius: .375rem;
        vertical-align: middle;
    }
    .eresep-badge i {
        margin-right: .4rem;
        vertical-align: middle;
    }
</style>

@include('erm.partials.modal-alergipasien')
@include('erm.partials.modal-resephistory')
@include('erm.partials.modal-paketracikan')

@include('erm.partials.modal-editnonracikan-dokter')

<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">E-Resep Pasien</h3>
        <input type="hidden" id="visitation_id" value="{{ $visitation->id }}">
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Rawat Jalan</li>
                            <li class="breadcrumb-item active">E-Resep</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('erm.partials.card-identitaspasien')

    <div class="card">
        <div class="card-body">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div style="display: flex; align-items: center;">
                        <h4 style="margin: 0;">Total Harga: <strong>Rp.</strong></h4>
                        <h4 id="total-harga" style="margin: 0;"><strong>0</strong></h4>
                    </div>
                    <div class="mb-3">
                        <button class="btn btn-sm btn-info btn-riwayat" data-url="{{ route('resep.historydokter', $pasien->id) }}">
                            Riwayat Dokter
                        </button>

                        <button class="btn btn-sm btn-info btn-riwayat" data-url="{{ route('resep.historyfarmasi', $pasien->id) }}">
                            Riwayat Farmasi
                        </button>
                        
                        <button id="paket-racikan" class="btn btn-sm btn-warning">Paket Racikan</button>
                        <button id="pasien-keluar-btn" class="btn btn-sm btn-danger" data-pasien-id="{{ $pasien->id }}" data-pasien-name="{{ $pasien->nama }}">Keluar</button>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">Dokter Input : {{ auth()->user()->name }}</h5>
                        <button class="btn btn-success btn-sm" id="simpan-catatan">Simpan Catatan</button>
                    </div>
                    <textarea class="form-control" id="catatan_dokter" placeholder="Tuliskan catatan disini ..." rows="3">{{ $catatan_dokter ?? ($catatan_resep ?? '') }}</textarea>
                </div>

                <!-- NON RACIKAN -->
                <h5><span class="eresep-badge"><i class="fas fa-pills"></i>Resep Non Racikan</span></h5>
                <div class="racikan-card mb-4 p-3 border rounded">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label>Nama Obat</label>
                            <select class="form-control select2-obat" name="obat_id" id="obat_id">
                                <option value="">Search and select an obat...</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Jumlah</label>
                            <input type="number" id="jumlah" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label>Aturan Pakai</label>
                            <div class="input-group mb-1">
                                <input type="number" min="1" id="frekuensi" class="form-control" placeholder="Frekuensi (misal: 1)">
                                <span class="input-group-text">x sehari</span>
                                <input type="text" min="1" id="dosis" class="form-control" placeholder="Dosis (misal: 1)">
                                <input type="text" id="keterangan_waktu" class="form-control" placeholder="Keterangan waktu (misal: sebelum makan)">
                            </div>
                            <input hidden type="text" id="aturan_pakai" class="form-control" placeholder="Aturan Pakai" readonly>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button id="tambah-resep" class="btn btn-primary btn-block">Tambah</button>
                        </div>
                    </div>

                    <!-- Legend for color coding -->
                    <div class="mb-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Keterangan: 
                            <span class="text-success font-weight-bold">Hijau</span> = Obat sudah ada di resep farmasi
                        </small>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nama Obat</th>
                                <th>Harga Satuan</th>
                                <th>Jumlah</th>
                                <th>Sisa Stok</th>
                                <th>Harga Akhir</th>
                                <th>Aturan Pakai</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="resep-table-body">
                            {{-- {{ dd($nonRacikans) }} --}}
                            @forelse ($nonRacikans as $resep)
                                <tr data-id="{{ $resep->id }}" data-obat-id="{{ $resep->obat_id }}" class="{{ in_array($resep->obat_id, $farmasiObatIds) ? 'text-success row-in-farmasi' : '' }}">
                                    <td>{{ $resep->obat->nama ?? '-' }}</td>
                                    <td>{{ $resep->obat->harga_nonfornas ?? 0 }}</td>
                                    <td>{{ $resep->jumlah }}</td>
                                    @php
                                        $gudangId = \App\Models\ERM\GudangMapping::getDefaultGudangId('resep');
                                        // Guard against missing related obat
                                        $stokGudang = ($gudangId && $resep->obat) ? $resep->obat->getStokByGudang($gudangId) : 0;
                                    @endphp
                                    <td style="color: {{ ($stokGudang < 10 ? 'red' : ($stokGudang < 100 ? 'yellow' : 'green')) }};">
                                        {{ (int) $stokGudang }}
                                    </td>
                                    <td>{{ ($resep->jumlah ?? 0) * ($resep->obat->harga_nonfornas ?? 0) }}</td>
                                    <td>{{ $resep->aturan_pakai }}</td>
                                    <td><button class="btn btn-success btn-sm edit" data-id="{{ $resep->id }}">Edit</button>
                                        <button class="btn btn-danger btn-sm hapus" data-id="{{ $resep->id }}">Hapus</button> </td>
                                </tr>
                            @empty
                                <tr class="no-data">
                                    <td colspan="6" class="text-center text-muted">Belum ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- RACIKAN -->
                <h5><span class="eresep-badge"><i class="fas fa-capsules"></i>Resep Racikan</span></h5>
                <div class="mb-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> Keterangan: 
                        <span class="text-success font-weight-bold">Hijau</span> = Obat sudah ada di resep farmasi racikan
                    </small>
                </div>
                
                <div id="racikan-container">
                    @foreach ($racikans as $ke => $items)
                    <div class="racikan-card mb-4 p-3 border rounded" data-racikan-ke="{{ $ke }}">
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 style=""><strong>Racikan {{ $ke }}
                                <span class="racikan-harga-detail" style="font-size: 1rem; font-weight: normal; margin-left: 10px;"></span>
                            </strong></h5>
                            <div>
                                <button class="btn btn-warning btn-sm edit-racikan mr-2">Edit Racikan</button>
                                <button class="btn btn-danger btn-sm hapus-racikan">Hapus Racikan</button>
                            </div>
                        </div>

                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nama Obat</th>
                                    <th>Dosis Obat</th>
                                    <th>Dosis Racik</th>
                                    <th>Harga Satuan</th>
                                    <th>Harga Akhir</th>
                                    <th>Sisa Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="resep-table-body">
                                @foreach ($items as $resep)
                                    <tr data-obat-id="{{ $resep->obat_id }}" class="{{ in_array($resep->obat_id, $farmasiRacikanObatIds) ? 'text-success row-in-farmasi' : '' }}">
                                        <td data-id="{{ $resep->id }}">{{ $resep->obat->nama ?? '-' }}</td>
                                        <td>{{ $resep->obat->dosis ?? '-' }}</td>
                                        <td>
                                            {{ $resep->dosis }}
                                            @if(empty($resep->dosis))
                                                <span style="color:red;font-weight:bold;">[DEBUG: dosis kosong]</span>
                                            @endif
                                        </td>
                                        <td>{{ $resep->obat->harga_nonfornas ?? 0 }}</td>
                                        <td>
                                            @php
                                                $dosisObat = (float)($resep->obat->dosis ?? 0);
                                                $dosisRacik = (float)($resep->dosis ?? 0);
                                                $hargaSatuan = (float)($resep->obat->harga_nonfornas ?? 0);
                                                $hargaAkhir = ($dosisObat > 0) ? ($dosisRacik / $dosisObat) * $hargaSatuan : 0;
                                            @endphp
                                            {{ $hargaAkhir }}
                                        </td>
                                        @php
                                            $gudangId = \App\Models\ERM\GudangMapping::getDefaultGudangId('resep');
                                            $stokGudang = ($gudangId && $resep->obat) ? $resep->obat->getStokByGudang($gudangId) : ($resep->obat->stok ?? 0);
                                        @endphp
                                        <td style="color: {{ ($stokGudang < 10 ? 'red' : (($stokGudang < 100) ? 'yellow' : 'green')) }};">
                                            {{ (int) $stokGudang }}
                                        </td>
                                        <td>
                                            <button class="btn btn-success btn-sm edit-obat disabled" disabled>Edit</button>
                                            <button class="btn btn-danger btn-sm hapus-obat disabled" disabled>Hapus</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Form untuk menambah obat baru ke racikan (hidden by default) -->
                        <div class="add-medication-form d-none mb-3">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Tambah Obat ke Racikan {{ $ke }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label>Nama Obat</label>
                                            <select class="form-control select2-obat-racikan-add" style="width: 100%;">
                                                <option value="">Pilih Obat</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label>Dosis</label>
                                            <input type="number" class="form-control dosis-racikan-add" placeholder="Dosis" step="0.01" min="0">
                                        </div>
                                        <div class="col-md-2">
                                            <label>Mode</label>
                                            <select class="form-control mode-racikan-add">
                                                <option value="manual">Manual</option>
                                                <option value="tablet">Per Tablet</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="button" class="btn btn-primary btn-sm btn-add-medication btn-block">Tambah</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <label>RACIKAN</label>
                                <select class="form-control select2-wadah-racikan wadah" name="wadah_id" disabled>
                                <option value="{{ $items->first()?->wadah?->id ?? '' }}">
                                    {{ $items->first()?->wadah?->nama ?? 'Pilih Wadah' }}
                                </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Bungkus</label>
                                <input type="number" class="form-control jumlah_bungkus bungkus" value="{{ $items->first()->bungkus }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label>Aturan Pakai</label>
                                <input type="text" class="form-control aturan_pakai" value="{{ $items->first()->aturan_pakai }}" disabled>
                            </div>
                        </div>

                        <button class="btn btn-success btn-block mt-3 tambah-resepracikan" disabled>Sudah Disimpan</button>
                        <button class="btn btn-primary btn-block mt-3 update-resepracikan d-none">Update Racikan</button>
                    </div>
                    @endforeach
                </div>
                <button id="tambah-racikan" class="btn btn-primary mb-3">Tambah Racikan</button>
                

            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
        // MODAL HTML for editing dosis
        const editObatModalHtml = `
        <div class="modal fade" id="editObatModal" tabindex="-1" role="dialog" aria-labelledby="editObatModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="editObatModalLabel">Edit Dosis Racik</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <form id="editObatForm">
                  <div class="form-group">
                    <label for="editDosisRacik">Dosis Racik</label>
                    <input type="text" class="form-control" id="editDosisRacik" name="dosis_racik" required>
                  </div>
                  <input type="hidden" id="editObatRowId">
                </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveEditObatBtn">Simpan</button>
              </div>
            </div>
          </div>
        </div>
        `;
        // Append modal to body if not exists
        if ($('#editObatModal').length === 0) {
            $('body').append(editObatModalHtml);
        }

        // Handle Edit button click in racikan table
        $('#racikan-container').on('click', '.edit-obat', function () {
            const row = $(this).closest('tr');
            const dosisRacik = row.find('td').eq(2).text().trim();
            const dbId = row.find('td[data-id]').data('id');
            // Store both row reference and db id for later
            $('#editDosisRacik').val(dosisRacik);
            $('#editObatRowId').val(row.index());
            $('#editObatModal').data('db-id', dbId || null);
            $('#editObatModal').modal('show');
        });

        // Save edited dosis racik
        $('#saveEditObatBtn').on('click', function () {
            const newDosis = $('#editDosisRacik').val().trim();
            const rowIdx = $('#editObatRowId').val();
            const dbId = $('#editObatModal').data('db-id');
            // Find the correct row in all racikan tables
            let found = false;
            $('#racikan-container .resep-table-body').each(function() {
                const rows = $(this).find('tr');
                if (rows.length > rowIdx) {
                    const row = rows.eq(rowIdx);
                    // Update Dosis Racik column (td:eq(2))
                    row.find('td').eq(2).text(newDosis);
                    // Ensure the td[data-id] still has the correct db id
                    if (dbId) {
                        row.find('td[data-id]').attr('data-id', dbId);
                    }
                    found = true;
                    return false;
                }
            });
            if (found) {
                $('#editObatModal').modal('hide');
            }
        });
    let racikanCount = {{ $lastRacikanKe ?? 0 }};
    let farmasiObatIds = @json($farmasiObatIds ?? []);
    let farmasiRacikanObatIds = @json($farmasiRacikanObatIds ?? []);

    // Function to check if obat exists in farmasi prescriptions
    function checkIfObatInFarmasi(obatId, callback) {
        $.ajax({
            url: "{{ route('erm.eresepfarmasi.json', $visitation->id) }}",
            method: 'GET',
            success: function(response) {
                const exists = response.nonRacikans && response.nonRacikans.some(item => item.obat_id == obatId);
                callback(exists);
            },
            error: function(xhr, status, error) {
                console.log('Error checking farmasi prescriptions:', error);
                callback(false);
            }
        });
    }

    // Function to check if obat exists in farmasi racikan prescriptions
    function checkIfObatInFarmasiRacikan(obatId, callback) {
        $.ajax({
            url: "{{ route('erm.eresepfarmasi.json', $visitation->id) }}",
            method: 'GET',
            success: function(response) {
                const exists = response.racikans && Object.values(response.racikans).some(racikanGroup => 
                    racikanGroup.some(item => item.obat_id == obatId)
                );
                callback(exists);
            },
            error: function(xhr, status, error) {
                console.log('Error checking farmasi racikan prescriptions:', error);
                callback(false);
            }
        });
    }

    $(document).ready(function () {
    // Get gudangId for resep transaction type from Blade
    const gudangId = {{ \App\Models\ERM\GudangMapping::getDefaultGudangId('resep') ?? 'null' }};
        // Always disable hapus-obat buttons in racikan on page load
        $('#racikan-container .hapus-obat').prop('disabled', true).addClass('disabled');

        $('.select2').select2({ width: '100%' });
        $('.select2-obat').select2({
            placeholder: 'Search obat...',
            minimumInputLength: 3,
            ajax: {
                url: '{{ route("obat.search") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    // Get metode_bayar_id from visitation (from blade or JS variable)
                    var metodeBayarId = {{ $visitation->metode_bayar_id ?? 'null' }};
                    return {
                        q: params.term,
                        metode_bayar_id: metodeBayarId,
                        visitation_id: $('#visitation_id').val()
                    };
                },
                processResults: function (data) {
                    // If your endpoint returns {results: [...]}, use that
                    if (Array.isArray(data.results)) {
                        return {
                            results: data.results.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.text,
                                    nama: item.nama,
                                    dosis: item.dosis,
                                    satuan: item.satuan,
                                    stok: item.stok,
                                    harga_nonfornas: item.harga_nonfornas
                                };
                            })
                        };
                    } else {
                        // fallback for array response
                        return {
                            results: data.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.nama + (item.harga_nonfornas ? ' - ' + item.harga_nonfornas : ''),
                                    nama: item.nama,
                                    dosis: item.dosis,
                                    satuan: item.satuan,
                                    stok: item.stok,
                                    harga_nonfornas: item.harga_nonfornas
                                };
                            })
                        };
                    }
                },
                cache: true
            }
        });

            // Show warning if selected obat has stock 0 (non-racikan)
                $('.select2-obat').on('select2:select', function(e) {
                    var data = e.params.data;
                    var stokSelected = (data.stok_gudang !== undefined ? data.stok_gudang : data.stok);
                    if (stokSelected !== undefined && parseInt(stokSelected) === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stok Obat Kosong',
                            text: 'Obat yang Anda pilih memiliki stok 0. Silakan pilih obat lain atau konfirmasi ke farmasi.',
                            confirmButtonText: 'OK'
                        });
                    }
                });
        $('.select2-wadah-racikan').select2({
            placeholder: 'Search wadah...',
            ajax: {
                url: '{{ route("wadah.search") }}', // Use the wadah.search route
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term // Search term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.map(item => ({
                            id: item.id,
                            text: item.text // Adjust based on your API response structure
                        }))
                    };
                },
                cache: true
            },

        });

        // Aturan pakai template not used on dokter resep page (only farmasi uses it)

        // Function to refresh row colors based on farmasi prescriptions
        function refreshRowColors() {
            // Handle non-racikan rows
            $('#resep-table-body tr[data-id]').each(function() {
                const row = $(this);
                const obatId = row.data('obat-id');
                
                if (obatId) {
                    checkIfObatInFarmasi(obatId, function(existsInFarmasi) {
                        if (existsInFarmasi) {
                            row.addClass('text-success row-in-farmasi');
                        } else {
                            row.removeClass('text-success row-in-farmasi');
                        }
                    });
                }
            });

            // Handle racikan rows
            $('#racikan-container .resep-table-body tr[data-obat-id]').each(function() {
                const row = $(this);
                const obatId = row.data('obat-id');
                
                if (obatId) {
                    checkIfObatInFarmasiRacikan(obatId, function(existsInFarmasi) {
                        if (existsInFarmasi) {
                            row.addClass('text-success row-in-farmasi');
                        } else {
                            row.removeClass('text-success row-in-farmasi');
                        }
                    });
                }
            });
        }

        // Call refresh function on page load with a delay
        setTimeout(refreshRowColors, 1000);
        
        // Optional: Periodically refresh colors every 30 seconds
        setInterval(refreshRowColors, 30000);

        // STORE NON RACIKAN
        $('#tambah-resep').on('click', function () {
            let obatId = $('#obat_id').val();
            let obatText = $('#obat_id option:selected').text();
            let jumlah = $('#jumlah').val();
            let aturanPakai = $('#aturan_pakai').val();
            let visitationId = $('#visitation_id').val();

            if (!obatId || !jumlah || !aturanPakai) {
                alert("Semua field wajib diisi.");
                return;
            }

            // Send data via AJAX
            $.ajax({
                url: "{{ route('resep.nonracikan.store') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    tipe: "nonracikan",
                    obat_id: obatId,
                    jumlah: jumlah,
                    aturan_pakai: aturanPakai,
                    visitation_id: visitationId
                },
                success: function (res) {
                    // Remove the "no data" row if it exists
                    $('#resep-table-body .no-data').remove();

                    // Check if this medication exists in farmasi prescriptions
                    checkIfObatInFarmasi(res.data.obat_id, function(existsInFarmasi) {
                        const rowClass = existsInFarmasi ? 'text-success row-in-farmasi' : '';
                        
                        // Append the new row to the table
                        $('#resep-table-body').prepend(`
                            <tr data-id="${res.data.id}" data-obat-id="${res.data.obat_id}" class="${rowClass}">
                                <td>${res.data.obat.nama}</td>
                                <td>${res.data.obat.harga_nonfornas || 0}</td>
                                <td>${res.data.jumlah}</td>
                                <td style="color: ${parseInt(res.data.obat.stok_gudang) < 10 ? 'red' : (parseInt(res.data.obat.stok_gudang) < 100 ? 'yellow' : 'green')}">${res.data.obat.stok_gudang !== undefined ? parseInt(res.data.obat.stok_gudang) : 0}</td>
                                <td>${(res.data.obat.harga_nonfornas || 0) * (res.data.jumlah || 0)}</td>
                                <td>${res.data.aturan_pakai}</td>
                                <td>
                                    <button class="btn btn-success btn-sm edit" data-id="${res.data.id}">Edit</button>
                                    <button class="btn btn-danger btn-sm hapus" data-id="${res.data.id}">Hapus</button>
                                </td>
                            </tr>
                        `);

                        // Update the total price
                        updateTotalPrice();
                        
                        // Refresh row colors after adding new prescription
                        setTimeout(refreshRowColors, 500);
                    });

                    // Clear the input fields
                    $('#obat_id').val(null).trigger('change');
                    $('#jumlah').val('');
                    $('#aturan_pakai').val('');
                    // Set focus to Nama Obat input after adding
                    setTimeout(function() {
                        $('#obat_id').select2('open');
                    }, 200);
                },
                error: function (xhr) {
                    alert('Gagal menambahkan resep: ' + xhr.responseJSON.message);
                }
            });
        });

        //DELETE NON RACIKAN
        $('#resep-table-body').on('click', '.hapus', function () {
            const row = $(this).closest('tr');
            const resepId = row.data('id');

            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Yakin ingin menghapus resep ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: "{{ route('resep.nonracikan.destroy', '') }}/" + resepId,
                        method: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function () {
                            row.remove();
                            if ($('#resep-table-body tr').length === 0) {
                                $('#resep-table-body').append(`<tr class="no-data"><td colspan="6" class="text-center text-muted">Belum ada data</td></tr>`);
                            }
                            updateTotalPrice();
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: 'Resep berhasil dihapus',
                                confirmButtonColor: '#3085d6'
                            });
                        },
                        error: function () {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Menghapus',
                                text: 'Gagal menghapus resep. Coba lagi.',
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    });
                }
            });
        });

        // SIMPAN CATATAN DOKTER INPUT
        $('#simpan-catatan').on('click', function() {
            let catatan = $('#catatan_dokter').val();
            let visitationId = $('#visitation_id').val();
            if (!catatan) {
                alert('Catatan tidak boleh kosong.');
                return;
            }
            $.ajax({
                url: "{{ route('resep.catatan.store') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    visitation_id: visitationId,
                    catatan_dokter: catatan
                },
                success: function(res) {
                    alert(res.message || 'Catatan berhasil disimpan.');
                },
                error: function(xhr) {
                    alert('Gagal menyimpan catatan: ' + (xhr.responseJSON?.message || 'Error'));
                }
            });
        });

        // UPDATE HARGA
        function updateTotalPrice() {
            let total = 0;
            // Non Racikan
            $('#resep-table-body tr').each(function () {
                let harga = parseFloat($(this).find('td').eq(1).text().replace(/[^\d.]/g, '')) || 0;
                let jumlah = parseFloat($(this).find('td').eq(2).text().replace(/[^\d.]/g, '')) || 0;
                total += harga * jumlah;
            });
            // Racikan
            $('#racikan-container .racikan-card').each(function () {
                let racikanTotal = 0;
                let bungkus = parseFloat($(this).find('.jumlah_bungkus, .bungkus').val()) || 1;
                $(this).find('.resep-table-body tr').each(function () {
                    // skip no-data rows
                    if ($(this).hasClass('no-data')) return;
                    let hargaAkhir = parseFloat($(this).find('td').eq(4).text()) || 0;
                    racikanTotal += hargaAkhir;
                });
                // Update racikan-harga display to match racikan-harga-detail format
                $(this).find('.racikan-harga').text(`(${new Intl.NumberFormat('id-ID').format(racikanTotal)} x ${bungkus} = ${new Intl.NumberFormat('id-ID').format(racikanTotal * bungkus)})`);
                // Update racikan-harga-detail display (formula beside racikan ke)
                $(this).find('.racikan-harga-detail').text(`(${new Intl.NumberFormat('id-ID').format(racikanTotal)} x ${bungkus} = ${new Intl.NumberFormat('id-ID').format(racikanTotal * bungkus)})`);
                total += racikanTotal * bungkus;
            });
            $('#total-harga').html('<strong>' + new Intl.NumberFormat('id-ID').format(total) + '</strong>');
        }

        // TAMBAH RACIKAN BARU
        $('#tambah-racikan').on('click', function () {
            racikanCount++;

            const racikanCard = `
                <div class="racikan-card mb-4 p-3 border rounded" data-racikan-ke="${racikanCount}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 style=""><strong>Racikan ${racikanCount}
                                                        <span class="racikan-harga" style="color: #ffc107; font-weight: normal; margin-left: 10px;">Rp. 0</span>

                        </strong></h5>
                        <div>
                            <button class="btn btn-warning btn-sm edit-racikan mr-2">Edit Racikan</button>
                            <button class="btn btn-danger btn-sm hapus-racikan">Hapus Racikan</button>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Nama Obat</label>
                            <select class="form-control select2-obat-racikan" name="obat_id">
                                <option value="">Search and select an obat...</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Dosis</label>
                            <input type="number" class="form-control dosis_input">
                        </div>
                        <div class="col-md-2">
                            <label>Satuan</label>
                            <select class="form-control mode_dosis">
                                <option value="normal">Normal</option>
                                <option value="tablet">Tablet</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary btn-block tambah-obat">Tambah ke Racikan</button>
                        </div>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nama Obat</th>
                                <th>Dosis Obat</th>
                                <th>Dosis Racik</th>
                                <th>Harga Satuan</th>
                                <th>Harga Akhir</th>
                                <th>Sisa Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="resep-table-body">
                            <tr class="no-data">
                                <td colspan="4" class="text-center text-muted">Belum ada data</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Form untuk menambah obat baru ke racikan (hidden by default) -->
                    <div class="add-medication-form d-none mb-3">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Tambah Obat ke Racikan ${racikanCount}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Nama Obat</label>
                                        <select class="form-control select2-obat-racikan-add" style="width: 100%;">
                                            <option value="">Pilih Obat</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Dosis</label>
                                        <input type="number" class="form-control dosis-racikan-add" placeholder="Dosis" step="0.01" min="0">
                                    </div>
                                    <div class="col-md-2">
                                        <label>Mode</label>
                                        <select class="form-control mode-racikan-add">
                                            <option value="manual">Manual</option>
                                            <option value="tablet">Per Tablet</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-primary btn-sm btn-add-medication btn-block">Tambah</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <label>RACIKAN</label>
                            <select class="form-control select2-wadah-racikan wadah" name="wadah_id">
                                <option value="">Search and select wadah...</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Bungkus</label>
                            <input type="number" class="form-control bungkus">
                        </div>
                        <div class="col-md-6">
                            <label>Aturan Pakai</label>
                            <input type="text" class="form-control aturan_pakai">
                        </div>
                    </div>

                    <button class="btn btn-success btn-block mt-3 tambah-resepracikan">Simpan Racikan ${racikanCount}</button>
                    <button class="btn btn-primary btn-block mt-3 update-resepracikan d-none">Update Racikan</button>
                </div>
            `;

            // Append the new racikan card to the container
            $('#racikan-container').append(racikanCard);

            // Reinitialize select2 for the dynamically added "Nama Obat" field
            $('.select2-obat-racikan').last().select2({
                placeholder: 'Search obat...',
                ajax: {
                    url: '{{ route("obat.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        var metodeBayarId = {{ $visitation->metode_bayar_id ?? 'null' }};
                        return {
                            q: params.term,
                            metode_bayar_id: metodeBayarId,
                            visitation_id: $('#visitation_id').val()
                        };
                    },
                    processResults: function (data) {
                        // Ensure zat aktif and stok_gudang are shown in dropdown
                        if (Array.isArray(data.results)) {
                            return {
                                results: data.results.map(function(item) {
                                    return {
                                        id: item.id,
                                        text: item.text,
                                        nama: item.nama,
                                        dosis: item.dosis,
                                        satuan: item.satuan,
                                        stok: item.stok,
                                        stok_gudang: item.stok_gudang,
                                        harga_nonfornas: item.harga_nonfornas
                                    };
                                })
                            };
                        } else {
                            return {
                                results: data.map(function(item) {
                                    var zatAktif = item.zat_aktif ? ' [' + item.zat_aktif + ']' : '';
                                    return {
                                        id: item.id,
                                        text: item.nama + zatAktif + (item.harga_nonfornas ? ' - ' + item.harga_nonfornas : ''),
                                        nama: item.nama,
                                        dosis: item.dosis,
                                        satuan: item.satuan,
                                        stok: item.stok,
                                        stok_gudang: item.stok_gudang,
                                        harga_nonfornas: item.harga_nonfornas
                                    };
                                })
                            };
                        }
                    },
                    cache: true
                },
                minimumInputLength: 3
            });
            $('.select2-wadah-racikan').last().select2({
                placeholder: 'Search wadah...',
                ajax: {
                    url: '{{ route("wadah.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                            return {
                                q: params.term, // Search term
                                visitation_id: $('#visitation_id').val()
                            };
                    },
                    processResults: function (data) {
                        return {
                            results: data.map(item => ({
                                id: item.id,
                                text: item.text // Use the text property directly from the server response
                            }))
                        };
                    },
                    cache: true
                },
            });
        });
        // TAMBAH OBAT KE RACIKAN
        $('#racikan-container').on('click', '.tambah-obat', function () {
            const card = $(this).closest('.racikan-card');
            const obatSelect = card.find('.select2-obat-racikan');
            const obatId = obatSelect.val();
            const obatText = obatSelect.find('option:selected').text();

            // Retrieve custom data attributes from the selected option
            const selectedOption = obatSelect.select2('data')[0]; // Get the selected option's data
            // Prefer per-gudang stock (`stok_gudang`) when available, otherwise fall back to `stok`
            const stok = (selectedOption && (selectedOption.stok_gudang !== undefined ? parseInt(selectedOption.stok_gudang) : (selectedOption.stok !== undefined ? parseInt(selectedOption.stok) : 0))) || 0;
            const defaultDosis = parseFloat(selectedOption.dosis) || 0; // Ensure it's a number
            const satuan = selectedOption.satuan || ''; // Default to empty string if undefined
            const hargaNonfornas = selectedOption.harga_nonfornas || 0; // Get harga satuan

            const dosisInput = parseFloat(card.find('.dosis_input').val()) || 0; // Ensure it's a number
            const mode = card.find('.mode_dosis').val();

            // Calculate the final dosis
            let dosisAkhir = mode === 'tablet' ? defaultDosis * dosisInput : dosisInput;

            if (!obatId || !dosisInput) {
                alert('Nama obat dan dosis harus diisi.');
                return;
            }

            const tbody = card.find('.resep-table-body');
            tbody.find('.no-data').remove();

            // Calculate harga akhir
            let hargaAkhir = (defaultDosis > 0) ? (dosisAkhir / defaultDosis) * hargaNonfornas : 0;

            // Calculate stokGudang for mapped gudang (prefer stok_gudang then stok)
            const stokGudang = (selectedOption && (selectedOption.stok_gudang !== undefined ? parseInt(selectedOption.stok_gudang) : (selectedOption.stok !== undefined ? parseInt(selectedOption.stok) : 0))) || 0;

            // Check if this medication exists in farmasi racikan prescriptions
            checkIfObatInFarmasiRacikan(obatId, function(existsInFarmasi) {
                const rowClass = existsInFarmasi ? 'text-success row-in-farmasi' : '';
                // Append the new row to the table
                tbody.prepend(`
                    <tr data-obat-id="${obatId}" class="${rowClass}">
                        <td data-id="" data-obat-id="${obatId}">${obatText}</td>
                        <td>${selectedOption.dosis || '-'}</td>
                        <td>${dosisAkhir} ${satuan}</td>
                        <td>${hargaNonfornas}</td>
                        <td>${hargaAkhir}</td>
                        <td style="color: ${(stokGudang < 10) ? 'red' : (stokGudang < 100 ? 'yellow' : 'green')}">${stokGudang}</td>
                        <td>
                            <button class="btn btn-success btn-sm edit-obat disabled" disabled>Edit</button>
                            <button class="btn btn-danger btn-sm hapus-obat disabled" disabled>Hapus</button>
                        </td>
                    </tr>
                `);
                // Clear the form inputs
                card.find('.select2-obat-racikan').val(null).trigger('change');
                card.find('.dosis_input').val('');
            });
        });
        // STORE RACIKAN
        $('#racikan-container').on('click', '.tambah-resepracikan', function () {
            const card = $(this).closest('.racikan-card');
            const racikanKe = card.data('racikan-ke');
            const visitationId = $('#visitation_id').val();
            const wadah = card.find('.wadah').val();
            const bungkus = card.find('.bungkus').val() || card.find('.jumlah_bungkus').val();
            const aturanPakai = card.find('.aturan_pakai').val();

                    // Validate required fields
            if (!bungkus || !aturanPakai) {
                alert('Field "Bungkus" dan "Aturan Pakai" wajib diisi.');
                return;
            }

            const obats = [];
            card.find('.resep-table-body tr').each(function () {
                if (!$(this).hasClass('no-data')) {
                    // Get database id from td[data-id] (if exists)
                    const td = $(this).find('td[data-id]');
                    let dbId = null;
                    if (td.length) {
                        dbId = td.attr('data-id');
                        // If dbId is empty or just obatId, treat as new
                        if (!dbId || dbId === $(this).data('obat-id').toString()) dbId = null;
                    }
                    const obatId = $(this).data('obat-id');
                    const dosis = $(this).find('td').eq(2).text(); // FIX: use td:eq(2) for input dosis
                    if (obatId) {
                        obats.push({ id: dbId || null, obat_id: obatId, dosis: dosis });
                    }
                }
            });

            if (obats.length === 0) {
                alert('Tambahkan minimal satu obat dalam racikan');
                return;
            }

            $.ajax({
                url: "{{ route('resep.racikan.store') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    visitation_id: visitationId,
                    racikan_ke: racikanKe,
                    wadah: wadah,
                    bungkus: bungkus,
                    aturan_pakai: aturanPakai,
                    obats: obats
                },
                success: function (res) {
                    alert(res.message);
                    card.find('.tambah-resepracikan').prop('disabled', true).text('Sudah Disimpan');
                    // Disable fields after successful save
                    card.find('.wadah, .bungkus, .aturan_pakai').prop('disabled', true);
                    updateTotalPrice();
                    
                    // Refresh row colors after saving racikan
                    setTimeout(refreshRowColors, 500);
                }
            });
            });

        // DELETE OBAT
        $('#racikan-container').on('click', '.hapus-obat', function () {
            // Prevent action if button is disabled
            if ($(this).is(':disabled') || $(this).hasClass('disabled')) {
                return;
            }
            // Mark row for deletion and hide it
            const row = $(this).closest('tr');
            row.addClass('obat-deleted').hide();
            const card = $(this).closest('.racikan-card');
            if (card.find('.resep-table-body tr:not(.obat-deleted)').length === 0) {
                card.find('.resep-table-body').append(`<tr class="no-data"><td colspan="4" class="text-center text-muted">Belum ada data</td></tr>`);
            }
            updateTotalPrice(); // Update total harga setelah obat dihapus dari racikan
        });
        // DELETE RACIKAN
        $(document).on('click', '.hapus-racikan', function () {
            const card = $(this).closest('.racikan-card');
            const racikanKe = card.data('racikan-ke');
            const visitationId = $('#visitation_id').val();

            // Cek apakah ada <tr> dengan class 'no data' dalam card
            const isNoData = card.find('tr.no-data').length > 0;

            // Jika ada <tr> dengan class 'no data', langsung dihapus
            if (isNoData) {
                card.remove();
                return;
            }

            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Yakin ingin menghapus racikan ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    // Request untuk menghapus racikan
                    $.ajax({
                        url: "{{ route('resep.racikan.destroy', ':racikanKe') }}".replace(':racikanKe', racikanKe),
                        method: "DELETE",
                        data: {
                            _token: "{{ csrf_token() }}",
                            visitation_id: visitationId,
                        },
                        success: function (res) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: res.message,
                                confirmButtonColor: '#3085d6'
                            });
                            card.remove(); // Hapus card racikan dari tampilan
                            updateTotalPrice(); // Update total harga setelah racikan dihapus
                        },
                        error: function (err) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Menghapus',
                                text: 'Gagal menghapus racikan',
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    });
                }
            });
        })        
         // EDIT NON RACIKAN
        $('#resep-table-body').on('click', '.edit', function() {
            const row = $(this).closest('tr');
            const id    = row.data('id');
            const jumlah = row.find('td').eq(2).text().trim();
            // const rawDiskon = row.find('td').eq(3).text().trim();   
            // const diskonValue = rawDiskon.replace('%', '').trim();
            const aturan = row.find('td').eq(5).text().trim();

            $('#edit-resep-id').val(id);
            $('#edit-jumlah').val(jumlah);
            // $('#edit-diskon').val(diskonValue);
            $('#edit-aturan').val(aturan);
            $('#editResepModal').modal('show');
        });
        // STORE EDIT NON RACIKAN
        $('#edit-resep-form').on('submit', function(e) {
            e.preventDefault();

            const id = $('#edit-resep-id').val();
            const url = "{{ route('resep.nonracikan.update', '') }}/" + id;
            const data = {
                _token: "{{ csrf_token() }}",
                _method: 'PUT',
                jumlah: $('#edit-jumlah').val(),
                // diskon: $('#edit-diskon').val(),
                aturan_pakai: $('#edit-aturan').val()
            };

            $.post(url, data)
            .done(function(res) {
                // Update the table row
                const row = $('#resep-table-body').find('tr[data-id="'+ id +'"]');
                row.find('td').eq(2).text(res.data.jumlah);
                // row.find('td').eq(3).text(res.data.diskon + ' %');
                row.find('td').eq(5).text(res.data.aturan_pakai);
                // Update Harga Akhir column
                 const hargaSatuan = parseFloat(row.find('td').eq(1).text().replace(/[^\d.]/g, '')) || 0;
                row.find('td').eq(4).text((res.data.jumlah * hargaSatuan).toLocaleString('id-ID'));
                $('#editResepModal').modal('hide');
                updateTotalPrice(); // Update total after edit
            })
            .fail(function(xhr) {
                alert('Gagal menyimpan perubahan: ' + xhr.responseJSON.message);
            });
        });
        // EDIT RACIKAN
        $('#racikan-container').on('click', '.edit-racikan', function () {
            const card = $(this).closest('.racikan-card');
            // Enable fields for editing
            card.find('.wadah, .jumlah_bungkus, .bungkus, .aturan_pakai').prop('disabled', false);
            // Enable edit and hapus buttons in this racikan only
            card.find('.edit-obat, .hapus-obat').prop('disabled', false).removeClass('disabled');
            // Hide the save button, show the update button
            card.find('.tambah-resepracikan').addClass('d-none');
            card.find('.update-resepracikan').removeClass('d-none');
        });

        // UPDATE RACIKAN
        $('#racikan-container').on('click', '.update-resepracikan', function () {
            const card = $(this).closest('.racikan-card');
            const racikanKe = card.data('racikan-ke');
            const visitationId = $('#visitation_id').val();
            const wadah = card.find('.wadah').val();
            const bungkus = card.find('.bungkus').val() || card.find('.jumlah_bungkus').val();
            const aturanPakai = card.find('.aturan_pakai').val();

            // Collect remaining obat rows (not deleted)
            const obatRows = card.find('.resep-table-body tr[data-obat-id]').not('.obat-deleted');
            let obats = [];
            obatRows.each(function() {
                const obatId = $(this).data('obat-id');
                // Get database id from td[data-id] (if exists)
                let dbId = null;
                // Find td[data-id] and ensure it is not empty or just obatId
                const td = $(this).find('td[data-id]');
                if (td.length) {
                    dbId = td.attr('data-id');
                    // If dbId is empty or just obatId, treat as new
                    if (!dbId || dbId === obatId.toString()) dbId = null;
                }
                // Get dosis racik from Dosis Racik column (td:eq(2))
                const dosisRacik = $(this).find('td').eq(2).text().trim();
                obats.push({
                    id: dbId || null, // null if new row
                    obat_id: obatId,
                    dosis: dosisRacik
                });
            });

            // Validate required fields
            if (!bungkus || !aturanPakai) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Data Belum Lengkap',
                    text: 'Field "Bungkus" dan "Aturan Pakai" wajib diisi.',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            // Send AJAX request to update the racikan
            $.ajax({
                url: "{{ route('resep.racikan.update', '') }}/" + racikanKe,
                method: "PUT",
                data: {
                    _token: "{{ csrf_token() }}",
                    visitation_id: visitationId,
                    wadah: wadah,
                    bungkus: bungkus,
                    aturan_pakai: aturanPakai,
                    obats: obats
                },
                success: function (res) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message || 'Racikan berhasil diupdate!',
                        confirmButtonColor: '#3085d6'
                    });
                    // Disable fields again
                    card.find('.wadah, .jumlah_bungkus, .bungkus, .aturan_pakai').prop('disabled', true);
                    // Show save button, hide update button
                    card.find('.tambah-resepracikan').removeClass('d-none');
                    card.find('.update-resepracikan').addClass('d-none');
                    // Hide add medication form
                    card.find('.add-medication-form').addClass('d-none');

            card.find('.edit-obat, .hapus-obat').prop('disabled', true).addClass('disabled');
                    // Update total price
                    updateTotalPrice();
                },
                error: function (xhr) {
                    console.log('Update racikan error:', xhr);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Update',
                        text: 'Gagal mengupdate racikan: ' + (xhr.responseJSON?.message || 'Error'),
                        confirmButtonColor: '#3085d6'
                    });
                }
            });
        });

        // MODAL RIWAYAT
        $(document).on('click', '.btn-riwayat', function () {
            console.log('Button clicked'); // Debugging
            let url = $(this).data('url');
            $('#riwayatModal').modal('show');
            $('#riwayatModalContent').html('<p class="text-center">Loading...</p>');

            $.get(url, function (data) {
                $('#riwayatModalContent').html(data);
            }).fail(function () {
                $('#riwayatModalContent').html('<p class="text-center text-danger">Gagal memuat data.</p>');
            });
        });

        // Handle Copy Resep button click
        $(document).on('click', '.btn-copy-resep', function() {
            const sourceVisitationId = $(this).data('visitation-id');
            const sourceType = $(this).data('source');
            const targetVisitationId = $('#visitation_id').val();
            
            if (!confirm(`Yakin ingin menyalin resep ini ke kunjungan saat ini?`)) return;
            
            // Show loading state
            $(this).html('<i class="fas fa-spinner fa-spin"></i> Menyalin...');
            const $button = $(this);
            
            // Send AJAX request to copy prescriptions
            $.ajax({
                url: "{{ route('erm.eresep.copyfromhistory') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    source_visitation_id: sourceVisitationId,
                    target_visitation_id: targetVisitationId,
                    source_type: sourceType
                },
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        location.reload(); // Reload to show the copied prescriptions
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('Gagal menyalin resep. Silakan coba lagi.');
                },
                complete: function() {
                    $button.html('<i class="fas fa-copy"></i> Salin Resep');
                }
            });
        });
        
        updateTotalPrice(); // 

        // AUTO-GENERATE ATURAN PAKAI TEMPLATE (NON RACIKAN)
        function updateAturanPakaiTemplate() {
            const frekuensi = $('#frekuensi').val();
            const dosis = $('#dosis').val();
            const keterangan = $('#keterangan_waktu').val();
            let aturan = '';
            if (frekuensi && dosis) {
                aturan = `${frekuensi} x sehari ${dosis}${keterangan ? ' ' + keterangan : ''}`;
            }
            $('#aturan_pakai').val(aturan);
        }
        $('#frekuensi, #dosis, #keterangan_waktu').on('input', updateAturanPakaiTemplate);
        // AUTO-FILL ATURAN PAKAI ON TAB (RACIKAN, DYNAMIC)
        $(document).on('keydown', '.aturan_pakai', function(e) {
            if (e.key === 'Tab' && !$(this).val()) {
                e.preventDefault();
                $(this).val('1 X Sehari 1');
                this.select();
            }
        });

        // PAKET RACIKAN FUNCTIONALITY
        let obatPaketCount = 0;

        // Open Paket Racikan Modal
        $(document).on('click', '#paket-racikan', function() {
            console.log('Paket Racikan button clicked!');
            
            // Reset form first
            resetFormPaket();
            
            // Load list
            loadPaketRacikanList();
            
            // Show modal
            $('#paketRacikanModal').modal('show');
        });
        
        // Initialize selects when modal is fully shown
        $('#paketRacikanModal').on('shown.bs.modal', function() {
            initializePaketRacikanSelects();
            // ensure any prefilled aturan pakai values are present so select2 shows them
            $('.select2-aturan-pakai').each(function(){
                const v = $(this).val();
                if (v && $(this).find('option[value="'+v+'"]').length === 0) {
                    $(this).append(new Option(v, v, true, true)).trigger('change');
                }
            });
        });

        // Reset button click handler
        $(document).on('click', '#resetFormPaketBtn', function() {
            resetFormPaket();
        });

        // Load Paket Racikan List (supports optional search term and multiple response shapes)
        function loadPaketRacikanList(searchTerm = '') {
            $.ajax({
                url: "{{ route('erm.paket-racikan.list') }}",
                method: 'GET',
                data: { q: searchTerm },
                success: function(response) {
                    let paketList = [];
                    if (!response) {
                        paketList = [];
                    } else if (Array.isArray(response)) {
                        paketList = response;
                    } else if (Array.isArray(response.data)) {
                        paketList = response.data;
                    } else if (response.success && Array.isArray(response.data)) {
                        paketList = response.data;
                    } else if (response.success && Array.isArray(response.pakets)) {
                        paketList = response.pakets;
                    }

                    let tbody = $('#paketRacikanTableBody');
                    tbody.empty();
                    if (paketList.length === 0) {
                        tbody.append('<tr><td colspan="4" class="text-center">Belum ada paket racikan</td></tr>');
                    } else {
                        paketList.forEach(function(paket, index) {
                            const paketJson = JSON.stringify(paket).replace(/'/g, "\\'");
                            let wadahNama = paket.wadah ? paket.wadah.nama : '-';
                            tbody.append(`
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${paket.nama_paket}</td>
                                    <td>${wadahNama}</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary copy-paket" data-id="${paket.id}" data-paket='${paketJson}'>Gunakan</button>
                                        <button class="btn btn-sm btn-info edit-paket" data-paket='${paketJson}'>Edit</button>
                                        <button class="btn btn-sm btn-danger delete-paket" data-id="${paket.id}">Hapus</button>
                                    </td>
                                </tr>
                            `);
                        });
                    }
                },
                error: function() {
                    const tbody = $('#paketRacikanTableBody');
                    tbody.empty();
                    tbody.append('<tr><td colspan="4" class="text-center text-danger">Gagal memuat paket racikan</td></tr>');
                }
            });
        }

        // Simple debounce helper
        function debounce(fn, wait) {
            let t;
            return function() {
                const ctx = this, args = arguments;
                clearTimeout(t);
                t = setTimeout(function(){ fn.apply(ctx, args); }, wait);
            };
        }

        // Hook up search input to reload daftar paket
        $(document).on('input', '#searchPaketRacikan', debounce(function(){
            const term = $(this).val();
            loadPaketRacikanList(term);
        }, 300));

        // Initialize Select2 helper for aturan pakai (shared)
        function initAturanPakaiSelect2(selector, dropdownParent) {
            $(selector).select2({
                width: '100%',
                placeholder: '-- Pilih Template Aturan Pakai --',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: '{{ route('erm.aturan-pakai.list.active') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { q: params.term }; },
                    processResults: function(data) {
                        return { results: (data || []).map(function(item){
                            if (typeof item === 'string') return { id: item, text: item };
                            return { id: item.template || item.id || item, text: item.template || item.name || item };
                        }) };
                    },
                    cache: true
                },
                templateResult: function(item){ return item && item.text ? $('<div>').text(item.text) : item.text; },
                templateSelection: function(item){ return item && item.text ? item.text : item.text; },
                dropdownParent: dropdownParent || undefined,
                escapeMarkup: function(m){ return m; }
            });
        }

        // Initialize Select2 for Paket Racikan
        function initializePaketRacikanSelects() {
            // Destroy existing select2 instances first
            $('.select2-wadah-paket').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });
            
            $('.select2-obat-paket').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });

            // Initialize wadah select2
            $('.select2-wadah-paket').select2({
                placeholder: 'Pilih Wadah',
                allowClear: true,
                ajax: {
                    url: '{{ route("wadah.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term, visitation_id: $('#visitation_id').val() };
                    },
                    processResults: function (data) {
                        return { results: data };
                    },
                    cache: true
                }
            });

            // Initialize obat select2 (ensure dropdownParent so it renders inside modal)
            $('.select2-obat-paket').select2({
                placeholder: 'Pilih Obat',
                allowClear: true,
                ajax: {
                    url: '{{ route("obat.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        if (Array.isArray(data.results)) {
                            return { results: data.results };
                        } else {
                            return { results: data };
                        }
                    },
                    cache: true
                },
                minimumInputLength: 2,
                dropdownParent: $('#paketRacikanModal')
            });

            // Initialize modal aturan pakai selects for create form and modal confirm
            initAturanPakaiSelect2('.select2-aturan-pakai', $('#paketRacikanModal'));
            initAturanPakaiSelect2('#paketAturanPakai', $('#gunakanPaketModal'));
        }

        // Add Obat to Paket
        $('#tambahObatPaket').on('click', function() {
            obatPaketCount++;
            let newObatItem = `
                <div class="obat-paket-item mb-2">
                    <div class="row">
                        <div class="col-md-8">
                            <select class="form-control select2-obat-paket" name="obats[${obatPaketCount}][obat_id]" required>
                                <option value="">Pilih Obat</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="obats[${obatPaketCount}][dosis]" placeholder="Dosis" required>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-sm remove-obat-paket">×</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('#obatPaketContainer').append(newObatItem);
            
            // Initialize select2 for the newly added obat select (ensure dropdownParent)
            let newSelect = $('#obatPaketContainer .select2-obat-paket').last();
            newSelect.select2({
                placeholder: 'Pilih Obat',
                allowClear: true,
                ajax: {
                    url: '{{ route("obat.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return { results: data };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                dropdownParent: $('#paketRacikanModal')
            });
            
            updateRemoveButtons();
        });

        // Remove Obat from Paket
        $(document).on('click', '.remove-obat-paket', function() {
            $(this).closest('.obat-paket-item').remove();
            updateRemoveButtons();
        });

        function updateRemoveButtons() {
            let items = $('.obat-paket-item');
            if (items.length > 1) {
                $('.remove-obat-paket').show();
            } else {
                $('.remove-obat-paket').hide();
            }
        }

        // Save Paket Racikan
        $('#formPaketRacikan').on('submit', function(e) {
            e.preventDefault();
            
            let formData = new FormData(this);
            let obatsData = [];
            
            $('.obat-paket-item').each(function() {
                let obatId = $(this).find('select').val();
                let dosis = $(this).find('input[type="text"]').val();
                
                if (obatId && dosis) {
                    obatsData.push({
                        obat_id: obatId,
                        dosis: dosis
                    });
                }
            });
            
            if (obatsData.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Data Belum Lengkap',
                    text: 'Minimal harus ada satu obat dalam paket',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }
            
            let data = {
                _token: "{{ csrf_token() }}",
                nama_paket: formData.get('nama_paket'),
                deskripsi: formData.get('deskripsi'),
                wadah_id: formData.get('wadah_id'),
                bungkus_default: formData.get('bungkus_default'),
                aturan_pakai_default: formData.get('aturan_pakai_default'),
                obats: obatsData
            };
            
            $.ajax({
                url: "{{ route('erm.paket-racikan.store') }}",
                method: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            confirmButtonColor: '#3085d6'
                        });
                        resetFormPaket();
                        loadPaketRacikanList();
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan',
                        text: 'Gagal menyimpan paket racikan: ' + (xhr.responseJSON?.message || 'Unknown error'),
                        confirmButtonColor: '#3085d6'
                    });
                }
            });
        });

        // Copy Paket Racikan
        $(document).on('click', '.copy-paket', function() {
            let paketId = $(this).data('id');
            let paketData = $(this).data('paket');
            
            // Set data paket ke modal konfirmasi
            $('#paketNamaDisplay').text(paketData.nama_paket);
            $('#paketBungkus').val(paketData.bungkus_default || 10);
            $('#paketAturanPakai').val(paketData.aturan_pakai_default || '');
            $('#selectedPaketId').val(paketId);
            
            // Tutup modal paket racikan dan buka modal konfirmasi
            $('#paketRacikanModal').modal('hide');
            $('#gunakanPaketModal').modal('show');
        });

        // Konfirmasi gunakan paket racikan
        $(document).on('click', '#konfirmasiGunakanPaket', function() {
            let paketId = $('#selectedPaketId').val();
            let bungkus = $('#paketBungkus').val();
            let aturanPakai = $('#paketAturanPakai').val();
            let visitationId = $('#visitation_id').val();
            
            // Validasi input
            if (!bungkus || !aturanPakai) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Data Belum Lengkap',
                    text: 'Bungkus dan Aturan Pakai harus diisi',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }
            
                // Handler untuk kembali ke modal paket racikan jika user batal di modal konfirmasi
            $(this).html('<i class="fas fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
            
            $.ajax({
                url: "{{ route('erm.paket-racikan.copy') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    paket_racikan_id: paketId,
                    visitation_id: visitationId,
                    bungkus: bungkus,
                    aturan_pakai: aturanPakai
                },
                success: function(response) {
                    if (response.success) {
                        // Tandai modal agar tidak buka ulang
                        $('#gunakanPaketModal').addClass('reload-after-close');
                        
                        // Update racikan counter
                        racikanCount = response.racikan_ke;
                        
                        // Dapatkan data paket untuk membuat card
                        // Prefer to read paket data from the copy button itself
                        let paketDataRaw = $('.copy-paket[data-id="' + paketId + '"]').first().data('paket');
                        let paketData = paketDataRaw;
                        try { if (typeof paketDataRaw === 'string') paketData = JSON.parse(paketDataRaw); } catch(e) { paketData = paketDataRaw; }
                        
                        if (paketData) {
                            // Buat racikan card dari data paket dengan bungkus dan aturan pakai custom
                            createRacikanCardFromPaketWithCustomData(paketData, response.racikan_ke, bungkus, aturanPakai);

                            // PATCH: Update stok + set row ids from server response (stok depends on gudang mapping)
                            if (response.obats && Array.isArray(response.obats)) {
                                const createdRacikanKe = response.racikan_ke;
                                const card = $('#racikan-container .racikan-card[data-racikan-ke="' + createdRacikanKe + '"]').last();
                                if (card.length) {
                                    const tbody = card.find('.resep-table-body');
                                    response.obats.forEach(function(ob){
                                        const row = tbody.find('tr[data-obat-id="' + ob.obat_id + '"]').first();
                                        if (!row.length) return;

                                        // set DB row id on the first td[data-id]
                                        const td = row.find('td[data-id]').first();
                                        if (td.length) {
                                            td.attr('data-id', ob.id);
                                        }

                                        // update stok cell (column "Sisa Stok" is index 5)
                                        const stokGudang = parseInt(ob.stok_gudang || 0, 10);
                                        const stokColor = stokGudang < 10 ? 'red' : (stokGudang < 100 ? 'yellow' : 'green');
                                        row.find('td').eq(5).css('color', stokColor).text(stokGudang);
                                    });
                                }
                            }
                            
                            // Update total price
                            updateTotalPrice();
                            
                            // Refresh row colors for new racikan
                            setTimeout(function() {
                                refreshRowColors();
                            }, 500);
                            
                            // Tutup semua modal
                            $('#paketRacikanModal').modal('hide');
                        }
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message || 'Paket racikan berhasil diterapkan!',
                            confirmButtonColor: '#3085d6',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: response.message || 'Gagal menerapkan paket racikan',
                            confirmButtonColor: '#3085d6'
                        });
                    }
                },
                error: function(xhr) {
                    console.error('Error copying paket:', xhr);
                    let errorMessage = 'Gagal menerapkan paket racikan';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: errorMessage,
                        confirmButtonColor: '#3085d6'
                    });
                },
                complete: function() {
                    $('#konfirmasiGunakanPaket').html('OK').prop('disabled', false);
                    $('#gunakanPaketModal').modal('hide');
                }
            });
        });

        // Edit Paket (populate form for editing) - Dokter
        $(document).on('click', '.edit-paket', function() {
            let paketRaw = $(this).attr('data-paket');
            let paket = null;
            try { paket = paketRaw ? JSON.parse(paketRaw) : $(this).data('paket'); } catch(e) { paket = $(this).data('paket'); }
            if (!paket) return;
            resetFormPaket();
            // set paket id for update
            $('#paketId').val(paket.id || '');
            // populate fields
            $('#formPaketRacikan input[name="nama_paket"]').val(paket.nama_paket || '');
            // wadah (select2) - set value then trigger change
            if (paket.wadah && paket.wadah.id) {
                $('.select2-wadah-paket').val(paket.wadah.id).trigger('change');
            } else {
                $('.select2-wadah-paket').val('').trigger('change');
            }
            $('#formPaketRacikan input[name="bungkus_default"]').val(paket.bungkus_default || 10);
            const aturanVal = paket.aturan_pakai_default || '';
            const aturanSelect = $('.select2-aturan-pakai');
            if (aturanSelect.length) {
                if (!aturanSelect.hasClass('select2-hidden-accessible')) {
                    initAturanPakaiSelect2('.select2-aturan-pakai', $('#paketRacikanModal'));
                }
                if (aturanVal && aturanSelect.find('option[value="'+aturanVal+'"]').length === 0) {
                    aturanSelect.append(new Option(aturanVal, aturanVal, true, true));
                }
                aturanSelect.val(aturanVal).trigger('change');
            } else {
                // fallback for legacy input
                $('#formPaketRacikan input[name="aturan_pakai_default"]').val(aturanVal || '');
            }

            // populate obat items
            const container = $('#obatPaketContainer');
            container.empty();
            if (Array.isArray(paket.details) && paket.details.length) {
                paket.details.forEach(function(detail, idx) {
                    const obatId = detail.obat ? detail.obat.id : (detail.obat_id || '');
                    const obatNama = detail.obat ? detail.obat.nama : '';
                    const dosis = detail.dosis || '';
                    container.append(`
                        <div class="obat-paket-item mb-2">
                            <div class="row">
                                <div class="col-md-8">
                                    <select class="form-control select2-obat-paket" name="obats[${idx}][obat_id]" required>
                                        ${obatId ? `<option value="${obatId}" selected>${obatNama}</option>` : '<option value="">Pilih Obat</option>'}
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="obats[${idx}][dosis]" value="${dosis}" placeholder="Dosis" required>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-danger btn-sm remove-obat-paket">×</button>
                                </div>
                            </div>
                        </div>
                    `);
                    obatPaketCount = idx + 1;
                });
            } else {
                // ensure at least one row
                container.html(`
                    <div class="obat-paket-item mb-2">
                        <div class="row">
                            <div class="col-md-8">
                                <select class="form-control select2-obat-paket" name="obats[0][obat_id]" required>
                                    <option value="">Pilih Obat</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="obats[0][dosis]" placeholder="Dosis" required>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-danger btn-sm remove-obat-paket" style="display:none;">×</button>
                            </div>
                        </div>
                    </div>
                `);
                obatPaketCount = 0;
            }
            initializePaketRacikanSelects();
            updateRemoveButtons();
            // show modal (ensure it's visible)
            $('#paketRacikanModal').modal('show');
        });

        // Handler untuk kembali ke modal paket racikan jika user batal di modal konfirmasi
        $('#gunakanPaketModal').on('hidden.bs.modal', function (e) {
            // Jika modal ditutup tanpa konfirmasi, buka kembali modal paket racikan
            if (!$(e.target).hasClass('reload-after-close')) {
                setTimeout(function() {
                    $('#paketRacikanModal').modal('show');
                }, 300);
            }
        });

        // Ensure aturan pakai select2 in konfirmasi modal is initialized and shows prefilled value
        $('#gunakanPaketModal').on('shown.bs.modal', function() {
            if (!$('#paketAturanPakai').hasClass('select2-hidden-accessible')) {
                initAturanPakaiSelect2('#paketAturanPakai', $('#gunakanPaketModal'));
            }
            const v = $('#paketAturanPakai').val();
            if (v && $('#paketAturanPakai').find('option[value="'+v+'"]').length === 0) {
                $('#paketAturanPakai').append(new Option(v, v, true, true)).trigger('change');
            }
        });

        // Function to create racikan card from paket data
        function createRacikanCardFromPaket(paket, racikanKe) {
            let obatRows = '';
            paket.details.forEach(function(detail) {
                // Calculate harga akhir
                let dosisObat = parseFloat(detail.obat.dosis) || 0;
                let dosisRacik = parseFloat(detail.dosis) || 0;
                let hargaSatuan = parseFloat(detail.obat.harga_nonfornas) || 0;
                let hargaAkhir = (dosisObat > 0) ? (dosisRacik / dosisObat) * hargaSatuan : 0;
                
                // Determine stock color - prefer per-gudang stock if available
                let stok = (detail.obat.stok_gudang !== undefined ? detail.obat.stok_gudang : detail.obat.stok) || 0;
                let stockColor = stok < 10 ? 'red' : (stok < 100 ? 'yellow' : 'green');
                
                obatRows += `
                    <tr data-obat-id="${detail.obat.id}">
                        <td>${detail.obat.nama || '-'}</td>
                        <td>${detail.obat.dosis || '-'}</td>
                        <td>${detail.dosis}</td>
                        <td>${hargaSatuan}</td>
                        <td>${hargaAkhir.toFixed(2)}</td>
                        <td style="color: ${stockColor};">${stok}</td>
                        <td><button class="btn btn-danger btn-sm hapus-obat disabled" disabled>Hapus</button></td>
                    </tr>
                `;
            });

            // Calculate total harga racikan
            let totalHargaAkhir = 0;
            paket.details.forEach(function(detail) {
                let dosisObat = parseFloat(detail.obat.dosis) || 0;
                let dosisRacik = parseFloat(detail.dosis) || 0;
                let hargaSatuan = parseFloat(detail.obat.harga_nonfornas) || 0;
                totalHargaAkhir += (dosisObat > 0) ? (dosisRacik / dosisObat) * hargaSatuan : 0;
            });
            let hargaRacikan = totalHargaAkhir * paket.bungkus_default;

            let racikanCard = `
                <div class="racikan-card mb-4 p-3 border rounded" data-racikan-ke="${racikanKe}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5><strong>Racikan ${racikanKe}
                            <span style="color: #00ff99; font-size: 1rem; font-weight: normal;">
                                (Rp. ${new Intl.NumberFormat('id-ID').format(hargaRacikan)})
                            </span>
                        </strong></h5>
                        <div>
                            <button class="btn btn-warning btn-sm edit-racikan mr-2">Edit Racikan</button>
                            <button class="btn btn-danger btn-sm hapus-racikan">Hapus Racikan</button>
                        </div>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nama Obat</th>
                                <th>Dosis Obat</th>
                                <th>Dosis Racik</th>
                                <th>Harga Satuan</th>
                                <th>Harga Akhir</th>
                                <th>Sisa Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="resep-table-body">
                            ${obatRows}
                        </tbody>
                    </table>

                    <!-- Form untuk menambah obat baru ke racikan (hidden by default) -->
                    <div class="add-medication-form d-none mb-3">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Tambah Obat ke Racikan ${racikanKe}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Nama Obat</label>
                                        <select class="form-control select2-obat-racikan-add" style="width: 100%;">
                                            <option value="">Pilih Obat</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Dosis</label>
                                        <input type="number" class="form-control dosis-racikan-add" placeholder="Dosis" step="0.01" min="0">
                                    </div>
                                    <div class="col-md-2">
                                        <label>Mode</label>
                                        <select class="form-control mode-racikan-add">
                                            <option value="manual">Manual</option>
                                            <option value="tablet">Per Tablet</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-primary btn-sm btn-add-medication btn-block">Tambah</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <label>RACIKAN</label>
                            <select class="form-control select2-wadah-racikan wadah" name="wadah_id" disabled>
                                <option value="${paket.wadah_id || ''}">${paket.wadah ? paket.wadah.nama : 'Pilih Wadah'}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Bungkus</label>
                            <input type="number" class="form-control jumlah_bungkus bungkus" value="${paket.bungkus_default}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label>Aturan Pakai</label>
                            <input type="text" class="form-control aturan_pakai" value="${paket.aturan_pakai_default || ''}" disabled>
                        </div>
                    </div>

                    <button class="btn btn-success btn-block mt-3 tambah-resepracikan" disabled>Sudah Disimpan</button>
                    <button class="btn btn-primary btn-block mt-3 update-resepracikan d-none">Update Racikan</button>
                </div>
            `;

            // Append to racikan container
            $('#racikan-container').append(racikanCard);
            
            // Initialize select2 for the new wadah select
            $('#racikan-container .select2-wadah-racikan').last().select2({
                placeholder: 'Search wadah...',
                ajax: {
                    url: '{{ route("wadah.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return { results: data };
                    },
                    cache: true
                }
            });
        }

        // Function to create racikan card from paket data with custom bungkus and aturan pakai
        function createRacikanCardFromPaketWithCustomData(paket, racikanKe, customBungkus, customAturanPakai) {
            let obatRows = '';
            paket.details.forEach(function(detail) {
                // Calculate harga akhir
                let dosisObat = parseFloat(detail.obat.dosis) || 0;
                let dosisRacik = parseFloat(detail.dosis) || 0;
                let hargaSatuan = parseFloat(detail.obat.harga_nonfornas) || 0;
                let hargaAkhir = (dosisObat > 0) ? (dosisRacik / dosisObat) * hargaSatuan : 0;
                
                // Determine stock color - prefer per-gudang stock if available
                let stok = (detail.obat.stok_gudang !== undefined ? detail.obat.stok_gudang : detail.obat.stok) || 0;
                let stockColor = stok < 10 ? 'red' : (stok < 100 ? 'yellow' : 'green');
                
                obatRows += `
                    <tr data-obat-id="${detail.obat.id}">
                        <td data-id="" data-obat-id="${detail.obat.id}">${detail.obat.nama || '-'}</td>
                        <td>${detail.obat.dosis || '-'}</td>
                        <td>${detail.dosis}</td>
                        <td>${new Intl.NumberFormat('id-ID').format(hargaSatuan)}</td>
                        <td>${new Intl.NumberFormat('id-ID').format(hargaAkhir)}</td>
                        <td style="color: ${stockColor};">${stok}</td>
                        <td><button class="btn btn-danger btn-sm hapus-obat disabled" disabled>Hapus</button></td>
                    </tr>
                `;
            });

            // Calculate total harga racikan with custom bungkus
            let totalHargaAkhir = 0;
            paket.details.forEach(function(detail) {
                let dosisObat = parseFloat(detail.obat.dosis) || 0;
                let dosisRacik = parseFloat(detail.dosis) || 0;
                let hargaSatuan = parseFloat(detail.obat.harga_nonfornas) || 0;
                totalHargaAkhir += (dosisObat > 0) ? (dosisRacik / dosisObat) * hargaSatuan : 0;
            });
            let hargaRacikan = totalHargaAkhir * customBungkus;

            let racikanCard = `
                <div class="racikan-card mb-4 p-3 border rounded" data-racikan-ke="${racikanKe}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5><strong>Racikan ${racikanKe}
                            <span style="color: #00ff99; font-size: 1rem; font-weight: normal;">
                                (Rp. ${new Intl.NumberFormat('id-ID').format(hargaRacikan)})
                            </span>
                        </strong></h5>
                        <div>
                            <button class="btn btn-warning btn-sm edit-racikan mr-2">Edit Racikan</button>
                            <button class="btn btn-danger btn-sm hapus-racikan">Hapus Racikan</button>
                        </div>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nama Obat</th>
                                <th>Dosis Obat</th>
                                <th>Dosis Racik</th>
                                <th>Harga Satuan</th>
                                <th>Harga Akhir</th>
                                <th>Sisa Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="resep-table-body">
                            ${obatRows}
                        </tbody>
                    </table>

                    <!-- Form untuk menambah obat baru ke racikan (hidden by default) -->
                    <div class="add-medication-form d-none mb-3">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Tambah Obat ke Racikan ${racikanKe}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Nama Obat</label>
                                        <select class="form-control select2-obat-racikan-add" style="width: 100%;">
                                            <option value="">Pilih Obat</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Dosis</label>
                                        <input type="number" class="form-control dosis-racikan-add" placeholder="Dosis" step="0.01" min="0">
                                    </div>
                                    <div class="col-md-2">
                                        <label>Mode</label>
                                        <select class="form-control mode-racikan-add">
                                            <option value="manual">Manual</option>
                                            <option value="tablet">Per Tablet</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-primary btn-sm btn-add-medication btn-block">Tambah</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <label>RACIKAN</label>
                            <select class="form-control select2-wadah-racikan wadah" name="wadah_id" disabled>
                                <option value="${paket.wadah ? paket.wadah.id : ''}" selected>${paket.wadah ? paket.wadah.nama : 'Pilih Wadah'}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Bungkus</label>
                            <input type="number" class="form-control jumlah_bungkus bungkus" value="${customBungkus}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label>Aturan Pakai</label>
                            <input type="text" class="form-control aturan_pakai" value="${customAturanPakai}" disabled>
                        </div>
                    </div>

                    <button class="btn btn-success btn-block mt-3 tambah-resepracikan" disabled>Sudah Disimpan</button>
                    <button class="btn btn-primary btn-block mt-3 update-resepracikan d-none">Update Racikan</button>
                </div>
            `;

            // Append to racikan container
            $('#racikan-container').append(racikanCard);
            
            // Initialize select2 for the new wadah select
            $('#racikan-container .select2-wadah-racikan').last().select2({
                placeholder: 'Search wadah...',
                ajax: {
                    url: '{{ route("wadah.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return { results: data };
                    },
                    cache: true
                }
            });
        }

        // Show Detail Paket
        $(document).on('click', '.detail-paket', function() {
            let paket = $(this).data('paket');
            let content = `
                <h6><strong>${paket.nama_paket}</strong></h6>
                <p><strong>Deskripsi:</strong> ${paket.deskripsi || '-'}</p>
                <p><strong>Wadah:</strong> ${paket.wadah ? paket.wadah.nama : '-'}</p>
                <p><strong>Bungkus Default:</strong> ${paket.bungkus_default}</p>
                <p><strong>Aturan Pakai Default:</strong> ${paket.aturan_pakai_default || '-'}</p>
                
                <h6><strong>Obat dalam Paket:</strong></h6>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Obat</th>
                            <th>Dosis</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            paket.details.forEach(function(detail, index) {
                content += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${detail.obat.nama}</td>
                        <td>${detail.dosis}</td>
                    </tr>
                `;
            });
            
            content += `
                    </tbody>
                </table>
            `;
            
            $('#detailPaketContent').html(content);
            $('#detailPaketModal').modal('show');
        });

        // Delete Paket Racikan
        $(document).on('click', '.delete-paket', function() {
            let paketId = $(this).data('id');
            
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Yakin ingin menghapus paket racikan ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: "{{ route('erm.paket-racikan.delete', '') }}/" + paketId,
                        method: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: response.message,
                                    confirmButtonColor: '#3085d6'
                                });
                                loadPaketRacikanList();
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Menghapus',
                                text: 'Gagal menghapus paket racikan: ' + (xhr.responseJSON?.message || 'Unknown error'),
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    });
                }
            });
        });

        // Reset Form Paket
        function resetFormPaket() {
            // Destroy select2 instances first
            $('.select2-wadah-paket').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });
            
            $('.select2-obat-paket').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });
            
            // Reset form
            $('#formPaketRacikan')[0].reset();
            
            // Reset obat container
            $('#obatPaketContainer').html(`
                <div class="obat-paket-item mb-2">
                    <div class="row">
                        <div class="col-md-8">
                            <select class="form-control select2-obat-paket" name="obats[0][obat_id]" required>
                                <option value="">Pilih Obat</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="obats[0][dosis]" placeholder="Dosis" required>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-sm remove-obat-paket" style="display:none;">×</button>
                        </div>
                    </div>
                </div>
            `);
            
            // Reset counter
            obatPaketCount = 0;
            
            // Re-initialize select2
            initializePaketRacikanSelects();
        }
    });

    // DEBUG: Global handler for edit-racikan to ensure it always works
    $(document).on('click', '.edit-racikan', function () {
        console.log('Edit racikan clicked!'); // Debug log
        const card = $(this).closest('.racikan-card');
        console.log('Found card:', card.length); // Debug log
        
        // Enable all possible field classes to handle different naming conventions in the HTML
        card.find('.wadah, .jumlah_bungkus, .bungkus, .aturan_pakai').prop('disabled', false);
        card.find('.tambah-resepracikan').addClass('d-none');
        card.find('.update-resepracikan').removeClass('d-none');
        // Enable hapus-obat buttons only in this racikan card
        card.find('.hapus-obat').prop('disabled', false).removeClass('disabled');
        // Disable hapus-obat in other racikan cards
        $('#racikan-container .racikan-card').not(card).find('.hapus-obat').prop('disabled', true).addClass('disabled');
        
        // Show the add medication form during edit mode
        const addForm = card.find('.add-medication-form');
        console.log('Found add medication form:', addForm.length); // Debug log
        addForm.removeClass('d-none');
        
        // Initialize Select2 for the medication dropdown in edit form if not already initialized
        const select = card.find('.select2-obat-racikan-add');
        console.log('Found select:', select.length); // Debug log
        if (!select.hasClass('select2-hidden-accessible')) {
            console.log('Initializing Select2...'); // Debug log
            select.select2({
                placeholder: 'Pilih Obat',
                allowClear: true,
                minimumInputLength: 3,
                ajax: {
                    url: '{{ route("obat.search") }}',
                    type: 'GET',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            visitation_id: $('#visitation_id').val()
                        };
                    },
                    processResults: function (data) {
                        // If your endpoint returns {results: [...]}, use that
                        if (Array.isArray(data.results)) {
                            return {
                                results: data.results.map(function(item) {
                                    return {
                                        id: item.id,
                                        text: `${item.nama} ${item.dosis} ${item.satuan}`,
                                        nama: item.nama,
                                        dosis: item.dosis,
                                        satuan: item.satuan,
                                        harga_nonfornas: item.harga_nonfornas,
                                        stok: item.stok
                                    };
                                })
                            };
                        } else {
                            // fallback for array response
                            return {
                                results: data.map(function(item) {
                                    return {
                                        id: item.id,
                                        text: `${item.nama} ${item.dosis} ${item.satuan}`,
                                        nama: item.nama,
                                        dosis: item.dosis,
                                        satuan: item.satuan,
                                        harga_nonfornas: item.harga_nonfornas,
                                        stok: item.stok
                                    };
                                })
                            };
                        }
                    },
                    cache: true
                },
                escapeMarkup: function (markup) {
                    return markup;
                },
                templateResult: function (data) {
                    return data.text;
                },
                templateSelection: function (data) {
                    return data.text;
                }
            });
        }
    });

    // Handle Add Medication button click in edit mode
    $(document).on('click', '.btn-add-medication', function () {
        console.log('Add medication button clicked!'); // Debug log
        const card = $(this).closest('.racikan-card');
        const racikanKe = card.data('racikan-ke');
        const obatSelect = card.find('.select2-obat-racikan-add');
        const dosisInput = card.find('.dosis-racikan-add');
        const modeSelect = card.find('.mode-racikan-add');
        
        console.log('Form elements found:', {
            card: card.length,
            obatSelect: obatSelect.length,
            dosisInput: dosisInput.length,
            modeSelect: modeSelect.length
        }); // Debug log
        
        const obatId = obatSelect.val();
        const obatData = obatSelect.select2('data')[0]; // Get full obat data
        const dosis = dosisInput.val();
        const mode = modeSelect.val();
        
        console.log('Form values:', { obatId, obatData, dosis, mode }); // Debug log
        
        // Validation
        if (!obatId || !dosis || !mode) {
            Swal.fire({
                icon: 'warning',
                title: 'Data Belum Lengkap',
                text: 'Semua field wajib diisi.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        // Check if obat already exists in this racikan
        let obatExists = false;
        card.find('.resep-table-body tr[data-obat-id]').each(function() {
            if ($(this).data('obat-id') == obatId && !$(this).hasClass('obat-deleted')) {
                obatExists = true;
                return false; // break loop
            }
        });
        
        if (obatExists) {
            Swal.fire({
                icon: 'warning',
                title: 'Obat Sudah Ada',
                text: 'Obat ini sudah ada dalam racikan ini.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        // Calculate harga akhir using data from select2
        let dosisObat = parseFloat(obatData.dosis) || 0;
        let dosisRacik = parseFloat(dosis) || 0;
        let hargaSatuan = parseFloat(obatData.harga_nonfornas) || 0;
        let hargaAkhir = (dosisObat > 0) ? (dosisRacik / dosisObat) * hargaSatuan : 0;
        
        // Determine stock color - prefer per-gudang stock (`stok_gudang`) returned by select2
        let stok = (obatData && (obatData.stok_gudang !== undefined ? obatData.stok_gudang : obatData.stok)) || 0;
        let stockColor = stok < 10 ? 'red' : (stok < 100 ? 'yellow' : 'green');
        
        // Add new row to the table
        const newRow = `
            <tr data-obat-id="${obatId}" class="new-medication-row">
                <td data-id="" data-obat-id="${obatId}">${obatData.nama || obatData.text}</td>
                <td>${obatData.dosis || '-'}</td>
                <td>${dosis}</td>
                <td>${new Intl.NumberFormat('id-ID').format(hargaSatuan)}</td>
                <td>${new Intl.NumberFormat('id-ID').format(hargaAkhir)}</td>
                <td style="color: ${stockColor};">${stok}</td>
                <td>
                    <button class="btn btn-success btn-sm edit-obat disabled" disabled title="Edit">Edit</button>
                    <button class="btn btn-danger btn-sm hapus-obat" title="Hapus">Hapus</button>
                </td>
            </tr>
        `;
        
        card.find('.resep-table-body').append(newRow);
        
        // Clear the form
        obatSelect.val(null).trigger('change');
        dosisInput.val('');
        modeSelect.val('');
        
        // Update total price
        updateTotalPrice();
        
        // Show success message
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: 'Obat berhasil ditambahkan ke racikan.',
            timer: 1500,
            showConfirmButton: false
        });
    });

    // Pasien Keluar button handler
    $('#pasien-keluar-btn').on('click', function() {
        const pasienId = $(this).data('pasien-id');
        const pasienName = $(this).data('pasien-name');
        
        Swal.fire({
            title: 'Konfirmasi',
            text: `Apakah Resep pasien ${pasienName} sudah bisa diproses farmasi?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.value) {
                // Send notification to farmasi users
                $.ajax({
                    url: '{{ route("erm.notify.pasien.keluar") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        pasien_id: pasienId,
                        pasien_name: pasienName
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: 'Notifikasi telah dikirim ke farmasi',
                                icon: 'success',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#3085d6'
                            }).then((result) => {
                                if (result.value) {
                                    window.close();
                                }
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Gagal mengirim notifikasi',
                            icon: 'error'
                        });
                        console.error('Failed to send notification:', error);
                    }
                });
            }
        });
    });
</script>
@endsection
