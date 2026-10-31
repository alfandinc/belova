@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
    $canMap = auth()->user()?->hasRole('Admin');
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['periodLabel'] . ' · invoice dari referral Marketing & Sales'"
    :href="$canMap ? $data['mappingUrl'] : null" href-label="Atur referral">
    @if (!$data['mapped'])
        <x-dash.empty icon="share-2" title="Belum ada referral yang dipetakan"
            text="Admin perlu menentukan referral mana yang dihitung sebagai Marketing & Sales di Admin Panel › Referral Marketing." />
    @else
        <div class="dw-stats">
            <x-dash.stat label="Omset Marketing & Sales" :value="Fmt::rpShort($data['total'])" :trend="$data['trend']" :trend-label="'vs ' . $data['previousLabel']" />
            <x-dash.stat label="Invoice" :value="Fmt::num($data['invoices'])" meta="dengan referral marketing" />
            <x-dash.stat label="Porsi dari omset" :value="Fmt::pct($data['share'])" meta="semua klinik" />
        </div>

        <div class="dw-section-label">Per klinik</div>
        <div class="dw-clinics is-columns is-compact">
            @foreach ($data['clinics'] as $c)
                <x-dash.clinic :klinik="$c['klinik']">
                    <div class="dw-stat-value">{{ Fmt::rpShort($c['omset']) }}</div>
                    <div class="dw-stat-meta">{{ Fmt::num($c['invoices']) }} invoice · {{ Fmt::pct($c['share']) }} dari omset klinik</div>
                    <x-dash.delta :trend="$c['trend']" label="vs periode lalu" />
                </x-dash.clinic>
            @endforeach
        </div>

        <div class="dw-section-label">Per sumber referral</div>
        @if (empty($data['sources']))
            <p class="dw-note mt-0">Belum ada invoice dari referral marketing pada periode ini.</p>
        @else
            <div class="dw-bars">
                @foreach ($data['sources'] as $source)
                    <x-dash.bar :label="$source['label']" :value="Fmt::rp($source['omset']) . ' · ' . Fmt::pct($source['pct'])"
                        :pct="$source['pct']" :sub="Fmt::num($source['invoices']) . ' invoice'" />
                @endforeach
            </div>
        @endif
    @endif
</x-dash.card>
