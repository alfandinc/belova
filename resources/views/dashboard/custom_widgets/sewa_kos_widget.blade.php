@php
    $data = $widget->data;
    $waNumber = fn ($phone) => preg_replace('/^0/', '62', preg_replace('/\D/', '', (string) $phone));
@endphp

<x-dash.card :widget="$widget" :subtitle="'Okupansi ' . $data['occupancy'] . '% · Piutang Rp ' . number_format($data['receivable'], 0, ',', '.') . ' (' . $data['unpaidCount'] . ' transaksi)'">
    @if (!empty($data['overdue']))
        <ul class="dw-list mb-2">
            @foreach ($data['overdue'] as $item)
                <li class="dw-item">
                    <span class="dw-avatar">{{ \Illuminate\Support\Str::limit($item->room ?? '-', 3, '') }}</span>
                    <span class="dw-item-main">
                        <span class="dw-item-title d-block">{{ $item->renter ?? '-' }}</span>
                        <span class="dw-item-meta d-block">
                            Kamar {{ $item->room ?? '-' }} · habis {{ $item->end->translatedFormat('d M') }}, belum perpanjang
                            @if ($item->phone)
                                · <a href="https://wa.me/{{ $waNumber($item->phone) }}" target="_blank" rel="noopener">WhatsApp</a>
                            @endif
                        </span>
                    </span>
                    <x-dash.pill tone="critical" icon="alert-octagon">Lewat {{ $item->days }} hari</x-dash.pill>
                </li>
            @endforeach
        </ul>
    @endif
    @if (empty($data['expiring']) && empty($data['overdue']))
        <x-dash.empty icon="key" title="Tidak ada sewa yang habis {{ $data['daysAhead'] }} hari ke depan" />
    @elseif (!empty($data['expiring']))
        <ul class="dw-list">
            @foreach ($data['expiring'] as $item)
                <li class="dw-item">
                    <span class="dw-avatar">{{ \Illuminate\Support\Str::limit($item->room ?? '-', 3, '') }}</span>
                    <span class="dw-item-main">
                        <span class="dw-item-title d-block">{{ $item->renter ?? '-' }}</span>
                        <span class="dw-item-meta d-block">
                            Kamar {{ $item->room ?? '-' }} · selesai {{ $item->end->translatedFormat('d M') }}
                            @if ($item->phone)
                                · <a href="https://wa.me/{{ $waNumber($item->phone) }}" target="_blank" rel="noopener">WhatsApp</a>
                            @endif
                        </span>
                    </span>
                    @if ($item->extended)
                        <x-dash.pill tone="good" icon="check">Diperpanjang</x-dash.pill>
                    @elseif ($item->days <= 1)
                        <x-dash.pill tone="critical" icon="alert-triangle">{{ $item->days === 0 ? 'Hari ini' : 'Besok' }}</x-dash.pill>
                    @else
                        <x-dash.pill tone="warning" icon="calendar">{{ $item->days }} hari lagi</x-dash.pill>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="text-right mt-2"><a href="{{ route('bcl.rooms') }}" class="small">Buka Daftar Kamar &rarr;</a></div>
    @endif
</x-dash.card>
