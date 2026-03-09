<!-- Modal: Old Notifications (Finance) -->
<div class="modal fade" id="modalOldNotificationsFinance" tabindex="-1" role="dialog" aria-labelledby="modalOldNotificationsFinanceLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalOldNotificationsFinanceLabel">Notifikasi Lama (Finance)</h5>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-mark-all-finance" style="margin-right:1rem;">Tandai semua sudah dibaca</button>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="old-fin-notifs-loading" style="display:none; text-align:center; padding:20px;">
                    <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>
                </div>
                <div id="old-fin-notifs-empty" style="display:none; text-align:center; color:#666;">Belum ada notifikasi lama.</div>
                <ul class="list-group" id="old-fin-notifs-list"></ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
