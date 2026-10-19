<!-- Modal for HasilLis Results -->
<style>
    /* Long results scroll inside the modal; the column titles stay on top */
    #hasilLisModal .modal-body { padding-top: 0; }
    #hasilLisModal #hasilLisDetailTable thead th { position: sticky; top: 0; z-index: 2; background: var(--sb-tile, #f1f5f9); box-shadow: 0 1px 0 var(--sb-line, #e2e8f0); }
</style>
<div class="modal fade" id="hasilLisModal" tabindex="-1" role="dialog" aria-labelledby="hasilLisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="hasilLisModalLabel">Detail Hasil LIS</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="hasil-lis-scroll">
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
