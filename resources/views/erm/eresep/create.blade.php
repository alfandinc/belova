@extends('layouts.erm.app')
@section('title', 'E-Resep')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection
@section('content')

@include('erm.partials.modal-alergipasien')

{{-- Modals --}}

<div class="modal fade bd-example-modal-lg" id="modalFarmasi" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title m-0" id="modalFarmasiLabel">Riwayat Obat Farmasi</h5>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div><!--end modal-header-->
            <div class="modal-body">
                <table class="table table-bordered table-sm">
                <thead class="thead-light">
                    <tr>
                    <th>No</th>
                    <th>Nama Obat</th>
                    <th>Dosis</th>
                    <th>Waktu Pemberian</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Paracetamol</td>
                        <td>500mg</td>
                        <td>Pagi dan malam</td>
                    </tr>
                    <!-- Tambah baris lain sesuai data -->
                </tbody>
                </table>
                
            </div><!--end modal-body-->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
            </div><!--end modal-footer-->
        </div><!--end modal-content-->
    </div><!--end modal-dialog-->
</div><!--end modal-->
<div class="modal fade bd-example-modal-lg" id="modalDokter" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title m-0" id="modalDokterLabel">Riwayat Obat Dokter</h5>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div><!--end modal-header-->
            <div class="modal-body">
                <table class="table table-bordered table-sm">
                <thead class="thead-light">
                    <tr>
                    <th>No</th>
                    <th>Nama Obat</th>
                    <th>Dosis</th>
                    <th>Waktu Pemberian</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Paracetamol</td>
                        <td>500mg</td>
                        <td>Pagi dan malam</td>
                    </tr>
                    <!-- Tambah baris lain sesuai data -->
                </tbody>
                </table>
                
            </div><!--end modal-body-->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
            </div><!--end modal-footer-->
        </div><!--end modal-content-->
    </div><!--end modal-dialog-->
</div><!--end modal-->
{{-- EndModals --}}

<div class="container-fluid">
    <div class="d-flex  align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">E-Resep Pasien</h3>
    </div>
    <!-- Page-Title -->
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
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->    
    
    @include('erm.partials.card-identitaspasien')

    <div class="card">
        <div class="card-body">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div style="display: flex; align-items: center;">
                        <h4 style="color: white; margin: 0;">Total Harga: <strong>Rp.</strong></h4>
                        <h4 id="total-harga" style="margin: 0; color: white;"><strong>0</strong></h4>
                    </div>
                    <!-- Tombol -->
                    <div class="mb-3">
                        
                        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalFarmasi">
                            Riwayat Farmasi
                        </button>

                        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalDokter">
                            Riwayat Dokter
                        </button>
                    </div>

                </div>

                <div class="mb-3">
                    <h5 ><strong>Dokter Input : Nama Dokter</strong></h5>
                    <textarea class="form-control" placeholder="Tuliskan catatan disini ..." rows="3"></textarea>
                </div>

            <h5 style="color: yellow;"><strong>Resep Non Racikan</strong></h5>
            <div class="racikan-card mb-4 p-3 border rounded" style="background-color: #333; color: white;">

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Nama Obat</label>
                        <select name="obat_id" id="obat_id" class="form-control select2">
                            @foreach ($obats as $obat)
                                <option 
                                    data-harga="{{ $obat->harga_umum }}" 
                                    data-stok="{{ $obat->stok }}" 
                                    
                                    value="{{ $obat->id }}">
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
                        <th>Jumlah Diberikan</th>
                        <th>Sisa Stok</th>
                        <th>Aturan Pakai</th> 
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="resep-table-body">
                    <tr class="no-data">
                    <td colspan="5" class="text-center text-muted">Belum ada data</td>
                    </tr>
                </tbody>
                </table>
            </div>

            <h5 style="color: yellow;"><strong>Resep Racikan</strong></h5>

<button id="tambah-racikan" class="btn btn-success mb-3">Tambah Racikan</button>

<div id="racikan-container">
    <!-- Racikan baru akan ditambahkan di sini -->
</div>

        </div>
    </div>
</div><!-- container -->


