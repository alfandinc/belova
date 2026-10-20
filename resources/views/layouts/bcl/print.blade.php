{{--
    Bare page for printable BCL documents (nota, refund) shown inside the print preview modal
    (?embed=1): always the light theme, no sidebar/topbar, only what the document needs.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Cetak') - {{ config('app.name') }}</title>
    <link href="{{ asset('dastone/default/assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('dastone/default/assets/css/icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('dastone/default/assets/css/app.min.css') }}" rel="stylesheet">
    <style>
        body { background: #fff; padding: 16px; }
        .card { box-shadow: none; border: 0; margin-bottom: 0; }
        @media print {
            body { padding: 0; }
            @page { margin: 10mm; }
        }
    </style>
    @yield('page_css')
    @yield('pagecss')
</head>
<body>
    @yield('bcl-content')
    <script src="{{ asset('dastone/default/assets/js/jquery.min.js') }}"></script>
    @yield('pagescript')
</body>
</html>
