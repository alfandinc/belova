{{-- Shared look of the marketing workflow pages (Content Plan, Request Desain): status pills,
     timeline, item cards and full-screen modals on phones. Every color has a light and a dark token. --}}
<style>
:root.theme-light, :root:not(.theme-dark) {
    --wf-surface: #ffffff;
    --wf-surface-solid: #ffffff;
    --wf-surface-2: #f3f5f8;
    --wf-border: #e3e6ea;
    --wf-text: #212529;
    --wf-muted: #6c757d;
    --wf-accent: #0d6efd;
    --wf-today: rgba(13, 110, 253, 0.06);
    --wf-danger: #c92a2a;
    --st-gray-bg: #eef0f3;      --st-gray-fg: #495057;
    --st-blue-bg: #e0f2fe;      --st-blue-fg: #075985;
    --st-amber-bg: #fef3c7;     --st-amber-fg: #92400e;
    --st-red-bg: #fee2e2;       --st-red-fg: #991b1b;
    --st-indigo-bg: #e0e7ff;    --st-indigo-fg: #3730a3;
    --st-green-bg: #dcfce7;     --st-green-fg: #166534;
    --st-muted-bg: #f1f3f5;     --st-muted-fg: #868e96;
}
:root.theme-dark {
    --wf-surface: rgba(255, 255, 255, 0.035);
    --wf-surface-solid: #2a2f45;
    --wf-surface-2: rgba(255, 255, 255, 0.06);
    --wf-border: rgba(255, 255, 255, 0.12);
    --wf-text: #e9ecef;
    --wf-muted: #9aa4b2;
    --wf-accent: #6ea8fe;
    --wf-today: rgba(110, 168, 254, 0.10);
    --wf-danger: #ff8787;
    --st-gray-bg: rgba(255,255,255,.09);   --st-gray-fg: #ced4da;
    --st-blue-bg: rgba(56,189,248,.16);    --st-blue-fg: #7dd3fc;
    --st-amber-bg: rgba(251,191,36,.16);   --st-amber-fg: #fcd34d;
    --st-red-bg: rgba(248,113,113,.18);    --st-red-fg: #fca5a5;
    --st-indigo-bg: rgba(129,140,248,.2);  --st-indigo-fg: #c7d2fe;
    --st-green-bg: rgba(74,222,128,.16);   --st-green-fg: #86efac;
    --st-muted-bg: rgba(255,255,255,.05);  --st-muted-fg: #8b95a1;
}

.wf-muted { color: var(--wf-muted) !important; }
.wf-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 10px; border-radius: 999px;
    font-size: .75rem; font-weight: 600; line-height: 1.5; white-space: nowrap;
}
.wf-pill.st-gray   { background: var(--st-gray-bg);   color: var(--st-gray-fg); }
.wf-pill.st-blue   { background: var(--st-blue-bg);   color: var(--st-blue-fg); }
.wf-pill.st-amber  { background: var(--st-amber-bg);  color: var(--st-amber-fg); }
.wf-pill.st-red    { background: var(--st-red-bg);    color: var(--st-red-fg); }
.wf-pill.st-indigo { background: var(--st-indigo-bg); color: var(--st-indigo-fg); }
.wf-pill.st-green  { background: var(--st-green-bg);  color: var(--st-green-fg); }
.wf-pill.st-muted  { background: var(--st-muted-bg);  color: var(--st-muted-fg); text-decoration: line-through; }
.wf-chip {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 1px 8px; border-radius: 6px; font-size: .74rem;
    background: var(--wf-surface-2); color: var(--wf-text); border: 1px solid var(--wf-border);
}
a.wf-chip:hover { text-decoration: none; border-color: var(--wf-accent); }

