@extends('layouts.erm.app')
@section('title', 'CPPT')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection
@section('content')

@include('erm.partials.modal-alergipasien')

<div class="container-fluid">
    <div class="d-flex  align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Catatan Perkembangan Pasien Terintegrasi</h3>
    </div>
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item active">CPPT</li>
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
            <!-- Nav tabs -->
            <ul class="nav nav-pills nav-justified" role="tablist">
                <li class="nav-item waves-effect waves-light">
                    <a class="nav-link active" data-toggle="tab" href="#soap" role="tab" aria-selected="true">SOAP</a>
                </li>
                <li class="nav-item waves-effect waves-light">
                    <a class="nav-link" data-toggle="tab" href="#sbar" role="tab" aria-selected="false">SBAR</a>
                </li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
                <div class="tab-pane p-3 active" id="soap" role="tabpanel">
                    <form action="{{ route('erm.cppt.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="jenis" value="soap">
                <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label><strong>Subject (S) *</strong></label>
                        <textarea name="s" class="form-control" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label><strong>Object (O) *</strong></label>
                        <textarea name="o" class="form-control" required></textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label><strong>Assessment (A) *</strong></label>
                        <textarea name="a" class="form-control" required></textarea>
                        
                    </div>
                    <div class="col-md-6">
                        <label><strong>Planning (P) *</strong></label>
                        <textarea name="p" class="form-control" required></textarea>

                    </div>
                </div>

                <div class="mb-3">
                    <label><strong>Rencana Tindak Lanjut</strong></label>
                    <select name="tindak_lanjut" class="form-control">
                        <option value="kembali">Kontrol Kembali</option>
                        <option value="selesai">Kontrol Selesai</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end">
                    <div class="mr-2">
                        <button type="submit" class="btn btn-primary">Simpan SOAP</button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-success">Tandai Dibaca</button>
                    </div>
                </div>
            </form>
                    
                </div>
                <div class="tab-pane p-3" id="sbar" role="tabpanel">
                    <form action="{{ route('erm.cppt.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="jenis" value="sbar">
                <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label><strong>Situation (S) *</strong></label>
                        <textarea name="s" class="form-control" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label><strong>Background (B) *</strong></label>
                        <textarea name="o" class="form-control" required></textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label><strong>Assessment (A) *</strong></label>
                        <textarea name="a" class="form-control" required></textarea>
                        
                    </div>
                    <div class="col-md-6">
                        <label><strong>Recommendation (R) *</strong></label>
                        <textarea name="p" class="form-control" required></textarea>

                    </div>
                </div>

                <div class="mb-3">
                    <label><strong>Rencana Tindak Lanjut</strong></label>
                    <select name="tindak_lanjut" class="form-control">
                        <option value="kembali">Kontrol Kembali</option>
                        <option value="selesai">Kontrol Selesai</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end">
                    <div class="mr-2">
                        <button type="submit" class="btn btn-primary">Simpan SOAP</button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-success">Tandai Dibaca</button>
                    </div>
                </div>
            </form>
                    
                </div>
            </div>    
        </div><!--end card-body-->
    </div><!--end card-->
           
</div><!-- container -->


@endsection
@section('scripts')
<script>  

   $(document).ready(function () {    
    $('.select2').select2({ width: '100%' });
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
