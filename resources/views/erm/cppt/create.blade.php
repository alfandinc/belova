@extends('layouts.erm.app')
@section('title', 'ERM | CPPT')
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
                            <li class="breadcrumb-item">Rawat Jalan</li>
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
                    <form id="form-cppt-soap" action="{{ route('erm.cppt.store') }}" method="POST" enctype="multipart/form-data">

                @csrf
                <input type="hidden" name="jenis_dokumen" value="1">
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
                    <select name="instruksi" class="form-control">
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
                    <form id="form-cppt-sbar" action="{{ route('erm.cppt.store') }}" method="POST" enctype="multipart/form-data">

                @csrf
                <input type="hidden" name="jenis_dokumen" value="2">
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
                    <select name="instruksi" class="form-control">
                        <option value="kembali">Kontrol Kembali</option>
                        <option value="selesai">Kontrol Selesai</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end">
                    <div class="mr-2">
                        <button type="submit" class="btn btn-primary">Simpan SBAR</button>
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

    {{-- CPPT History --}}
<div class="card mt-4">
    <div class="card-header bg-light">
        <strong>Riwayat CPPT</strong>
        
    </div>
    <div class="card-body">
        @forelse ($cpptList as $cppt)
            <div class="row border-bottom py-3">
                <div class="col-md-1 text-center">
                    <div class="font-weight-bold text-muted small">{{ \Carbon\Carbon::parse($cppt->created_at)->translatedFormat('d M Y H:i') }}</div>
                    @php
            $user = $cppt->user;
        @endphp
        <div class="display-4 text-dark">
            @if ($user && $user->hasRole('perawat'))
                P
            @elseif ($user && $user->hasRole('dokter'))
                D
            @else
                {{ strtoupper(substr(optional($user)->name ?? '', 0, 1)) }}
            @endif
        </div>
                </div>
                <div class="col-md-11">
                    <div class="row">
                        @if ($cppt->jenis_dokumen == 1)
                            <div class="col-md-6"><strong>Subject (S)</strong>: <br>{{ $cppt->s }}</div>
                            <div class="col-md-6"><strong>Object (O)</strong>: <br>{!! nl2br(e($cppt->o)) !!}</div>
                        @elseif ($cppt->jenis_dokumen == 2)
                            <div class="col-md-6"><strong>Situation (S)</strong>: <br>{{ $cppt->s }}</div>
                            <div class="col-md-6"><strong>Background (B)</strong>: <br>{!! nl2br(e($cppt->o)) !!}</div>
                        @endif
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6"><strong>Assessment (A)</strong>: <br>{{ $cppt->a }}</div>
                        @if ($cppt->jenis_dokumen == 1)
                            <div class="col-md-6"><strong>Planning (P)</strong>: <br>{!! nl2br(e($cppt->p)) !!}</div>
                        @elseif ($cppt->jenis_dokumen == 2)
                            <div class="col-md-6"><strong>Recommendation (R)</strong>: <br>{!! nl2br(e($cppt->p)) !!}</div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted text-center">Belum ada catatan CPPT.</p>
        @endforelse

    </div>
</div>

           
</div><!-- container -->


@endsection
@section('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    // Modal alergi logic
    $('#btnBukaAlergi').on('click', function () {
        $('#modalAlergi').modal('show');
    });

    var initialStatusAlergi = $('input[name="statusAlergi"]:checked').val();
    if (initialStatusAlergi === 'ada') {
        $('#inputKataKunciWrapper, #selectAlergiWrapper, #selectKandunganWrapper').show();
    } else {
        $('#inputKataKunciWrapper, #selectAlergiWrapper, #selectKandunganWrapper').hide();
    }

    $('input[name="statusAlergi"]').on('change', function () {
        if ($(this).val() === 'ada') {
            $('#inputKataKunciWrapper, #selectAlergiWrapper, #selectKandunganWrapper').show();
        } else {
            $('#inputKataKunciWrapper, #selectAlergiWrapper, #selectKandunganWrapper').hide();
            $('#inputKataKunci').val('');
            $('#selectAlergi, #selectKandungan').val(null).trigger('change');
        }
    });

    function refreshCpptHistory(visitationId) {
        $.ajax({
            url: '/erm/cppt/history-json/' + visitationId,
            type: 'GET',
            success: function (res) {
                let html = '';
                if (res.length === 0) {
                    html = '<p class="text-muted text-center">Belum ada catatan CPPT.</p>';
                } else {
                    res.forEach(cppt => {
                        let sLabel = cppt.jenis_dokumen == 1 ? 'Subject (S)' : 'Situation (S)';
                        let oLabel = cppt.jenis_dokumen == 1 ? 'Object (O)' : 'Background (B)';
                        let pLabel = cppt.jenis_dokumen == 1 ? 'Planning (P)' : 'Recommendation (R)';
                        let userInitial = cppt.user?.name?.charAt(0).toUpperCase() || '-';

                        html += `
                            <div class="row border-bottom py-3">
                                <div class="col-md-1 text-center">
                                    <div class="font-weight-bold text-muted small">${cppt.formatted_date}</div>
                                    <div class="display-4 text-dark">${userInitial}</div>
                                </div>
                                <div class="col-md-11">
                                    <div class="row">
                                        <div class="col-md-6"><strong>${sLabel}</strong>: <br>${cppt.s}</div>
                                        <div class="col-md-6"><strong>${oLabel}</strong>: <br>${cppt.o.replace(/\n/g, '<br>')}</div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-6"><strong>Assessment (A)</strong>: <br>${cppt.a}</div>
                                        <div class="col-md-6"><strong>${pLabel}</strong>: <br>${cppt.p.replace(/\n/g, '<br>')}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }
                // Temukan dan ganti bagian card-body dalam card Riwayat CPPT
                $('.card:has(strong:contains("Riwayat CPPT")) .card-body').html(html);
            }
        });
    }

    // AJAX Submit - SOAP
    $('#form-cppt-soap').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        let visitationId = formData.get('visitation_id');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                Swal.fire({ title: 'Menyimpan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            },
            success: function (res) {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: 'SOAP berhasil disimpan.', timer: 2000, showConfirmButton: false });
                $('#form-cppt-soap')[0].reset();
                refreshCpptHistory(visitationId);
            },
            error: function (xhr) {
                let msg = 'Terjadi kesalahan saat menyimpan.';
                if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
                Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
            }
        });
    });

    // AJAX Submit - SBAR
    $('#form-cppt-sbar').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        let visitationId = formData.get('visitation_id');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                Swal.fire({ title: 'Menyimpan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            },
            success: function (res) {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: 'SBAR berhasil disimpan.', timer: 2000, showConfirmButton: false });
                $('#form-cppt-sbar')[0].reset();
                refreshCpptHistory(visitationId);
            },
            error: function (xhr) {
                let msg = 'Terjadi kesalahan saat menyimpan.';
                if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
                Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
            }
        });
    });

});
</script>
@endsection


