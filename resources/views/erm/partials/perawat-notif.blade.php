{{-- Perawat Notification Polling & Sound --}}
@if (Auth::user()->hasRole('Perawat'))
<script>
if (document.getElementById('rawatjalan-table')) {
    // Rawat Jalan has its own notification handler with richer behavior.
} else {
if (!document.getElementById('erm-perawat-notification-popup-style')) {
    $('head').append(
        '<style id="erm-perawat-notification-popup-style">'
        + '.swal2-icon.swal2-info.swal2-door-icon{border-color:#17a2b8;color:#17a2b8;}'
        + '.swal2-door-icon .fas{font-size:2.2rem;color:#17a2b8;line-height:1;}'
        + '.swal2-icon.swal2-info.swal2-call-icon{border-color:#f59e0b;color:#f59e0b;}'
        + '.swal2-call-icon .fas{font-size:2.2rem;color:#f59e0b;line-height:1;}'
        + '</style>'
    );
}

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

if (typeof window.__ermRealtimeNotificationSinceMs === 'undefined') {
    window.__ermRealtimeNotificationSinceMs = Date.now();
}

if (window.__ermPerawatNotifTimer) {
    clearInterval(window.__ermPerawatNotifTimer);
}

window.__ermPerawatNotifTimer = setInterval(function() {
    $.get('/erm/get-notif', {
        since_ms: window.__ermRealtimeNotificationSinceMs
    }, function(data) {
        if (data.new) {
            let soundFile = '/sounds/notif.mp3';
            let popupIconHtml = null;
            let popupCustomClass = {};
            if (data.message === 'Mohon buka pintu untuk pasien.') {
                soundFile = '/sounds/bell.wav';
                popupIconHtml = '<i class="fas fa-door-open"></i>';
                popupCustomClass = {
                    icon: 'swal2-door-icon'
                };
            } else if (data.message === 'Mohon datang ke ruang dokter.') {
                popupIconHtml = '<i class="fas fa-user-md"></i>';
                popupCustomClass = {
                    icon: 'swal2-call-icon'
                };
            }
            Swal.fire({
                title: data.title || 'Notifikasi dari Dokter',
                text: data.message + (data.sender ? ('\n(Dari: ' + data.sender + ')') : ''),
                icon: 'info',
                iconHtml: popupIconHtml,
                customClass: popupCustomClass,
                confirmButtonText: 'OK'
            });
            if (window.soundEnabled) {
                var audio = new Audio(soundFile);
                audio.play();
            }
        }
    });
}, 2000);
}
</script>
@endif
