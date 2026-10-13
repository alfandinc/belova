@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
    $max = max(1, collect($data['rows'])->max('total'));
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['periodLabel'] . ' · growth vs ' . $data['previousLabel']">
    @if (empty($data['rows']))
        <x-dash.empty icon="user-check" title="Belum ada omset dokter pada periode ini" />
    @else
        <ul class="dw-list">
            @foreach ($data['rows'] as $row)
                <li class="dw-item d-block">
                    <div class="dw-bar-top mb-1">
                        <span class="dw-bar-label">{{ $row->nama }}</span>
                        <span class="dw-bar-value">{{ Fmt::rp($row->total) }}</span>
                    </div>
                    <div class="dw-bar-track"><div class="dw-bar-fill" style="width: {{ $row->total / $max * 100 }}%"></div></div>
                    <div class="d-flex justify-content-between align-items-center mt-1" style="gap: 8px;">
                        <span class="dw-item-meta">
                            {{ $row->klinik ? $row->klinik . ' · ' : '' }}{{ Fmt::num($row->patients) }} pasien · {{ Fmt::num($row->visits) }} kunjungan · rata-rata {{ Fmt::rpShort($row->average) }}/kunjungan
                        </span>
                        <x-dash.delta :trend="$row->trend" class="mt-0" />
                    </div>
                </li>
            @endforeach
        </ul>
        @if ($data['more'] > 0)
            <p class="dw-note">+{{ $data['more'] }} dokter lainnya.</p>
        @endif
    @endif
</x-dash.card>
