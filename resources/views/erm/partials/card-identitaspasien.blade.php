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
<style>
    /* Shared by the identity card and the allergy card (.pc-scope) */
    .pc-scope {
        --pc-danger: #dc2626;
        --pc-danger-soft: rgba(220, 38, 38, .07);
        --pc-danger-border: rgba(220, 38, 38, .3);
        --pc-warn-soft: #fffbeb;
        --pc-warn-text: #92400e;
        --pc-accent-text: var(--pc-accent-text-light);
    }
    html.theme-dark .pc-scope {
        --pc-danger: #f87171;
        --pc-danger-soft: rgba(248, 113, 113, .09);
        --pc-danger-border: rgba(248, 113, 113, .32);
        --pc-warn-soft: rgba(251, 191, 36, .12);
        --pc-warn-text: #fbbf24;
        --pc-accent-text: var(--pc-accent-text-dark);
    }
    .pc-scope .pc-chip {
        display: inline-flex; align-items: center; gap: 5px; border-radius: 999px;
        padding: 2px 10px; font-size: .76rem; font-weight: 700; line-height: 1.6; white-space: nowrap;
        background: var(--sb-tile, #f1f5f9); color: var(--sb-tile-icon, #66718d);
    }

    /* Same look as the dashboard header card (.dsh-hero): clinic-accent outline, tint, glow and corner rings */
    .pasien-card {
        --pc-tint: rgba(var(--pc-accent-rgb), .09);
        container-type: inline-size;
        position: relative;
        overflow: hidden;
        background:
            radial-gradient(520px 260px at 100% 0%, rgba(var(--pc-accent-rgb), .16), transparent 70%),
            linear-gradient(135deg, var(--pc-tint), transparent 60%),
            var(--sb-bg, #fff);
        color: var(--sb-text-strong, #1e2538);
        border: 1px solid rgba(var(--pc-accent-rgb), .22);
        border-radius: 14px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 12px 32px -18px rgba(var(--pc-accent-rgb), .45);
        margin-bottom: 1.25rem;
        padding: 14px 18px;
    }
    html.theme-dark .pasien-card {
        --pc-tint: rgba(var(--pc-accent-rgb), .16);
        border-color: rgba(var(--pc-accent-rgb), .32);
        box-shadow: 0 1px 2px rgba(0, 0, 0, .3);
    }
    /* Decorative rings in the corner (as on the dashboard) */
    .pasien-card .pc-deco {
        position: absolute; top: -90px; right: -60px; width: 280px; height: 280px; border-radius: 50%;
        border: 36px solid rgba(var(--pc-accent-rgb), .06); pointer-events: none;
    }
    .pasien-card .pc-deco::after {
        content: ''; position: absolute; inset: 40px; border-radius: 50%;
        border: 1px dashed rgba(var(--pc-accent-rgb), .25);
    }
    .pasien-card .pc-panes, .pasien-card .pc-grid { position: relative; }
    /* Panes: stacked on narrow cards, side by side on wide ones (container queries below) */
    .pasien-card .pc-panes { display: grid; grid-template-columns: minmax(0, 1fr); gap: 18px; align-items: start; }

    /* Identity: name, then a clearly visible No. RM + age */
    .pasien-card .pc-ident { min-width: 0; }
    .pasien-card .pc-name-row { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; }
    .pasien-card .pc-name { font-size: 1.35rem; font-weight: 700; line-height: 1.25; margin: 0; color: var(--sb-text-strong, #1e2538); overflow-wrap: anywhere; }
    /* Gender (+ age): coloured label with text, not just a small icon */
    .pasien-card .pc-gender {
        display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 3px 11px;
        font-size: .85rem; font-weight: 600; white-space: nowrap;
        background: var(--sb-tile, #f1f5f9); color: var(--sb-text-strong, #1e2538);
    }
    .pasien-card .pc-gender i { font-size: .95rem; }
    .pasien-card .pc-gender.is-male { background: rgba(37, 99, 235, .12); color: #1d4ed8; }
    .pasien-card .pc-gender.is-female { background: rgba(219, 39, 119, .12); color: #be185d; }
    html.theme-dark .pasien-card .pc-gender.is-male { background: rgba(96, 165, 250, .16); color: #93c5fd; }
    html.theme-dark .pasien-card .pc-gender.is-female { background: rgba(244, 114, 182, .16); color: #f9a8d4; }
    .pasien-card .pc-gender-sep { opacity: .55; }
    .pasien-card .pc-meta { display: flex; flex-wrap: wrap; gap: 6px 8px; align-items: center; margin-top: 8px; }
    /* No. RM: number only, on the clinic accent so it stays easy to spot */
    .pasien-card .pc-rm {
        display: inline-flex; align-items: center; border-radius: 8px; padding: 2px 10px;
        background: var(--pc-accent); color: #fff; line-height: 1.4; cursor: help;
        font-size: 1rem; font-weight: 800; letter-spacing: .05em; font-variant-numeric: tabular-nums;
    }
    .pasien-card .pc-icon {
        display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 8px;
        font-size: .85rem; background: var(--sb-tile, #f1f5f9); color: var(--sb-tile-icon, #66718d); cursor: help;
    }
    .pasien-card .pc-chip-vip { background: rgba(245, 158, 11, .15); color: #b45309; }
    .pasien-card .pc-chip-familia { background: rgba(34, 197, 94, .14); color: #15803d; }
    .pasien-card .pc-chip-black { background: #1f2937; color: #f9fafb; }
    .pasien-card .pc-chip-akses { background: rgba(var(--pc-accent-rgb), .12); color: var(--pc-accent-text); }
    .pasien-card .pc-chip-new { background: rgba(var(--pc-accent-rgb), .14); color: var(--pc-accent-text); }
    html.theme-dark .pasien-card .pc-chip-vip { color: #fbbf24; }
    html.theme-dark .pasien-card .pc-chip-familia { color: #4ade80; }
    html.theme-dark .pasien-card .pc-chip-black { background: #0b0f17; color: #e5e7eb; box-shadow: inset 0 0 0 1px #3f4a63; }

    /* Side panes (this visit, allergy) */
    .pasien-card .pc-pane {
        border-radius: 12px; padding: 9px 14px; min-width: 0;
        background: var(--sb-tile, #f1f5f9); border: 1px solid var(--sb-line, #e2e8f0);
    }
    .pasien-card .pc-pane-head { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 4px 10px; min-height: 22px; }
    /* Kunjungan / lab terakhir side by side in the pane head */
    .pasien-card .pc-pane-links { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 2px 14px; }
    .pasien-card .pc-pane-title { white-space: nowrap; font-size: .72rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--sb-muted, #8a94ad); }
    .pasien-card .pc-visit-rows { display: grid; gap: 3px; margin-top: 6px; font-size: .88rem; }
    .pasien-card .pc-visit-row { display: flex; align-items: baseline; gap: 8px; min-width: 0; color: var(--sb-text, #4b5671); font-weight: 400; }
    .pasien-card .pc-visit-row i { color: var(--sb-tile-icon, #66718d); width: 14px; text-align: center; flex: 0 0 14px; }
    .pasien-card .pc-visit-row span { overflow-wrap: anywhere; }
    .pasien-card .pc-visit-row .pc-visit-main { font-weight: 500; color: var(--sb-text-strong, #1e2538); }

    /* Right column: "Kunjungan ini" pane, then the allergy pane */
    .pasien-card .pc-side { display: grid; gap: 8px; min-width: 0; }

    /* Allergy pane: safety information, red while the patient has allergies */
    .pasien-card .pc-alergi.has-alergi { background: var(--pc-danger-soft); border-color: var(--pc-danger-border); }
    .pasien-card .pc-alergi.has-alergi .pc-alergi-title { color: var(--pc-danger); }
    /* Allergy pane on one line: title, chips / note, Edit */
    .pasien-card .pc-alergi { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; }
    .pasien-card .pc-alergi .pc-pane-head { display: contents; }
    .pasien-card .pc-alergi .pc-alergi-edit { order: 3; margin-left: auto; }
    .pasien-card .pc-alergi-body { order: 2; display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; }
    .pasien-card .alergi-badges { display: flex; flex-wrap: wrap; gap: 6px; }
    .pasien-card .alergi-badges:not(:has(.pc-chip)) { display: none; }
    .pasien-card .alergi-badges .pc-chip { padding: 2px 11px; font-size: .78rem; letter-spacing: .02em; }
    .pasien-card .pc-alergi-item { background: #dc2626; color: #fff; }
    .pasien-card .pc-alergi-unverified { background: var(--pc-warn-soft); color: var(--pc-warn-text); }
    .pasien-card .alergi-label { font-weight: 600; font-size: .88rem; color: var(--sb-text-strong, #1e2538); }
    .pasien-card .alergi-label.is-empty { font-weight: 400; color: var(--sb-muted, #8a94ad); }
    /* Kata kunci that adds to the chips: a small grey note */
    .pasien-card .alergi-label.is-note { font-weight: 400; font-size: .82rem; color: var(--sb-muted, #8a94ad); }
    .pasien-card .alergi-label.is-note::before { content: 'Kata kunci: '; }
    .pasien-card .pc-alergi-edit {
        border: 1px solid rgba(var(--pc-accent-rgb), .35); background: var(--sb-bg, #fff); color: var(--pc-accent-text);
        border-radius: 7px; padding: 1px 10px; font-size: .75rem; font-weight: 600; line-height: 1.5;
    }
    .pasien-card .pc-alergi-edit:hover { background: rgba(var(--pc-accent-rgb), .1); }

    /* Details: 2 columns (NIK | tanggal lahir, No. HP | alamat); the left one only as wide as its values; one column on narrow cards */
    .pasien-card .pc-grid {
        display: grid; grid-template-columns: minmax(150px, max-content) minmax(0, 1fr); align-items: start; gap: 10px 40px;
        margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--sb-line, #e2e8f0);
    }
    .pasien-card .pc-field.is-alamat { grid-column: auto; }
    /* Medium cards: the left column is narrow, so alamat gets a full row */
    @container (min-width: 481px) and (max-width: 999px) { .pasien-card .pc-field.is-alamat { grid-column: 1 / -1; } }
    .pasien-card .pc-field { display: flex; align-items: flex-start; gap: 8px; min-width: 0; }
    .pasien-card .pc-field-icon {
        flex: 0 0 26px; width: 26px; height: 26px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;
        font-size: .78rem; background: rgba(var(--pc-accent-rgb), .1); color: var(--pc-accent-text); cursor: help;
    }
    .pasien-card .pc-field-icon.is-birthday { background: var(--pc-danger-soft); color: var(--pc-danger); }
    .pasien-card .pc-value { font-weight: 400; font-size: .9rem; line-height: 1.45; padding-top: 3px; color: var(--sb-text, #4b5671); overflow-wrap: anywhere; }
    .pasien-card .pc-value a { color: var(--pc-accent-text); }
    .pasien-card .pc-value.is-birthday { color: var(--pc-danger); }

    /* History: plain text links */
    .pasien-card .pc-history { display: flex; flex-wrap: wrap; gap: 4px 18px; margin-top: 8px; font-size: .82rem; }
    .pasien-card .pc-history-link { display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; font-size: .8rem; color: var(--sb-muted, #8a94ad); text-decoration: none; }
    .pasien-card .pc-history-link .fa-chevron-right { font-size: .6rem; opacity: .7; }
    .pasien-card .pc-history-link strong { color: var(--pc-accent-text); font-weight: 600; }
    .pasien-card .pc-history-link:hover strong, .pasien-card .pc-history-link:focus strong { text-decoration: underline; }

    @container (min-width: 640px) {
        .pasien-card .pc-panes { grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr); }
    }
    @container (min-width: 1000px) {
        .pasien-card .pc-panes { grid-template-columns: minmax(0, 1.6fr) minmax(320px, 1fr); }
    }
    @container (max-width: 480px) {
        .pasien-card .pc-name { font-size: 1.12rem; }
        .pasien-card .pc-pane-links { justify-content: flex-start; }
        .pasien-card .pc-grid { gap: 8px 16px; }
        .pasien-card .pc-grid { grid-template-columns: minmax(0, 1fr); }
    }
</style>

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
                        <a class="pc-history-link" href="{{ route('erm.riwayatkunjungan.index', $pasien->id) }}" target="_blank" title="{{ $lv->translatedFormat('d F Y') }} · Buka riwayat kunjungan">
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
<script>
// Allergy pane: same rules as the Blade above. Also used by erm.partials.modal-alergipasien after saving.
window.pcUpdateAlergiDisplay = function(data) {
    var keyword = $.trim(data.kataKunci || '');
    var names = data.alergiNames || [];
    // Only a kata kunci and no zat aktif: 'belum diverifikasi' (yellow)
    var unverified = names.length === 1 && keyword && names[0] === data.kataKunci;
    var chips = unverified ? [] : names.filter(function(name) { return !!name; });
    var hasAlergi = unverified || chips.length > 0 || !!keyword;

    var badgesHtml = unverified
        ? '<span class="pc-chip pc-alergi-unverified"><i class="fas fa-exclamation-triangle"></i>Belum diverifikasi</span>'
        : chips.map(function(name) { return '<span class="pc-chip pc-alergi-item">' + $('<div>').text(name).html() + '</span>'; }).join('');
    $('.alergi-badges').html(badgesHtml);

    // Kata kunci: hidden when it only repeats a chip, a small note when it adds something
    var keywordInChips = !!keyword && chips.some(function(name) { return String(name).trim().toLowerCase() === keyword.toLowerCase(); });
    $('.alergi-label')
        .text(hasAlergi ? keyword : 'Tidak ada alergi tercatat')
        .removeClass('is-empty is-note d-none')
        .addClass(!hasAlergi ? 'is-empty' : ((keywordInChips || !keyword) ? 'd-none' : (chips.length ? 'is-note' : '')));

    // The card turns red while the patient has allergies
    $('.pc-alergi').toggleClass('has-alergi', hasAlergi);
};

// Refresh the allergy pane every 10 s, so an allergy entered on another screen (perawat / dokter / farmasi) shows up here
(function() {
    var url = @json(route('erm.alergi.current', $visitation->id));
    var lastSignature = null;
    var busy = false;

    function syncAlergiForm(data) {
        // Keep the Edit modal's form current, so saving it doesn't put back stale allergies
        var $form = $('#formAlergi');
        if (!$form.length) return;
        var status = data.status === 'ada' ? 'ada' : 'tidak';
        $form.find('input[name="statusAlergi"][value="' + status + '"]').prop('checked', true).trigger('change');
        $form.find('#inputKataKunci').val(data.kataKunci || '');
        $form.find('select[name="zataktif_id[]"]').val((data.alergiIds || []).map(String)).trigger('change');
    }

    function poll() {
        // Not while the tab is in the background, a request is running, or the allergy form is being edited
        if (busy || document.hidden || $('#modalAlergi').hasClass('show')) return;
        busy = true;
        $.ajax({ url: url, dataType: 'json', cache: false })
            .done(function(data) {
                var signature = JSON.stringify(data);
                if (signature !== lastSignature) {
                    window.pcUpdateAlergiDisplay(data);
                    syncAlergiForm(data);
                }
                lastSignature = signature;
            })
            .always(function() { busy = false; });
    }

    $(function() {
        if (!$('.pc-alergi').length) return;
        poll();
        setInterval(poll, 10000);
    });
})();

$(document).ready(function() {
    // Open combined modal and set current statuses
    $('#modalStatusCombined').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var pasienId = button.data('pasien-id');
        var currentStatusPasien = button.data('current-status-pasien');
        var currentStatusAkses = button.data('current-status-akses');
        
        $('#combined_status_pasien').val(currentStatusPasien);
        $('#combined_status_akses').val(currentStatusAkses);
        $('#modalStatusCombined').data('pasien-id', pasienId);
    });
    
    // Save combined status
    $('#saveStatusCombined').on('click', function() {
        var pasienId = $('#modalStatusCombined').data('pasien-id');
        var newStatusPasien = $('#combined_status_pasien').val();
        var newStatusAkses = $('#combined_status_akses').val();
        
        // Update both statuses at once with the combined endpoint
        $.ajax({
            url: '/erm/pasiens/' + pasienId + '/update-status-combined',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                status_pasien: newStatusPasien,
                status_akses: newStatusAkses
            },
            success: function(response) {
                if(response.success) {
                    $('#modalStatusCombined').modal('hide');
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Status pasien berhasil diperbarui.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Reload the page to update all UI elements
                    location.reload();
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Gagal memperbarui status pasien.',
                });
            }
        });
    });
});
</script>
@endpush
