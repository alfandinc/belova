@extends('layouts.erm.app')
@section('title', 'E-Resep Farmasi')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection
@section('content')

@include('erm.partials.eresep-theme')

@include('erm.partials.modal-alergipasien')
@include('erm.partials.modal-resephistory')
@include('erm.partials.modal-resepedukasi')

@include('erm.partials.modal-editnonracikan-farmasi')
@include('erm.partials.modal-paketracikan-farmasi')

<!-- Modal Edit Dosis Racikan Obat -->
<div class="modal fade" id="editDosisModal" tabindex="-1" role="dialog" aria-labelledby="editDosisModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editDosisModalLabel">Edit Dosis Obat Racikan</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="edit-dosis-form">
          <input type="hidden" id="edit-dosis-row-id">
          <div class="form-group">
            <label for="edit-dosis-value">Dosis</label>
            <input type="text" class="form-control" id="edit-dosis-value" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="save-dosis-btn">Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Etiket Biru -->
<div class="modal fade" id="etiketBiruModal" tabindex="-1" role="dialog" aria-labelledby="etiketBiruModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="etiketBiruModalLabel">Etiket Biru</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="etiket-biru-form">
          <input type="hidden" id="etiket-visitation-id" value="{{ $visitation->id }}">
          
          <div class="form-group">
            <label for="etiket-pasien">Pasien</label>
            <input type="text" class="form-control" id="etiket-pasien" value="{{ $pasien->nama }}" readonly>
          </div>
          
          <div class="form-group">
            <label for="etiket-obat">Obat</label>
            <select class="form-control select2-etiket-obat" id="etiket-obat" required>
              <option value="">Pilih Obat...</option>
            </select>
          </div>

                    <div class="form-group" id="etiket-racikan-name-group" style="display:none;">
                        <label for="etiket-racikan-name">Nama Etiket (untuk Racikan)</label>
                        <input type="text" class="form-control" id="etiket-racikan-name" placeholder="Contoh: Racikan 1 atau Racikan A">
                        <small class="form-text text-muted">Jika Anda memilih racikan, isi nama ini untuk mengganti teks yang dicetak di etiket biru.</small>
                    </div>
          
          <div class="form-group">
            <label for="etiket-expire">Tanggal Kedaluwarsa</label>
            <input type="date" class="form-control" id="etiket-expire" required>
          </div>
          
                    <div class="form-group">
                        <label>Waktu Pakai</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="etiket-pagi" name="pagi">
                            <label class="form-check-label" for="etiket-pagi">Pagi</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="etiket-siang" name="siang">
                            <label class="form-check-label" for="etiket-siang">Siang</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="etiket-sore" name="sore">
                            <label class="form-check-label" for="etiket-sore">Sore</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="etiket-malam" name="malam">
                            <label class="form-check-label" for="etiket-malam">Malam</label>
                        </div>
                    </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="print-etiket-biru-btn">Print Etiket Biru</button>
      </div>
    </div>
  </div>
</div>

<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">E-Resep Farmasi Pasien</h3>
        <input type="hidden" id="visitation_id" value="{{ $visitation->id }}">
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Farmasi</li>
                            <li class="breadcrumb-item active">E-Resep</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('erm.partials.card-identitaspasien')

    @include('erm.partials.form-theme')

    @php
        $gudangResepId = \App\Models\ERM\GudangMapping::getDefaultGudangId('resep');
        $stokClass = fn ($stok) => $stok < 10 ? 'stok-low' : ($stok < 100 ? 'stok-mid' : 'stok-ok');
    @endphp

    <div id="eresep-area" class="erm-form eresep-page" style="{{ \App\Support\ClinicTheme::cssVars('ef') }}">
        @if(!empty($isInvoiceLocked) && $isInvoiceLocked)
            <div class="eresep-lock" role="alert">
                <i class="fas fa-lock"></i>
                <div><strong>Resep dikunci.</strong> Invoice sudah ditransaksikan. Hanya aturan pakai yang masih bisa diubah (untuk cetak etiket).</div>
            </div>
        @endif

        {{-- Tools: cetak, riwayat, paket --}}
        <div class="eresep-toolbar">
            <span class="eresep-toolbar-label">Cetak</span>
            <button type="button" class="btn btn-erm-tool tone-blue btn-cetakresep"><i class="fas fa-print mr-1"></i>Resep</button>
            <button type="button" class="btn btn-erm-tool tone-teal btn-cetakedukasi"><i class="fas fa-book-medical mr-1"></i>Edukasi</button>
            <button type="button" class="btn btn-erm-tool tone-amber btn-cetaketiket"><i class="fas fa-tag mr-1"></i>Etiket</button>
            <button type="button" class="btn btn-erm-tool tone-indigo btn-cetaketiketbiru" data-toggle="modal" data-target="#etiketBiruModal"><i class="fas fa-tag mr-1"></i>Etiket Biru</button>
            <span class="eresep-toolbar-sep"></span>
            <span class="eresep-toolbar-label">Riwayat</span>
            <button type="button" class="btn btn-erm-tool tone-green btn-riwayat" data-url="{{ route('resep.historydokter', ['pasienId' => $pasien->id, 'current' => $visitation->id, 'target' => 'farmasi']) }}" data-title="Riwayat Resep Dokter" data-type="dokter">
                <i class="fas fa-user-md mr-1"></i>Dokter
            </button>
            <button type="button" class="btn btn-erm-tool tone-purple btn-riwayat" data-url="{{ route('resep.historyfarmasi', ['pasienId' => $pasien->id, 'current' => $visitation->id, 'target' => 'farmasi']) }}" data-title="Riwayat Resep Farmasi" data-type="farmasi">
                <i class="fas fa-clinic-medical mr-1"></i>Farmasi
            </button>
            <span class="eresep-toolbar-sep"></span>
            <button type="button" id="paket-racikan" class="btn btn-erm-tool tone-orange"><i class="fas fa-box-open mr-1"></i>Paket Racikan</button>
        </div>

        {{-- 1. Catatan dokter --}}
        <section class="erm-section">
            <div class="erm-section-head">
                <span class="erm-section-num">1</span>
                <h4 class="erm-section-title">Catatan Dokter<span class="erm-section-sub">Ditulis dokter di E-Resep, baca sebelum menyiapkan obat</span></h4>
            </div>
            @if(filled($catatan_resep ?? null))
                <div class="eresep-note">{{ $catatan_resep }}</div>
            @else
                <div class="eresep-note is-empty">Tidak ada catatan dari dokter.</div>
            @endif
            @if (!$nonRacikans->count() && !$racikans->count())
                <div class="eresep-empty-racikan" id="empty-resep-message">
                    <i class="fas fa-info-circle mr-1"></i>Belum ada resep farmasi. Tambahkan obat di bawah, atau salin dari <strong>Riwayat Dokter</strong>.
                </div>
            @endif
        </section>

        <div id="resep-wrapper">
            {{-- 2. Non racikan --}}
            <section class="erm-section">
                <div class="erm-section-head">
                    <span class="erm-section-num">2</span>
                    <h4 class="erm-section-title">Resep Non Racikan<span class="erm-section-sub">Obat jadi; stok dari gudang resep</span></h4>
                </div>
                <div class="row add-obat-row eresep-add-row">
                    <div class="col-lg-4 col-md-12">
                        <div class="form-group">
                            <label for="obat_id">Nama Obat</label>
                            <select class="form-control select2-obat" name="obat_id" id="obat_id">
                                <option value="">Cari nama obat / zat aktif...</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-1 col-md-2 col-6">
                        <div class="form-group">
                            <label for="jumlah">Jumlah</label>
                            <input type="number" id="jumlah" class="form-control" placeholder="0" min="1" step="1">
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="form-group">
                            <label for="aturan_pakai">Aturan Pakai</label>
                            {{-- Free text with "sering dipakai" recommendations (erm.partials.aturan-pakai-builder) --}}
                            <input type="text" id="aturan_pakai" name="aturan_pakai" class="form-control" placeholder="mis. 2 X SEHARI 1 TABLET">
                        </div>
                    </div>
                    <div class="col-lg-1 col-md-2 col-6">
                        <div class="form-group">
                            <label for="diskon">Disc (%)</label>
                            <input type="number" class="form-control" id="diskon" placeholder="0" min="0" max="100">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-2">
                        <div class="form-group">
                            <label class="d-none d-md-block">&nbsp;</label>
                            <button type="button" id="tambah-resep" class="btn btn-erm-accent btn-block"><i class="fas fa-plus mr-1"></i>Tambah</button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered eresep-table">
                        <thead>
                            <tr>
                                <th>Nama Obat</th>
                                <th class="text-center">Jumlah</th>
                                <th class="text-right">Harga</th>
                                <th class="text-center">Disc</th>
                                <th class="text-center">Stok</th>
                                <th>Aturan Pakai</th>
                                <th class="eresep-col-aksi">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="resep-table-body">
                            @forelse ($nonRacikans as $resep)
                                @php
                                    $stokGudang = ($gudangResepId && $resep->obat) ? (int) $resep->obat->getStokByGudang($gudangResepId) : 0;
                                @endphp
                                <tr data-id="{{ $resep->id }}" data-obat-id="{{ $resep->obat_id }}">
                                    <td class="font-weight-bold">{{ $resep->obat->nama ?? '-' }}</td>
                                    <td class="text-center">{{ $resep->jumlah }}</td>
                                    <td class="text-right">Rp. {{ $resep->harga ?? $resep->obat->harga_nonfornas ?? 0 }}</td>
                                    <td class="text-center">{{ $resep->diskon ?? '0'}} %</td>
                                    <td class="text-center {{ $stokClass($stokGudang) }}">{{ $stokGudang }}</td>
                                    <td>{{ $resep->aturan_pakai }}</td>
                                    <td class="eresep-col-aksi">
                                        <button type="button" class="btn btn-sm btn-erm-soft edit" data-id="{{ $resep->id }}">Edit</button>
                                        <button type="button" class="btn btn-sm btn-erm-danger hapus" data-id="{{ $resep->id }}">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr class="no-data">
                                    <td colspan="7" class="text-center text-muted">Belum ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- 3. Racikan --}}
            <section class="erm-section">
                <div class="erm-section-head">
                    <span class="erm-section-num">3</span>
                    <h4 class="erm-section-title">Resep Racikan<span class="erm-section-sub">Stok dikurangi = dosis racik x bungkus / dosis obat</span></h4>
                    <button type="button" id="tambah-racikan" class="btn btn-sm btn-erm-accent ml-auto"><i class="fas fa-plus mr-1"></i>Tambah Racikan</button>
                </div>

                <div id="racikan-container">
                    @foreach ($racikans as $ke => $items)
                    <div class="racikan-card" data-racikan-ke="{{ $ke }}">
                        <div class="racikan-card-head">
                            @php $racikanNama = $racikanNames[$ke] ?? null; @endphp
                            <h5><strong class="racikan-title">{{ $racikanNama ?: 'Racikan ' . $ke }}</strong>@if($racikanNama)<span class="racikan-no">Racikan {{ $ke }}</span>@endif <span class="racikan-harga-detail"></span></h5>
                            <div>
                                <button type="button" class="btn btn-sm btn-erm-soft edit-racikan">Edit Racikan</button>
                                <button type="button" class="btn btn-sm btn-erm-danger hapus-racikan">Hapus Racikan</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                        <table class="table table-bordered eresep-table">
                            <thead>
                                <tr>
                                    <th>Nama Obat</th>
                                    <th>Dosis</th>
                                    <th class="text-center">Stok</th>
                                    <th class="text-center">Stok Dikurangi</th>
                                    <th class="eresep-col-aksi">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="resep-table-body">
                                @foreach ($items as $resep)
                                    @php
                                        $stokGudang = ($gudangResepId && $resep->obat) ? (int) $resep->obat->getStokByGudang($gudangResepId) : 0;
                                        // compute stok dikurangi = (dosis_input * bungkus) / dosis_obat
                                        $prescribedDosis = 0;
                                        $baseDosis = 0;
                                        if (preg_match('/(\d+(?:[.,]\d+)?)/', $resep->dosis ?? '', $m)) {
                                            $prescribedDosis = (float) str_replace(',', '.', $m[1]);
                                        }
                                        if (!empty($resep->obat->dosis) && preg_match('/(\d+(?:[.,]\d+)?)/', $resep->obat->dosis, $m2)) {
                                            $baseDosis = (float) str_replace(',', '.', $m2[1]);
                                        }
                                        $bungkusGroup = $items->first()->bungkus ?? 1;
                                        $stokDikurangi = ($baseDosis > 0) ? ($prescribedDosis * (float)$bungkusGroup) / $baseDosis : 0;
                                        $wadahNama = strtolower(trim(optional($items->first()->wadah)->nama ?? ''));
                                        $isFractionalWadah = in_array($wadahNama, ['botol', 'cream / lotion'], true);
                                        if (!is_numeric($stokDikurangi)) {
                                            $stokDikurangiDisplay = '0';
                                        } elseif ($isFractionalWadah) {
                                            $stokDikurangiDisplay = rtrim(rtrim(number_format($stokDikurangi, 3, '.', ''), '0'), '.');
                                        } else {
                                            $stokDikurangiDisplay = (string) ceil($stokDikurangi);
                                        }
                                    @endphp
                                    <tr data-id="{{ $resep->id }}" data-obat-id="{{ $resep->obat_id }}" data-dosis="{{ $resep->dosis }}" data-base-dosis="{{ $baseDosis }}" data-jumlah="{{ $resep->jumlah }}">
                                        <td data-id="{{ $resep->obat_id }}" class="font-weight-bold">{{ $resep->obat->nama ?? '-' }}</td>
                                        <td>{{ $resep->dosis }}</td>
                                        <td class="text-center {{ $stokClass($stokGudang) }}">{{ $stokGudang }}</td>
                                        <td class="text-center stok-dikurangi {{ $stokDikurangi > $stokGudang ? 'stok-low' : '' }}">{{ $stokDikurangiDisplay }}</td>
                                        <td class="eresep-col-aksi">
                                            <button type="button" class="btn btn-sm btn-erm-soft edit-obat" disabled>Edit</button>
                                            <button type="button" class="btn btn-sm btn-erm-danger hapus-obat" disabled>Hapus</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <label>Wadah</label>
                                <select class="form-control select2-wadah-racikan wadah" name="wadah_id" disabled>
                                <option value="{{ $items->first()?->wadah?->id ?? '' }}">
                                    {{ $items->first()?->wadah?->nama ?? 'Pilih Wadah' }}
                                </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Bungkus</label>
                                <input type="number" class="form-control jumlah_bungkus" min="1" value="{{ $items->first()->bungkus }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label>Aturan Pakai</label>
                                <input type="text" class="form-control aturan_pakai" value="{{ $items->first()->aturan_pakai }}" disabled>
                            </div>
                        </div>
                        <button type="button" class="btn btn-block racikan-save-btn tambah-resepracikan" disabled>Sudah Disimpan</button>
                        <button type="button" class="btn btn-block racikan-save-btn is-primary update-resepracikan d-none">Update Racikan</button>
                    </div>
                    @endforeach
                </div>
                <div class="eresep-empty-racikan {{ count($racikans) ? 'd-none' : '' }}">Belum ada racikan. Klik <strong>Tambah Racikan</strong> atau gunakan <strong>Paket Racikan</strong>.</div>
            </section>
        </div>

        <div class="erm-actionbar">
            <div class="eresep-total">
                <span class="eresep-total-label">Total Harga</span>
                <span class="eresep-total-value">Rp <span id="total-harga"><strong>0</strong></span></span>
            </div>
            <button type="button" id="submit-all" class="btn btn-success"><i class="fas fa-paper-plane mr-1"></i>Submit Resep</button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@include('erm.partials.aturan-pakai-builder')
