@extends('layouts.erm.app')
@section('title', 'E-Resep Farmasi')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection
@section('content')

@include('erm.partials.modal-alergipasien')
@include('erm.partials.modal-resepdokter')
@include('erm.partials.modal-resepfarmasi')

<!-- Edit Non‑Racikan Modal -->
<div class="modal fade" id="editResepModal" tabindex="-1" role="dialog" aria-labelledby="editResepModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="edit-resep-form">
      @csrf
      @method('PUT')
      <input type="hidden" name="resep_id" id="edit-resep-id">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editResepModalLabel">Edit Resep</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Batal">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="edit-jumlah">Jumlah</label>
            <input type="number" class="form-control" id="edit-jumlah" name="jumlah" required>
          </div>
          <div class="form-group">
            <label for="edit-aturan">Aturan Pakai</label>
            <input type="text" class="form-control" id="edit-aturan" name="aturan_pakai" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </div>
    </form>
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
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div style="display: flex; align-items: center;">
                        <h4 style="margin: 0;">Total Harga: <strong>Rp.</strong></h4>
                        <h4 id="total-harga" style="margin: 0; color: white;"><strong>0</strong></h4>
                        
                    </div>
                    @if (!$nonRacikans->count() && !$racikans->count())
    <div class="alert alert-info" id="empty-resep-message">
        Belum ada data dari dokter. Anda bisa menambahkan resep baru atau salin dari dokter.
    </div>
@endif
                    <div class="mb-3">
                        <button id="copy-from-dokter" class="btn btn-warning">Salin Resep dari Dokter</button>

                        <button class="btn btn-primary btn-sm" >Cetak Resep</button>
                        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalFarmasi">Riwayat Farmasi</button>
                        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalDokter">Riwayat Dokter</button>
                        <button id="submit-all" class="btn btn-success btn-sm">Submit Resep</button>
                    </div>
                </div>

                <div class="mb-3">
                    <h5>Catatan Dokter :</h5>
                    <textarea readonly class="form-control" rows="3"></textarea>
                </div>
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
                                        data-harga="{{ $obat->harga_umum }}" 
                                        data-stok="{{ $obat->stok }}">
                                        {{ $obat->nama }} {{ $obat->dosis }} {{ $obat->satuan }}
                                    </option>
                                @endforeach
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
                                <th>Harga</th>
                                <th>Jumlah</th>
                                <th>Stok</th>
                                <th>Aturan Pakai</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="resep-table-body">
                            @forelse ($nonRacikans as $resep)
                                <tr data-id="{{ $resep->id }}">
                                    <td>{{ $resep->obat->nama ?? '-' }}</td>
                                    <td>{{ $resep->obat->harga_umum ?? 0 }}</td>
                                    <td>{{ $resep->jumlah }}</td>
                                    <td>{{ $resep->obat->stok ?? 0 }}</td>
                                    <td>{{ $resep->aturan_pakai }}</td>
                                    <td><button class="btn btn-success btn-sm edit" data-id="{{ $resep->id }}">Edit</button> <button class="btn btn-danger btn-sm hapus" data-id="{{ $resep->id }}">Hapus</button> </td>
                                    
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
            let visitationId = $('#visitation_id').val();  // Pastikan id yang digunakan sama

            if (!obatId || !jumlah || !aturanPakai) return alert("Semua field wajib diisi.");

            // Kirim data via AJAX
            $.ajax({
                url: "{{ route('resep.nonracikan.store') }}", // disesuaikan nanti
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    tipe: "nonracikan",
                    obat_id: obatId,
                    jumlah: jumlah,
                    aturan_pakai: aturanPakai,
                    visitation_id: visitationId 
                },
                success: function () {
                    $('#resep-table-body .no-data').remove();
                    $('#resep-table-body').append(`
                        <tr>
                            <td>${obatText}</td>
                            <td>${harga}</td>
                            <td>${jumlah}</td>
                            <td>${stok}</td>
                            <td>${aturanPakai}</td>
                            <td><button class="btn btn-danger btn-sm hapus">Hapus</button></td>
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

        // UPDATE HARGA
        function updateTotalPrice() {
            let total = 0;
            $('#resep-table-body tr').each(function () {
                let harga = parseFloat($(this).find('td').eq(1).text()) || 0;
                let jumlah = parseInt($(this).find('td').eq(2).text()) || 0;
                total += harga * jumlah;
            });
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
                url: "{{ route('resep.racikan.destroy', ':racikanKe') }}".replace(':racikanKe', racikanKe),
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
        // EDIT RACIKAN
        $('#resep-table-body').on('click', '.edit', function() {
            const row = $(this).closest('tr');
            const id    = row.data('id');
            const jumlah = row.find('td').eq(2).text().trim();
            const aturan = row.find('td').eq(4).text().trim();

            $('#edit-resep-id').val(id);
            $('#edit-jumlah').val(jumlah);
            $('#edit-aturan').val(aturan);
            $('#editResepModal').modal('show');
        });

        // STORE EDUT RACIKAN
        $('#edit-resep-form').on('submit', function(e) {
            e.preventDefault();

            const id = $('#edit-resep-id').val();
            const url = "{{ route('resep.nonracikan.update', '') }}/" + id;
            const data = {
                _token: "{{ csrf_token() }}",
                _method: 'PUT',
                jumlah: $('#edit-jumlah').val(),
                aturan_pakai: $('#edit-aturan').val()
            };

            $.post(url, data)
            .done(function(res) {
                // Update the table row
                const row = $('#resep-table-body').find('tr[data-id="'+ id +'"]');
                row.find('td').eq(2).text(res.data.jumlah);
                row.find('td').eq(4).text(res.data.aturan_pakai);

                $('#editResepModal').modal('hide');
            })
            .fail(function(xhr) {
                alert('Gagal menyimpan perubahan: ' + xhr.responseJSON.message);
            });
        });

        // SUBMIT KE BILLING
        $('#submit-all').on('click', function () {
            // Disable all buttons on the page
            $('button').prop('disabled', true);

            // Optional: Tampilkan loading
            $(this).text('Menyimpan...').addClass('btn-secondary').removeClass('btn-success');
        });

        $('#copy-from-dokter').on('click', function () {
            const visitationId = $('#visitation_id').val();
            
            $.ajax({
                url: `/erm/eresepfarmasi/${visitationId}/copy-from-dokter`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function (res) {
                    alert(res.message);

                    if (res.status === 'success') {
                        location.reload(); // or fetch data dynamically if needed
                    }
                },
                error: function () {
                    alert('Gagal menyalin resep dari dokter.');
                }
            });
        });

              
        updateTotalPrice(); // <--- Tambahkan ini
    
    });

</script>
@endsection
