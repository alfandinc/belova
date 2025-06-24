@extends('layouts.erm.app')
@section('title', 'E-Resep')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection
@section('content')

@include('erm.partials.modal-alergipasien')
@include('erm.partials.modal-resephistory')

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
                        
                         <button class="btn btn-warning btn-sm">Paket Racikan</button>
                         <button class="btn btn-danger btn-sm" onclick="window.close()">Keluar</button>
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
                            <input type="text" id="aturan_pakai" class="form-control">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button id="tambah-resep" class="btn btn-primary btn-block">Tambah</button>
                        </div>
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
                                <tr data-id="{{ $resep->id }}">
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
                
                <div id="racikan-container">
                    @foreach ($racikans as $ke => $items)
                    <div class="racikan-card mb-4 p-3 border rounded" data-racikan-ke="{{ $ke }}">
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 style=""><strong>Racikan {{ $ke }}
                                <span style="color: #00ff99; font-size: 1rem; font-weight: normal;">
                                    @php
                                        $bungkus = (float)($items->first()->bungkus ?? 1);
                                        $totalHargaAkhir = $items->sum(function($resep) {
                                            $dosisObat = (float)($resep->obat->dosis ?? 0);
                                            $dosisRacik = (float)($resep->dosis ?? 0);
                                            $hargaSatuan = (float)($resep->obat->harga_nonfornas ?? 0);
                                            return ($dosisObat > 0) ? ($dosisRacik / $dosisObat) * $hargaSatuan : 0;
                                        });
                                        $hargaRacikan = $totalHargaAkhir * $bungkus;
                                    @endphp
                                    (Rp. {{ number_format($hargaRacikan, 0, ',', '.') }})
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
                                @foreach ($items as $resep)
                                    <tr>
                                       
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

    $(document).ready(function () {
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

                    // Append the new row to the table
                    $('#resep-table-body').append(`
                        <tr data-id="${res.data.id}">
                            <td>${res.data.obat.nama}</td>
                            <td>${res.data.obat.harga_nonfornas || 0}</td>
                            <td>${res.data.jumlah}</td>
                            
                            <td>${res.data.obat.stok || 0}</td>
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

            if (!confirm('Yakin ingin menghapus resep ini?')) return;

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
                },
                error: function () {
                    alert('Gagal menghapus resep. Coba lagi.');
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
                    let dosisObat = parseFloat($(this).find('td').eq(1).text()) || 0;
                    let dosisRacik = parseFloat($(this).find('td').eq(2).text()) || 0;
                    let hargaSatuan = parseFloat($(this).find('td').eq(3).text()) || 0;
                    let hargaAkhir = 0;
                    if (dosisObat > 0) {
                        hargaAkhir = (dosisRacik / dosisObat) * hargaSatuan;
                    }
                    racikanTotal += hargaAkhir;
                });
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
                        <h5 style="color: yellow;"><strong>Racikan ${racikanCount}</strong></h5>
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

            // Append the new row to the table
            tbody.append(`
                <tr>
                    <td data-id="${obatId}">${obatText}</td>
                    <td>${selectedOption.dosis || '-'}</td>
                    <td>${dosisAkhir} ${satuan}</td>
                    <td>${hargaNonfornas}</td>
                    <td>${hargaAkhir}</td>
                    <td style="color: ${(stok < 10) ? 'red' : (stok < 100 ? 'yellow' : 'green')}">${stok}</td>
                    <td><button class="btn btn-danger btn-sm hapus-obat">Hapus</button></td>
                </tr>
            `);
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
                    const obatId = $(this).find('td').eq(0).data('id'); // sesuaikan jika perlu
                    const dosis = $(this).find('td').eq(1).text();
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
                }
            });
            });

        // DELETE OBAT
        $('#racikan-container').on('click', '.hapus-obat', function () {
            $(this).closest('tr').remove();
            const card = $(this).closest('.racikan-card');
            if (card.find('.resep-table-body tr').length === 0) {
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

            if (!confirm('Yakin ingin menghapus resep ini?')) return;

            // Request untuk menghapus racikan
            $.ajax({
                url: "{{ route('resep.racikan.destroy', ':racikanKe') }}".replace(':racikanKe', racikanKe),
                method: "DELETE",
                data: {
                    _token: "{{ csrf_token() }}",
                    visitation_id: visitationId,
                },
                success: function (res) {
                    alert(res.message); // Notifikasi
                    card.remove(); // Hapus card racikan dari tampilan
                    updateTotalPrice(); // Update total harga setelah racikan dihapus
                },
                error: function (err) {
                    alert('Gagal menghapus racikan');
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
            
            console.log('Update racikan - racikanKe:', racikanKe);
            console.log('Update racikan - bungkus:', bungkus);
            console.log('Update racikan - aturanPakai:', aturanPakai);
            
            // Validate required fields
            if (!bungkus || !aturanPakai) {
                alert('Field "Bungkus" dan "Aturan Pakai" wajib diisi.');
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
                    aturan_pakai: aturanPakai
                },
                success: function (res) {
                    alert(res.message || 'Racikan berhasil diupdate!');
                    // Disable fields again
                    card.find('.wadah, .jumlah_bungkus, .bungkus, .aturan_pakai').prop('disabled', true);
                    // Show save button, hide update button
                    card.find('.tambah-resepracikan').removeClass('d-none');
                    card.find('.update-resepracikan').addClass('d-none');
                    // Update total price
                    updateTotalPrice();
                },
                error: function (xhr) {
                    console.log('Update racikan error:', xhr);
                    alert('Gagal mengupdate racikan: ' + (xhr.responseJSON?.message || 'Error'));
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

        // AUTO-FILL ATURAN PAKAI ON TAB (NON RACIKAN)
        $('#aturan_pakai').on('keydown', function(e) {
            if (e.key === 'Tab' && !$(this).val()) {
                e.preventDefault();
                $(this).val('1 X Sehari 1');
                this.select();
            }
        });
        // AUTO-FILL ATURAN PAKAI ON TAB (RACIKAN, DYNAMIC)
        $(document).on('keydown', '.aturan_pakai', function(e) {
            if (e.key === 'Tab' && !$(this).val()) {
                e.preventDefault();
                $(this).val('1 X Sehari 1');
                this.select();
            }
        });
    });

    // DEBUG: Global handler for edit-racikan to ensure it always works
    $(document).on('click', '.edit-racikan', function () {
        const card = $(this).closest('.racikan-card');
        // Enable all possible field classes to handle different naming conventions in the HTML
        card.find('.wadah, .jumlah_bungkus, .bungkus, .aturan_pakai').prop('disabled', false);
        card.find('.tambah-resepracikan').addClass('d-none');
        card.find('.update-resepracikan').removeClass('d-none');
        console.log('Edit racikan clicked - fields enabled');
    });
</script>
@endsection
