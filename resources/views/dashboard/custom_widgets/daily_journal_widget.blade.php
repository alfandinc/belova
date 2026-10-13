@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
    $statusPill = [
        'todo' => ['neutral', 'Belum mulai'],
        'in_progress' => ['info', 'Dikerjakan'],
        'done' => ['good', 'Selesai'],
        'skipped' => ['neutral', 'Dilewati'],
    ];
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['periodLabel'] . ($data['mode'] === 'team' ? ' · tim' : ' · task saya')"
    :href="$data['journalUrl']" href-label="Buka Daily Journal">
    <x-slot:actions>
        @if ($data['canTeam'])
            <div class="dw-seg" role="radiogroup" aria-label="Tampilan task">
                <label><input type="radio" name="daily_journal_mode" value="my" data-dashboard-filter-input @checked($data['mode'] === 'my')><span>Saya</span></label>
                <label><input type="radio" name="daily_journal_mode" value="team" data-dashboard-filter-input @checked($data['mode'] === 'team')><span>Tim</span></label>
            </div>
        @endif
    </x-slot:actions>

    @if ($data['total'] === 0)
        <x-dash.empty icon="book-open" title="Belum ada task pada periode ini" text="Catat rencana kerja di Daily Journal.">
            <a href="{{ route('daily-journal.index') }}" class="dw-link d-inline-flex mt-2"><i data-feather="plus"></i> Tambah task</a>
        </x-dash.empty>
    @else
        <div class="dw-stats">
            <x-dash.stat label="Progres" :value="Fmt::pct($data['progress'])" :meta="Fmt::num($data['done']) . ' dari ' . Fmt::num($data['total']) . ' task selesai'" />
            <x-dash.stat label="Lewat deadline" :value="Fmt::num($data['overdue'])" :tone="$data['overdue'] > 0 ? 'critical' : null"
                :meta="$data['overdue'] > 0 ? 'segera selesaikan' : 'aman'" />
        </div>

        <div class="dw-section-label">Status task</div>
        <x-dash.stack :segments="$data['segments']" :legend="false" />
        <ul class="dw-legend is-inline">
            @foreach ($data['segments'] as $segment)
                <li><i class="dw-dot" style="background: {{ $segment['color'] }}"></i><span class="dw-legend-label">{{ $segment['label'] }}</span><span class="dw-legend-value">{{ $segment['value'] }}</span></li>
            @endforeach
        </ul>

        <div class="dw-section-label">Task terdekat</div>
        <ul class="dw-list">
            @foreach ($data['tasks'] as $task)
                <li class="dw-item">
                    <span class="dw-item-lead">
                        {{ $task->date?->translatedFormat('d') }}
                        <small>{{ $task->date?->translatedFormat('M') }}</small>
                    </span>
                    <span class="dw-item-main">
                        <span class="dw-item-title d-block {{ in_array($task->status, ['done', 'skipped'], true) ? 'text-muted' : '' }}">{{ $task->title }}</span>
                        <span class="dw-item-meta d-block">
                            @if ($task->owner){{ $task->owner }} · @endif
                            @if ($task->time){{ $task->time }} · @endif
                            @if ($task->deadline)
                                <span class="{{ $task->overdue ? 'text-danger font-weight-bold' : '' }}">Deadline {{ $task->deadline->translatedFormat('d M') }}{{ $task->overdue ? ' (lewat)' : '' }}</span>
                            @endif
                            @if ($task->from) · dari {{ $task->from }} @endif
                        </span>
                    </span>
                    <x-dash.pill :tone="$statusPill[$task->status][0] ?? 'neutral'">{{ $statusPill[$task->status][1] ?? $task->status }}</x-dash.pill>
                </li>
            @endforeach
        </ul>
        @if ($data['total'] > count($data['tasks']))
            <p class="dw-note">Menampilkan {{ count($data['tasks']) }} dari {{ Fmt::num($data['total']) }} task.</p>
        @endif
    @endif
</x-dash.card>
