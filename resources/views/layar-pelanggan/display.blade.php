@php
    $logoFor = function (?string $klinikNama) {
        $n = strtolower((string) $klinikNama);
        if (str_contains($n, 'premiere')) return asset('img/logo-premiere.png');
        if (str_contains($n, 'skin')) return asset('img/logo-belovaskin.png');
        if (str_contains($n, 'dental') || str_contains($n, 'gigi')) return asset('img/logo-dental.png');
        return asset('img/logo-belovacorp.png');
    };
    $defaultLogo = $logoFor(optional($layar->klinik)->nama);
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $layar->nama }}</title>
    <link rel="icon" href="{{ asset('img/logo-favicon-belova.png') }}">
    <style>
        :root {
            --bg: #f6f3ef;
            --card: #ffffff;
            --ink: #2b2522;
            --muted: #8a7f78;
            --line: #ebe4dd;
            --accent: #9c6b4e;
            --accent-soft: #f3e9e1;
            --good: #2f8f5b;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; background: var(--bg); color: var(--ink);
            font-family: -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            -webkit-user-select: none; user-select: none; overflow: hidden; }
        .screen { position: fixed; inset: 0; display: none; }
        .screen.active { display: flex; }

        /* idle */
        #idle { flex-direction: column; align-items: center; justify-content: center; gap: 28px; text-align: center; }
        #idle img { max-width: 46vw; max-height: 28vh; object-fit: contain; }
        #idle .welcome { font-size: clamp(22px, 3.4vw, 40px); font-weight: 300; letter-spacing: .02em; }
        #idle .clock { font-size: clamp(16px, 2vw, 22px); color: var(--muted); }
        #hold { flex-direction: column; align-items: center; justify-content: center; gap: 22px; text-align: center; }
        #hold img { max-width: 36vw; max-height: 22vh; object-fit: contain; }
        #hold .welcome { font-size: clamp(24px, 3.4vw, 40px); font-weight: 500; }
        #hold .clock { font-size: clamp(16px, 2vw, 22px); color: var(--muted); }
        .dots { display: flex; gap: 10px; }
        .dots span { width: 12px; height: 12px; border-radius: 50%; background: var(--accent); opacity: .3; animation: dot 1.2s infinite; }
        .dots span:nth-child(2) { animation-delay: .2s; }
        .dots span:nth-child(3) { animation-delay: .4s; }
        @keyframes dot { 0%, 80%, 100% { opacity: .3; transform: scale(1); } 40% { opacity: 1; transform: scale(1.25); } }

        /* billing */
        #billing { flex-direction: column; padding: 24px 28px; gap: 18px; }
        .bill-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .bill-head img { height: 52px; max-width: 220px; object-fit: contain; }
        .bill-head .who { text-align: right; }
        .bill-head .who .lbl { font-size: 14px; color: var(--muted); }
        .bill-head .who .name { font-size: clamp(20px, 2.6vw, 30px); font-weight: 600; }
        .bill-body { flex: 1; display: flex; gap: 22px; min-height: 0; }
        .items { flex: 1.6; background: var(--card); border-radius: 18px; border: 1px solid var(--line);
            display: flex; flex-direction: column; min-height: 0; }
        .items h3 { margin: 0; padding: 16px 20px; font-size: 16px; color: var(--muted); font-weight: 600;
            border-bottom: 1px solid var(--line); }
        .items-list { flex: 1; overflow-y: auto; padding: 4px 20px; scroll-behavior: smooth; }
        .item { display: flex; justify-content: space-between; gap: 14px; padding: 14px 0; border-bottom: 1px dashed var(--line); }
        .item:last-child { border-bottom: 0; }
        .item .nm { font-size: clamp(16px, 1.8vw, 20px); font-weight: 500; }
        .item .sub { font-size: 14px; color: var(--muted); margin-top: 3px; }
        .item .disc { color: var(--good); }
        .item .amt { font-size: clamp(16px, 1.8vw, 20px); font-weight: 600; white-space: nowrap; }
        .item.new { animation: flash 1.6s ease-out; }
        @keyframes flash { from { background: var(--accent-soft); } to { background: transparent; } }
        .empty { color: var(--muted); padding: 30px 0; text-align: center; }

        .sum { flex: 1; background: var(--card); border-radius: 18px; border: 1px solid var(--line);
            padding: 22px; display: flex; flex-direction: column; gap: 12px; }
        .sum .row { display: flex; justify-content: space-between; font-size: clamp(15px, 1.6vw, 18px); }
        .sum .row span:first-child { color: var(--muted); }
        .sum .total { margin-top: auto; background: var(--accent); color: #fff; border-radius: 14px; padding: 20px 22px; }
        .sum .total .lbl { font-size: 16px; opacity: .85; }
        .sum .total .val { font-size: clamp(32px, 4.6vw, 56px); font-weight: 700; line-height: 1.15; word-break: break-all; }

        /* survey */
        #survey { flex-direction: column; align-items: center; justify-content: center; padding: 28px; gap: 22px; text-align: center; }
        #survey .q { font-size: clamp(24px, 3.4vw, 40px); font-weight: 600; max-width: 900px; }
        #survey .hi { font-size: clamp(16px, 1.9vw, 22px); color: var(--muted); }
        .emojis { display: flex; gap: clamp(10px, 2vw, 26px); flex-wrap: wrap; justify-content: center; }
        .emo { background: var(--card); border: 2px solid var(--line); border-radius: 22px; width: clamp(96px, 14vw, 150px);
            padding: 14px 6px 12px; cursor: pointer; transition: transform .12s, border-color .12s, background .12s;
            display: flex; flex-direction: column; align-items: center; gap: 6px; font: inherit; color: inherit; }
        .emo .e { font-size: clamp(48px, 7vw, 76px); line-height: 1; filter: grayscale(.15); }
        .emo .t { font-size: clamp(13px, 1.4vw, 16px); color: var(--muted); }
        .emo:active { transform: scale(.95); }
        .emo.sel { border-color: var(--accent); background: var(--accent-soft); transform: scale(1.06); }
        .emo.sel .e { filter: none; }
        .emojis.picked .emo:not(.sel) { opacity: .45; }
        .complain { width: min(760px, 100%); display: none; flex-direction: column; gap: 12px; }
        .complain.show { display: flex; }
        .complain textarea { width: 100%; min-height: 110px; border-radius: 14px; border: 1px solid var(--line); padding: 14px 16px;
            font: inherit; font-size: 18px; resize: none; background: var(--card); -webkit-user-select: text; user-select: text; }
        .complain textarea:focus { outline: 2px solid var(--accent); border-color: transparent; }
        .btn-send { align-self: center; background: var(--accent); color: #fff; border: 0; border-radius: 14px; padding: 16px 48px;
            font-size: 20px; font-weight: 600; cursor: pointer; }
        .btn-send:disabled { opacity: .6; }
        .countdown { font-size: 13px; color: var(--muted); }

        /* thanks */
        #thanks { flex-direction: column; align-items: center; justify-content: center; gap: 14px; text-align: center; }
        #thanks .big { font-size: 90px; }
        #thanks .t1 { font-size: clamp(26px, 3.6vw, 42px); font-weight: 600; }
        #thanks .t2 { font-size: clamp(16px, 2vw, 22px); color: var(--muted); }

        #net { position: fixed; right: 12px; bottom: 10px; font-size: 12px; color: #b24a3b; display: none; }
        #net.show { display: block; }

        @media (max-width: 760px), (orientation: portrait) {
            #billing { padding: 16px; }
            .bill-body { flex-direction: column; }
            .items { flex: 1; }
            .sum { flex: none; }
        }
    </style>
</head>
<body>
    <section id="idle" class="screen active">
        <img id="idleLogo" src="{{ $defaultLogo }}" alt="logo">
        <div class="welcome">Selamat datang</div>
        <div class="clock" id="clock"></div>
    </section>

    <section id="hold" class="screen">
        <img id="holdLogo" src="{{ $defaultLogo }}" alt="logo">
        <div class="welcome">Mohon tunggu sebentar</div>
        <div class="clock">Tagihan Anda sedang disiapkan oleh kasir</div>
        <div class="dots"><span></span><span></span><span></span></div>
    </section>

    <section id="billing" class="screen">
        <div class="bill-head">
            <img id="billLogo" src="{{ $defaultLogo }}" alt="logo">
            <div class="who">
                <div class="lbl">Rincian tagihan</div>
                <div class="name" id="billName">-</div>
            </div>
        </div>
        <div class="bill-body">
            <div class="items">
                <h3>Item</h3>
                <div class="items-list" id="itemsList"></div>
            </div>
            <div class="sum">
                <div class="row"><span>Subtotal</span><span id="sSubtotal">Rp 0</span></div>
                <div class="row" id="rDiskon"><span>Diskon</span><span id="sDiskon"></span></div>
                <div class="row" id="rPajak"><span>Pajak</span><span id="sPajak"></span></div>
                <div class="row" id="rAdmin"><span>Biaya Administrasi</span><span id="sAdmin"></span></div>
                <div class="row" id="rOngkir"><span>Biaya Ongkir</span><span id="sOngkir"></span></div>
                <div class="total">
                    <div class="lbl">Total Pembayaran</div>
                    <div class="val" id="sTotal">Rp 0</div>
                </div>
            </div>
        </div>
    </section>

    <section id="survey" class="screen">
        <div class="hi" id="surveyHi"></div>
        <div class="q">Bagaimana pengalaman Anda hari ini?</div>
        <div class="emojis" id="emojis">
            <button type="button" class="emo" data-r="1"><span class="e">😡</span><span class="t">Sangat Tidak Puas</span></button>
            <button type="button" class="emo" data-r="2"><span class="e">🙁</span><span class="t">Tidak Puas</span></button>
            <button type="button" class="emo" data-r="3"><span class="e">😐</span><span class="t">Cukup</span></button>
            <button type="button" class="emo" data-r="4"><span class="e">🙂</span><span class="t">Puas</span></button>
            <button type="button" class="emo" data-r="5"><span class="e">😍</span><span class="t">Sangat Puas</span></button>
        </div>
        <div class="complain" id="complain">
            <textarea id="keluhan" maxlength="1000" placeholder="Ada keluhan atau saran? Tulis di sini (opsional)"></textarea>
            <button type="button" class="btn-send" id="btnSend">Kirim</button>
        </div>
        <div class="countdown" id="countdown"></div>
    </section>

    <section id="thanks" class="screen">
        <div class="big">🙏</div>
        <div class="t1">Terima kasih!</div>
        <div class="t2">Masukan Anda membantu kami melayani lebih baik.</div>
    </section>

    <div id="net">Koneksi terputus, mencoba lagi…</div>

<script>
(function () {
    'use strict';
    var STATE_URL = @json($stateUrl);
    var SURVEY_URL = @json($surveyUrl);
    var DEFAULT_LOGO = @json($defaultLogo);
    var LOGOS = {
        premiere: @json(asset('img/logo-premiere.png')),
        skin: @json(asset('img/logo-belovaskin.png')),
        dental: @json(asset('img/logo-dental.png'))
    };
    var POLL_MS = 1500;

    var key = '';
    var current = { mode: 'idle' };
    var holdUntil = 0;           // thanks screen: ignore server state until then
    var shownItemKeys = {};
    var surveyInvoice = null;
    var rating = 0;
    var autoSendTimer = null;
    var surveyDeadline = 0;
    var failures = 0;

    function $(id) { return document.getElementById(id); }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
    function rp(n) { n = Math.round(Number(n) || 0); return 'Rp ' + n.toLocaleString('id-ID'); }
    function qtyFmt(q) { q = Number(q) || 0; return (Math.round(q * 100) / 100).toLocaleString('id-ID'); }
    function logoFor(klinik) {
        var n = String(klinik || '').toLowerCase();
        if (n.indexOf('premiere') >= 0) return LOGOS.premiere;
        if (n.indexOf('skin') >= 0) return LOGOS.skin;
        if (n.indexOf('dental') >= 0 || n.indexOf('gigi') >= 0) return LOGOS.dental;
        return DEFAULT_LOGO;
    }

    function show(id) {
        ['idle', 'hold', 'billing', 'survey', 'thanks'].forEach(function (s) { $(s).classList.toggle('active', s === id); });
    }

    function tickClock() {
        var d = new Date();
        $('clock').textContent = d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
            + ' · ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        if (surveyDeadline && current.mode === 'survey') {
            var left = Math.max(0, Math.round((surveyDeadline - Date.now()) / 1000));
            $('countdown').textContent = left > 0 ? 'Layar kembali otomatis dalam ' + left + ' detik' : '';
        }
    }

    function renderBilling(s) {
        var logo = logoFor(s.klinik);
        $('billLogo').src = logo;
        $('idleLogo').src = logo;
        $('billName').textContent = s.pasien || '-';

        var items = s.items || [];
        var list = $('itemsList');
        var html = '';
        var nextKeys = {};
        var hasNew = false;
        items.forEach(function (it, i) {
            var k = it.nama + '|' + it.qty + '|' + it.total;
            var isNew = current.mode === 'billing' && !shownItemKeys[k];
            if (isNew) hasNew = true;
            nextKeys[k] = true;
            var sub = qtyFmt(it.qty) + ' × ' + rp(it.harga);
            if (it.diskon) sub += ' · <span class="disc">diskon ' + esc(it.diskon) + '</span>';
            html += '<div class="item' + (isNew ? ' new' : '') + '"><div><div class="nm">' + esc(it.nama) + '</div>'
                + '<div class="sub">' + sub + '</div></div><div class="amt">' + rp(it.total) + '</div></div>';
        });
        list.innerHTML = html || '<div class="empty">Belum ada item</div>';
        if (hasNew) list.scrollTop = list.scrollHeight;
        shownItemKeys = nextKeys;

        var t = s.totals || {};
        $('sSubtotal').textContent = rp(t.subtotal);
        setRow('rDiskon', 'sDiskon', t.diskon, '- ');
        setRow('rPajak', 'sPajak', t.pajak, '+ ');
        setRow('rAdmin', 'sAdmin', t.admin, '+ ');
        setRow('rOngkir', 'sOngkir', t.ongkir, '+ ');
        $('sTotal').textContent = rp(t.grand_total);
    }

    function setRow(rowId, valId, v, sign) {
        v = Number(v) || 0;
        $(rowId).style.display = v > 0 ? 'flex' : 'none';
        $(valId).textContent = sign + rp(v);
    }

    function resetSurvey() {
        rating = 0;
        clearTimeout(autoSendTimer);
        $('keluhan').value = '';
        $('complain').classList.remove('show');
        $('emojis').classList.remove('picked');
        Array.prototype.forEach.call(document.querySelectorAll('.emo'), function (b) { b.classList.remove('sel'); });
        $('btnSend').disabled = false;
        $('btnSend').textContent = 'Kirim';
    }

    function renderSurvey(s) {
        if (surveyInvoice !== s.invoice_id) {
            resetSurvey();
            surveyInvoice = s.invoice_id;
        }
        $('idleLogo').src = logoFor(s.klinik);
        $('surveyHi').textContent = 'Terima kasih, ' + (s.pasien || 'Bapak/Ibu');
        surveyDeadline = Date.now() + (Number(s.remaining) || 0) * 1000;
    }

    function apply(s) {
        var prevMode = current.mode;
        if (s.mode === 'billing') {
            if (prevMode !== 'billing') shownItemKeys = {};
            renderBilling(s);
            show('billing');
        } else if (s.mode === 'survey') {
            renderSurvey(s);
            show('survey');
        } else if (s.mode === 'hold') {
            shownItemKeys = {};
            $('holdLogo').src = logoFor(s.klinik);
            show('hold');
        } else {
            surveyInvoice = null;
            surveyDeadline = 0;
            resetSurvey();
            show('idle');
        }
        current = s;
    }

    // a picked emoji with no "Kirim" (patient walked away) is still saved after a while
    function armAutoSend() {
        clearTimeout(autoSendTimer);
        autoSendTimer = setTimeout(function () {
            if ($('keluhan').value.trim() === '' && document.activeElement !== $('keluhan')) sendSurvey();
            else armAutoSend();
        }, 25000);
    }

    document.querySelectorAll('.emo').forEach(function (b) {
        b.addEventListener('click', function () {
            rating = Number(b.getAttribute('data-r'));
            document.querySelectorAll('.emo').forEach(function (x) { x.classList.toggle('sel', x === b); });
            $('emojis').classList.add('picked');
            $('complain').classList.add('show');
            $('keluhan').placeholder = rating <= 3
                ? 'Mohon maaf atas ketidaknyamanannya. Apa yang bisa kami perbaiki? (opsional)'
                : 'Ada saran untuk kami? (opsional)';
            armAutoSend();
        });
    });
    $('keluhan').addEventListener('input', armAutoSend);
    $('btnSend').addEventListener('click', sendSurvey);

    function sendSurvey() {
        if (!rating || !surveyInvoice || $('btnSend').disabled) return;
        clearTimeout(autoSendTimer);
        $('btnSend').disabled = true;
        $('btnSend').textContent = 'Mengirim…';
        fetch(SURVEY_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ invoice_id: surveyInvoice, rating: rating, keluhan: $('keluhan').value })
        }).then(function (r) {
            if (r.ok || r.status === 409) {
                if (document.activeElement) document.activeElement.blur();
                holdUntil = Date.now() + 6000;
                show('thanks');
                current = { mode: 'thanks' };
                key = '';
            } else {
                throw new Error('HTTP ' + r.status);
            }
        }).catch(function () {
            $('btnSend').disabled = false;
            $('btnSend').textContent = 'Gagal, coba kirim lagi';
        });
    }

    function poll() {
        var controller = ('AbortController' in window) ? new AbortController() : null;
        var t = controller ? setTimeout(function () { controller.abort(); }, 8000) : null;
        fetch(STATE_URL + '?k=' + encodeURIComponent(key), {
            headers: { 'Accept': 'application/json' },
            cache: 'no-store',
            signal: controller ? controller.signal : undefined
        }).then(function (r) {
            if (r.status === 404) { location.reload(); return null; }
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        }).then(function (j) {
            if (t) clearTimeout(t);
            if (!j) return;
            failures = 0;
            $('net').classList.remove('show');
            if (Date.now() < holdUntil) return;          // keep the thanks screen a moment
            if (j.same) return;
            key = j.k;
            if (j.state) apply(j.state);
        }).catch(function () {
            if (t) clearTimeout(t);
            failures++;
            if (failures >= 3) $('net').classList.add('show');
        }).then(function () {
            setTimeout(poll, failures ? Math.min(10000, POLL_MS * (failures + 1)) : POLL_MS);
        });
    }

    // first touch → fullscreen (browsers only allow it after a user gesture)
    document.addEventListener('click', function goFull() {
        var el = document.documentElement;
        var fn = el.requestFullscreen || el.webkitRequestFullscreen;
        if (fn && !document.fullscreenElement) { try { fn.call(el); } catch (e) {} }
    }, { once: true });

    // keep the screen on where supported (https / localhost only)
    try { if (navigator.wakeLock) navigator.wakeLock.request('screen').catch(function () {}); } catch (e) {}

    // reload once a day in the early morning to pick up new versions of this page
    setInterval(function () {
        var d = new Date();
        if (d.getHours() === 4 && d.getMinutes() === 0 && current.mode === 'idle') location.reload();
    }, 60000);

    tickClock();
    setInterval(tickClock, 1000);
    poll();
})();
</script>
</body>
</html>