@include('erm.partials.obat-alternatif')

<script>
    let racikanCount = {{ $lastRacikanKe ?? 0 }};
    // Racikan card title: the paket name when the racikan is a paket (number as a small tag), else "Racikan N"
    function racikanTitleHtml(ke, nama) {
        const esc = v => $('<div>').text(v == null ? '' : String(v)).html();
        return nama
            ? `<strong class="racikan-title">${esc(nama)}</strong><span class="racikan-no">Racikan ${ke}</span>`
            : `<strong class="racikan-title">Racikan ${ke}</strong>`;
    }

    // nama: undefined keeps the current name (e.g. only the number changed), null / '' = custom racikan
    function setRacikanTitle(card, nama) {
        if (nama === undefined) {
            nama = card.find('.racikan-no').length ? card.find('.racikan-title').text() : null;
        }
        const h5 = card.find('.racikan-card-head h5').first();
        h5.find('.racikan-title, .racikan-no').remove();
        h5.prepend(racikanTitleHtml(card.attr('data-racikan-ke'), nama));
    }

    // The server renumbers a racikan whose number was taken meanwhile; make the card follow it
    function adoptRacikanKe(card, racikanKe) {
        racikanKe = parseInt(racikanKe, 10);
        if (!racikanKe || racikanKe === parseInt(card.data('racikan-ke'), 10)) return;
        card.attr('data-racikan-ke', racikanKe).data('racikan-ke', racikanKe);
        setRacikanTitle(card);
        racikanCount = Math.max(racikanCount, racikanKe);
    }
    let pendingResepMutationCount = 0;

    function stokClass(stok) {
        stok = parseInt(stok, 10) || 0;
        return stok < 10 ? 'stok-low' : (stok < 100 ? 'stok-mid' : 'stok-ok');
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function escapeAttr(value) {
        return String(value == null ? '' : value).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }

    function ajaxErrorMessage(xhr, fallback) {
        const json = xhr && xhr.responseJSON;
        if (json && json.errors) return Object.values(json.errors).flat().join('\n');
        return (json && json.message) || fallback;
    }

    function toggleEmptyRacikan() {
        $('.eresep-empty-racikan').not('#empty-resep-message').toggleClass('d-none', $('#racikan-container .racikan-card').length > 0);
    }

    // Obat search: visitation_id makes obat.search drop obat the patient is allergic to
    function initFarmasiObatSelect($select, extra) {
        $select.select2(Object.assign({
            width: '100%',
            placeholder: 'Cari nama obat / zat aktif...',
            minimumInputLength: 3,
            ajax: {
                url: '{{ route("obat.search") }}',
                dataType: 'json',
                delay: 150,
                data: function (params) {
                    return { q: params.term, visitation_id: $('#visitation_id').val() };
                },
                processResults: function (data) {
                    const items = Array.isArray(data.results) ? data.results : (Array.isArray(data) ? data : []);
                    return { results: items.map(mapFarmasiObatItem) };
                },
                cache: true
            },
            escapeMarkup: function (markup) { return markup; },
            templateResult: renderFarmasiObatOption,
            templateSelection: function (item) { return escapeHtml(item.text || ''); }
        }, extra || {}));
    }

    // obat.search / obat.alternatif row -> Select2 item used by the farmasi handlers
    function mapFarmasiObatItem(item) {
        return {
            id: item.id,
            text: item.text || (item.nama + (item.harga_nonfornas ? ' - ' + item.harga_nonfornas : '')),
            nama: item.nama,
            dosis: item.dosis,
            satuan: item.satuan,
            harga: item.harga_nonfornas,
            harga_nonfornas: item.harga_nonfornas,
            stok: item.stok,
            stok_gudang: typeof item.stok_gudang !== 'undefined' ? item.stok_gudang : item.stok
        };
    }

    // Option row: name + harga + stok
    function renderFarmasiObatOption(item) {
        if (!item.id) return escapeHtml(item.text || '');
        const harga = Number(item.harga_nonfornas);
        const hargaText = Number.isFinite(harga) && harga > 0 ? 'Rp ' + new Intl.NumberFormat('id-ID').format(harga) : 'Harga jual belum diatur';
        const stok = parseInt(item.stok_gudang !== undefined ? item.stok_gudang : item.stok, 10) || 0;
        return `${escapeHtml(item.text)} - ${hargaText} · Stok ${stok}`;
    }

    function initWadahSelect($select) {
        $select.select2({
            placeholder: 'Pilih wadah...',
            ajax: {
                url: '{{ route("wadah.search") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term, visitation_id: $('#visitation_id').val() }; },
                processResults: function (data) { return { results: (data || []).map(item => ({ id: item.id, text: item.text })) }; },
                cache: true
            }
        });
    }

    // One obat row of a racikan table (stok dikurangi is filled in by updateTotalPrice)
    function racikanRowHtml(o) {
        const stok = parseInt(o.stok, 10) || 0;
        return `
            <tr ${o.id ? `data-id="${o.id}"` : ''} data-obat-id="${o.obatId}" data-dosis="${escapeAttr(o.dosis)}" data-base-dosis="${escapeAttr(o.baseDosis || '')}" data-jumlah="${o.jumlah || 1}">
                <td data-id="${o.obatId}" class="font-weight-bold">${escapeHtml(o.nama || '-')}</td>
                <td>${escapeHtml(o.dosisLabel || o.dosis)}</td>
                <td class="text-center ${stokClass(stok)}">${stok}</td>
                <td class="text-center stok-dikurangi">-</td>
                <td class="eresep-col-aksi">
                    <button type="button" class="btn btn-sm btn-erm-soft edit-obat"${o.editable ? '' : ' disabled'}>Edit</button>
                    <button type="button" class="btn btn-sm btn-erm-danger hapus-obat"${o.editable ? '' : ' disabled'}>Hapus</button>
                </td>
            </tr>`;
    }

    // A racikan card; saved = already in the database (fields locked until "Edit Racikan")
    function racikanCardHtml(o) {
        const ke = o.ke;
        const dis = o.saved ? ' disabled' : '';
        return `
            <div class="racikan-card" data-racikan-ke="${ke}">
                <div class="racikan-card-head">
                    <h5>${racikanTitleHtml(ke, o.nama)} <span class="racikan-harga-detail"></span></h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-erm-soft edit-racikan${o.saved ? '' : ' d-none'}">Edit Racikan</button>
                        <button type="button" class="btn btn-sm btn-erm-danger hapus-racikan">Hapus Racikan</button>
                    </div>
                </div>
                ${o.saved ? '' : racikanAddRowHtml()}
                <div class="table-responsive">
                    <table class="table table-bordered eresep-table">
                        <thead>
                            <tr>
                                <th>Nama Obat</th><th>Dosis</th><th class="text-center">Stok</th>
                                <th class="text-center">Stok Dikurangi</th><th class="eresep-col-aksi">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="resep-table-body">
                            ${o.rows || '<tr class="no-data"><td colspan="5" class="text-center text-muted">Belum ada obat</td></tr>'}
                        </tbody>
                    </table>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <label>Wadah</label>
                        <select class="form-control select2-wadah-racikan wadah" name="wadah_id"${dis}>
                            ${o.wadahId ? `<option value="${escapeAttr(o.wadahId)}" selected>${escapeHtml(o.wadahNama || '')}</option>` : '<option value="">Pilih wadah...</option>'}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Bungkus</label>
                        <input type="number" class="form-control bungkus" min="1" value="${escapeAttr(o.bungkus)}"${dis}>
                    </div>
                    <div class="col-md-6">
                        <label>Aturan Pakai</label>
                        <input type="text" class="form-control aturan_pakai" placeholder="Ketik aturan pakai" value="${escapeAttr(o.aturan)}"${dis}>
                    </div>
                </div>
                <button type="button" class="btn btn-block racikan-save-btn tambah-resepracikan"${dis}>${o.saved ? 'Sudah Disimpan' : 'Simpan Racikan ' + ke}</button>
                <button type="button" class="btn btn-block racikan-save-btn is-primary update-resepracikan d-none">Update Racikan</button>
            </div>`;
    }

    function racikanAddRowHtml() {
        return `
                <div class="row add-obat-row">
                    <div class="col-md-6"><div class="form-group">
                        <label>Nama Obat</label>
                        <select class="form-control select2-obat-racikan" name="obat_id"><option value="">Cari nama obat / zat aktif...</option></select>
                    </div></div>
                    <div class="col-md-2 col-6"><div class="form-group">
                        <label>Dosis</label>
                        <input type="number" class="form-control dosis_input" min="0" step="0.01">
                    </div></div>
                    <div class="col-md-2 col-6"><div class="form-group">
                        <label>Satuan Dosis</label>
                        <select class="form-control mode_dosis"><option value="normal">Normal</option><option value="tablet">Tablet</option></select>
                    </div></div>
                    <div class="col-md-2"><div class="form-group">
                        <label class="d-none d-md-block">&nbsp;</label>
                        <button type="button" class="btn btn-erm-accent btn-block tambah-obat">Tambah</button>
                    </div></div>
                </div>`;
    }

    let IS_INVOICE_LOCKED = @json($isInvoiceLocked ?? false);
    const INVOICE_PAYMENT_METHOD = @json($invoicePaymentMethod ?? null);

    function getSubmitButton() {
        return $('#submit-all');
    }

    function hasUnsavedRacikanChanges() {
        let hasUnsaved = false;

        $('#racikan-container .racikan-card').each(function () {
            const card = $(this);
            const hasRows = card.find('.resep-table-body tr').not('.no-data').length > 0;
            const needsCreate = card.find('.tambah-resepracikan:visible').not(':disabled').length > 0;
            const needsUpdate = card.find('.update-resepracikan:visible').not(':disabled').length > 0;

            if (hasRows && (needsCreate || needsUpdate)) {
                hasUnsaved = true;
                return false;
            }
        });

        return hasUnsaved;
    }

    function getSubmitBlockReason() {
        if (pendingResepMutationCount > 0) {
            return 'Tunggu hingga penyimpanan racikan selesai, lalu submit ulang.';
        }

        if (hasUnsavedRacikanChanges()) {
            return 'Masih ada racikan yang belum disimpan atau belum di-update.';
        }

        return '';
    }

    function refreshSubmitButtonState() {
        const $btn = getSubmitButton();
        if (!$btn.length) return;

        const isProcessing = $btn.data('processing') === true;
        const blockReason = getSubmitBlockReason();

        if (isProcessing) {
            return;
        }

        $btn.prop('disabled', !!blockReason);
        $btn.attr('title', blockReason || '');
    }

    function beginResepMutation() {
        pendingResepMutationCount += 1;
        refreshSubmitButtonState();
    }

    function endResepMutation() {
        pendingResepMutationCount = Math.max(0, pendingResepMutationCount - 1);
        refreshSubmitButtonState();
    }

    function normalizeWadahName(value) {
        return String(value || '')
            .trim()
            .replace(/\s+/g, ' ')
            .replace(/\s+\d+(?:[.,]\d+)?$/, '')
            .toLowerCase();
    }

    function cardUsesFractionalStockReduction(card) {
        const wadahSelect = card.find('.wadah, .select2-wadah-racikan').first();
        let wadahName = '';

        if (wadahSelect.length) {
            wadahName = wadahSelect.find('option:selected').text() || '';
            if (!wadahName && wadahSelect.hasClass('select2-hidden-accessible')) {
                try {
                    const selectedData = wadahSelect.select2('data') || [];
                    wadahName = selectedData[0] && (selectedData[0].text || selectedData[0].nama) ? (selectedData[0].text || selectedData[0].nama) : '';
                } catch (e) {
                    // Ignore select2 access errors.
                }
            }
        }

        const normalizedWadahName = normalizeWadahName(wadahName);

        return normalizedWadahName === 'botol'
            || normalizedWadahName.startsWith('botol ')
            || normalizedWadahName === 'cream / lotion'
            || normalizedWadahName.startsWith('cream / lotion ');
    }

    function formatStockReductionValue(value, allowFractional) {
        const numericValue = Number(value);
        if (!Number.isFinite(numericValue) || numericValue <= 0) {
            return '0';
        }

        if (allowFractional) {
            return numericValue.toFixed(3).replace(/\.0+$/, '').replace(/(\.\d*[1-9])0+$/, '$1');
        }

        return String(Math.ceil(numericValue));
    }

    function extractDoseNumber(value) {
        if (value === null || value === undefined) return 0;
        if (typeof value === 'number') return Number.isFinite(value) ? value : 0;

        const match = String(value).match(/(\d+(?:[.,]\d+)?)/);
        return match ? parseFloat(match[1].replace(',', '.')) || 0 : 0;
    }

    function getCurrentRacikanBungkus(card) {
        return extractDoseNumber(card.find('.jumlah_bungkus').val() || card.find('.bungkus').val()) || 1;
    }

    function recalculateRacikanRowStock(row, options = {}) {
        const card = options.card || row.closest('.racikan-card');
        const dosisRacik = extractDoseNumber(options.dosis ?? row.attr('data-dosis') ?? row.find('td').eq(1).text());
        let baseDosis = extractDoseNumber(options.baseDosis ?? row.attr('data-base-dosis'));

        if (baseDosis <= 0) {
            const obatId = row.attr('data-obat-id') || row.find('td').eq(0).attr('data-id') || null;
            const fallbackInfo = options.priceMap && obatId ? options.priceMap[obatId] : null;
            baseDosis = extractDoseNumber(fallbackInfo ? fallbackInfo.dosis : null);
            if (baseDosis > 0) {
                row.attr('data-base-dosis', baseDosis);
            }
        }

        const currentBungkus = getCurrentRacikanBungkus(card);
        const allowFractional = options.allowFractional ?? cardUsesFractionalStockReduction(card);
        let stokDikurangi = 0;
        if (baseDosis > 0 && dosisRacik > 0) {
            const rawReduction = (dosisRacik * currentBungkus) / baseDosis;
            stokDikurangi = allowFractional ? rawReduction : Math.ceil(rawReduction);
        }

        const rowStokCell = row.find('.stok-dikurangi');
        if (rowStokCell.length) {
            const stokAvailable = extractDoseNumber(row.find('td').eq(2).text());
            rowStokCell.text(formatStockReductionValue(stokDikurangi, allowFractional));
            rowStokCell.toggleClass('stok-low', stokDikurangi > stokAvailable);
        }

        return {
            dosisRacik,
            baseDosis,
            currentBungkus,
            stokDikurangi,
        };
    }

    function showInvoiceLockedMessage() {
        const msg = 'Resep dikunci karena Invoice sudah ditransaksikan.';
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Tidak bisa mengubah resep',
                text: msg
            });
        } else {
            alert(msg);
        }
    }

    function applyInvoiceLock() {
        if (!IS_INVOICE_LOCKED) return;

        const root = $('.container-fluid').first();
        const lockScope = $('#resep-wrapper').length ? $('#resep-wrapper') : (root.find('.card-body').first().length ? root.find('.card-body').first() : root);

        // Disable editable form controls inside resep area
        lockScope.find('input, select, textarea').prop('disabled', true);
        lockScope.find('select.select2-hidden-accessible').each(function(){
            $(this).prop('disabled', true).trigger('change.select2');
        });

        // Disable only mutation actions (keep print/history usable)
        // Exception: when locked, allow editing ONLY aturan pakai via:
        // - non racikan: .edit modal (aturan only)
        // - racikan: .edit-racikan then .update-resepracikan (aturan only)
        const mutationSelectors = [
            '#tambah-resep',
            '#tambah-racikan',
            '#submit-all',
            '#paket-racikan',
            '#save-dosis-btn',
            '.hapus',
            '.hapus-racikan',
            '.hapus-obat',
            '.tambah-obat',
            '.tambah-resepracikan',
            '.edit-obat',
            '.btn-copy-resep',
            '.copy-paket',
            '.edit-paket',
            '.delete-paket'
        ].join(',');

        root.find(mutationSelectors).each(function(){
            const $el = $(this);
            if ($el.is('a')) {
                $el.addClass('disabled').attr('aria-disabled', 'true');
            } else {
                $el.prop('disabled', true).addClass('disabled');
            }
        });

        // Block mutation form submits only
        $(document).on('submit.invoiceLock', '#edit-dosis-form, #formPaketRacikanFarmasi', function(e){
            e.preventDefault();
            showInvoiceLockedMessage();
        });

        // Block mutation clicks (in case some elements are links)
        $(document).on('click.invoiceLock', mutationSelectors, function(e){
            e.preventDefault();
            showInvoiceLockedMessage();
        });
    }

    function startInvoiceLockPolling() {
        if (IS_INVOICE_LOCKED) return;

        const url = "{{ route('erm.eresep.invoice-lock-status', $visitation->id) }}";
        let isChecking = false;

        function checkOnce() {
            if (IS_INVOICE_LOCKED || isChecking) return;
            isChecking = true;

            $.get(url)
                .done(function (res) {
                    if (res && res.is_locked) {
                        IS_INVOICE_LOCKED = true;
                        showInvoiceLockedMessage();
                        applyInvoiceLock();
                    }
                })
                .always(function () {
                    isChecking = false;
                });
        }

        checkOnce();
        setInterval(checkOnce, 5000);
    }

    $(document).ready(function () {
        // Handlers outside this ready block (edit racikan, fetchFarmasiResep) use it too
        window.updateTotalPrice = updateTotalPrice;
        $('.select2').select2({ width: '100%' });
        initFarmasiObatSelect($('.select2-obat'));
        initWadahSelect($('.select2-wadah-racikan'));

        // ALTERNATIF: after an obat is chosen, offer obat with kandungan in common (separate request, search stays fast)
        ObatAlternatif.bind('#eresep-area #obat_id, #eresep-area .select2-obat-racikan', {
            params: () => ({ visitation_id: $('#visitation_id').val() }),
            mapItem: mapFarmasiObatItem
        });

        // ATURAN PAKAI: free text with "sering dipakai" recommendations from resep history
        // (non-racikan, edit modal, every racikan card and the paket racikan forms)
        const apNonRacikan = AturanPakai.mount('#aturan_pakai');
        AturanPakai.mount('#edit-aturan');
        AturanPakai.autoMount('#racikan-container', { racikan: true });
        AturanPakai.autoMount('#paketRacikanModalFarmasi', { racikan: true });
        AturanPakai.autoMount('#gunakanPaketModalFarmasi', { racikan: true });
        $('#obat_id').on('select2:select', function (e) { apNonRacikan.setObat(e.params.data.id); });
        $('#obat_id').on('select2:clear', function () { apNonRacikan.setObat(null); });

        // STORE NON RACIKAN
        $('#tambah-resep').on('click', function () {
            let obatId = $('#obat_id').val();
            let obatText = $('#obat_id option:selected').text();
            let jumlah = parseInt($('#jumlah').val() || 0, 10);
            // Get the currently selected select2 data object
            let selectedData = $('#obat_id').select2('data')[0] || {};
            // harga may be on selectedData
            let harga = selectedData && selectedData.harga ? parseFloat(selectedData.harga) : null;
            // stok may be provided as stok_gudang or stok on the select2 item
            let stokAvailable = typeof selectedData.stok_gudang !== 'undefined' ? parseInt(selectedData.stok_gudang || 0, 10) : (typeof selectedData.stok !== 'undefined' ? parseInt(selectedData.stok || 0, 10) : null);
            let aturanPakai = ($('#aturan_pakai').val() || '').trim();
            let diskon = $('#diskon').val() || 0;
            let visitationId = $('#visitation_id').val();  // Pastikan id yang digunakan sama

            if (!obatId || !jumlah || !aturanPakai) return Swal.fire('Peringatan', "Semua field wajib diisi.", "warning");
            if (jumlah < 1) return Swal.fire('Peringatan', 'Jumlah minimal 1.', 'warning');
            if (diskon < 0 || diskon > 100) return Swal.fire('Peringatan', 'Diskon harus antara 0 dan 100%.', 'warning');
            if (harga === null) return Swal.fire('Harga Belum Diatur', 'Obat ini belum punya harga jual. Atur harga jual di master obat dulu.', 'warning');
            const $btn = $(this);
            if ($btn.prop('disabled')) return;

            // If stok info is available, block adding when requested jumlah > stok
            if (stokAvailable !== null && !isNaN(stokAvailable) && jumlah > stokAvailable) {
                return Swal.fire('Stok Tidak Cukup', `Jumlah yang diminta (${jumlah}) lebih besar dari stok tersedia (${stokAvailable}). Silakan ubah jumlah atau periksa stok.`, 'error');
            }

            // If stok info not available or jumlah <= stokAvailable, proceed normally
            submitTambahResep({obatId, jumlah, harga, diskon, aturanPakai, visitationId});

            function submitTambahResep(payload) {
                $btn.prop('disabled', true);
                $.ajax({
                    url: "{{ route('resepfarmasi.nonracikan.store') }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        tipe: "nonracikan",
                        obat_id: payload.obatId,
                        jumlah: payload.jumlah,
                        harga: payload.harga,
                        diskon: payload.diskon,
                        aturan_pakai: payload.aturanPakai,
                        visitation_id: payload.visitationId
                    },
                    success: function (res) {
                        const d = res.data;
                        $('#resep-table-body .no-data').remove();
                        const stokGudang = d.obat.stok_gudang !== undefined ? parseInt(d.obat.stok_gudang, 10) : 0;
                        $('#resep-table-body').append(`
                            <tr data-id="${d.id}" data-obat-id="${d.obat.id}">
                                <td class="font-weight-bold">${escapeHtml(d.obat.nama)}</td>
                                <td class="text-center">${d.jumlah}</td>
                                <td class="text-right">Rp. ${d.harga}</td>
                                <td class="text-center">${d.diskon} %</td>
                                <td class="text-center ${stokClass(stokGudang)}">${stokGudang}</td>
                                <td>${escapeHtml(d.aturan_pakai)}</td>
                                <td class="eresep-col-aksi">
                                    <button type="button" class="btn btn-sm btn-erm-soft edit" data-id="${d.id}">Edit</button>
                                    <button type="button" class="btn btn-sm btn-erm-danger hapus" data-id="${d.id}">Hapus</button>
                                </td>
                            </tr>
                        `);
                        $('#empty-resep-message').hide();
                        updateTotalPrice();

                        // Clear the input fields
                        $('#obat_id').val(null).trigger('change');
                        $('#jumlah').val('');
                        $('#diskon').val('');
                        AturanPakai.get('#aturan_pakai').reset();
                    },
                    error: function (xhr) {
                        Swal.fire('Error', 'Gagal menambahkan resep: ' + ajaxErrorMessage(xhr, 'Unknown error'), 'error');
                    },
                    complete: function () {
                        $btn.prop('disabled', IS_INVOICE_LOCKED);
                    }
                });
            }
        });

        //DELETE NON RACIKAN
        $('#resep-table-body').on('click', '.hapus', function () {
            const row = $(this).closest('tr');
            const resepId = row.data('id');

            Swal.fire({
                title: 'Konfirmasi',
                text: 'Yakin ingin menghapus resep ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    deleteNonRacikan();
                }
            });
            
            function deleteNonRacikan() {
                $.ajax({
                    url: "{{ route('resepfarmasi.nonracikan.destroy', '') }}/" + resepId,
                    method: 'DELETE',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                success: function () {
                    row.remove();
                    if ($('#resep-table-body tr').length === 0) {
                        $('#resep-table-body').append(`<tr class="no-data"><td colspan="7" class="text-center text-muted">Belum ada data</td></tr>`);
                    }
                    updateTotalPrice();
                },
                error: function () {
                    Swal.fire('Error', 'Gagal menghapus resep. Coba lagi.', 'error');
                }
            });
            }
        });

        // Helper: robustly parse currency/formatted numbers to JS Number
        function parseCurrencyToNumber(input) {
            if (input === null || input === undefined) return 0;
            // If already a number, return it
            if (typeof input === 'number') return input;
            let s = ('' + input).trim();
            // Remove currency text like 'Rp' and whitespace
            s = s.replace(/Rp\.?\s*/ig, '').trim();

            // If contains both dot and comma, assume dot thousands and comma decimal: 1.234,56
            if (s.indexOf('.') !== -1 && s.indexOf(',') !== -1) {
                s = s.replace(/\./g, '').replace(/,/g, '.');
            } else if (s.indexOf('.') !== -1 && s.indexOf(',') === -1) {
                // Only dot present. Could be decimal or thousands.
                const dotCount = (s.match(/\./g) || []).length;
                if (dotCount > 1) {
                    // multiple dots: treat as thousands separators
                    s = s.replace(/\./g, '');
                } else {
                    // single dot: decide based on digits after dot
                    const after = s.split('.').pop();
                    if (after.length === 3) {
                        // e.g. 1.000 -> thousands separator
                        s = s.replace(/\./g, '');
                    } else {
                        // treat as decimal separator (leave dot)
                    }
                }
            } else if (s.indexOf(',') !== -1 && s.indexOf('.') === -1) {
                // Only comma present -> decimal separator in id locale
                s = s.replace(/,/g, '.');
            }

            // Strip any remaining non-numeric except dot and minus
            s = s.replace(/[^0-9.\-]/g, '');
            const n = parseFloat(s);
            return isNaN(n) ? 0 : n;
        }

        // UPDATE TOTAL HARGA (debounced: typing in Bungkus used to fire one JSON request per key stroke)
        let totalPriceTimer = null;
        let totalPriceSeq = 0;
        function updateTotalPrice() {
            clearTimeout(totalPriceTimer);
            totalPriceTimer = setTimeout(computeTotalPrice, 200);
        }

        function computeTotalPrice() {
            const seq = ++totalPriceSeq;
            let total = 0;
            // Sum Non-Racikan (table layout: td[0]=nama, td[1]=jumlah, td[2]=harga, td[3]=diskon ...)
            const nonRacikanBreakdown = [];
            $('#resep-table-body tr').each(function () {
                // Skip placeholder rows
                if ($(this).hasClass('no-data')) return;
                // Try to extract price and quantity robustly
                const jumlahText = $(this).find('td').eq(1).text();
                const jumlah = parseFloat((jumlahText || '').replace(/[^\d.,-]/g, '').replace(',', '.').trim()) || 0;
                // harga may be in different formats (with Rp. or plain number)
                let hargaRaw = $(this).find('td').eq(2).text() || '';
                const harga = parseCurrencyToNumber(hargaRaw) || 0;
                let diskonRaw = $(this).find('td').eq(3).text() || '';
                diskonRaw = diskonRaw.toString().replace('%', '').trim();
                const diskon = parseFloat(diskonRaw) || 0;

                const discountedPrice = harga * jumlah * (1 - diskon / 100);
                total += discountedPrice;
                nonRacikanBreakdown.push({
                    id: $(this).data('id') || null,
                    obatId: $(this).data('obat-id') || null,
                    jumlahText,
                    jumlah,
                    hargaRaw: $(this).find('td').eq(2).text(),
                    harga,
                    diskonRaw: $(this).find('td').eq(3).text(),
                    diskon,
                    discountedPrice
                });
            });


            // Collect unique obat ids present in all racikan cards
            const obatIds = new Set();
            $('#racikan-container .racikan-card').each(function () {
                $(this).find('.resep-table-body tr').each(function () {
                    if ($(this).hasClass('no-data') || $(this).hasClass('obat-deleted')) return;
                    const obatId = $(this).data('obat-id') || $(this).find('td[data-id]').data('id');
                    if (obatId) obatIds.add(obatId);
                });
            });

            // If there are no racikan obat, we can finish immediately
            if (obatIds.size === 0) {
                $('#total-harga').html('<strong>' + new Intl.NumberFormat('id-ID').format(total) + '</strong>');
                return;
            }

            // Fetch visitation JSON which contains obat details (harga_nonfornas and dosis)
            const visitationId = $('#visitation_id').val();
            $.get(`/erm/eresepfarmasi/${visitationId}/json`, function (res) {
                if (seq !== totalPriceSeq) return; // a newer calculation is running
                try {
                    // Build a lookup map obat_id -> { harga_nonfornas, dosis }
                    const priceMap = {};
                    if (Array.isArray(res.nonRacikans)) {
                        res.nonRacikans.forEach(item => {
                            if (item.obat && item.obat.id) {
                                priceMap[item.obat.id] = {
                                    harga: parseFloat(item.obat.harga_nonfornas) || 0,
                                    dosis: item.obat.dosis || ''
                                };
                            }
                        });
                    }
                    if (res.racikans) {
                        Object.values(res.racikans).forEach(group => {
                            group.forEach(item => {
                                if (item.obat && item.obat.id) {
                                    priceMap[item.obat.id] = {
                                        harga: parseFloat(item.obat.harga_nonfornas) || (priceMap[item.obat.id]?.harga || 0),
                                        dosis: item.obat.dosis || (priceMap[item.obat.id]?.dosis || '')
                                    };
                                }
                            });
                        });
                    }

                    // Now iterate racikan cards and compute racikan totals
                    const racikanBreakdown = [];
                    $('#racikan-container .racikan-card').each(function () {
                        let racikanTotal = 0;
                        const card = $(this);
                        const cardItems = [];
                        card.find('.resep-table-body tr').each(function () {
                            if ($(this).hasClass('no-data') || $(this).hasClass('obat-deleted')) return;
                            const row = $(this);
                            const obatId = row.data('obat-id') || row.find('td[data-id]').data('id') || null;
                            const dosisStr = (row.data('dosis') || row.find('td').eq(1).text() || '').toString();
                            const dosisRacik = extractDoseNumber(dosisStr);

                            const obatInfo = priceMap[obatId] || { harga: 0, dosis: '' };
                            const baseDosisStr = row.attr('data-base-dosis') || (obatInfo.dosis || '').toString();
                            const baseDosis = extractDoseNumber(baseDosisStr);
                            if (baseDosis > 0) {
                                row.attr('data-base-dosis', baseDosis);
                            }
                            const hargaSatuan = parseCurrencyToNumber(obatInfo.harga) || 0;
                            const allowFractional = cardUsesFractionalStockReduction(card);

                            const hargaAkhir = (baseDosis > 0 && dosisRacik > 0) ? (dosisRacik / baseDosis) * hargaSatuan : 0;
                            racikanTotal += hargaAkhir;

                            recalculateRacikanRowStock(row, { card, baseDosis, dosis: dosisStr, priceMap, allowFractional });

                            cardItems.push({
                                obatId,
                                dosisStr,
                                dosisRacik,
                                baseDosisStr: obatInfo.dosis || '',
                                baseDosis,
                                hargaSatuan,
                                hargaAkhir
                            });
                        });

                        // Bungkus multiplier (check both class names)
                        const bungkus = parseFloat(card.find('.jumlah_bungkus').val() || card.find('.bungkus').val()) || 1;
                        // Update any UI display for racikan price if present
                        const formattedRacikan = `(${new Intl.NumberFormat('id-ID').format(racikanTotal)} x ${bungkus} = ${new Intl.NumberFormat('id-ID').format(racikanTotal * bungkus)})`;
                        card.find('.racikan-harga').text(formattedRacikan);
                        card.find('.racikan-harga-detail').text(formattedRacikan);

                        racikanBreakdown.push({
                            racikanKe: card.data('racikan-ke') || null,
                            bungkus,
                            racikanTotal,
                            racikanTotalWithBungkus: racikanTotal * bungkus,
                            items: cardItems
                        });

                        total += racikanTotal * bungkus;
                    });


                    // Finally update the total price display
                    $('#total-harga').html('<strong>' + new Intl.NumberFormat('id-ID').format(total) + '</strong>');
                } catch (e) {
                    console.error('Error computing racikan prices', e);
                    // fallback: still display non-racikan total
                    $('#total-harga').html('<strong>' + new Intl.NumberFormat('id-ID').format(total) + '</strong>');
                }
            }).fail(function () {
                // On fail, just show non-racikan total
                $('#total-harga').html('<strong>' + new Intl.NumberFormat('id-ID').format(total) + '</strong>');
            });
        }

        // TAMBAH RACIKAN BARU
        $('#tambah-racikan').on('click', function () {
            racikanCount++;
            $('#racikan-container').append(racikanCardHtml({ ke: racikanCount, saved: false }));
            const card = $('#racikan-container .racikan-card').last();
            initFarmasiObatSelect(card.find('.select2-obat-racikan'));
            initWadahSelect(card.find('.select2-wadah-racikan'));
            // Aturan pakai field is picked up by AturanPakai.autoMount('#racikan-container')
            toggleEmptyRacikan();
            updateTotalPrice();
            refreshSubmitButtonState();
        });

        // Saved card: lock its fields again (unlocked by "Edit Racikan")
        function lockRacikanCard(card) {
            card.find('.wadah, .bungkus, .jumlah_bungkus, .aturan_pakai').prop('disabled', true);
            card.find('.select2-wadah-racikan').trigger('change.select2');
            card.find('.add-obat-row, .obat-alt').remove();
            card.find('.update-resepracikan').addClass('d-none').prop('disabled', false);
            card.find('.tambah-resepracikan').removeClass('d-none').prop('disabled', true).text('Sudah Disimpan');
            card.find('.edit-racikan').removeClass('d-none');
            card.find('.hapus-obat, .edit-obat').prop('disabled', true);
        }

        // UPDATE RACIKAN
        $('#racikan-container').on('click', '.update-resepracikan', function () {
            const $btn = $(this);
            const card = $btn.closest('.racikan-card');
            const racikanKe = parseInt(card.attr('data-racikan-ke'), 10);
            const visitationId = $('#visitation_id').val();
            const wadah = card.find('.wadah').val();
            const bungkus = parseInt(card.find('.bungkus').val() || card.find('.jumlah_bungkus').val(), 10);
            const aturanPakai = (card.find('.aturan_pakai').val() || '').trim();
            const locked = typeof IS_INVOICE_LOCKED !== 'undefined' && IS_INVOICE_LOCKED;

            if (!racikanKe) {
                Swal.fire('Error', 'Nomor racikan tidak valid. Muat ulang halaman.', 'error');
                return;
            }
            if (!aturanPakai) {
                Swal.fire('Peringatan', 'Aturan pakai harus diisi!', 'warning');
                return;
            }
            if (!locked && (!wadah || !bungkus || bungkus < 1)) {
                Swal.fire('Peringatan', 'Wadah dan jumlah bungkus (minimal 1) harus diisi!', 'warning');
                return;
            }

            // Locked invoice: only aturan pakai may change, so no item list is sent
            const obats = [];
            if (!locked) {
                card.find('.resep-table-body tr[data-obat-id]').each(function () {
                    const row = $(this);
                    const entry = {
                        obat_id: row.attr('data-obat-id'),
                        dosis: row.attr('data-dosis') || row.find('td').eq(1).text().trim(),
                        jumlah: row.attr('data-jumlah') || 1
                    };
                    // Only the persisted resep row id (new rows have none)
                    if (row.attr('data-id')) entry.id = row.attr('data-id');
                    obats.push(entry);
                });
                if (obats.length === 0) {
                    Swal.fire('Peringatan', 'Racikan tanpa obat tidak bisa disimpan. Gunakan "Hapus Racikan" untuk menghapusnya.', 'warning');
                    return;
                }
            }

            const payload = {
                _method: 'PUT',
                visitation_id: visitationId,
                aturan_pakai: aturanPakai,
                _token: '{{ csrf_token() }}'
            };
            if (!locked) {
                payload.wadah = wadah;
                payload.bungkus = bungkus;
                payload.obats = obats;
            }

            beginResepMutation();
            $btn.prop('disabled', true);
            $.ajax({
                url: "{{ route('resepfarmasi.racikan.update', '') }}/" + racikanKe,
                type: 'POST',
                data: payload,
                success: function (response) {
                    if (!response.success) {
                        Swal.fire('Error', 'Terjadi kesalahan: ' + response.message, 'error');
                        return;
                    }
                    Swal.fire({ icon: 'success', title: response.message || 'Racikan berhasil diupdate', timer: 1500, showConfirmButton: false });
                    lockRacikanCard(card);
                    if ('racikan_nama' in response) setRacikanTitle(card, response.racikan_nama);
                    // Rows from the server carry the new resep ids, so a next update edits instead of re-creating
                    if (!locked && Array.isArray(response.obats)) {
                        card.find('.resep-table-body').html(response.obats.map(function (ob) {
                            return racikanRowHtml({
                                id: ob.id,
                                obatId: ob.obat_id,
                                nama: ob.obat_nama,
                                dosis: ob.dosis,
                                baseDosis: extractDoseNumber(ob.obat_dosis),
                                jumlah: ob.jumlah,
                                stok: ob.stok_gudang
                            });
                        }).join(''));
                    }
                    updateTotalPrice();
                },
                error: function (xhr) {
                    Swal.fire('Error', 'Gagal menyimpan perubahan: ' + ajaxErrorMessage(xhr, xhr.responseText || 'Error'), 'error');
                },
                complete: function () {
                    $btn.prop('disabled', false);
                    endResepMutation();
                }
            });
        });

        // TAMBAH OBAT KE RACIKAN (new card, or a saved card in edit mode)
        $('#racikan-container').on('click', '.tambah-obat', function () {
            const card = $(this).closest('.racikan-card');
            const obatSelect = card.find('.select2-obat-racikan');
            const obatId = obatSelect.val();
            const selectedOption = obatSelect.hasClass('select2-hidden-accessible') ? (obatSelect.select2('data')[0] || {}) : {};
            const dosisInput = extractDoseNumber(card.find('.dosis_input').val());

            if (!obatId || !dosisInput) {
                Swal.fire('Peringatan', 'Nama obat dan dosis harus diisi.', 'warning');
                return;
            }
            if (card.find('.resep-table-body tr[data-obat-id="' + obatId + '"]').length) {
                Swal.fire('Obat Sudah Ada', 'Obat ini sudah ada dalam racikan ini.', 'warning');
                return;
            }

            const baseDosis = extractDoseNumber(selectedOption.dosis);
            // "Tablet": dosis is a number of tablets, so multiply by the obat's own dosis
            const dosisAkhir = card.find('.mode_dosis').val() === 'tablet' ? baseDosis * dosisInput : dosisInput;
            const dosisText = (dosisAkhir + ' ' + (selectedOption.satuan || '')).trim();

            const tbody = card.find('.resep-table-body');
            tbody.find('.no-data').remove();
            tbody.append(racikanRowHtml({
                obatId: obatId,
                nama: selectedOption.nama || selectedOption.text,
                dosis: dosisText,
                baseDosis: baseDosis,
                stok: selectedOption.stok_gudang !== undefined ? selectedOption.stok_gudang : selectedOption.stok,
                editable: true
            }));
            obatSelect.val(null).trigger('change');
            card.find('.dosis_input').val('');
            updateTotalPrice();
            refreshSubmitButtonState();
        });

        $('#racikan-container').on('input change', '.bungkus, .jumlah_bungkus', function () {
            updateTotalPrice();
        });

        $('#racikan-container').on('change', '.wadah, .select2-wadah-racikan', function () {
            updateTotalPrice();
        });

        // STORE RACIKAN
        $('#racikan-container').on('click', '.tambah-resepracikan', function () {
            const $btn = $(this);
            const card = $btn.closest('.racikan-card');
            const racikanKe = card.data('racikan-ke');
            const visitationId = $('#visitation_id').val();
            const wadah = card.find('.wadah').val();
            const bungkus = parseInt(card.find('.bungkus').val() || card.find('.jumlah_bungkus').val(), 10);
            const aturanPakai = (card.find('.aturan_pakai').val() || '').trim();

            if (!wadah || !bungkus || !aturanPakai) {
                Swal.fire('Peringatan', 'Wadah, Bungkus dan Aturan Pakai wajib diisi.', 'warning');
                return;
            }
            if (bungkus < 1) {
                Swal.fire('Peringatan', 'Bungkus minimal 1.', 'warning');
                return;
            }

            const obats = [];
            card.find('.resep-table-body tr[data-obat-id]').each(function () {
                obats.push({ obat_id: $(this).attr('data-obat-id'), dosis: $(this).attr('data-dosis') || $(this).find('td').eq(1).text().trim() });
            });

            if (obats.length === 0) {
                Swal.fire('Peringatan', 'Tambahkan minimal satu obat dalam racikan', 'warning');
                return;
            }

            beginResepMutation();
            $btn.prop('disabled', true).text('Menyimpan...');
            $.ajax({
                url: "{{ route('resepfarmasi.racikan.store') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    visitation_id: visitationId,
                    racikan_ke: racikanKe,
                    wadah: wadah,
                    bungkus: bungkus,
                    aturan_pakai: aturanPakai,
                    obats: obats
                },
                success: function (res) {
                    adoptRacikanKe(card, res.racikan_ke);
                    Swal.fire({ icon: 'success', title: res.message || 'Racikan berhasil disimpan.', timer: 1500, showConfirmButton: false });
                    lockRacikanCard(card);
                    if ('racikan_nama' in res) setRacikanTitle(card, res.racikan_nama);
                    // Store the new resep ids (same order as sent) + fresh stok
                    const rows = card.find('.resep-table-body tr[data-obat-id]');
                    (res.obats || []).forEach(function (obat, idx) {
                        const row = rows.eq(idx);
                        const stokGudang = parseInt(obat.stok_gudang, 10) || 0;
                        row.attr('data-id', obat.id).attr('data-dosis', obat.dosis).attr('data-jumlah', obat.jumlah || 1);
                        row.find('td').eq(2).removeClass('stok-low stok-mid stok-ok').addClass(stokClass(stokGudang)).text(stokGudang);
                    });
                    $('#empty-resep-message').hide();
                    updateTotalPrice();
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).text('Simpan Racikan ' + card.data('racikan-ke'));
                    Swal.fire('Gagal Menyimpan', ajaxErrorMessage(xhr, 'Gagal menyimpan racikan.'), 'error');
                },
                complete: function () {
                    endResepMutation();
                }
            });
        });

        // EDIT OBAT RACIKAN (open modal)
        $('#racikan-container').on('click', '.edit-obat', function () {
            const row = $(this).closest('tr');
            $('#edit-dosis-value').val(row.attr('data-dosis'));
            // Keep the row itself: a row index alone matched the same row number in the first racikan card
            $('#editDosisModal').data('row', row).modal('show');
        });

        // SAVE DOSIS FROM MODAL (applied on "Update Racikan")
        $('#save-dosis-btn').on('click', function () {
            const row = $('#editDosisModal').data('row');
            const newDosis = ($('#edit-dosis-value').val() || '').trim();
            if (!newDosis) {
                Swal.fire('Peringatan', 'Dosis tidak boleh kosong!', 'warning');
                return;
            }
            if (!row || !row.length) return;
            row.attr('data-dosis', newDosis).data('dosis', newDosis);
            row.find('td').eq(1).text(newDosis);
            recalculateRacikanRowStock(row, { card: row.closest('.racikan-card'), dosis: newDosis });
            updateTotalPrice();
            $('#editDosisModal').modal('hide');
        });

        // DELETE OBAT DARI RACIKAN (saved on "Simpan" / "Update Racikan")
        $('#racikan-container').on('click', '.hapus-obat', function () {
            const row = $(this).closest('tr');
            const card = row.closest('.racikan-card');
            row.remove();
            if (card.find('.resep-table-body tr[data-obat-id]').length === 0) {
                card.find('.resep-table-body').append(`<tr class="no-data"><td colspan="5" class="text-center text-muted">Belum ada obat</td></tr>`);
            }
            updateTotalPrice();
            refreshSubmitButtonState();
        });
        // DELETE RACIKAN
        $(document).on('click', '.hapus-racikan', function () {
            const card = $(this).closest('.racikan-card');
            const racikanKe = card.data('racikan-ke');
            const visitationId = $('#visitation_id').val();

            if (IS_INVOICE_LOCKED) return;

            // Not saved yet (its "Simpan" button is still active): nothing on the server to delete.
            // A saved card whose obat were all removed in edit mode still exists on the server.
            if (!card.find('.tambah-resepracikan').prop('disabled')) {
                card.remove();
                toggleEmptyRacikan();
                updateTotalPrice();
                refreshSubmitButtonState();
                return;
            }

            Swal.fire({
                title: 'Konfirmasi',
                text: 'Yakin ingin menghapus Racikan ' + racikanKe + '?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    deleteRacikan();
                }
            });
            
            function deleteRacikan() {
                // Request untuk menghapus racikan
                $.ajax({
                    url: "{{ route('resepfarmasi.racikan.destroy', ':racikanKe') }}".replace(':racikanKe', racikanKe),
                    method: "DELETE",
                    data: {
                        _token: "{{ csrf_token() }}",
                        visitation_id: visitationId,
                    },
                success: function (res) {
                    Swal.fire('Sukses', res.message, 'success'); // Notifikasi
                    card.remove(); // Hapus card racikan dari tampilan
                    toggleEmptyRacikan();
                    updateTotalPrice();
                    refreshSubmitButtonState();
                },
                error: function (xhr) {
                    Swal.fire('Error', ajaxErrorMessage(xhr, 'Gagal menghapus racikan'), 'error');
                }
                });
            }
        });
        // EDIT NON RACIKAN
        $('#resep-table-body').on('click', '.edit', function() {
            const row = $(this).closest('tr');
            const id    = row.data('id');
            const jumlah = row.find('td').eq(1).text().trim();
            const rawDiskon = row.find('td').eq(3).text().trim();  // e.g. "10 %"
            const diskonValue = rawDiskon.replace('%', '').trim(); // "10"
            const aturan = row.find('td').eq(5).text().trim();

            $('#edit-resep-id').val(id);
            $('#edit-jumlah').val(jumlah);
            $('#edit-diskon').val(diskonValue);
            const apEdit = AturanPakai.get('#edit-aturan');
            apEdit.setValue(aturan);
            apEdit.setObat(row.data('obat-id'));

            // When invoice is locked, only allow editing aturan pakai
            $('#edit-jumlah').prop('disabled', !!IS_INVOICE_LOCKED);
            $('#edit-diskon').prop('disabled', !!IS_INVOICE_LOCKED);
            $('#edit-aturan').prop('disabled', false);

            $('#editResepModal').modal('show');
        });

        // STORE EDIT NON RACIKAN
        $('#edit-resep-form').on('submit', function(e) {
            e.preventDefault();

            const id = $('#edit-resep-id').val();
            const url = "{{ route('resepfarmasi.nonracikan.update', '') }}/" + id;
            let data = {
                _token: "{{ csrf_token() }}",
                _method: 'PUT',
                aturan_pakai: $('#edit-aturan').val()
            };

            // Only send jumlah/diskon when invoice is not locked
            if (!IS_INVOICE_LOCKED) {
                data.jumlah = $('#edit-jumlah').val();
                data.diskon = $('#edit-diskon').val();
            }

            $.post(url, data)
            .done(function(res) {
                // Update the table row
                const row = $('#resep-table-body').find('tr[data-id="'+ id +'"]');
                if (res && res.data) {
                    // Keep existing values when locked; backend won't change them
                    if (typeof res.data.jumlah !== 'undefined' && res.data.jumlah !== null) {
                        row.find('td').eq(1).text(res.data.jumlah);
                    }
                    if (typeof res.data.diskon !== 'undefined' && res.data.diskon !== null) {
                        row.find('td').eq(3).text(res.data.diskon + ' %');
                    }
                }
                row.find('td').eq(5).text(res.data.aturan_pakai);

                $('#editResepModal').modal('hide');
                // Update totals after editing non-racikan
                updateTotalPrice();
            })
            .fail(function(xhr) {
                Swal.fire('Error', 'Gagal menyimpan perubahan: ' + xhr.responseJSON.message, 'error');
            });
        });

        // SUBMIT KE BILLING
        $('#submit-all').on('click', function () {
            const $btn = $(this);
            const visitationId = $('#visitation_id').val();
            const submitBlockReason = getSubmitBlockReason();

            if (submitBlockReason) {
                refreshSubmitButtonState();
                return Swal.fire('Peringatan', submitBlockReason, 'warning');
            }

            // Stock (non racikan + racikan, resep gudang and the other gudangs) is checked by the server on submit

            // Check if already submitted
            const isSubmitted = $btn.hasClass('btn-secondary');

            if (isSubmitted) {
                Swal.fire({
                    title: 'Konfirmasi',
                    text: 'Resep sudah pernah disubmit. Yakin ingin submit ulang?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Submit Ulang!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.value) {
                        doSubmit(true); // Force resubmit
                    }
                });
            } else {
                Swal.fire({
                    title: 'Konfirmasi',
                    text: 'Yakin ingin submit resep ke billing? Stok akan dikurangi saat pembayaran.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Submit!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.value) {
                        doSubmit(false);
                    }
                });
            }
            
            // abaikanStok: the user accepted that the stok is only enough in another gudang
            function doSubmit(force = false, abaikanStok = false) {
                $btn.data('processing', true);
                $btn.prop('disabled', true).text('Memproses...');
                
                $.ajax({
                    url: "{{ route('resepfarmasi.submit') }}",
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        visitation_id: visitationId,
                        force: force ? 1 : 0,
                        abaikan_stok: abaikanStok ? 1 : 0
                    },
                    success: function (res) {
                        if (res.status === 'warning' && res.stok_warning) {
                            Swal.fire({
                                title: 'Memakai stok ED',
                                html: res.message,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Ya, tetap submit',
                                cancelButtonText: 'Batal'
                            }).then((result) => {
                                if (result.value) {
                                    doSubmit(force, true);
                                } else {
                                    resetButton();
                                }
                            });
                        } else if (res.status === 'warning' && res.need_confirm) {
                            Swal.fire({
                                title: 'Konfirmasi',
                                text: res.message,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Ya, Submit!',
                                cancelButtonText: 'Batal'
                            }).then((result) => {
                                if (result.value) {
                                    doSubmit(true, abaikanStok); // Resubmit with force
                                } else {
                                    resetButton();
                                }
                            });
                        } else if (res.status === 'success') {
                            Swal.fire('Sukses', res.message, 'success');
                            $btn.data('processing', false);
                            $btn.text('Sudah Disubmit').addClass('btn-secondary').removeClass('btn-success').prop('disabled', false);
                            refreshSubmitButtonState();
                        } else if (res.status === 'error') {
                            Swal.fire({ icon: 'error', title: 'Belum bisa submit', html: res.message });
                            resetButton();
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Gagal submit resep. Coba lagi.', 'error');
                        resetButton();
                    }
                });
                
                function resetButton() {
                    $btn.data('processing', false);
                    $btn.prop('disabled', false);
                    if (isSubmitted) {
                        $btn.text('Sudah Disubmit').addClass('btn-secondary').removeClass('btn-success');
                    } else {
                        $btn.text('Submit Resep').addClass('btn-success').removeClass('btn-secondary');
                    }
                    refreshSubmitButtonState();
                }
            }
        });




        $(document).on('click', '.btn-riwayat', function () {
            const url = $(this).data('url');
            $('#riwayatModalTitle').text($(this).data('title') || 'Riwayat Resep');
            $('#riwayatModal').modal('show');
            $('#riwayatModalContent').html('<div class="rr-loading"><i class="fas fa-spinner fa-spin mr-1"></i>Memuat riwayat...</div>');

            $.get(url, function (data) {
                $('#riwayatModalContent').html(data);
            }).fail(function () {
                $('#riwayatModalContent').html('<div class="rr-empty text-danger">Gagal memuat riwayat. Coba lagi.</div>');
            });
        });



        // SALIN RESEP dari riwayat: adds to the current resep (no reload). Obat / racikan that are already in it
        // need a choice first: copy only what is new, or copy them again too.
        $(document).on('click', '.btn-copy-resep', function() {
            const $button = $(this);
            if (IS_INVOICE_LOCKED) {
                showInvoiceLockedMessage();
                return;
            }

            Swal.fire({
                title: 'Salin Resep?',
                text: 'Obat dari resep ini ditambahkan ke resep saat ini.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Salin!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) copyResepFromHistory($button, '');
            });
        });

        function copyResepFromHistory($button, onDuplicate) {
            const label = $button.html();
            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyalin...');
            beginResepMutation();
            $.ajax({
                url: "{{ route('erm.eresepfarmasi.copyfromhistory') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    source_visitation_id: $button.data('visitation-id'),
                    target_visitation_id: $('#visitation_id').val(),
                    source_type: $button.data('source'),
                    on_duplicate: onDuplicate
                }
            }).done(function (res) {
                if (res.status === 'confirm') {
                    askCopyDuplicates(res).then(function (choice) {
                        if (choice) copyResepFromHistory($button, choice);
                    });
                    return;
                }
                if (res.status === 'success') {
                    renderCopiedResep(res.added || {});
                    $('#riwayatModal').modal('hide');
                    Swal.fire({ icon: 'success', title: 'Resep disalin', text: res.message });
                    return;
                }
                Swal.fire({ icon: res.status === 'error' ? 'error' : 'info', title: res.message });
            }).fail(function (xhr) {
                Swal.fire('Error', ajaxErrorMessage(xhr, 'Gagal menyalin resep. Silakan coba lagi.'), 'error');
            }).always(function () {
                $button.prop('disabled', false).html(label);
                endResepMutation();
            });
        }

        // Resolves 'skip' (only what is new), 'include' (also the ones already there) or null (cancelled)
        function askCopyDuplicates(res) {
            const list = (res.duplicates || []).map(nama => `<li>${escapeHtml(nama)}</li>`).join('');
            return Swal.fire({
                icon: 'question',
                title: 'Sebagian obat sudah ada',
                html: `<div style="text-align:left;font-size:.95rem;">Sudah ada di resep saat ini:<ul style="margin:6px 0 10px 18px;">${list}</ul>`
                    + `${res.new_count ? `<strong>${res.new_count}</strong> obat/racikan lainnya belum ada dan akan disalin.` : 'Tidak ada obat lain yang belum ada.'}</div>`,
                input: 'checkbox',
                inputValue: 0,
                inputPlaceholder: 'Tetap salin juga yang sudah ada (jadi dobel)',
                showCancelButton: true,
                confirmButtonText: 'Salin',
                cancelButtonText: 'Batal'
            }).then(function (r) {
                if (r.dismiss) return null;
                return r.value ? 'include' : 'skip';
            });
        }

        // Show the copied rows like the ones added by hand
        function renderCopiedResep(added) {
            (added.non_racikan || []).forEach(function (d) {
                $('#resep-table-body .no-data').remove();
                const stok = parseInt(d.stok_gudang, 10) || 0;
                $('#resep-table-body').append(`
                    <tr data-id="${d.id}" data-obat-id="${d.obat_id}">
                        <td class="font-weight-bold">${escapeHtml(d.obat_nama)}</td>
                        <td class="text-center">${d.jumlah}</td>
                        <td class="text-right">Rp. ${d.harga ?? d.harga_nonfornas ?? 0}</td>
                        <td class="text-center">${d.diskon ?? 0} %</td>
                        <td class="text-center ${stokClass(stok)}">${stok}</td>
                        <td>${escapeHtml(d.aturan_pakai)}</td>
                        <td class="eresep-col-aksi">
                            <button type="button" class="btn btn-sm btn-erm-soft edit" data-id="${d.id}">Edit</button>
                            <button type="button" class="btn btn-sm btn-erm-danger hapus" data-id="${d.id}">Hapus</button>
                        </td>
                    </tr>
                `);
            });

            (added.racikans || []).forEach(function (r) {
                const rows = (r.items || []).map(it => racikanRowHtml({
                    id: it.id,
                    obatId: it.obat_id,
                    nama: it.obat_nama,
                    dosis: it.dosis,
                    baseDosis: extractDoseNumber(it.obat_dosis),
                    jumlah: it.jumlah,
                    stok: it.stok_gudang
                })).join('');
                $('#racikan-container').append(racikanCardHtml({
                    ke: r.racikan_ke,
                    nama: r.nama,
                    saved: true,
                    rows: rows,
                    wadahId: r.wadah_id,
                    wadahNama: r.wadah_nama,
                    bungkus: r.bungkus,
                    aturan: r.aturan_pakai
                }));
                initWadahSelect($('#racikan-container .select2-wadah-racikan').last());
                racikanCount = Math.max(racikanCount, parseInt(r.racikan_ke, 10) || 0);
            });

            $('#empty-resep-message').hide();
            toggleEmptyRacikan();
            updateTotalPrice();
            refreshSubmitButtonState();
        }

        $('.btn-cetakresep').on('click', function() {
            const visitationId = $('#visitation_id').val();
            // Open the print route in a new tab
            window.open(`/erm/eresepfarmasi/${visitationId}/print`, '_blank');
        });

        $('.btn-cetaketiket').on('click', function() {
            const visitationId = $('#visitation_id').val();
            // Open the etiket print route in a new tab
            window.open(`/erm/eresepfarmasi/${visitationId}/print-etiket`, '_blank');
        });

        // Initialize Select2 for Etiket Biru modal
        $('#etiketBiruModal').on('shown.bs.modal', function () {
            $('.select2-etiket-obat').select2({
                width: '100%',
                placeholder: 'Pilih Obat...',
                dropdownParent: $('#etiketBiruModal'),
                ajax: {
                    url: '{{ route("erm.eresepfarmasi.get-visitation-obat", ":visitationId") }}'.replace(':visitationId', $('#visitation_id').val()),
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                            // Group racikan components into a single option per racikan_ke
                            const results = [];
                            const seenRacikan = {};

                            (data || []).forEach(function(item) {
                                if (item.racikan_ke) {
                                    const key = String(item.racikan_ke);
                                    if (!seenRacikan[key]) {
                                        let label = 'Racikan ' + key;
                                        const paketName = item.paket_racikan_name || null;
                                        if (paketName) label = paketName;
                                        // Add a single racikan option (id prefixed to distinguish)
                                        results.push({
                                            id: 'racikan:' + key,
                                            text: label,
                                            is_racikan: true,
                                            racikan_ke: key
                                        });
                                        seenRacikan[key] = true;
                                    }
                                } else {
                                    // Normal (non-racikan) obat option
                                    results.push({
                                        id: item.obat_id,
                                        text: item.obat_nama
                                    });
                                }
                            });

                            return { results: results };
                    },
                    cache: true
                }
            });

            // Show/hide custom label input when racikan option is selected
            $('#etiket-obat').on('change', function() {
                const val = $(this).val();
                if (val && val.toString().startsWith('racikan:')) {
                    // extract racikan ke and prefill label from selected option text
                    const ke = val.toString().split(':')[1] || '';
                    const selData = $('#etiket-obat').select2('data');
                    const labelText = (Array.isArray(selData) && selData[0] && selData[0].text) ? selData[0].text : ('Racikan ' + ke);
                    $('#etiket-racikan-name').val(labelText);
                    $('#etiket-racikan-name-group').show();
                } else {
                    $('#etiket-racikan-name').val('');
                    $('#etiket-racikan-name-group').hide();
                }
            });
        });

        // Handle Print Etiket Biru button click
        $('#print-etiket-biru-btn').on('click', function() {
            const visitationId = $('#etiket-visitation-id').val();
            const obatId = $('#etiket-obat').val();
            const expireDate = $('#etiket-expire').val();
            
            if (!obatId || !expireDate) {
                Swal.fire('Peringatan', 'Semua field wajib diisi.', 'warning');
                return;
            }

            // Create form data
            // Determine whether the selected option is a racikan group
            let isRacikan = false;
            let racikanKe = null;
            if (obatId && obatId.toString().startsWith('racikan:')) {
                isRacikan = true;
                racikanKe = obatId.toString().split(':')[1];
            }

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('visitation_id', visitationId);
            if (isRacikan) {
                formData.append('racikan_ke', racikanKe);
                const customLabel = $('#etiket-racikan-name').val();
                if (customLabel) formData.append('label_name', customLabel);
            } else {
                formData.append('obat_id', obatId);
            }
            formData.append('expire_date', expireDate);
            // Append checkbox values (1 if checked, 0 if not)
            formData.append('pagi', $('#etiket-pagi').is(':checked') ? 1 : 0);
            formData.append('siang', $('#etiket-siang').is(':checked') ? 1 : 0);
            formData.append('sore', $('#etiket-sore').is(':checked') ? 1 : 0);
            formData.append('malam', $('#etiket-malam').is(':checked') ? 1 : 0);

            // Submit form to generate PDF
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/erm/eresepfarmasi/etiket-biru/print';
            form.target = '_blank';
            
            // Add CSRF token
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = '{{ csrf_token() }}';
            form.appendChild(tokenInput);
            
            // Add other fields
            for (let [key, value] of formData.entries()) {
                if (key !== '_token') {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                }
            }
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
            
            // Close modal
            $('#etiketBiruModal').modal('hide');
        });
           
        updateTotalPrice(); // <--- Tambahkan ini

        // PAKET RACIKAN FUNCTIONALITY (copied from dokter page)
        let obatPaketCount = 0;

        // DataTable instance for Paket Racikan list (Farmasi modal)
        let paketRacikanDt = null;

        function ensurePaketRacikanDataTable() {
            if (!$.fn || !$.fn.DataTable) return null;

            if ($.fn.DataTable.isDataTable('#paketRacikanTableFarmasi')) {
                paketRacikanDt = $('#paketRacikanTableFarmasi').DataTable();
                return paketRacikanDt;
            }

            paketRacikanDt = $('#paketRacikanTableFarmasi').DataTable({
                paging: true,
                pagingType: 'simple',
                pageLength: 10,
                lengthChange: false,
                searching: false,
                ordering: false,
                info: false,
                autoWidth: false,
                drawCallback: function () {
                    if (window.feather) {
                        window.feather.replace();
                    }
                },
                language: {
                    emptyTable: 'Belum ada paket racikan',
                    zeroRecords: 'Belum ada paket racikan',
                    paginate: { previous: 'Prev', next: 'Next' }
                },
                data: [],
                columns: [
                    {
                        data: null,
                        width: '8%',
                        render: function (_data, _type, _row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: null,
                        render: function (row) {
                            const nama = row && row.nama_paket ? row.nama_paket : '';
                            const wadahNama = row && row.wadah && row.wadah.nama ? row.wadah.nama : '';
                            const wadahHtml = wadahNama ? `<div class="pr-sub">${$('<div>').text(wadahNama).html()}</div>` : '';
                            const paketJson = JSON.stringify(row).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
                            return `<div class="edit-paket" data-paket="${paketJson}" role="button" title="Klik untuk mengubah paket ini"><strong>${$('<div>').text(nama).html()}</strong>${wadahHtml}</div>`;
                        }
                    },
                    {
                        data: null,
                        render: function (row) {
                            const details = (row && Array.isArray(row.details)) ? row.details : [];
                            if (!details.length) return '<span style="opacity:.6;">-</span>';

                            const lines = details.map(function (d) {
                                const obatNama = d && d.obat && d.obat.nama ? d.obat.nama : '';
                                const dosis = d && d.dosis ? d.dosis : '';
                                const satuan = d && d.obat && d.obat.satuan ? d.obat.satuan : '';
                                let dosisText = dosis;
                                if (satuan) {
                                    const doseLower = (dosisText || '').toString().toLowerCase();
                                    const unitLower = satuan.toString().toLowerCase();
                                    if (dosisText && !doseLower.includes(unitLower)) {
                                        dosisText = `${dosisText} ${satuan}`;
                                    } else if (!dosisText) {
                                        dosisText = satuan;
                                    }
                                }
                                const left = $('<div>').text(obatNama).html();
                                const right = $('<div>').text(dosisText).html();
                                if (left && right) {
                                    return `<div class="d-flex justify-content-between" style="gap:8px;">
                                                <div style="flex:1; min-width:0;">${left}</div>
                                                <div class="text-right text-nowrap" style="min-width:70px;">${right}</div>
                                            </div>`;
                                }
                                if (left) return `<div>${left}</div>`;
                                if (right) return `<div class="text-right">${right}</div>`;
                                return '';
                            }).filter(Boolean);

                            return `<div style="font-size:12px; line-height:1.2;">${lines.join('')}</div>`;
                        }
                    },
                    {
                        data: null,
                        render: function (row) {
                            const paketJson = JSON.stringify(row).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
                            return `
                                <button type="button" class="btn btn-sm pr-btn-use copy-paket" data-id="${row.id}" data-paket="${paketJson}">Gunakan</button>
                                <button type="button" class="btn btn-sm pr-btn-del delete-paket" data-id="${row.id}" title="Hapus" aria-label="Hapus"><i data-feather="trash-2" class="icon-xs"></i></button>
                            `;
                        }
                    }
                ]
            });

            if (window.feather) {
                window.feather.replace();
            }

            return paketRacikanDt;
        }

        // Open Paket Racikan Modal
        $(document).on('click', '#paket-racikan', function() {
            // Reset form first
            resetFormPaket();
            // Load list (respect current search term if present)
            const term = $('#searchPaketRacikanFarmasi').val() || '';
            loadPaketRacikanList(term);
            // Show modal
            $('#paketRacikanModalFarmasi').modal('show');
        });

        // Initialize selects when modal is fully shown
        $('#paketRacikanModalFarmasi').on('shown.bs.modal', function() {
            initializePaketRacikanSelects();

            // Ensure DataTables layout is correct when the table becomes visible
            const dt = ensurePaketRacikanDataTable();
            if (dt) {
                setTimeout(function(){ dt.columns.adjust(); }, 0);
            }
        });

        // Reset button click handler (Farmasi)
        $(document).on('click', '#resetFormPaketBtnFarmasi', function() {
            resetFormPaket();
        });

        // Load Paket Racikan List (robust to different response shapes)
        function loadPaketRacikanList(searchTerm = '') {
            $.ajax({
                url: "{{ route('erm.paket-racikan.list') }}",
                method: 'GET',
                data: { q: searchTerm },
                success: function(response) {
                    let paketList = [];
                    if (!response) {
                        paketList = [];
                    } else if (Array.isArray(response)) {
                        paketList = response;
                    } else if (Array.isArray(response.data)) {
                        paketList = response.data;
                    } else if (response.success && Array.isArray(response.data)) {
                        paketList = response.data;
                    } else if (response.success && Array.isArray(response.pakets)) {
                        paketList = response.pakets;
                    }

                    const dt = ensurePaketRacikanDataTable();
                    if (dt) {
                        dt.clear();
                        dt.rows.add(paketList || []);
                        dt.draw();
                    } else {
                        // Fallback: plain table rendering
                        let tbody = $('#paketRacikanTableBodyFarmasi');
                        tbody.empty();
                        if (!paketList || paketList.length === 0) {
                            tbody.append('<tr><td colspan="4" class="text-center">Belum ada paket racikan</td></tr>');
                        } else {
                            paketList.forEach(function(paket, index) {
                                const paketJson = JSON.stringify(paket).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
                                let wadahNama = paket.wadah ? paket.wadah.nama : '';
                                let wadahHtml = wadahNama ? `<div class="pr-sub">${$('<div>').text(wadahNama).html()}</div>` : '';

                                const details = Array.isArray(paket.details) ? paket.details : [];
                                const obatLines = details.map(function(d){
                                    const obatNama = d && d.obat && d.obat.nama ? d.obat.nama : '';
                                    const dosis = d && d.dosis ? d.dosis : '';
                                    const satuan = d && d.obat && d.obat.satuan ? d.obat.satuan : '';
                                    let dosisText = dosis;
                                    if (satuan) {
                                        const doseLower = (dosisText || '').toString().toLowerCase();
                                        const unitLower = satuan.toString().toLowerCase();
                                        if (dosisText && !doseLower.includes(unitLower)) {
                                            dosisText = `${dosisText} ${satuan}`;
                                        } else if (!dosisText) {
                                            dosisText = satuan;
                                        }
                                    }
                                    const left = $('<div>').text(obatNama).html();
                                    const right = $('<div>').text(dosisText).html();
                                    if (left && right) {
                                        return `<div class="d-flex justify-content-between" style="gap:8px;">
                                                    <div style="flex:1; min-width:0;">${left}</div>
                                                    <div class="text-right text-nowrap" style="min-width:70px;">${right}</div>
                                                </div>`;
                                    }
                                    if (left) return `<div>${left}</div>`;
                                    if (right) return `<div class="text-right">${right}</div>`;
                                    return '';
                                }).filter(Boolean);
                                const obatHtml = obatLines.length ? `<div style="font-size:12px; line-height:1.2;">${obatLines.join('')}</div>` : '<span style="opacity:.6;">-</span>';

                                tbody.append(`
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td><div class="edit-paket" data-paket="${paketJson}" role="button" title="Klik untuk mengubah paket ini"><strong>${$('<div>').text(paket.nama_paket || '').html()}</strong>${wadahHtml}</div></td>
                                        <td>${obatHtml}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm pr-btn-use copy-paket" data-id="${paket.id}" data-paket="${paketJson}">Gunakan</button>
                                            <button type="button" class="btn btn-sm pr-btn-del delete-paket" data-id="${paket.id}" title="Hapus" aria-label="Hapus"><i data-feather="trash-2" class="icon-xs"></i></button>
                                        </td>
                                    </tr>
                                `);
                            });
                        }
                    }
                },
                error: function() {
                    const dt = ensurePaketRacikanDataTable();
                    if (dt) {
                        dt.clear().draw();
                    } else {
                        const tbody = $('#paketRacikanTableBodyFarmasi');
                        tbody.empty();
                        tbody.append('<tr><td colspan="4" class="text-center text-danger">Gagal memuat paket racikan</td></tr>');
                    }
                }
            });
        }

        // Simple debounce helper
        function debounce(fn, wait) {
            let t;
            return function() {
                const ctx = this, args = arguments;
                clearTimeout(t);
                t = setTimeout(function(){ fn.apply(ctx, args); }, wait);
            };
        }

        // Hook up search input to reload daftar paket
        $(document).on('input', '#searchPaketRacikanFarmasi', debounce(function(){
            const term = $(this).val();
            loadPaketRacikanList(term);
        }, 300));

        // Initialize Select2 for Paket Racikan
        function initializePaketRacikanSelects() {
            // Destroy existing select2 instances first
            $('.select2-wadah-paket').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });
            $('.select2-obat-paket').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });

            // Initialize wadah select2
            $('.select2-wadah-paket').select2({
                placeholder: 'Pilih Wadah',
                allowClear: true,
                ajax: {
                    url: '{{ route("wadah.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) { return { q: params.term, visitation_id: $('#visitation_id').val() }; },
                    processResults: function (data) { return { results: data }; },
                    cache: true
                }
            });

            // Initialize obat select2
            $('.select2-obat-paket').select2({
                placeholder: 'Pilih Obat',
                allowClear: true,
                ajax: {
                    url: '{{ route("obat.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) { return { q: params.term }; },
                    processResults: function (data) {
                        if (Array.isArray(data.results)) { return { results: data.results }; } else { return { results: data }; }
                    },
                    cache: true
                },
                minimumInputLength: 2,
                dropdownParent: $('#paketRacikanModalFarmasi')
            });
        }

        // Add Obat to Paket (Farmasi)
        $('#tambahObatPaketFarmasi').on('click', function() {
            obatPaketCount++;
            let newObatItem = `
                <div class="obat-paket-item mb-2">
                    <div class="row">
                        <div class="col-md-8">
                            <select class="form-control select2-obat-paket" name="obats[${obatPaketCount}][obat_id]" required>
                                <option value="">Pilih Obat</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="obats[${obatPaketCount}][dosis]" placeholder="Dosis" required>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-sm remove-obat-paket">×</button>
                        </div>
                    </div>
                </div>
            `;
            $('#obatPaketContainerFarmasi').append(newObatItem);
            let newSelect = $('#obatPaketContainerFarmasi .select2-obat-paket').last();
            newSelect.select2({
                placeholder: 'Pilih Obat',
                allowClear: true,
                ajax: {
                    url: '{{ route("obat.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params){ return { q: params.term }; },
                    processResults: function(data){
                        if (Array.isArray(data.results)) { return { results: data.results }; } else { return { results: data }; }
                    },
                    cache: true
                },
                minimumInputLength: 2,
                dropdownParent: $('#paketRacikanModalFarmasi')
            });
            updateRemoveButtons();
        });

        // Remove Obat from Paket
        $(document).on('click', '.remove-obat-paket', function() { $(this).closest('.obat-paket-item').remove(); updateRemoveButtons(); });

        function updateRemoveButtons() { let items = $('.obat-paket-item'); if (items.length > 1) { $('.remove-obat-paket').show(); } else { $('.remove-obat-paket').hide(); } }

        // Save Paket Racikan
        $('#formPaketRacikanFarmasi').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            let obatsData = [];
            $('.obat-paket-item').each(function() {
                let obatId = $(this).find('select').val();
                let dosis = $(this).find('input[type="text"]').val();
                if (obatId && dosis) { obatsData.push({ obat_id: obatId, dosis: dosis }); }
            });
            if (obatsData.length === 0) { Swal.fire({ icon: 'warning', title: 'Data Belum Lengkap', text: 'Minimal harus ada satu obat dalam paket', confirmButtonColor: '#3085d6' }); return; }
            let paketId = $('#paketIdFarmasi').val();
            let data = { _token: "{{ csrf_token() }}", nama_paket: formData.get('nama_paket'), wadah_id: formData.get('wadah_id'), bungkus_default: formData.get('bungkus_default'), aturan_pakai_default: formData.get('aturan_pakai_default'), obats: obatsData };
            if (paketId) {
                // update existing paket
                data._method = 'PUT';
                $.ajax({ url: "{{ route('erm.paket-racikan.update', '') }}/" + paketId, method: 'POST', data: data, success: function(response) { if (response.success) { Swal.fire({ icon: 'success', title: 'Berhasil!', text: response.message, confirmButtonColor: '#3085d6' }); resetFormPaket(); loadPaketRacikanList(); /* keep modal open for further edits */ } }, error: function(xhr) { Swal.fire({ icon: 'error', title: 'Gagal Mengupdate', text: 'Gagal mengupdate paket racikan: ' + (xhr.responseJSON?.message || 'Unknown error'), confirmButtonColor: '#3085d6' }); } });
            } else {
                // create new paket
                $.ajax({ url: "{{ route('erm.paket-racikan.store') }}", method: 'POST', data: data, success: function(response) { if (response.success) { Swal.fire({ icon: 'success', title: 'Berhasil!', text: response.message, confirmButtonColor: '#3085d6' }); resetFormPaket(); loadPaketRacikanList(); } }, error: function(xhr) { Swal.fire({ icon: 'error', title: 'Gagal Menyimpan', text: 'Gagal menyimpan paket racikan: ' + (xhr.responseJSON?.message || 'Unknown error'), confirmButtonColor: '#3085d6' }); } });
            }
        });

        // Copy Paket Racikan
        $(document).on('click', '.copy-paket', function() {
            let paketId = $(this).data('id');
            // read raw attribute and parse safely
            let raw = $(this).attr('data-paket');
            let paketData = null;
            try { paketData = raw ? JSON.parse(raw) : null; } catch(e) { paketData = $(this).data('paket') || null; }
            if (paketData) {
                $('#paketNamaDisplayFarmasi').text(paketData.nama_paket);
                $('#paketBungkusFarmasi').val(paketData.bungkus_default || 10);
                const aturanVal = paketData.aturan_pakai_default || '';
                $('#paketAturanPakaiFarmasi').val(aturanVal);
            }
            $('#selectedPaketIdFarmasi').val(paketId);
            // store paketData on the hidden selector so konfirmasi handler can access it
            $('#selectedPaketIdFarmasi').data('paket', paketData);
            $('#paketRacikanModalFarmasi').modal('hide');
            $('#gunakanPaketModalFarmasi').modal('show');
        });

        // Konfirmasi gunakan paket racikan (Farmasi)
        $(document).on('click', '#konfirmasiGunakanPaketFarmasi', function() {
            let paketId = $('#selectedPaketIdFarmasi').val();
            let bungkus = $('#paketBungkusFarmasi').val();
            let aturanPakai = $('#paketAturanPakaiFarmasi').val();
            let visitationId = $('#visitation_id').val();
            if (!bungkus || !aturanPakai) { Swal.fire({ icon: 'warning', title: 'Data Belum Lengkap', text: 'Bungkus dan Aturan Pakai harus diisi', confirmButtonColor: '#3085d6' }); return; }
            $(this).html('<i class="fas fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
            beginResepMutation();
            $.ajax({ url: "{{ route('erm.paket-racikan.copy.farmasi') }}", method: 'POST', data: { _token: "{{ csrf_token() }}", paket_racikan_id: paketId, visitation_id: visitationId, bungkus: bungkus, aturan_pakai: aturanPakai }, success: function(response) {
                    if (response.success) {
                        $('#gunakanPaketModalFarmasi').addClass('reload-after-close');
                        const createdRacikanKe = response.racikan_ke;
                        racikanCount = Math.max(racikanCount, parseInt(createdRacikanKe, 10) || 0);
                        // Prefer client-side rendering from paket data saved earlier
                        let paketData = $('#selectedPaketIdFarmasi').data('paket') || null;
                        if (paketData && typeof paketData === 'string') {
                            try { paketData = JSON.parse(paketData); } catch (e) { paketData = null; }
                        }
                        if (paketData) {
                            createRacikanCardFromPaketWithCustomData(paketData, createdRacikanKe, bungkus, aturanPakai);
                            // PATCH: Update stok/ids from server response if available (stok depends on gudang mapping)
                            if (response.obats && Array.isArray(response.obats)) {
                                const card = $('#racikan-container .racikan-card[data-racikan-ke="' + createdRacikanKe + '"]').last();
                                if (card.length) {
                                    const tbody = card.find('.resep-table-body');
                                    response.obats.forEach(function(ob){
                                        const stokGudang = parseInt(ob.stok_gudang || 0, 10);
                                        // Find the existing row rendered from paket data and patch it
                                        const row = tbody.find('tr[data-obat-id="' + ob.obat_id + '"]').first();
                                        if (row.length) {
                                            row.attr('data-id', ob.id);
                                            row.attr('data-obat-id', ob.obat_id);
                                            row.attr('data-dosis', ob.dosis);
                                            row.attr('data-jumlah', ob.jumlah || 1);
                                            row.find('td').eq(2).removeClass('stok-low stok-mid stok-ok').addClass(stokClass(stokGudang)).text(stokGudang);
                                        }
                                    });
                                }
                            }
                            updateTotalPrice();
                        } else {
                            // fallback to server fetch if paket data not available
                            fetchFarmasiResep();
                            setTimeout(function(){ updateTotalPrice(); }, 300);
                        }
                        $('#paketRacikanModalFarmasi').modal('hide');
                        Swal.fire({ icon: 'success', title: 'Berhasil!', text: response.message || 'Paket racikan berhasil diterapkan!', confirmButtonColor: '#3085d6', timer:2000, showConfirmButton:false });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: response.message || 'Gagal menerapkan paket racikan', confirmButtonColor: '#3085d6' });
                    }
                }, error: function(xhr) { let errorMessage = 'Gagal menerapkan paket racikan'; if (xhr.responseJSON && xhr.responseJSON.message) { errorMessage = xhr.responseJSON.message; } Swal.fire({ icon: 'error', title: 'Gagal', text: errorMessage, confirmButtonColor: '#3085d6' }); }, complete: function() { endResepMutation(); $('#konfirmasiGunakanPaketFarmasi').html('Gunakan Paket').prop('disabled', false); $('#gunakanPaketModalFarmasi').modal('hide'); }
            });
        });

        // Handler untuk kembali ke modal paket racikan jika user batal di modal konfirmasi (Farmasi)
        $('#gunakanPaketModalFarmasi').on('hidden.bs.modal', function (e) {
            if (!$(e.target).hasClass('reload-after-close')) {
                setTimeout(function() { $('#paketRacikanModalFarmasi').modal('show'); }, 300);
            }
            // one-shot flag: the next cancel must reopen the list again
            $(e.target).removeClass('reload-after-close');
        });

        // Racikan card for a paket that was just applied (already saved on the server)
        function createRacikanCardFromPaketWithCustomData(paket, racikanKe, customBungkus, customAturanPakai) {
            const details = paket.details || paket.obats || paket.items || [];
            const rows = details.map(function (detail) {
                const obat = detail.obat || {};
                return racikanRowHtml({
                    obatId: obat.id || '',
                    nama: obat.nama,
                    dosis: detail.dosis,
                    baseDosis: extractDoseNumber(obat.dosis),
                    stok: typeof obat.stok_gudang !== 'undefined' ? obat.stok_gudang : obat.stok
                });
            }).join('');

            $('#racikan-container').append(racikanCardHtml({
                ke: racikanKe,
                nama: paket.nama_paket || null,
                saved: true,
                rows: rows,
                // keep the paket's wadah: an empty wadah would be saved as NULL on the next "Update Racikan"
                wadahId: paket.wadah ? paket.wadah.id : (paket.wadah_id || ''),
                wadahNama: paket.wadah ? paket.wadah.nama : '',
                bungkus: customBungkus,
                aturan: customAturanPakai
            }));
            initWadahSelect($('#racikan-container .select2-wadah-racikan').last());
            toggleEmptyRacikan();
        }

        // Edit Paket (populate form for editing)
        $(document).on('click', '.edit-paket', function() {
            let paketRaw = $(this).attr('data-paket');
            let paket = null;
            try { paket = paketRaw ? JSON.parse(paketRaw) : $(this).data('paket'); } catch(e) { paket = $(this).data('paket'); }
            if (!paket) return;
            resetFormPaket();
            // set paket id for update
            $('#paketIdFarmasi').val(paket.id || '');
            // populate fields
            $('#formPaketRacikanFarmasi input[name="nama_paket"]').val(paket.nama_paket || '');
            // wadah (ajax select2): the option must exist before it can be selected, else the paket loses its wadah on save
            $('.select2-wadah-paket').find('option').not('[value=""]').remove();
            if (paket.wadah && paket.wadah.id) {
                $('.select2-wadah-paket').append(new Option(paket.wadah.nama, paket.wadah.id, true, true)).trigger('change');
            } else {
                $('.select2-wadah-paket').val('').trigger('change');
            }
            setPaketFormMode(paket.nama_paket);
            $('#formPaketRacikanFarmasi input[name="bungkus_default"]').val(paket.bungkus_default || 10);
            $('#formPaketRacikanFarmasi input[name="aturan_pakai_default"]').val(paket.aturan_pakai_default || '');

            // populate obat items
            const container = $('#obatPaketContainerFarmasi');
            container.empty();
            if (Array.isArray(paket.details) && paket.details.length) {
                paket.details.forEach(function(detail, idx) {
                    const obatId = detail.obat ? detail.obat.id : (detail.obat_id || '');
                    const obatNama = detail.obat ? detail.obat.nama : '';
                    const dosis = detail.dosis || '';
                    container.append(`
                        <div class="obat-paket-item mb-2">
                            <div class="row">
                                <div class="col-md-8">
                                    <select class="form-control select2-obat-paket" name="obats[${idx}][obat_id]" required>
                                        ${obatId ? `<option value="${obatId}" selected>${obatNama}</option>` : '<option value="">Pilih Obat</option>'}
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="obats[${idx}][dosis]" value="${dosis}" placeholder="Dosis" required>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-danger btn-sm remove-obat-paket">×</button>
                                </div>
                            </div>
                        </div>
                    `);
                    obatPaketCount = idx + 1;
                });
            } else {
                // ensure at least one row
                container.html(`
                    <div class="obat-paket-item mb-2">
                        <div class="row">
                            <div class="col-md-8">
                                <select class="form-control select2-obat-paket" name="obats[0][obat_id]" required>
                                    <option value="">Pilih Obat</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="obats[0][dosis]" placeholder="Dosis" required>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-danger btn-sm remove-obat-paket" style="display:none;">×</button>
                            </div>
                        </div>
                    </div>
                `);
                obatPaketCount = 0;
            }
            initializePaketRacikanSelects();
            updateRemoveButtons();
            // show modal (ensure it's visible)
            $('#paketRacikanModalFarmasi').modal('show');
        });

        // Delete Paket Racikan
        $(document).on('click', '.delete-paket', function() {
            let paketId = $(this).data('id');
            Swal.fire({ title: 'Konfirmasi Hapus', text: 'Yakin ingin menghapus paket racikan ini?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal' }).then((result) => {
                if (result.value) {
                    $.ajax({ url: "{{ route('erm.paket-racikan.delete', '') }}/" + paketId, method: 'DELETE', data: { _token: "{{ csrf_token() }}" }, success: function(response) { if (response.success) { Swal.fire({ icon: 'success', title: 'Berhasil!', text: response.message, confirmButtonColor: '#3085d6' }); loadPaketRacikanList(); } }, error: function(xhr) { Swal.fire({ icon: 'error', title: 'Gagal Menghapus', text: 'Gagal menghapus paket racikan: ' + (xhr.responseJSON?.message || 'Unknown error'), confirmButtonColor: '#3085d6' }); } });
                }
            });
        });

        // Reset Form Paket
        // The form both creates and edits (clicking a paket name): say which, so "Simpan" does not
        // overwrite a paket someone meant to copy as a new one
        function setPaketFormMode(editingNama) {
            const $modal = $('#paketRacikanModal, #paketRacikanModalFarmasi');
            $modal.find('.paket-form-title strong').text(editingNama ? 'Edit Paket: ' + editingNama : 'Buat Paket Racikan Baru');
            $modal.find('form button[type="submit"]').text(editingNama ? 'Update Paket' : 'Simpan Paket');
        }

        function resetFormPaket() {
            setPaketFormMode(null);
            $('.select2-wadah-paket').find('option').not('[value=""]').remove();
            $('.select2-wadah-paket').each(function() { if ($(this).hasClass('select2-hidden-accessible')) { $(this).select2('destroy'); } });
            $('.select2-obat-paket').each(function() { if ($(this).hasClass('select2-hidden-accessible')) { $(this).select2('destroy'); } });
            $('#formPaketRacikanFarmasi')[0].reset();
            // Ensure paketId is cleared so subsequent saves create new paket instead of updating
            $('#paketIdFarmasi').val('');
            $('#obatPaketContainerFarmasi').html(`
                <div class="obat-paket-item mb-2">
                    <div class="row">
                        <div class="col-md-8">
                            <select class="form-control select2-obat-paket" name="obats[0][obat_id]" required>
                                <option value="">Pilih Obat</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="obats[0][dosis]" placeholder="Dosis" required>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-sm remove-obat-paket" style="display:none;">×</button>
                        </div>
                    </div>
                </div>
            `);
            obatPaketCount = 0;
            initializePaketRacikanSelects();
        }

    });

    // Rebuild the racikan cards from the database (fallback after applying a paket).
    // The non racikan table is left alone: nothing there changed, and its stok column comes from the server render.
    function fetchFarmasiResep() {
        const visitationId = $('#visitation_id').val();

        $.get(`/erm/eresepfarmasi/${visitationId}/json`, function (res) {
            $('#resep-wrapper').show();
            $('#empty-resep-message').hide();

            const racikanWrapper = $('#racikan-container');
            racikanWrapper.empty();
            // reset the page counter (not a local copy), so new cards continue after the loaded racikan
            racikanCount = 0;

            // res.racikans is a list of groups; the racikan number is on the items themselves
            Object.values(res.racikans || {}).forEach(function (items) {
                const first = (items || [])[0];
                if (!first) return;
                const ke = parseInt(first.racikan_ke, 10);
                const rows = items.map(function (item) {
                    const obat = item.obat || {};
                    return racikanRowHtml({
                        id: item.id,
                        obatId: item.obat_id,
                        nama: obat.nama,
                        dosis: item.dosis,
                        baseDosis: extractDoseNumber(obat.dosis),
                        jumlah: item.jumlah,
                        stok: obat.stok_gudang
                    });
                }).join('');

                racikanWrapper.append(racikanCardHtml({
                    nama: first.paket_racikan_nama || null,
                    ke: ke,
                    saved: true,
                    rows: rows,
                    wadahId: first.wadah ? first.wadah.id : (first.wadah_id || ''),
                    wadahNama: first.wadah ? first.wadah.nama : '',
                    bungkus: first.bungkus || 1,
                    aturan: first.aturan_pakai || ''
                }));
                initWadahSelect(racikanWrapper.find('.select2-wadah-racikan').last());
                racikanCount = Math.max(racikanCount, ke || 0);
            });
            toggleEmptyRacikan();
            updateTotalPrice();
            refreshSubmitButtonState();
        });
    }

