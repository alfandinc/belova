<!-- Modal Edukasi Obat -->
<div class="modal fade" id="edukasiObatModal" tabindex="-1" role="dialog" aria-labelledby="edukasiObatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="edukasiObatModalLabel">Edukasi Obat kepada Pasien</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="simpan_etiket_label">
                            <label class="form-check-label" for="simpan_etiket_label">
                                Simpan obat dalam Etiket Label
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="simpan_suhu_kulkas">
                            <label class="form-check-label" for="simpan_suhu_kulkas">
                                Simpan di suhu kulkas
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="simpan_tempat_kering">
                            <label class="form-check-label" for="simpan_tempat_kering">
                                Simpan di tempat kering, suhu kamar
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="hindarkan_jangkauan_anak">
                            <label class="form-check-label" for="hindarkan_jangkauan_anak">
                                Hindarkan dari jangkauan anak
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Insulin : lihat brosur edukasi no</label>
                            <input type="text" class="form-control" id="insulin_brosur">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Inhalasi : lihat brosur edukasi no</label>
                            <input type="text" class="form-control" id="inhalasi_brosur">
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Apoteker :</label>
                            <select class="form-control select2" id="apoteker_id">
                                <!-- Will be populated via AJAX -->
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="btn-cetak-edukasi-pdf">Cetak</button>
                <button type="button" class="btn btn-primary" id="btn-selesai-edukasi">Selesai</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')

<script>
$(document).ready(function() {
    const edukasiDataUrl = "{{ url('/erm/edukasi-obat/__ID__/data') }}";
    const edukasiCheckboxes = ['simpan_etiket_label', 'simpan_suhu_kulkas', 'simpan_tempat_kering', 'hindarkan_jangkauan_anak'];

    // Opening the form: apoteker list, then what was saved for this visit (so Cetak does not overwrite it with an empty form)
    $('.btn-cetakedukasi').on('click', function() {
        $('#edukasiObatModal').modal('show');
        loadApotekers().then(loadEdukasi);
        const totalHarga = $('#total-harga').text().trim();
        $('#modal_total_pembayaran').text('Rp ' + totalHarga);
    });

    let apotekersLoaded = null;
    function loadApotekers() {
        if (!apotekersLoaded) {
            apotekersLoaded = $.get("{{ route('erm.get-apotekers') }}").then(function(data) {
                const selectElement = $('#apoteker_id').empty().append('<option value="">Pilih Apoteker</option>');
                data.forEach(function(apoteker) {
                    selectElement.append(new Option(apoteker.name, apoteker.id));
                });
                // The current user when they are an apoteker
                const currentUserId = "{{ Auth::id() }}";
                if (selectElement.find('option[value="' + currentUserId + '"]').length) {
                    selectElement.val(currentUserId);
                }
            }, function() {
                apotekersLoaded = null;
            });
        }
        return apotekersLoaded;
    }

    function loadEdukasi() {
        return $.get(edukasiDataUrl.replace('__ID__', encodeURIComponent($('#visitation_id').val()))).then(function(edukasi) {
            if (!edukasi) return;
            edukasiCheckboxes.forEach(function(id) { $('#' + id).prop('checked', !!Number(edukasi[id])); });
            $('#insulin_brosur').val(edukasi.insulin_brosur || '');
            $('#inhalasi_brosur').val(edukasi.inhalasi_brosur || '');
            if (edukasi.apoteker_id && $('#apoteker_id option[value="' + edukasi.apoteker_id + '"]').length) {
                $('#apoteker_id').val(String(edukasi.apoteker_id)).trigger('change');
            }
        });
    }

    function edukasiFormData() {
        const data = {
            _token: "{{ csrf_token() }}",
            visitation_id: $('#visitation_id').val(),
            insulin_brosur: $('#insulin_brosur').val() || null,
            inhalasi_brosur: $('#inhalasi_brosur').val() || null,
            apoteker_id: $('#apoteker_id').val(),
        };
        edukasiCheckboxes.forEach(function(id) { data[id] = $('#' + id).is(':checked') ? 1 : 0; });
        return data;
    }

    // Saves the form; resolves when saved
    function saveEdukasi() {
        const data = edukasiFormData();
        if (!data.apoteker_id) {
            Swal.fire({ icon: 'warning', title: 'Pilih apoteker', text: 'Silakan pilih apoteker terlebih dahulu.' });
            return $.Deferred().reject().promise();
        }
        return $.post("{{ route('edukasi.obat.store') }}", data).fail(function(err) {
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Edukasi obat gagal disimpan: ' + ((err.responseJSON && err.responseJSON.message) || 'Terjadi kesalahan') });
        });
    }

    $('#btn-selesai-edukasi').on('click', function() {
        const $btn = $(this).prop('disabled', true);
        saveEdukasi().done(function() {
            $('#edukasiObatModal').modal('hide');
            Swal.fire({ icon: 'success', title: 'Tersimpan', text: 'Edukasi obat disimpan.', timer: 1500, showConfirmButton: false });
        }).always(function() { $btn.prop('disabled', false); });
    });

    // Cetak: save, then the browser's print dialog straight away (no new tab)
    $('#btn-cetak-edukasi-pdf').on('click', function() {
        const $btn = $(this).prop('disabled', true);
        saveEdukasi().done(function() {
            $('#edukasiObatModal').modal('hide');
            const url = "{{ url('/erm/edukasi-obat') }}/" + encodeURIComponent($('#visitation_id').val()) + '/print';
            window.ErmPdfPreview.drop(url);
            window.ErmPdfPreview.printPdf(url, 'Edukasi Obat');
        }).always(function() { $btn.prop('disabled', false); });
    });
});
</script>

@endpush
