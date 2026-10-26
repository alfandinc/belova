{{-- Jadwal viewer (karyawan & dokter, ramah HP). Fetched on first click of a "js-open-jadwal" element (see layouts/shared/sidebar). --}}
<style>
.jv-dialog { max-width: 1200px; width: 95vw; }
.jv-tabs .nav-link { font-weight: 600; padding: 8px; }
.jv-nav { display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
.jv-nav .btn { min-height: 40px; }
.jv-nav .jv-label { flex: 1; font-weight: 600; color: inherit; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.jv-days { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 4px; margin-bottom: 8px; }
.jv-mode { min-width: 40px; }
.jv-day { min-width: 0; min-height: 50px; border: 1px solid rgba(128,128,128,.35); border-radius: 10px; background: transparent; color: inherit; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4px 2px; position: relative; }
.jv-day small { font-size: 11px; opacity: .75; }
.jv-day b { font-size: 16px; line-height: 1.2; font-weight: 600; }
.jv-day.red small, .jv-day.red b { color: #e74c3c; }
.jv-day.today::after { content: ''; position: absolute; bottom: 4px; width: 6px; height: 6px; border-radius: 50%; background: #1e88e5; }
.jv-day.active { background: #1e88e5; border-color: #1e88e5; color: #fff; }
.jv-day.active small, .jv-day.active b { color: #fff; }
.jv-day.active.today::after { background: #fff; }
.jv-filters { display: flex; gap: 6px; margin-bottom: 8px; }
.jv-filters .form-control { min-height: 38px; font-size: 15px; }
.jv-filters .jv-clinic { max-width: 45%; }
.jv-table-wrap::-webkit-scrollbar { display: none; }
.jv-mini { border-radius: 6px; font-size: 12px; font-weight: 500; line-height: 1.3; padding: 4px 8px; white-space: nowrap; }
.jv-mini + .jv-mini { margin-top: 3px; }
.jv-mini.off { opacity: .45; font-weight: 400; }
.jv-mini.libur { background: #e74c3c; color: #fff; }
.jv-body { max-height: calc(100vh - 330px); overflow-y: auto; -webkit-overflow-scrolling: touch; }
.jv-empty { padding: 24px 8px; text-align: center; opacity: .75; }
.jv-day-title { font-weight: 600; padding: 4px 2px 6px; }
.jv-group { position: sticky; top: 0; z-index: 1; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; padding: 6px 8px; background: rgba(128,128,128,.18); backdrop-filter: blur(6px); border-radius: 6px; margin-top: 6px; }
.jv-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 8px; border-bottom: 1px solid rgba(128,128,128,.18); }
.jv-row.me, .jv-table tr.me td { background: rgba(30,136,229,.12); }
.jv-name { min-width: 0; font-size: 14px; font-weight: 400; }
.jv-name small { display: block; font-weight: 400; font-size: 11px; opacity: .7; }
.jv-val { flex: none; max-width: 48%; text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 3px; }
.jv-chip { display: inline-block; border-radius: 6px; padding: 3px 8px; font-size: 12px; font-weight: 500; white-space: nowrap; }
.jv-chip2 { text-align: center; line-height: 1.25; padding: 4px 10px; }
.jv-chip2 small { display: block; font-size: 10.5px; font-weight: 400; opacity: .85; max-width: 120px; overflow: hidden; text-overflow: ellipsis; }
.jv-libur { background: #e74c3c; color: #fff; }
.jv-off { opacity: .55; font-size: 12px; }
.jv-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; vertical-align: middle; }
.jv-table-wrap { overflow: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
.jv-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; }
.jv-table th, .jv-table td { padding: 5px 6px; border-bottom: 1px solid rgba(128,128,128,.2); text-align: center; vertical-align: middle; }
.jv-table td { min-width: 112px; padding: 6px 5px; }
.jv-table thead th { min-width: 112px; font-weight: 600; }
body.jv-open #belovaChatWidget { display: none !important; }
.jv-table thead th { position: sticky; top: 0; z-index: 2; background: var(--jv-bg, #fff); }
.jv-table th.today, .jv-table td.today { background-color: rgba(30,136,229,.08); }
.jv-table th.red { color: #e74c3c; }
.jv-table .jv-sticky { position: sticky; left: 0; z-index: 1; background: var(--jv-bg, #fff); text-align: left; min-width: 150px; }
.jv-table thead .jv-sticky { z-index: 3; }
.jv-table .jv-group-row td { font-weight: 700; font-size: 12px; text-transform: uppercase; background: rgba(128,128,128,.15); text-align: left; }
.jv-group-label { position: sticky; left: 8px; }
.jv-table tr.me td.jv-sticky { background: linear-gradient(rgba(30,136,229,.12), rgba(30,136,229,.12)), var(--jv-bg, #fff); }
/* Jadwal dokter sebulan: kalender Senin–Minggu, tiap tanggal berisi dokter yang praktik */
.jv-cal { width: 100%; min-width: 760px; table-layout: fixed; border-collapse: separate; border-spacing: 0; font-size: 12px; }
.jv-cal th { position: sticky; top: 0; z-index: 2; background: var(--jv-bg, #fff); text-align: center; font-weight: 600; padding: 5px 2px; border-bottom: 1px solid rgba(128,128,128,.3); }
.jv-cal th.red, .jv-cal td.red .jv-cal-num { color: #e74c3c; }
.jv-cal td { vertical-align: top; height: 92px; padding: 4px; border-right: 1px solid rgba(128,128,128,.18); border-bottom: 1px solid rgba(128,128,128,.18); cursor: pointer; overflow: hidden; }
.jv-cal td:hover { background: rgba(128,128,128,.08); }
.jv-cal td.out { opacity: .4; }
.jv-cal td.today { background: rgba(30,136,229,.1); }
.jv-cal-num { font-weight: 700; font-size: 12px; margin-bottom: 2px; }
.jv-cal td.today .jv-cal-num { color: #1e88e5; }
.jv-cal-item { font-size: 11px; line-height: 1.3; border-left: 3px solid; border-radius: 3px; padding: 1px 4px; margin-top: 2px; background: rgba(128,128,128,.12); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.jv-cal-item small { opacity: .75; }
@media (max-width: 767.98px) {
    #jadwalModal .modal-dialog.jv-dialog { margin: 0; width: 100%; max-width: 100%; height: 100%; }
    #jadwalModal .modal-content { min-height: 100%; border-radius: 0; border: 0; }
    .jv-body { max-height: none; }
    .jv-day b { font-size: 15px; }
    .jv-name { font-size: 13.5px; }
    .jv-nav { gap: 4px; }
    .jv-table { font-size: 12px; }
    .jv-table td, .jv-table thead th { min-width: 108px; }
    .jv-table .jv-sticky { min-width: 118px; max-width: 128px; white-space: normal; box-shadow: 2px 0 4px rgba(0,0,0,.15); }
    .jv-table .jv-sticky.jv-name { font-size: 12.5px; }
    .jv-nav .btn { padding: 6px 10px; }
    .jv-nav .jv-label { font-size: 14px; padding: 6px 2px; }
}
</style>

<!-- Jadwal Modal (tampilan HTML, ramah HP) -->
<div class="modal fade" id="jadwalModal" tabindex="-1" role="dialog" aria-labelledby="jadwalModalLabel" aria-hidden="true">
    <div class="modal-dialog jv-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="jadwalModalLabel"><i class="fas fa-calendar-alt mr-1"></i> Jadwal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body p-2 p-md-3">
                <ul class="nav nav-pills nav-justified jv-tabs mb-2" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#jv-karyawan" data-jv="karyawan" role="tab">Karyawan</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#jv-dokter" data-jv="dokter" role="tab">Dokter</a></li>
                </ul>
                <div class="tab-content">
                    @foreach(['karyawan' => 'Cari karyawan...', 'dokter' => 'Cari dokter...'] as $jvKey => $jvPlaceholder)
                    <div class="tab-pane fade {{ $jvKey === 'karyawan' ? 'show active' : '' }}" id="jv-{{ $jvKey }}" role="tabpanel" data-jv-pane="{{ $jvKey }}">
                        <div class="jv-nav">
                            <button type="button" class="btn btn-outline-secondary jv-prev" aria-label="Minggu sebelumnya"><i class="fas fa-chevron-left"></i></button>
                            <button type="button" class="btn btn-link jv-label" title="Kembali ke minggu ini">-</button>
                            <button type="button" class="btn btn-outline-secondary jv-next" aria-label="Minggu berikutnya"><i class="fas fa-chevron-right"></i></button>
                            <button type="button" class="btn btn-outline-secondary jv-mode" title="Tampilan seminggu / per hari"><i class="fas fa-table"></i></button>
                            @if($jvKey === 'dokter')
                                <button type="button" class="btn btn-outline-secondary jv-month" title="Tampilan sebulan"><i class="fas fa-calendar-alt"></i><span class="d-none d-md-inline ml-1">Bulan</span></button>
                            @endif
                            <button type="button" class="btn btn-primary jv-download" title="Download gambar"><i class="fas fa-download"></i><span class="d-none d-md-inline ml-1">Gambar</span></button>
                        </div>
                        <div class="jv-days"></div>
                        <div class="jv-filters">
                            @if($jvKey === 'dokter')
                                <select class="form-control form-control-sm jv-clinic"><option value="">Semua Klinik</option></select>
                            @endif
                            <input type="search" class="form-control form-control-sm jv-search" placeholder="{{ $jvPlaceholder }}">
                        </div>
                        @if($jvKey === 'karyawan')<div class="jv-perubahan"></div>@endif
                        <div class="jv-body"><div class="jv-empty">Memuat jadwal...</div></div>
                    </div>
                    @endforeach
                </div>
                <canvas id="jadwalPdfCanvas" style="display:none;"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
function initBelovaJadwalViewer() {
    if (!window.jQuery || window.belovaOpenJadwal) return;
    var $ = window.jQuery;

    // pdf.js is only needed for the "Download gambar" button: load it on first use
    var pdfJsPromise = null;
    function ensurePdfJs() {
        if (window.pdfjsLib) return Promise.resolve(window.pdfjsLib);
        if (!pdfJsPromise) {
            pdfJsPromise = new Promise(function (resolve, reject) {
                var s = document.createElement('script');
                s.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
                s.onload = function () {
                    window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                    resolve(window.pdfjsLib);
                };
                s.onerror = reject;
                document.head.appendChild(s);
            });
        }
        return pdfJsPromise;
    }

    // Helper to render all pages of a PDF and download each as PNG
    async function renderAndDownloadPdfPages(url, baseFilename, canvasElement) {
        try {
            const pdfjsLib = await ensurePdfJs();
            const loadingTask = pdfjsLib.getDocument(url);
            const pdf = await loadingTask.promise;
            const total = pdf.numPages;
            const canvas = canvasElement || document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            for (let pageNum = 1; pageNum <= total; pageNum++) {
                const page = await pdf.getPage(pageNum);
                const viewport = page.getViewport({ scale: 1.5 });
                canvas.width = Math.round(viewport.width);
                canvas.height = Math.round(viewport.height);
                await page.render({ canvasContext: ctx, viewport: viewport }).promise;

                // Convert to blob to avoid memory/url size limits and trigger download
                await new Promise((resolve) => {
                    canvas.toBlob(function(blob) {
                        if (!blob) return resolve();
                        const link = document.createElement('a');
                        const pageSuffix = total > 1 ? ('_page' + pageNum) : '';
                        const filename = baseFilename + pageSuffix + '.png';
                        const urlBlob = URL.createObjectURL(blob);
                        link.href = urlBlob;
                        link.download = filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        // Revoke object URL after a small delay to ensure download started
                        setTimeout(() => URL.revokeObjectURL(urlBlob), 1000);
                        resolve();
                    }, 'image/png');
                });
            }
        } catch (err) {
            console.error('Error rendering PDF pages', err);
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan saat memproses PDF.' });
        }
    }

    // Helper to render all pages into a single tall image and download as PNG
    async function renderPdfToSingleImage(url, baseFilename, canvasElement) {
        try {
            const pdfjsLib = await ensurePdfJs();
            const loadingTask = pdfjsLib.getDocument(url);
            const pdf = await loadingTask.promise;
            const total = pdf.numPages;

            // First, measure each page viewport to determine total height and max width
            const viewports = [];
            let totalHeight = 0;
            let maxWidth = 0;
            const scale = 1.5;
            for (let i = 1; i <= total; i++) {
                const page = await pdf.getPage(i);
                const vp = page.getViewport({ scale });
                viewports.push(vp);
                totalHeight += Math.round(vp.height);
                maxWidth = Math.max(maxWidth, Math.round(vp.width));
            }

            // Safety: browsers have limits on canvas size. If too big, fallback to per-page downloads.
            const MAX_CANVAS_DIMENSION = 32767; // conservative limit for many browsers
            if (maxWidth > MAX_CANVAS_DIMENSION || totalHeight > MAX_CANVAS_DIMENSION) {
                console.warn('Combined image too large, falling back to per-page downloads');
                return renderAndDownloadPdfPages(url, baseFilename, canvasElement);
            }

            const canvas = canvasElement || document.createElement('canvas');
            canvas.width = maxWidth;
            canvas.height = totalHeight;
            const ctx = canvas.getContext('2d');

            // Render each page sequentially and draw to the combined canvas
            let yOffset = 0;
            for (let i = 1; i <= total; i++) {
                const page = await pdf.getPage(i);
                const vp = viewports[i - 1];

                // Render page to a temporary canvas to avoid needing different widths
                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = Math.round(vp.width);
                tempCanvas.height = Math.round(vp.height);
                const tempCtx = tempCanvas.getContext('2d');
                await page.render({ canvasContext: tempCtx, viewport: vp }).promise;

                // Draw the temp canvas onto the big canvas at current offset
                ctx.drawImage(tempCanvas, 0, yOffset, tempCanvas.width, tempCanvas.height);
                yOffset += tempCanvas.height;
            }

            // Convert combined canvas to blob and download
            await new Promise((resolve) => {
                canvas.toBlob(function(blob) {
                    if (!blob) return resolve();
                    const link = document.createElement('a');
                    const filename = baseFilename + '.png';
                    const urlBlob = URL.createObjectURL(blob);
                    link.href = urlBlob;
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    setTimeout(() => URL.revokeObjectURL(urlBlob), 1000);
                    resolve();
                }, 'image/png');
            });
        } catch (err) {
            console.error('Error creating combined PDF image, falling back to per-page', err);
            // Fallback to per-page download
            return renderAndDownloadPdfPages(url, baseFilename, canvasElement);
        }
    }

    // ===== Jadwal viewer (HTML, ramah HP) =====
    (function () {
        var URL_KARYAWAN = "{{ route('hrd.schedule.view_data') }}";
        var URL_DOKTER = "{{ route('hrd.dokter-schedule.view_data') }}";
        var isMobile = function () { return window.matchMedia('(max-width: 767.98px)').matches; };

        function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
        function ymd(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }
        function mondayOf(d) { d = new Date(d); var day = d.getDay(); d.setDate(d.getDate() + ((day === 0 ? -6 : 1) - day)); return ymd(d); }
        function addDays(s, n) { var p = s.split('-'); var d = new Date(+p[0], +p[1] - 1, +p[2]); d.setDate(d.getDate() + n); return ymd(d); }
        function parseYmd(s) { var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +(p[2] || 1)); }
        function addMonths(m, n) { var p = m.split('-'); var d = new Date(+p[0], +p[1] - 1 + n, 1); return ymd(d).slice(0, 7); }
        function contrast(hex) {
            var c = (hex || '').replace('#', '');
            if (c.length === 3) c = c[0] + c[0] + c[1] + c[1] + c[2] + c[2];
            if (c.length !== 6) return '#000';
            var r = parseInt(c.substr(0, 2), 16), g = parseInt(c.substr(2, 2), 16), b = parseInt(c.substr(4, 2), 16);
            return (r * 299 + g * 587 + b * 114) / 1000 > 150 ? '#000' : '#fff';
        }
        function chip(text, color, title) {
            return '<span class="jv-chip" style="background:' + esc(color) + ';color:' + contrast(color) + '"' + (title ? ' title="' + esc(title) + '"' : '') + '>' + esc(text) + '</span>';
        }
        // "09:00","19:00" -> "09.00 – 19.00" (satu baris, format Indonesia)
        function jam(start, end) { return String(start).replace(':', '.') + ' – ' + String(end).replace(':', '.'); }
        function jamRange(t) { var p = String(t).split('–'); return p.length === 2 ? jam(p[0], p[1]) : t; }
        function liburChip(label) { return '<span class="jv-chip jv-libur">' + esc(label) + '</span>'; }

        // Isi sel karyawan untuk satu hari (compact = tabel mingguan, selain itu = daftar harian)
        function karyawanCell(day, compact) {
            if (!day) return '<span class="jv-off">Libur</span>';
            if (day.libur) return liburChip(day.libur);
            return day.shifts.map(function (s) {
                if (compact) return chip(jam(s.start, s.end), s.color, s.name);
                return '<span class="jv-chip jv-chip2" style="background:' + esc(s.color) + ';color:' + contrast(s.color) + '">' +
                    '<small>' + esc(s.name) + '</small>' + esc(jam(s.start, s.end)) + '</span>';
            }).join('');
        }
        // Sel ringkas untuk tabel mingguan: "09.00 – 19.00" satu baris
        function miniCell(day) {
            if (!day) return '<div class="jv-mini off">–</div>';
            if (day.libur) return '<div class="jv-mini libur">' + esc(day.libur) + '</div>';
            return day.shifts.map(function (s) {
                return '<div class="jv-mini" style="background:' + esc(s.color) + ';color:' + contrast(s.color) + '" title="' + esc(s.name) + '">' +
                    esc(jam(s.start, s.end)) + '</div>';
            }).join('');
        }

        // "Jadwal Anda diubah" notifications (sent when HRD changes the viewer's schedule)
        function renderPerubahan(rows) {
            var $box = $('.jv-perubahan');
            if (!rows.length) { $box.empty(); return; }
            $box.html('<div class="alert alert-warning py-2 mb-2"><b><i class="fas fa-bell mr-1"></i>Jadwal Anda diubah</b>'
                + '<ul class="mb-1 pl-3">' + rows.map(function (r) {
                    return '<li>' + esc(r.pesan) + ' <small class="text-muted">(' + esc(r.oleh) + ', ' + esc(r.waktu) + ')</small></li>';
                }).join('') + '</ul><button type="button" class="btn btn-sm btn-outline-dark jv-baca">Oke, sudah dibaca</button></div>');
        }
        $(document).on('click', '.jv-baca', function () {
            $.ajax({ url: "{{ route('hrd.schedule.baca_perubahan') }}", method: 'POST', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } })
                .done(function () { $('.jv-perubahan').empty(); $('.jv-unread').remove(); });
        });

        function createViewer(key) {
            var $pane = $('[data-jv-pane="' + key + '"]');
            var state = { start: mondayOf(new Date()), day: null, mode: null, data: null, month: ymd(new Date()).slice(0, 7) };

            function load() {
                var params = state.mode === 'month' ? { month: state.month } : { start_date: state.start };
                if (key === 'dokter') params.clinic_id = $pane.find('.jv-clinic').val() || '';
                $pane.find('.jv-body').html('<div class="jv-empty"><span class="spinner-border spinner-border-sm mr-1"></span> Memuat jadwal...</div>');
                $.getJSON(key === 'dokter' ? URL_DOKTER : URL_KARYAWAN, params)
                    .done(function (data) {
                        state.data = data;
                        if (key === 'karyawan') renderPerubahan(data.perubahan || []);
                        var today = data.dates.filter(function (d) { return d.today; })[0];
                        if (!state.day || !data.dates.some(function (d) { return d.date === state.day; })) {
                            state.day = today ? today.date : data.dates[0].date;
                        }
                        if (key === 'dokter' && data.clinicOptions) {
                            var $sel = $pane.find('.jv-clinic');
                            if ($sel.find('option').length <= 1) {
                                data.clinicOptions.forEach(function (k) { $sel.append('<option value="' + k.id + '">' + esc(k.nama) + '</option>'); });
                            }
                        }
                        render();
                    })
                    .fail(function () {
                        $pane.find('.jv-body').html('<div class="jv-empty text-danger">Gagal memuat jadwal.</div>');
                    });
            }

            function renderDays() {
                var html = state.data.dates.map(function (d) {
                    var cls = 'jv-day' + (state.mode === 'day' && d.date === state.day ? ' active' : '') + (d.today ? ' today' : '') + (d.sunday || d.holiday ? ' red' : '');
                    return '<button type="button" class="' + cls + '" data-date="' + d.date + '"' + (d.holiday ? ' title="' + esc(d.holiday) + '"' : '') + '>' +
                        '<small>' + esc(d.day) + '</small><b>' + esc(d.num) + '</b></button>';
                }).join('');
                // Sebulan: tanggal sudah ada di kalender, strip hari disembunyikan
                $pane.find('.jv-days').html(state.mode === 'month' ? '' : html).toggle(state.mode !== 'month');
                // Tombol mode: tampilkan ikon tujuan (tabel seminggu <-> daftar harian)
                $pane.find('.jv-mode').toggleClass('active', state.mode === 'week')
                    .html(state.mode === 'week' ? '<i class="fas fa-list"></i>' : '<i class="fas fa-table"></i>');
                $pane.find('.jv-month').toggleClass('active', state.mode === 'month');
                $pane.find('.jv-prev').attr('aria-label', state.mode === 'month' ? 'Bulan sebelumnya' : 'Minggu sebelumnya');
                $pane.find('.jv-next').attr('aria-label', state.mode === 'month' ? 'Bulan berikutnya' : 'Minggu berikutnya');
                $pane.find('.jv-label').attr('title', state.mode === 'month' ? 'Kembali ke bulan ini' : 'Kembali ke minggu ini');
                $pane.find('.jv-label').text(state.data.label);
            }

            function matches(name) {
                var q = ($pane.find('.jv-search').val() || '').trim().toLowerCase();
                return !q || (name || '').toLowerCase().indexOf(q) !== -1;
            }

            function render() {
                if (!state.data) return;
                if (!state.mode) state.mode = isMobile() ? 'day' : 'week';
                renderDays();
                var groups = key === 'dokter' ? state.data.kliniks : state.data.divisions;
                $pane.find('.jv-body').html(state.mode === 'month' ? renderMonth(groups) : (state.mode === 'day' ? renderDay(groups) : renderWeek(groups)));
                if (state.mode === 'week') {
                    // Geser tabel supaya kolom hari yang dipilih langsung terlihat di samping kolom nama
                    var wrap = $pane.find('.jv-table-wrap')[0];
                    var idx = state.data.dates.findIndex(function (d) { return d.date === state.day; });
                    var th = wrap && wrap.querySelectorAll('thead th')[idx + 1];
                    var sticky = wrap && wrap.querySelector('thead th.jv-sticky');
                    if (th && sticky && idx > 0) wrap.scrollLeft = th.offsetLeft - sticky.offsetWidth;
                }
            }

            function personName(p) {
                return (key === 'dokter' ? '<span class="jv-dot" style="background:' + esc(p.color) + '"></span>' : '') +
                    esc(p.nama) + (p.posisi ? '<small>' + esc(p.posisi) + '</small>' : '');
            }

            function renderDay(groups) {
                var dayInfo = state.data.dates.filter(function (d) { return d.date === state.day; })[0];
                var html = '<div class="jv-day-title">' + esc(dayInfo.dayLong) + (dayInfo.holiday ? ' <span class="badge badge-danger">' + esc(dayInfo.holiday) + '</span>' : '') + '</div>';
                var any = false;
                groups.forEach(function (g) {
                    var people = key === 'dokter' ? g.dokters : g.employees;
                    var rows = people.filter(function (p) {
                        return matches(p.nama) && (key === 'dokter' ? !!p.days[state.day] : true);
                    }).map(function (p) {
                        var right = key === 'dokter' ? chip(jamRange(p.days[state.day]), p.color) : karyawanCell(p.days[state.day], false);
                        var me = key === 'karyawan' && p.id === state.data.me ? ' me' : '';
                        return '<div class="jv-row' + me + '"><div class="jv-name">' + personName(p) + '</div><div class="jv-val">' + right + '</div></div>';
                    }).join('');
                    if (rows) { any = true; html += (g.name ? '<div class="jv-group">' + esc(g.name) + '</div>' : '') + rows; }
                });
                return any ? html : html + '<div class="jv-empty">Tidak ada jadwal ' + (key === 'dokter' ? 'dokter ' : '') + 'di hari ini.</div>';
            }

            function renderWeek(groups) {
                var dates = state.data.dates;
                var html = '<div class="jv-table-wrap"><table class="jv-table"><thead><tr><th class="jv-sticky">' + (key === 'dokter' ? 'Dokter' : 'Karyawan') + '</th>' +
                    dates.map(function (d) {
                        return '<th class="' + (d.today ? 'today' : '') + (d.sunday || d.holiday ? ' red' : '') + '"' + (d.holiday ? ' title="' + esc(d.holiday) + '"' : '') + '>' + esc(d.day) + '<br><small>' + esc(d.num) + '</small></th>';
                    }).join('') + '</tr></thead><tbody>';
                var any = false;
                groups.forEach(function (g) {
                    var people = (key === 'dokter' ? g.dokters : g.employees).filter(function (p) { return matches(p.nama); });
                    if (!people.length) return;
                    any = true;
                    if (g.name) html += '<tr class="jv-group-row"><td colspan="' + (dates.length + 1) + '"><span class="jv-group-label">' + esc(g.name) + '</span></td></tr>';
                    people.forEach(function (p) {
                        var me = key === 'karyawan' && p.id === state.data.me ? ' class="me"' : '';
                        html += '<tr' + me + '><td class="jv-sticky jv-name">' + personName(p) + '</td>';
                        dates.forEach(function (d) {
                            // Jam mulai/selesai bertumpuk supaya 7 hari muat di layar HP
                            var v;
                            if (key === 'dokter') {
                                var t = p.days[d.date];
                                v = t ? '<div class="jv-mini" style="background:' + esc(p.color) + ';color:' + contrast(p.color) + '">' + esc(jamRange(t)) + '</div>' : '<div class="jv-mini off">–</div>';
                            } else {
                                v = miniCell(p.days[d.date]);
                            }
                            html += '<td class="' + (d.today ? 'today' : '') + '">' + v + '</td>';
                        });
                        html += '</tr>';
                    });
                });
                html += '</tbody></table></div>';
                return any ? html : '<div class="jv-empty">Tidak ada jadwal minggu ini.</div>';
            }

            // Jadwal dokter sebulan: kalender per minggu, tiap tanggal = dokter yang praktik + jamnya (klik = per hari)
            function renderMonth(groups) {
                var dates = state.data.dates;
                var people = [];
                groups.forEach(function (g) { g.dokters.forEach(function (p) { if (matches(p.nama)) people.push(p); }); });
                var html = '<div class="jv-table-wrap"><table class="jv-cal"><thead><tr>' +
                    dates.slice(0, 7).map(function (d) { return '<th class="' + (d.sunday ? 'red' : '') + '">' + esc(d.day) + '</th>'; }).join('') +
                    '</tr></thead><tbody>';
                var any = false;
                dates.forEach(function (d, i) {
                    if (i % 7 === 0) html += '<tr>';
                    var items = people.filter(function (p) { return !!p.days[d.date]; })
                        .sort(function (a, b) { return String(a.days[d.date]).localeCompare(String(b.days[d.date])); })
                        .map(function (p) {
                            return '<div class="jv-cal-item" style="border-left-color:' + esc(p.color) + '" title="' + esc(p.nama + ' ' + jamRange(p.days[d.date])) + '">' +
                                esc(p.nama) + ' <small>' + esc(jamRange(p.days[d.date])) + '</small></div>';
                        }).join('');
                    if (items && d.inMonth) any = true;
                    var cls = (d.inMonth ? '' : 'out') + (d.today ? ' today' : '') + (d.sunday ? ' red' : '');
                    html += '<td class="' + cls + '" data-cal-date="' + d.date + '" title="' + esc(d.dayLong) + '"><div class="jv-cal-num">' + esc(d.num) + '</div>' + items + '</td>';
                    if (i % 7 === 6) html += '</tr>';
                });
                html += '</tbody></table></div>';
                return any ? html : html + '<div class="jv-empty">Tidak ada jadwal dokter di bulan ini.</div>';
            }

            // Events
            $pane.on('click', '.jv-prev', function () {
                if (state.mode === 'month') { state.month = addMonths(state.month, -1); load(); return; }
                state.start = addDays(state.start, -7); state.day = null; load();
            });
            $pane.on('click', '.jv-next', function () {
                if (state.mode === 'month') { state.month = addMonths(state.month, 1); load(); return; }
                state.start = addDays(state.start, 7); state.day = null; load();
            });
            $pane.on('click', '.jv-label', function () {
                if (state.mode === 'month') { state.month = ymd(new Date()).slice(0, 7); load(); return; }
                state.start = mondayOf(new Date()); state.day = null; load();
            });
            $pane.on('click', '.jv-month', function () {
                if (state.mode === 'month') {
                    // Back to the week of the month shown (this week when it is the current month)
                    state.mode = 'week';
                    state.start = state.month === ymd(new Date()).slice(0, 7) ? mondayOf(new Date()) : mondayOf(parseYmd(state.month));
                    state.day = null;
                } else {
                    state.mode = 'month';
                    state.month = (state.day || state.start).slice(0, 7);
                }
                load();
            });
            // Klik tanggal di kalender: buka daftar dokter hari itu
            $pane.on('click', '[data-cal-date]', function () {
                var date = $(this).data('cal-date');
                state.mode = 'day';
                state.day = date;
                state.start = mondayOf(parseYmd(date));
                load();
            });
            $pane.on('click', '.jv-day', function () {
                state.mode = 'day';
                state.day = $(this).data('date');
                render();
            });
            $pane.on('click', '.jv-mode', function () {
                if (state.mode === 'month') {
                    state.mode = 'week';
                    state.start = state.month === ymd(new Date()).slice(0, 7) ? mondayOf(new Date()) : mondayOf(parseYmd(state.month));
                    state.day = null;
                    load();
                    return;
                }
                state.mode = state.mode === 'week' ? 'day' : 'week';
                render();
            });
            $pane.on('input', '.jv-search', render);
            $pane.on('change', '.jv-clinic', load);
            $pane.on('click', '.jv-download', function () {
                var clinic = $pane.find('.jv-clinic').val();
                var month = state.mode === 'month' ? state.month : state.start.slice(0, 7);
                var url = key === 'dokter'
                    ? "{{ route('hrd.dokter-schedule.print') }}?month=" + month + (clinic ? '&clinic_id=' + clinic : '')
                    : "{{ route('hrd.schedule.print') }}?start_date=" + state.start;
                var name = key === 'dokter' ? 'jadwal_dokter_' + month : 'jadwal_karyawan_' + state.start;
                var $btn = $(this).prop('disabled', true);
                Promise.resolve(renderPdfToSingleImage(url, name, document.getElementById('jadwalPdfCanvas')))
                    .finally(function () { $btn.prop('disabled', false); });
            });

            // Geser kiri/kanan di daftar harian untuk ganti hari (HP)
            var touchX = null;
            $pane.on('touchstart', '.jv-body', function (e) { touchX = e.originalEvent.touches[0].clientX; });
            $pane.on('touchend', '.jv-body', function (e) {
                if (touchX === null || state.mode !== 'day' || !state.data) return;
                var dx = e.originalEvent.changedTouches[0].clientX - touchX;
                touchX = null;
                if (Math.abs(dx) < 60) return;
                var idx = state.data.dates.findIndex(function (d) { return d.date === state.day; }) + (dx < 0 ? 1 : -1);
                if (idx < 0) { state.start = addDays(state.start, -7); state.day = addDays(state.start, 6); load(); return; }
                if (idx > 6) { state.start = addDays(state.start, 7); state.day = state.start; load(); return; }
                state.day = state.data.dates[idx].date;
                render();
            });

            return { load: function () { if (!state.data) load(); } };
        }

        var viewers = { karyawan: createViewer('karyawan'), dokter: createViewer('dokter') };
        // Kolom/header sticky butuh warna latar solid yang sama dengan modal (tema terang/gelap)
        $('#jadwalModal').on('shown.bs.modal', function () {
            var content = this.querySelector('.modal-content');
            content.style.setProperty('--jv-bg', getComputedStyle(content).backgroundColor);
        });
        // Tombol chat melayang menutupi daftar di HP: sembunyikan selama jadwal terbuka
        $('#jadwalModal').on('show.bs.modal', function () { $('body').addClass('jv-open'); })
            .on('hidden.bs.modal', function () { $('body').removeClass('jv-open'); });
        window.belovaOpenJadwal = function () {
            $('#jadwalModal').modal('show');
            viewers[$('.jv-tabs .nav-link.active').data('jv')].load();
        };
        $('.jv-tabs a[data-toggle="pill"]').on('shown.bs.tab', function () { viewers[$(this).data('jv')].load(); });
    })();
}
// Loaded lazily (GET jadwal.viewer) after the page is ready, or inline on a normal page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBelovaJadwalViewer);
} else {
    initBelovaJadwalViewer();
}
</script>
