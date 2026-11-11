{{-- Layar Pelanggan (customer display) controls on the billing page. Script: layar-pelanggan-script. --}}
@if(auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Kasir', 'Finance']) && !empty($visitation))
<div id="layarPelangganPanel" class="mt-3 pt-3 border-top small" style="display:none;">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="font-weight-bold">
            <i class="fas fa-tv mr-1"></i>Layar Pasien
            <span id="lpDot" class="d-inline-block rounded-circle ml-1" style="width:8px;height:8px;background:#adb5bd;vertical-align:middle;" title="Status tablet"></span>
        </div>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="lpAuto">
            <label class="custom-control-label" for="lpAuto" title="Tampilkan billing ke layar pasien otomatis saat halaman ini dibuka">Otomatis</label>
        </div>
    </div>
    <select id="lpSelect" class="form-control form-control-sm mb-2" style="display:none;"></select>
    <div class="d-flex" style="gap:.35rem;">
        <button type="button" id="lpShowBtn" class="btn btn-sm btn-outline-primary flex-fill" title="Tampilkan billing ini di layar pasien">
            <i class="fas fa-desktop mr-1"></i>Tampilkan
        </button>
        <button type="button" id="lpSurveyBtn" class="btn btn-sm btn-outline-success flex-fill" title="Tampilkan survei kepuasan di layar pasien" style="display:none;">
            <i class="far fa-smile mr-1"></i>Survei
        </button>
        <button type="button" id="lpClearBtn" class="btn btn-sm btn-outline-secondary flex-fill" title="Kembalikan layar pasien ke tampilan awal">
            <i class="fas fa-eraser mr-1"></i>Kosongkan
        </button>
    </div>
    <div id="lpStatus" class="text-muted mt-2" style="min-height:1.2em;"></div>
</div>
<div id="layarPelangganEmpty" class="mt-3 pt-3 border-top small text-muted" style="display:none;">
    <i class="fas fa-tv mr-1"></i>Belum ada layar pasien.
    @if(auth()->user()->hasRole('Admin'))
        <a href="{{ route('finance.layar-pelanggan.index') }}" target="_blank">Atur layar</a>
    @else
        Minta Admin menambahkan di menu Layar Pelanggan.
    @endif
</div>
@endif