@endsection
@section('scripts')
<script>
    $(document).ready(function () {
        $('.select2').select2({ width: '100%' });
    });
    // Saat tombol modal alergi ditekan
    $('#btnBukaAlergi').on('click', function () {
        $('#modalAlergi').modal('show');
    });

    // Toggle semua bagian tergantung status
        var initialStatusAlergi = $('input[name="statusAlergi"]:checked').val(); // Ambil status yang dipilih awalnya
    
    // Jika status alergi adalah 'ada', tampilkan semua elemen yang terkait
    if (initialStatusAlergi === 'ada') {
        $('#inputKataKunciWrapper').show();
        $('#selectAlergiWrapper').show();
        $('#selectKandunganWrapper').show();
    } else {
        // Jika tidak, sembunyikan elemen-elemen tersebut
        $('#inputKataKunciWrapper').hide();
        $('#selectAlergiWrapper').hide();
        $('#selectKandunganWrapper').hide();
    }
    $('input[name="statusAlergi"]').on('change', function () {
        if ($(this).val() === 'ada') {
            $('#inputKataKunciWrapper').show();
            $('#selectAlergiWrapper').show();
            $('#selectKandunganWrapper').show();
        } else {
            $('#inputKataKunciWrapper').hide();
            $('#selectAlergiWrapper').hide();
            $('#selectKandunganWrapper').hide();
            $('#inputKataKunci').val('');
            $('#selectAlergi, #selectKandungan').val(null).trigger('change');
        }
    });

// Fungsi Resep Non Racikan
$('#tambah-resep').click(function () {
    let obat = $('#obat_id').val();
    let obatText = $('#obat_id option:selected').text();
    let jumlah = $('#jumlah').val();
    let harga = $('#obat_id option:selected').data('harga');
    let stok = $('#obat_id option:selected').data('stok');
    let aturan_pakai = $('#aturan_pakai').val();

    if (!obat || !jumlah || !aturan_pakai) {
        alert("Semua field wajib diisi.");
        return;
    }

    // Hapus baris "Belum ada data"
    $('#resep-table-body .no-data').remove();

    // Tambah baris baru
    $('#resep-table-body').append(`
        <tr>
            <td>${obatText}</td>
            <td>${harga}</td>
            <td>${jumlah}</td>            
            <td>${stok}</td>
            <td>${aturan_pakai}</td>
            <td><button class="btn btn-danger btn-sm hapus">Hapus</button></td>
        </tr>
    `);

    // Update total price
    updateTotalPrice();
});

// Hapus row dan update total
$('#resep-table-body').on('click', '.hapus', function () {
    $(this).closest('tr').remove();
    if ($('#resep-table-body tr').length === 0) {
        $('#resep-table-body').append(`
            <tr class="no-data">
            <td colspan="4" class="text-center text-muted">Belum ada data</td>
            </tr>
        `);
    }

    // Update total price
    updateTotalPrice();
});

