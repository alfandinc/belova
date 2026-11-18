{{--
    Shared "Daftarkan Kunjungan" button. Opens the shared modal
    (erm.rawatjalans.partials.modal-daftar-kunjungan), which must be included on the page;
    klinik and layanan are picked there with chips (no jenis dropdown here any more).

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
@endphp
<button type="button" class="btn btn-success{{ $btnSize }} btn-daftarkan-pasien-rawatjalan" data-jenis="konsultasi"{!! $pasienData !!}>
    <i class="fas fa-calendar-plus mr-1"></i> {{ $label }}
</button>
