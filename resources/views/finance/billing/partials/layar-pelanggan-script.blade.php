{{--
    Layar Pelanggan: mirrors the bill on the patient's tablet and shows the satisfaction survey after payment.
    Hooks in create.blade.php: calculateTotals() → layarPelanggan.onTotals(snapshot); payment success → afterPayment(invoiceId).

    "Bagikan ke pasien" switch (per billing page):
      ON  → the bill is mirrored live.
      OFF → the tablet shows a neutral "mohon tunggu" screen (no name / items / prices) and nothing is sent,
            so the kasir can adjust prices the patient should not watch; ON again shows the final bill.
    Starts ON for an unpaid bill, OFF when the payment was already processed (kasir browsing old bills).
--}}
@if(auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Kasir', 'Finance']) && !empty($visitation))
<script>
window.layarPelanggan = (function () {
    'use strict';
    var LIST_URL = @json(route('finance.layar-pelanggan.kasir-list'));
    var PUSH_URL = @json(url('/finance/layar-pelanggan/__ID__/push'));
    var CSRF = @json(csrf_token());
    var VISITATION_ID = @json($visitation->id ?? null);
    var PAID_AT_LOAD = @json(!empty($invoice?->payment_method));
    var LS_SEL = 'layarPelanggan.selected';

    var layars = [];
    var selectedId = null;
    var sharing = !PAID_AT_LOAD;
    var snapshot = null;
    var lastSentJson = '';
    var pushTimer = null;
    var onScreen = false;         // this page's bill or its "mohon tunggu" screen is on the tablet
    var paidHere = false;         // payment processed on this page → the survey takes over
    var ready = false;

    function lsGet(k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } }
    function lsSet(k, v) { try { window.localStorage.setItem(k, v); } catch (e) {} }
    function status(text, cls) {
        $('#lpStatus').removeClass('text-muted text-success text-danger text-warning').addClass(cls || 'text-muted').text(text || '');
    }
    function pushUrl() { return PUSH_URL.replace('__ID__', String(selectedId)); }
    function currentLayar() { return layars.find(function (l) { return String(l.id) === String(selectedId); }) || null; }

    function setSharingUi() {
        $('#lpShare').prop('checked', sharing);
        $('#lpPausedNote').toggle(!sharing && onScreen);
    }

    function renderDot() {
        var l = currentLayar();
        var online = !!(l && l.online);
        $('#lpDot').css('background', online ? '#28a745' : '#dc3545')
            .attr('title', online ? 'Tablet terhubung' : 'Tablet tidak terhubung (cek tablet / wifi)');
    }

    function loadList(initial) {
        return $.getJSON(LIST_URL).then(function (res) {
            layars = (res && res.data) || [];
            if (!layars.length) {
                $('#layarPelangganPanel').hide();
                $('#layarPelangganEmpty').show();
                return;
            }
            $('#layarPelangganEmpty').hide();
            $('#layarPelangganPanel').show();

            var saved = lsGet(LS_SEL);
            if (!layars.some(function (l) { return String(l.id) === String(selectedId); })) {
                selectedId = layars.some(function (l) { return String(l.id) === String(saved); }) ? saved : layars[0].id;
            }
            var $sel = $('#lpSelect').empty();
            layars.forEach(function (l) { $sel.append($('<option>').val(l.id).text(l.nama)); });
            $sel.val(String(selectedId)).toggle(layars.length > 1);
            renderDot();

            if (initial) {
                ready = true;
                setSharingUi();
                if (sharing && snapshot) schedulePush(0);
                else if (PAID_AT_LOAD) status('Billing sudah dibayar: tidak dibagikan otomatis.');
            }
        }).catch(function () { /* panel stays hidden */ });
    }

    function post(data) {
        return $.ajax({
            url: pushUrl(),
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': CSRF }
        });
    }

    function pushBilling(force) {
        if (!selectedId || !snapshot || !VISITATION_ID || !sharing) return;
        var body = { mode: 'billing', visitation_id: VISITATION_ID, items: snapshot.items, totals: snapshot.totals, force: !!force };
        var json = JSON.stringify([selectedId, body.items, body.totals]);
        if (!force && onScreen && json === lastSentJson) return;   // nothing changed
        post(body).then(function (res) {
            if (!sharing) return;                                    // switched off while sending
            if (res && res.success) {
                lastSentJson = json;
                onScreen = true;
                $('#lpForceBtn').hide();
                status('Billing tampil di layar pasien.', 'text-success');
            } else if (res && res.blocked === 'survey') {
                onScreen = false;
                $('#lpForceBtn').show();
                status(res.message, 'text-warning');
            }
        }).catch(function (xhr) {
            status((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal mengirim ke layar pasien.', 'text-danger');
        });
    }

    function schedulePush(delay) {
        clearTimeout(pushTimer);
        pushTimer = setTimeout(function () { pushBilling(false); }, delay == null ? 500 : delay);
    }

    function startSharing() {
        sharing = true;
        lastSentJson = '';
        setSharingUi();
        pushBilling(false);
    }

    function stopSharing() {
        sharing = false;
        clearTimeout(pushTimer);
        lastSentJson = '';
        setSharingUi();
        if (!selectedId || !VISITATION_ID) return;
        post({ mode: 'hold', visitation_id: VISITATION_ID }).then(function (res) {
            if (sharing) return;
            if (res && res.skipped === 'survey') {
                onScreen = false;
                status('Tidak dibagikan. Layar sedang menampilkan survei pasien sebelumnya.');
            } else {
                onScreen = true;
                status('Tidak dibagikan.', 'text-warning');
            }
            setSharingUi();
        }).catch(function () { status('Gagal menjeda layar pasien.', 'text-danger'); });
    }

    function pushSurvey(invoiceId) {
        if (!selectedId || !invoiceId) return;
        clearTimeout(pushTimer);
        post({ mode: 'survey', invoice_id: invoiceId }).then(function (res) {
            onScreen = false;
            sharing = false;
            setSharingUi();
            $('#lpForceBtn').hide();
            if (res && res.success) {
                status('Survei kepuasan tampil di layar pasien.', 'text-success');
                $('#lpSurveyBtn').hide();
            } else {
                status((res && res.message) || 'Survei tidak bisa ditampilkan.', res && res.already ? 'text-muted' : 'text-warning');
            }
        }).catch(function (xhr) {
            status((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menampilkan survei.', 'text-danger');
        });
    }

    $(function () {
        if (!$('#layarPelangganPanel').length) return;
        setSharingUi();
        $('#lpShare').on('change', function () {
            if (this.checked) startSharing(); else stopSharing();
        });
        $('#lpSelect').on('change', function () {
            selectedId = this.value;
            lsSet(LS_SEL, selectedId);
            onScreen = false;
            lastSentJson = '';
            renderDot();
            if (sharing) pushBilling(false);
        });
        $('#lpForceBtn').on('click', function () {
            sharing = true;
            setSharingUi();
            pushBilling(true);
        });
        $('#lpSurveyBtn').on('click', function () {
            pushSurvey(window.oldInvoice && window.oldInvoice.id);
        });
        if (PAID_AT_LOAD && window.oldInvoice && window.oldInvoice.id) $('#lpSurveyBtn').show();

        loadList(true);
        setInterval(function () { loadList(false); }, 20000);

        // leaving the page takes this bill / "mohon tunggu" off the screen (never a survey or another patient's bill)
        window.addEventListener('pagehide', function () {
            if (!onScreen || !selectedId || !navigator.sendBeacon) return;
            var fd = new FormData();
            fd.append('_token', CSRF);
            fd.append('mode', 'idle');
            fd.append('only_visitation', VISITATION_ID);
            try { navigator.sendBeacon(pushUrl(), fd); } catch (e) {}
        });
    });

    return {
        onTotals: function (snap) {
            snapshot = snap;
            if (ready && sharing && !paidHere) schedulePush();
        },
        afterPayment: function (invoiceId) {
            paidHere = true;
            $('#lpSurveyBtn').show();
            pushSurvey(invoiceId);
        }
    };
})();
</script>
@endif
