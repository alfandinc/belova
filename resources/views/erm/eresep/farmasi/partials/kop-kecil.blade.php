{{-- Compact letterhead of the visit's klinik: small icon + name + address lines ($kop from App\Support\PdfLetterhead) --}}
<table style="width:100%; border:none; margin-bottom:0;">
    <tr>
        @if(!empty($kop['icon']))
            <td style="width:60px; vertical-align:top; padding-right:0;">
                <img src="{{ $kop['icon'] }}" alt="Logo" style="width:55px; height:auto;">
            </td>
        @endif
        <td style="vertical-align:top;">
            <div class="header-left">
                <h2 style="margin: 0; font-size: 14px; font-weight: bold;">{{ $kop['nama'] }}</h2>
                @if(!empty($kop['lines']))
                    <p style="margin: 1px 0; font-size: 12px;">{!! collect($kop['lines'])->map(fn ($line) => e($line))->implode('<br>') !!}</p>
                @endif
            </div>
        </td>
    </tr>
</table>
