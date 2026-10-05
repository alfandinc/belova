@extends('layouts.hrd.app')
@section('title', 'HRD | Libur Nasional')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container-fluid px-2">
    <div class="row mb-2">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="mb-0 font-weight-bold">Libur Nasional</h3>
                <div class="text-muted small">Karyawan yang terjadwal masuk pada libur nasional mendapat +1 jatah ganti libur, sama seperti hari Minggu.</div>
            </div>
            <div class="d-flex align-items-center mt-2 mt-sm-0">
                <div class="btn-group btn-group-sm mr-2" role="group" aria-label="Pilih tahun">
                    <button type="button" class="btn btn-light border" id="btnPrevYear" title="Tahun sebelumnya"><i class="fas fa-chevron-left"></i></button>
                    <button type="button" class="btn btn-light border font-weight-bold" id="yearLabel" disabled>{{ $tahun }}</button>
                    <button type="button" class="btn btn-light border" id="btnNextYear" title="Tahun berikutnya"><i class="fas fa-chevron-right"></i></button>
                </div>
                <button type="button" class="btn btn-sm btn-primary text-nowrap" id="btnAddLibur">
                    <i class="fas fa-plus-circle mr-1"></i>Tambah Libur
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-2">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="tableLiburNasional">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Tanggal</th>
                            <th>Nama Libur</th>
                            <th>Karyawan Terjadwal</th>
                            <th style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="small text-muted mt-2">
                <i class="fas fa-info-circle mr-1"></i>Menambah, memindah, atau menghapus libur nasional otomatis menyesuaikan jatah ganti libur karyawan yang sudah terjadwal pada tanggal tersebut.
                Libur yang jatuh pada hari Minggu tidak dihitung dua kali.
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalLibur" tabindex="-1" role="dialog" aria-labelledby="modalLiburLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLiburLabel">Tambah Libur Nasional</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="formLibur" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="liburId">
                    <div class="form-group">
                        <label for="liburTanggal">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="tanggal" id="liburTanggal" required>
                    </div>
                    <div class="form-group mb-0">
                        <label for="liburNama">Nama Libur <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama" id="liburNama" maxlength="150" required placeholder="Contoh: Hari Kemerdekaan RI">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveLibur">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('hrd.pengajuan._scripts')
<script>
$(function () {
    var P = window.Pengajuan;
    var baseUrl = "{{ route('hrd.master.libur-nasional.index') }}";
    var tahun = {{ (int) $tahun }};
    var rows = [];
    var $tbody = $('#tableLiburNasional tbody'), $form = $('#formLibur');

    function load() {
        $('#yearLabel').text(tahun);
        $tbody.html('<tr><td colspan="5" class="text-center text-muted"><i class="fa fa-spinner fa-spin mr-1"></i>Memuat...</td></tr>');
        $.getJSON(baseUrl, { tahun: tahun })
            .done(function (res) {
                rows = (res && res.data) || [];
                if (!rows.length) {
                    $tbody.html('<tr><td colspan="5" class="text-center text-muted">Belum ada libur nasional untuk tahun ' + tahun + '.</td></tr>');
                    return;
                }
                $tbody.html($.map(rows, function (r, i) {
                    var terjadwal = r.is_sunday
                        ? '<span class="text-muted small">Hari Minggu (sudah dihitung)</span>'
                        : (r.terjadwal ? r.terjadwal + ' karyawan' : '<span class="text-muted">-</span>');
                    return '<tr>'
                        + '<td>' + (i + 1) + '</td>'
                        + '<td>' + P.escapeHtml(r.hari) + ', ' + P.escapeHtml(r.tanggal_label) + '</td>'
                        + '<td>' + P.escapeHtml(r.nama) + '</td>'
                        + '<td>' + terjadwal + '</td>'
                        + '<td class="text-nowrap"><div class="btn-group btn-group-sm">'
                        + '<button type="button" class="btn btn-warning btn-edit" data-id="' + r.id + '" title="Ubah"><i class="fas fa-edit"></i></button>'
                        + '<button type="button" class="btn btn-danger btn-delete" data-id="' + r.id + '" title="Hapus"><i class="fas fa-trash"></i></button>'
                        + '</div></td></tr>';
                }).join(''));
            })
            .fail(function (xhr) {
                $tbody.html('<tr><td colspan="5" class="text-center text-danger">Gagal memuat data.</td></tr>');
                P.showError(xhr);
            });
    }

    function findRow(id) { return $.grep(rows, function (r) { return r.id === id; })[0]; }

    $('#btnPrevYear').on('click', function () { tahun--; load(); });
    $('#btnNextYear').on('click', function () { tahun++; load(); });

    $('#btnAddLibur').on('click', function () {
        $form[0].reset();
        P.clearFieldErrors($form);
        $('#liburId').val('');
        $('#modalLiburLabel').text('Tambah Libur Nasional');
        $('#modalLibur').modal('show');
    });

    $(document).on('click', '.btn-edit', function () {
        var r = findRow($(this).data('id'));
        if (!r) return;
        P.clearFieldErrors($form);
        $('#liburId').val(r.id);
        $('#liburTanggal').val(r.tanggal);
        $('#liburNama').val(r.nama);
        $('#modalLiburLabel').text('Ubah Libur Nasional');
        $('#modalLibur').modal('show');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        P.clearFieldErrors($form);
        var ok = true;
        $.each([$('#liburTanggal'), $('#liburNama')], function (_, $i) {
            if (!$.trim($i.val())) { P.setFieldError($i, 'Wajib diisi.'); ok = false; }
        });
        if (!ok) return;

        var id = $('#liburId').val(), data = $form.serializeArray();
        if (id) data.push({ name: '_method', value: 'PUT' });
        var $btn = $('#btnSaveLibur');
        P.setBusy($btn, true);
        $.ajax({ url: id ? baseUrl + '/' + id : baseUrl, type: 'POST', data: $.param(data) })
            .done(function (res) {
                $('#modalLibur').modal('hide');
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message });
                // Jump to the year of the saved date so it is visible
                var y = parseInt(($('#liburTanggal').val() || '').substr(0, 4), 10);
                if (y) tahun = y;
                load();
            })
            .fail(function (xhr) {
                if (!P.applyServerErrors($form, xhr)) P.showError(xhr);
            })
            .always(function () { P.setBusy($btn, false); });
    });

    $(document).on('click', '.btn-delete', function () {
        var r = findRow($(this).data('id'));
        if (!r) return;
        var impact = (!r.is_sunday && r.terjadwal)
            ? '<br><br>Jatah ganti libur <b>' + r.terjadwal + ' karyawan</b> yang terjadwal pada tanggal ini akan dikurangi 1.'
            : '';
        Swal.fire({
            icon: 'warning',
            title: 'Hapus libur nasional?',
            html: P.escapeHtml(r.nama) + ' (' + P.escapeHtml(r.tanggal_label) + ')' + impact,
            showCancelButton: true,
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33'
        }).then(function (result) {
            if (!result.value) return;
            $.ajax({ url: baseUrl + '/' + r.id, type: 'POST', data: { _method: 'DELETE' } })
                .done(function (res) { P.toast(res.message); load(); })
                .fail(function (xhr) { P.showError(xhr); });
        });
    });

    load();
});
</script>
@endsection
