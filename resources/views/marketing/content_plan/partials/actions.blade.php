<div class="btn-group btn-group-sm" role="group" aria-label="Actions">
    	<button class="btn btn-info btn-edit" data-id="{{ $row->id }}" title="Edit"><i class="fas fa-edit"></i></button>
    	<button class="btn btn-secondary btn-statistics" data-id="{{ $row->id }}" title="Statistics"><i class="fas fa-chart-bar"></i></button>
    	<button class="btn btn-danger btn-delete" data-id="{{ $row->id }}" title="Delete"><i class="fas fa-trash"></i></button>
</div>
<?php
    try {
        $latestBrief = $row->briefs()->with('user')->orderBy('created_at','desc')->first();
    } catch (\Throwable $e) {
        $latestBrief = null;
    }
?>
@if($latestBrief && $latestBrief->user)
    <div class="mt-1 small text-muted">Brief by : {{ $latestBrief->user->name }}</div>
@endif
