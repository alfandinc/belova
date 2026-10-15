@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['periodLabel'] . ' · growth vs ' . $data['previousLabel']">
    @if ($data['total'] <= 0)
        <x-dash.empty icon="user-check" title="Belum ada omset dokter pada periode ini" />
    @else
        <div class="dw-clinics">
            @foreach ($data['clinics'] as $c)
                @php($max = max(1, collect($c['rows'])->max('total')))
                <x-dash.clinic :klinik="$c['klinik']" :meta="Fmt::rp($c['total'])">
                    @forelse ($c['rows'] as $row)
                        <div class="dw-item d-block">
                            <div class="dw-bar-top mb-1">
                                <span class="dw-bar-label">{{ $row->nama }}</span>
                                <span class="dw-bar-value">{{ Fmt::rp($row->total) }}</span>
                            </div>
                            <div class="dw-bar-track"><div class="dw-bar-fill" style="width: {{ $row->total / $max * 100 }}%; background: {{ $c['klinik']->color }};"></div></div>
                            <div class="d-flex justify-content-between align-items-center mt-1" style="gap: 8px;">
                                <span class="dw-item-meta">
                                    {{ Fmt::num($row->patients) }} pasien · {{ Fmt::num($row->visits) }} kunjungan · rata-rata {{ Fmt::rpShort($row->average) }}/kunjungan
                                </span>
                                <x-dash.delta :trend="$row->trend" class="mt-0" />
                            </div>
                        </div>
                    @empty
                        <div class="dw-clinic-empty">Belum ada omset dokter pada periode ini.</div>
                    @endforelse
                    @if ($c['more'] > 0)
                        <p class="dw-note mt-1">+{{ $c['more'] }} dokter lainnya.</p>
                    @endif
                </x-dash.clinic>
            @endforeach
        </div>
    @endif
</x-dash.card>
