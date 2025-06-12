@extends('layouts.erm.app')
@section('title', 'E-Resep Farmasi')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection
@section('content')

@include('erm.partials.modal-alergipasien')
@include('erm.partials.modal-resephistory')

@include('erm.partials.modal-editnonracikan-farmasi')

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
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div style="display: flex; align-items: center;">
                        <h4 style="margin: 0;">Total Harga: <strong>Rp.</strong></h4>
                        <h4 id="total-harga" style="margin: 0; color: white;"><strong>0</strong></h4>
                        
                    </div>
                   
                    <div class="mb-3">
                        <button id="copy-from-dokter" class="btn btn-warning btn-sm">Salin Resep dari Dokter</button>

                        <button class="btn btn-primary btn-sm btn-cetakresep" >Cetak Resep</button>
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
                    <textarea readonly class="form-control" rows="3"></textarea>
                </div>
                 @if (!$nonRacikans->count() && !$racikans->count())
                    <div class="alert alert-info" id="empty-resep-message">
                        Belum ada data dari dokter. Anda bisa menambahkan resep baru atau salin dari dokter.
                    </div>
                @endif
<div id="resep-wrapper">
                <!-- NON RACIKAN -->
                <h5 style="color: yellow;"><strong>Resep Non Racikan</strong></h5>
                <div class="racikan-card mb-4 p-3 border rounded">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label>Nama Obat</label>
                            <select name="obat_id" id="obat_id" class="form-control select2">
                                @foreach ($obats as $obat)
                                    <option 
                                        value="{{ $obat->id }}" 
                                        data-harga="{{ $obat->harga_nonfornas }}" 
                                        data-stok="{{ $obat->stok }}">
                                        {{ $obat->nama }} {{ $obat->dosis }} {{ $obat->satuan }}
                                    </option>
                                @endforeach
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

                    <table class="table table-bordered" style="color: white;">
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
                                    
                                    <td>{{ $resep->obat->stok ?? 0 }}</td>
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
                            <h5 style="color: yellow;"><strong>Racikan {{ $ke }}</strong></h5>
                            <button class="btn btn-danger btn-sm hapus-racikan">Hapus Racikan</button>
                        </div>

                        <table class="table table-bordered text-white">
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
                                    <tr>
                                        <td data-id="{{ $resep->id }}">{{ $resep->obat->nama ?? '-' }}</td>
                                        <td>{{ $resep->dosis }}</td>
                                        <td>{{ $resep->obat->stok ?? 0 }}</td>
                                        <td><button class="btn btn-danger btn-sm hapus-obat">Hapus</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="row">
                            <div class="col-md-3">
                                <label>Wadah</label>
                                <select class="form-control wadah">
                                    @foreach (['Kapsul', 'Ampul', 'Botol', 'Sachet'] as $wadah)
                                        <option value="{{ $wadah }}" {{ $items->first()->wadah == $wadah ? 'selected' : '' }}>{{ $wadah }}</option>
                                    @endforeach
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

        // STORE NON RACIKAN
        $('#tambah-resep').on('click', function () {
            let obatId = $('#obat_id').val();
            let obatText = $('#obat_id option:selected').text();
            let jumlah = $('#jumlah').val();
            let harga = $('#obat_id option:selected').data('harga');
            let stok = $('#obat_id option:selected').data('stok');
            let aturanPakai = $('#aturan_pakai').val();
            let diskon = $('#diskon').val() || 0;
            let visitationId = $('#visitation_id').val();  // Pastikan id yang digunakan sama
            

            if (!obatId || !jumlah || !aturanPakai) return alert("Semua field wajib diisi.");

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
                    const resep = res.data;
                    $('#resep-table-body .no-data').remove();
                    $('#resep-table-body').append(`
                        <tr data-id="${resep.id}">
                            <td>${obatText}</td>
                            <td>${jumlah}</td>
                            <td>${harga}</td>
                            <td>${diskon} %</td>                           
                            <td>${stok}</td>
                            <td>${aturanPakai}</td>
                            <td>
                                <button class="btn btn-success btn-sm edit" data-id="${resep.id}">Edit</button>
                                <button class="btn btn-danger btn-sm hapus" data-id="${resep.id}">Hapus</button>
                            </td>
                        </tr>
                    `);
                    updateTotalPrice();
                }
            });
        });

        //DELETE NON RACIKAN
        $('#resep-table-body').on('click', '.hapus', function () {
            const row = $(this).closest('tr');
            const resepId = row.data('id');

            if (!confirm('Yakin ingin menghapus resep ini?')) return;

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
                    alert('Gagal menghapus resep. Coba lagi.');
                }
            });
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

            $('#racikan-container').append(`
                <div class="racikan-card mb-4 p-3 border rounded" data-racikan-ke="${racikanCount}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 style="color: yellow;"><strong>Racikan ${racikanCount}</strong></h5>
                        <button class="btn btn-danger btn-sm hapus-racikan">Hapus Racikan</button>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Nama Obat</label>
                            <select class="form-control select2 obat_id">
                                @foreach ($obats as $obat)
                                    <option 
                                        value="{{ $obat->id }}" 
                                        data-stok="{{ $obat->stok }}"
                                        data-dosis="{{ $obat->dosis }}"
                                        data-satuan="{{ $obat->satuan }}">
                                        {{ $obat->nama }} {{ $obat->dosis }} {{ $obat->satuan }}
                                    </option>
                                @endforeach
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
                            <label>Wadah</label>
                            <select class="form-control wadah">
                                <option value="Kapsul">Kapsul</option>
                                <option value="Ampul">Ampul</option>
                                <option value="Botol">Botol</option>
                                <option value="Sachet">Sachet</option>
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
            `);

            $('.select2').select2({ width: '100%' });
        });
        // TAMBAH OBAT KE RACIKAN
        $('#racikan-container').on('click', '.tambah-obat', function () {
            const card = $(this).closest('.racikan-card');
            const obatSelect = card.find('.obat_id');
            const dosisInput = parseInt(card.find('.dosis_input').val());
            const mode = card.find('.mode_dosis').val();

            const text = obatSelect.find('option:selected').text();
            const satuan = obatSelect.find('option:selected').data('satuan');
            const stok = obatSelect.find('option:selected').data('stok');
            const defaultDosis = obatSelect.find('option:selected').data('dosis');

            let dosisAkhir = mode === 'tablet' ? defaultDosis * dosisInput : dosisInput;

            const tbody = card.find('.resep-table-body');
            tbody.find('.no-data').remove();

            tbody.append(`
                <tr>
                    <td data-id="${obatSelect.val()}">${text}</td>
                    <td>${dosisAkhir} ${satuan}</td>
                    <td>${stok}</td>
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
            const bungkus = card.find('.bungkus').val();
            const aturanPakai = card.find('.aturan_pakai').val();

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
                    alert(res.message);
                    card.find('.tambah-resepracikan').prop('disabled', true).text('Disimpan');
                }
            });
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

            if (!confirm('Yakin ingin menghapus resep ini?')) return;

            // Request untuk menghapus racikan
            $.ajax({
                url: "{{ route('resepfarmasi.racikan.destroy', ':racikanKe') }}".replace(':racikanKe', racikanKe),
                method: "DELETE",
                data: {
                    _token: "{{ csrf_token() }}",
                    visitation_id: visitationId,
                },
                success: function (res) {
                    alert(res.message); // Notifikasi
                    card.remove(); // Hapus card racikan dari tampilan
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
                alert('Gagal menyimpan perubahan: ' + xhr.responseJSON.message);
            });
        });

        // SUBMIT KE BILLING
        $('#submit-all').on('click', function () {
        if (!confirm('Yakin ingin submit resep ini?')) return;

        // Disable all buttons except specific ones
        $('button').not('.btn-cetakresep, .btn-riwayat').prop('disabled', true);

        // Disable all input fields, select, and textarea
        $('input, select, textarea').prop('disabled', true);

        // Change the text and style of the submit button to indicate processing
        $(this).text('Telah disimpan').addClass('btn-secondary').removeClass('btn-success');

        const visitationId = $('#visitation_id').val();

        // Send the AJAX request
        $.ajax({
            url: "{{ route('resepfarmasi.submit') }}", // Define this route in web.php
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                visitation_id: visitationId
            },
            success: function (res) {
                alert(res.message); // Notify the user
                // window.location.reload(); // Optionally reload the page
            },
            error: function (err) {
                alert('Gagal submit resep. Coba lagi.');
                // Re-enable buttons and inputs if submission fails
                $('button').not('.btn-primary, .btn-riwayat').prop('disabled', false);
                $('input, select, textarea').prop('disabled', false);
                $('#submit-all').text('Submit Resep').addClass('btn-success').removeClass('btn-secondary');
            }
        });
    });

        //COPY RESEP DOKTER
        $('#copy-from-dokter').on('click', function () {
            const visitationId = $('#visitation_id').val();

            $.ajax({
                url: `/erm/eresepfarmasi/${visitationId}/copy-from-dokter`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function (res) {
                    if (res.status === 'success') {
                        alert(res.message);
                        fetchFarmasiResep(); // load the copied data dynamically
                        updateTotalPrice();
                    } else {
                        alert(res.message);
                    }
                },
                error: function () {
                    alert('Gagal menyalin resep dari dokter.');
                }
            });
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
            const visitationId = $(this).data('visitation-id');
            const source = $(this).data('source');
            const currentVisitationId = $('#visitation_id').val();
            
            if (!confirm(`Yakin ingin menyalin resep ini ke kunjungan saat ini?`)) return;
            
            // Show loading
            $(this).html('<i class="fas fa-spinner fa-spin"></i> Menyalin...');
            const $button = $(this);
            
            // Determine which endpoint to use based on source
            const url = source === 'dokter' 
                ? `{{ url('/resep/dokter') }}/${visitationId}/get`
                : `{{ url('/resep/farmasi') }}/${visitationId}/get`;
            
            $.get(url)
                .done(function(response) {
                    if (response.success) {
                        // Clear existing data
                        $('#resep-table-body').html('');
                        $('#racikan-container').html('');
                        racikanCount = 0;
                        
                        // Process non-racikan medications
                        if (response.nonRacikans.length > 0) {
                            response.nonRacikans.forEach(function(resep) {
                                $('#resep-table-body').append(`
                                    <tr data-id="${resep.id}">
                                        <td>${resep.obat.nama || '-'}</td>
                                        <td>${resep.obat.harga_nonfornas || 0}</td>
                                        <td>${resep.jumlah}</td>
                                        <td>${resep.obat.stok || 0}</td>
                                        <td>${resep.aturan_pakai}</td>
                                        <td>
                                            <button class="btn btn-success btn-sm edit" data-id="${resep.id}">Edit</button>
                                            <button class="btn btn-danger btn-sm hapus" data-id="${resep.id}">Hapus</button>
                                        </td>
                                    </tr>
                                `);
                            });
                        } else {
                            $('#resep-table-body').append(`
                                <tr class="no-data">
                                    <td colspan="6" class="text-center text-muted">Belum ada data</td>
                                </tr>
                            `);
                        }
                        
                        // Process racikan medications
                        if (Object.keys(response.racikans).length > 0) {
                            Object.entries(response.racikans).forEach(([racikanKe, items]) => {
                                racikanCount = Math.max(racikanCount, parseInt(racikanKe));
                                
                                // Create racikan card
                                const racikanCard = $(`
                                    <div class="racikan-card mb-4 p-3 border rounded" data-racikan-ke="${racikanKe}">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h5 style="color: yellow;"><strong>Racikan ${racikanKe}</strong></h5>
                                            <div>
                                                <button class="btn btn-danger btn-sm hapus-racikan">Hapus Racikan</button>
                                            </div>
                                        </div>
                                        
                                        <table class="table table-bordered text-white">
                                            <thead>
                                                <tr>
                                                    <th>Nama Obat</th>
                                                    <th>Dosis</th>
                                                    <th>Stok</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="resep-table-body"></tbody>
                                        </table>
                                        
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label>Wadah</label>
                                                <select class="form-control select2-wadah-racikan wadah" name="wadah_id">
                                                    <option value="${items[0].wadah_id || ''}">${items[0].wadah?.nama || 'Pilih Wadah'}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label>Bungkus</label>
                                                <input type="number" class="form-control jumlah_bungkus" value="${items[0].bungkus || ''}">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Aturan Pakai</label>
                                                <input type="text" class="form-control aturan_pakai" value="${items[0].aturan_pakai || ''}">
                                            </div>
                                        </div>
                                        
                                        <button class="btn btn-success btn-block mt-3 tambah-resepracikan" disabled>Sudah Disimpan</button>
                                    </div>
                                `);
                                
                                // Add items to the racikan table
                                const tbody = racikanCard.find('.resep-table-body');
                                items.forEach(function(racik) {
                                    tbody.append(`
                                        <tr>
                                            <td data-id="${racik.obat_id}">${racik.obat.nama || '-'}</td>
                                            <td>${racik.dosis || '-'}</td>
                                            <td>${racik.obat.stok || 0}</td>
                                            <td><button class="btn btn-danger btn-sm hapus-obat">Hapus</button></td>
                                        </tr>
                                    `);
                                });
                                
                                // Add the racikan card to the container
                                $('#racikan-container').append(racikanCard);
                                
                                // Initialize select2 for wadah
                                racikanCard.find('.select2-wadah-racikan').select2({
                                    placeholder: 'Search wadah...',
                                    ajax: {
                                        url: '{{ route("wadah.search") }}',
                                        dataType: 'json',
                                        delay: 250,
                                        data: function (params) {
                                            return { q: params.term };
                                        },
                                        processResults: function (data) {
                                            return {
                                                results: data.map(item => ({
                                                    id: item.id,
                                                    text: item.text
                                                }))
                                            };
                                        },
                                        cache: true
                                    },
                                });
                            });
                        }
                        
                        // Close the modal
                        $('#riwayatModal').modal('hide');
                        
                        // Show success message
                        alert('Resep berhasil disalin!');
                        updateTotalPrice();
                    }
                })
                .fail(function() {
                    alert('Gagal menyalin resep. Silakan coba lagi.');
                })
                .always(function() {
                    // Reset button text
                    $button.html('<i class="fas fa-copy"></i> Salin Resep');
                });
        });

              
        updateTotalPrice(); // <--- Tambahkan ini
    
    });

    function fetchFarmasiResep() {
    const visitationId = $('#visitation_id').val();

    $.get(`/erm/eresepfarmasi/${visitationId}/json`, function (res) {
        $('#resep-wrapper').show();
        $('#empty-resep-message').hide();
        $('#copy-from-dokter').hide();

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

        Object.entries(res.racikans).forEach(([ke, items]) => {
            const wadah = items[0].wadah ?? '';
            const bungkus = items[0].bungkus ?? '';
            const aturan = items[0].aturan_pakai ?? '';

            const rows = items.map(item => {
                return `
                    <tr>
                        <td data-id="${item.id}">${item.obat?.nama ?? '-'}</td>
                        <td>${item.dosis}</td>
                        <td>${item.obat?.stok ?? 0}</td>
                        <td><button class="btn btn-danger btn-sm hapus-obat">Hapus</button></td>
                    </tr>`;
            }).join('');

            racikanWrapper.append(`
                <div class="racikan-card mb-4 p-3 border rounded" data-racikan-ke="${ke}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 style="color: yellow;"><strong>Racikan ${ke}</strong></h5>
                        <button class="btn btn-danger btn-sm hapus-racikan">Hapus Racikan</button>
                    </div>

                    <table class="table table-bordered text-white">
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
                            <label>Wadah</label>
                            <select class="form-control wadah">
                                ${['Kapsul', 'Ampul', 'Botol', 'Sachet'].map(opt => `
                                    <option value="${opt}" ${opt === wadah ? 'selected' : ''}>${opt}</option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Bungkus</label>
                            <input type="number" class="form-control jumlah_bungkus" value="${bungkus}">
                        </div>
                        <div class="col-md-6">
                            <label>Aturan Pakai</label>
                            <input type="text" class="form-control aturan_pakai" value="${aturan}">
                        </div>
                    </div>

                    <button class="btn btn-success btn-block mt-3 tambah-resepracikan" disabled>Sudah Disimpan</button>
                </div>
            `);

            racikanCount++;
        });
    });
}

</script>
@endsection
