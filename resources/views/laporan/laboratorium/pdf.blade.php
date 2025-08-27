<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Laboratorium</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        th { background: #eee; }
        h2 { margin-bottom: 0; }
    </style>
</head>
<body>
    <h2>Laporan Laboratorium</h2>
    <table>
        <thead>
            <tr>
                <th>Tanggal Visit</th>
                <th>Pasien</th>
                <th>Nama Test</th>
                <th>Dokter</th>
                <th>Harga Jual</th>
                <th>Invoice</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr>
                <td>{{ $row['Tanggal Visit'] }}</td>
                <td>{{ $row['Pasien'] }}</td>
                <td>{{ $row['Nama Test'] }}</td>
                <td>{{ $row['Dokter'] }}</td>
                <td>{{ $row['Harga Jual'] }}</td>
                <td>{{ $row['Invoice'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
