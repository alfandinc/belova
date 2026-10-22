@extends('layouts.marketing.app')

@section('title', 'Content Plan')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@push('styles')
{{-- "Add to Home Screen": opens Content Plan like an app on the team's phones --}}
<link rel="manifest" href="/content-plan.webmanifest">
<meta name="theme-color" content="#7b52ab">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Content Plan">
<link rel="apple-touch-icon" href="/img/pwa/content-plan-192.png">
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-bs4.min.css" rel="stylesheet">
@include('marketing.partials.workflow_styles')
<style>
/* page header, segmented tabs, chips, card buttons and step bars: marketing/partials/workflow_styles */

/* ---------- toolbar ---------- */
.cp-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 14px; }
.cp-weeknav { display: flex; align-items: center; gap: 4px; position: relative; }
.cp-icon-btn {
    width: 34px; height: 34px; border-radius: 8px; border: 1px solid var(--wf-border); background: var(--wf-surface-solid);
    color: var(--wf-text); display: inline-flex; align-items: center; justify-content: center;
}
.cp-icon-btn:hover { border-color: var(--wf-accent); }
.cp-week-label {
    border: 0; background: transparent; color: var(--wf-text); font-weight: 700; font-size: 1.02rem;
    padding: 4px 8px; border-radius: 8px; white-space: nowrap;
}
.cp-week-label:hover { background: var(--wf-surface-2); }
.cp-week-label i { font-size: .7rem; color: var(--wf-muted); margin-left: 4px; }
.cp-week-date { position: absolute; left: 40px; top: 100%; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
.cp-ghost-btn { border: 1px solid var(--wf-border); background: var(--wf-surface-solid); color: var(--wf-text); border-radius: 8px; padding: 5px 12px; font-size: .82rem; font-weight: 600; margin-left: 6px; }
.cp-ghost-btn:hover { border-color: var(--wf-accent); }
.cp-toolbar-right { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-left: auto; }
@media (max-width: 767.98px) {
    .cp-top .cp-new { width: 100%; }
    .cp-weeknav { width: 100%; }
    .cp-week-label { flex: 1 1 auto; text-align: center; }
    .cp-toolbar-right { margin-left: 0; width: 100%; }
}

/* ---------- toolbar by purpose: AKSI (do something) · FILTER (which content) · TAMPILAN (how it looks) ·
   PERLU PERHATIAN (information, click to see the list) ---------- */
.cp-toolbar { justify-content: space-between; }
.cp-mainbar { gap: 10px 16px; }
.cp-mainbar #cpMainTabs { flex: 0 0 auto; }
@media (max-width: 767.98px) { .cp-mainbar #cpMainTabs { width: 100%; } .cp-mainbar #cpMainTabs .nav-link { flex: 1 1 0; text-align: center; } }
.cp-tb-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-left: auto; }
.cp-action-btn {
    display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--wf-accent); background: var(--wf-surface-solid);
    color: var(--wf-accent); border-radius: 8px; padding: 6px 12px; font-size: .82rem; font-weight: 700; white-space: nowrap;
    transition: background .15s, color .15s;
}
.cp-action-btn:hover, .cp-action-btn:focus { background: var(--wf-accent); color: #fff; outline: none; }
.cp-action-btn:hover .cp-insight-last { color: rgba(255,255,255,.8); }
.cp-action-btn.is-icon { padding: 6px 10px; }
.cp-action-btn[disabled] { opacity: .6; }
.cp-controls {
    display: flex; flex-wrap: wrap; align-items: center; gap: 10px 28px; padding: 8px 12px; margin-bottom: 12px;
    border-radius: 12px; background: var(--wf-surface-2);
}
.cp-ctl-group { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; min-width: 0; }
.cp-ctl-label { font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--wf-muted); white-space: nowrap; }
.cp-ctl-label i { margin-right: 3px; }
.cp-brand-filter { display: flex; flex-wrap: wrap; gap: 6px; }
.cp-filter-chip {
    display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--wf-border); background: var(--wf-surface-solid);
    color: var(--wf-text); border-radius: 999px; padding: 4px 12px; font-size: .8rem; font-weight: 600; white-space: nowrap; cursor: pointer;
}
.cp-filter-chip:hover { border-color: var(--wf-accent); }
.cp-filter-chip.active { background: var(--wf-accent); border-color: var(--wf-accent); color: #fff; }
.cp-filter-chip .cp-logo { width: 18px; height: 18px; box-shadow: 0 0 0 1px var(--wf-border); }
/* search: filters this week's board and lists matches of all weeks below the box */
.cp-search { position: relative; width: 240px; max-width: 100%; }
.cp-search input {
    width: 100%; border: 1px solid var(--wf-border); background: var(--wf-surface-solid); color: var(--wf-text);
    border-radius: 999px; padding: 5px 28px 5px 30px; font-size: .82rem; outline: none;
}
.cp-search input:focus { border-color: var(--wf-accent); box-shadow: 0 0 0 3px rgba(13,110,253,.15); }
.cp-search input::-webkit-search-cancel-button { display: none; }
.cp-search.is-active input { border-color: var(--wf-accent); }
.cp-search-ico { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); font-size: .75rem; color: var(--wf-muted); pointer-events: none; }
.cp-search-clear {
    position: absolute; right: 6px; top: 50%; transform: translateY(-50%); border: 0; background: none;
    color: var(--wf-muted); font-size: 1.1rem; line-height: 1; padding: 0 4px; cursor: pointer;
}
.cp-search-drop {
    position: absolute; z-index: 1050; top: calc(100% + 6px); left: 0; width: 420px; max-width: calc(100vw - 32px);
    max-height: 60vh; overflow-y: auto; background: var(--wf-surface-solid); border: 1px solid var(--wf-border);
    border-radius: 12px; box-shadow: 0 12px 32px rgba(0,0,0,.18); padding: 6px;
}
.cp-search-head { font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--wf-muted); padding: 6px 8px 4px; }
.cp-search-item { display: block; width: 100%; text-align: left; border: 0; background: none; color: var(--wf-text); border-radius: 8px; padding: 7px 8px; cursor: pointer; }
.cp-search-item:hover, .cp-search-item.is-focus { background: var(--wf-surface-2); }
.cp-search-item .t { font-weight: 600; font-size: .85rem; line-height: 1.35; }
.cp-search-item .t mark { background: rgba(250,204,21,.45); color: inherit; padding: 0; border-radius: 2px; }
.cp-search-item .m { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 8px; font-size: .74rem; color: var(--wf-muted); margin-top: 2px; }
.cp-search-item .m .wf-pill { font-size: .66rem; padding: 0 7px; }
.cp-search-empty { padding: 14px 8px; text-align: center; font-size: .82rem; color: var(--wf-muted); }
@media (max-width: 767.98px) { .cp-search { width: 100%; } .cp-search-drop { width: 100%; } }
.cp-alerts { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 12px; }
.cp-alert {
    display: inline-flex; align-items: center; gap: 6px; border: 0; border-radius: 8px; padding: 5px 12px;
    font-size: .8rem; font-weight: 700; cursor: pointer; white-space: nowrap;
}
.cp-alert:hover { filter: brightness(.96); text-decoration: underline; }
.cp-alert.is-danger { background: var(--st-red-bg); color: var(--st-red-fg); }
.cp-alert.is-warn { background: var(--st-amber-bg); color: var(--st-amber-fg); }
.cp-alert.is-idea { background: var(--st-indigo-bg); color: var(--st-indigo-fg); }
@media (max-width: 767.98px) {
    .cp-tb-actions { margin-left: 0; width: 100%; }
    .cp-controls { gap: 10px; }
}

/* ---------- week board: wide day columns (about 4 per row on a laptop), agenda list on phones ---------- */
/* days as a slider: N comfortable columns visible (N from the screen width, set by script as --cp-col-w),
   ◀ ▶ move one day; each day scrolls down on its own. Phones: one day under the other. */
/* width 0 + min-width 100%: the slider takes the page width but never widens the page with its 7 days
   (otherwise the whole page grew sideways and the columns were measured on that wider page) */
.cp-board-wrap, .cp-day-bar { width: 0; min-width: 100%; }
.cp-board-wrap { position: relative; }
.cp-page { min-width: 0; }
.cp-board {
    display: flex; gap: 12px; align-items: flex-start; overflow-x: auto; scroll-behavior: smooth;
    scroll-snap-type: x mandatory; scrollbar-width: none;
}
.cp-board::-webkit-scrollbar { display: none; }
.cp-day {
    background: var(--wf-surface-2); border-radius: 12px; min-width: 0; display: flex; flex-direction: column;
    box-shadow: 0 1px 3px rgba(0,0,0,.07), 0 0 0 1px var(--wf-border);
    flex: 0 0 var(--cp-col-w, 340px); max-height: calc(100vh - 230px); scroll-snap-align: start;
}
.cp-day-list { overflow-y: auto; scrollbar-width: thin; }
/* bar above the slider: ◀ day chips ▶ (the arrows never cover a day) */
.cp-day-bar { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.cp-slide-btn {
    flex: 0 0 auto; width: 32px; height: 32px; border-radius: 50%; border: 1px solid var(--wf-border);
    background: var(--wf-surface-solid); color: var(--wf-text);
    display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: opacity .15s, border-color .15s;
}
.cp-slide-btn:hover { color: var(--wf-accent); border-color: var(--wf-accent); }
.cp-slide-btn[disabled] { opacity: .35; cursor: default; }
/* day chips: jump to a day; the days in view are marked */
.cp-day-nav { display: flex; gap: 6px; overflow-x: auto; scrollbar-width: none; flex: 1 1 auto; min-width: 0; }
.cp-day-nav::-webkit-scrollbar { display: none; }
.cp-day-chip {
    flex: 0 0 auto; border: 1px solid var(--wf-border); background: var(--wf-surface-solid); color: var(--wf-muted);
    border-radius: 999px; padding: 4px 12px; font-size: .78rem; font-weight: 600; white-space: nowrap; cursor: pointer;
}
.cp-day-chip b { color: var(--wf-text); font-weight: 800; margin-left: 3px; }
.cp-day-chip .n { margin-left: 5px; font-size: .68rem; padding: 0 6px; border-radius: 999px; background: var(--wf-surface-2); }
.cp-day-chip.in-view { border-color: var(--wf-accent); color: var(--wf-accent); background: rgba(13,110,253,.06); }
.cp-day-chip.is-today { box-shadow: inset 0 0 0 1px var(--wf-accent); }
@media (max-width: 575.98px) {
    .cp-day-bar { display: none; }
    .cp-board { flex-direction: column; overflow-x: visible; gap: 8px; scroll-snap-type: none; }
    .cp-day { flex: 0 0 auto; width: 100%; max-height: none; }
    .cp-day-list { overflow-y: visible; }
}
/* section icons on the cards: hidden unless "Ikon bagian" is on (the workflow buttons always show) */
.cp-board:not(.show-tools) .cp-item-tools { display: none; }
/* "Ringkas": title, time, status, PIC and the next step only */
.cp-board.is-compact .cp-thumb, .cp-board.is-compact .cp-item-tools, .cp-board.is-compact .cp-item-tags,
.cp-board.is-compact .cp-icons, .cp-board.is-compact .cp-insight, .cp-board.is-compact .cp-late { display: none; }
.cp-board.is-compact .cp-item { --py: 9px; --px: 12px; padding-bottom: 8px; margin-bottom: 8px; }
.cp-board.is-compact .cp-item-title { font-size: .88rem; -webkit-line-clamp: 2; }
.cp-board.is-compact .cp-item-top { margin-bottom: 6px; padding-top: 7px; padding-bottom: 6px; }
.cp-board.is-compact .cp-item-foot { margin-top: 6px; padding-top: 6px; }
.cp-board.is-compact .cp-item-actions { margin-top: 6px; }
/* day header: "Jumat 23 Oktober 2026" written out */
.cp-day-head { display: flex; align-items: center; gap: 8px; padding: 10px 10px 8px 12px; }
.cp-day-date { display: flex; flex-wrap: wrap; align-items: baseline; gap: 0 6px; min-width: 0; font-size: .92rem; color: var(--wf-text); line-height: 1.3; }
.cp-day-date strong { font-weight: 800; }
.cp-day-date span { color: var(--wf-muted); font-weight: 600; }
.cp-day-today { font-size: .68rem; font-weight: 800; padding: 2px 8px; border-radius: 999px; background: var(--wf-accent); color: #fff; white-space: nowrap; }
/* today: its column header is tinted (no outline around the whole column) */
.cp-day.is-today .cp-day-head { background: rgba(13,110,253,.12); }
:root.theme-dark .cp-day.is-today .cp-day-head { background: rgba(110,168,254,.2); }
.cp-day.is-today .cp-day-date strong { color: var(--wf-accent); }
.cp-day.is-past .cp-day-date, .cp-day.is-past .cp-day-date span { color: var(--wf-muted); }
.cp-day-stat { font-size: .7rem; font-weight: 700; padding: 1px 7px; border-radius: 999px; background: var(--wf-surface-solid); color: var(--wf-muted); }
.cp-day-stat.green { background: var(--st-green-bg); color: var(--st-green-fg); }
.cp-day-stat.red { background: var(--st-red-bg); color: var(--st-red-fg); }
.cp-day-stat.yellow { background: var(--st-amber-bg); color: var(--st-amber-fg); }
.cp-day-add {
    margin-left: auto; width: 28px; height: 28px; border-radius: 8px; border: 0; background: transparent; color: var(--wf-muted);
    display: inline-flex; align-items: center; justify-content: center; opacity: .6; transition: opacity .15s, background .15s;
}
.cp-day:hover .cp-day-add, .cp-day-add:focus { opacity: 1; }
.cp-day-add:hover { background: var(--wf-surface-solid); color: var(--wf-accent); }
@media (hover: none) { .cp-day-add { opacity: 1; } }
.cp-day-list { padding: 4px 8px 8px; min-height: 64px; flex: 1 1 auto; }
.cp-day-head { position: sticky; top: 0; z-index: 2; background: inherit; border-radius: 12px 12px 0 0; }
.cp-day-empty { color: var(--wf-muted); font-size: .76rem; text-align: center; padding: 14px 0; opacity: .7; }
@media (max-width: 575.98px) {
    .cp-day-list { min-height: 0; }
    .cp-day-empty { text-align: left; padding: 4px 4px 8px; }
}

/* ---------- content card ---------- */
/* every card has the same body; only its header (logo · time · status) carries the clinic colour */
.cp-item {
    --py: 12px; --px: 14px;
    position: relative; border-radius: 12px; padding: var(--py) var(--px) 10px; margin-bottom: 10px; overflow: hidden;
    background: var(--wf-surface-solid);
    cursor: pointer; color: var(--wf-text); box-shadow: 0 1px 2px rgba(0,0,0,.05), 0 0 0 1px var(--wf-border);
    transition: box-shadow .15s ease;
}
.cp-item::before { content: none; }
.cp-item:last-child { margin-bottom: 0; }
.cp-item:hover, .cp-item:focus { outline: none; box-shadow: 0 6px 18px rgba(0,0,0,.10), 0 0 0 2px var(--brand, var(--wf-accent)); }

.cp-item-top {
    display: flex; align-items: center; gap: 8px;
    margin: calc(-1 * var(--py)) calc(-1 * var(--px)) 10px; padding: 9px var(--px) 8px;
    background: var(--brand-bg, var(--wf-surface-2));
}
/* the time is the second thing read after the title: a clear dark badge with a clock */
.cp-item-time {
    display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 8px;
    font-weight: 800; font-size: .9rem; color: var(--wf-text); background: var(--wf-surface-solid);
    box-shadow: inset 0 0 0 1px var(--wf-border); font-variant-numeric: tabular-nums; line-height: 1.2; white-space: nowrap;
}
.cp-item-time i { font-size: .75rem; color: var(--brand, var(--wf-accent)); }
.cp-item-when { display: inline-flex; align-items: center; gap: 5px; font-size: .76rem; font-weight: 600; color: var(--wf-muted); white-space: nowrap; margin-top: 3px; }
.cp-item-when i { font-size: .7rem; }
.cp-item-top > .cp-status, .cp-item-top > .wf-pill { flex-shrink: 0; }
.cp-item-top .wf-pill { margin-left: auto; font-size: .68rem; padding: 1px 8px; opacity: .9; }
.cp-warn { color: var(--wf-danger); font-size: .8rem; animation: cp-blink 1.2s linear infinite; }
@keyframes cp-blink { 50% { opacity: .3; } }

/* the title is what the card is about: biggest and darkest text, everything else is quieter */
.cp-item-title {
    font-weight: 700; font-size: .93rem; line-height: 1.4; color: var(--wf-text); word-break: break-word;
    display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
}
/* title with an optional preview square on its left */
.cp-item-main { display: flex; gap: 10px; align-items: flex-start; }
.cp-item-main-text { flex: 1 1 auto; min-width: 0; }
.cp-thumb {
    position: relative; flex: 0 0 auto; width: 64px; height: 64px; border-radius: 10px; overflow: hidden; display: block;
    background: var(--wf-surface-2); box-shadow: 0 0 0 1px var(--wf-border);
}
.cp-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
a.cp-thumb:hover { box-shadow: 0 0 0 2px var(--brand, var(--wf-accent)); }
.cp-thumb.is-ref img { opacity: .85; }
.cp-thumb b {
    position: absolute; left: 4px; bottom: 4px; font-size: .58rem; font-weight: 700; line-height: 1; padding: 2px 5px;
    border-radius: 5px; background: rgba(0,0,0,.6); color: #fff;
}
.cp-item .cp-item-tags { align-items: center; gap: 4px 10px; margin-top: 6px; }
.cp-item .cp-item-tags .cp-tag { font-size: .68rem; font-weight: 600; padding: 1px 7px; }
/* pillar: a quiet coloured label, not a chip */
.cp-pilar { display: inline-flex; align-items: center; gap: 5px; font-size: .72rem; font-weight: 600; color: var(--p, var(--wf-muted)); }
.cp-pilar::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: var(--p, var(--wf-muted)); }
/* published: pill, then go-live date/time, then lateness — stacked on the right of the top row */
.cp-item-top { align-items: flex-start; }
.cp-item-top > .cp-logos, .cp-item-top > .cp-item-time { margin-top: 1px; }
.cp-status { margin-left: auto; display: flex; flex-direction: column; align-items: flex-end; gap: 2px; text-align: right; min-width: 0; }
.cp-status .wf-pill { margin-left: 0; font-size: .68rem; padding: 1px 8px; }
.cp-late { display: inline-flex; align-items: center; gap: 4px; font-size: .7rem; font-weight: 600; color: var(--st-amber-fg); white-space: nowrap; }
.cp-late i { font-size: .62rem; }
.cp-item-top .wf-pill i { font-size: .6rem; }
/* brand chips (each in its own brand colour) + pilar / jenis tags; wraps instead of truncating */
.cp-item-tags { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 8px; }
.cp-chip-opt .cp-logo { width: 22px; height: 22px; box-shadow: 0 0 0 1px var(--wf-border); }
/* clinic logos (brand) in the card's top row and in idea cards / brand pickers */
.cp-logos { display: inline-flex; align-items: center; }
.cp-logos .cp-logo + .cp-logo { margin-left: -6px; }
.cp-logo {
    width: 22px; height: 22px; border-radius: 50%; flex: 0 0 auto; overflow: hidden; background: #fff;
    display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 0 0 2px var(--wf-surface-solid), 0 0 0 3px var(--wf-border);
}
.cp-logo img { width: 100%; height: 100%; object-fit: contain; }
.cp-logo.has-bg img { padding: 3px; }
.cp-logo.is-text { color: #fff; font-size: .55rem; font-weight: 700; }
.cp-brand-opt { display: inline-flex; align-items: center; gap: 8px; }
.cp-brand-opt .cp-logo { width: 20px; height: 20px; box-shadow: 0 0 0 1px var(--wf-border); }
.cp-tag-brand {
    --c: var(--wf-muted);
    color: var(--c); background: color-mix(in srgb, var(--c) 12%, transparent);
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--c) 30%, transparent);
}
.cp-tag-brand::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: var(--c); flex: 0 0 auto; }

