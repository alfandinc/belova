{{--
    Riwayat resep (dokter or farmasi) of a patient, one compact card per visit; loaded into #riwayatModalContent.
    Page 1 has the toolbar (count + search); later pages ("Muat lebih banyak") only append cards.
    Styles + the search / load-more handlers live in erm.partials.modal-resephistory.
--}}
@php
    // when the row was written (date + time)
    $dibuat = fn ($resep) => $resep->created_at ? \Carbon\Carbon::parse($resep->created_at)->format('d/m/Y H:i') : '-';
    $obatLabel = function ($resep) {
        if (!$resep->obat) {
            return ['nama' => 'Obat dihapus', 'off' => true];
        }
        return ['nama' => $resep->obat->nama, 'off' => (int) $resep->obat->status_aktif === 0];
    };
@endphp

@if ($page === 1)
    <div class="rr-toolbar">
        <span class="rr-count">{{ $totalVisits }} kunjungan dengan resep {{ $source }}</span>
        @if ($totalVisits)
            <input type="search" class="form-control form-control-sm rr-search" placeholder="Cari obat di riwayat yang tampil..." aria-label="Cari obat di riwayat">
        @endif
    </div>
    @if ($reseps->isEmpty())
        <div class="rr-empty">Belum ada riwayat resep {{ $source }} untuk pasien ini.</div>
    @endif
@endif

@foreach ($reseps as $visitationId => $group)
    @php
        $visitation = optional($group->first())->visitation;
        $tanggal = $visitation && $visitation->tanggal_visitation ? \Carbon\Carbon::parse($visitation->tanggal_visitation) : null;
        $dokterName = optional(optional(optional($visitation)->dokter)->user)->name;
        $isCurrent = $currentVisitationId && (string) $visitationId === (string) $currentVisitationId;
        $nonRacikans = $group->whereNull('racikan_ke');
        $racikans = $group->whereNotNull('racikan_ke')->groupBy('racikan_ke');
    @endphp
    <div class="rr-visit{{ $isCurrent ? ' is-current' : '' }}">
        <div class="rr-head">
            {{-- tanggal and dokter, same shape: icon tile, main text, small second line --}}
            <div class="rr-when">
                <div class="rr-meta rr-meta-date">
                    <span class="rr-ico"><i class="far fa-calendar-alt"></i></span>
                    <div class="rr-meta-text">
                        <div class="rr-strong">{{ $tanggal ? $tanggal->copy()->locale('id')->translatedFormat('l, j F Y') : '-' }}</div>
                        <div class="rr-ago">{{ $tanggal ? $tanggal->copy()->locale('id')->diffForHumans(['parts' => 1]) : 'Tanggal tidak diketahui' }}</div>
                    </div>
                </div>
                <div class="rr-meta rr-meta-dokter">
                    <span class="rr-ico"><i class="fas fa-user-md"></i></span>
                    <div class="rr-meta-text">
                        <div class="rr-strong" title="{{ $dokterName }}">{{ $dokterName ?: 'Dokter tidak diketahui' }}</div>
                        <div class="rr-ago">{{ optional(optional(optional($visitation)->dokter)->spesialisasi)->nama ?: 'Dokter' }}</div>
                    </div>
                </div>
            </div>
            @if ($isCurrent && $source === ($target ?? $source))
                {{-- copying the open visit into itself would only duplicate it --}}
                <span class="rr-badge">Kunjungan saat ini</span>
            @else
                @if ($isCurrent)
                    <span class="rr-badge">Kunjungan saat ini</span>
                @endif
                <button type="button" class="btn btn-sm rr-copy btn-copy-resep" data-visitation-id="{{ $visitationId }}" data-source="{{ $source }}">
                    <i class="fas fa-copy mr-1"></i>Salin
                </button>
            @endif
        </div>

        {{-- Non racikan (blue) and racikan (purple) tables with the same columns (table-layout: fixed + colgroup),
             so Jumlah / Aturan Pakai / Dibuat line up across both and across cards --}}
        @if ($nonRacikans->isNotEmpty())
            <table class="rr-table rr-table-nonracikan">
                <colgroup><col class="rr-col-obat"><col class="rr-col-jml"><col class="rr-col-aturan"><col class="rr-col-dibuat"></colgroup>
                <thead>
                    <tr><th><i class="fas fa-pills"></i>Obat Non Racikan</th><th>Jumlah</th><th>Aturan Pakai</th><th>Dibuat</th></tr>
                </thead>
                <tbody>
                    @foreach ($nonRacikans as $item)
                        @php $o = $obatLabel($item); @endphp
                        <tr>
                            <td><span class="{{ $o['off'] ? 'is-off' : '' }}">{{ $o['nama'] }}</span>@if ($o['off'] && $item->obat) <span class="rr-flag">nonaktif</span>@endif</td>
                            <td>{{ $item->jumlah ?? '-' }}</td>
                            <td>{{ $item->aturan_pakai ?: '-' }}</td>
                            <td class="rr-dibuat">{{ $dibuat($item) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($racikans->isNotEmpty())
            <table class="rr-table rr-table-racikan">
                <colgroup><col class="rr-col-obat"><col class="rr-col-jml"><col class="rr-col-aturan"><col class="rr-col-dibuat"></colgroup>
                <thead>
                    <tr><th><i class="fas fa-mortar-pestle"></i>Obat Racikan</th><th>Jumlah</th><th>Aturan Pakai</th><th>Dibuat</th></tr>
                </thead>
                <tbody>
                    @foreach ($racikans as $ke => $items)
                        @php
                            $first = $items->first();
                            $paketName = $racikanPaketNames[$visitationId][$ke] ?? null;
                        @endphp
                        <tr class="rr-racikan">
                            <td>
                                {{ $paketName ?: 'Racikan ' . $ke }}
                                @if ($first->wadah)<div class="rr-sub">Wadah: {{ $first->wadah->nama }}</div>@endif
                            </td>
                            <td>{{ $first->bungkus ?? '-' }} bungkus</td>
                            <td>{{ $first->aturan_pakai ?: '-' }}</td>
                            <td class="rr-dibuat">{{ $dibuat($first) }}</td>
                        </tr>
                        {{-- its isi: a sub-table with the same columns, Dosis right under Jumlah --}}
                        <tr class="rr-isi-row">
                            <td colspan="4">
                                <table class="rr-isi-table">
                                    <colgroup><col class="rr-col-obat"><col class="rr-col-jml"><col class="rr-col-aturan"><col class="rr-col-dibuat"></colgroup>
                                    <thead>
                                        <tr><th>Isi Racikan</th><th>Dosis</th><th></th><th></th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($items as $racik)
                                            @php $o = $obatLabel($racik); @endphp
                                            <tr>
                                                <td><span class="{{ $o['off'] ? 'is-off' : '' }}">{{ $o['nama'] }}</span>@if ($o['off'] && $racik->obat) <span class="rr-flag">nonaktif</span>@endif</td>
                                                <td>{{ $racik->dosis ?? '-' }}</td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endforeach

@if ($nextUrl)
    <button type="button" class="btn btn-sm btn-block rr-more" data-url="{{ $nextUrl }}">Muat lebih banyak</button>
@endif
