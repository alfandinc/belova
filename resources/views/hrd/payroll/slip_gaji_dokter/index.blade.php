@extends('layouts.hrd.app')
@section('title', 'Slip Gaji Dokter')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h4 class="card-title mb-0">Slip Gaji Dokter</h4>
                <div class="text-muted small">
                    @if($isCeoView)
                        Tinjau slip gaji dokter yang diajukan HRD, lalu Approve atau Reject.
                    @else
                        Alur: Draft &rarr; Submit ke CEO &rarr; Approved / Rejected &rarr; Paid.
                    @endif
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center mt-2">
                <div class="mr-3 text-right">
                    <div class="text-muted small mb-0">Total Beban Gaji</div>
                    <div class="font-weight-bold" id="slipDokterTotalBeban">Rp 0,00</div>
                </div>
                <input type="month" id="filterBulan" class="form-control mr-2" style="width:180px;" value="{{ $bulan }}">
                <select id="filterStatus" class="form-control mr-2" style="width:150px;">
                    <option value="">Semua Status</option>
                    <option value="draft">Draft</option>
                    <option value="submitted" {{ $isCeoView ? 'selected' : '' }}>Submitted</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="paid">Paid</option>
                </select>
                <button class="btn btn-outline-success mr-2" id="btnExportSlip" title="Export sesuai filter"><i class="fa fa-file-excel"></i> Export</button>
                @if($canManage)
                    <button class="btn btn-info mr-2" id="btnBulkSubmit" title="Submit semua slip Draft/Rejected bulan ini ke CEO">Submit Semua</button>
                    <button class="btn btn-success mr-2" id="btnBuatSlip">Buat Slip</button>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="slipGajiDokterTable" class="table table-bordered table-striped" style="width:100%">
                        <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Dokter</th>
                                        <th>Bulan</th>
                                        <th>Total Pendapatan</th>
                                        <th>Total Potongan</th>
                                        <th>Total Gaji</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(function(){
        function escapeHtml(value) {
            return String(value === null || value === undefined ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function formatRupiah(value) {
            var num = parseFloat(value);
            if (isNaN(num)) num = 0;
            return 'Rp ' + num.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function normalizeStatus(status) {
            return String(status || 'draft').toLowerCase().trim();
        }

        var STATUS_BADGE = {
            draft: ['secondary', 'Draft'],
            submitted: ['info', 'Submitted'],
            approved: ['warning', 'Approved'],
            rejected: ['danger', 'Rejected'],
            paid: ['success', 'Paid']
        };

        function renderStatusBadge(status) {
            var b = STATUS_BADGE[normalizeStatus(status)] || ['secondary', escapeHtml(status)];
            return '<span class="badge badge-' + b[0] + '">' + b[1] + '</span>';
        }

        // Buttons for the status transitions the server says this user may do
        var TRANSITION_BUTTON = {
            submitted: { cls: 'btn-info', icon: 'fa-paper-plane', label: 'Submit', confirm: 'Submit slip ini ke CEO untuk di-approve?' },
            approved: { cls: 'btn-success', icon: 'fa-check', label: 'Approve', confirm: 'Approve slip gaji ini?' },
            rejected: { cls: 'btn-danger', icon: 'fa-times', label: 'Reject', confirm: 'Reject slip ini agar HRD bisa merevisi?' },
            paid: { cls: 'btn-success', icon: 'fa-coins', label: 'Pay', confirm: 'Tandai slip ini sebagai Paid? Slip Paid akan tampil di My Payroll dokter.' },
            draft: { cls: 'btn-outline-danger', icon: 'fa-undo', label: 'Unpaid', confirm: 'Batalkan status Paid? Slip kembali ke Draft dan harus disubmit & di-approve ulang.' }
        };

        function renderTrend(current, previous) {
            if (previous === null || previous === undefined) return '';
            var diff = parseFloat(current || 0) - parseFloat(previous || 0);
            if (diff > 0) return '<div class="small text-success">&uarr; ' + formatRupiah(diff) + ' vs bln lalu</div>';
            if (diff < 0) return '<div class="small text-danger">&darr; ' + formatRupiah(Math.abs(diff)) + ' vs bln lalu</div>';
            return '<div class="small text-muted">Sama dgn bln lalu</div>';
        }

        // Pick the most useful message from a failed AJAX response (incl. Laravel 422 validation errors)
        function ajaxErrorMessage(xhr, fallback) {
            var json = xhr && xhr.responseJSON;
            if (json && json.errors) {
                var firstKey = Object.keys(json.errors)[0];
                if (firstKey && json.errors[firstKey] && json.errors[firstKey][0]) return json.errors[firstKey][0];
            }
            if (json && json.message) return json.message;
            return fallback;
        }

        const table = $('#slipGajiDokterTable').DataTable({
            ajax: {
                url: '{{ route("hrd.payroll.slip_gaji_dokter.data") }}',
                data: function(d){ d.bulan = $('#filterBulan').val(); d.status = $('#filterStatus').val(); }
            },
            columns: [
                // row number
                { data: null, orderable: false, render: function(data, type, row, meta){ return meta.row + meta.settings._iDisplayStart + 1; } },
                { data: null, render: function(data, type){
                    // display dokter's user name if available
                    var name = '-';
                    if (data.dokter && data.dokter.user && data.dokter.user.name) name = data.dokter.user.name;
                    else if (data.dokter && data.dokter.id) name = 'Dokter ' + data.dokter.id;
                    return type === 'display' ? escapeHtml(name) : name;
                }, defaultContent: '-' },
                { data: 'bulan' },
                { data: 'total_pendapatan', className: 'text-right text-nowrap', render: function(d, type){ return type === 'display' ? formatRupiah(d) : d; } },
                { data: 'total_potongan', className: 'text-right text-nowrap', render: function(d, type){ return type === 'display' ? formatRupiah(d) : d; } },
                { data: 'total_gaji', className: 'text-right text-nowrap', render: function(d, type, row){
                    if (type !== 'display') return d;
                    return '<div class="font-weight-bold">' + formatRupiah(d) + '</div>' + renderTrend(d, row.last_month_total_gaji);
                } },
                { data: 'status_gaji', render: function(d, type){ return type === 'display' ? renderStatusBadge(d) : d; } },
                { data: null, orderable: false, className: 'text-nowrap', render: function(data){
                    var html = '';
                    (data.allowed_transitions || []).forEach(function(next){
                        var b = TRANSITION_BUTTON[next];
                        if (!b) return;
                        html += '<button class="btn btn-sm ' + b.cls + ' mr-1 btn-set-status" data-id="' + data.id + '" data-status="' + next + '">'
                            + '<i class="fa ' + b.icon + ' mr-1"></i>' + b.label + '</button>';
                    });
                    if (data.editable) {
                        html += '<button class="btn btn-sm btn-secondary mr-1 btn-edit" data-id="' + data.id + '">Edit</button>';
                    }
                    html += '<a href="{{ url('hrd/payroll/slip-gaji-dokter/print') }}/' + data.id + '" class="btn btn-sm btn-primary mr-1" target="_blank">Print</a>';
                    if (data.editable) {
                        html += '<button class="btn btn-sm btn-danger btn-delete" data-id="' + data.id + '">Delete</button>';
                    }
                    return html;
                }}
            ]
        });

        $('#slipGajiDokterTable').on('xhr.dt', function(e, settings, json){
            $('#slipDokterTotalBeban').text(formatRupiah(json && json.total_beban !== undefined ? json.total_beban : 0));
        });

        // Ensure CSRF token is present on all AJAX requests (for DELETE)
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

        $('#filterBulan, #filterStatus').on('change', function(){ table.ajax.reload(); });

        // Export current filter to Excel
        $('#btnExportSlip').on('click', function(){
            var params = $.param({ bulan: $('#filterBulan').val(), status: $('#filterStatus').val() });
            window.location.href = '{{ route('hrd.payroll.slip_gaji_dokter.export') }}?' + params;
        });

        // Status transitions (Submit / Approve / Reject / Pay / Unpaid)
        $(document).on('click', '.btn-set-status', function(){
            var id = $(this).data('id');
            var next = $(this).data('status');
            var b = TRANSITION_BUTTON[next] || { label: next, confirm: 'Ubah status slip?' };
            Swal.fire({
                title: b.label + ' Slip Gaji',
                text: b.confirm,
                icon: (next === 'rejected' || next === 'draft') ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, ' + b.label,
                cancelButtonText: 'Batal'
            }).then(function(result){
                if (!result.value) return;
                $.post('{{ url('hrd/payroll/slip-gaji-dokter/status') }}/' + id, { status_gaji: next })
                    .done(function(){
                        table.ajax.reload(null, false);
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Status slip diubah menjadi ' + (STATUS_BADGE[next] ? STATUS_BADGE[next][1] : next) + '.' });
                    })
                    .fail(function(xhr){
                        Swal.fire({ icon: 'error', title: 'Error', text: ajaxErrorMessage(xhr, 'Gagal mengubah status.') });
                    });
            });
        });

        // Submit every Draft/Rejected slip of the selected month
        $('#btnBulkSubmit').on('click', function(){
            var bulan = $('#filterBulan').val();
            if (!bulan) return;
            Swal.fire({
                title: 'Submit Semua?',
                text: 'Submit semua slip Draft/Rejected bulan ' + bulan + ' ke CEO?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Submit',
                cancelButtonText: 'Batal'
            }).then(function(result){
                if (!result.value) return;
                $.post('{{ route('hrd.payroll.slip_gaji_dokter.bulk_submit') }}', { bulan: bulan })
                    .done(function(res){
                        table.ajax.reload(null, false);
                        Swal.fire({ icon: 'success', title: 'Selesai', text: res.message });
                    })
                    .fail(function(xhr){
                        Swal.fire({ icon: 'error', title: 'Error', text: ajaxErrorMessage(xhr, 'Gagal submit slip.') });
                    });
            });
        });

        // Open create modal
        $('#btnBuatSlip').on('click', function(){
            const f = $('#createSlipForm')[0];
            if (f) f.reset();
            // Pre-fill bulan
            $('#createBulan').val($('#filterBulan').val());
            // reset tambahan container and add one empty row
            $('#create_tambahan_container').html('');
            addTambahanRow('', null);
            // clear any previously selected file in create form to avoid stale uploads
            $('#jasmed_file').val('');
            // pot pajak starts in auto (2.5%) mode
            $('#pot_pajak').data('manual', false);
            setFieldVisibility('', 0);
            $('#createSlipModal').modal('show');
        });

        // Adjust visible pendapatan fields based on dokter's klinik
        function setFieldVisibility(prefix, klinikId) {
            // prefix: '' for create, 'edit_' for edit
            const hideExtra = (parseInt(klinikId) === 2);
            const fields = ['peresepan_obat', 'rujuk_lab', 'pembuatan_konten'];
            fields.forEach(function(f){
                const selector = '#' + (prefix ? prefix : '') + f;
                const $el = $(selector);
                if ($el.length) {
                    if (hideExtra) {
                        $el.closest('.form-group').hide();
                        // clear value so it won't affect totals
                        $el.val(0);
                    } else {
                        $el.closest('.form-group').show();
                    }
                }
            });
        }

        // Fetch dokter klinik and apply visibility for create & edit
        function fetchAndApplyDokter(dokterId, prefix) {
            if (!dokterId) {
                // default: show all
                setFieldVisibility(prefix, 0);
                recalcTotalsFor(prefix);
                return;
            }
            $.get(`{{ url('hrd/payroll/slip-gaji-dokter/dokter') }}/${dokterId}`)
                .done(function(res){
                    const klinikId = res && res.data ? res.data.klinik_id : null;
                    setFieldVisibility(prefix, klinikId);
                }).fail(function(){
                    // on error, default to show all
                    setFieldVisibility(prefix, 0);
                }).always(function(){
                    // hidden fields may have been zeroed -> refresh totals
                    recalcTotalsFor(prefix);
                });
        }

        // Wire change handlers for create & edit dokter selects
        $(document).on('change', '#create_dokter_id', function(){
            fetchAndApplyDokter($(this).val(), '');
        });
        $(document).on('change', '#edit_dokter_id', function(){
            fetchAndApplyDokter($(this).val(), 'edit_');
        });

        // helper: parse float safe
        function parseNum(v) {
            v = v === undefined || v === null || v === '' ? 0 : v;
            v = typeof v === 'string' ? v.replace(/,/g, '') : v;
            const n = parseFloat(v);
            return isNaN(n) ? 0 : n;
        }

        // ---------- Pendapatan Tambahan helpers ----------
        function renderTambahanRow(prefix, index, label = '', amount = 0) {
            // prefix used only for element IDs, names must remain pendapatan_tambahan[...] so server receives array
            const nameLabel = `pendapatan_tambahan[${index}][label]`;
            const nameAmt = `pendapatan_tambahan[${index}][amount]`;
            const idLabel = (prefix ? prefix : '') + `tambahan_label_${index}`;
            const idAmt = (prefix ? prefix : '') + `tambahan_amount_${index}`;
            const amtClass = prefix === 'edit_' ? 'form-control tambahan-amount calc-input-edit' : 'form-control tambahan-amount calc-input';
            return `
                <div class="form-row tambahan-row" data-index="${index}" style="display:flex; gap:8px; margin-bottom:6px;">
                    <input type="text" name="${nameLabel}" id="${idLabel}" class="form-control tambahan-label" placeholder="Komponen (contoh: attending event)" value="${escapeHtml(label)}">
                    <input type="number" step="0.01" name="${nameAmt}" id="${idAmt}" class="${amtClass}" placeholder="Nominal" value="${parseFloat(amount || 0).toFixed(2)}" style="width:160px;">
                    <button type="button" class="btn btn-sm btn-danger btn-remove-tambahan">&times;</button>
                </div>`;
        }

        function addTambahanRow(prefix, item) {
            const containerId = prefix === 'edit_' ? '#edit_tambahan_container' : '#create_tambahan_container';
            const $container = $(containerId);
            const index = $container.find('.tambahan-row').length;
            const label = item && item.label ? item.label : '';
            const amount = item && item.amount ? item.amount : 0;
            $container.append(renderTambahanRow(prefix, index, label, amount));
        }

        // Remove tambahan row
        $(document).on('click', '.btn-remove-tambahan', function(){
            const $row = $(this).closest('.tambahan-row');
            const $container = $row.closest('#create_tambahan_container, #edit_tambahan_container');
            $row.remove();
            // re-index names inside this container so server receives contiguous array for that form
            $container.find('.tambahan-row').each(function(i, el){
                const $el = $(el);
                $el.attr('data-index', i);
                $el.find('.tambahan-label').attr('name', `pendapatan_tambahan[${i}][label]`);
                $el.find('.tambahan-amount').attr('name', `pendapatan_tambahan[${i}][amount]`);
            });
            // recalc totals
            recalcTotalsFor('');
            recalcTotalsFor('edit_');
        });

    // add tambahan buttons
    $(document).on('click', '#create_add_tambahan', function(){ addTambahanRow('', null); });
    $(document).on('click', '#edit_add_tambahan', function(){ addTambahanRow('edit_', null); });

        // Get sum of tambahan for a given container
        function getTambahanTotal(prefix) {
            const containerId = prefix === 'edit_' ? '#edit_tambahan_container' : '#create_tambahan_container';
            let sum = 0;
            $(containerId).find('.tambahan-amount').each(function(){
                sum += parseNum($(this).val());
            });
            return sum;
        }

        // pot pajak = 2.5% dari (base pendapatan EXCLUDING pendapatan tambahan - bagi hasil)
        function computePotPajak(prefix) {
            function $id(s) { return $('#' + prefix + s); }
            const basePend = parseNum($id('jasa_konsultasi').val()) + parseNum($id('jasa_tindakan').val())
                + parseNum($id('tunjangan_jabatan').val()) + parseNum($id('overtime').val())
                + parseNum($id('uang_duduk').val()) + parseNum($id('peresepan_obat').val())
                + parseNum($id('rujuk_lab').val()) + parseNum($id('pembuatan_konten').val());
            const bagiHasil = parseNum($id('bagi_hasil').val());
            return { basePend: basePend, potPajak: Math.max(0, basePend - bagiHasil) * 0.025 };
        }

        // Recalc totals for the create form (prefix '') or the edit form (prefix 'edit_')
        function recalcTotalsFor(prefix) {
            function $id(s) { return $('#' + prefix + s); }
            const calc = computePotPajak(prefix);
            const bagiHasil = parseNum($id('bagi_hasil').val());
            const potonganLain = parseNum($id('potongan_lain').val());
            const totalPend = calc.basePend + getTambahanTotal(prefix);

            // If user manually entered pot_pajak, use that value; otherwise use computed
            let potPajak = calc.potPajak;
            if ($id('pot_pajak').data('manual')) {
                potPajak = parseNum($id('pot_pajak').val());
            } else {
                $id('pot_pajak').val(calc.potPajak.toFixed(2));
            }

            const totalPot = bagiHasil + potPajak + potonganLain;
            const totalGaji = totalPend - totalPot;

            $id('total_pendapatan').val(totalPend);
            $id('total_pendapatan_display').val(formatRupiah(totalPend));
            $id('total_potongan').val(totalPot);
            $id('total_potongan_display').val(formatRupiah(totalPot));
            $id('total_gaji').val(totalGaji);
            $id('total_gaji_display').val(formatRupiah(totalGaji));
        }

        // attach live handlers
        $(document).on('input', '.calc-input, .calc-input-right', function(){ recalcTotalsFor(''); });
        $(document).on('input', '.calc-input-edit, .calc-input-right-edit', function(){ recalcTotalsFor('edit_'); });

        // Typing in pot_pajak switches it to manual mode so recalc won't overwrite it
        $(document).on('input', '#pot_pajak', function(){
            $(this).data('manual', true);
            recalcTotalsFor('');
        });
        $(document).on('input', '#edit_pot_pajak', function(){
            $(this).data('manual', true);
            recalcTotalsFor('edit_');
        });
        // Back to auto 2.5%
        $(document).on('click', '.btn-reset-pajak', function(){
            const prefix = $(this).data('prefix') || '';
            $('#' + prefix + 'pot_pajak').data('manual', false);
            recalcTotalsFor(prefix);
        });

        // ensure totals calculated on modal show
        $('#createSlipModal').on('shown.bs.modal', function(){ recalcTotalsFor(''); });

        // Submit create form (supports file upload)
        $('#createSlipForm').on('submit', function(e){
            e.preventDefault();
            if (!$('#create_dokter_id').val()) {
                Swal.fire({ icon: 'warning', title: 'Dokter belum dipilih', text: 'Silakan pilih dokter terlebih dahulu.' });
                return;
            }
            recalcTotalsFor('');
            const formEl = this;
            const $btn = $(formEl).find('button[type="submit"]');
            const formData = new FormData(formEl);
            // Ensure totals and csrf
            formData.set('total_pendapatan', $('#total_pendapatan').val());
            formData.set('total_potongan', $('#total_potongan').val());
            formData.set('total_gaji', $('#total_gaji').val());
            formData.set('_token', '{{ csrf_token() }}');

            $btn.prop('disabled', true);
            $.ajax({
                url: '{{ route('hrd.payroll.slip_gaji_dokter.store') }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(){
                    table.ajax.reload();
                    $('#createSlipModal').modal('hide');
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Slip gaji dokter berhasil dibuat.' });
                    formEl.reset();
                },
                error: function(xhr){
                    Swal.fire({ icon: 'error', title: 'Error', text: ajaxErrorMessage(xhr, 'Terjadi kesalahan') });
                },
                complete: function(){
                    $btn.prop('disabled', false);
                }
            });
        });

        // Edit button click - open edit modal and populate
        // Prefer using the DataTable row data (keeps UI consistent when filter changes).
        // Fallback to the existing AJAX GET if the row data is missing or incomplete.
        $(document).on('click', '.btn-edit', function(){
            const id = $(this).data('id');
            // try to read the row data from DataTable first
            let rowData = null;
            try {
                // table is in the outer scope
                const $tr = $(this).closest('tr');
                rowData = table.row($tr).data();
            } catch (e) {
                rowData = null;
            }

            const populateModal = function(data){
                if (!data) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Data tidak tersedia.' });
                    return;
                }
                var st = normalizeStatus(data.status_gaji);
                if (st !== 'draft' && st !== 'rejected') {
                    Swal.fire({ icon: 'info', title: 'Info', text: 'Hanya slip Draft atau Rejected yang bisa diedit.' });
                    return;
                }
                // clear previous preview and file input to avoid showing stale lampiran
                $('#edit_jasmed_preview').html('');
                try { $('#edit_jasmed_file').val(''); } catch(e) {}

                $('#edit_id').val(data.id);
                $('#edit_dokter_id').val(data.dokter_id);
                $('#edit_bulan').val(data.bulan);
                $('#edit_jasa_konsultasi').val(parseFloat(data.jasa_konsultasi || 0).toFixed(2));
                $('#edit_jasa_tindakan').val(parseFloat(data.jasa_tindakan || 0).toFixed(2));
                $('#edit_uang_duduk').val(parseFloat(data.uang_duduk || 0).toFixed(2));
                $('#edit_tunjangan_jabatan').val(parseFloat(data.tunjangan_jabatan || 0).toFixed(2));
                $('#edit_overtime').val(parseFloat(data.overtime || 0).toFixed(2));
                $('#edit_peresepan_obat').val(parseFloat(data.peresepan_obat || 0).toFixed(2));
                $('#edit_rujuk_lab').val(parseFloat(data.rujuk_lab || 0).toFixed(2));
                $('#edit_pembuatan_konten').val(parseFloat(data.pembuatan_konten || 0).toFixed(2));
                $('#edit_bagi_hasil').val(parseFloat(data.bagi_hasil || 0).toFixed(2));
                $('#edit_potongan_lain').val(parseFloat(data.potongan_lain || 0).toFixed(2));
                // show existing attachment preview if present
                if (data.jasmed_file) {
                    $('#edit_jasmed_preview').html('<a href="{{ url('hrd/payroll/slip-gaji-dokter/jasmed') }}/' + data.id + '" target="_blank">Lihat Lampiran</a>');
                } else {
                    $('#edit_jasmed_preview').html('');
                }
                // populate tambahan
                $('#edit_tambahan_container').html('');
                if (data.pendapatan_tambahan && Array.isArray(data.pendapatan_tambahan) && data.pendapatan_tambahan.length) {
                    data.pendapatan_tambahan.forEach(function(it){ addTambahanRow('edit_', it); });
                } else {
                    // ensure at least one empty row
                    addTambahanRow('edit_', null);
                }
                // keep a stored pot_pajak that was manually overridden (differs from the 2.5% formula)
                const storedPajak = parseFloat(data.pot_pajak || 0);
                const computedPajak = computePotPajak('edit_').potPajak;
                $('#edit_pot_pajak').val(storedPajak.toFixed(2)).data('manual', Math.abs(storedPajak - computedPajak) > 0.01);
                // adjust visible fields according to dokter's klinik (recalcs totals when done)
                fetchAndApplyDokter(data.dokter_id, 'edit_');
                recalcTotalsFor('edit_');
                $('#editSlipModal').modal('show');
            };

            // If rowData exists and seems to match the id, use it.
            if (rowData && (rowData.id == id || rowData.id === id)) {
                populateModal(rowData);
                return;
            }

            // Fallback: fetch from server
            $.get(`{{ url('hrd/payroll/slip-gaji-dokter') }}/${id}`, function(res){
                populateModal(res.data);
            }).fail(function(){
                Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal mengambil data.' });
            });
        });

        // Delete button click - confirm and call destroy
        $(document).on('click', '.btn-delete', function(){
            const id = $(this).data('id');
            Swal.fire({
                title: 'Yakin?',
                text: 'Data slip gaji akan dihapus. Tindakan ini tidak dapat dibatalkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (!result.value) return;
                $.ajax({
                    url: `{{ url('hrd/payroll/slip-gaji-dokter') }}/${id}`,
                    method: 'DELETE',
                    success: function(){
                        table.ajax.reload();
                        Swal.fire({ icon: 'success', title: 'Terhapus', text: 'Slip gaji berhasil dihapus.' });
                    },
                    error: function(xhr){
                        Swal.fire({ icon: 'error', title: 'Error', text: ajaxErrorMessage(xhr, 'Gagal menghapus data.') });
                    }
                });
            });
        });

        // Submit edit form (supports file upload)
        $('#editSlipForm').on('submit', function(e){
            e.preventDefault();
            if (!$('#edit_dokter_id').val()) {
                Swal.fire({ icon: 'warning', title: 'Dokter belum dipilih', text: 'Silakan pilih dokter terlebih dahulu.' });
                return;
            }
            const formEl = this;
            const $btn = $(formEl).find('button[type="submit"]');
            const id = $('#edit_id').val();
            recalcTotalsFor('edit_');
            const formData = new FormData(formEl);
            formData.set('total_pendapatan', $('#edit_total_pendapatan').val());
            formData.set('total_potongan', $('#edit_total_potongan').val());
            formData.set('total_gaji', $('#edit_total_gaji').val());
            formData.set('_token', '{{ csrf_token() }}');

            $btn.prop('disabled', true);
            $.ajax({
                url: `{{ url('hrd/payroll/slip-gaji-dokter/update') }}/${id}`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(){
                    table.ajax.reload();
                    $('#editSlipModal').modal('hide');
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Slip updated.' });
                },
                error: function(xhr){
                    Swal.fire({ icon: 'error', title: 'Error', text: ajaxErrorMessage(xhr, 'Gagal mengupdate slip.') });
                },
                complete: function(){
                    $btn.prop('disabled', false);
                }
            });
        });
        // When edit modal hides, clear preview and reset form to avoid stale data when opened next
        $('#editSlipModal').on('hidden.bs.modal', function(){
            $('#edit_jasmed_preview').html('');
            // reset form fields (including file input)
            const f = $('#editSlipForm')[0];
            if (f) f.reset();
            $('#edit_pot_pajak').data('manual', false);
            // clear tambahan container
            $('#edit_tambahan_container').html('');
        });
    });
</script>
<style>
    /* compact grid to reduce modal vertical length (shared by create & edit modals) */
    .compact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .compact-grid .form-group { margin-bottom: 8px; }
    .modal-xl .modal-body { max-height: 80vh; overflow-y: auto; }
</style>
@if($canManage)
<!-- Create Slip Modal -->
<div class="modal fade" id="createSlipModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="createSlipModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createSlipModalLabel">Buat Slip Gaji Dokter</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="createSlipForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Dokter <span class="text-danger">*</span></label>
                                <select name="dokter_id" id="create_dokter_id" class="form-control" required>
                                    <option value="">-- Pilih Dokter --</option>
                                    @foreach($dokters as $d)
                                        @continue($d->is_active === false)
                                        <option value="{{ $d->id }}">{{ $d->user->name ?? ('Dokter ' . $d->id) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Bulan</label>
                                <input type="month" name="bulan" id="createBulan" class="form-control" value="{{ $bulan }}" required>
                            </div>
                            <div class="form-group">
                                <label>Lampiran (PDF / JPG / PNG)</label>
                                <input type="file" name="jasmed_file" id="jasmed_file" accept="application/pdf,image/jpeg,image/png" class="form-control-file">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-7">
                            <h6 class="text-success">Pendapatan</h6>
                            <div class="compact-grid">
                                <div class="form-group">
                                    <label>Jasa Konsultasi</label>
                                    <input type="number" step="0.01" name="jasa_konsultasi" id="jasa_konsultasi" class="form-control calc-input" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Jasa Tindakan</label>
                                    <input type="number" step="0.01" name="jasa_tindakan" id="jasa_tindakan" class="form-control calc-input" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Uang Duduk</label>
                                    <input type="number" step="0.01" name="uang_duduk" id="uang_duduk" class="form-control calc-input" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Tunjangan Jabatan</label>
                                    <input type="number" step="0.01" name="tunjangan_jabatan" id="tunjangan_jabatan" class="form-control calc-input" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Peresepan Obat</label>
                                    <input type="number" step="0.01" name="peresepan_obat" id="peresepan_obat" class="form-control calc-input" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Rujuk Lab</label>
                                    <input type="number" step="0.01" name="rujuk_lab" id="rujuk_lab" class="form-control calc-input" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Pembuatan Konten</label>
                                    <input type="number" step="0.01" name="pembuatan_konten" id="pembuatan_konten" class="form-control calc-input" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Overtime</label>
                                    <input type="number" step="0.01" name="overtime" id="overtime" class="form-control calc-input" value="0">
                                </div>
                            </div>

                            <!-- Pendapatan Tambahan (dynamic rows) -->
                            <div class="mt-2">
                                <label class="text-muted">Pendapatan Tambahan</label>
                                <div id="create_tambahan_container"></div>
                                <button type="button" id="create_add_tambahan" class="btn btn-sm btn-outline-primary mt-2">Tambah Pendapatan Tambahan</button>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <h6 class="text-danger">Potongan</h6>
                            <div class="compact-grid">
                                <div class="form-group">
                                    <label>Bagi Hasil</label>
                                    <input type="number" step="0.01" name="bagi_hasil" id="bagi_hasil" class="form-control calc-input-right" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Pot Pajak (2.5%) <a href="#" class="small btn-reset-pajak" data-prefix="" onclick="return false;" title="Hitung ulang otomatis 2.5%">auto</a></label>
                                    <!-- not a calc-input: typing here switches to manual mode -->
                                    <input type="number" step="0.01" name="pot_pajak" id="pot_pajak" class="form-control" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Potongan Lain</label>
                                    <input type="number" step="0.01" name="potongan_lain" id="potongan_lain" class="form-control calc-input-right" value="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-success">Total Pendapatan</label>
                                <input type="text" readonly id="total_pendapatan_display" class="form-control" value="Rp 0,00">
                                <input type="hidden" name="total_pendapatan" id="total_pendapatan">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-danger">Total Potongan</label>
                                <input type="text" readonly id="total_potongan_display" class="form-control" value="Rp 0,00">
                                <input type="hidden" name="total_potongan" id="total_potongan">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Total Gaji</label>
                                <input type="text" readonly id="total_gaji_display" class="form-control" value="Rp 0,00">
                                <input type="hidden" name="total_gaji" id="total_gaji">
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <small class="text-muted">Slip baru disimpan sebagai <strong>Draft</strong>. Gunakan tombol Submit di tabel untuk mengajukan ke CEO.</small>
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

<!-- Edit Slip Modal -->
<div class="modal fade" id="editSlipModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="editSlipModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSlipModalLabel">Edit Slip Gaji Dokter</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editSlipForm" enctype="multipart/form-data">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Dokter <span class="text-danger">*</span></label>
                                <select name="dokter_id" id="edit_dokter_id" class="form-control" required>
                                    <option value="">-- Pilih Dokter --</option>
                                    {{-- include inactive dokters so older slips still show their dokter --}}
                                    @foreach($dokters as $d)
                                        <option value="{{ $d->id }}">{{ $d->user->name ?? ('Dokter ' . $d->id) }}{{ $d->is_active === false ? ' (nonaktif)' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Bulan</label>
                                <input type="month" name="bulan" id="edit_bulan" class="form-control" value="{{ $bulan }}" required>
                            </div>
                            <div class="form-group">
                                <label>Lampiran (PDF / JPG / PNG)</label>
                                <input type="file" name="jasmed_file" id="edit_jasmed_file" accept="application/pdf,image/jpeg,image/png" class="form-control-file">
                                <div id="edit_jasmed_preview" class="mt-1"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-7">
                            <h6 class="text-success">Pendapatan</h6>
                            <div class="compact-grid">
                                <div class="form-group">
                                    <label>Jasa Konsultasi</label>
                                    <input type="number" step="0.01" name="jasa_konsultasi" id="edit_jasa_konsultasi" class="form-control calc-input-edit" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Jasa Tindakan</label>
                                    <input type="number" step="0.01" name="jasa_tindakan" id="edit_jasa_tindakan" class="form-control calc-input-edit" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Uang Duduk</label>
                                    <input type="number" step="0.01" name="uang_duduk" id="edit_uang_duduk" class="form-control calc-input-edit" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Tunjangan Jabatan</label>
                                    <input type="number" step="0.01" name="tunjangan_jabatan" id="edit_tunjangan_jabatan" class="form-control calc-input-edit" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Peresepan Obat</label>
                                    <input type="number" step="0.01" name="peresepan_obat" id="edit_peresepan_obat" class="form-control calc-input-edit" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Rujuk Lab</label>
                                    <input type="number" step="0.01" name="rujuk_lab" id="edit_rujuk_lab" class="form-control calc-input-edit" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Pembuatan Konten</label>
                                    <input type="number" step="0.01" name="pembuatan_konten" id="edit_pembuatan_konten" class="form-control calc-input-edit" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Overtime</label>
                                    <input type="number" step="0.01" name="overtime" id="edit_overtime" class="form-control calc-input-edit" value="0">
                                </div>
                            </div>

                            <!-- Pendapatan Tambahan (dynamic rows) for edit -->
                            <div class="mt-2">
                                <label class="text-muted">Pendapatan Tambahan</label>
                                <div id="edit_tambahan_container"></div>
                                <button type="button" id="edit_add_tambahan" class="btn btn-sm btn-outline-primary mt-2">Tambah Pendapatan Tambahan</button>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <h6 class="text-danger">Potongan</h6>
                            <div class="compact-grid">
                                <div class="form-group">
                                    <label>Bagi Hasil</label>
                                    <input type="number" step="0.01" name="bagi_hasil" id="edit_bagi_hasil" class="form-control calc-input-right-edit" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Pot Pajak (2.5%) <a href="#" class="small btn-reset-pajak" data-prefix="edit_" onclick="return false;" title="Hitung ulang otomatis 2.5%">auto</a></label>
                                    <!-- editable by user; JS will avoid overwriting when user manually edits -->
                                    <input type="number" step="0.01" name="pot_pajak" id="edit_pot_pajak" class="form-control" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Potongan Lain</label>
                                    <input type="number" step="0.01" name="potongan_lain" id="edit_potongan_lain" class="form-control calc-input-right-edit" value="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-success">Total Pendapatan</label>
                                <input type="text" readonly id="edit_total_pendapatan_display" class="form-control" value="Rp 0,00">
                                <input type="hidden" name="total_pendapatan" id="edit_total_pendapatan">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-danger">Total Potongan</label>
                                <input type="text" readonly id="edit_total_potongan_display" class="form-control" value="Rp 0,00">
                                <input type="hidden" name="total_potongan" id="edit_total_potongan">
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Total Gaji</label>
                                <input type="text" readonly id="edit_total_gaji_display" class="form-control" value="Rp 0,00">
                                <input type="hidden" name="total_gaji" id="edit_total_gaji">
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <small class="text-muted">Status diubah lewat tombol di tabel (Submit / Pay).</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
