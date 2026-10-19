{{-- Tabs shared by pages grouped under one sidebar menu. Usage: @include('bcl.partials.subnav', ['group' => 'finance']) --}}
@php
    $groups = [
        'finance' => [
            ['label' => 'Pemasukan', 'icon' => 'trending-up', 'route' => 'bcl.income.index'],
            ['label' => 'Pengeluaran', 'icon' => 'trending-down', 'route' => 'bcl.expense.index'],
        ],
        'settings' => [
            ['label' => 'Daftar Harga', 'icon' => 'dollar-sign', 'route' => 'bcl.pricelist.index'],
            ['label' => 'Kategori & Foto', 'icon' => 'folder', 'route' => 'bcl.category.index'],
            ['label' => 'WiFi Kamar', 'icon' => 'wifi', 'route' => 'bcl.roomwifi.index'],
            ['label' => 'Template Chat', 'icon' => 'message-circle', 'route' => 'bcl.chat_template.index'],
        ],
    ];
@endphp
<ul class="nav nav-pills mb-3 bcl-subnav">
    @foreach($groups[$group] ?? [] as $tab)
    <li class="nav-item">
        <a class="nav-link py-1 {{ request()->routeIs($tab['route']) ? 'active' : '' }}" href="{{ route($tab['route']) }}">
            <i data-feather="{{ $tab['icon'] }}" class="icon-xs mr-1"></i>{{ $tab['label'] }}
        </a>
    </li>
    @endforeach
</ul>