/* Tabs that scroll sideways on phones instead of wrapping */
.wf-tabs { flex-wrap: nowrap; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
.wf-tabs::-webkit-scrollbar { display: none; }
.wf-tabs .nav-link { white-space: nowrap; }
.wf-count {
    display: inline-block; min-width: 20px; padding: 0 6px; margin-left: 4px;
    border-radius: 999px; font-size: .72rem; font-weight: 700; text-align: center;
    background: var(--st-amber-bg); color: var(--st-amber-fg);
}
.nav-pills .nav-link.active .wf-count { background: rgba(255,255,255,.25); color: #fff; }

/* Filter chips (status filters) */
.wf-filter-chips { display: flex; gap: 6px; overflow-x: auto; padding-bottom: 2px; scrollbar-width: none; }
.wf-filter-chips::-webkit-scrollbar { display: none; }
.wf-filter-chip {
    flex: 0 0 auto; border: 1px solid var(--wf-border); background: var(--wf-surface);
    color: var(--wf-text); border-radius: 999px; padding: 4px 12px; font-size: .82rem; font-weight: 600;
}
.wf-filter-chip.active { background: var(--wf-accent); border-color: var(--wf-accent); color: #fff; }
.wf-filter-chip .wf-count { margin-left: 6px; }

/* Generic item card */
.wf-card {
    background: var(--wf-surface); border: 1px solid var(--wf-border); border-radius: 10px;
    padding: 12px; color: var(--wf-text);
}
.wf-card.is-clickable { cursor: pointer; transition: border-color .15s ease, box-shadow .15s ease; }
.wf-card.is-clickable:hover, .wf-card.is-clickable:focus { border-color: var(--wf-accent); outline: none; box-shadow: 0 0 0 3px rgba(13,110,253,.12); }
.wf-card-title { font-weight: 700; line-height: 1.3; word-break: break-word; }
.wf-meta { font-size: .8rem; color: var(--wf-muted); }
.wf-actions { display: flex; flex-wrap: wrap; gap: 6px; }
.wf-actions .btn { font-weight: 600; }
.wf-empty { text-align: center; color: var(--wf-muted); padding: 28px 12px; }
.wf-late { color: var(--wf-danger); font-weight: 700; }

/* Reference link preview (Instagram / TikTok / YouTube embed) */
.wf-embed { border: 1px solid var(--wf-border); border-radius: 10px; padding: 8px 10px; background: var(--wf-surface-2); }
.wf-embed-head { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; font-size: .84rem; font-weight: 600; }
.wf-embed-head a { margin-left: auto; }
.wf-embed-frame { margin-top: 8px; text-align: center; }
.wf-embed-frame iframe { width: 100%; border: 0; border-radius: 8px; background: #fff; display: inline-block; }

/* Timeline ("Riwayat") */
.wf-timeline { list-style: none; margin: 0; padding: 0 0 0 4px; }
.wf-timeline li { position: relative; padding: 0 0 14px 22px; }
.wf-timeline li::before {
    content: ''; position: absolute; left: 5px; top: 6px; bottom: -6px; width: 2px; background: var(--wf-border);
}
.wf-timeline li:last-child::before { display: none; }
.wf-timeline li::after {
    content: ''; position: absolute; left: 0; top: 4px; width: 12px; height: 12px;
    border-radius: 50%; background: var(--wf-surface); border: 2px solid var(--wf-accent);
}
.wf-timeline .wf-tl-head { font-size: .86rem; }
.wf-timeline .wf-tl-note {
    margin-top: 4px; padding: 6px 10px; border-radius: 8px; font-size: .84rem;
    background: var(--wf-surface-2); white-space: pre-wrap; word-break: break-word;
}

/* Scrollable modals whose header/body/footer sit inside a <form>: the form must be the flex column,
   otherwise the body does not scroll and the footer (Simpan) is pushed out of view */
.wf-modal .modal-content > form { display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; max-height: inherit; overflow: hidden; }
.wf-modal .modal-content > form > .modal-body { overflow-y: auto; min-height: 0; flex: 1 1 auto; }
@media (min-width: 1200px) {
    .wf-modal .modal-xl { max-width: min(1400px, 94vw); }
}

/* Right-hand "Riwayat" panel inside a modal */
.wf-side {
    background: var(--wf-surface-2); border: 1px solid var(--wf-border); border-radius: 10px; padding: 12px;
}
.wf-side-title { font-weight: 700; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
@media (min-width: 992px) {
    .wf-side { position: sticky; top: 0; max-height: calc(100vh - 220px); overflow-y: auto; }
}
@media (max-width: 991.98px) {
    .wf-side { margin-top: 16px; }
}

/* Full-screen modals on phones, sticky footer with the main buttons */
@media (max-width: 767.98px) {
    .wf-modal .modal-dialog { margin: 0; max-width: 100%; width: 100%; min-height: 100%; }
    .wf-modal .modal-content { min-height: 100vh; border-radius: 0; border: 0; }
    .wf-modal .modal-dialog-scrollable { height: 100%; max-height: 100%; }
    .wf-modal .modal-dialog-scrollable .modal-content { height: 100vh; max-height: 100vh; }
    .wf-modal .modal-body { padding: 12px; }
    .wf-modal .modal-footer {
        position: sticky; bottom: 0; z-index: 3; background-color: inherit;
        padding: 8px 12px; flex-wrap: nowrap;
    }
    .wf-modal .modal-footer .btn { flex: 1 1 auto; }
}
.wf-modal .modal-footer { background-color: inherit; }

/* ===== Shared page parts of Content Plan & Request Desain ===== */
/* page header */
.cp-page { padding-top: 18px; padding-bottom: 24px; }
.cp-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
.cp-top h4 { font-size: 1.35rem; font-weight: 700; margin: 0; letter-spacing: -.01em; }
.cp-top p { margin: 2px 0 0; font-size: .84rem; color: var(--wf-muted); }
.cp-new { border-radius: 10px; padding: 8px 16px; font-weight: 600; box-shadow: 0 2px 8px rgba(13,110,253,.25); }
.cp-top-actions { display: flex; gap: 8px; align-items: center; }
.cp-install { border-radius: 10px; font-weight: 600; }
@media (max-width: 767.98px) { .cp-top-actions { width: 100%; } .cp-top-actions .btn { flex: 1 1 0; } }

/* segmented controls (tabs, Publish/Produksi) */
.cp-seg { display: inline-flex; padding: 3px; gap: 2px; border-radius: 10px; background: var(--wf-surface-2); border: 1px solid var(--wf-border); flex-wrap: nowrap; max-width: 100%; overflow-x: auto; scrollbar-width: none; }
.cp-seg::-webkit-scrollbar { display: none; }
.cp-seg .nav-link, .cp-seg button {
    border: 0; background: transparent; color: var(--wf-muted); font-weight: 600; font-size: .86rem;
    padding: 6px 14px; border-radius: 8px; line-height: 1.3; white-space: nowrap;
}
.cp-seg .nav-link.active, .cp-seg button.active { background: var(--wf-surface-solid); color: var(--wf-text); box-shadow: 0 1px 3px rgba(0,0,0,.12); }
.cp-seg .nav-link:hover, .cp-seg button:hover { color: var(--wf-text); }
.cp-seg-sm button { padding: 4px 10px; font-size: .8rem; }
.cp-tabsbar { margin-bottom: 14px; }

/* toolbar chips (filters, "Tugas saya", warnings) */
.cp-chip {
    display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--wf-border); background: var(--wf-surface-solid);
    color: var(--wf-text); border-radius: 999px; padding: 5px 12px; font-size: .8rem; font-weight: 600; white-space: nowrap;
}
.cp-chip:hover { border-color: var(--wf-accent); }
.cp-chip.active { background: var(--wf-accent); border-color: var(--wf-accent); color: #fff; }
.cp-chip.is-danger { background: var(--st-red-bg); border-color: transparent; color: var(--st-red-fg); }
.cp-chip.is-ok { background: var(--st-green-bg); border-color: transparent; color: var(--st-green-fg); }
.cp-chip.is-idea { background: var(--st-indigo-bg); border-color: transparent; color: var(--st-indigo-fg); }
.cp-chip.is-warn { background: var(--st-amber-bg); border-color: transparent; color: var(--st-amber-fg); }

/* tappable choices replacing dropdowns */
.cp-chips { display: flex; flex-wrap: wrap; gap: 8px; }
.cp-chip-opt {
    position: relative; display: inline-flex; align-items: center; gap: 8px; min-height: 40px; padding: 7px 14px;
    border-radius: 10px; border: 1px solid var(--wf-border); background: var(--wf-surface-solid); color: var(--wf-text);
    font-size: .86rem; font-weight: 600; cursor: pointer; transition: border-color .15s, background .15s, box-shadow .15s;
}
.cp-chip-opt:hover { border-color: var(--wf-accent); }
.cp-chip-opt:focus { outline: none; box-shadow: 0 0 0 3px rgba(13,110,253,.18); }
.cp-chip-opt.is-on { border-color: var(--wf-accent); background: rgba(13,110,253,.08); box-shadow: inset 0 0 0 1px var(--wf-accent); }
:root.theme-dark .cp-chip-opt.is-on { background: rgba(110,168,254,.16); }
.cp-chip-opt > i:first-child { color: var(--wf-muted); }
.cp-chip-opt.is-on > i:first-child { color: var(--wf-accent); }
.cp-chip-ico { display: inline-flex; font-size: 1.05rem; }
.cp-chip-ico svg { width: 15px; height: 15px; fill: currentColor; }
.cp-chip-check {
    position: absolute; top: -6px; right: -6px; width: 18px; height: 18px; border-radius: 50%; font-size: .6rem;
    display: inline-flex; align-items: center; justify-content: center; background: var(--wf-accent); color: #fff;
}
.cp-chips.is-disabled .cp-chip-opt { cursor: default; opacity: .75; }
.cp-chips.is-disabled .cp-chip-opt:not(.is-on) { display: none; }
@media (max-width: 575.98px) { .cp-chip-opt { flex: 1 1 calc(50% - 4px); justify-content: flex-start; } }

/* quick workflow buttons on a card */
.cp-item-actions { display: flex; gap: 6px; margin-top: 10px; }
.cp-act {
    flex: 1 1 0; display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 32px;
    border: 0; border-radius: 8px; font-size: .78rem; font-weight: 700; padding: 5px 8px; cursor: pointer;
    transition: filter .15s ease;
}
.cp-act:hover { filter: brightness(.95); }
.cp-act i { font-size: .72rem; }
.cp-act.is-primary { background: var(--wf-accent); color: #fff; }
.cp-act.is-success { background: #2f9e44; color: #fff; }
.cp-act.is-warning { background: var(--st-amber-bg); color: var(--st-amber-fg); }
.cp-act.is-wait { background: var(--st-indigo-bg); color: var(--st-indigo-fg); cursor: default; filter: none; }
.cp-act.is-alert { background: var(--st-red-bg); color: var(--st-red-fg); cursor: default; filter: none; }

/* small tags on cards */
.cp-tag {
    display: inline-flex; align-items: center; gap: 5px; max-width: 100%; padding: 2px 8px; border-radius: 999px;
    font-size: .72rem; font-weight: 600; line-height: 1.4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    background: var(--wf-surface-2); color: var(--wf-muted);
}
.cp-tag i { font-size: .65rem; opacity: .8; }
.cp-tag.cp-tag-rev { background: var(--st-amber-bg); color: var(--st-amber-fg); }

/* workflow bar + progress steps in a modal */
.cp-workflow { background: var(--wf-surface-2); border: 1px solid var(--wf-border); border-radius: 10px; padding: 10px 12px; }
.cp-steps { display: flex; align-items: center; overflow-x: auto; gap: 4px; scrollbar-width: none; }
.cp-steps::-webkit-scrollbar { display: none; }
.cp-step { display: flex; align-items: center; gap: 6px; flex: 0 0 auto; font-size: .8rem; color: var(--wf-muted); font-weight: 600; }
.cp-step .dot { width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: .7rem; border: 2px solid var(--wf-border); background: var(--wf-surface); }
.cp-step.done { color: var(--wf-text); }
.cp-step.done .dot { background: var(--st-green-bg); border-color: transparent; color: var(--st-green-fg); }
.cp-step.current { color: var(--wf-text); }
.cp-step.current .dot { background: var(--wf-accent); border-color: var(--wf-accent); color: #fff; }
.cp-step.current.is-revisi .dot { background: #e8590c; border-color: #e8590c; }
.cp-step-line { flex: 0 0 18px; height: 2px; background: var(--wf-border); }

/* workflow notifications (bottom-right; top of the screen on phones) */
.wf-notif-stack { position: fixed; right: 16px; bottom: 16px; z-index: 2000; display: flex; flex-direction: column; gap: 8px; width: 340px; max-width: calc(100vw - 32px); }
@media (max-width: 575.98px) { .wf-notif-stack { top: 12px; bottom: auto; right: 12px; left: 12px; width: auto; max-width: none; } }
.wf-notif {
    display: flex; align-items: flex-start; gap: 10px; padding: 12px 12px 12px 14px; border-radius: 12px; cursor: pointer;
    background: var(--wf-surface-solid); color: var(--wf-text); box-shadow: 0 10px 30px rgba(0,0,0,.18), 0 0 0 1px var(--wf-border);
    border-left: 4px solid var(--wf-accent);
}
.wf-notif.is-new { animation: wf-notif-in .25s ease; }
.wf-notif.tone-warning { border-left-color: #f59f00; }
.wf-notif.tone-success { border-left-color: #2f9e44; }
.wf-notif.tone-danger { border-left-color: #e8590c; }
.wf-notif-ico { margin-top: 2px; color: var(--wf-muted); }
.wf-notif.tone-warning .wf-notif-ico { color: #f59f00; }
.wf-notif.tone-success .wf-notif-ico { color: #2f9e44; }
.wf-notif.tone-danger .wf-notif-ico { color: #e8590c; }
.wf-notif-body { flex: 1 1 auto; min-width: 0; font-size: .84rem; line-height: 1.35; }
.wf-notif-body strong { display: block; font-size: .88rem; }
.wf-notif-body span { display: block; color: var(--wf-muted); word-break: break-word; }
.wf-notif-close { border: 0; background: transparent; color: var(--wf-muted); font-size: 1.2rem; line-height: 1; padding: 0 2px; }
.wf-notif-more {
    display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 6px 12px; border-radius: 10px; font-size: .8rem;
    background: var(--wf-surface-solid); color: var(--wf-muted); box-shadow: 0 4px 14px rgba(0,0,0,.12), 0 0 0 1px var(--wf-border);
}
.wf-notif-more button { border: 0; background: transparent; color: var(--wf-accent); font-weight: 600; padding: 0; }
@keyframes wf-notif-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

/* drop zones */
.cp-drop {
    border: 2px dashed var(--wf-border); border-radius: 10px; padding: 16px; text-align: center; cursor: pointer;
    color: var(--wf-muted); font-size: .85rem; background: var(--wf-surface);
}
.cp-drop:hover, .cp-drop:focus, .cp-drop.is-over { border-color: var(--wf-accent); color: var(--wf-text); outline: none; }
.cp-drop i { font-size: 1.3rem; display: block; }
.cp-drop-compact { padding: 10px 12px; }
.cp-drop-compact i { display: inline; font-size: 1rem; }
</style>
