@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
    $maxTindakan = max(1, collect($data['topTindakan'])->max('total'));
    $maxObat = max(1, collect($data['topObat'])->max('total'));
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['periodLabel'] . ' · total ' . Fmt::rp($data['total'])">
    @if ($data['total'] <= 0)
        <x-dash.empty icon="pie-chart" title="Belum ada omset pada periode ini" />
    @else
        <div class="row">
            <div class="col-lg-5">
                <div class="dw-section-label">Per kategori · growth vs {{ $data['previousLabel'] }}</div>
                <x-dash.stack :segments="collect($data['categories'])->map(fn ($c) => [
                    'label' => $c['label'], 'value' => Fmt::rp($c['amount']), 'pct' => $c['pct'], 'color' => $c['color'],
                ])->all()" :legend="false" />
                <ul class="dw-legend">
                    @foreach ($data['categories'] as $category)
                        <li>
                            <i class="dw-dot" style="background: {{ $category['color'] }}"></i>
                            <span class="dw-legend-label">{{ $category['label'] }}</span>
                            <span class="dw-legend-value">{{ Fmt::rp($category['amount']) }}</span>
                            <span class="dw-legend-pct">{{ Fmt::pct($category['pct']) }}</span>
                            <x-dash.delta :trend="$category['trend']" class="mt-0" style="width: 64px; justify-content: flex-end;" />
                        </li>
                    @endforeach
                </ul>

                @if (count($data['clinics']) > 1)
                    <div class="dw-section-label">Komposisi per klinik</div>
                    @foreach ($data['clinics'] as $clinic)
                        <div class="mb-3">
                            <div class="dw-bar-top">
                                <span class="dw-bar-label">{{ $clinic['nama'] }}</span>
                                <span class="dw-bar-value">{{ Fmt::rp($clinic['total']) }}</span>
                            </div>
                            <x-dash.stack :segments="$clinic['segments']" :legend="false" />
                        </div>
                    @endforeach
                @endif

                <div class="dw-section-label">Metode pembayaran</div>
                <div class="dw-bars">
                    @foreach ($data['methods'] as $method)
                        <x-dash.bar :label="$method['label']" :value="Fmt::rpShort($method['total']) . ' · ' . Fmt::pct($method['pct'])"
                            :pct="$method['pct']" :sub="Fmt::num($method['invoices']) . ' invoice'" />
                    @endforeach
                </div>
            </div>

            <div class="col-lg-7">
                <div class="row">
                    <div class="col-md-6">
                        <div class="dw-section-label">Tindakan terlaris</div>
                        @forelse ($data['topTindakan'] as $item)
                            <x-dash.bar class="mb-3" :label="$item->name" :value="Fmt::rpShort($item->total)"
                                :pct="$item->total / $maxTindakan * 100" :sub="Fmt::num($item->qty) . 'x'" />
                        @empty
                            <p class="dw-note mt-0">Belum ada tindakan terjual.</p>
                        @endforelse
                    </div>
                    <div class="col-md-6">
                        <div class="dw-section-label">Obat & produk terlaris</div>
                        @forelse ($data['topObat'] as $item)
                            <x-dash.bar class="mb-3" :label="$item->name" :value="Fmt::rpShort($item->total)"
                                :pct="$item->total / $maxObat * 100" :sub="Fmt::num($item->qty, floor($item->qty) == $item->qty ? 0 : 1) . ' terjual'" />
                        @empty
                            <p class="dw-note mt-0">Belum ada obat/produk terjual.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <p class="dw-note">Diskon/pajak tingkat invoice dibagi proporsional ke item, jadi total kategori = total omset.</p>
    @endif
</x-dash.card>
