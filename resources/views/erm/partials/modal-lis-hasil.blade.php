<!-- Modal for HasilLis Results -->
<div class="modal fade" id="hasilLisModal" tabindex="-1" role="dialog" aria-labelledby="hasilLisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="hasilLisModalLabel">Detail Hasil LIS</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="hasilLisDetailTable">
                        <thead>
                            <tr>
                                <th style="width: 5%">No.</th>
                                <th style="width: 30%">Nama Pemeriksaan</th>
                                <th style="width: 15%">Hasil</th>
                                <th style="width: 10%">Flag</th>
                                <th style="width: 25%">Nilai Rujukan</th>
                                <th style="width: 15%">Satuan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