.cp-item-foot { display: flex; align-items: center; gap: 8px; margin-top: 10px; padding-top: 8px; border-top: 1px dashed var(--wf-border); }
.cp-pic { display: inline-flex; align-items: center; gap: 6px; min-width: 0; flex: 1 1 auto; font-size: .76rem; font-weight: 500; color: var(--wf-muted); }
.cp-pic::before { content: '\f007'; font-family: 'Font Awesome 5 Free'; font-weight: 400; font-size: .7rem; opacity: .8; }
.cp-pic > span:last-child { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cp-pic.is-empty { color: var(--wf-muted); font-weight: 400; }
.cp-avatar {
    width: 22px; height: 22px; border-radius: 50%; flex: 0 0 auto; font-size: .6rem; font-weight: 700;
    display: inline-flex; align-items: center; justify-content: center; background: var(--st-blue-bg); color: var(--st-blue-fg);
}
.cp-icons { display: inline-flex; align-items: center; gap: 4px; flex: 0 0 auto; }
.cp-plat {
    position: relative; width: 26px; height: 26px; border-radius: 7px; display: inline-flex; align-items: center; justify-content: center;
    background: var(--wf-surface-2); font-size: .9rem; line-height: 1;
}
.cp-plat svg { width: 13px; height: 13px; fill: currentColor; }
a.cp-plat { box-shadow: 0 0 0 1.5px #2f9e44; text-decoration: none; }
a.cp-plat:hover { background: var(--st-green-bg); }
a.cp-plat::after {
    content: ''; position: absolute; right: -3px; top: -3px; width: 9px; height: 9px; border-radius: 50%;
    background: #2f9e44; border: 2px solid var(--wf-surface-solid);
}
.cp-design-icon { width: 26px; height: 26px; border-radius: 7px; display: inline-flex; align-items: center; justify-content: center; font-size: .78rem; background: var(--st-indigo-bg); color: var(--st-indigo-fg); }
.cp-item.ui-sortable-helper { box-shadow: 0 10px 28px rgba(0,0,0,.25); }
.cp-item-placeholder { border: 2px dashed var(--wf-accent); border-radius: 10px; height: 64px; margin-bottom: 8px; }

.cp-tags { display: flex; flex-wrap: wrap; gap: 4px; }

/* ---------- "Konten Baru" chooser ---------- */
.cp-choice {
    display: flex; align-items: center; gap: 14px; width: 100%; text-align: left; border: 1px solid var(--wf-border);
    background: var(--wf-surface-solid); color: var(--wf-text); border-radius: 12px; padding: 14px; margin-bottom: 10px;
    transition: border-color .15s, box-shadow .15s;
}
.cp-choice:last-child { margin-bottom: 0; }
.cp-choice:hover, .cp-choice:focus { border-color: var(--wf-accent); box-shadow: 0 0 0 3px rgba(13,110,253,.12); outline: none; }
.cp-choice-icon { width: 44px; height: 44px; border-radius: 12px; flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; font-size: 1.15rem; }
.cp-choice-icon.idea { background: var(--st-amber-bg); color: var(--st-amber-fg); }
.cp-choice-icon.plan { background: var(--st-blue-bg); color: var(--st-blue-fg); }
.cp-choice-icon.routine { background: var(--st-green-bg); color: var(--st-green-fg); }

/* ---------- konten rutin: compact card, no workflow; dashed ring tells it apart ---------- */
.cp-item.cp-routine { --py: 10px; --px: 12px; padding-bottom: 8px; }
.cp-item.cp-routine .cp-item-title { -webkit-line-clamp: 2; }
.cp-rt-ico { font-size: .68rem; color: var(--wf-muted); }
.cp-item.cp-routine.is-late { box-shadow: 0 0 0 1.5px var(--wf-danger); }
.cp-item.cp-routine.is-posted, .cp-item.cp-routine.is-skipped { opacity: .78; }
.cp-item.cp-routine .cp-item-foot { margin-top: 8px; padding-top: 6px; }
.cp-rt-done { margin-top: 8px; font-size: .76rem; font-weight: 600; color: var(--st-green-fg); display: flex; align-items: center; gap: 6px; }
.cp-rt-done.is-skip { color: var(--wf-muted); font-weight: 400; }
.cp-rt-done.is-late { color: var(--st-amber-fg); }

/* ---------- Instagram insight ---------- */
.cp-insight-group { display: inline-flex; align-items: center; gap: 4px; }
.cp-insight-last { font-weight: 400; color: var(--wf-muted); font-size: .74rem; }
.cp-chip.active .cp-insight-last { color: rgba(255,255,255,.8); }
/* numbers under the "Published" pill (right-aligned, one line) */
.cp-insight { display: inline-flex; flex-wrap: wrap; justify-content: flex-end; align-items: center; gap: 2px 9px; font-size: .74rem; color: var(--wf-muted); font-weight: 600; white-space: nowrap; }
.cp-insight i { font-size: .68rem; margin-right: 3px; }
.cp-insight .up { color: var(--st-green-fg); font-weight: 700; }
.cp-insight-box { border: 1px solid var(--wf-border); border-radius: 10px; padding: 10px 12px; background: var(--wf-surface-2); }
.cp-insight-table td, .cp-insight-table th { white-space: nowrap; font-size: .8rem; }
.cp-insight-table .up { color: var(--st-green-fg); font-size: .72rem; margin-left: 4px; }
.cp-insight-svg { display: block; max-width: 100%; height: auto; }
.cp-insight-svg .line { fill: none; stroke: var(--wf-accent); stroke-width: 2; }
.cp-insight-svg .area { fill: var(--wf-accent); opacity: .08; }
.cp-insight-svg .dot { fill: var(--wf-accent); }
.cp-insight-svg text { fill: var(--wf-muted); font-size: 10px; }
.cp-besttime { font-size: .8rem; margin: -6px 0 14px; padding: 8px 10px; border-radius: 8px; background: var(--st-amber-bg); color: var(--st-amber-fg); line-height: 1.45; }
.cp-besttime button { border: 0; background: transparent; color: inherit; font-weight: 700; text-decoration: underline; padding: 0; }
.cp-designer-load { font-size: .78rem; margin-top: 6px; color: var(--wf-muted); }
.cp-designer-load:empty { display: none; }
.cp-designer-load.is-sedang { color: var(--st-amber-fg); }
.cp-designer-load.is-padat { color: var(--wf-danger); }

/* ---------- saved content: one part at a time ---------- */
#contentPlanModal.is-section .cp-group-title { display: none; }
.cp-summary { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 12px; }
.cp-sum-box { border-radius: 12px; padding: 12px 14px; background: var(--wf-surface-2); min-width: 0; }
.cp-sum-head { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: .9rem; }
.cp-sum-head > i { color: var(--wf-muted); width: 16px; text-align: center; }
.cp-sum-head .btn { font-size: .76rem; padding: 2px 10px; border-radius: 8px; }
.cp-sum-title { font-weight: 800; font-size: 1rem; line-height: 1.35; margin-bottom: 6px; word-break: break-word; }
.cp-sum-text { font-size: .84rem; line-height: 1.5; color: var(--wf-text); white-space: pre-wrap; word-break: break-word; }
.cp-sum-row { display: grid; grid-template-columns: 120px 1fr; gap: 8px; font-size: .84rem; padding: 4px 0; border-top: 1px dashed var(--wf-border); }
.cp-sum-row:first-of-type { border-top: 0; }
.cp-sum-row > span { color: var(--wf-muted); }
.cp-sum-row > div { min-width: 0; word-break: break-word; }
.cp-sum-row .cp-logo { width: 18px; height: 18px; vertical-align: middle; }
.cp-sum-plat { display: inline-flex; align-items: center; gap: 5px; margin-right: 8px; white-space: nowrap; }
.cp-sum-tl { font-size: .82rem; padding: 4px 0; border-top: 1px dashed var(--wf-border); }
.cp-sum-tl:first-of-type { border-top: 0; }

/* small icon buttons on a card: open one part directly */
.cp-item-tools { display: flex; gap: 4px; margin-top: 8px; }
.cp-tool {
    width: 30px; height: 28px; border-radius: 8px; border: 0; background: transparent; color: var(--wf-muted);
    display: inline-flex; align-items: center; justify-content: center; font-size: .8rem; cursor: pointer; transition: background .15s, color .15s;
}
.cp-tool:hover, .cp-tool:focus { background: var(--wf-surface-solid); color: var(--brand, var(--wf-accent)); outline: none; }

.cp-link-check { font-size: .78rem; margin-top: 4px; text-align: left; line-height: 1.4; }
.cp-link-check:empty { display: none; }
.cp-link-check.is-ok { color: var(--st-green-fg); }
.cp-link-check.is-warn { color: var(--st-amber-fg); }
.cp-link-check.is-bad { color: var(--wf-danger); }
.cp-link-check.is-wait { color: var(--wf-muted); }
.cp-ig-account { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.cp-ig-account .meta { flex: 1 1 200px; min-width: 0; font-size: .8rem; }
.cp-act.is-ghost { background: var(--wf-surface-2); color: var(--wf-text); flex: 0 0 auto; padding: 5px 12px; }
.cp-rt-row { display: flex; align-items: flex-start; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--wf-border); }
.cp-rt-row:last-child { border-bottom: 0; }
.cp-rt-row-date { width: 86px; flex: 0 0 auto; font-weight: 600; font-size: .82rem; }
.cp-rt-row-body { flex: 1 1 auto; min-width: 0; font-size: .8rem; }
.cp-rt-row-actions { display: flex; gap: 4px; flex-wrap: wrap; justify-content: flex-end; }
.cp-rt-row-actions .btn { padding: 1px 8px; font-size: .74rem; }
.cp-rt-list-item .cp-plat { width: 22px; height: 22px; font-size: .78rem; }
.cp-choice strong { display: block; font-size: .95rem; }
.cp-choice small { display: block; color: var(--wf-muted); font-size: .8rem; line-height: 1.4; margin-top: 2px; }
.cp-choice .fa-chevron-right { margin-left: auto; color: var(--wf-muted); }

/* Form groups (Dasar / Penugasan / Caption & publikasi) */
.cp-group:not(.is-first) { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--wf-border); }
.cp-stage-hint { margin-top: 16px; padding: 8px 12px; border-radius: 8px; font-size: .8rem; color: var(--wf-muted); background: var(--wf-surface-2); }
.cp-group-title { font-size: .78rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--wf-muted); margin-bottom: 10px; }
.cp-optional { font-weight: 400; letter-spacing: 0; text-transform: none; margin-left: 4px; }
#contentPlanModal.is-wizard .cp-group-title { display: none; }

/* New-content wizard progress */
.cp-wizard { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; }
.cp-wizard-step { display: flex; align-items: center; gap: 8px; flex: 0 0 auto; font-weight: 600; font-size: .88rem; color: var(--wf-muted); line-height: 1.15; }
.cp-wizard-step small { display: block; font-weight: 400; font-size: .7rem; }
.cp-wizard-step .dot {
    width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto;
    border: 2px solid var(--wf-border); background: var(--wf-surface-solid); font-size: .8rem;
}
.cp-wizard-step.is-active { color: var(--wf-text); }
.cp-wizard-step.is-active .dot { background: var(--wf-accent); border-color: var(--wf-accent); color: #fff; }
.cp-wizard-step.is-done .dot { background: var(--st-green-bg); border-color: transparent; color: var(--st-green-fg); }
.cp-wizard-line { flex: 1 1 24px; height: 2px; background: var(--wf-border); min-width: 12px; }
@media (max-width: 575.98px) { .cp-wizard-step small { display: none; } .cp-wizard-step { font-size: .8rem; } }

/* Workflow bar in the modal (bar + steps: workflow_styles) */
.cp-prep-list { display: flex; flex-wrap: wrap; gap: 6px 16px; font-weight: 600; }
.cp-prep { display: inline-flex; align-items: center; gap: 6px; color: var(--wf-muted); }
.cp-prep.is-ok { color: var(--st-green-fg); }
.cp-workflow-note { margin-top: 8px; padding: 8px 10px; border-radius: 8px; font-size: .85rem; background: var(--wf-surface); border: 1px solid var(--wf-border); white-space: pre-wrap; }

/* Image references (drop zone: workflow_styles) */
.cp-ref-grid:empty { display: none; }
.cp-ref-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); gap: 8px; }
.cp-ref { position: relative; border-radius: 8px; overflow: hidden; border: 1px solid var(--wf-border); aspect-ratio: 1 / 1; background: var(--wf-surface-2); }
.cp-ref img { width: 100%; height: 100%; object-fit: cover; cursor: zoom-in; display: block; }
.cp-ref-remove { position: absolute; top: 4px; right: 4px; width: 26px; height: 26px; border: 0; border-radius: 50%; background: rgba(201,42,42,.92); color: #fff; line-height: 1; }

/* Ide Konten cards */
.cp-idea-toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 12px; }
.cp-idea-toolbar .wf-filter-chips { flex: 1 1 100%; }
.cp-idea-filters { display: flex; flex-wrap: wrap; gap: 8px; flex: 1 1 100%; }
.cp-idea-filters .form-control { flex: 1 1 180px; min-width: 0; max-width: 280px; border-radius: 8px; }
@media (max-width: 575.98px) { .cp-idea-filters .form-control { max-width: none; } }
.cp-idea-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 12px; }
.cp-idea { display: flex; flex-direction: column; gap: 8px; }
.cp-idea-head { display: flex; gap: 10px; }
.cp-idea-thumb { width: 64px; height: 64px; border-radius: 8px; object-fit: cover; flex: 0 0 auto; cursor: zoom-in; border: 1px solid var(--wf-border); }
.cp-idea-note { font-size: .84rem; padding: 6px 10px; border-radius: 8px; background: var(--wf-surface-2); white-space: pre-wrap; word-break: break-word; }
.cp-idea .wf-actions { margin-top: auto; padding-top: 4px; }
.cp-idea .wf-actions .btn { flex: 1 1 auto; }

.cl-preview { position: relative; display: inline-block; }
.cl-preview img { max-width: 220px; max-height: 180px; border-radius: 10px; border: 1px solid var(--wf-border); cursor: zoom-in; }
.cl-preview .cp-ref-remove { top: 6px; right: 6px; }

.note-editor.note-frame { border-color: var(--wf-border); }
</style>
@endpush

@section('content')
<div class="container-fluid cp-page">
    <div class="cp-top">
        <div>
            <h4>Content Plan</h4>
            <p>Kalender konten sosmed, dari ide sampai report.</p>
        </div>
        <div class="cp-top-actions">
            <button type="button" class="btn btn-light cp-install" id="btnNotif" style="display:none"><i class="far fa-bell mr-1"></i> Aktifkan notifikasi</button>
            <button type="button" class="btn btn-light cp-install" id="btnInstallApp" style="display:none"><i class="fas fa-mobile-alt mr-1"></i> Pasang di HP</button>
            @if($canManage)
                <button type="button" class="btn btn-primary cp-new" id="btnNewContent"><i class="fas fa-plus mr-1"></i> Konten Baru</button>
            @endif
        </div>
    </div>

    {{-- one row: Jadwal / Ide Konten · week (Jadwal only) · actions (Jadwal only) --}}
    <div class="cp-toolbar cp-mainbar">
                <div class="nav cp-seg" id="cpMainTabs" role="tablist">
                    <a class="nav-link active" id="schedule-tab" data-toggle="tab" href="#scheduleTab" role="tab"><i class="far fa-calendar mr-1"></i> Jadwal</a>
                    <a class="nav-link" id="idea-tab" data-toggle="tab" href="#ideaTab" role="tab"><i class="far fa-lightbulb mr-1"></i> Ide Konten <span class="wf-count" id="ideaPendingCount" style="display:none" title="Menunggu approval"></span></a>
                </div>
                <div class="cp-weeknav js-schedule-only">
                    <button type="button" class="cp-icon-btn" id="btnPrevWeek" title="Minggu sebelumnya"><i class="fas fa-chevron-left"></i></button>
                    <button type="button" class="cp-week-label" id="btnWeekLabel" title="Pilih tanggal"><span id="cpWeekLabel"></span><i class="fas fa-chevron-down"></i></button>
                    <input type="date" class="cp-week-date" id="cpWeekDate" tabindex="-1" aria-label="Pilih minggu">
                    <button type="button" class="cp-icon-btn" id="btnNextWeek" title="Minggu berikutnya"><i class="fas fa-chevron-right"></i></button>
                    <button type="button" class="cp-ghost-btn" id="btnThisWeek">Hari ini</button>
                </div>
                {{-- AKSI: buttons that do something --}}
                <div class="cp-tb-actions js-schedule-only">
                    <button type="button" class="cp-action-btn" id="btnRoutines" title="Kelola konten yang diupload rutin (mis. We Are Open)"><i class="fas fa-redo-alt"></i> Konten rutin</button>
                    @if($canManage)
                        <span class="cp-insight-group">
                            <button type="button" class="cp-action-btn" id="btnInsightSync" title="Ambil views, likes, komentar dll. konten Instagram yang sudah Published"><i class="fas fa-sync-alt"></i> Update insight <span class="cp-insight-last" id="cpInsightLast"></span></button>
                            <button type="button" class="cp-action-btn is-icon" id="btnIgAccounts" title="Akun Instagram yang terhubung"><i class="fab fa-instagram"></i></button>
                        </span>
                    @endif
                </div>
    </div>

    <div class="tab-content">
        {{-- JADWAL --}}
        <div class="tab-pane fade show active" id="scheduleTab" role="tabpanel">
            {{-- FILTER (which content shows) · TAMPILAN (how the board looks) --}}
            <div class="cp-controls">
                <div class="cp-ctl-group">
                    <span class="cp-ctl-label"><i class="fas fa-filter"></i> Filter</span>
                    <div class="cp-search" id="cpSearch">
                        <i class="fas fa-search cp-search-ico"></i>
                        <input type="search" id="cpSearchInput" placeholder="Cari judul konten…" autocomplete="off" aria-label="Cari konten" title="Cari judul konten di semua minggu (Ctrl+K)">
                        <button type="button" class="cp-search-clear" id="cpSearchClear" aria-label="Hapus pencarian" style="display:none">&times;</button>
                        <div class="cp-search-drop" id="cpSearchDrop" style="display:none"></div>
                    </div>
                    <div class="cp-brand-filter" id="cpBrandFilter" role="group" aria-label="Filter klinik"></div>
                    <button type="button" class="cp-filter-chip" id="btnMine" aria-pressed="false" title="Hanya konten dengan PIC saya"><i class="far fa-user"></i> Tugas saya</button>
                </div>
                <div class="cp-ctl-group">
                    <span class="cp-ctl-label"><i class="far fa-eye"></i> Tampilan</span>
                    <div class="cp-seg cp-seg-sm" id="cpMode" role="group" aria-label="Kelompokkan menurut">
                        <button type="button" class="active" data-mode="tanggal_publish" title="Kartu per tanggal & jam tayang">Tanggal publish</button>
                        <button type="button" data-mode="tanggal_produksi" title="Kartu per deadline produksi (kapan konten harus jadi)">Deadline produksi</button>
                    </div>
                    <div class="cp-seg cp-seg-sm" id="cpDensity" role="group" aria-label="Kepadatan kartu">
                        <button type="button" class="active" data-density="full" title="Kartu lengkap: pratinjau, tag, statistik">Lengkap</button>
                        <button type="button" data-density="compact" title="Kartu ringkas: lebih banyak konten terlihat">Ringkas</button>
                    </div>
                    <div class="cp-seg cp-seg-sm" role="group" aria-label="Ikon bagian di kartu">
                        <button type="button" id="btnTools" aria-pressed="false" title="Tampilkan ikon Dasar / PIC / Brief / Riwayat di setiap kartu"><i class="fas fa-th mr-1"></i>Ikon bagian</button>
                    </div>
                </div>
            </div>

            {{-- PERLU PERHATIAN: information only (click to see the list); hidden when there is nothing --}}
            <div class="cp-alerts" id="cpAlerts" style="display:none">
                <span class="cp-ctl-label"><i class="fas fa-bell"></i> Perlu perhatian</span>
                <button type="button" class="cp-alert is-danger" id="btnOverdue" style="display:none" title="Lihat konten terlewat"><i class="fas fa-exclamation-triangle"></i> <span id="stat_terlewat">0</span> terlewat</button>
                <button type="button" class="cp-alert is-warn" id="btnNoLink" style="display:none" title="Konten Instagram yang sudah Published tapi belum ada link postingannya, jadi insight-nya tidak terlacak"><i class="fas fa-link"></i> <span id="stat_nolink">0</span> belum ada link IG</button>
                <button type="button" class="cp-alert is-idea" id="cpReadyIdeas" style="display:none" title="Ide yang sudah di-approve, tinggal dijadwalkan"><i class="far fa-lightbulb"></i> <span id="cpReadyCount">0</span> ide siap dijadwalkan</button>
            </div>
            {{-- days as a slider: a few comfortable columns at a time, ◀ ▶ or a day chip to move --}}
            <div class="cp-day-bar">
                <button type="button" class="cp-slide-btn" id="cpSlidePrev" aria-label="Hari sebelumnya" title="Hari sebelumnya"><i class="fas fa-chevron-left"></i></button>
                <div class="cp-day-nav" id="cpDayNav"></div>
                <button type="button" class="cp-slide-btn" id="cpSlideNext" aria-label="Hari berikutnya" title="Hari berikutnya"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="cp-board-wrap">
                <div class="cp-board" id="weekBoard"></div>
            </div>
        </div>

        {{-- IDE KONTEN --}}
        <div class="tab-pane fade" id="ideaTab" role="tabpanel">
            <div class="cp-idea-toolbar">
                <div class="wf-filter-chips" id="ideaStatusChips">
                    <button type="button" class="wf-filter-chip active" data-status="pending">Menunggu Approval <span class="wf-count" data-count="pending">0</span></button>
                    <button type="button" class="wf-filter-chip" data-status="approved">Siap Dijadwalkan <span class="wf-count" data-count="approved">0</span></button>
                    <button type="button" class="wf-filter-chip" data-status="rejected">Rejected <span class="wf-count" data-count="rejected">0</span></button>
                    <button type="button" class="wf-filter-chip" data-status="scheduled">Sudah Dijadwalkan <span class="wf-count" data-count="scheduled">0</span></button>
                    <button type="button" class="wf-filter-chip" data-status="all">Semua</button>
                </div>
                <div class="cp-idea-filters">
                    <input type="search" class="form-control form-control-sm" id="ideaSearch" placeholder="Cari judul ide…">
                    <select class="form-control form-control-sm" id="ideaBrand">
                        <option value="">Semua brand</option>
                        @foreach($brands as $b)<option value="{{ $b['value'] }}">{{ $b['label'] }}</option>@endforeach
                    </select>
                    <select class="form-control form-control-sm" id="ideaPic">
                        <option value="">Semua PIC</option>
                        @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="cp-idea-grid" id="ideaList"></div>
            <div class="text-center mt-3"><button type="button" class="btn btn-light" id="ideaMore" style="display:none">Muat lagi</button></div>
        </div>
    </div>
</div>

{{-- "Konten Baru": idea (needs approval) or schedule directly --}}
<div class="modal fade" id="newContentModal" tabindex="-1" role="dialog" aria-labelledby="newContentTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newContentTitle">Konten baru</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <button type="button" class="cp-choice" data-choice="idea">
                    <span class="cp-choice-icon idea"><i class="far fa-lightbulb"></i></span>
                    <span><strong>Ajukan ide</strong><small>Belum pasti atau perlu persetujuan. Masuk ke daftar Ide Konten; setelah di-approve, tinggal dijadwalkan.</small></span>
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button type="button" class="cp-choice" data-choice="plan">
                    <span class="cp-choice-icon plan"><i class="far fa-calendar-plus"></i></span>
                    <span><strong>Jadwalkan langsung</strong><small>Sudah pasti tayang (mis. konten mendadak). Langsung masuk kalender, tetap di-review sebelum publish.</small></span>
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button type="button" class="cp-choice" data-choice="routine">
                    <span class="cp-choice-icon routine"><i class="fas fa-redo-alt"></i></span>
                    <span><strong>Konten rutin</strong><small>Dibuat sekali, diupload rutin (mis. <em>We Are Open</em> setiap hari jam 09.00). Muncul otomatis di jadwal, cukup tandai Sudah upload.</small></span>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@include('marketing.content_plan.partials.modal')
@include('marketing.content_plan.partials.content_list_modal')
@include('marketing.content_plan.partials.routine_modal')
@if($canManage)
    @include('marketing.content_plan.partials.insight_modal')
@endif

