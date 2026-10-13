@if (Auth::user() && Auth::user()->hasRole('Farmasi'))
<script>
window.soundEnabled = false;
// Initialize last check to server time so existing unread notifications
// prior to this page load are not shown as 'new' immediately after login.
// Use a small buffer to avoid missing notifications created in the same second.
window.lastFarmasiNotifCheck = window.lastFarmasiNotifCheck || {{ time() - 3 }};
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

var farmasiPollBusy = false;
function pollFarmasiNotifications() {
    if (farmasiPollBusy) return;
    farmasiPollBusy = true;

    // Continue from the previous leader tab's last check so nothing falls in the hand-over gap
    var sharedLastCheck = parseInt(BelovaTabLeader.shared('farmasi-notif'), 10);
    if (sharedLastCheck && sharedLastCheck < window.lastFarmasiNotifCheck) {
        window.lastFarmasiNotifCheck = sharedLastCheck;
    }

    // Send lastCheck and page so server can correctly compare timestamps
    $.get('{{ route("erm.check.notifications") }}', { lastCheck: window.lastFarmasiNotifCheck, page: window.farmasiNotifPage }, function(data) {
        // update last check timestamp from server to avoid re-processing
        if (data && data.timestamp) {
            try { window.lastFarmasiNotifCheck = parseInt(data.timestamp) || window.lastFarmasiNotifCheck; } catch(e) {}
            BelovaTabLeader.share('farmasi-notif', window.lastFarmasiNotifCheck);
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
    }).always(function() {
        farmasiPollBusy = false;
    });
}

// Poll every 2s from one tab per browser (the tab the user is looking at); see public/js/belova-tab-leader.js
BelovaTabLeader.run('farmasi-notif', 2000, pollFarmasiNotifications);
</script>
@endif
