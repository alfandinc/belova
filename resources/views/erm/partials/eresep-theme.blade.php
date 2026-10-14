{{--
    E-Resep look (dokter + farmasi), on top of erm.partials.form-theme (.erm-form / .erm-section / .erm-actionbar).
    Wrap the page in .erm-form.eresep-page with ClinicTheme::cssVars('ef'). Adds:
      .eresep-toolbar                  riwayat / paket / cetak buttons above the sections
      .racikan-card (+ -head)          one racikan inside the Racikan section
      .eresep-subcard                  inline "tambah obat" box inside a racikan
      .btn-erm-accent / -soft / -ghost / -danger, .racikan-save-btn
      .btn-erm-tool.tone-*             coloured toolbar buttons (blue, teal, amber, indigo, green, purple, orange)
      .stok-low / .stok-mid / .stok-ok stock colours (readable in light + dark)
      .row-in-farmasi                  dokter rows that farmasi already has
      .eresep-total                    total harga in the action bar
--}}
@once
<style>
    .eresep-page { --er-ok: #059669; --er-mid: #b45309; --er-low: #dc2626; }
    html.theme-dark .eresep-page { --er-ok: #34d399; --er-mid: #fbbf24; --er-low: #f87171; }

    /* Toolbar */
    .eresep-toolbar {
        display: flex; align-items: center; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; padding: 8px 12px;
        background: var(--sb-bg, #fff); border: 1px solid var(--sb-line, #e2e8f0); border-radius: 14px;
    }
    .eresep-toolbar-label { font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--sb-muted, #8a94ad); margin-right: 2px; }
    .eresep-toolbar-sep { width: 1px; align-self: stretch; background: var(--sb-line, #e2e8f0); margin: 2px 6px; }

    /* Toolbar buttons: one tint per action (--t = tone rgb, --t-text = label colour, lighter in dark mode) */
    .eresep-page .btn-erm-tool {
        --t: 37, 99, 235; --t-text: #1d4ed8;
        padding: .45rem .9rem; font-size: .875rem; line-height: 1.3;
        background: rgba(var(--t), .1); border: 1px solid rgba(var(--t), .3); color: var(--t-text);
    }
    .eresep-page .btn-erm-tool i { opacity: .9; }
    .eresep-page .btn-erm-tool:hover, .eresep-page .btn-erm-tool:focus { background: rgba(var(--t), .2); border-color: rgba(var(--t), .5); color: var(--t-text); }
    .eresep-page .btn-erm-tool.tone-blue   { --t: 37, 99, 235;  --t-text: #1d4ed8; }
    .eresep-page .btn-erm-tool.tone-teal   { --t: 13, 148, 136; --t-text: #0f766e; }
    .eresep-page .btn-erm-tool.tone-amber  { --t: 217, 119, 6;  --t-text: #b45309; }
    .eresep-page .btn-erm-tool.tone-indigo { --t: 79, 70, 229;  --t-text: #4338ca; }
    .eresep-page .btn-erm-tool.tone-green  { --t: 5, 150, 105;  --t-text: #047857; }
    .eresep-page .btn-erm-tool.tone-purple { --t: 147, 51, 234; --t-text: #7e22ce; }
    .eresep-page .btn-erm-tool.tone-orange { --t: 234, 88, 12;  --t-text: #c2410c; }
    html.theme-dark .eresep-page .btn-erm-tool.tone-blue   { --t-text: #93c5fd; }
    html.theme-dark .eresep-page .btn-erm-tool.tone-teal   { --t-text: #5eead4; }
    html.theme-dark .eresep-page .btn-erm-tool.tone-amber  { --t-text: #fcd34d; }
    html.theme-dark .eresep-page .btn-erm-tool.tone-indigo { --t-text: #a5b4fc; }
    html.theme-dark .eresep-page .btn-erm-tool.tone-green  { --t-text: #6ee7b7; }
    html.theme-dark .eresep-page .btn-erm-tool.tone-purple { --t-text: #d8b4fe; }
    html.theme-dark .eresep-page .btn-erm-tool.tone-orange { --t-text: #fdba74; }

    /* Buttons */
    .eresep-page .btn { border-radius: 9px; font-weight: 600; }
    .eresep-page .btn-erm-accent { background: var(--ef-accent); border-color: var(--ef-accent); color: #fff; }
    .eresep-page .btn-erm-accent:hover, .eresep-page .btn-erm-accent:focus { filter: brightness(1.08); color: #fff; }
    .eresep-page .btn-erm-soft { background: rgba(var(--ef-accent-rgb), .1); border: 1px solid rgba(var(--ef-accent-rgb), .25); color: var(--ef-accent-text); }
    .eresep-page .btn-erm-soft:hover { background: rgba(var(--ef-accent-rgb), .18); color: var(--ef-accent-text); }
    .eresep-page .btn-erm-ghost { background: transparent; border: 1px solid var(--sb-line, #e2e8f0); color: var(--sb-text-strong, #1e2538); }
    .eresep-page .btn-erm-ghost:hover { border-color: rgba(var(--ef-accent-rgb), .5); color: var(--ef-accent-text); }
    .eresep-page .btn-erm-danger { background: rgba(220, 38, 38, .08); border: 1px solid rgba(220, 38, 38, .3); color: var(--er-low); }
    .eresep-page .btn-erm-danger:hover { background: rgba(220, 38, 38, .16); color: var(--er-low); }
    .eresep-page .btn.disabled, .eresep-page .btn:disabled { opacity: .45; }

    /* Lock banner + empty states */
    .eresep-lock {
        display: flex; gap: 10px; align-items: flex-start; margin-bottom: 14px; padding: 10px 14px; border-radius: 12px;
        background: rgba(217, 119, 6, .1); border: 1px solid rgba(217, 119, 6, .35); color: var(--sb-text-strong, #1e2538); font-size: .85rem;
    }
    .eresep-lock i { color: var(--er-mid); margin-top: 3px; }
    .eresep-empty-racikan { padding: 4px 0 14px; font-size: .85rem; color: var(--sb-muted, #8a94ad); }

    /* Tables */
    .eresep-page .eresep-table { background: var(--sb-bg, #fff); font-size: .86rem; }
    .eresep-page .eresep-table td { padding: 8px 10px; }
    .eresep-page .eresep-col-aksi { width: 1%; white-space: nowrap; }
    .eresep-page .eresep-table .no-data td { padding: 18px 10px; }
    .eresep-page .table-responsive > .table { margin-bottom: 0; }
    .eresep-page .table-responsive { margin-bottom: 12px; }

    /* Stock + "already in farmasi" */
    .eresep-page .stok-low { color: var(--er-low) !important; font-weight: 700; }
    .eresep-page .stok-mid { color: var(--er-mid) !important; font-weight: 700; }
    .eresep-page .stok-ok { color: var(--er-ok) !important; font-weight: 600; }
    .eresep-page tr.row-in-farmasi > td { background: rgba(16, 185, 129, .08); color: var(--er-ok); }
    .eresep-page tr.row-in-farmasi > td:first-child { box-shadow: inset 3px 0 0 var(--er-ok); }
    .eresep-legend { display: flex; align-items: center; gap: 6px; margin: -2px 0 8px; font-size: .76rem; color: var(--sb-muted, #8a94ad); }
    .eresep-legend-swatch { width: 12px; height: 12px; border-radius: 4px; background: rgba(16, 185, 129, .18); box-shadow: inset 3px 0 0 var(--er-ok); }

    /* Racikan cards */
    .eresep-page .racikan-card {
        background: var(--sb-tile, #f4f6fa); border: 1px solid var(--sb-line, #e2e8f0) !important; border-radius: 12px !important;
        padding: 12px 14px 14px !important; margin-bottom: 14px !important;
    }
    .eresep-page .racikan-card > .d-flex:first-child,
    .eresep-page .racikan-card-head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
    .eresep-page .racikan-card h5 { margin: 0; font-size: .95rem; color: var(--sb-text-strong, #1e2538); }
    /* Racikan made from a paket: paket name as title, its number as a small tag (billing / etiket use the number) */
    .eresep-page .racikan-no {
        display: inline-block; margin-left: 8px; padding: 1px 8px; border-radius: 6px; vertical-align: middle;
        font-size: .7rem; font-weight: 700; background: var(--sb-bg, #fff); border: 1px solid var(--sb-line, #e2e8f0); color: var(--sb-muted, #8a94ad);
    }
    .eresep-page .racikan-harga-detail, .eresep-page .racikan-harga {
        margin-left: 8px; font-size: .8rem; font-weight: 600; color: var(--ef-accent-text) !important;
    }
    .eresep-page .racikan-card .form-control:disabled { background-color: var(--sb-bg, #fff); opacity: .85; }
    .eresep-page .racikan-save-btn {
        margin-top: 12px; padding: 8px 14px; border-radius: 10px; font-weight: 700;
        background: var(--ef-accent); border: 1px solid var(--ef-accent); color: #fff;
    }
    .eresep-page .racikan-save-btn:disabled { background: transparent; color: var(--er-ok); border-color: rgba(16, 185, 129, .45); opacity: 1; }
    .eresep-page .racikan-save-btn:disabled::before { content: "\2713  "; }
    .eresep-page .racikan-save-btn.is-primary { background: var(--ef-accent); color: #fff; }
    .eresep-subcard { margin-bottom: 12px; padding: 10px 12px 12px; border-radius: 10px; border: 1px dashed rgba(var(--ef-accent-rgb), .45); background: var(--sb-bg, #fff); }

    /* Catatan dokter (farmasi, read only) */
    .eresep-note {
        padding: 12px 14px; margin-bottom: 10px; border-radius: 10px; white-space: pre-wrap; font-size: 1.05rem; font-weight: 700; line-height: 1.5;
        background: rgba(220, 38, 38, .06); border: 1px solid rgba(220, 38, 38, .25); border-left: 4px solid var(--er-low); color: var(--er-low);
    }
    .eresep-note.is-empty { font-size: .85rem; font-weight: 500; color: var(--sb-muted, #8a94ad); background: transparent; border-color: var(--sb-line, #e2e8f0); border-left-width: 1px; }

    /* Action bar total */
    .eresep-total { margin-right: auto; display: flex; flex-direction: column; line-height: 1.15; }
    .eresep-total-label { font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--sb-muted, #8a94ad); }
    .eresep-total-value { font-size: 1.25rem; font-weight: 800; color: var(--ef-accent-text); }
    .eresep-total-value strong { font-weight: 800; }
    .erm-actionbar .btn-success.btn-secondary, .erm-actionbar .btn-secondary { background: var(--sb-tile, #eef1f6); border-color: var(--sb-line, #e2e8f0); color: var(--sb-text-strong, #1e2538); }

    @media (max-width: 575.98px) {
        .eresep-page .racikan-card { padding: 10px !important; }
        .eresep-total { flex: 1 1 100%; flex-direction: row; align-items: baseline; gap: 8px; }
    }
</style>
@endonce
