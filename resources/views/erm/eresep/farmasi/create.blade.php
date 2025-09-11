@extends('layouts.erm.app')
@section('title', 'E-Resep Farmasi')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection
@section('content')

@include('erm.partials.modal-alergipasien')
@include('erm.partials.modal-resephistory')
@include('erm.partials.modal-resepedukasi')

@include('erm.partials.modal-editnonracikan-farmasi')

<!-- Modal Edit Dosis Racikan Obat -->
<div class="modal fade" id="editDosisModal" tabindex="-1" role="dialog" aria-labelledby="editDosisModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editDosisModalLabel">Edit Dosis Obat Racikan</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="edit-dosis-form">
          <input type="hidden" id="edit-dosis-row-id">
          <div class="form-group">
            <label for="edit-dosis-value">Dosis</label>
            <input type="text" class="form-control" id="edit-dosis-value" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="save-dosis-btn">Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Etiket Biru -->
<div class="modal fade" id="etiketBiruModal" tabindex="-1" role="dialog" aria-labelledby="etiketBiruModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="etiketBiruModalLabel">Etiket Biru</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="etiket-biru-form">
          <input type="hidden" id="etiket-visitation-id" value="{{ $visitation->id }}">
          
          <div class="form-group">
            <label for="etiket-pasien">Pasien</label>
            <input type="text" class="form-control" id="etiket-pasien" value="{{ $pasien->nama }}" readonly>
          </div>
          
          <div class="form-group">
            <label for="etiket-obat">Obat</label>
            <select class="form-control select2-etiket-obat" id="etiket-obat" required>
              <option value="">Pilih Obat...</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="etiket-expire">Tanggal Kedaluwarsa</label>
            <input type="date" class="form-control" id="etiket-expire" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="print-etiket-biru-btn">Print Etiket Biru</button>
      </div>
    </div>
  </div>
</div>

<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">E-Resep Farmasi Pasien</h3>
        <input type="hidden" id="visitation_id" value="{{ $visitation->id }}">
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Farmasi</li>
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
            <div class="container">                    <div class="d-flex justify-content-between align-items-center mb-3">
                    <div style="display: flex; align-items: center;">
                        <h4 style="margin: 0;">Total Harga: <strong>Rp.</strong></h4>
                        <h4 id="total-harga" style="margin: 0; "><strong>0</strong></h4>
                        
                    </div>
                   
                    <div class="mb-3">
                        <button class="btn btn-primary btn-sm btn-cetakresep" >Cetak Resep</button>
                        <button class="btn btn-primary btn-sm btn-cetakedukasi" >Cetak Edukasi</button>
                        <button class="btn btn-primary btn-sm btn-cetaketiket" >Cetak Etiket</button>
                        <button class="btn btn-primary btn-sm btn-cetaketiketbiru" data-toggle="modal" data-target="#etiketBiruModal">Etiket Biru</button>
                        <button class="btn btn-sm btn-info btn-riwayat" data-url="{{ route('resep.historydokter', $pasien->id) }}">
                            Riwayat Dokter
                        </button>

                        <button class="btn btn-sm btn-info btn-riwayat" data-url="{{ route('resep.historyfarmasi', $pasien->id) }}">
                            Riwayat Farmasi
                        </button>
                        <button id="submit-all" class="btn btn-success btn-sm">Submit Resep</button>
                        {{-- <button class="btn btn-danger btn-sm" onclick="window.close()">Keluar</button> --}}
                    </div>
                </div>

                <div class="mb-3">
                    <h5>Catatan Dokter :</h5>
                    <textarea readonly class="form-control" rows="3" style="font-size: 1.5rem; font-weight: bold; color: red;">{{ $catatan_resep ?? '' }}</textarea>
                </div>
                 @if (!$nonRacikans->count() && !$racikans->count())
                    <div class="alert alert-info" id="empty-resep-message">
                        Belum ada data dari dokter. Anda bisa menambahkan resep baru atau salin dari dokter.
                    </div>
                @endif
