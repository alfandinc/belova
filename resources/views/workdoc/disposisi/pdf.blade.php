<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Disposisi</title>
    <style>
        @page { margin: 0; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #000; margin: 0; }
        .header { margin: 0; }
        .header-img { width: 100%; height: auto; display: block; }
        .page-content { padding: 20px 40px 40px; }
        table { width:100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 6px; vertical-align: top; }
        .no-border td, .no-border th { border: none; }
        .section-title { font-weight: bold; padding: 6px; }
        .grid-2 { width: 50%; }
        .checkbox { display:inline-block; width: 12px; height: 12px; border:1px solid #000; margin-right:6px; }
        .checked { background: #000; }
        .flex { display:flex; }
        .col { flex:1; }
        .muted { color: #555; }
        .line { border-bottom:1px dotted #000; height: 18px; }
        .title { text-align: center; font-weight: bold; margin: 2px 0 10px 0; font-size: 18pt; }
        /* Memorandum-style info table */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .info-table td { padding: 2px 0; vertical-align: top; border: none; }
        .label { width: 90px; font-weight: bold; }
        .content { }
    </style>
</head>
<body>
    <div class="header">
        @if(!empty($headerBase64))
            <img src="{{ $headerBase64 }}" class="header-img" alt="header">
        @endif
    </div>

    <div class="page-content">
    <div class="title">LEMBAR DISPOSISI</div>
    <!-- Memorandum-style detail block -->
    <table class="info-table">
        <tr>
            <td class="label">No.</td>
            <td class="content">: {{ optional($disposisi->memorandum)->nomor_memo ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal</td>
            <td class="content">: {{ optional(optional($disposisi->memorandum)->tanggal)->translatedFormat('l, d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Perihal</td>
            <td class="content">: {{ optional($disposisi->memorandum)->perihal }}</td>
        </tr>
        <tr>
            <td class="label">Dari</td>
            <td class="content">: {{ optional(optional($disposisi->memorandum)->division)->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Kepada</td>
            <td class="content">: {{ !empty(optional($disposisi->memorandum)->kepada) ? (optional($disposisi->memorandum)->kepada.' '.(optional(optional($disposisi->memorandum)->klinik)->nama ?? '')) : '-' }}</td>
        </tr>
    </table>

    <table style="margin-top:8px;">
        <tr>
            <td colspan="2" class="section-title">Disposisi Pimpinan</td>
        </tr>
        @php
            $opts = [
                'Untuk diketahui',
                'Untuk ditindaklanjuti',
                'Untuk dipelajari',
                'Koordinasi dengan unit terkait',
                'Segera dilaksanakan',
                'Arsipkan',
                'Lain-lain',
            ];
            $selected = collect($disposisi->disposisi_pimpinan ?? []);
        @endphp
        <tr>
            <td class="grid-2">
                <div><span class="checkbox @if($selected->contains('Untuk diketahui')) checked @endif"></span>Untuk diketahui</div>
                <div><span class="checkbox @if($selected->contains('Untuk dipelajari')) checked @endif"></span>Untuk dipelajari</div>
                <div><span class="checkbox @if($selected->contains('Segera dilaksanakan')) checked @endif"></span>Segera dilaksanakan</div>
                <div><span class="checkbox @if($selected->contains('Lain-lain')) checked @endif"></span>Lain-lain</div>
            </td>
            <td>
                <div><span class="checkbox @if($selected->contains('Untuk ditindaklanjuti')) checked @endif"></span>Untuk ditindaklanjuti</div>
                <div><span class="checkbox @if($selected->contains('Koordinasi dengan unit terkait')) checked @endif"></span>Koordinasi dengan unit terkait</div>
                <div><span class="checkbox @if($selected->contains('Arsipkan')) checked @endif"></span>Arsipkan</div>
            </td>
        </tr>
    </table>

    <table style="margin-top:8px;">
        <tr>
            <td colspan="2" class="section-title">Tujuan Disposisi</td>
        </tr>
        @php
            $selectedIds = collect($selectedDivisionIds ?? [])->map(function($id){ return (int)$id; })->all();
            $divs = collect($allDivisions ?? []);
            $half = (int) ceil(($divs instanceof \Illuminate\Support\Collection ? $divs->count() : count($divs)) / 2);
            $leftList = $divs instanceof \Illuminate\Support\Collection ? $divs->slice(0, $half) : array_slice($divs, 0, $half);
            $rightList = $divs instanceof \Illuminate\Support\Collection ? $divs->slice($half) : array_slice($divs, $half);
        @endphp
        <tr>
            <td class="grid-2">
                @foreach($leftList as $d)
                    @php $id = is_array($d) ? $d['id'] : $d->id; $name = is_array($d) ? ($d['name'] ?? ('Divisi '.$id)) : ($d->name ?? ('Divisi '.$id)); @endphp
                    <div><span class="checkbox @if(in_array((int)$id, $selectedIds)) checked @endif"></span>{{ $name }}</div>
                @endforeach
            </td>
            <td>
                @foreach($rightList as $d)
                    @php $id = is_array($d) ? $d['id'] : $d->id; $name = is_array($d) ? ($d['name'] ?? ('Divisi '.$id)) : ($d->name ?? ('Divisi '.$id)); @endphp
                    <div><span class="checkbox @if(in_array((int)$id, $selectedIds)) checked @endif"></span>{{ $name }}</div>
                @endforeach
            </td>
        </tr>
    </table>

    <table style="margin-top:8px;">
        <tr>
            <td class="grid-2">Catatan/Tanggapan</td>
            <td>{{ $disposisi->catatan }}</td>
        </tr>
    </table>
    </div>
</body>
</html>
