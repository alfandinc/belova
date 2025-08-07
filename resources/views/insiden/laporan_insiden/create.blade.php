@extends('layouts.insiden.app')
@section('title', 'Tambah Laporan Insiden')
@section('navbar')
    @include('layouts.insiden.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="card mt-3">
        <div class="card-body">
            <div id="form-alert"></div>
            <form id="formLaporanInsiden" method="POST" action="{{ isset($laporan) ? route('insiden.laporan_insiden.update', $laporan->id) : route('insiden.laporan_insiden.store') }}">
                @csrf

                <!-- 1. Data Pasien -->
                <h5 class="mb-3"><strong>1. Data Pasien</strong></h5>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="pasien_id">Pasien</label>
                        <select name="pasien_id" id="pasien_id" class="form-control" style="width: 100%">
                            @if(isset($laporan) && $laporan->pasien)
                                <option value="{{ $laporan->pasien->id }}" selected>{{ $laporan->pasien->nama }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="no_rm">No RM</label>
                        <input type="text" name="no_rm" id="no_rm" class="form-control" readonly>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="umur">Umur</label>
                        <input type="text" name="umur" id="umur" class="form-control" readonly>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="jenis_kelamin">Jenis Kelamin</label>
                        <input type="text" name="jenis_kelamin" id="jenis_kelamin" class="form-control" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="penanggung_biaya">Penanggung Biaya</label>
                        <select name="penanggung_biaya" id="penanggung_biaya" class="form-control select2-penanggung" style="width: 100%">
                            <option value="">Pilih Penanggung Biaya</option>
                            <option value="Pribadi" {{ (isset($laporan) && $laporan->penanggung_biaya == 'Pribadi') ? 'selected' : '' }}>Pribadi</option>
                            <option value="ASKES Pemerintah" {{ (isset($laporan) && $laporan->penanggung_biaya == 'ASKES Pemerintah') ? 'selected' : '' }}>ASKES Pemerintah</option>
                            <option value="JAMKESMAS" {{ (isset($laporan) && $laporan->penanggung_biaya == 'JAMKESMAS') ? 'selected' : '' }}>JAMKESMAS</option>
                            <option value="Asuransi Swasta" {{ (isset($laporan) && $laporan->penanggung_biaya == 'Asuransi Swasta') ? 'selected' : '' }}>Asuransi Swasta</option>
                            <option value="Perusahaan" {{ (isset($laporan) && $laporan->penanggung_biaya == 'Perusahaan') ? 'selected' : '' }}>Perusahaan</option>
                            <option value="Jaminan Kesehatan Daerah" {{ (isset($laporan) && $laporan->penanggung_biaya == 'Jaminan Kesehatan Daerah') ? 'selected' : '' }}>Jaminan Kesehatan Daerah</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="tanggal_masuk">Tanggal Masuk</label>
                        <input type="date" name="tanggal_masuk" id="tanggal_masuk" class="form-control" value="{{ $laporan->tanggal_masuk ?? '' }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <!-- 2. Tanggal & Waktu Insiden -->
                        <h5 class="mb-3 mt-4"><strong>2. Tanggal & Waktu Insiden</strong></h5>
                        <div class="form-group">
                            <input type="datetime-local" name="tanggal_insiden" id="tanggal_insiden" class="form-control" value="{{ isset($laporan->tanggal_insiden) ? date('Y-m-d\TH:i', strtotime($laporan->tanggal_insiden)) : '' }}">
                        </div>
                        
                        <!-- 3. Insiden -->
                        <h5 class="mb-3 mt-4"><strong>3. Insiden</strong></h5>
                        <div class="form-group">
                            <input type="text" name="insiden" id="insiden" class="form-control" value="{{ $laporan->insiden ?? '' }}">
                        </div>

                        <!-- 4. Kronologi Insiden -->
                        <h5 class="mb-3 mt-4"><strong>4. Kronologi Insiden</strong></h5>
                        <div class="form-group">
                            <textarea name="kronologi_insiden" id="kronologi_insiden" class="form-control" rows="4">{{ $laporan->kronologi_insiden ?? '' }}</textarea>
                        </div>

                        <!-- 5. Jenis Insiden -->
                        <h5 class="mb-3 mt-4"><strong>5. Jenis Insiden</strong></h5>
                        <div class="form-group">
                            <select name="jenis_insiden" id="jenis_insiden" class="form-control select2-jenis-insiden" style="width: 100%">
                                <option value="">Pilih Jenis Insiden</option>
                                <option value="Kejadian Nyaris Cedera / KNC (Near Miss)" {{ (isset($laporan) && $laporan->jenis_insiden == 'Kejadian Nyaris Cedera / KNC (Near Miss)') ? 'selected' : '' }}>Kejadian Nyaris Cedera / KNC (Near Miss)</option>
                                <option value="Kejadian Tidak Cedera / KTC (No Harm)" {{ (isset($laporan) && $laporan->jenis_insiden == 'Kejadian Tidak Cedera / KTC (No Harm)') ? 'selected' : '' }}>Kejadian Tidak Cedera / KTC (No Harm)</option>
                                <option value="Kejadian tidak Diharapkan /  KTD (Adverse Event)" {{ (isset($laporan) && $laporan->jenis_insiden == 'Kejadian tidak Diharapkan /  KTD (Adverse Event)') ? 'selected' : '' }}>Kejadian tidak Diharapkan /  KTD (Adverse Event)</option>
                                <option value="Kejadian Sentinel (Sentinel Event)" {{ (isset($laporan) && $laporan->jenis_insiden == 'Kejadian Sentinel (Sentinel Event)') ? 'selected' : '' }}>Kejadian Sentinel (Sentinel Event)</option>
                            </select>
                        </div>

                        <!-- 6. Orang Yang Pertama Melapor -->
                        <h5 class="mb-3 mt-4"><strong>6. Orang Yang Pertama Melapor</strong></h5>
                        <div class="form-group">
                            <select name="pertama_lapor" id="pertama_lapor" class="form-control select2-pertama-lapor" style="width: 100%">
                                <option value="">Pilih Pertama Lapor</option>
                                <option value="dokter" {{ (isset($laporan) && $laporan->pertama_lapor == 'dokter') ? 'selected' : '' }}>Dokter</option>
                                <option value="perawat" {{ (isset($laporan) && $laporan->pertama_lapor == 'perawat') ? 'selected' : '' }}>Perawat</option>
                                <option value="staf" {{ (isset($laporan) && $laporan->pertama_lapor == 'staf') ? 'selected' : '' }}>Staf</option>
                                <option value="pasien" {{ (isset($laporan) && $laporan->pertama_lapor == 'pasien') ? 'selected' : '' }}>Pasien</option>
                                <option value="keluarga/pendamping pasien" {{ (isset($laporan) && $laporan->pertama_lapor == 'keluarga/pendamping pasien') ? 'selected' : '' }}>Keluarga/Pendamping Pasien</option>
                                <option value="pengunjung" {{ (isset($laporan) && $laporan->pertama_lapor == 'pengunjung') ? 'selected' : '' }}>Pengunjung</option>
                            </select>
                        </div>

                        <!-- 7. Insiden Terjadi Pada -->
                        <h5 class="mb-3 mt-4"><strong>7. Insiden Terjadi Pada</strong></h5>
                        <div class="form-group">
                            <select name="insiden_pada" id="insiden_pada" class="form-control select2-insiden-pada" style="width: 100%">
                                <option value="">Pilih Insiden Pada</option>
                                <option value="pasien" {{ (isset($laporan) && $laporan->insiden_pada == 'pasien') ? 'selected' : '' }}>Pasien</option>
                                <option value="lain-lain" {{ (isset($laporan) && $laporan->insiden_pada == 'lain-lain') ? 'selected' : '' }}>Lain-lain</option>
                            </select>
                        </div>

                        <!-- 8. Jenis Pasien -->
                        <h5 class="mb-3 mt-4"><strong>8. Jenis Pasien</strong></h5>
                        <div class="form-group">
                            <select name="jenis_pasien" id="jenis_pasien" class="form-control select2-jenis-pasien" style="width: 100%">
                                <option value="">Pilih Jenis Pasien</option>
                                <option value="Pasien Rawat Jalan" {{ (isset($laporan) && $laporan->jenis_pasien == 'Pasien Rawat Jalan') ? 'selected' : '' }}>Pasien Rawat Jalan</option>
                                <option value="Pasien Rawat Inap" {{ (isset($laporan) && $laporan->jenis_pasien == 'Pasien Rawat Inap') ? 'selected' : '' }}>Pasien Rawat Inap</option>
                                <option value="Pasien UGD" {{ (isset($laporan) && $laporan->jenis_pasien == 'Pasien UGD') ? 'selected' : '' }}>Pasien UGD</option>
                                <option value="Lain-Lain" {{ (isset($laporan) && $laporan->jenis_pasien == 'Lain-Lain') ? 'selected' : '' }}>Lain-Lain</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <!-- 9. Tempat Terjadinya Insiden -->
                        <h5 class="mb-3 mt-4"><strong>9. Tempat Terjadinya Insiden</strong></h5>
                        <div class="form-group">
                            <input type="text" name="lokasi_insiden" id="lokasi_insiden" class="form-control" value="{{ $laporan->lokasi_insiden ?? '' }}">
                        </div>

                        <!-- 10. Spesialisasi -->
                        <h5 class="mb-3 mt-4"><strong>10. Spesialisasi</strong></h5>
                        <div class="form-group">
                            <select name="spesialisasi_id" id="spesialisasi_id" class="form-control select2-spesialisasi" style="width: 100%">
                                @if(isset($laporan) && $laporan->spesialisasi)
                                    <option value="{{ $laporan->spesialisasi->id }}" selected>{{ $laporan->spesialisasi->nama }}</option>
                                @endif
                            </select>
                        </div>

                        <!-- 11. Unit Terkait Yang Menyebabkan Insiden -->
                        <h5 class="mb-3 mt-4"><strong>11. Unit Terkait Yang Menyebabkan Insiden</strong></h5>
                        <div class="form-group">
                            <select name="unit_penyebab" id="unit_penyebab" class="form-control select2-unit-penyebab" style="width: 100%">
                                @if(isset($laporan) && $laporan->unitPenyebab)
                                    <option value="{{ $laporan->unitPenyebab->id }}" selected>{{ $laporan->unitPenyebab->name }}</option>
                                @endif
                            </select>
                        </div>

                        <!-- 12. Akibat Insiden Terhadap Pasien -->
                        <h5 class="mb-3 mt-4"><strong>12. Akibat Insiden Terhadap Pasien</strong></h5>
                        <div class="form-group">
                            <select name="akibat_insiden" id="akibat_insiden" class="form-control select2-akibat-insiden" style="width: 100%">
                                <option value="">Pilih Akibat Insiden</option>
                                <option value="Kematian" {{ (isset($laporan) && $laporan->akibat_insiden == 'Kematian') ? 'selected' : '' }}>Kematian</option>
                                <option value="Cedera Irreversibel/Cedera Berat" {{ (isset($laporan) && $laporan->akibat_insiden == 'Cedera Irreversibel/Cedera Berat') ? 'selected' : '' }}>Cedera Irreversibel/Cedera Berat</option>
                                <option value="Cedera Reversibel/Cedera Sedang" {{ (isset($laporan) && $laporan->akibat_insiden == 'Cedera Reversibel/Cedera Sedang') ? 'selected' : '' }}>Cedera Reversibel/Cedera Sedang</option>
                                <option value="Cedera Ringan" {{ (isset($laporan) && $laporan->akibat_insiden == 'Cedera Ringan') ? 'selected' : '' }}>Cedera Ringan</option>
                                <option value="Tidak Ada Cedera" {{ (isset($laporan) && $laporan->akibat_insiden == 'Tidak Ada Cedera') ? 'selected' : '' }}>Tidak Ada Cedera</option>
                            </select>
                        </div>

                        <!-- 13. Tindakan Yang Dilakukan Setelah Kejadian -->
                        <h5 class="mb-3 mt-4"><strong>13. Tindakan Yang Dilakukan Setelah Kejadian</strong></h5>
                        <div class="form-group">
                            <textarea name="tindakan_dilakukan" id="tindakan_dilakukan" class="form-control" rows="3">{{ $laporan->tindakan_dilakukan ?? '' }}</textarea>
                        </div>

                        <!-- 14. Tindakan Dilakukan Oleh -->
                        <h5 class="mb-3 mt-4"><strong>14. Tindakan Dilakukan Oleh</strong></h5>
                        <div class="form-group">
                            <select name="tindakan_oleh" id="tindakan_oleh" class="form-control select2-tindakan-oleh" style="width: 100%">
                                <option value="">Pilih Tindakan Oleh</option>
                                <option value="Dokter" {{ (isset($laporan) && $laporan->tindakan_oleh == 'Dokter') ? 'selected' : '' }}>Dokter</option>
                                <option value="Perawat" {{ (isset($laporan) && $laporan->tindakan_oleh == 'Perawat') ? 'selected' : '' }}>Perawat</option>
                                <option value="Staf" {{ (isset($laporan) && $laporan->tindakan_oleh == 'Staf') ? 'selected' : '' }}>Staf</option>
                            </select>
                        </div>

                        <!-- 15. Kejadian Pernah Terjadi, Langkah & Pencegahan -->
                        <h5 class="mb-3 mt-4"><strong>15. Apakah Kejadian Pernah Terjadi?</strong></h5>
                        <div class="form-group">
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="pernah_terjadi" id="pernah_terjadi_ya" value="1" {{ (isset($laporan) && $laporan->pernah_terjadi == 1) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="pernah_terjadi_ya">Ya</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="pernah_terjadi" id="pernah_terjadi_tidak" value="0" {{ (!isset($laporan) || (isset($laporan) && $laporan->pernah_terjadi == 0)) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="pernah_terjadi_tidak">Tidak</label>
                                </div>
                            </div>
                            <div id="langkahPencegahanGroup" style="display: none;">
                                <h6 class="mt-3">Apa Langkah Yang Diambil?</h6>
                                <textarea name="langkah_diambil" id="langkah_diambil" class="form-control" rows="2">{{ $laporan->langkah_diambil ?? '' }}</textarea>
                                <h6 class="mt-3">Apa Pencegahan Agar Tidak Terulang?</h6>
                                <textarea name="pencegahan" id="pencegahan" class="form-control" rows="2">{{ $laporan->pencegahan ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Simpan</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Show/hide langkah_diambil and pencegahan based on pernah_terjadi
    function toggleLangkahPencegahan() {
        var val = $("input[name='pernah_terjadi']:checked").val();
        if (val == '1') {
            $('#langkahPencegahanGroup').show();
        } else {
            $('#langkahPencegahanGroup').hide();
        }
    }
    $(document).on('change', "input[name='pernah_terjadi']", toggleLangkahPencegahan);
    $(function() {
        toggleLangkahPencegahan();
    });
    // Select2 for tindakan_oleh
    $('.select2-tindakan-oleh').select2({
        placeholder: 'Pilih Tindakan Oleh',
        allowClear: true,
        width: 'resolve'
    });

$(function() {
    // Auto-fill pasien info if editing
    @if(isset($laporan) && $laporan->pasien)
        $('#no_rm').val('{{ $laporan->pasien->id }}');
        // Calculate umur from tanggal_lahir
        @if($laporan->pasien->tanggal_lahir)
            (function() {
                var birth = new Date('{{ $laporan->pasien->tanggal_lahir }}');
                var today = new Date();
                var umur = today.getFullYear() - birth.getFullYear();
                var m = today.getMonth() - birth.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
                    umur--;
                }
                $('#umur').val(umur + ' tahun');
            })();
        @else
            $('#umur').val('');
        @endif
        // Jenis kelamin
        var jk = '';
        @if($laporan->pasien->gender === 'L')
            jk = 'Laki-laki';
        @elseif($laporan->pasien->gender === 'P')
            jk = 'Perempuan';
        @else
            jk = '{{ $laporan->pasien->gender ?? '' }}';
        @endif
        $('#jenis_kelamin').val(jk);
    @endif
        // Select2 for akibat_insiden
    $('.select2-akibat-insiden').select2({
        placeholder: 'Pilih Akibat Insiden',
        allowClear: true,
        width: 'resolve'
    });
    // Select2 for penanggung_biaya
    $('.select2-penanggung').select2({
        placeholder: 'Pilih Penanggung Biaya',
        allowClear: true,
        width: 'resolve'
    });

    // Select2 for jenis_insiden
    $('.select2-jenis-insiden').select2({
        placeholder: 'Pilih Jenis Insiden',
        allowClear: true,
        width: 'resolve'
    });

        // Select2 for pertama_lapor
    $('.select2-pertama-lapor').select2({
        placeholder: 'Pilih Pertama Lapor',
        allowClear: true,
        width: 'resolve'
    });

        // Select2 for insiden_pada
    $('.select2-insiden-pada').select2({
        placeholder: 'Pilih Insiden Pada',
        allowClear: true,
        width: 'resolve'
    });
        // Select2 for jenis_pasien
    $('.select2-jenis-pasien').select2({
        placeholder: 'Pilih Jenis Pasien',
        allowClear: true,
        width: 'resolve'
    });

    // Select2 for pasien
    $('#pasien_id').select2({
        placeholder: 'Cari Pasien...',
        allowClear: true,
        ajax: {
            url: '{{ route('insiden.laporan_insiden.pasien-search') }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
                    page: params.page || 1
                };
            },
            processResults: function (data) {
                return {
                    results: data.data.map(function(item) {
                        // Pass extra data for selection
                        return {
                            id: item.id,
                            text: item.nama,
                            tanggal_lahir: item.tanggal_lahir,
                            gender: item.gender
                        };
                    }),
                    pagination: {
                        more: data.pagination && data.pagination.more
                    }
                };
            },
            cache: true
        },
        minimumInputLength: 2
    });

        // Select2 for spesialisasi_id (AJAX)
    $('.select2-spesialisasi').select2({
        placeholder: 'Pilih Spesialisasi',
        allowClear: true,
        ajax: {
            url: '/erm/spesialisasi-select2',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
                    page: params.page || 1
                };
            },
            processResults: function (data) {
                return {
                    results: data.data.map(function(item) {
                        return { id: item.id, text: item.nama };
                    }),
                    pagination: {
                        more: data.pagination && data.pagination.more
                    }
                };
            },
            cache: true
        },
        minimumInputLength: 1
    });

    // On pasien select, fill no_rm, umur, jenis_kelamin
    $('#pasien_id').on('select2:select', function(e) {
        var data = e.params.data;
        $('#no_rm').val(data.id);
        // Calculate umur from tanggal_lahir
        if (data.tanggal_lahir) {
            var birth = new Date(data.tanggal_lahir);
            var today = new Date();
            var umur = today.getFullYear() - birth.getFullYear();
            var m = today.getMonth() - birth.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
                umur--;
            }
            $('#umur').val(umur + ' tahun');
        } else {
            $('#umur').val('');
        }
        // Jenis kelamin
        var jk = '';
        if (data.gender === 'L') jk = 'Laki-laki';
        else if (data.gender === 'P') jk = 'Perempuan';
        else jk = data.gender || '';
        $('#jenis_kelamin').val(jk);
    });

        // Select2 for unit_penyebab (AJAX)
    $('.select2-unit-penyebab').select2({
        placeholder: 'Pilih Unit Penyebab',
        allowClear: true,
        ajax: {
            url: '/insiden/laporan_insiden/division-select2',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
                    page: params.page || 1
                };
            },
            processResults: function (data) {
                return {
                    results: data.data.map(function(item) {
                        return { id: item.id, text: item.name };
                    }),
                    pagination: {
                        more: data.pagination && data.pagination.more
                    }
                };
            },
            cache: true
        },
        minimumInputLength: 1
    });

    // On clear
    $('#pasien_id').on('select2:clear', function() {
        $('#no_rm').val('');
        $('#umur').val('');
        $('#jenis_kelamin').val('');
    });

    $('#formLaporanInsiden').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var isEdit = url.match(/\/[0-9]+$/); // if url ends with /{id}
        var ajaxMethod = isEdit ? 'PUT' : 'POST';
        $.ajax({
            url: url,
            type: 'POST',
            data: form.serialize() + (isEdit ? '&_method=PUT' : ''),
            success: function(res) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Data berhasil disimpan!',
                    timer: 1200,
                    showConfirmButton: false
                });
                setTimeout(function() {
                    window.location.href = "{{ route('insiden.laporan_insiden.index') }}";
                }, 1200);
            },
            error: function(xhr) {
                var errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : null;
                form.find('.text-danger').remove();
                var errorMsg = 'Terjadi kesalahan saat menyimpan data.';
                if (errors) {
                    errorMsg = Object.values(errors).map(function(errArr) { return errArr[0]; }).join('\n');
                    for (var key in errors) {
                        var input = form.find('[name="' + key + '"]');
                        input.after('<span class="text-danger">' + errors[key][0] + '</span>');
                    }
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: errorMsg
                });
            }
        });
    });
});
</script>
@endpush
