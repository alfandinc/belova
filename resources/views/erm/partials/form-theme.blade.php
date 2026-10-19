{{--
    Shared look for the ERM assessment forms (asesmen perawat, asesmen dokter + its spesialisasi partials).
    Wrap the form in .erm-form with ClinicTheme::cssVars('ef'); restyles the existing Bootstrap markup (labels,
    inputs, radio / checkbox, tables) and adds:
      .erm-section (+ .erm-section-head / -num / -title / -sub)  numbered section cards
      .erm-subhead                                              small uppercase group title
      .erm-unit                                                 input with a unit suffix ("mmHg")
      .erm-actionbar                                            save bar stuck to the bottom of the screen
    Surface colours come from the sidebar tokens (--sb-*, light + dark, public/css/sidebar.css).
--}}
@once
<link rel="stylesheet" href="{{ asset('css/erm-form-theme.css') }}?v={{ filemtime(public_path('css/erm-form-theme.css')) }}">
@endonce
