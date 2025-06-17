<!-- Upload Lab Hasil Modal -->
<div class="modal fade" id="uploadLabHasilModal" tabindex="-1" role="dialog" aria-labelledby="uploadLabHasilModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadLabHasilModalLabel">Upload Hasil Laboratorium</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="uploadLabHasilForm">
                    <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="namaPemeriksaan">Nama Pemeriksaan</label>
                            <input type="text" class="form-control" id="namaPemeriksaan" name="nama_pemeriksaan" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="tanggalPemeriksaan">Tanggal Pemeriksaan</label>
                            <input type="date" class="form-control" id="tanggalPemeriksaan" name="tanggal_pemeriksaan" required value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="asalLab">Asal Laboratorium</label>
                            <select class="form-control" id="asalLab" name="asal_lab" required>
                                <option value="internal">Lab Internal</option>
                                <option value="eksternal">Lab Eksternal</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="dokterPemeriksa">Dokter Pemeriksa</label>
                            <input type="text" class="form-control" id="dokterPemeriksa" name="dokter" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="catatan">Catatan</label>
                        <textarea class="form-control" id="catatan" name="catatan" rows="2"></textarea>
                    </div>
                    
                    <!-- File upload for external lab -->
                    <div class="form-group" id="hasilFileGroup" style="display: none;">
                        <label for="hasilFile">File Hasil Lab (PDF)</label>
                        <input type="file" class="form-control-file" id="hasilFile" name="hasil_file" accept="application/pdf">
                        <small class="form-text text-muted">Upload file PDF hasil laboratorium (max: 10MB)</small>
                    </div>
                    
                    <!-- Detailed result input for internal lab -->
                    <div id="hasilDetailGroup">
                        <h5>Detail Hasil Lab</h5>
                        <button type="button" class="btn btn-sm btn-primary mb-3" id="addHasilDetail">
                            <i class="fas fa-plus"></i> Tambah Hasil
                        </button>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered" id="hasilDetailTable">
                                <thead>
                                    <tr>
                                        <th>Nama Test</th>
                                        <th>Flag</th>
                                        <th>Hasil</th>
                                        <th>Satuan</th>
                                        <th>Nilai Rujukan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Detail rows will be added here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" form="uploadLabHasilForm" class="btn btn-primary">Simpan</button>
            </div>
        </div>
    </div>
</div>