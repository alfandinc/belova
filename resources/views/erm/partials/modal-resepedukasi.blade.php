<!-- Modal Edukasi Obat -->
<div class="modal fade" id="edukasiObatModal" tabindex="-1" role="dialog" aria-labelledby="edukasiObatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="edukasiObatModalLabel">EDUKASI OBAT Klinik Pratama</h5>
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
    // Handle Cetak Edukasi button click
    $('.btn-cetakedukasi').on('click', function() {
        // Show the modal
        $('#edukasiObatModal').modal('show');
        
        // Load apoteker options
        loadApotekers();
        
        // Update the total price in modal
        const totalHarga = $('#total-harga').text().trim();
        $('#modal_total_pembayaran').text('Rp ' + totalHarga);
    });

    // Function to load apotekers
    function loadApotekers() {
        // Only load if not already loaded
        if ($('#apoteker_id option').length <= 1) {
            $.ajax({
                url: "{{ route('erm.get-apotekers') }}",
                method: 'GET',
                success: function(data) {
                    const selectElement = $('#apoteker_id');
                    selectElement.empty();
                    selectElement.append('<option value="">Pilih Apoteker</option>');
                    
                    data.forEach(function(apoteker) {
                        selectElement.append(`<option value="${apoteker.id}">${apoteker.name}</option>`);
                    });
                    
                    // Select current user if they're an apoteker
                    const currentUserId = "{{ Auth::id() }}";
                    if (selectElement.find(`option[value="${currentUserId}"]`).length) {
                        selectElement.val(currentUserId);
                    }
                },
                error: function(err) {
                    console.error('Error loading apotekers:', err);
                }
            });
        }
    }

    // Handle Selesai button click
    $('#btn-selesai-edukasi').on('click', function() {
        const visitationId = $('#visitation_id').val();
        const formData = {
            visitation_id: visitationId,
            simpan_etiket_label: $('#simpan_etiket_label').is(':checked') ? 1 : 0,
            simpan_suhu_kulkas: $('#simpan_suhu_kulkas').is(':checked') ? 1 : 0,
            simpan_tempat_kering: $('#simpan_tempat_kering').is(':checked') ? 1 : 0,
            hindarkan_jangkauan_anak: $('#hindarkan_jangkauan_anak').is(':checked') ? 1 : 0,
            insulin_brosur: $('#insulin_brosur').val() || null,
            inhalasi_brosur: $('#inhalasi_brosur').val() || null,
            apoteker_id: $('#apoteker_id').val(),
        };
        
        if (!formData.apoteker_id) {
            alert('Silakan pilih Apoteker terlebih dahulu');
            return;
        }
        
        $.ajax({
            url: "{{ route('edukasi.obat.store') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                ...formData
            },
            success: function(response) {
                alert('Edukasi obat berhasil disimpan');
                $('#edukasiObatModal').modal('hide');
            },
            error: function(err) {
                console.error(err);
                alert('Gagal menyimpan edukasi obat: ' + (err.responseJSON?.message || 'Terjadi kesalahan'));
            }
        });
    });

    // Handle Cetak button click
    $('#btn-cetak-edukasi-pdf').on('click', function() {
        const visitationId = $('#visitation_id').val();
        const formData = {
            visitation_id: visitationId,
            simpan_etiket_label: $('#simpan_etiket_label').is(':checked') ? 1 : 0,
            simpan_suhu_kulkas: $('#simpan_suhu_kulkas').is(':checked') ? 1 : 0,
            simpan_tempat_kering: $('#simpan_tempat_kering').is(':checked') ? 1 : 0,
            hindarkan_jangkauan_anak: $('#hindarkan_jangkauan_anak').is(':checked') ? 1 : 0,
            insulin_brosur: $('#insulin_brosur').val() || null,
            inhalasi_brosur: $('#inhalasi_brosur').val() || null,
            apoteker_id: $('#apoteker_id').val(),
        };
        
        if (!formData.apoteker_id) {
            alert('Silakan pilih Apoteker terlebih dahulu');
            return;
        }
        
        $.ajax({
            url: "{{ route('edukasi.obat.store') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                ...formData
            },
            success: function(response) {
                // Open print page in new tab
                window.open(`/erm/edukasi-obat/${visitationId}/print`, '_blank');
            },
            error: function(err) {
                console.error(err);
                alert('Gagal menyimpan dan mencetak edukasi obat: ' + (err.responseJSON?.message || 'Terjadi kesalahan'));
            }
        });
    });
});
</script>

@endpush