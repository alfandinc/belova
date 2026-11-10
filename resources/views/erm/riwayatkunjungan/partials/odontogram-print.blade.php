{{-- Odontogram for the PDFs (DomPDF: plain tables, no SVG). Needs $odontogram = ['data' => [...], 'intra_oral' => [...]] --}}
@php
    $odg = $odontogram['data'] ?? [];
    $intra = $odontogram['intra_oral'] ?? [];
    $dmf = \App\Support\Odontogram::dmf($odg);
    $used = collect($odg)->flatMap(fn ($c) => array_keys($c))->unique()->sort()->values();
@endphp
<style>
  .odg-print { width: 100%; border-collapse: collapse; margin: 4px 0 6px; }
  .odg-print td { border: 1px solid #000; padding: 1px; text-align: center; vertical-align: top; font-size: 9px; width: 5.5%; }
  .odg-print td.odg-lbl { text-align: left; width: 10%; font-size: 9px; border: none; padding-right: 3px; }
  .odg-print td.odg-gap { border: none; width: 1%; }
  .odg-print td.odg-blank { border: none; }
  .odg-print .odg-n { font-weight: bold; font-size: 9px; }
  .odg-print .odg-c { font-size: 8px; line-height: 1.15; }
  .odg-print td.odg-marked { background: #f1f1f1; }
  .odg-legend { font-size: 10px; }
</style>

<table class="odg-print">
  @foreach (\App\Support\Odontogram::ROWS as $rowLabel => [$rightSide, $leftSide])
    @php $pad = 8 - count($rightSide); @endphp
    <tr>
      <td class="odg-lbl">{{ $rowLabel }}</td>
      @for ($i = 0; $i < $pad; $i++)<td class="odg-blank"></td>@endfor
      @foreach ($rightSide as $tooth)
        @include('erm.riwayatkunjungan.partials.odontogram-print-tooth', ['tooth' => (string) $tooth, 'conditions' => $odg[(string) $tooth] ?? []])
      @endforeach
      <td class="odg-gap"></td>
      @foreach ($leftSide as $tooth)
        @include('erm.riwayatkunjungan.partials.odontogram-print-tooth', ['tooth' => (string) $tooth, 'conditions' => $odg[(string) $tooth] ?? []])
      @endforeach
      @for ($i = 0; $i < $pad; $i++)<td class="odg-blank"></td>@endfor
    </tr>
  @endforeach
</table>

<div class="odg-legend">
  <strong>DMF-T:</strong> {{ $dmf['DMFT'] }} (D {{ $dmf['D'] }}, M {{ $dmf['M'] }}, F {{ $dmf['F'] }})
  &nbsp;&nbsp; <strong>def-t:</strong> {{ $dmf['deft'] }} (d {{ $dmf['d'] }}, e {{ $dmf['e'] }}, f {{ $dmf['f'] }})
  @if ($used->isNotEmpty())
    <br><strong>Keterangan:</strong>
    {{ $used->map(fn ($code) => $code . ' = ' . (\App\Support\Odontogram::CONDITIONS[$code][0] ?? $code))->implode('; ') }}.
    Permukaan: M mesial, O oklusal/insisal, D distal, V bukal/labial, L lingual/palatal. Gigi tanpa kode = sehat.
  @else
    <br>Semua gigi sehat / tidak ada temuan.
  @endif
  @php
    $intraText = collect(\App\Support\Odontogram::INTRA_ORAL)->map(function ($def, $field) use ($intra) {
        if (empty($intra[$field]) && empty($intra[$field . '_ket'])) return null;
        return $def[0] . ': ' . ($intra[$field] ?? '-') . (!empty($intra[$field . '_ket']) ? ' (' . $intra[$field . '_ket'] . ')' : '');
    })->filter();
    if (!empty($intra['lain_lain'])) $intraText->push('Lain-lain: ' . $intra['lain_lain']);
  @endphp
  @if ($intraText->isNotEmpty())
    <br><strong>Status intra oral:</strong> {{ $intraText->implode('; ') }}
  @endif
</div>
