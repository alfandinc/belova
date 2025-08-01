<!-- Modal for Adding HasilLis Results -->
<div class="modal fade" id="addLisHasilModal" tabindex="-1" role="dialog" aria-labelledby="addLisHasilModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addLisHasilModalLabel">Tambah Hasil LIS</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addLisHasilForm">
                    <input type="hidden" id="lis_visitation_id" name="visitation_id" value="{{ $visitation->id }}">
                    
                    <!-- Kode LIS is auto-generated, so it's hidden -->
                    <input type="hidden" id="kode_lis" name="kode_lis">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="header">Header</label>
                                <input type="text" class="form-control" id="header" name="header" placeholder="Header">
                                <small class="text-muted">Akan terisi otomatis berdasarkan kategori test</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sub_header">Sub Header</label>
                                <input type="text" class="form-control" id="sub_header" name="sub_header" placeholder="Sub Header">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="nama_test">Nama Test <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="nama_test" name="nama_test" required>
                                    <option value="">Pilih Test</option>
                                    @foreach($labTests as $test)
                                        <option value="{{ $test->nama }}" data-kategori="{{ $test->labKategori->nama ?? '' }}" data-test-id="{{ $test->id }}">{{ $test->nama }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="nama_test_error">Nama test harus diisi</div>
                                <small class="text-muted">Ketik untuk mencari atau menambah test baru</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="hasil">Hasil</label>
                                <input type="text" class="form-control" id="hasil" name="hasil" placeholder="Hasil">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="flag">Flag</label>
                                <select class="form-control" id="flag" name="flag">
                                    <option value="">Pilih Flag</option>
                                    <option value="H">H (High)</option>
                                    <option value="L">L (Low)</option>
                                    <option value="N">N (Normal)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="metode">Metode</label>
                                <input type="text" class="form-control" id="metode" name="metode" placeholder="Metode">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nilai_rujukan">Nilai Rujukan</label>
                                <input type="text" class="form-control" id="nilai_rujukan" name="nilai_rujukan" placeholder="Nilai Rujukan">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="satuan">Satuan</label>
                                <input type="text" class="form-control" id="satuan" name="satuan" placeholder="Satuan">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveLisHasil">Simpan</button>
            </div>
        </div>
    </div>
</div>
