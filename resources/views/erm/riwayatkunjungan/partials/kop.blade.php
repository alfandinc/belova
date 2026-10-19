{{-- Letterhead of the visit's klinik ($kop from App\Support\PdfLetterhead); include it first in <body>.
     With artwork: full-width header at the top and footer wave at the bottom of every page (fixed elements
     repeat per page in DomPDF); without: logo + name + address lines.
     $kopPaper: paper width in mm (210 = A4, the default; 148 = A5), the artwork is scaled to it. --}}
@if(!empty($kop['header']))
  @php
    $kopW = $kopPaper ?? 210;
    $kopScale = $kopW / 210;
    $kopSide = round(18 * $kopScale, 1);
    $kopHeaderH = round($kop['header']['height_mm'] * $kopScale, 1);
    $kopFooterH = !empty($kop['footer']) ? round($kop['footer']['height_mm'] * $kopScale, 1) : 0;
    $kopTop = $kopHeaderH + 4;
    $kopBottom = $kopFooterH ? $kopFooterH + 4 : 15;
  @endphp
  <style>
    @page { margin: {{ $kopTop }}mm {{ $kopSide }}mm {{ $kopBottom }}mm {{ $kopSide }}mm; }
    .kop-header { position: fixed; top: -{{ $kopTop }}mm; left: -{{ $kopSide }}mm; width: {{ $kopW }}mm; height: {{ $kopHeaderH }}mm; }
    .kop-footer { position: fixed; bottom: -{{ $kopBottom }}mm; left: -{{ $kopSide }}mm; width: {{ $kopW }}mm; height: {{ $kopFooterH }}mm; }
    .kop-header img, .kop-footer img { display: block; width: {{ $kopW }}mm; }
  </style>
  <div class="kop-header"><img src="{{ $kop['header']['path'] }}" alt="{{ $kop['nama'] }}"></div>
  @if(!empty($kop['footer']))
    <div class="kop-footer"><img src="{{ $kop['footer']['path'] }}" alt=""></div>
  @endif
@else
  <table class="no-border" style="margin-bottom: 0;">
    <tr>
      <td style="width: 35%;">
        @if(!empty($kop['logo']))
          <img src="{{ $kop['logo'] }}" alt="Logo" style="width: 180px;">
        @endif
      </td>
      <td style="width: 65%; text-align: right;">
        <strong>{{ $kop['nama'] }}</strong><br>
        @foreach($kop['lines'] as $line)
          {{ $line }}<br>
        @endforeach
      </td>
    </tr>
  </table>
  <hr>
@endif
