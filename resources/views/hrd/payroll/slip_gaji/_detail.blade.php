
<form id="formEditSlipGaji">
    <input type="hidden" name="id" id="slip_gaji_id" value="{{ $slip->id }}">
<div class="row">
    <div class="col-md-6">
        <table class="table table-bordered">
            <tr><th>No Induk</th><td><input type="text" class="form-control" name="no_induk" value="{{ $slip->employee->no_induk ?? '-' }}" readonly></td></tr>
            <tr><th>Nama</th><td><input type="text" class="form-control" name="nama" value="{{ $slip->employee->nama ?? '-' }}" readonly></td></tr>
            <tr><th>Divisi</th><td><input type="text" class="form-control" name="divisi" value="{{ $slip->employee->division->name ?? '-' }}" readonly></td></tr>
            <tr><th>Bulan</th><td><input type="text" class="form-control" name="bulan" value="{{ $slip->bulan }}" readonly></td></tr>
            <tr><th>Status</th><td>
                <select class="form-control" name="status_gaji">
                    <option value="draft" {{ $slip->status_gaji == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="final" {{ $slip->status_gaji == 'final' ? 'selected' : '' }}>Final</option>
                </select>
            </td></tr>
        </table>
    </div>
    <div class="col-md-6">
        <table class="table table-bordered">
            <tr>
                <th>Jumlah Hari Masuk / Total Jam Lembur</th>
                <td>
                    <div class="form-row">
                        <div class="col">
                            <input type="number" class="form-control" name="total_hari_masuk" value="{{ $slip->total_hari_masuk }}" placeholder="Hari Masuk">
                        </div>
                        <div class="col">
                            <input type="number" class="form-control" name="total_jam_lembur" value="{{ $slip->total_jam_lembur }}" placeholder="Jam Lembur">
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
                            <input type="number" class="form-control" name="poin_kehadiran" id="poin_kehadiran" value="{{ $slip->poin_kehadiran ?? '' }}" placeholder="Kehadiran">
                        </div>
                        <div class="col">
                            <input type="number" class="form-control" name="poin_penilaian" id="poin_penilaian" value="{{ $slip->poin_penilaian ?? '' }}" placeholder="Penilaian">
                        </div>
                        <div class="col">
                            <input type="number" class="form-control" name="poin_marketing" id="poin_marketing" value="{{ $slip->poin_marketing ?? '' }}" placeholder="Marketing">
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
    $('#poin_kehadiran, #poin_penilaian, #poin_marketing').on('input', updateKpiTotal);
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
    // ...existing code...
    function sumPendapatan() {
        var fields = [
            'gaji_pokok', 'tunjangan_jabatan', 'tunjangan_masa_kerja', 'uang_makan', 'uang_kpi', 'uang_lembur', 'jasa_medis'
        ];
        var total = 0;
        fields.forEach(function(name) {
            var val = parseFloat($('[name="'+name+'"]').val()) || 0;
            total += val;
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
    // Trigger update saat input berubah
    $('[name="gaji_pokok"], [name="tunjangan_jabatan"], [name="tunjangan_masa_kerja"], [name="uang_makan"], [name="uang_kpi"], [name="uang_lembur"], [name="jasa_medis"], [name="benefit_bpjs_kesehatan"], [name="benefit_jht"], [name="benefit_jkk"], [name="benefit_jkm"], [name="potongan_pinjaman"], [name="potongan_bpjs_kesehatan"], [name="potongan_jamsostek"], [name="potongan_penalty"], [name="potongan_lain"]')
        .on('input', function() {
            updateTotalGaji();
            sumBenefit();
        });
    // Inisialisasi saat modal dibuka
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
            <tr><th>Gaji Pokok</th><td><input type="number" step="0.01" class="form-control" name="gaji_pokok" value="{{ $slip->gaji_pokok }}"></td></tr>
            <tr><th>Tunjangan Jabatan</th><td><input type="number" step="0.01" class="form-control" name="tunjangan_jabatan" value="{{ $slip->tunjangan_jabatan }}"></td></tr>
            <tr><th>Tunjangan Masa Kerja</th><td><input type="number" step="0.01" class="form-control" name="tunjangan_masa_kerja" value="{{ $slip->tunjangan_masa_kerja }}"></td></tr>
            <tr><th>Uang Makan</th><td><input type="number" step="0.01" class="form-control" name="uang_makan" value="{{ $slip->uang_makan }}"></td></tr>
            <tr><th>Uang KPI</th><td><input type="number" step="0.01" class="form-control" name="uang_kpi" value="{{ $slip->uang_kpi }}"></td></tr>
            <tr><th>Uang Lembur</th><td><input type="number" step="0.01" class="form-control" name="uang_lembur" value="{{ $slip->uang_lembur }}"></td></tr>
            <tr><th>Jasa Medis</th><td><input type="number" step="0.01" class="form-control" name="jasa_medis" value="{{ $slip->jasa_medis }}"></td></tr>
        </table>
        <h5 class="mt-4">Benefit</h5>
        <table class="table table-bordered bg-light">
            <tr><th>Benefit BPJS Kesehatan</th><td><input type="number" step="0.01" class="form-control" name="benefit_bpjs_kesehatan" value="{{ $slip->benefit_bpjs_kesehatan }}"></td></tr>
            <tr><th>Benefit JHT</th><td><input type="number" step="0.01" class="form-control" name="benefit_jht" value="{{ $slip->benefit_jht }}"></td></tr>
            <tr><th>Benefit JKK</th><td><input type="number" step="0.01" class="form-control" name="benefit_jkk" value="{{ $slip->benefit_jkk }}"></td></tr>
            <tr><th>Benefit JKM</th><td><input type="number" step="0.01" class="form-control" name="benefit_jkm" value="{{ $slip->benefit_jkm }}"></td></tr>
        </table>
    </div>
    <div class="col-md-6">
        <h5>Potongan</h5>
        <table class="table table-bordered">
            <tr><th>Potongan Pinjaman</th><td><input type="number" step="0.01" class="form-control" name="potongan_pinjaman" value="{{ $slip->potongan_pinjaman }}"></td></tr>
            <tr><th>Potongan BPJS Kesehatan</th><td><input type="number" step="0.01" class="form-control" name="potongan_bpjs_kesehatan" value="{{ $slip->potongan_bpjs_kesehatan }}"></td></tr>
            <tr><th>Potongan Jamsostek</th><td><input type="number" step="0.01" class="form-control" name="potongan_jamsostek" value="{{ $slip->potongan_jamsostek }}"></td></tr>
            <tr><th>Potongan Penalty</th><td><input type="number" step="0.01" class="form-control" name="potongan_penalty" value="{{ $slip->potongan_penalty }}"></td></tr>
            <tr><th>Potongan Lain</th><td><input type="number" step="0.01" class="form-control" name="potongan_lain" value="{{ $slip->potongan_lain }}"></td></tr>
        </table>
    </div>
</div>
</form>
