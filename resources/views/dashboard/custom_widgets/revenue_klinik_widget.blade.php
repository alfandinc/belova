@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
    $t = $data['total'];
@endphp

<x-dash.card :widget="$widget" :subtitle="'Per ' . $data['refLabel'] . ' · garis tipis pada bar = posisi target seharusnya hari ini'">
    <div class="dw-table-wrap">
        <table class="dw-grid-table">
            <thead>
                <tr>
                    <th>Klinik</th>
                    <th class="text-right">Omset {{ $data['monthLabel'] }} (MTD)</th>
                    <th>Target {{ $data['monthLabel'] }}</th>
                    <th class="text-right">Omset YTD</th>
                    <th>Target {{ $data['ytdTargetLabel'] }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['rows'] as $row)
                    <tr>
                        <td>
                            <div class="dw-cell-title"><i class="dw-dot" style="background: {{ $row->color }}"></i> {{ $row->nama }}</div>
                            <div class="dw-stat-meta mt-0">{{ Fmt::pct($row->share) }} omset bulan ini</div>
                        </td>
                        <td class="text-right">
                            <div class="dw-cell-num">{{ Fmt::rp($row->mtd) }}</div>
                            <x-dash.delta :trend="$row->mom" label="vs bln lalu" />
                        </td>
                        <td><x-dash.meter :pct="$row->monthPct" :pace="$data['pace']" /></td>
                        <td class="text-right">
                            <div class="dw-cell-num">{{ Fmt::rp($row->ytd) }}</div>
                            <x-dash.delta :trend="$row->yoy" label="vs thn lalu" />
                        </td>
                        <td><x-dash.meter :pct="$row->ytdPct" /></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td><div class="dw-cell-title">Total</div></td>
                    <td class="text-right">
                        <div class="dw-cell-num">{{ Fmt::rp($t->mtd) }}</div>
                        <x-dash.delta :trend="$t->mom" label="vs bln lalu" />
                    </td>
                    <td><x-dash.meter :pct="$t->monthPct" :pace="$data['pace']" /></td>
                    <td class="text-right">
                        <div class="dw-cell-num">{{ Fmt::rp($t->ytd) }}</div>
                        <x-dash.delta :trend="$t->yoy" label="vs thn lalu" />
                    </td>
                    <td><x-dash.meter :pct="$t->ytdPct" /></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <p class="dw-note">MTD dibanding tanggal yang sama bulan lalu; YTD dibanding tanggal yang sama tahun lalu. Omset = invoice lunas/sebagian berdasarkan tanggal bayar.</p>
</x-dash.card>
