{{-- Drawing tools under the Status Lokalis canvas; the logic lives in asesmendokter/create.blade.php --}}
<style>
    .lokalis-toolbar { gap: .4rem; }
    .lokalis-toolbar .lokalis-color { width: 28px; height: 28px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px #adb5bd; padding: 0; cursor: pointer; }
    .lokalis-toolbar .lokalis-color.active { box-shadow: 0 0 0 3px #0d6efd; }
    .lokalis-toolbar .lokalis-sep { width: 1px; height: 26px; background: #dee2e6; margin: 0 .25rem; }
    .lokalis-fullscreen { background: #fff; padding: 1rem; overflow: auto; }
    .lokalis-fullscreen #drawingCanvas { max-height: calc(100vh - 140px); width: auto; max-width: 100%; }
    html.theme-dark .lokalis-fullscreen { background: #1f2430; }
    html.theme-dark .lokalis-toolbar .lokalis-color { border-color: #1f2430; }
</style>
<div class="col-12 mb-1 text-center small text-muted">
    Coret langsung di gambar (mouse, jari, atau stylus). <b>Otomatis tersimpan saat klik Simpan.</b>
    <span class="badge badge-warning ml-1 d-none" id="lokalisDirtyBadge">Ada perubahan, belum disimpan</span>
</div>
<div class="col-12 mb-3 d-flex flex-wrap justify-content-center align-items-center lokalis-toolbar">
    <button type="button" class="lokalis-color active" data-color="#dc3545" style="background:#dc3545" title="Merah"></button>
    <button type="button" class="lokalis-color" data-color="#0d6efd" style="background:#0d6efd" title="Biru"></button>
    <button type="button" class="lokalis-color" data-color="#00c853" style="background:#00c853" title="Hijau"></button>
    <button type="button" class="lokalis-color" data-color="#212529" style="background:#212529" title="Hitam"></button>
    <span class="lokalis-sep"></span>
    <select class="custom-select custom-select-sm" id="lokalisSize" style="width:auto" title="Ketebalan garis">
        <option value="3">Tipis</option>
        <option value="5" selected>Sedang</option>
        <option value="9">Tebal</option>
    </select>
    <span class="lokalis-sep"></span>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="lokalisUndo"><i class="fas fa-undo mr-1"></i>Undo</button>
    <button type="button" class="btn btn-sm btn-outline-danger" id="resetButton"><i class="fas fa-eraser mr-1"></i>Hapus coretan</button>
    <button type="button" class="btn btn-sm btn-outline-primary" id="lokalisFullscreen"><i class="fas fa-expand mr-1"></i>Perbesar</button>
</div>
