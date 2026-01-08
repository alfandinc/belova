@extends('layouts.erm.app')
@section('title', 'E-Resep Farmasi')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection
@section('content')

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

    <div class="card">
        <div class="card-body">
            <div class="container">                    <div class="d-flex justify-content-between align-items-center mb-3">
                    <div style="display: flex; align-items: center;">
                        <h4 style="margin: 0;">Total Harga: <strong>Rp.</strong></h4>
                        <h4 id="total-harga" style="margin: 0; "><strong>0</strong></h4>
                        
                    </div>
                   
                    <div class="mb-3">
                        <button class="btn btn-primary btn-sm btn-cetakresep" >Cetak Resep</button>
                        <button class="btn btn-primary btn-sm btn-cetakedukasi" >Cetak Edukasi</button>
                        <button class="btn btn-primary btn-sm btn-cetaketiket" >Cetak Etiket</button>
                        <button class="btn btn-primary btn-sm btn-cetaketiketbiru" data-toggle="modal" data-target="#etiketBiruModal">Etiket Biru</button>
                        <button id="paket-racikan" class="btn btn-sm btn-warning">Paket Racikan</button>
                        <button class="btn btn-sm btn-info btn-riwayat" data-url="{{ route('resep.historydokter', $pasien->id) }}" data-title="Riwayat Resep Dokter" data-type="dokter">
                            Riwayat Dokter
                        </button>

                        <button class="btn btn-sm btn-warning btn-riwayat" data-url="{{ route('resep.historyfarmasi', $pasien->id) }}" data-title="Riwayat Resep Farmasi" data-type="farmasi">
                            Riwayat Farmasi
                        </button>
                        <button id="submit-all" class="btn btn-success btn-sm">Submit Resep</button>
                        {{-- <button class="btn btn-danger btn-sm" onclick="window.close()">Keluar</button> --}}
                    </div>
                </div>

                <div class="mb-3">
                    <h5>Catatan Dokter :</h5>
                    <textarea readonly class="form-control" rows="3" style="font-size: 1.5rem; font-weight: bold; color: red;">{{ $catatan_resep ?? '' }}</textarea>
                </div>
                 @if (!$nonRacikans->count() && !$racikans->count())
                    <div class="alert alert-info" id="empty-resep-message">
                        Belum ada data dari dokter. Anda bisa menambahkan resep baru atau salin dari dokter.
                    </div>
                @endif
<div id="resep-wrapper">
                <!-- NON RACIKAN -->
                <h5 style="color: blue;"><strong>Resep Non Racikan</strong></h5>
                <div class="racikan-card mb-4 p-3 border rounded">
                    <div class="row add-obat-row mb-3">
                        <div class="col-md-4">
                            <label>Nama Obat</label>
                            <select class="form-control select2-obat" name="obat_id" id="obat_id">
                                <option value="">Search and select an obat...</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label>Jumlah</label>
                            <input type="number" id="jumlah" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-4">
                            <label>Aturan Pakai</label>
                            <div class="mb-1">
                                <select id="aturan_pakai_template" class="form-control select2-aturan-template" style="width:100%">
                                    <option value=""></option>
                                </select>
                            </div>
                            <input type="hidden" id="aturan_pakai" name="aturan_pakai">
                        </div>
                        <div class="col-md-1">
                            <label for="diskon">Disc (%)</label>
                            <input type="number" class="form-control" id="diskon"  placeholder="0" min="0" max="100">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button id="tambah-resep" class="btn btn-primary btn-block">Tambah</button>
                        </div>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nama Obat</th>
                                <th>Jumlah</th>
                                <th>Harga</th>
                                <th>Disc</th>
                                
                                <th>Stok</th>
                                <th>Aturan Pakai</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="resep-table-body">
                            @forelse ($nonRacikans as $resep)
                                <tr data-id="{{ $resep->id }}">
                                    <td>{{ $resep->obat->nama ?? '-' }}</td>
                                    <td>{{ $resep->jumlah }}</td>
                                    <td>Rp. {{ $resep->harga ?? $resep->obat->harga_nonfornas ?? 0 }}</td>
                                    <td>{{ $resep->diskon ?? '0'}} %</td>
                                    
                                    @php
                                        $gudangId = \App\Models\ERM\GudangMapping::getDefaultGudangId('resep');
                                        $stokGudang = ($gudangId && $resep->obat) ? $resep->obat->getStokByGudang($gudangId) : 0;
                                    @endphp
                                        <td style="color: {{ ($stokGudang < 10 ? 'red' : ($stokGudang < 100 ? 'yellow' : 'green')) }};">
                                        {{ (int) $stokGudang }}
                                    </td>
                                    <td>{{ $resep->aturan_pakai }}</td>
                                    <td><button class="btn btn-success btn-sm edit" data-id="{{ $resep->id }}">Edit</button>
                                        <button class="btn btn-danger btn-sm hapus" data-id="{{ $resep->id }}">Hapus</button> </td>
                                    
                                </tr>
                            @empty
                                <tr class="no-data">
                                    <td colspan="6" class="text-center text-muted">Belum ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- RACIKAN -->
                <h5 style="color: blue;"><strong>Resep Racikan</strong></h5>
                
                <div id="racikan-container">
                    @foreach ($racikans as $ke => $items)
                    <div class="racikan-card mb-4 p-3 border rounded" data-racikan-ke="{{ $ke }}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 style="color: blue;"><strong>Racikan {{ $ke }}</strong></h5>
                            <div>
                                <button class="btn btn-warning btn-sm edit-racikan mr-2">Edit Racikan</button>
                                <button class="btn btn-danger btn-sm hapus-racikan">Hapus Racikan</button>
                            </div>
                        </div>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                            <th>Nama Obat</th>
                                            <th>Dosis</th>
                                            <th>Stok</th>
                                            <th>Stok Dikurangi</th>
                                            <th>Aksi</th>
                                        </tr>
                            </thead>
                            <tbody class="resep-table-body">
                                @foreach ($items as $resep)
                                    <tr data-id="{{ $resep->id }}" data-obat-id="{{ $resep->obat_id }}" data-dosis="{{ $resep->dosis }}" data-jumlah="{{ $resep->jumlah }}">
                                        <td>{{ $resep->obat->nama ?? '-' }}</td>
                                        <td>{{ $resep->dosis }}</td>
                                        @php
                                            $gudangId = \App\Models\ERM\GudangMapping::getDefaultGudangId('resep');
                                            $stokGudang = ($gudangId && $resep->obat) ? $resep->obat->getStokByGudang($gudangId) : 0;
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
                                            // Round up to the next integer (0.2 -> 1, 0.8 -> 1)
                                            $stokDikurangiDisplay = is_numeric($stokDikurangi) ? (int) ceil($stokDikurangi) : 0;
                                        @endphp
                                        <td style="color: {{ ($stokGudang < 10 ? 'red' : ($stokGudang < 100 ? 'yellow' : 'green')) }};">
                                            {{ (int) $stokGudang }}
                                        </td>
                                        <td class="stok-dikurangi" style="color: {{ ($stokDikurangi > $stokGudang ? 'red' : 'inherit') }};">{{ $stokDikurangiDisplay }}</td>
                                        <td>
                                            <button class="btn btn-warning btn-sm edit-obat" disabled>Edit</button>
                                            <button class="btn btn-danger btn-sm hapus-obat" disabled>Hapus</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="row">
                            <div class="col-md-3">
                                <label>Wadah</label>
                                <select class="form-control select2-wadah-racikan wadah" name="wadah_id">
                                <option value="{{ $items->first()?->wadah?->id ?? '' }}">
                                    {{ $items->first()?->wadah?->nama ?? 'Pilih Wadah' }}
                                </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Bungkus</label>
                                <input type="number" class="form-control jumlah_bungkus" value="{{ $items->first()->bungkus }}">
                            </div>
                            <div class="col-md-6">
                                <label>Aturan Pakai</label>
                                <input type="text" class="form-control aturan_pakai" value="{{ $items->first()->aturan_pakai }}">
                            </div>
                        </div>
                        <button class="btn btn-success btn-block mt-3 tambah-resepracikan" disabled>Sudah Disimpan</button>
                        <button class="btn btn-primary btn-block mt-3 update-resepracikan d-none">Update</button>
                    </div>
                    @endforeach
                </div>
                <button id="tambah-racikan" class="btn btn-primary mb-3">Tambah Racikan</button>
