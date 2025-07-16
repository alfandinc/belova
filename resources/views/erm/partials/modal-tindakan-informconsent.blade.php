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
        <button id="fullscreenSignature" class="btn btn-info">Perbesar Tanda Tangan</button>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    let signaturePad = null;
    let savedImageData = null;
    const fullscreenBtn = document.getElementById('fullscreenSignature');
    fullscreenBtn?.addEventListener('click', function() {
        const canvas = document.getElementById('signatureCanvas');
        if (!canvas) return;
        const container = canvas.parentElement;
        if (container.requestFullscreen) {
            container.requestFullscreen();
        } else if (container.webkitRequestFullscreen) {
            container.webkitRequestFullscreen();
        } else if (container.msRequestFullscreen) {
            container.msRequestFullscreen();
        }
    });

    function resizeCanvasForFullscreen(isFullscreen) {
        const canvas = document.getElementById('signatureCanvas');
        if (!canvas) return;
        // Save current drawing before resizing
        if (signaturePad && !isFullscreen) {
            savedImageData = canvas.toDataURL();
        }
        if (isFullscreen) {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            canvas.style.width = '100vw';
            canvas.style.height = '100vh';
        } else {
            canvas.width = 350;
            canvas.height = 150;
            canvas.style.width = '';
            canvas.style.height = '';
        }
        // Reinitialize signature pad after resizing
        if (window.SignaturePad) {
            signaturePad = new window.SignaturePad(canvas, {
                minWidth: 2.5,
                maxWidth: 4.5,
                penColor: 'black'
            });
        }
        // Restore drawing after exiting fullscreen
        if (!isFullscreen && savedImageData) {
            const img = new window.Image();
            img.onload = function() {
                canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
            };
            img.src = savedImageData;
        }
    }

    document.addEventListener('fullscreenchange', function() {
        const isFullscreen = document.fullscreenElement !== null;
        resizeCanvasForFullscreen(isFullscreen);
    });

    // Initial signature pad setup
    const canvas = document.getElementById('signatureCanvas');
    if (canvas && window.SignaturePad) {
        signaturePad = new window.SignaturePad(canvas);
    }
});
</script>