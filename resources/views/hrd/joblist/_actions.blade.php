@php $currentUserId = auth()->id(); @endphp
<div class="btn-group" role="group">
    @if($currentUserId && $currentUserId == ($row->created_by ?? $row->creator?->id))
        <button class="btn btn-sm btn-secondary btn-edit-job" data-id="{{ $row->id }}">Edit</button>
    @endif
    <button class="btn btn-sm btn-info btn-lihat" data-id="{{ $row->id }}">Lihat</button>
    @if((($row->status ?? '') === 'progress'))
        <button class="btn btn-sm btn-success btn-selesai" data-id="{{ $row->id }}">Selesai</button>
    @endif
    @if((($row->status ?? '') === 'delegated'))
        <button class="btn btn-sm btn-primary btn-dibaca" data-id="{{ $row->id }}">Dibaca</button>
    @endif
    @if($currentUserId && $currentUserId == ($row->created_by ?? $row->creator?->id))
        <button class="btn btn-sm btn-danger btn-delete-job" data-id="{{ $row->id }}">Delete</button>
    @endif
</div>
