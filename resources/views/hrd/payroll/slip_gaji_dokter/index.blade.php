@extends('layouts.hrd.app')
@section('title', 'Slip Gaji Dokter')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="card-title">Slip Gaji Dokter</h4>
            <div class="d-flex align-items-center">
                <input type="month" id="filterBulan" class="form-control mr-2" style="width:180px;" value="{{ $bulan }}">
                <button class="btn btn-success mr-2" id="btnBuatSlip">Buat Slip</button>
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
        const table = $('#slipGajiDokterTable').DataTable({
            ajax: {
                url: '{{ route("hrd.payroll.slip_gaji_dokter.data") }}',
                data: function(d){ d.bulan = $('#filterBulan').val(); }
            },
            columns: [
                // row number
                { data: null, render: function(data, type, row, meta){ return meta.row + meta.settings._iDisplayStart + 1; } },
                { data: null, render: function(data){
                    // display dokter's user name if available
                    if (data.dokter && data.dokter.user && data.dokter.user.name) return data.dokter.user.name;
                    if (data.dokter && data.dokter.id) return 'Dokter ' + data.dokter.id;
                    return '-';
                }, defaultContent: '-' },
                { data: 'bulan' },
                { data: 'total_pendapatan' },
                { data: 'total_potongan' },
                { data: 'total_gaji' },
                { data: 'status_gaji' },
                { data: null, render: function(data){
                    return `
                        <button class="btn btn-sm btn-secondary btn-edit" data-id="${data.id}">Edit</button>
                        &nbsp;
                        <a href="/hrd/payroll/slip-gaji-dokter/print/${data.id}" class="btn btn-sm btn-primary" target="_blank">Print</a>
                        &nbsp;
                        <button class="btn btn-sm btn-danger btn-delete" data-id="${data.id}">Delete</button>`;
                }}
            ]
        });

        // Ensure CSRF token is present on all AJAX requests (for DELETE)
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

        $('#filterBulan').on('change', function(){ table.ajax.reload(); });

        // Open create modal
        $('#btnBuatSlip').on('click', function(){
            // Pre-fill bulan
            $('#createBulan').val($('#filterBulan').val());
            // reset tambahan container and add one empty row
            $('#create_tambahan_container').html('');
            addTambahanRow('', null);
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
                return;
            }
            $.get(`/hrd/payroll/slip-gaji-dokter/dokter/${dokterId}`)
                .done(function(res){
                    const klinikId = res && res.data ? res.data.klinik_id : null;
                    setFieldVisibility(prefix, klinikId);
                }).fail(function(){
                    // on error, default to show all
                    setFieldVisibility(prefix, 0);
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
                    <input type="text" name="${nameLabel}" id="${idLabel}" class="form-control tambahan-label" placeholder="Komponen (contoh: attending event)" value="${label}">
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
            recalcTotals();
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


        function recalcTotals() {
            const jasaKons = parseNum($('#jasa_konsultasi').val());
            const jasaTind = parseNum($('#jasa_tindakan').val());
            const uangDuduk = parseNum($('#uang_duduk').val());
            const tunjanganJabatan = parseNum($('#tunjangan_jabatan').val());
            const overtime = parseNum($('#overtime').val());
            const peresepanObat = parseNum($('#peresepan_obat').val());
            const rujukLab = parseNum($('#rujuk_lab').val());
            const pembuatanKonten = parseNum($('#pembuatan_konten').val());
            const bagiHasil = parseNum($('#bagi_hasil').val());
            const potonganLain = parseNum($('#potongan_lain').val());

            const basePend = jasaKons + jasaTind + tunjanganJabatan + overtime + uangDuduk + peresepanObat + rujukLab + pembuatanKonten;
            const tambahanTotal = getTambahanTotal('');
            const totalPend = basePend + tambahanTotal;
            // pot pajak = 2.5% dari (base pendapatan EXCLUDING pendapatan tambahan - bagi hasil)
            const baseForPajak = Math.max(0, basePend - bagiHasil);
            const potPajak = +(baseForPajak * 0.025);
            // Only write computed pot_pajak if user hasn't manually overridden it
            if (!$('#pot_pajak').data('manual')) {
                $('#pot_pajak').val(potPajak.toFixed(2));
            }

            const totalPot = bagiHasil + potPajak + potonganLain;
            const totalGaji = totalPend - totalPot;

            $('#total_pendapatan').val(totalPend);
            $('#total_pendapatan_display').val(totalPend.toFixed(2));
            $('#total_potongan').val(totalPot);
            $('#total_potongan_display').val(totalPot.toFixed(2));
            $('#total_gaji').val(totalGaji);
            $('#total_gaji_display').val(totalGaji.toFixed(2));
        }

        // attach live handlers
        $(document).on('input', '.calc-input, .calc-input-right', function(){
            recalcTotals();
        });

        // If user focuses the pot_pajak inputs, mark them as manually edited so recalc won't overwrite
        $(document).on('focus', '#pot_pajak, #edit_pot_pajak', function(){
            $(this).data('manual', true);
        });

        // ensure totals calculated on modal show
        $('#createSlipModal').on('shown.bs.modal', function(){ recalcTotals(); });

        // Submit create form (supports file upload)
        $('#createSlipForm').on('submit', function(e){
            e.preventDefault();
            recalcTotals();
            const formEl = this;
            const formData = new FormData(formEl);
            // Ensure totals and csrf
            formData.set('total_pendapatan', $('#total_pendapatan').val());
            formData.set('total_potongan', $('#total_potongan').val());
            formData.set('total_gaji', $('#total_gaji').val());
            formData.set('_token', '{{ csrf_token() }}');

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
                    const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Terjadi kesalahan';
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                }
            });
        });

        // Generic recalc for edit modal (prefix e.g. 'edit_')
        function recalcTotalsFor(prefix) {
            function $id(s) { return $('#' + prefix + s); }
            const jasaKons = parseNum($id('jasa_konsultasi').val());
            const jasaTind = parseNum($id('jasa_tindakan').val());
            const uangDuduk = parseNum($id('uang_duduk').val());
            const tunjanganJabatan = parseNum($id('tunjangan_jabatan').val());
            const overtime = parseNum($id('overtime').val());
            const peresepanObat = parseNum($id('peresepan_obat').val());
            const rujukLab = parseNum($id('rujuk_lab').val());
            const pembuatanKonten = parseNum($id('pembuatan_konten').val());
            const bagiHasil = parseNum($id('bagi_hasil').val());
            const potonganLain = parseNum($id('potongan_lain').val());

            const basePend = jasaKons + jasaTind + tunjanganJabatan + overtime + uangDuduk + peresepanObat + rujukLab + pembuatanKonten;
            const tambahanTotal = getTambahanTotal(prefix);
            const totalPend = basePend + tambahanTotal;
            // pot pajak = 2.5% dari (base pendapatan EXCLUDING pendapatan tambahan - bagi hasil)
            const baseForPajak = Math.max(0, basePend - bagiHasil);
            const potPajak = +(baseForPajak * 0.025);
            // Only write computed pot_pajak if user hasn't manually overridden it
            if (!$id('pot_pajak').data('manual')) {
                $id('pot_pajak').val(potPajak.toFixed(2));
            }

            const totalPot = bagiHasil + potPajak + potonganLain;
            const totalGaji = totalPend - totalPot;

            $id('total_pendapatan').val(totalPend);
            $id('total_pendapatan_display').val(totalPend.toFixed(2));
            $id('total_potongan').val(totalPot);
            $id('total_potongan_display').val(totalPot.toFixed(2));
            $id('total_gaji').val(totalGaji);
            $id('total_gaji_display').val(totalGaji.toFixed(2));
        }

        // Edit button click - open edit modal and populate
        $(document).on('click', '.btn-edit', function(){
            const id = $(this).data('id');
            $.get(`/hrd/payroll/slip-gaji-dokter/${id}`, function(res){
                const data = res.data;
                // populate fields prefixed with edit_
                $('#edit_id').val(data.id);
                $('#edit_dokter_id').val(data.dokter_id);
                $('#edit_bulan').val(data.bulan);
                // adjust visible fields according to dokter's klinik
                fetchAndApplyDokter(data.dokter_id, 'edit_');
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
                // pot_pajak is computed so we don't set it directly; recalcTotalsFor will set
                $('#edit_pot_pajak').val(parseFloat(data.pot_pajak || 0).toFixed(2));
                $('#edit_status_gaji').val(data.status_gaji || 'draft');
                // show existing attachment preview if present
                if (data.jasmed_file) {
                    $('#edit_jasmed_preview').html('<a href="/storage/' + data.jasmed_file + '" target="_blank">Lihat Lampiran</a>');
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
                // compute totals
                recalcTotalsFor('edit_');
                $('#editSlipModal').modal('show');
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
                    url: `/hrd/payroll/slip-gaji-dokter/${id}`,
                    method: 'DELETE',
                    success: function(){
                        table.ajax.reload();
                        Swal.fire({ icon: 'success', title: 'Terhapus', text: 'Slip gaji berhasil dihapus.' });
                    },
                    error: function(xhr){
                        const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal menghapus data.';
                        Swal.fire({ icon: 'error', title: 'Error', text: msg });
                    }
                });
            });
        });

        // live handlers for edit modal
        $(document).on('input', '.calc-input-edit, .calc-input-right-edit', function(){ recalcTotalsFor('edit_'); });

        // Submit edit form (supports file upload)
        $('#editSlipForm').on('submit', function(e){
            e.preventDefault();
            const formEl = this;
            const id = $('#edit_id').val();
            recalcTotalsFor('edit_');
            const formData = new FormData(formEl);
            formData.set('total_pendapatan', $('#edit_total_pendapatan').val());
            formData.set('total_potongan', $('#edit_total_potongan').val());
            formData.set('total_gaji', $('#edit_total_gaji').val());
            formData.set('_token', '{{ csrf_token() }}');

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
                error: function(){
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal mengupdate slip.' });
                }
            });
        });
    });