<div id="resep-wrapper">
                <!-- NON RACIKAN -->
                <h5 style="color: blue;"><strong>Resep Non Racikan</strong></h5>
                <div class="racikan-card mb-4 p-3 border rounded">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label>Nama Obat</label>
                            <select class="form-control select2-obat" name="obat_id" id="obat_id">
                                <option value="">Search and select an obat...</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label>Jumlah</label>
                            <input type="number" id="jumlah" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-4">
                            <label>Aturan Pakai</label>
                            <input type="text" id="aturan_pakai" class="form-control" placeholder="Tulisakan Aturan Pakai...">
                        </div>
                        <div class="col-md-1">
                            <label for="diskon">Disc (%)</label>
                            <input type="number" class="form-control" id="diskon"  placeholder="0" min="0" max="100">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button id="tambah-resep" class="btn btn-primary btn-block">Tambah</button>
                        </div>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nama Obat</th>
                                <th>Jumlah</th>
                                <th>Harga</th>
                                <th>Disc</th>
                                
                                <th>Stok</th>
                                <th>Aturan Pakai</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="resep-table-body">
                            @forelse ($nonRacikans as $resep)
                                <tr data-id="{{ $resep->id }}">
                                    <td>{{ $resep->obat->nama ?? '-' }}</td>
                                    <td>{{ $resep->jumlah }}</td>
                                    <td>Rp. {{ $resep->obat->harga_nonfornas ?? 0 }}</td>
                                    <td>{{ $resep->diskon ?? '0'}} %</td>
                                    
                                    @php
                                        $gudangId = \App\Models\ERM\GudangMapping::getDefaultGudangId('resep');
                                        $stokGudang = $gudangId ? $resep->obat->getStokByGudang($gudangId) : 0;
                                    @endphp
                                    <td style="color: {{ ($stokGudang < 10 ? 'red' : ($stokGudang < 100 ? 'yellow' : 'green')) }};">
                                        {{ (int) $stokGudang }}
                                    </td>
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
                <h5 style="color: blue;"><strong>Resep Racikan</strong></h5>
                
                <div id="racikan-container">
                    @foreach ($racikans as $ke => $items)
                    <div class="racikan-card mb-4 p-3 border rounded" data-racikan-ke="{{ $ke }}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 style="color: blue;"><strong>Racikan {{ $ke }}</strong></h5>
                            <div>
                                <button class="btn btn-warning btn-sm edit-racikan mr-2">Edit Racikan</button>
                                <button class="btn btn-danger btn-sm hapus-racikan">Hapus Racikan</button>
                            </div>
                        </div>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nama Obat</th>
                                    <th>Dosis</th>
                                    <th>Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="resep-table-body">
                                @foreach ($items as $resep)
                                    <tr data-id="{{ $resep->id }}" data-obat-id="{{ $resep->obat_id }}" data-dosis="{{ $resep->dosis }}" data-jumlah="{{ $resep->jumlah }}">
                                        <td>{{ $resep->obat->nama ?? '-' }}</td>
                                        <td>{{ $resep->dosis }}</td>
                                        @php
                                            $gudangId = \App\Models\ERM\GudangMapping::getDefaultGudangId('resep');
                                            $stokGudang = $gudangId ? $resep->obat->getStokByGudang($gudangId) : 0;
                                        @endphp
                                        <td style="color: {{ ($stokGudang < 10 ? 'red' : ($stokGudang < 100 ? 'yellow' : 'green')) }};">
                                            {{ (int) $stokGudang }}
                                        </td>
                                        <td>
                                            <button class="btn btn-warning btn-sm edit-obat" disabled>Edit</button>
                                            <button class="btn btn-danger btn-sm hapus-obat" disabled>Hapus</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="row">
                            <div class="col-md-3">
                                <label>Wadah</label>
                                <select class="form-control select2-wadah-racikan wadah" name="wadah_id">
                                <option value="{{ $items->first()?->wadah?->id ?? '' }}">
                                    {{ $items->first()?->wadah?->nama ?? 'Pilih Wadah' }}
                                </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Bungkus</label>
                                <input type="number" class="form-control jumlah_bungkus" value="{{ $items->first()->bungkus }}">
                            </div>
                            <div class="col-md-6">
                                <label>Aturan Pakai</label>
                                <input type="text" class="form-control aturan_pakai" value="{{ $items->first()->aturan_pakai }}">
                            </div>
                        </div>
                        <button class="btn btn-success btn-block mt-3 tambah-resepracikan" disabled>Sudah Disimpan</button>
                        <button class="btn btn-primary btn-block mt-3 update-resepracikan d-none">Update</button>
                    </div>
                    @endforeach
                </div>
                <button id="tambah-racikan" class="btn btn-primary mb-3">Tambah Racikan</button>
