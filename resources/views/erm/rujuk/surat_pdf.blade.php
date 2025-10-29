<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title>Surat Permintaan Konsultasi - {{ optional($rujuk->pasien)->nama ?? '-' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        @page { margin: 25mm 15mm; }
    body { font-family: Arial, Helvetica, sans-serif; color:#111; font-size:13px }
    .header { display:flex; align-items:center; justify-content:space-between; }
    .logo { width:180px; }
    .clinic { text-align:right; }
    .clinic h4 { margin:0; font-size:15px; }
        .clinic .small { font-size:10px; color:#333 }
        hr.topline { border:0; border-top:2px solid #000; margin-top:6px; }
    .title { text-align:center; margin-top:6px; margin-bottom:8px; font-weight:700; font-size:16px; }
    .box { border:1px solid #000; padding:12px; margin-top:8px; }
        .box .topcells { display:flex; }
        .topcells .cell { flex:1; border:1px solid #000; padding:6px; font-size:12px }
        .details { margin-top:8px; }
        table.details-table { width:100%; border-collapse:collapse; }
    table.details-table td { vertical-align:top; padding:8px; font-size:13px }
        td.label { width:18%; font-weight:700; }
        td.colon { width:2%; }
        td.value { width:30%; }
        td.label-right { width:18%; font-weight:700; text-align:right; }
        .signature-block { margin-top:40px; }
        .bottom-right { position: absolute; right: 25mm; bottom: 30mm; text-align:center; }
        .qr { width:110px; height:110px; }
    </style>
</head>
<body>
    {{-- Use table layout for header because DomPDF has limited flexbox support --}}
    <table class="header-table" style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="vertical-align:middle; width:50%;">
                <div class="logo">
                    @php
                        // choose logo based on klinik id; adjust IDs as needed
                        $clinicId = optional($rujuk->visitation->klinik)->id;
                        // mapping: clinic id 1 -> premiere (blue), id 2 -> beauty (pink)
                        $logoFile = 'img/logo.png';
                        if ($clinicId == 1) {
                            $logoFile = 'img/logo-premiere.png';
                        } elseif ($clinicId == 2) {
                            $logoFile = 'img/logo-beauty.png';
                        }
                        $logoPath = public_path($logoFile);
                    @endphp
                    @if(file_exists($logoPath))
                        {{-- Use filesystem path so DomPDF can embed the image --}}
                        <img src="{{ $logoPath }}" style="height:70px; display:block;" />
                    @else
                        <div style="height:70px;"></div>
                    @endif
                </div>
            </td>
            <td style="vertical-align:middle; width:50%; text-align:right;">
                <div class="clinic">
                    <h4 style="margin:0;">KLINIK UTAMA PREMIERE BELOVA</h4>
                    <div class="small">Jl. Melon Raya No.27, Karangasem, Laweyan, Surakarta</div>
                    <div class="small">Telp. 0821-1600-0093</div>
                    <div class="small">www.premierebelova.id</div>
                </div>
            </td>
        </tr>
    </table>
    <hr class="topline" />

    <div class="title">SURAT PERMINTAAN KONSULTASI</div>

    {{-- Top row table moved outside the main box so it appears above the bordered box --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:6px;">
        <tr>
            <td style="width:50%; border:1px solid #000; padding:6px;">Tanggal: {{ optional($rujuk->created_at)->translatedFormat('j F Y') ?? '-' }}</td>
            <td style="width:50%; border:1px solid #000; padding:6px;">Klinik: {{ optional($rujuk->visitation->klinik)->nama ?? 'Klinik Utama Premiere Belova' }}</td>
        </tr>
    </table>

    <div class="box">
        <div class="details">
            <table class="details-table">
                <tr>
                    <td class="label">Nama</td>
                    <td class="colon">:</td>
                    <td class="value">{{ strtoupper(optional($rujuk->pasien)->nama ?? '-') }}</td>
                    <td class="label-right">No RM</td>
                    <td class="colon">:</td>
                    <td class="value">{{ str_pad(optional($rujuk->pasien)->id ?? '-', 6, '0', STR_PAD_LEFT) }}</td>
                </tr>
                <tr>
                    <td class="label">Umur</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $age ? $age.' Tahun' : '-' }}</td>
                    <td class="label-right">Jenis Kelamin</td>
                    <td class="colon">:</td>
                    <td class="value">{{ optional($rujuk->pasien)->gender ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Diagnosa</td>
                    <td class="colon">:</td>
                    <td class="value" colspan="4">
                        @if($rujuk->keterangan)
                            {!! nl2br(e($rujuk->keterangan)) !!}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label">Konsul Ke</td>
                    <td class="colon">:</td>
                    <td class="value" colspan="4">{{ optional($rujuk->dokterTujuan->user)->name ?? '-' }}</td>
                </tr>
            </table>
        </div>
    </div>

    @php
        $printedAt = now()->translatedFormat('j F Y');
        $qrImage = null;
        try {
            // Generate QR based on sending doctor's name. Fallbacks: dokterPengirim -> Auth user -> dokterTujuan
            $dokterName = optional($rujuk->dokterPengirim->user)->name
                ?? (Auth::user()->name ?? optional($rujuk->dokterTujuan->user)->name ?? '');
            $qrText = trim($dokterName);

            // If empty, fallback to an identifier string
            if (empty($qrText)) {
                $qrText = 'Rujuk#' . ($rujuk->id ?? '');
            }

            // Try PNG writer first
            try {
                $renderer = new \BaconQrCode\Renderer\Image\Png();
                $writer = new \BaconQrCode\Writer($renderer);
                $png = $writer->writeString($qrText);
                if (!empty($png)) {
                    $qrImage = 'data:image/png;base64,' . base64_encode($png);
                }
            } catch (\Throwable $ex1) {
                // PNG generation failed; attempt SVG fallback
                try {
                    $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                        new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
                        new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
                    );
                    $writer = new \BaconQrCode\Writer($renderer);
                    $svg = $writer->writeString($qrText);
                    if (!empty($svg)) {
                        $qrImage = 'data:image/svg+xml;base64,' . base64_encode($svg);
                    }
                } catch (\Throwable $ex2) {
                    // both failed - log for debugging
                    if (function_exists('logger')) {
                        logger()->error('QR generation failed for rujuk id ' . ($rujuk->id ?? '-') . ': ' . $ex2->getMessage());
                    } else {
                        \Log::error('QR generation failed for rujuk id ' . ($rujuk->id ?? '-') . ': ' . $ex2->getMessage());
                    }
                    $qrImage = null;
                }
            }
        } catch (\Throwable $ex) {
            if (function_exists('logger')) {
                logger()->error('QR generation outer failed for rujuk id ' . ($rujuk->id ?? '-') . ': ' . $ex->getMessage());
            } else {
                \Log::error('QR generation outer failed for rujuk id ' . ($rujuk->id ?? '-') . ': ' . $ex->getMessage());
            }
            $qrImage = null;
        }
    @endphp

    <div class="bottom-right">
        <div style="margin-bottom:8px;">Surakarta, {{ $printedAt }}</div>
        @if($qrImage)
            <div style="margin-bottom:8px;"><img src="{{ $qrImage }}" class="qr" /></div>
        @else
            <div style="height:110px; width:110px; border:1px solid #ccc; margin-bottom:8px;"></div>
        @endif
        <div style="font-weight:700;">{{ optional($rujuk->dokterPengirim->user)->name ?? (Auth::user()->name ?? '-') }}</div>
        {{-- <div style="font-size:10px;">dr.</div> --}}
    </div>

</body>
</html>
