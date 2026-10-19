{{-- Daily Journal reminder: tasks given by a manager, daily reports from the team, and deadlines. Included by layouts/shared/sidebar. --}}
@auth
<style>
.djr-card { position: fixed; left: 20px; bottom: 20px; z-index: 1150; width: min(360px, calc(100vw - 32px)); background: #fff; color: #0f172a; border: 1px solid rgba(148,163,184,.28); border-radius: 16px; box-shadow: 0 18px 44px rgba(15,23,42,.22); overflow: hidden; animation: djrIn .25s ease-out; }
.theme-dark .djr-card, html.theme-dark .djr-card, html[data-theme="dark"] .djr-card, body.dark-sidenav .djr-card { background: #0f172a; color: #e2e8f0; border-color: rgba(148,163,184,.2); box-shadow: 0 18px 44px rgba(2,6,23,.55); }
@keyframes djrIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
.djr-head { display: flex; align-items: center; gap: 10px; padding: 12px 12px 10px 14px; background: linear-gradient(90deg, rgba(30,136,229,.12), rgba(100,181,246,.06)); }
.djr-head-icon { width: 32px; height: 32px; border-radius: 10px; background: #1e88e5; color: #fff; display: inline-flex; align-items: center; justify-content: center; flex: none; }
.djr-head-title { flex: 1; font-weight: 700; font-size: 14px; }
.djr-close { border: 0; background: transparent; color: inherit; opacity: .6; width: 28px; height: 28px; border-radius: 8px; font-size: 18px; line-height: 1; }
.djr-close:hover { opacity: 1; background: rgba(128,128,128,.14); }
.djr-body { padding: 8px 14px 4px; max-height: 260px; overflow-y: auto; }
.djr-item { padding: 8px 0; border-bottom: 1px solid rgba(128,128,128,.16); font-size: 13px; }
.djr-item:last-child { border-bottom: 0; }
.djr-item b { display: block; font-size: 13px; }
.djr-item span { opacity: .78; font-size: 12.5px; }
.djr-warn { display: flex; gap: 8px; align-items: center; padding: 8px 10px; margin: 6px 0; border-radius: 10px; background: rgba(231,76,60,.1); color: #e74c3c; font-size: 12.5px; font-weight: 600; }
.djr-foot { display: flex; justify-content: flex-end; gap: 6px; padding: 8px 12px 12px; }
@media (max-width: 767.98px) { .djr-card { left: 12px; bottom: 12px; } }
</style>

<script>window.BelovaJournalReminderConfig = {!! json_encode(['userId' => auth()->id(), 'swalCss' => asset('dastone/plugins/sweet-alert2/sweetalert2.min.css'), 'swalJs' => asset('dastone/plugins/sweet-alert2/sweetalert2.min.js'), 'routes' => ['daily-journal.reminders' => route('daily-journal.reminders'), 'daily-journal.updates' => route('daily-journal.updates'), 'daily-journal.reminders.read' => route('daily-journal.reminders.read')]], 15) !!};</script>
<script src="{{ asset('js/belova-journal-reminder.js') }}?v={{ filemtime(public_path('js/belova-journal-reminder.js')) }}"></script>
@endauth
