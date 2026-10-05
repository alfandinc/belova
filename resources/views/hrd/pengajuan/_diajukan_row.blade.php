{{-- Originally requested date range, shown only when an approver approved fewer days. Param: $p --}}
@if($p->tanggal_mulai_diajukan !== null)
<tr class="table-warning">
    <th>Diajukan Awalnya</th>
    <td>
        {{ $p->tanggal_mulai_diajukan->locale('id')->translatedFormat('j F Y') }}
        @unless($p->tanggal_mulai_diajukan->isSameDay($p->tanggal_selesai_diajukan))
            &ndash; {{ $p->tanggal_selesai_diajukan->locale('id')->translatedFormat('j F Y') }}
        @endunless
        ({{ $p->total_hari_diajukan }} hari)
        <div class="small text-muted">Disetujui sebagian. Tanggal di atas adalah tanggal yang disetujui.</div>
    </td>
</tr>
@endif