</div>  

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
            width: '100%',
            minimumInputLength: 3,
            placeholder: 'Search obat...',
            ajax: {
                url: '{{ route("obat.search") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    // If your endpoint returns {results: [...]}, use that
                    if (Array.isArray(data.results)) {
                        return {
                            results: data.results.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.text,
                                    harga: item.harga_nonfornas,
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
                                    text: item.nama + (item.harga_nonfornas ? ' - ' + item.harga_nonfornas : ''),
                                    harga: item.harga_nonfornas,
                                    stok: item.stok
                                };
                            })
                        };
                    }
                },
                cache: true
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

        // STORE NON RACIKAN
        $('#tambah-resep').on('click', function () {
            let obatId = $('#obat_id').val();
            let obatText = $('#obat_id option:selected').text();
            let jumlah = $('#jumlah').val();
            // let harga = $('#obat_id option:selected').data('harga');
            // Debug the selected data
    let selectedData = $('#obat_id').select2('data')[0];
    console.log("Selected data:", selectedData);
    
    // Make sure harga is properly formatted as a number
    let harga = selectedData && selectedData.harga ? parseFloat(selectedData.harga) : null;
    console.log("Harga value:", harga);

            let stok = $('#obat_id option:selected').data('stok');
            let aturanPakai = $('#aturan_pakai').val();
            let diskon = $('#diskon').val() || 0;
            let visitationId = $('#visitation_id').val();  // Pastikan id yang digunakan sama
            

            if (!obatId || !jumlah || !aturanPakai) return Swal.fire('Peringatan', "Semua field wajib diisi.", "warning");

            // Kirim data via AJAX
            $.ajax({
                url: "{{ route('resepfarmasi.nonracikan.store') }}", // disesuaikan nanti
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    tipe: "nonracikan",
                    obat_id: obatId,
                    jumlah: jumlah,
                    harga: harga,
                    diskon: diskon,
                    aturan_pakai: aturanPakai,
                    visitation_id: visitationId 
                },
                success: function (res) {
                    // const resep = res.data;
                    $('#resep-table-body .no-data').remove();
                    const stokGudang = res.data.obat.stok_gudang !== undefined ? parseInt(res.data.obat.stok_gudang) : 0;
                    const stokColor = stokGudang < 10 ? 'red' : (stokGudang < 100 ? 'yellow' : 'green');
                    $('#resep-table-body').append(`
                        <tr data-id="${res.data.id}">
                            <td>${res.data.obat.nama}</td>
                            <td>${res.data.jumlah}</td>
                            <td>${res.data.obat.harga_nonfornas}</td>
                            <td>${res.data.diskon} %</td>                           
                            <td style="color: ${stokColor};">${stokGudang}</td>
                            <td>${res.data.aturan_pakai}</td>
                            <td>
                                <button class="btn btn-success btn-sm edit" data-id="${res.data.id}">Edit</button>
                                <button class="btn btn-danger btn-sm hapus" data-id="${res.data.id}">Hapus</button>
                            </td>
                        </tr>
                    `);
                    updateTotalPrice();

                    // Clear the input fields
                    $('#obat_id').val(null).trigger('change');
                    $('#jumlah').val('');
                    $('#aturan_pakai').val('');
                },
                error: function (xhr) {
                    Swal.fire('Error', 'Gagal menambahkan resep: ' + xhr.responseJSON.message, 'error');
                }
            });
        });

        //DELETE NON RACIKAN
        $('#resep-table-body').on('click', '.hapus', function () {
            const row = $(this).closest('tr');
            const resepId = row.data('id');

            Swal.fire({
                title: 'Konfirmasi',
                text: 'Yakin ingin menghapus resep ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    deleteNonRacikan();
                }
            });
            
            function deleteNonRacikan() {
                $.ajax({
                    url: "{{ route('resepfarmasi.nonracikan.destroy', '') }}/" + resepId,
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
                    Swal.fire('Error', 'Gagal menghapus resep. Coba lagi.', 'error');
                }
            });
            }
        });

        // UPDATE TOTAL HARGA
        function updateTotalPrice() {
    let total = 0;

    // Iterate through each row in the table body
    $('#resep-table-body tr').each(function () {
        const harga = parseFloat($(this).find('td').eq(2).text().replace('Rp. ', '').replace(',', '').trim()) || 0; // Extract and parse the price
        const jumlah = parseInt($(this).find('td').eq(1).text().trim()) || 0; // Extract and parse the quantity
        const diskon = parseFloat($(this).find('td').eq(3).text().replace('%', '').trim()) || 0; // Extract and parse the discount

        // Calculate the discounted price
        const discountedPrice = harga * jumlah * (1 - diskon / 100);
        total += discountedPrice;
    });

    // Update the total price display
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
                            <label>Satuan Dosis</label>
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
                                <th>Dosis</th>
                                <th>Stok</th>
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
                            <label>Racikan</label>
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
                            // Support both {results: [...]} and [...] response
                            let items = Array.isArray(data.results) ? data.results : data;
                            return {
                                results: items.map(function(item) {
                                    return {
                                        id: item.id,
                                        text: item.text || (item.nama + (item.harga_nonfornas ? ' - ' + item.harga_nonfornas : '')),
                                        nama: item.nama,
                                        dosis: item.dosis,
                                        satuan: item.satuan,
                                        stok: item.stok,
                                        stok_gudang: typeof item.stok_gudang !== 'undefined' ? item.stok_gudang : item.stok,
                                        harga_nonfornas: item.harga_nonfornas
                                    };
                                })
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
        // UPDATE RACIKAN
        $('#racikan-container').on('click', '.update-resepracikan', function () {
            const card = $(this).closest('.racikan-card');
            const racikanKe = card.data('racikan-ke');
            const visitationId = $('#visitation_id').val();
            const wadah = card.find('.wadah').val();
            
            // Find bungkus value - check both possible class names
            const bungkus = card.find('.bungkus').val() || card.find('.jumlah_bungkus').val();
            
            const aturanPakai = card.find('.aturan_pakai').val();
            
            console.log('Debug - racikanKe:', racikanKe);
            console.log('Debug - bungkus:', bungkus);
            console.log('Debug - aturanPakai:', aturanPakai);
            
            // Validate required fields
            if (!bungkus || !aturanPakai) {
                Swal.fire('Peringatan', 'Jumlah bungkus dan aturan pakai harus diisi!', 'warning');
                return;
            }
            
            // Store the original racikanKe before any updates
            const originalRacikanKe = racikanKe;
            
            console.log('Original racikanKe before update:', originalRacikanKe);
            
            if (originalRacikanKe === 0 || originalRacikanKe === '0') {
                Swal.fire('Error', 'Invalid racikan ID (0). Cannot update this racikan.', 'error');
                return;
            }
            
            // Collect all obat rows in this racikan
            const obats = [];
            card.find('.resep-table-body tr').each(function () {
                const row = $(this);
                if (!row.hasClass('no-data')) {
                    // Try to get id from data-id attribute, fallback to td data-id if needed
                    let id = row.data('id');
                    if (!id) {
                        id = row.find('td').eq(0).data('id');
                    }
                    obats.push({
                        id: id,
                        obat_id: row.data('obat-id') || row.find('td').eq(0).data('id'),
                        dosis: row.data('dosis') || row.find('td').eq(1).text(),
                        jumlah: row.data('jumlah') || 1
                    });
                }
            });

            // Send AJAX request to update the racikan
            $.ajax({
                url: `/erm/resepfarmasi/racikan/${originalRacikanKe}`,
                type: 'POST',
                data: {
                    _method: 'PUT',
                    visitation_id: visitationId,
                    wadah: wadah,
                    bungkus: bungkus,
                    aturan_pakai: aturanPakai,
                    obats: obats,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Sukses', response.message, 'success');
                        card.find('.wadah, .bungkus, .jumlah_bungkus, .aturan_pakai').prop('disabled', true);
                        card.find('.update-resepracikan').addClass('d-none');
                        card.find('.tambah-resepracikan').removeClass('d-none');
                        card.find('.hapus-obat').prop('disabled', true);
                        card.find('.jumlah_bungkus, .bungkus').val(bungkus);
                        card.find('.aturan_pakai').val(aturanPakai);
                        card.attr('data-racikan-ke', originalRacikanKe);
                    } else {
                        Swal.fire('Error', 'Terjadi kesalahan: ' + response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Update failed:', xhr, status, error);
                    Swal.fire('Error', 'Gagal menyimpan perubahan: ' + (xhr.responseJSON ? xhr.responseJSON.message : xhr.responseText), 'error');
                }
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
            // Use stok_gudang if available, fallback to stok
            const stokGudang = typeof selectedOption.stok_gudang !== 'undefined' ? parseInt(selectedOption.stok_gudang) : (selectedOption.stok || 0);
            const stokColor = stokGudang < 10 ? 'red' : (stokGudang < 100 ? 'yellow' : 'green');
            const defaultDosis = parseFloat(selectedOption.dosis) || 0; // Ensure it's a number
            const satuan = selectedOption.satuan || ''; // Default to empty string if undefined

            const dosisInput = parseFloat(card.find('.dosis_input').val()) || 0; // Ensure it's a number
            const mode = card.find('.mode_dosis').val();

            // Calculate the final dosis
            let dosisAkhir = mode === 'tablet' ? defaultDosis * dosisInput : dosisInput;

            if (!obatId || !dosisInput) {
                Swal.fire('Peringatan', 'Nama obat dan dosis harus diisi.', 'warning');
                return;
            }

            const tbody = card.find('.resep-table-body');
            tbody.find('.no-data').remove();

            // Append the new row to the table
            tbody.append(`
                <tr data-id="" data-obat-id="${obatId}" data-dosis="${dosisAkhir}" data-jumlah="1">
                    <td data-id="${obatId}">${obatText}</td>
                    <td>${dosisAkhir} ${satuan}</td>
                    <td><span style="color: ${stokColor};">${stokGudang}</span></td>
                    <td><button class="btn btn-danger btn-sm hapus-obat" disabled>Hapus</button></td>
                </tr>
            `);
        });
        // STORE RACIKAN
        $('#racikan-container').on('click', '.tambah-resepracikan', function () {
            const card = $(this).closest('.racikan-card');
            const racikanKe = card.data('racikan-ke');
            const visitationId = $('#visitation_id').val();
            const wadah = card.find('.wadah').val();
            const bungkus = card.find('.bungkus').val();
            const aturanPakai = card.find('.aturan_pakai').val();

                     // Validate required fields
            if (!bungkus || !aturanPakai) {
                Swal.fire('Peringatan', 'Field "Bungkus" dan "Aturan Pakai" wajib diisi.', 'warning');
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
                Swal.fire('Peringatan', 'Tambahkan minimal satu obat dalam racikan', 'warning');
                return;
            }

            $.ajax({
                url: "{{ route('resepfarmasi.racikan.store') }}",
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
                    Swal.fire('Sukses', res.message, 'success');
                    card.find('.tambah-resepracikan').prop('disabled', true).text('Disimpan');
                    // Ensure Update button is present but hidden
                    if (card.find('.update-resepracikan').length === 0) {
                        card.append('<button class="btn btn-primary btn-block mt-3 update-resepracikan d-none">Update</button>');
                    } else {
                        card.find('.update-resepracikan').addClass('d-none');
                    }
                        // PATCH: Update each obat row with the correct data-id and stok_gudang from backend
                        if (res.obats && Array.isArray(res.obats)) {
                            let rows = card.find('.resep-table-body tr');
                            res.obats.forEach(function(obat, idx) {
                                let row = rows.eq(idx);
                                row.attr('data-id', obat.id);
                                row.attr('data-obat-id', obat.obat_id);
                                row.attr('data-dosis', obat.dosis);
                                row.attr('data-jumlah', obat.jumlah || 1);
                                // Update stok column and color
                                let stokGudang = typeof obat.stok_gudang !== 'undefined' ? parseInt(obat.stok_gudang) : 0;
                                let stokColor = stokGudang < 10 ? 'red' : (stokGudang < 100 ? 'yellow' : 'green');
                                row.find('td').eq(2).html(`<span style="color: ${stokColor};">${stokGudang}</span>`);
                            });
                        }
                }
            });
            });

        // EDIT OBAT RACIKAN (open modal)
        $('#racikan-container').on('click', '.edit-obat', function () {
            const row = $(this).closest('tr');
            const dosis = row.data('dosis');
            $('#edit-dosis-row-id').val(row.index()); // use row index for identification
            $('#edit-dosis-value').val(dosis);
            $('#editDosisModal').modal('show');
        });

        // SAVE DOSIS FROM MODAL
        $('#save-dosis-btn').on('click', function () {
            const rowIndex = $('#edit-dosis-row-id').val();
            const newDosis = $('#edit-dosis-value').val();
            if (!newDosis) {
                Swal.fire('Peringatan', 'Dosis tidak boleh kosong!', 'warning');
                return;
            }
            // Find the row by index and update data + cell
            const row = $('.racikan-card .resep-table-body tr').eq(rowIndex);
            row.data('dosis', newDosis);
            row.find('td').eq(1).text(newDosis); // update cell display
            $('#editDosisModal').modal('hide');
        });

        // DELETE OBAT DARI RACIKAN
        $('#racikan-container').on('click', '.hapus-obat', function () {
            $(this).closest('tr').remove();
            const card = $(this).closest('.racikan-card');
            if (card.find('.resep-table-body tr').length === 0) {
                card.find('.resep-table-body').append(`<tr class="no-data"><td colspan="4" class="text-center text-muted">Belum ada data</td></tr>`);
            }
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
                title: 'Konfirmasi',
                text: 'Yakin ingin menghapus racikan ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    deleteRacikan();
                }
            });
            
            function deleteRacikan() {
                // Request untuk menghapus racikan
                $.ajax({
                    url: "{{ route('resepfarmasi.racikan.destroy', ':racikanKe') }}".replace(':racikanKe', racikanKe),
                    method: "DELETE",
                    data: {
                        _token: "{{ csrf_token() }}",
                        visitation_id: visitationId,
                    },
                success: function (res) {
                    Swal.fire('Sukses', res.message, 'success'); // Notifikasi
                    card.remove(); // Hapus card racikan dari tampilan
                },
                error: function (err) {
                    Swal.fire('Error', 'Gagal menghapus racikan', 'error');
                }
                });
            }
        });
        // EDIT NON RACIKAN
        $('#resep-table-body').on('click', '.edit', function() {
            const row = $(this).closest('tr');
            const id    = row.data('id');
            const jumlah = row.find('td').eq(1).text().trim();
            const rawDiskon = row.find('td').eq(3).text().trim();  // e.g. "10 %"
            const diskonValue = rawDiskon.replace('%', '').trim(); // "10"
            const aturan = row.find('td').eq(5).text().trim();

            $('#edit-resep-id').val(id);
            $('#edit-jumlah').val(jumlah);
            $('#edit-diskon').val(diskonValue);
            $('#edit-aturan').val(aturan);
            $('#editResepModal').modal('show');
        });

        // STORE EDIT NON RACIKAN
        $('#edit-resep-form').on('submit', function(e) {
            e.preventDefault();

            const id = $('#edit-resep-id').val();
            const url = "{{ route('resepfarmasi.nonracikan.update', '') }}/" + id;
            const data = {
                _token: "{{ csrf_token() }}",
                _method: 'PUT',
                jumlah: $('#edit-jumlah').val(),
                diskon: $('#edit-diskon').val(),
                aturan_pakai: $('#edit-aturan').val()
            };

            $.post(url, data)
            .done(function(res) {
                // Update the table row
                const row = $('#resep-table-body').find('tr[data-id="'+ id +'"]');
                row.find('td').eq(1).text(res.data.jumlah);
                row.find('td').eq(3).text(res.data.diskon + ' %');
                row.find('td').eq(5).text(res.data.aturan_pakai);

                $('#editResepModal').modal('hide');
            })
            .fail(function(xhr) {
                Swal.fire('Error', 'Gagal menyimpan perubahan: ' + xhr.responseJSON.message, 'error');
            });
        });

        // SUBMIT KE BILLING
        $('#submit-all').on('click', function () {
            const $btn = $(this);
            const visitationId = $('#visitation_id').val();
            
            // Check if already submitted
            const isSubmitted = $btn.hasClass('btn-secondary');
            
            if (isSubmitted) {
                Swal.fire({
                    title: 'Konfirmasi',
                    text: 'Resep sudah pernah disubmit. Yakin ingin submit ulang?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Submit Ulang!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.value) {
                        doSubmit(true); // Force resubmit
                    }
                });
            } else {
                Swal.fire({
                    title: 'Konfirmasi',
                    text: 'Yakin ingin submit resep ke billing? Stok akan dikurangi saat pembayaran.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Submit!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.value) {
                        doSubmit(false);
                    }
                });
            }
            
            function doSubmit(force = false) {
                $btn.prop('disabled', true).text('Memproses...');
                
                $.ajax({
                    url: "{{ route('resepfarmasi.submit') }}",
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        visitation_id: visitationId,
                        force: force ? 1 : 0
                    },
                    success: function (res) {
                        if (res.status === 'warning' && res.need_confirm) {
                            Swal.fire({
                                title: 'Konfirmasi',
                                text: res.message,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Ya, Submit!',
                                cancelButtonText: 'Batal'
                            }).then((result) => {
                                if (result.value) {
                                    doSubmit(true); // Resubmit with force
                                } else {
                                    resetButton();
                                }
                            });
                        } else if (res.status === 'success') {
                            Swal.fire('Sukses', res.message, 'success');
                            $btn.text('Sudah Disubmit').addClass('btn-secondary').removeClass('btn-success').prop('disabled', false);
                        } else if (res.status === 'error') {
                            Swal.fire('Error', res.message, 'error');
                            resetButton();
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Gagal submit resep. Coba lagi.', 'error');
                        resetButton();
                    }
                });
                
                function resetButton() {
                    $btn.prop('disabled', false);
                    if (isSubmitted) {
                        $btn.text('Sudah Disubmit').addClass('btn-secondary').removeClass('btn-success');
                    } else {
                        $btn.text('Submit Resep').addClass('btn-success').removeClass('btn-secondary');
                    }
                }
            }
        });




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
            
            // Show SweetAlert confirmation
            Swal.fire({
                title: 'Konfirmasi',
                text: 'Yakin ingin menyalin resep ini ke kunjungan saat ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Salin!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    copyResep();
                }
            });
            
            function copyResep() {
                // Show loading state
                const $button = $('.btn-copy-resep');
                $button.html('<i class="fas fa-spinner fa-spin"></i> Menyalin...');
                
                // Send AJAX request to copy prescriptions
                $.ajax({
                url: "{{ route('erm.eresepfarmasi.copyfromhistory') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    source_visitation_id: sourceVisitationId,
                    target_visitation_id: targetVisitationId,
                    source_type: sourceType
                },
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            title: 'Sukses',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload(); // Reload to show the copied prescriptions
                        });
                    } else {
                        Swal.fire('Peringatan', response.message, 'warning');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Gagal menyalin resep. Silakan coba lagi.', 'error');
                },
                complete: function() {
                    $button.html('<i class="fas fa-copy"></i> Salin Resep');
                }
            });
            } // End of copyResep function
        });

        $('.btn-cetakresep').on('click', function() {
            const visitationId = $('#visitation_id').val();
            // Open the print route in a new tab
            window.open(`/erm/eresepfarmasi/${visitationId}/print`, '_blank');
        });

        $('.btn-cetaketiket').on('click', function() {
            const visitationId = $('#visitation_id').val();
            // Open the etiket print route in a new tab
            window.open(`/erm/eresepfarmasi/${visitationId}/print-etiket`, '_blank');
        });

        // Initialize Select2 for Etiket Biru modal
        $('#etiketBiruModal').on('shown.bs.modal', function () {
            $('.select2-etiket-obat').select2({
                width: '100%',
                placeholder: 'Pilih Obat...',
                dropdownParent: $('#etiketBiruModal'),
                ajax: {
                    url: '{{ route("erm.eresepfarmasi.get-visitation-obat", ":visitationId") }}'.replace(':visitationId', $('#visitation_id').val()),
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return {
                            results: data.map(function(item) {
                                return {
                                    id: item.obat_id,
                                    text: item.obat_nama + (item.racikan_ke ? ' (Racikan ' + item.racikan_ke + ')' : '')
                                };
                            })
                        };
                    },
                    cache: true
                }
            });
        });

        // Handle Print Etiket Biru button click
        $('#print-etiket-biru-btn').on('click', function() {
            const visitationId = $('#etiket-visitation-id').val();
            const obatId = $('#etiket-obat').val();
            const expireDate = $('#etiket-expire').val();
            
            if (!obatId || !expireDate) {
                Swal.fire('Peringatan', 'Semua field wajib diisi.', 'warning');
                return;
            }

            // Create form data
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('visitation_id', visitationId);
            formData.append('obat_id', obatId);
            formData.append('expire_date', expireDate);

            // Submit form to generate PDF
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/erm/eresepfarmasi/etiket-biru/print';
            form.target = '_blank';
            
            // Add CSRF token
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = '{{ csrf_token() }}';
            form.appendChild(tokenInput);
            
            // Add other fields
            for (let [key, value] of formData.entries()) {
                if (key !== '_token') {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                }
            }
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
            
            // Close modal
            $('#etiketBiruModal').modal('hide');
        });
           
        updateTotalPrice(); // <--- Tambahkan ini
    
    });

    function fetchFarmasiResep() {
    const visitationId = $('#visitation_id').val();
    console.log('Fetching resep data for visitation:', visitationId);

    $.get(`/erm/eresepfarmasi/${visitationId}/json`, function (res) {
        $('#resep-wrapper').show();
        $('#empty-resep-message').hide();

        // ==== NON RACIKAN ====
        const tbody = $('#resep-table-body');
        tbody.empty();

        res.non_racikans.forEach(item => {
            tbody.append(`
                <tr data-id="${item.id}">
                    <td>${item.obat?.nama ?? '-'}</td>
                    <td>${item.jumlah}</td>
                    <td>${item.obat?.harga_nonfornas ?? 0}</td>
                    <td>${item.diskon ?? 0}</td>                   
                    <td>${item.obat?.stok ?? 0}</td>
                    <td>${item.aturan_pakai}</td>
                    <td>
                        <button class="btn btn-success btn-sm edit" data-id="${item.id}">Edit</button>
                        <button class="btn btn-danger btn-sm hapus" data-id="${item.id}">Hapus</button>
                    </td>
                </tr>
            `);
        });

        // ==== RACIKAN ====
        const racikanWrapper = $('#racikan-container');
        racikanWrapper.empty(); // clear old data
        let racikanCount = 0;
        
        console.log('Racikan data from server:', res.racikans);

        Object.entries(res.racikans).forEach(([ke, items]) => {
            console.log(`Processing racikan with ke=${ke}, items:`, items);
            
            if (ke === '0' || ke === 0) {
                console.warn('WARNING: Found racikan with key 0, this will cause update problems!');
            }
            
            const wadah = items[0].wadah ?? '';
            const bungkus = items[0].bungkus ?? '';
            const aturan = items[0].aturan_pakai ?? '';

            const rows = items.map(item => {
                // Ensure dosis always has the unit displayed
                const dosis = item.dosis;
                
                return `
                    <tr>
                        <td data-id="${item.id}">${item.obat?.nama ?? '-'}</td>
                        <td>${dosis}</td>
                        <td>${item.obat?.stok ?? 0}</td>
<td><button class="btn btn-danger btn-sm hapus-obat" disabled>Hapus</button></td>
                    </tr>`;
            }).join('');

            racikanWrapper.append(`
                <div class="racikan-card mb-4 p-3 border rounded" data-racikan-ke="${ke}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 style="color: yellow;"><strong>Racikan ${ke}</strong></h5>
                        <div>
                            <button class="btn btn-warning btn-sm edit-racikan mr-2">Edit Racikan</button>
                            <button class="btn btn-danger btn-sm hapus-racikan">Hapus Racikan</button>
                        </div>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nama Obat</th>
                                <th>Dosis</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="resep-table-body">
                            ${rows}
                        </tbody>
                    </table>

                    <div class="row">
                        <div class="col-md-3">
                            <label>Racikan</label>
                            <select class="form-control wadah" disabled>
                                ${['Kapsul', 'Ampul', 'Botol', 'Sachet'].map(opt => `
                                    <option value="${opt}" ${opt === wadah ? 'selected' : ''}>${opt}</option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Bungkus</label>
                            <input type="number" class="form-control jumlah_bungkus" value="${bungkus}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label>Aturan Pakai</label>
                            <input type="text" class="form-control aturan_pakai" value="${aturan}" disabled>
                        </div>
                    </div>

                    <button class="btn btn-success btn-block mt-3 tambah-resepracikan" disabled>Sudah Disimpan</button>
                    <button class="btn btn-primary btn-block mt-3 update-resepracikan d-none">Update</button>
                </div>
            `);

            racikanCount++;
        });
    });
}

// DEBUG: Global handler for edit-racikan to ensure it always works
$(document).on('click', '.edit-racikan', function () {
    const card = $(this).closest('.racikan-card');
    // Enable all possible field classes to handle different naming conventions in the HTML
    card.find('.wadah, .jumlah_bungkus, .bungkus, .aturan_pakai').prop('disabled', false);
    card.find('.tambah-resepracikan').addClass('d-none');
    // Always show and enable Update button
    card.find('.update-resepracikan').removeClass('d-none').prop('disabled', false);
    // Enable all Hapus and Edit buttons in this racikan card
    card.find('.hapus-obat, .edit-obat').prop('disabled', false);
    console.log('Edit racikan clicked - fields enabled, hapus-obat and edit-obat enabled');
});

// Notification polling for farmasi create page
@if(auth()->user()->hasRole('Farmasi'))
$(document).ready(function() {
    let lastCheck = 0;
    let isPolling = false;
    
    function checkForNewNotifications() {
        if (isPolling) return;
        isPolling = true;
        
        $.ajax({
            url: '{{ route("erm.check.notifications") }}',
            type: 'GET',
            data: {
                lastCheck: lastCheck,
                page: 'create'
            },
            success: function(response) {
                if (response.hasNew && response.message) {
                    Swal.fire({
                        title: 'Ada Perubahan di Resep Dokter!',
                        text: response.message,
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                }
                lastCheck = response.timestamp;
            },
            error: function(xhr, status, error) {
                console.error('Error checking for notifications:', error);
            },
            complete: function() {
                isPolling = false;
            }
        });
    }
    
    // Check for notifications every 10 seconds
    setInterval(checkForNewNotifications, 10000);
    checkForNewNotifications();
});
@endif
</script>
@endsection
