<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SOP {{ $tindakan->nama }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header h2 {
            font-size: 18px;
            color: #666;
            margin-top: 0;
        }
        .sop-list {
            margin-left: 20px;
        }
        .sop-item {
            margin-bottom: 20px;
        }
        .sop-item h3 {
            margin-bottom: 5px;
            font-size: 16px;
            font-weight: bold;
        }
        .sop-item p {
            margin-top: 5px;
        }
        .footer {
            margin-top: 40px;
            font-size: 12px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Standard Operating Procedure (SOP)</h1>
        <h2>{{ $tindakan->nama }}</h2>
    </div>
    
    <div class="sop-list">
        @foreach($sopList as $index => $sop)
            <div class="sop-item">
                <h3>{{ $index + 1 }}. {{ $sop->nama_sop }}</h3>
                <p>{{ $sop->deskripsi }}</p>
            </div>
        @endforeach
    </div>
    
    <div class="footer">
        <p>Document generated on {{ now()->format('d F Y') }}</p>
    </div>
</body>
</html>