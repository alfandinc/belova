@extends('layouts.erm.app')
@section('title', 'ERM | CPPT')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection
@section('content')

@include('erm.partials.modal-alergipasien')

<div class="container-fluid">
<style>
    .cppt-value {
        /* background: #23263a; */
        /* color: #fff; */
        border-radius: 0.7em;
        padding: 0.7em 1em;
        margin-bottom: 0.7em;
        /* font-size: 0.97rem; */
        box-shadow: 0 1px 6px rgba(33,150,243,0.07);
        word-break: break-word;
        border: 1px solid #2196f3;
    }
    .cppt-entry {
        /* background: #23263a; */
        border-radius: 1.2em;
        box-shadow: 0 2px 12px rgba(33,150,243,0.08);
        padding: 1.5em 1.2em;
        margin-bottom: 1.5em;
        display: flex;
        align-items: flex-start;
    }
    .cppt-entry .cppt-label {
        margin-bottom: 0.5em;
    }
    .cppt-entry .cppt-meta {
        min-width: 140px;
        text-align: center;
        margin-right: 2em;
    }
    .cppt-entry .cppt-meta .display-4 {
    font-size: 4rem;
    font-weight: 700;
    color: #2196f3;
    margin-top: 0.5em;
    }
    .cppt-entry .cppt-content {
        flex: 1;
    }
    .cppt-entry .row {
        margin-bottom: 0.7em;
    }
    .cppt-entry .row:last-child {
        margin-bottom: 0;
    }
    .cppt-label {
        background: #2196f3;
        color: #fff;
        font-weight: bold;
        font-size: 0.95rem;
        padding: 0.35em 1.2em;
        border-radius: 0.5em;
        box-shadow: 0 2px 8px rgba(33,150,243,0.12);
        display: inline-block;
        margin-bottom: 0.7em;
        letter-spacing: 0.5px;
        border: none;
    }
</style>
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
                        <textarea name="s" class="form-control" rows="8" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label><strong>Object (O) *</strong></label></textarea>
                            <textarea id="objectO" name="o" class="form-control" rows="8" required></textarea>
                            @if(auth()->user() && auth()->user()->hasRole('Perawat'))
                            <button type="button" class="btn btn-secondary mt-2" id="btnTemplateO">Template</button>
                            @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label><strong>Assessment (A) *</strong></label>
                        <textarea name="a" class="form-control" rows="8" required></textarea>
                        
                    </div>
                    <div class="col-md-6">
                        <label><strong>Planning (P) *</strong></label></textarea>
                        <textarea id="planningP" name="p" class="form-control" rows="8" required></textarea>
                            @if(auth()->user() && auth()->user()->hasRole('Perawat'))
                            <button type="button" class="btn btn-secondary mt-2" id="btnTemplateP">Template</button>
                            @endif

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
                        <textarea name="s" class="form-control" rows="8" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label><strong>Background (B) *</strong></label>
                        <textarea name="o" class="form-control" rows="8" required></textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label><strong>Assessment (A) *</strong></label>
                        <textarea name="a" class="form-control" rows="8" required></textarea>
                        
                    </div>
                    <div class="col-md-6">
                        <label><strong>Recommendation (R) *</strong></label>
                        <textarea name="p" class="form-control" rows="8" required></textarea>

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
            <div class="cppt-entry">
                <div class="cppt-meta">
                    <div class="font-weight-bold text-muted small">{{ \Carbon\Carbon::parse($cppt->created_at)->translatedFormat('d M Y H:i') }}</div>
                    @php $user = $cppt->user; @endphp
                    <div class="display-4">
                        @if ($user && $user->hasRole('perawat'))
                            P
                        @elseif ($user && $user->hasRole('dokter'))
                            D
                        @else
                            {{ strtoupper(substr(optional($user)->name ?? '', 0, 1)) }}
                        @endif
                    </div>
                </div>
                <div class="cppt-content">
                    <div class="row">
                        @if ($cppt->jenis_dokumen == 1)
                            <div class="col-md-6"><span class="cppt-label">Subject (S)</span><div class="cppt-value">{{ $cppt->s }}</div></div>
                            <div class="col-md-6"><span class="cppt-label">Object (O)</span><div class="cppt-value">{!! nl2br(e($cppt->o)) !!}</div></div>
                        @elseif ($cppt->jenis_dokumen == 2)
                            <div class="col-md-6"><span class="cppt-label">Situation (S)</span><div class="cppt-value">{{ $cppt->s }}</div></div>
                            <div class="col-md-6"><span class="cppt-label">Background (B)</span><div class="cppt-value">{!! nl2br(e($cppt->o)) !!}</div></div>
                        @endif
                    </div>
                    <div class="row">
                        <div class="col-md-6"><span class="cppt-label">Assessment (A)</span><div class="cppt-value">{{ $cppt->a }}</div></div>
                        @if ($cppt->jenis_dokumen == 1)
                            <div class="col-md-6"><span class="cppt-label">Planning (P)</span><div class="cppt-value">{!! nl2br(e($cppt->p)) !!}</div></div>
                        @elseif ($cppt->jenis_dokumen == 2)
                            <div class="col-md-6"><span class="cppt-label">Recommendation (R)</span><div class="cppt-value">{!! nl2br(e($cppt->p)) !!}</div></div>
                        @endif
                    </div>
                </div>
            </div>
            <hr style="border-color:#2196f3;border-width:3px;opacity:0.5;">
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

        // Template button logic for Object (O)
        $('#btnTemplateO').on('click', function () {
            $('#objectO').val('KU = Baik\nT = \nN = \nRR = \nS = \nTB = \nBB = \nRESIKO JATUH= TIDAK BERESIKO\nSKALA NYERI= 0');
        });

        // Template button logic for Planning (P)
        $('#btnTemplateP').on('click', function () {
            $('#planningP').val('Monitor KU dan VS\nKolaborasi dengan Dokter');
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
                                        <div class="col-md-6"><strong><span>${sLabel}</span></strong>: <br>${cppt.s}</div>
                                        <div class="col-md-6"><strong><span>${oLabel}</span></strong>: <br>${cppt.o.replace(/\n/g, '<br>')}</div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-6"><strong><span>Assessment (A)</span></strong>: <br>${cppt.a}</div>
                                        <div class="col-md-6"><strong><span>${pLabel}</span></strong>: <br>${cppt.p.replace(/\n/g, '<br>')}</div>
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


