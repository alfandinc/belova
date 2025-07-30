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
        }al: Add background color for better visibility */
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
                        <h4 id="total-harga" style="margin: 0; color: white;"><strong>0</strong></h4>
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
                    <h5>Dokter Input : {{ auth()->user()->name }}</h5>
                    <textarea class="form-control" id="catatan_dokter" placeholder="Tuliskan catatan disini ..." rows="3">{{ $catatan_dokter ?? ($catatan_resep ?? '') }}</textarea>
                    <div class="mt-2">
                        <button class="btn btn-success btn-sm" id="simpan-catatan">Simpan Catatan</button>
                    </div>
                </div>

                <!-- NON RACIKAN -->
                <h5 style="color: yellow;"><strong>Resep Non Racikan</strong></h5>
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
                                <input type="number" min="1" id="dosis" class="form-control" placeholder="Dosis (misal: 1)">
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

                    <table class="table table-bordered" style="color: white;">
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
                                    <td style="color: {{ ($resep->obat->stok ?? 0) < 10 ? 'red' : (($resep->obat->stok ?? 0) < 100 ? 'yellow' : 'green') }};">
                                        {{ $resep->obat->stok ?? 0 }}
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
                <h5 style="color: yellow;"><strong>Resep Racikan</strong></h5>
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
                                <span class="racikan-harga-detail" style="color: #ffc107; font-size: 1rem; font-weight: normal; margin-left: 10px;"></span>
                            </strong></h5>
                            <div>
                                <button class="btn btn-warning btn-sm edit-racikan mr-2">Edit Racikan</button>
                                <button class="btn btn-danger btn-sm hapus-racikan">Hapus Racikan</button>
                            </div>
                        </div>

                        <table class="table table-bordered text-white">
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
                                        <td>{{ $resep->dosis }}</td>
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
                                        <td style="color: {{ ($resep->obat->stok ?? 0) < 10 ? 'red' : (($resep->obat->stok ?? 0) < 100 ? 'yellow' : 'green') }};">
                                            {{ $resep->obat->stok ?? 0 }}
                                        </td>
                                        <td><button class="btn btn-danger btn-sm hapus-obat">Hapus</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

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
        // Always disable hapus-obat buttons in racikan on page load
        $('#racikan-container .hapus-obat').prop('disabled', true).addClass('disabled');

        $('.select2').select2({ width: '100%' });
        $('.select2-obat').select2({
            placeholder: 'Search obat...',
            ajax: {
                url: '{{ route("obat.search") }}', // Define this route in your controller
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
                            text: `${item.nama} ${item.dosis} ${item.satuan}`
                        }))
                    };
                },
                cache: true
            },
            minimumInputLength: 3
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

        // Function to refresh row colors based on farmasi prescriptions
        function refreshRowColors() {
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
                        $('#resep-table-body').append(`
                            <tr data-id="${res.data.id}" data-obat-id="${res.data.obat_id}" class="${rowClass}">
                                <td>${res.data.obat.nama}</td>
                                <td>${res.data.obat.harga_nonfornas || 0}</td>
                                <td>${res.data.jumlah}</td>
                                
                                <td>${res.data.obat.stok || 0}</td>
                                <td>${(res.data.obat.harga_nonfornas || 0) * (res.data.jumlah || 0)}</td>
                                <td>${res.data.aturan_pakai}</td>
                                <td>
                                    <button class="btn btn-success btn-sm edit" data-id="${res.data.id}">Edit</button>
                                    <button class="btn btn-danger btn-sm hapus" data-id="${res.data.id}">Hapus</button>
    // Disable hapus-obat buttons in racikan by default on page load
    $('#racikan-container .hapus-obat').prop('disabled', true).addClass('disabled');
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

                    <table class="table table-bordered text-white">
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
                    url: '{{ route("obat.search") }}', // Define this route in your controller
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
                                text: `${item.nama} ${item.dosis} ${item.satuan}`,
                                stok: item.stok, // Include stok in the data
                                dosis: item.dosis, // Include dosis in the data
                                satuan: item.satuan, // Include satuan in the data
                                harga_nonfornas: item.harga_nonfornas // Ensure harga_nonfornas is included!
                            }))
                        };
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
                            q: params.term // Search term
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
            const stok = selectedOption.stok || 0; // Default to 0 if undefined
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

            // Check if this medication exists in farmasi racikan prescriptions
            checkIfObatInFarmasiRacikan(obatId, function(existsInFarmasi) {
                const rowClass = existsInFarmasi ? 'text-success row-in-farmasi' : '';
                // Append the new row to the table
                tbody.append(`
                    <tr data-obat-id="${obatId}" class="${rowClass}">
                        <td data-id="${obatId}">${obatText}</td>
                        <td>${selectedOption.dosis || '-'}</td>
                        <td>${dosisAkhir} ${satuan}</td>
                        <td>${hargaNonfornas}</td>
                        <td>${hargaAkhir}</td>
                        <td style="color: ${(stok < 10) ? 'red' : (stok < 100 ? 'yellow' : 'green')}">${stok}</td>
                        <td><button class="btn btn-danger btn-sm hapus-obat disabled" disabled>Hapus</button></td>
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
                    const obatId = $(this).find('td').eq(0).data('id');
                    const dosis = $(this).find('td').eq(2).text(); // FIX: use td:eq(2) for input dosis
                    if (obatId) {
                        obats.push({ obat_id: obatId, dosis: dosis });
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
                const dosis = $(this).find('.dosis').val();
                const jumlah = $(this).find('.jumlah').val();
                obats.push({
                    obat_id: obatId,
                    dosis: dosis,
                    jumlah: jumlah
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
                    // Disable hapus-obat buttons again after update
                    card.find('.hapus-obat').prop('disabled', true).addClass('disabled');
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
        });

        // Reset button click handler
        $(document).on('click', '#resetFormPaketBtn', function() {
            resetFormPaket();
        });

        // Load Paket Racikan List
        function loadPaketRacikanList() {
            console.log('Loading paket racikan list...');
            $.ajax({
                url: "{{ route('erm.paket-racikan.list') }}",
                method: 'GET',
                success: function(response) {
                    console.log('Paket racikan list loaded:', response);
                    if (response.success) {
                        let tbody = $('#paketRacikanTableBody');
                        tbody.empty();
                        
                        if (response.data.length === 0) {
                            tbody.append('<tr><td colspan="4" class="text-center">Belum ada paket racikan</td></tr>');
                        } else {
                            response.data.forEach(function(paket, index) {
                                let wadahNama = paket.wadah ? paket.wadah.nama : '-';
                                
                                tbody.append(`
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${paket.nama_paket}</td>
                                        <td>${wadahNama}</td>
                                        <td>
                                            <button class="btn btn-sm btn-primary copy-paket" data-id="${paket.id}">Gunakan</button>
                                            <button class="btn btn-sm btn-info detail-paket" data-paket='${JSON.stringify(paket)}'>Detail</button>
                                            <button class="btn btn-sm btn-danger delete-paket" data-id="${paket.id}">Hapus</button>
                                        </td>
                                    </tr>
                                `);
                            });
                        }
                    }
                },
                error: function(xhr) {
                    console.error('Error loading paket racikan:', xhr);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memuat Data',
                        text: 'Gagal memuat daftar paket racikan: ' + (xhr.responseJSON?.message || xhr.statusText),
                        confirmButtonColor: '#3085d6'
                    });
                }
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
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return { results: data };
                    },
                    cache: true
                }
            });

            // Initialize obat select2
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
                        return { results: data };
                    },
                    cache: true
                },
                minimumInputLength: 2
            });
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
            
            // Initialize select2 for the newly added obat select
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
                minimumInputLength: 2
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
            let visitationId = $('#visitation_id').val();
            
            Swal.fire({
                title: 'Konfirmasi',
                text: 'Yakin ingin menggunakan paket racikan ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Gunakan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    // Show loading state
                    $(this).html('<i class="fas fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
                    
                    $.ajax({
                        url: "{{ route('erm.paket-racikan.copy') }}",
                        method: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            paket_racikan_id: paketId,
                            visitation_id: visitationId
                        },
                        success: function(response) {
                            if (response.success) {
                                // Update racikan counter
                                racikanCount = response.racikan_ke;
                                
                                // Get the paket data to build the card
                                let paket = null;
                                $('.copy-paket[data-id="' + paketId + '"]').closest('tr').find('.detail-paket').each(function() {
                                    paket = $(this).data('paket');
                                });
                                
                                if (paket) {
                                    // Create the racikan card HTML
                                    createRacikanCardFromPaket(paket, response.racikan_ke);
                                }
                                
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: response.message,
                                    confirmButtonColor: '#3085d6'
                                });
                                $('#paketRacikanModal').modal('hide');
                                
                                // Update total price
                                updateTotalPrice();
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: 'Gagal menggunakan paket racikan: ' + (xhr.responseJSON?.message || 'Unknown error'),
                                confirmButtonColor: '#3085d6'
                            });
                        },
                        complete: function() {
                            // Reset button state
                            $('.copy-paket').html('Gunakan').prop('disabled', false);
                        }
                    });
                }
            });
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
                
                // Determine stock color
                let stok = detail.obat.stok || 0;
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
                totalHargaAkhir += (dosisObat > 0) ? (dosisRacik / dosisObat) * $hargaSatuan : 0;
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

                    <table class="table table-bordered text-white">
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
        const card = $(this).closest('.racikan-card');
        // Enable all possible field classes to handle different naming conventions in the HTML
        card.find('.wadah, .jumlah_bungkus, .bungkus, .aturan_pakai').prop('disabled', false);
        card.find('.tambah-resepracikan').addClass('d-none');
        card.find('.update-resepracikan').removeClass('d-none');
        // Enable hapus-obat buttons only in this racikan card
        card.find('.hapus-obat').prop('disabled', false).removeClass('disabled');
        // Disable hapus-obat in other racikan cards
        $('#racikan-container .racikan-card').not(card).find('.hapus-obat').prop('disabled', true).addClass('disabled');
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
