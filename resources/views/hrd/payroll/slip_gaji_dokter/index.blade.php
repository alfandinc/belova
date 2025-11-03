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
            <a href="/hrd/payroll/slip-gaji-dokter/print/${data.id}" class="btn btn-sm btn-primary" target="_blank">Print</a>`;
                }}
            ]
        });

        $('#filterBulan').on('change', function(){ table.ajax.reload(); });

        // Open create modal
        $('#btnBuatSlip').on('click', function(){
            // Pre-fill bulan
            $('#createBulan').val($('#filterBulan').val());
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

            const totalPend = jasaKons + jasaTind + tunjanganJabatan + overtime + uangDuduk + peresepanObat + rujukLab + pembuatanKonten;
            // pot pajak = 2.5% dari (total pendapatan - bagi hasil)
            const baseForPajak = Math.max(0, totalPend - bagiHasil);
            const potPajak = +(baseForPajak * 0.025);
            // write computed pot_pajak back to the field (readonly)
            $('#pot_pajak').val(potPajak.toFixed(2));

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

            const totalPend = jasaKons + jasaTind + tunjanganJabatan + overtime + uangDuduk + peresepanObat + rujukLab + pembuatanKonten;
            const baseForPajak = Math.max(0, totalPend - bagiHasil);
            const potPajak = +(baseForPajak * 0.025);
            $id('pot_pajak').val(potPajak.toFixed(2));

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
                // compute totals
                recalcTotalsFor('edit_');
                $('#editSlipModal').modal('show');
            }).fail(function(){
                Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal mengambil data.' });
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
            <form id="createSlipForm">
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
                                    <input type="number" step="0.01" name="pot_pajak" id="pot_pajak" class="form-control calc-input-right" value="0" readonly>
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
            <form id="editSlipForm">
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
                                    <input type="number" step="0.01" name="pot_pajak" id="edit_pot_pajak" class="form-control calc-input-right-edit" value="0" readonly>
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
