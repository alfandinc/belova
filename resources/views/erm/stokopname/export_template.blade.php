<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>obat_id</th>
            <th>nama_obat</th>
            <th>stok_sistem</th>
            <th>stok_fisik</th>
            <th>notes</th>
        </tr>
    </thead>
    <tbody>
        @foreach($obats as $obat)
        <tr>
            <td>{{ $obat->id }}</td>
            <td>{{ $obat->nama }}</td>
            <td>{{ $obat->stok }}</td>
            <td></td>
            <td></td>
        </tr>
        @endforeach
    </tbody>
</table>