</div>  

            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')

<script>
    let racikanCount = {{ $lastRacikanKe ?? 0 }};

    $(document).ready(function () {
        $('.select2').select2({ width: '100%' });
        $('.select2-obat').select2({
            width: '100%',
            minimumInputLength: 3,
            placeholder: 'Search obat...',
            ajax: {
                url: '{{ route("obat.search") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    // If your endpoint returns {results: [...]}, use that
                    if (Array.isArray(data.results)) {
                        return {
                            results: data.results.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.text,
                                    harga: item.harga_nonfornas,
                                    stok: item.stok
                                };
                            })
                        };
                    } else {
                        // fallback for array response
                        return {
                            results: data.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.nama + (item.harga_nonfornas ? ' - ' + item.harga_nonfornas : ''),
                                    harga: item.harga_nonfornas,
                                    stok: item.stok
                                };
                            })
                        };
                    }
                },
                cache: true
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

        // Initialize Select2 for Aturan Pakai templates (AJAX-only, template-only selection)
        $('.select2-aturan-template').select2({
            width: '100%',
            placeholder: '-- Pilih Template Aturan Pakai --',
            allowClear: true,
            minimumInputLength: 2,
            ajax: {
                url: '{{ route('erm.aturan-pakai.list.active') }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return {
                        results: (data || []).map(function(item){
                            return {
                                id: item.id,
                                text: item.template.length > 80 ? item.template.substring(0,80) + '...' : item.template,
                                template: item.template
                            };
                        })
                    };
                },
                cache: true
            },
            templateResult: function(item){
                return item && item.template ? $('<div>').text(item.template) : item.text;
            },
            templateSelection: function(item){
                return item && item.template ? item.template : item.text;
            },
            escapeMarkup: function(m){ return m; }
        });

        // Initialize Select2 for edit modal aturan pakai (used when editing non-racikan)
        $('.select2-edit-aturan').select2({
            width: '100%',
            placeholder: '-- Pilih Template Aturan Pakai --',
            allowClear: true,
            minimumInputLength: 2,
            ajax: {
                url: '{{ route('erm.aturan-pakai.list.active') }}',
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) {
                    return { results: (data || []).map(function(item){ return { id: item.id, text: item.template.length > 80 ? item.template.substring(0,80) + '...' : item.template, template: item.template }; }) };
                },
                cache: true
            },
            templateResult: function(item){ return item && item.template ? $('<div>').text(item.template) : item.text; },
            templateSelection: function(item){ return item && item.template ? item.template : item.text; },
            escapeMarkup: function(m){ return m; }
        });

        // Initialize Select2 for Aturan Pakai (Farmasi modal only)
        function initAturanPakaiSelect2(selector, dropdownParent) {
            $(selector).select2({
                width: '100%',
                placeholder: '-- Pilih Template Aturan Pakai --',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: '{{ route('erm.aturan-pakai.list.active') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { q: params.term }; },
                    processResults: function(data) {
                        return { results: (data || []).map(function(item){
                            if (typeof item === 'string') return { id: item, text: item };
                            return { id: item.template || item.id || item, text: item.template || item.name || item };
                        }) };
                    },
                    cache: true
                },
                templateResult: function(item){ return item && item.text ? $('<div>').text(item.text) : item.text; },
                templateSelection: function(item){ return item && item.text ? item.text : item.text; },
                dropdownParent: dropdownParent || undefined,
                escapeMarkup: function(m){ return m; }
            });
        }

        // Sync selected template into hidden input for modal
        $('#edit-aturan-select').on('select2:select', function(e){
            const tpl = e.params && e.params.data && e.params.data.template ? e.params.data.template : (e.params && e.params.data ? e.params.data.text : '');
            $('#edit-aturan').val(tpl);
        });
        $('#edit-aturan-select').on('select2:clear', function(){ $('#edit-aturan').val(''); });

        // When a template is selected, copy template text into hidden input only
        $('#aturan_pakai_template').on('select2:select', function(e){
            const data = e.params && e.params.data ? e.params.data : null;
            const tpl = data && data.template ? data.template : data && data.text ? data.text : '';
            $('#aturan_pakai').val(tpl);
        });

        $('#aturan_pakai_template').on('select2:clear', function(){
            $('#aturan_pakai').val('');
        });

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
            // Robustly obtain aturan pakai: prefer hidden input, fallback to select2 selected template/text
            let aturanPakai = $('#aturan_pakai').val();
            if (!aturanPakai) {
                try {
                    const sel = $('#aturan_pakai_template').select2('data') || [];
                    if (sel.length > 0) {
                        const d = sel[0];
                        aturanPakai = (d && (d.template || d.text)) ? (d.template || d.text) : aturanPakai;
                    }
                } catch (e) {
                    // ignore if select2 not initialized
                }
            }
            let diskon = $('#diskon').val() || 0;
            let visitationId = $('#visitation_id').val();  // Pastikan id yang digunakan sama

            if (!obatId || !jumlah || !aturanPakai) return Swal.fire('Peringatan', "Semua field wajib diisi.", "warning");

            // If stok info is available, block adding when requested jumlah > stok
            if (stokAvailable !== null && !isNaN(stokAvailable) && jumlah > stokAvailable) {
                return Swal.fire('Stok Tidak Cukup', `Jumlah yang diminta (${jumlah}) lebih besar dari stok tersedia (${stokAvailable}). Silakan ubah jumlah atau periksa stok.`, 'error');
            }

            // If stok info not available or jumlah <= stokAvailable, proceed normally
            submitTambahResep({obatId, jumlah, harga, diskon, aturanPakai, visitationId});

            function submitTambahResep(payload) {
                $.ajax({
                    url: "{{ route('resepfarmasi.nonracikan.store') }}", // disesuaikan nanti
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
                        // const resep = res.data;
                        $('#resep-table-body .no-data').remove();
                        const stokGudang = res.data.obat.stok_gudang !== undefined ? parseInt(res.data.obat.stok_gudang) : 0;
                        const stokColor = stokGudang < 10 ? 'red' : (stokGudang < 100 ? 'yellow' : 'green');
                        $('#resep-table-body').append(`
                            <tr data-id="${res.data.id}" data-obat-id="${res.data.obat.id}">
                                <td>${res.data.obat.nama}</td>
                                <td>${res.data.jumlah}</td>
                                <td>${res.data.harga}</td>
                                <td>${res.data.diskon} %</td>                           
                                <td style="color: ${stokColor};">${stokGudang}</td>
                                <td>${res.data.aturan_pakai}</td>
                                <td>
                                    <button class="btn btn-success btn-sm edit" data-id="${res.data.id}">Edit</button>
                                    <button class="btn btn-danger btn-sm hapus" data-id="${res.data.id}">Hapus</button>
                                </td>
                            </tr>
                        `);
                        updateTotalPrice();

                        // Clear the input fields (clear both hidden input and select2 control)
                        $('#obat_id').val(null).trigger('change');
                        $('#jumlah').val('');
                        $('#aturan_pakai').val('');
                        try { $('#aturan_pakai_template').val(null).trigger('change'); } catch(e){}
                    },
                    error: function (xhr) {
                        const msg = xhr && xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unknown error';
                        Swal.fire('Error', 'Gagal menambahkan resep: ' + msg, 'error');
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
                        $('#resep-table-body').append(`<tr class="no-data"><td colspan="6" class="text-center text-muted">Belum ada data</td></tr>`);
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

        // UPDATE TOTAL HARGA
        function updateTotalPrice() {
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

            if (nonRacikanBreakdown.length) console.table(nonRacikanBreakdown);

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
                try {
                    // Build a lookup map obat_id -> { harga_nonfornas, dosis }
                    const priceMap = {};
                    if (res.non_racikans && Array.isArray(res.non_racikans)) {
                        res.non_racikans.forEach(item => {
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
                            // Extract numeric value from dosis string
                            const dosisMatch = dosisStr.match(/(\d+(?:[.,]\d+)?)/);
                            const dosisRacik = dosisMatch ? parseFloat(dosisMatch[0].replace(',', '.')) : 0;

                            const obatInfo = priceMap[obatId] || { harga: 0, dosis: '' };
                            const baseDosisStr = (obatInfo.dosis || '').toString();
                            const baseMatch = baseDosisStr.match(/(\d+(?:[.,]\d+)?)/);
                            const baseDosis = baseMatch ? parseFloat(baseMatch[0].replace(',', '.')) : 0;
                            const hargaSatuan = parseCurrencyToNumber(obatInfo.harga) || 0;

                            const hargaAkhir = (baseDosis > 0 && dosisRacik > 0) ? (dosisRacik / baseDosis) * hargaSatuan : 0;
                            racikanTotal += hargaAkhir;

                            // compute stok dikurangi for this row using current bungkus value on the card
                            const currentBungkus = parseFloat(card.find('.jumlah_bungkus').val() || card.find('.bungkus').val()) || 1;
                            let stokDikurangi = 0;
                            if (baseDosis > 0) {
                                stokDikurangi = (dosisRacik * currentBungkus) / baseDosis;
                            }
                            // round up to nearest integer
                            const stokDikurangiDisplay = Number.isFinite(stokDikurangi) ? Math.ceil(stokDikurangi) : 0;
                            // update the DOM cell if present
                            try {
                                const rowStokCell = row.find('.stok-dikurangi');
                                if (rowStokCell.length) {
                                    // Determine stok available for coloring if known
                                    const stokCellText = row.find('td').eq(2).text().trim();
                                    const stokAvailable = parseFloat(stokCellText.replace(/[^0-9.,-]/g, '').replace(',', '.')) || 0;
                                    rowStokCell.text(stokDikurangiDisplay);
                                    if (stokDikurangi > stokAvailable) rowStokCell.css('color', 'red');
                                    else rowStokCell.css('color', 'inherit');
                                }
                            } catch (e) {
                                // ignore DOM update errors
                            }

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

                    if (racikanBreakdown.length) console.log('Racikan breakdown:', racikanBreakdown);

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

            const racikanCard = `
                <div class="racikan-card mb-4 p-3 border rounded" data-racikan-ke="${racikanCount}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 style="color: yellow;"><strong>Racikan ${racikanCount}</strong></h5>
                        <div>
                            <button class="btn btn-warning btn-sm edit-racikan mr-2">Edit Racikan</button>
                            <button class="btn btn-danger btn-sm hapus-racikan">Hapus Racikan</button>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Nama Obat</label>
                            <select class="form-control select2-obat-racikan" name="obat_id">
                                <option value="">Search and select an obat...</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Dosis</label>
                            <input type="number" class="form-control dosis_input">
                        </div>
                        <div class="col-md-2">
                            <label>Satuan Dosis</label>
                            <select class="form-control mode_dosis">
                                <option value="normal">Normal</option>
                                <option value="tablet">Tablet</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary btn-block tambah-obat">Tambah ke Racikan</button>
                        </div>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nama Obat</th>
                                <th>Dosis</th>
                                <th>Stok</th>
                                <th>Stok Dikurangi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="resep-table-body">
                            <tr class="no-data">
                                <td colspan="5" class="text-center text-muted">Belum ada data</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="row">
                        <div class="col-md-3">
                            <label>Racikan</label>
                            <select class="form-control select2-wadah-racikan wadah" name="wadah_id">
                                <option value="">Search and select wadah...</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Bungkus</label>
                            <input type="number" class="form-control bungkus">
                        </div>
                        <div class="col-md-6">
                            <label>Aturan Pakai</label>
                            <select class="form-control select2-aturan-template-racikan" style="width:100%">
                                <option value=""></option>
                            </select>
                            <input type="hidden" class="aturan_pakai" />
                        </div>
                    </div>

                    <button class="btn btn-success btn-block mt-3 tambah-resepracikan">Simpan Racikan ${racikanCount}</button>
                </div>
            `;

            // Append the new racikan card to the container
            $('#racikan-container').append(racikanCard);

            // Reinitialize select2 for the dynamically added "Nama Obat" field
            $('.select2-obat-racikan').last().select2({
                placeholder: 'Search obat...',
                ajax: {
                    url: '{{ route("obat.search") }}', // Define this route in your controller
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term // Search term
                        };
                    },
                        processResults: function (data) {
                            // Support both {results: [...]} and [...] response
                            let items = Array.isArray(data.results) ? data.results : data;
                            return {
                                results: items.map(function(item) {
                                    return {
                                        id: item.id,
                                        text: item.text || (item.nama + (item.harga_nonfornas ? ' - ' + item.harga_nonfornas : '')),
                                        nama: item.nama,
                                        dosis: item.dosis,
                                        satuan: item.satuan,
                                        stok: item.stok,
                                        stok_gudang: typeof item.stok_gudang !== 'undefined' ? item.stok_gudang : item.stok,
                                        harga_nonfornas: item.harga_nonfornas
                                    };
                                })
                            };
                        },
                    cache: true
                },
                minimumInputLength: 3
            });
            $('.select2-wadah-racikan').last().select2({
                placeholder: 'Search wadah...',
                ajax: {
                    url: '{{ route("wadah.search") }}',
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
                                text: item.text // Use the text property directly from the server response
                            }))
                        };
                    },
                    cache: true
                },
            });

            // Initialize Select2 for aturan pakai on the newly added racikan card
            const $tplSel = $('.select2-aturan-template-racikan').last();
            $tplSel.select2({
                width: '100%',
                placeholder: '-- Pilih Template Aturan Pakai --',
                allowClear: true,
                minimumInputLength: 2,
                ajax: {
                    url: '{{ route('erm.aturan-pakai.list.active') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { q: params.term }; },
                    processResults: function(data) {
                        return {
                            results: (data || []).map(function(item){
                                return { id: item.id, text: item.template.length > 80 ? item.template.substring(0,80) + '...' : item.template, template: item.template };
                            })
                        };
                    },
                    cache: true
                },
                templateResult: function(item){ return item && item.template ? $('<div>').text(item.template) : item.text; },
                templateSelection: function(item){ return item && item.template ? item.template : item.text; },
                escapeMarkup: function(m){ return m; }
            });

            // When a template is selected, copy to the hidden .aturan_pakai in the same card
            $tplSel.on('select2:select', function(e){
                const tpl = e.params && e.params.data && e.params.data.template ? e.params.data.template : (e.params && e.params.data ? e.params.data.text : '');
                $(this).closest('.racikan-card').find('.aturan_pakai').val(tpl);
            });
            $tplSel.on('select2:clear', function(){ $(this).closest('.racikan-card').find('.aturan_pakai').val(''); });

            // Update totals when a new (empty) racikan card is added
            updateTotalPrice();


        });
        // UPDATE RACIKAN
        $('#racikan-container').on('click', '.update-resepracikan', function () {
            const card = $(this).closest('.racikan-card');
            const racikanKe = card.data('racikan-ke');
            const visitationId = $('#visitation_id').val();
            const wadah = card.find('.wadah').val();
            
            // Find bungkus value - check both possible class names
            const bungkus = card.find('.bungkus').val() || card.find('.jumlah_bungkus').val();
            
            const aturanPakai = card.find('.aturan_pakai').val();
            
            console.log('Debug - racikanKe:', racikanKe);
            console.log('Debug - bungkus:', bungkus);
            console.log('Debug - aturanPakai:', aturanPakai);
            
            // Validate required fields
            if (!bungkus || !aturanPakai) {
                Swal.fire('Peringatan', 'Jumlah bungkus dan aturan pakai harus diisi!', 'warning');
                return;
            }
            
            // Store the original racikanKe before any updates
            const originalRacikanKe = racikanKe;
            
            console.log('Original racikanKe before update:', originalRacikanKe);
            
            if (originalRacikanKe === 0 || originalRacikanKe === '0') {
                Swal.fire('Error', 'Invalid racikan ID (0). Cannot update this racikan.', 'error');
                return;
            }
            
            // Collect all obat rows in this racikan
            const obats = [];
            card.find('.resep-table-body tr').each(function () {
                const row = $(this);
                if (!row.hasClass('no-data')) {
                    // Prefer explicit attributes; use attr to detect empty strings
                    let id = row.attr('data-id');
                    if (!id) {
                        // maybe server id stored on first td
                        id = row.find('td').eq(0).attr('data-id') || null;
                    }
                    const obatId = row.attr('data-obat-id') || row.find('td').eq(0).attr('data-id') || null;
                    const dosis = row.attr('data-dosis') || row.find('td').eq(1).text();
                    const jumlah = row.attr('data-jumlah') || 1;

                    const entry = {
                        obat_id: obatId,
                        dosis: dosis,
                        jumlah: jumlah
                    };

                    // Only include id if it's truthy (existing DB row)
                    if (id) entry.id = id;

                    obats.push(entry);
                }
            });

            // Debug payload before sending
            console.log('Updating racikan payload:', {
                visitation_id: visitationId,
                wadah: wadah,
                bungkus: bungkus,
                aturan_pakai: aturanPakai,
                obats: obats
            });

            // Send AJAX request to update the racikan
            $.ajax({
                url: `/erm/resepfarmasi/racikan/${originalRacikanKe}`,
                type: 'POST',
                data: {
                    _method: 'PUT',
                    visitation_id: visitationId,
                    wadah: wadah,
                    bungkus: bungkus,
                    aturan_pakai: aturanPakai,
                    obats: obats,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Sukses', response.message, 'success');
                        card.find('.wadah, .bungkus, .jumlah_bungkus, .aturan_pakai').prop('disabled', true);
                        // also disable Select2 control if present
                        try {
                            const $tpl = card.find('.select2-aturan-template-racikan');
                            if ($tpl.length) {
                                $tpl.prop('disabled', true).trigger('change.select2');
                            }
                        } catch (e) {
                            console.error('Error disabling select2 after update', e);
                        }
                        card.find('.update-resepracikan').addClass('d-none');
                        card.find('.tambah-resepracikan').removeClass('d-none');
                        card.find('.hapus-obat, .edit-obat').prop('disabled', true);
                        card.find('.jumlah_bungkus, .bungkus').val(bungkus);
                        card.find('.aturan_pakai').val(aturanPakai);
                        card.attr('data-racikan-ke', originalRacikanKe);
                        // Refresh the resaep list for this visitation to reflect DB state
                        fetchFarmasiResep();
                        // If controller returned obats, patch newly created rows with returned ids
                        if (response.obats && Array.isArray(response.obats)) {
                            // Replace rows inside this card with server data to ensure ids are present
                            const tbody = card.find('.resep-table-body');
                            tbody.empty();
                            response.obats.forEach(function(ob){
                                const stokColor = ob.stok_gudang < 10 ? 'red' : (ob.stok_gudang < 100 ? 'yellow' : 'green');
                                tbody.append(`<tr data-id="${ob.id}" data-obat-id="${ob.obat_id}" data-dosis="${ob.dosis}" data-jumlah="${ob.jumlah}"><td data-id="${ob.obat_id}">${ob.obat_nama ?? ''}</td><td>${ob.dosis}</td><td><span style=\"color: ${stokColor};\">${ob.stok_gudang}</span></td><td class=\"stok-dikurangi\">-</td><td><button class=\"btn btn-danger btn-sm hapus-obat\">Hapus</button></td></tr>`);
                            });
                            // Ensure totals updated after injecting server-side obat rows
                            updateTotalPrice();
                        }
                    } else {
                        Swal.fire('Error', 'Terjadi kesalahan: ' + response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Update failed:', xhr, status, error);
                    Swal.fire('Error', 'Gagal menyimpan perubahan: ' + (xhr.responseJSON ? xhr.responseJSON.message : xhr.responseText), 'error');
                }
            });
        });
        
        // TAMBAH OBAT KE RACIKAN
        $('#racikan-container').on('click', '.tambah-obat', function () {
            const card = $(this).closest('.racikan-card');
            const obatSelect = card.find('.select2-obat-racikan');
            const obatId = obatSelect.val();
            const obatText = obatSelect.find('option:selected').text();

            // Retrieve custom data attributes from the selected option
            const selectedOption = obatSelect.select2('data')[0]; // Get the selected option's data
            // Use stok_gudang if available, fallback to stok
            const stokGudang = typeof selectedOption.stok_gudang !== 'undefined' ? parseInt(selectedOption.stok_gudang) : (selectedOption.stok || 0);
            const stokColor = stokGudang < 10 ? 'red' : (stokGudang < 100 ? 'yellow' : 'green');
            const defaultDosis = parseFloat(selectedOption.dosis) || 0; // Ensure it's a number
            const satuan = selectedOption.satuan || ''; // Default to empty string if undefined

            const dosisInput = parseFloat(card.find('.dosis_input').val()) || 0; // Ensure it's a number
            const mode = card.find('.mode_dosis').val();

            // Calculate the final dosis
            let dosisAkhir = mode === 'tablet' ? defaultDosis * dosisInput : dosisInput;

            if (!obatId || !dosisInput) {
                Swal.fire('Peringatan', 'Nama obat dan dosis harus diisi.', 'warning');
                return;
            }

            const tbody = card.find('.resep-table-body');
            tbody.find('.no-data').remove();

            // Determine current bungkus value for this card (may be empty yet)
            const currentBungkus = parseFloat(card.find('.bungkus').val() || card.find('.jumlah_bungkus').val()) || 1;
            // Compute stok dikurangi = (dosisAkhir * bungkus) / baseDosis
            const baseDosis = parseFloat(selectedOption.dosis) || 0;
            let stokDikurangi = 0;
            if (baseDosis > 0) {
                stokDikurangi = (dosisAkhir * currentBungkus) / baseDosis;
            }
            // round up to nearest integer for display
            const stokDikurangiDisplay = Number.isFinite(stokDikurangi) ? String(Math.ceil(stokDikurangi)) : '0';

            // Append the new row to the table (do NOT include empty data-id attribute)
            tbody.append(`
                <tr data-obat-id="${obatId}" data-dosis="${dosisAkhir}" data-base-dosis="${baseDosis}" data-jumlah="1">
                    <td data-id="${obatId}">${obatText}</td>
                    <td>${dosisAkhir} ${satuan}</td>
                    <td><span style="color: ${stokColor};">${stokGudang}</span></td>
                    <td class="stok-dikurangi" style="color: ${parseFloat(stokDikurangi) > stokGudang ? 'red' : 'inherit'};">${stokDikurangiDisplay}</td>
                    <td><button class="btn btn-danger btn-sm hapus-obat">Hapus</button></td>
                </tr>
            `);
            // Refresh totals after adding a racikan row
            updateTotalPrice();
        });
        // STORE RACIKAN
        $('#racikan-container').on('click', '.tambah-resepracikan', function () {
            const card = $(this).closest('.racikan-card');
            const racikanKe = card.data('racikan-ke');
            const visitationId = $('#visitation_id').val();
            const wadah = card.find('.wadah').val();
            const bungkus = card.find('.bungkus').val();
            const aturanPakai = card.find('.aturan_pakai').val();

                     // Validate required fields
            if (!bungkus || !aturanPakai) {
                Swal.fire('Peringatan', 'Field "Bungkus" dan "Aturan Pakai" wajib diisi.', 'warning');
                return;
            }

            const obats = [];
            card.find('.resep-table-body tr').each(function () {
                if (!$(this).hasClass('no-data')) {
                    const obatId = $(this).find('td').eq(0).data('id'); // sesuaikan jika perlu
                    const dosis = $(this).find('td').eq(1).text();
                    if (obatId) {
                        obats.push({ obat_id: obatId, dosis: dosis });
                    }
                }
            });

            if (obats.length === 0) {
                Swal.fire('Peringatan', 'Tambahkan minimal satu obat dalam racikan', 'warning');
                return;
            }

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
                    Swal.fire('Sukses', res.message, 'success');
                    card.find('.tambah-resepracikan').prop('disabled', true).text('Disimpan');
                    // Disable inputs in the card after saving: wadah (select2), bungkus, jumlah_bungkus
                    try {
                        card.find('.wadah, .bungkus, .jumlah_bungkus').prop('disabled', true);
                        const $wadahSel = card.find('.select2-wadah-racikan');
                        if ($wadahSel.length) {
                            $wadahSel.prop('disabled', true).trigger('change.select2');
                        }
                        const $tpl = card.find('.select2-aturan-template-racikan');
                        if ($tpl.length) {
                            $tpl.prop('disabled', true).trigger('change.select2');
                        }
                        const $hidden = card.find('.aturan_pakai');
                        if ($hidden.length) $hidden.prop('disabled', true);
                    } catch (e) {
                        console.error('Error disabling racikan inputs after save', e);
                    }
                    // Disable per-row edit/hapus buttons for obat rows in this card
                    try { card.find('.edit-obat, .hapus-obat').prop('disabled', true); } catch(e){}
                    // Ensure Update button is present but hidden
                    if (card.find('.update-resepracikan').length === 0) {
                        card.append('<button class="btn btn-primary btn-block mt-3 update-resepracikan d-none">Update</button>');
                    } else {
                        card.find('.update-resepracikan').addClass('d-none');
                    }
                        // PATCH: Update each obat row with the correct data-id and stok_gudang from backend
                        if (res.obats && Array.isArray(res.obats)) {
                            let rows = card.find('.resep-table-body tr');
                            res.obats.forEach(function(obat, idx) {
                                let row = rows.eq(idx);
                                row.attr('data-id', obat.id);
                                row.attr('data-obat-id', obat.obat_id);
                                row.attr('data-dosis', obat.dosis);
                                row.attr('data-jumlah', obat.jumlah || 1);
                                // Update stok column and color
                                let stokGudang = typeof obat.stok_gudang !== 'undefined' ? parseInt(obat.stok_gudang) : 0;
                                let stokColor = stokGudang < 10 ? 'red' : (stokGudang < 100 ? 'yellow' : 'green');
                                row.find('td').eq(2).html(`<span style="color: ${stokColor};">${stokGudang}</span>`);
                                // placeholder for stok dikurangi; will be recomputed by updateTotalPrice()
                                if (row.find('td').eq(3).length) {
                                    row.find('td').eq(3).html('-');
                                }
                            });
                        }
                        // After saving, disable aturan pakai selector (or input) to prevent edits
                        try {
                            const $tpl = card.find('.select2-aturan-template-racikan');
                            if ($tpl.length) {
                                $tpl.prop('disabled', true).trigger('change.select2');
                            }
                            const $hidden = card.find('.aturan_pakai');
                            if ($hidden.length) {
                                $hidden.prop('disabled', true);
                            }
                        } catch (e) {
                            console.error('Error disabling aturan_pakai after save', e);
                        }

                        // Refresh totals after racikan saved
            updateTotalPrice();
                }
            });
            });

        // EDIT OBAT RACIKAN (open modal)
        $('#racikan-container').on('click', '.edit-obat', function () {
            const row = $(this).closest('tr');
            const dosis = row.data('dosis');
            $('#edit-dosis-row-id').val(row.index()); // use row index for identification
            $('#edit-dosis-value').val(dosis);
            $('#editDosisModal').modal('show');
        });

        // SAVE DOSIS FROM MODAL
        $('#save-dosis-btn').on('click', function () {
            const rowIndex = $('#edit-dosis-row-id').val();
            const newDosis = $('#edit-dosis-value').val();
            if (!newDosis) {
                Swal.fire('Peringatan', 'Dosis tidak boleh kosong!', 'warning');
                return;
            }
            // Find the row by index and update data + cell
            const row = $('.racikan-card .resep-table-body tr').eq(rowIndex);
            row.data('dosis', newDosis);
            row.attr('data-dosis', newDosis);
            row.find('td').eq(1).text(newDosis); // update cell display

            // Recalculate stok dikurangi for this row if base dose and stok available
            try {
                const card = row.closest('.racikan-card');
                const baseDosis = parseFloat(row.attr('data-base-dosis') || row.data('base-dosis') || 0) || 0;
                const currentBungkus = parseFloat(card.find('.bungkus').val() || card.find('.jumlah_bungkus').val()) || 1;
                const stokCell = row.find('td').eq(2).text() || row.find('td').eq(2).text();
                const stokAvailable = parseFloat((stokCell || '').toString().replace(/[^0-9.,-]/g, '').replace(',', '.')) || 0;
                if (baseDosis > 0) {
                    const stokDikurangi = Math.ceil((parseFloat(newDosis) * currentBungkus) / baseDosis) || 0;
                    const sdCell = row.find('.stok-dikurangi');
                    sdCell.text(stokDikurangi);
                    sdCell.css('color', stokDikurangi > stokAvailable ? 'red' : 'inherit');
                }
            } catch (e) {
                console.error('Error recalculating stok dikurangi after dosis edit', e);
            }

            // Trigger a full price/stock recalculation as well
            updateTotalPrice();

            $('#editDosisModal').modal('hide');
        });

        // DELETE OBAT DARI RACIKAN
        $('#racikan-container').on('click', '.hapus-obat', function () {
            $(this).closest('tr').remove();
            const card = $(this).closest('.racikan-card');
            if (card.find('.resep-table-body tr').length === 0) {
                card.find('.resep-table-body').append(`<tr class="no-data"><td colspan="5" class="text-center text-muted">Belum ada data</td></tr>`);
            }
            updateTotalPrice();
        });
        // DELETE RACIKAN
        $(document).on('click', '.hapus-racikan', function () {
            const card = $(this).closest('.racikan-card');
            const racikanKe = card.data('racikan-ke');
            const visitationId = $('#visitation_id').val();

            // Cek apakah ada <tr> dengan class 'no data' dalam card
            const isNoData = card.find('tr.no-data').length > 0;

            // Jika ada <tr> dengan class 'no data', langsung dihapus
            if (isNoData) {
                card.remove();
                // Recompute totals after removing an empty racikan card
                updateTotalPrice();
                return;
            }

            Swal.fire({
                title: 'Konfirmasi',
                text: 'Yakin ingin menghapus racikan ini?',
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
                    // Update totals after successful racikan delete
                    updateTotalPrice();
                },
                error: function (err) {
                    Swal.fire('Error', 'Gagal menghapus racikan', 'error');
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
            // populate hidden and select2 with current aturan_pakai
            $('#edit-aturan').val(aturan);
            const $sel = $('#edit-aturan-select');
            // clear previous selection
            $sel.val(null).trigger('change');
            if (aturan) {
                // create a temporary option to display the current text
                const tmpId = 'tmp_' + Date.now();
                const newOption = new Option(aturan, tmpId, true, true);
                $sel.append(newOption).trigger('change');
                // ensure hidden input synced (select2:select handler will also set it)
                $('#edit-aturan').val(aturan);
            }
            $('#editResepModal').modal('show');
        });

        // STORE EDIT NON RACIKAN
        $('#edit-resep-form').on('submit', function(e) {
            e.preventDefault();

            const id = $('#edit-resep-id').val();
            const url = "{{ route('resepfarmasi.nonracikan.update', '') }}/" + id;
            const data = {
                _token: "{{ csrf_token() }}",
                _method: 'PUT',
                jumlah: $('#edit-jumlah').val(),
                diskon: $('#edit-diskon').val(),
                aturan_pakai: $('#edit-aturan').val()
            };

            $.post(url, data)
            .done(function(res) {
                // Update the table row
                const row = $('#resep-table-body').find('tr[data-id="'+ id +'"]');
                row.find('td').eq(1).text(res.data.jumlah);
                row.find('td').eq(3).text(res.data.diskon + ' %');
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

            // Pre-submit stock validation for non-racikan items
            const stockProblems = [];
            $('#resep-table-body tr').each(function () {
                const row = $(this);
                if (row.hasClass('no-data')) return;
                // jumlah is in td[1], stok in td[4]
                const jumlahText = row.find('td').eq(1).text().trim();
                const stokText = row.find('td').eq(4).text().trim();
                const jumlahVal = parseInt((jumlahText || '0').replace(/[^0-9-]/g, ''), 10) || 0;
                const stokVal = parseInt((stokText || '0').replace(/[^0-9-]/g, ''), 10);
                // If stokVal is NaN (unknown), skip check
                if (!isNaN(stokVal) && jumlahVal > stokVal) {
                    const nama = row.find('td').eq(0).text().trim();
                    stockProblems.push({ nama, jumlah: jumlahVal, stok: stokVal });
                }
            });

            if (stockProblems.length > 0) {
                const lines = stockProblems.map(p => `${p.nama}: diminta ${p.jumlah}, stok ${p.stok}`);
                return Swal.fire({
                    title: 'Stok Tidak Cukup',
                    html: `Beberapa obat memiliki jumlah yang lebih besar dari stok tersedia:<br><ul style="text-align:left">${lines.map(l => `<li>${l}</li>`).join('')}</ul>Harap koreksi sebelum submit.`,
                    icon: 'error'
                });
            }

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
            
            function doSubmit(force = false) {
                $btn.prop('disabled', true).text('Memproses...');
                
                $.ajax({
                    url: "{{ route('resepfarmasi.submit') }}",
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        visitation_id: visitationId,
                        force: force ? 1 : 0
                    },
                    success: function (res) {
                        if (res.status === 'warning' && res.need_confirm) {
                            Swal.fire({
                                title: 'Konfirmasi',
                                text: res.message,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Ya, Submit!',
                                cancelButtonText: 'Batal'
                            }).then((result) => {
                                if (result.value) {
                                    doSubmit(true); // Resubmit with force
                                } else {
                                    resetButton();
                                }
                            });
                        } else if (res.status === 'success') {
                            Swal.fire('Sukses', res.message, 'success');
                            $btn.text('Sudah Disubmit').addClass('btn-secondary').removeClass('btn-success').prop('disabled', false);
                        } else if (res.status === 'error') {
                            Swal.fire('Error', res.message, 'error');
                            resetButton();
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Gagal submit resep. Coba lagi.', 'error');
                        resetButton();
                    }
                });
                
                function resetButton() {
                    $btn.prop('disabled', false);
                    if (isSubmitted) {
                        $btn.text('Sudah Disubmit').addClass('btn-secondary').removeClass('btn-success');
                    } else {
                        $btn.text('Submit Resep').addClass('btn-success').removeClass('btn-secondary');
                    }
                }
            }
        });




        $(document).on('click', '.btn-riwayat', function () {
            console.log('Button clicked'); // Debugging
            let url = $(this).data('url');
            // Set modal title dynamically from data-title attribute (fallback to generic)
            const title = $(this).data('title') || 'Riwayat Resep';
            $('#riwayatModalTitle').text(title);

            // Set header color based on data-type (dokter => blue, farmasi => yellow)
            const type = $(this).data('type') || '';
            const header = $('#riwayatModal .modal-header');
            // remove any previously applied bg/text classes
            header.removeClass('bg-primary bg-warning text-white text-dark');
            if (type === 'dokter') {
                header.addClass('bg-primary text-white');
            } else if (type === 'farmasi') {
                header.addClass('bg-warning text-dark');
            }

            $('#riwayatModal').modal('show');
            $('#riwayatModalContent').html('<p class="text-center">Loading...</p>');

            $.get(url, function (data) {
                $('#riwayatModalContent').html(data);
            }).fail(function () {
                $('#riwayatModalContent').html('<p class="text-center text-danger">Gagal memuat data.</p>');
            });
        });



                // Handle Copy Resep button click
        $(document).on('click', '.btn-copy-resep', function() {
            const sourceVisitationId = $(this).data('visitation-id');
            const sourceType = $(this).data('source');
            const targetVisitationId = $('#visitation_id').val();
            
            // Show SweetAlert confirmation
            Swal.fire({
                title: 'Konfirmasi',
                text: 'Yakin ingin menyalin resep ini ke kunjungan saat ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Salin!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    copyResep();
                }
            });
            
            function copyResep() {
                // Show loading state
                const $button = $('.btn-copy-resep');
                $button.html('<i class="fas fa-spinner fa-spin"></i> Menyalin...');
                
                // Send AJAX request to copy prescriptions
                $.ajax({
                url: "{{ route('erm.eresepfarmasi.copyfromhistory') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    source_visitation_id: sourceVisitationId,
                    target_visitation_id: targetVisitationId,
                    source_type: sourceType
                },
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            title: 'Sukses',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload(); // Reload to show the copied prescriptions
                        });
                    } else {
                        Swal.fire('Peringatan', response.message, 'warning');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Gagal menyalin resep. Silakan coba lagi.', 'error');
                },
                complete: function() {
                    $button.html('<i class="fas fa-copy"></i> Salin Resep');
                }
            });
            } // End of copyResep function
        });

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
            // Preload paket racikan list so we can label racikan by paket name when matched
            let paketCache = [];
            let paketCacheLoaded = false;
            function normalizeDose(s){ if(!s) return ''; return (''+s).replace(/\s+/g,'').toLowerCase(); }
            function getPaketListFromResponse(resp){
                if (!resp) return [];
                if (Array.isArray(resp)) return resp;
                if (Array.isArray(resp.data)) return resp.data;
                if (resp.success && Array.isArray(resp.data)) return resp.data;
                if (resp.success && Array.isArray(resp.pakets)) return resp.pakets;
                return [];
            }
            // Fetch paket list asynchronously (best effort)
            $.ajax({ url: "{{ route('erm.paket-racikan.list') }}", method: 'GET' })
                .done(function(resp){ paketCache = getPaketListFromResponse(resp) || []; paketCacheLoaded = true; })
                .fail(function(){ paketCache = []; paketCacheLoaded = false; });

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

                            // Helper to try find paket name for a racikan group
                            function findPaketNameForRacikan(racikanKe){
                                if (!paketCacheLoaded || !Array.isArray(paketCache) || paketCache.length === 0) return null;
                                const rItems = (data || []).filter(function(it){ return String(it.racikan_ke||'') === String(racikanKe||''); });
                                if (rItems.length === 0) return null;
                                const rIds = rItems.map(function(it){ return String(it.obat_id||''); }).filter(Boolean).sort();
                                const rDoseMap = {};
                                rItems.forEach(function(it){ rDoseMap[String(it.obat_id||'')] = normalizeDose(it.dosis||''); });

                                let candidate = null;
                                // prefer exact dose + obat match; fallback to obat-only match
                                paketCache.forEach(function(p){
                                    const dets = p.details || p.obats || p.items || [];
                                    const pIds = dets.map(function(d){ return String((d.obat && d.obat.id) ? d.obat.id : (d.obat_id||'')); }).filter(Boolean).sort();
                                    if (pIds.length !== rIds.length) return;
                                    // compare id sets
                                    const sameIds = pIds.every(function(val, idx){ return val === rIds[idx]; });
                                    if (!sameIds) return;
                                    // check dose equality when available
                                    const doseAllMatch = dets.every(function(d){
                                        const oid = String((d.obat && d.obat.id) ? d.obat.id : (d.obat_id||''));
                                        const pDose = normalizeDose(d.dosis||'');
                                        return rDoseMap[oid] ? rDoseMap[oid] === pDose : true; // tolerate missing dose from response
                                    });
                                    if (doseAllMatch) {
                                        candidate = p.nama_paket || p.name || null;
                                    } else if (!candidate) {
                                        // keep as weaker match if no exact dose match found later
                                        candidate = p.nama_paket || p.name || null;
                                    }
                                });
                                return candidate;
                            }

                            (data || []).forEach(function(item) {
                                if (item.racikan_ke) {
                                    const key = String(item.racikan_ke);
                                    if (!seenRacikan[key]) {
                                        let label = 'Racikan ' + key;
                                        const paketName = findPaketNameForRacikan(key);
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

        // Open Paket Racikan Modal
        $(document).on('click', '#paket-racikan', function() {
            // Reset form first
            resetFormPaket();
            // Load list
            loadPaketRacikanList();
            // Show modal
            $('#paketRacikanModalFarmasi').modal('show');
        });

        // Initialize selects when modal is fully shown
        $('#paketRacikanModalFarmasi').on('shown.bs.modal', function() {
            initializePaketRacikanSelects();
            // init aturan pakai select2 for farmasi modal and ensure prefilled value shows
            initAturanPakaiSelect2('.select2-aturan-pakai-farmasi', $('#paketRacikanModalFarmasi'));
            $('.select2-aturan-pakai-farmasi').each(function(){
                const v = $(this).val();
                if (v && $(this).find('option[value="'+v+'"]').length === 0) {
                    $(this).append(new Option(v, v, true, true)).trigger('change');
                }
            });
        });

        // Reset button click handler (Farmasi)
        $(document).on('click', '#resetFormPaketBtnFarmasi', function() {
            resetFormPaket();
        });

        // Load Paket Racikan List (robust to different response shapes)
        function loadPaketRacikanList() {
            $.ajax({
                url: "{{ route('erm.paket-racikan.list') }}",
                method: 'GET',
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

                    let tbody = $('#paketRacikanTableBodyFarmasi');
                    tbody.empty();
                    if (paketList.length === 0) {
                        tbody.append('<tr><td colspan="3" class="text-center">Belum ada paket racikan</td></tr>');
                    } else {
                        paketList.forEach(function(paket, index) {
                            // ensure paket object is available as data on buttons (stringify may be needed)
                            const paketJson = JSON.stringify(paket).replace(/'/g, "\\'");
                                tbody.append(`
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${paket.nama_paket}</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary copy-paket" data-id="${paket.id}" data-paket='${paketJson}'>Gunakan</button>
                                        <button class="btn btn-sm btn-info edit-paket" data-paket='${paketJson}'>Edit</button>
                                        <button class="btn btn-sm btn-danger delete-paket" data-id="${paket.id}">Hapus</button>
                                    </td>
                                </tr>
                            `);
                        });
                    }
                },
                error: function() {
                    const tbody = $('#paketRacikanTableBodyFarmasi');
                    tbody.empty();
                    tbody.append('<tr><td colspan="4" class="text-center text-danger">Gagal memuat paket racikan</td></tr>');
                }
            });
        }

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
                // ensure option exists so select2 can show it when initialized
                if (aturanVal && $('#paketAturanPakaiFarmasi option[value="'+aturanVal+'"]').length === 0) {
                    $('#paketAturanPakaiFarmasi').append(new Option(aturanVal, aturanVal, true, true));
                }
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
            $.ajax({ url: "{{ route('erm.paket-racikan.copy.farmasi') }}", method: 'POST', data: { _token: "{{ csrf_token() }}", paket_racikan_id: paketId, visitation_id: visitationId, bungkus: bungkus, aturan_pakai: aturanPakai }, success: function(response) {
                    if (response.success) {
                        $('#gunakanPaketModalFarmasi').addClass('reload-after-close');
                        racikanCount = response.racikan_ke;
                        // Prefer client-side rendering from paket data saved earlier
                        let paketData = $('#selectedPaketIdFarmasi').data('paket') || null;
                        if (paketData && typeof paketData === 'string') {
                            try { paketData = JSON.parse(paketData); } catch (e) { paketData = null; }
                        }
                        if (paketData) {
                            createRacikanCardFromPaketWithCustomData(paketData, response.racikan_ke, bungkus, aturanPakai);
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
                }, error: function(xhr) { let errorMessage = 'Gagal menerapkan paket racikan'; if (xhr.responseJSON && xhr.responseJSON.message) { errorMessage = xhr.responseJSON.message; } Swal.fire({ icon: 'error', title: 'Gagal', text: errorMessage, confirmButtonColor: '#3085d6' }); }, complete: function() { $('#konfirmasiGunakanPaketFarmasi').html('OK').prop('disabled', false); $('#gunakanPaketModalFarmasi').modal('hide'); }
            });
        });

        // Handler untuk kembali ke modal paket racikan jika user batal di modal konfirmasi (Farmasi)
        $('#gunakanPaketModalFarmasi').on('hidden.bs.modal', function (e) {
            if (!$(e.target).hasClass('reload-after-close')) {
                setTimeout(function() { $('#paketRacikanModalFarmasi').modal('show'); }, 300);
            }
        });

        // Initialize aturan pakai select2 for gunakan modal when shown
        $('#gunakanPaketModalFarmasi').on('shown.bs.modal', function() {
            initAturanPakaiSelect2('#paketAturanPakaiFarmasi', $('#gunakanPaketModalFarmasi'));
            const sel = $('#paketAturanPakaiFarmasi');
            const v = sel.val();
            if (v && sel.find('option[value="'+v+'"]').length === 0) {
                sel.append(new Option(v, v, true, true)).trigger('change');
            }
        });

        // Function to create racikan card from paket data (reused functions from dokter page)
        function createRacikanCardFromPaket(paket, racikanKe) {
            // Delegate to the unified builder so paket-created cards match manual "Tambah Racikan"
            const bungkus = paket.bungkus_default || 1;
            const aturan = paket.aturan_pakai_default || '';
            createRacikanCardFromPaketWithCustomData(paket, racikanKe, bungkus, aturan);
        }

        function createRacikanCardFromPaketWithCustomData(paket, racikanKe, customBungkus, customAturanPakai) {
            // Build obat rows compatible with the manual "Tambah Racikan" template
            let obatRows = '';
            const details = paket.details || paket.obats || paket.items || [];
            details.forEach(function(detail) {
                const obat = detail.obat || {};
                const nama = obat.nama || '-';
                const dosisRacik = detail.dosis || '-';
                const satuan = obat.satuan || '';
                // prefer stok_gudang if present (server may provide it)
                const stokGudang = typeof obat.stok_gudang !== 'undefined' ? obat.stok_gudang : (obat.stok || 0);
                const stokColor = stokGudang < 10 ? 'red' : (stokGudang < 100 ? 'yellow' : 'inherit');
                obatRows += `
                    <tr data-obat-id="${obat.id || ''}" data-dosis="${dosisRacik}" data-base-dosis="${obat.dosis || ''}" data-jumlah="1">
                        <td data-id="${obat.id || ''}">${nama}</td>
                        <td>${dosisRacik} ${satuan}</td>
                        <td><span style="color: ${stokColor};">${stokGudang}</span></td>
                        <td class="stok-dikurangi">-</td>
                        <td><button class="btn btn-danger btn-sm hapus-obat" disabled>Hapus</button></td>
                    </tr>
                `;
            });

            // Use the exact same card structure as manual add, but with controls disabled (saved state)
            let racikanCard = `
                <div class="racikan-card mb-4 p-3 border rounded" data-racikan-ke="${racikanKe}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 style="color: yellow;"><strong>Racikan ${racikanKe}</strong></h5>
                        <div>
                            <button class="btn btn-warning btn-sm edit-racikan mr-2">Edit Racikan</button>
                            <button class="btn btn-danger btn-sm hapus-racikan">Hapus Racikan</button>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Nama Obat</label>
                            <select class="form-control select2-obat-racikan" name="obat_id">
                                <option value="">Search and select an obat...</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Dosis</label>
                            <input type="number" class="form-control dosis_input" disabled>
                        </div>
                        <div class="col-md-2">
                            <label>Satuan Dosis</label>
                            <select class="form-control mode_dosis" disabled>
                                <option value="normal">Normal</option>
                                <option value="tablet">Tablet</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary btn-block tambah-obat" disabled>Tambah ke Racikan</button>
                        </div>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nama Obat</th>
                                <th>Dosis</th>
                                <th>Stok</th>
                                <th>Stok Dikurangi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="resep-table-body">
                            ${obatRows || '<tr class="no-data"><td colspan="6" class="text-center text-muted">Belum ada data</td></tr>'}
                        </tbody>
                    </table>

                    <div class="row">
                        <div class="col-md-3">
                            <label>Racikan</label>
                            <select class="form-control select2-wadah-racikan wadah" name="wadah_id" disabled>
                                <option value="">Search and select wadah...</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Bungkus</label>
                            <input type="number" class="form-control bungkus" value="${customBungkus}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label>Aturan Pakai</label>
                            <select class="form-control select2-aturan-template-racikan" style="width:100%" disabled>
                                <option value="">${customAturanPakai || ''}</option>
                            </select>
                            <input type="hidden" class="aturan_pakai" value="${customAturanPakai || ''}" />
                        </div>
                    </div>

                    <button class="btn btn-success btn-block mt-3 tambah-resepracikan" disabled>Sudah Disimpan</button>
                </div>
            `;

            $('#racikan-container').append(racikanCard);
            // initialize select2 for the appended wadah so UI remains consistent
            $('#racikan-container .select2-wadah-racikan').last().select2({ placeholder: 'Search wadah...', ajax: { url: '{{ route("wadah.search") }}', dataType: 'json', delay: 250, data: function (params) { return { q: params.term }; }, processResults: function (data) { return { results: data }; }, cache: true } });
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
            // wadah (select2) - set value then trigger change
            if (paket.wadah && paket.wadah.id) {
                $('.select2-wadah-paket').val(paket.wadah.id).trigger('change');
            } else {
                $('.select2-wadah-paket').val('').trigger('change');
            }
            $('#formPaketRacikanFarmasi input[name="bungkus_default"]').val(paket.bungkus_default || 10);
            const aturanVal = paket.aturan_pakai_default || '';
            const aturanSelect = $('.select2-aturan-pakai-farmasi');
            if (aturanSelect.length) {
                if (!aturanSelect.hasClass('select2-hidden-accessible')) {
                    initAturanPakaiSelect2('.select2-aturan-pakai-farmasi', $('#paketRacikanModalFarmasi'));
                }
                if (aturanVal && aturanSelect.find('option[value="'+aturanVal+'"]').length === 0) {
                    aturanSelect.append(new Option(aturanVal, aturanVal, true, true));
                }
                aturanSelect.val(aturanVal).trigger('change');
            } else {
                // fallback for legacy input
                $('#formPaketRacikanFarmasi input[name="aturan_pakai_default"]').val(aturanVal || '');
            }

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
        function resetFormPaket() {
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

    function fetchFarmasiResep() {
    const visitationId = $('#visitation_id').val();
    console.log('Fetching resep data for visitation:', visitationId);

    $.get(`/erm/eresepfarmasi/${visitationId}/json`, function (res) {
        $('#resep-wrapper').show();
        $('#empty-resep-message').hide();

        // ==== NON RACIKAN ====
        const tbody = $('#resep-table-body');
        tbody.empty();

    res.non_racikans.forEach(item => {
            tbody.append(`
                <tr data-id="${item.id}">
                    <td>${item.obat?.nama ?? '-'}</td>
                    <td>${item.jumlah}</td>
                    <td>${item.harga ?? item.obat?.harga_nonfornas ?? 0}</td>
                    <td>${item.diskon ?? 0}</td>                   
                    <td>${item.obat?.stok ?? 0}</td>
                    <td>${item.aturan_pakai}</td>
                    <td>
                        <button class="btn btn-success btn-sm edit" data-id="${item.id}">Edit</button>
                        <button class="btn btn-danger btn-sm hapus" data-id="${item.id}">Hapus</button>
                    </td>
                </tr>
            `);
        });

        // ==== RACIKAN ====
        const racikanWrapper = $('#racikan-container');
        racikanWrapper.empty(); // clear old data
        let racikanCount = 0;
        
        console.log('Racikan data from server:', res.racikans);

    Object.entries(res.racikans).forEach(([ke, items]) => {
            console.log(`Processing racikan with ke=${ke}, items:`, items);

            if (ke === '0' || ke === 0) {
                console.warn('WARNING: Found racikan with key 0, this will cause update problems!');
            }

            // Build a paket-like object so we can reuse the unified card builder
            const first = items[0] || {};
            const bungkus = first.bungkus ?? (first.bungkus_default || 1);
            const aturan = first.aturan_pakai ?? (first.aturan_pakai_default || '');
            const paketObj = {
                wadah: first.wadah ? { id: first.wadah_id || '', nama: first.wadah } : null,
                bungkus_default: bungkus,
                aturan_pakai_default: aturan,
                details: items.map(item => ({ obat: item.obat || {}, dosis: item.dosis }))
            };

            // Use the same DOM builder used by paket and manual add -> ensures identical markup
            createRacikanCardFromPaketWithCustomData(paketObj, ke, bungkus, aturan);
            racikanCount++;
        });
        // After rebuilding the DOM from server response, update totals
        updateTotalPrice();
    });
}

// DEBUG: Global handler for edit-racikan to ensure it always works
$(document).on('click', '.edit-racikan', function () {
    const card = $(this).closest('.racikan-card');
    // Enable all possible field classes to handle different naming conventions in the HTML
    card.find('.wadah, .jumlah_bungkus, .bungkus, .aturan_pakai').prop('disabled', false);
    card.find('.tambah-resepracikan').addClass('d-none');
    // Always show and enable Update button
    card.find('.update-resepracikan').removeClass('d-none').prop('disabled', false);
    // Enable all Hapus and Edit buttons in this racikan card
    card.find('.hapus-obat, .edit-obat').prop('disabled', false);
    console.log('Edit racikan clicked - fields enabled, hapus-obat and edit-obat enabled');

    // If the add-obat form doesn't exist in this card, insert it so user can add obat while editing
    // Prevent duplicate add-rows: if a select2 for obat already exists, don't insert another
    if (card.find('.select2-obat-racikan').length === 0) {
        // remove any stray add-obat-row remnants to avoid duplicates
        card.find('.add-obat-row').not(':first').remove();
        const addRow = `
            <div class="row add-obat-row mb-3">
                <div class="col-md-6">
                    <label>Nama Obat</label>
                    <select class="form-control select2-obat-racikan" name="obat_id">
                        <option value="">Search and select an obat...</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Dosis</label>
                    <input type="number" class="form-control dosis_input">
                </div>
                <div class="col-md-2">
                    <label>Satuan Dosis</label>
                    <select class="form-control mode_dosis">
                        <option value="normal">Normal</option>
                        <option value="tablet">Tablet</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary btn-block tambah-obat">Tambah ke Racikan</button>
                </div>
            </div>
        `;

        // Insert the add row right above the table inside the card
        card.find('table').before(addRow);

        // Initialize select2 for the newly inserted select (only on the last one)
        card.find('.select2-obat-racikan').last().select2({
            placeholder: 'Search obat...',
            ajax: {
                url: '{{ route("obat.search") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    let items = Array.isArray(data.results) ? data.results : data;
                    return {
                        results: items.map(function(item) {
                            return {
                                id: item.id,
                                text: item.text || (item.nama + (item.harga_nonfornas ? ' - ' + item.harga_nonfornas : '')),
                                nama: item.nama,
                                dosis: item.dosis,
                                satuan: item.satuan,
                                stok: item.stok,
                                stok_gudang: typeof item.stok_gudang !== 'undefined' ? item.stok_gudang : item.stok,
                                harga_nonfornas: item.harga_nonfornas
                            };
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 3
        });

        // Enable the tambah-obat button we just inserted
        card.find('.tambah-obat').prop('disabled', false);
    } else {
        // If the form exists, just ensure the add button is enabled
        card.find('.tambah-obat').prop('disabled', false);
    }

    // Ensure aturan_pakai is editable: convert existing disabled text input into a select2 + hidden input
    (function handleAturanPakaiConversion() {
        // If there's already a select2 control, just enable it
        const $existingSelect = card.find('.select2-aturan-template-racikan');
        if ($existingSelect.length) {
            $existingSelect.prop('disabled', false).trigger('change.select2');
            card.find('.aturan_pakai').prop('disabled', false);
            return;
        }

        // Find plain input (server-rendered) and replace it with select + hidden input
        const $plain = card.find('.aturan_pakai').filter(function(){ return $(this).is('input[type=text]') || $(this).is('input'); }).first();
        if ($plain.length) {
            const currentVal = $plain.val() || '';
            // Build elements
            const selHtml = `<select class="form-control select2-aturan-template-racikan" style="width:100%"><option value=""></option></select>`;
            const hiddenHtml = `<input type="hidden" class="aturan_pakai" value="${(currentVal+'').replace(/"/g,'&quot;')}">`;
            $plain.replaceWith(selHtml + hiddenHtml);

            const $sel = card.find('.select2-aturan-template-racikan').last();
            // initialize select2 like other instances
            $sel.select2({
                width: '100%',
                placeholder: '-- Pilih Template Aturan Pakai --',
                allowClear: true,
                minimumInputLength: 2,
                ajax: {
                    url: '{{ route('erm.aturan-pakai.list.active') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { q: params.term }; },
                    processResults: function(data) {
                        return {
                            results: (data || []).map(function(item){ return { id: item.id, text: item.template.length > 80 ? item.template.substring(0,80) + '...' : item.template, template: item.template }; })
                        };
                    },
                    cache: true
                },
                templateResult: function(item){ return item && item.template ? $('<div>').text(item.template) : item.text; },
                templateSelection: function(item){ return item && item.template ? item.template : item.text; },
                escapeMarkup: function(m){ return m; }
            });

            // If there was an existing value, insert it as a temporary option so it shows
            if (currentVal) {
                const tmpId = 'tmp_' + Date.now();
                const newOption = new Option(currentVal, tmpId, true, true);
                $sel.append(newOption).trigger('change');
            }

            // Sync selection into hidden input
            $sel.on('select2:select', function(e){
                const tpl = e.params && e.params.data && e.params.data.template ? e.params.data.template : (e.params && e.params.data ? e.params.data.text : '');
                $(this).closest('.racikan-card').find('.aturan_pakai').val(tpl);
            });
            $sel.on('select2:clear', function(){ $(this).closest('.racikan-card').find('.aturan_pakai').val(''); });
        }
    })();
});


// Notification polling for farmasi create page
@if(auth()->user()->hasRole('Farmasi'))
$(document).ready(function() {
    let lastCheck = 0;
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
                lastCheck = response.timestamp;
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
