{{-- Topbar extras (moved from the old main menu): online users + mood, mood picker, profile editor, update info. --}}
<div class="modal fade" id="emotionPickerModal" tabindex="-1" role="dialog" aria-labelledby="emotionPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emotionPickerModalLabel">Ganti Mood</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Pilih mood terbaru untuk ditampilkan di topbar.</p>
                <div class="emotion-picker-grid" id="emotion-picker-grid">
                    @foreach($emotionOptions as $emotionKey => $emotionOption)
                        <button type="button" class="emotion-picker-option {{ $currentUserEmotion === $emotionKey ? 'active' : '' }}" data-emotion="{{ $emotionKey }}">
                            <span class="emoji">{{ $emotionOption['emoji'] }}</span>
                            <span class="label">{{ $emotionOption['label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="save-emotion-btn">Simpan Mood</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" id="profileModalContent"></div>
    </div>
</div>

@include('partials.system_update_modal')

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery) return;
    var $ = window.jQuery;

    // Modals rendered inside the fixed topbar area: move them to <body> so the backdrop stacks correctly
    $('#emotionPickerModal, #editProfileModal, #systemUpdateModal, #jadwalModal, #passwordVerificationModal').appendTo('body');

    var currentUserId = {{ (int) Auth::id() }};
    var currentUserEmotion = @json($currentUserEmotion);
    var hasEmployeeProfile = {{ Auth::user() && Auth::user()->employee ? 'true' : 'false' }};
    var profileModalUrl = @json(route('hrd.employee.profile.modal'));
    var heartbeatUrl = @json(route('user-emotions.heartbeat'));
    var updateEmotionUrl = @json(route('user-emotions.update'));
    var csrf = $('meta[name="csrf-token"]').attr('content') || @json(csrf_token());
    var spinner = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>';

    function notify(icon, title, text) {
        if (window.Swal) { Swal.fire({ icon: icon, title: title, text: text }); } else { alert(title + (text ? '\n' + text : '')); }
    }

    function esc(value) {
        return String(value == null ? '' : value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    // ----- Online users -----
    function avatarHtml(item, withTooltip) {
        var isMe = Number(item.user_id || 0) === currentUserId;
        var avatar = item.avatar_url
            ? '<img src="' + esc(item.avatar_url) + '" alt="' + esc(item.name) + '" loading="lazy">'
            : '<span class="emotion-avatar-fallback">' + esc(item.initials || 'U') + '</span>';
        return '<span class="emotion-avatar-item ' + (isMe ? 'is-current-user js-open-emotion-picker' : '') + '" data-user-id="' + esc(item.user_id) + '">'
            + '<span class="emotion-avatar">' + avatar + '</span>'
            + '<span class="emotion-badge" style="background:' + esc(item.color || '#475569') + ';">' + esc(item.emoji || '🙂') + '</span>'
            + (withTooltip ? '<span class="emotion-avatar-tooltip"><span class="name">' + esc(item.name || 'User') + '</span><span class="mood">' + esc(item.label || 'Calm') + '</span></span>' : '')
            + '</span>';
    }

    function renderOnline(items) {
        items = Array.isArray(items) ? items.slice() : [];
        // Me first, then by name
        items.sort(function (a, b) {
            if (Number(a.user_id) === currentUserId) return -1;
            if (Number(b.user_id) === currentUserId) return 1;
            return String(a.name || '').localeCompare(String(b.name || ''));
        });

        var max = 14;
        $('#belovaOnlineCount').text(items.length);
        if (!items.length) {
            $('#user-emotion-board').html('<span class="emotion-chip-empty">Belum ada yang online</span>');
        } else {
            var html = items.slice(0, max).map(function (item) { return avatarHtml(item, true); }).join('');
            if (items.length > max) {
                html += '<button type="button" class="emotion-more js-online-more" title="Lihat semua">+' + (items.length - max) + '</button>';
            }
            $('#user-emotion-board').html(html);
        }

        $('#belovaOnlinePanel').html(items.length ? items.map(function (item) {
            return '<div class="row-user">' + avatarHtml(item, false)
                + '<div class="meta">' + esc(item.name || 'User') + (Number(item.user_id) === currentUserId ? ' <small class="d-inline">(Anda)</small>' : '')
                + '<small>' + esc(item.emoji || '') + ' ' + esc(item.label || '') + '</small></div></div>';
        }).join('') : '<div class="p-2 small text-muted">Belum ada yang online.</div>');
    }

    function heartbeat() {
        $.ajax({ url: heartbeatUrl, type: 'POST', data: { _token: csrf } })
            .done(function (response) { renderOnline(response.data || []); });
    }

    $(document).on('click', '#belovaOnlineToggle, .js-online-more', function (e) {
        e.stopPropagation();
        $('#belovaOnlinePanel').toggleClass('show');
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#belovaOnlinePanel').length) $('#belovaOnlinePanel').removeClass('show');
    });

    // Server-rendered list is fresh (RefreshActiveUserEmotion already ran for this request): no request on page load
    renderOnline(@json(array_values($activeUserEmotions)));
    setInterval(heartbeat, 60000);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') heartbeat();
    });

    // ----- Mood picker -----
    function selectEmotion(emotion) {
        $('.emotion-picker-option').removeClass('active');
        $('.emotion-picker-option[data-emotion="' + emotion + '"]').addClass('active');
    }

    $(document).on('click', '.js-open-emotion-picker', function (e) {
        e.preventDefault();
        selectEmotion(currentUserEmotion);
        $('#emotionPickerModal').modal('show');
    });
    $(document).on('click', '.emotion-picker-option', function () { selectEmotion($(this).data('emotion')); });
    $('#save-emotion-btn').on('click', function () {
        var $button = $(this).prop('disabled', true).text('Menyimpan...');
        var emotion = $('.emotion-picker-option.active').data('emotion') || 'calm';
        $.ajax({ url: updateEmotionUrl, type: 'POST', data: { _token: csrf, emotion: emotion } })
            .done(function (response) {
                currentUserEmotion = emotion;
                renderOnline(response.data || []);
                $('#emotionPickerModal').modal('hide');
            })
            .always(function () { $button.prop('disabled', false).text('Simpan Mood'); });
    });

    // ----- Profile editor -----
    function initProfileForm() {
        $('#profileModalContent').find('.custom-file-input').on('change', function () {
            var fileName = $(this).val().split('\\').pop();
            $(this).siblings('.custom-file-label').addClass('selected').html(fileName || 'Pilih file...');
        });

        $('#profileUpdateForm').on('submit', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $submit = $form.find('button[type="submit"]');
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                beforeSend: function () {
                    $submit.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...').attr('disabled', true);
                },
                success: function (response) {
                    if (!response.success) {
                        notify('error', 'Error!', response.message || 'Gagal memperbarui profil.');
                        return;
                    }
                    $('#editProfileModal').modal('hide');
                    if (window.Swal) {
                        Swal.fire({ icon: 'success', title: 'Berhasil!', text: response.message, timer: 2000, timerProgressBar: true })
                            .then(function () { window.location.reload(); });
                    } else {
                        window.location.reload();
                    }
                },
                error: function (xhr) {
                    var errors = (xhr.responseJSON && xhr.responseJSON.errors) || {};
                    var messages = Object.keys(errors).map(function (key) { return errors[key][0]; });
                    if (window.Swal) {
                        Swal.fire({ icon: 'error', title: 'Validation Error', html: messages.length ? messages.map(esc).join('<br>') : 'Terjadi kesalahan saat menyimpan profil.' });
                    } else {
                        alert(messages.join('\n') || 'Terjadi kesalahan saat menyimpan profil.');
                    }
                },
                complete: function () {
                    $submit.html('Simpan Perubahan').attr('disabled', false);
                }
            });
        });
    }

    $(document).on('click', '.js-open-profile-modal', function (e) {
        e.preventDefault();
        if (!hasEmployeeProfile) {
            notify('warning', 'Profil tidak tersedia', 'Data karyawan tidak ditemukan');
            return;
        }
        $('#profileModalContent').html(spinner);
        $('#editProfileModal').modal('show');
        $.get(profileModalUrl)
            .done(function (html) {
                $('#profileModalContent').html(html);
                initProfileForm();
            })
            .fail(function (xhr) {
                $('#editProfileModal').modal('hide');
                var message = (xhr.status === 404 && xhr.responseJSON && xhr.responseJSON.message) || 'Form profil gagal dimuat. Silakan coba lagi.';
                notify('warning', 'Profil tidak tersedia', message);
            });
    });

    // ----- System update info (auto-shown once per browser per update; bump the version with each new update) -----
    var SYSTEM_UPDATE_VERSION = '2026-10-12';
    $('#info-update-btn').on('click', function () { $('#systemUpdateModal').modal('show'); });
    try {
        if (localStorage.getItem('systemUpdateModalShown') !== SYSTEM_UPDATE_VERSION) {
            $('#systemUpdateModal').modal('show');
            localStorage.setItem('systemUpdateModalShown', SYSTEM_UPDATE_VERSION);
        }
    } catch (e) { /* storage blocked */ }

    // ----- Logout -----
    $('#belovaLogoutLink').on('click', function (e) {
        e.preventDefault();
        var form = document.getElementById('logout-form');
        if (!window.Swal) { form.submit(); return; }
        Swal.fire({
            title: 'Konfirmasi Logout',
            text: 'Apakah Anda yakin ingin logout?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Logout',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (result.isConfirmed || result.value) form.submit();
        });
    });
});
</script>
