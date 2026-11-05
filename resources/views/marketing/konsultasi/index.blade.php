@extends('layouts.marketing.app')

@section('title', 'Biaya Konsultasi dan Lain Lain')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Biaya Konsultasi dan Lain Lain</h4>
            <button class="btn btn-primary" id="btnAddKonsultasi">
                <i class="mdi mdi-plus"></i> Tambah Data
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="konsultasiTable">
                    <thead>
                        <tr>
                            <th style="width: 6%;">No</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Spesialisasi</th>
                            <th style="width: 18%;">Harga</th>
                            <th style="width: 16%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($konsultasis as $index => $konsultasi)
                            <tr data-id="{{ $konsultasi->id }}">
                                <td>{{ $index + 1 }}</td>
                                @php $radiologiList = $hasRadiologi ? $konsultasi->radiologiTests : collect(); @endphp
                                <td>
                                    {{ $konsultasi->nama }}
                                    @foreach($radiologiList as $test)
                                        <div class="small text-muted"><i class="mdi mdi-radiology-box-outline"></i> termasuk {{ $test->nama }} Rp {{ number_format($test->pivot->harga, 0, ',', '.') }}</div>
                                    @endforeach
                                </td>
                                <td>{{ $konsultasi->kategori ?: '-' }}</td>
                                @php $spesialisasiList = $hasSpesialisasi ? $konsultasi->spesialisasis->sortBy('nama') : collect(); @endphp
                                <td data-order="{{ $spesialisasiList->isEmpty() ? '0' : '1' . $spesialisasiList->pluck('nama')->implode(',') }}">
                                    @forelse($spesialisasiList as $spesialisasi)
                                        <span class="badge badge-soft-primary">{{ $spesialisasi->nama }}</span>
                                    @empty
                                        @if($hasSpesialisasi && str_contains(mb_strtolower($konsultasi->nama), 'konsultasi'))
                                            <span class="badge badge-soft-danger">Nonaktif / belum dipetakan: tidak muncul di dokter</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    @endforelse
                                </td>
                                <td data-order="{{ $konsultasi->harga }}">Rp {{ number_format($konsultasi->harga, 0, ',', '.') }}</td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-warning btn-edit"
                                        data-id="{{ $konsultasi->id }}"
                                        data-nama="{{ $konsultasi->nama }}"
                                        data-kategori="{{ $konsultasi->kategori }}"
                                        data-spesialisasi-ids="{{ $spesialisasiList->pluck('id')->values()->toJson() }}"
                                        data-harga="{{ $konsultasi->harga }}"
                                        data-radiologi="{{ $radiologiList->map(fn ($t) => ['test_id' => $t->id, 'harga' => (float) $t->pivot->harga])->values()->toJson() }}"
                                        data-terkunci="{{ $terkunciIds->has($konsultasi->id) ? 1 : 0 }}"
                                    >
                                        Edit
                                    </button>
                                    @if($dipakaiIds->has($konsultasi->id))
                                        {{-- Its billing / invoice history needs the item: retire it with Nonaktif instead --}}
                                        <span class="d-inline-block" tabindex="0" title="Sudah dipakai di billing / invoice: tidak bisa dihapus. Gunakan Nonaktif di Edit.">
                                            <button type="button" class="btn btn-sm btn-danger" disabled style="pointer-events: none;">Hapus</button>
                                        </span>
                                    @else
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-danger btn-delete"
                                            data-id="{{ $konsultasi->id }}"
                                            data-nama="{{ $konsultasi->nama }}"
                                        >
                                            Hapus
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="konsultasiModal" tabindex="-1" role="dialog" aria-labelledby="konsultasiModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="konsultasiForm">
                @csrf
                <input type="hidden" id="formMethod" value="POST">
                <input type="hidden" id="konsultasiId">
                <div class="modal-header">
                    <h5 class="modal-title" id="konsultasiModalLabel">Tambah Biaya Konsultasi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <input type="text" class="form-control" id="nama" name="nama" required>
                    </div>
                    <div class="form-group">
                        <label for="kategori">Kategori</label>
                        <input type="text" class="form-control" id="kategori" name="kategori" placeholder="Contoh: Konsultasi, Administrasi, Lain-lain">
                    </div>
                    <div class="form-group">
                        <label for="spesialisasi_ids">Spesialisasi</label>
                        <select class="form-control" id="spesialisasi_ids" name="spesialisasi_ids[]" multiple style="width: 100%;" @unless($hasSpesialisasi) disabled @endunless>
                            @foreach ($spesialisasis as $spesialisasi)
                                <option value="{{ $spesialisasi->id }}">{{ $spesialisasi->nama }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            @if($hasSpesialisasi)
                                Wajib untuk jenis konsultasi: hanya muncul di dokter spesialisasi yang dipilih (boleh beberapa).
                            @else
                                Belum aktif: jalankan migration dulu.
                            @endif
                        </small>
                        @if($hasSpesialisasi)
                            <div class="custom-control custom-checkbox mt-1">
                                <input type="checkbox" class="custom-control-input" id="nonaktif">
                                <label class="custom-control-label" for="nonaktif">Nonaktif: tidak muncul di dokter (data dan riwayatnya tetap ada)</label>
                            </div>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="harga">Harga</label>
                        <input type="number" min="0" step="0.01" class="form-control" id="harga" name="harga" required>
                    </div>
                    <div class="form-group mb-0">
                        <label class="d-flex justify-content-between align-items-center">
                            <span>Termasuk radiologi <small class="text-muted">(item paket lama, mis. + USG)</small></span>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddRadiologi" @unless($hasRadiologi) disabled @endunless>
                                <i class="mdi mdi-plus"></i> Radiologi
                            </button>
                        </label>
                        <div class="alert alert-warning py-1 px-2 small mb-2 d-none" id="radiologiLocked">
                            <i class="mdi mdi-lock"></i> Radiologi paket ini sudah dipakai transaksi, jadi tidak bisa diubah lagi (koreksi hanya di hari pengisian) agar riwayat USG lama tetap benar.
                            Untuk aturan baru: buat item baru, lalu centang <b>Nonaktif</b> di item ini. Harga paket tetap boleh diubah.
                        </div>
                        <div id="radiologiRows"></div>
                        <small class="form-text text-muted" id="radiologiInfo">
                            @if($hasRadiologi)
                                Hanya untuk item yang harganya sudah termasuk radiologi, agar USG di transaksinya tercatat. Untuk konsultasi baru tidak perlu: centang <b>Tambahan konsultasi</b> di Master Radiologi, dokter tinggal mencentang USG di popup.
                            @else
                                Belum aktif: jalankan migration dulu.
                            @endif
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#konsultasiTable').DataTable({
        pageLength: 25,
        order: [[3, 'asc'], [1, 'asc']],
    });

    // Multi-select spesialisasi; a jenis konsultasi without any is offered nowhere
    const $spesialisasi = $('#spesialisasi_ids');
    if ($.fn.select2) {
        $spesialisasi.select2({
            dropdownParent: $('#konsultasiModal'),
            placeholder: 'Pilih spesialisasi...',
            allowClear: true,
            width: '100%',
        });
    }

    function resetForm() {
        $('#konsultasiForm')[0].reset();
        $spesialisasi.val([]).trigger('change');
        $('#radiologiRows').empty();
        setRadiologiLocked(false);
        $('#nonaktif').prop('checked', false).trigger('change');
        updateRadiologiInfo();
        $('#konsultasiId').val('');
        $('#formMethod').val('POST');
        $('#konsultasiModalLabel').text('Tambah Biaya Konsultasi');
    }

    // Radiologi included in the paket: one row per test with its part of the harga
    const radiologiTests = @json($radiologiTests);
    const rupiah = function (n) { return 'Rp ' + Math.round(Number(n) || 0).toLocaleString('id-ID'); };

    function addRadiologiRow(testId, harga) {
        const options = radiologiTests.map(function (t) {
            return '<option value="' + t.id + '" data-harga="' + t.harga + '"' + (String(t.id) === String(testId) ? ' selected' : '') + '>'
                + $('<div>').text(t.nama).html() + ' (normal ' + rupiah(t.harga) + ')</option>';
        }).join('');
        const $row = $('<div class="input-group input-group-sm mb-2 radiologi-row">'
            + '<select class="form-control radiologi-test"><option value="">Pilih pemeriksaan...</option>' + options + '</select>'
            + '<div class="input-group-prepend input-group-append"><span class="input-group-text">Rp</span></div>'
            + '<input type="number" min="0" step="0.01" class="form-control radiologi-harga" style="max-width:130px" placeholder="Harga paket">'
            + '<div class="input-group-append"><button type="button" class="btn btn-outline-danger btn-remove-radiologi" title="Hapus">&times;</button></div>'
            + '</div>');
        $row.find('.radiologi-harga').val(harga !== undefined ? harga : '');
        $('#radiologiRows').append($row);
        updateRadiologiInfo();
    }

    const radiologiInfoDefault = $('#radiologiInfo').html();

    function updateRadiologiInfo() {
        const $rows = $('#radiologiRows .radiologi-row');
        if (!$rows.length) {
            $('#radiologiInfo').html(radiologiInfoDefault);
            return;
        }
        let bagian = 0;
        $rows.each(function () { bagian += Number($(this).find('.radiologi-harga').val()) || 0; });
        const harga = Number($('#harga').val()) || 0;
        $('#radiologiInfo').html('Tagihan pasien tetap ' + rupiah(harga) + ' = konsultasi <b>' + rupiah(harga - bagian) + '</b> + radiologi <b>' + rupiah(bagian) + '</b>'
            + (bagian > harga ? ' <span class="text-danger">(radiologi melebihi harga)</span>' : ''));
    }

    function setRadiologiLocked(locked) {
        $('#radiologiLocked').toggleClass('d-none', !locked);
        $('#btnAddRadiologi').prop('disabled', locked || {{ $hasRadiologi ? 'false' : 'true' }});
        $('#radiologiRows').find('select, input, button').prop('disabled', locked);
    }

    // Nonaktif = no spesialisasi
    $('#nonaktif').on('change', function () {
        $spesialisasi.prop('disabled', this.checked || {{ $hasSpesialisasi ? 'false' : 'true' }});
    });

    $('#btnAddRadiologi').on('click', function () { addRadiologiRow(); });
    $(document).on('click', '.btn-remove-radiologi', function () { $(this).closest('.radiologi-row').remove(); updateRadiologiInfo(); });
    $(document).on('input change', '.radiologi-harga, #harga', updateRadiologiInfo);

    function showValidationError(xhr, fallbackMessage) {
        let message = fallbackMessage;

        if (xhr.responseJSON) {
            if (xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }

            if (xhr.responseJSON.errors) {
                const firstError = Object.values(xhr.responseJSON.errors)[0];
                if (Array.isArray(firstError) && firstError.length) {
                    message = firstError[0];
                }
            }
        }

        Swal.fire('Gagal', message, 'error');
    }

    $('#btnAddKonsultasi').on('click', function () {
        resetForm();
        $('#konsultasiModal').modal('show');
    });

    $('.btn-edit').on('click', function () {
        resetForm();
        $('#konsultasiId').val($(this).data('id'));
        $('#nama').val($(this).data('nama'));
        $('#kategori').val($(this).data('kategori'));
        $spesialisasi.val(($(this).data('spesialisasi-ids') || []).map(String)).trigger('change');
        $('#harga').val($(this).data('harga'));
        ($(this).data('radiologi') || []).forEach(function (r) { addRadiologiRow(r.test_id, r.harga); });
        setRadiologiLocked(Number($(this).data('terkunci')) === 1);
        const tanpaSpesialisasi = !($(this).data('spesialisasi-ids') || []).length;
        $('#nonaktif').prop('checked', tanpaSpesialisasi && String($(this).data('nama')).toLowerCase().indexOf('konsultasi') !== -1).trigger('change');
        $('#formMethod').val('PUT');
        $('#konsultasiModalLabel').text('Edit Biaya Konsultasi');
        $('#konsultasiModal').modal('show');
    });

    $('#konsultasiForm').on('submit', function (e) {
        e.preventDefault();

        const id = $('#konsultasiId').val();
        const method = $('#formMethod').val();
        const url = method === 'PUT'
            ? '{{ url('/marketing/biaya-konsultasi') }}/' + id
            : '{{ route('marketing.konsultasi.store') }}';
        const payload = {
            _token: '{{ csrf_token() }}',
            nama: $('#nama').val(),
            kategori: $('#kategori').val(),
            nonaktif: $('#nonaktif').is(':checked') ? 1 : 0,
            spesialisasi_ids: $('#nonaktif').is(':checked') ? [] : ($spesialisasi.val() || []),
            harga: $('#harga').val(),
            radiologi: $('#radiologiRows .radiologi-row').map(function () {
                return { test_id: $(this).find('.radiologi-test').val(), harga: $(this).find('.radiologi-harga').val() };
            }).get(),
        };

        if (method === 'PUT') {
            payload._method = 'PUT';
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: payload,
            success: function (response) {
                Swal.fire('Berhasil', response.message, 'success').then(function () {
                    window.location.reload();
                });
            },
            error: function (xhr) {
                showValidationError(xhr, 'Data gagal disimpan.');
            }
        });
    });

    $('.btn-delete').on('click', function () {
        const id = $(this).data('id');
        const nama = $(this).data('nama');

        Swal.fire({
            title: 'Hapus data?',
            text: 'Data "' + nama + '" akan dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: '{{ url('/marketing/biaya-konsultasi') }}/' + id,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'DELETE'
                },
                success: function (response) {
                    Swal.fire('Berhasil', response.message, 'success').then(function () {
                        window.location.reload();
                    });
                },
                error: function (xhr) {
                    showValidationError(xhr, 'Data gagal dihapus.');
                }
            });
        });
    });
});
</script>
@endpush