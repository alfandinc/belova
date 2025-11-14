@extends('layouts.marketing.app')

@section('title', 'Content Plan')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@push('styles')
<!-- Summernote CSS (Bootstrap 4) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-bs4.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Content Plan</h4>
            <button class="btn btn-primary" id="btnAddContentPlan">Tambah Content Plan</button>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="filterDateRange">Filter Tanggal Publish</label>
                    <input type="text" id="filterDateRange" class="form-control" autocomplete="off" placeholder="Pilih rentang tanggal">
                </div>
                <div class="col-md-3">
                    <label for="filterBrand">Filter Brand</label>
                    <select id="filterBrand" class="form-control select2" multiple>
                        <option value="Premiere Belova">Premiere Belova</option>
                        <option value="Belova Skin">Belova Skin</option>
                        <option value="BCL">BCL</option>
                        <option value="dr Fika">dr Fika</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filterPlatform">Filter Platform</label>
                    <select id="filterPlatform" class="form-control select2" multiple>
                        <option value="Instagram">Instagram</option>
                        <option value="Facebook">Facebook</option>
                        <option value="TikTok">TikTok</option>
                        <option value="YouTube">YouTube</option>
                        <option value="Website">Website</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filterStatus">Filter Status</label>
                    <select id="filterStatus" class="form-control select2">
                        <option value="">Semua Status</option>
                        <option value="Draft">Draft</option>
                        <option value="Scheduled">Scheduled</option>
                        <option value="Published">Published</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered" id="contentPlanTable" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Brand</th>
                        <th>Judul</th>
                        <th>Tanggal Publish</th>
                        <th>Platform</th>
                        <th>Jenis Konten</th>
                        <th>Link Publikasi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
            </div>
        </div>
    </div>
</div>

