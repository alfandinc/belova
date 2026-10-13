{{-- Daily Journal modal. Fetched on first click of a "js-open-journal" element (see layouts/shared/sidebar). --}}
<style>
#dailyJournalModal .dj-dialog { max-width: 800px; }
#dailyJournalModal .modal-body { padding: 14px 16px 16px; }
.dj-tabs { display: flex; gap: 4px; margin-bottom: 12px; border-bottom: 1px solid rgba(128,128,128,.2); }
.dj-tab { border: 0; background: transparent; color: inherit; padding: 8px 12px; font-size: 13px; font-weight: 600; opacity: .65; border-bottom: 2px solid transparent; margin-bottom: -1px; }
.dj-tab:hover { opacity: 1; }
.dj-tab.active { opacity: 1; color: #1e88e5; border-bottom-color: #1e88e5; }
.dj-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 12px; }
.dj-nav { display: flex; align-items: center; gap: 4px; flex: 1; min-width: 200px; }
.dj-nav .btn { min-height: 34px; }
.dj-label { flex: 1; min-width: 0; font-weight: 600; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dj-search { max-width: 180px; height: 34px; font-size: 13px; }
.dj-add { display: flex; gap: 8px; }
.dj-add .form-control { min-height: 42px; font-size: 15px; }
.dj-add .btn { min-height: 42px; white-space: nowrap; }
.dj-quick { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-top: 8px; }
.dj-quick-date { border: 1px solid rgba(128,128,128,.3); background: transparent; color: inherit; border-radius: 999px; padding: 2px 10px; font-size: 12px; font-weight: 600; }
.dj-quick-date.active { background: rgba(30,136,229,.14); border-color: #1e88e5; color: #1e88e5; }
.dj-quick-sep { flex: 1; }
.dj-toggle-more { font-size: 12px; padding: 2px 0; }
.dj-add-more { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; margin-top: 8px; }
.dj-add-more .dj-wide, .dj-edit .dj-wide { grid-column: 1 / -1; }
.dj-field label { display: block; font-size: 11px; font-weight: 600; opacity: .75; margin-bottom: 2px; }
.dj-field textarea { resize: vertical; min-height: 60px; }
.dj-chips { display: flex; flex-wrap: wrap; gap: 6px; margin: 12px 0 8px; }
.dj-chip { border: 1px solid rgba(128,128,128,.35); background: transparent; color: inherit; border-radius: 999px; padding: 4px 12px; font-size: 12px; font-weight: 600; line-height: 1.6; }
.dj-chip b { margin-left: 4px; opacity: .7; }
.dj-chip.active { background: #1e88e5; border-color: #1e88e5; color: #fff; }
.dj-chip.active b { opacity: 1; }
.dj-report { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding: 8px 12px; margin-bottom: 8px; border-radius: 12px; background: rgba(30,136,229,.08); font-size: 12.5px; }
.dj-report.is-sent { background: rgba(32,201,151,.1); }
.dj-list { max-height: calc(100vh - 420px); min-height: 160px; overflow-y: auto; -webkit-overflow-scrolling: touch; padding: 2px 4px 8px 2px; }
.dj-group { display: flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; opacity: .65; padding: 12px 2px 6px; }
.dj-group::after { content: ''; flex: 1; height: 1px; background: rgba(128,128,128,.22); }
.dj-group .dj-group-count { font-weight: 600; opacity: .8; text-transform: none; letter-spacing: 0; }
.dj-group.is-warn { color: #e67e22; opacity: 1; }
.dj-task { --dj-c: #1e88e5; --dj-soft: rgba(30,136,229,.12); position: relative; display: flex; align-items: center; gap: 12px; padding: 12px 12px 12px 16px; margin-bottom: 8px; border-radius: 14px; border: 1px solid rgba(128,128,128,.16); background: rgba(128,128,128,.045); overflow: hidden; transition: box-shadow .15s, background .15s; }
.dj-task::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--dj-c); }
.dj-task:hover { background: rgba(128,128,128,.08); box-shadow: 0 6px 18px rgba(15,23,42,.08); }
.dj-task.is-in_progress { --dj-c: #f39c12; --dj-soft: rgba(243,156,18,.16); }
.dj-task.is-done { --dj-c: #20c997; --dj-soft: rgba(32,201,151,.15); }
.dj-task.is-skipped { --dj-c: #95a5a6; --dj-soft: rgba(149,165,166,.18); }
.dj-task.is-overdue { border-color: rgba(231,76,60,.45); }
.dj-check { flex: none; width: 28px; height: 28px; border-radius: 50%; border: 2px solid var(--dj-c); background: transparent; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; padding: 0; transition: background .15s, transform .15s; }
.dj-check:hover:not(:disabled) { background: var(--dj-soft); transform: scale(1.08); }
.dj-check:disabled { cursor: default; }
.dj-task.is-done .dj-check { background: #20c997; border-color: #20c997; }
.dj-task.is-skipped .dj-check { background: #95a5a6; border-color: #95a5a6; }
.dj-main { flex: 1; min-width: 0; }
.dj-title { font-size: 14.5px; font-weight: 600; line-height: 1.35; word-break: break-word; }
.dj-task.is-done .dj-title, .dj-task.is-skipped .dj-title { text-decoration: line-through; opacity: .5; font-weight: 500; }
.dj-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; font-size: 11.5px; margin-top: 5px; }
.dj-meta-item { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 999px; background: rgba(128,128,128,.12); opacity: .9; }
.dj-meta-item.is-from { background: rgba(142,68,173,.14); color: #8e44ad; opacity: 1; }
.dj-meta-item.is-repeat { background: rgba(30,136,229,.12); color: #1e88e5; opacity: 1; }
.dj-move { border: 0; background: rgba(243,156,18,.16); color: #e67e22; border-radius: 999px; padding: 2px 9px; font-size: 11.5px; font-weight: 700; }
.dj-move:hover { background: rgba(243,156,18,.28); }
.dj-note { font-size: 12.5px; opacity: .75; margin-top: 5px; white-space: pre-wrap; word-break: break-word; }
.dj-badge { display: inline-flex; align-items: center; gap: 4px; border-radius: 999px; padding: 2px 8px; font-size: 11px; font-weight: 700; }
.dj-badge.overdue { background: #e74c3c; color: #fff; }
.dj-badge.due-today { background: #f39c12; color: #fff; }
.dj-badge.deadline { background: rgba(231,76,60,.12); color: #e74c3c; }
.dj-side { flex: none; display: flex; align-items: center; gap: 2px; }
.dj-status { -webkit-appearance: none; -moz-appearance: none; appearance: none; width: auto; height: 30px; padding: 2px 26px 2px 12px; border: 0; border-radius: 999px; font-size: 12px; font-weight: 700; color: var(--dj-c); background-color: var(--dj-soft); background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' fill='none' stroke='%23888' stroke-width='1.6' stroke-linecap='round'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; cursor: pointer; }
.dj-status:focus { box-shadow: 0 0 0 2px var(--dj-soft); outline: none; }
.dj-status option { color: #111827; background: #fff; }
.dj-status-pill { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; color: var(--dj-c); background: var(--dj-soft); }
.dj-icon-btn { width: 32px; height: 32px; padding: 0; border: 0; background: transparent; color: inherit; opacity: .55; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; font-size: 13px; transition: opacity .15s, background .15s, color .15s; }
.dj-icon-btn:hover:not(:disabled) { opacity: 1; background: rgba(128,128,128,.14); }
.dj-icon-btn.is-danger:hover:not(:disabled) { color: #e74c3c; background: rgba(231,76,60,.12); }
.dj-icon-btn:disabled { opacity: .2; cursor: not-allowed; }
.dj-task.is-editing { display: block; padding: 14px; }
.dj-edit { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
.dj-edit-actions { grid-column: 1 / -1; display: flex; justify-content: flex-end; gap: 6px; }
.dj-member { border: 1px solid rgba(128,128,128,.18); border-radius: 14px; margin-bottom: 10px; overflow: hidden; }
.dj-member-head { display: flex; align-items: center; gap: 12px; padding: 12px 14px; cursor: pointer; background: rgba(128,128,128,.045); }
.dj-member-head:hover { background: rgba(128,128,128,.08); }
.dj-avatar { flex: none; width: 38px; height: 38px; border-radius: 12px; background: linear-gradient(135deg, #1e88e5, #64b5f6); color: #fff; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; justify-content: center; }
.dj-member-info { flex: 1; min-width: 0; }
.dj-member-name { font-weight: 700; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dj-member-sub { font-size: 11.5px; opacity: .7; }
.dj-progress { height: 6px; border-radius: 999px; background: rgba(128,128,128,.18); overflow: hidden; margin-top: 6px; }
.dj-progress > span { display: block; height: 100%; background: #20c997; border-radius: 999px; }
.dj-member-stats { flex: none; display: flex; align-items: center; gap: 6px; font-size: 12px; }
.dj-member-body { padding: 10px 12px 4px; }
.dj-member-body .dj-task { margin-bottom: 6px; padding: 9px 10px 9px 14px; }
.dj-recurring { display: flex; align-items: center; gap: 12px; padding: 12px 14px; margin-bottom: 8px; border-radius: 14px; border: 1px solid rgba(128,128,128,.16); background: rgba(128,128,128,.045); }
.dj-recurring-icon { flex: none; width: 34px; height: 34px; border-radius: 10px; background: rgba(30,136,229,.12); color: #1e88e5; display: inline-flex; align-items: center; justify-content: center; }
.dj-empty { text-align: center; padding: 40px 12px; opacity: .7; }
.dj-empty-icon { font-size: 34px; margin-bottom: 6px; }
.dj-error { color: #e74c3c; font-size: 12px; margin-top: 6px; }
.dj-toast { position: absolute; left: 50%; bottom: 14px; transform: translateX(-50%); background: rgba(17,24,39,.92); color: #fff; padding: 6px 14px; border-radius: 999px; font-size: 12px; font-weight: 600; pointer-events: none; opacity: 0; transition: opacity .2s; z-index: 5; max-width: 90%; text-align: center; }
.dj-toast.show { opacity: 1; }
.dj-busy { position: relative; }
.dj-busy::after { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 2px; background: linear-gradient(90deg, transparent, #1e88e5, transparent); background-size: 50% 100%; background-repeat: no-repeat; animation: djBar .9s linear infinite; }
@keyframes djBar { from { background-position: -50% 0; } to { background-position: 150% 0; } }
/* Snappier open/close than Bootstrap's default */
#dailyJournalModal.fade .modal-dialog { transition-duration: .12s; }
#dailyJournalModal.fade { transition-duration: .12s; }
.dj-assign { display: flex; gap: 6px; padding: 10px 12px; border-top: 1px solid rgba(128,128,128,.16); }
.dj-assign .form-control { height: 36px; font-size: 13.5px; }
.dj-assign input[type="date"] { max-width: 150px; }
.dj-hint { font-size: 12px; opacity: .7; margin-bottom: 10px; }
.dj-skeleton { height: 58px; border-radius: 14px; margin-bottom: 8px; background: linear-gradient(90deg, rgba(128,128,128,.08), rgba(128,128,128,.16), rgba(128,128,128,.08)); background-size: 200% 100%; animation: djSk 1.2s ease-in-out infinite; }
@keyframes djSk { from { background-position: 100% 0; } to { background-position: -100% 0; } }
@media (max-width: 767.98px) {
    #dailyJournalModal .modal-dialog.dj-dialog { margin: 0; width: 100%; max-width: 100%; height: 100%; }
    #dailyJournalModal .modal-content { min-height: 100%; border-radius: 0; border: 0; }
    .dj-list { max-height: none; }
    .dj-add { flex-wrap: wrap; }
    .dj-add-more, .dj-edit { grid-template-columns: 1fr 1fr; }
    .dj-search { max-width: none; flex: 1 1 100%; }
    .dj-task { align-items: flex-start; flex-wrap: wrap; }
    .dj-side { width: 100%; padding-left: 40px; }
    .dj-member-stats { display: none; }
    body.dj-open #belovaChatWidget { display: none !important; }
}
@media (max-width: 767.98px) { .dj-assign { flex-wrap: wrap; } .dj-assign input[type="date"] { max-width: none; flex: 1; } }
/* Task table: fixed columns so every row lines up */
.dj-table { width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 0; font-size: 13px; }
.dj-table th { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; opacity: .55; padding: 6px 8px; border-bottom: 1px solid rgba(128,128,128,.25); text-align: left; white-space: nowrap; }
.dj-table td { padding: 8px; border-bottom: 1px solid rgba(128,128,128,.14); vertical-align: middle; }
.dj-table .c-check { width: 38px; padding-right: 0; }
.dj-table .c-date { width: 78px; white-space: nowrap; }
.dj-table .c-dl { width: 112px; white-space: nowrap; }
.dj-table .c-status { width: 132px; }
.dj-table .c-act { width: 84px; text-align: right; white-space: nowrap; }
.dj-row { --dj-c: #1e88e5; --dj-soft: rgba(30,136,229,.12); }
.dj-row.is-in_progress { --dj-c: #f39c12; --dj-soft: rgba(243,156,18,.16); }
.dj-row.is-done { --dj-c: #20c997; --dj-soft: rgba(32,201,151,.15); }
.dj-row.is-skipped { --dj-c: #95a5a6; --dj-soft: rgba(149,165,166,.18); }
.dj-row:hover td { background: rgba(128,128,128,.05); }
.dj-row .dj-check { width: 22px; height: 22px; font-size: 10px; border-width: 2px; }
.dj-row.is-done .dj-check { background: #20c997; border-color: #20c997; }
.dj-row.is-skipped .dj-check { background: #95a5a6; border-color: #95a5a6; }
.dj-ttl { font-weight: 600; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dj-row.is-done .dj-ttl, .dj-row.is-skipped .dj-ttl { text-decoration: line-through; opacity: .5; font-weight: 500; }
.dj-sub { font-size: 11.5px; opacity: .62; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: 1px; }
.dj-sub .is-from { color: #8e44ad; font-weight: 600; }
.dj-muted { opacity: .4; }
.dj-date-carried { color: #e67e22; font-weight: 700; }
.dj-dl-over { color: #e74c3c; font-weight: 700; }
.dj-dl-today { color: #e67e22; font-weight: 700; }
.dj-table .dj-status { width: 100%; height: 28px; font-size: 11.5px; padding-left: 10px; }
.dj-table .dj-status-pill { padding: 3px 10px; font-size: 11.5px; }
.dj-table .dj-icon-btn { width: 26px; height: 26px; font-size: 12px; }
.dj-group-row td { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; opacity: .65; padding: 14px 8px 4px; border-bottom: 0; background: transparent !important; }
.dj-group-row.is-warn td { color: #e67e22; opacity: 1; }
.dj-edit-row td { background: rgba(30,136,229,.05) !important; padding: 12px; }
.dj-member-body { padding: 2px 8px 6px; }
@media (max-width: 767.98px) {
    .dj-table .c-date { display: none; }
    .dj-table .c-dl { width: 74px; }
    .dj-table .c-status { width: 100px; }
    .dj-table .c-act { width: 58px; }
    .dj-table .c-check { width: 30px; }
}
</style>

<div class="modal fade" id="dailyJournalModal" tabindex="-1" role="dialog" aria-labelledby="dailyJournalModalLabel" aria-hidden="true">
    <div class="modal-dialog dj-dialog" role="document">
        <div class="modal-content" style="position: relative;">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="dailyJournalModalLabel"><i class="fas fa-book-open mr-1"></i> Daily Journal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="dj-tabs" role="tablist">
                    <button type="button" class="dj-tab active" data-dj-tab="mine">Task Saya</button>
                    <button type="button" class="dj-tab d-none" data-dj-tab="team">Tim</button>
                    <button type="button" class="dj-tab" data-dj-tab="recurring"><i class="fas fa-redo-alt mr-1" style="font-size:11px"></i>Berulang</button>
                </div>

                {{-- Own tasks only; tasks for the team are given from their card in the Tim tab --}}
                <form id="djAddForm" autocomplete="off">
                    <div class="dj-add">
                        <input type="text" class="form-control" name="title" maxlength="120" placeholder="Tulis task baru, lalu Enter..." required>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i><span class="d-none d-sm-inline ml-1">Tambah</span></button>
                    </div>
                    <div class="dj-quick">
                        <button type="button" class="dj-quick-date" data-dj-quick="0">Hari ini</button>
                        <button type="button" class="dj-quick-date" data-dj-quick="1">Besok</button>
                        <button type="button" class="dj-quick-date" data-dj-quick="monday">Senin depan</button>
                        <span class="dj-quick-sep"></span>
                        <button type="button" class="btn btn-link dj-toggle-more" id="djToggleMore"><i class="fas fa-sliders-h mr-1"></i>Detail</button>
                    </div>
                    <div class="dj-add-more d-none" id="djAddMore">
                        <div class="dj-field">
                            <label for="djAddDate">Tanggal</label>
                            <input type="date" class="form-control form-control-sm" id="djAddDate" name="task_date">
                        </div>
                        <div class="dj-field">
                            <label for="djAddTime">Jam</label>
                            <input type="time" class="form-control form-control-sm" id="djAddTime" name="scheduled_time">
                        </div>
                        <div class="dj-field">
                            <label for="djAddRepeat">Ulangi</label>
                            <select class="form-control form-control-sm" id="djAddRepeat" name="repeat">
                                <option value="">Tidak</option>
                                <option value="daily">Setiap hari</option>
                                <option value="weekdays">Hari kerja (Sen–Jum)</option>
                                <option value="weekly">Setiap minggu</option>
                                <option value="monthly">Setiap bulan</option>
                            </select>
                        </div>
                        <div class="dj-field" id="djDeadlineField">
                            <label for="djAddDeadline">Deadline</label>
                            <input type="date" class="form-control form-control-sm" id="djAddDeadline" name="deadline_date">
                        </div>
                        <div class="dj-field d-none" id="djRepeatUntilField">
                            <label for="djAddRepeatUntil">Ulangi sampai</label>
                            <input type="date" class="form-control form-control-sm" id="djAddRepeatUntil" name="repeat_until" title="Kosongkan jika tanpa batas">
                        </div>
                        <div class="dj-field dj-wide">
                            <label for="djAddNote">Catatan</label>
                            <textarea class="form-control form-control-sm" id="djAddNote" name="note" maxlength="2000" rows="2" placeholder="Opsional"></textarea>
                        </div>
                    </div>
                    <div class="dj-error d-none" id="djAddError"></div>
                </form>

                <div class="dj-hint d-none" id="djTeamHint"><i class="fas fa-info-circle mr-1"></i>Klik <b>+</b> di kartu anggota untuk memberi task.</div>

                <div class="dj-toolbar mt-3" id="djToolbar">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Periode">
                        <button type="button" class="btn btn-outline-primary active" data-dj-period="day">Hari</button>
                        <button type="button" class="btn btn-outline-primary" data-dj-period="week">Minggu</button>
                        <button type="button" class="btn btn-outline-primary" data-dj-period="month">Bulan</button>
                    </div>
                    <div class="dj-nav">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-dj-step="-1" aria-label="Sebelumnya"><i class="fas fa-chevron-left"></i></button>
                        <span class="dj-label" id="djLabel">-</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-dj-step="1" aria-label="Berikutnya"><i class="fas fa-chevron-right"></i></button>
                        <button type="button" class="btn btn-sm btn-link d-none" id="djToday">Hari ini</button>
                    </div>
                    <input type="search" class="form-control dj-search" id="djSearch" placeholder="Cari task...">
                </div>

                <div id="djChips" class="dj-chips"></div>
                <div id="djReport"></div>
                <div class="dj-list" id="djList"></div>
            </div>
            <div class="dj-toast" id="djToast"></div>
        </div>
    </div>
</div>

<script>
function initBelovaDailyJournal() {
    if (!window.jQuery || window.belovaOpenJournal) return;
    var $ = window.jQuery;
    var $modal = $('#dailyJournalModal');
    var $list = $('#djList');
    var endpoints = {
        tasks: @json(route('daily-journal.tasks')),
        team: @json(route('daily-journal.team')),
        teamTasksTemplate: @json(url('/daily-journal/team/__ID__/tasks')),
        store: @json(route('daily-journal.store')),
        report: @json(route('daily-journal.report')),
        recurrences: @json(route('daily-journal.recurrences')),
        taskTemplate: @json(url('/daily-journal/__ID__')),
        recurrenceTemplate: @json(url('/daily-journal/recurrences/__ID__')),
    };
    var csrf = $('meta[name="csrf-token"]').attr('content');
    var STATUS = [
        { id: 'todo', label: 'Belum mulai' },
        { id: 'in_progress', label: 'Dikerjakan' },
        { id: 'done', label: 'Selesai' },
        { id: 'skipped', label: 'Dilewati' }
    ];
    var STATUS_LABEL = {};
    STATUS.forEach(function (s) { STATUS_LABEL[s.id] = s.label; });
    var OPEN = { todo: true, in_progress: true };

    var state = {
        tab: 'mine', period: 'day', date: todayStr(), status: '', search: '',
        data: null, team: null, recurring: null, canTeam: false,
        // Responses per tab/period/date: shown instantly when coming back, refreshed in the background
        cache: {},
        editingId: null, openMembers: {}, memberTasks: {}, assigning: null,
        request: null, quickDate: null
    };

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function toStr(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function todayStr() { return toStr(new Date()); }
    function parse(s) { var p = String(s).split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
    function addDays(s, n) { var d = parse(s); d.setDate(d.getDate() + n); return toStr(d); }
    function esc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
        });
    }
    function fmtDate(s) {
        if (!s) return '';
        var d = parse(s);
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return pad(d.getDate()) + ' ' + months[d.getMonth()] + (d.getFullYear() !== new Date().getFullYear() ? ' ' + d.getFullYear() : '');
    }
    function fmtDay(s) {
        var days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        if (s === todayStr()) return 'Hari ini · ' + fmtDate(s);
        if (s === addDays(todayStr(), 1)) return 'Besok · ' + fmtDate(s);
        return days[parse(s).getDay()] + ', ' + fmtDate(s);
    }
    function initials(name) {
        return String(name || '').split(/\s+/).filter(Boolean).slice(0, 2).map(function (p) { return p.charAt(0).toUpperCase(); }).join('') || '?';
    }
    function nextMonday() {
        var d = new Date();
        d.setDate(d.getDate() + ((8 - d.getDay()) % 7 || 7));
        return toStr(d);
    }
    function tpl(template, id) { return template.replace('__ID__', id); }
    function cacheKey() { return state.tab + '|' + (state.tab === 'recurring' ? '' : state.period + '|' + state.date); }

    var toastTimer = null;
    function toast(text) {
        var el = document.getElementById('djToast');
        el.textContent = text;
        el.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { el.classList.remove('show'); }, 2200);
    }
    function errorText(xhr, fallback) {
        var r = xhr && xhr.responseJSON;
        if (r && r.errors) {
            var first = Object.keys(r.errors)[0];
            if (first && r.errors[first][0]) return r.errors[first][0];
        }
        return (r && r.message) || fallback || 'Terjadi kesalahan, coba lagi.';
    }
    function send(method, url, data) {
        return $.ajax({
            url: url,
            type: method === 'GET' ? 'GET' : 'POST',
            data: method === 'GET' || method === 'POST' ? data : $.extend({ _method: method }, data),
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        });
    }

    // ---------------------------------------------------------------- loading
    function applyData(tab, data) {
        if (tab === 'team') state.team = data;
        else if (tab === 'recurring') state.recurring = data.data;
        else {
            state.data = data;
            setTeamAvailable(!!data.can_team);
        }
    }

    // Cached view first (instant), then the fresh one; a thin bar shows the refresh
    function load() {
        if (state.request) state.request.abort();
        var tab = state.tab, key = cacheKey();
        var cached = state.cache[key];
        if (cached) {
            applyData(tab, cached);
        } else if (tab === 'mine') {
            state.data = null;
        } else if (tab === 'team') {
            state.team = null;
        }
        render();
        $list.addClass('dj-busy');

        var url = tab === 'team' ? endpoints.team : (tab === 'recurring' ? endpoints.recurrences : endpoints.tasks);
        var params = tab === 'recurring' ? {} : { period: state.period, date: state.date };

        state.request = send('GET', url, params)
            .done(function (data) {
                state.cache[key] = data;
                if (cacheKey() !== key) return;
                applyData(tab, data);
                if (tab === 'team') {
                    state.memberTasks = {};
                    Object.keys(state.openMembers).forEach(function (id) { if (state.openMembers[id]) loadMemberTasks(id); });
                }
                render();
            })
            .fail(function (xhr, textStatus) {
                if (textStatus === 'abort' || cached) return;
                $list.html('<div class="dj-empty">' + esc(errorText(xhr, 'Gagal memuat.')) + ' <a href="#" data-dj-retry>Coba lagi</a></div>');
            })
            .always(function () {
                state.request = null;
                $list.removeClass('dj-busy');
            });
    }

    // Any change makes other cached periods stale; the current one is kept up to date in place
    function invalidateOthers() {
        var keep = cacheKey();
        Object.keys(state.cache).forEach(function (k) { if (k !== keep) delete state.cache[k]; });
    }

    function setTeamAvailable(available) {
        state.canTeam = available;
        $('[data-dj-tab="team"]').toggleClass('d-none', !available);
    }

    function defaultAddDate() {
        if (state.quickDate) return state.quickDate;
        var d = state.data;
        if (state.period === 'day' || !d) return state.date;
        var t = todayStr();
        return t >= d.start && t <= d.end ? t : d.start;
    }

    function syncQuickDates() {
        var current = $('#djAddDate').val() || defaultAddDate();
        var map = { '0': todayStr(), '1': addDays(todayStr(), 1), monday: nextMonday() };
        $('[data-dj-quick]').each(function () {
            $(this).toggleClass('active', map[this.getAttribute('data-dj-quick')] === current);
        });
    }

    // ---------------------------------------------------------------- local list updates
    function sortTasks(tasks) {
        tasks.sort(function (a, b) {
            var ca = OPEN[a.status] ? 0 : 1, cb = OPEN[b.status] ? 0 : 1;
            if (ca !== cb) return ca - cb;
            if (a.task_date !== b.task_date) return a.task_date < b.task_date ? -1 : 1;
            var ta = a.scheduled_time || '99', tb = b.scheduled_time || '99';
            if (ta !== tb) return ta < tb ? -1 : 1;
            return a.id - b.id;
        });
    }

    function inView(t) {
        var d = state.data;
        return !!d && (t.carried || (t.task_date >= d.start && t.task_date <= d.end));
    }

    function upsertTask(t) {
        var d = state.data;
        if (!d) return;
        d.tasks = d.tasks.filter(function (x) { return x.id !== t.id; });
        if (inView(t)) d.tasks.push(t);
        sortTasks(d.tasks);
        state.cache[cacheKey()] = d;
        invalidateOthers();
    }

    function removeTask(id) {
        if (state.data) state.data.tasks = state.data.tasks.filter(function (x) { return x.id !== id; });
        Object.keys(state.memberTasks).forEach(function (mid) {
            if (Array.isArray(state.memberTasks[mid])) state.memberTasks[mid] = state.memberTasks[mid].filter(function (x) { return x.id !== id; });
        });
        invalidateOthers();
    }

    // ---------------------------------------------------------------- render
    function render() {
        $('[data-dj-tab]').each(function () { $(this).toggleClass('active', this.getAttribute('data-dj-tab') === state.tab); });
        $('#djAddForm').toggleClass('d-none', state.tab !== 'mine');
        $('#djTeamHint').toggleClass('d-none', state.tab !== 'team');
        $('#djToolbar').toggleClass('d-none', state.tab === 'recurring');
        $('#djSearch').attr('placeholder', state.tab === 'team' ? 'Cari nama...' : 'Cari task...');
        $('[data-dj-period]').each(function () { $(this).toggleClass('active', this.getAttribute('data-dj-period') === state.period); });
        if (!$('#djAddMore').is(':visible') || !$('#djAddDate').val()) $('#djAddDate').val(defaultAddDate());
        syncQuickDates();

        // Background refreshes re-render the list: keep whatever is being typed in an inline form
        // Keyed by the task / member the form belongs to, so one form's text never lands in another
        function fieldKey(el) {
            var $owner = $(el).closest('[data-dj-id], [data-dj-member]');
            return ($owner.attr('data-dj-id') ? 't' + $owner.attr('data-dj-id') : 'm' + $owner.attr('data-dj-member')) + ':' + el.name;
        }
        var typed = {};
        $list.find('form input, form textarea').each(function () {
            if (this.name) typed[fieldKey(this)] = this.value;
        });
        var active = document.activeElement;
        var focused = active && active.name && $list[0].contains(active) ? fieldKey(active) : null;

        $('#djChips, #djReport').empty();
        if (state.tab === 'team') renderTeam();
        else if (state.tab === 'recurring') renderRecurring();
        else renderMine();

        $list.find('form input, form textarea').each(function () {
            var key = this.name ? fieldKey(this) : null;
            if (key && Object.prototype.hasOwnProperty.call(typed, key)) this.value = typed[key];
            if (key && key === focused) this.focus();
        });
    }

    function skeleton(n) {
        return new Array(n + 1).join('<div class="dj-skeleton"></div>');
    }

    function matchesSearch(t) {
        if (!state.search) return true;
        var q = state.search.toLowerCase();
        return (t.title + ' ' + (t.note || '') + ' ' + (t.from || '')).toLowerCase().indexOf(q) !== -1;
    }

    function renderMine() {
        var d = state.data;
        if (!d) {
            $list.html(skeleton(3));
            return;
        }
        $('#djLabel').text(d.label);
        $('#djToday').toggleClass('d-none', !!d.is_current);

        // Counted here so local changes (status, add, delete) update the chips without a request
        var counts = { all: d.tasks.length, todo: 0, in_progress: 0, done: 0, skipped: 0 };
        d.tasks.forEach(function (t) { counts[t.status]++; });
        var chips = [{ id: '', label: 'Semua', count: counts.all }].concat(STATUS.map(function (s) {
            return { id: s.id, label: s.label, count: counts[s.id] };
        }));
        $('#djChips').html(chips.map(function (c) {
            return '<button type="button" class="dj-chip' + (state.status === c.id ? ' active' : '') + '" data-dj-status="' + c.id + '">'
                + esc(c.label) + '<b>' + c.count + '</b></button>';
        }).join(''));

        if (d.report && d.report.total > 0) {
            var sent = d.report.unreported === 0;
            $('#djReport').html('<div class="dj-report' + (sent ? ' is-sent' : '') + '">'
                + '<span>' + (sent
                    ? '<i class="fas fa-check-circle mr-1" style="color:#20c997"></i>Laporan hari ini sudah dikirim ke atasan.'
                    : '<i class="fas fa-paper-plane mr-1" style="color:#1e88e5"></i>' + d.report.unreported + ' task belum dilaporkan ke atasan.') + '</span>'
                + '<button type="button" class="btn btn-sm ' + (sent ? 'btn-outline-secondary' : 'btn-primary') + '" data-dj-action="report">'
                + (sent ? 'Kirim ulang' : 'Kirim laporan') + '</button></div>');
        }

        var tasks = d.tasks.filter(function (t) { return (!state.status || t.status === state.status) && matchesSearch(t); });
        if (!tasks.length) {
            $list.html('<div class="dj-empty"><div class="dj-empty-icon">' + (state.status || state.search ? '🔍' : '📝') + '</div>'
                + (state.search ? 'Tidak ada task yang cocok.' : (state.status ? 'Tidak ada task dengan status ini.' : 'Belum ada task. Tulis task pertama di kolom atas.')) + '</div>');
            return;
        }

        var groups = [], byKey = {};
        tasks.forEach(function (t) {
            var key = t.carried ? 'carried' : t.task_date;
            if (!byKey[key]) {
                byKey[key] = { key: key, tasks: [] };
                groups.push(byKey[key]);
            }
            byKey[key].tasks.push(t);
        });
        groups.sort(function (a, b) {
            if (a.key === 'carried') return -1;
            if (b.key === 'carried') return 1;
            return a.key < b.key ? -1 : 1;
        });

        // Group rows only when they say something (several days, or carried-over tasks)
        var showGroups = groups.length > 1 || state.period !== 'day';
        $list.html(taskTable(groups.map(function (g) {
            var head = '';
            if (showGroups) {
                head = g.key === 'carried'
                    ? '<tr class="dj-group-row is-warn"><td colspan="6"><i class="fas fa-hourglass-half mr-1"></i>Belum selesai dari sebelumnya · ' + g.tasks.length + '</td></tr>'
                    : '<tr class="dj-group-row"><td colspan="6">' + esc(fmtDay(g.key)) + ' · ' + g.tasks.length + '</td></tr>';
            }
            return head + g.tasks.map(function (t) { return renderTask(t, false); }).join('');
        }).join('')));
    }

    function taskTable(rows) {
        return '<table class="dj-table"><thead><tr>'
            + '<th class="c-check"></th><th>Task</th><th class="c-date">Tanggal</th><th class="c-dl">Deadline</th>'
            + '<th class="c-status">Status</th><th class="c-act"></th>'
            + '</tr></thead><tbody>' + rows + '</tbody></table>';
    }

    function renderTask(t, teamView) {
        if (state.editingId === t.id) return renderEdit(t);

        // Second line: time, repetition, who gave it, note
        var sub = [];
        if (t.scheduled_time) sub.push('<i class="far fa-clock"></i> ' + esc(t.scheduled_time));
        if (t.repeat) sub.push('<i class="fas fa-redo-alt"></i> ' + esc(t.repeat));
        if (t.from && !teamView) sub.push('<span class="is-from">dari ' + esc(t.from) + '</span>');
        if (t.note) sub.push(esc(t.note));

        var deadline = '<span class="dj-muted">—</span>';
        if (t.deadline_date) {
            deadline = t.overdue
                ? '<span class="dj-dl-over" title="Lewat deadline"><i class="fas fa-flag mr-1"></i>' + esc(fmtDate(t.deadline_date)) + '</span>'
                : (t.due_today
                    ? '<span class="dj-dl-today"><i class="fas fa-flag mr-1"></i>Hari ini</span>'
                    : esc(fmtDate(t.deadline_date)));
        }

        var status = teamView
            ? '<span class="dj-status-pill">' + esc(STATUS_LABEL[t.status] || t.status) + '</span>'
            : '<select class="dj-status" data-dj-action="status" aria-label="Status"' + (t.can_edit ? '' : ' disabled') + '>'
                + STATUS.map(function (s) {
                    return '<option value="' + s.id + '"' + (s.id === t.status ? ' selected' : '') + '>' + esc(s.label) + '</option>';
                }).join('') + '</select>';

        var actions = '';
        if (!teamView && t.carried && t.can_edit && OPEN[t.status]) {
            actions += '<button type="button" class="dj-icon-btn" data-dj-action="move" title="Pindah ke hari ini"><i class="fas fa-arrow-right"></i></button>';
        }
        if (!teamView && t.can_edit) {
            actions += '<button type="button" class="dj-icon-btn" data-dj-action="edit" title="Ubah"><i class="fas fa-pen"></i></button>';
        }
        if (t.can_delete) {
            actions += '<button type="button" class="dj-icon-btn is-danger" data-dj-action="delete" title="Hapus"><i class="far fa-trash-alt"></i></button>';
        }

        return ''
            + '<tr class="dj-row is-' + t.status + '" data-dj-id="' + t.id + '">'
            + '<td class="c-check"><button type="button" class="dj-check" data-dj-action="toggle"' + (teamView || !t.can_edit ? ' disabled' : '')
            + ' title="' + (t.status === 'done' ? 'Tandai belum selesai' : 'Tandai selesai') + '">'
            + (t.status === 'done' ? '<i class="fas fa-check"></i>' : (t.status === 'skipped' ? '<i class="fas fa-minus"></i>' : '')) + '</button></td>'
            + '<td><div class="dj-ttl" title="' + esc(t.title) + '">' + esc(t.title) + '</div>'
            + (sub.length ? '<div class="dj-sub" title="' + esc(t.note || '') + '">' + sub.join(' · ') + '</div>' : '') + '</td>'
            + '<td class="c-date' + (t.carried ? ' dj-date-carried' : '') + '"' + (t.carried ? ' title="Belum selesai dari sebelumnya"' : '') + '>' + esc(fmtDate(t.task_date)) + '</td>'
            + '<td class="c-dl">' + deadline + '</td>'
            + '<td class="c-status">' + status + '</td>'
            + '<td class="c-act">' + actions + '</td>'
            + '</tr>';
    }

    function renderEdit(t) {
        return ''
            + '<tr class="dj-edit-row" data-dj-id="' + t.id + '"><td colspan="6">'
            + '  <form class="dj-edit" data-dj-edit-form>'
            + '    <div class="dj-field dj-wide"><label>Task</label><input type="text" class="form-control form-control-sm" name="title" maxlength="120" required value="' + esc(t.title) + '"></div>'
            + '    <div class="dj-field"><label>Tanggal</label><input type="date" class="form-control form-control-sm" name="task_date" required value="' + esc(t.task_date) + '"></div>'
            + '    <div class="dj-field"><label>Jam</label><input type="time" class="form-control form-control-sm" name="scheduled_time" value="' + esc(t.scheduled_time || '') + '"></div>'
            + '    <div class="dj-field"><label>Deadline</label><input type="date" class="form-control form-control-sm" name="deadline_date" value="' + esc(t.deadline_date || '') + '"></div>'
            + '    <div class="dj-field dj-wide"><label>Catatan</label><textarea class="form-control form-control-sm" name="note" maxlength="2000" rows="2">' + esc(t.note || '') + '</textarea></div>'
            + '    <div class="dj-error dj-wide d-none" data-dj-edit-error></div>'
            + '    <div class="dj-edit-actions">'
            + '      <button type="button" class="btn btn-sm btn-light" data-dj-action="cancel">Batal</button>'
            + '      <button type="submit" class="btn btn-sm btn-primary">Simpan</button>'
            + '    </div>'
            + '  </form>'
            + '</td></tr>';
    }

    function renderTeam() {
        var d = state.team;
        if (!d) {
            $list.html(skeleton(3));
            return;
        }
        $('#djLabel').text(d.label);
        $('#djToday').toggleClass('d-none', !!d.is_current);

        var q = state.search.toLowerCase();
        var members = d.members.filter(function (m) {
            return !q || (m.name + ' ' + (m.position || '')).toLowerCase().indexOf(q) !== -1;
        });

        if (!members.length) {
            $list.html('<div class="dj-empty"><div class="dj-empty-icon">👥</div>'
                + (q ? 'Tidak ada nama yang cocok.' : 'Belum ada task tim di periode ini.') + '</div>');
            return;
        }

        $list.html(members.map(function (m) {
            var open = !!state.openMembers[m.id];
            var tasks = state.memberTasks[m.id];
            var progress = m.progress == null ? '' : '<div class="dj-progress"><span style="width:' + m.progress + '%"></span></div>';
            var stats = ''
                + (m.overdue ? '<span class="dj-badge overdue"><i class="fas fa-flag"></i>' + m.overdue + ' lewat</span>' : '')
                + '<span class="dj-meta-item">' + m.counts.done + '/' + (m.counts.all - m.counts.skipped) + ' selesai</span>';
            var body = '';
            if (open) {
                body = !Array.isArray(tasks)
                    ? '<div class="dj-member-body">' + (tasks === 'error' ? '<div class="dj-empty py-3">Gagal memuat.</div>' : skeleton(2)) + '</div>'
                    : '<div class="dj-member-body">' + (tasks.length
                        ? taskTable(tasks.map(function (t) { return renderTask(t, true); }).join(''))
                        : '<div class="dj-empty py-3">Belum ada task.</div>') + '</div>';
            }
            var assign = state.assigning === String(m.id)
                ? '<form class="dj-assign" data-dj-assign-form>'
                    + '<input type="text" class="form-control" name="title" maxlength="120" required placeholder="Task untuk ' + esc(m.name.split(',')[0]) + '...">'
                    + '<input type="date" class="form-control" name="task_date" value="' + esc(defaultAddDate()) + '">'
                    + '<button type="submit" class="btn btn-primary btn-sm px-3">Beri</button>'
                    + '<button type="button" class="btn btn-light btn-sm" data-dj-action="assign-cancel">Batal</button>'
                    + '</form>'
                : '';
            return ''
                + '<div class="dj-member" data-dj-member="' + m.id + '">'
                + '  <div class="dj-member-head" data-dj-action="member-toggle">'
                + '    <span class="dj-avatar">' + esc(initials(m.name)) + '</span>'
                + '    <div class="dj-member-info">'
                + '      <div class="dj-member-name">' + esc(m.name) + '</div>'
                + '      <div class="dj-member-sub">' + esc(m.position || '') + (m.position ? ' · ' : '') + m.counts.all + ' task'
                + (m.progress == null ? '' : ' · ' + m.progress + '% selesai') + '</div>'
                + progress
                + '    </div>'
                + '    <div class="dj-member-stats">' + stats + '</div>'
                + '    <button type="button" class="btn btn-sm btn-outline-primary" data-dj-action="assign" title="Beri task"><i class="fas fa-plus"></i></button>'
                + '    <i class="fas fa-chevron-' + (open ? 'up' : 'down') + ' ml-1" style="opacity:.5"></i>'
                + '  </div>'
                + assign + body
                + '</div>';
        }).join(''));
    }

    function renderRecurring() {
        if (!state.recurring) {
            $list.html(skeleton(2));
            return;
        }
        var q = state.search.toLowerCase();
        var items = state.recurring.filter(function (r) {
            return !q || (r.title + ' ' + (r.for || '')).toLowerCase().indexOf(q) !== -1;
        });
        if (!items.length) {
            $list.html('<div class="dj-empty"><div class="dj-empty-icon">🔁</div>Belum ada task berulang.<br>'
                + '<small>Buat lewat <b>Detail → Ulangi</b> saat menambah task.</small></div>');
            return;
        }
        $list.html(items.map(function (r) {
            return ''
                + '<div class="dj-recurring" data-dj-recurrence="' + r.id + '">'
                + '  <span class="dj-recurring-icon"><i class="fas fa-redo-alt"></i></span>'
                + '  <div class="dj-main">'
                + '    <div class="dj-title">' + esc(r.title) + '</div>'
                + '    <div class="dj-meta">'
                + '      <span class="dj-meta-item is-repeat">' + esc(r.label) + '</span>'
                + (r.scheduled_time ? '<span class="dj-meta-item"><i class="far fa-clock"></i>' + esc(r.scheduled_time) + '</span>' : '')
                + '      <span class="dj-meta-item">Mulai ' + esc(fmtDate(r.start_date)) + (r.end_date ? ' – ' + esc(fmtDate(r.end_date)) : '') + '</span>'
                + (r.for ? '<span class="dj-meta-item is-from"><i class="fas fa-user"></i>untuk ' + esc(r.for) + '</span>' : '')
                + '    </div>'
                + '  </div>'
                + '  <button type="button" class="btn btn-sm btn-outline-danger" data-dj-action="stop-recurrence">Hentikan</button>'
                + '</div>';
        }).join(''));
    }

    function findTask(id) {
        var pool = state.data ? state.data.tasks.slice() : [];
        Object.keys(state.memberTasks).forEach(function (mid) {
            if (Array.isArray(state.memberTasks[mid])) pool = pool.concat(state.memberTasks[mid]);
        });
        return pool.filter(function (t) { return t.id === id; })[0];
    }
    function idOf(el) { return Number($(el).closest('[data-dj-id]').attr('data-dj-id')); }

    function confirmThen(title, text, confirmText, fn) {
        if (window.Swal) {
            window.Swal.fire({
                icon: 'warning', title: title, text: text, showCancelButton: true,
                confirmButtonText: confirmText, cancelButtonText: 'Batal', reverseButtons: true
            }).then(function (r) { if (r.isConfirmed || r.value) fn(); });
        } else if (window.confirm(title + '\n' + text)) {
            fn();
        }
    }

    function loadMemberTasks(id) {
        state.memberTasks[id] = 'loading';
        send('GET', tpl(endpoints.teamTasksTemplate, id), { period: state.period, date: state.date })
            .done(function (res) { state.memberTasks[id] = res.tasks || []; })
            .fail(function () { state.memberTasks[id] = 'error'; })
            .always(function () { if (state.tab === 'team') render(); });
    }

    // ---------------------------------------------------------------- events
    $modal.on('click', '[data-dj-tab]', function () {
        state.tab = this.getAttribute('data-dj-tab');
        state.search = '';
        $('#djSearch').val('');
        load();
    });

    $modal.on('click', '[data-dj-period]', function () {
        state.period = this.getAttribute('data-dj-period');
        load();
    });

    $modal.on('click', '[data-dj-step]', function () {
        var step = Number(this.getAttribute('data-dj-step'));
        var d = parse(state.date);
        if (state.period === 'day') d.setDate(d.getDate() + step);
        else if (state.period === 'week') d.setDate(d.getDate() + step * 7);
        else { d.setDate(1); d.setMonth(d.getMonth() + step); }
        state.date = toStr(d);
        load();
    });

    $('#djToday').on('click', function () {
        state.date = todayStr();
        load();
    });

    $('#djSearch').on('input', function () {
        state.search = this.value.trim();
        render();
    });

    // Status chips filter what is already loaded
    $modal.on('click', '[data-dj-status]', function () {
        state.status = this.getAttribute('data-dj-status');
        render();
    });

    $modal.on('click', '[data-dj-retry]', function (e) {
        e.preventDefault();
        load();
    });

    $modal.on('click', '[data-dj-quick]', function () {
        var key = this.getAttribute('data-dj-quick');
        state.quickDate = key === 'monday' ? nextMonday() : addDays(todayStr(), Number(key));
        $('#djAddDate').val(state.quickDate);
        syncQuickDates();
        $('#djAddForm input[name="title"]').trigger('focus');
    });
    $('#djAddDate').on('change', function () {
        state.quickDate = this.value || null;
        syncQuickDates();
    });

    $('#djToggleMore').on('click', function () {
        var $more = $('#djAddMore');
        $more.toggleClass('d-none');
        if (!$more.hasClass('d-none') && !$('#djAddDate').val()) $('#djAddDate').val(defaultAddDate());
    });

    $('#djAddRepeat').on('change', function () {
        var repeating = !!this.value;
        $('#djDeadlineField').toggleClass('d-none', repeating);
        $('#djRepeatUntilField').toggleClass('d-none', !repeating);
    });

    $('#djAddForm').on('submit', function (e) {
        e.preventDefault();
        var form = this;
        var $btn = $(form).find('button[type="submit"]').prop('disabled', true);
        var repeat = form.repeat.value;
        var data = {
            title: form.title.value.trim(),
            task_date: form.task_date.value || defaultAddDate(),
            scheduled_time: form.scheduled_time.value,
            deadline_date: repeat ? '' : form.deadline_date.value,
            repeat: repeat,
            repeat_until: repeat ? form.repeat_until.value : '',
            note: form.note.value.trim()
        };
        $('#djAddError').addClass('d-none');

        send('POST', endpoints.store, data)
            .done(function (res) {
                form.title.value = '';
                form.scheduled_time.value = '';
                form.deadline_date.value = '';
                form.note.value = '';
                form.repeat.value = '';
                form.repeat_until.value = '';
                $('#djAddRepeat').trigger('change');
                if (repeat) state.recurring = null;
                delete state.cache['recurring|'];

                var t = res.task;
                if (t && inView(t)) {
                    state.status = '';
                    upsertTask(t);
                    render();
                    toast(res.message || 'Task ditambahkan');
                } else if (t) {
                    // Added outside the shown period: go there so it is visible
                    toast('Ditambahkan untuk ' + fmtDay(t.task_date));
                    invalidateOthers();
                    if (state.period === 'day') { state.date = t.task_date; load(); }
                } else {
                    toast((res.message || 'Task ditambahkan') + ' (mulai sesuai jadwal pengulangan)');
                }
                form.title.focus();
            })
            .fail(function (xhr) {
                $('#djAddError').text(errorText(xhr)).removeClass('d-none');
            })
            .always(function () { $btn.prop('disabled', false); });
    });

    // Status changes show immediately; the list is only reloaded if the server refuses
    function setStatus(id, next, message) {
        var t = findTask(id);
        if (!t || t.status === next) return;
        t.status = next;
        t.overdue = t.overdue && !!OPEN[next];
        t.due_today = t.due_today && !!OPEN[next];
        t.can_delete = t.can_delete && next === 'todo';
        render();
        toast(message);
        invalidateOthers();
        send('PATCH', tpl(endpoints.taskTemplate, id), { status: next, view_start: state.data && state.data.start })
            .done(function (res) { if (res.task) { upsertTask(res.task); render(); } })
            .fail(function (xhr) { toast(errorText(xhr)); delete state.cache[cacheKey()]; load(); });
    }

    $modal.on('click', '[data-dj-action="toggle"]', function () {
        var t = findTask(idOf(this));
        if (!t) return;
        var next = t.status === 'done' ? 'todo' : 'done';
        setStatus(t.id, next, next === 'done' ? 'Selesai 🎉' : 'Ditandai belum selesai');
    });

    $modal.on('change', '[data-dj-action="status"]', function () {
        setStatus(idOf(this), this.value, 'Status diperbarui');
    });

    $modal.on('click', '[data-dj-action="move"]', function () {
        var id = idOf(this);
        send('PATCH', tpl(endpoints.taskTemplate, id), { task_date: todayStr(), view_start: state.data && state.data.start })
            .done(function (res) { upsertTask(res.task); render(); toast('Dipindah ke hari ini'); })
            .fail(function (xhr) { toast(errorText(xhr)); });
    });

    $modal.on('click', '[data-dj-action="edit"]', function () {
        state.editingId = idOf(this);
        render();
        $list.find('[data-dj-edit-form] input[name="title"]').trigger('focus');
    });

    $modal.on('click', '[data-dj-action="cancel"]', function () {
        state.editingId = null;
        render();
    });

    $modal.on('submit', '[data-dj-edit-form]', function (e) {
        e.preventDefault();
        var form = this;
        var $btn = $(form).find('button[type="submit"]').prop('disabled', true);
        send('PATCH', tpl(endpoints.taskTemplate, idOf(form)), {
            title: form.title.value.trim(),
            task_date: form.task_date.value,
            scheduled_time: form.scheduled_time.value,
            deadline_date: form.deadline_date.value,
            note: form.note.value.trim(),
            view_start: state.data && state.data.start
        }).done(function (res) {
            state.editingId = null;
            upsertTask(res.task);
            render();
            toast('Task diperbarui');
        }).fail(function (xhr) {
            $(form).find('[data-dj-edit-error]').text(errorText(xhr)).removeClass('d-none');
            $btn.prop('disabled', false);
        });
    });

    $modal.on('click', '[data-dj-action="delete"]', function () {
        var t = findTask(idOf(this));
        if (!t) return;
        confirmThen('Hapus task?', t.title, 'Ya, hapus', function () {
            removeTask(t.id);
            if (state.data) state.cache[cacheKey()] = state.data;
            render();
            toast('Task dihapus');
            send('DELETE', tpl(endpoints.taskTemplate, t.id), {})
                .done(function () { if (state.tab === 'team') { delete state.cache[cacheKey()]; load(); } })
                .fail(function (xhr) { toast(errorText(xhr)); delete state.cache[cacheKey()]; load(); });
        });
    });

    $modal.on('click', '[data-dj-action="report"]', function () {
        var $btn = $(this).prop('disabled', true);
        send('POST', endpoints.report, { date: state.date })
            .done(function (res) {
                if (state.data && state.data.report) state.data.report.unreported = 0;
                render();
                toast(res.message || 'Laporan terkirim');
            })
            .fail(function (xhr) { toast(errorText(xhr)); $btn.prop('disabled', false); });
    });

    $modal.on('click', '[data-dj-action="member-toggle"]', function (e) {
        if ($(e.target).closest('[data-dj-action="assign"]').length) return;
        var id = $(this).closest('[data-dj-member]').attr('data-dj-member');
        state.openMembers[id] = !state.openMembers[id];
        if (state.openMembers[id] && !Array.isArray(state.memberTasks[id])) loadMemberTasks(id);
        render();
    });

    $modal.on('click', '[data-dj-action="assign"]', function () {
        var id = $(this).closest('[data-dj-member]').attr('data-dj-member');
        state.assigning = state.assigning === id ? null : id;
        render();
        $list.find('[data-dj-assign-form] input[name="title"]').trigger('focus');
    });

    $modal.on('click', '[data-dj-action="assign-cancel"]', function () {
        state.assigning = null;
        render();
    });

    $modal.on('submit', '[data-dj-assign-form]', function (e) {
        e.preventDefault();
        var form = this;
        var id = $(form).closest('[data-dj-member]').attr('data-dj-member');
        var $btn = $(form).find('button[type="submit"]').prop('disabled', true);
        send('POST', endpoints.store, { title: form.title.value.trim(), task_date: form.task_date.value || todayStr(), assignee_id: id })
            .done(function (res) {
                toast(res.message || 'Task diberikan');
                state.assigning = null;
                state.openMembers[id] = true;
                delete state.cache[cacheKey()];
                invalidateOthers();
                load();
            })
            .fail(function (xhr) {
                toast(errorText(xhr));
                $btn.prop('disabled', false);
            });
    });

    $modal.on('click', '[data-dj-action="stop-recurrence"]', function () {
        var id = $(this).closest('[data-dj-recurrence]').attr('data-dj-recurrence');
        confirmThen('Hentikan pengulangan?', 'Task yang sudah dibuat tetap ada.', 'Ya, hentikan', function () {
            state.recurring = (state.recurring || []).filter(function (r) { return String(r.id) !== String(id); });
            state.cache[cacheKey()] = { data: state.recurring };
            render();
            send('DELETE', tpl(endpoints.recurrenceTemplate, id), {})
                .done(function () { toast('Pengulangan dihentikan'); })
                .fail(function (xhr) { toast(errorText(xhr)); delete state.cache[cacheKey()]; load(); });
        });
    });

    // Floating chat button covers the list on phones: hide it while the journal is open
    $modal.on('show.bs.modal', function () { $('body').addClass('dj-open'); })
        .on('shown.bs.modal', function () { if (state.tab === 'mine') $('#djAddForm input[name="title"]').trigger('focus'); })
        .on('hidden.bs.modal', function () { $('body').removeClass('dj-open'); state.editingId = null; state.assigning = null; });

    /** options: { tab: 'mine' | 'team' | 'recurring', date: 'YYYY-MM-DD' } */
    window.belovaOpenJournal = function (options) {
        options = options || {};
        if (options.tab) state.tab = options.tab;
        if (options.date) { state.date = options.date; state.period = 'day'; }
        if (options.tab === 'team') setTeamAvailable(true);
        $modal.modal('show');
        load();
        // The Tim tab button needs can_team, which comes with "Task Saya"
        if (state.tab !== 'mine' && !state.data) {
            send('GET', endpoints.tasks, { period: state.period, date: state.date })
                .done(function (data) { state.cache['mine|' + state.period + '|' + state.date] = data; applyData('mine', data); });
        }
    };
}
// Loaded lazily (GET daily-journal.modal) after the page is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBelovaDailyJournal);
} else {
    initBelovaDailyJournal();
}
</script>