// Fungsi untuk menghitung total harga
function updateTotalPrice() {
    let totalPrice = 0;
    
    // Loop through each row in the table
    $('#resep-table-body tr').each(function () {
        let harga = parseFloat($(this).find('td').eq(1).text()); // Ambil harga
        let jumlah = parseInt($(this).find('td').eq(2).text()); // Ambil jumlah
        
        // Add to the total price (harga * jumlah)
        totalPrice += harga * jumlah;
    });

    // If totalPrice is NaN, set it to 0
    if (isNaN(totalPrice)) {
        totalPrice = 0;
    }

    
    // Format the total price to currency format (e.g., 10.000)
    let formattedPrice = new Intl.NumberFormat('id-ID').format(totalPrice);

    // Update the total price in the view
    $('#total-harga').html('<strong>' + formattedPrice + '</strong>'); // Display formatted total
}


    // Fungsi Resep Racikan
    let racikanCount = 0;

    $('#tambah-racikan').click(function () {
        racikanCount++;

        const racikanHtml = `
        <div class="racikan-card mb-4 p-3 border rounded" style="background-color: #333; color: white;">
            <div class="d-flex justify-content-between align-items-center">
                <h5 style="color: yellow;"><strong>Racikan ${racikanCount}</strong></h5>
                <button type="button" class="btn btn-danger btn-sm hapus-racikan">Hapus Racikan</button>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Nama Obat</label>
                    <select name="obat_id" class="form-control select2">
                        @foreach ($obats as $obat)
                            <option 
                                data-harga="{{ $obat->harga_umum }}" 
                                data-stok="{{ $obat->stok }}" 
                                data-dosis="{{ $obat->dosis }}" 
                                data-satuan="{{ $obat->satuan }}"
                                value="{{ $obat->id }}">
                                {{ $obat->nama }} {{ $obat->dosis }} {{ $obat->satuan }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Dosis</label>
                    <input type="number" class="form-control dosis_input" placeholder="Masukkan dosis">
                </div>
                <div class="col-md-2">
                    <label>Satuan Dosis</label>
                    <select class="form-control mode_dosis">
                        <option value="normal">Normal</option>
                        <option value="tablet">Tablet</option>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-primary btn-block tambah-obat">Tambah ke Racikan</button>
                </div>      
            </div>

            <table class="table table-bordered" style="color: white;">
                <thead>
                    <tr>
                        <th>Nama Obat</th>                        
                        <th>Dosis</th>
                        <th>Sisa Stok</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody class="resep-table-body">
                    <tr class="no-data">
                        <td colspan="6" class="text-center text-muted">Belum ada data</td>
                    </tr>
                </tbody>
            </table>
            <div class="row">
                
                <div class="col-md-3">
                    <label>Bungkus Racikan</label>
                    <select class="form-control select2 bungkus">
                        <option value="Kapsul">Kapsul</option>
                        <option value="Ampul">Ampul</option>
                        <option value="Botol">Botol</option>
                        <option value="Sachet">Sachet</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Jumlah Bungkus</label>
                    <input type="number" class="form-control jumlah_bungkus">
                </div>
                <div class="col-md-6">
                    <label>Aturan Pakai</label>
                    <input type="text" class="form-control aturan_pakai">
                </div>
            </div>
        </div>`;

        $('#racikan-container').append(racikanHtml);
        $('.select2').select2({ width: '100%' });
    });

    // Saat memilih obat
    $('#racikan-container').on('change', 'select[name="obat_id"]', function () {
        const card = $(this).closest('.racikan-card');
        const obatSelect = $(this);
        // Tidak autofill dosis input lagi
        // const dosisObat = obatSelect.find('option:selected').data('dosis');
        // card.find('.dosis_input').val(dosisObat);
    });

    // Tambahkan obat ke racikan
    $('#racikan-container').on('click', '.tambah-obat', function () {
        const card = $(this).closest('.racikan-card');
        const obatSelect = card.find('select[name="obat_id"]');
        const obatText = obatSelect.find('option:selected').text();
        const stok = obatSelect.find('option:selected').data('stok');
        const dosisObat = obatSelect.find('option:selected').data('dosis');
        const satuanObat = obatSelect.find('option:selected').data('satuan');
        
        const dosisInput = parseInt(card.find('.dosis_input').val()); // Dosis yang diinput oleh pengguna
        const aturanPakai = card.find('.aturan_pakai').val();
        const modeDosis = card.find('.mode_dosis').val(); // Ambil pilihan dosis normal atau tablet
        const tbody = card.find('.resep-table-body');

        if (!obatSelect.val()  || isNaN(dosisInput)) {
            alert("Semua field wajib diisi.");
            return;
        }

        let dosisAkhir;

        if (modeDosis === 'tablet') {
            dosisAkhir = dosisObat * dosisInput; // Jika tablet, hitung dosis akhir
        } else {
            dosisAkhir = dosisInput; // Jika normal, dosis akhir sama dengan dosis input
        }

        tbody.find('.no-data').remove();

        tbody.append(`
            <tr>
                <td>${obatText}</td>               
                <td>${dosisAkhir} ${satuanObat}</td> <!-- Dosis akhir sesuai dengan pilihan dosis -->
                <td>${stok}</td>
                <td><button type="button" class="btn btn-danger btn-sm hapus">Hapus</button></td>
            </tr>
        `);

        // Reset input setelah tambah
        
        card.find('.dosis_input').val('');
    });

    // Hapus obat dari tabel
    $('#racikan-container').on('click', '.hapus', function () {
        const tbody = $(this).closest('tbody');
        $(this).closest('tr').remove();

        if (tbody.find('tr').length === 0) {
            tbody.append(`
                <tr class="no-data">
                    <td colspan="6" class="text-center text-muted">Belum ada data</td>
                </tr>
            `);
        }
    });

    // Hapus satu racikan card
    $('#racikan-container').on('click', '.hapus-racikan', function () {
        $(this).closest('.racikan-card').remove();
    });


</script>

@endsection
