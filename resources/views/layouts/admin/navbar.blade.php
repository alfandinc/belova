<!-- Admin Left Sidenav -->
<div class="left-sidenav">
    <div class="brand mt-3">
        <a href="/admin" class="logo">
            <span>
                <img src="{{ asset('img/logo-belova-klinik.png') }}" alt="logo" class="logo-dark" style="height:48px;">
            </span>
        </a>
    </div>
    <div class="menu-content h-100" data-simplebar>
        <ul class="metismenu left-sidenav-menu">
            <li class="menu-label mt-0">Admin</li>

            <li>
                <a href="/admin"><i data-feather="grid" class="align-self-center menu-icon"></i><span>Dashboard</span></a>
            </li>

            <li class="menu-label">User Management</li>
            <li>
                <a href="{{ route('admin.users.index') }}"><i data-feather="users" class="align-self-center menu-icon"></i><span>Users</span></a>
            </li>
            <li>
                <a href="{{ route('admin.roles.index') }}"><i data-feather="shield" class="align-self-center menu-icon"></i><span>Roles</span></a>
            </li>

            <li class="menu-label">WhatsApp</li>
            <li>
                <a href="{{ route('admin.whatsapp_test.index') }}"><i data-feather="message-circle" class="align-self-center menu-icon"></i><span>WhatsApp Test</span></a>
            </li>
            <li>
                <a href="{{ route('admin.wa_messages.index') }}"><i data-feather="file-text" class="align-self-center menu-icon"></i><span>Message Log</span></a>
            </li>

            <li class="menu-label">Others</li>
            <li>
                <a href="/admin/settings"><i data-feather="settings" class="align-self-center menu-icon"></i><span>Settings</span></a>
            </li>
        </ul>
    </div>
</div>
<!-- end left-sidenav -->
