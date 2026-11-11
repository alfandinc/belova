@extends('layouts.finance.app')
@section('title', 'Finance | Layar Pelanggan')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center" style="gap:1rem;">
            <div>
                <h3 class="mb-0 font-weight-bold">Layar Pelanggan</h3>
                <div class="text-muted small">Tablet di meja kasir yang menghadap pasien: menampilkan rincian billing secara langsung dan survei kepuasan setelah pembayaran.</div>
            </div>
            <button type="button" class="btn btn-primary btn-sm" id="btnAddLayar">
                <i class="fas fa-plus mr-1"></i> Tambah Layar
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th style="width:160px;">Klinik</th>
                            <th style="width:100px;">Status</th>
                            <th style="width:170px;">Tablet</th>
                            <th>Link untuk tablet</th>
                            <th style="width:250px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($layars as $layar)
                            <tr>
                                <td class="font-weight-bold">{{ $layar->nama }}</td>
                                <td>{{ optional($layar->klinik)->nama ?? 'Semua' }}</td>
                                <td>
                                    @if($layar->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="small">
                                    @if($layar->isOnline())
                                        <span class="text-success"><i class="fas fa-circle mr-1" style="font-size:8px;"></i>Terhubung</span>
                                    @elseif($layar->last_seen_at)
                                        <span class="text-muted"><i class="fas fa-circle mr-1" style="font-size:8px;"></i>Terakhir {{ $layar->last_seen_at->diffForHumans() }}</span>
                                    @else
                                        <span class="text-muted">Belum pernah dibuka</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control lp-link" readonly value="{{ $layar->displayUrl() }}">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary btn-copy" title="Salin link"><i class="far fa-copy"></i></button>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap" style="gap:.35rem;">
                                        <button type="button" class="btn btn-sm btn-outline-dark btn-qr" data-target="#qr-{{ $layar->id }}" title="QR untuk dibuka di tablet"><i class="fas fa-qrcode mr-1"></i>QR</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit"
                                                data-id="{{ $layar->id }}" data-nama="{{ $layar->nama }}"
                                                data-klinik="{{ $layar->klinik_id }}" data-active="{{ $layar->is_active ? 1 : 0 }}"><i class="fas fa-edit mr-1"></i>Edit</button>
                                        <button type="button" class="btn btn-sm btn-outline-warning btn-regen" data-id="{{ $layar->id }}" title="Buat link baru (link lama tidak berlaku)"><i class="fas fa-sync-alt mr-1"></i>Link Baru</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="{{ $layar->id }}" data-nama="{{ $layar->nama }}"><i class="fas fa-trash"></i></button>
                                    </div>
                                    <div id="qr-{{ $layar->id }}" class="d-none">
                                        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(240)->margin(1)->generate($layar->displayUrl()) !!}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada layar. Klik "Tambah Layar".</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mt-3">
        <div class="card-body small">
            <div class="font-weight-bold mb-2"><i class="fas fa-info-circle mr-1"></i>Cara memasang di tablet</div>
            <ol class="mb-0 pl-3">
                <li>Tablet terhubung ke wifi klinik (jaringan yang sama dengan server).</li>
                <li>Buka link layar di browser tablet (scan QR atau ketik link). Tidak perlu login.</li>
                <li>Disarankan memakai aplikasi kiosk (misalnya <em>Fully Kiosk Browser</em>) agar layar selalu menyala, layar penuh, dan pasien tidak bisa keluar dari halaman.</li>
                <li>Di halaman billing, kasir akan melihat panel <strong>Layar Pasien</strong>. Titik hijau menandakan tablet terhubung.</li>
                <li>Jika tablet hilang atau link tersebar, klik <strong>Link Baru</strong>: link lama langsung tidak berlaku.</li>
            </ol>
        </div>
    </div>
</div>

