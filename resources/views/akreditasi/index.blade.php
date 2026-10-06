@extends('layouts.workdoc.app')
@section('title', 'Akreditasi | Dokumen Kerja')
@section('navbar')
    @include('layouts.workdoc.navbar')
@endsection
@section('content')
@php
    $fileIcon = function ($filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'])) return 'fa-file-image text-info';
        if (in_array($ext, ['mp4','webm','ogg','mov','avi','mkv'])) return 'fa-file-video text-purple';
        if ($ext === 'pdf') return 'fa-file-pdf text-danger';
        if (in_array($ext, ['doc','docx'])) return 'fa-file-word text-primary';
        if (in_array($ext, ['xls','xlsx','csv'])) return 'fa-file-excel text-success';
        return 'fa-file text-muted';
    };
@endphp
<style>
    .akr-nav .list-group-item { padding: .45rem .75rem; border-left: 3px solid transparent; }
    .akr-nav .list-group-item.active { border-left-color: #fff; }
    .akr-bab { font-size: .75rem; letter-spacing: .5px; }
    .akr-admin { opacity: .55; }
    .akr-admin:hover { opacity: 1; }
    .akr-doc + .akr-doc { border-top: 1px dashed rgba(127,127,127,.25); }
    .akr-ep .card-header { cursor: pointer; }
</style>

<div class="container-fluid">
    <div class="row mt-3">
        {{-- Left: BAB & Standar --}}
        <div class="col-lg-3 mb-3">
            <div class="card mb-0">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center px-2 py-1">
                        <h5 class="m-0">Akreditasi</h5>
                        @if($isAdmin)
                            <button class="btn btn-sm btn-outline-primary js-name-form" data-kind="bab" data-url="{{ route('akreditasi.bab.store') }}" data-method="POST" title="Tambah BAB">
                                <i class="fas fa-plus"></i> BAB
                            </button>
                        @endif
                    </div>
                    <input type="text" id="akrSearch" class="form-control form-control-sm my-2" placeholder="Cari standar...">

                    <div class="akr-nav">
                        @forelse($babs as $bab)
                            <div class="akr-bab-group mb-2">
                                <div class="d-flex justify-content-between align-items-center px-2 pt-2 pb-1">
                                    <span class="akr-bab text-uppercase text-muted font-weight-bold">{{ $bab->name }}</span>
                                    @if($isAdmin)
                                        <span class="akr-admin text-nowrap">
                                            <a href="#" class="js-name-form text-muted mr-1" data-kind="standar" data-url="{{ route('akreditasi.standar.store', $bab) }}" data-method="POST" title="Tambah Standar"><i class="fas fa-plus"></i></a>
                                            <a href="#" class="js-name-form text-muted mr-1" data-kind="bab" data-url="{{ route('akreditasi.bab.update', $bab) }}" data-method="PUT" data-name="{{ $bab->name }}" title="Ubah BAB"><i class="fas fa-pen"></i></a>
                                            <a href="#" class="js-delete text-muted" data-url="{{ route('akreditasi.bab.destroy', $bab) }}" data-label="BAB {{ $bab->name }}" title="Hapus BAB"><i class="fas fa-trash"></i></a>
                                        </span>
                                    @endif
                                </div>
                                <div class="list-group list-group-flush">
                                    @forelse($bab->standars as $s)
                                        @php $done = $s->eps_count > 0 && $s->eps_done_count === $s->eps_count; @endphp
                                        <a href="{{ route('akreditasi.standar.detail', $s) }}"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center akr-standar {{ $standar && $standar->id === $s->id ? 'active' : '' }}"
                                           data-search="{{ strtolower($bab->name . ' ' . $s->name) }}">
                                            <span class="text-truncate mr-2">{{ $s->name }}</span>
                                            <span class="badge badge-pill {{ $done ? 'badge-success' : 'badge-soft-secondary' }}" title="EP dengan bukti / total EP">{{ $s->eps_done_count }}/{{ $s->eps_count }}</span>
                                        </a>
                                    @empty
                                        <div class="px-3 py-1 small text-muted">Belum ada standar</div>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted small py-4">Belum ada BAB.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: EPs of the selected Standar --}}
        <div class="col-lg-9">
            @if(!$standar)
                <div class="card"><div class="card-body text-center text-muted py-5">
                    <i class="fas fa-medal fa-2x mb-2"></i><br>
                    Belum ada standar. {{ $isAdmin ? 'Tambahkan BAB dan Standar di panel kiri.' : 'Hubungi Admin untuk menambahkan.' }}
                </div></div>
            @else
                @php
                    $total = $standar->eps->count();
                    $done = $standar->eps->filter(fn ($e) => $e->documents->isNotEmpty())->count();
                    $pct = $total ? round($done / $total * 100) : 0;
                @endphp
                <div class="card mb-3">
                    <div class="card-body py-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-start">
                            <div class="mb-2">
                                <div class="small text-muted text-uppercase">{{ $standar->bab->name }}</div>
                                <h4 class="m-0">
                                    {{ $standar->name }}
                                    @if($isAdmin)
                                        <span class="akr-admin small ml-1">
                                            <a href="#" class="js-name-form text-muted mr-1" data-kind="standar" data-url="{{ route('akreditasi.standar.update', $standar) }}" data-method="PUT" data-name="{{ $standar->name }}" title="Ubah Standar"><i class="fas fa-pen"></i></a>
                                            <a href="#" class="js-delete text-muted" data-url="{{ route('akreditasi.standar.destroy', $standar) }}" data-label="Standar {{ $standar->name }}" data-redirect="{{ route('akreditasi.index') }}" title="Hapus Standar"><i class="fas fa-trash"></i></a>
                                        </span>
                                    @endif
                                </h4>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <button class="btn btn-sm btn-outline-secondary mr-2" id="toggleAll">Buka semua</button>
                                @if($isAdmin)
                                    <button class="btn btn-sm btn-primary js-ep-form" data-url="{{ route('akreditasi.ep.store', $standar) }}" data-method="POST"><i class="fas fa-plus mr-1"></i> EP</button>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="progress flex-grow-1 mr-3" style="height:8px;">
                                <div class="progress-bar {{ $pct == 100 ? 'bg-success' : '' }}" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="small text-nowrap">{{ $done }}/{{ $total }} EP sudah ada bukti</span>
                        </div>
                    </div>
                </div>

                @forelse($standar->eps as $ep)
                    @php $hasDocs = $ep->documents->isNotEmpty(); @endphp
                    <div class="card akr-ep mb-2">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center" data-toggle="collapse" data-target="#ep-{{ $ep->id }}">
                            <div class="d-flex align-items-center text-truncate">
                                <i class="fas {{ $hasDocs ? 'fa-check-circle text-success' : 'fa-circle text-muted' }} mr-2"></i>
                                <strong class="mr-2">{{ $ep->name }}</strong>
                                <span class="text-muted small text-truncate d-none d-md-inline">{{ $ep->elemen_penilaian }}</span>
                            </div>
                            <div class="text-nowrap ml-2">
                                <span class="badge badge-soft-primary" title="Skor maksimal">Skor {{ $ep->skor_maksimal }}</span>
                                <span class="badge {{ $hasDocs ? 'badge-soft-success' : 'badge-soft-secondary' }}">{{ $ep->documents->count() }} dok</span>
                            </div>
                        </div>
                        <div id="ep-{{ $ep->id }}" class="collapse ep-body">
                            <div class="card-body pt-2">
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <div class="small text-muted text-uppercase">Elemen Penilaian</div>
                                        <div>{{ $ep->elemen_penilaian ?: '-' }}</div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <div class="small text-muted text-uppercase">Kelengkapan Bukti</div>
                                        <div>{{ $ep->kelengkapan_bukti ?: '-' }}</div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-2 mb-1">
                                    <span class="small text-muted text-uppercase">Dokumen Bukti</span>
                                    <span>
                                        @if($isAdmin)
                                            <span class="akr-admin mr-2">
                                                <a href="#" class="js-ep-form text-muted mr-1" data-url="{{ route('akreditasi.ep.update', $ep) }}" data-method="PUT"
                                                   data-ep="{{ json_encode($ep->only(['name', 'elemen_penilaian', 'kelengkapan_bukti', 'skor_maksimal'])) }}" title="Ubah EP"><i class="fas fa-pen"></i></a>
                                                <a href="#" class="js-delete text-muted" data-url="{{ route('akreditasi.ep.destroy', $ep) }}" data-label="{{ $ep->name }}" title="Hapus EP"><i class="fas fa-trash"></i></a>
                                            </span>
                                        @endif
                                        <button class="btn btn-sm btn-primary js-upload" data-url="{{ route('akreditasi.ep.document.upload', $ep) }}" data-ep="{{ $ep->name }}">
                                            <i class="fas fa-upload mr-1"></i> Upload
                                        </button>
                                    </span>
                                </div>

                                @forelse($ep->documents as $doc)
                                    <div class="akr-doc d-flex align-items-center py-2">
                                        <i class="fas {{ $fileIcon($doc->filename) }} fa-lg mr-2" style="width:20px;"></i>
                                        <a href="{{ asset('storage/' . $doc->filepath) }}" target="_blank" class="text-truncate mr-auto">{{ $doc->filename }}</a>
                                        <span class="small text-muted mx-3 d-none d-sm-inline">{{ optional($doc->created_at)->format('d M Y') }}</span>
                                        <a href="#" class="js-delete text-danger" data-url="{{ route('akreditasi.document.destroy', $doc) }}" data-label="dokumen {{ $doc->filename }}" title="Hapus dokumen"><i class="fas fa-trash"></i></a>
                                    </div>
                                @empty
                                    <div class="small text-muted py-2">Belum ada dokumen.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card"><div class="card-body text-center text-muted py-4">
                        Belum ada EP di standar ini.
                    </div></div>
                @endforelse
            @endif
        </div>
    </div>
</div>

{{-- Name modal (BAB / Standar) --}}
<div class="modal fade" id="nameModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-sm" role="document">
    <form class="modal-content" id="nameForm">
      <div class="modal-header py-2">
        <h6 class="modal-title" id="nameTitle"></h6>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="text" class="form-control" name="name" id="nameInput" maxlength="255" required>
      </div>
      <div class="modal-footer py-2">
        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
      </div>
    </form>
  </div>
</div>

{{-- EP modal --}}
<div class="modal fade" id="epModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <form class="modal-content" id="epForm">
      <div class="modal-header py-2">
        <h6 class="modal-title" id="epTitle"></h6>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group col-8">
            <label class="small">Nama EP</label>
            <input type="text" class="form-control" name="name" maxlength="255" required>
          </div>
          <div class="form-group col-4">
            <label class="small">Skor Maksimal</label>
            <input type="number" class="form-control" name="skor_maksimal" min="0" required>
          </div>
        </div>
        <div class="form-group">
          <label class="small">Elemen Penilaian</label>
          <textarea class="form-control" name="elemen_penilaian" rows="3" maxlength="255"></textarea>
        </div>
        <div class="form-group mb-0">
          <label class="small">Kelengkapan Bukti</label>
          <textarea class="form-control" name="kelengkapan_bukti" rows="3" maxlength="255" required></textarea>
        </div>
      </div>
      <div class="modal-footer py-2">
        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
      </div>
    </form>
  </div>
</div>

{{-- Upload modal --}}
<div class="modal fade" id="uploadModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <form class="modal-content" id="uploadForm" enctype="multipart/form-data">
      <div class="modal-header py-2">
        <h6 class="modal-title">Upload dokumen · <span id="uploadEp"></span></h6>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <input type="file" class="form-control-file" name="document" required
                 accept="image/*,video/*,application/pdf,.doc,.docx,.xls,.xlsx">
        </div>
        <div class="form-group mb-0">
          <label class="small text-muted">Nama file (opsional)</label>
          <input type="text" class="form-control form-control-sm" name="custom_filename" maxlength="200" placeholder="Kosongkan untuk memakai nama asli">
        </div>
      </div>
      <div class="modal-footer py-2">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-upload mr-1"></i> Upload</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    var formUrl, formMethod;

    function errorMessage(xhr) {
        var res = xhr.responseJSON || {};
        if (res.errors) return Object.values(res.errors)[0][0];
        return res.message || 'Terjadi kesalahan.';
    }

    function send(url, method, data, isFile) {
        Swal.fire({ title: 'Menyimpan...', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
        var opts = { url: url, type: 'POST', data: data };
        if (isFile) { opts.processData = false; opts.contentType = false; }
        if (method !== 'POST') {
            if (isFile) data.append('_method', method); else opts.data += '&_method=' + method;
        }
        return $.ajax(opts).fail(function (xhr) { Swal.fire('Gagal', errorMessage(xhr), 'error'); });
    }

    // Keep the opened EP cards open after a reload
    var openKey = 'akr-open-{{ optional($standar)->id }}';
    function rememberOpen() {
        try { sessionStorage.setItem(openKey, JSON.stringify($('.ep-body.show').map(function () { return this.id; }).get())); } catch (e) {}
    }
    try { (JSON.parse(sessionStorage.getItem(openKey)) || []).forEach(function (id) { $('#' + id).addClass('show'); }); } catch (e) {}
    if (!$('.ep-body.show').length && $('.ep-body').length === 1) $('.ep-body').addClass('show');
    $('.ep-body').on('shown.bs.collapse hidden.bs.collapse', rememberOpen);

    function reload() { rememberOpen(); location.reload(); }

    $('#toggleAll').on('click', function () {
        var open = $('.ep-body.show').length < $('.ep-body').length;
        $('.ep-body').collapse(open ? 'show' : 'hide');
        $(this).text(open ? 'Tutup semua' : 'Buka semua');
    });

    // Filter standar list
    $('#akrSearch').on('input', function () {
        var q = $(this).val().toLowerCase();
        $('.akr-standar').each(function () { $(this).toggleClass('d-none', q && $(this).data('search').indexOf(q) === -1); });
        $('.akr-bab-group').each(function () { $(this).toggle(!q || $(this).find('.akr-standar:not(.d-none)').length > 0); });
    });

    // BAB / Standar add & rename
    $(document).on('click', '.js-name-form', function (e) {
        e.preventDefault(); e.stopPropagation();
        formUrl = $(this).data('url'); formMethod = $(this).data('method');
        var kind = $(this).data('kind') === 'bab' ? 'BAB' : 'Standar';
        $('#nameTitle').text((formMethod === 'POST' ? 'Tambah ' : 'Ubah ') + kind);
        $('#nameInput').val($(this).data('name') || '');
        $('#nameModal').modal('show');
    });
    $('#nameModal').on('shown.bs.modal', function () { $('#nameInput').trigger('focus'); });
    $('#nameForm').on('submit', function (e) {
        e.preventDefault();
        send(formUrl, formMethod, $(this).serialize()).done(reload);
    });

    // EP add & edit
    $(document).on('click', '.js-ep-form', function (e) {
        e.preventDefault(); e.stopPropagation();
        formUrl = $(this).data('url'); formMethod = $(this).data('method');
        var ep = $(this).data('ep') || { name: 'EP ' + ($('.akr-ep').length + 1), skor_maksimal: 10 };
        var form = $('#epForm')[0];
        form.reset();
        ['name', 'elemen_penilaian', 'kelengkapan_bukti', 'skor_maksimal'].forEach(function (f) { form.elements[f].value = ep[f] == null ? '' : ep[f]; });
        $('#epTitle').text(formMethod === 'POST' ? 'Tambah EP' : 'Ubah ' + ep.name);
        $('#epModal').modal('show');
    });
    $('#epForm').on('submit', function (e) {
        e.preventDefault();
        send(formUrl, formMethod, $(this).serialize()).done(reload);
    });

    // Upload document
    $(document).on('click', '.js-upload', function (e) {
        e.preventDefault(); e.stopPropagation();
        formUrl = $(this).data('url');
        $('#uploadForm')[0].reset();
        $('#uploadEp').text($(this).data('ep'));
        $('#uploadModal').modal('show');
    });
    $('#uploadForm').on('submit', function (e) {
        e.preventDefault();
        send(formUrl, 'POST', new FormData(this), true).done(reload);
    });

    // Delete (BAB / Standar / EP / document)
    $(document).on('click', '.js-delete', function (e) {
        e.preventDefault(); e.stopPropagation();
        var url = $(this).data('url'), redirect = $(this).data('redirect');
        Swal.fire({
            title: 'Hapus ' + $(this).data('label') + '?',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Hapus', cancelButtonText: 'Batal', confirmButtonColor: '#d33'
        }).then(function (r) {
            if (!r.value) return;
            send(url, 'DELETE', '').done(function () { redirect ? location.href = redirect : reload(); });
        });
    });
});
</script>
@endsection
