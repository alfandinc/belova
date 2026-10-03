@extends('layouts.erm.app')

@section('title', 'Events')

@section('navbar')
    @include('layouts.erm.navbar-ngaji')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mt-3 mb-2">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="mb-0 font-weight-bold">Events</h3>
                <div class="text-muted small">Kelola event, lihat pasien yang datang ke event, dan proses billing event.</div>
            </div>
            <button id="btn-add-event" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> Tambah Event</button>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="events-table" class="table table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Event</th>
                                <th>Periode</th>
                                <th>Klinik / Lokasi</th>
                                <th>Promo</th>
                                <th>Status</th>
                                <th>Pasien</th>
                                <th>Dokumen</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="eventModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalTitle">Tambah Event</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="event-form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="event-id" name="id">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Kode Event</label>
                            <input type="text" class="form-control" id="kode_event" name="kode_event" required>
                        </div>
                        <div class="form-group col-md-8">
                            <label>Nama Event</label>
                            <input type="text" class="form-control" id="nama_event" name="nama_event" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi Event</label>
                        <textarea class="form-control" id="deskripsi_event" name="deskripsi_event" rows="3"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Tanggal Mulai</label>
                            <input type="datetime-local" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Tanggal Selesai</label>
                            <input type="datetime-local" class="form-control" id="tanggal_selesai" name="tanggal_selesai">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Klinik</label>
                            <select class="form-control" id="klinik_id" name="klinik_id" required>
                                <option value="">Pilih Klinik</option>
                                @foreach($kliniks as $klinik)
                                    <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Lokasi</label>
                            <input type="text" class="form-control" id="lokasi" name="lokasi">
                        </div>
                        <div class="form-group col-md-2">
                            <label>Target Market</label>
                            <input type="text" class="form-control" id="target_market" name="target_market">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Status</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="aktif">Aktif</option>
                                <option value="selesai">Selesai</option>
                            </select>
                        </div>
                        <div class="form-group col-md-8">
                            <label>Promo Terkait</label>
                            <select class="form-control" id="promo_ids" name="promo_ids[]" multiple>
                                @foreach($promos as $promo)
                                    <option value="{{ $promo->id }}">{{ $promo->name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Billing event hanya bisa menjual item dari promo yang dipilih di sini.</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Dokumen Proposal</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input event-file-input" id="dokumen_proposal" name="dokumen_proposal" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                <label class="custom-file-label" for="dokumen_proposal">Pilih file</label>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Dokumen Laporan</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input event-file-input" id="dokumen_laporan" name="dokumen_laporan" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                <label class="custom-file-label" for="dokumen_laporan">Pilih file</label>
                            </div>
                        </div>
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

@section('scripts')
<script>
    $(function () {
        const csrfToken = '{{ csrf_token() }}';
        const baseUrl = '{{ url('events') }}';
        const storageUrl = '{{ asset('storage') }}';

        $('#promo_ids').select2({
            width: '100%',
            placeholder: 'Pilih promo terkait',
            closeOnSelect: false,
            dropdownParent: $('#eventModal')
        });

        function esc(value) {
            return $('<div>').text(value == null ? '' : String(value)).html();
        }

        function formatDateTime(value) {
            if (!value) return '-';
            const date = new Date(String(value).replace(' ', 'T'));
            if (Number.isNaN(date.getTime())) return esc(value);
            return date.toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        }

        function toDatetimeLocal(value) {
            return value ? String(value).replace(' ', 'T').slice(0, 16) : '';
        }

        function resetForm() {
            $('#event-form')[0].reset();
            $('#event-id').val('');
            $('#promo_ids').val([]).trigger('change');
            $('.custom-file-label').text('Pilih file');
        }

        const table = $('#events-table').DataTable({
            ajax: { url: `${baseUrl}/data` },
            order: [[2, 'desc']],
            columns: [
                { data: null, orderable: false, searchable: false, render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                { data: 'nama_event', render: function (data, type, row) {
                    if (type !== 'display') return (row.nama_event || '') + ' ' + (row.kode_event || '');
                    const desc = row.deskripsi_event ? `<div class="text-muted small mt-1">${esc(row.deskripsi_event)}</div>` : '';
                    return `<a href="${baseUrl}/${row.id}" class="font-weight-bold">${esc(row.nama_event || '-')}</a><div class="small text-muted">${esc(row.kode_event || '')}</div>${desc}`;
                } },
                { data: 'tanggal_mulai', render: function (data, type, row) {
                    if (type !== 'display') return data || '';
                    return `<div>${formatDateTime(row.tanggal_mulai)}</div><div class="text-muted small">s/d ${formatDateTime(row.tanggal_selesai)}</div>`;
                } },
                { data: null, render: function (data, type, row) {
                    const klinik = row.klinik && row.klinik.nama ? row.klinik.nama : '-';
                    return `<div>${esc(klinik)}</div>` + (row.lokasi ? `<div class="text-muted small">${esc(row.lokasi)}</div>` : '');
                } },
                { data: 'promos', orderable: false, render: function (data) {
                    if (!Array.isArray(data) || !data.length) return '<span class="text-muted">-</span>';
                    return data.map(function (promo) { return `<span class="badge badge-info mr-1 mb-1">${esc(promo.name || '-')}</span>`; }).join('');
                } },
                { data: 'status', render: function (data, type) {
                    const selesai = String(data || '').toLowerCase() === 'selesai';
                    if (type !== 'display') return selesai ? 'Selesai' : 'Aktif';
                    return selesai ? '<span class="badge badge-secondary">Selesai</span>' : '<span class="badge badge-success">Aktif</span>';
                } },
                { data: 'patients_count', className: 'text-center', render: function (data, type) {
                    const n = Number(data || 0);
                    return type === 'display' ? `<span class="font-weight-bold">${n}</span>` : n;
                } },
                { data: null, orderable: false, searchable: false, render: function (data, type, row) {
                    const links = [];
                    if (row.dokumen_proposal) links.push(`<a class="btn btn-sm btn-outline-primary mr-1 mb-1" href="${storageUrl}/${esc(row.dokumen_proposal)}" target="_blank">Proposal</a>`);
                    if (row.dokumen_laporan) links.push(`<a class="btn btn-sm btn-outline-success mb-1" href="${storageUrl}/${esc(row.dokumen_laporan)}" target="_blank">Laporan</a>`);
                    return links.length ? links.join('') : '<span class="text-muted">-</span>';
                } },
                { data: null, orderable: false, searchable: false, className: 'text-nowrap', render: function (data, type, row) {
                    return `<div class="btn-group" role="group">`
                        + `<a href="${baseUrl}/${row.id}" class="btn btn-sm btn-primary" title="Lihat event: pasien & billing"><i class="fas fa-eye mr-1"></i>View</a>`
                        + `<button class="btn btn-sm btn-info btn-edit" data-id="${row.id}" title="Edit"><i class="fas fa-pen"></i></button>`
                        + `<button class="btn btn-sm btn-danger btn-delete" data-id="${row.id}" title="Hapus"><i class="fas fa-trash"></i></button>`
                        + `</div>`;
                } }
            ]
        });

        $('#btn-add-event').on('click', function () {
            resetForm();
            $('#eventModalTitle').text('Tambah Event');
            $('#eventModal').modal('show');
        });

        $(document).on('change', '.event-file-input', function () {
            const fileName = ($(this).val() || '').split('\\').pop();
            $(this).next('.custom-file-label').text(fileName || 'Pilih file');
        });

        $('#events-table').on('click', '.btn-edit', function () {
            const id = $(this).data('id');
            resetForm();
            $.get(`${baseUrl}/${id}/edit-data`, function (response) {
                const item = response.item;
                $('#event-id').val(item.id);
                $('#kode_event').val(item.kode_event);
                $('#nama_event').val(item.nama_event);
                $('#deskripsi_event').val(item.deskripsi_event);
                $('#tanggal_mulai').val(toDatetimeLocal(item.tanggal_mulai));
                $('#tanggal_selesai').val(toDatetimeLocal(item.tanggal_selesai));
                $('#klinik_id').val(item.klinik_id);
                $('#lokasi').val(item.lokasi);
                $('#target_market').val(item.target_market);
                $('#status').val(item.status);
                $('#promo_ids').val((item.promos || []).map(function (promo) { return String(promo.id); })).trigger('change');
                $('#eventModalTitle').text('Edit Event');
                $('#eventModal').modal('show');
            });
        });

        $('#event-form').on('submit', function (e) {
            e.preventDefault();
            const id = $('#event-id').val();
            const formData = new FormData(this);
            formData.append('_token', csrfToken);
            if (id) formData.append('_method', 'PUT');

            $.ajax({
                url: id ? `${baseUrl}/${id}` : baseUrl,
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function () {
                    $('#eventModal').modal('hide');
                    table.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Data event disimpan', timer: 1200, showConfirmButton: false });
                },
                error: function (xhr) {
                    const errors = xhr.responseJSON && xhr.responseJSON.errors;
                    const text = errors ? Object.values(errors).flat().join('\n') : ((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan event');
                    Swal.fire({ icon: 'error', title: 'Error', text: text });
                }
            });
        });

        $('#events-table').on('click', '.btn-delete', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Hapus event?',
                text: 'Data yang dihapus tidak bisa dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (!(result.isConfirmed || result.value)) return;
                $.ajax({
                    url: `${baseUrl}/${id}`,
                    method: 'POST',
                    data: { _token: csrfToken, _method: 'DELETE' },
                    success: function () {
                        table.ajax.reload(null, false);
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Event dihapus', timer: 1200, showConfirmButton: false });
                    },
                    error: function (xhr) {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus event' });
                    }
                });
            });
        });
    });
</script>
@endsection
