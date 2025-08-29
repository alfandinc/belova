{{-- Perawat Notification Polling & Sound --}}
@if (Auth::user()->hasRole('Perawat'))
<script>
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
setInterval(function() {
    $.get('/erm/get-notif', function(data) {
        if (data.new) {
            let soundFile = '/sounds/notif.mp3';
            if (data.message === 'Mohon buka pintu untuk pasien.') {
                soundFile = '/sounds/bell.wav';
            }
            Swal.fire({
                title: 'Notifikasi dari Dokter',
                text: data.message + (data.sender ? ('\n(Dari: ' + data.sender + ')') : ''),
                icon: 'info',
                confirmButtonText: 'OK'
            });
            if (window.soundEnabled) {
                var audio = new Audio(soundFile);
                audio.play();
            }
        }
    });
}, 2000);
</script>
@endif