</script>
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
                <style>
                    /* compact grid to reduce modal vertical length */
                    .compact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
                    .compact-grid .form-group { margin-bottom: 8px; }
                    .modal-xl .modal-body { max-height: 80vh; overflow-y: auto; }
                </style>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Dokter</label>
                                <select name="dokter_id" id="create_dokter_id" class="form-control">
                                    <option value="">-- Pilih Dokter (opsional) --</option>
                                    @foreach($dokters as $d)
                                        <option value="{{ $d->id }}">{{ $d->user->name ?? ('Dokter ' . $d->id) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Bulan</label>
                                <input type="month" name="bulan" id="createBulan" class="form-control" value="{{ $bulan }}">
                            </div>
                            <div class="form-group">
                                <label>Lampiran (PDF / JPG / PNG)</label>
                                <input type="file" name="jasmed_file" id="jasmed_file" accept="application/pdf,image/*" class="form-control-file">
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
                                <!-- total pendapatan moved below to align with total potongan -->
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
                                    <label>Pot Pajak (2.5%)</label>
                                    <!-- remove from calc-input-right so user's edits aren't immediately overwritten -->
                                    <input type="number" step="0.01" name="pot_pajak" id="pot_pajak" class="form-control" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Potongan Lain</label>
                                    <input type="number" step="0.01" name="potongan_lain" id="potongan_lain" class="form-control calc-input-right" value="0">
                                </div>
                                <!-- total potongan moved below to align with total pendapatan -->
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-success">Total Pendapatan</label>
                                <input type="text" readonly id="total_pendapatan_display" class="form-control" value="0">
                                <input type="hidden" name="total_pendapatan" id="total_pendapatan">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-danger">Total Potongan</label>
                                <input type="text" readonly id="total_potongan_display" class="form-control" value="0">
                                <input type="hidden" name="total_potongan" id="total_potongan">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Total Gaji</label>
                                <input type="text" readonly id="total_gaji_display" class="form-control" value="0">
                                <input type="hidden" name="total_gaji" id="total_gaji">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status_gaji" id="status_gaji" class="form-control">
                                    <option value="draft">Draft</option>
                                    <option value="paid">Paid</option>
                                </select>
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
                <style>
                    /* reuse compact grid styles for edit modal */
                    /* place inside form to scope it to modal */
                    .compact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
                    .compact-grid .form-group { margin-bottom: 8px; }
                    .modal-xl .modal-body { max-height: 80vh; overflow-y: auto; }
                </style>
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Dokter</label>
                                <select name="dokter_id" id="edit_dokter_id" class="form-control">
                                    <option value="">-- Pilih Dokter (opsional) --</option>
                                    @foreach($dokters as $d)
                                        <option value="{{ $d->id }}">{{ $d->user->name ?? ('Dokter ' . $d->id) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Bulan</label>
                                <input type="month" name="bulan" id="edit_bulan" class="form-control" value="{{ $bulan }}">
                            </div>
                            <div class="form-group">
                                <label>Lampiran (PDF / JPG / PNG)</label>
                                <input type="file" name="jasmed_file" id="edit_jasmed_file" accept="application/pdf,image/*" class="form-control-file">
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
                                <!-- total pendapatan moved below to align with total potongan -->
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
                                    <label>Pot Pajak (2.5%)</label>
                                    <!-- editable by user; JS will avoid overwriting when user manually edits -->
                                    <input type="number" step="0.01" name="pot_pajak" id="edit_pot_pajak" class="form-control" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Potongan Lain</label>
                                    <input type="number" step="0.01" name="potongan_lain" id="edit_potongan_lain" class="form-control calc-input-right-edit" value="0">
                                </div>
                                <!-- total potongan moved below to align with total pendapatan -->
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-success">Total Pendapatan</label>
                                <input type="text" readonly id="edit_total_pendapatan_display" class="form-control" value="0">
                                <input type="hidden" name="total_pendapatan" id="edit_total_pendapatan">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-danger">Total Potongan</label>
                                <input type="text" readonly id="edit_total_potongan_display" class="form-control" value="0">
                                <input type="hidden" name="total_potongan" id="edit_total_potongan">
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Total Gaji</label>
                                <input type="text" readonly id="edit_total_gaji_display" class="form-control" value="0">
                                <input type="hidden" name="total_gaji" id="edit_total_gaji">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status_gaji" id="edit_status_gaji" class="form-control">
                                    <option value="draft">Draft</option>
                                    <option value="paid">Paid</option>
                                </select>
                            </div>
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
@endsection