{{-- Konten terlewat --}}
<div class="modal fade wf-modal" id="overdueModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="overdueTitle">Konten terlewat</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body"><div id="overdueList" class="d-flex flex-column" style="gap:8px"></div></div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Tutup</button></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-bs4.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
@include('marketing.partials.workflow_scripts')
<script>
$(function () {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    var W = window.MkWf, esc = W.esc;
    var CAN_MANAGE = @json($canManage);
    var STATUS_LABELS = @json($statusLabels);
    var OVERDUE_STATUSES = @json($overdueStatuses);
    var ROUTES = {
        week: @json(route('marketing.content-plan.week')),
        statusList: @json(route('marketing.content-plan.status_list')),
        search: @json(route('marketing.content-plan.search')),
        store: @json(route('marketing.content-plan.store')),
        reportStore: @json(route('marketing.content-report.store')),
        ideaList: @json(route('marketing.content-list.list')),
        ideaStats: @json(route('marketing.content-list.stats')),
        ideaStore: @json(route('marketing.content-list.store')),
        designStore: @json(route('design-request.store')),
        designPage: @json(route('design-request.index'))
    };
    var PLAN_ACTIONS = @json(\App\Http\Controllers\Marketing\ContentPlanController::ACTION_LABELS);
    var IDEA_ACTIONS = @json(\App\Http\Controllers\Marketing\ContentListController::ACTION_LABELS);
    // brand value => {label, title, color, logo, logo_bg}: the clinics (+ BCL) with their logo & color
    // names offered when recording who approved / asked for a revision in the WA group
    var REVIEWERS = @json($reviewers);
    var SELF_APPROVER = @json($selfApprover ?? null);
    var BRAND_LIST = @json($brands), BRAND_META = {};
    (BRAND_LIST || []).forEach(function (b) { BRAND_META[b.value] = b; });
    var IS_TOUCH = ('ontouchstart' in window) || navigator.maxTouchPoints > 0;

    function planPill(status) { return W.pill('plan', status, STATUS_LABELS[status] || status); }

    // =================================================================== BOARD
    var scheduleBy = 'tanggal_publish';
    var onlyMine = false;
    var weekStart = moment().startOf('isoWeek');
    var rowsById = {};
    var boardSeq = 0;

    function weekEnd() { return weekStart.clone().endOf('isoWeek'); }

    // the board filters (Tugas saya, klinik) added to any list request
    var brandFilter = '';
    try { brandFilter = localStorage.getItem('cp_brand') || ''; } catch (e) {}
    function filterParams(p) {
        p = p || {};
        p.mine = onlyMine ? 1 : 0;
        if (brandFilter) p.filter_brand = [brandFilter];
        return p;
    }
    // search text: filters the board of the open week too (not the "perlu perhatian" counters)
    var searchQ = '';
    function boardParams() {
        var p = filterParams({ date_start: weekStart.format('DD/MM/YYYY'), date_end: weekEnd().format('DD/MM/YYYY'), schedule_by: scheduleBy });
        if (searchQ) p.q = searchQ;
        return p;
    }

    // Filter klinik: chips with the clinic logos (remembered in this browser)
    function renderBrandFilter() {
        var chip = function (value, inner, title) {
            return '<button type="button" class="cp-filter-chip' + (brandFilter === value ? ' active' : '') + '" data-brand="' + esc(value) + '" title="' + esc(title) + '">' + inner + '</button>';
        };
        $('#cpBrandFilter').html(chip('', 'Semua klinik', 'Semua klinik')
            + (BRAND_LIST || []).map(function (b) { return chip(b.value, brandLogo(b.value) + esc(b.label), b.title || b.label); }).join(''));
    }
    $('#cpBrandFilter').on('click', '.cp-filter-chip', function () {
        brandFilter = String($(this).data('brand') || '');
        try { localStorage.setItem('cp_brand', brandFilter); } catch (e) {}
        renderBrandFilter();
        loadBoard();
    });

    // "Perlu perhatian" only shows when one of its items has something
    function refreshAlerts() {
        $('#cpAlerts').toggle($('#cpAlerts .cp-alert').filter(function () { return this.style.display !== 'none'; }).length > 0);
    }

    // Same "terlewat" rule as the server (ContentPlanController::OVERDUE_STATUSES)
    function isOverdue(row) {
        if (!row.schedule_date || (OVERDUE_STATUSES[scheduleBy] || []).indexOf(row.status) === -1) return false;
        return scheduleBy === 'tanggal_produksi'
            ? moment(row.schedule_date, 'YYYY-MM-DD').isBefore(moment(), 'day')
            : moment(row.schedule_date).isBefore(moment());
    }

    function IS_DARK() { return document.documentElement.classList.contains('theme-dark'); }

    function rgba(hex, alpha) {
        var h = String(hex || '#6b7280').replace('#', '');
        if (h.length === 3) h = h.replace(/(.)/g, '$1$1');
        return 'rgba(' + parseInt(h.substr(0, 2), 16) + ',' + parseInt(h.substr(2, 2), 16) + ',' + parseInt(h.substr(4, 2), 16) + ',' + alpha + ')';
    }

    function brandColor(row) {
        var b = (row.brand || [])[0];
        return b ? ((BRAND_META[b] || {}).color || '#6b7280') : null;
    }

    // Round clinic logo (BCL: white logo on its color); brands without a logo get their first letters
    function brandLogo(b) {
        var m = BRAND_META[b] || { label: b, title: b, color: '#6b7280' };
        if (m.logo) {
            return '<span class="cp-logo' + (m.logo_bg ? ' has-bg' : '') + '" style="' + (m.logo_bg ? 'background:' + m.logo_bg : '') + '" title="' + esc(m.title || m.label) + '">'
                + '<img src="' + esc(m.logo) + '" alt="' + esc(m.label) + '"></span>';
        }
        var letters = String(b || '?').replace(/[^A-Za-z ]/g, '').trim().split(/\s+/).slice(0, 2).map(function (w) { return w.charAt(0).toUpperCase(); }).join('');
        return '<span class="cp-logo is-text" style="background:' + m.color + '" title="' + esc(m.title || b) + '">' + esc(letters || '?') + '</span>';
    }

    var PLATFORM_ICONS = { Instagram: 'fab fa-instagram', TikTok: 'fab fa-tiktok', YouTube: 'fab fa-youtube', Facebook: 'fab fa-facebook', Website: 'fas fa-globe', Other: 'fas fa-ellipsis-h' };

    function initials(name) {
        return String(name || '').replace(/[^A-Za-z\s]/g, ' ').trim().split(/\s+/).slice(0, 2).map(function (w) { return w.charAt(0).toUpperCase(); }).join('') || '?';
    }

    // platform logo colors (TikTok follows the text color so it shows in light and dark theme)
    var PLATFORM_COLORS = { Instagram: '#E4405F', TikTok: 'var(--wf-text)', YouTube: '#FF0000', Facebook: '#1877F2', Website: '#0d9488', Other: 'var(--wf-muted)' };

    // Font Awesome 5.13 in the app has no TikTok logo: inline SVG instead
    var TIKTOK_SVG = '<svg viewBox="0 0 448 512" aria-hidden="true"><path d="M448 209.9a210.1 210.1 0 0 1-122.8-39.3v178.8A162.6 162.6 0 1 1 185 188.3v89.9a74.6 74.6 0 1 0 52.2 71.2V0h88a121 121 0 0 0 1.9 22.2A122.2 122.2 0 0 0 381 102.4a121.4 121.4 0 0 0 67 20.1z"/></svg>';

    function platformGlyph(name) {
        var color = PLATFORM_COLORS[name] || 'var(--wf-muted)';
        return name === 'TikTok'
            ? '<span style="color:' + color + ';display:inline-flex">' + TIKTOK_SVG + '</span>'
            : '<i class="' + (PLATFORM_ICONS[name] || 'fas fa-circle') + '" style="color:' + color + '"></i>';
    }

    var CARD_ACTIONS = {
        start: { label: 'Mulai produksi', icon: 'fas fa-play', cls: 'is-primary' },
        submit_review: { label: 'Ajukan review', icon: 'fas fa-paper-plane', cls: 'is-primary' },
        approve: { label: 'Approved', icon: 'fas fa-check', cls: 'is-success' },
        revise: { label: 'Revisi masuk', icon: 'fas fa-undo', cls: 'is-warning' },
        publish: { label: 'Tandai published', icon: 'fas fa-bullhorn', cls: 'is-success' }
    };
    // Before production the card leads through the preparation in order: PIC → brief → mulai produksi
    function prepStep(plan) {
        if (plan.status !== 'Scheduled' && plan.status !== 'Draft') return null;
        if (!plan.assigned_to) return 2;
        if (!plan.has_brief) return 3;
        return null;
    }

    function cardActions(row) {
        var prep = (row.transitions || []).length ? prepStep(row) : null;
        if (prep) {
            return '<div class="cp-item-actions"><button type="button" class="cp-act js-stop js-card-prep is-primary" data-step="' + prep + '">'
                + (prep === 2 ? '<i class="fas fa-user-plus"></i> Tentukan PIC' : '<i class="fas fa-pen"></i> Isi brief') + '</button></div>';
        }
        // made by the designer: the designer's own buttons (Ambil / Selesai dikerjakan) move this card
        if (row.needs_designer && (row.transitions || []).length) {
            var who = row.designer_name || 'designer';
            if (row.status === 'Scheduled' || row.status === 'Draft') {
                // H-1: deadline produksi close / past and the designer has not started
                var urgent = { tomorrow: 'Deadline besok', today: 'Deadline hari ini', late: 'Lewat deadline' }[row.design_urgency];
                return '<div class="cp-item-actions"><span class="cp-act ' + (urgent ? 'is-alert' : 'is-wait') + '" title="Produksi mulai otomatis saat designer mengambil job">'
                    + '<i class="fas ' + (urgent ? 'fa-exclamation-triangle' : 'fa-hourglass-half') + '"></i> '
                    + (urgent ? urgent + ', belum diambil ' + esc(row.designer_name || 'designer') : 'Menunggu ' + esc(row.designer_name ? who + ' mengambil job' : 'diambil designer')) + '</span></div>';
            }
            if (row.status === 'Produksi' || row.status === 'Revisi') {
                return '<div class="cp-item-actions"><span class="cp-act is-wait" title="Review otomatis saat designer menandai selesai">'
                    + '<i class="fas fa-pen-nib"></i> ' + (row.status === 'Revisi' ? 'Direvisi ' : 'Dikerjakan ') + esc(who) + '</span></div>';
            }
        }
        var list = (row.transitions || []).filter(function (t) {
            if (!CARD_ACTIONS[t.action]) return false;
            // only the main next step on the card; shortcuts (review straight from Terjadwal,
            // revisi from Siap Publish) stay in the modal
            if ((row.status === 'Scheduled' || row.status === 'Draft') && t.action !== 'start') return false;
            if (row.status === 'Done' && t.action === 'revise') return false;
            return true;
        });
        if (!list.length) return '';
        return '<div class="cp-item-actions">' + list.map(function (t) {
            var a = CARD_ACTIONS[t.action];
            return '<button type="button" class="cp-act js-stop js-card-action ' + a.cls + '" data-action="' + t.action + '" title="' + esc(t.label) + '">'
                + '<i class="' + a.icon + '"></i> ' + a.label + '</button>';
        }).join('') + '</div>';
    }

    // Card, top to bottom: time + status / title / one muted line "brand · pilar · jenis" /
    // PIC on the left, platform logos on the right (green dot + clickable once the post link is filled in)
    function itemHtml(row) {
        var color = brandColor(row);
        var time = scheduleBy === 'tanggal_publish'
            ? (row.tanggal_publish ? moment(row.tanggal_publish).format('HH.mm') : '')
            : 'Tayang ' + W.date(row.tanggal_publish, { weekday: false, year: false, short: true });

        // the clinic shows as logo (top-left) + card color; the only tag is the content pillar
        var logos = (row.brand || []).map(brandLogo).join('');
        var tags = pilarLabel(row.konten_pilar);

        var icons = (row.platforms || []).map(function (p) {
            return p.url
                ? '<a class="cp-plat js-stop" href="' + esc(p.url) + '" target="_blank" rel="noopener" title="Lihat postingan ' + esc(p.name) + '">' + platformGlyph(p.name) + '</a>'
                : '<span class="cp-plat" title="' + esc(p.name) + ' (belum ada link)">' + platformGlyph(p.name) + '</span>';
        }).join('');
        if (row.design_status) icons = '<span class="cp-design-icon" title="Desain: ' + esc(row.design_status) + '"><i class="fas fa-pen-nib"></i></span>' + icons;

        var pic = row.assigned_to_name
            ? '<span class="cp-pic" title="PIC: ' + esc(row.assigned_to_name) + '"><span>' + esc(row.assigned_to_name) + '</span></span>'
            : '<span class="cp-pic is-empty"><span>Belum ada PIC</span></span>';

        // the main next step(s) as buttons on the card; cancel / reopen stay in the modal
        var actions = cardActions(row);

        // progress recorded from the WA group: revision count and how long it has waited for review
        var progress = '';
        if (row.revisi_count) progress += '<span class="cp-tag cp-tag-rev" title="Jumlah revisi yang dicatat"><i class="fas fa-undo"></i>' + row.revisi_count + '× revisi</span>';
        if (row.status === 'Review' && row.review_since) {
            var waited = moment().diff(moment(row.review_since), 'hours');
            progress += '<span class="cp-tag" title="Menunggu review sejak ' + esc(W.date(row.review_since, { time: true })) + '"><i class="far fa-clock"></i>'
                + (waited < 24 ? waited + ' jam' : Math.floor(waited / 24) + ' hari') + ' di review</span>';
        }
        var pub = row.status === 'Published' ? row.published : null;

        // card tinted with the clinic color (light enough to keep the text readable in both themes)
        var style = color ? ' style="--brand:' + color + ';--brand-bg:' + rgba(color, IS_DARK() ? .22 : .12) + '"' : '';
        return '<div class="cp-item" tabindex="0" role="button" data-id="' + row.id + '"' + style + '>'
            + '<div class="cp-item-top">'
            + (logos ? '<span class="cp-logos">' + logos + '</span>' : '')
            + (time ? (scheduleBy === 'tanggal_publish'
                ? '<span class="cp-item-time" title="Jam tayang"><i class="far fa-clock"></i>' + esc(time) + '</span>'
                // production view: the column is the production deadline; the go-live date is only a quiet hint
                : '<span class="cp-item-when" title="Tayang ' + esc(W.date(row.tanggal_publish, { time: true })) + '"><i class="far fa-calendar"></i>' + esc(time) + '</span>') : '')
            + (isOverdue(row) ? '<i class="fas fa-exclamation-triangle cp-warn" title="Terlewat"></i>' : '')
            + (row.status === 'Published' ? publishedPill(pub && pub.at, pub && pub.by, pub && pub.late_by, row.tanggal_publish, insightLine(row)) : planPill(row.status)) + '</div>'
            + '<div class="cp-item-main">' + thumbHtml(row) + '<div class="cp-item-main-text">'
            + '<div class="cp-item-title">' + esc(row.judul) + '</div>'
            + (tags || progress ? '<div class="cp-item-tags">' + tags + progress + '</div>' : '')
            + '</div></div>'
            + '<div class="cp-item-foot">' + pic + (icons ? '<span class="cp-icons">' + icons + '</span>' : '') + '</div>'
            + cardTools(row)
            + actions
            + '</div>';
    }

    /**
     * Status of published content and uploaded konten rutin, the same for both, on the right of the card:
     * "✓ Published · 09.00" (the card already sits under its day: the date only shows when it went live on
     * another day), under it the numbers (views, likes, comments), under that how late it was.
     * Who recorded it: tooltip.
     */
    function publishedPill(at, by, lateBy, day, stats) {
        var when = '';
        if (at) {
            when = day && !moment(at).isSame(moment(day), 'day')
                ? W.date(at, { weekday: false, year: false, short: true, time: true })
                : moment(at).format('HH.mm');
        }
        return '<span class="cp-status"' + (by ? ' title="Dicatat oleh ' + esc(by) + (at ? ' · tayang ' + esc(W.date(at, { time: true })) : '') + '"' : '') + '>'
            + '<span class="wf-pill st-green"><i class="fas fa-check"></i>Published' + (when ? ' · ' + esc(when) : '') + '</span>'
            + (stats || '')
            + (lateBy ? '<span class="cp-late"><i class="far fa-clock"></i>telat ' + esc(lateBy) + '</span>' : '')
            + '</span>';
    }
    /**
     * Preview square next to the title, in this order: the published Instagram post (click = open it),
     * the picture of the link referensi (click = open the reference), the first reference picture of the
     * brief. The last two are marked "Ref".
     */
    function thumbHtml(row) {
        var t = row.thumb;
        if (!t) return '';
        var img = '<img src="' + esc(t.url) + '" alt="" loading="lazy">';
        if (t.kind === 'post') {
            var ig = (row.platforms || []).filter(function (p) { return p.name === 'Instagram' && p.url; })[0];
            return ig
                ? '<a class="cp-thumb js-stop" href="' + esc(ig.url) + '" target="_blank" rel="noopener" title="Lihat postingan di Instagram">' + img + '</a>'
                : '<span class="cp-thumb">' + img + '</span>';
        }
        if (t.kind === 'link' && t.link) {
            return '<a class="cp-thumb is-ref js-stop" href="' + esc(t.link) + '" target="_blank" rel="noopener" title="Buka link referensi">' + img + '<b><i class="fas fa-link"></i> Ref</b></a>';
        }
        return '<span class="cp-thumb is-ref" title="Gambar referensi dari brief">' + img + '<b>Ref</b></span>';
    }

    // pillar as a small coloured label (same colours as the weekly pillar mix)
    function pilarLabel(p) {
        if (!p) return '';
        var i = PILAR_LIST.indexOf(p);
        var color = PILAR_COLORS[(i < 0 ? 5 : i) % PILAR_COLORS.length];
        return '<span class="cp-pilar" style="--p:' + color + '" title="Pilar konten">' + esc(p) + '</span>';
    }

    // "(selesai/total)": done = Published (publish mode) or Review/Siap Publish/Published (production mode).
    // Red only for past days with unfinished items; cancelled items are not counted.
    // Konten rutin count too: uploaded = done, skipped days are left out.
    function dayStat(day, rows, routines) {
        var doneSt = scheduleBy === 'tanggal_produksi' ? ['Review', 'Done', 'Published'] : ['Published'];
        var active = rows.filter(function (r) { return r.status !== 'Cancelled'; });
        var rt = (routines || []).filter(function (o) { return o.state !== 'skipped'; });
        var total = active.length + rt.length;
        if (!total) return '';
        var done = active.filter(function (r) { return doneSt.indexOf(r.status) !== -1; }).length
            + rt.filter(function (o) { return o.state === 'posted'; }).length;
        var cls = done === total ? 'green' : (day.isBefore(moment(), 'day') ? 'red' : (done ? 'yellow' : ''));
        var title = scheduleBy === 'tanggal_produksi' ? 'Sudah diproduksi / total' : 'Sudah published / total';
        return '<span class="cp-day-stat ' + cls + '" title="' + title + '">' + done + '/' + total + '</span>';
    }

    // Content and konten rutin of one day, in order of time
    function dayItems(rows, routines) {
        var list = rows.map(function (r) {
            return { t: r.tanggal_publish ? moment(r.tanggal_publish).format('HH:mm') : '99:99', html: itemHtml(r) };
        });
        if (scheduleBy === 'tanggal_publish') {
            (routines || []).forEach(function (o) { list.push({ t: o.time, html: routineHtml(o) }); });
            list.sort(function (a, b) { return a.t < b.t ? -1 : (a.t > b.t ? 1 : 0); });
        }
        return list.map(function (x) { return x.html; }).join('');
    }

    function renderBoard(grouped, routinesByDay) {
        rowsById = {};
        routineByKey = {};
        routinesByDay = routinesByDay || {};
        var html = '';
        for (var i = 0; i < 7; i++) {
            var d = weekStart.clone().add(i, 'days');
            var key = d.format('YYYY-MM-DD');
            var rows = grouped[key] || [];
            var routines = routinesByDay[key] || [];
            rows.forEach(function (r) { rowsById[r.id] = r; });
            routines.forEach(function (o) { routineByKey[o.key] = o; });
            var isToday = d.isSame(moment(), 'day');
            var isPast = d.isBefore(moment(), 'day');
            html += '<div class="cp-day' + (isToday ? ' is-today' : '') + (isPast ? ' is-past' : '') + '" data-date="' + key + '">'
                + '<div class="cp-day-head">'
                + '<div class="cp-day-date"><strong>' + W.HARI[d.day()] + '</strong><span>' + d.date() + ' ' + W.BULAN_PANJANG[d.month()] + ' ' + d.year() + '</span></div>'
                + (isToday ? '<span class="cp-day-today">Hari ini</span>' : '')
                + dayStat(d, rows, scheduleBy === 'tanggal_publish' ? routines : [])
                + (CAN_MANAGE ? '<button type="button" class="cp-day-add" data-date="' + key + '" title="Jadwalkan konten di ' + W.dayTitle(d) + '"><i class="fas fa-plus"></i></button>' : '')
                + '</div><div class="cp-day-list" data-date="' + key + '">'
                + (rows.length || (scheduleBy === 'tanggal_publish' && routines.length) ? dayItems(rows, routines) : '<div class="cp-day-empty">Kosong</div>')
                + '</div></div>';
        }
        $('#weekBoard').html(html);
        enableDrag();
        renderDayNav(grouped, routinesByDay);
        layoutSlider();
    }

    // pillar colours of the coloured pillar label on the cards
    var PILAR_LIST = @json($kontenPilar);
    var PILAR_COLORS = ['#2563eb', '#db2777', '#f59f00', '#16a34a', '#7c3aed', '#0891b2'];

    var lastBoardKey = null;
    function loadBoard(opts) {
        opts = opts || {};
        var seq = ++boardSeq;
        if (!opts.silent) $('#weekBoard').css('opacity', .5);
        var params = boardParams();
        $.get(ROUTES.week, params).done(function (resp) {
            if (seq !== boardSeq) return; // week/mode changed meanwhile
            // the 10-second refresh only redraws when something changed (no flicker, hover / focus kept)
            var key = JSON.stringify([params, resp && resp.data, resp && resp.routines]);
            if (opts.silent && key === lastBoardKey) return;
            lastBoardKey = key;
            // a redraw keeps where the user was looking (days scroll sideways, each day scrolls down)
            var $board = $('#weekBoard');
            var keepX = $board.scrollLeft(), keepY = {};
            $board.find('.cp-day-list').each(function () { keepY[$(this).data('date')] = this.scrollTop; });
            renderBoard((resp && resp.data) || {}, (resp && resp.routines) || {});
            if (opts.silent) {
                $board.scrollLeft(keepX);
                $board.find('.cp-day-list').each(function () { if (keepY[$(this).data('date')]) this.scrollTop = keepY[$(this).data('date')]; });
            }
            if (opts.scrollToday) {
                var $today = $board.find('.cp-day.is-today');
                if ($today.length && window.innerWidth < 576) $('html, body').animate({ scrollTop: $today.offset().top - 70 }, 200);
                else if ($today.length) $board.scrollLeft($today[0].offsetLeft);
            }
        }).fail(function () {
            if (seq === boardSeq) $('#weekBoard').html('<div class="wf-empty">Gagal memuat jadwal. Coba muat ulang halaman.</div>');
        }).always(function () {
            if (seq === boardSeq) $('#weekBoard').css('opacity', 1);
        });
        loadOverdueCount();
    }

    // Drag a card to another day (desktop only; on phones change the date in the form)
    function enableDrag() {
        if (!CAN_MANAGE || IS_TOUCH || !$.fn.sortable) return;
        $('#weekBoard .cp-day-list').sortable({
            connectWith: '#weekBoard .cp-day-list',
            items: '.cp-item:not(.cp-routine)', // konten rutin follows its own days
            placeholder: 'cp-item-placeholder',
            tolerance: 'pointer',
            start: function () { window._cpDragging = true; },
            stop: function () { window._cpDragging = false; },
            receive: function (event, ui) {
                var row = rowsById[ui.item.data('id')];
                var targetDate = $(this).data('date');
                if (!row || !targetDate) { loadBoard({ silent: true }); return; }
                var payload = {};
                if (scheduleBy === 'tanggal_produksi') {
                    payload.tanggal_produksi = targetDate;
                } else {
                    var time = row.tanggal_publish ? moment(row.tanggal_publish).format('HH:mm:ss') : '09:00:00';
                    payload.tanggal_publish = targetDate + ' ' + time;
                }
                $.post('/marketing/content-plan/' + row.id + '/inline-update', payload)
                    .done(function () { W.toast('Jadwal dipindah ke ' + W.date(targetDate)); loadBoard({ silent: true }); })
                    .fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr, 'Gagal memindahkan jadwal.'), 'error'); loadBoard({ silent: true }); });
            }
        });
    }

    $(document).on('click keydown', '#weekBoard .cp-item', function (e) {
        if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
        if ($(e.target).closest('.js-stop').length) return;
        e.preventDefault();
        if ($(this).hasClass('cp-routine')) { openRoutine($(this).data('routine')); return; }
        openPlan($(this).data('id'));
    });
    $(document).on('click', '#weekBoard .cp-day-add', function () { prepareNewPlan({ date: $(this).data('date') }); });

    // ---- day slider: N comfortable columns (from the board width), ◀ ▶ one day, chips to jump
    var colStep = 0;
    function layoutSlider() {
        var b = document.getElementById('weekBoard');
        if (!b) return;
        if (window.innerWidth < 576) { b.style.removeProperty('--cp-col-w'); return; }
        var w = b.clientWidth;
        if (w < 100) return; // board hidden (Ide Konten tab): measured again when Jadwal opens
        var n = w >= 1500 ? 4 : (w >= 1050 ? 3 : (w >= 640 ? 2 : 1));
        var col = Math.floor((w - 12 * (n - 1)) / n);
        b.style.setProperty('--cp-col-w', col + 'px');
        colStep = col + 12;
        updateSlider();
    }
    function updateSlider() {
        var b = document.getElementById('weekBoard');
        if (!b) return;
        var max = b.scrollWidth - b.clientWidth - 2;
        $('#cpSlidePrev').prop('disabled', b.scrollLeft <= 2);
        $('#cpSlideNext').prop('disabled', b.scrollLeft >= max);
        var left = b.scrollLeft, right = left + b.clientWidth;
        $('#weekBoard .cp-day').each(function (i) {
            var mid = this.offsetLeft + this.offsetWidth / 2;
            $('#cpDayNav .cp-day-chip').eq(i).toggleClass('in-view', mid >= left && mid <= right);
        });
    }
    function renderDayNav(grouped, routinesByDay) {
        var html = '';
        for (var i = 0; i < 7; i++) {
            var d = weekStart.clone().add(i, 'days'), key = d.format('YYYY-MM-DD');
            var n = (grouped[key] || []).filter(function (r) { return r.status !== 'Cancelled'; }).length
                + (scheduleBy === 'tanggal_publish' ? ((routinesByDay || {})[key] || []).length : 0);
            html += '<button type="button" class="cp-day-chip' + (d.isSame(moment(), 'day') ? ' is-today' : '') + '" data-index="' + i + '" title="' + esc(W.dayTitle(d)) + '">'
                + W.HARI[d.day()].slice(0, 3) + '<b>' + d.date() + '</b>' + (n ? '<span class="n">' + n + '</span>' : '') + '</button>';
        }
        $('#cpDayNav').html(html);
    }
    $('#cpDayNav').on('click', '.cp-day-chip', function () {
        var day = $('#weekBoard .cp-day').get(+$(this).data('index'));
        if (day) document.getElementById('weekBoard').scrollLeft = day.offsetLeft;
    });
    $('#cpSlidePrev, #cpSlideNext').on('click', function () {
        var b = document.getElementById('weekBoard');
        b.scrollLeft += (this.id === 'cpSlideNext' ? 1 : -1) * (colStep || b.clientWidth);
    });
    var sliderTick = null;
    $('#weekBoard').on('scroll', function () {
        if (sliderTick) return;
        sliderTick = requestAnimationFrame(function () { sliderTick = null; updateSlider(); });
    });
    $('#schedule-tab').on('shown.bs.tab', layoutSlider);
    // the week and the board actions belong to Jadwal only
    $('#cpMainTabs').on('shown.bs.tab', 'a', function () { $('.js-schedule-only').toggle(this.id === 'schedule-tab'); });
    var sliderResize = null;
    $(window).on('resize', function () { clearTimeout(sliderResize); sliderResize = setTimeout(layoutSlider, 150); });

    // ---- section icons on the cards: off by default (remembered in this browser)
    var showTools = false;
    try { showTools = localStorage.getItem('cp_tools') === '1'; } catch (e) {}
    function applyTools() {
        $('#weekBoard').toggleClass('show-tools', showTools);
        $('#btnTools').toggleClass('active', showTools).attr('aria-pressed', showTools);
    }
    $('#btnTools').on('click', function () {
        showTools = !showTools;
        try { localStorage.setItem('cp_tools', showTools ? '1' : '0'); } catch (e) {}
        applyTools();
    });
    applyTools();

    // ---- card density: Lengkap / Ringkas (remembered in this browser)
    var density = 'full';
    try { density = localStorage.getItem('cp_density') || 'full'; } catch (e) {}
    function applyDensity() {
        $('#weekBoard').toggleClass('is-compact', density === 'compact');
        $('#cpDensity button').each(function () { $(this).toggleClass('active', $(this).data('density') === density); });
    }
    $('#cpDensity').on('click', 'button', function () {
        density = $(this).data('density');
        try { localStorage.setItem('cp_density', density); } catch (e) {}
        applyDensity();
    });
    applyDensity();

    // ---- mode, week navigation, "Tugas Saya"
    function updateModeUi() {
        $('#cpMode button').each(function () { $(this).toggleClass('active', $(this).data('mode') === scheduleBy); });
    }
    $('#cpMode button').on('click', function () {
        scheduleBy = $(this).data('mode');
        updateModeUi();
        loadBoard();
    });

    $('#btnMine').on('click', function () {
        onlyMine = !onlyMine;
        $(this).toggleClass('active', onlyMine).attr('aria-pressed', onlyMine);
        try { localStorage.setItem('cp_only_mine', onlyMine ? '1' : '0'); } catch (e) {}
        loadBoard();
    });

    // "19 – 25 Okt 2026" (or "28 Sep – 4 Okt 2026" across months); click it to pick any date
    function updateWeekLabel() {
        var end = weekEnd();
        var label = weekStart.month() === end.month()
            ? weekStart.date() + ' – ' + end.date() + ' ' + W.BULAN_PANJANG[end.month()] + ' ' + end.year()
            : weekStart.date() + ' ' + W.BULAN_PANJANG[weekStart.month()] + ' – ' + end.date() + ' ' + W.BULAN_PANJANG[end.month()] + ' ' + end.year();
        $('#cpWeekLabel').text(label);
        $('#cpWeekDate').val(weekStart.format('YYYY-MM-DD'));
        $('#btnThisWeek').toggle(!weekStart.isSame(moment().startOf('isoWeek'), 'day'));
    }
    function goToWeek(day) {
        weekStart = moment(day).startOf('isoWeek');
        updateWeekLabel();
        loadBoard();
    }
    $('#btnWeekLabel').on('click', function () {
        var input = document.getElementById('cpWeekDate');
        try { if (input.showPicker) { input.showPicker(); return; } } catch (e) {}
        input.focus(); input.click();
    });
    $('#cpWeekDate').on('change', function () { if (this.value) goToWeek(this.value); });
    $('#btnPrevWeek').on('click', function () { goToWeek(weekStart.clone().subtract(7, 'days')); });
    $('#btnNextWeek').on('click', function () { goToWeek(weekStart.clone().add(7, 'days')); });
    $('#btnThisWeek').on('click', function () { goToWeek(moment()); });

    // ---- search: typing filters this week's board and lists the matches of all weeks under the box;
    // a result jumps to its week and opens the content. "#123" finds content number 123.
    var searchTimer = null, searchSeq = 0;
    function searchUsable(q) { return q.length >= 2 || /^#?\d+$/.test(q); }
    function highlight(text, q) {
        var html = esc(text || '(tanpa judul)');
        q.replace(/^#/, '').split(/\s+/).filter(Boolean).forEach(function (w) {
            var safe = esc(w).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            html = html.replace(new RegExp('(' + safe + ')(?![^<]*>)', 'ig'), '<mark>$1</mark>');
        });
        return html;
    }
    function renderSearch(resp, q) {
        var rows = (resp && resp.data) || [], routines = (resp && resp.routines) || [];
        var out = '';
        if (routines.length) {
            out += '<div class="cp-search-head">Konten rutin</div>' + routines.map(function (r) {
                return '<button type="button" class="cp-search-item" data-routine="' + r.id + '">'
                    + '<div class="t"><i class="fas fa-redo-alt cp-rt-ico mr-1"></i>' + highlight(r.judul, q) + '</div>'
                    + '<div class="m"><span>' + esc(r.days) + ' · ' + esc(r.time) + '</span>' + (r.brand ? '<span>' + esc(r.brand) + '</span>' : '')
                    + (!r.active ? '<span class="wf-pill st-gray">Nonaktif</span>' : '') + '</div></button>';
            }).join('');
        }
        if (rows.length) {
            out += '<div class="cp-search-head">Konten' + (rows.length >= 30 ? ' (30 terbaru, perjelas kata kunci)' : ' (' + rows.length + ')') + '</div>' + rows.map(function (it) {
                return '<button type="button" class="cp-search-item" data-id="' + it.id + '" data-date="' + esc(it.date || '') + '">'
                    + '<div class="t">' + highlight(it.judul, q) + '</div>'
                    + '<div class="m"><span>' + esc(it.day) + (it.time ? ' · ' + esc(it.time) : '') + '</span>' + planPill(it.status)
                    + (it.brand ? '<span>' + esc(it.brand) + '</span>' : '')
                    + (it.assigned ? '<span><i class="far fa-user mr-1"></i>' + esc(it.assigned) + '</span>' : '') + '</div></button>';
            }).join('');
        }
        if (!out) {
            out = '<div class="cp-search-empty">Tidak ada konten dengan judul “' + esc(q) + '”'
                + (brandFilter || onlyMine ? '<br><span class="small">Filter klinik / Tugas saya sedang aktif</span>' : '') + '</div>';
        }
        $('#cpSearchDrop').html(out).show();
    }
    function runSearch() {
        var q = $.trim($('#cpSearchInput').val());
        var usable = searchUsable(q);
        $('#cpSearchClear').toggle(q !== '');
        $('#cpSearch').toggleClass('is-active', usable);
        if ((usable ? q : '') !== searchQ) {
            searchQ = usable ? q : '';
            loadBoard();
        }
        if (!usable) { $('#cpSearchDrop').hide().empty(); return; }
        var seq = ++searchSeq;
        $.getJSON(ROUTES.search, filterParams({ q: q, schedule_by: scheduleBy })).done(function (resp) {
            if (seq === searchSeq) renderSearch(resp, q);
        });
    }
    function clearSearch() {
        $('#cpSearchInput').val('');
        runSearch();
    }
    $('#cpSearchInput').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(runSearch, 300);
    }).on('focus', function () {
        if (searchQ && $('#cpSearchDrop').children().length) $('#cpSearchDrop').show();
    }).on('keydown', function (e) {
        var $items = $('#cpSearchDrop .cp-search-item'), $cur = $items.filter('.is-focus'), i = $items.index($cur);
        if (e.key === 'Escape') {
            if ($('#cpSearchDrop').is(':visible')) $('#cpSearchDrop').hide(); else clearSearch();
        } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (!$items.length) return;
            $('#cpSearchDrop').show();
            i = e.key === 'ArrowDown' ? Math.min(i + 1, $items.length - 1) : Math.max(i - 1, 0);
            $items.removeClass('is-focus').eq(i).addClass('is-focus')[0].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(searchTimer);
            if ($cur.length) $cur.trigger('click');
            else if ($items.length === 1) $items.trigger('click');
            else runSearch();
        }
    });
    $('#cpSearchClear').on('click', function () { clearSearch(); $('#cpSearchInput').trigger('focus'); });
    $('#cpSearchDrop').on('click', '.cp-search-item', function () {
        var $it = $(this);
        $('#cpSearchDrop').hide();
        if ($it.data('routine')) { openRoutine($it.data('routine')); return; }
        var date = String($it.data('date') || '');
        if (date && !moment(date).isSame(weekStart, 'isoWeek')) goToWeek(date);
        openPlan($it.data('id'));
    });
    $(document).on('mousedown', function (e) {
        if (!$(e.target).closest('#cpSearch').length) $('#cpSearchDrop').hide();
    }).on('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K') && $('#scheduleTab').is(':visible')) {
            e.preventDefault();
            $('#cpSearchInput').trigger('focus').trigger('select');
        }
    });

    // ---- konten terlewat (all weeks)
    function loadOverdueCount() {
        $.getJSON(ROUTES.statusList, filterParams({ status: 'terlewat', schedule_by: scheduleBy, count_only: 1 }))
            .done(function (r) {
                var n = (r && r.count) || 0;
                $('#stat_terlewat').text(n);
                $('#btnOverdue').toggle(n > 0);
                refreshAlerts();
            });
        if (CAN_MANAGE) {
            $.getJSON(ROUTES.statusList, filterParams({ status: 'tanpa_link', count_only: 1 })).done(function (r) {
                var n = (r && r.count) || 0;
                $('#stat_nolink').text(n);
                $('#btnNoLink').toggle(n > 0);
                refreshAlerts();
            });
        }
    }
    // Published on Instagram without a post link: open the list, each opens the content to add the link
    $('#btnNoLink').on('click', function () {
        $.getJSON(ROUTES.statusList, filterParams({ status: 'tanpa_link' })).done(function (r) {
            var rows = (r && r.data) || [];
            $('#overdueTitle').text('Belum ada link Instagram (' + rows.length + ')');
            $('#overdueList').html('<div class="wf-muted small">Konten ini sudah Published di Instagram, tapi link postingannya belum diisi atau bukan link postingan, jadi views &amp; insight-nya tidak terlacak. Buka kontennya lalu isi <strong>Link publikasi Instagram</strong> (Bagikan → Salin link).</div>'
                + rows.map(function (it) {
                    return '<div class="wf-card is-clickable js-open-plan" tabindex="0" data-id="' + it.id + '">'
                        + '<div class="d-flex justify-content-between flex-wrap" style="gap:6px"><span class="wf-meta">' + esc(it.day) + ' · ' + esc(it.time) + '</span>' + planPill(it.status) + '</div>'
                        + '<div class="wf-card-title mt-1">' + esc(it.judul) + '</div>'
                        + '<div class="wf-meta mt-1">' + (it.link ? '<i class="fas fa-unlink mr-1"></i>Link tidak valid: ' + esc(it.link) : '<i class="fas fa-unlink mr-1"></i>Belum ada link Instagram')
                        + (it.assigned ? ' · ' + esc(it.assigned) : '') + '</div></div>';
                }).join(''));
            $('#overdueModal').modal('show');
        });
    });
    $('#btnOverdue').on('click', function () {
        $.getJSON(ROUTES.statusList, filterParams({ status: 'terlewat', schedule_by: scheduleBy })).done(function (r) {
            var rows = (r && r.data) || [];
            $('#overdueTitle').text('Konten terlewat (' + rows.length + ')' + (scheduleBy === 'tanggal_produksi' ? ' · deadline produksi' : ' · jadwal publish'));
            $('#overdueList').html(rows.length ? rows.map(function (it) {
                if (it.type === 'routine') {
                    return '<div class="wf-card is-clickable js-open-routine" tabindex="0" data-routine="' + it.routine_id + '">'
                        + '<div class="d-flex justify-content-between flex-wrap" style="gap:6px"><span class="wf-meta">' + esc(it.day) + ' · ' + esc(it.time) + '</span><span class="wf-pill st-red">Belum diupload</span></div>'
                        + '<div class="wf-card-title mt-1"><i class="fas fa-redo-alt cp-rt-ico mr-1"></i>' + esc(it.judul) + '</div>'
                        + '<div class="wf-meta mt-1">Konten rutin · ' + (it.assigned ? '<i class="fas fa-user mr-1"></i>' + esc(it.assigned) : 'Semua tim') + '</div>'
                        + '</div>';
                }
                return '<div class="wf-card is-clickable js-open-plan" tabindex="0" data-id="' + it.id + '">'
                    + '<div class="d-flex justify-content-between flex-wrap" style="gap:6px"><span class="wf-meta">' + esc(it.day) + (it.time ? ' · ' + esc(it.time) : '') + '</span>' + planPill(it.status) + '</div>'
                    + '<div class="wf-card-title mt-1">' + esc(it.judul) + '</div>'
                    + '<div class="wf-meta mt-1">' + (it.assigned ? '<i class="fas fa-user mr-1"></i>' + esc(it.assigned) : 'Belum ada PIC') + (it.brand ? ' · ' + esc(it.brand) : '') + '</div>'
                    + '</div>';
            }).join('') : '<div class="wf-empty"><i class="fas fa-check-circle fa-2x mb-2 d-block"></i>Tidak ada konten terlewat.</div>');
            $('#overdueModal').modal('show');
        });
    });
    $(document).on('click', '#overdueList .js-open-plan', function () {
        var id = $(this).data('id');
        $('#overdueModal').one('hidden.bs.modal', function () { openPlan(id); }).modal('hide');
    });
    $(document).on('click', '#overdueList .js-open-routine', function () {
        var id = $(this).data('routine');
        $('#overdueModal').one('hidden.bs.modal', function () { openRoutine(id); }).modal('hide');
    });

    // =================================================================== PLAN MODAL
    var $planModal = $('#contentPlanModal');
    var $planForm = $('#contentPlanForm');
    var currentPlan = null;

    // Brand pickers show the clinic logo next to each option
    function brandOptionTemplate(opt) {
        if (!opt.id) return opt.text;
        return $('<span class="cp-brand-opt">' + brandLogo(opt.id) + '<span>' + esc(opt.text) + '</span></span>');
    }
    function select2Options($el, $parent) {
        var o = { dropdownParent: $parent, width: '100%', placeholder: $el.data('placeholder') || '', allowClear: !!$el.data('allow-clear') };
        if ($el.is('#brand, #cl_brand')) { o.templateResult = brandOptionTemplate; o.templateSelection = brandOptionTemplate; }
        return o;
    }
    // Older rows may hold a brand that is no longer offered (e.g. "dr Fika"): add it so editing keeps it
    function setBrands($select, values) {
        (values || []).forEach(function (v) {
            if (v && !$select.find('option').filter(function () { return this.value === v; }).length) $select.append(new Option(v, v));
        });
        $select.val(values || []).trigger('change.select2');
    }

    $('.cp-select2').each(function () {
        if (!$.fn.select2) return;
        $(this).select2(select2Options($(this), $planModal));
    });

    /**
     * Klinik, platform and jenis konten as tappable buttons (logos) instead of dropdowns.
     * The hidden <select multiple> stays the source of truth (form data, validation, .val()):
     * a tap toggles its option; any change of the select (also .trigger('change.select2')) redraws the buttons.
     */
    var JENIS_ICONS = { Feed: 'far fa-image', Story: 'far fa-clock', Reels: 'fas fa-film', Artikel: 'far fa-file-alt', Other: 'fas fa-ellipsis-h' };
    function chipIcon(selectId, value) {
        if (/days$/.test(selectId)) return '';
        if (/brand$/.test(selectId)) return brandLogo(value);
        if (/platform$/.test(selectId)) return '<span class="cp-chip-ico">' + platformGlyph(value) + '</span>';
        return '<i class="' + (JENIS_ICONS[value] || 'fas fa-tag') + '"></i>';
    }
    function renderChipPicker($select) {
        var $box = $select.next('.cp-chips');
        if (!$box.length) $box = $('<div class="cp-chips" role="group"></div>').attr('aria-label', $select.data('placeholder') || '').insertAfter($select);
        var selected = $select.val() || [];
        var disabled = $select.prop('disabled');
        $box.toggleClass('is-disabled', disabled).html($select.find('option').map(function () {
            var on = selected.indexOf(this.value) !== -1;
            var label = /brand$/.test($select.attr('id')) && BRAND_META[this.value] ? BRAND_META[this.value].label : $(this).text();
            return '<button type="button" class="cp-chip-opt' + (on ? ' is-on' : '') + '" data-value="' + esc(this.value) + '" aria-pressed="' + on + '"' + (disabled ? ' disabled' : '') + '>'
                + chipIcon($select.attr('id'), this.value) + '<span>' + esc(label) + '</span>'
                + (on ? '<i class="fas fa-check cp-chip-check"></i>' : '') + '</button>';
        }).get().join(''));
    }
    $('.js-chip-picker').each(function () {
        var $select = $(this);
        renderChipPicker($select);
        $select.on('change change.select2', function () { renderChipPicker($select); });
    });
    $(document).on('click', '.cp-chips .cp-chip-opt', function () {
        var $select = $(this).closest('.cp-chips').prev('select');
        var opt = $select.find('option').filter(function (i, o) { return o.value === String($(this).data('value')); }.bind(this))[0];
        if (!opt || $select.prop('disabled')) return;
        opt.selected = !opt.selected;
        $select.trigger('change'); // redraws the buttons + e.g. the link publikasi inputs for platforms
    });

    function setPlanReadOnly(readOnly) {
        $planForm.find('input, textarea, select').not('[type=hidden]').prop('disabled', readOnly);
        $planModal.find('.js-chip-picker').trigger('change.select2'); // redraw buttons as (not) clickable
        $planModal.find('.btn-save-plan, #cb_drop_area, #cr_new_btn').toggle(!readOnly);
    }

    function resetPlanModal() {
        currentPlan = null;
        $planForm[0].reset();
        $planForm.attr('data-action', 'store').attr('data-id', '');
        $planForm.find('input[name="content_list_id"]').val('');
        $planModal.find('.cp-select2').val(null).trigger('change.select2');
        fillWho(null);
        $planModal.find('.js-chip-picker').val([]).trigger('change.select2');
        $('#link_publikasi_wrapper').empty();
        $('#cpModalStatus').empty();
        $('#cpWorkflow').hide();
        $('.cp-saved-only').hide();
        $('.btn-delete-plan').hide();
        showPlanTab('detail');
        resetBrief();
        resetReport(null);
        $('#cpTimeline').empty();
        showPlanSide(false);
        $('#cpDesignList').empty();
        $('#cpDesignCount').hide();
        setPlanReadOnly(!CAN_MANAGE);
        clearSection();
        setWizard(0);
    }

    // Switch modal tabs instantly: chained .tab('show') calls overlap their fade and leave two panes visible
    function showPlanTab(name) {
        $('#cpTabs .nav-link').removeClass('active').attr('aria-selected', 'false');
        $('#tab-' + name + '-tab').addClass('active').attr('aria-selected', 'true');
        $planModal.find('.tab-content:first > .tab-pane').removeClass('active show');
        $('#tab-' + name).addClass('active show');
    }

    // ---- new content = 3 steps (Dasar required; Penugasan & Brief optional). 0 = editing: all fields + tabs.
    var wizardStep = 0;
    function setWizard(step) {
        wizardStep = step;
        var on = step > 0;
        $planModal.toggleClass('is-wizard', on);
        $('#cpWizard').toggle(on);
        $('#cpTabs').toggle(!on);
        // a short step-by-step form reads better in a medium modal; saved content uses the wide one
        $planModal.find('.modal-dialog').toggleClass('modal-xl', !on).toggleClass('modal-lg', on);
        if (on) showGroups(step === 2 ? ['2'] : ['1']);
        else showGroups(['1', '2', 'caption', 'links']);
        $('#cpPrevStep').toggle(on && step > 1);
        $('#cpNextStep').toggle(on && step < 3).find('.js-next-label').text(step === 1 ? 'Lanjut: Penugasan' : 'Lanjut: Brief');
        $('#cpCloseBtn').text(on ? 'Batal' : 'Tutup');
        $planModal.find('.btn-save-plan')
            .toggleClass('btn-primary', !on || step === 3)
            .toggleClass('btn-outline-primary', on && step < 3)
            .find('.js-save-label').text(on && step < 3 ? 'Simpan sekarang' : 'Simpan');
        if (!on) return;
        if (currentPlan) {
            // finishing the preparation of saved content: same steps, without the full-view extras
            $('#cpWorkflow').hide();
            showPlanSide(false);
            $('.btn-delete-plan').hide();
            $('#contentPlanModalLabel').text('Persiapan konten #' + currentPlan.id);
        }
        $('#cpWizard .cp-wizard-step').each(function () {
            var s = +$(this).data('step');
            // saved content: a step is done when its data is there, not only when it lies behind us
            var done = s < step || (currentPlan && (s === 1 || (s === 2 && currentPlan.assigned_to) || (s === 3 && currentPlan.has_brief)));
            $(this).toggleClass('is-active', s === step).toggleClass('is-done', !!done && s !== step)
                .find('.dot').html(done && s !== step ? '<i class="fas fa-check"></i>' : s);
        });
        showPlanTab(step === 3 ? 'brief' : 'detail');
    }

    /**
     * Saved content opens one part at a time instead of one big form: the summary ("Ringkasan") with a box
     * per part, or a single part (Dasar, Penugasan, Brief, …) with its own Simpan. The form underneath stays
     * one form (every field is sent on save); only the part on screen changes.
     * The step-by-step form is only for new content.
     */
    var SECTIONS = {
        ringkasan: { label: 'Ringkasan', tab: 'summary' },
        dasar: { label: 'Informasi dasar', icon: 'fas fa-info-circle', tab: 'detail', groups: ['1'], edit: true },
        penugasan: { label: 'Penugasan & PIC', icon: 'fas fa-user-cog', tab: 'detail', groups: ['2'], edit: true },
        brief: { label: 'Brief', icon: 'far fa-file-alt', tab: 'brief', edit: true },
        caption: { label: 'Caption & aset', icon: 'far fa-comment-alt', tab: 'detail', groups: ['caption'], edit: true },
        publikasi: { label: 'Link publikasi', icon: 'fas fa-link', tab: 'detail', groups: ['links'], edit: true },
        desain: { label: 'Desain', icon: 'fas fa-pen-nib', tab: 'design' },
        report: { label: 'Insight & report', icon: 'fas fa-chart-line', tab: 'report', edit: true }, // edit: only with non-Instagram platforms
        riwayat: { label: 'Riwayat', icon: 'fas fa-history', side: true }
    };
    var currentSection = null, sectionFromSummary = false;

    function setSection(name) {
        var s = SECTIONS[name] || SECTIONS.ringkasan;
        currentSection = SECTIONS[name] ? name : 'ringkasan';
        $planModal.addClass('is-section');
        $('#cpWizard, #cpTabs, #cpPrevStep, #cpNextStep').hide();
        $planModal.find('.modal-dialog').removeClass('modal-xl').addClass('modal-lg');
        $('#cpWorkflow').toggle(currentSection === 'ringkasan' && !!currentPlan);
        if (s.side) {
            $('#cpMain').hide();
            $('#cpSide').show().removeClass('col-lg-4').addClass('col-12');
        } else {
            $('#cpMain').show().removeClass('col-lg-8');
            $('#cpSide').hide();
            showPlanTab(s.tab);
            if (s.groups) showGroups(s.groups);
        }
        $('#contentPlanModalLabel').text(currentPlan ? (currentSection === 'ringkasan' ? 'Konten #' + currentPlan.id : s.label) : s.label);
        $('#cpBackSummary').toggle(currentSection !== 'ringkasan');
        // Insight & report of an Instagram-only content: numbers come in by themselves, nothing to save
        var editable = !!s.edit && CAN_MANAGE && (currentSection !== 'report' || applyReportMode());
        $planModal.find('.btn-save-plan').toggle(editable).removeClass('btn-outline-primary').addClass('btn-primary')
            .find('.js-save-label').text('Simpan');
        $('.btn-delete-plan').toggle(currentSection === 'ringkasan' && CAN_MANAGE);
        $('#cpCloseBtn').text('Tutup');
        $planModal.find('.modal-body').scrollTop(0);
        if (currentSection === 'penugasan') showDesignerLoad();
        if (currentSection === 'dasar') showBestTime();
    }
    function clearSection() {
        currentSection = null;
        sectionFromSummary = false;
        $planModal.removeClass('is-section');
        $('#cpMain').show();
        $('#cpSide').removeClass('col-12').addClass('col-lg-4');
        $('#cpBackSummary').hide();
        $planModal.find('.btn-save-plan').show();
    }
    $('#cpBackSummary').on('click', function () { sectionFromSummary = false; setSection('ringkasan'); });
    $(document).on('click', '#cpSummary .js-section', function () {
        sectionFromSummary = true;
        openSectionNow($(this).data('section'));
    });
    function openSectionNow(name) {
        setSection(name);
        if (name === 'brief') focusHeadline();
    }

    // Brief: cursor in the headline (no highlight, no dropdown opening by itself)
    function focusHeadline() {
        if (!CAN_MANAGE) return;
        var go = function () { $('#cb_headline').trigger('focus'); };
        if ($planModal.hasClass('show')) setTimeout(go, 60);
        else $planModal.one('shown.bs.modal', go);
    }
    function focusSection(step) { openSectionNow(step === 3 ? 'brief' : 'penugasan'); }

    // inside the summary (preparation checklist)
    $(document).on('click', '#cpWorkflowActions .js-prep', function () { sectionFromSummary = true; focusSection(+$(this).data('step')); });

    // on the card: preparation buttons and the small section icons
    $(document).on('click', '#weekBoard .js-card-prep', function (e) {
        e.preventDefault();
        openPlan($(this).closest('.cp-item').data('id'), false, { section: +$(this).data('step') === 3 ? 'brief' : 'penugasan' });
    });
    $(document).on('click', '#weekBoard .js-card-section', function (e) {
        e.preventDefault();
        e.stopPropagation();
        openPlan($(this).closest('.cp-item').data('id'), false, { section: $(this).data('section') });
    });

    // which parts a content has at its stage
    function sectionsFor(p) {
        var produced = ['Produksi', 'Review', 'Revisi', 'Done', 'Published'].indexOf(p.status) !== -1;
        var list = ['dasar', 'penugasan', 'brief'];
        if (produced) list.push('caption');
        if (p.status === 'Done' || p.status === 'Published') list.push('publikasi');
        if (p.design_job || (p.design_requests || []).length) list.push('desain');
        if (p.status === 'Published') list.push('report');
        list.push('riwayat');
        return list;
    }

    // small icon buttons on a card, straight to one part
    function cardTools(row) {
        var list = ['dasar', 'penugasan', 'brief'];
        if (row.needs_designer || row.design_status) list.push('desain');
        if (row.status === 'Published') list.push('report');
        list.push('riwayat');
        return '<div class="cp-item-tools">' + list.map(function (k) {
            return '<button type="button" class="cp-tool js-stop js-card-section" data-section="' + k + '" title="' + esc(SECTIONS[k].label) + '" aria-label="' + esc(SECTIONS[k].label) + '">'
                + '<i class="' + SECTIONS[k].icon + '"></i></button>';
        }).join('') + '</div>';
    }

    function optionText(sel, value) {
        return value ? $(sel).find('option').filter(function () { return this.value === String(value); }).first().text() : '';
    }
    function plain(html, max) {
        var t = $('<div>').html(html || '').text().replace(/\s+/g, ' ').trim();
        return max && t.length > max ? t.slice(0, max) + '…' : t;
    }

    // Ringkasan: one box per part, read-only, each with its own button
    function renderSummary(p) {
        var row = function (label, value) { return '<div class="cp-sum-row"><span>' + label + '</span><div>' + (value || '<span class="wf-muted">–</span>') + '</div></div>'; };
        var box = function (key, body, cta) {
            var s = SECTIONS[key];
            return '<div class="cp-sum-box"><div class="cp-sum-head"><i class="' + s.icon + '"></i><strong>' + esc(s.label) + '</strong>'
                + '<button type="button" class="btn btn-sm btn-light ml-auto js-section" data-section="' + key + '">' + (cta || (s.edit && CAN_MANAGE ? '<i class="fas fa-pen mr-1"></i>Ubah' : 'Lihat')) + '</button></div>'
                + body + '</div>';
        };
        var html = '';
        html += box('dasar',
            '<div class="cp-sum-title">' + esc(p.judul || '') + '</div>'
            + row('Tayang', p.tanggal_publish ? esc(W.date(p.tanggal_publish, { time: true })) : '')
            + row('Klinik', (p.brand || []).map(function (b) { return brandLogo(b) + ' ' + esc((BRAND_META[b] || {}).label || b); }).join('<br>'))
            + row('Platform', (p.platform || []).map(function (x) { return '<span class="cp-sum-plat">' + platformGlyph(x) + esc(x) + '</span>'; }).join(' '))
            + row('Jenis', esc((p.jenis_konten || []).join(', ')))
            + row('Pilar', pilarLabel(p.konten_pilar)));

        var job = p.design_job;
        html += box('penugasan',
            row('PIC', p.assigned_to ? esc(optionText('#assigned_to', p.assigned_to)) : '<span class="text-danger">Belum ada PIC</span>')
            + row('Deadline produksi', p.tanggal_produksi ? esc(W.date(p.tanggal_produksi)) : '')
            + row('Dikerjakan', job ? '<i class="fas fa-pen-nib mr-1"></i>Designer: ' + esc(job.designer || 'menunggu diambil') + ' · ' + W.pill('design', job.status) : 'Content creator'),
            !p.assigned_to && CAN_MANAGE ? '<i class="fas fa-user-plus mr-1"></i>Tentukan PIC' : null);

        var b = p.brief || {};
        var imgs = (b.visual_references || []).length;
        var refLink = p.link_referensi
            ? '<div class="small mt-1"><i class="fas fa-link mr-1 text-muted"></i><a href="' + esc(/^https?:\/\//i.test(p.link_referensi) ? p.link_referensi : 'https://' + p.link_referensi) + '" target="_blank" rel="noopener">' + esc(plain(p.link_referensi, 50)) + '</a></div>' : '';
        html += box('brief', (p.has_brief || imgs
            ? (b.headline ? '<div class="cp-sum-title">' + esc(b.headline) + '</div>' : '')
                + (b.sub_headline ? '<div class="wf-muted mb-1">' + esc(b.sub_headline) + '</div>' : '')
                + (b.isi_konten ? '<div class="cp-sum-text">' + esc(plain(b.isi_konten, 260)) + '</div>' : '')
                + (imgs ? '<div class="wf-muted small mt-1"><i class="far fa-image mr-1"></i>' + imgs + ' gambar referensi</div>' : '')
            : '<div class="wf-muted">Belum ada brief.</div>') + refLink,
            !p.has_brief && CAN_MANAGE ? '<i class="fas fa-pen mr-1"></i>Isi brief' : null);

        var list = sectionsFor(p);
        if (list.indexOf('caption') !== -1) {
            html += box('caption', (p.caption ? '<div class="cp-sum-text">' + esc(plain(p.caption, 220)) + '</div>' : '<div class="wf-muted">Belum ada caption.</div>')
                + (p.link_asset ? row('Aset', '<a href="' + esc(p.link_asset) + '" target="_blank" rel="noopener">Buka file</a>') : ''));
        }
        if (list.indexOf('publikasi') !== -1) {
            var links = p.link_publikasi || {};
            html += box('publikasi', (p.platform || []).map(function (x) {
                return row('<span class="cp-sum-plat">' + platformGlyph(x) + esc(x) + '</span>', links[x] ? '<a href="' + esc(links[x]) + '" target="_blank" rel="noopener">' + esc(plain(links[x], 40)) + '</a>' : '');
            }).join(''));
        }
        if (list.indexOf('desain') !== -1) {
            html += box('desain', job ? row('Status', W.pill('design', job.status) + ' ' + esc(job.designer || 'belum diambil')) : '<div class="wf-muted">Lihat request desain konten ini.</div>');
        }
        if (list.indexOf('report') !== -1) {
            html += box('report', '<div class="wf-muted">Views, likes, komentar &amp; perkembangan per hari.</div>', '<i class="fas fa-chart-line mr-1"></i>Lihat');
        }
        var tl = (p.timeline || []).slice(-3).reverse();
        html += box('riwayat', tl.length ? tl.map(function (t) {
            return '<div class="cp-sum-tl"><strong>' + esc(t.user) + '</strong> · ' + esc(PLAN_ACTIONS[t.action] || t.action) + '<div class="wf-muted small">' + esc(W.date(t.at, { time: true })) + '</div></div>';
        }).join('') : '<div class="wf-muted">Belum ada riwayat.</div>', 'Lihat semua');

        $('#cpSummary').html(html);
    }

    /**
     * Show only these Detail sections: '1' Dasar (left column), '2' Penugasan, 'caption' Caption & aset,
     * 'links' Link publikasi (right column). A column without visible sections is hidden and the other one
     * takes the full width.
     */
    function showGroups(list) {
        $planModal.find('.cp-group').each(function () {
            $(this).toggle(list.indexOf(String($(this).data('group'))) !== -1).removeClass('is-first');
        });
        var colA = list.indexOf('1') !== -1;
        var colB = ['2', 'caption', 'links'].some(function (g) { return list.indexOf(g) !== -1; });
        $('.cp-col-a').toggle(colA).toggleClass('col-lg-6', colA && colB);
        $('.cp-col-b').toggle(colB).toggleClass('col-lg-6', colA && colB);
        // first visible section of each column has no divider above it
        $('.cp-col-a, .cp-col-b').each(function () {
            $(this).find('.cp-group').filter(function () { return list.indexOf(String($(this).data('group'))) !== -1; }).first().addClass('is-first');
        });
        $('#cpStageHint').hide();
    }

    // Saved content: the Detail tab shows what the current stage needs
    var STAGE_GROUPS = {
        Draft: ['1', '2'], Scheduled: ['1', '2'],
        Produksi: ['1', '2', 'caption'], Review: ['1', '2', 'caption'], Revisi: ['1', '2', 'caption'],
        Done: ['1', '2', 'caption', 'links'], Published: ['1', '2', 'caption', 'links'], Cancelled: ['1', '2', 'caption', 'links']
    };
    var STAGE_HINTS = {
        Draft: 'Caption, aset & link publikasi muncul setelah konten masuk Produksi.',
        Scheduled: 'Caption, aset & link publikasi muncul setelah konten masuk Produksi.',
        Produksi: 'Link publikasi muncul saat konten Siap Publish.',
        Review: 'Link publikasi muncul saat konten Siap Publish.',
        Revisi: 'Link publikasi muncul saat konten Siap Publish.'
    };
    function setStageGroups(status) {
        showGroups(STAGE_GROUPS[status] || ['1', '2', 'caption', 'links']);
        var hint = STAGE_HINTS[status];
        $('#cpStageHint').html(hint ? '<i class="fas fa-info-circle mr-1"></i>' + esc(hint) : '').toggle(!!hint);
    }

    function missingBasics() {
        var missing = [];
        if (!$.trim($('#judul').val())) missing.push('Judul');
        if (!$('#tanggal_publish_date').val()) missing.push('Tanggal tayang');
        if (!($('#platform').val() || []).length) missing.push('Platform');
        if (!($('#jenis_konten').val() || []).length) missing.push('Jenis konten');
        return missing;
    }

    $('#cpNextStep').on('click', function () {
        if (wizardStep === 1) {
            var missing = missingBasics();
            if (missing.length) { Swal.fire('Lengkapi dulu', missing.join(', ') + ' wajib diisi.', 'warning'); return; }
        }
        setWizard(Math.min(3, wizardStep + 1));
    });
    $('#cpPrevStep').on('click', function () { setWizard(Math.max(1, wizardStep - 1)); });

    // Riwayat panel on the right only exists for saved content
    function showPlanSide(show) {
        $('#cpSide').toggle(show);
        $('#cpMain').toggleClass('col-lg-8', show);
    }

    function prepareNewPlan(opts) {
        opts = opts || {};
        resetPlanModal();
        var src = opts.source || null;
        $('#contentPlanModalLabel').text(src ? 'Jadwalkan Ide Konten' : 'Jadwal Konten Baru');
        $('#tanggal_publish_date').val(opts.date || moment().format('YYYY-MM-DD'));
        $('#tanggal_publish_time').val('09:00');
        if (src) {
            $planForm.find('input[name="content_list_id"]').val(src.id);
            $('#judul').val(src.judul || '');
            setBrands($('#brand'), src.brand);
            $('#platform').val(src.platform || []).trigger('change.select2');
            $('#jenis_konten').val(src.jenis_konten || []).trigger('change.select2');
            $('#konten_pilar').val(src.konten_pilar || '').trigger('change.select2');
            $('#assigned_to').val(src.assigned_to || '').trigger('change.select2');
            $('#link_referensi').val(src.link_referensi || '');
            if (src.gambar_url) addServerRef(src.gambar_url, null);
        }
        renderLinkInputs($('#platform').val() || [], {});
        setWizard(1);
        $planModal.modal('show');
    }

    function fillPlanForm(p) {
        $('#judul').val(p.judul || '');
        var pub = p.tanggal_publish ? moment(p.tanggal_publish) : null;
        $('#tanggal_publish_date').val(pub ? pub.format('YYYY-MM-DD') : '');
        $('#tanggal_publish_time').val(pub ? pub.format('HH:mm') : '09:00');
        $('#tanggal_produksi').val(p.tanggal_produksi || '');
        $('#assigned_to').val(p.assigned_to || '').trigger('change.select2');
        setBrands($('#brand'), p.brand);
        $('#platform').val(p.platform || []).trigger('change.select2');
        $('#jenis_konten').val(p.jenis_konten || []).trigger('change.select2');
        $('#konten_pilar').val(p.konten_pilar || '').trigger('change.select2');
        $('#caption').val(p.caption || '');
        $('#mention').val(p.mention || '');
        $('#link_referensi').val(p.link_referensi || '');
        $('#link_asset').val(p.link_asset || '');
        renderLinkInputs(p.platform || [], p.link_publikasi || {});
        refreshLinkPreviews();
    }

    var STEPS = [
        { label: 'Terjadwal', statuses: ['Draft', 'Scheduled'] },
        { label: 'Produksi', statuses: ['Produksi'] },
        { label: 'Review', statuses: ['Review', 'Revisi'] },
        { label: 'Siap Publish', statuses: ['Done'] },
        { label: 'Published', statuses: ['Published'] }
    ];

    function renderWorkflow(p) {
        var idx = -1;
        STEPS.forEach(function (s, i) { if (s.statuses.indexOf(p.status) !== -1) idx = i; });
        var html = '';
        STEPS.forEach(function (s, i) {
            var cls = i < idx ? 'done' : (i === idx ? 'current' : '');
            if (i === idx && p.status === 'Revisi') cls += ' is-revisi';
            var label = (i === idx && p.status === 'Revisi') ? 'Revisi' : s.label;
            if (i) html += '<span class="cp-step-line"></span>';
            html += '<span class="cp-step ' + cls + '"><span class="dot">' + (i < idx ? '<i class="fas fa-check"></i>' : (i + 1)) + '</span>' + label + '</span>';
        });
        $('#cpSteps').html(p.status === 'Cancelled' ? '<span class="cp-step current"><span class="dot"><i class="fas fa-ban"></i></span>Dibatalkan</span>' : html);

        // the note of the step that brought the content here (revisi reason, approval note, …)
        var last = (p.timeline || []).filter(function (t) { return t.to_status === p.status && t.notes; }).pop();
        var hint = {
            Draft: 'Mulai produksi saat konten mulai dikerjakan.',
            Scheduled: 'Mulai produksi saat konten mulai dikerjakan.',
            Produksi: 'Setelah konten jadi, ajukan review ke tim Marketing.',
            Review: 'Menunggu review: Approve bila siap tayang, atau minta revisi dengan catatan.',
            Revisi: 'Perbaiki konten lalu ajukan review lagi.',
            Done: 'Siap tayang. Setelah diposting, tandai Published dan isi link postingan.',
            Published: 'Sudah tayang. Isi angka performa di tab Report.',
            Cancelled: 'Konten dibatalkan.'
        }[p.status] || '';
        var note = last ? '<strong>' + esc(last.user) + '</strong> · ' + W.date(last.at, { time: true }) + '\n' + esc(last.notes) : '';
        var revCount = (p.timeline || []).filter(function (t) { return t.action === 'revise'; }).length;
        if (revCount) hint += (hint ? ' ' : '') + '(Sudah ' + revCount + '× revisi.)';
        $('#cpWorkflowNote').html((hint ? '<div class="wf-muted">' + esc(hint) + '</div>' : '') + (note ? '<div class="mt-1">' + note + '</div>' : '')).toggle(!!(hint || note));

        // Terjadwal: preparation checklist; "Mulai Produksi" only once PIC and brief are there
        var prep = (p.transitions || []).length ? prepStep(p) : null;
        if (p.status === 'Scheduled' || p.status === 'Draft') {
            var check = function (ok, label) { return '<span class="cp-prep' + (ok ? ' is-ok' : '') + '"><i class="fas ' + (ok ? 'fa-check-circle' : 'fa-circle') + '"></i>' + label + '</span>'; };
            var job = p.design_job;
            var next = prep ? 'Lengkapi persiapan, lalu mulai produksi.'
                : (job ? 'Persiapan lengkap. Produksi mulai otomatis saat ' + (job.designer || 'designer') + ' mengambil job.'
                       : 'Persiapan lengkap. Klik Mulai Produksi saat konten mulai dikerjakan.');
            $('#cpWorkflowNote').html('<div class="cp-prep-list">' + check(true, 'Dasar') + check(!!p.assigned_to, 'PIC') + check(!!p.has_brief, 'Brief')
                + (job ? check(false, job.designer ? 'Designer: ' + esc(job.designer) : 'Designer: menunggu diambil') : '')
                + '</div><div class="wf-muted mt-1">' + esc(next) + '</div>').show();
        } else if ((p.status === 'Produksi' || p.status === 'Revisi') && p.design_job) {
            $('#cpWorkflowNote').html('<div class="wf-muted"><i class="fas fa-pen-nib mr-1"></i>'
                + esc('Sedang ' + (p.status === 'Revisi' ? 'direvisi' : 'dikerjakan') + ' ' + (p.design_job.designer || 'designer')
                + '. Status jadi Review otomatis saat designer menandai selesai; kirim hasilnya di grup WA.') + '</div>'
                + (note ? '<div class="mt-1">' + note + '</div>' : '')).show();
        }
        var btns = (p.transitions || []).filter(function (t) { return !(prep && t.action === 'start'); }).map(function (t) {
            return '<button type="button" class="btn btn-sm btn-' + t.style + ' js-transition" data-action="' + t.action + '">' + esc(t.label) + '</button>';
        }).join('');
        if (prep) {
            btns = '<button type="button" class="btn btn-sm btn-primary js-prep" data-step="' + prep + '">'
                + (prep === 2 ? '<i class="fas fa-user-plus mr-1"></i> Tentukan PIC' : '<i class="fas fa-pen mr-1"></i> Isi brief') + '</button>' + btns;
        }
        $('#cpWorkflowActions').html(btns).toggle(!!btns);
        $('#cpWorkflow').show();
    }

    function renderDesignList(p) {
        var list = p.design_requests || [];
        $('#cpDesignCount').text(list.length).toggle(list.length > 0);
        $('#cpDesignList').html(list.length ? list.map(function (d) {
            return '<a class="wf-card d-block mb-2 text-reset" style="text-decoration:none" href="' + ROUTES.designPage + '?open=' + d.id + '" target="_blank" rel="noopener">'
                + '<div class="d-flex justify-content-between flex-wrap" style="gap:6px"><span class="wf-card-title">#' + d.id + ' ' + esc(d.judul) + '</span>' + W.pill('design', d.status) + '</div>'
                + '<div class="wf-meta mt-1">' + (d.designer ? 'Designer: ' + esc(d.designer) : 'Belum diambil designer') + (d.deadline ? ' · Deadline ' + W.date(d.deadline) : '') + ' · <i class="fas fa-external-link-alt"></i> buka</div>'
                + '</a>';
        }).join('') : '<div class="wf-muted small mb-2">Belum ada request desain untuk konten ini.</div>');
    }

    // opts.section: open that part (see SECTIONS); default the summary. keepTab: stay on the part now open.
    function openPlan(id, keepTab, opts) {
        if (!id) return;
        opts = opts || {};
        var keepSection = keepTab ? currentSection : null;
        $.get('/marketing/content-plan/' + id).done(function (p) {
            if (!$planModal.hasClass('show')) resetPlanModal();
            currentPlan = p;
            $planForm.attr('data-action', 'update').attr('data-id', p.id);
            $planForm.find('input[name="content_list_id"]').val('');
            $('#contentPlanModalLabel').text('Konten #' + p.id);
            $('#cpModalStatus').html(planPill(p.status));
            fillPlanForm(p);
            fillWho(p.design_job);
            renderWorkflow(p);
            setWizard(0); // a saved content always opens as the one full form (before the stage sections)
            setStageGroups(p.status);
            renderDesignList(p);
            $('#cpTimeline').html(W.timeline(p.timeline, PLAN_ACTIONS));
            showPlanSide(true);
            $('.cp-saved-only').show();
            if (CAN_MANAGE) $('.btn-delete-plan').show();
            loadBriefInto(p);
            resetReport(p.id, p.status);
            renderSummary(p);
            if (!keepTab) sectionFromSummary = false;
            openSectionNow(opts.section || keepSection || 'ringkasan');
            if (!$planModal.hasClass('show')) $planModal.modal('show');
        }).fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr, 'Konten tidak ditemukan.'), 'error'); });
    }

    // ---- workflow buttons
    /**
     * One workflow step, from the modal or straight from a board card.
     * plan: {id, judul, transitions, platform: [names], link_publikasi: {platform: url}}
     */
    /**
     * "Tayang jam": when the post really went live. The button is often pressed later (checked off at noon
     * for a 09.00 post), so it starts at the scheduled time; the moment of recording is kept separately.
     * withDate: also the date (regular content may go live on another day).
     */
    function tayangInput(scheduled, withDate) {
        var m = scheduled ? moment(scheduled) : moment();
        return '<div class="d-flex flex-wrap align-items-end mb-1" style="gap:8px">'
            + (withDate ? '<div style="flex:1 1 150px"><label class="d-block mb-1" for="tayangDate" style="text-transform:none">Tayang tanggal</label>'
                + '<input type="date" id="tayangDate" class="swal2-input m-0 w-100" value="' + m.format('YYYY-MM-DD') + '"></div>' : '')
            + '<div style="flex:1 1 110px"><label class="d-block mb-1" for="tayangTime" style="text-transform:none">Tayang jam</label>'
            + '<input type="time" id="tayangTime" class="swal2-input m-0 w-100" value="' + m.format('HH:mm') + '"></div></div>'
            + '<div class="small text-muted mb-1">Isi jam postingan benar-benar tayang (bila dicentang belakangan).</div>';
    }
    function readTayang(withDate) {
        var time = $('#tayangTime').val();
        var date = withDate ? $('#tayangDate').val() : 'x';
        if (!time || !date) { Swal.showValidationMessage('Isi kapan postingan tayang.'); return null; }
        return withDate ? date + ' ' + time : time;
    }

    /**
     * Instagram post link checked while typed: format (story / profile / short link) and whether the post is
     * found in a connected account, so a wrong link is fixed before it silently drops out of the insights.
     */
    function bindLinkCheck($input, $out, checkNow) {
        if (!$input.length || !$out.length) return;
        var timer = null, last = null;
        var run = function () {
            var url = $.trim($input.val());
            if (url === last) return;
            last = url;
            if (!url) { $out.empty(); return; }
            $out.attr('class', 'cp-link-check js-link-check is-wait').html('<span class="spinner-border spinner-border-sm mr-1"></span> Mengecek link…');
            $.getJSON(INSIGHT_URL + '/check-link', { url: url }).done(function (r) {
                if ($.trim($input.val()) !== url) return;
                var cls = { ok: 'is-ok', not_post: 'is-bad', not_found: 'is-warn', unchecked: 'is-wait' }[r.status] || '';
                var icon = { ok: 'fa-check-circle', not_post: 'fa-times-circle', not_found: 'fa-exclamation-triangle', unchecked: 'fa-info-circle' }[r.status] || 'fa-info-circle';
                $out.attr('class', 'cp-link-check js-link-check ' + cls).html(r.message ? '<i class="fas ' + icon + ' mr-1"></i>' + esc(r.message) : '');
            }).fail(function () { $out.empty(); });
        };
        $input.on('input paste change', function () { clearTimeout(timer); timer = setTimeout(run, 600); });
        if (checkNow && $.trim($input.val())) run();
        else last = $.trim($input.val()); // an unchanged saved link is not re-checked
    }

    function runTransition(plan, action, onDone) {
        var t = (plan.transitions || []).filter(function (x) { return x.action === action; })[0];
        if (!t) return;
        var opts = {
            title: t.label + '?', note: t.note, confirmText: t.label, confirmColor: W.buttonStyleColor(t.style),
            text: plan.judul ? '“' + plan.judul + '”' : undefined
        };

        if (action === 'publish') {
            // ask the post link per platform in the same dialog
            var links = plan.link_publikasi || {};
            var platforms = plan.platform || [];
            opts.text = undefined;
            opts.html = '<div class="text-left">' + tayangInput(plan.tanggal_publish, true) + platforms.map(function (p) {
                return '<label class="d-block mt-2 mb-1" style="text-transform:none">Link ' + esc(p) + '</label>'
                    + '<input class="swal2-input m-0 w-100 js-pub-link" data-platform="' + esc(p) + '" value="' + esc(links[p] || '') + '" placeholder="https://…">'
                    + (p === 'Instagram' && CAN_MANAGE ? '<div class="cp-link-check js-link-check"></div>' : '');
            }).join('') + '<div class="small text-muted mt-2">Minimal satu link wajib diisi.</div></div>';
            opts.didOpen = function () {
                var $ig = $('.js-pub-link[data-platform="Instagram"]');
                bindLinkCheck($ig, $ig.next('.js-link-check'), true);
            };
            opts.note = null;
            opts.preConfirm = function () {
                var out = {}, any = false;
                $('.js-pub-link').each(function () { var v = $.trim($(this).val()); if (v) { out[$(this).data('platform')] = v; any = true; } });
                if (!any) { Swal.showValidationMessage('Isi minimal satu link publikasi.'); return false; }
                var tayang = readTayang(true);
                if (!tayang) return false;
                return { links: out, tayang_at: tayang };
            };
        }

        if (action === 'approve' || action === 'revise') {
            // Decisions are made in the team's WA group; the creator records who said it + pastes the message
            var isRevise = action === 'revise';
            // Approval by a Marketing user defaults to themselves; others pick from the list
            var preset = !isRevise && SELF_APPROVER ? SELF_APPROVER : '';
            var actorNames = preset && REVIEWERS.indexOf(preset) === -1 ? [preset].concat(REVIEWERS) : REVIEWERS;
            opts.note = null;
            opts.text = undefined;
            opts.title = isRevise ? 'Catat revisi' : 'Catat approval';
            opts.html = '<div class="text-left cp-decision">'
                + (plan.judul ? '<div class="small text-muted mb-2">“' + esc(plan.judul) + '”</div>' : '')
                + '<label class="d-block mb-1" for="cpActor" style="text-transform:none">' + (isRevise ? 'Revisi dari siapa?' : 'Di-approve oleh siapa?') + '</label>'
                + '<select id="cpActor" class="swal2-select m-0 w-100"><option value="">— pilih —</option>'
                + actorNames.map(function (n) { return '<option' + (n === preset ? ' selected' : '') + '>' + esc(n) + '</option>'; }).join('')
                + '<option value="__other">Lainnya…</option></select>'
                + '<input id="cpActorOther" class="swal2-input m-0 mt-2 w-100" placeholder="Tulis nama" style="display:none">'
                + '<label class="d-block mt-3 mb-1" for="cpDecisionNote" style="text-transform:none">'
                + (isRevise ? 'Isi revisi <span class="text-danger">*</span> <span class="text-muted small">(tempel dari WA)</span>' : 'Catatan <span class="text-muted small">(opsional)</span>') + '</label>'
                + '<textarea id="cpDecisionNote" class="swal2-textarea m-0 w-100" rows="4" placeholder="'
                + (isRevise ? 'Mis. logo kebesaran, ganti hook di detik 3' : 'Mis. ok tayang, caption boleh dipakai') + '"></textarea>'
                + '</div>';
            opts.didOpen = function () {
                $('#cpActor').on('change', function () { $('#cpActorOther').toggle(this.value === '__other').focus(); });
            };
            opts.preConfirm = function () {
                var actor = $('#cpActor').val() === '__other' ? $.trim($('#cpActorOther').val()) : $('#cpActor').val();
                var note = $.trim($('#cpDecisionNote').val());
                if (!actor) { Swal.showValidationMessage(isRevise ? 'Pilih dari siapa revisinya.' : 'Pilih siapa yang meng-approve.'); return false; }
                if (isRevise && !note) { Swal.showValidationMessage('Isi revisinya (tempel pesan dari WA).'); return false; }
                return { actor: actor, notes: note };
            };
        }

        W.askNote(opts).then(function (res) {
            if (!res) return;
            var payload = { action: action, notes: res.notes || '' };
            if (action === 'publish') { payload.link_publikasi = res.value.links; payload.tayang_at = res.value.tayang_at; }
            if (action === 'approve' || action === 'revise') { payload.actor = res.value.actor; payload.notes = res.value.notes; }
            $.post('/marketing/content-plan/' + plan.id + '/transition', payload)
                .done(function (r) {
                    W.toast('Status: ' + r.status_label);
                    // just published with an Instagram link: take the first insight snapshot right away
                    if (action === 'publish' && payload.link_publikasi && payload.link_publikasi.Instagram) {
                        $.post(INSIGHT_URL + '/sync', { plan_id: plan.id }).always(function () { loadBoard({ silent: true }); });
                    }
                    loadBoard({ silent: true });
                    if (onDone) onDone(r);
                })
                .fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr), 'error'); });
        });
    }

    $(document).on('click', '#cpWorkflowActions .js-transition', function () {
        if (!currentPlan) return;
        var id = currentPlan.id;
        runTransition(currentPlan, $(this).data('action'), function () { openPlan(id, true); });
    });

    // Quick actions on the board cards: the main next step without opening the modal
    $(document).on('click', '#weekBoard .js-card-action', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var row = rowsById[$(this).closest('.cp-item').data('id')];
        if (!row) return;
        runTransition({
            id: row.id, judul: row.judul, transitions: row.transitions, tanggal_publish: row.tanggal_publish,
            platform: (row.platforms || []).map(function (p) { return p.name; }),
            link_publikasi: row.link_publikasi || {}
        }, $(this).data('action'));
    });

    // ---- link publikasi inputs (one per platform)
    function renderLinkInputs(platforms, existing) {
        var $wrap = $('#link_publikasi_wrapper').empty();
        existing = existing || {};
        if (!platforms || !platforms.length) {
            $wrap.html('<div class="col-12 wf-muted small">Pilih platform dulu.</div>');
            return;
        }
        platforms.forEach(function (p) {
            var $col = $('<div class="col-sm-6 mb-2"></div>');
            var $input = $('<input type="text" class="form-control form-control-sm">')
                .attr('name', 'link_publikasi[' + p + ']').attr('placeholder', p + ': https://…').attr('aria-label', 'Link ' + p)
                .val(existing[p] || '');
            $col.append($input);
            if (p === 'Instagram' && CAN_MANAGE) {
                var $check = $('<div class="cp-link-check js-link-check"></div>');
                $col.append($check);
                bindLinkCheck($input, $check);
            }
            $wrap.append($col);
        });
        if (!CAN_MANAGE) $wrap.find('input').prop('disabled', true);
    }
    $('#platform').on('change', function () {
        var typed = {};
        $('#link_publikasi_wrapper input').each(function () {
            var m = /^link_publikasi\[(.*)\]$/.exec($(this).attr('name') || '');
            if (m && $(this).val()) typed[m[1]] = $(this).val();
        });
        renderLinkInputs($(this).val() || [], typed);
    });

    // ---- save (detail + brief + report in one click)
    $planForm.on('submit', function (e) {
        e.preventDefault();
        if (!CAN_MANAGE) return;
        var missing = missingBasics();
        if (missing.length) {
            if (wizardStep) setWizard(1); else if (currentSection) openSectionNow('dasar'); else showPlanTab('detail');
            Swal.fire('Lengkapi dulu', missing.join(', ') + ' wajib diisi.', 'warning');
            return;
        }

        var action = $planForm.attr('data-action'), id = $planForm.attr('data-id');
        var fd = new FormData(this);
        fd.set('tanggal_publish', $('#tanggal_publish_date').val() + ' ' + ($('#tanggal_publish_time').val() || '09:00') + ':00');
        if (action === 'update') fd.append('_method', 'PUT');

        var $btn = $planModal.find('.btn-save-plan');
        var original = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Menyimpan…');

        $.ajax({ url: action === 'store' ? ROUTES.store : '/marketing/content-plan/' + id, method: 'POST', data: fd, processData: false, contentType: false })
            .done(function (res) {
                var planId = (res && res.data && res.data.id) || id;
                // a plan scheduled from an idea already got a brief with the idea's image: update that one
                if (res && res.brief_id && !$('#cb_id').val()) $('#cb_id').val(res.brief_id);
                $.when(saveBrief(planId), saveReport(planId)).always(function () {
                    // a part opened from the summary goes back to the (refreshed) summary; otherwise close
                    if (action === 'update' && sectionFromSummary) openPlan(planId, false, { section: 'ringkasan' });
                    else $planModal.modal('hide');
                    loadBoard({ silent: true });
                    loadIdeas(true);
                    W.toast(action === 'store' ? 'Jadwal konten dibuat' : 'Perubahan disimpan');
                    $btn.prop('disabled', false).html(original);
                });
            })
            .fail(function (xhr) {
                Swal.fire('Gagal menyimpan', W.errorMessage(xhr), 'error');
                $btn.prop('disabled', false).html(original);
            });
    });

    $('.btn-delete-plan').on('click', function () {
        if (!currentPlan) return;
        W.askNote({ title: 'Hapus konten ini?', text: 'Brief dan report ikut tidak bisa diakses. Bila ingin menyimpan jejaknya, gunakan "Batalkan".', confirmText: 'Hapus', confirmColor: '#c92a2a' })
            .then(function (res) {
                if (!res) return;
                $.ajax({ url: '/marketing/content-plan/' + currentPlan.id, method: 'DELETE' })
                    .done(function () { $planModal.modal('hide'); loadBoard({ silent: true }); loadIdeas(true); W.toast('Konten dihapus'); })
                    .fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr), 'error'); });
            });
    });

    // ---- "Siapa yang mengerjakan?" (Penugasan): "Butuh designer" makes the designer's job on save
    function setWho(needsDesigner, lockedText) {
        $('#needs_designer').val(needsDesigner ? 1 : 0);
        $('#cpWhoChips .cp-chip-opt').each(function () {
            var on = String($(this).data('who')) === (needsDesigner ? '1' : '0');
            $(this).toggleClass('is-on', on).attr('aria-pressed', on).prop('disabled', !!lockedText || !CAN_MANAGE)
                .find('.cp-chip-check').remove();
            if (on) $(this).append('<i class="fas fa-check cp-chip-check"></i>');
        });
        $('#cpDesignerFields').toggle(!!needsDesigner);
        $('#cpWhoLock').html(lockedText ? '<i class="fas fa-lock mr-1"></i>' + esc(lockedText) : '').toggle(!!lockedText);
    }
    $('#cpWhoChips').on('click', '.cp-chip-opt', function () {
        if (!$(this).prop('disabled')) setWho(String($(this).data('who')) === '1');
    });
    function fillWho(job) {
        $('#designer_id').prop('disabled', !CAN_MANAGE).val(job ? (job.designer_id || '') : '').trigger('change.select2');
        if (!job) { setWho(false); return; }
        // once the designer took the job the choice is fixed (revisi / approval run through the content)
        var locked = job.status !== 'Baru'
            ? 'Sedang dikerjakan designer' + (job.designer ? ' ' + job.designer : '') + ' (' + job.status + ').'
            : '';
        setWho(true, locked);
        if (locked) $('#designer_id').prop('disabled', true).trigger('change.select2');
    }
    // Best posting time (from the Instagram insights), shown under the time field; follows the chosen jenis
    var bestTimeCache = {};
    function showBestTime() {
        var $box = $('#cpBestTime');
        if (!CAN_MANAGE || (currentPlan && currentPlan.status === 'Published')) { $box.hide(); return; }
        var jenis = ($('#jenis_konten').val() || [])[0] || '';
        var render = function (h) {
            if (!h) { $box.hide(); return; }
            var start = h.slot.split('–')[0].replace('.', ':');
            $box.html('<i class="fas fa-lightbulb mr-1"></i>Jam tayang terbaik' + (jenis ? ' untuk <b>' + esc(jenis) + '</b>' : '') + ': <b>' + esc(h.slot) + '</b>'
                + ' — rata-rata ' + fmtNum(h.avg) + ' views di 3 hari pertama'
                + (h.ratio && h.ratio > 1 ? ' (' + String(Math.round((h.ratio - 1) * 100)) + '% di atas rata-rata)' : '')
                + ', dari ' + h.count + ' konten.' + (h.day ? ' Hari terbaik: <b>' + esc(h.day) + '</b>.' : '')
                + ' <button type="button" class="js-use-besttime" data-time="' + esc(start) + '">Pakai jam ' + esc(start.replace(':', '.')) + '</button>').show();
        };
        if (bestTimeCache.hasOwnProperty(jenis)) { render(bestTimeCache[jenis]); return; }
        $.getJSON(@json(route('marketing.content-analytics.best-time')), { jenis: jenis }).done(function (r) {
            bestTimeCache[jenis] = r.hint;
            render(r.hint);
        });
    }
    $(document).on('click', '.js-use-besttime', function () { $('#tanggal_publish_time').val($(this).data('time')).trigger('change'); });
    $('#jenis_konten').on('change', showBestTime);
    $planModal.on('shown.bs.modal', showBestTime);

    // Workload of the chosen designer, so a busy designer is not given yet another job blindly
    var capacityCache = null, capacityAt = 0;
    function showDesignerLoad() {
        var $out = $('#cpDesignerLoad');
        if (!CAN_MANAGE || !$('#cpDesignerFields').is(':visible')) { $out.empty(); return; }
        var render = function (c) {
            var id = String($('#designer_id').val() || '');
            if (!id) {
                $out.html(c.queue.count ? '<i class="fas fa-inbox mr-1"></i>Antrian saat ini: <b>' + c.queue.count + ' job</b> menunggu diambil.' : '');
                return;
            }
            var d = (c.designers || []).filter(function (x) { return String(x.id) === id; })[0];
            if (!d) { $out.empty(); return; }
            var lvl = { ringan: 'Ringan', sedang: 'Sedang', padat: 'Padat' }[d.level];
            $out.attr('class', 'cp-designer-load is-' + d.level).html('<i class="fas fa-briefcase mr-1"></i>Beban <b>' + lvl + '</b>: ' + d.active + ' job aktif'
                + (d.due_week ? ', ' + d.due_week + ' deadline minggu ini' : '') + (d.late ? ', <b>' + d.late + ' terlambat</b>' : '')
                + (d.clashes ? ', ' + d.clashes + ' hari deadline bertumpuk' : '') + '.');
        };
        if (capacityCache && Date.now() - capacityAt < 60000) { render(capacityCache); return; }
        $.getJSON(ROUTES.designPage + '/capacity').done(function (c) { capacityCache = c; capacityAt = Date.now(); render(c); });
    }
    $(document).on('change', '#designer_id', showDesignerLoad);
    $('#cpWhoChips').on('click', '.cp-chip-opt', function () { setTimeout(showDesignerLoad, 0); });
    $planModal.on('shown.bs.modal', showDesignerLoad);
    $('#cpNextStep, #cpPrevStep').on('click', function () { setTimeout(showDesignerLoad, 0); });

    $(document).on('click', '.js-go-penugasan', function (e) {
        e.preventDefault();
        if (currentSection) { sectionFromSummary = true; openSectionNow('penugasan'); return; }
        showPlanTab('detail');
        setTimeout(function () { var el = document.getElementById('cpWhoChips'); if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 200);
    });

    // =================================================================== BRIEF
    var briefFiles = null;   // DataTransfer with newly picked images
    var briefRemoved = [];   // stored image paths the user removed
    var briefLoadedHtml = null;

    function newTransfer() { try { return new DataTransfer(); } catch (e) { return null; } }

    function resetBrief() {
        destroyEditor();
        $('#cb_content_plan_id, #cb_id, #cb_headline, #cb_sub_headline').val('');
        $('#cb_isi_konten').val('');
        $('#cb_preview').empty();
        briefFiles = newTransfer();
        briefRemoved = [];
        briefLoadedHtml = null;
        updateBriefCheck();
    }

    function loadBriefInto(p) {
        resetBrief();
        $('#cb_content_plan_id').val(p.id);
        var b = p.brief;
        if (!b) return;
        $('#cb_id').val(b.id);
        $('#cb_headline').val(b.headline || '');
        $('#cb_sub_headline').val(b.sub_headline || '');
        briefLoadedHtml = b.isi_konten || '';
        $('#cb_isi_konten').val(briefLoadedHtml);
        (b.visual_references || []).forEach(function (path) { addServerRef(storageUrl(path), path); });
        if ($('#tab-brief').hasClass('active')) initEditor();
        updateBriefCheck();
    }

    function storageUrl(path) {
        if (!path) return '';
        if (/^(https?:|data:|blob:)/.test(path)) return path;
        return '/storage/' + String(path).replace(/^\/?storage\//, '');
    }

    function addServerRef(src, path) {
        var $el = $('<div class="cp-ref"><img alt=""></div>');
        $el.find('img').attr('src', src);
        if (path && CAN_MANAGE) {
            $('<button type="button" class="cp-ref-remove" title="Hapus">&times;</button>').on('click', function (e) {
                e.stopPropagation();
                briefRemoved.push(path);
                $el.remove();
                updateBriefCheck();
            }).appendTo($el);
        }
        $('#cb_preview').append($el);
    }

    function renderNewRefs() {
        $('#cb_preview .cp-ref.is-new').remove();
        if (!briefFiles) return;
        Array.from(briefFiles.files).forEach(function (file, idx) {
            var $el = $('<div class="cp-ref is-new"><img alt=""></div>');
            var reader = new FileReader();
            reader.onload = function (ev) { $el.find('img').attr('src', ev.target.result); };
            reader.readAsDataURL(file);
            $('<button type="button" class="cp-ref-remove" title="Hapus">&times;</button>').on('click', function (e) {
                e.stopPropagation();
                var next = newTransfer();
                Array.from(briefFiles.files).forEach(function (f, i) { if (i !== idx) next.items.add(f); });
                briefFiles = next;
                renderNewRefs();
                updateBriefCheck();
            }).appendTo($el);
            $('#cb_preview').append($el);
        });
    }

    function addBriefFiles(list) {
        if (!briefFiles) briefFiles = newTransfer();
        if (!briefFiles) return;
        Array.from(list || []).forEach(function (f) { if (f.type && f.type.indexOf('image/') === 0) briefFiles.items.add(f); });
        renderNewRefs();
        updateBriefCheck();
    }

    $('#cb_drop_area').on('click keydown', function (e) {
        if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
        e.preventDefault();
        $('#cb_visual_references').trigger('click');
    }).on('dragover', function (e) { e.preventDefault(); $(this).addClass('is-over'); })
      .on('dragleave', function () { $(this).removeClass('is-over'); })
      .on('drop', function (e) {
          e.preventDefault(); $(this).removeClass('is-over');
          addBriefFiles(e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files);
      });
    $('#cb_visual_references').on('change', function () { addBriefFiles(this.files); $(this).val(''); });
    $(document).on('paste', function (e) {
        // screenshots pasted while the Brief tab is open
        if (!$planModal.hasClass('show') || !$('#tab-brief').hasClass('active') || !CAN_MANAGE) return;
        var items = (e.originalEvent.clipboardData || {}).items || [];
        var files = [];
        Array.from(items).forEach(function (it) { if (it.kind === 'file' && it.type.indexOf('image/') === 0) { var f = it.getAsFile(); if (f) files.push(f); } });
        if (files.length) { e.preventDefault(); addBriefFiles(files); }
    });
    $(document).on('click', '#cb_preview img, .cl-preview img, .cp-idea-thumb', function () { window.open($(this).attr('src'), '_blank'); });

    function initEditor() {
        if (!$.fn.summernote || $('#cb_isi_konten').data('summernote')) return;
        $('#cb_isi_konten').summernote({
            height: 200,
            // Bootstrap tooltips of the toolbar stay stuck on the page after the modal closes
            tooltip: false,
            toolbar: [['style', ['bold', 'italic', 'underline', 'clear']], ['para', ['ul', 'ol']], ['insert', ['link']]],
            callbacks: { onChange: updateBriefCheck }
        });
        if (briefLoadedHtml) $('#cb_isi_konten').summernote('code', briefLoadedHtml);
        if (!CAN_MANAGE) $('#cb_isi_konten').summernote('disable');
    }
    function destroyEditor() {
        try { if ($('#cb_isi_konten').data('summernote')) $('#cb_isi_konten').summernote('destroy'); } catch (e) {}
    }
    $('#tab-brief-tab').on('shown.bs.tab', initEditor);
    $planModal.on('hidden.bs.modal', function () { destroyEditor(); currentPlan = null; });

    function briefHtml() {
        var $el = $('#cb_isi_konten');
        return $el.data('summernote') ? $el.summernote('code') : $el.val();
    }
    function isEmptyHtml(html) { return $.trim($('<div>').html(html || '').text()) === '' && !/<img/i.test(html || ''); }

    function hasBriefChanges() {
        var files = briefFiles ? briefFiles.files.length : 0;
        return $.trim($('#cb_headline').val()) !== '' || $.trim($('#cb_sub_headline').val()) !== ''
            || !isEmptyHtml(briefHtml()) || files > 0 || briefRemoved.length > 0;
    }
    function updateBriefCheck() { $('#tab-brief-tab .brief-check').toggle(hasBriefChanges()); }
    $('#cb_headline, #cb_sub_headline').on('input', updateBriefCheck);

    function saveBrief(planId) {
        if (!hasBriefChanges()) return $.Deferred().resolve().promise();
        var fd = new FormData();
        fd.append('content_plan_id', planId);
        fd.append('headline', $('#cb_headline').val());
        fd.append('sub_headline', $('#cb_sub_headline').val());
        var html = briefHtml();
        fd.append('isi_konten', isEmptyHtml(html) ? '' : html);
        if (briefFiles) Array.from(briefFiles.files).forEach(function (f) { fd.append('visual_references[]', f); });
        briefRemoved.forEach(function (p) { fd.append('remove_visual_references[]', p); });
        if ($('#cb_id').val()) fd.append('id', $('#cb_id').val());
        return $.ajax({ url: '/marketing/content-brief', method: 'POST', data: fd, processData: false, contentType: false })
            .fail(function (xhr) { Swal.fire('Brief gagal disimpan', W.errorMessage(xhr), 'error'); });
    }

    // =================================================================== REPORT
    var METRICS = ['likes', 'comments', 'saves', 'shares', 'reach', 'impressions'];

    function metric(name) { return parseInt($('#cr_' + name).val() || 0, 10) || 0; }
    function computeRates() {
        var interactions = metric('likes') + metric('comments') + metric('saves') + metric('shares');
        $('#cr_eri').text((metric('impressions') > 0 ? interactions / metric('impressions') * 100 : 0).toFixed(2));
        $('#cr_err').text((metric('reach') > 0 ? interactions / metric('reach') * 100 : 0).toFixed(2));
    }
    $(METRICS.map(function (m) { return '#cr_' + m; }).join(',')).on('input', computeRates);

    function clearReportForm() {
        $('#cr_id').val('');
        METRICS.forEach(function (m) { $('#cr_' + m).val(0); });
        computeRates();
        $('#cr_history_tbody tr').removeClass('table-active');
    }

    function resetReport(planId, status) {
        loadInsight(planId, status);
        clearReportForm();
        $('#cr_content_plan_id').val(planId || '');
        $('#cpReportHint').toggle(!!planId && status !== 'Published');
        applyReportMode();
        renderReportHistory([]);
        if (!planId) return;
        $.getJSON('/marketing/content-report/by-plan/' + planId).done(function (reports) {
            if (String($('#cr_content_plan_id').val()) === String(planId)) renderReportHistory(Array.isArray(reports) ? reports : []);
        });
    }

    function renderReportHistory(reports) {
        var $tb = $('#cr_history_tbody').empty();
        if (!reports.length) { $tb.html('<tr><td colspan="4" class="wf-muted small">Belum ada report.</td></tr>'); return; }
        reports.forEach(function (r, i) {
            var eri = Number(r.eri || 0), prev = reports[i + 1], growth = '—';
            if (prev && Number(prev.eri)) {
                var ch = (eri - Number(prev.eri)) / Number(prev.eri) * 100;
                growth = '<span class="' + (ch > 0 ? 'text-success' : (ch < 0 ? 'text-danger' : '')) + '">' + (ch > 0 ? '↑' : (ch < 0 ? '↓' : '')) + Math.abs(ch).toFixed(1) + '%</span>';
            }
            $tb.append('<tr data-id="' + r.id + '" style="cursor:pointer"><td class="text-nowrap">' + W.date(r.recorded_at || r.created_at, { time: true }) + '</td><td>' + eri.toFixed(2) + '</td><td>' + Number(r.err || 0).toFixed(2) + '</td><td class="text-nowrap">' + growth + '</td></tr>');
        });
    }
    $('#cr_history_tbody').on('click', 'tr[data-id]', function () {
        var $tr = $(this);
        $.getJSON('/marketing/content-report/' + $tr.data('id')).done(function (r) {
            $('#cr_history_tbody tr').removeClass('table-active');
            $tr.addClass('table-active');
            $('#cr_id').val(r.id);
            METRICS.forEach(function (m) { $('#cr_' + m).val(r[m] || 0); });
            computeRates();
        });
    });
    $('#cr_new_btn').on('click', clearReportForm);

    function saveReport(planId) {
        var hasData = $('#cr_id').val() || METRICS.some(function (m) { return metric(m) > 0; });
        if (!hasData) return $.Deferred().resolve().promise();
        var data = { content_plan_id: planId };
        METRICS.forEach(function (m) { data[m] = metric(m); });
        var req = $('#cr_id').val()
            ? $.ajax({ url: '/marketing/content-report/' + $('#cr_id').val(), method: 'PUT', data: data })
            : $.post(ROUTES.reportStore, data);
        return req.fail(function (xhr) { Swal.fire('Report gagal disimpan', W.errorMessage(xhr), 'error'); });
    }

    // =================================================================== IDE KONTEN
    var ideaStatus = 'pending', ideaPage = 1, ideaSearchTimer = null;

    function ideaFilters() {
        return { filter_brand: $('#ideaBrand').val() || '', filter_assigned_to: $('#ideaPic').val() || '', q: $.trim($('#ideaSearch').val()) };
    }

    function loadIdeaStats() {
        $.getJSON(ROUTES.ideaStats, ideaFilters()).done(function (s) {
            $('#ideaStatusChips [data-count]').each(function () { $(this).text(s[$(this).data('count')] || 0); });
            var pending = s.pending || 0;
            $('#ideaPendingCount').text(pending).toggle(pending > 0);
            var ready = s.approved || 0;
            $('#cpReadyCount').text(ready);
            $('#cpReadyIdeas').toggle(CAN_MANAGE && ready > 0);
            refreshAlerts();
        });
    }

    function ideaCard(it) {
        var tags = '';
        if (it.konten_pilar) tags += '<span class="wf-chip">' + esc(it.konten_pilar) + '</span>';
        (it.jenis_konten || []).forEach(function (j) { tags += '<span class="wf-chip">' + esc(j) + '</span>'; });
        (it.platform || []).forEach(function (p) { tags += '<span class="wf-chip">' + esc(p) + '</span>'; });
        if (it.link_referensi) {
            var ref = W.embedInfo(it.link_referensi);
            var href = /^https?:\/\//i.test(it.link_referensi) ? it.link_referensi : 'https://' + it.link_referensi;
            tags += '<a class="wf-chip" href="' + esc(href) + '" target="_blank" rel="noopener" title="Buka referensi"><i class="' + (ref ? ref.icon : 'fas fa-link') + '"></i> Referensi</a>';
        }

        var info = '';
        if (it.status === 'Approved' && it.approved_by_name) info = 'Approved oleh ' + esc(it.approved_by_name) + ' · ' + W.date(it.approved_at, { time: true });
        if (it.status === 'Scheduled' && it.scheduled_plan) info = 'Dijadwalkan publish ' + W.date(it.scheduled_plan.tanggal_publish, { time: true });

        var note = '';
        if (it.approval_notes) {
            var prefix = it.status === 'Rejected' ? 'Alasan reject' : (it.status === 'Pending' ? 'Catatan sebelumnya' : 'Catatan approval');
            note = '<div class="cp-idea-note"><strong>' + prefix + ':</strong> ' + esc(it.approval_notes) + '</div>';
        }

        var c = it.can || {}, btn = '';
        if (c.approve) btn += '<button type="button" class="btn btn-sm btn-success js-idea-approve" data-id="' + it.id + '"><i class="fas fa-check mr-1"></i>Approve</button>';
        if (c.reject) btn += '<button type="button" class="btn btn-sm btn-outline-danger js-idea-reject" data-id="' + it.id + '"><i class="fas fa-times mr-1"></i>Reject</button>';
        if (c.schedule) btn += '<button type="button" class="btn btn-sm btn-primary js-idea-schedule" data-id="' + it.id + '"><i class="fas fa-calendar-plus mr-1"></i>Jadwalkan</button>';
        if (it.status === 'Scheduled' && it.scheduled_plan) btn += '<button type="button" class="btn btn-sm btn-outline-primary js-idea-open-plan" data-plan="' + it.scheduled_plan.id + '"><i class="fas fa-calendar-alt mr-1"></i>Lihat Jadwal</button>';
        if (c.edit) btn += '<button type="button" class="btn btn-sm btn-light js-idea-edit" data-id="' + it.id + '"><i class="fas fa-edit mr-1"></i>Edit</button>';
        else btn += '<button type="button" class="btn btn-sm btn-light js-idea-edit" data-id="' + it.id + '"><i class="fas fa-history mr-1"></i>Riwayat</button>';
        if (c['delete']) btn += '<button type="button" class="btn btn-sm btn-light text-danger js-idea-delete" data-id="' + it.id + '" title="Hapus"><i class="fas fa-trash"></i></button>';

        return '<div class="wf-card cp-idea">'
            + '<div class="cp-idea-head">'
            + (it.gambar_url ? '<img class="cp-idea-thumb" src="' + esc(it.gambar_url) + '" alt="">' : '')
            + '<div style="min-width:0;flex:1">'
            + '<div class="d-flex justify-content-between align-items-start" style="gap:6px"><div class="wf-card-title">' + esc(it.judul) + '</div>' + W.pill('ide', it.status) + '</div>'
            + '<div class="wf-meta mt-1 d-flex align-items-center flex-wrap" style="gap:6px">'
            + ((it.brand || []).length ? '<span class="cp-logos">' + it.brand.map(brandLogo).join('') + '</span>' : '')
            + '<span>' + (it.assigned_to_name ? esc(it.assigned_to_name) : 'Belum ada PIC') + '</span></div>'
            + '</div></div>'
            + (tags ? '<div class="cp-tags">' + tags + '</div>' : '')
            + (it.catatan ? '<div class="wf-meta" style="white-space:pre-wrap">' + esc(it.catatan) + '</div>' : '')
            + (info ? '<div class="wf-meta"><i class="fas fa-info-circle mr-1"></i>' + info + '</div>' : '')
            + note
            + '<div class="wf-actions">' + btn + '</div>'
            + '</div>';
    }

    var lastIdeasKey = null;
    function loadIdeas(reset, silent) {
        if (reset) ideaPage = 1;
        var params = $.extend({ status: ideaStatus, page: ideaPage }, ideaFilters());
        $.getJSON(ROUTES.ideaList, params).done(function (r) {
            var key = JSON.stringify([params, r]);
            if (silent && key === lastIdeasKey) return;
            lastIdeasKey = key;
            var html = (r.data || []).map(ideaCard).join('');
            if (ideaPage === 1) {
                $('#ideaList').html(html || '<div class="wf-empty" style="grid-column:1/-1">Tidak ada ide pada filter ini.'
                    + (CAN_MANAGE && ideaStatus === 'pending' ? '<div class="mt-2"><button type="button" class="btn btn-sm btn-primary js-add-idea"><i class="fas fa-plus mr-1"></i>Tambah ide</button></div>' : '') + '</div>');
            } else {
                $('#ideaList').append(html);
            }
            $('#ideaMore').toggle(!!r.has_more);
        });
        if (reset) loadIdeaStats();
    }

    $('#ideaStatusChips').on('click', '.wf-filter-chip', function () {
        $('#ideaStatusChips .wf-filter-chip').removeClass('active');
        $(this).addClass('active');
        ideaStatus = $(this).data('status');
        loadIdeas(true);
    });
    $('#ideaBrand, #ideaPic').on('change', function () { loadIdeas(true); });
    $('#ideaSearch').on('input', function () { clearTimeout(ideaSearchTimer); ideaSearchTimer = setTimeout(function () { loadIdeas(true); }, 350); });
    $('#ideaMore').on('click', function () { ideaPage++; loadIdeas(false); });

    $(document).on('click', '.js-idea-approve', function () {
        var id = $(this).data('id');
        W.askNote({ title: 'Approve ide ini?', note: 'optional', placeholder: 'Catatan untuk PIC (mis. angle yang diinginkan)…', confirmText: 'Approve', confirmColor: '#198754' })
            .then(function (res) { if (res) decideIdea(id, 'Approved', res.notes); });
    });
    $(document).on('click', '.js-idea-reject', function () {
        var id = $(this).data('id');
        W.askNote({ title: 'Reject ide ini?', note: 'required', placeholder: 'Alasan reject / apa yang perlu diubah…', confirmText: 'Reject', confirmColor: '#c92a2a' })
            .then(function (res) { if (res) decideIdea(id, 'Rejected', res.notes); });
    });
    function decideIdea(id, status, notes) {
        $.post('/marketing/content-list/' + id + '/approve', { approval_status: status, approval_notes: notes })
            .done(function () { W.toast(status === 'Approved' ? 'Ide di-approve' : 'Ide di-reject'); loadIdeas(true); })
            .fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr), 'error'); });
    }
    $(document).on('click', '.js-idea-schedule', function () {
        $.getJSON('/marketing/content-list/' + $(this).data('id') + '/schedule-payload')
            .done(function (src) { prepareNewPlan({ source: src }); })
            .fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr), 'error'); });
    });
    $(document).on('click', '.js-idea-open-plan', function () { openPlan($(this).data('plan')); });
    $(document).on('click', '.js-idea-delete', function () {
        var id = $(this).data('id');
        W.askNote({ title: 'Hapus ide ini?', text: 'Data yang dihapus tidak dapat dikembalikan.', confirmText: 'Hapus', confirmColor: '#c92a2a' }).then(function (res) {
            if (!res) return;
            $.ajax({ url: '/marketing/content-list/' + id, method: 'DELETE' })
                .done(function () { W.toast('Ide dihapus'); loadIdeas(true); })
                .fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr), 'error'); });
        });
    });

    // ---- idea form
    var $ideaModal = $('#contentListModal'), $ideaForm = $('#contentListForm');
    $('.cl-select2').each(function () {
        if (!$.fn.select2) return;
        $(this).select2(select2Options($(this), $ideaModal));
    });

    function renderIdeaImage(src) {
        var $p = $('#cl_gambar_preview').empty();
        if (!src) return;
        var $wrap = $('<div class="cl-preview"><img alt=""></div>');
        $wrap.find('img').attr('src', src);
        if (CAN_MANAGE && !$('#cl_judul').prop('disabled')) {
            $('<button type="button" class="cp-ref-remove" title="Hapus gambar">&times;</button>').on('click', function () {
                $('#cl_gambar_referensi').val('');
                $('#cl_remove_gambar').val('1');
                renderIdeaImage(null);
            }).appendTo($wrap);
        }
        $p.append($wrap);
    }
    function previewIdeaFile(file) {
        $('#cl_remove_gambar').val('0');
        var reader = new FileReader();
        reader.onload = function (ev) { renderIdeaImage(ev.target.result); };
        reader.readAsDataURL(file);
    }
    function setIdeaImage(file) {
        if (!file || !/^image\//.test(file.type || '')) return;
        try { var dt = new DataTransfer(); dt.items.add(file); $('#cl_gambar_referensi')[0].files = dt.files; } catch (e) { return; }
        setIdeaMore(true); // a pasted screenshot must be visible
        previewIdeaFile(file);
    }

    // Optional idea fields sit behind "Detail tambahan"; opened automatically when they hold data
    function setIdeaMore(open) {
        $('#clMore').toggle(open);
        $('#clMoreToggle').attr('aria-expanded', open).toggle(!open);
    }
    $('#clMoreToggle').on('click', function () { setIdeaMore(true); });
    $('#cl_gambar_drop').on('click keydown', function (e) {
        if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
        e.preventDefault();
        if (!$('#cl_judul').prop('disabled')) $('#cl_gambar_referensi').trigger('click');
    });
    $('#cl_gambar_referensi').on('change', function () { if (this.files && this.files[0]) previewIdeaFile(this.files[0]); });
    $(document).on('paste', function (e) {
        if (!$ideaModal.hasClass('show') || !CAN_MANAGE || $('#cl_judul').prop('disabled')) return;
        var items = (e.originalEvent.clipboardData || {}).items || [];
        for (var i = 0; i < items.length; i++) {
            if (items[i].type && items[i].type.indexOf('image/') === 0) { e.preventDefault(); setIdeaImage(items[i].getAsFile()); return; }
        }
    });

    // Editing: wider modal with the Riwayat panel on the right; new idea: compact form only
    function showIdeaSide(show) {
        $('#clHistoryWrap').toggle(show);
        $('#clMain').toggleClass('col-lg-8', show);
        $('#clDialog').toggleClass('modal-xl', show).toggleClass('modal-lg', !show);
    }

    function openIdea(id) {
        $ideaForm[0].reset();
        $ideaForm.attr('data-action', id ? 'update' : 'store').attr('data-id', id || '');
        $ideaModal.find('.cl-select2').val(null).trigger('change.select2');
        $ideaModal.find('.js-chip-picker').val([]).trigger('change.select2');
        $('#cl_remove_gambar').val('0');
        $('#clResubmitNote').hide();
        showIdeaSide(false);
        $ideaForm.find('input, textarea, select').not('[type=hidden]').prop('disabled', !CAN_MANAGE);
        $ideaModal.find('.js-chip-picker').val([]).trigger('change.select2');
        $('#cl_gambar_drop').toggle(CAN_MANAGE);
        renderIdeaImage(null);
        setIdeaMore(false);
        $('#btnSaveContentList').toggle(CAN_MANAGE).html('<i class="fas fa-paper-plane mr-1"></i> ' + (id ? 'Simpan' : 'Simpan & Ajukan'));

        if (!id) {
            $('#contentListModalLabel').text('Ide Konten Baru');
            $ideaModal.modal('show');
            return;
        }
        $.getJSON('/marketing/content-list/' + id).done(function (r) {
            $('#contentListModalLabel').text('Ide Konten #' + r.id);
            $('#cl_judul').val(r.judul || '');
            setBrands($('#cl_brand'), r.brand);
            $('#cl_platform').val(r.platform || []).trigger('change.select2');
            $('#cl_jenis_konten').val(r.jenis_konten || []).trigger('change.select2');
            $('#cl_konten_pilar').val(r.konten_pilar || '').trigger('change.select2');
            $('#cl_assigned_to').val(r.assigned_to || '').trigger('change.select2');
            $('#cl_link_referensi').val(r.link_referensi || '');
            $('#cl_catatan').val(r.catatan || '');
            if (r.display_status === 'Scheduled') {
                // scheduled ideas are read-only here; changes happen in the Content Plan
                $ideaForm.find('input, textarea, select').not('[type=hidden]').prop('disabled', true);
                $ideaModal.find('.js-chip-picker').trigger('change.select2');
                $('#cl_gambar_drop').hide();
                $('#btnSaveContentList').hide();
                $('#clResubmitNote').html('Ide ini sudah dijadwalkan. Ubah detailnya lewat jadwal di Content Plan.').show();
            } else if (r.display_status === 'Approved' || r.display_status === 'Rejected') {
                $('#clResubmitNote').html('Status saat ini <strong>' + esc(r.display_status) + '</strong>. Menyimpan perubahan akan mengembalikan ide ke <strong>Menunggu Approval</strong>.').show();
                $('#btnSaveContentList').html('<i class="fas fa-paper-plane mr-1"></i> Simpan & Ajukan Ulang');
            }
            renderIdeaImage(r.gambar_url);
            setIdeaMore(!!((r.brand || []).length || r.konten_pilar || r.assigned_to || r.link_referensi || r.catatan || r.gambar_url));
            $('#clTimeline').html(W.timeline(r.timeline, IDEA_ACTIONS));
            showIdeaSide(true);
            $ideaModal.modal('show');
        }).fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr), 'error'); });
    }
    $(document).on('click', '.js-idea-edit', function () { openIdea($(this).data('id')); });
    $(document).on('click', '.js-add-idea', function () { openIdea(null); });

    // The header button follows the open tab, so "idea or schedule?" is answered by where the user is
    // "Konten Baru" is always visible; the chooser explains idea (needs approval) vs schedule directly
    $('#btnNewContent').on('click', function () { $('#newContentModal').modal('show'); });
    $('#newContentModal').on('click', '.cp-choice', function () {
        var choice = $(this).data('choice');
        $('#newContentModal').one('hidden.bs.modal', function () {
            if (choice === 'idea') {
                $('#idea-tab').tab('show');
                openIdea(null);
            } else if (choice === 'routine') {
                newRoutine();
            } else {
                prepareNewPlan({ date: moment().format('YYYY-MM-DD') });
            }
        }).modal('hide');
    });

    // Approved ideas waiting to be scheduled: chip on the Jadwal toolbar
    $('#cpReadyIdeas').on('click', function () {
        $('#idea-tab').tab('show');
        $('#ideaStatusChips .wf-filter-chip[data-status="approved"]').trigger('click');
    });

    $ideaForm.on('submit', function (e) {
        e.preventDefault();
        var missing = [];
        if (!$.trim($('#cl_judul').val())) missing.push('Judul');
        if (!($('#cl_platform').val() || []).length) missing.push('Platform');
        if (!($('#cl_jenis_konten').val() || []).length) missing.push('Jenis konten');
        if (missing.length) { Swal.fire('Lengkapi dulu', missing.join(', ') + ' wajib diisi.', 'warning'); return; }

        var action = $ideaForm.attr('data-action'), id = $ideaForm.attr('data-id');
        var fd = new FormData(this);
        if (action === 'update') fd.append('_method', 'PUT');
        var $btn = $('#btnSaveContentList'), original = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Menyimpan…');
        $.ajax({ url: action === 'update' ? '/marketing/content-list/' + id : ROUTES.ideaStore, method: 'POST', data: fd, processData: false, contentType: false })
            .done(function (res) {
                $ideaModal.modal('hide');
                W.toast(action === 'store' ? 'Ide diajukan, menunggu approval' : (res && res.resubmitted ? 'Disimpan & diajukan ulang' : 'Perubahan disimpan'));
                loadIdeas(true);
            })
            .fail(function (xhr) { Swal.fire('Gagal menyimpan', W.errorMessage(xhr), 'error'); })
            .always(function () { $btn.prop('disabled', false).html(original); });
    });

    // ---- reference link preview (Instagram / TikTok / YouTube) under both link fields
    var previewTimer = null;
    function refreshLinkPreviews() {
        W.linkPreview($('#cl_link_preview'), $('#cl_link_referensi').val());
        W.linkPreview($('#cp_link_preview'), $('#link_referensi').val());
    }
    $('#cl_link_referensi, #link_referensi').on('input change', function () {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(refreshLinkPreviews, 500);
    });
    $ideaModal.on('shown.bs.modal', refreshLinkPreviews);
    $planModal.on('shown.bs.modal', refreshLinkPreviews);
    // stop videos and free the iframes when a modal closes
    $ideaModal.add($planModal).on('hidden.bs.modal', function () {
        W.linkPreview($('#cl_link_preview'), '');
        W.linkPreview($('#cp_link_preview'), '');
    });

    // ---- "Pasang di HP": add Content Plan to the phone's home screen (opens like an app, no browser bar)
    var installPrompt = null;
    var isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    var isPhone = window.matchMedia('(max-width: 767.98px)').matches;
    $('#btnInstallApp').toggle(isPhone && !isStandalone);
    window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); installPrompt = e; });
    $('#btnInstallApp').on('click', function () {
        if (installPrompt) {
            installPrompt.prompt();
            installPrompt.userChoice.then(function () { installPrompt = null; });
            return;
        }
        var ios = /iphone|ipad|ipod/i.test(navigator.userAgent);
        Swal.fire({
            title: 'Pasang di layar utama',
            html: ios
                ? '<div class="text-left">1. Buka halaman ini di <b>Safari</b>.<br>2. Ketuk tombol <b>Bagikan</b> <i class="fas fa-share-square"></i>.<br>3. Pilih <b>Tambah ke Layar Utama</b>.</div>'
                : '<div class="text-left">1. Ketuk menu <b>⋮</b> di pojok kanan atas Chrome.<br>2. Pilih <b>Tambahkan ke layar utama</b> / <b>Instal aplikasi</b>.<br>3. Ketuk <b>Tambahkan</b>.</div>',
            confirmButtonText: 'Mengerti'
        });
    });

    // =================================================================== KONTEN RUTIN
    // Made once, uploaded on fixed days: on the board each day only needs "Sudah upload" (or "Lewati")
    var ROUTINE_URL = @json(url('/marketing/content-routine'));
    var ME = @json(auth()->id());
    var routineByKey = {};
    var currentRoutine = null;
    var RT_STATE = {
        posted: { cls: 'green', label: 'Published' },
        skipped: { cls: 'gray', label: 'Dilewati' },
        late: { cls: 'red', label: 'Terlewat' },
        due: { cls: 'blue', label: 'Hari ini' },
        upcoming: { cls: 'gray', label: 'Terjadwal' }
    };
    function routinePill(state) {
        var s = RT_STATE[state] || RT_STATE.upcoming;
        return '<span class="wf-pill st-' + s.cls + '">' + s.label + '</span>';
    }

    // buttons of an open day (today or missed); recorded days show who did it
    function routineActions(o) {
        // uploaded: shown in the status pill ("Published · 09.05"), like regular content
        if (o.state === 'posted') return '';
        if (o.state === 'skipped') {
            return '<div class="cp-rt-done is-skip"><i class="fas fa-forward"></i>Dilewati' + (o.notes ? ': ' + esc(o.notes) : '') + '</div>';
        }
        // future days too: posts are often scheduled ahead in the platform's own scheduler
        if (!CAN_MANAGE) return '';
        return '<div class="cp-item-actions">'
            + '<button type="button" class="cp-act is-success js-stop js-rt-post" data-key="' + esc(o.key) + '"><i class="fas fa-check"></i> Sudah upload</button>'
            + '<button type="button" class="cp-act is-ghost js-stop js-rt-skip" data-key="' + esc(o.key) + '" title="Tidak diupload hari ini (mis. klinik tutup)">Lewati</button>'
            + '</div>';
    }

    function routineHtml(o) {
        var color = brandColor(o);
        var logos = (o.brand || []).map(brandLogo).join('');
        var icons = (o.platforms || []).map(function (p) {
            return p.url
                ? '<a class="cp-plat js-stop" href="' + esc(p.url) + '" target="_blank" rel="noopener" title="Lihat postingan ' + esc(p.name) + '">' + platformGlyph(p.name) + '</a>'
                : '<span class="cp-plat" title="' + esc(p.name) + '">' + platformGlyph(p.name) + '</span>';
        }).join('');
        var pic = o.assigned_to_name
            ? '<span class="cp-pic" title="PIC upload"><span>' + esc(o.assigned_to_name) + '</span></span>'
            : '<span class="cp-pic is-empty"><span>Semua tim</span></span>';
        var style = color ? ' style="--brand:' + color + ';--brand-bg:' + rgba(color, IS_DARK() ? .22 : .12) + '"' : '';
        return '<div class="cp-item cp-routine is-' + o.state + '" tabindex="0" role="button" data-routine="' + o.routine_id + '" data-key="' + esc(o.key) + '"' + style + '>'
            + '<div class="cp-item-top">'
            + (logos ? '<span class="cp-logos">' + logos + '</span>' : '')
            + '<span class="cp-item-time" title="Konten rutin · ' + esc(o.days_label) + '"><i class="fas fa-redo-alt"></i>' + esc(o.time.replace(':', '.')) + '</span>'
            + (o.state === 'posted' ? publishedPill(o.done_at, o.done_by, o.late_by, o.date) : routinePill(o.state)) + '</div>'
            + '<div class="cp-item-title" title="' + esc(o.judul) + '">' + esc(o.judul) + '</div>'
            + '<div class="cp-item-foot">' + pic + (icons ? '<span class="cp-icons">' + icons + '</span>' : '') + '</div>'
            + routineActions(o)
            + '</div>';
    }

    function afterRoutineChange(message) {
        W.toast(message);
        loadBoard({ silent: true });
        if (currentRoutine && $('#routineModal').hasClass('show')) openRoutine(currentRoutine.id);
    }

    function postRoutine(o) {
        var platforms = (o.platforms || []).map(function (p) { return p.name; });
        W.askNote({
            title: 'Sudah upload?',
            html: '<div class="text-left"><div class="small text-muted mb-2">“' + esc(o.judul) + '” · ' + esc(W.date(o.date)) + '</div>'
                + (o.state === 'upcoming' ? '<div class="small mb-2"><i class="far fa-clock mr-1"></i>Tanggal ini belum tiba: tandai bila postingannya sudah dijadwalkan di aplikasi sosmed.</div>' : '')
                + tayangInput(o.date + ' ' + o.time, false)
                + platforms.map(function (p) {
                    return '<label class="d-block mt-2 mb-1" style="text-transform:none">Link ' + esc(p) + ' <span class="text-muted small">(opsional)</span></label>'
                        + '<input class="swal2-input m-0 w-100 js-rt-link" data-platform="' + esc(p) + '" placeholder="https://…">';
                }).join('') + '</div>',
            confirmText: 'Sudah upload',
            confirmColor: '#198754',
            preConfirm: function () {
                var out = {};
                $('.js-rt-link').each(function () { var v = $.trim($(this).val()); if (v) out[$(this).data('platform')] = v; });
                var jam = readTayang(false);
                if (!jam) return false;
                return { links: out, jam: jam };
            }
        }).then(function (res) {
            if (!res) return;
            $.post(ROUTINE_URL + '/' + o.routine_id + '/post', { tanggal: o.date, link_publikasi: res.value.links, jam_tayang: res.value.jam })
                .done(function () { afterRoutineChange('Tercatat sudah upload'); })
                .fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr), 'error'); });
        });
    }

    function skipRoutine(o) {
        W.askNote({
            title: 'Lewati ' + W.date(o.date) + '?', text: '“' + o.judul + '” tidak diupload hari itu.',
            note: 'required', placeholder: 'Alasan, mis. klinik tutup / libur nasional', confirmText: 'Lewati', confirmColor: '#6c757d'
        }).then(function (res) {
            if (!res) return;
            $.post(ROUTINE_URL + '/' + o.routine_id + '/skip', { tanggal: o.date, notes: res.notes })
                .done(function () { afterRoutineChange('Hari ini dilewati'); })
                .fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr), 'error'); });
        });
    }

    function undoRoutine(o) {
        W.askNote({ title: 'Batalkan catatan ' + W.date(o.date) + '?', text: 'Hari ini kembali menjadi belum diupload.', confirmText: 'Batalkan catatan', confirmColor: '#c92a2a' })
            .then(function (res) {
                if (!res) return;
                $.post(ROUTINE_URL + '/' + o.routine_id + '/undo', { tanggal: o.date })
                    .done(function () { afterRoutineChange('Catatan dibatalkan'); })
                    .fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr), 'error'); });
            });
    }

    // occurrences come from the board or from the open routine's history
    function findOccurrence(key) {
        if (routineByKey[key]) return routineByKey[key];
        return ((currentRoutine && currentRoutine.history) || []).filter(function (o) { return o.key === key; })[0];
    }
    $(document).on('click', '.js-rt-post', function (e) { e.stopPropagation(); var o = findOccurrence($(this).data('key')); if (o) postRoutine(o); });
    $(document).on('click', '.js-rt-skip', function (e) { e.stopPropagation(); var o = findOccurrence($(this).data('key')); if (o) skipRoutine(o); });
    $(document).on('click', '.js-rt-undo', function (e) { e.stopPropagation(); var o = findOccurrence($(this).data('key')); if (o) undoRoutine(o); });

    // ---- modal: form on the left, last 30 days on the right
    var $rtModal = $('#routineModal'), $rtForm = $('#routineForm');

    function setRoutineLayout(saved) {
        $('#rtSide').toggle(saved);
        $('#rtMain').toggleClass('col-lg-7', saved);
        $rtModal.find('.modal-dialog').toggleClass('modal-xl', saved).toggleClass('modal-lg', !saved);
        $('#rtDelete').toggle(saved && CAN_MANAGE);
        $('#rtActiveWrap').toggle(saved);
    }

    function fillRoutineForm(r) {
        $rtForm[0].reset();
        $('#rt_judul').val(r.judul || '');
        setBrands($('#rt_brand'), r.brand || []);
        $('#rt_platform').val(r.platform || []).trigger('change.select2');
        $('#rt_jenis_konten').val(r.jenis_konten || []).trigger('change.select2');
        $('#rt_days').val((r.days || []).map(String)).trigger('change.select2');
        $('#rt_jam').val(r.jam || '09:00');
        $('#rt_start').val(r.start_date || moment().format('YYYY-MM-DD'));
        $('#rt_end').val(r.end_date || '');
        $('#rt_assigned').val(r.assigned_to ? String(r.assigned_to) : '');
        $('#rt_asset').val(r.asset_link || '');
        $('#rt_caption').val(r.caption || '');
        $('#rt_catatan').val(r.catatan || '');
        $('#rt_active').prop('checked', r.is_active !== false);
        $rtForm.find('input, textarea, select').prop('disabled', !CAN_MANAGE);
        $rtForm.find('.js-chip-picker').trigger('change.select2');
        $('.js-rt-days').toggle(CAN_MANAGE);
    }

    function newRoutine() {
        currentRoutine = null;
        $('#routineTitle').text('Konten rutin baru');
        // the creator usually uploads it: PIC defaults to the current user
        var self = $('#rt_assigned option[value="' + ME + '"]').length ? ME : null;
        fillRoutineForm({ days: [1, 2, 3, 4, 5, 6, 7], jam: '09:00', start_date: moment().format('YYYY-MM-DD'), assigned_to: self });
        setRoutineLayout(false);
        $rtModal.modal('show');
    }

    function historyRow(o) {
        var links = (o.platforms || []).filter(function (p) { return p.url; }).map(function (p) {
            return '<a class="cp-plat" href="' + esc(p.url) + '" target="_blank" rel="noopener" title="Lihat postingan ' + esc(p.name) + '">' + platformGlyph(p.name) + '</a>';
        }).join(' ');
        // posted: when it went live, and (if different) when / by whom it was checked off
        var recorded = o.recorded_at && o.done_at && Math.abs(moment(o.recorded_at).diff(moment(o.done_at), 'minutes')) > 5
            ? ' · dicatat ' + W.date(o.recorded_at, { weekday: false, time: true }) : '';
        var who = o.state === 'posted'
            ? 'Tayang ' + (o.done_at ? moment(o.done_at).format('HH.mm') : '-') + (o.late_by ? ' <span class="cp-tag cp-tag-rev">telat ' + esc(o.late_by) + '</span>' : '')
                + recorded + ' oleh ' + esc(o.done_by || '')
            : (o.state === 'skipped' ? esc(o.notes || '') : (o.state === 'late' ? 'Belum diupload' : 'Jam ' + esc(o.time.replace(':', '.'))));
        var actions = '';
        if (CAN_MANAGE) {
            if (o.state === 'posted' || o.state === 'skipped') {
                actions = '<button type="button" class="btn btn-link text-muted js-rt-undo" data-key="' + esc(o.key) + '">Batalkan</button>';
            } else {
                actions = '<button type="button" class="btn btn-success js-rt-post" data-key="' + esc(o.key) + '">Sudah upload</button>'
                    + '<button type="button" class="btn btn-light js-rt-skip" data-key="' + esc(o.key) + '">Lewati</button>';
            }
        }
        return '<div class="cp-rt-row"><div class="cp-rt-row-date">' + esc(W.date(o.date)) + '</div>'
            + '<div class="cp-rt-row-body">' + routinePill(o.state) + '<div class="wf-meta mt-1">' + who + (links ? ' ' + links : '') + '</div></div>'
            + (actions ? '<div class="cp-rt-row-actions">' + actions + '</div>' : '') + '</div>';
    }

    function openRoutine(id) {
        $.getJSON(ROUTINE_URL + '/' + id).done(function (r) {
            currentRoutine = r;
            $('#routineTitle').html('<i class="fas fa-redo-alt mr-2 text-muted"></i>' + esc(r.judul)
                + ' <span class="wf-muted small font-weight-normal">· ' + esc(r.days_label) + ' ' + esc(r.jam.replace(':', '.')) + '</span>'
                + (!r.is_active ? ' <span class="wf-pill st-gray ml-1">Nonaktif</span>' : ''));
            fillRoutineForm(r);
            $('#rtScore').text('Bulan ini ' + r.month_posted + '/' + r.month_total + ' · ' + r.month_on_time + ' tepat waktu')
                .attr('title', 'Diupload / hari terjadwal (tanpa yang dilewati); tepat waktu = tayang paling lambat 30 menit dari jadwal')
                .toggle(r.month_total > 0);
            $('#rtHistory').html((r.history || []).length ? r.history.map(historyRow).join('') : '<div class="wf-empty py-3">Belum ada hari upload.</div>');
            $('#rtTimeline').html(W.timeline(r.timeline));
            setRoutineLayout(true);
            if (!$rtModal.hasClass('show')) $rtModal.modal('show');
        }).fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr, 'Konten rutin tidak ditemukan.'), 'error'); });
    }

    $('.js-rt-days').on('click', function () {
        $('#rt_days').val(String($(this).data('days')).split(',')).trigger('change');
    });

    $rtForm.on('submit', function (e) {
        e.preventDefault();
        var missing = [];
        if (!$.trim($('#rt_judul').val())) missing.push('Judul');
        if (!($('#rt_platform').val() || []).length) missing.push('Platform');
        if (!($('#rt_days').val() || []).length) missing.push('Hari');
        if (!$('#rt_jam').val()) missing.push('Jam upload');
        if (!$('#rt_start').val()) missing.push('Tanggal mulai');
        if (missing.length) { Swal.fire('Lengkapi dulu', missing.join(', ') + ' wajib diisi.', 'warning'); return; }

        var data = $rtForm.serializeArray();
        if (currentRoutine) {
            data.push({ name: '_method', value: 'PUT' }, { name: 'is_active', value: $('#rt_active').is(':checked') ? 1 : 0 });
        }
        var $btn = $('#rtSave').prop('disabled', true);
        $.post(currentRoutine ? ROUTINE_URL + '/' + currentRoutine.id : ROUTINE_URL, $.param(data))
            .done(function (r) {
                W.toast(currentRoutine ? 'Konten rutin disimpan' : 'Konten rutin dibuat, muncul di jadwal');
                var id = currentRoutine ? currentRoutine.id : r.id;
                loadBoard({ silent: true });
                openRoutine(id);
            })
            .fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr), 'error'); })
            .always(function () { $btn.prop('disabled', false); });
    });

    $('#rtDelete').on('click', function () {
        if (!currentRoutine) return;
        W.askNote({ title: 'Hapus konten rutin?', text: '“' + currentRoutine.judul + '” dan semua catatan uploadnya ikut terhapus. Untuk berhenti sementara, matikan saja "Aktif".', confirmText: 'Hapus', confirmColor: '#c92a2a' })
            .then(function (res) {
                if (!res) return;
                $.ajax({ url: ROUTINE_URL + '/' + currentRoutine.id, method: 'DELETE' })
                    .done(function () { $rtModal.modal('hide'); W.toast('Konten rutin dihapus'); loadBoard({ silent: true }); })
                    .fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr), 'error'); });
            });
    });

    // ---- list of all routines (toolbar chip)
    function loadRoutineList() {
        $.getJSON(ROUTINE_URL).done(function (r) {
            var rows = r.data || [];
            $('#routineList').html(rows.length ? rows.map(function (x) {
                var icons = (x.platform || []).map(function (p) { return '<span class="cp-plat">' + platformGlyph(p) + '</span>'; }).join('');
                var status = !x.is_active ? '<span class="wf-pill st-gray">Nonaktif</span>' : (x.ended ? '<span class="wf-pill st-gray">Selesai</span>' : '');
                return '<div class="wf-card is-clickable cp-rt-list-item js-open-routine-list" tabindex="0" data-routine="' + x.id + '">'
                    + '<div class="d-flex align-items-center flex-wrap" style="gap:8px">'
                    + '<span class="cp-logos">' + (x.brand || []).map(brandLogo).join('') + '</span>'
                    + '<strong>' + esc(x.judul) + '</strong>' + status
                    + '<span class="ml-auto cp-icons">' + icons + '</span></div>'
                    + '<div class="wf-meta mt-1"><i class="fas fa-redo-alt mr-1"></i>' + esc(x.days_label) + ' · ' + esc(x.jam.replace(':', '.'))
                    + ' · ' + (x.assigned_to_name ? esc(x.assigned_to_name) : 'Semua tim')
                    + (x.month_total ? ' · bulan ini ' + x.month_posted + '/' + x.month_total + ' diupload' : '') + '</div></div>';
            }).join('') : '<div class="wf-empty">Belum ada konten rutin.' + (CAN_MANAGE ? ' Klik <strong>+ Konten rutin</strong> untuk membuat.' : '') + '</div>');
        });
    }
    $('#btnRoutines').on('click', function () { loadRoutineList(); $('#routineListModal').modal('show'); });
    $(document).on('click keydown', '.js-open-routine-list', function (e) {
        if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
        var id = $(this).data('routine');
        $('#routineListModal').one('hidden.bs.modal', function () { openRoutine(id); }).modal('hide');
    });
    $('#routineListModal').on('click', '.js-new-routine', function () {
        $('#routineListModal').one('hidden.bs.modal', newRoutine).modal('hide');
    });

    // =================================================================== INSTAGRAM INSIGHT
    // "Update insight" (best every morning) stores today's numbers of each published Instagram post;
    // the card shows the latest numbers, the Report tab the numbers per day
    var INSIGHT_URL = @json(url('/marketing/content-insight'));

    // 12400 -> "12,4rb", 1250000 -> "1,3jt"
    function fmtNum(n) {
        if (n === null || n === undefined) return '–';
        if (n >= 1000000) return (n / 1000000).toFixed(1).replace('.0', '').replace('.', ',') + 'jt';
        if (n >= 10000) return (n / 1000).toFixed(1).replace('.0', '').replace('.', ',') + 'rb';
        return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function insightLine(row) {
        var s = row.status === 'Published' ? row.insight : null;
        if (!s) return '';
        var old = !moment(s.tanggal).isSame(moment(), 'day');
        // sits under the "Published" pill: one compact line, the views gained today in green
        return '<span class="cp-insight" title="Insight Instagram per ' + esc(W.date(s.tanggal)) + (old ? ' (belum diupdate hari ini)' : '')
            + (s.views_delta ? ' · +' + fmtNum(s.views_delta) + ' views sejak update sebelumnya' : '') + '">'
            + (s.views !== null ? '<span><i class="far fa-eye"></i>' + fmtNum(s.views) + (s.views_delta ? ' <b class="up">+' + fmtNum(s.views_delta) + '</b>' : '') + '</span>' : '')
            + (s.likes !== null ? '<span><i class="far fa-heart"></i>' + fmtNum(s.likes) + '</span>' : '')
            + (s.comments !== null ? '<span><i class="far fa-comment"></i>' + fmtNum(s.comments) + '</span>' : '')
            + '</span>';
    }

    function showInsightLast(iso) {
        $('#cpInsightLast').text(iso ? '· ' + (moment(iso).isSame(moment(), 'day') ? moment(iso).format('HH.mm') : W.date(iso, { weekday: false })) : '');
    }

    function syncSummary(r) {
        var parts = ['<div class="text-left small">'];
        parts.push('<div class="mb-2"><strong>' + r.updated + '</strong> konten diperbarui.</div>');
        if ((r.not_found || []).length) parts.push('<div class="mb-2"><strong>Tidak ditemukan di akun terhubung</strong> (cek link atau akunnya):<br>' + r.not_found.map(esc).join('<br>') + '</div>');
        if ((r.skipped || []).length) parts.push('<div class="mb-2"><strong>Dilewati:</strong><br>' + r.skipped.map(esc).join('<br>') + '</div>');
        if ((r.missing_metrics || []).length) parts.push('<div class="mb-2 text-muted">Metrik tidak tersedia dari Instagram: ' + r.missing_metrics.map(esc).join(', ') + '.</div>');
        if ((r.account_notes || []).length) parts.push('<div class="mb-2 text-muted">Data akun yang tidak tersedia (mis. akun &lt; 100 followers):<br>' + r.account_notes.map(esc).join('<br>') + '</div>');
        if ((r.errors || []).length) parts.push('<div class="text-danger">' + r.errors.map(esc).join('<br>') + '</div>');
        parts.push('</div>');
        return parts.join('');
    }

    function runInsightSync(planId, onDone) {
        var $btn = planId ? $('#cpInsightOne') : $('#btnInsightSync');
        var html = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Mengambil…');
        $.post(INSIGHT_URL + '/sync', planId ? { plan_id: planId } : {})
            .done(function (r) {
                var clean = !(r.not_found || []).length && !(r.errors || []).length && !(r.skipped || []).length && !(r.missing_metrics || []).length;
                if (clean) W.toast(r.updated + ' konten diperbarui');
                else Swal.fire({ title: 'Insight diperbarui', html: syncSummary(r), icon: r.errors.length ? 'warning' : 'info' });
                showInsightLast(moment().toISOString());
                loadBoard({ silent: true });
                if (onDone) onDone();
            })
            .fail(function (xhr) {
                if (xhr.responseJSON && xhr.responseJSON.no_account) { openIgAccounts(); return; }
                Swal.fire('Gagal', W.errorMessage(xhr), 'error');
            })
            .always(function () { $btn.prop('disabled', false).html(html); });
    }
    $('#btnInsightSync').on('click', function () { runInsightSync(null); });
    $('#cpInsightOne').on('click', function () {
        var id = currentPlan && currentPlan.id;
        if (id) runInsightSync(id, function () { loadInsight(id, 'Published'); });
    });

    // ---- Report tab: numbers per day (line of views + table)
    function insightChart(rows) {
        var pts = rows.filter(function (r) { return r.views !== null; });
        if (pts.length < 2) return '<div class="wf-muted small">Grafik muncul setelah ada data minimal 2 hari.</div>';
        // drawn at the box width (the Report tab may still be hidden: then 600 and scaled evenly)
        var w = Math.max(300, Math.floor($('#cpInsightChart').width() || 600)), h = 150, pad = 26, max = Math.max.apply(null, pts.map(function (r) { return r.views; })) || 1;
        var x = function (i) { return pad + i * (w - pad * 2) / (pts.length - 1); };
        var y = function (v) { return h - pad - v / max * (h - pad * 2); };
        var line = pts.map(function (r, i) { return (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(r.views).toFixed(1); }).join(' ');
        var area = line + ' L' + x(pts.length - 1).toFixed(1) + ' ' + (h - pad) + ' L' + x(0).toFixed(1) + ' ' + (h - pad) + ' Z';
        var dots = pts.map(function (r, i) { return '<circle class="dot" cx="' + x(i).toFixed(1) + '" cy="' + y(r.views).toFixed(1) + '" r="3"><title>' + esc(W.date(r.tanggal)) + ': ' + fmtNum(r.views) + ' views</title></circle>'; }).join('');
        var labels = '<text x="' + pad + '" y="' + (h - 6) + '">' + esc(W.date(pts[0].tanggal, { weekday: false })) + '</text>'
            + '<text x="' + (w - pad) + '" y="' + (h - 6) + '" text-anchor="end">' + esc(W.date(pts[pts.length - 1].tanggal, { weekday: false })) + '</text>'
            + '<text x="' + pad + '" y="12">' + fmtNum(max) + ' views</text>';
        return '<svg class="cp-insight-svg" width="' + w + '" height="' + h + '" viewBox="0 0 ' + w + ' ' + h + '" role="img" aria-label="Views per hari">'
            + '<path class="area" d="' + area + '"/><path class="line" d="' + line + '"/>' + dots + labels + '</svg>';
    }

    /**
     * Instagram numbers come in by themselves ("Update insight"); the manual report is only for the other
     * platforms of the content (TikTok, YouTube, …), so an Instagram-only content shows no manual form.
     * Returns whether the manual form is shown.
     */
    function applyReportMode() {
        var pf = (currentPlan && currentPlan.platform) || [];
        var ig = pf.indexOf('Instagram') !== -1;
        var others = pf.filter(function (p) { return p !== 'Instagram'; });
        var manual = !currentPlan || !ig || others.length > 0;
        $('#cpManualReport').toggle(manual);
        $('#cpManualTitle').text(ig && others.length ? 'Report manual: ' + others.join(', ') : 'Report');
        $('#cpManualHint').text(ig
            ? 'Angka Instagram terisi otomatis lewat Update insight. Isi angka ' + others.join(', ') + ' dari insight platformnya.'
            : 'Isi angka dari insight platform (mis. H+1 dan H+7).');
        return manual;
    }

    function loadInsight(planId, status) {
        var $box = $('#cpInsightBox');
        var hasIg = currentPlan && (currentPlan.platform || []).indexOf('Instagram') !== -1;
        if (!CAN_MANAGE || !planId || status !== 'Published' || !hasIg) { $box.hide(); return; }
        $box.show();
        $('#cpInsightChart').empty();
        $('#cpInsightRows').html('<tr><td colspan="7" class="wf-muted small">Memuat…</td></tr>');
        $.getJSON(INSIGHT_URL + '/plan/' + planId).done(function (r) {
            if (!currentPlan || String(currentPlan.id) !== String(planId)) return;
            var rows = r.data || [];
            $('#cpInsightChart').html(insightChart(rows));
            var cell = function (row, m) {
                return '<td>' + fmtNum(row[m]) + (row[m + '_delta'] > 0 ? '<span class="up">+' + fmtNum(row[m + '_delta']) + '</span>' : '') + '</td>';
            };
            $('#cpInsightRows').html(rows.length ? rows.slice().reverse().map(function (row) {
                return '<tr><td>' + esc(W.date(row.tanggal)) + '</td>' + ['views', 'reach', 'likes', 'comments', 'saves', 'shares'].map(function (m) { return cell(row, m); }).join('') + '</tr>';
            }).join('') : '<tr><td colspan="7" class="wf-muted small">Belum ada data. Klik <strong>Update konten ini</strong> (link Instagram harus terisi).</td></tr>');
        });
    }

    // ---- connected accounts
    function loadIgAccounts() {
        return $.getJSON(INSIGHT_URL + '/accounts').done(function (r) {
            showInsightLast(r.last_synced_at);
            $('#igAccountList').html((r.data || []).length ? r.data.map(function (a) {
                var brand = a.brand && BRAND_META[a.brand] ? BRAND_META[a.brand].label : (a.brand || '');
                var expires = a.token_expires_at ? moment(a.token_expires_at) : null;
                var soon = expires && expires.diff(moment(), 'days') < 10;
                // the clinic decides where the account shows on "Analisa Konten"
                var clinicSelect = '<select class="form-control form-control-sm js-ig-brand" data-id="' + a.id + '" style="width:auto;display:inline-block">'
                    + '<option value="">— pilih klinik —</option>' + BRAND_LIST.map(function (b) {
                        return '<option value="' + esc(b.value) + '"' + (b.value === a.brand ? ' selected' : '') + '>' + esc(b.label) + '</option>';
                    }).join('') + '</select>';
                return '<div class="wf-card cp-ig-account"><i class="fab fa-instagram fa-lg" style="color:#E4405F"></i>'
                    + '<div class="meta"><strong>@' + esc(a.username || '?') + '</strong> ' + clinicSelect
                    + (!brand ? ' <span class="text-danger small">Pilih kliniknya</span>' : '')
                    + '<div class="wf-muted">' + (a.last_synced_at ? 'Update terakhir ' + esc(W.date(a.last_synced_at, { time: true })) : 'Belum pernah update')
                    + (expires ? ' · token s/d <span class="' + (soon ? 'text-danger font-weight-bold' : '') + '">' + esc(W.date(a.token_expires_at, { weekday: false })) + '</span>' : '') + '</div>'
                    + (a.last_error ? '<div class="text-danger">' + esc(a.last_error) + '</div>' : '') + '</div>'
                    + '<button type="button" class="btn btn-sm btn-light text-danger js-ig-remove" data-id="' + a.id + '" data-name="' + esc(a.username || '') + '">Lepas</button></div>';
            }).join('') : '<div class="wf-empty py-3">Belum ada akun terhubung.</div>');
        });
    }
    function openIgAccounts() { loadIgAccounts(); $('#igAccountsModal').modal('show'); }
    $('#btnIgAccounts').on('click', openIgAccounts);

    $('#igConnectForm').on('submit', function (e) {
        e.preventDefault();
        var token = $.trim($('#ig_token').val());
        if (!token) return;
        var $btn = $('#igConnectBtn').prop('disabled', true);
        $.post(INSIGHT_URL + '/accounts', { token: token, brand: $('#ig_brand').val() })
            .done(function (r) { $('#ig_token').val(''); W.toast('@' + r.username + ' terhubung'); loadIgAccounts(); })
            .fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr), 'error'); })
            .always(function () { $btn.prop('disabled', false); });
    });
    $(document).on('change', '.js-ig-brand', function () {
        $.post(INSIGHT_URL + '/accounts/' + $(this).data('id'), { brand: $(this).val() })
            .done(function () { W.toast('Klinik akun disimpan'); loadIgAccounts(); })
            .fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr), 'error'); });
    });
    $(document).on('click', '.js-ig-remove', function () {
        var id = $(this).data('id'), name = $(this).data('name');
        W.askNote({ title: 'Lepas @' + name + '?', text: 'Insight yang sudah tersimpan tetap ada. Update berikutnya tidak membaca akun ini lagi.', confirmText: 'Lepas', confirmColor: '#c92a2a' })
            .then(function (res) {
                if (!res) return;
                $.ajax({ url: INSIGHT_URL + '/accounts/' + id, method: 'DELETE' })
                    .done(function () { W.toast('Akun dilepas'); loadIgAccounts(); })
                    .fail(function (xhr) { Swal.fire('Gagal', W.errorMessage(xhr), 'error'); });
            });
    });

    // =================================================================== START
    renderBrandFilter();
    if (CAN_MANAGE) loadIgAccounts();
    try { onlyMine = localStorage.getItem('cp_only_mine') === '1'; } catch (e) {}
    $('#btnMine').toggleClass('active', onlyMine).attr('aria-pressed', onlyMine);
    updateWeekLabel();
    updateModeUi();
    loadBoard({ scrollToday: true });
    loadIdeas(true);

    var params = new URLSearchParams(window.location.search);
    if (params.get('tab') === 'ide') $('#idea-tab').tab('show');
    if (params.get('open')) openPlan(params.get('open'));
    if (params.get('routine')) openRoutine(params.get('routine'));
    if (params.get('ig') && CAN_MANAGE) openIgAccounts(); // "Hubungkan akun" from Analisa Konten

    // Live board: refresh every 10 seconds (and right after a notification); skipped while a dialog
    // is open or a card is being dragged, redraws only what changed
    function refreshLive() {
        if (document.hidden || window._cpDragging || $('.modal.show').length || Swal.isVisible()) return;
        loadBoard({ silent: true });
        loadIdeaStats();
        if ($('#ideaTab').hasClass('active') && ideaPage === 1) loadIdeas(true, true);
    }
    setInterval(refreshLive, 10000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) refreshLive(); });

    // Notifications: perlu approval, siap publish, revisi masuk, ide baru, …
    MkNotify.start({
        userId: @json(auth()->id()),
        url: @json(route('workflow-notifications.feed')),
        button: '#btnNotif',
        onEvents: refreshLive,
        open: function (item) {
            // an item of this page opens in place instead of reloading
            var u = new URL(item.url, window.location.origin);
            if (u.pathname !== window.location.pathname) return false;
            if (u.searchParams.get('open')) openPlan(u.searchParams.get('open'));
            if (u.searchParams.get('routine')) openRoutine(u.searchParams.get('routine'));
            if (u.searchParams.get('tab') === 'ide') $('#idea-tab').tab('show');
            return true;
        }
    });
});
</script>
@endpush