// EDIT RACIKAN. Locked invoice: only aturan pakai (for etiket); otherwise all fields + obat rows.
$(document).on('click', '.edit-racikan', function () {
    const card = $(this).closest('.racikan-card');
    const locked = typeof IS_INVOICE_LOCKED !== 'undefined' && IS_INVOICE_LOCKED;

    card.find('.tambah-resepracikan').addClass('d-none');
    card.find('.update-resepracikan').removeClass('d-none').prop('disabled', false);
    card.find('.aturan_pakai').prop('disabled', false);

    if (locked) {
        card.find('.wadah, .jumlah_bungkus, .bungkus').prop('disabled', true);
        card.find('.hapus-obat, .edit-obat').prop('disabled', true);
        refreshSubmitButtonState();
        return;
    }

    card.find('.wadah, .jumlah_bungkus, .bungkus').prop('disabled', false);
    card.find('.select2-wadah-racikan').trigger('change.select2');
    card.find('.hapus-obat, .edit-obat').prop('disabled', false);

    // "Tambah obat" row above the table (saved cards don't carry one)
    if (card.find('.add-obat-row').length === 0) {
        card.find('.table-responsive').first().before(racikanAddRowHtml());
    }
    const obatSelect = card.find('.select2-obat-racikan');
    if (!obatSelect.hasClass('select2-hidden-accessible')) {
        initFarmasiObatSelect(obatSelect);
    }
    card.find('.add-obat-row').find('input, select, button').prop('disabled', false);

    refreshSubmitButtonState();
});

// Apply invoice lock after all handlers are registered
$(document).ready(function () {
    applyInvoiceLock();
    startInvoiceLockPolling();
    refreshSubmitButtonState();
});


// Notification polling for farmasi create page
@if(auth()->user()->hasRole('Farmasi'))
$(document).ready(function() {
    // Use small buffer to avoid missing notifications created in the same second.
    let lastCheck = {{ time() - 3 }};
    let isPolling = false;
    
    function checkForNewNotifications() {
        if (isPolling) return;
        isPolling = true;
        
        $.ajax({
            url: '{{ route("erm.check.notifications") }}',
            type: 'GET',
            data: {
                lastCheck: lastCheck,
                page: 'create'
            },
            success: function(response) {
                if (response.hasNew && response.message) {
                    Swal.fire({
                        title: 'Ada Perubahan di Resep Dokter!',
                        text: response.message,
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                }
                if (response && response.timestamp) lastCheck = response.timestamp;
            },
            error: function(xhr, status, error) {
                console.error('Error checking for notifications:', error);
            },
            complete: function() {
                isPolling = false;
            }
        });
    }
    
    // Check for notifications every 10 seconds
    setInterval(checkForNewNotifications, 10000);
    checkForNewNotifications();
});
@endif
</script>
@endsection
