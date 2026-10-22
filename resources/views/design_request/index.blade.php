@extends('layouts.marketing.app')

@section('title', 'Request Desain')

@section('navbar')
    @include('layouts.shared.sidebar')
@endsection

@push('styles')
@include('marketing.partials.workflow_styles')
<style>
/* ---------- toolbar ---------- */
.dr-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 14px; }
.dr-filters { display: flex; gap: 6px; overflow-x: auto; scrollbar-width: none; flex: 1 1 auto; min-width: 0; }
.dr-filters::-webkit-scrollbar { display: none; }
.dr-filters .cp-chip i { font-size: .72rem; opacity: .8; }
.dr-search { flex: 0 1 240px; min-width: 0; border-radius: 8px; }
@media (max-width: 767.98px) { .dr-search { flex: 1 1 100%; } }

/* ---------- Antrian: one column per status (2 on tablets, stacked on phones) ---------- */
.dr-board { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; align-items: start; }
@media (max-width: 1199.98px) { .dr-board { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 575.98px) { .dr-board { grid-template-columns: 1fr; gap: 8px; } }
.dr-col { background: var(--wf-surface-2); border-radius: 12px; min-width: 0; }
.dr-col-head { display: flex; align-items: center; gap: 8px; padding: 10px 12px 2px; }
.dr-col-title { font-weight: 700; font-size: .92rem; }
.dr-col-count { margin-left: auto; font-size: .7rem; font-weight: 700; padding: 1px 8px; border-radius: 999px; background: var(--wf-surface-solid); color: var(--wf-muted); }
.dr-col-hint { font-size: .74rem; color: var(--wf-muted); padding: 0 12px 8px; }
.dr-col-list { padding: 0 8px 8px; }
.dr-col-empty { color: var(--wf-muted); font-size: .76rem; text-align: center; padding: 14px 0; opacity: .7; }
.dr-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 12px; align-items: start; }

/* ---------- job card (same build as a Content Plan card, tinted with the jenis colour) ---------- */
.dr-item {
    position: relative; border-radius: 12px; padding: 12px 14px 10px; margin-bottom: 10px; cursor: pointer; color: var(--wf-text);
    background: linear-gradient(var(--jenis-bg, transparent), var(--jenis-bg, transparent)), var(--wf-surface-solid);
    box-shadow: 0 1px 2px rgba(0,0,0,.05), 0 0 0 1px var(--jenis-line, var(--wf-border)); transition: box-shadow .15s ease;
}
.dr-list .dr-item { margin-bottom: 0; }
.dr-item:last-child { margin-bottom: 0; }
.dr-item:hover, .dr-item:focus { outline: none; box-shadow: 0 6px 18px rgba(0,0,0,.10), 0 0 0 2px var(--jenis, var(--wf-accent)); }
/* ---------- designer workload ---------- */
.dr-cap { background: var(--wf-surface-solid); border-radius: 12px; box-shadow: 0 0 0 1px var(--wf-border); margin-bottom: 14px; }
.dr-cap > summary { list-style: none; cursor: pointer; padding: 10px 14px; display: flex; flex-wrap: wrap; align-items: center; gap: 6px 12px; }
.dr-cap > summary::-webkit-details-marker { display: none; }
.dr-cap > summary::after { content: '\f078'; font-family: 'Font Awesome 5 Free'; font-weight: 900; font-size: .7rem; color: var(--wf-muted); margin-left: auto; }
.dr-cap[open] > summary::after { content: '\f077'; }
.dr-cap-title { font-weight: 700; font-size: .9rem; }
.dr-cap-sum { display: flex; flex-wrap: wrap; gap: 6px; }
.dr-cap-pill { font-size: .74rem; font-weight: 700; padding: 2px 9px; border-radius: 999px; background: var(--wf-surface-2); }
.dr-cap-pill.ringan { background: var(--st-green-bg); color: var(--st-green-fg); }
.dr-cap-pill.sedang { background: var(--st-amber-bg); color: var(--st-amber-fg); }
.dr-cap-pill.padat { background: var(--st-red-bg); color: var(--st-red-fg); }
.dr-cap-pill.queue { background: var(--st-indigo-bg); color: var(--st-indigo-fg); }
.dr-cap-body { padding: 0 14px 12px; }
.dr-cap-row { display: grid; grid-template-columns: minmax(150px, 220px) 1fr; gap: 8px 16px; align-items: center; padding: 10px 0; border-top: 1px solid var(--wf-border); }
@media (max-width: 767.98px) { .dr-cap-row { grid-template-columns: 1fr; } }
.dr-cap-name { font-weight: 700; font-size: .88rem; }
.dr-cap-meta { font-size: .76rem; color: var(--wf-muted); margin-top: 2px; }
.dr-cap-meta .late { color: var(--wf-danger); font-weight: 700; }
.dr-cap-days { display: grid; grid-template-columns: repeat(14, minmax(18px, 1fr)); gap: 3px; }
.dr-cap-day { height: 28px; border-radius: 6px; background: var(--wf-surface-2); display: flex; align-items: center; justify-content: center; font-size: .7rem; font-weight: 700; color: var(--wf-muted); }
.dr-cap-day.d1 { background: var(--st-blue-bg); color: var(--st-blue-fg); }
.dr-cap-day.d2 { background: var(--st-red-bg); color: var(--st-red-fg); }
.dr-cap-day.is-weekend { opacity: .7; }
.dr-cap-dayhead { display: grid; grid-template-columns: repeat(14, minmax(18px, 1fr)); gap: 3px; font-size: .64rem; color: var(--wf-muted); text-align: center; margin-bottom: 3px; }
.dr-cap-note { font-size: .76rem; color: var(--wf-muted); margin-top: 8px; }

