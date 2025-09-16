@if (Auth::user() && Auth::user()->hasRole('Farmasi'))
<script>
window.soundEnabled = false;
window.lastFarmasiNotifCheck = window.lastFarmasiNotifCheck || 0;
// Optional: allow overriding page context from a global variable
window.farmasiNotifPage = window.farmasiNotifPage || 'index';
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
    }).catch(function() {
        // user dismissed, keep sound disabled but allow alerts
    });
});

function pollFarmasiNotifications() {
    // Send lastCheck and page so server can correctly compare timestamps
    $.get('{{ route("erm.check.notifications") }}', { lastCheck: window.lastFarmasiNotifCheck, page: window.farmasiNotifPage }, function(data) {
        // update last check timestamp from server to avoid re-processing
        if (data && data.timestamp) {
            try { window.lastFarmasiNotifCheck = parseInt(data.timestamp) || window.lastFarmasiNotifCheck; } catch(e) {}
        }

        if (data && data.hasNew) {
            // same sound selection logic as perawat
            var soundFile = '/sounds/notif.mp3';
            if (localStorage.getItem('notifSoundType') === 'bell') {
                soundFile = '/sounds/bell.wav';
            }
            var title = 'Notifikasi';
            if (data.type === 'pasien_keluar') title = 'Pasien Keluar!';
            else if (data.type) title = data.type;

            var text = data.message || '';
            if (!text && data.sender) text = '(Dari: ' + data.sender + ')';

            Swal.fire({
                title: title,
                text: text,
                icon: 'info',
                confirmButtonText: 'OK'
            });
            if (window.soundEnabled) {
                var audio = new Audio(soundFile);
                audio.play();
            }
        }
    }).fail(function(xhr, status, err) {
        // silently ignore (could be auth redirect when session expires)
        //console.warn('farmasi poll failed', status);
    });
}

// Start polling every 2s
setInterval(pollFarmasiNotifications, 2000);
// immediate first poll
pollFarmasiNotifications();
</script>
@endif
