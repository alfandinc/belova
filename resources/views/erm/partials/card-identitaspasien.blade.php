@php
    $pasien = $visitation->pasien;

    // Member status + fast access
    $statusConfig = [
        'VIP' => ['class' => 'pc-chip-vip', 'icon' => 'fas fa-crown'],
        'Familia' => ['class' => 'pc-chip-familia', 'icon' => 'fas fa-users'],
        'Black Card' => ['class' => 'pc-chip-black', 'icon' => 'fas fa-credit-card'],
        'Regular' => ['class' => 'pc-chip-muted', 'icon' => 'fas fa-user'],
    ];
    $status = $pasien->status_pasien ?? 'Regular';
    $config = $statusConfig[$status] ?? $statusConfig['Regular'];
    $akses = $pasien->status_akses ?? 'normal';

    $isMale = ($pasien->gender ?? null) === 'Laki-laki';
    $isFemale = ($pasien->gender ?? null) === 'Perempuan';

    $parseDate = function ($value) {
        if (empty($value) || $value === '-') {
            return null;
        }
        try {
            return $value instanceof \DateTimeInterface ? \Carbon\Carbon::instance($value) : \Carbon\Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    };
    $lv = $parseDate($lastVisitDate ?? null);
    $ll = $parseDate($lastLabVisitDate ?? null);
    $tanggalLahir = $parseDate($pasien->tanggal_lahir ?? null);
    $isBirthday = $tanggalLahir ? $tanggalLahir->isBirthday() : false;
    // Relative day count ("hari ini", "12 hari lalu"); diffForHumans says "2 bulan" for anything from 45 to 75 days
    $relativeDays = function (?\Carbon\Carbon $date) {
        if (!$date) {
            return null;
        }
        $days = (int) $date->copy()->startOfDay()->diffInDays(now()->startOfDay(), false);

        return match (true) {
            $days <= 0 => 'hari ini',
            $days === 1 => 'kemarin',
            $days < 60 => $days . ' hari lalu',
            default => $date->diffForHumans(),
        };
    };

    // Alamat + desa / kecamatan / kabupaten / provinsi, skipping parts already written in alamat
    $fullAddress = $pasien->full_alamat ?? '-';
    // Shown: the wilayah (desa, kecamatan, kabupaten, provinsi); the typed street only when there is no wilayah.
    // The full address (street included) is the tooltip.
    $wilayahParts = collect([
        optional($pasien->village)->name,
        optional(optional($pasien->village)->district)->name,
        optional(optional(optional($pasien->village)->district)->regency)->name,
        optional(optional(optional(optional($pasien->village)->district)->regency)->province)->name,
    ])->filter()->map(fn ($name) => \Illuminate\Support\Str::title(mb_strtolower($name)));
    $alamatJalan = trim(preg_replace('/\s*,\s*/', ', ', trim((string) ($pasien->alamat ?? ''), " ,")));
    $shortAddress = $wilayahParts->isNotEmpty()
        ? $wilayahParts->implode(', ')
        : ($alamatJalan !== '' ? \Illuminate\Support\Str::limit($alamatJalan, 60) : '-');

    $alergiUnverified = isset($alergiList) && count($alergiList) === 1 && empty($alergiList[0]->zataktif_id);
    $hasAlergi = $alergiUnverified || (isset($alergiNames) && count($alergiNames) > 0) || !empty($alergikatakunci);
    // Kata kunci next to the chips: hidden when it only repeats a chip, a small note when it adds something
    $alergiKeyword = trim((string) ($alergikatakunci ?? ''));
    $alergiChips = $alergiUnverified ? [] : array_values($alergiNames ?? []);
    $keywordInChips = $alergiKeyword !== '' && collect($alergiChips)->contains(fn ($name) => strcasecmp(trim((string) $name), $alergiKeyword) === 0);
    $alergiLabelClass = match (true) {
        !$hasAlergi => 'is-empty',
        $keywordInChips || $alergiKeyword === '' => 'd-none',
        count($alergiChips) > 0 => 'is-note',
        default => '',
    };

    // This visit
    $visitDate = $parseDate($visitation->tanggal_visitation ?? null);
    $visitTime = !empty($visitation->waktu_kunjungan) ? substr($visitation->waktu_kunjungan, 0, 5) : null;
    $dokterName = optional(optional($visitation->dokter)->user)->name;
    $visitNumber = (int) ($visitNumber ?? 0);
@endphp

{{--
    Patient identity card shared by the ERM visit pages (asesmen, CPPT, resep, lab, ...).
    Three panes: who the patient is, this visit, allergies; then contact details and history.
    Matches the sidebar: surface tokens (--sb-*, light + dark, public/css/sidebar.css) and the clinic accent
    (App\Support\ClinicTheme, as --pc-accent*). Responsive by the card's own width (container queries).
--}}
<link rel="stylesheet" href="{{ asset('css/erm-patient-card.css') }}?v={{ filemtime(public_path('css/erm-patient-card.css')) }}">

<div class="pasien-card pc-scope" style="{{ \App\Support\ClinicTheme::cssVars('pc') }}">
    <div class="pc-deco" aria-hidden="true"></div>
    <div class="pc-panes">
        {{-- Who --}}
        <div class="pc-ident">
            {{-- Name, then the member status / fast access icons (name on hover; Regular is the default, so no icon) --}}
            <div class="pc-name-row">
                <h3 class="pc-name">{{ ucfirst($pasien->nama ?? '-') }}</h3>
                @if($status !== 'Regular')
                    <span class="pc-icon {{ $config['class'] }}" title="Member {{ $status }}" aria-label="Member {{ $status }}" data-toggle="tooltip"><i class="{{ $config['icon'] }}"></i></span>
                @endif
                @if($akses == 'akses cepat')
                    <span class="pc-icon pc-chip-akses" title="Akses Cepat" aria-label="Akses Cepat" data-toggle="tooltip"><i class="fas fa-wheelchair"></i></span>
                @endif
            </div>
            {{-- No. RM (number only), gender + age --}}
            <div class="pc-meta">
                <span class="pc-rm" title="No. RM" aria-label="No. RM {{ $pasien->id ?? '-' }}">{{ $pasien->id ?? '-' }}</span>
                <span class="pc-gender {{ $isMale ? 'is-male' : ($isFemale ? 'is-female' : '') }}">
                    @if($isMale)
                        <i class="fas fa-mars"></i>Laki-laki
                    @elseif($isFemale)
                        <i class="fas fa-venus"></i>Perempuan
                    @else
                        <i class="fas fa-genderless"></i>{{ $pasien->gender ?: '-' }}
                    @endif
                    @if(!empty($usia) && $usia !== '-')
                        <span class="pc-gender-sep">&middot;</span><span title="{{ $usiaLengkap ?? $usia }}">{{ $usia }}</span>
                    @endif
                </span>
            </div>
    {{-- Details: icon + value (the label is the icon's tooltip) --}}
    <div class="pc-grid">
        <div class="pc-field">
            <span class="pc-field-icon" title="{{ $pasien->identity_label ?? 'Identitas' }}" aria-label="{{ $pasien->identity_label ?? 'Identitas' }}"><i class="fas fa-id-card"></i></span>
            <span class="pc-value">{{ $pasien->identity_number ?? '-' }}</span>
        </div>
        <div class="pc-field">
            <span class="pc-field-icon {{ $isBirthday ? 'is-birthday' : '' }}" title="Tanggal Lahir" aria-label="Tanggal Lahir"><i class="fas fa-birthday-cake"></i></span>
            <span class="pc-value {{ $isBirthday ? 'is-birthday' : '' }}">{{ $tanggalLahir ? $tanggalLahir->translatedFormat('d F Y') : ($pasien->tanggal_lahir ?? '-') }}@if($isBirthday) <span title="Ulang tahun hari ini">🎉</span>@endif</span>
        </div>
        <div class="pc-field">
            <span class="pc-field-icon" title="No. HP" aria-label="No. HP"><i class="fas fa-phone"></i></span>
            <span class="pc-value">{{ $pasien->no_hp ?: '-' }}</span>
        </div>
        <div class="pc-field is-alamat">
            <span class="pc-field-icon" title="Alamat" aria-label="Alamat"><i class="fas fa-home"></i></span>
            <span class="pc-value" title="{{ $fullAddress }}">{{ $shortAddress }}</span>
        </div>
    </div>
        </div>

        {{-- Right column: this visit, then the allergies --}}
        <div class="pc-side">
        {{-- This visit (+ when the patient last came / last had lab work) --}}
        <div class="pc-pane">
            <div class="pc-pane-head">
                <span class="pc-pane-title"><i class="fas fa-clipboard-list mr-1"></i>Kunjungan ini</span>
                <div class="pc-pane-links">
                    @if($lv)
                        <a class="pc-history-link" href="{{ route('erm.riwayatkunjungan.index', array_filter(['pasien' => $pasien->id, 'visitation_id' => isset($visitation) ? $visitation->id : null])) }}" target="_blank" title="{{ $lv->translatedFormat('d F Y') }} · Buka riwayat kunjungan">
                            Kunjungan terakhir: <strong>{{ $relativeDays($lv) }}</strong><i class="fas fa-chevron-right"></i>
                        </a>
                    @else
                        <span class="pc-chip pc-chip-new" title="Belum ada kunjungan klinis sebelumnya"><i class="fas fa-star"></i>Pasien baru</span>
                    @endif
                    @if($ll)
                        <a href="#" id="lastLabSpan" class="pc-history-link" data-toggle="modal" data-target="#modalLastLabPermintaan" title="{{ $ll->translatedFormat('d F Y') }} · Lihat permintaan lab">
                            Lab terakhir: <strong>{{ $relativeDays($ll) }}</strong><i class="fas fa-chevron-right"></i>
                        </a>
                    @else
                        <span class="pc-history-link">Lab terakhir: belum ada</span>
                    @endif
                </div>
            </div>
            <div class="pc-visit-rows">
                <div class="pc-visit-row">
                    <i class="far fa-calendar"></i>
                    <span>{{ $visitDate ? $visitDate->translatedFormat('l, d M Y') : '-' }}{{ $visitTime ? ' · ' . str_replace(':', '.', $visitTime) : '' }}</span>
                </div>
                <div class="pc-visit-row"><i class="fas fa-user-md"></i><span class="pc-visit-main">{{ $dokterName ?: 'Tanpa dokter' }}</span></div>
            </div>
        </div>

        {{-- Allergy (.alergi-label / .alergi-badges / .pc-alergi are updated by erm.partials.modal-alergipasien) --}}
        <div class="pc-pane pc-alergi {{ $hasAlergi ? 'has-alergi' : '' }}" role="region" aria-label="Alergi pasien">
            <div class="pc-pane-head">
                <span class="pc-pane-title pc-alergi-title"><i class="fas fa-allergies mr-1"></i>Alergi</span>
                <button type="button" class="pc-alergi-edit" data-toggle="modal" data-target="#modalAlergi">
                    <i class="fas fa-pen mr-1"></i>Edit
                </button>
            </div>
            <div class="pc-alergi-body">
                <div class="alergi-badges">@if($alergiUnverified)<span class="pc-chip pc-alergi-unverified"><i class="fas fa-exclamation-triangle"></i>Belum diverifikasi</span>@else @foreach($alergiChips as $alergiName)<span class="pc-chip pc-alergi-item">{{ $alergiName }}</span>@endforeach @endif</div>
                <span class="alergi-label {{ $alergiLabelClass }}">{{ $hasAlergi ? $alergiKeyword : 'Tidak ada alergi tercatat' }}</span>
            </div>
        </div>
        </div>
    </div>

</div>

<!-- Modal Edit Status Pasien & Akses (Combined) -->
<div class="modal fade" id="modalStatusCombined" tabindex="-1" role="dialog" aria-labelledby="modalStatusCombinedLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalStatusCombinedLabel">Edit Status Pasien</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="statusCombinedForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="combined_status_pasien">Status Pasien</label>
                                <select class="form-control" id="combined_status_pasien" name="status_pasien" required>
                                    <option value="Regular">Regular</option>
                                    <option value="VIP">VIP</option>
                                    <option value="Familia">Familia</option>
                                    <option value="Black Card">Black Card</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="combined_status_akses">Status Akses</label>
                                <select class="form-control" id="combined_status_akses" name="status_akses" required>
                                    <option value="normal">Normal</option>
                                    <option value="akses cepat">Akses Cepat</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveStatusCombined">Simpan</button>
            </div>
        </div>
    </div>
</div>

        <!-- Modal for Last Lab Permintaan -->
        <div class="modal fade" id="modalLastLabPermintaan" tabindex="-1" role="dialog" aria-labelledby="modalLastLabPermintaanLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalLastLabPermintaanLabel">Permintaan Lab pada Kunjungan Terkait</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        @if(isset($lastLabPermintaanList) && count($lastLabPermintaanList) > 0)
                            <ul class="list-group">
                                @foreach($lastLabPermintaanList as $permintaan)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $permintaan->labTest->nama ?? '-' }}
                                        <span class="badge badge-primary badge-pill">{{ $permintaan->status }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center">Tidak ada permintaan lab pada kunjungan ini.</div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

@push('scripts')
<script>window.ErmPatientCardConfig = {!! json_encode(['alergiCurrentUrl' => route('erm.alergi.current', $visitation->id)], 15) !!};</script>
<script src="{{ asset('js/erm-patient-card.js') }}?v={{ filemtime(public_path('js/erm-patient-card.js')) }}"></script>
@endpush
