@php
    $isPaid = strtolower((string)($slip->status_gaji ?? '')) === 'paid';
@endphp

@if($isPaid)
    <div class="alert alert-info">
        Slip ini sudah berstatus <strong>Paid</strong>. Data tidak bisa diedit.
    </div>
@endif

<form id="formEditSlipGaji" enctype="multipart/form-data">
    <input type="hidden" name="id" id="slip_gaji_id" value="{{ $slip->id }}">
<div class="row">
    <div class="col-md-6">
        <table class="table table-bordered">
            <tr><th>No Induk</th><td><input type="text" class="form-control" name="no_induk" value="{{ $slip->employee->no_induk ?? '-' }}" readonly></td></tr>
            <tr><th>Nama</th><td><input type="text" class="form-control" name="nama" value="{{ $slip->employee->nama ?? '-' }}" readonly></td></tr>
            <tr><th>Divisi</th><td><input type="text" class="form-control" name="divisi" value="{{ $slip->employee->division->name ?? '-' }}" readonly></td></tr>
            <tr><th>Bulan</th><td><input type="text" class="form-control" name="bulan" value="{{ $slip->bulan }}" readonly></td></tr>
            <tr><th>Status</th><td>
                <select class="form-control" name="status_gaji" {{ $isPaid ? 'disabled' : '' }}>
                    <option value="draft" {{ $slip->status_gaji == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="diapprove" {{ $slip->status_gaji == 'diapprove' ? 'selected' : '' }}>Diapprove</option>
                    <option value="paid" {{ $slip->status_gaji == 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </td></tr>
            <tr>
                <th>Jasmed File (Image)</th>
                <td>
                    <input type="file" class="form-control" name="jasmed_file" accept="image/*" {{ $isPaid ? 'disabled' : '' }}>
                    @if($slip->jasmed_file)
                        <div class="mt-2">
                            <img src="{{ route('hrd.payroll.slip_gaji.jasmed', ['id' => $slip->id]) }}" alt="Jasmed File" style="max-width:120px; max-height:120px;">
                        </div>
                    @endif
                </td>
            </tr>
        </table>
        
    </div>
    <div class="col-md-6">
        <table class="table table-bordered">
            <tr>
                <th>Jumlah Hari Masuk / Total Jam Lembur</th>
                <td>
                    <div class="form-row">
                        <div class="col">
                            <input type="number" class="form-control" name="total_hari_masuk" value="{{ $slip->total_hari_masuk }}" placeholder="Hari Masuk" {{ $isPaid ? 'disabled' : '' }}>
                        </div>
                        <div class="col">
                            <input type="number" step="0.01" min="0" class="form-control" name="total_jam_lembur" value="{{ number_format((($slip->total_jam_lembur ?? 0) / 60), 2, '.', '') }}" placeholder="Jam Lembur" readonly>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <th>KPI Poin</th>
                <td>
                    <div class="form-row">
                        <div class="col">
                            <input type="number" class="form-control" name="kpi_poin" id="kpi_poin" value="{{ $slip->kpi_poin }}" readonly placeholder="Total KPI">
                        </div>
                        <div class="col">
                            <input type="number" class="form-control" name="poin_kehadiran" id="poin_kehadiran" value="{{ $slip->poin_kehadiran ?? '' }}" placeholder="Kehadiran" {{ $isPaid ? 'disabled' : '' }}>
                        </div>
                        <div class="col">
                            <input type="number" class="form-control" name="poin_penilaian" id="poin_penilaian" value="{{ $slip->poin_penilaian ?? '' }}" placeholder="Penilaian" {{ $isPaid ? 'disabled' : '' }}>
                        </div>
                        <div class="col">
                            <input type="number" class="form-control" name="poin_marketing" id="poin_marketing" value="{{ $slip->poin_marketing ?? '' }}" placeholder="Marketing" {{ $isPaid ? 'disabled' : '' }}>
                        </div>
                    </div>
                </td>
            </tr>
<script>
$(function() {
    function updateKpiTotal() {
        var kehadiran = parseFloat($('#poin_kehadiran').val()) || 0;
        var penilaian = parseFloat($('#poin_penilaian').val()) || 0;
        var marketing = parseFloat($('#poin_marketing').val()) || 0;
        var total = kehadiran + penilaian + marketing;
        $('#kpi_poin').val(total);
    }
    // Ensure we don't double-bind handlers when modal/script runs multiple times
    $('#poin_kehadiran, #poin_penilaian, #poin_marketing').off('input').on('input', updateKpiTotal);
    // Inisialisasi saat modal dibuka
    updateKpiTotal();
});
</script>
            <tr><th>Jumlah Pendapatan</th><td><input type="number" step="0.01" class="form-control" name="total_pendapatan" id="total_pendapatan" value="{{ $slip->total_pendapatan }}" readonly></td></tr>
            <tr><th>Jumlah Potongan</th><td><input type="number" step="0.01" class="form-control" name="total_potongan" id="total_potongan" value="{{ $slip->total_potongan }}" readonly></td></tr>
            <tr><th>Total Gaji</th><td><input type="number" step="0.01" class="form-control" name="total_gaji" id="total_gaji" value="{{ $slip->total_gaji }}" readonly></td></tr>
            <tr><th>Total Benefit</th><td><input type="number" step="0.01" class="form-control" name="total_benefit" id="total_benefit" value="{{ $slip->total_benefit ?? 0 }}" readonly></td></tr>
<script>
$(function() {
    // Pendapatan tambahan dynamic rows handling
    var tambahanIndex = 0;

    function addPendapatanRow(label, amount) {
        var idx = tambahanIndex++;
        var $row = $(
            '<div class="input-group mb-2 pendapatan-tambahan-row" data-idx="'+idx+'">'
            + '<input type="text" class="form-control mr-2 pendapatan-tambahan-label" name="pendapatan_tambahan['+idx+'][label]" placeholder="Komponen (contoh: attending event)" value="'+(label?label:'')+'">'
            + '<input type="number" step="0.01" class="form-control pendapatan-tambahan-amount" name="pendapatan_tambahan['+idx+'][amount]" placeholder="0.00" value="'+(amount?amount:'')+'">'
            + '<div class="input-group-append">'
                + '<button class="btn btn-danger btn-remove-pendapatan" type="button">&times;</button>'
            + '</div>'
            + '</div>'
        );
        $('#pendapatanTambahanContainer').append($row);
        // bind remove
        $row.find('.btn-remove-pendapatan').on('click', function() {
            $row.remove();
            updateTotalGaji();
        });
        // bind change to recalc
        $row.find('.pendapatan-tambahan-amount').on('input', function() {
            updateTotalGaji();
        });
    }

    // Initialize with existing pendapatan_tambahan from server
    var existingTambahan = {!! json_encode($slip->pendapatan_tambahan ?? []) !!};
    // Ensure container is cleared and index reset when this script runs
    $('#pendapatanTambahanContainer').empty();
    tambahanIndex = 0;
    if (Array.isArray(existingTambahan) && existingTambahan.length > 0) {
        existingTambahan.forEach(function(it) {
            addPendapatanRow(it.label || '', it.amount || '');
        });
    }

    function sumPendapatan() {
        var fields = [
            'gaji_pokok', 'tunjangan_jabatan', 'tunjangan_masa_kerja', 'uang_makan', 'uang_kpi', 'uang_lembur', 'jasa_medis'
        ];
        var total = 0;
        fields.forEach(function(name) {
            var val = parseFloat($('[name="'+name+'"]').val()) || 0;
            total += val;
        });
        // include pendapatan tambahan amounts
        $('.pendapatan-tambahan-amount').each(function() {
            var v = parseFloat($(this).val()) || 0;
            total += v;
        });
        $('#total_pendapatan').val(total.toFixed(2));
        return total;
    }

    function sumBenefit() {
        var fields = [
            'benefit_bpjs_kesehatan', 'benefit_jht', 'benefit_jkk', 'benefit_jkm'
        ];
        var total = 0;
        fields.forEach(function(name) {
            var val = parseFloat($('[name="'+name+'"]').val()) || 0;
            total += val;
        });
        $('#total_benefit').val(total.toFixed(2));
        return total;
    }
    function sumPotongan() {
        var fields = [
            'potongan_pinjaman', 'potongan_bpjs_kesehatan', 'potongan_jamsostek', 'potongan_penalty', 'potongan_lain'
        ];
        var total = 0;
        fields.forEach(function(name) {
            var val = parseFloat($('[name="'+name+'"]').val()) || 0;
            total += val;
        });
        $('#total_potongan').val(total.toFixed(2));
        return total;
    }
    function updateTotalGaji() {
        var pendapatan = sumPendapatan();
        var potongan = sumPotongan();
        var totalGaji = pendapatan - potongan;
        $('#total_gaji').val(totalGaji.toFixed(2));
    }

    // Unbind previous delegated handlers then bind once to avoid duplicates
    $(document).off('input', '[name="gaji_pokok"], [name="tunjangan_jabatan"], [name="tunjangan_masa_kerja"], [name="uang_makan"], [name="uang_kpi"], [name="uang_lembur"], [name="jasa_medis"], [name="benefit_bpjs_kesehatan"], [name="benefit_jht"], [name="benefit_jkk"], [name="benefit_jkm"], [name="potongan_pinjaman"], [name="potongan_bpjs_kesehatan"], [name="potongan_jamsostek"], [name="potongan_penalty"], [name="potongan_lain"]');
    $(document).on('input', '[name="gaji_pokok"], [name="tunjangan_jabatan"], [name="tunjangan_masa_kerja"], [name="uang_makan"], [name="uang_kpi"], [name="uang_lembur"], [name="jasa_medis"], [name="benefit_bpjs_kesehatan"], [name="benefit_jht"], [name="benefit_jkk"], [name="benefit_jkm"], [name="potongan_pinjaman"], [name="potongan_bpjs_kesehatan"], [name="potongan_jamsostek"], [name="potongan_penalty"], [name="potongan_lain"]', function() {
        updateTotalGaji();
        sumBenefit();
    });

    // Ensure add button isn't bound multiple times
    $(document).off('click', '#btnTambahPendapatanTambahan').on('click', '#btnTambahPendapatanTambahan', function() {
        addPendapatanRow('', '');
    });

    // Initialize totals
    updateTotalGaji();
    sumBenefit();
});
</script>
        </table>
    </div>
</div>
<hr>
<div class="row">
    <div class="col-md-6">
        <h5>Pendapatan</h5>
        <table class="table table-bordered">
            <tr><th>Gaji Pokok</th><td><input type="number" step="0.01" class="form-control" name="gaji_pokok" value="{{ $slip->gaji_pokok }}" {{ $isPaid ? 'disabled' : '' }}></td></tr>
            <tr><th>Tunjangan Jabatan</th><td><input type="number" step="0.01" class="form-control" name="tunjangan_jabatan" value="{{ $slip->tunjangan_jabatan }}" {{ $isPaid ? 'disabled' : '' }}></td></tr>
            <tr><th>Tunjangan Masa Kerja</th><td><input type="number" step="0.01" class="form-control" name="tunjangan_masa_kerja" value="{{ $slip->tunjangan_masa_kerja }}" {{ $isPaid ? 'disabled' : '' }}></td></tr>
            <tr><th>Uang Makan</th><td><input type="number" step="0.01" class="form-control" name="uang_makan" value="{{ $slip->uang_makan }}" {{ $isPaid ? 'disabled' : '' }}></td></tr>
            <tr><th>Uang KPI</th><td><input type="number" step="0.01" class="form-control" name="uang_kpi" value="{{ $slip->uang_kpi }}" {{ $isPaid ? 'disabled' : '' }}></td></tr>
            <tr><th>Uang Lembur</th><td><input type="number" step="0.01" class="form-control" name="uang_lembur" value="{{ $slip->uang_lembur }}" {{ $isPaid ? 'disabled' : '' }}></td></tr>
            <tr><th>Jasa Medis</th><td><input type="number" step="0.01" class="form-control" name="jasa_medis" value="{{ $slip->jasa_medis }}" {{ $isPaid ? 'disabled' : '' }}></td></tr>
            <tr>
                <th>Pendapatan Tambahan</th>
                <td>
                    <div id="pendapatanTambahanContainer"><!-- Dynamic additional income rows will be injected here --></div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnTambahPendapatanTambahan" {{ $isPaid ? 'disabled' : '' }}>Tambah Pendapatan Tambahan</button>
                    </div>
                </td>
            </tr>
        </table>
        <h5 class="mt-4">Benefit</h5>
        <table class="table table-bordered bg-light">
            <tr><th>Benefit BPJS Kesehatan</th><td><input type="number" step="0.01" class="form-control" name="benefit_bpjs_kesehatan" value="{{ $slip->benefit_bpjs_kesehatan }}" {{ $isPaid ? 'disabled' : '' }}></td></tr>
            <tr><th>Benefit JHT</th><td><input type="number" step="0.01" class="form-control" name="benefit_jht" value="{{ $slip->benefit_jht }}" {{ $isPaid ? 'disabled' : '' }}></td></tr>
            <tr><th>Benefit JKK</th><td><input type="number" step="0.01" class="form-control" name="benefit_jkk" value="{{ $slip->benefit_jkk }}" {{ $isPaid ? 'disabled' : '' }}></td></tr>
            <tr><th>Benefit JKM</th><td><input type="number" step="0.01" class="form-control" name="benefit_jkm" value="{{ $slip->benefit_jkm }}" {{ $isPaid ? 'disabled' : '' }}></td></tr>
        </table>
    </div>
    <div class="col-md-6">
        <h5>Potongan</h5>
        <table class="table table-bordered">
            <tr><th>Potongan Pinjaman</th><td><input type="number" step="0.01" class="form-control" name="potongan_pinjaman" value="{{ $slip->potongan_pinjaman }}" {{ $isPaid ? 'disabled' : '' }}></td></tr>
            <tr><th>Potongan BPJS Kesehatan</th><td><input type="number" step="0.01" class="form-control" name="potongan_bpjs_kesehatan" value="{{ $slip->potongan_bpjs_kesehatan }}" {{ $isPaid ? 'disabled' : '' }}></td></tr>
            <tr><th>Potongan Jamsostek</th><td><input type="number" step="0.01" class="form-control" name="potongan_jamsostek" value="{{ $slip->potongan_jamsostek }}" {{ $isPaid ? 'disabled' : '' }}></td></tr>
            <tr><th>Potongan Penalty</th><td><input type="number" step="0.01" class="form-control" name="potongan_penalty" value="{{ $slip->potongan_penalty }}" {{ $isPaid ? 'disabled' : '' }}></td></tr>
            <tr><th>Potongan Lain</th><td><input type="number" step="0.01" class="form-control" name="potongan_lain" value="{{ $slip->potongan_lain }}" {{ $isPaid ? 'disabled' : '' }}></td></tr>
        </table>
    </div>
</div>
</form>
