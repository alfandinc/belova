@foreach ($reseps as $visitationId => $group)
    @php
        $tanggal = $group->first()->visitation->tanggal_visitation ?? '-';
        $nonRacikans = $group->whereNull('racikan_ke');
        $racikans = $group->whereNotNull('racikan_ke')->groupBy('racikan_ke');
    @endphp

    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-3">🗓️ Tanggal Kunjungan: {{ $tanggal }}</h5>
            <button class="btn btn-primary btn-sm btn-copy-resep" data-visitation-id="{{ $visitationId }}" data-source="farmasi">
                <i class="fas fa-copy"></i> Salin Resep
            </button>
        </div>

        {{-- Non-Racikan Table --}}
        @if ($nonRacikans->count())
            <h6>Obat Non-Racikan</h6>
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Nama Obat</th>
                        <th>Jumlah</th>
                        <th>Aturan Pakai</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($nonRacikans as $item)
                        <tr>
                            <td>{{ $item->obat->nama ?? '-' }}</td>
                            <td>{{ $item->jumlah }}</td>
                            <td>{{ $item->aturan_pakai }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Racikan Table --}}
        @foreach ($racikans as $ke => $items)
            <h6 class="mt-4">Obat Racikan #{{ $ke }}</h6>
            <p><strong>Wadah:</strong> {{ $items->first()->wadah ?? '-' }} | <strong>Bungkus:</strong> {{ $items->first()->bungkus ?? '-' }}</p>
            <p><strong>Aturan Pakai:</strong> {{ $items->first()->aturan_pakai ?? '-' }}</p>

            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Nama Obat</th>
                        <th>Dosis</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $racik)
                        <tr>
                            <td>{{ $racik->obat->nama ?? '-' }}</td>
                            <td>{{ $racik->dosis ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    </div>
@endforeach
