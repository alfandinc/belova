@php
    $grouped = $list->groupBy('visitation_id');
@endphp
<div class="table-responsive">
    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>No Resep</th>
                <th>Pasien</th>
                <th>Tanggal Visit</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse($grouped as $visitationId => $items)
                @php
                    $first = $items->first();
                    $total = $items->sum('jumlah');
                @endphp
                <tr>
                    <td>{{ $first->resepDetail && $first->resepDetail->no_resep ? $first->resepDetail->no_resep : '-' }}</td>
                    <td>{{ $first->visitation && $first->visitation->pasien ? $first->visitation->pasien->nama : '-' }}</td>
                    <td>{{ $first->visitation ? ($first->visitation->tanggal_visitation ?? '-') : '-' }}</td>
                    <td>{{ $total }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
