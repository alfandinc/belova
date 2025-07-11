<!-- Modal for Inform Consent -->
<div class="modal fade" id="modalInformConsent" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
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
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button id="saveInformConsent" class="btn btn-success d-none">Simpan</button> <!-- Add Simpan button -->
      </div>
    </div>
  </div>
</div>

<style>
  #modalInformConsentBody .card {
    max-height: 65vh;
    overflow-y: auto;
  }
</style>