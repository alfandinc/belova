{{-- Perawat / Pendaftaran / Lab notification polling & sound (popups: erm.partials.notif-queue) --}}
@if (Auth::user()->hasAnyRole(['Perawat', 'Pendaftaran', 'Lab']))
<script>
if (document.getElementById('rawatjalan-table')) {
    // Rawat Jalan polls itself (rawatjalans/assets/index_js) into the same queue, with the unread badge.
} else {
window.soundEnabled = false;
$(function() {
    Swal.fire({
        title: 'Aktifkan Notifikasi Suara?',
        text: 'Klik OK untuk mengaktifkan suara notifikasi. Anda hanya perlu melakukan ini sekali.',
        icon: 'question',
        confirmButtonText: 'OK'
    }).then(() => {
        var audio = new Audio('/sounds/confirm.mp3');
        audio.play();
        window.soundEnabled = true;
    });
});

if (window.__ermPerawatNotifTimer) {
    clearInterval(window.__ermPerawatNotifTimer);
}

var perawatPollBusy = false;
// One poller per browser (shared with the Rawat Jalan page handler); see public/js/belova-tab-leader.js
window.__ermPerawatNotifTimer = null;
BelovaTabLeader.run('erm-get-notif', 2000, function() {
    if (perawatPollBusy) return;
    perawatPollBusy = true;

    // Every unread notification of this login (max 15 minutes old) comes back until it is read;
    // the queue shows each once per browser, never over another popup, and marks it read on OK
    $.get('/erm/get-notif', { queue: 1 }, function(data) {
        window.BelovaNotifQueue.push(data.items || [], window.BelovaNotifQueue.ermPopup);
    }).always(function() {
        perawatPollBusy = false;
    });
});
}
</script>
@endif
