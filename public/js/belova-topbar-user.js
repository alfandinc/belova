document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery) return;
    var $ = window.jQuery;

    // Modals rendered inside the fixed topbar area: move them to <body> so the backdrop stacks correctly
    $('#emotionPickerModal, #passwordChangeModal, #userPhotoModal,#systemUpdateModal, #jadwalModal, #passwordVerificationModal').appendTo('body');

    var currentUserId = window.BelovaTopbarUserConfig.currentUserId;
    var currentUserEmotion = window.BelovaTopbarUserConfig.currentUserEmotion;
    var heartbeatUrl = window.BelovaTopbarUserConfig.heartbeatUrl;
    var updateEmotionUrl = window.BelovaTopbarUserConfig.updateEmotionUrl;
    var csrf = $('meta[name="csrf-token"]').attr('content') || window.BelovaTopbarUserConfig.csrf;

    function esc(value) {
        return String(value == null ? '' : value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    // ----- Online users -----
    function avatarHtml(item, withTooltip) {
        var isMe = Number(item.user_id || 0) === currentUserId;
        var avatar = item.avatar_url
            ? '<img src="' + esc(item.avatar_url) + '" alt="' + esc(item.name) + '" data-initials="' + esc(item.initials || 'U') + '" loading="lazy">'
            : '<span class="emotion-avatar-fallback">' + esc(item.initials || 'U') + '</span>';
        // Own avatar: change mood; someone else's: view their profile photo
        return '<span class="emotion-avatar-item ' + (isMe ? 'is-current-user js-open-emotion-picker' : 'js-view-user-photo') + '" data-user-id="' + esc(item.user_id) + '"'
            + (isMe ? '' : ' role="button" tabindex="0" aria-label="Lihat foto ' + esc(item.name || 'User') + '"') + '>'
            + '<span class="emotion-avatar">' + avatar + '</span>'
            + '<span class="emotion-badge" style="background:' + esc(item.color || '#475569') + ';">' + esc(item.emoji || '🙂') + '</span>'
            + (withTooltip ? '<span class="emotion-avatar-tooltip"><span class="name">' + esc(item.name || 'User') + '</span><span class="mood">' + esc(item.label || 'Calm') + '</span></span>' : '')
            + '</span>';
    }

    var onlineById = {};

    function renderOnline(items) {
        items = Array.isArray(items) ? items.slice() : [];
        onlineById = {};
        items.forEach(function (item) { onlineById[String(item.user_id)] = item; });
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

        // Photo file missing: show the initials instead of a broken image
        $('#user-emotion-board img[data-initials], #belovaOnlinePanel img[data-initials]').on('error', function () {
            $(this).replaceWith('<span class="emotion-avatar-fallback">' + esc($(this).data('initials')) + '</span>');
        });
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

    // Profile photo of an online user, large
    $(document).on('click keydown', '.js-view-user-photo', function (e) {
        if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
        e.preventDefault();
        e.stopPropagation();
        var item = onlineById[String($(this).data('user-id'))];
        if (!item) return;
        $('#belovaOnlinePanel').removeClass('show');
        var initials = '<span class="user-photo-initials">' + esc(item.initials || 'U') + '</span>';
        var $frame = $('#userPhotoFrame').html(initials);
        if (item.avatar_url) {
            // Missing / unreadable file: keep the initials instead of a broken image
            $('<img>', { alt: 'Foto ' + (item.name || 'User') })
                .on('load', function () { $frame.html(this); })
                .attr('src', item.avatar_url);
        }
        $('#userPhotoName').text(item.name || 'User');
        $('#userPhotoMood').text(((item.emoji || '') + ' ' + (item.label || '')).trim());
        $('#userPhotoModal').modal('show');
    });

    // Server-rendered list is fresh (RefreshActiveUserEmotion already ran for this request): no request on page load
    renderOnline(window.BelovaTopbarUserConfig.activeUserEmotions);
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

    // ----- Ganti password (topbar menu + Profil Saya button) -----
    var $pwModal = $('#passwordChangeModal'), $pwForm = $('#passwordChangeForm'), $pwError = $('#pw-error');
    function pwError(message) {
        $pwError.text(message).toggle(!!message);
    }
    $(document).on('click', '.js-open-password-modal', function (e) {
        e.preventDefault();
        $pwForm[0].reset();
        $('#pw-current, #pw-new, #pw-confirm').attr('type', 'password');
        pwError('');
        $pwModal.modal('show');
    });
    $pwModal.on('shown.bs.modal', function () { $('#pw-current').trigger('focus'); });
    $('#pw-show').on('change', function () {
        $('#pw-current, #pw-new, #pw-confirm').attr('type', this.checked ? 'text' : 'password');
    });
    $pwForm.on('submit', function (e) {
        e.preventDefault();
        if (!$('#pw-current').val() || !$('#pw-new').val()) return pwError('Isi password saat ini dan password baru.');
        if ($('#pw-new').val().length < 8) return pwError('Password baru minimal 8 karakter.');
        if ($('#pw-new').val() !== $('#pw-confirm').val()) return pwError('Ulangi password baru: belum sama.');
        pwError('');
        var $btn = $('#pw-save').prop('disabled', true).text('Menyimpan...');
        $.ajax({ url: $pwForm.attr('action'), type: 'POST', data: $pwForm.serialize(), dataType: 'json' })
            .done(function (res) {
                $pwModal.modal('hide');
                if (window.Swal) Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, timerProgressBar: true });
            })
            .fail(function (xhr) {
                var r = xhr.responseJSON || {};
                var msg = r.errors ? r.errors[Object.keys(r.errors)[0]][0]
                    : (xhr.status === 419 ? 'Sesi habis. Muat ulang halaman lalu coba lagi.' : (r.message || 'Password gagal diperbarui.'));
                pwError(msg);
            })
            .always(function () { $btn.prop('disabled', false).text('Ganti Password'); });
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
