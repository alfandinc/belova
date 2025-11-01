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
            const bagiHasil = parseNum($('#bagi_hasil').val());

            const totalPend = jasaKons + jasaTind + uangDuduk;
            // pot pajak = 2.5% dari total pendapatan
            const potPajak = +(totalPend * 0.025);
            // write computed pot_pajak back to the field (readonly)
            $('#pot_pajak').val(potPajak.toFixed(2));

            const totalPot = bagiHasil + potPajak;
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

        // Submit create form
        $('#createSlipForm').on('submit', function(e){
            e.preventDefault();
            recalcTotals();
            const form = $(this);
            const payload = form.serializeArray();
            // Ensure CSRF token included (Laravel meta token also available)
            payload.push({ name: '_token', value: '{{ csrf_token() }}' });
            $.ajax({
                url: '{{ route('hrd.payroll.slip_gaji_dokter.store') }}',
                method: 'POST',
                data: $.param(payload),
                success: function(){
                    table.ajax.reload();
                    $('#createSlipModal').modal('hide');
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Slip gaji dokter berhasil dibuat.' });
                    form[0].reset();
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
            const bagiHasil = parseNum($id('bagi_hasil').val());

            const totalPend = jasaKons + jasaTind + uangDuduk;
            const potPajak = +(totalPend * 0.025);
            $id('pot_pajak').val(potPajak.toFixed(2));

            const totalPot = bagiHasil + potPajak;
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
                $('#edit_jasa_konsultasi').val(parseFloat(data.jasa_konsultasi || 0).toFixed(2));
                $('#edit_jasa_tindakan').val(parseFloat(data.jasa_tindakan || 0).toFixed(2));
                $('#edit_uang_duduk').val(parseFloat(data.uang_duduk || 0).toFixed(2));
                $('#edit_bagi_hasil').val(parseFloat(data.bagi_hasil || 0).toFixed(2));
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

        // Submit edit form
        $('#editSlipForm').on('submit', function(e){
            e.preventDefault();
            const form = $(this);
            const id = $('#edit_id').val();
            recalcTotalsFor('edit_');
            const payload = form.serializeArray();
            payload.push({ name: '_token', value: '{{ csrf_token() }}' });
            $.ajax({
                url: `{{ url('hrd/payroll/slip-gaji-dokter/update') }}/${id}`,
                method: 'POST',
                data: $.param(payload),
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
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createSlipModalLabel">Buat Slip Gaji Dokter</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="createSlipForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Dokter</label>
                        <select name="dokter_id" class="form-control">
                            <option value="">-- Pilih Dokter (opsional) --</option>
                            @foreach($dokters as $d)
                                <option value="{{ $d->id }}">{{ $d->user->name ?? ('Dokter ' . $d->id) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Bulan</label>
                        <input type="month" name="bulan" id="createBulan" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Pendapatan</h6>
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
                                <label>Total Pendapatan</label>
                                <input type="text" readonly id="total_pendapatan_display" class="form-control" value="0">
                                <input type="hidden" name="total_pendapatan" id="total_pendapatan">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6>Potongan</h6>
                            <div class="form-group">
                                <label>Bagi Hasil</label>
                                <input type="number" step="0.01" name="bagi_hasil" id="bagi_hasil" class="form-control calc-input-right" value="0">
                            </div>
                            <div class="form-group">
                                <label>Pot Pajak (2.5% dari Total Pendapatan)</label>
                                <input type="number" step="0.01" name="pot_pajak" id="pot_pajak" class="form-control calc-input-right" value="0" readonly>
                            </div>
                            <div class="form-group">
                                <label>Total Potongan</label>
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
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSlipModalLabel">Edit Slip Gaji Dokter</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editSlipForm">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Dokter</label>
                        <select name="dokter_id" id="edit_dokter_id" class="form-control">
                            <option value="">-- Pilih Dokter (opsional) --</option>
                            @foreach($dokters as $d)
                                <option value="{{ $d->id }}">{{ $d->user->name ?? ('Dokter ' . $d->id) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Bulan</label>
                        <input type="month" name="bulan" id="edit_bulan" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Pendapatan</h6>
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
                                <label>Total Pendapatan</label>
                                <input type="text" readonly id="edit_total_pendapatan_display" class="form-control" value="0">
                                <input type="hidden" name="total_pendapatan" id="edit_total_pendapatan">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6>Potongan</h6>
                            <div class="form-group">
                                <label>Bagi Hasil</label>
                                <input type="number" step="0.01" name="bagi_hasil" id="edit_bagi_hasil" class="form-control calc-input-right-edit" value="0">
                            </div>
                            <div class="form-group">
                                <label>Pot Pajak (2.5% dari Total Pendapatan)</label>
                                <input type="number" step="0.01" name="pot_pajak" id="edit_pot_pajak" class="form-control calc-input-right-edit" value="0" readonly>
                            </div>
                            <div class="form-group">
                                <label>Total Potongan</label>
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