.dr-item.is-urgent { box-shadow: 0 0 0 2px var(--wf-danger); }
.dr-tag-urgent { background: var(--st-red-bg) !important; color: var(--st-red-fg) !important; }
.dr-item-top { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
.dr-jenis-ico {
    width: 24px; height: 24px; border-radius: 50%; flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center;
    font-size: .68rem; color: #fff; background: var(--jenis, var(--wf-muted));
}
.dr-item-jenis { font-size: .78rem; font-weight: 700; color: var(--wf-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dr-item-top .wf-pill { margin-left: auto; font-size: .7rem; padding: 1px 9px; }
.dr-item-title {
    font-weight: 700; font-size: .93rem; line-height: 1.35; word-break: break-word;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.dr-item-title small { color: var(--wf-muted); font-weight: 600; }
.dr-item-tags { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 8px; }
.dr-item-foot { display: flex; align-items: center; gap: 8px; margin-top: 10px; padding-top: 8px; border-top: 1px solid var(--wf-border); font-size: .78rem; }
.dr-who { display: inline-flex; align-items: center; gap: 6px; min-width: 0; flex: 1 1 auto; font-weight: 600; }
.dr-who span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dr-who i { color: var(--wf-muted); font-size: .72rem; }
.dr-who.is-empty { color: var(--wf-muted); font-weight: 400; }
.dr-due { flex: 0 0 auto; color: var(--wf-muted); font-weight: 600; white-space: nowrap; }
.dr-due.is-soon { color: var(--st-amber-fg); }
.dr-due.is-late { color: var(--wf-danger); }

/* ---------- detail ---------- */
.dr-head-ico { width: 30px; height: 30px; font-size: .8rem; }
.dr-section { margin-top: 18px; }
.dr-section-title { font-size: .78rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--wf-muted); margin-bottom: 8px; }
.dr-section-title .wf-muted { text-transform: none; letter-spacing: 0; font-weight: 400; }
.dr-facts { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 8px; }
.dr-fact { background: var(--wf-surface-2); border-radius: 10px; padding: 8px 12px; min-width: 0; }
.dr-fact dt { font-size: .72rem; font-weight: 600; color: var(--wf-muted); margin: 0; }
.dr-fact dd { margin: 2px 0 0; font-weight: 600; font-size: .88rem; word-break: break-word; }
.dr-brief { white-space: pre-wrap; word-break: break-word; background: var(--wf-surface-2); border-radius: 10px; padding: 10px 12px; font-size: .9rem; }
.dr-wf-hint { font-size: .85rem; color: var(--wf-muted); margin-top: 8px; }
.dr-wf-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.dr-wf-actions .btn { font-weight: 600; border-radius: 8px; }
.dr-files { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 8px; }
.dr-file { position: relative; border: 1px solid var(--wf-border); border-radius: 8px; overflow: hidden; background: var(--wf-surface); }
.dr-file a { display: block; color: inherit; text-decoration: none; }
.dr-file .thumb { aspect-ratio: 4 / 3; display: flex; align-items: center; justify-content: center; background: var(--wf-surface-2); }
.dr-file .thumb img { width: 100%; height: 100%; object-fit: cover; }
.dr-file .thumb i { font-size: 1.8rem; color: var(--wf-muted); }
.dr-file .name { font-size: .74rem; padding: 4px 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dr-file .del { position: absolute; top: 4px; right: 4px; width: 24px; height: 24px; border: 0; border-radius: 50%; background: rgba(201,42,42,.92); color: #fff; line-height: 1; }
.dr-version { border: 1px solid var(--wf-border); border-radius: 10px; padding: 10px; display: flex; gap: 10px; align-items: flex-start; }
.dr-version + .dr-version { margin-top: 8px; }
.dr-version .thumb { width: 64px; height: 64px; flex: 0 0 auto; border-radius: 8px; overflow: hidden; background: var(--wf-surface-2); display: flex; align-items: center; justify-content: center; }
.dr-version .thumb img { width: 100%; height: 100%; object-fit: cover; }
.dr-version .thumb i { font-size: 1.5rem; color: var(--wf-muted); }
.dr-version.is-latest { border-color: var(--wf-accent); }
.dr-upload { border: 1px dashed var(--wf-border); border-radius: 10px; padding: 10px 12px; }
.dr-upload summary { font-weight: 600; font-size: .85rem; cursor: pointer; color: var(--wf-accent); }
.dr-wa-note { display: flex; align-items: center; gap: 8px; font-size: .85rem; color: var(--wf-muted); background: var(--wf-surface-2); border-radius: 10px; padding: 10px 12px; }
.dr-wa-note i { color: #25d366; font-size: 1.1rem; }

/* ---------- form ---------- */
.dr-form-hint { font-size: .8rem; color: var(--wf-muted); margin-top: 8px; }
.dr-form-group-title { font-size: .78rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--wf-muted); margin: 18px 0 10px; padding-top: 16px; border-top: 1px solid var(--wf-border); }
.dr-picked { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
.dr-picked:empty { display: none; }
</style>
@endpush

@section('content')
<div class="container-fluid cp-page">
    <div class="cp-top">
        <div>
            <h4>Request Desain</h4>
            <p>Antrian kerja designer: kemasan, materi cetak, konten sosmed &amp; lainnya.</p>
        </div>
        <div class="cp-top-actions">
            <button type="button" class="btn btn-light cp-install" id="btnNotif" style="display:none"><i class="far fa-bell mr-1"></i> Aktifkan notifikasi</button>
            <button type="button" class="btn btn-primary cp-new" id="btnNewRequest"><i class="fas fa-plus mr-1"></i> Request Baru</button>
        </div>
    </div>

    <div class="cp-tabsbar">
        <div class="nav cp-seg" id="drTabs" role="tablist">
            @if($isMarketing)
                <a class="nav-link active" href="#" data-scope="aktif"><i class="fas fa-columns mr-1"></i> Antrian <span class="wf-count" data-count="aktif" style="display:none"></span></a>
                <a class="nav-link" href="#" data-scope="tugas"><i class="fas fa-pen-nib mr-1"></i> Tugas Saya <span class="wf-count" data-count="tugas" style="display:none"></span></a>
                <a class="nav-link" href="#" data-scope="saya"><i class="far fa-paper-plane mr-1"></i> Request Saya <span class="wf-count" data-count="saya" style="display:none"></span></a>
            @else
                <a class="nav-link active" href="#" data-scope="saya"><i class="far fa-paper-plane mr-1"></i> Request Saya <span class="wf-count" data-count="saya" style="display:none"></span></a>
            @endif
            <a class="nav-link" href="#" data-scope="selesai"><i class="fas fa-check mr-1"></i> Selesai</a>
        </div>
    </div>

    <div class="dr-toolbar">
        <div class="dr-filters" id="drJenisChips">
            <button type="button" class="cp-chip active" data-jenis="">Semua</button>
            @foreach($jenisOptions as $j)
                <button type="button" class="cp-chip" data-jenis="{{ $j }}"><i class="fas js-jenis-icon" data-jenis-icon="{{ $j }}"></i> {{ $j }}</button>
            @endforeach
        </div>
        <input type="search" class="form-control form-control-sm dr-search" id="drSearch" placeholder="Cari judul atau #nomor…">
    </div>

    @if($isMarketing)
        {{-- workload of the designers: who is full, whose deadlines pile up, what nobody took yet --}}
        <details class="dr-cap" id="drCapacity">
            <summary><span class="dr-cap-title"><i class="fas fa-users mr-1"></i>Beban kerja designer</span><span class="dr-cap-sum" id="drCapSum"></span></summary>
            <div class="dr-cap-body" id="drCapBody"></div>
        </details>
    @endif

    <div id="drContent"></div>
    <div class="text-center mt-3"><button type="button" class="btn btn-light" id="drMore" style="display:none">Muat lagi</button></div>
</div>

{{-- Detail: workflow + info on the left, riwayat on the right (like a Content Plan item) --}}
<div class="modal fade wf-modal" id="drDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header align-items-center">
                <div class="d-flex align-items-center" style="gap:10px;min-width:0">
                    <span id="drDetailIcon"></span>
                    <h5 class="modal-title mb-0 text-truncate" id="drDetailTitle">Request</h5>
                    <span id="drDetailStatus"></span>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-8" id="drDetailMain"></div>
                    <div class="col-lg-4">
                        <div class="wf-side">
                            <div class="wf-side-title"><i class="fas fa-history"></i> Riwayat
                                <button type="button" class="btn btn-sm btn-light ml-auto js-action" data-action="comment" id="drCommentBtn" style="display:none"><i class="far fa-comment mr-1"></i> Komentar</button>
                            </div>
                            <div id="drTimeline"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light text-danger mr-auto js-action" data-action="cancel" id="drCancelBtn" style="display:none"><i class="fas fa-times mr-1"></i> Batalkan request</button>
                <button type="button" class="btn btn-light" id="drEditBtn" style="display:none"><i class="fas fa-edit mr-1"></i> Edit</button>
                <button type="button" class="btn btn-light" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- Buat / edit --}}
<div class="modal fade wf-modal" id="drFormModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <form id="drForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="drFormTitle">Request Desain Baru</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="content_plan_id" id="drf_content_plan_id">
                    <input type="hidden" name="rnd_produk_id" id="drf_rnd_produk_id">
                    <input type="hidden" name="rnd_kemasan_sisi" id="drf_rnd_kemasan_sisi">
                    <input type="hidden" name="jenis" id="drf_jenis" value="{{ $jenisOptions[0] ?? '' }}">
                    <div id="drfSource" class="cp-workflow mb-3 small" style="display:none"></div>

                    <div class="form-group">
                        <label>Mau dibuatkan apa? <span class="text-danger">*</span></label>
                        <div class="cp-chips" id="drfJenisChips">
                            @foreach($jenisOptions as $j)
                                <button type="button" class="cp-chip-opt" data-value="{{ $j }}"><i class="fas js-jenis-icon" data-jenis-icon="{{ $j }}"></i> {{ $j }}</button>
                            @endforeach
                        </div>
                        <div class="dr-form-hint" id="drfSosmedHint" style="display:none">
                            <i class="fas fa-info-circle mr-1"></i>Konten yang dijadwalkan di Content Plan cukup pilih <strong>Butuh designer</strong> di sana; job otomatis masuk ke sini.
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="drf_judul">Judul <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="drf_judul" name="judul" maxlength="255" required placeholder="Contoh: Brosur promo Desember klinik Premiere">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-sm-6">
                            <label for="drf_deadline">Deadline</label>
                            <input type="date" class="form-control" id="drf_deadline" name="deadline">
                        </div>
                        @if($isMarketing)
                            <div class="form-group col-sm-6" id="drfDesignerGroup">
                                <label for="drf_designer">Designer <span class="text-muted font-weight-normal text-lowercase">(opsional)</span></label>
                                <select class="form-control" id="drf_designer" name="designer_id">
                                    <option value="">Biarkan designer mengambil sendiri</option>
                                    @foreach($designers as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="drf_brief">Brief / kebutuhan</label>
                        <textarea class="form-control" id="drf_brief" name="brief" rows="4" placeholder="Tujuan, teks yang harus ada, warna/gaya, tempat dipakai…"></textarea>
                    </div>

                    <div id="drfSpec">
                        <div class="dr-form-group-title">Spesifikasi <span class="text-muted font-weight-normal text-lowercase" style="letter-spacing:0">(opsional)</span></div>
                        <div class="form-row">
                            <div class="form-group col-sm-4">
                                <label for="drf_ukuran">Ukuran</label>
                                <input type="text" class="form-control" id="drf_ukuran" name="spesifikasi[ukuran]" placeholder="A5, 60x160 cm…">
                            </div>
                            <div class="form-group col-sm-4">
                                <label for="drf_bahan">Bahan</label>
                                <input type="text" class="form-control" id="drf_bahan" name="spesifikasi[bahan]" placeholder="Art paper 150gsm…">
                            </div>
                            <div class="form-group col-sm-4">
                                <label for="drf_jumlah">Jumlah</label>
                                <input type="text" class="form-control" id="drf_jumlah" name="spesifikasi[jumlah]" placeholder="500 lembar">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="drf_catatan">Catatan cetak / vendor</label>
                            <input type="text" class="form-control" id="drf_catatan" name="spesifikasi[catatan]" placeholder="Vendor, finishing, format file yang diminta vendor…">
                        </div>
                    </div>

                    <div class="dr-form-group-title">Referensi <span class="text-muted font-weight-normal text-lowercase" style="letter-spacing:0">(opsional)</span></div>
                    <label class="cp-drop cp-drop-compact d-block mb-0" for="drf_referensi" tabindex="0">
                        <i class="fas fa-paperclip mr-1"></i> Pilih gambar / PDF contoh <span class="d-block small mt-1">Maks. 10 file, masing-masing 10 MB</span>
                    </label>
                    <input type="file" class="d-none" id="drf_referensi" name="referensi[]" accept="image/*,application/pdf" multiple>
                    <div class="dr-picked" id="drfPicked"></div>
                    <div id="drfExisting" class="dr-files mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="drfSubmit"><i class="fas fa-paper-plane mr-1"></i> Kirim Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@include('marketing.partials.workflow_scripts')
<script>
$(function () {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    var W = window.MkWf, esc = W.esc;
    var IS_MARKETING = @json($isMarketing);
    var ME = @json(auth()->id());
    var DESIGNERS = @json($designers);
    var REVIEWERS = @json($reviewers);
    var SELF_APPROVER = @json($selfApprover);
    var PREFILL = @json($prefill);
    var OPEN_ID = @json($openId);
    var MAX_MB = @json($maxFileMb);
    var RESULT_EXT = @json($resultExtensions);
    var BASE = @json(url('/design-request'));
    var CP_URL = @json(url('/marketing/content-plan'));

    var scope = IS_MARKETING ? 'aktif' : 'saya';
    var jenis = '', page = 1, searchTimer = null, current = null, items = [];

    // each kind of job has its own icon + colour (card tint), like a clinic colour on Content Plan cards
    var JENIS = {
        'Konten Sosmed': { icon: 'fa-hashtag', color: '#7048e8' },
        'Kemasan': { icon: 'fa-box-open', color: '#e8590c' },
        'Cetak': { icon: 'fa-print', color: '#0ca678' },
        'Lainnya': { icon: 'fa-shapes', color: '#868e96' }
    };
    function jenisInfo(j) { return JENIS[j] || JENIS.Lainnya; }
    $('.js-jenis-icon').each(function () { $(this).addClass(jenisInfo($(this).data('jenis-icon')).icon); });

    function isDark() { return document.documentElement.classList.contains('theme-dark'); }
    function rgba(hex, a) {
        var n = parseInt(hex.slice(1), 16);
        return 'rgba(' + (n >> 16 & 255) + ',' + (n >> 8 & 255) + ',' + (n & 255) + ',' + a + ')';
    }
    function jenisIcon(j, extra) {
        var info = jenisInfo(j);
        return '<span class="dr-jenis-ico ' + (extra || '') + '" style="--jenis:' + info.color + '" title="' + esc(j) + '"><i class="fas ' + info.icon + '"></i></span>';
    }

    var COLUMNS = [
        { status: 'Baru', hint: 'Belum mulai dikerjakan' },
        { status: 'Dikerjakan', hint: 'Sedang dikerjakan designer' },
        { status: 'Revisi', hint: 'Ada revisi masuk' },
        { status: 'Review', hint: 'Hasil sudah dikirim, menunggu keputusan' }
    ];

    // =================================================================== CARD
    var CARD_ACTIONS = {
        take: { icon: 'fas fa-play', cls: 'is-primary' },
        submit_review: { icon: 'fas fa-check', cls: 'is-success' },
        approve: { icon: 'fas fa-check', cls: 'is-success' },
        revise: { icon: 'fas fa-undo', cls: 'is-warning' }
    };
    function wait(icon, text, title) {
        return '<div class="cp-item-actions"><span class="cp-act is-wait"' + (title ? ' title="' + esc(title) + '"' : '') + '><i class="' + icon + '"></i> ' + esc(text) + '</span></div>';
    }

    // The card shows the one next step (or who it is waiting for); everything else is in the detail
    function cardActions(it) {
        var active = ['Baru', 'Dikerjakan', 'Review', 'Revisi'].indexOf(it.status) !== -1;
        if (!active) return '';
        if (it.waiting_brief) return wait('fas fa-hourglass-half', 'Menunggu brief konten', 'Job bisa diambil setelah content creator mengisi brief');
        if (it.is_content_job && it.status === 'Review') return wait('fab fa-whatsapp', 'Approval di grup WA', 'Approval / revisi dicatat content creator di Content Plan');

        var otherDesigner = it.designer_id && String(it.designer_id) !== String(ME);
        if (otherDesigner && it.status === 'Baru') return wait('fas fa-hourglass-half', 'Menunggu ' + it.designer + ' mulai');
        if (otherDesigner && (it.status === 'Dikerjakan' || it.status === 'Revisi')) {
            return wait('fas fa-pen-nib', (it.status === 'Revisi' ? 'Direvisi ' : 'Dikerjakan ') + it.designer);
        }

        var list = (it.actions || []).filter(function (a) {
            return CARD_ACTIONS[a.action] && !(a.action === 'revise' && it.status !== 'Review');
        });
        if (list.length) {
            return '<div class="cp-item-actions">' + list.map(function (a) {
                var c = CARD_ACTIONS[a.action];
                return '<button type="button" class="cp-act js-card-action ' + c.cls + '" data-action="' + a.action + '">'
                    + '<i class="' + c.icon + '"></i> ' + esc(a.label) + '</button>';
            }).join('') + '</div>';
        }
        // requester without workflow buttons: show where the job is
        if (it.status === 'Baru') return wait('fas fa-hourglass-half', 'Menunggu diambil designer');
        if (it.status === 'Review') return wait('fab fa-whatsapp', 'Menunggu approval Marketing');
        return wait('fas fa-pen-nib', (it.status === 'Revisi' ? 'Direvisi ' : 'Dikerjakan ') + (it.designer || 'designer'));
    }

    function dueHtml(it) {
        if (it.status === 'Selesai') return '<span class="dr-due"><i class="fas fa-check mr-1"></i>' + (it.completed_at ? W.date(it.completed_at) : 'Selesai') + '</span>';
        if (it.status === 'Dibatalkan') return '<span class="dr-due">Dibatalkan</span>';
        if (!it.deadline) return '<span class="dr-due">Tanpa deadline</span>';
        var dl = W.deadline(it.deadline);
        var text = dl.state === 'late' || dl.state === 'soon' ? dl.text : W.date(it.deadline, { weekday: false });
        return '<span class="dr-due ' + (dl.state === 'late' ? 'is-late' : (dl.state === 'soon' ? 'is-soon' : '')) + '" title="Deadline ' + esc(W.date(it.deadline, { year: true })) + '">'
            + (dl.state === 'late' ? '<i class="fas fa-exclamation-triangle mr-1"></i>' : '<i class="far fa-clock mr-1"></i>') + esc(text) + '</span>';
    }

    // Card, top to bottom: jenis + status / title / tags (sumber, revisi, file) / designer + deadline / next step
    function card(it, withPill) {
        var info = jenisInfo(it.jenis);
        var tags = '';
        var urgent = { tomorrow: 'Deadline besok, belum diambil', today: 'Deadline hari ini, belum diambil', late: 'Lewat deadline, belum diambil' }[it.urgency];
        if (urgent) tags += '<span class="cp-tag dr-tag-urgent"><i class="fas fa-exclamation-triangle"></i>' + urgent + '</span>';
        if (it.content_plan_id) tags += '<span class="cp-tag" title="' + esc(it.source || '') + '"><i class="far fa-calendar"></i>Content Plan</span>';
        else if (it.rnd_produk_id) tags += '<span class="cp-tag" title="' + esc(it.source || '') + '"><i class="fas fa-flask"></i>Produk R&amp;D</span>';
        if (it.revisi_count) tags += '<span class="cp-tag cp-tag-rev" title="Jumlah revisi yang dicatat"><i class="fas fa-undo"></i>' + it.revisi_count + '× revisi</span>';
        if (it.hasil_count) tags += '<span class="cp-tag"><i class="fas fa-layer-group"></i>' + it.hasil_count + ' file</span>';
        if (it.requester && (scope !== 'saya')) tags += '<span class="cp-tag" title="Peminta"><i class="far fa-user"></i>' + esc(it.requester) + '</span>';

        var who = it.designer
            ? '<span class="dr-who" title="Designer"><i class="fas fa-pen-nib"></i><span>' + esc(it.designer) + '</span></span>'
            : '<span class="dr-who is-empty"><span>Belum ada designer</span></span>';
        var style = ' style="--jenis:' + info.color + ';--jenis-bg:' + rgba(info.color, isDark() ? .14 : .06) + ';--jenis-line:' + rgba(info.color, .3) + '"';
        return '<div class="dr-item js-open' + (urgent ? ' is-urgent' : '') + '" tabindex="0" role="button" data-id="' + it.id + '"' + style + '>'
            + '<div class="dr-item-top">' + jenisIcon(it.jenis) + '<span class="dr-item-jenis">' + esc(it.jenis) + '</span>'
            + (withPill ? W.pill('design', it.status) : '') + '</div>'
            + '<div class="dr-item-title">' + esc(it.judul) + ' <small>#' + it.id + '</small></div>'
            + (tags ? '<div class="dr-item-tags">' + tags + '</div>' : '')
            + '<div class="dr-item-foot">' + who + dueHtml(it) + '</div>'
            + cardActions(it)
            + '</div>';
    }

    var lastKey = null;
    function load(reset, silent) {
        if (reset) page = 1;
        var params = { scope: scope, jenis: jenis, q: $.trim($('#drSearch').val()), page: page, include_done: scope === 'saya' ? 0 : undefined };
        $.getJSON(BASE + '/list', params)
            .done(function (r) {
                // the 10-second refresh only redraws when something changed
                var key = JSON.stringify([params, r]);
                if (silent && key === lastKey) return;
                lastKey = key;
                var rows = r.data || [];
                items = page === 1 ? rows : items.concat(rows);
                if (scope === 'aktif') {
                    $('#drContent').html('<div class="dr-board">' + COLUMNS.map(function (c) {
                        var col = rows.filter(function (i) { return i.status === c.status; });
                        return '<div class="dr-col"><div class="dr-col-head">' + W.pill('design', c.status) + '<span class="dr-col-count">' + col.length + '</span></div>'
                            + '<div class="dr-col-hint">' + esc(c.hint) + '</div>'
                            + '<div class="dr-col-list">' + (col.length ? col.map(function (i) { return card(i, false); }).join('') : '<div class="dr-col-empty">Kosong</div>') + '</div></div>';
                    }).join('') + '</div>');
                } else {
                    var html = rows.map(function (i) { return card(i, true); }).join('');
                    if (page === 1) $('#drContent').html('<div class="dr-list" id="drList">' + (html || '<div class="wf-empty" style="grid-column:1/-1">' + emptyText() + '</div>') + '</div>');
                    else $('#drList').append(html);
                }
                $('#drMore').toggle(!!r.has_more);
            });
        loadSummary();
        loadCapacity();
    }

    // ---- designer workload
    var LEVEL_LABEL = { ringan: 'Ringan', sedang: 'Sedang', padat: 'Padat' };
    function loadCapacity() {
        if (!IS_MARKETING) return;
        $.getJSON(BASE + '/capacity').done(function (c) {
            var people = (c.designers || []).filter(function (d) { return d.active > 0; });
            var idle = (c.designers || []).filter(function (d) { return !d.active; });
            $('#drCapSum').html(people.map(function (d) {
                return '<span class="dr-cap-pill ' + d.level + '" title="' + esc(LEVEL_LABEL[d.level]) + '">' + esc(d.name.split(' ')[0]) + ' · ' + d.active + ' job</span>';
            }).join('') + (c.queue.count ? '<span class="dr-cap-pill queue">Antrian · ' + c.queue.count + ' job</span>' : '')
                + (!people.length && !c.queue.count ? '<span class="dr-cap-pill">Tidak ada job aktif</span>' : ''));

            var head = '<div class="dr-cap-dayhead">' + c.days.map(function (d) {
                var m = moment(d);
                return '<span>' + (m.isSame(moment(), 'day') ? 'Hari ini' : W.HARI[m.day()].slice(0, 2) + ' ' + m.date()) + '</span>';
            }).join('') + '</div>';
            var row = function (d) {
                var s = d.by_status;
                var parts = [];
                ['Baru', 'Dikerjakan', 'Revisi', 'Review'].forEach(function (k) { if (s[k]) parts.push(s[k] + ' ' + k.toLowerCase()); });
                var days = d.per_day.map(function (day) {
                    var m = moment(day.date);
                    var cls = day.count >= 2 ? 'd2' : (day.count === 1 ? 'd1' : '');
                    return '<span class="dr-cap-day ' + cls + (m.day() === 0 || m.day() === 6 ? ' is-weekend' : '') + '" title="' + esc(W.date(day.date)) + ': '
                        + (day.count ? day.count + ' deadline · ' + day.jobs.join(', ') : 'tidak ada deadline') + '">' + (day.count || '') + '</span>';
                }).join('');
                return '<div class="dr-cap-row"><div><div class="dr-cap-name">' + esc(d.name) + ' <span class="dr-cap-pill ' + d.level + '">' + LEVEL_LABEL[d.level] + '</span></div>'
                    + '<div class="dr-cap-meta">' + d.active + ' job aktif' + (parts.length ? ' (' + parts.join(', ') + ')' : '')
                    + (d.due_week ? ' · ' + d.due_week + ' deadline minggu ini' : '')
                    + (d.late ? ' · <span class="late">' + d.late + ' terlambat</span>' : '')
                    + (d.clashes ? ' · <span class="late">' + d.clashes + ' hari deadline bertumpuk</span>' : '') + '</div></div>'
                    + '<div>' + head + '<div class="dr-cap-days">' + days + '</div></div></div>';
            };
            $('#drCapBody').html(people.map(row).join('')
                + (c.queue.count ? '<div class="dr-cap-row"><div><div class="dr-cap-name">Antrian (belum ada designer)</div><div class="dr-cap-meta">' + c.queue.count + ' job menunggu diambil</div></div>'
                    + '<div class="dr-cap-meta">' + c.queue.jobs.map(function (j) { return esc(j.judul) + (j.deadline ? ' (deadline ' + esc(W.date(j.deadline, { weekday: false })) + ')' : ''); }).join(' · ') + '</div></div>' : '')
                + (idle.length ? '<div class="dr-cap-note">Tanpa job aktif: ' + idle.map(function (d) { return esc(d.name); }).join(', ') + '.</div>' : '')
                + '<div class="dr-cap-note">Kotak per hari = jumlah deadline: biru 1 job, merah 2 job atau lebih (bertumpuk). Beban: ringan &lt; ' + c.levels.sedang
                + ' job aktif, sedang ' + c.levels.sedang + '–' + (c.levels.padat - 1) + ', padat ≥ ' + c.levels.padat + '.</div>');
        });
    }

    function emptyText() {
        if (scope === 'tugas') return 'Belum ada pekerjaan yang ditugaskan ke Anda.';
        if (scope === 'saya') return 'Anda belum punya request aktif. Klik <strong>Request Baru</strong> untuk meminta desain.';
        return 'Tidak ada data.';
    }

    function loadSummary() {
        $.getJSON(BASE + '/summary').done(function (s) {
            $('#drTabs [data-count]').each(function () {
                var n = s[$(this).data('count')] || 0;
                $(this).text(n).toggle(n > 0);
            });
        });
    }

    $('#drTabs').on('click', '.nav-link', function (e) {
        e.preventDefault();
        $('#drTabs .nav-link').removeClass('active');
        $(this).addClass('active');
        scope = $(this).data('scope');
        load(true);
    });
    $('#drJenisChips').on('click', '.cp-chip', function () {
        $('#drJenisChips .cp-chip').removeClass('active');
        $(this).addClass('active');
        jenis = $(this).data('jenis');
        load(true);
    });
    $('#drSearch').on('input', function () { clearTimeout(searchTimer); searchTimer = setTimeout(function () { load(true); }, 350); });
    $('#drMore').on('click', function () { page++; load(false); });
    $(document).on('click keydown', '.js-open', function (e) {
        if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
        if ($(e.target).closest('.js-card-action').length) return;
        e.preventDefault();
        openDetail($(this).data('id'));
    });
    $(document).on('click', '.js-card-action', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var id = $(this).closest('.dr-item').data('id');
        var it = items.filter(function (x) { return String(x.id) === String(id); })[0];
        if (it) runAction(it, $(this).data('action'));
    });

    // =================================================================== WORKFLOW ACTIONS (card + detail)
    function runAction(job, action, onDone) {
        var a = (job.actions || []).filter(function (x) { return x.action === action; })[0];
        if (!a) return;
        var title = job.judul ? '“' + job.judul + '”' : undefined;
        var opts = { title: a.label + '?', note: a.note, confirmText: a.label, confirmColor: W.buttonStyleColor(a.style), text: title };

        if (action === 'take') {
            opts.text = 'Anda tercatat sebagai designer' + (job.is_content_job ? '; konten ikut masuk Produksi.' : '.');
        }
        if (action === 'submit_review') {
            opts.title = 'Selesai dikerjakan?';
            opts.text = 'Pastikan hasilnya sudah dikirim di grup WA tim' + (job.is_content_job ? '; konten otomatis masuk Review.' : '.');
            opts.placeholder = 'Catatan untuk tim (opsional)';
        }
        if (action === 'comment') opts = { title: 'Tambah komentar', note: 'required', placeholder: 'Tulis komentar…', confirmText: 'Kirim' };
        if (action === 'cancel') { opts.title = 'Batalkan request?'; opts.placeholder = 'Alasan dibatalkan'; }
        if (action === 'assign') {
            opts.text = undefined;
            opts.html = '<div class="text-left"><label class="d-block mb-1" style="text-transform:none">Tugaskan ke</label>'
                + '<select id="drAssignSelect" class="swal2-select w-100 m-0">' + DESIGNERS.map(function (u) {
                    return '<option value="' + u.id + '"' + (String(u.id) === String(job.designer_id) ? ' selected' : '') + '>' + esc(u.name) + '</option>';
                }).join('') + '</select><div class="small text-muted mt-2">Job tetap menunggu sampai designer menekan <b>Mulai kerjakan</b>.</div></div>';
            opts.note = null;
            opts.preConfirm = function () { return $('#drAssignSelect').val(); };
        }
        if (action === 'approve' || action === 'revise') {
            // decided in the team's WA group: record who said it + paste the message (same as Content Plan)
            var isRevise = action === 'revise';
            var preset = !isRevise && SELF_APPROVER ? SELF_APPROVER : '';
            var names = preset && REVIEWERS.indexOf(preset) === -1 ? [preset].concat(REVIEWERS) : REVIEWERS;
            opts.note = null;
            opts.text = undefined;
            opts.title = isRevise ? 'Catat revisi' : 'Catat approval';
            opts.html = '<div class="text-left">'
                + (job.judul ? '<div class="small text-muted mb-2">“' + esc(job.judul) + '”</div>' : '')
                + '<label class="d-block mb-1" for="drActor" style="text-transform:none">' + (isRevise ? 'Revisi dari siapa?' : 'Di-approve oleh siapa?') + '</label>'
                + '<select id="drActor" class="swal2-select m-0 w-100"><option value="">— pilih —</option>'
                + names.map(function (n) { return '<option' + (n === preset ? ' selected' : '') + '>' + esc(n) + '</option>'; }).join('')
                + '<option value="__other">Lainnya…</option></select>'
                + '<input id="drActorOther" class="swal2-input m-0 mt-2 w-100" placeholder="Tulis nama" style="display:none">'
                + '<label class="d-block mt-3 mb-1" for="drDecisionNote" style="text-transform:none">'
                + (isRevise ? 'Isi revisi <span class="text-danger">*</span> <span class="text-muted small">(tempel dari WA)</span>' : 'Catatan <span class="text-muted small">(opsional)</span>') + '</label>'
                + '<textarea id="drDecisionNote" class="swal2-textarea m-0 w-100" rows="4" placeholder="'
                + (isRevise ? 'Mis. logo diperkecil, warna ganti ke hijau' : 'Mis. ok cetak 500 lembar') + '"></textarea></div>';
            opts.didOpen = function () {
                $('#drActor').on('change', function () { $('#drActorOther').toggle(this.value === '__other').focus(); });
            };
            opts.preConfirm = function () {
                var actor = $('#drActor').val() === '__other' ? $.trim($('#drActorOther').val()) : $('#drActor').val();
                var note = $.trim($('#drDecisionNote').val());
                if (!actor) { Swal.showValidationMessage(isRevise ? 'Pilih dari siapa revisinya.' : 'Pilih siapa yang meng-approve.'); return false; }
                if (isRevise && !note) { Swal.showValidationMessage('Isi revisinya (tempel pesan dari WA).'); return false; }
                return { actor: actor, notes: note };
            };
        }

        W.askNote(opts).then(function (res) {
            if (!res) return;
            var payload = { action: action, notes: res.notes || '' };
            if (action === 'assign') payload.designer_id = res.value;
            if (action === 'approve' || action === 'revise') { payload.actor = res.value.actor; payload.notes = res.value.notes; }
            $.post(BASE + '/' + job.id + '/action', payload)
                .done(function () {
                    W.toast(action === 'comment' ? 'Komentar ditambahkan' : a.label + ' tercatat');
                    load(true);
                    if (onDone) onDone();
                })
                .fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr), 'error'); });
        });
    }

    // =================================================================== DETAIL
    var STATUS_HINT = {
        Baru: 'Menunggu designer mulai mengerjakan.',
        Dikerjakan: 'Kirim hasil desain di grup WA tim, lalu klik Selesai dikerjakan.',
        Review: 'Hasil sudah dikirim. Catat keputusan dari grup WA: Approved atau Revisi masuk.',
        Revisi: 'Ada revisi (lihat riwayat). Kirim versi baru di grup WA, lalu klik Selesai dikerjakan lagi.',
        Selesai: 'Desain sudah final.',
        Dibatalkan: 'Request dibatalkan.'
    };
    var CONTENT_HINT = {
        Baru: 'Job dari Content Plan. Mulai kerjakan setelah brief konten ada; status konten ikut jadi Produksi.',
        Dikerjakan: 'Kirim hasil desain di grup WA tim, lalu klik Selesai dikerjakan (konten otomatis masuk Review).',
        Review: 'Menunggu keputusan di grup WA. Approval / revisi dicatat content creator di Content Plan.',
        Revisi: 'Ada revisi (lihat riwayat). Kirim versi baru di grup WA, lalu klik Selesai dikerjakan lagi.',
        Selesai: 'Konten sudah di-approve.',
        Dibatalkan: 'Job dibatalkan.'
    };

    function steps(status) {
        var list = ['Baru', 'Dikerjakan', 'Review', 'Selesai'];
        if (status === 'Dibatalkan') return '<div class="wf-muted small"><i class="fas fa-ban mr-1"></i>Request dibatalkan</div>';
        var at = status === 'Revisi' ? 1 : list.indexOf(status);
        return '<div class="cp-steps">' + list.map(function (s, i) {
            var cls = i < at || status === 'Selesai' ? 'done' : (i === at ? 'current' + (status === 'Revisi' ? ' is-revisi' : '') : '');
            var label = i === 1 && status === 'Revisi' ? 'Revisi' : s;
            return (i ? '<span class="cp-step-line"></span>' : '') + '<span class="cp-step ' + cls + '"><span class="dot">'
                + (cls === 'done' ? '<i class="fas fa-check"></i>' : i + 1) + '</span>' + label + '</span>';
        }).join('') + '</div>';
    }

    function fileIcon(f) {
        if (f.is_link) return 'fa-link';
        var n = (f.name || '').toLowerCase();
        if (/\.pdf$/.test(n)) return 'fa-file-pdf';
        if (/\.(zip|rar)$/.test(n)) return 'fa-file-archive';
        if (/\.(mp4|mov)$/.test(n)) return 'fa-file-video';
        return 'fa-file';
    }

    function fileTile(f, canDelete) {
        return '<div class="dr-file"><a href="' + esc(f.url) + '" target="_blank" rel="noopener" title="' + esc(f.name) + '">'
            + '<div class="thumb">' + (f.is_image ? '<img src="' + esc(f.url) + '" alt="">' : '<i class="fas ' + fileIcon(f) + '"></i>') + '</div>'
            + '<div class="name">' + esc(f.name) + '</div></a>'
            + (canDelete ? '<button type="button" class="del js-del-file" data-id="' + f.id + '" title="Hapus">&times;</button>' : '')
            + '</div>';
    }

    function versionRow(f, i, canDelete) {
        return '<div class="dr-version' + (i === 0 ? ' is-latest' : '') + '">'
            + '<a class="thumb" href="' + esc(f.url) + '" target="_blank" rel="noopener">' + (f.is_image ? '<img src="' + esc(f.url) + '" alt="">' : '<i class="fas ' + fileIcon(f) + '"></i>') + '</a>'
            + '<div style="min-width:0;flex:1">'
            + '<div class="d-flex justify-content-between flex-wrap" style="gap:6px"><strong>v' + f.version + (i === 0 ? ' · terbaru' : '') + '</strong>'
            + '<span class="wf-meta">' + esc(f.user || '') + ' · ' + W.date(f.at, { time: true }) + '</span></div>'
            + '<div class="small text-truncate"><a href="' + esc(f.url) + '" target="_blank" rel="noopener">' + (f.is_link ? '<i class="fas fa-external-link-alt mr-1"></i>' : '<i class="fas fa-download mr-1"></i>') + esc(f.name) + '</a></div>'
            + (f.notes ? '<div class="wf-meta" style="white-space:pre-wrap">' + esc(f.notes) + '</div>' : '')
            + '</div>'
            + (canDelete ? '<button type="button" class="btn btn-sm btn-light text-danger js-del-file" data-id="' + f.id + '" title="Hapus versi ini"><i class="fas fa-trash"></i></button>' : '')
            + '</div>';
    }

    // One brief: a job from a Content Plan shows the content's brief (always the latest version)
    function briefSection(d) {
        var cb = d.content_brief;
        if (!cb) {
            return '<div class="dr-section"><div class="dr-section-title">Brief</div><div class="dr-brief">'
                + (d.brief ? esc(d.brief) : '<span class="wf-muted">Tidak ada brief.</span>') + '</div></div>';
        }
        var hasText = cb.headline || cb.sub_headline || cb.isi;
        return '<div class="dr-section"><div class="dr-section-title">Brief konten <span class="wf-muted small">· dari Content Plan, selalu versi terbaru</span></div>'
            + '<div class="dr-brief">'
            + (cb.headline ? '<div class="font-weight-bold">' + esc(cb.headline) + '</div>' : '')
            + (cb.sub_headline ? '<div class="mb-1">' + esc(cb.sub_headline) + '</div>' : '')
            + (cb.isi ? '<div class="' + (cb.headline || cb.sub_headline ? 'mt-2' : '') + '">' + esc(cb.isi) + '</div>' : '')
            + (!hasText ? '<span class="wf-muted">Brief konten belum diisi oleh content creator.</span>' : '')
            + '</div>'
            + ((cb.platform || []).length || (cb.jenis_konten || []).length || cb.tanggal_publish
                ? '<div class="wf-meta mt-1">' + esc((cb.platform || []).concat(cb.jenis_konten || []).join(' · ')) + (cb.tanggal_publish ? ' · tayang ' + W.date(cb.tanggal_publish, { time: true }) : '') + '</div>' : '')
            + ((cb.images || []).length ? '<div class="dr-files mt-2">' + cb.images.map(function (src) {
                return '<div class="dr-file"><a href="' + esc(src) + '" target="_blank" rel="noopener"><div class="thumb"><img src="' + esc(src) + '" alt=""></div></a></div>';
            }).join('') + '</div>' : '')
            + (cb.caption ? '<details class="mt-2"><summary class="small font-weight-bold">Caption</summary><div class="dr-brief mt-1">' + esc(cb.caption) + '</div></details>' : '')
            + '</div>'
            + (d.brief ? '<div class="dr-section"><div class="dr-section-title">Catatan</div><div class="dr-brief">' + esc(d.brief) + '</div></div>' : '');
    }

    function fact(label, value) { return '<div class="dr-fact"><dt>' + label + '</dt><dd>' + value + '</dd></div>'; }

    function renderDetail(d) {
        current = d;
        var contentJob = !!d.is_content_job;
        $('#drDetailIcon').html(jenisIcon(d.jenis, 'dr-head-ico'));
        $('#drDetailTitle').text(d.judul).attr('title', '#' + d.id + ' ' + d.judul);
        $('#drDetailStatus').html(W.pill('design', d.status));
        $('#drEditBtn').toggle(!!d.can.edit);
        var acts = d.actions || [];
        var has = function (name) { return acts.some(function (a) { return a.action === name; }); };
        $('#drCommentBtn').toggle(has('comment'));
        $('#drCancelBtn').toggle(has('cancel'));

        // workflow bar: progress, what happens now, the buttons for the next step
        var buttons = acts.filter(function (a) { return ['comment', 'cancel'].indexOf(a.action) === -1; }).map(function (a) {
            return '<button type="button" class="btn btn-sm btn-' + a.style + ' js-action" data-action="' + a.action + '">' + esc(a.label) + '</button>';
        }).join('');
        var hint = (contentJob ? CONTENT_HINT : STATUS_HINT)[d.status] || '';
        var html = '<div class="cp-workflow">' + steps(d.status)
            + '<div class="dr-wf-hint"><i class="fas fa-info-circle mr-1"></i>' + esc(hint) + '</div>'
            + (buttons ? '<div class="dr-wf-actions">' + buttons + '</div>' : '')
            + '</div>';

        var dl = W.deadline(d.deadline);
        var open = d.status !== 'Selesai' && d.status !== 'Dibatalkan';
        var source = '';
        if (d.content_plan_id) source = '<a href="' + CP_URL + '?open=' + d.content_plan_id + '" target="_blank" rel="noopener">' + esc(d.source || 'Content Plan') + ' <i class="fas fa-external-link-alt small"></i></a>';
        else if (d.source) source = esc(d.source);
        html += '<div class="dr-section"><dl class="dr-facts mb-0">'
            + fact('Jenis', esc(d.jenis))
            + fact('Deadline', d.deadline ? '<span class="' + (open && dl.state === 'late' ? 'wf-late' : '') + '">' + W.date(d.deadline, { year: true }) + (open ? ' · ' + esc(dl.text) : '') + '</span>' : '<span class="wf-muted">Tanpa deadline</span>')
            + fact('Designer', d.designer ? esc(d.designer) : '<span class="wf-muted">Belum ada</span>')
            + fact('Peminta', esc(d.requester || '-') + ' <span class="wf-muted small">· ' + W.date(d.created_at) + '</span>')
            + (source ? fact('Terkait', source) : '')
            + '</dl></div>';

        html += briefSection(d);

        var spec = d.spesifikasi || {};
        var specRows = [['Ukuran', spec.ukuran], ['Bahan', spec.bahan], ['Jumlah', spec.jumlah], ['Catatan', spec.catatan]].filter(function (r) { return r[1]; });
        if (specRows.length) {
            html += '<div class="dr-section"><div class="dr-section-title">Spesifikasi</div><dl class="dr-facts mb-0">'
                + specRows.map(function (r) { return fact(r[0], esc(r[1])); }).join('') + '</dl></div>';
        }
        if ((d.referensi || []).length) {
            html += '<div class="dr-section"><div class="dr-section-title">Referensi</div><div class="dr-files">'
                + d.referensi.map(function (f) { return fileTile(f, false); }).join('') + '</div></div>';
        }

        // results go to the WA group; final files (print / packaging for the vendor) may be kept here
        var hasil = d.hasil || [];
        html += '<div class="dr-section"><div class="dr-section-title">Hasil desain</div>'
            + '<div class="dr-wa-note"><i class="fab fa-whatsapp"></i><span>Hasil &amp; revisi dikirim lewat grup WA tim.'
            + (!contentJob ? ' File final (mis. untuk vendor cetak) boleh disimpan di sini.' : '') + '</span></div>'
            + (hasil.length ? '<div class="mt-2">' + hasil.map(function (f, i) { return versionRow(f, i, d.can.upload); }).join('') + '</div>' : '')
            + (d.can.upload ? '<details class="dr-upload mt-2"><summary><i class="fas fa-upload mr-1"></i> Simpan file final</summary><form id="drUploadForm" class="mt-2">'
                + '<input type="file" class="form-control-file mb-2" name="file" accept=".' + RESULT_EXT.join(',.') + '">'
                + '<input type="url" class="form-control form-control-sm mb-2" name="url" placeholder="…atau tempel link Google Drive (untuk file > ' + MAX_MB + ' MB)">'
                + '<input type="text" class="form-control form-control-sm mb-2" name="notes" placeholder="Catatan versi (opsional)">'
                + '<button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-upload mr-1"></i> Simpan</button>'
                + '</form></details>' : '')
            + '</div>';

        $('#drDetailMain').html(html);
        $('#drTimeline').html(W.timeline(d.timeline));
    }

    function openDetail(id) {
        $.getJSON(BASE + '/' + id).done(function (d) {
            renderDetail(d);
            if (!$('#drDetailModal').hasClass('show')) $('#drDetailModal').modal('show');
        }).fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr, 'Request tidak ditemukan atau bukan milik Anda.'), 'error'); });
    }

    $(document).on('click', '#drDetailModal .js-action', function () {
        if (!current) return;
        var id = current.id;
        runAction(current, $(this).data('action'), function () { openDetail(id); });
    });

    $(document).on('submit', '#drUploadForm', function (e) {
        e.preventDefault();
        var fd = new FormData(this);
        var file = this.file.files[0];
        if (!file && !$.trim(this.url.value)) { Swal.fire('Pilih file atau isi link', 'Unggah file atau tempel link Google Drive.', 'warning'); return; }
        if (file && file.size > MAX_MB * 1024 * 1024) { Swal.fire('File terlalu besar', 'Maksimal ' + MAX_MB + ' MB. Untuk file besar, tempel link Google Drive.', 'warning'); return; }
        if (!file) fd.delete('file');
        var $btn = $(this).find('button[type=submit]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Menyimpan…');
        $.ajax({ url: BASE + '/' + current.id + '/upload', method: 'POST', data: fd, processData: false, contentType: false })
            .done(function () { W.toast('File disimpan'); openDetail(current.id); load(true); })
            .fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr), 'error'); $btn.prop('disabled', false).html('<i class="fas fa-upload mr-1"></i> Simpan'); });
    });

    $(document).on('click', '#drDetailMain .js-del-file', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        W.askNote({ title: 'Hapus file ini?', confirmText: 'Hapus', confirmColor: '#c92a2a' }).then(function (res) {
            if (!res) return;
            $.ajax({ url: BASE + '/files/' + id, method: 'DELETE' })
                .done(function () { W.toast('File dihapus'); openDetail(current.id); load(true); })
                .fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr), 'error'); });
        });
    });

    // =================================================================== FORM
    var removeFiles = [];

    function setJenis(value) {
        $('#drf_jenis').val(value);
        $('#drfJenisChips .cp-chip-opt').each(function () {
            var on = $(this).data('value') === value;
            $(this).toggleClass('is-on', on).find('.cp-chip-check').remove();
            if (on) $(this).append('<span class="cp-chip-check"><i class="fas fa-check"></i></span>');
        });
        // print & packaging need sizes/materials; social content usually not
        var linkedPlan = !!$('#drf_content_plan_id').val();
        $('#drfSpec').toggle(value !== 'Konten Sosmed');
        $('#drfSosmedHint').toggle(value === 'Konten Sosmed' && !linkedPlan);
    }
    $('#drfJenisChips').on('click', '.cp-chip-opt', function () { setJenis($(this).data('value')); });

    $('#drf_referensi').on('change', function () {
        $('#drfPicked').html(Array.prototype.map.call(this.files || [], function (f) {
            return '<span class="cp-tag"><i class="fas fa-paperclip"></i>' + esc(f.name) + '</span>';
        }).join(''));
    });
    $('label[for=drf_referensi]').on('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $('#drf_referensi').trigger('click'); } });

    function openForm(data) {
        data = data || {};
        var editing = !!data.id;
        var $f = $('#drForm');
        $f[0].reset();
        $f.attr('data-id', editing ? data.id : '');
        removeFiles = [];
        $('#drfPicked').empty();
        $('#drFormTitle').text(editing ? 'Edit Request #' + data.id : 'Request Desain Baru');
        $('#drfSubmit').html(editing ? '<i class="fas fa-save mr-1"></i> Simpan' : '<i class="fas fa-paper-plane mr-1"></i> Kirim Request');
        $('#drf_judul').val(data.judul || '');
        $('#drf_brief').val(data.brief || '');
        $('#drf_deadline').val(data.deadline || '');
        // the designer is handed over at creation; later changes go through "Tugaskan ke…"
        $('#drfDesignerGroup').toggle(!editing);
        $('#drf_designer').val('');
        var spec = data.spesifikasi || {};
        $('#drf_ukuran').val(spec.ukuran || ''); $('#drf_bahan').val(spec.bahan || '');
        $('#drf_jumlah').val(spec.jumlah || ''); $('#drf_catatan').val(spec.catatan || '');
        $('#drf_content_plan_id').val(editing ? '' : (data.content_plan_id || ''));
        $('#drf_rnd_produk_id').val(editing ? '' : (data.rnd_produk_id || ''));
        $('#drf_rnd_kemasan_sisi').val(editing ? '' : (data.rnd_kemasan_sisi || ''));
        var src = data.source_label || (editing ? data.source : null);
        $('#drfSource').html(src ? '<i class="fas fa-link mr-1"></i> Terkait: <strong>' + esc(src) + '</strong>' : '').toggle(!!src);
        $('#drfExisting').html((editing ? (data.referensi || []) : []).map(function (f) {
            return '<div class="dr-file" data-id="' + f.id + '"><a href="' + esc(f.url) + '" target="_blank" rel="noopener"><div class="thumb">'
                + (f.is_image ? '<img src="' + esc(f.url) + '" alt="">' : '<i class="fas ' + fileIcon(f) + '"></i>') + '</div><div class="name">' + esc(f.name) + '</div></a>'
                + '<button type="button" class="del js-form-del" data-id="' + f.id + '" title="Hapus">&times;</button></div>';
        }).join(''));
        setJenis(data.jenis || 'Cetak');
        $('#drFormModal').modal('show');
    }
    $('#drfExisting').on('click', '.js-form-del', function () {
        removeFiles.push($(this).data('id'));
        $(this).closest('.dr-file').remove();
    });

    $('#btnNewRequest').on('click', function () { openForm(null); });
    $('#drEditBtn').on('click', function () {
        var d = current;
        $('#drDetailModal').one('hidden.bs.modal', function () { openForm(d); }).modal('hide');
    });

    $('#drForm').on('submit', function (e) {
        e.preventDefault();
        if (!$.trim($('#drf_judul').val())) { Swal.fire('Lengkapi dulu', 'Judul wajib diisi.', 'warning'); return; }
        var id = $(this).attr('data-id');
        var fd = new FormData(this);
        removeFiles.forEach(function (fid) { fd.append('remove_files[]', fid); });
        ['content_plan_id', 'rnd_produk_id', 'rnd_kemasan_sisi', 'designer_id'].forEach(function (k) { if (!fd.get(k)) fd.delete(k); });
        if (id) fd.delete('designer_id');
        var $btn = $('#drfSubmit'), original = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Menyimpan…');
        $.ajax({ url: id ? BASE + '/' + id : BASE, method: 'POST', data: fd, processData: false, contentType: false })
            .done(function (r) {
                $('#drFormModal').modal('hide');
                W.toast(id ? 'Request diperbarui' : 'Request terkirim ke designer');
                load(true);
                var openAfter = id || (r && r.id);
                if (openAfter) setTimeout(function () { openDetail(openAfter); }, 300);
            })
            .fail(function (xhr) {
                var j = xhr.responseJSON || {};
                if (j.existing_id) {
                    Swal.fire({ title: 'Sudah ada request', html: esc(j.message), icon: 'info', confirmButtonText: 'Buka request itu' })
                        .then(function (res) { if (res.isConfirmed) { $('#drFormModal').modal('hide'); openDetail(j.existing_id); } });
                    return;
                }
                Swal.fire('Gagal', W.errorMessage(xhr), 'error');
            })
            .always(function () { $btn.prop('disabled', false).html(original); });
    });

    // =================================================================== START
    load(true);
    if (PREFILL) openForm(PREFILL);
    else if (OPEN_ID) openDetail(OPEN_ID);

    // Live board: refresh every 10 seconds (and right after a notification); skipped while a dialog is
    // open or more pages of "Selesai" are loaded
    function refreshLive() {
        if (document.hidden || $('.modal.show').length || Swal.isVisible() || page > 1) return;
        load(true, true);
    }
    setInterval(refreshLive, 10000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) refreshLive(); });

    // Notifications: ditugaskan ke Anda, desain perlu approval, revisi, request baru, …
    MkNotify.start({
        userId: ME,
        url: @json(route('workflow-notifications.feed')),
        button: '#btnNotif',
        onEvents: refreshLive,
        open: function (item) {
            var u = new URL(item.url, window.location.origin);
            if (u.pathname !== window.location.pathname || !u.searchParams.get('open')) return false;
            openDetail(u.searchParams.get('open'));
            return true;
        }
    });
});
</script>
@endpush
