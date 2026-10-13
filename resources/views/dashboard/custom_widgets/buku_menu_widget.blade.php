{{-- Price lookup. Tables are server-side DataTables, started by DashboardWidgetScripts (partials/widget_scripts). --}}
@php($key = 'bm-' . $widget->id)

<x-dash.card :widget="$widget" subtitle="Cari harga obat, tindakan, dan pemeriksaan lab" :href="route('buku-menu.index')" href-label="Buka Buku Menu">
    <div data-dash-buku-menu
         data-obat-url="{{ route('buku-menu.data') }}"
         data-tindakan-url="{{ route('buku-menu.tindakan-data') }}"
         data-lab-url="{{ route('buku-menu.labtest-data') }}">
        <div class="dw-search">
            <i data-feather="search"></i>
            <input type="search" class="js-bm-search" placeholder="Ketik nama obat, tindakan, atau lab…" aria-label="Cari di Buku Menu">
        </div>

        <nav class="dw-tabs" role="tablist">
            <a href="#{{ $key }}-obat" class="active" data-bm-tab="obat" role="tab">Obat</a>
            <a href="#{{ $key }}-tindakan" data-bm-tab="tindakan" role="tab">Tindakan</a>
            <a href="#{{ $key }}-lab" data-bm-tab="lab" role="tab">Lab</a>
        </nav>

        <div data-bm-pane="obat">
            <table class="dw-table js-bm-table" data-bm-table="obat">
                <thead><tr><th>Nama obat</th><th>Satuan</th><th class="text-right">Stok</th><th class="text-right">Harga jual</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
        <div data-bm-pane="tindakan" hidden>
            <table class="dw-table js-bm-table" data-bm-table="tindakan">
                <thead><tr><th>Nama tindakan</th><th>Jenis harga</th><th>Harga</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
        <div data-bm-pane="lab" hidden>
            <table class="dw-table js-bm-table" data-bm-table="lab">
                <thead><tr><th>Pemeriksaan</th><th>Kategori</th><th>Harga</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</x-dash.card>
