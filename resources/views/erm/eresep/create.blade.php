@extends('layouts.erm.app')
@section('title', 'E-Resep')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection
@section('content')

@include('erm.partials.eresep-theme')

@include('erm.partials.modal-alergipasien')
@include('erm.partials.modal-resephistory')
@include('erm.partials.modal-paketracikan')

@include('erm.partials.modal-editnonracikan-dokter')

<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">E-Resep Dokter</h3>
        <input type="hidden" id="visitation_id" value="{{ $visitation->id }}">
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Rawat Jalan</li>
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
                <div><strong>Resep dikunci.</strong> Invoice sudah ditransaksikan, resep tidak bisa ditambah, diubah, dihapus atau disubmit.</div>
            </div>
        @endif

        {{-- Tools: riwayat + paket --}}
        <div class="eresep-toolbar">
            <span class="eresep-toolbar-label">Riwayat</span>
            <button type="button" class="btn btn-sm btn-erm-ghost btn-riwayat" data-url="{{ route('resep.historydokter', ['pasienId' => $pasien->id, 'current' => $visitation->id, 'target' => 'dokter']) }}" data-title="Riwayat Resep Dokter">
                <i class="fas fa-user-md mr-1"></i>Resep Dokter
            </button>
            <button type="button" class="btn btn-sm btn-erm-ghost btn-riwayat" data-url="{{ route('resep.historyfarmasi', ['pasienId' => $pasien->id, 'current' => $visitation->id, 'target' => 'dokter']) }}" data-title="Riwayat Resep Farmasi">
                <i class="fas fa-clinic-medical mr-1"></i>Resep Farmasi
            </button>
            <span class="eresep-toolbar-sep"></span>
            <button type="button" id="paket-racikan" class="btn btn-sm btn-erm-ghost">
                <i class="fas fa-box-open mr-1"></i>Paket Racikan
            </button>
        </div>

        {{-- 1. Catatan --}}
        <section class="erm-section">
            <div class="erm-section-head">
                <span class="erm-section-num">1</span>
                <h4 class="erm-section-title">Catatan untuk Farmasi<span class="erm-section-sub">Dokter input: {{ auth()->user()->name }}</span></h4>
            </div>
            <div class="form-group">
                <textarea class="form-control" id="catatan_dokter" placeholder="Tuliskan catatan untuk farmasi di sini ..." rows="3" aria-label="Catatan untuk farmasi">{{ $catatan_dokter ?? ($catatan_resep ?? '') }}</textarea>
            </div>
            <div class="d-flex justify-content-end mb-2">
                <button type="button" class="btn btn-sm btn-erm-soft" id="simpan-catatan"><i class="fas fa-save mr-1"></i>Simpan Catatan</button>
            </div>
        </section>

        {{-- 2. Non racikan --}}
        <section class="erm-section">
            <div class="erm-section-head">
                <span class="erm-section-num">2</span>
                <h4 class="erm-section-title">Resep Non Racikan<span class="erm-section-sub">Obat jadi dengan jumlah dan aturan pakai</span></h4>
            </div>
            <div class="row eresep-add-row">
                <div class="col-lg-5 col-md-12">
                    <div class="form-group">
                        <label for="obat_id">Nama Obat</label>
                        <select class="form-control select2-obat" name="obat_id" id="obat_id">
                            <option value="">Cari nama obat / zat aktif...</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3">
                    <div class="form-group">
                        <label for="jumlah">Jumlah</label>
                        <input type="number" id="jumlah" class="form-control" min="1" step="1" placeholder="0">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="form-group">
                        <label for="aturan_pakai">Aturan Pakai</label>
                        {{-- Free text with "sering dipakai" recommendations (erm.partials.aturan-pakai-builder) --}}
                        <input type="text" id="aturan_pakai" class="form-control" placeholder="mis. 2 X SEHARI 1 TABLET">
                    </div>
                </div>
                <div class="col-lg-2 col-md-3">
                    <div class="form-group">
                        <label class="d-none d-md-block">&nbsp;</label>
                        <button type="button" id="tambah-resep" class="btn btn-erm-accent btn-block"><i class="fas fa-plus mr-1"></i>Tambah</button>
                    </div>
                </div>
            </div>

            <div class="eresep-legend"><span class="eresep-legend-swatch"></span>Hijau = obat sudah ada di resep farmasi</div>

            <div class="table-responsive">
                <table class="table table-bordered eresep-table">
                    <thead>
                        <tr>
                            <th>Nama Obat</th>
                            <th class="text-right">Harga Satuan</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-center">Sisa Stok</th>
                            <th class="text-right">Harga Akhir</th>
                            <th>Aturan Pakai</th>
                            <th class="eresep-col-aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="resep-table-body">
                        @forelse ($nonRacikans as $resep)
                            @php
                                $hargaSatuan = (float) ($resep->obat->harga_nonfornas ?? 0);
                                // Guard against missing related obat
                                $stokGudang = ($gudangResepId && $resep->obat) ? (int) $resep->obat->getStokByGudang($gudangResepId) : 0;
                            @endphp
                            <tr data-id="{{ $resep->id }}" data-obat-id="{{ $resep->obat_id }}" data-harga="{{ $hargaSatuan }}" class="{{ in_array($resep->obat_id, $farmasiObatIds) ? 'text-success row-in-farmasi' : '' }}">
                                <td class="font-weight-bold">{{ $resep->obat->nama ?? '-' }}</td>
                                <td class="text-right">Rp {{ number_format($hargaSatuan, 0, ',', '.') }}</td>
                                <td class="text-center">{{ $resep->jumlah }}</td>
                                <td class="text-center {{ $stokClass($stokGudang) }}">{{ $stokGudang }}</td>
                                <td class="text-right">Rp {{ number_format(($resep->jumlah ?? 0) * $hargaSatuan, 0, ',', '.') }}</td>
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
                <h4 class="erm-section-title">Resep Racikan<span class="erm-section-sub">Hijau = obat sudah ada di racikan farmasi</span></h4>
                <button type="button" id="tambah-racikan" class="btn btn-sm btn-erm-accent ml-auto"><i class="fas fa-plus mr-1"></i>Tambah Racikan</button>
            </div>

            <div id="racikan-container">
                @foreach ($racikans as $ke => $items)
                <div class="racikan-card" data-racikan-ke="{{ $ke }}">

                    <div class="racikan-card-head">
                        @php $racikanNama = $racikanNames[$ke] ?? null; @endphp
                        <h5><strong class="racikan-title">{{ $racikanNama ?: 'Racikan ' . $ke }}</strong>@if($racikanNama)<span class="racikan-no">Racikan {{ $ke }}</span>@endif
                            <span class="racikan-harga-detail"></span>
                        </h5>
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
                                <th>Dosis Obat</th>
                                <th>Dosis Racik</th>
                                <th class="text-right">Harga Satuan</th>
                                <th class="text-right">Harga Akhir</th>
                                <th class="text-center">Sisa Stok</th>
                                <th class="eresep-col-aksi">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="resep-table-body">
                            @foreach ($items as $resep)
                                @php
                                    $dosisObat = (float) ($resep->obat->dosis ?? 0);
                                    $dosisRacik = (float) ($resep->dosis ?? 0);
                                    $hargaSatuan = (float) ($resep->obat->harga_nonfornas ?? 0);
                                    $hargaAkhir = ($dosisObat > 0) ? ($dosisRacik / $dosisObat) * $hargaSatuan : 0;
                                    $stokGudang = ($gudangResepId && $resep->obat) ? (int) $resep->obat->getStokByGudang($gudangResepId) : (int) ($resep->obat->total_stok ?? 0);
                                @endphp
                                <tr data-obat-id="{{ $resep->obat_id }}" data-harga-satuan="{{ $hargaSatuan }}" data-harga-akhir="{{ $hargaAkhir }}" class="{{ in_array($resep->obat_id, $farmasiRacikanObatIds) ? 'text-success row-in-farmasi' : '' }}">
                                    <td data-id="{{ $resep->id }}" class="font-weight-bold">{{ $resep->obat->nama ?? '-' }}</td>
                                    <td>{{ $resep->obat->dosis ?? '-' }}</td>
                                    <td>
                                        @if(empty($resep->dosis))
                                            <span class="text-danger font-weight-bold">Dosis kosong</span>
                                        @else
                                            {{ $resep->dosis }}
                                        @endif
                                    </td>
                                    <td class="text-right">Rp {{ number_format($hargaSatuan, 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($hargaAkhir, 0, ',', '.') }}</td>
                                    <td class="text-center {{ $stokClass($stokGudang) }}">{{ $stokGudang }}</td>
                                    <td class="eresep-col-aksi">
                                        <button type="button" class="btn btn-sm btn-erm-soft edit-obat disabled" disabled>Edit</button>
                                        <button type="button" class="btn btn-sm btn-erm-danger hapus-obat disabled" disabled>Hapus</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>

                    <!-- Form untuk menambah obat baru ke racikan (hidden by default) -->
                    <div class="add-medication-form d-none">
                        <div class="eresep-subcard">
                            <div class="erm-subhead">Tambah Obat ke Racikan {{ $ke }}</div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label>Nama Obat</label>
                                    <select class="form-control select2-obat-racikan-add" style="width: 100%;">
                                        <option value="">Pilih Obat</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>Dosis</label>
                                    <input type="number" class="form-control dosis-racikan-add" placeholder="Dosis" step="0.01" min="0">
                                </div>
                                <div class="col-md-2">
                                    <label>Mode</label>
                                    <select class="form-control mode-racikan-add">
                                        <option value="manual">Manual</option>
                                        <option value="tablet">Per Tablet</option>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-erm-accent btn-block btn-add-medication">Tambah</button>
                                </div>
                            </div>
                        </div>
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
                            <input type="number" class="form-control jumlah_bungkus bungkus" min="1" value="{{ $items->first()->bungkus }}" disabled>
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

        <div class="erm-actionbar">
            <div class="eresep-total">
                <span class="eresep-total-label">Total Harga</span>
                <span class="eresep-total-value">Rp <span id="total-harga"><strong>0</strong></span></span>
            </div>
            <button type="button" id="pasien-keluar-btn" class="btn btn-erm-primary" data-pasien-id="{{ $pasien->id }}" data-pasien-name="{{ $pasien->nama }}">
                <i class="fas fa-paper-plane mr-1"></i>Selesai &amp; Kirim ke Farmasi
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@include('erm.partials.aturan-pakai-builder')
@include('erm.partials.obat-alternatif')
<script>
    let IS_INVOICE_LOCKED = @json($isInvoiceLocked ?? false);
    const INVOICE_PAYMENT_METHOD = @json($invoicePaymentMethod ?? null);

    function showInvoiceLockedMessage() {
        const msg = 'Resep dikunci karena Invoice sudah ditransaksikan.';
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                icon: 'warning',
                title: 'Terkunci',
                text: msg,
                confirmButtonColor: '#3085d6'
            });
        } else {
            alert(msg);
        }
    }

    function stokClass(stok) {
        stok = parseInt(stok, 10) || 0;
        return stok < 10 ? 'stok-low' : (stok < 100 ? 'stok-mid' : 'stok-ok');
    }

    function formatRupiah(value) {
        return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value) || 0);
    }

    function swalWarn(text, title) {
        Swal.fire({ icon: 'warning', title: title || 'Data Belum Lengkap', text: text, confirmButtonColor: '#3085d6' });
    }

    function ajaxErrorMessage(xhr, fallback) {
        const json = xhr && xhr.responseJSON;
        if (json && json.errors) return Object.values(json.errors).flat().join('\n');
        return (json && json.message) || fallback;
    }

    function escapeAttr(value) {
        return String(value == null ? '' : value).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }

    // One obat row of a racikan table. hargaAkhir = dosis racik / dosis obat x harga satuan
    function racikanRowHtml(o) {
        const dosisObat = parseFloat(o.dosisObat) || 0;
        const hargaSatuan = parseFloat(o.hargaSatuan) || 0;
        const hargaAkhir = dosisObat > 0 ? (parseFloat(o.dosisRacik) || 0) / dosisObat * hargaSatuan : 0;
        const stok = parseInt(o.stok, 10) || 0;
        return `
            <tr data-obat-id="${o.obatId}" data-harga-satuan="${hargaSatuan}" data-harga-akhir="${hargaAkhir}" class="${o.rowClass || ''}">
                <td data-id="${o.dbId || ''}" data-obat-id="${o.obatId}" class="font-weight-bold">${escapeObatMarkup(o.nama || '-')}</td>
                <td>${escapeObatMarkup(o.dosisObat || '-')}</td>
                <td>${escapeObatMarkup(o.dosisRacik)}</td>
                <td class="text-right">${formatRupiah(hargaSatuan)}</td>
                <td class="text-right">${formatRupiah(hargaAkhir)}</td>
                <td class="text-center ${stokClass(stok)}">${stok}</td>
                <td class="eresep-col-aksi">
                    <button type="button" class="btn btn-sm btn-erm-soft edit-obat${o.editable ? '' : ' disabled'}"${o.editable ? '' : ' disabled'}>Edit</button>
                    <button type="button" class="btn btn-sm btn-erm-danger hapus-obat${o.editable ? '' : ' disabled'}"${o.editable ? '' : ' disabled'}>Hapus</button>
                </td>
            </tr>`;
    }

    // A racikan card; saved = already in the database (fields locked until "Edit Racikan")
    function racikanCardHtml(o) {
        const ke = o.ke;
        const dis = o.saved ? ' disabled' : '';
        const addRow = o.saved ? '' : `
                <div class="row racikan-add-row">
                    <div class="col-md-6"><div class="form-group">
                        <label>Nama Obat</label>
                        <select class="form-control select2-obat-racikan" name="obat_id"><option value="">Cari nama obat / zat aktif...</option></select>
                    </div></div>
                    <div class="col-md-2 col-6"><div class="form-group">
                        <label>Dosis</label>
                        <input type="number" class="form-control dosis_input" min="0" step="0.01">
                    </div></div>
                    <div class="col-md-2 col-6"><div class="form-group">
                        <label>Satuan</label>
                        <select class="form-control mode_dosis"><option value="normal">Normal</option><option value="tablet">Tablet</option></select>
                    </div></div>
                    <div class="col-md-2"><div class="form-group">
                        <label class="d-none d-md-block">&nbsp;</label>
                        <button type="button" class="btn btn-erm-accent btn-block tambah-obat">Tambah</button>
                    </div></div>
                </div>`;
        return `
            <div class="racikan-card" data-racikan-ke="${ke}">
                <div class="racikan-card-head">
                    <h5>${racikanTitleHtml(ke, o.nama)} <span class="racikan-harga-detail"></span></h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-erm-soft edit-racikan${o.saved ? '' : ' d-none'}">Edit Racikan</button>
                        <button type="button" class="btn btn-sm btn-erm-danger hapus-racikan">Hapus Racikan</button>
                    </div>
                </div>
                ${addRow}
                <div class="table-responsive">
                    <table class="table table-bordered eresep-table">
                        <thead>
                            <tr>
                                <th>Nama Obat</th><th>Dosis Obat</th><th>Dosis Racik</th>
                                <th class="text-right">Harga Satuan</th><th class="text-right">Harga Akhir</th>
                                <th class="text-center">Sisa Stok</th><th class="eresep-col-aksi">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="resep-table-body">
                            ${o.rows || '<tr class="no-data"><td colspan="7" class="text-center text-muted">Belum ada obat</td></tr>'}
                        </tbody>
                    </table>
                </div>
                <div class="add-medication-form d-none">
                    <div class="eresep-subcard">
                        <div class="erm-subhead">Tambah Obat ke Racikan ${ke}</div>
                        <div class="row">
                            <div class="col-md-6">
                                <label>Nama Obat</label>
                                <select class="form-control select2-obat-racikan-add" style="width: 100%;"><option value="">Pilih Obat</option></select>
                            </div>
                            <div class="col-md-2">
                                <label>Dosis</label>
                                <input type="number" class="form-control dosis-racikan-add" placeholder="Dosis" step="0.01" min="0">
                            </div>
                            <div class="col-md-2">
                                <label>Mode</label>
                                <select class="form-control mode-racikan-add"><option value="manual">Manual</option><option value="tablet">Per Tablet</option></select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-erm-accent btn-block btn-add-medication">Tambah</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <label>Wadah</label>
                        <select class="form-control select2-wadah-racikan wadah" name="wadah_id"${dis}>
                            ${o.wadahId ? `<option value="${escapeAttr(o.wadahId)}" selected>${escapeObatMarkup(o.wadahNama || '')}</option>` : '<option value="">Pilih wadah...</option>'}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Bungkus</label>
                        <input type="number" class="form-control jumlah_bungkus bungkus" min="1" value="${escapeAttr(o.bungkus)}"${dis}>
                    </div>
                    <div class="col-md-6">
                        <label>Aturan Pakai</label>
                        <input type="text" class="form-control aturan_pakai" value="${escapeAttr(o.aturan)}"${dis}>
                    </div>
                </div>
                <button type="button" class="btn btn-block racikan-save-btn tambah-resepracikan"${dis}>${o.saved ? 'Sudah Disimpan' : 'Simpan Racikan ' + ke}</button>
                <button type="button" class="btn btn-block racikan-save-btn is-primary update-resepracikan d-none">Update Racikan</button>
            </div>`;
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

    function applyInvoiceLock() {
        if (!IS_INVOICE_LOCKED) return;

        const root = $('.container-fluid').first();
        const lockScope = $('#eresep-area');

        // Disable editable form controls inside the resep area
        lockScope.find('input, select, textarea').prop('disabled', true);

        // If select2 already initialized, keep it visually consistent
        lockScope.find('select.select2-hidden-accessible').each(function(){
            $(this).prop('disabled', true).trigger('change.select2');
        });

        // Disable only mutation buttons/controls (keep navigation/history buttons usable)
        const mutationSelectors = [
            '#tambah-resep',
            '#tambah-racikan',
            '#simpan-catatan',
            '#paket-racikan',
            '#saveEditObatBtn',
            '.edit',
            '.edit-racikan',
            '.update-resepracikan',
            '.hapus',
            '.hapus-racikan',
            '.hapus-obat',
            '.tambah-obat',
            '.tambah-resepracikan',
            '.btn-add-medication',
            '.edit-obat',
            '.copy-paket',
            '.delete-paket',
            '.edit-paket'
        ].join(',');

        root.find(mutationSelectors).prop('disabled', true).addClass('disabled');

        // Block mutation form submits only
        $(document).on('submit.invoiceLock', '#editObatForm, #formPaketRacikan', function(e){
            e.preventDefault();
            showInvoiceLockedMessage();
        });

        // Block mutation clicks (in case some elements are not buttons)
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

    function formatHargaNonFornas(value) {
        const numericValue = Number(value);

        if (!Number.isFinite(numericValue) || numericValue <= 0) {
            return 'Harga jual belum diatur';
        }

        return `Harga Jual: Rp ${new Intl.NumberFormat('id-ID').format(numericValue)}`;
    }

    function escapeObatMarkup(value) {
        return $('<div>').text(value || '').html();
    }

    function mapObatSearchResults(data) {
        const items = Array.isArray(data.results) ? data.results : (Array.isArray(data) ? data : []);

        return {
            results: items.map(function(item) {
                const text = item.text || [item.nama, item.dosis, item.satuan].filter(Boolean).join(' ');

                return {
                    id: item.id,
                    text: text,
                    nama: item.nama,
                    zat_aktif: item.zat_aktif || '',
                    dosis: item.dosis,
                    satuan: item.satuan,
                    satuan_stok: item.satuan_stok,
                    stok: item.stok,
                    stok_gudang: item.stok_gudang,
                    harga_nonfornas: item.harga_nonfornas,
                    harga_nonfornas_formatted: item.harga_nonfornas_formatted || formatHargaNonFornas(item.harga_nonfornas)
                };
            })
        };
    }

    function renderObatOption(item) {
        if (!item.id) {
            return escapeObatMarkup(item.text || '');
        }

        const secondaryText = item.harga_nonfornas_formatted || formatHargaNonFornas(item.harga_nonfornas);
        const stok = item.stok_gudang !== undefined ? item.stok_gudang : item.stok;
        const stokText = stok !== undefined ? ` · Stok ${parseInt(stok, 10) || 0}` : '';

        return `${escapeObatMarkup(item.text)} - ${escapeObatMarkup(secondaryText)}${stokText}`;
    }

    function renderObatSelection(item) {
        if (!item.id) {
            return item.text || '';
        }

        const secondaryText = item.harga_nonfornas_formatted || formatHargaNonFornas(item.harga_nonfornas);

        return `${item.text} | ${secondaryText}`;
    }

    function getObatSearchParams(params, includeMetodeBayar) {
        const payload = {
            q: params.term || '',
            visitation_id: $('#visitation_id').val()
        };

        if (includeMetodeBayar) {
            payload.metode_bayar_id = {{ $visitation->metode_bayar_id ?? 'null' }};
        }

        return payload;
    }

        // MODAL HTML for editing dosis
        const editObatModalHtml = `
        <div class="modal fade" id="editObatModal" tabindex="-1" role="dialog" aria-labelledby="editObatModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="editObatModalLabel">Edit Dosis Racik</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <form id="editObatForm">
                  <div class="form-group">
                    <label for="editDosisRacik">Dosis Racik</label>
                    <input type="text" class="form-control" id="editDosisRacik" name="dosis_racik" required>
                  </div>
                  <input type="hidden" id="editObatRowId">
                </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveEditObatBtn">Simpan</button>
              </div>
            </div>
          </div>
        </div>
        `;
        // Append modal to body if not exists
        if ($('#editObatModal').length === 0) {
            $('body').append(editObatModalHtml);
        }

        // Handle Edit button click in racikan table
        $('#racikan-container').on('click', '.edit-obat', function () {
            const row = $(this).closest('tr');
            $('#editDosisRacik').val(row.find('td').eq(2).text().trim());
            // Keep the row itself: a row index alone matched the same row number in the first racikan card
            $('#editObatModal').data('row', row).modal('show');
        });

        // Save edited dosis racik (applied on "Update Racikan")
        $('#saveEditObatBtn').on('click', function () {
            const newDosis = $('#editDosisRacik').val().trim();
            const row = $('#editObatModal').data('row');
            if (!newDosis) {
                swalWarn('Dosis racik tidak boleh kosong.');
                return;
            }
            if (!row || !row.length) return;

            row.find('td').eq(2).text(newDosis);
            const dosisObat = parseFloat(row.find('td').eq(1).text()) || 0;
            const hargaSatuan = parseFloat(row.attr('data-harga-satuan')) || 0;
            const hargaAkhir = dosisObat > 0 ? (parseFloat(newDosis) || 0) / dosisObat * hargaSatuan : 0;
            row.attr('data-harga-akhir', hargaAkhir);
            row.find('td').eq(4).text(formatRupiah(hargaAkhir));
            $('#editObatModal').modal('hide');
            updateTotalPrice();
        });
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
    // Obat ids already in the farmasi resep (non racikan / racikan), refreshed by refreshRowColors()
    let farmasiObatIds = (@json($farmasiObatIds ?? [])).map(String);
    let farmasiRacikanObatIds = (@json($farmasiRacikanObatIds ?? [])).map(String);

    function farmasiRowClass(obatId, racikan) {
        return (racikan ? farmasiRacikanObatIds : farmasiObatIds).includes(String(obatId)) ? 'text-success row-in-farmasi' : '';
    }

    function toggleEmptyRacikan() {
        $('.eresep-empty-racikan').toggleClass('d-none', $('#racikan-container .racikan-card').length > 0);
    }

    $(document).ready(function () {
    applyInvoiceLock();
    startInvoiceLockPolling();
    // Handlers outside this ready block (edit mode racikan) use it too
    window.updateTotalPrice = updateTotalPrice;

        // Always disable hapus-obat buttons in racikan on page load
        $('#racikan-container .hapus-obat').prop('disabled', true).addClass('disabled');

        $('.select2').select2({ width: '100%' });
        $('.select2-obat').select2({
            placeholder: 'Search obat...',
            minimumInputLength: 3,
            ajax: {
                url: '{{ route("obat.search") }}',
                dataType: 'json',
                delay: 150,
                data: function (params) {
                    return getObatSearchParams(params, true);
                },
                processResults: function (data) {
                    return mapObatSearchResults(data);
                },
                cache: true
            },
            escapeMarkup: function (markup) {
                return markup;
            },
            templateResult: function (data) {
                return renderObatOption(data);
            },
            templateSelection: function (data) {
                return renderObatSelection(data);
            }
        });

            // Show warning if selected obat has stock 0 (non-racikan)
                $('.select2-obat').on('select2:select', function(e) {
                    var data = e.params.data;
                    var stokSelected = (data.stok_gudang !== undefined ? data.stok_gudang : data.stok);
                    if (stokSelected !== undefined && parseInt(stokSelected) === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stok Obat Kosong',
                            text: 'Obat yang Anda pilih memiliki stok 0. Silakan pilih obat lain atau konfirmasi ke farmasi.',
                            confirmButtonText: 'OK'
                        });
                    }
                });
        $('.select2-wadah-racikan').select2({
            placeholder: 'Search wadah...',
            ajax: {
                url: '{{ route("wadah.search") }}', // Use the wadah.search route
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term // Search term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.map(item => ({
                            id: item.id,
                            text: item.text // Adjust based on your API response structure
                        }))
                    };
                },
                cache: true
            },

        });

        // Aturan pakai template not used on dokter resep page (only farmasi uses it)

        // Mark rows whose obat farmasi already has (one request for the whole page, not one per row)
        function refreshRowColors() {
            $.get("{{ route('erm.eresepfarmasi.json', $visitation->id) }}").done(function (response) {
                farmasiObatIds = (response.nonRacikans || []).map(item => String(item.obat_id));
                farmasiRacikanObatIds = [];
                Object.values(response.racikans || {}).forEach(function (group) {
                    (group || []).forEach(item => farmasiRacikanObatIds.push(String(item.obat_id)));
                });

                $('#resep-table-body tr[data-obat-id]').each(function () {
                    $(this).toggleClass('text-success row-in-farmasi', farmasiObatIds.includes(String($(this).data('obat-id'))));
                });
                $('#racikan-container .resep-table-body tr[data-obat-id]').each(function () {
                    $(this).toggleClass('text-success row-in-farmasi', farmasiRacikanObatIds.includes(String($(this).data('obat-id'))));
                });
            });
        }

        setTimeout(refreshRowColors, 1000);
        setInterval(refreshRowColors, 30000);

        // STORE NON RACIKAN
        $('#tambah-resep').on('click', function () {
            const $btn = $(this);
            let obatId = $('#obat_id').val();
            let jumlah = parseInt($('#jumlah').val(), 10);
            let aturanPakai = ($('#aturan_pakai').val() || '').trim();
            let visitationId = $('#visitation_id').val();

            if (!obatId || !aturanPakai || !jumlah) {
                swalWarn('Nama obat, jumlah dan aturan pakai wajib diisi.');
                return;
            }
            if (jumlah < 1) {
                swalWarn('Jumlah minimal 1.');
                return;
            }
            if ($btn.prop('disabled')) return;
            $btn.prop('disabled', true);

            // Send data via AJAX
            $.ajax({
                url: "{{ route('resep.nonracikan.store') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    tipe: "nonracikan",
                    obat_id: obatId,
                    jumlah: jumlah,
                    aturan_pakai: aturanPakai,
                    visitation_id: visitationId
                },
                success: function (res) {
                    // Remove the "no data" row if it exists
                    $('#resep-table-body .no-data').remove();

                    const d = res.data;
                    const harga = parseFloat(d.obat.harga_nonfornas) || 0;
                    const stok = parseInt(d.obat.stok_gudang, 10) || 0;
                    $('#resep-table-body').prepend(`
                        <tr data-id="${d.id}" data-obat-id="${d.obat_id}" data-harga="${harga}" class="${farmasiRowClass(d.obat_id, false)}">
                            <td class="font-weight-bold">${escapeObatMarkup(d.obat.nama)}</td>
                            <td class="text-right">${formatRupiah(harga)}</td>
                            <td class="text-center">${d.jumlah}</td>
                            <td class="text-center ${stokClass(stok)}">${stok}</td>
                            <td class="text-right">${formatRupiah(harga * (d.jumlah || 0))}</td>
                            <td>${escapeObatMarkup(d.aturan_pakai)}</td>
                            <td class="eresep-col-aksi">
                                <button type="button" class="btn btn-sm btn-erm-soft edit" data-id="${d.id}">Edit</button>
                                <button type="button" class="btn btn-sm btn-erm-danger hapus" data-id="${d.id}">Hapus</button>
                            </td>
                        </tr>
                    `);
                    updateTotalPrice();

                    // Clear the input fields
                    $('#obat_id').val(null).trigger('change');
                    $('#jumlah').val('');
                    AturanPakai.get('#aturan_pakai').reset();
                    // Set focus to Nama Obat input after adding
                    setTimeout(function() {
                        $('#obat_id').select2('open');
                    }, 200);
                },
                error: function (xhr) {
                    Swal.fire({ icon: 'error', title: 'Gagal Menambahkan', text: ajaxErrorMessage(xhr, 'Gagal menambahkan resep.') });
                },
                complete: function () {
                    $btn.prop('disabled', IS_INVOICE_LOCKED);
                }
            });
        });

        //DELETE NON RACIKAN
        $('#resep-table-body').on('click', '.hapus', function () {
            const row = $(this).closest('tr');
            const resepId = row.data('id');

            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Yakin ingin menghapus resep ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: "{{ route('resep.nonracikan.destroy', '') }}/" + resepId,
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
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: 'Resep berhasil dihapus',
                                confirmButtonColor: '#3085d6'
                            });
                        },
                        error: function () {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Menghapus',
                                text: 'Gagal menghapus resep. Coba lagi.',
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    });
                }
            });
        });

        // SIMPAN CATATAN DOKTER INPUT
        $('#simpan-catatan').on('click', function() {
            let catatan = $('#catatan_dokter').val();
            let visitationId = $('#visitation_id').val();
            if (!catatan || !catatan.trim()) {
                swalWarn('Catatan tidak boleh kosong.');
                return;
            }
            $.ajax({
                url: "{{ route('resep.catatan.store') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    visitation_id: visitationId,
                    catatan_dokter: catatan
                },
                success: function(res) {
                    Swal.fire({ icon: 'success', title: res.message || 'Catatan berhasil disimpan.', timer: 1500, showConfirmButton: false });
                },
                error: function(xhr) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: ajaxErrorMessage(xhr, 'Gagal menyimpan catatan.') });
                }
            });
        });

        // UPDATE HARGA (prices come from data-* attributes; the cells hold formatted "Rp 12.500" text)
        function updateTotalPrice() {
            let total = 0;
            // Non Racikan
            $('#resep-table-body tr[data-id]').each(function () {
                let harga = parseFloat($(this).attr('data-harga')) || 0;
                let jumlah = parseFloat($(this).find('td').eq(2).text()) || 0;
                total += harga * jumlah;
            });
            // Racikan
            $('#racikan-container .racikan-card').each(function () {
                let racikanTotal = 0;
                let bungkus = parseFloat($(this).find('.jumlah_bungkus, .bungkus').val()) || 1;
                $(this).find('.resep-table-body tr[data-obat-id]').each(function () {
                    // rows removed in edit mode stay hidden until "Update Racikan"
                    if ($(this).hasClass('obat-deleted')) return;
                    racikanTotal += parseFloat($(this).attr('data-harga-akhir')) || 0;
                });
                // Formula beside "Racikan N": harga per bungkus x bungkus = total
                $(this).find('.racikan-harga, .racikan-harga-detail').text(`${formatRupiah(racikanTotal)} x ${bungkus} = ${formatRupiah(racikanTotal * bungkus)}`);
                total += racikanTotal * bungkus;
            });
            $('#total-harga').html('<strong>' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(total) + '</strong>');
        }

        $('#racikan-container').on('input change', '.bungkus, .jumlah_bungkus', updateTotalPrice);

        // TAMBAH RACIKAN BARU
        $('#tambah-racikan').on('click', function () {
            racikanCount++;

            $('#racikan-container').append(racikanCardHtml({ ke: racikanCount, saved: false }));
            const card = $('#racikan-container .racikan-card').last();
            toggleEmptyRacikan();

            card.find('.select2-obat-racikan').select2({
                placeholder: 'Cari obat...',
                ajax: {
                    url: '{{ route("obat.search") }}',
                    dataType: 'json',
                    delay: 150,
                    data: function (params) {
                        return getObatSearchParams(params, true);
                    },
                    processResults: function (data) {
                        return mapObatSearchResults(data);
                    },
                    cache: true
                },
                escapeMarkup: function (markup) {
                    return markup;
                },
                templateResult: function (data) {
                    return renderObatOption(data);
                },
                templateSelection: function (data) {
                    return renderObatSelection(data);
                },
                minimumInputLength: 3
            });
            initWadahSelect(card.find('.select2-wadah-racikan'));
        });

        // TAMBAH OBAT KE RACIKAN (card not saved yet)
        $('#racikan-container').on('click', '.tambah-obat', function () {
            const card = $(this).closest('.racikan-card');
            const obatSelect = card.find('.select2-obat-racikan');
            const obatId = obatSelect.val();
            const selectedOption = obatSelect.select2('data')[0] || {};
            const dosisInput = parseFloat(card.find('.dosis_input').val()) || 0;

            if (!obatId || dosisInput <= 0) {
                swalWarn('Nama obat dan dosis harus diisi.');
                return;
            }
            if (card.find('.resep-table-body tr[data-obat-id="' + obatId + '"]').not('.obat-deleted').length) {
                swalWarn('Obat ini sudah ada dalam racikan ini.', 'Obat Sudah Ada');
                return;
            }

            const defaultDosis = parseFloat(selectedOption.dosis) || 0;
            const satuan = selectedOption.satuan || '';
            // "Tablet" mode: dosis is a number of tablets, so multiply by the obat's own dosis
            const dosisAkhir = card.find('.mode_dosis').val() === 'tablet' ? defaultDosis * dosisInput : dosisInput;
            const stok = selectedOption.stok_gudang !== undefined ? selectedOption.stok_gudang : selectedOption.stok;

            const tbody = card.find('.resep-table-body');
            tbody.find('.no-data').remove();
            tbody.append(racikanRowHtml({
                obatId: obatId,
                nama: selectedOption.nama || selectedOption.text,
                dosisObat: selectedOption.dosis,
                dosisRacik: (dosisAkhir + ' ' + satuan).trim(),
                hargaSatuan: selectedOption.harga_nonfornas,
                stok: stok,
                rowClass: farmasiRowClass(obatId, true),
                editable: true
            }));
            obatSelect.val(null).trigger('change');
            card.find('.dosis_input').val('');
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
                swalWarn('Wadah, Bungkus dan Aturan Pakai wajib diisi.');
                return;
            }
            if (bungkus < 1) {
                swalWarn('Bungkus minimal 1.');
                return;
            }

            const obats = [];
            card.find('.resep-table-body tr[data-obat-id]').not('.obat-deleted').each(function () {
                const dbId = $(this).find('td[data-id]').attr('data-id') || null;
                obats.push({ id: dbId, obat_id: $(this).data('obat-id'), dosis: $(this).find('td').eq(2).text().trim() });
            });

            if (obats.length === 0) {
                swalWarn('Tambahkan minimal satu obat dalam racikan.');
                return;
            }

            $btn.prop('disabled', true).text('Menyimpan...');
            $.ajax({
                url: "{{ route('resep.racikan.store') }}",
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
                    if ('racikan_nama' in res) setRacikanTitle(card, res.racikan_nama);
                    Swal.fire({ icon: 'success', title: res.message || 'Racikan berhasil disimpan.', timer: 1500, showConfirmButton: false });
                    $btn.text('Sudah Disimpan');
                    // Saved: lock the card like a server-rendered one (edit via "Edit Racikan")
                    card.find('.racikan-add-row, .obat-alt').remove();
                    card.find('.wadah, .bungkus, .jumlah_bungkus, .aturan_pakai').prop('disabled', true);
                    card.find('.edit-obat, .hapus-obat').prop('disabled', true).addClass('disabled');
                    card.find('.edit-racikan').removeClass('d-none');
                    updateTotalPrice();
                    setTimeout(refreshRowColors, 500);
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).text('Simpan Racikan ' + card.data('racikan-ke'));
                    Swal.fire({ icon: 'error', title: 'Gagal Menyimpan', text: ajaxErrorMessage(xhr, 'Gagal menyimpan racikan.') });
                }
            });
        });

        // DELETE OBAT (marked, removed on "Update Racikan"; unsaved cards drop the row right away)
        $('#racikan-container').on('click', '.hapus-obat', function () {
            if ($(this).is(':disabled') || $(this).hasClass('disabled')) {
                return;
            }
            const row = $(this).closest('tr');
            const card = row.closest('.racikan-card');
            if (row.find('td[data-id]').attr('data-id')) {
                row.addClass('obat-deleted').hide();
            } else {
                row.remove();
            }
            if (card.find('.resep-table-body tr[data-obat-id]').not('.obat-deleted').length === 0) {
                card.find('.resep-table-body').append(`<tr class="no-data"><td colspan="7" class="text-center text-muted">Belum ada obat</td></tr>`);
            }
            updateTotalPrice();
        });

        // DELETE RACIKAN
        $(document).on('click', '.hapus-racikan', function () {
            const card = $(this).closest('.racikan-card');
            const racikanKe = card.data('racikan-ke');
            const visitationId = $('#visitation_id').val();

            // Not saved yet (its "Simpan" button is still active): nothing on the server to delete
            if (!card.find('.tambah-resepracikan').prop('disabled')) {
                card.remove();
                toggleEmptyRacikan();
                updateTotalPrice();
                return;
            }

            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Yakin ingin menghapus Racikan ' + racikanKe + '?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: "{{ route('resep.racikan.destroy', ':racikanKe') }}".replace(':racikanKe', racikanKe),
                        method: "DELETE",
                        data: {
                            _token: "{{ csrf_token() }}",
                            visitation_id: visitationId,
                        },
                        success: function (res) {
                            Swal.fire({ icon: 'success', title: res.message || 'Racikan dihapus', timer: 1500, showConfirmButton: false });
                            card.remove();
                            toggleEmptyRacikan();
                            updateTotalPrice();
                        },
                        error: function (xhr) {
                            Swal.fire({ icon: 'error', title: 'Gagal Menghapus', text: ajaxErrorMessage(xhr, 'Gagal menghapus racikan.') });
                        }
                    });
                }
            });
        });
         // EDIT NON RACIKAN
        $('#resep-table-body').on('click', '.edit', function() {
            const row = $(this).closest('tr');
            const id    = row.data('id');
            const jumlah = row.find('td').eq(2).text().trim();
            // const rawDiskon = row.find('td').eq(3).text().trim();   
            // const diskonValue = rawDiskon.replace('%', '').trim();
            const aturan = row.find('td').eq(5).text().trim();

            $('#edit-resep-id').val(id);
            $('#edit-jumlah').val(jumlah);
            // $('#edit-diskon').val(diskonValue);
            const apEdit = AturanPakai.get('#edit-aturan');
            apEdit.setValue(aturan);
            apEdit.setObat(row.data('obat-id'));
            $('#editResepModal').modal('show');
        });
        // STORE EDIT NON RACIKAN
        $('#edit-resep-form').on('submit', function(e) {
            e.preventDefault();

            const id = $('#edit-resep-id').val();
            const url = "{{ route('resep.nonracikan.update', '') }}/" + id;
            const data = {
                _token: "{{ csrf_token() }}",
                _method: 'PUT',
                jumlah: $('#edit-jumlah').val(),
                aturan_pakai: $('#edit-aturan').val()
            };

            $.post(url, data)
            .done(function(res) {
                const row = $('#resep-table-body').find('tr[data-id="'+ id +'"]');
                const hargaSatuan = parseFloat(row.attr('data-harga')) || 0;
                row.find('td').eq(2).text(res.data.jumlah);
                row.find('td').eq(4).text(formatRupiah(res.data.jumlah * hargaSatuan));
                row.find('td').eq(5).text(res.data.aturan_pakai);
                $('#editResepModal').modal('hide');
                updateTotalPrice();
            })
            .fail(function(xhr) {
                Swal.fire({ icon: 'error', title: 'Gagal Menyimpan', text: ajaxErrorMessage(xhr, 'Gagal menyimpan perubahan.') });
            });
        });

        // UPDATE RACIKAN ("Edit Racikan" itself is handled by the document-level handler below)
        $('#racikan-container').on('click', '.update-resepracikan', function () {
            const $btn = $(this);
            const card = $btn.closest('.racikan-card');
            const racikanKe = card.data('racikan-ke');
            const visitationId = $('#visitation_id').val();
            const wadah = card.find('.wadah').val();
            const bungkus = parseInt(card.find('.bungkus').val() || card.find('.jumlah_bungkus').val(), 10);
            const aturanPakai = (card.find('.aturan_pakai').val() || '').trim();

            // Remaining obat rows (rows removed in edit mode are hidden with .obat-deleted)
            const obats = [];
            card.find('.resep-table-body tr[data-obat-id]').not('.obat-deleted').each(function() {
                obats.push({
                    id: $(this).find('td[data-id]').attr('data-id') || null, // null = new row
                    obat_id: $(this).data('obat-id'),
                    dosis: $(this).find('td').eq(2).text().trim()
                });
            });

            if (!wadah || !bungkus || !aturanPakai) {
                swalWarn('Wadah, Bungkus dan Aturan Pakai wajib diisi.');
                return;
            }
            if (bungkus < 1) {
                swalWarn('Bungkus minimal 1.');
                return;
            }
            if (obats.length === 0) {
                swalWarn('Racikan tanpa obat tidak bisa disimpan. Gunakan "Hapus Racikan" untuk menghapusnya.');
                return;
            }

            $btn.prop('disabled', true);
            $.ajax({
                url: "{{ route('resep.racikan.update', '') }}/" + racikanKe,
                method: "PUT",
                data: {
                    _token: "{{ csrf_token() }}",
                    visitation_id: visitationId,
                    wadah: wadah,
                    bungkus: bungkus,
                    aturan_pakai: aturanPakai,
                    obats: obats
                },
                success: function (res) {
                    Swal.fire({ icon: 'success', title: res.message || 'Racikan berhasil diupdate!', timer: 1500, showConfirmButton: false });
                    if ('racikan_nama' in res) setRacikanTitle(card, res.racikan_nama);
                    card.find('.resep-table-body tr.obat-deleted').remove();
                    // New rows got database ids: store them so the next update edits instead of re-creating
                    (res.obats || []).forEach(function (ob) {
                        card.find('.resep-table-body tr[data-obat-id="' + ob.obat_id + '"] td[data-id=""]').first().attr('data-id', ob.id);
                    });
                    card.find('.wadah, .jumlah_bungkus, .bungkus, .aturan_pakai').prop('disabled', true);
                    card.find('.tambah-resepracikan').removeClass('d-none');
                    card.find('.update-resepracikan').addClass('d-none');
                    card.find('.add-medication-form').addClass('d-none');
                    card.find('.edit-obat, .hapus-obat').prop('disabled', true).addClass('disabled');
                    updateTotalPrice();
                    setTimeout(refreshRowColors, 500);
                },
                error: function (xhr) {
                    Swal.fire({ icon: 'error', title: 'Gagal Update', text: ajaxErrorMessage(xhr, 'Gagal mengupdate racikan.') });
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        });

        // MODAL RIWAYAT
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
                confirmButtonText: 'Ya, Salin',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.value) copyResepFromHistory($button, '');
            });
        });

        function copyResepFromHistory($button, onDuplicate) {
            const label = $button.html();
            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyalin...');
            $.ajax({
                url: "{{ route('erm.eresep.copyfromhistory') }}",
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
                Swal.fire({ icon: 'error', title: 'Gagal', text: ajaxErrorMessage(xhr, 'Gagal menyalin resep. Silakan coba lagi.') });
            }).always(function () {
                $button.prop('disabled', false).html(label);
            });
        }

        // Resolves 'skip' (only what is new), 'include' (also the ones already there) or null (cancelled)
        function askCopyDuplicates(res) {
            const list = (res.duplicates || []).map(nama => `<li>${escapeObatMarkup(nama)}</li>`).join('');
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
                const harga = parseFloat(d.harga_nonfornas) || 0;
                $('#resep-table-body').prepend(`
                    <tr data-id="${d.id}" data-obat-id="${d.obat_id}" data-harga="${harga}" class="${farmasiRowClass(d.obat_id, false)}">
                        <td class="font-weight-bold">${escapeObatMarkup(d.obat_nama)}</td>
                        <td class="text-right">${formatRupiah(harga)}</td>
                        <td class="text-center">${d.jumlah}</td>
                        <td class="text-center ${stokClass(d.stok_gudang)}">${parseInt(d.stok_gudang, 10) || 0}</td>
                        <td class="text-right">${formatRupiah(harga * (d.jumlah || 0))}</td>
                        <td>${escapeObatMarkup(d.aturan_pakai)}</td>
                        <td class="eresep-col-aksi">
                            <button type="button" class="btn btn-sm btn-erm-soft edit" data-id="${d.id}">Edit</button>
                            <button type="button" class="btn btn-sm btn-erm-danger hapus" data-id="${d.id}">Hapus</button>
                        </td>
                    </tr>
                `);
            });

            (added.racikans || []).forEach(function (r) {
                const rows = (r.items || []).map(it => racikanRowHtml({
                    dbId: it.id,
                    obatId: it.obat_id,
                    nama: it.obat_nama,
                    dosisObat: it.obat_dosis,
                    dosisRacik: it.dosis,
                    hargaSatuan: it.harga_nonfornas,
                    stok: it.stok_gudang,
                    rowClass: farmasiRowClass(it.obat_id, true)
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

            toggleEmptyRacikan();
            updateTotalPrice();
            setTimeout(refreshRowColors, 500);
        }
        
        updateTotalPrice(); // 

        // ATURAN PAKAI: free text with "sering dipakai" recommendations from resep history
        // ALTERNATIF: after an obat is chosen, offer obat with kandungan in common (separate request, search stays fast)
        ObatAlternatif.bind('#eresep-area #obat_id', {
            params: () => getObatSearchParams({}, true),
            mapItem: item => mapObatSearchResults({ results: [item] }).results[0]
        });
        ObatAlternatif.bind('#eresep-area .select2-obat-racikan, #eresep-area .select2-obat-racikan-add', {
            params: $select => getObatSearchParams({}, !$select.hasClass('select2-obat-racikan-add')),
            mapItem: item => mapObatSearchResults({ results: [item] }).results[0]
        });

        const apNonRacikan = AturanPakai.mount('#aturan_pakai');
        AturanPakai.mount('#edit-aturan');
        AturanPakai.autoMount('#racikan-container', { racikan: true });
        AturanPakai.autoMount('#paketRacikanModal', { racikan: true });
        AturanPakai.autoMount('#gunakanPaketModal', { racikan: true });
        $('#obat_id').on('select2:select', function (e) {
            apNonRacikan.setObat(e.params.data.id);
        });
        $('#obat_id').on('select2:clear', function () { apNonRacikan.setObat(null); });

        // PAKET RACIKAN FUNCTIONALITY
        let obatPaketCount = 0;

        // DataTable instance for Paket Racikan list (modal)
        let paketRacikanDt = null;

        function ensurePaketRacikanDataTable() {
            // Fallback gracefully if DataTables plugin is not available
            if (!$.fn || !$.fn.DataTable) return null;

            if ($.fn.DataTable.isDataTable('#paketRacikanTable')) {
                paketRacikanDt = $('#paketRacikanTable').DataTable();
                return paketRacikanDt;
            }

            paketRacikanDt = $('#paketRacikanTable').DataTable({
                paging: true,
                pagingType: 'simple',
                pageLength: 10,
                lengthChange: false,
                searching: false, // we use external input #searchPaketRacikan
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
            
            // Load list
            loadPaketRacikanList();
            
            // Show modal
            $('#paketRacikanModal').modal('show');
        });
        
        // Initialize selects when modal is fully shown
        $('#paketRacikanModal').on('shown.bs.modal', function() {
            initializePaketRacikanSelects();

            // Ensure DataTables layout is correct when the table becomes visible
            const dt = ensurePaketRacikanDataTable();
            if (dt) {
                setTimeout(function(){ dt.columns.adjust(); }, 0);
            }
        });

        // Reset button click handler
        $(document).on('click', '#resetFormPaketBtn', function() {
            resetFormPaket();
        });

        // Load Paket Racikan List (supports optional search term and multiple response shapes)
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
                        // Fallback: plain table rendering (no pagination)
                        let tbody = $('#paketRacikanTableBody');
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
                        const tbody = $('#paketRacikanTableBody');
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
        $(document).on('input', '#searchPaketRacikan', debounce(function(){
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
                    data: function (params) {
                        return { q: params.term, visitation_id: $('#visitation_id').val() };
                    },
                    processResults: function (data) {
                        return { results: data };
                    },
                    cache: true
                }
            });

            // Initialize obat select2 (ensure dropdownParent so it renders inside modal)
            $('.select2-obat-paket').select2({
                placeholder: 'Pilih Obat',
                allowClear: true,
                ajax: {
                    url: '{{ route("obat.search") }}',
                    dataType: 'json',
                    delay: 150,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        if (Array.isArray(data.results)) {
                            return { results: data.results };
                        } else {
                            return { results: data };
                        }
                    },
                    cache: true
                },
                minimumInputLength: 2,
                dropdownParent: $('#paketRacikanModal')
            });
        }

        // Add Obat to Paket
        $('#tambahObatPaket').on('click', function() {
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
            
            $('#obatPaketContainer').append(newObatItem);
            
            // Initialize select2 for the newly added obat select (ensure dropdownParent)
            let newSelect = $('#obatPaketContainer .select2-obat-paket').last();
            newSelect.select2({
                placeholder: 'Pilih Obat',
                allowClear: true,
                ajax: {
                    url: '{{ route("obat.search") }}',
                    dataType: 'json',
                    delay: 150,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        // obat.search answers { results: [...] }
                        return { results: Array.isArray(data.results) ? data.results : data };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                dropdownParent: $('#paketRacikanModal')
            });
            
            updateRemoveButtons();
        });

        // Remove Obat from Paket
        $(document).on('click', '.remove-obat-paket', function() {
            $(this).closest('.obat-paket-item').remove();
            updateRemoveButtons();
        });

        function updateRemoveButtons() {
            let items = $('.obat-paket-item');
            if (items.length > 1) {
                $('.remove-obat-paket').show();
            } else {
                $('.remove-obat-paket').hide();
            }
        }

        // Save Paket Racikan
        $('#formPaketRacikan').on('submit', function(e) {
            e.preventDefault();
            
            let formData = new FormData(this);
            let obatsData = [];
            
            $('.obat-paket-item').each(function() {
                let obatId = $(this).find('select').val();
                let dosis = $(this).find('input[type="text"]').val();
                
                if (obatId && dosis) {
                    obatsData.push({
                        obat_id: obatId,
                        dosis: dosis
                    });
                }
            });
            
            if (obatsData.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Data Belum Lengkap',
                    text: 'Minimal harus ada satu obat dalam paket',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }
            
            const paketId = (formData.get('id') || '').toString().trim();
            const wadahIdRaw = formData.get('wadah_id');
            const wadahId = (wadahIdRaw === null || wadahIdRaw === undefined || wadahIdRaw === '') ? null : wadahIdRaw;

            let data = {
                _token: "{{ csrf_token() }}",
                nama_paket: formData.get('nama_paket'),
                deskripsi: formData.get('deskripsi'),
                wadah_id: wadahId,
                bungkus_default: formData.get('bungkus_default'),
                aturan_pakai_default: formData.get('aturan_pakai_default'),
                obats: obatsData
            };

            // If paketId exists, update instead of create
            let url = "{{ route('erm.paket-racikan.store') }}";
            if (paketId) {
                const updateUrlTemplate = "{{ route('erm.paket-racikan.update', ['id' => '__ID__']) }}";
                url = updateUrlTemplate.replace('__ID__', encodeURIComponent(paketId));
                data._method = 'PUT';
            }
            
            $.ajax({
                url: url,
                method: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            confirmButtonColor: '#3085d6'
                        });
                        resetFormPaket();
                        loadPaketRacikanList();
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan',
                        text: 'Gagal menyimpan paket racikan: ' + (xhr.responseJSON?.message || 'Unknown error'),
                        confirmButtonColor: '#3085d6'
                    });
                }
            });
        });

        // Copy Paket Racikan
        $(document).on('click', '.copy-paket', function() {
            let paketId = $(this).data('id');
            let paketData = $(this).data('paket');
            
            // Set data paket ke modal konfirmasi
            $('#paketNamaDisplay').text(paketData.nama_paket);
            $('#paketBungkus').val(paketData.bungkus_default || 10);
            $('#paketAturanPakai').val(paketData.aturan_pakai_default || '');
            $('#selectedPaketId').val(paketId);
            
            // Tutup modal paket racikan dan buka modal konfirmasi
            $('#paketRacikanModal').modal('hide');
            $('#gunakanPaketModal').modal('show');
        });

        // Konfirmasi gunakan paket racikan
        $(document).on('click', '#konfirmasiGunakanPaket', function() {
            let paketId = $('#selectedPaketId').val();
            let bungkus = $('#paketBungkus').val();
            let aturanPakai = $('#paketAturanPakai').val();
            let visitationId = $('#visitation_id').val();
            
            // Validasi input
            if (!bungkus || !aturanPakai) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Data Belum Lengkap',
                    text: 'Bungkus dan Aturan Pakai harus diisi',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }
            
                // Handler untuk kembali ke modal paket racikan jika user batal di modal konfirmasi
            $(this).html('<i class="fas fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
            
            $.ajax({
                url: "{{ route('erm.paket-racikan.copy') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    paket_racikan_id: paketId,
                    visitation_id: visitationId,
                    bungkus: bungkus,
                    aturan_pakai: aturanPakai
                },
                success: function(response) {
                    if (response.success) {
                        // Tandai modal agar tidak buka ulang
                        $('#gunakanPaketModal').addClass('reload-after-close');
                        
                        // Update racikan counter (never lower it below cards still open on screen)
                        racikanCount = Math.max(racikanCount, parseInt(response.racikan_ke, 10) || 0);
                        
                        // Dapatkan data paket untuk membuat card
                        // Prefer to read paket data from the copy button itself
                        let paketDataRaw = $('.copy-paket[data-id="' + paketId + '"]').first().data('paket');
                        let paketData = paketDataRaw;
                        try { if (typeof paketDataRaw === 'string') paketData = JSON.parse(paketDataRaw); } catch(e) { paketData = paketDataRaw; }
                        
                        if (paketData) {
                            // Buat racikan card dari data paket dengan bungkus dan aturan pakai custom
                            createRacikanCardFromPaketWithCustomData(paketData, response.racikan_ke, bungkus, aturanPakai);

                            // PATCH: Update stok + set row ids from server response (stok depends on gudang mapping)
                            if (response.obats && Array.isArray(response.obats)) {
                                const createdRacikanKe = response.racikan_ke;
                                const card = $('#racikan-container .racikan-card[data-racikan-ke="' + createdRacikanKe + '"]').last();
                                if (card.length) {
                                    const tbody = card.find('.resep-table-body');
                                    response.obats.forEach(function(ob){
                                        const row = tbody.find('tr[data-obat-id="' + ob.obat_id + '"]').first();
                                        if (!row.length) return;

                                        // set DB row id on the first td[data-id]
                                        const td = row.find('td[data-id]').first();
                                        if (td.length) {
                                            td.attr('data-id', ob.id);
                                        }

                                        // update stok cell (column "Sisa Stok" is index 5)
                                        const stokGudang = parseInt(ob.stok_gudang || 0, 10);
                                        row.find('td').eq(5).removeClass('stok-low stok-mid stok-ok').addClass(stokClass(stokGudang)).text(stokGudang);
                                    });
                                }
                            }
                            
                            // Update total price
                            updateTotalPrice();
                            
                            // Refresh row colors for new racikan
                            setTimeout(function() {
                                refreshRowColors();
                            }, 500);
                            
                            // Tutup semua modal
                            $('#paketRacikanModal').modal('hide');
                        }
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message || 'Paket racikan berhasil diterapkan!',
                            confirmButtonColor: '#3085d6',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: response.message || 'Gagal menerapkan paket racikan',
                            confirmButtonColor: '#3085d6'
                        });
                    }
                },
                error: function(xhr) {
                    console.error('Error copying paket:', xhr);
                    let errorMessage = 'Gagal menerapkan paket racikan';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: errorMessage,
                        confirmButtonColor: '#3085d6'
                    });
                },
                complete: function() {
                    $('#konfirmasiGunakanPaket').html('Gunakan Paket').prop('disabled', false);
                    $('#gunakanPaketModal').modal('hide');
                }
            });
        });

        // Edit Paket (populate form for editing) - Dokter
        $(document).on('click', '.edit-paket', function() {
            let paketRaw = $(this).attr('data-paket');
            let paket = null;
            try { paket = paketRaw ? JSON.parse(paketRaw) : $(this).data('paket'); } catch(e) { paket = $(this).data('paket'); }
            if (!paket) return;
            resetFormPaket();
            // set paket id for update
            $('#paketId').val(paket.id || '');
            // populate fields
            $('#formPaketRacikan input[name="nama_paket"]').val(paket.nama_paket || '');
            // left empty, saving would clear the paket's deskripsi
            $('#formPaketRacikan textarea[name="deskripsi"]').val(paket.deskripsi || '');
            // wadah (ajax select2): the option must exist before it can be selected, else the paket loses its wadah on save
            $('.select2-wadah-paket').find('option').not('[value=""]').remove();
            if (paket.wadah && paket.wadah.id) {
                $('.select2-wadah-paket').append(new Option(paket.wadah.nama, paket.wadah.id, true, true)).trigger('change');
            } else {
                $('.select2-wadah-paket').val('').trigger('change');
            }
            setPaketFormMode(paket.nama_paket);
            $('#formPaketRacikan input[name="bungkus_default"]').val(paket.bungkus_default || 10);
            $('#formPaketRacikan input[name="aturan_pakai_default"]').val(paket.aturan_pakai_default || '');

            // populate obat items
            const container = $('#obatPaketContainer');
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
            $('#paketRacikanModal').modal('show');
        });

        // Handler untuk kembali ke modal paket racikan jika user batal di modal konfirmasi
        $('#gunakanPaketModal').on('hidden.bs.modal', function (e) {
            // Jika modal ditutup tanpa konfirmasi, buka kembali modal paket racikan
            if (!$(e.target).hasClass('reload-after-close')) {
                setTimeout(function() {
                    $('#paketRacikanModal').modal('show');
                }, 300);
            }
            // one-shot flag: the next cancel must reopen the list again
            $(e.target).removeClass('reload-after-close');
        });

        // Racikan card for a paket that was just applied (already saved on the server)
        function createRacikanCardFromPaketWithCustomData(paket, racikanKe, customBungkus, customAturanPakai) {
            const rows = (paket.details || []).map(function (detail) {
                const obat = detail.obat || {};
                return racikanRowHtml({
                    obatId: obat.id,
                    nama: obat.nama,
                    dosisObat: obat.dosis,
                    dosisRacik: detail.dosis,
                    hargaSatuan: obat.harga_nonfornas,
                    stok: obat.stok_gudang !== undefined ? obat.stok_gudang : obat.stok,
                    rowClass: farmasiRowClass(obat.id, true)
                });
            }).join('');

            $('#racikan-container').append(racikanCardHtml({
                ke: racikanKe,
                nama: paket.nama_paket || null,
                saved: true,
                rows: rows,
                wadahId: paket.wadah ? paket.wadah.id : (paket.wadah_id || ''),
                wadahNama: paket.wadah ? paket.wadah.nama : '',
                bungkus: customBungkus,
                aturan: customAturanPakai
            }));
            toggleEmptyRacikan();
            initWadahSelect($('#racikan-container .select2-wadah-racikan').last());
        }

        // Show Detail Paket
        $(document).on('click', '.detail-paket', function() {
            let paket = $(this).data('paket');
            let content = `
                <h6><strong>${paket.nama_paket}</strong></h6>
                <p><strong>Deskripsi:</strong> ${paket.deskripsi || '-'}</p>
                <p><strong>Wadah:</strong> ${paket.wadah ? paket.wadah.nama : '-'}</p>
                <p><strong>Bungkus Default:</strong> ${paket.bungkus_default}</p>
                <p><strong>Aturan Pakai Default:</strong> ${paket.aturan_pakai_default || '-'}</p>
                
                <h6><strong>Obat dalam Paket:</strong></h6>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Obat</th>
                            <th>Dosis</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            paket.details.forEach(function(detail, index) {
                content += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${detail.obat.nama}</td>
                        <td>${detail.dosis}</td>
                    </tr>
                `;
            });
            
            content += `
                    </tbody>
                </table>
            `;
            
            $('#detailPaketContent').html(content);
            $('#detailPaketModal').modal('show');
        });

        // Delete Paket Racikan
        $(document).on('click', '.delete-paket', function() {
            let paketId = $(this).data('id');
            
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Yakin ingin menghapus paket racikan ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: "{{ route('erm.paket-racikan.delete', '') }}/" + paketId,
                        method: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: response.message,
                                    confirmButtonColor: '#3085d6'
                                });
                                loadPaketRacikanList();
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Menghapus',
                                text: 'Gagal menghapus paket racikan: ' + (xhr.responseJSON?.message || 'Unknown error'),
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    });
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
            // Destroy select2 instances first
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
            
            // Reset form
            $('#formPaketRacikan')[0].reset();
            $('#paketId').val('');
            
            // Reset obat container
            $('#obatPaketContainer').html(`
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
            
            // Reset counter
            obatPaketCount = 0;
            
            // Re-initialize select2
            initializePaketRacikanSelects();
        }
    });

    // EDIT RACIKAN: unlock the card's fields + obat rows and show the "tambah obat" box
    $(document).on('click', '.edit-racikan', function () {
        if (IS_INVOICE_LOCKED) return;
        const card = $(this).closest('.racikan-card');

        card.find('.wadah, .jumlah_bungkus, .bungkus, .aturan_pakai').prop('disabled', false);
        card.find('.tambah-resepracikan').addClass('d-none');
        card.find('.update-resepracikan').removeClass('d-none');
        card.find('.edit-obat, .hapus-obat').prop('disabled', false).removeClass('disabled');
        card.find('.add-medication-form').removeClass('d-none');

        const select = card.find('.select2-obat-racikan-add');
        if (!select.hasClass('select2-hidden-accessible')) {
            select.select2({
                placeholder: 'Pilih Obat',
                allowClear: true,
                minimumInputLength: 3,
                ajax: {
                    url: '{{ route("obat.search") }}',
                    type: 'GET',
                    dataType: 'json',
                    delay: 150,
                    data: function (params) {
                        return getObatSearchParams(params, false);
                    },
                    processResults: function (data) {
                        return mapObatSearchResults(data);
                    },
                    cache: true
                },
                escapeMarkup: function (markup) {
                    return markup;
                },
                templateResult: function (data) {
                    return renderObatOption(data);
                },
                templateSelection: function (data) {
                    return renderObatSelection(data);
                }
            });
        }
    });

    // TAMBAH OBAT in edit mode (saved together with "Update Racikan")
    $(document).on('click', '.btn-add-medication', function () {
        const card = $(this).closest('.racikan-card');
        const obatSelect = card.find('.select2-obat-racikan-add');
        const dosisInput = card.find('.dosis-racikan-add');
        const modeSelect = card.find('.mode-racikan-add');

        const obatId = obatSelect.val();
        const obatData = obatSelect.select2('data')[0] || {};
        const dosis = parseFloat(dosisInput.val()) || 0;

        if (!obatId || dosis <= 0) {
            swalWarn('Nama obat dan dosis wajib diisi.');
            return;
        }
        if (card.find('.resep-table-body tr[data-obat-id="' + obatId + '"]').not('.obat-deleted').length) {
            swalWarn('Obat ini sudah ada dalam racikan ini.', 'Obat Sudah Ada');
            return;
        }

        // "Per Tablet": dosis is a number of tablets, so multiply by the obat's own dosis (same as a new racikan)
        const dosisObat = parseFloat(obatData.dosis) || 0;
        const dosisRacik = modeSelect.val() === 'tablet' ? dosisObat * dosis : dosis;

        card.find('.resep-table-body .no-data').remove();
        card.find('.resep-table-body').append(racikanRowHtml({
            obatId: obatId,
            nama: obatData.nama || obatData.text,
            dosisObat: obatData.dosis,
            dosisRacik: (dosisRacik + ' ' + (obatData.satuan || '')).trim(),
            hargaSatuan: obatData.harga_nonfornas,
            stok: obatData.stok_gudang !== undefined ? obatData.stok_gudang : obatData.stok,
            rowClass: farmasiRowClass(obatId, true),
            editable: true
        }));

        obatSelect.val(null).trigger('change');
        dosisInput.val('');
        modeSelect.val('manual');
        updateTotalPrice();
    });

    // Pasien Keluar button handler
    $('#pasien-keluar-btn').on('click', function() {
        const pasienId = $(this).data('pasien-id');
        const pasienName = $(this).data('pasien-name');
        
        Swal.fire({
            title: 'Konfirmasi',
            text: `Apakah Resep pasien ${pasienName} sudah bisa diproses farmasi?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.value) {
                // Send notification to farmasi users
                $.ajax({
                    url: '{{ route("erm.notify.pasien.keluar") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        pasien_id: pasienId,
                        pasien_name: pasienName
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: 'Notifikasi telah dikirim ke farmasi',
                                icon: 'success',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#3085d6'
                            }).then((result) => {
                                if (result.value) {
                                    window.close();
                                }
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Gagal mengirim notifikasi',
                            icon: 'error'
                        });
                        console.error('Failed to send notification:', error);
                    }
                });
            }
        });
    });
</script>
@endsection
