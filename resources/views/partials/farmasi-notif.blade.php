@if (Auth::user() && Auth::user()->hasRole('Farmasi'))
<script>
window.soundEnabled = false;
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

// One Farmasi popup (erm.partials.notif-queue shows them one at a time); a database notification is marked
// read when the user clicks OK, so it comes back (on any page) until then
function farmasiNotifPopup(item) {
    var title = 'Notifikasi';
    if (item.type === 'pasien_keluar') title = 'Pasien Keluar!';
    else if (item.type) title = item.type;

    var text = item.message || '';
    if (!text && item.sender) text = '(Dari: ' + item.sender + ')';

    var popup = Swal.fire({
        title: title,
        text: item.multiline ? undefined : text,
        html: item.multiline ? window.rujukanNotifHtml(item.message) : undefined,
        icon: 'info',
        confirmButtonText: 'OK'
    });
    window.BelovaNotifQueue.playSound(localStorage.getItem('notifSoundType') === 'bell' ? '/sounds/bell.wav' : '/sounds/notif.mp3');

    return popup.then(function(result) {
        if (item.source === 'database' && result && (result.value === true || result.isConfirmed)) {
            $.post('{{ url("erm/farmasi/notifications") }}/' + encodeURIComponent(item.id) + '/mark-read', {
                _token: '{{ csrf_token() }}'
            });
        }
        return result;
    });
}

var farmasiPollBusy = false;
function pollFarmasiNotifications() {
    if (farmasiPollBusy) return;
    farmasiPollBusy = true;

    $.get('{{ route("erm.check.notifications") }}', { page: window.farmasiNotifPage, queue: 1 }, function(data) {
        if (data && data.items) {
            window.BelovaNotifQueue.push(data.items, farmasiNotifPopup);
        }
    }).fail(function(xhr, status, err) {
        // silently ignore (could be auth redirect when session expires)
    }).always(function() {
        farmasiPollBusy = false;
    });
}

// Poll every 2s from one tab per browser (the tab the user is looking at); see public/js/belova-tab-leader.js
BelovaTabLeader.run('farmasi-notif', 2000, pollFarmasiNotifications);
</script>
@endif
