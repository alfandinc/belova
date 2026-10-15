@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['periodLabel'] . ' · total ' . Fmt::rp($data['total'])">
    @if ($data['total'] <= 0)
        <x-dash.empty icon="pie-chart" title="Belum ada omset pada periode ini" />
    @else
        <div class="dw-section-row">
            <div class="dw-section-label">Semua klinik · per kategori · growth vs {{ $data['previousLabel'] }}</div>
            @if ($data['itemsUrl'])
                <button type="button" class="dw-more-btn" data-dash-items="{{ $data['itemsUrl'] }}" data-category="tindakan">
                    Semua item <i data-feather="list"></i>
                </button>
            @endif
        </div>
        <x-dash.stack :segments="collect($data['categories'])->map(fn ($c) => [
            'label' => $c['label'], 'value' => Fmt::rp($c['amount']), 'pct' => $c['pct'], 'color' => $c['color'],
        ])->all()" :legend="false" />
        <ul class="dw-legend is-inline">
            @foreach ($data['categories'] as $category)
                <li>
                    <i class="dw-dot" style="background: {{ $category['color'] }}"></i>
                    <span class="dw-legend-label">{{ $category['label'] }}</span>
                    <span class="dw-legend-value">{{ Fmt::rp($category['amount']) }}</span>
                    <span class="dw-legend-pct" style="width: auto;">{{ Fmt::pct($category['pct']) }}</span>
                    <x-dash.delta :trend="$category['trend']" class="mt-0" />
                </li>
            @endforeach
        </ul>

        <div class="dw-section-label">Semua klinik · metode bayar · Umum / Asuransi</div>
        <div class="row">
            <div class="col-lg-6">
                <x-dash.metode-bayar :split="$data['metodeBayar']" />
            </div>
        </div>

        <div class="dw-section-label">Per klinik</div>
        <div class="dw-clinics is-columns">
            @foreach ($data['clinics'] as $c)
                <x-dash.clinic :klinik="$c['klinik']" :meta="Fmt::rp($c['total']) . ' · ' . Fmt::pct(Fmt::share($c['total'], $data['total'])) . ' omset'">
                    @if ($c['total'] <= 0)
                        <div class="dw-clinic-empty">Belum ada omset pada periode ini.</div>
                    @else
                        <x-dash.stack :segments="$c['categories']" :legend="false" />
                        <ul class="dw-legend">
                            @foreach ($c['categories'] as $category)
                                @continue($category['pct'] <= 0)
                                <li>
                                    <i class="dw-dot" style="background: {{ $category['color'] }}"></i>
                                    <span class="dw-legend-label">{{ $category['label'] }}</span>
                                    <span class="dw-legend-value">{{ $category['value'] }}</span>
                                    <span class="dw-legend-pct">{{ Fmt::pct($category['pct']) }}</span>
                                </li>
                            @endforeach
                        </ul>

                        @if ($c['metodeBayar'])
                            <div class="dw-section-label">Metode bayar · Umum / Asuransi</div>
                            <x-dash.metode-bayar :split="$c['metodeBayar']" :color="$c['klinik']->color" />
                        @endif

                        @foreach ([['Tindakan terlaris', 'tindakan', $c['topTindakan'], 'x', 'Belum ada tindakan terjual.'], ['Obat & produk terlaris', 'obat', $c['topObat'], ' terjual', 'Belum ada obat/produk terjual.']] as [$title, $category, $items, $unit, $emptyText])
                            @php($max = max(1, collect($items)->max('total')))
                            <div class="dw-section-row">
                                <div class="dw-section-label">{{ $title }}</div>
                                @if (!empty($items) && $data['itemsUrl'])
                                    <button type="button" class="dw-more-btn" data-dash-items="{{ $data['itemsUrl'] }}"
                                        data-category="{{ $category }}" data-klinik="{{ $c['klinik']->id }}">
                                        Lihat semua <i data-feather="list"></i>
                                    </button>
                                @endif
                            </div>
                            @forelse ($items as $item)
                                <x-dash.bar class="mb-2" :label="$item->name" :value="Fmt::rpShort($item->total)" :pct="$item->total / $max * 100"
                                    :color="$c['klinik']->color" :sub="Fmt::num($item->qty, floor($item->qty) == $item->qty ? 0 : 1) . $unit" />
                            @empty
                                <p class="dw-note mt-0">{{ $emptyText }}</p>
                            @endforelse
                        @endforeach
                    @endif
                </x-dash.clinic>
            @endforeach
        </div>
        <p class="dw-note">Diskon/pajak tingkat invoice dibagi proporsional ke item, jadi total kategori = total omset.</p>
    @endif
</x-dash.card>
