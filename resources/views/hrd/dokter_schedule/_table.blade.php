@php
    $today = now()->toDateString();
    $allDokters = collect($doktersByKlinik)->flatten(1);
    // Preset jam: dari shift default dokter + jadwal minggu ini
    $presets = $allDokters->pluck('schedule_default')->merge(collect($schedules)->values())
        ->filter()->unique()->sort()->values();
@endphp
<input type="hidden" id="week-start" value="{{ $startOfWeek->toDateString() }}">
<script type="application/json" id="time-presets">{!! json_encode($presets, JSON_HEX_TAG) !!}</script>

<div class="sched-scroll" id="sched-scroll">
    <table class="table table-bordered sched-table mb-0" id="sched-table" data-total="{{ $allDokters->count() }}">
        <thead>
            <tr>
                <th class="sched-name-col">Dokter</th>
                @foreach($dates as $i => $date)
                    @php $d = \Carbon\Carbon::parse($date)->locale('id'); @endphp
                    <th class="sched-day-head {{ $date === $today ? 'is-today' : '' }} {{ $d->isWeekend() ? 'is-weekend' : '' }}"
                        data-col="{{ $i }}" title="Klik untuk memilih seluruh kolom">
                        <div>{{ $d->isoFormat('dddd') }}</div>
                        <small>{{ $d->isoFormat('D MMM') }}</small>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($doktersByKlinik as $klinikName => $dokters)
                <tr class="sched-division" data-division="{{ $klinikName }}" data-klinik-id="{{ $dokters->first()->klinik_id }}">
                    <td colspan="{{ count($dates) + 1 }}">
                        <i class="fa fa-chevron-down sched-caret"></i>
                        {{ $klinikName }}
                        <span class="badge badge-light ml-1">{{ count($dokters) }}</span>
                    </td>
                </tr>
                @foreach($dokters as $dokter)
                    <tr class="employee-row" data-division="{{ $klinikName }}" data-name="{{ strtolower($dokter->schedule_name) }}"
                        data-color="{{ $dokter->schedule_color }}" data-default="{{ $dokter->schedule_default }}">
                        <td class="sched-name-col sched-emp" title="Klik untuk memilih satu minggu">
                            <span class="doctor-dot" style="background: {{ $dokter->schedule_color }}"></span>{{ $dokter->schedule_name }}
                            <small class="d-block text-muted">
                                {{ $dokter->schedule_default ? 'Default ' . str_replace('-', '–', $dokter->schedule_default) : 'Belum ada jam default' }}
                            </small>
                        </td>
                        @foreach($dates as $i => $date)
                            @php $val = $schedules[$dokter->id . '_' . $date] ?? ''; @endphp
                            <td class="sc {{ $date === $today ? 'is-today' : '' }}" data-col="{{ $i }}"
                                data-emp="{{ $dokter->id }}" data-date="{{ $date }}"
                                data-val="{{ $val }}" data-orig="{{ $val }}"></td>
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="sched-name-col text-muted small">Dokter praktek</td>
                @foreach($dates as $i => $date)
                    <td class="sched-count text-center small" data-col="{{ $i }}">-</td>
                @endforeach
            </tr>
        </tfoot>
    </table>
</div>

<!-- Shift default dokter (collapsible) -->
<div class="card shadow-sm mt-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
        <a href="#shift-mgmt-body" data-toggle="collapse" class="font-weight-bold text-dark">
            <i class="fa fa-cog mr-1"></i> Jam Default & Warna Dokter
        </a>
        <button type="button" class="btn btn-sm btn-primary" id="btn-add-shift"><i class="fa fa-plus"></i> Tambah</button>
    </div>
    <div id="shift-mgmt-body" class="collapse">
        <div class="card-body p-2">
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr><th>Dokter</th><th>Klinik</th><th>Jam Default</th><th style="width:90px">Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach($allDokters as $dokter)
                        <tr>
                            <td><span class="doctor-dot" style="background: {{ $dokter->schedule_color }}"></span>{{ $dokter->schedule_name }}</td>
                            <td>{{ $dokter->klinik->nama ?? '-' }}</td>
                            <td>{{ $dokter->schedule_default ? str_replace('-', ' – ', $dokter->schedule_default) : '-' }}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary shift-edit-btn"
                                        data-shift-id="{{ $dokter->schedule_shift_id }}"
                                        data-dokter-id="{{ $dokter->id }}"
                                        data-default="{{ $dokter->schedule_default }}"
                                        data-color="{{ $dokter->schedule_color }}">{{ $dokter->schedule_shift_id ? 'Edit' : 'Atur' }}</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
