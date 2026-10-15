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
                <div class="dw-section-row">
                    <div class="dw-section-label">Per kategori · growth vs {{ $data['previousLabel'] }}</div>
                    <button type="button" class="dw-more-btn" data-dash-items="{{ $data['itemsUrl'] }}" data-category="tindakan">
                        Semua item <i data-feather="list"></i>
                    </button>
                </div>
                <x-dash.stack :segments="collect($data['categories'])->map(fn ($c) => [
                    'label' => $c['label'], 'value' => Fmt::rp($c['amount']), 'pct' => $c['pct'], 'color' => $c['color'],
                ])->all()" :legend="false" />
                <ul class="dw-legend">
                    @foreach ($data['categories'] as $category)
                        <li class="d-block">
                            <div class="d-flex align-items-center" style="gap: 8px;">
                                <i class="dw-dot" style="background: {{ $category['color'] }}"></i>
                                <span class="dw-legend-label">{{ $category['label'] }}</span>
                                <span class="dw-legend-value">{{ Fmt::rp($category['amount']) }}</span>
                                <span class="dw-legend-pct">{{ Fmt::pct($category['pct']) }}</span>
                                <x-dash.delta :trend="$category['trend']" class="mt-0" style="width: 64px; justify-content: flex-end;" />
                            </div>
                            @if ($category['amount'] > 0)
                                <x-dash.split :items="$category['clinics']" :bar="false" class="mt-0" style="padding-left: 17px;" />
                            @endif
                        </li>
                    @endforeach
                </ul>

                @if (count($data['clinics']) > 1)
                    <div class="dw-section-label">Komposisi per klinik</div>
                    @foreach ($data['clinics'] as $clinic)
                        <div class="mb-3">
                            <div class="dw-bar-top">
                                <span class="dw-bar-label"><i class="dw-dot" style="background: {{ $clinic['color'] }}"></i> {{ $clinic['nama'] }}</span>
                                <span class="dw-bar-value">{{ Fmt::rp($clinic['total']) }}</span>
                            </div>
                            <x-dash.stack :segments="$clinic['segments']" :legend="false" />
                        </div>
                    @endforeach
                @endif

                <div class="dw-section-label">Metode pembayaran</div>
                <div class="dw-bars">
                    @foreach ($data['methods'] as $method)
                        <div>
                            <x-dash.bar :label="$method['label']" :value="Fmt::rpShort($method['total']) . ' · ' . Fmt::pct($method['pct'])"
                                :pct="$method['pct']" :sub="Fmt::num($method['invoices']) . ' invoice'" />
                            <x-dash.split :items="$method['clinics']" :bar="false" class="mt-1" />
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-lg-7">
                <div class="row">
                    @foreach ([['Tindakan terlaris', 'tindakan', $data['topTindakan'], $maxTindakan, 'Belum ada tindakan terjual.'], ['Obat & produk terlaris', 'obat', $data['topObat'], $maxObat, 'Belum ada obat/produk terjual.']] as [$title, $category, $items, $max, $emptyText])
                        <div class="col-md-6">
                            <div class="dw-section-row">
                                <div class="dw-section-label">{{ $title }}</div>
                                @if (!empty($items))
                                    <button type="button" class="dw-more-btn" data-dash-items="{{ $data['itemsUrl'] }}" data-category="{{ $category }}">
                                        Lihat semua <i data-feather="list"></i>
                                    </button>
                                @endif
                            </div>
                            @forelse ($items as $item)
                                <div class="mb-3">
                                    <x-dash.bar :label="$item->name" :value="Fmt::rpShort($item->total)" :pct="$item->total / $max * 100"
                                        :sub="Fmt::num($item->qty, floor($item->qty) == $item->qty ? 0 : 1) . ($category === 'obat' ? ' terjual' : 'x')" />
                                    <x-dash.split :items="collect($item->clinics)->where('amount', '>', 0)->values()->all()" :bar="false" class="mt-1" />
                                </div>
                            @empty
                                <p class="dw-note mt-0">{{ $emptyText }}</p>
                            @endforelse
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <p class="dw-note">Diskon/pajak tingkat invoice dibagi proporsional ke item, jadi total kategori = total omset. Warna titik di bawah angka = klinik.</p>
    @endif
</x-dash.card>
