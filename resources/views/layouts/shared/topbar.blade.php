@php
    $topbarShowThemeToggle = $topbarShowThemeToggle ?? true;
    $topbarProfileRoute = $topbarProfileRoute ?? 'hrd.employee.profile';
    $topbarDateTimeId = $topbarDateTimeId ?? 'shared-topbar-date-time-display';
    $topbarUser = Auth::user();
    $topbarPhotoPath = null;

    if ($topbarUser && $topbarUser->employee && $topbarUser->employee->photo) {
        $topbarPhotoPath = $topbarUser->employee->photo;
    } elseif ($topbarUser && $topbarUser->dokter && $topbarUser->dokter->photo) {
        $topbarPhotoPath = $topbarUser->dokter->photo;
    }

    $topbarOnlineUsers = $topbarUser ? \App\Http\Controllers\AuthController::getActiveUserEmotions() : [];
    $topbarEmotionOptions = \App\Http\Controllers\AuthController::emotionCatalog();
    $topbarCurrentEmotion = session('user_emotion', \App\Http\Controllers\AuthController::DEFAULT_EMOTION);
    // Bell: what is waiting on the user (same counts as the sidebar badges, which are cached per user)
    $topbarAttention = \App\Support\Navigation\AttentionItems::forUser($topbarUser);
@endphp

