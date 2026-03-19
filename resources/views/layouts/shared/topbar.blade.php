@php
    $topbarShowDateTime = $topbarShowDateTime ?? false;
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
@endphp

<style>
    .shared-topbar-actions {
        gap: 12px;
        margin-right: 20px;
    }

    .shared-topbar-action-item {
        margin: 0 !important;
        display: flex;
        align-items: center;
    }

    .shared-topbar-profile-trigger {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 0 !important;
    }

    .shared-topbar-profile-trigger::after {
        display: none !important;
    }

    .shared-topbar-profile-name {
        margin: 0 !important;
    }

    .shared-theme-toggle {
        cursor: pointer;
        width: 36px;
        height: 36px;
        box-sizing: border-box;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: 1px solid var(--border-color, rgba(148, 163, 184, 0.35));
        color: var(--text-color, inherit);
        transition: all 0.3s ease;
        padding: 0;
    }

    .shared-theme-toggle:hover {
        background: var(--border-color, rgba(148, 163, 184, 0.18));
    }

    .shared-main-menu-toggle {
        background: transparent !important;
        border-color: #dc3545 !important;
        color: #dc3545 !important;
    }

    .shared-main-menu-toggle:hover {
        background: rgba(220, 53, 69, 0.12) !important;
        border-color: #dc3545 !important;
        color: #dc3545 !important;
    }

    .shared-main-menu-toggle i,
    .shared-main-menu-toggle svg {
        color: #dc3545 !important;
        stroke: #dc3545 !important;
        width: 14px;
        height: 14px;
        stroke-width: 2px;
    }

    .shared-main-menu-toggle .shared-main-menu-icon {
        width: auto;
        height: auto;
        font-size: 14px;
        line-height: 1;
    }

    .shared-main-menu-toggle:hover i,
    .shared-main-menu-toggle:hover svg {
        color: #dc3545 !important;
        stroke: #dc3545 !important;
    }

    .shared-theme-toggle:focus {
        outline: none;
        box-shadow: none;
    }

    .shared-theme-toggle-input {
        display: none;
    }

    .shared-topbar-avatar {
        width: 36px;
        height: 36px;
        box-sizing: border-box;
        display: block;
        object-fit: cover;
        border-radius: 10px;
        border: 1px solid var(--border-color, rgba(148, 163, 184, 0.35));
        background: transparent;
    }
</style>

<!-- Top Bar Start -->
<div class="topbar">
    @if($topbarShowDateTime)
        <nav class="navbar-custom d-flex align-items-center" style="width:100%;">
            <ul class="list-unstyled topbar-nav mb-0 d-flex align-items-center" style="margin-right:auto;">
                <li>
                    <button class="nav-link button-menu-mobile">
                        <i data-feather="menu" class="align-self-center topbar-icon"></i>
                    </button>
                </li>
            </ul>

        <div class="date-time-display mx-auto" id="{{ $topbarDateTimeId }}" style="font-size:15px; min-width:260px; text-align:center; font-weight:600;"></div>

            <ul class="list-unstyled topbar-nav float-right mb-0 d-flex align-items-center shared-topbar-actions" style="margin-left:auto;">
    @else
        <nav class="navbar-custom">
            <ul class="list-unstyled topbar-nav float-right mb-0 d-flex align-items-center shared-topbar-actions">
    @endif

                <li class="dropdown shared-topbar-action-item">
                    <a class="nav-link dropdown-toggle waves-effect waves-light nav-user shared-topbar-profile-trigger" data-toggle="dropdown" href="#" role="button"
                        aria-haspopup="false" aria-expanded="false">
                        <span class="nav-user-name hidden-sm shared-topbar-profile-name">{{ $topbarUser->name ?? '-' }}</span>
                        @if($topbarPhotoPath)
                            <img src="{{ asset('storage/' . $topbarPhotoPath) }}" alt="profile-user" class="shared-topbar-avatar" />
                        @else
                            <img src="{{ asset('img/avatar.png') }}" alt="profile-user" class="shared-topbar-avatar" />
                        @endif
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="{{ route($topbarProfileRoute) }}"><i data-feather="user" class="align-self-center icon-xs icon-dual mr-1"></i> Profile</a>
                        <a class="dropdown-item" href="#"><i data-feather="settings" class="align-self-center icon-xs icon-dual mr-1"></i> Settings</a>
                        <div class="dropdown-divider mb-0"></div>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                        <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i data-feather="power" class="align-self-center icon-xs icon-dual mr-1"></i> Logout
                        </a>
                    </div>
                </li>

    @if($topbarShowThemeToggle)
                <li class="nav-item shared-topbar-action-item">
                    <input type="checkbox" id="darkModeSwitch" class="shared-theme-toggle-input">
                    <button type="button" class="shared-theme-toggle" id="sharedThemeToggle" title="Toggle theme" aria-label="Toggle theme">
                        <i class="fas fa-sun"></i>
                    </button>
                </li>
    @endif

                <li class="nav-item shared-topbar-action-item">
                    <a href="/" class="shared-theme-toggle shared-main-menu-toggle" title="Kembali ke Main Menu" aria-label="Kembali ke Main Menu">
                        <i class="fas fa-home shared-main-menu-icon"></i>
                    </a>
                </li>

            </ul><!--end topbar-nav-->

    @if(!$topbarShowDateTime)
            <ul class="list-unstyled topbar-nav mb-0">
                <li>
                    <button class="nav-link button-menu-mobile">
                        <i data-feather="menu" class="align-self-center topbar-icon"></i>
                    </button>
                </li>
            </ul>
    @endif
        </nav>
    <!-- end navbar-->
</div>
<!-- Top Bar End -->

@if($topbarShowDateTime)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function updateSharedTopbarDateTime() {
                const now = new Date();
                const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                const dayName = days[now.getDay()];
                const day = String(now.getDate()).padStart(2, '0');
                const month = months[now.getMonth()];
                const year = now.getFullYear();
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                const formatted = `${dayName}, ${day} ${month} ${year} ${hours}:${minutes}:${seconds}`;
                const element = document.getElementById(@json($topbarDateTimeId));

                if (element) {
                    element.textContent = formatted;
                }
            }

            setInterval(updateSharedTopbarDateTime, 1000);
            updateSharedTopbarDateTime();
        });
    </script>
@endif

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
                syncThemeToggleIcon();
            });

            themeToggleInput.addEventListener('change', syncThemeToggleIcon);

            setTimeout(syncThemeToggleIcon, 0);
        });
    </script>
@endif