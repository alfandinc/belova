@extends('layouts.finance.app')
@section('title', 'Finance | Jurnal Umum')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center" style="gap:1rem;">
            <div>
                <h3 class="mb-0 font-weight-bold">Jurnal Umum</h3>
                <div class="text-muted small">Buat, edit, dan posting jurnal multi-baris dengan kontrol keseimbangan debit-kredit sebelum penyimpanan.</div>
            </div>
            <div class="d-flex flex-wrap justify-content-end" style="gap:.75rem;">
                <button type="button" class="btn btn-primary" id="btn-create-jurnal">
                    <i class="fas fa-plus mr-1"></i> Buat Jurnal
                </button>
                <div class="card shadow-sm border-0 mb-0" style="min-width:160px;">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted small">Entry Jurnal</div>
                        <div class="h5 mb-0 font-weight-bold">{{ number_format($summary['total_entries'], 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="card shadow-sm border-0 mb-0" style="min-width:160px;">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted small">Total Debit</div>
                        <div class="h5 mb-0 font-weight-bold text-success">Rp {{ number_format($summary['total_debet'], 2, ',', '.') }}</div>
                    </div>
                </div>
                <div class="card shadow-sm border-0 mb-0" style="min-width:160px;">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted small">Total Kredit</div>
                        <div class="h5 mb-0 font-weight-bold text-danger">Rp {{ number_format($summary['total_kredit'], 2, ',', '.') }}</div>
                    </div>
                </div>
                <div class="card shadow-sm border-0 mb-0" style="min-width:160px;">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted small">Draft Pos</div>
                        <div class="h5 mb-0 font-weight-bold text-warning">{{ number_format($summary['draft_rows'], 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-3">
            <div class="row align-items-end">
                <div class="col-md-2 mb-2">
                    <label class="small text-muted mb-1">Tanggal Awal</label>
                    <input type="date" id="filter-jurnal-start" class="form-control form-control-sm" value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small text-muted mb-1">Tanggal Akhir</label>
                    <input type="date" id="filter-jurnal-end" class="form-control form-control-sm" value="{{ now()->endOfMonth()->format('Y-m-d') }}">
                </div>
                <div class="col-md-4 mb-2">
                    <label class="small text-muted mb-1">Akun</label>
                    <select id="filter-jurnal-akun" class="form-control form-control-sm">
                        <option value="">Semua Akun</option>
                        @foreach($akunOptions as $akun)
                            <option value="{{ $akun->id }}">{{ $akun->kode_akun }} - {{ $akun->nama_akun }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small text-muted mb-1">Status</label>
                    <select id="filter-jurnal-pos" class="form-control form-control-sm">
                        <option value="">Semua Status</option>
                        <option value="D">Ada Debit</option>
                        <option value="K">Ada Kredit</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2 text-md-right">
                    <button type="button" id="reset-filter-jurnal" class="btn btn-light btn-sm border mt-4 mt-md-0">
                        <i class="fas fa-sync-alt mr-1"></i> Reset Filter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:.75rem;">
                <div>
                    <div class="font-weight-bold">Daftar entry jurnal</div>
                    <div class="text-muted small">Setiap nomor jurnal dapat berisi banyak akun. Sistem hanya menyimpan jurnal yang balance.</div>
                </div>
                <div class="badge badge-light border px-3 py-2 text-muted">Balance check aktif</div>
            </div>
            <div class="table-responsive">
                <table id="datatable-finance-jurnal" class="table table-bordered table-striped" style="width:100%;">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>No Jurnal</th>
                            <th>Ringkasan Akun</th>
                            <th>Debit</th>
                            <th>Kredit</th>
                            <th>Selisih</th>
                            <th>Status</th>
                            <th>User</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="jurnalModal" tabindex="-1" role="dialog" aria-labelledby="jurnalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content border-0 shadow">
            <form id="jurnalForm">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="jurnalModalLabel">Buat Jurnal Umum</h5>
                        <div class="text-muted small">Masukkan header jurnal dan susun beberapa baris akun seperti lembar jurnal Accurate.</div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body pt-3">
                    <div class="alert alert-danger" id="jurnalFormError" style="display:none;"></div>
                    <input type="hidden" id="jurnal-id" name="id">
                    <div class="card border-0 bg-light mb-3">
                        <div class="card-body py-3">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="small text-muted mb-1">No Jurnal</label>
                                    <input type="text" class="form-control" id="jurnal-no" name="no_jurnal" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="small text-muted mb-1">Tanggal</label>
                                    <input type="date" class="form-control" id="jurnal-tanggal" name="tanggal" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="small text-muted mb-1">Referensi</label>
                                    <input type="text" class="form-control" id="jurnal-ref" name="ref_id" placeholder="Opsional">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="small text-muted mb-1">Status Balance</label>
                                    <div class="form-control bg-white d-flex align-items-center justify-content-between">
                                        <span id="jurnal-balance-status" class="badge badge-warning">Draft</span>
                                        <strong id="jurnal-balance-diff">0,00</strong>
                                    </div>
                                </div>
                                <div class="col-12 mb-0">
                                    <label class="small text-muted mb-1">Keterangan</label>
                                    <textarea class="form-control" id="jurnal-keterangan" name="keterangan" rows="2" required placeholder="Contoh: Jurnal penyesuaian kas kecil"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-0">
                        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center" style="gap:.75rem;">
                            <div>
                                <div class="font-weight-bold">Baris jurnal</div>
                                <div class="text-muted small">Setiap baris wajib memiliki satu akun dan hanya salah satu kolom debit atau kredit.</div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-add-jurnal-line">
                                <i class="fas fa-plus mr-1"></i> Tambah Baris
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0" id="jurnal-lines-table">
                                    <thead class="bg-light">
                                        <tr>
                                            <th style="width: 50px;">No</th>
                                            <th style="min-width: 320px;">Akun</th>
                                            <th style="width: 180px;">Debit</th>
                                            <th style="width: 180px;">Kredit</th>
                                            <th style="width: 70px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                    <tfoot>
                                        <tr class="bg-light">
                                            <th colspan="2" class="text-right">Total</th>
                                            <th class="text-right text-success" id="jurnal-total-debet">0,00</th>
                                            <th class="text-right text-danger" id="jurnal-total-kredit">0,00</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-jurnal">Simpan Jurnal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="jurnalDetailModal" tabindex="-1" role="dialog" aria-labelledby="jurnalDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title" id="jurnalDetailModalLabel">Detail Jurnal</h5>
                    <div class="text-muted small" id="jurnal-detail-subtitle">-</div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body pt-3">
                <div class="row mb-3">
                    <div class="col-md-3 mb-2">
                        <div class="card bg-light border-0">
                            <div class="card-body py-2">
                                <div class="text-muted small">No Jurnal</div>
                                <div class="font-weight-bold" id="detail-no-jurnal">-</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="card bg-light border-0">
                            <div class="card-body py-2">
                                <div class="text-muted small">Tanggal</div>
                                <div class="font-weight-bold" id="detail-tanggal-jurnal">-</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="card bg-light border-0">
                            <div class="card-body py-2">
                                <div class="text-muted small">Referensi</div>
                                <div class="font-weight-bold" id="detail-ref-jurnal">-</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="card bg-light border-0">
                            <div class="card-body py-2">
                                <div class="text-muted small">User</div>
                                <div class="font-weight-bold" id="detail-user-jurnal">-</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Keterangan</div>
                    <div class="font-weight-bold" id="detail-keterangan-jurnal">-</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Akun</th>
                                <th>Debit</th>
                                <th>Kredit</th>
                                <th>Pos</th>
                            </tr>
                        </thead>
                        <tbody id="jurnal-detail-body"></tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <th colspan="2" class="text-right">Total</th>
                                <th class="text-right text-success" id="detail-total-debet">0,00</th>
                                <th class="text-right text-danger" id="detail-total-kredit">0,00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var akunOptions = @json($akunOptions->map(function ($akun) { return ['id' => $akun->id, 'label' => $akun->kode_akun . ' - ' . $akun->nama_akun]; })->values());
        var defaultStart = '{{ now()->startOfMonth()->format('Y-m-d') }}';
        var defaultEnd = '{{ now()->endOfMonth()->format('Y-m-d') }}';

        var jurnalTable = $('#datatable-finance-jurnal').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("finance.jurnal.data") }}',
                data: function (d) {
                    d.start_date = $('#filter-jurnal-start').val();
                    d.end_date = $('#filter-jurnal-end').val();
                    d.akun_id = $('#filter-jurnal-akun').val();
                    d.pos = $('#filter-jurnal-pos').val();
                }
            },
            order: [[1, 'desc']],
            columns: [
                { data: null, orderable: false, searchable: false, render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                { data: 'tanggal_display', name: 'tanggal' },
                { data: 'nomor_display', name: 'no_jurnal' },
                { data: 'akun_display', name: 'akun_summary', orderable: false },
                { data: 'debet_display', name: 'total_debet' },
                { data: 'kredit_display', name: 'total_kredit' },
                { data: 'balance_display', name: 'balance', orderable: false, searchable: false },
                { data: 'status_display', name: 'status', orderable: false, searchable: false },
                { data: 'user_display', name: 'user_name', orderable: false },
                { data: 'keterangan', name: 'keterangan', defaultContent: '-' },
                { data: 'actions_display', orderable: false, searchable: false }
            ]
        });

        function buildUrl(template, id) {
            return template.replace(':id', id);
        }

        function formatCurrency(value) {
            return Number(value || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function generateJournalNumber() {
            var now = new Date();
            var pad = function (value) { return String(value).padStart(2, '0'); };
            return 'JU-' + now.getFullYear() + pad(now.getMonth() + 1) + pad(now.getDate()) + '-' + pad(now.getHours()) + pad(now.getMinutes()) + pad(now.getSeconds());
        }

        function lineOptionHtml(selectedId) {
            var html = '<option value="">Pilih akun</option>';
            akunOptions.forEach(function (item) {
                html += '<option value="' + item.id + '"' + (String(selectedId || '') === String(item.id) ? ' selected' : '') + '>' + item.label + '</option>';
            });
            return html;
        }

        function buildLineRow(line) {
            line = line || {};
            return [
                '<tr>',
                    '<td class="align-middle text-center line-number"></td>',
                    '<td><select class="form-control jurnal-akun-select" name="lines[][akun_id]">' + lineOptionHtml(line.akun_id) + '</select></td>',
                    '<td><input type="number" min="0" step="0.01" class="form-control text-right jurnal-debet-input" name="lines[][debet]" value="' + (line.debet || '') + '" placeholder="0.00"></td>',
                    '<td><input type="number" min="0" step="0.01" class="form-control text-right jurnal-kredit-input" name="lines[][kredit]" value="' + (line.kredit || '') + '" placeholder="0.00"></td>',
                    '<td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm btn-remove-line"><i class="fas fa-times"></i></button></td>',
                '</tr>'
            ].join('');
        }

        function renumberLines() {
            $('#jurnal-lines-table tbody tr').each(function (index) {
                $(this).find('.line-number').text(index + 1);
                $(this).find('.jurnal-akun-select').attr('name', 'lines[' + index + '][akun_id]');
                $(this).find('.jurnal-debet-input').attr('name', 'lines[' + index + '][debet]');
                $(this).find('.jurnal-kredit-input').attr('name', 'lines[' + index + '][kredit]');
            });
        }

        function updateBalanceSummary() {
            var totalDebet = 0;
            var totalKredit = 0;

            $('#jurnal-lines-table tbody tr').each(function () {
                totalDebet += parseFloat($(this).find('.jurnal-debet-input').val() || 0);
                totalKredit += parseFloat($(this).find('.jurnal-kredit-input').val() || 0);
            });

            $('#jurnal-total-debet').text(formatCurrency(totalDebet));
            $('#jurnal-total-kredit').text(formatCurrency(totalKredit));

            var diff = totalDebet - totalKredit;
            $('#jurnal-balance-diff').text(formatCurrency(diff));

            var $badge = $('#jurnal-balance-status');
            $badge.removeClass('badge-warning badge-success badge-danger');

            if ($('#jurnal-lines-table tbody tr').length < 2 || (totalDebet === 0 && totalKredit === 0)) {
                $badge.addClass('badge-warning').text('Draft');
                return;
            }

            if (Math.abs(diff) < 0.005) {
                $badge.addClass('badge-success').text('Balanced');
                return;
            }

            $badge.addClass('badge-danger').text('Unbalanced');
        }

        function resetJurnalForm() {
            $('#jurnalForm')[0].reset();
            $('#jurnal-id').val('');
            $('#jurnal-no').val(generateJournalNumber());
            $('#jurnal-tanggal').val(defaultEnd);
            $('#jurnal-lines-table tbody').empty();
            $('#jurnalFormError').hide().empty();
            $('#jurnalModalLabel').text('Buat Jurnal Umum');
            $('#btn-save-jurnal').text('Simpan Jurnal');
            $('#btn-add-jurnal-line').trigger('click');
            $('#btn-add-jurnal-line').trigger('click');
            updateBalanceSummary();
        }

        function showFormErrors(xhr) {
            var message = 'Terjadi kesalahan pada server.';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                var items = [];
                $.each(xhr.responseJSON.errors, function (key, value) {
                    items.push(Array.isArray(value) ? value.join('<br>') : value);
                });
                message = items.join('<br>');
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            $('#jurnalFormError').html(message).show();
        }

        $('#filter-jurnal-start, #filter-jurnal-end, #filter-jurnal-akun, #filter-jurnal-pos').on('change', function () {
            jurnalTable.ajax.reload();
        });

        $('#reset-filter-jurnal').on('click', function () {
            $('#filter-jurnal-start').val(defaultStart);
            $('#filter-jurnal-end').val(defaultEnd);
            $('#filter-jurnal-akun').val('');
            $('#filter-jurnal-pos').val('');
            jurnalTable.ajax.reload();
        });

        $('#btn-add-jurnal-line').on('click', function () {
            $('#jurnal-lines-table tbody').append(buildLineRow());
            renumberLines();
            updateBalanceSummary();
        });

        $('#btn-create-jurnal').on('click', function () {
            resetJurnalForm();
            $('#jurnalModal').modal('show');
        });

        $(document).on('click', '.btn-remove-line', function () {
            if ($('#jurnal-lines-table tbody tr').length <= 2) {
                Swal.fire('Validasi', 'Jurnal minimal memiliki dua baris.', 'warning');
                return;
            }
            $(this).closest('tr').remove();
            renumberLines();
            updateBalanceSummary();
        });

        $(document).on('input', '.jurnal-debet-input, .jurnal-kredit-input', updateBalanceSummary);

        $(document).on('click', '.btn-edit-jurnal', function () {
            var id = $(this).data('id');
            resetJurnalForm();

            $.get(buildUrl('{{ route("finance.jurnal.show", ":id") }}', id))
                .done(function (response) {
                    var data = response.data || {};
                    $('#jurnal-id').val(id);
                    $('#jurnal-no').val(data.no_jurnal || '');
                    $('#jurnal-tanggal').val(data.tanggal || defaultEnd);
                    $('#jurnal-ref').val(data.ref_id || '');
                    $('#jurnal-keterangan').val(data.keterangan || '');
                    $('#jurnal-lines-table tbody').empty();

                    (data.lines || []).forEach(function (line) {
                        $('#jurnal-lines-table tbody').append(buildLineRow(line));
                    });

                    renumberLines();
                    updateBalanceSummary();
                    $('#jurnalModalLabel').text('Edit Jurnal Umum');
                    $('#btn-save-jurnal').text('Update Jurnal');
                    $('#jurnalModal').modal('show');
                })
                .fail(function () {
                    Swal.fire('Error', 'Gagal memuat detail jurnal.', 'error');
                });
        });

        $(document).on('click', '.btn-view-jurnal', function () {
            var id = $(this).data('id');

            $.get(buildUrl('{{ route("finance.jurnal.show", ":id") }}', id))
                .done(function (response) {
                    var data = response.data || {};
                    $('#detail-no-jurnal').text(data.no_jurnal || '-');
                    $('#detail-tanggal-jurnal').text(data.tanggal || '-');
                    $('#detail-ref-jurnal').text(data.ref_id || '-');
                    $('#detail-user-jurnal').text(data.user_name || '-');
                    $('#detail-keterangan-jurnal').text(data.keterangan || '-');
                    $('#jurnal-detail-subtitle').text('Rincian baris jurnal dan posisi debit-kredit.');

                    var rows = (data.lines || []).map(function (line, index) {
                        return '<tr>' +
                            '<td>' + (index + 1) + '</td>' +
                            '<td>' + (line.akun_label || '-') + '</td>' +
                            '<td class="text-right text-success">' + formatCurrency(line.debet || 0) + '</td>' +
                            '<td class="text-right text-danger">' + formatCurrency(line.kredit || 0) + '</td>' +
                            '<td>' + (line.pos === 'D' ? 'Debit' : 'Kredit') + '</td>' +
                        '</tr>';
                    }).join('');

                    $('#jurnal-detail-body').html(rows || '<tr><td colspan="5" class="text-center text-muted">Tidak ada detail jurnal.</td></tr>');
                    $('#detail-total-debet').text(formatCurrency((data.totals || {}).debet || 0));
                    $('#detail-total-kredit').text(formatCurrency((data.totals || {}).kredit || 0));
                    $('#jurnalDetailModal').modal('show');
                })
                .fail(function () {
                    Swal.fire('Error', 'Gagal memuat detail jurnal.', 'error');
                });
        });

        $('#jurnalForm').on('submit', function (e) {
            e.preventDefault();

            var id = $('#jurnal-id').val();
            var url = id ? buildUrl('{{ route("finance.jurnal.update", ":id") }}', id) : '{{ route("finance.jurnal.store") }}';
            var payload = $(this).serializeArray();

            if (id) {
                payload.push({ name: '_method', value: 'PUT' });
            }

            $('#jurnalFormError').hide().empty();
            $('#btn-save-jurnal').prop('disabled', true);

            $.ajax({
                url: url,
                method: 'POST',
                data: $.param(payload)
            }).done(function (response) {
                $('#jurnalModal').modal('hide');
                Swal.fire('Sukses', response.message || 'Jurnal berhasil disimpan.', 'success');
                jurnalTable.ajax.reload(null, false);
            }).fail(function (xhr) {
                showFormErrors(xhr);
            }).always(function () {
                $('#btn-save-jurnal').prop('disabled', false);
            });
        });

        $(document).on('click', '.btn-delete-jurnal', function () {
            var id = $(this).data('id');
            var label = $(this).data('label');

            Swal.fire({
                title: 'Hapus jurnal?',
                text: label,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: buildUrl('{{ route("finance.jurnal.destroy", ":id") }}', id),
                    method: 'POST',
                    data: { _method: 'DELETE', _token: '{{ csrf_token() }}' }
                }).done(function (response) {
                    Swal.fire('Terhapus', response.message || 'Jurnal berhasil dihapus.', 'success');
                    jurnalTable.ajax.reload(null, false);
                }).fail(function (xhr) {
                    var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Gagal menghapus jurnal.';
                    Swal.fire('Error', message, 'error');
                });
            });
        });
    });
</script>
@endsection
