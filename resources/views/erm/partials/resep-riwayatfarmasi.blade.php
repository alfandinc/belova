@foreach ($reseps as $visitationId => $group)
    @include('erm.partials.resep-riwayatfarmasi-visit', [
        'visitationId' => $visitationId,
        'group' => $group,
        'racikanPaketNames' => $racikanPaketNames ?? [],
        'showCopyButton' => $showCopyButton ?? true,
    ])
    <hr class="my-4" style="border-top: 1px solid;">
@endforeach
