<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Kehadiran Karyawan</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h2>Rekap Kehadiran Karyawan</h2>
    <table>
        <thead>
            <tr>
                <th>No Induk</th>
                <th>Nama</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Cuti</th>
                <th>Sisa Cuti</th>
                <th>Jumlah Hari Masuk</th>
                <th>On Time</th>
                <th>Overtime</th>
                <th>Terlambat</th>
                <th>Menit Terlambat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                <tr>
                    <td>{{ $row['no_induk'] }}</td>
                    <td>{{ $row['nama'] }}</td>
                    <td>{{ $row['sakit'] }}</td>
                    <td>{{ $row['izin'] }}</td>
                    <td>{{ $row['cuti'] }}</td>
                    <td>{{ $row['sisa_cuti'] }}</td>
                    <td>{{ $row['jumlah_hari_masuk'] }}</td>
                    <td>{{ $row['on_time'] }}</td>
                    <td>{{ $row['overtime'] }}</td>
                    <td>{{ $row['terlambat'] }}</td>
                    <td>{{ $row['menit_terlambat'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
