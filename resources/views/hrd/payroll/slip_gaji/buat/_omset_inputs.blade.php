{{--
    Omset Bulanan per penghasil omset (insentif pool). Each row can be typed by hand or pulled from the system
    (Laporan Omset numbers, see PrSlipGajiController::omsetSistemSources); the value is saved with Buat Slip.
--}}
@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $groups = collect($sources)->groupBy('group', true);
@endphp

<div class="omset-bulanan">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-2" style="gap:8px">
        <div>
            <div class="font-weight-bold">Omset Bulanan · {{ $periode }}</div>
            <small class="text-muted">Untuk insentif omset. Pilih sumber "Tarik dari sistem" atau isi manual.</small>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btnTarikSemuaOmset">
            <i class="fas fa-sync-alt mr-1"></i> Tarik semua dari sistem
        </button>
    </div>

    @forelse ($insentifOmset as $omset)
        @php
            $value = $saved[$omset->id] ?? null;
            $suggest = $suggested[$omset->id] ?? null;
        @endphp
        <div class="form-group mb-2 omset-row" data-id="{{ $omset->id }}" data-suggest="{{ $suggest }}">
            <label class="mb-1" for="omset_{{ $omset->id }}">{{ $omset->nama_penghasil }}</label>
            <div class="form-row">
                <div class="col-sm-7 mb-1">
                    <select class="form-control form-control-sm omset-source" aria-label="Sumber omset {{ $omset->nama_penghasil }}">
                        <option value="">Isi manual</option>
                        @foreach ($groups as $group => $items)
                            <optgroup label="Tarik dari sistem · {{ $group }}">
                                @foreach ($items as $key => $source)
                                    <option value="{{ $key }}" data-nominal="{{ $source['nominal'] }}">{{ $source['label'] }} · {{ $rp($source['nominal']) }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-5 mb-1">
                    <input type="number" class="form-control form-control-sm omset-nominal" name="omset_bulanan[{{ $omset->id }}]"
                        id="omset_{{ $omset->id }}" step="any" min="0" value="{{ $value !== null ? (float) $value : '' }}" placeholder="0">
                </div>
            </div>
            <small class="text-muted omset-hint">{{ $value !== null ? 'Tersimpan: ' . $rp($value) : 'Belum diisi untuk bulan ini' }}</small>
        </div>
    @empty
        <div class="alert alert-light mb-0">Belum ada master Insentif Omset.</div>
    @endforelse

    <small class="text-muted d-block mt-2">
        Angka sistem = Laporan Omset (invoice yang sudah ditransaksikan dikurangi retur).
        Hati-hati dobel: kalau Asuransi punya baris sendiri, pilih "Umum saja" untuk baris klinik.
    </small>
</div>
