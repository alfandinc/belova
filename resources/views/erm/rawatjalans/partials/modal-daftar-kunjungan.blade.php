<!-- Modal: Daftarkan Pasien (Rawat Jalan) -->
<div class="modal fade" id="modalDaftarKunjunganRawatJalan" tabindex="-1" role="dialog" aria-labelledby="modalDaftarKunjunganRawatJalanLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="form-daftar-kunjungan-rawatjalan">
            @csrf
            <input type="hidden" name="jenis_kunjungan" id="rj_jenis_kunjungan" value="1">
            <input type="hidden" id="rj_mode" value="konsultasi">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalDaftarKunjunganRawatJalanLabel">Daftarkan Kunjungan Pasien</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="la la-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Pasien</label>
                        <select id="rj_pasien_id" name="pasien_id" class="form-control select2" required></select>
                    </div>

                    <div class="form-group">
                        <label>Klinik</label>
                        <select id="rj_klinik_id" name="klinik_id" class="form-control select2" required>
                            <option value="" disabled selected>Pilih Klinik</option>
                            @foreach($kliniks as $klinik)
                                <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Dokter</label>
                        <select id="rj_dokter_id" name="dokter_id" class="form-control select2" required disabled>
                            <option value="">Pilih Dokter</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tanggal Kunjungan</label>
                        <input type="date" class="form-control" id="rj_tanggal_visitation" name="tanggal_visitation" required>
                    </div>

                    <div class="form-group" id="rj_waktu_group">
                        <label>Waktu Kunjungan (Opsional)</label>
                        <input type="time" class="form-control" id="rj_waktu_kunjungan" name="waktu_kunjungan">
                    </div>

                    <div class="form-group">
                        <label for="rj_metode_bayar_id">Cara Bayar</label>
                        <select class="form-control select2" id="rj_metode_bayar_id" name="metode_bayar_id" required>
                            <option value="" selected disabled>Pilih Metode Bayar</option>
                            @foreach($metodeBayar as $metode)
                                <option value="{{ $metode->id }}">{{ $metode->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" id="rj_no_antrian_group">
                        <label>No Antrian</label>
                        <input type="text" name="no_antrian" id="rj_no_antrian" class="form-control" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function(){
    // init select2 inside modal
    $('#modalDaftarKunjunganRawatJalan select.select2:not(#rj_pasien_id)').select2({ width: '100%' });

    // pasien select2 ajax
    $('#rj_pasien_id').select2({
        width: '100%',
        placeholder: 'Cari pasien (nama / RM / NIK)',
        allowClear: true,
        ajax: {
            url: "{{ route('erm.pasiens.select2') }}",
            dataType: 'json',
            delay: 250,
            data: function(params){
                return { q: params.term || '' };
            },
            processResults: function(data){
                return data;
            },
            cache: true
        }
    });

    function applyMode(mode){
        mode = (mode || 'konsultasi').toString();
        $('#rj_mode').val(mode);

        if (mode === 'produk') {
            $('#modalDaftarKunjunganRawatJalanLabel').text('Daftarkan Kunjungan Beli Produk Pasien');
            $('#rj_jenis_kunjungan').val('2');
            $('#rj_waktu_group').hide();
            $('#rj_no_antrian_group').hide();
            $('#rj_waktu_kunjungan').val('');
            $('#rj_no_antrian').val('');
        } else if (mode === 'lab') {
            $('#modalDaftarKunjunganRawatJalanLabel').text('Daftarkan Kunjungan Laboratorium Pasien');
            $('#rj_jenis_kunjungan').val('3');
            $('#rj_waktu_group').hide();
            $('#rj_no_antrian_group').hide();
            $('#rj_waktu_kunjungan').val('');
            $('#rj_no_antrian').val('');
        } else {
            $('#modalDaftarKunjunganRawatJalanLabel').text('Daftarkan Kunjungan Pasien');
            $('#rj_jenis_kunjungan').val('1');
            $('#rj_waktu_group').show();
            $('#rj_no_antrian_group').show();
        }
    }

    // open modal (from dropdown)
    $(document).on('click', '.btn-daftarkan-pasien-rawatjalan', function(e){
        e.preventDefault();
        const mode = $(this).data('jenis') || 'konsultasi';
        applyMode(mode);
        // default tanggal = today
        try {
            if (window.moment) {
                $('#rj_tanggal_visitation').val(moment().format('YYYY-MM-DD'));
            }
        } catch(e) {}

        $('#modalDaftarKunjunganRawatJalan').modal('show');
    });

    function cekAntrianRJ(){
        let dokterId = $('#rj_dokter_id').val();
        let tanggal = $('#rj_tanggal_visitation').val();
        if (!dokterId || !tanggal) return;
        if ($('#rj_mode').val() !== 'konsultasi') return;

        $.get("{{ route('erm.visitations.cekAntrian') }}", { dokter_id: dokterId, tanggal: tanggal }, function(res){
            $('#rj_no_antrian').val(res.no_antrian || '');
        }).fail(function(){
            $('#rj_no_antrian').val('');
        });
    }

    // klinik => load doctors
    $('#rj_klinik_id').on('change', function(){
        let klinikId = $(this).val();
        let dokterSelect = $('#rj_dokter_id');

        dokterSelect.empty().append('<option value="">Loading...</option>').prop('disabled', true);
        if (!klinikId) {
            dokterSelect.empty().append('<option value="">Pilih Dokter</option>').prop('disabled', true).trigger('change.select2');
            return;
        }

        $.ajax({
            url: `/get-dokters/${klinikId}`,
            type: 'GET'
        }).done(function(data){
            dokterSelect.empty().append('<option value="">Pilih Dokter</option>');
            if (data && data.length) {
                $.each(data, function(_, dokter){
                    let dokterName = (dokter.user && dokter.user.name) ? dokter.user.name : 'Unknown Doctor';
                    let spesialis = (dokter.spesialisasi && dokter.spesialisasi.nama) ? ` (${dokter.spesialisasi.nama})` : '';
                    dokterSelect.append(`<option value="${dokter.id}">${dokterName}${spesialis}</option>`);
                });
            } else {
                dokterSelect.append('<option value="" disabled>Tidak ada dokter di klinik ini</option>');
            }
            dokterSelect.prop('disabled', false).trigger('change.select2');
        }).fail(function(){
            dokterSelect.empty().append('<option value="">Pilih Dokter</option>').prop('disabled', true).trigger('change.select2');
            Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal mengambil data dokter' });
        });
    });

    // dokter/date => cek antrian
    $('#rj_dokter_id, #rj_tanggal_visitation').on('change', function(){
        cekAntrianRJ();
    });

    // submit
    $('#form-daftar-kunjungan-rawatjalan').on('submit', function(e){
        e.preventDefault();

        let url = "{{ route('erm.visitations.store') }}";
        const mode = $('#rj_mode').val();
        if (mode === 'produk') {
            url = "{{ route('erm.visitations.produk.store') }}";
        } else if (mode === 'lab') {
            url = "{{ route('erm.visitations.lab.store') }}";
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: $(this).serialize()
        }).done(function(res){
            $('#modalDaftarKunjunganRawatJalan').modal('hide');
            $('#form-daftar-kunjungan-rawatjalan')[0].reset();
            $('#rj_pasien_id').val(null).trigger('change');
            $('#rj_dokter_id').empty().append('<option value="">Pilih Dokter</option>').prop('disabled', true).trigger('change.select2');
            $('#rj_no_antrian').val('');

            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: (res && res.message) ? res.message : 'Kunjungan berhasil disimpan.',
                confirmButtonText: 'OK'
            }).then(function(){
                try {
                    $('#rawatjalan-table').DataTable().ajax.reload(null, false);
                } catch(e) {}
                try {
                    if (typeof updateStats === 'function') updateStats();
                } catch(e) {}
            });
        }).fail(function(xhr){
            let msg = 'Terjadi kesalahan. Pastikan semua data valid.';
            if (xhr && xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonText: 'OK' });
        });
    });

    // cleanup on close
    $('#modalDaftarKunjunganRawatJalan').on('hidden.bs.modal', function(){
        try { $('#form-daftar-kunjungan-rawatjalan')[0].reset(); } catch(e) {}
        try { $('#rj_pasien_id').val(null).trigger('change'); } catch(e) {}
        try { $('#rj_dokter_id').empty().append('<option value="">Pilih Dokter</option>').prop('disabled', true).trigger('change.select2'); } catch(e) {}
        $('#rj_no_antrian').val('');
        applyMode('konsultasi');
    });

    // Default mode
    applyMode('konsultasi');
});
</script>
@endpush
