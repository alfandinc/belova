@extends('layouts.hrd.app')

@section('title','Workdoc - Create Memorandum')

@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">{{ isset($memorandum) ? 'Edit Memorandum' : 'Create Memorandum' }}</h4>
                    <a href="{{ route('workdoc.memorandum.index') }}" class="btn btn-secondary">Kembali</a>
                </div>
                <div class="card-body">
                    <form id="createMemoForm">
                        <div class="row">
                            <div class="form-group col-lg-4 col-md-6 col-12">
                                <label>Tanggal</label>
                                <input type="date" class="form-control" name="tanggal" id="tanggal" required value="{{ isset($memorandum) && $memorandum->tanggal ? $memorandum->tanggal->format('Y-m-d') : now()->format('Y-m-d') }}">
                            </div>
                            <div class="form-group col-lg-4 col-md-6 col-12">
                                <label>Klinik</label>
                                <select class="form-control" name="klinik_id" id="klinik_id">
                                    <option value="">- Pilih Klinik -</option>
                                    @foreach($clinics as $kl)
                                        <option value="{{ $kl->id }}" {{ (isset($memorandum) && $memorandum->klinik_id == $kl->id) ? 'selected' : '' }}>{{ $kl->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-lg-4 col-md-6 col-12">
                                <label>Nomor Memo</label>
                                <input type="text" class="form-control" name="nomor_memo" id="nomor_memo" value="{{ $memorandum->nomor_memo ?? '' }}" placeholder="e.g., MEMO-001/KP-BL/XII/2026" readonly>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-lg-4 col-md-6 col-12">
                                <label>Perihal</label>
                                <input type="text" class="form-control" name="perihal" id="perihal" placeholder="Tuliskan perihal memorandum" required value="{{ $memorandum->perihal ?? '' }}">
                            </div>
                            <div class="form-group col-lg-4 col-md-6 col-12">
                                <label>Dari Divisi</label>
                                <select class="form-control" name="dari_division_id" id="dari_division_id">
                                    <option value="">- Pilih Divisi -</option>
                                    @foreach($divisions as $div)
                                        <option value="{{ $div->id }}" {{ (isset($memorandum) && $memorandum->dari_division_id == $div->id) ? 'selected' : ((isset($defaultDivisionId) && !isset($memorandum) && $defaultDivisionId == $div->id) ? 'selected' : '') }}>{{ $div->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-lg-4 col-md-6 col-12">
                                <label>Kepada</label>
                                <input type="text" class="form-control" name="kepada" id="kepada" placeholder="Nama penerima / jabatan" value="{{ $memorandum->kepada ?? '' }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Isi</label>
                            <textarea id="isi" name="isi" class="form-control" rows="6">{{ $memorandum->isi ?? '' }}</textarea>
                        </div>
                        <button type="button" class="btn btn-primary" id="submitCreate">{{ isset($memorandum) ? 'Update' : 'Simpan' }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs4.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs4.min.css" rel="stylesheet" />
<script>
$(function(){
    $('#isi').summernote({
        height: 250,
        toolbar: [
            ['style', ['style']],
            ['font', ['fontname', 'fontsize', 'bold', 'italic', 'underline', 'clear', 'color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link', 'picture', 'video']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ],
        fontNames: ['Arial','Arial Black','Comic Sans MS','Courier New','Georgia','Helvetica','Tahoma','Times New Roman','Trebuchet MS','Verdana'],
        fontSizes: ['8','9','10','11','12','14','16','18','20','22','24','26','28','30','36','48','64','82','96'],
        fontsizeunit: 'px'
    });

    const isEdit = {{ isset($memorandum) ? 'true' : 'false' }};
    const url = isEdit ? '{{ isset($memorandum) ? route('workdoc.memorandum.update', $memorandum) : '' }}' : '{{ route('workdoc.memorandum.store') }}';

    function maybeGenerateNomor(){
        const isEdit = {{ isset($memorandum) ? 'true' : 'false' }};
        if (isEdit && $('#nomor_memo').val()) return; // don't auto-change on edit if already set
        const tanggal = $('#tanggal').val();
        const klinik_id = $('#klinik_id').val();
        if (!tanggal) return;
        $.get('{{ route('workdoc.memorandum.generate_number') }}', { tanggal, klinik_id })
         .done(function(resp){ $('#nomor_memo').val(resp.nomor_memo || ''); })
         .fail(function(){ /* silent */ });
    }

    $('#tanggal, #klinik_id').on('change', maybeGenerateNomor);
    // initial attempt on load
    maybeGenerateNomor();

    $('#submitCreate').on('click', function(){
        const data = {
            tanggal: $('#tanggal').val(),
            nomor_memo: $('#nomor_memo').val(),
            perihal: $('#perihal').val(),
            dari_division_id: $('#dari_division_id').val(),
            kepada: $('#kepada').val(),
            isi: $('#isi').summernote('code'),
            klinik_id: $('#klinik_id').val(),
            _token: '{{ csrf_token() }}'
        };
        if (isEdit) { data._method = 'PUT'; }

        $.post(url, data)
            .done(function(resp){
                Swal.fire({icon:'success', title:'Sukses', text: resp.message || 'Berhasil disimpan'});
                window.location.href = '{{ route('workdoc.memorandum.index') }}';
            })
            .fail(function(xhr){
                const msg = xhr.responseJSON?.message || 'Terjadi kesalahan';
                Swal.fire({icon:'error', title:'Gagal', text: msg});
            });
    });
});
</script>
@endpush

@push('styles')
<style>
    .card-body .form-group label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        margin-bottom: .25rem;
        letter-spacing: .2px;
    }
    .card-body .row { margin-bottom: .75rem; }
    .card-body .form-control { border-radius: .375rem; }
    @media (max-width: 767.98px) {
        .card-body .row { margin-bottom: 1rem; }
    }
</style>
@endpush