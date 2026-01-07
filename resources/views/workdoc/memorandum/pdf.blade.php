<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Memorandum</title>
    <style>
        @page { margin: 0; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #000; margin: 0; }
        .header { margin: 0; }
        .header-img { width: 100%; height: auto; display: block; }
        .page-content { padding: 20px 40px 40px; }
        .title { text-align: center; font-weight: bold; margin: 2px 0 10px 0; font-size: 18pt; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .info-table td { padding: 2px 0; vertical-align: top; }
        .label { width: 90px; font-weight: bold; }
        .content { }
        .body { margin-top: 10px; }
        .signature { margin-top: 30px; }
        .small { font-size: 11px; color: #333; }
        ul { margin: 6px 0 6px 18px; }
        ol { margin: 6px 0 6px 18px; }
    </style>
</head>
<body>

    <div class="header">
        @if(!empty($headerBase64))
            <img src="{{ $headerBase64 }}" class="header-img" alt="header">
        @endif
    </div>

    <div class="page-content">
    <div class="title">MEMORANDUM</div>

    <table class="info-table">
        <tr>
            <td class="label">No.</td>
            <td class="content">: {{ $memorandum->nomor_memo ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal</td>
            <td class="content">: {{ optional($memorandum->tanggal)->translatedFormat('l, d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Perihal</td>
            <td class="content">: {{ $memorandum->perihal }}</td>
        </tr>
        <tr>
            <td class="label">Dari</td>
            <td class="content">: {{ $memorandum->division->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Kepada</td>
            <td class="content">: {{ !empty($memorandum->kepada) ? ($memorandum->kepada.' '.($memorandum->klinik->nama ?? '')) : '-' }}</td>
        </tr>
    </table>

    <div class="body">
        {!! $memorandum->isi !!}
    </div>

    <div class="signature">
        <div><strong>{{ $memorandum->klinik->nama ?? 'Klinik Belova' }}</strong></div>
        <div class="small">Manager Pelayanan Medis</div>
        <br><br><br>
        <div class="small">{{ $memorandum->user->name ?? '' }}</div>
    </div>
    </div>
</body>
</html>