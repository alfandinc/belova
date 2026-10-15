{{-- Sumber Omset > "Lihat semua": every sold item of one category, of one clinic ($klinik) or with omset and qty per clinic (DashboardController@omsetItems) --}}
@php
    use App\Support\Dashboard\Fmt;
    $qty = fn ($v) => Fmt::num($v, floor($v) == $v ? 0 : 1);
@endphp

@if ($klinik)
    <x-dash.clinic :klinik="$klinik" :meta="$periodLabel . ' · ' . Fmt::num($items->count()) . ' item · total ' . Fmt::rp($total)" class="dw-clinic-banner mb-3"></x-dash.clinic>
@else
    <div class="dw-note mt-0 mb-2">
        Semua klinik · {{ $periodLabel }} · {{ Fmt::num($items->count()) }} item · total {{ Fmt::rp($total) }}
    </div>
@endif

@if ($items->isEmpty())
    <x-dash.empty icon="inbox" title="Belum ada penjualan pada periode ini" />
@else
    <div class="dw-table-wrap">
        <table class="dw-grid-table" data-dash-items-table style="width: 100%;">
            <thead>
                <tr>
                    <th class="text-right">#</th>
                    <th>Item</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Omset</th>
                    @foreach ($clinics as $klinik)
                        <th class="text-right" title="{{ $klinik->nama }}">
                            <i class="dw-dot" style="background: {{ $klinik->color }}"></i> {{ $klinik->short }}
                        </th>
                    @endforeach
                    <th class="text-right">Porsi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $i => $item)
                    <tr>
                        <td class="text-right" data-order="{{ $i + 1 }}">{{ $i + 1 }}</td>
                        <td><div class="dw-cell-title">{{ $item->name }}</div></td>
                        <td class="text-right" data-order="{{ $item->qty }}">{{ $qty($item->qty) }}</td>
                        <td class="text-right" data-order="{{ $item->total }}"><div class="dw-cell-num">{{ Fmt::rp($item->total) }}</div></td>
                        @foreach ($clinics as $klinik)
                            @php($amount = (float) ($item->byKlinik[(string) $klinik->id] ?? 0))
                            <td class="text-right" data-order="{{ $amount }}">
                                @if ($amount > 0)
                                    <div class="dw-cell-num">{{ Fmt::rp($amount) }}</div>
                                    <div class="dw-stat-meta mt-0">{{ $qty($item->qtyByKlinik[(string) $klinik->id] ?? 0) }}x</div>
                                @else
                                    <span class="dw-stat-meta">–</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="text-right" data-order="{{ $item->total }}">{{ Fmt::pct(Fmt::share($item->total, $total), 1) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
