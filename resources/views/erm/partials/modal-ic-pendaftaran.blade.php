<!-- Modal: IC Pendaftaran -->
<div class="modal fade" id="icModal" tabindex="-1" role="dialog" aria-labelledby="icModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document" style="max-width:1100px;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="icModalLabel">Persetujuan & Informasi Pasien</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="max-height:80vh; overflow-y:auto;">
        <style>
          :root { --ic-accent: #1e6fb8; --ic-text: #2b2b2b; }
          .ic-modal { font-family: 'Helvetica Neue', Arial, sans-serif; color:var(--ic-text); }
          .ic-doc-title { text-align:center; font-weight:800; margin:6px 0 12px; letter-spacing:0.3px; font-size:15px; }
          .ic-fields { width:100%; margin-bottom:14px; font-size:13px; }
          .ic-fields td { padding:8px 6px; vertical-align:middle; }
          .ic-label { width:140px; color:#333; font-weight:600; }
          .ic-line { border-bottom:1px dotted #bdbdbd; display:inline-block; min-width:240px; padding-bottom:4px; }
          .ic-section { margin-top:14px; }
          .ic-section h5 { font-size:14px; margin-bottom:8px; color:var(--ic-accent); font-weight:700; }
          .ic-section ol { padding-left:26px; margin:0 0 10px 0; }
          .ic-section ol li { margin-bottom:10px; text-align:justify; line-height:1.45; }
          .ic-section ol li::marker { font-weight:700; color:var(--ic-accent); }
          .ic-sign { margin-top:22px; }
          .ic-sign canvas { border:1px solid #e0e0e0; background:#fff; }
          .ic-actions { margin-top:12px; display:flex; justify-content:space-between; align-items:center; }
          .ic-actions .btn { min-width:110px; }
        </style>

        <div style="text-align:center; margin-bottom:8px; font-weight:700;">HAK DAN KEWAJIBAN PASIEN KLINIK UTAMA PREMIERE BELOVA</div>

        <table class="ic-fields">
          <tr>
            <td style="width:140px;">NAMA PASIEN</td>
            <td><span id="ic_nama" class="ic-line">&nbsp;</span></td>
            <td style="width:140px;">TANGGAL LAHIR</td>
            <td><span id="ic_tanggal_lahir" class="ic-line">&nbsp;</span></td>
          </tr>
          <tr>
            <td>ALAMAT</td>
            <td><span id="ic_alamat" class="ic-line">&nbsp;</span></td>
            <td>NIK</td>
            <td><span id="ic_nik" class="ic-line">&nbsp;</span></td>
          </tr>
          <tr>
            <td>NO. RM</td>
            <td><span id="ic_no_rm" class="ic-line">&nbsp;</span></td>
            <td>NO. HP</td>
            <td><span id="ic_no_hp" class="ic-line">&nbsp;</span></td>
          </tr>
        </table>

        <div class="ic-section">
          <h5>A. HAK DAN KEWAJIBAN KLINIK</h5>
          <ol>
            <li>Seluruh pasien yang datang akan memperoleh informasi tentang hak dan kewajiban yang berlaku di Klinik.</li>
            <li>Klinik memberikan pelayanan tanpa membedakan kelas, jenis kelamin dan agama secara manusiawi, adil, jujur, dan tanpa diskriminasi,</li>
            <li>Klinik memberikan pelayanan kesehatan yang profesional, bermutu seusai dengan standar profesi dan standar prosedur operasional;</li>
            <li>Klinik melindungi privasi dan kerahasian penyakit yang diderita termasuk data-data medisnya;</li>
            <li>Klink memberikan pilihan kepada pasien atas persetujuan atau penolakkan tindakan atau pengobatan yang akan dilakukan oleh tenaga kesehatan terhadap penyakut yang dideritanya;</li>
            <li>Klinik memfasilitasi keluarga untuk mendampingi pasien yang menbutuhkan bantuan;</li>
            <li>Klinik memonitoring dan mengevaluasi secara periodik pelaksanaan edukasi hak dan kewajiban pasien.</li>
          </ol>
        </div>

        <div class="ic-section">
          <h5>B. HAK PASIEN</h5>
          <ol>
            <li>Memperoleh informasi mengenai tata tertib dan peraturan yang berlaku di Klinik Utama Premiere Belova.</li>
            <li>Memperoleh informasi mengenai hak dan kewajiban pasien.</li>
            <li>Memperoleh layanan yang manusiawi, adil, jujur, dan tanpa diskriminasi.</li>
            <li>Memperoleh pelayanan kesehatan bermutu sesuai dengan standar profesi.</li>
            <li>Memperoleh layanan yang efektif dan effisien sehingga pasien terhindar dari kerugian fisik dan materi.</li>
            <li>Mengajukan pengaduan atas kualitas pelayanan yang didapatkan melalui kotak saran.</li>
            <li>Mendapatkan privasi dan kerahasian penyakit yang diderita termasuk data medinya.</li>
            <li>Memberikan persetujuan atau menolak atas tindakan yang akan dilakukan oleh tenaga kesehatan terhadap penyakit yang dideritanya.</li>
            <li>Mendapatkan infromasi yang meliputi diagnosis dan tata cara tindakan medis, tujuan tindakan medis, alternatif tindakan, resiko dan komplikasi yang mungkin terjadi, dan prognosis terhadap tindakan yang dilakukan serta perkiraan biaya pengobatan.</li>
            <li>Memperoleh keamanan dan keselamatan dirinya selama dalam perawatan di Klinik Utama Premiere Belova.</li>
            <li>Menyampaikan usul dan saran dalam rangka perbaikan atas perlakuan Klinik terhadap dirinya.</li>
          </ol>
        </div>

        <div class="ic-section">
          <h5>C. KEWAJIBAN PASIEN</h5>
          <ol>
            <li>Pasien dan keluarga berkewajiban menaati segala peraturan dan tata tertib di Klinik.</li>
            <li>Pasien wajib untuk menginformasikan secara jujur tentang segala sesuatu mengenai penyakit yang dideritanya.</li>
            <li>Pasien wajib untuk mentaati segala intrusksi dokter dalam rangka pengobatannya.</li>
            <li>Pasien dan pengantar berkewajiban untuk memenuhi segala ketentuan administrasi.</li>
          </ol>
        </div>

        <div class="ic-sign">
          <label style="font-weight:700;">TANDA TANGAN (GORES/KLIK DAN GAMBAR TANDA TANGAN ANDA)</label>
          <div class="border p-2" style="background:#fff; margin-top:8px;">
            <canvas id="signature-pad" style="width:100%; height:260px; touch-action: none;"></canvas>
          </div>
          <div class="ic-actions">
            <div>
              <button type="button" class="btn btn-sm btn-light" id="clear-signature">Bersihkan</button>
            </div>
            <div>
              <button type="button" class="btn btn-primary" id="save-signature">Simpan</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Signature Pad library -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var canvas = document.querySelector('#signature-pad');
  var signaturePad = null;

  function formatDateIdLong(s) {
    if (!s) return '';
    try {
      var dateStr = s.toString();
      // Normalize to YYYY-MM-DD
      if (dateStr.indexOf('T') !== -1) {
        dateStr = dateStr.substring(0, 10);
      }
      var parts = dateStr.split('-');
      if (parts.length === 3) {
        var y = parseInt(parts[0], 10);
        var m = Math.max(1, Math.min(12, parseInt(parts[1], 10)));
        var d = parseInt(parts[2], 10);
        var bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        return d + ' ' + bulan[m - 1] + ' ' + y;
      }
    } catch (e) {}
    return s;
  }

  function resizeCanvas() {
    // If canvas is not visible its offsetWidth/Height can be 0
    var width = canvas.offsetWidth || 600;
    var height = canvas.offsetHeight || 200;
    var ratio = Math.max(window.devicePixelRatio || 1, 1);
    canvas.width = Math.floor(width * ratio);
    canvas.height = Math.floor(height * ratio);
    canvas.getContext('2d').scale(ratio, ratio);
  }

  // Initialize or re-init signature pad when modal is shown (canvas must be visible)
  $('#icModal').on('shown.bs.modal', function () {
    resizeCanvas();
    if (!signaturePad) {
      signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgba(255,255,255,0)' });
    } else {
      signaturePad.clear();
    }

    // If opened from index list, patient data is attached to modal data
    var pasienData = $('#icModal').data('pasien') || {};
    var fromIndex = !!pasienData.id;

    var nama = '';
    var alamat = '';
    var nik = '';
    var no_hp = '';
    var tanggal_lahir = '';

    if (fromIndex) {
      nama = pasienData.nama || '';
      alamat = pasienData.alamat || '';
      nik = pasienData.nik || '';
      no_hp = pasienData.no_hp || '';
      tanggal_lahir = formatDateIdLong(pasienData.tanggal_lahir || '');
    } else {
      // Populate patient fields from the form inputs (create/edit page)
      nama = $('#pasien-form').find('#nama').val() || '';
      alamat = $('#pasien-form').find('#alamat').val() || '';
      nik = $('#pasien-form').find('#nik').val() || '';
      no_hp = $('#pasien-form').find('#no_hp').val() || $('#pasien-form').find('#no_hp2').val() || '';
      tanggal_lahir = formatDateIdLong($('#pasien-form').find('#tanggal_lahir').val() || '');
    }

    $('#ic_nama').text(nama);
    $('#ic_alamat').text(alamat);
    $('#ic_nik').text(nik);
    $('#ic_no_hp').text(no_hp);
    $('#ic_tanggal_lahir').text(tanggal_lahir);
    // NO. RM (use pasien id when available)
    $('#ic_no_rm').text(pasienData.id || '');
  });

  // On window resize, only resize if modal is open/visible
  window.addEventListener('resize', function () {
    if ($('#icModal').is(':visible')) {
      resizeCanvas();
    }
  });

  document.getElementById('clear-signature').addEventListener('click', function () {
    if (signaturePad) signaturePad.clear();
  });

  document.getElementById('save-signature').addEventListener('click', function () {
    if (!signaturePad || signaturePad.isEmpty()) {
      alert('Silakan tanda tangani dulu sebelum menyimpan.');
      return;
    }

    var dataUrl = signaturePad.toDataURL('image/png');
    var pasienData = $('#icModal').data('pasien') || {};
    if (pasienData.id) {
      // From index page: directly submit signature for existing patient
      $.ajax({
        url: '/erm/ic-pendaftaran/store',
        method: 'POST',
        data: {
          pasien_id: pasienData.id,
          signature: dataUrl
        },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (icResp) {
          if (icResp.success) {
            $('#icModal').modal('hide');
            Swal.fire({
              icon: 'success',
              title: 'Berhasil!',
              text: 'Persetujuan berhasil disimpan.',
              confirmButtonText: 'OK'
            }).then(() => {
              // Optionally refresh the datatable to reflect status
              if (window.$ && $('#pasiens-table').length) {
                $('#pasiens-table').DataTable().ajax.reload(null, false);
              }
            });
          } else {
            alert('Gagal membuat persetujuan: ' + (icResp.message || 'Unknown'));
          }
        },
        error: function (xhr2) {
          var msg = 'Gagal menyimpan persetujuan.';
          if (xhr2.responseJSON && xhr2.responseJSON.message) msg = xhr2.responseJSON.message;
          Swal.fire({ icon: 'error', title: 'Gagal!', text: msg });
        }
      });
      return;
    }

    // From create/edit page: keep legacy behavior (save patient then save IC)
    var form = $('#pasien-form');
    if (!form.length) {
      Swal.fire({ icon: 'error', title: 'Gagal!', text: 'Form pasien tidak ditemukan.' });
      return;
    }
    var action = form.attr('action');
    $('#terms').prop('checked', true);
    var fd = new FormData(form[0]);
    fd.append('signature', dataUrl);

    $.ajax({
      url: action,
      method: 'POST',
      data: fd,
      contentType: false,
      processData: false,
      dataType: 'json',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function (resp) {
        if (!(resp && resp.pasien && resp.pasien.id)) {
          Swal.fire({ icon: 'error', title: 'Gagal!', text: 'Gagal menyimpan pasien.' });
          return;
        }
        var pasienId = resp.pasien.id;
        $.ajax({
          url: '/erm/ic-pendaftaran/store',
          method: 'POST',
          data: { pasien_id: pasienId, signature: dataUrl },
          headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
          success: function (icResp) {
            if (icResp.success) {
              $('#consent_pdf_path').val(icResp.pdf_url);
              $('#terms').prop('checked', true);
              $('#icModal').modal('hide');
              Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Data pasien dan persetujuan berhasil disimpan.', confirmButtonText: 'OK' })
                .then(() => {
                  Swal.fire({ title: 'Buka kunjungan?', text: 'Apakah Anda ingin membuka form kunjungan?', icon: 'question', showCancelButton: true, confirmButtonText: 'Ya', cancelButtonText: 'Tidak' })
                    .then((result2) => {
                      if (result2.value) {
                        $('#modal-pasien-id').val(resp.pasien.id);
                        $('#modal-nama-pasien').val(resp.pasien.nama);
                        $('#modalKunjungan').modal('show');
                      } else {
                        location.reload();
                      }
                    });
                });
            } else {
              Swal.fire({ icon: 'error', title: 'Gagal!', text: icResp.message || 'Gagal membuat persetujuan.' });
            }
          },
          error: function () {
            Swal.fire({ icon: 'error', title: 'Gagal!', text: 'Gagal menyimpan persetujuan.' });
          }
        });
      },
      error: function (xhr) {
        let errors = xhr.responseJSON?.errors;
        let errorMsg = 'Terjadi kesalahan saat menyimpan data pasien.';
        if (errors) {
          errorMsg = Object.values(errors).map(err => `• ${err}`).join('\n');
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMsg = xhr.responseJSON.message;
        }
        Swal.fire({ icon: 'error', title: 'Gagal!', text: errorMsg });
      }
    });
  });
});
</script>