@include('marketing.content_plan.partials.modal')
@include('marketing.content_plan.partials.content_report_modal')
@include('marketing.content_plan.partials.brief_modal')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Summernote JS (Bootstrap 4) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-bs4.min.js"></script>
<script>
$(function() {
    // Status filter select2
    $('#filterStatus').select2({
        width: '100%',
        placeholder: 'Pilih Status',
        allowClear: true,
        dropdownParent: $('#filterStatus').parent()
    });
    $('#filterStatus').on('change', function() {
        table.ajax.reload();
    });
    // Platform filter select2
    $('#filterPlatform').select2({
        width: '100%',
        placeholder: 'Pilih Platform',
        allowClear: true,
        dropdownParent: $('#filterPlatform').parent()
    });
    $('#filterPlatform').on('change', function() {
        table.ajax.reload();
    });
    // Brand filter select2
    $('#filterBrand').select2({
        width: '100%',
        placeholder: 'Pilih Brand',
        allowClear: true,
        dropdownParent: $('#filterBrand').parent()
    });
    $('#filterBrand').on('change', function() {
        table.ajax.reload();
    });
    // Date Range Picker for filter
    $('#filterDateRange').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear',
            format: 'DD/MM/YYYY'
        }
    });
    $('#filterDateRange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
        table.ajax.reload();
    });
    $('#filterDateRange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        table.ajax.reload();
    });
    // (Removed image reference inputs and preview per UI simplification)
    let table = $('#contentPlanTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        scrollX: true,
        autoWidth: false,
        ajax: {
            url: '{{ route('marketing.content-plan.index') }}',
            data: function(d) {
                let range = $('#filterDateRange').val();
                if (range) {
                    let parts = range.split(' - ');
                    if (parts.length === 2) {
                        d.date_start = parts[0];
                        d.date_end = parts[1];
                    }
                }
                let brands = $('#filterBrand').val();
                if (brands && brands.length > 0) {
                    d.filter_brand = brands;
                }
                let platforms = $('#filterPlatform').val();
                if (platforms && platforms.length > 0) {
                    d.filter_platform = platforms;
                }
                let status = $('#filterStatus').val();
                if (status) {
                    d.filter_status = status;
                }
            }
        },
        columns: [
            { data: null, name: 'no', orderable: false, searchable: false },
            { data: 'brand', name: 'brand' },
            { data: 'judul', name: 'judul' },
            { data: 'tanggal_publish', name: 'tanggal_publish', render: function(data) {
                if (data) {
                    // Use moment.js to format date in Indonesian on two lines: date and time
                    var m = moment(data).locale('id');
                    var datePart = m.format('D MMMM YYYY');
                    var timePart = m.format('HH.mm');
                    return `<div>${datePart}<br><small style="color:#6c757d">jam ${timePart}</small></div>`;
                }
                return '';
            } },
            { data: 'platform', name: 'platform', render: function(data) {
                if (!data) return '';
                let icons = {
                    'Instagram': '<i class="fab fa-instagram fa-lg" title="Instagram" style="color:#E4405F; font-size:1.5em;"></i>',
                    'Facebook': '<i class="fab fa-facebook fa-lg" title="Facebook" style="color:#1877F3; font-size:1.5em;"></i>',
                    'TikTok': `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" style="vertical-align:middle;position:relative;top:-2px;" shape-rendering="geometricPrecision" text-rendering="geometricPrecision" image-rendering="optimizeQuality" fill-rule="evenodd" clip-rule="evenodd" viewBox="0 0 1000 1000"><path d="M906.25 0H93.75C42.19 0 0 42.19 0 93.75v812.49c0 51.57 42.19 93.75 93.75 93.75l812.5.01c51.56 0 93.75-42.19 93.75-93.75V93.75C1000 42.19 957.81 0 906.25 0zM684.02 319.72c-32.42-21.13-55.81-54.96-63.11-94.38-1.57-8.51-2.45-17.28-2.45-26.25H515l-.17 414.65c-1.74 46.43-39.96 83.7-86.8 83.7-14.57 0-28.27-3.63-40.35-9.99-27.68-14.57-46.63-43.58-46.63-76.97 0-47.96 39.02-86.98 86.97-86.98 8.95 0 17.54 1.48 25.66 4.01V421.89c-8.41-1.15-16.95-1.86-25.66-1.86-105.01 0-190.43 85.43-190.43 190.45 0 64.42 32.18 121.44 81.3 155.92 30.93 21.72 68.57 34.51 109.14 34.51 105.01 0 190.43-85.43 190.43-190.43V400.21c40.58 29.12 90.3 46.28 143.95 46.28V343.03c-28.89 0-55.8-8.59-78.39-23.31z"/></svg>`,
                    'YouTube': '<i class="fab fa-youtube fa-lg" title="YouTube" style="color:#FF0000; font-size:1.5em;"></i>',
                    'Website': '<i class="fas fa-globe fa-lg" title="Website" style="color:#28a745; font-size:1.5em;"></i>',
                    'Other': '<i class="fas fa-ellipsis-h fa-lg" title="Other" style="color:#6c757d; font-size:1.5em;"></i>'
                };
                let arr = [];
                if (Array.isArray(data)) {
                    arr = data;
                } else if (typeof data === 'string') {
                    // Try to handle comma-separated string (e.g. "Instagram,Facebook")
                    if (data.indexOf(',') !== -1) {
                        arr = data.split(',').map(s => s.trim());
                    } else {
                        arr = [data.trim()];
                    }
                }
                return arr.map(p => icons[p] || p).join(' ');
            } },
            { data: 'jenis_konten', name: 'jenis_konten' },
            { data: 'link_publikasi', name: 'link_publikasi', render: function(data) {
                if (data) {
                    return `<a href="${data}" target="_blank" style="word-break:break-word;white-space:normal;">${data}</a>`;
                }
                return '';
            } },
            { data: 'status', name: 'status', render: function(data, type, row) {
                // Render as an inline select so user can change status directly
                var options = ['Draft','Scheduled','Published','Cancelled'];
                // Map status to colors
                var map = {
                    'draft': {bg: '#6c757d', color: '#ffffff'},
                    'scheduled': {bg: '#ffc107', color: '#212529'},
                    'published': {bg: '#28a745', color: '#ffffff'},
                    'cancelled': {bg: '#dc3545', color: '#ffffff'}
                };
                var current = data ? data.toLowerCase() : '';
                var style = '';
                if (current && map[current]) {
                    style = `background-color:${map[current].bg};color:${map[current].color};border-color:transparent;`;
                }
                // Wrap select in a div so we can color the background reliably across browsers
                var html = `<div class="inline-status-wrap" style="display:inline-block;padding:4px 6px;border-radius:4px;${style}">`;
                html += `<select class="form-control form-control-sm inline-status" data-id="${row.id}" style="min-width:120px;background:transparent;border:0;box-shadow:none;color:inherit;">`;
                options.forEach(function(opt) {
                    var sel = (data && data.toLowerCase() === opt.toLowerCase()) ? 'selected' : '';
                    html += `<option value="${opt}" ${sel}>${opt}</option>`;
                });
                html += `</select>`;
                html += `</div>`;
                return html;
            } },
            { data: 'action', orderable: false, searchable: false },
        ],
        order: [[2, 'desc']],
        drawCallback: function(settings) {
            var api = this.api();
            api.column(0, {search:'applied', order:'applied'}).nodes().each(function(cell, i) {
                cell.innerHTML = api.page.info().start + i + 1;
            });
            // Inject 'Add Brief' button into action column for each visible row
            api.rows({page: 'current'}).nodes().each(function(row, i) {
                try {
                    var $row = $(row);
                    var data = api.row(row).data() || {};
                    var id = data.id || $row.find('[data-id]').data('id');
                    var $actionTd = $row.find('td').last();
                    if ($actionTd.length && $actionTd.find('.btn-add-brief').length === 0) {
                        var btn = `<button class="btn btn-sm btn-outline-secondary btn-add-brief me-1" data-id="${id}" title="Add Brief">Brief</button>`;
                        // place before statistics button if present, otherwise append
                        var $stats = $actionTd.find('.btn-statistics');
                        if ($stats.length) {
                            $stats.first().before(btn);
                        } else {
                            $actionTd.append(btn);
                        }
                    }
                } catch (e) {
                    console.error('error injecting add-brief button', e);
                }
            });
        }
    });

    // Add Content Plan
    $('#btnAddContentPlan').on('click', function() {
        $('#contentPlanForm')[0].reset();
        $('#contentPlanModalLabel').text('Tambah Content Plan');
        $('#contentPlanModal').modal('show');
        $('#contentPlanForm').attr('data-action', 'store');
        $('#contentPlanForm').attr('data-id', '');
        $('.select2').val(null).trigger('change');
        $('#brand').val(null).trigger('change');
        // default status to Scheduled when creating a new content plan
        try { $('#status').val('Scheduled').trigger('change'); } catch(e) {}
    });

    // Store/Update Content Plan
    $('#contentPlanForm').on('submit', function(e) {
        var $btn = $('#contentPlanModal .btn-primary');
        $btn.prop('disabled', true);
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm mr-1"></span> Menyimpan...');
        e.preventDefault();
        let action = $(this).attr('data-action');
        let id = $(this).attr('data-id');
        let url = action === 'store' ? '{{ route('marketing.content-plan.store') }}' : `/marketing/content-plan/${id}`;
        let method = action === 'store' ? 'POST' : 'POST'; // Always POST, use _method for PUT
        let form = this;
        let formData = new FormData(form);
        if (action === 'update') {
            formData.append('_method', 'PUT');
        }
        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                $('#contentPlanModal').modal('hide');
                table.ajax.reload();
                Swal.fire('Sukses', 'Data berhasil disimpan!', 'success');
                $btn.prop('disabled', false);
                $btn.html(originalText);
            },
            error: function(xhr) {
                let msg = 'Terjadi kesalahan.';
                if(xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).join('<br>');
                }
                Swal.fire('Error', msg, 'error');
                $btn.prop('disabled', false);
                $btn.html(originalText);
            }
        });
    });

    // Edit Content Plan
    $('#contentPlanTable').on('click', '.btn-edit', function() {
        let id = $(this).data('id');
        $.get(`/marketing/content-plan/${id}`, function(res) {
            let data = res;
            $('#contentPlanForm')[0].reset();
            $('#contentPlanModalLabel').text('Edit Content Plan');
            $('#contentPlanModal').modal('show');
            $('#contentPlanForm').attr('data-action', 'update');
            $('#contentPlanForm').attr('data-id', id);
            $('#judul').val(data.judul);
            if (Array.isArray(data.brand)) {
                $('#brand').val(data.brand).trigger('change');
            } else if (data.brand) {
                $('#brand').val([data.brand]).trigger('change');
            } else {
                $('#brand').val(null).trigger('change');
            }
            // Format tanggal_publish to 'YYYY-MM-DDTHH:MM' for datetime-local input
            let tgl = data.tanggal_publish ? data.tanggal_publish.replace(' ', 'T').slice(0,16) : '';
            $('#tanggal_publish').val(tgl);
            $('#platform').val(data.platform).trigger('change');
            $('#status').val(data.status);
            $('#jenis_konten').val(data.jenis_konten).trigger('change');
            $('#link_asset').val(data.link_asset);
            $('#link_publikasi').val(data.link_publikasi);
            // populate caption and mention if present
            try {
                $('#caption').val(data.caption || '');
                $('#mention').val(data.mention || '');
            } catch(e) {}
            // populate caption and mention if present
            try {
                $('#caption').val(data.caption || '');
                $('#mention').val(data.mention || '');
            } catch(e) {}
        });
    });

    // Delete Content Plan
    $('#contentPlanTable').on('click', '.btn-delete', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Yakin hapus?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!'
        }).then((result) => {
            if(result.isConfirmed) {
                $.ajax({
                    url: `/marketing/content-plan/${id}`,
                    method: 'DELETE',
                    success: function() {
                        table.ajax.reload();
                        Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success');
                    },
                    error: function() {
                        Swal.fire('Error', 'Gagal menghapus data.', 'error');
                    }
                });
            }
        });
    });

    // Initialize modal select2s once for smoother rendering
    $('#brand').select2({
        dropdownParent: $('#contentPlanModal'),
        width: '100%',
        multiple: true,
        placeholder: 'Pilih Brand'
    });
    $('#platform').select2({
        dropdownParent: $('#contentPlanModal'),
        width: '100%',
        multiple: true,
        placeholder: 'Pilih Platform'
    });
    $('#jenis_konten').select2({
        dropdownParent: $('#contentPlanModal'),
        width: '100%',
        multiple: true,
        placeholder: 'Pilih Jenis Konten'
    });
    $('#status').select2({
        dropdownParent: $('#contentPlanModal'),
        width: '100%'
    });

    // Setup CSRF for AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Handle inline status change
    $('#contentPlanTable').on('change', '.inline-status', function() {
        var $select = $(this);
        var id = $select.data('id');
        var value = $select.val();
        var original = $select.prop('disabled');
        $select.prop('disabled', true);
        // update styling immediately for better UX
        function applyStatusStyle($selectEl, val) {
            var map = {
                'draft': {bg: '#6c757d', color: '#ffffff'},
                'scheduled': {bg: '#ffc107', color: '#212529'},
                'published': {bg: '#28a745', color: '#ffffff'},
                'cancelled': {bg: '#dc3545', color: '#ffffff'}
            };
            var key = val ? val.toLowerCase() : '';
            var $wrap = $selectEl.closest('.inline-status-wrap');
            if ($wrap.length === 0) return;
            if (map[key]) {
                $wrap.css({'background-color': map[key].bg, 'color': map[key].color, 'border-color':'transparent'});
                // ensure select inherits text color
                $wrap.find('select').css({'color': map[key].color});
            } else {
                $wrap.css({'background-color':'', 'color':'', 'border-color':''});
                $wrap.find('select').css({'color':''});
            }
        }
        applyStatusStyle($select, value);

        $.post(`/marketing/content-plan/${id}/inline-update`, { status: value })
            .done(function(res) {
                // Optionally show a small toast or just reload the row
                table.ajax.reload(null, false);
            })
            .fail(function(xhr) {
                var msg = 'Gagal menyimpan perubahan.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire('Error', msg, 'error');
                table.ajax.reload(null, false);
            })
            .always(function() {
                $select.prop('disabled', false);
            });
    });

    // Open Content Report modal from actions
    $('#contentPlanTable').on('click', '.btn-statistics', function() {
        var id = $(this).data('id');
        // reset form
        $('#contentPlanReportForm')[0].reset();
        $('#cr_id').val('');
        $('#cr_content_plan_id').val(id);
        $('#cr_content_plan_title').text('Loading...');
        // fetch content plan title via existing endpoint
        $.get(`/marketing/content-plan/${id}`, function(plan) {
            $('#cr_content_plan_title').text(plan.judul + ' (id:' + id + ')');
        }).fail(function(){
            $('#cr_content_plan_title').text('Content Plan #' + id);
        });

        // fetch all reports for the plan (history)
        var cr_reports = [];
        $.get(`/marketing/content-report/by-plan/${id}`, function(reports) {
            cr_reports = Array.isArray(reports) ? reports : [];
            var report = cr_reports.length ? cr_reports[0] : null;
            // populate history table
            renderHistory(cr_reports);

            if (report) {
                $('#cr_id').val(report.id);
                $('#cr_likes').val(report.likes || 0);
                $('#cr_comments').val(report.comments || 0);
                $('#cr_saves').val(report.saves || 0);
                $('#cr_shares').val(report.shares || 0);
                $('#cr_reach').val(report.reach || 0);
                $('#cr_impressions').val(report.impressions || 0);
                // populate readonly ERI and ERR fields if available
                $('#cr_eri').val(report.eri != null ? Number(report.eri).toFixed(2) : '0.00');
                $('#cr_err').val(report.err != null ? Number(report.err).toFixed(2) : '0.00');
                if (report.recorded_at) {
                    // format to YYYY-MM-DDTHH:MM
                    var dt = report.recorded_at.replace(' ', 'T').slice(0,16);
                    $('#cr_recorded_at').val(dt);
                } else {
                    // default recorded_at to now (local datetime) when no report exists
                    var now = new Date();
                    var local = now.toISOString().slice(0,16);
                    $('#cr_recorded_at').val(local);
                }
            } else {
                // ensure fields have defaults
                $('#cr_id').val('');
                $('#cr_likes').val(0);
                $('#cr_comments').val(0);
                $('#cr_saves').val(0);
                $('#cr_shares').val(0);
                $('#cr_reach').val(0);
                $('#cr_impressions').val(0);
                computeAndShowERIERR();
                // default recorded_at to now when no existing report
                var now = new Date();
                var local = now.toISOString().slice(0,16);
                $('#cr_recorded_at').val(local);
            }
            $('#contentPlanReportModal').modal('show');
        }).fail(function() {
            // still show modal even if no report
            cr_reports = [];
            renderHistory(cr_reports);
            // initialize fields with zero to allow live calculation
            $('#cr_id').val('');
            $('#cr_likes').val(0);
            $('#cr_comments').val(0);
            $('#cr_saves').val(0);
            $('#cr_shares').val(0);
            $('#cr_reach').val(0);
            $('#cr_impressions').val(0);
            computeAndShowERIERR();
            // set recorded_at to now by default
            var now = new Date();
            var local = now.toISOString().slice(0,16);
            $('#cr_recorded_at').val(local);
            $('#contentPlanReportModal').modal('show');
        });
    });

    // Compute and display ERI/ERR in modal based on current numeric input values
    function computeAndShowERIERR() {
        var likes = parseInt($('#cr_likes').val() || 0, 10);
        var comments = parseInt($('#cr_comments').val() || 0, 10);
        var saves = parseInt($('#cr_saves').val() || 0, 10);
        var shares = parseInt($('#cr_shares').val() || 0, 10);
        var reach = parseInt($('#cr_reach').val() || 0, 10);
        var impressions = parseInt($('#cr_impressions').val() || 0, 10);
        var interactions = (likes || 0) + (comments || 0) + (saves || 0) + (shares || 0);
        var eri = (impressions > 0) ? (interactions / impressions) * 100 : 0;
        var err = (reach > 0) ? (interactions / reach) * 100 : 0;
        // display with 2 decimals
        $('#cr_eri').val(eri.toFixed(2));
        $('#cr_err').val(err.toFixed(2));
    }

    // Bind live calculation to input changes inside the modal
    $('#contentPlanReportModal').on('input', '#cr_likes, #cr_comments, #cr_saves, #cr_shares, #cr_reach, #cr_impressions', function() {
        computeAndShowERIERR();
    });

    // Render history table on the right side of the modal
    function renderHistory(reports) {
        var $tb = $('#cr_history_tbody');
        $tb.empty();
        if (!reports || reports.length === 0) {
            $tb.append('<tr><td colspan="4" class="text-muted small">No history</td></tr>');
            return;
        }
        // reports expected in descending order (newest first). Growth is computed
        // compared to the previous record (the next item in the array, which is older).
        reports.forEach(function(r, idx) {
            var when = r.recorded_at ? r.recorded_at : r.created_at;
            // format when using moment (local timezone)
            var whenFormatted = '';
            if (when) {
                try {
                    whenFormatted = moment(when).locale('id').format('D MMM YYYY') + '<br><small style="color:#6c757d">' + moment(when).format('HH.mm') + '</small>';
                } catch (e) {
                    whenFormatted = when;
                }
            }
            var eri = r.eri != null ? Number(r.eri) : 0;
            var err = r.err != null ? Number(r.err) : 0;

            // compute growth compared to previous (older) record
            var growthHtml = '<span class="text-muted">—</span>';
            var prev = reports[idx+1]; // older record
            if (prev && prev.eri != null) {
                var prevEri = Number(prev.eri);
                if (prevEri === 0) {
                    // cannot compute percent change from 0
                    growthHtml = '<span class="text-muted">N/A</span>';
                } else {
                    var change = ((eri - prevEri) / prevEri) * 100;
                    var up = change > 0;
                    var cls = up ? 'text-success' : (change < 0 ? 'text-danger' : 'text-muted');
                    var arrow = change > 0 ? '↑' : (change < 0 ? '↓' : '');
                    growthHtml = `<span class="${cls}">${arrow} ${Math.abs(change).toFixed(2)}%</span>`;
                }
            }

            var row = `<tr data-id="${r.id}" style="cursor:pointer"><td style="white-space:nowrap">${whenFormatted}</td><td>${eri.toFixed(2)}</td><td>${err.toFixed(2)}</td><td style="white-space:nowrap">${growthHtml}</td></tr>`;
            $tb.append(row);
        });
    }

    // Click a history row to load that report into the form for viewing/editing
    $('#cr_history_tbody').on('click', 'tr', function() {
        var id = $(this).data('id');
        if (!id) return;
        // mark selected row
        $('#cr_history_tbody tr').removeClass('table-active');
        $(this).addClass('table-active');
        // Fetch the report by id from server to get full fields (or use cr_reports cache if available)
        $.get(`/marketing/content-report/${id}`, function(report){
            if (report) {
                $('#cr_id').val(report.id);
                $('#cr_likes').val(report.likes || 0);
                $('#cr_comments').val(report.comments || 0);
                $('#cr_saves').val(report.saves || 0);
                $('#cr_shares').val(report.shares || 0);
                $('#cr_reach').val(report.reach || 0);
                $('#cr_impressions').val(report.impressions || 0);
                $('#cr_eri').val(report.eri != null ? Number(report.eri).toFixed(2) : '0.00');
                $('#cr_err').val(report.err != null ? Number(report.err).toFixed(2) : '0.00');
                if (report.recorded_at) {
                    var dt = report.recorded_at.replace(' ', 'T').slice(0,16);
                    $('#cr_recorded_at').val(dt);
                }
            }
        }).fail(function(){
            Swal.fire('Error','Gagal mengambil report','error');
        });
    });

    // 'New' button — clear the form and prepare to create a new report
    $('#cr_new_btn').on('click', function() {
        $('#cr_id').val('');
        $('#cr_likes').val(0);
        $('#cr_comments').val(0);
        $('#cr_saves').val(0);
        $('#cr_shares').val(0);
        $('#cr_reach').val(0);
        $('#cr_impressions').val(0);
        computeAndShowERIERR();
        // set recorded_at to now
        var now = new Date();
        var local = now.toISOString().slice(0,16);
        $('#cr_recorded_at').val(local);
        // visually deselect any selected history row
        $('#cr_history_tbody tr').removeClass('table-active');
    });

    // Submit content plan report (create or update)
    $('#contentPlanReportForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#cr_id').val();
        var planId = $('#cr_content_plan_id').val();
        var fd = {
            content_plan_id: planId,
            likes: $('#cr_likes').val() || 0,
            comments: $('#cr_comments').val() || 0,
            saves: $('#cr_saves').val() || 0,
            shares: $('#cr_shares').val() || 0,
            reach: $('#cr_reach').val() || 0,
            impressions: $('#cr_impressions').val() || 0,
            // engagement rate will be calculated server-side (eri/err).
            recorded_at: $('#cr_recorded_at').val() ? $('#cr_recorded_at').val().replace('T',' ') : null
        };

        if (!id) {
            // create
            $.post('{{ route('marketing.content-report.store') }}', fd)
                .done(function(res){
                    $('#contentPlanReportModal').modal('hide');
                    table.ajax.reload(null, false);
                    Swal.fire('Sukses','Report disimpan','success');
                })
                .fail(function(xhr){
                    var msg = 'Gagal menyimpan report.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors).join('<br>');
                    Swal.fire('Error', msg, 'error');
                });
        } else {
            // update
            $.ajax({
                url: `/marketing/content-report/${id}`,
                method: 'PUT',
                data: fd,
            }).done(function(res){
                $('#contentPlanReportModal').modal('hide');
                table.ajax.reload(null, false);
                Swal.fire('Sukses','Report diperbarui','success');
            }).fail(function(xhr){
                var msg = 'Gagal memperbarui report.';
                if (xhr.responseJSON && xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors).join('<br>');
                Swal.fire('Error', msg, 'error');
            });
        }
    });

    // Content Brief modal handling (add/edit brief)
    // Open brief modal when action button is clicked in the datatable (button should have class .btn-add-brief and data-id)
    $('#contentPlanTable').on('click', '.btn-add-brief', function() {
        var planId = $(this).data('id');
        console.log('btn-add-brief clicked, planId=', planId);
        // reset form
        $('#contentBriefForm')[0].reset();
        $('#cb_preview').empty();
        $('#cb_content_plan_id').val(planId);
        // clear any previously attached DataTransfer files
        window._cbDataTransfer = new DataTransfer();
        // clear any previously loaded brief cache
        window._cbLoadedBrief = null;
        window._cbLoadedIsi = null;
        // init summernote handled on modal shown event

        // Ensure modal is attached to body (avoids stacking-context/z-index issues)
        var $modal = $('#contentBriefModal');
        try {
            $modal.appendTo('body');
        } catch (e) {
            console.warn('appendTo body failed', e);
        }

        // Try Bootstrap 5 modal API first (preferred), otherwise try jQuery plugin
        try {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                // Ensure static backdrop + keyboard disabled when using the Bootstrap Modal JS API
                window._cbBsModal = new bootstrap.Modal(document.getElementById('contentBriefModal'), { backdrop: 'static', keyboard: false });
                window._cbBsModal.show();
            } else if ($modal && $modal.length && $.fn.modal) {
                $modal.modal('show');
            } else {
                // Last resort: toggle classes manually
                $modal.addClass('show').css('display','block').attr('aria-modal','true').attr('role','dialog');
                $('body').addClass('modal-open');
            }
        } catch (err) {
            console.error('Failed to show modal', err);
        }

        // Fetch latest brief for this plan (if any) and populate form
        // Also fetch the content plan details to show contextual fields (text-only)
        $.get(`/marketing/content-plan/${planId}`).done(function(plan){
            try {
                var judul = plan.judul || '';
                var brandStr = '';
                if (Array.isArray(plan.brand)) brandStr = plan.brand.join(', ');
                else brandStr = plan.brand || '';
                var platformStr = '';
                if (Array.isArray(plan.platform)) platformStr = plan.platform.join(', ');
                else platformStr = plan.platform || '';
                var jenis = plan.jenis_konten || '';

                // Set text elements (and fall back to inputs if they exist)
                if ($('#cb_judul_text').length) { $('#cb_judul_text').text(judul); } else { $('#cb_judul').val(judul); }
                if ($('#cb_brand_text').length) { $('#cb_brand_text').text(brandStr); } else { $('#cb_brand').val(brandStr); }
                if ($('#cb_platform_text').length) { $('#cb_platform_text').text(platformStr); } else { $('#cb_platform').val(platformStr); }
                if ($('#cb_jenis_konten_text').length) { $('#cb_jenis_konten_text').text(jenis); } else { $('#cb_jenis_konten').val(jenis); }
            } catch(e) {}
        }).fail(function(){
            // ignore
        });

        $.get(`/marketing/content-brief/by-plan/${planId}`)
            .done(function(res){
                if (!res) return;
                window._cbLoadedBrief = res;
                try {
                    $('#cb_headline').val(res.headline || '');
                    $('#cb_sub_headline').val(res.sub_headline || '');
                } catch(e){}
                // isi_konten: may need to wait for summernote init; store temporarily
                window._cbLoadedIsi = res.isi_konten || '';

                // Render existing visual_references (array of storage paths)
                if (Array.isArray(res.visual_references) && res.visual_references.length) {
                    var $preview = $('#cb_preview');
                    $preview.empty();
                        res.visual_references.forEach(function(p, idx){
                            var src = '/storage/' + p;
                            var $wrap = $('<div class="position-relative border rounded bg-white" style="width:100%;height:220px;overflow:hidden;display:block;margin-bottom:12px"></div>');
                            var $img = $('<img>').attr('src', src).attr('data-full', src).css({'width':'100%','height':'100%','object-fit':'cover','cursor':'zoom-in'});
                            // mark as existing so removal won't attempt to mutate DataTransfer; simple UI removal hides it
                            var $remove = $('<button type="button" class="btn btn-sm btn-danger position-absolute" title="Hapus" style="top:6px;right:6px;padding:2px 6px">×</button>');
                            $remove.on('click', function(){ $wrap.remove(); });
                            $wrap.append($img).append($remove);
                            $preview.append($wrap);
                        });
                }
            }).fail(function(xhr){
                // 204 or 404 will fall here; ignore silently
            });
    });

    // Initialize Summernote when modal is shown (Bootstrap 4 event)
    $('#contentBriefModal').on('shown.bs.modal', function() {
        if ($.fn.summernote) {
            try { $('#cb_isi_konten').summernote('destroy'); } catch(e) {}
            try { $('#cb_isi_konten').summernote({height: 200}); } catch(e) { console.warn('summernote init failed', e); }
            // if we previously loaded content from server, set it into summernote
            try {
                if (window._cbLoadedIsi) {
                    $('#cb_isi_konten').summernote('code', window._cbLoadedIsi);
                    window._cbLoadedIsi = null;
                }
            } catch(e) { /* ignore */ }
        }
    });

    // Destroy Summernote when modal is hidden to avoid duplicate instances
    $('#contentBriefModal').on('hidden.bs.modal', function() {
        if ($.fn.summernote) {
            try { $('#cb_isi_konten').summernote('destroy'); } catch(e) {}
        }
    });

    // Drop area interactions
    (function(){
        var $drop = $('#cb_drop_area');
        var $input = $('#cb_visual_references');

        $drop.on('click', function(){ $input.trigger('click'); });

        $drop.on('dragover', function(e){ e.preventDefault(); e.stopPropagation(); $drop.addClass('border-primary'); });
        $drop.on('dragleave drop', function(e){ e.preventDefault(); e.stopPropagation(); $drop.removeClass('border-primary'); });

        $drop.on('drop', function(e){
            e.preventDefault();
            e.stopPropagation();
            var dt = e.originalEvent.dataTransfer;
            if (!window._cbDataTransfer) window._cbDataTransfer = new DataTransfer();
            if (dt && dt.files && dt.files.length) {
                Array.from(dt.files).forEach(function(f){
                    if (f.type && f.type.indexOf('image') === 0) window._cbDataTransfer.items.add(f);
                });
            }
            renderCbPreview();
        });

        // Handle paste (paste screenshot directly into modal)
        function handlePaste(e){
            try {
                var clipboard = (e.originalEvent && e.originalEvent.clipboardData) || (e.clipboardData) || null;
                if (!clipboard) return;
                if (!window._cbDataTransfer) window._cbDataTransfer = new DataTransfer();
                var added = false;
                // Prefer items (gives access to files)
                if (clipboard.items && clipboard.items.length) {
                    Array.from(clipboard.items).forEach(function(item){
                        if (item.kind === 'file' && item.type.indexOf('image') === 0) {
                            var file = item.getAsFile();
                            if (file) { window._cbDataTransfer.items.add(file); added = true; }
                        }
                    });
                }
                // Fallback to clipboard.files
                if (!added && clipboard.files && clipboard.files.length) {
                    Array.from(clipboard.files).forEach(function(f){ if (f.type && f.type.indexOf('image') === 0) { window._cbDataTransfer.items.add(f); added = true; } });
                }
                if (added) {
                    renderCbPreview();
                    e.preventDefault();
                }
            } catch (err) {
                console.warn('paste handling failed', err);
            }
        }

        // Attach paste listener when modal is shown, remove when hidden
        $('#contentBriefModal').on('shown.bs.modal', function(){
            $(document).on('paste.cb', handlePaste);
        });
        $('#contentBriefModal').on('hidden.bs.modal', function(){
            $(document).off('paste.cb', handlePaste);
        });

        $input.on('change', function(e){
            var files = Array.from(e.target.files || []);
            if (!window._cbDataTransfer) window._cbDataTransfer = new DataTransfer();
            files.forEach(function(f){ if (f.type && f.type.indexOf('image') === 0) window._cbDataTransfer.items.add(f); });
            renderCbPreview();
            // reset native input so user can re-select same file if needed
            $input.val('');
        });

        // Render preview thumbnails
        function renderCbPreview(){
            var $preview = $('#cb_preview');
            $preview.empty();
            var dt = window._cbDataTransfer || {files: []};
            Array.from(dt.files).forEach(function(file, idx){
                var reader = new FileReader();
                var $wrap = $('<div class="position-relative border rounded bg-white" style="width:100%;height:220px;overflow:hidden;display:block;margin-bottom:12px"></div>');
                var $remove = $('<button type="button" class="btn btn-sm btn-danger position-absolute" title="Hapus" style="top:6px;right:6px;padding:2px 6px">×</button>');
                $remove.on('click', function(){
                    // remove file from DataTransfer
                    var dt = window._cbDataTransfer;
                    var newDt = new DataTransfer();
                    Array.from(dt.files).forEach(function(f, i){ if (i !== idx) newDt.items.add(f); });
                    window._cbDataTransfer = newDt;
                    renderCbPreview();
                });
                reader.onload = function(e){
                    var $img = $('<img>').attr('src', e.target.result).attr('data-full', e.target.result).css({'width':'100%','height':'100%','object-fit':'cover','cursor':'zoom-in'});
                    $wrap.append($img).append($remove);
                    $preview.append($wrap);
                };
                reader.readAsDataURL(file);
            });
        }
    })();

    // Click on any preview image: open full-size image in a new tab
    $('#cb_preview').on('click', 'img', function(){
        var src = $(this).data('full') || $(this).attr('src');
        if (!src) return;
        try {
            // Open in a new tab/window to let user view or download at native resolution
            window.open(src, '_blank');
        } catch (e) {
            // as a last resort, navigate current window
            window.location.href = src;
        }
    });

    // Submit brief form via AJAX
    $('#contentBriefForm').on('submit', function(e){
        e.preventDefault();
        var $btn = $('#contentBriefModal .btn-primary');
        $btn.prop('disabled', true).text('Menyimpan...');
        var fd = new FormData();
        fd.append('content_plan_id', $('#cb_content_plan_id').val());
        fd.append('headline', $('#cb_headline').val());
        fd.append('sub_headline', $('#cb_sub_headline').val());
        // pull summernote content if available
        var isi = $('#cb_isi_konten').val();
        if ($.fn.summernote) isi = $('#cb_isi_konten').summernote('code');
        fd.append('isi_konten', isi);
        // append files from DataTransfer
        var dt = window._cbDataTransfer || {files: []};
        Array.from(dt.files).forEach(function(f, i){ fd.append('visual_references[]', f); });

        $.ajax({
            url: '/marketing/content-brief',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
        }).done(function(res){
            // hide using the Bootstrap Modal instance if we created one, otherwise fallback to jQuery plugin
            if (window._cbBsModal && typeof window._cbBsModal.hide === 'function') {
                try { window._cbBsModal.hide(); } catch(e){ $('#contentBriefModal').modal('hide'); }
            } else {
                try { $('#contentBriefModal').modal('hide'); } catch(e) { $('#contentBriefModal').removeClass('show').css('display','none'); $('body').removeClass('modal-open'); }
            }
            table.ajax.reload(null, false);
            Swal.fire('Sukses', 'Content brief disimpan', 'success');
        }).fail(function(xhr){
            var msg = 'Gagal menyimpan content brief.';
            if (xhr.responseJSON && xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors).join('<br>');
            Swal.fire('Error', msg, 'error');
        }).always(function(){
            $btn.prop('disabled', false).text('Simpan Brief');
        });
    });
});
</script>
@endpush
