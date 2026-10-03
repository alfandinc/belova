{{--
    Shared "Daftarkan Kunjungan" button + dropdown. Opens the shared modal
    (erm.rawatjalans.partials.modal-daftar-kunjungan), which must be included on the page.

    Optional params:
      $label      button text (default "Daftarkan Kunjungan")
      $size       'sm' for tables
      $pasienId   pre-selects (and locks) the patient in the modal
      $pasienNama patient name shown with the locked patient
--}}
@php
    $label = $label ?? 'Daftarkan Kunjungan';
    $btnSize = ($size ?? null) === 'sm' ? ' btn-sm' : '';
    $pasienData = !empty($pasienId)
        ? ' data-id="' . e($pasienId) . '" data-nama="' . e($pasienNama ?? '') . '"'
        : '';
    $items = [
        ['jenis' => 'konsultasi', 'icon' => 'fas fa-stethoscope', 'text' => 'Konsultasi'],
        ['jenis' => 'lab', 'icon' => 'fas fa-flask', 'text' => 'Laboratorium'],
        ['jenis' => 'produk', 'icon' => 'fas fa-shopping-bag', 'text' => 'Produk dan Obat'],
        ['jenis' => 'event', 'icon' => 'fas fa-calendar-alt', 'text' => 'Event'],
        ['jenis' => 'marketplace', 'icon' => 'fas fa-store', 'text' => 'Marketplace'],
    ];
@endphp
<div class="btn-group" role="group">
    <button type="button" class="btn btn-success{{ $btnSize }} dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-calendar-plus mr-1"></i> {{ $label }}
    </button>
    <div class="dropdown-menu dropdown-menu-right">
        @foreach($items as $item)
            <a href="#" class="dropdown-item btn-daftarkan-pasien-rawatjalan" data-jenis="{{ $item['jenis'] }}"{!! $pasienData !!}><i class="{{ $item['icon'] }} mr-2"></i>{{ $item['text'] }}</a>
        @endforeach
    </div>
</div>
