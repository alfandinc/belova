@extends('layouts.erm.app')

@section('title', 'ERM | Surat Istirahat')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')

@include('erm.partials.modal-alergipasien')
<!-- Modal -->
<div class="modal fade" id="modalSurat" tabindex="-1">
    <div class="modal-dialog">
        <form id="formSurat">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buat Surat Istirahat</h5>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="pasien_id" value="{{ $pasien->id }}">
                    <div class="mb-2">
                        <label>Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Jumlah Hari</label>
                        <input type="text" name="jumlah_hari" class="form-control" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="container-fluid">
    <div class="d-flex  align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Surat Istirahat</h3>
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
                            <li class="breadcrumb-item active">Surat Istirahat</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    @include('erm.partials.card-identitaspasien')

<div class="container">
    
    <button class="btn btn-primary mb-2" data-toggle="modal" data-target="#modalSurat">Buat Surat</button>

    <table class="table table-bordered" id="suratTable">
        <thead>
            <tr>
                <th>Tanggal Mulai</th>
                <th>Tanggal Selesai</th>
                <th>Jumlah Hari</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($surats ?? [] as $surat)
                <tr>
                    <td>{{ $surat->tanggal_mulai }}</td>
                    <td>{{ $surat->tanggal_selesai }}</td>
                    <td>{{ $surat->jumlah_hari }}</td>
                    <td><a href="{{ route('erm.surat.cetak', $surat->id) }}" class="btn btn-sm btn-secondary">Cetak</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

</div><!-- container -->


@endsection
@section('scripts')
<script>
$(document).ready(function() {
    function countDays() {
        let mulai = $('input[name="tanggal_mulai"]').val();
        let selesai = $('input[name="tanggal_selesai"]').val();
        if (mulai && selesai) {
            let start = new Date(mulai);
            let end = new Date(selesai);
            let diff = (end - start) / (1000 * 60 * 60 * 24) + 1;
            $('input[name="jumlah_hari"]').val(diff);
        }
    }

    $('input[name="tanggal_mulai"], input[name="tanggal_selesai"]').on('change', countDays);

    $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

    $('#formSurat').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("erm.suratistirahat.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(data) {
                let row = `<tr>
                    <td>${data.tanggal_mulai}</td>
                    <td>${data.tanggal_selesai}</td>
                    <td>${data.jumlah_hari}</td>
                    <td><a href="/erm/surat/${data.id}/cetak" target="_blank" class="btn btn-sm btn-secondary">Cetak</a></td>
                </tr>`;
                $('#suratTable tbody').append(row);
                $('#modalSurat').modal('hide');
                $('#formSurat')[0].reset();
            },
            error: function(err) {
                alert("Terjadi kesalahan.");
            }
        });
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
});
</script>
@endsection

