@props(['icon' => 'inbox', 'title' => 'Belum ada data', 'text' => null])

<div {{ $attributes->merge(['class' => 'dw-empty']) }}>
    <div class="dw-empty-icon"><i data-feather="{{ $icon }}"></i></div>
    <div class="dw-empty-title">{{ $title }}</div>
    @if ($text)
        <p class="dw-empty-text">{{ $text }}</p>
    @endif
    {{ $slot }}
</div>
