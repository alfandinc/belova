{{-- Instagram accounts connected for "Update insight" --}}
<div class="modal fade wf-modal" id="igAccountsModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fab fa-instagram mr-2" style="color:#E4405F"></i>Akun Instagram untuk insight</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <p class="wf-muted small mb-3">
          Sistem membaca views, reach, likes, komentar, saves &amp; shares postingan yang sudah <strong>Published</strong> (link Instagram terisi)
          dari akun-akun di bawah. Tekan <strong>Update insight</strong> setiap pagi: angka hari itu tersimpan, jadi perkembangan per hari terlihat.
        </p>
        <div id="igAccountList" class="d-flex flex-column mb-3" style="gap:8px"></div>

        <div class="cp-workflow">
          <div class="font-weight-bold mb-2"><i class="fas fa-plus-circle mr-1"></i> Hubungkan akun</div>
          <ol class="small wf-muted pl-3 mb-2">
            <li>Buka <strong>developers.facebook.com</strong> → aplikasi Anda → <strong>Kasus penggunaan → Sesuaikan → Penyiapan API dengan login Instagram</strong>.</li>
            <li>Bagian <strong>Buat token akses</strong> → akun klinik → <strong>Buat token</strong> → salin.</li>
            <li>Tempel di bawah. Token disimpan terenkripsi dan diperpanjang otomatis.</li>
          </ol>
          <form id="igConnectForm" class="form-row align-items-end">
            <div class="form-group col-md-7 mb-2">
              <label for="ig_token" style="text-transform:none">Token akses</label>
              <input type="password" class="form-control" id="ig_token" autocomplete="off" placeholder="IGAA…" required>
            </div>
            <div class="form-group col-md-3 mb-2">
              <label for="ig_brand" style="text-transform:none">Klinik</label>
              <select class="form-control" id="ig_brand">
                <option value="">—</option>
                @foreach($brands as $b)<option value="{{ $b['value'] }}">{{ $b['label'] }}</option>@endforeach
              </select>
            </div>
            <div class="form-group col-md-2 mb-2">
              <button type="submit" class="btn btn-primary btn-block" id="igConnectBtn">Hubungkan</button>
            </div>
          </form>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
