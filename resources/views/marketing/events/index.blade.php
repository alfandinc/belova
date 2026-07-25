@extends('layouts.marketing.app')

@section('title', 'Event Marketing')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-1">Event Marketing</h4>
                            <p class="text-muted mb-0">Kelola agenda event marketing, proposal, dan laporan dalam satu tabel.</p>
                        </div>
                        <button id="btn-add-event" class="btn btn-primary">Tambah Event</button>
                    </div>

                    <table id="marketing-event-table" class="table table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode Event</th>
                                <th>Nama Event</th>
                                <th>Periode</th>
                                <th>Klinik</th>
                                <th>Lokasi</th>
                                <th>Target Market</th>
                                <th>Promo</th>
                                <th>Status</th>
                                <th>Dokumen</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="marketingEventModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Form Event Marketing</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="marketing-event-form" enctype="multipart/form-data">
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
                        <div class="form-group col-md-4">
                            <label>Promo Terkait</label>
                            <select class="form-control select2" id="promo_ids" name="promo_ids[]" multiple>
                                @foreach($promos as $promo)
                                    <option value="{{ $promo->id }}">{{ $promo->name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Billing event hanya bisa menjual item dari promo yang dipilih di sini.</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Dokumen Proposal</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input event-file-input" id="dokumen_proposal" name="dokumen_proposal" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                <label class="custom-file-label" for="dokumen_proposal">Pilih file</label>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Dokumen Laporan</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input event-file-input" id="dokumen_laporan" name="dokumen_laporan" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                <label class="custom-file-label" for="dokumen_laporan">Pilih file</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
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
        const baseUrl = '{!! url('marketing/events') !!}';

        $('#promo_ids').select2({
            width: '100%',
            placeholder: 'Pilih promo terkait',
            closeOnSelect: false,
            dropdownParent: $('#marketingEventModal')
        });

        function formatDateTime(value) {
            if (!value) {
                return '-';
            }

            const date = new Date(value.replace(' ', 'T'));
            if (Number.isNaN(date.getTime())) {
                return value;
            }

            return date.toLocaleString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        }

        function toDatetimeLocal(value) {
            if (!value) {
                return '';
            }

            return value.replace(' ', 'T').slice(0, 16);
        }

        function badgeStatus(status) {
            const normalized = (status || '').toLowerCase();
            const badgeClass = normalized === 'selesai' ? 'badge-success' : 'badge-warning';
            const label = normalized === 'selesai' ? 'Selesai' : 'Aktif';

            return `<span class="badge ${badgeClass}">${label}</span>`;
        }

        function documentLinks(row) {
            const links = [];

            if (row.dokumen_proposal) {
                links.push(`<a class="btn btn-sm btn-outline-primary mr-1 mb-1" href="{{ asset('storage') }}/${row.dokumen_proposal}" target="_blank">Proposal</a>`);
            }

            if (row.dokumen_laporan) {
                links.push(`<a class="btn btn-sm btn-outline-success mb-1" href="{{ asset('storage') }}/${row.dokumen_laporan}" target="_blank">Laporan</a>`);
            }

            return links.length ? links.join('') : '<span class="text-muted">-</span>';
        }

        function resetForm() {
            $('#marketing-event-form')[0].reset();
            $('#event-id').val('');
            $('#promo_ids').val([]).trigger('change');
            $('.custom-file-label').text('Pilih file');
        }

        function renderPromoBadges(promos) {
            if (!Array.isArray(promos) || !promos.length) {
                return '<span class="text-muted">-</span>';
            }

            return promos.map(function (promo) {
                const label = $('<div>').text(promo.name || '-').html();
                return `<span class="badge badge-info mr-1 mb-1">${label}</span>`;
            }).join('');
        }

        const table = $('#marketing-event-table').DataTable({
            ajax: {
                url: `${baseUrl}/data`,
            },
            order: [[3, 'desc']],
            columns: [
                {
                    data: null,
                    render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { data: 'kode_event' },
                {
                    data: null,
                    render: function (data, type, row) {
                        const title = row.nama_event || '-';
                        const desc = row.deskripsi_event ? `<div class="text-muted small mt-1">${$('<div>').text(row.deskripsi_event).html()}</div>` : '';
                        return `<div><strong>${$('<div>').text(title).html()}</strong>${desc}</div>`;
                    }
                },
                {
                    data: null,
                    render: function (data, type, row) {
                        return `<div>${formatDateTime(row.tanggal_mulai)}</div><div class="text-muted small">s/d ${formatDateTime(row.tanggal_selesai)}</div>`;
                    }
                },
                {
                    data: null,
                    render: function (data, type, row) {
                        return row.klinik && row.klinik.nama ? row.klinik.nama : '-';
                    }
                },
                { data: 'lokasi', defaultContent: '-' },
                { data: 'target_market', defaultContent: '-' },
                {
                    data: 'promos',
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        return renderPromoBadges(data || []);
                    }
                },
                {
                    data: 'status',
                    render: function (data) {
                        return badgeStatus(data);
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        return documentLinks(row);
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        return `<div class="btn-group" role="group"><button class="btn btn-sm btn-info btn-edit" data-id="${row.id}">Edit</button><button class="btn btn-sm btn-danger btn-delete" data-id="${row.id}">Delete</button></div>`;
                    }
                }
            ]
        });

        $('#btn-add-event').on('click', function () {
            resetForm();
            $('#marketingEventModal').modal('show');
        });

        $(document).on('change', '.event-file-input', function () {
            const fileName = ($(this).val() || '').split('\\').pop();
            $(this).next('.custom-file-label').text(fileName || 'Pilih file');
        });

        $('#marketing-event-table').on('click', '.btn-edit', function () {
            const id = $(this).data('id');

            resetForm();

            $.get(`${baseUrl}/${id}`, function (response) {
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
                $('#marketingEventModal').modal('show');
            });
        });

        $('#marketing-event-form').on('submit', function (e) {
            e.preventDefault();

            const id = $('#event-id').val();
            const formData = new FormData(this);
            formData.append('_token', csrfToken);

            if (id) {
                formData.append('_method', 'PUT');
            }

            $.ajax({
                url: id ? `${baseUrl}/${id}` : baseUrl,
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function () {
                    $('#marketingEventModal').modal('hide');
                    table.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Data event disimpan', timer: 1200, showConfirmButton: false });
                },
                error: function (xhr) {
                    Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Validation failed' });
                }
            });
        });

        $('#marketing-event-table').on('click', '.btn-delete', function () {
            const id = $(this).data('id');

            Swal.fire({
                title: 'Hapus event?',
                text: 'Data yang dihapus tidak bisa dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: `${baseUrl}/${id}`,
                    method: 'POST',
                    data: {
                        _token: csrfToken,
                        _method: 'DELETE'
                    },
                    success: function () {
                        table.ajax.reload(null, false);
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Data event dihapus', timer: 1200, showConfirmButton: false });
                    },
                    error: function () {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal menghapus data' });
                    }
                });
            });
        });
    });
</script>
@endsection
