{{--
    Look of the paket racikan modals (erm.partials.modal-paketracikan + -farmasi), same language as the
    riwayat resep modal (.rr-*): rounded modal, tile body, white cards, small uppercase headers, purple = racikan.
    Only presentation: every id / class / name the e-resep pages' JS uses is kept in the markup.
--}}
@once
<style>
    .pr-modal { border-radius: 14px; overflow: hidden; background: var(--sb-bg, #fff); color: var(--sb-text-strong, #1e2538); border: 1px solid var(--sb-line, #e2e8f0); }
    .pr-modal .modal-header { align-items: center; border-bottom: 1px solid var(--sb-line, #e2e8f0); }
    .pr-modal .modal-title { font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
    .pr-modal .modal-title small { display: block; font-size: .76rem; font-weight: 500; color: var(--sb-muted, #8a94ad); }
    .pr-modal .close { color: var(--sb-text-strong, #1e2538); }
    .pr-modal .modal-body { background: var(--sb-tile, #f4f6fa); padding: 16px; }
    .pr-modal .modal-footer { border-top: 1px solid var(--sb-line, #e2e8f0); }
    .pr-title-ico { width: 32px; height: 32px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 32px;
        background: rgba(124, 58, 237, .12); color: #7c3aed; font-size: .85rem; }
    html.theme-dark .pr-title-ico { color: #c4b5fd; }

    /* white cards inside the tile body */
    .pr-card { background: var(--sb-bg, #fff); border: 1px solid var(--sb-line, #e2e8f0); border-radius: 12px; overflow: hidden;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .05), 0 4px 14px rgba(15, 23, 42, .05); margin-bottom: 14px; }
    html.theme-dark .pr-card { box-shadow: none; }
    .pr-card-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 14px;
        background: var(--sb-tile, #f1f5f9); border-bottom: 1px solid var(--sb-line, #e2e8f0); }
    .pr-card-title { margin: 0; font-size: .8rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--sb-text, #4b5671); }
    .pr-card-title strong { font-weight: 800; }
    .pr-card-body { padding: 12px 14px; }

    /* search with icon */
    .pr-search { position: relative; }
    .pr-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--sb-muted, #8a94ad); font-size: .8rem; }
    .pr-search input { padding-left: 32px; }

    /* inputs */
    .pr-modal .form-control { border-radius: 9px; border-color: var(--sb-line, #e2e8f0); background-color: var(--sb-input, #f4f6fa); color: var(--sb-text-strong, #1e2538); }
    .pr-modal .form-control:focus { background-color: var(--sb-bg, #fff); border-color: rgba(124, 58, 237, .5); box-shadow: 0 0 0 3px rgba(124, 58, 237, .12); }
    .pr-modal label { font-size: .76rem; font-weight: 700; color: var(--sb-text, #4b5671); margin-bottom: 4px; }
    .pr-modal .form-group { margin-bottom: 12px; }
    .pr-modal .select2-container .select2-selection--single { border-radius: 9px; border-color: var(--sb-line, #e2e8f0); min-height: 36px; }

    /* daftar paket (DataTable) */
    .pr-modal .pr-table { margin: 0 !important; width: 100% !important; font-size: .84rem; color: inherit; border: 0; }
    .pr-modal .pr-table thead th { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #7c3aed;
        background: rgba(124, 58, 237, .07); border: 0; border-bottom: 1px solid var(--sb-line, #e2e8f0); padding: 8px 12px; white-space: nowrap; }
    html.theme-dark .pr-modal .pr-table thead th { color: #c4b5fd; }
    .pr-modal .pr-table td { border: 0; border-bottom: 1px solid var(--sb-line, #e2e8f0); padding: 9px 12px; vertical-align: top; }
    .pr-modal .pr-table tbody tr:last-child td { border-bottom: 0; }
    .pr-modal .pr-table td:first-child { color: var(--sb-muted, #8a94ad); }
    .pr-modal .edit-paket { cursor: pointer; }
    .pr-modal .edit-paket strong { font-weight: 700; }
    .pr-modal .edit-paket:hover strong { color: #7c3aed; text-decoration: underline; }
    .pr-modal .pr-sub { margin-top: 2px; font-size: .74rem; color: var(--sb-muted, #8a94ad); }
    .pr-modal .pr-table td:last-child { white-space: nowrap; text-align: right; }
    .pr-btn-use { border-radius: 8px; font-weight: 600; font-size: .78rem; padding: 3px 12px; border: 1px solid #7c3aed; background: #7c3aed; color: #fff; }
    .pr-btn-use:hover { filter: brightness(1.08); color: #fff; }
    .pr-btn-del { border-radius: 8px; font-size: .78rem; padding: 3px 8px; border: 1px solid rgba(220, 38, 38, .3); background: rgba(220, 38, 38, .08); color: #dc2626; }
    .pr-btn-del:hover { background: rgba(220, 38, 38, .16); color: #dc2626; }
    .pr-btn-del svg { width: 14px; height: 14px; }
    .pr-modal .dataTables_paginate { padding: 8px 12px; }

    /* form: obat rows */
    .pr-obat-head { display: flex; align-items: center; justify-content: space-between; margin: 6px 0 6px; }
    .pr-obat-head span { font-size: .76rem; font-weight: 700; color: var(--sb-text, #4b5671); }
    .pr-modal .obat-paket-item { padding: 8px; border: 1px dashed var(--sb-line, #e2e8f0); border-radius: 10px; margin-bottom: 8px !important; }
    .pr-modal .obat-paket-item .row { margin: 0 -4px; }
    .pr-modal .obat-paket-item .row > div { padding: 0 4px; }
    .pr-modal .remove-obat-paket { border-radius: 8px; width: 100%; height: 36px; padding: 0; background: rgba(220, 38, 38, .08); border: 1px solid rgba(220, 38, 38, .3); color: #dc2626; }
    .pr-btn-soft { border-radius: 8px; font-weight: 600; font-size: .78rem; padding: 3px 10px; border: 1px solid rgba(124, 58, 237, .35); background: rgba(124, 58, 237, .07); color: #7c3aed; }
    .pr-btn-soft:hover { background: rgba(124, 58, 237, .14); color: #6d28d9; }
    .pr-form-actions { display: flex; gap: 8px; margin-top: 12px; }
    .pr-form-actions .btn { border-radius: 9px; font-weight: 700; }
    .pr-btn-primary { flex: 1; background: #7c3aed; border: 1px solid #7c3aed; color: #fff; }
    .pr-btn-primary:hover { filter: brightness(1.08); color: #fff; }
    .pr-btn-ghost { background: transparent; border: 1px solid var(--sb-line, #e2e8f0); color: var(--sb-text-strong, #1e2538); }

    /* gunakan paket: the chosen paket shown as a highlighted box */
    .pr-chosen { display: flex; align-items: center; gap: 10px; padding: 10px 12px; margin-bottom: 14px; border-radius: 10px;
        background: rgba(124, 58, 237, .07); border: 1px solid rgba(124, 58, 237, .25); }
    .pr-chosen small { display: block; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--sb-muted, #8a94ad); }
    .pr-chosen p { margin: 0; font-weight: 700; font-size: .95rem; }
</style>
@endonce
