@extends('layouts.erm.app')

@section('title', 'ERM | Tindakan & Inform Consent')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')
@include('erm.partials.modal-alergipasien')

@include('erm.partials.modal-tindakan-informconsent')
@include('erm.partials.modal-tindakan-fotohasil')
@include('erm.partials.modal-tindakan-spk')

<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Tindakan &amp;</h3>
        <h3 class="mb-0"><strong>Inform Consent</strong></h3>
    </div>
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Rawat Jalan</li>
                            <li class="breadcrumb-item active">Tindakan &amp; Inform Consent</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->
    @include('erm.partials.card-identitaspasien')

    @include('erm.partials.form-theme')
    <style>
        .tindakan-page .erm-section { padding-bottom: 14px; }
        .tindakan-page .erm-section-head .btn { margin-left: auto; border-radius: 9px; font-weight: 600; }
        .tindakan-page .table { margin-bottom: 0; }
        /* Price + action rows of the paket list line up */
        .price-block { width: 100%; }
        .price-block .price-row { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 4px 0; }
        .price-block .price-row + .price-row { border-top: 1px dashed var(--sb-line, #e2e8f0); }
        .price-block .price-label { font-size: .75rem; font-weight: 700; color: var(--sb-muted, #8a94ad); }
        .price-block .price-value { font-weight: 700; text-align: right; }
        .price-block .price-value s { color: var(--sb-muted, #8a94ad); font-weight: 500; margin-right: 6px; }
        .price-block .btn { min-width: 96px; border-radius: 8px; font-weight: 600; }
        .tindakan-name-link { color: var(--sb-text-strong, #1e2538); font-weight: 700; text-decoration: none; }
        .tindakan-name-link:hover { color: var(--ef-accent-text); text-decoration: underline; }
        .tindakan-badge { display: inline-block; margin-left: 6px; padding: 2px 8px; border-radius: 999px; font-size: .68rem; font-weight: 800; vertical-align: middle; }
        .tindakan-badge.is-current { background: rgba(var(--ef-accent-rgb), .14); color: var(--ef-accent-text); }
        .tindakan-badge.is-new { background: rgba(220, 38, 38, .12); color: #dc2626; }
        .tindakan-badge.is-count { background: var(--sb-tile, #f1f5f9); color: var(--sb-text, #4b5671); }
        .tindakan-badge.is-paket { background: rgba(13, 148, 136, .12); color: #0d9488; }
        .tindakan-date { font-size: .78rem; color: var(--sb-muted, #8a94ad); margin-top: 2px; }
        .tindakan-spec { font-size: .75rem; color: var(--sb-muted, #8a94ad); }
        .tindakan-actions .btn { border-radius: 8px; }
    </style>

    <div class="row tindakan-page erm-form" style="{{ \App\Support\ClinicTheme::cssVars('ef') }}">
        <!-- Left: Daftar Paket Tindakan -->
        <div class="col-xl-6 mb-3">
            <section class="erm-section h-100">
                <div class="erm-section-head">
                    <span class="erm-section-num">1</span>
                    <h4 class="erm-section-title">Daftar Tindakan<span class="erm-section-sub">Klik nama untuk melihat isi paket</span></h4>
                </div>
                <div class="table-responsive">
                    <table id="tindakanTable" class="table w-100">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Harga</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </section>
        </div>

        <!-- Right: Riwayat Tindakan Pasien -->
        <div class="col-xl-6 mb-3">
            <section class="erm-section h-100">
                <div class="erm-section-head">
                    <span class="erm-section-num">2</span>
                    <h4 class="erm-section-title">Riwayat Tindakan<span class="erm-section-sub">Semua kunjungan pasien</span></h4>
                    <a href="/erm/tindakan/history/{{ $visitation->id }}/print-detail" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-print mr-1"></i>Print Detail
                    </a>
                </div>
                <div class="table-responsive">
                    <table id="historyTindakanTable" class="table w-100">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Tindakan</th>
                                <th>Dokter</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- Detail riwayat tindakan: obat / BHP yang dipakai -->
<div class="modal fade" id="modalRiwayatDetail" tabindex="-1" aria-labelledby="modalRiwayatDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRiwayatDetailLabel">Obat &amp; BHP Tindakan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="riwayatDetailContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Isi paket tindakan modal -->
<div class="modal fade" id="modalSopDetail" tabindex="-1" aria-labelledby="modalSopDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSopDetailLabel">Isi Paket Tindakan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered" id="sopTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Obat (Bundled)</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Print Select Beautician Modal -->
<div class="modal fade" id="modalSelectBeautician" tabindex="-1" aria-labelledby="modalSelectBeauticianLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSelectBeauticianLabel">Pilih Beautician untuk Print</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="selectBeautician">Beautician</label>
                    <select id="selectBeautician" class="form-control">
                        <option value="">-- Pilih Beautician --</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" id="confirmPrintWithBeautician" class="btn btn-primary">Print</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
{{-- Loaded up front so the QR of dokter / perawat is ready when the inform consent is saved --}}
<script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>

<script>
    // QR data URI in the browser (empty when the library failed to load: the server then makes it)
    function generateQrDataUriBrowser(text, size = 200) {
        try {
            if (!window.QRious) return null;
            return new QRious({ value: text || '', size: size, level: 'H' }).toDataURL();
        } catch (e) {
            console.error('QR generation failed', e);
            return null;
        }
    }

    $(document).ready(function () {
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });
        const spesialisasiId = @json($spesialisasiId);
        const currentVisitationId = @json($visitation->id);
        let signaturePads = {};
        let savingInformConsent = false;

        function escapeHtml(value) {
            return $('<div>').text(value == null ? '' : String(value)).html();
        }

        function formatRupiah(value) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
        }

        // Today in the browser's own timezone (toISOString is UTC: before 07.00 WIB it gives yesterday)
        function todayLocal() {
            const d = new Date();
            const pad = function (n) { return String(n).padStart(2, '0'); };
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        }

        function hasPaketVisit(row) {
            return typeof row.harga_paket_visit !== 'undefined' && row.harga_paket_visit !== null && String(row.harga_paket_visit).trim() !== '';
        }

        function errorMessage(xhr, fallback) {
            return (xhr && xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : fallback;
        }

        // Daftar paket tindakan
        $('#tindakanTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: 10,
            ajax: { url: `/erm/tindakan/data/${spesialisasiId}`, data: { visitation_id: currentVisitationId } },
            columns: [
                { data: 'nama', name: 'nama', render: function (data, type, row) {
                        let badges = '';
                        const createdDate = row.created_at ? new Date(row.created_at) : null;
                        if (createdDate && !isNaN(createdDate.getTime()) && (Date.now() - createdDate) / 86400000 <= 30) {
                            badges += '<span class="tindakan-badge is-new">BARU</span>';
                        }
                        // Remaining sessions of this patient's open multi visit package
                        let usage = '';
                        if (row.multi_used !== null && row.multi_total !== null && typeof row.multi_used !== 'undefined') {
                            usage = `<div class="mt-1"><span class="tindakan-badge is-paket ml-0">Paket terpakai ${row.multi_used}/${row.multi_total}</span></div>`;
                        }
                        return `<a href="#" class="tindakan-name-link" data-id="${row.id}">${escapeHtml(data)}</a>${badges}${usage}`;
                    }
                },
                {
                    data: 'harga',
                    name: 'harga',
                    render: function (data, type, row) {
                        let normal = data ? formatRupiah(data) : '-';
                        if (row.harga_diskon && row.diskon_active) {
                            normal = `<s>${normal}</s>${formatRupiah(row.harga_diskon)}`;
                        }
                        let out = `<div class="price-block"><div class="price-row"><span class="price-label">Normal</span><span class="price-value">${normal}</span></div>`;
                        if (hasPaketVisit(row)) {
                            out += `<div class="price-row"><span class="price-label">${row.multi_visit_total || 3}x Visit</span><span class="price-value">${formatRupiah(row.harga_paket_visit)}</span></div>`;
                        }
                        return out + '</div>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        let out = '<div class="price-block">';
                        out += `<div class="price-row"><button class="btn btn-success btn-sm buat-tindakan" title="Buat Tindakan (Normal)" data-id="${row.id}" data-type="tindakan" data-harga-type="normal"><i class="fas fa-plus mr-1"></i>Normal</button></div>`;
                        if (hasPaketVisit(row)) {
                            const label = (row.multi_visit_total || 3) + 'x Visit';
                            out += `<div class="price-row"><button class="btn btn-primary btn-sm buat-tindakan" title="Buat Tindakan (${label})" data-id="${row.id}" data-type="tindakan" data-harga-type="3x"><i class="fas fa-plus mr-1"></i>${label}</button></div>`;
                        }
                        return out + '</div>';
                    }
                },
            ],
        });

        // Riwayat tindakan pasien
        $('#historyTindakanTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: 5,
            ajax: `/erm/tindakan/history/${currentVisitationId}`,
            columns: [
                { data: 'tanggal', name: 'tanggal', visible: false },
                { data: 'tindakan', name: 'tindakan', render: function (data, type, row) {
                        let name = escapeHtml(data);
                        if (parseInt(row.duplicate_count || 1) > 1) {
                            name += `<span class="tindakan-badge is-count">${parseInt(row.duplicate_count)}x</span>`;
                        }
                        if (row.current) {
                            name += '<span class="tindakan-badge is-current">Kunjungan ini</span>';
                        }
                        return `<div>${name}${row.tanggal ? `<div class="tindakan-date">${escapeHtml(row.tanggal)}</div>` : ''}</div>`;
                    }
                },
                { data: 'dokter', name: 'dokter', render: function (data, type, row) {
                        const spec = row.spesialisasi && row.spesialisasi !== '-' ? `<div class="tindakan-spec">${escapeHtml(row.spesialisasi)}</div>` : '';
                        return `<div>${escapeHtml(data)}</div>${spec}`;
                    }
                },
                {
                    data: 'dokumen',
                    name: 'dokumen',
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        let group = '<div class="btn-group tindakan-actions" role="group">';
                        if (row.inform_consent) {
                            const ic = row.inform_consent;
                            const hasFoto = (ic.before_image_path || '').trim() !== '' && (ic.after_image_path || '').trim() !== '';
                            group += `<a href="/storage/${escapeHtml(ic.file_path)}" target="_blank" class="btn btn-info btn-sm" title="Inform Consent"><i class="fas fa-file-alt"></i></a>`;
                            group += `<button class="btn ${hasFoto ? 'btn-primary' : 'btn-success'} btn-sm foto-hasil-btn" title="${hasFoto ? 'Lihat Foto' : 'Upload Foto'}" data-id="${ic.id}" data-before="${escapeHtml(ic.before_image_path || '')}" data-after="${escapeHtml(ic.after_image_path || '')}"><i class="fas ${hasFoto ? 'fa-eye' : 'fa-upload'}"></i></button>`;
                        } else {
                            group += '<button class="btn btn-secondary btn-sm" disabled title="Belum ada inform consent"><i class="fas fa-file-alt"></i></button>';
                            group += '<button class="btn btn-secondary btn-sm" disabled title="Belum ada inform consent"><i class="fas fa-upload"></i></button>';
                        }
                        if (row.visitation_id) {
                            group += `<a href="/erm/tindakan/history/${row.visitation_id}/print-detail" target="_blank" class="btn btn-outline-primary btn-sm" title="Print Detail"><i class="fas fa-print"></i></a>`;
                        }
                        // Obat / BHP and Batalkan only for this visit's tindakan
                        if (row.current) {
                            group += `<button class="btn btn-outline-secondary btn-sm detail-riwayat-btn" title="Obat & BHP" data-id="${row.id}"><i class="fas fa-pills"></i></button>`;
                            group += `<button class="btn btn-danger btn-sm batalkan-tindakan-btn" title="Batalkan" data-id="${row.id}"><i class="fas fa-ban"></i></button>`;
                        }
                        return group + '</div>';
                    }
                },
                // Hidden column for raw date sorting
                { data: 'tanggal_raw', name: 'tanggal_raw', visible: false },
            ],
            order: [[4, 'desc']]
        });

        // Foto hasil
        $(document).on('click', '.foto-hasil-btn', function () {
            const id = $(this).data('id');
            const beforePath = $(this).data('before');
            const afterPath = $(this).data('after');
            $('#informConsentId').val(id);
            $('#beforeImage').val('');
            $('#afterImage').val('');
            $('#beforePreview').hide();
            $('#afterPreview').hide();
            $('#allowPost').prop('checked', false);
            if (beforePath) {
                $('#beforePreview').attr('src', `/storage/${beforePath}`).show();
            }
            if (afterPath) {
                $('#afterPreview').attr('src', `/storage/${afterPath}`).show();
            }
            $.get(`/erm/inform-consent/${id}/get`, function (response) {
                if (response && typeof response.allow_post !== 'undefined') {
                    $('#allowPost').prop('checked', !!response.allow_post);
                }
            });
            $('#modalFotoHasil').modal('show');
        });

        // Print detail asks for the beautician first
        $(document).on('click', 'a[href*="/print-detail"]', function (e) {
            e.preventDefault();
            window._printTargetUrl = $(this).attr('href');
            $('#selectBeautician').html('<option value="">-- Pilih Beautician --</option>');
            $.get('/erm/beauticians').done(function (res) {
                const data = (res && res.data) ? res.data : res;
                (Array.isArray(data) ? data : []).forEach(function (u) {
                    $('#selectBeautician').append(`<option value="${escapeHtml(u.id)}">${escapeHtml(u.name)}</option>`);
                });
            }).always(function () {
                $('#modalSelectBeautician').modal('show');
            });
        });

        $(document).on('click', '#confirmPrintWithBeautician', function () {
            const beautId = $('#selectBeautician').val();
            if (!beautId) {
                Swal.fire('Pilih Beautician', 'Silakan pilih beautician terlebih dahulu.', 'warning');
                return;
            }
            const url = window._printTargetUrl || '';
            if (!url) return;
            window.open(url + (url.indexOf('?') === -1 ? '?' : '&') + 'beautician_id=' + encodeURIComponent(beautId), '_blank');
            $('#modalSelectBeautician').modal('hide');
        });

        // Preview images before upload
        function previewImage(input, previewSelector) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    $(previewSelector).attr('src', e.target.result).show();
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        $('#beforeImage').change(function () { previewImage(this, '#beforePreview'); });
        $('#afterImage').change(function () { previewImage(this, '#afterPreview'); });

        $('#saveFotoHasil').click(function () {
            const id = $('#informConsentId').val();
            const formData = new FormData($('#fotoHasilForm')[0]);
            Swal.fire({ title: 'Mengunggah...', text: 'Mohon tunggu, foto sedang diunggah.', icon: 'info', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false });
            $.ajax({
                url: `/erm/tindakan/upload-foto/${id}`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Berhasil', 'Foto hasil berhasil diunggah.', 'success');
                        $('#modalFotoHasil').modal('hide');
                        $('#historyTindakanTable').DataTable().ajax.reload();
                    } else {
                        Swal.fire('Gagal', 'Foto gagal diunggah.', 'error');
                    }
                },
                error: function (xhr) {
                    Swal.fire('Gagal', errorMessage(xhr, 'Foto gagal diunggah. Silakan coba lagi.'), 'error');
                }
            });
        });

        // Signature pads of the inform consent form (re-created for every newly loaded form)
        function initializeSignaturePads(step) {
            const patientCanvas = $('#signatureCanvas').get(0);
            const witnessCanvas = $('#witnessSignatureCanvas').get(0);
            if (!patientCanvas || !witnessCanvas) {
                return false;
            }
            if (!signaturePads[step]) {
                const scale = window.devicePixelRatio || 1;
                [patientCanvas, witnessCanvas].forEach(function (canvas) {
                    canvas.width = canvas.parentElement.clientWidth * scale;
                    canvas.height = canvas.parentElement.clientHeight * scale;
                    canvas.getContext('2d').scale(scale, scale);
                });
                signaturePads[step] = {
                    patient: new SignaturePad(patientCanvas),
                    witness: new SignaturePad(witnessCanvas)
                };
            }
            return true;
        }

        $(document).on('click', '#clearSignature', function () {
            if (signaturePads[1] && signaturePads[1].patient) signaturePads[1].patient.clear();
        });
        $(document).on('click', '#clearWitnessSignature', function () {
            if (signaturePads[1] && signaturePads[1].witness) signaturePads[1].witness.clear();
        });

        $(document).on('shown.bs.modal', '#modalInformConsent', function () {
            // Small delay so the form has its final size before the canvas is measured
            setTimeout(function () { initializeSignaturePads(1); }, 300);
        });

        function loadInformConsentModal(tindakanId, visitationId) {
            $.get(`/erm/tindakan/inform-consent/${tindakanId}?visitation_id=${visitationId}`)
                .done(function (html) {
                    $('#modalInformConsentBody').html(html);
                    // Price choice (normal / multi visit) on top of the form
                    $.get(`/erm/tindakan/${tindakanId}/prices`).done(function (res) {
                        if (!res.success) return;
                        const $form = $('#modalInformConsentBody').find('#informConsentForm');
                        if (!$form.length) return;
                        const preferred = window.preferredHargaType || 'normal';
                        const hargaPaketVisit = res.harga_paket_visit;
                        let html = '<div class="form-group mb-3"><label class="font-weight-bold">Pilih Jenis Harga</label><div>';
                        html += `<div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="harga_type" id="harga_normal" value="normal" ${preferred === 'normal' ? 'checked' : ''}><label class="form-check-label" for="harga_normal">Normal - ${formatRupiah(res.harga || 0)}</label></div>`;
                        if (hasPaketVisit({ harga_paket_visit: hargaPaketVisit })) {
                            html += `<div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="harga_type" id="harga_3x" value="3x" ${preferred === '3x' ? 'checked' : ''}><label class="form-check-label" for="harga_3x">${res.multi_visit_total || 3}x Visit - ${formatRupiah(hargaPaketVisit)}</label></div>`;
                        }
                        $form.prepend(html + '</div></div>');
                        window.preferredHargaType = null;
                    });
                    $('#modalInformConsentBody').append('<div class="text-center mt-4"><button id="saveInformConsent" class="btn btn-success">Simpan</button></div>');
                    $('#modalInformConsent').modal('show');
                })
                .fail(function () {
                    Swal.fire('Gagal', 'Form inform consent gagal dimuat.', 'error');
                });
        }

        function saveDuplicateTindakanDirectly(tindakanId, visitationId) {
            const formData = new FormData();
            formData.append('tindakan_id', tindakanId);
            formData.append('visitation_id', visitationId);
            formData.append('tanggal', todayLocal());
            formData.append('harga_type', window.preferredHargaType || 'normal');
            formData.append('reuse_existing_inform_consent', '1');

            Swal.fire({ title: 'Menyimpan...', text: 'Mohon tunggu, data sedang disimpan.', icon: 'info', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false });
            $.ajax({
                url: '/erm/tindakan/inform-consent/save',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    window.preferredHargaType = null;
                    if (response.success) {
                        Swal.fire('Berhasil', 'Tindakan berhasil ditambahkan.', 'success').then(function () {
                            $('#historyTindakanTable').DataTable().ajax.reload();
                            $('#tindakanTable').DataTable().ajax.reload(null, false);
                        });
                    } else {
                        Swal.fire('Gagal', 'Tindakan gagal disimpan.', 'error');
                    }
                },
                error: function (xhr) {
                    window.preferredHargaType = null;
                    Swal.fire('Gagal', errorMessage(xhr, 'Tindakan gagal disimpan. Silakan coba lagi.'), 'error');
                }
            });
        }

        $(document).on('click', '.buat-tindakan', function () {
            const id = $(this).data('id');
            window.lastTindakanIdClicked = id;
            // Price chosen with the button (normal / 3x)
            window.preferredHargaType = $(this).data('harga-type') || null;
            signaturePads = {};

            if ($(this).data('type') !== 'tindakan') return;
            // The same tindakan again in this visit reuses its inform consent
            $.get(`/erm/tindakan/${id}/exists-in-visitation`, { visitation_id: currentVisitationId })
                .done(function (check) {
                    if (check && check.success && check.exists) {
                        Swal.fire({
                            title: 'Tindakan ini sudah ditambahkan',
                            text: 'Tambahkan lagi?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, tambahkan',
                            cancelButtonText: 'Batal'
                        }).then(function (result) {
                            if (result.isConfirmed || result.value) {
                                saveDuplicateTindakanDirectly(id, currentVisitationId);
                            } else {
                                window.preferredHargaType = null;
                            }
                        });
                        return;
                    }
                    loadInformConsentModal(id, currentVisitationId);
                })
                .fail(function () {
                    loadInformConsentModal(id, currentVisitationId);
                });
        });

        $(document).on('click', '#saveInformConsent', function () {
            saveSingleInformConsent();
        });

        function saveSingleInformConsent() {
            // One save at a time: a double click would add the tindakan and its billing twice
            if (savingInformConsent) return;
            const form = $('#informConsentForm');
            const tindakanId = form.find('input[name="tindakan_id"]').val() || window.lastTindakanIdClicked || null;
            const visitationId = form.find('input[name="visitation_id"]').val() || currentVisitationId;

            const hasSignaturePad = signaturePads[1] && signaturePads[1].patient && signaturePads[1].witness;
            if (form.length && hasSignaturePad) {
                if (signaturePads[1].patient.isEmpty()) {
                    Swal.fire('Tanda tangan kosong', 'Tanda tangan pasien wajib diisi.', 'error');
                    return;
                }
                if (signaturePads[1].witness.isEmpty()) {
                    Swal.fire('Tanda tangan kosong', 'Tanda tangan saksi wajib diisi.', 'error');
                    return;
                }
                $('#signatureData').val(signaturePads[1].patient.toDataURL());
                $('#witnessSignatureData').val(signaturePads[1].witness.toDataURL());
            }

            let formData;
            let url;
            if (form.length) {
                // QR of dokter and perawat for the PDF
                const dokterNameForQr = form.find('input[name="dokter_name"]').val() || $('.dokter-nama').text() || '';
                const perawatNameForQr = @json(optional(auth()->user())->name ?? '');
                ['dokter_qr', 'perawat_qr'].forEach(function (name) {
                    if (!form.find(`input[name="${name}"]`).length) {
                        form.append(`<input type="hidden" name="${name}" />`);
                    }
                });
                form.find('input[name="dokter_qr"]').val(generateQrDataUriBrowser('Dokter: ' + dokterNameForQr) || '');
                form.find('input[name="perawat_qr"]').val(generateQrDataUriBrowser('Perawat: ' + perawatNameForQr) || '');

                formData = new FormData(form[0]);
                url = form.attr('action');
            } else {
                formData = new FormData();
                formData.append('tindakan_id', tindakanId);
                formData.append('visitation_id', visitationId);
                formData.append('tanggal', todayLocal());
                url = '/erm/tindakan/inform-consent/save';
            }

            savingInformConsent = true;
            Swal.fire({ title: 'Menyimpan...', text: 'Mohon tunggu, data sedang disimpan.', icon: 'info', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false });
            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                complete: function () {
                    savingInformConsent = false;
                },
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Berhasil', 'Tindakan dan billing berhasil disimpan.', 'success').then(function () {
                            $('#modalInformConsent').modal('hide');
                            $('#historyTindakanTable').DataTable().ajax.reload();
                            $('#tindakanTable').DataTable().ajax.reload(null, false);
                        });
                    } else {
                        Swal.fire('Gagal', 'Inform consent gagal disimpan.', 'error');
                    }
                },
                error: function (xhr) {
                    Swal.fire('Gagal', errorMessage(xhr, 'Data gagal disimpan. Silakan coba lagi.'), 'error');
                }
            });
        }

        // SPK (read only)
        $(document).on('click', '.spk-btn', function () {
            const riwayatId = $(this).data('riwayat-id') || $(this).data('id');
            $('#modalSpkReadOnlyBody').html('<div class="text-center py-4">Memuat...</div>');
            $('#modalSpkReadOnly').modal('show');
            $.get(`/erm/tindakan/spk/by-riwayat/${riwayatId}`, function (response) {
                if (!response.success) {
                    $('#modalSpkReadOnlyBody').html('<div class="alert alert-danger">Data SPK tidak ditemukan.</div>');
                    return;
                }
                const data = response.data;
                let tanggalTindakan = data.spk?.tanggal_tindakan || '-';
                if (tanggalTindakan !== '-') {
                    const d = new Date(tanggalTindakan);
                    tanggalTindakan = !isNaN(d) ? d.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) : tanggalTindakan;
                }
                $('#modalSpkReadOnlyBody').html(`<div class='row mb-2'>
                        <div class='col-md-4'><b>Nama Pasien:</b> ${escapeHtml(data.pasien_nama)}</div>
                        <div class='col-md-4'><b>No RM:</b> ${escapeHtml(data.pasien_id)}</div>
                        <div class='col-md-4'><b>Dokter PJ:</b> ${escapeHtml(data.dokter_nama)}</div>
                    </div>
                    <div class='row mb-2'>
                        <div class='col-md-4'><b>Tanggal Tindakan:</b> ${escapeHtml(tanggalTindakan)}</div>
                        <div class='col-md-4'><b>Nama Tindakan:</b> ${escapeHtml(data.tindakan_nama)}</div>
                        <div class='col-md-4'><b>Harga:</b> ${escapeHtml(data.harga)}</div>
                    </div>
                    <div class='row mb-2'>
                        <div class='col-md-4'><b>Jam Mulai:</b> ${data.spk?.waktu_mulai ? escapeHtml(data.spk.waktu_mulai.substring(0, 5)) : '-'}</div>
                        <div class='col-md-4'><b>Jam Selesai:</b> ${data.spk?.waktu_selesai ? escapeHtml(data.spk.waktu_selesai.substring(0, 5)) : '-'}</div>
                    </div>`);
            }).fail(function () {
                $('#modalSpkReadOnlyBody').html('<div class="alert alert-danger">Data SPK gagal dimuat.</div>');
            });
        });

        // Batalkan: a multi visit session is cancelled alone (the server refuses the paid first session while later ones exist)
        $(document).on('click', '.batalkan-tindakan-btn', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Batalkan Tindakan?',
                html: 'Tindakan beserta billing, inform consent, SPK, dan obat/BHP-nya akan dihapus.<br><small>Untuk paket multi visit, hanya sesi ini yang dibatalkan dan kuota paket dikembalikan.</small>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, batalkan',
                cancelButtonText: 'Tidak'
            }).then(function (result) {
                if (!(result.isConfirmed || result.value)) return;
                $.ajax({
                    url: `/erm/tindakan/riwayat/${id}`,
                    method: 'DELETE',
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Berhasil', response.message, 'success');
                            $('#historyTindakanTable').DataTable().ajax.reload();
                            $('#tindakanTable').DataTable().ajax.reload(null, false);
                        } else {
                            Swal.fire('Gagal', response.message || 'Tindakan tidak dapat dibatalkan.', 'error');
                        }
                    },
                    error: function (xhr) {
                        Swal.fire('Gagal', errorMessage(xhr, 'Terjadi kesalahan server.'), 'error');
                    }
                });
            });
        });

        // Isi paket (click on the tindakan name)
        $(document).on('click', '.tindakan-name-link', function (e) {
            e.preventDefault();
            const tindakanId = $(this).data('id');
            $('#modalSopDetailLabel').text('Isi Paket Tindakan');
            $('#sopTable tbody').html('<tr><td colspan="4" class="text-center">Memuat...</td></tr>');
            $('#modalSopDetail').modal('show');
            $.get(`/erm/tindakan/${tindakanId}/sop-list`, function (response) {
                if (!response.success) {
                    $('#sopTable tbody').html('<tr><td colspan="4" class="text-center">Gagal memuat data</td></tr>');
                    return;
                }
                $('#modalSopDetailLabel').text('Isi Paket Tindakan: ' + response.tindakan);
                if (!response.kode_tindakans || !response.kode_tindakans.length) {
                    $('#sopTable tbody').html('<tr><td colspan="4" class="text-center">Belum ada tindakan di paket ini</td></tr>');
                    return;
                }
                $('#sopTable tbody').html(response.kode_tindakans.map(function (item) {
                    let obatList = '-';
                    if (item.obats && item.obats.length) {
                        obatList = '<ul class="mb-0 pl-3">' + item.obats.map(function (o) {
                            const jumlah = o.jumlah !== null ? (' x ' + o.jumlah) : '';
                            const dosis = o.dosis ? (' | ' + o.dosis + (o.satuan_dosis ? (' ' + o.satuan_dosis) : '')) : '';
                            return '<li>' + escapeHtml(o.nama + jumlah + dosis) + '</li>';
                        }).join('') + '</ul>';
                    }
                    return `<tr><td>${escapeHtml(item.no)}</td><td>${escapeHtml(item.kode)}</td><td>${escapeHtml(item.nama)}</td><td>${obatList}</td></tr>`;
                }).join(''));
            }).fail(function () {
                $('#sopTable tbody').html('<tr><td colspan="4" class="text-center">Gagal memuat data</td></tr>');
            });
        });

        // Obat / BHP of a riwayat tindakan (change obat, qty, dosis)
        $(document).on('click', '.detail-riwayat-btn', function () {
            const riwayatId = $(this).data('id');
            $('#riwayatDetailContent').html('<div class="text-center py-4">Memuat...</div>');
            $('#modalRiwayatDetail').modal('show');
            $.get(`/erm/riwayat-tindakan/${riwayatId}/detail`, function (response) {
                $('#riwayatDetailContent').html(response.html);
            }).fail(function () {
                $('#riwayatDetailContent').html('<div class="alert alert-danger">Detail gagal dimuat.</div>');
            });
        });
    });
</script>

@endsection
