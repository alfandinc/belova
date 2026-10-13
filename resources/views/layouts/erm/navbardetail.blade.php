{{-- ERM visit pages: unified sidebar with the visit actions on top. --}}
@include('layouts.shared.sidebar', ['sidebarContextView' => 'layouts.erm.partials.visit-menu'])

@include('erm.rawatjalans.partials.modal-daftar-kunjungan')
@include('erm.partials.modal-rujuk')
