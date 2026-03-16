<div class="modal fade" id="modalScheduledMessages" tabindex="-1" role="dialog" aria-labelledby="modalScheduledMessagesLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalScheduledMessagesLabel">Scheduled Message List</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <small class="text-muted" id="scheduled-messages-summary">Memuat scheduled message...</small>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-refresh-scheduled-messages">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0" id="scheduled-messages-table">
                        <thead>
                            <tr>
                                <th>Pasien</th>
                                <th>No. Tujuan</th>
                                <th>Session</th>
                                <th>Jadwal Kirim</th>
                                <th>Status</th>
                                <th>Pesan</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>