<div class="modal fade" id="modalLayar" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="formLayar" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLayarTitle">Tambah Layar</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="layarId">
                <div class="form-group">
                    <label for="layarNama">Nama</label>
                    <input type="text" class="form-control" id="layarNama" maxlength="100" placeholder="Contoh: Kasir 1" required>
                </div>
                <div class="form-group">
                    <label for="layarKlinik">Klinik (untuk logo layar awal)</label>
                    <select class="form-control" id="layarKlinik">
                        <option value="">Semua / Belova</option>
                        @foreach($kliniks as $k)
                            <option value="{{ $k->id }}">{{ $k->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="layarActive" checked>
                    <label class="custom-control-label" for="layarActive">Aktif</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalQr" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Scan di tablet</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body text-center">
                <div id="qrHolder" class="d-inline-block p-2 bg-white rounded"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    const baseUrl = @json(url('finance/layar-pelanggan'));
    const csrf = $('meta[name="csrf-token"]').attr('content');

    function req(method, url, data) {
        return $.ajax({ url: url, type: method, data: data || {}, headers: { 'X-CSRF-TOKEN': csrf }, dataType: 'json' });
    }
    function fail(xhr) {
        const msg = (xhr.responseJSON && (xhr.responseJSON.message || Object.values(xhr.responseJSON.errors || {})[0])) || 'Terjadi kesalahan.';
        Swal.fire('Gagal', Array.isArray(msg) ? msg[0] : msg, 'error');
    }
    function done(res) {
        Swal.fire({ icon: 'success', title: res.message || 'Berhasil', timer: 1200, showConfirmButton: false }).then(() => location.reload());
    }

    $('#btnAddLayar').on('click', function () {
        $('#modalLayarTitle').text('Tambah Layar');
        $('#layarId').val('');
        $('#layarNama').val('');
        $('#layarKlinik').val('');
        $('#layarActive').prop('checked', true);
        $('#modalLayar').modal('show');
    });

    $('.btn-edit').on('click', function () {
        const b = $(this);
        $('#modalLayarTitle').text('Edit Layar');
        $('#layarId').val(b.data('id'));
        $('#layarNama').val(b.data('nama'));
        $('#layarKlinik').val(b.data('klinik') || '');
        $('#layarActive').prop('checked', String(b.data('active')) === '1');
        $('#modalLayar').modal('show');
    });

    $('#formLayar').on('submit', function (e) {
        e.preventDefault();
        const id = $('#layarId').val();
        const data = { nama: $('#layarNama').val(), klinik_id: $('#layarKlinik').val(), is_active: $('#layarActive').is(':checked') ? 1 : 0 };
        req(id ? 'PUT' : 'POST', id ? baseUrl + '/' + id : baseUrl, data).then(done).catch(fail);
    });

    $('.btn-regen').on('click', function () {
        const id = $(this).data('id');
        Swal.fire({ title: 'Buat link baru?', text: 'Link lama langsung tidak berlaku. Tablet harus dibuka ulang dengan link baru.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, buat', cancelButtonText: 'Batal' })
            .then(r => { if (r.value || r.isConfirmed) req('POST', baseUrl + '/' + id + '/regenerate').then(done).catch(fail); });
    });

    $('.btn-delete').on('click', function () {
        const id = $(this).data('id');
        Swal.fire({ title: 'Hapus layar ' + $(this).data('nama') + '?', text: 'Hasil survei yang sudah masuk tetap tersimpan.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Hapus', confirmButtonColor: '#d33', cancelButtonText: 'Batal' })
            .then(r => { if (r.value || r.isConfirmed) req('DELETE', baseUrl + '/' + id).then(done).catch(fail); });
    });

    $('.btn-copy').on('click', function () {
        const input = $(this).closest('.input-group').find('.lp-link')[0];
        input.select();
        try { navigator.clipboard ? navigator.clipboard.writeText(input.value) : document.execCommand('copy'); } catch (e) { document.execCommand('copy'); }
        Swal.fire({ icon: 'success', title: 'Link disalin', timer: 900, showConfirmButton: false });
    });

    $('.btn-qr').on('click', function () {
        $('#qrHolder').html($($(this).data('target')).html());
        $('#modalQr').modal('show');
    });
});
</script>
@endsection
