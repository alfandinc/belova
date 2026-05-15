@if ($visitations->isEmpty())
    <div class="text-center text-muted">Tidak ada data riwayat.</div>
@else
    @foreach ($visitations as $visit)
        @php
            $resepGroup = $resepsByVisitation->get($visit->id, collect());
            $tindakans = $tindakanByVisitation->get($visit->id, collect());
            $tanggalRaw = $visit->tanggal_visitation ?? null;
            $tanggal = $tanggalRaw ? \Carbon\Carbon::parse($tanggalRaw)->translatedFormat('d F Y') : '-';
            $dokterName = $visit->dokter && $visit->dokter->user ? $visit->dokter->user->name : ($visit->dokter->nama ?? '-');
            $nonRacikans = $resepGroup->whereNull('racikan_ke');
            $racikans = $resepGroup->whereNotNull('racikan_ke')->groupBy('racikan_ke');
        @endphp

        <div class="card mb-3">
            <div class="card-header">
                <strong>Visitation: {{ $tanggal }}</strong>
                <span class="text-muted">| Dokter: {{ $dokterName }}</span>
            </div>
            <div class="card-body">
                <h6>Riwayat Obat</h6>
                @if ($nonRacikans->count() || $racikans->count())
                    <ul class="mb-3 pl-3">
                        @foreach ($nonRacikans as $item)
                            <li>{{ $item->obat->nama ?? 'Obat dihapus' }}</li>
                        @endforeach

                        @foreach ($racikans as $ke => $items)
                            @php
                                $racikanLabel = $racikanPaketNames[$visit->id][$ke] ?? ('Racikan #' . $ke);
                                $obatNames = $items
                                    ->map(fn ($item) => $item->obat->nama ?? 'Obat dihapus')
                                    ->values()
                                    ->implode(', ');
                            @endphp
                            <li><strong>{{ $racikanLabel }}</strong>: {{ $obatNames }}</li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-muted mb-3">Tidak ada riwayat obat</div>
                @endif

                <h6 class="mt-2">Riwayat Tindakan</h6>
                @if ($tindakans->count())
                    <ul class="mb-0 pl-3">
                        @foreach ($tindakans as $tindakan)
                            <li>{{ $tindakan->tindakan->nama ?? '-' }}</li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-muted">Tidak ada tindakan</div>
                @endif
            </div>
        </div>
    @endforeach
@endif
