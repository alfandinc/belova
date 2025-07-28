@foreach ($reseps as $visitationId => $group)
    @php
        $visitation = $group->first()->visitation ?? null;
        $tanggalRaw = $visitation->tanggal_visitation ?? null;
        $tanggal = $tanggalRaw ? \Carbon\Carbon::parse($tanggalRaw)->translatedFormat('d F Y') : '-';
        $dokterName = $visitation && $visitation->dokter && $visitation->dokter->user ? $visitation->dokter->user->name : '-';
        $nonRacikans = $group->whereNull('racikan_ke');
        $racikans = $group->whereNotNull('racikan_ke')->groupBy('racikan_ke');
    @endphp

    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-3">🗓️ Tanggal Kunjungan: {{ $tanggal }} <span class="ml-2">👨‍⚕️ Dokter: <strong>{{ $dokterName }}</strong></span></h5>
            <button class="btn btn-primary btn-sm btn-copy-resep" data-visitation-id="{{ $visitationId }}" data-source="dokter">
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
                        <th>Dibuat</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($nonRacikans as $item)
                        <tr>
                            <td>
                                @if ($item->obat)
                                    {{ $item->obat->nama }}
                                    @if (isset($item->obat->status_aktif) && $item->obat->status_aktif == 0)
                                        <span class="badge badge-warning">Non Aktif</span>
                                    @endif
                                @else
                                    <span class="text-danger">Obat dihapus</span>
                                @endif
                            </td>
                            <td>{{ $item->jumlah }}</td>
                            <td>{{ $item->aturan_pakai }}</td>
                            <td>{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->translatedFormat('d/m/Y H:i') : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Racikan Table --}}
        @foreach ($racikans as $ke => $items)
            <h6 class="mt-4">Obat Racikan #{{ $ke }}</h6>
            <p><strong>Wadah:</strong> {{ $items->first()->wadah->nama ?? '-' }} | <strong>Jumlah Bungkus:</strong> {{ $items->first()->bungkus ?? '-' }}</p>
            <p><strong>Aturan Pakai:</strong> {{ $items->first()->aturan_pakai ?? '-' }}</p>

            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Nama Obat</th>
                        <th>Dosis</th>
                        <th>Dibuat</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $racik)
                        <tr>
                            <td>
                                @if ($racik->obat)
                                    {{ $racik->obat->nama }}
                                    @if (isset($racik->obat->status_aktif) && $racik->obat->status_aktif == 0)
                                        <span class="badge badge-warning">Non Aktif</span>
                                    @endif
                                @else
                                    <span class="text-danger">Obat dihapus</span>
                                @endif
                            </td>
                            <td>{{ $racik->dosis ?? '-' }}</td>
                            <td>{{ $racik->created_at ? \Carbon\Carbon::parse($racik->created_at)->translatedFormat('d/m/Y H:i') : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    </div>
    <hr class="my-4" style="border-top: 1px solid;">
@endforeach