<style>
    .belova-topbar { display: flex; align-items: center; width: 100%; gap: 12px; padding-right: 20px; }
    .belova-topbar .topbar-nav { margin: 0; }
    .belova-topbar-right { display: flex; align-items: center; gap: 10px; margin-left: auto; flex: 0 0 auto; }

    .shared-topbar-profile-trigger { display: inline-flex; align-items: center; gap: 10px; padding: 0 !important; }
    .shared-topbar-profile-trigger::after { display: none !important; }
    .shared-topbar-profile-name { margin: 0 !important; }
    .shared-theme-toggle {
        cursor: pointer; width: 36px; height: 36px; box-sizing: border-box; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center; background: transparent;
        border: 1px solid var(--border-color, rgba(148, 163, 184, 0.35)); color: var(--text-color, inherit);
        transition: all 0.3s ease; padding: 0;
    }
    .shared-theme-toggle:hover { background: var(--border-color, rgba(148, 163, 184, 0.18)); }
    .shared-theme-toggle:focus { outline: none; box-shadow: none; }
    .shared-theme-toggle-input { display: none; }
    .shared-topbar-avatar {
        width: 36px; height: 36px; box-sizing: border-box; display: block; object-fit: cover; border-radius: 10px;
        border: 1px solid var(--border-color, rgba(148, 163, 184, 0.35)); background: transparent;
    }
    .belova-topbar .date-time-display { font-size: 14px; font-weight: 600; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .belova-topbar .date-time-display .dt-date { opacity: .7; font-weight: 500; margin-right: 6px; }

    /* Online users (with mood) */
    .belova-online { flex: 1 1 auto; min-width: 0; display: flex; align-items: center; justify-content: center; gap: 10px; position: relative; }
    .belova-online-count {
        display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; white-space: nowrap;
        padding: 4px 10px; border-radius: 999px; border: 1px solid rgba(148, 163, 184, 0.3); background: transparent; color: inherit; cursor: pointer;
    }
    .belova-online-count .dot { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2); }
    .emotion-board { display: flex; align-items: center; gap: 2px; min-width: 0; }
    .emotion-avatar-item { position: relative; display: inline-flex; width: 38px; height: 38px; border-radius: 50%; flex: 0 0 auto; z-index: 1; }
    .emotion-avatar-item:hover { z-index: 6; }
    .emotion-avatar-item.is-current-user { cursor: pointer; }
    .emotion-avatar-item.is-current-user .emotion-avatar { box-shadow: 0 0 0 2px #1e88e5; }
    .emotion-avatar { width: 38px; height: 38px; border-radius: 50%; overflow: hidden; border: 2px solid rgba(148, 163, 184, 0.35); background: rgba(148, 163, 184, 0.15); }
    .emotion-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .emotion-avatar-fallback { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; color: #fff; background: linear-gradient(135deg, #64748b, #334155); }
    .emotion-badge {
        position: absolute; bottom: -3px; right: -3px; width: 18px; height: 18px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; font-size: 11px; line-height: 1;
        border: 2px solid rgba(15, 23, 42, 0.6); transition: transform 0.18s ease;
    }
    .emotion-avatar-item:hover .emotion-badge { transform: scale(1.5); }
    .emotion-avatar-tooltip {
        position: absolute; left: 50%; top: calc(100% + 8px); transform: translateX(-50%); padding: 5px 10px; border-radius: 10px;
        background: rgba(15, 23, 42, 0.94); color: #fff; font-size: 11px; white-space: nowrap; opacity: 0; pointer-events: none;
        transition: opacity 0.18s ease; display: flex; flex-direction: column; align-items: center; gap: 1px;
    }
    .emotion-avatar-tooltip .name { font-weight: 700; }
    .emotion-avatar-tooltip .mood { font-size: 10px; color: rgba(255, 255, 255, 0.8); }
    .emotion-avatar-item:hover .emotion-avatar-tooltip { opacity: 1; }
    .emotion-more { width: 38px; height: 38px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; border: 1px dashed rgba(148, 163, 184, 0.6); background: transparent; color: inherit; cursor: pointer; flex: 0 0 auto; }
    .emotion-chip-empty { font-size: 12px; opacity: .6; }
    .belova-online-panel {
        display: none; position: absolute; top: calc(100% + 12px); left: 50%; transform: translateX(-50%); z-index: 1050;
        width: 300px; max-height: 360px; overflow-y: auto; border-radius: 12px; padding: 8px;
        background: var(--belova-panel-bg, #1e2430); border: 1px solid rgba(148, 163, 184, 0.25); box-shadow: 0 16px 40px rgba(0, 0, 0, 0.3);
    }
    html.theme-light .belova-online-panel, [data-theme="light"] .belova-online-panel { --belova-panel-bg: #fff; }
    .belova-online-panel.show { display: block; }
    .belova-online-panel .row-user { display: flex; align-items: center; gap: 10px; padding: 6px 8px; border-radius: 8px; }
    .belova-online-panel .row-user:hover { background: rgba(148, 163, 184, 0.12); }
    .belova-online-panel .row-user .emotion-avatar-item { width: 32px; height: 32px; }
    .belova-online-panel .row-user .emotion-avatar { width: 32px; height: 32px; }
    .belova-online-panel .row-user .emotion-avatar-tooltip { display: none; }
    .belova-online-panel .row-user .meta { min-width: 0; font-size: 13px; line-height: 1.25; }
    .belova-online-panel .row-user .meta small { display: block; opacity: .7; }

    /* Mood picker */
    .emotion-picker-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; }
    .emotion-picker-option { border: 1px solid rgba(148, 163, 184, 0.3); border-radius: 12px; background: transparent; color: inherit; padding: 12px 8px; text-align: center; cursor: pointer; transition: all .2s ease; }
    .emotion-picker-option:hover { transform: translateY(-2px); }
    .emotion-picker-option.active { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.22); background: rgba(37, 99, 235, 0.12); }
    .emotion-picker-option .emoji { display: block; font-size: 24px; line-height: 1; margin-bottom: 8px; }
    .emotion-picker-option .label { display: block; font-size: 13px; font-weight: 600; }

    /* Bell: what needs the user's action */
    .belova-bell { position: relative; }
    .belova-bell-count {
        position: absolute; top: -6px; right: -6px; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px;
        display: inline-flex; align-items: center; justify-content: center;
        background: #ef4444; color: #fff; font-size: 10px; font-weight: 700; line-height: 1;
        box-shadow: 0 0 0 2px var(--belova-topbar-bg, #1e2430);
    }
    html.theme-light .belova-bell-count, [data-theme="light"] .belova-bell-count { --belova-topbar-bg: #fff; }
    .belova-attention-menu {
        width: 360px; max-width: calc(100vw - 24px); padding: 0; overflow: hidden; border-radius: 12px;
        background: var(--belova-panel-bg, #1e2430); border: 1px solid rgba(148, 163, 184, 0.25); box-shadow: 0 16px 40px rgba(0, 0, 0, 0.3);
    }
    html.theme-light .belova-attention-menu, [data-theme="light"] .belova-attention-menu { --belova-panel-bg: #fff; }
    .belova-attention-head { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; padding: 12px 16px; border-bottom: 1px solid rgba(148, 163, 184, 0.2); }
    .belova-attention-head strong { font-size: 14px; }
    .belova-attention-head span { font-size: 12px; opacity: .7; }
    .belova-attention-list { list-style: none; margin: 0; padding: 6px; max-height: 420px; overflow-y: auto; }
    .belova-attention-item { display: flex; align-items: center; gap: 12px; padding: 9px 10px; border-radius: 8px; color: inherit !important; text-decoration: none !important; }
    .belova-attention-item:hover { background: rgba(148, 163, 184, 0.12); }
    .belova-attention-item .ic {
        flex: 0 0 32px; width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;
        background: rgba(148, 163, 184, 0.14);
    }
    .belova-attention-item .ic .feather { width: 16px; height: 16px; }
    .belova-attention-item .main { flex: 1 1 auto; min-width: 0; line-height: 1.3; }
    .belova-attention-item .title { display: block; font-size: 13px; font-weight: 600; }
    .belova-attention-item .meta { display: block; font-size: 11px; opacity: .7; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .belova-attention-item .n { flex: 0 0 auto; min-width: 26px; padding: 3px 8px; border-radius: 999px; font-size: 12px; font-weight: 700; text-align: center; }
    .belova-attention-item .n.is-critical { background: rgba(239, 68, 68, 0.16); color: #f87171; }
    .belova-attention-item .n.is-warning { background: rgba(245, 158, 11, 0.16); color: #f59e0b; }
    .belova-attention-item .n.is-info { background: rgba(59, 130, 246, 0.16); color: #60a5fa; }
    html.theme-light .belova-attention-item .n.is-critical { color: #dc2626; }
    html.theme-light .belova-attention-item .n.is-warning { color: #b45309; }
    html.theme-light .belova-attention-item .n.is-info { color: #2563eb; }
    .belova-attention-empty { padding: 28px 16px; text-align: center; font-size: 13px; opacity: .75; }
    .belova-attention-empty .feather { width: 28px; height: 28px; margin-bottom: 8px; color: #22c55e; }

    /* Informasi Update: blinking glow so users notice it */
    .belova-topbar-info { color: #f59e0b; border-color: rgba(245, 158, 11, 0.6); animation: belovaInfoBlink 1.2s ease-in-out infinite; }
    .belova-topbar-info:hover { animation-play-state: paused; background: rgba(245, 158, 11, 0.18); }
    @keyframes belovaInfoBlink {
        0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); background: rgba(245, 158, 11, 0.22); color: #fbbf24; }
        50% { box-shadow: 0 0 10px 4px rgba(245, 158, 11, 0.45); background: transparent; color: rgba(245, 158, 11, 0.45); }
    }
    @media (prefers-reduced-motion: reduce) { .belova-topbar-info { animation: none; background: rgba(245, 158, 11, 0.22); } }

    @media (max-width: 1399.98px) { .emotion-board .emotion-avatar-item:nth-child(n+9) { display: none; } }
    @media (max-width: 1199.98px) {
        .emotion-board .emotion-avatar-item:nth-child(n+5) { display: none; }
        .belova-topbar .date-time-display .dt-date { display: none; }
    }
    @media (max-width: 767.98px) {
        .emotion-board, .shared-topbar-profile-name, .belova-topbar-info { display: none !important; }
        .belova-topbar { padding-right: 10px; gap: 8px; }
    }
</style>

<!-- Top Bar Start -->
<div class="topbar" style="{{ \App\Support\ClinicTheme::cssVars('sb') }}">
    <nav class="navbar-custom belova-topbar">
        <ul class="list-unstyled topbar-nav mb-0">
            <li>
                <button class="nav-link button-menu-mobile" title="Tampilkan / sembunyikan menu">
                    <i data-feather="menu" class="align-self-center topbar-icon"></i>
                </button>
            </li>
        </ul>

        <div class="belova-online" id="belovaOnline">
            <button type="button" class="belova-online-count" id="belovaOnlineToggle" title="Lihat semua yang sedang online">
                <span class="dot"></span><span id="belovaOnlineCount">{{ count($topbarOnlineUsers) }}</span> online
            </button>
            <div class="emotion-board" id="user-emotion-board"></div>
            <div class="belova-online-panel" id="belovaOnlinePanel"></div>
        </div>

        <div class="belova-topbar-right">
            <div class="date-time-display" id="{{ $topbarDateTimeId }}"></div>

            @if($topbarShowThemeToggle)
                <input type="checkbox" id="darkModeSwitch" class="shared-theme-toggle-input">
                <button type="button" class="shared-theme-toggle" id="sharedThemeToggle" title="Ganti tema" aria-label="Ganti tema">
                    <i class="fas fa-sun"></i>
                </button>
            @endif

            <div class="dropdown belova-bell">
                <button type="button" class="shared-theme-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                        title="Perlu Perhatian" aria-label="Perlu Perhatian{{ $topbarAttention['total'] > 0 ? ': ' . $topbarAttention['total'] . ' menunggu' : '' }}">
                    <i data-feather="bell" style="width: 17px; height: 17px;"></i>
                    @if ($topbarAttention['total'] > 0)
                        <span class="belova-bell-count">{{ $topbarAttention['total'] > 99 ? '99+' : $topbarAttention['total'] }}</span>
                    @endif
                </button>
                <div class="dropdown-menu dropdown-menu-right belova-attention-menu">
                    <div class="belova-attention-head">
                        <strong>Perlu Perhatian</strong>
                        <span>{{ $topbarAttention['total'] > 0 ? $topbarAttention['total'] . ' menunggu tindak lanjut Anda' : 'Semua beres' }}</span>
                    </div>
                    @if (empty($topbarAttention['items']))
                        <div class="belova-attention-empty">
                            <i data-feather="check-circle"></i>
                            <div>Tidak ada persetujuan, pengingat, atau tenggat yang menunggu Anda.</div>
                        </div>
                    @else
                        <ul class="belova-attention-list">
                            @foreach ($topbarAttention['items'] as $item)
                                <li>
                                    <a href="{{ $item['url'] }}" class="belova-attention-item {{ $item['class'] }}">
                                        <span class="ic"><i data-feather="{{ $item['icon'] }}"></i></span>
                                        <span class="main">
                                            <span class="title">{{ $item['label'] }}</span>
                                            <span class="meta">{{ $item['module'] }} · {{ collect($item['reasons'])->map(fn ($r) => $r['count'] . ' ' . strtolower($r['title']))->implode(', ') }}</span>
                                        </span>
                                        <span class="n is-{{ $item['tone'] }}">{{ $item['count'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <button type="button" class="shared-theme-toggle belova-topbar-info" id="info-update-btn" title="Informasi Update" aria-label="Informasi Update">
                <i class="fas fa-info-circle"></i>
            </button>

            <div class="dropdown">
                <a class="nav-link dropdown-toggle nav-user shared-topbar-profile-trigger" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                    <span class="nav-user-name shared-topbar-profile-name">{{ $topbarUser->name ?? '-' }}</span>
                    @if($topbarPhotoPath)
                        <img src="{{ route('chat.avatar', ['user' => $topbarUser->id, 'size' => 48]) }}" alt="profile-user" class="shared-topbar-avatar" width="36" height="36" loading="eager" fetchpriority="high" decoding="async" />
                    @else
                        <img src="{{ asset('img/avatar.png') }}" alt="profile-user" class="shared-topbar-avatar" width="36" height="36" loading="eager" fetchpriority="high" decoding="async" />
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item js-open-profile-modal" href="#"><i data-feather="edit-2" class="align-self-center icon-xs icon-dual mr-1"></i> Edit Profil</a>
                    <a class="dropdown-item" href="{{ route($topbarProfileRoute) }}"><i data-feather="user" class="align-self-center icon-xs icon-dual mr-1"></i> Halaman Profil</a>
                    <a class="dropdown-item js-open-emotion-picker" href="#"><i data-feather="smile" class="align-self-center icon-xs icon-dual mr-1"></i> Ganti Mood</a>
                    <div class="dropdown-divider mb-0"></div>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    <a class="dropdown-item" href="#" id="belovaLogoutLink">
                        <i data-feather="power" class="align-self-center icon-xs icon-dual mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>
</div>
<!-- Top Bar End -->

@include('partials.topbar_user_modals', [
    'emotionOptions' => $topbarEmotionOptions,
    'currentUserEmotion' => $topbarCurrentEmotion,
    'activeUserEmotions' => $topbarOnlineUsers,
])

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var element = document.getElementById(@json($topbarDateTimeId));
        var days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        function pad(n) { return String(n).padStart(2, '0'); }

        function updateSharedTopbarDateTime() {
            if (!element) return;
            var now = new Date();
            element.innerHTML = '<span class="dt-date">' + days[now.getDay()] + ', ' + pad(now.getDate()) + ' ' + months[now.getMonth()] + ' ' + now.getFullYear() + '</span>'
                + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
        }

        setInterval(updateSharedTopbarDateTime, 1000);
        updateSharedTopbarDateTime();
    });
</script>

@if($topbarShowThemeToggle)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggleButton = document.getElementById('sharedThemeToggle');
            const themeToggleInput = document.getElementById('darkModeSwitch');

            if (!themeToggleButton || !themeToggleInput) {
                return;
            }

            function syncThemeToggleIcon() {
                const icon = themeToggleButton.querySelector('i');

                if (!icon) {
                    return;
                }

                icon.className = themeToggleInput.checked ? 'fas fa-sun' : 'fas fa-moon';
            }

            themeToggleButton.addEventListener('click', function() {
                themeToggleInput.checked = !themeToggleInput.checked;
                themeToggleInput.dispatchEvent(new Event('change', { bubbles: true }));
                if (window.belovaSetThemeCookie) window.belovaSetThemeCookie(themeToggleInput.checked ? 'dark' : 'light');
                syncThemeToggleIcon();
            });

            themeToggleInput.addEventListener('change', syncThemeToggleIcon);

            setTimeout(syncThemeToggleIcon, 0);
        });
    </script>
@endif
