<!-- Modal for Inform Consent (the form is loaded per tindakan; signature pads + "Perbesar" are run by erm/tindakan/create) -->
<div class="modal fade" id="modalInformConsent" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Inform Consent Tindakan</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="modalInformConsentBody">
        <div class="card">
          <!-- Card content here -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

{{-- Full-screen signing area for one signature ("Perbesar"): what is drawn here goes into that signature pad --}}
<div class="ic-sign-overlay" id="icSignOverlay" aria-hidden="true">
  <div class="ic-sign-overlay-bar">
    <strong id="icSignOverlayTitle">Tanda Tangan</strong>
    <div>
      <button type="button" class="btn btn-sm btn-outline-danger" id="icSignOverlayClear">Hapus</button>
      <button type="button" class="btn btn-sm btn-secondary" id="icSignOverlayCancel">Batal</button>
      <button type="button" class="btn btn-sm btn-success" id="icSignOverlayUse">Gunakan</button>
    </div>
  </div>
  <canvas id="icSignOverlayCanvas"></canvas>
</div>

<style>
  /* The whole form scrolls inside the modal (modal-dialog-scrollable); no inner scroll box */
  /* Signature boxes: as wide as the column (the templates set a fixed 350 x 150) */
  #modalInformConsentBody .signature-pad-container {
    width: 100% !important;
    max-width: 520px;
    height: 190px !important;
    touch-action: none;
  }
  .ic-sign-expand { margin-left: 4px; }
  .ic-sign-overlay { display: none; position: fixed; inset: 0; z-index: 2100; background: #fff; flex-direction: column; }
  .ic-sign-overlay.show { display: flex; }
  .ic-sign-overlay-bar { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 10px 14px; border-bottom: 1px solid #dee2e6; background: #f8f9fa; color: #212529; }
  .ic-sign-overlay-bar .btn { margin-left: 4px; }
  #icSignOverlayCanvas { flex: 1 1 auto; width: 100%; touch-action: none; background: #fff; }
</style>
