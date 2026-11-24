{{-- Approval progress rows for a pengajuan detail table. Param: $p (model with status_/notes_/tanggal_persetujuan_ fields) --}}
@php
    $badge = function ($status) {
        return match ($status) {
            'disetujui' => '<span class="badge badge-success">Disetujui</span>',
            'ditolak' => '<span class="badge badge-danger">Ditolak</span>',
            default => '<span class="badge badge-warning">Menunggu</span>',
        };
    };
    $steps = [
        'Manager' => [$p->status_manager, $p->notes_manager, $p->tanggal_persetujuan_manager],
        'HRD' => [$p->status_hrd, $p->notes_hrd, $p->tanggal_persetujuan_hrd],
    ];
    $atasanList = method_exists($p, 'persetujuanAtasan') ? $p->persetujuanAtasan()->with('approver')->get() : collect();
@endphp
@foreach($steps as $label => [$status, $notes, $tanggal])
<tr>
    <th>Persetujuan {{ $label }}</th>
    <td>
        {!! $badge($status) !!}
        @if($label === 'HRD' && $status === 'menunggu' && $p->status_manager === 'ditolak')
            <span class="text-muted small ml-1">(tidak diproses karena ditolak Manager)</span>
        @endif
        @if($tanggal)
            <span class="text-muted small ml-1">{{ $tanggal->locale('id')->translatedFormat('j M Y H:i') }}</span>
        @endif
        @if($label === 'Manager' && $atasanList->isNotEmpty())
            {{-- 2+ direct superiors: each decision listed; 2 approvals needed, 1 rejection rejects --}}
            <ul class="list-unstyled small mb-0 mt-1">
                @foreach($atasanList as $pa)
                <li>
                    <i class="fas {{ $pa->status === 'ditolak' ? 'fa-times text-danger' : 'fa-check text-success' }} mr-1"></i>
                    {{ $pa->approver->nama ?? '-' }}
                    <span class="text-muted">{{ $pa->created_at?->locale('id')->translatedFormat('j M Y H:i') }}</span>
                    @if($pa->notes)<div class="ml-3"><i class="fas fa-comment-alt text-muted mr-1"></i>{{ $pa->notes }}</div>@endif
                </li>
                @endforeach
            </ul>
            @if($status === 'menunggu')
                <div class="small text-muted mt-1">Menunggu persetujuan atasan lain</div>
            @endif
        @elseif($notes)
            <div class="mt-1"><i class="fas fa-comment-alt text-muted mr-1"></i>{{ $notes }}</div>
        @endif
    </td>
</tr>
@endforeach
@if($p->created_at)
<tr>
    <th>Diajukan Pada</th>
    <td>{{ $p->created_at->locale('id')->translatedFormat('j F Y H:i') }}</td>
</tr>
@endif
