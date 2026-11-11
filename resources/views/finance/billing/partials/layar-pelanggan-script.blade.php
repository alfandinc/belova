{{--
    Layar Pelanggan: mirrors the bill on the patient's tablet and shows the satisfaction survey after payment.
    Hooks in create.blade.php: calculateTotals() → layarPelanggan.onTotals(snapshot); payment success → afterPayment(invoiceId).
    A bill whose payment was already processed when the page opened is NOT shown automatically (kasir browsing old bills).
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
    var LS_AUTO = 'layarPelanggan.auto';

    var layars = [];
    var selectedId = null;
    var auto = true;
    var snapshot = null;
    var lastSentJson = '';
    var pushTimer = null;
    var showingBill = false;      // this page put its bill on the screen
    var paidHere = false;         // payment processed on this page → stop auto bill pushes
    var ready = false;

    function lsGet(k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } }
    function lsSet(k, v) { try { window.localStorage.setItem(k, v); } catch (e) {} }
    function status(text, cls) {
        $('#lpStatus').removeClass('text-muted text-success text-danger text-warning').addClass(cls || 'text-muted').text(text || '');
    }
    function pushUrl() { return PUSH_URL.replace('__ID__', String(selectedId)); }
    function currentLayar() { return layars.find(function (l) { return String(l.id) === String(selectedId); }) || null; }

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
                if (auto && !PAID_AT_LOAD && snapshot) schedulePush(0);
                else if (PAID_AT_LOAD) status('Billing sudah dibayar: tidak ditampilkan otomatis.');
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
        if (!selectedId || !snapshot || !VISITATION_ID) return;
        var body = { mode: 'billing', visitation_id: VISITATION_ID, items: snapshot.items, totals: snapshot.totals, force: !!force };
        var json = JSON.stringify([selectedId, body.items, body.totals]);
        if (!force && showingBill && json === lastSentJson) return;   // nothing changed
        post(body).then(function (res) {
            if (res && res.success) {
                lastSentJson = json;
                showingBill = true;
                status('Billing tampil di layar pasien.', 'text-success');
            } else if (res && res.blocked === 'survey') {
                showingBill = false;
                status(res.message + ' Klik "Tampilkan" untuk menimpa.', 'text-warning');
            }
        }).catch(function (xhr) {
            status((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal mengirim ke layar pasien.', 'text-danger');
        });
    }

    function schedulePush(delay) {
        clearTimeout(pushTimer);
        pushTimer = setTimeout(function () { pushBilling(false); }, delay == null ? 500 : delay);
    }

    function pushSurvey(invoiceId) {
        if (!selectedId || !invoiceId) return;
        clearTimeout(pushTimer);
        post({ mode: 'survey', invoice_id: invoiceId }).then(function (res) {
            showingBill = false;
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

    function clearScreen() {
        if (!selectedId) return;
        clearTimeout(pushTimer);
        post({ mode: 'idle' }).then(function () {
            showingBill = false;
            lastSentJson = '';
            status('Layar pasien dikosongkan.');
        }).catch(function () { status('Gagal mengosongkan layar.', 'text-danger'); });
    }

    $(function () {
        if (!$('#layarPelangganPanel').length) return;
        auto = lsGet(LS_AUTO) !== '0';
        $('#lpAuto').prop('checked', auto).on('change', function () {
            auto = this.checked;
            lsSet(LS_AUTO, auto ? '1' : '0');
            if (auto && !PAID_AT_LOAD && !paidHere) schedulePush(0);
        });
        $('#lpSelect').on('change', function () {
            selectedId = this.value;
            lsSet(LS_SEL, selectedId);
            showingBill = false;
            renderDot();
        });
        $('#lpShowBtn').on('click', function () { pushBilling(true); });
        $('#lpClearBtn').on('click', clearScreen);
        $('#lpSurveyBtn').on('click', function () {
            pushSurvey(window.oldInvoice && window.oldInvoice.id);
        });
        if (PAID_AT_LOAD && window.oldInvoice && window.oldInvoice.id) $('#lpSurveyBtn').show();

        loadList(true);
        setInterval(function () { loadList(false); }, 20000);

        // leaving the page takes this bill off the screen (never a survey or another patient's bill)
        window.addEventListener('pagehide', function () {
            if (!showingBill || !selectedId || !navigator.sendBeacon) return;
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
            if (!ready) return;
            if (auto && !PAID_AT_LOAD && !paidHere) schedulePush();
            else if (showingBill && !paidHere) schedulePush();   // shown manually: keep it live
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
