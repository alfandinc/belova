{{-- Shared look of every dashboard widget (components/dash/*). Uses the --dsh-* tokens of dashboard/index. --}}
<style>
    .dsh {
        --dw-series-1: #2a78d6;
        --dw-series-2: #eb6834;
        --dw-series-3: #1baf7a;
        --dw-series-4: #eda100;
        --dw-heat-rgb: 42, 120, 214;
        --dw-other: #b8b6ae;
        --dw-bar: #5b6b82; /* single-measure bars: neutral, so categorical hues keep one meaning */
        --dw-good: #0ca30c;
        --dw-good-ink: #006300;
        --dw-good-soft: #ecfdf3;
        --dw-warning: #fab219;
        --dw-warning-ink: #8a5a00;
        --dw-warning-soft: #fff8e6;
        --dw-critical: #d03b3b;
        --dw-critical-ink: #b42323;
        --dw-critical-soft: #fdf0f0;
        --dw-good-line: #b7e4c0;
        --dw-warning-line: #f5dc9d;
        --dw-critical-line: #f3c4c4;
        --dw-info-line: #bfdbfe;
        --dw-grid: #eef0f3;
        /* One fixed color per clinic (AppSupportDashboardClinics), same hues as the login page / sidebar accent */
        --dw-klinik-premiere: #2563eb;
        --dw-klinik-skin: #db2777;
        --dw-klinik-dental: #16a34a;
        --dw-hit: #fde68a; /* dashboard search: matched text */
        --dw-hit-ink: #1e293b;
    }

    /* Dark theme (html.theme-dark, set by the layout's theme toggle): same hues, stepped for the dark surface */
    html.theme-dark .dsh {
        --dsh-ink: #edf0f5;
        --dsh-ink-2: #c6ccda;
        --dsh-muted: #9aa3b8;
        --dsh-faint: #7d869c;
        --dsh-line: #3a4160;
        --dsh-line-soft: #343a52;
        --dsh-surface: #2c3144;
        --dsh-surface-2: #262b3c;
        --dsh-accent: #6ea8f7;
        --dsh-accent-soft: rgba(57, 135, 229, 0.16);
        --dsh-danger: #f07070;
        --dsh-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
        --dw-series-1: #3987e5;
        --dw-series-2: #d95926;
        --dw-series-3: #199e70;
        --dw-series-4: #c98500;
        --dw-heat-rgb: 57, 135, 229;
        --dw-other: #6b7286;
        --dw-bar: #8d98b0;
        --dw-good-ink: #4cc74c;
        --dw-good-soft: rgba(12, 163, 12, 0.14);
        --dw-warning-ink: #f5c04a;
        --dw-warning-soft: rgba(250, 178, 25, 0.13);
        --dw-critical-ink: #f07a7a;
        --dw-critical-soft: rgba(208, 59, 59, 0.16);
        --dw-good-line: rgba(12, 163, 12, 0.4);
        --dw-warning-line: rgba(250, 178, 25, 0.4);
        --dw-critical-line: rgba(208, 59, 59, 0.45);
        --dw-info-line: rgba(57, 135, 229, 0.45);
        --dw-grid: #383f58;
        --dw-klinik-premiere: #5b93f2;
        --dw-klinik-skin: #ea5d9e;
        --dw-klinik-dental: #32b768;
        --dw-hit: rgba(250, 178, 25, 0.38);
        --dw-hit-ink: #fff7e0;
    }

    html.theme-dark .dsh .dw-select,
    html.theme-dark .dsh .dw-search input,
    html.theme-dark .dsh #dashboard-daterange {
        color: var(--dsh-ink);
    }

    /* Card */
    .dw {
        display: flex;
        flex-direction: column;
        height: 100%;
        background: var(--dsh-surface);
        border: 1px solid var(--dsh-line);
        border-radius: var(--dsh-radius);
        box-shadow: var(--dsh-shadow);
        min-width: 0;
    }

    .dw-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 20px 12px;
    }

    .dw-head-main {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .dw-icon {
        flex: 0 0 36px;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: var(--dsh-accent-soft);
        color: var(--dsh-accent);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dw-icon .feather {
        width: 18px;
        height: 18px;
    }

    .dw-head-text {
        min-width: 0;
    }

    .dw-title {
        margin: 0;
        font-size: 0.98rem;
        font-weight: 700;
        color: var(--dsh-ink);
        line-height: 1.3;
    }

    .dw-sub {
        font-size: 0.78rem;
        color: var(--dsh-muted);
        margin-top: 1px;
    }

    .dw-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    .dw-body {
        padding: 4px 20px 16px;
        flex: 1 1 auto;
        min-width: 0;
    }

    .dw-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 20px;
        border-top: 1px solid var(--dsh-line-soft);
    }

    .dw-foot-note {
        font-size: 0.78rem;
        color: var(--dsh-muted);
    }

    .dw-link {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--dsh-accent);
        white-space: nowrap;
    }

    .dw-link:hover {
        color: var(--dsh-accent);
        text-decoration: underline;
    }

    .dw-link .feather {
        width: 14px;
        height: 14px;
    }

    .dw-section-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--dsh-faint);
        margin: 16px 0 8px;
    }

    .dw-body > .dw-section-label:first-child {
        margin-top: 4px;
    }

    /* Headline numbers */
    .dw-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 10px;
    }

    .dw-stat {
        padding: 12px 14px;
        border-radius: 12px;
        background: var(--dsh-surface-2);
        border: 1px solid var(--dsh-line-soft);
        min-width: 0;
    }

    .dw-stat.is-hero {
        background: var(--dsh-surface);
        border-color: var(--dsh-line);
    }

    .dw-stat-label {
        font-size: 0.74rem;
        font-weight: 600;
        color: var(--dsh-muted);
    }

    .dw-stat-value {
        margin-top: 2px;
        font-size: 1.3rem;
        font-weight: 700;
        line-height: 1.25;
        color: var(--dsh-ink);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .dw-stat.is-hero .dw-stat-value {
        font-size: 1.75rem;
    }

    .dw-stat.is-critical .dw-stat-value { color: var(--dw-critical-ink); }
    .dw-stat.is-warning .dw-stat-value { color: var(--dw-warning-ink); }
    .dw-stat.is-good .dw-stat-value { color: var(--dw-good-ink); }

    .dw-stat-meta {
        font-size: 0.75rem;
        color: var(--dsh-muted);
        margin-top: 2px;
    }

    /* x-dash.target-gap: what a target still needs (total + per day) */
    .dw-gap {
        display: flex;
        align-items: flex-start;
        gap: 6px;
        margin-top: 6px;
        font-size: 0.75rem;
        line-height: 1.4;
        color: var(--dsh-ink-2);
    }

    .dw-gap strong {
        color: var(--dsh-ink);
        white-space: nowrap;
    }

    .dw-gap .feather {
        width: 13px;
        height: 13px;
        flex: 0 0 auto;
        margin-top: 2px;
        color: var(--dw-warning-ink);
    }

    .dw-gap.is-done .feather {
        color: var(--dw-good-ink);
    }

    .dw-trend {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        margin-top: 4px;
        font-size: 0.76rem;
        font-weight: 700;
    }

    .dw-trend .feather {
        width: 14px;
        height: 14px;
    }

    .dw-trend span {
        font-weight: 500;
        color: var(--dsh-muted);
        margin-left: 2px;
    }

    .dw-trend.is-good { color: var(--dw-good-ink); }
    .dw-trend.is-bad { color: var(--dw-critical-ink); }
    .dw-trend.is-flat { color: var(--dsh-muted); }

    /* Bars */
    .dw-bars {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .dw-bar {
        display: block;
        color: inherit;
    }

    a.dw-bar:hover {
        text-decoration: none;
        color: inherit;
    }

    a.dw-bar:hover .dw-bar-label {
        color: var(--dsh-accent);
    }

    .dw-bar-top {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 10px;
        font-size: 0.82rem;
        margin-bottom: 5px;
    }

    .dw-bar-label {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: var(--dsh-ink-2);
        font-weight: 600;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dw-bar-value {
        font-weight: 700;
        color: var(--dsh-ink);
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    .dw-bar-track {
        height: 8px;
        border-radius: 4px;
        background: var(--dw-grid);
        overflow: hidden;
    }

    .dw-bar-track.has-marker {
        position: relative;
        overflow: visible;
    }

    .dw-bar-fill {
        height: 100%;
        border-radius: 4px;
        background: var(--dw-bar);
        min-width: 2px;
    }

    .dw-bar-sub {
        font-size: 0.74rem;
        color: var(--dsh-muted);
        margin-top: 4px;
    }

    .dw-dot {
        display: inline-block;
        width: 9px;
        height: 9px;
        border-radius: 3px;
        flex: 0 0 9px;
    }

    /* Stacked composition bar: 2px surface gap between segments */
    .dw-stack {
        display: flex;
        gap: 2px;
        height: 12px;
        border-radius: 4px;
        overflow: hidden;
        background: var(--dsh-surface);
    }

    .dw-stack-seg {
        height: 100%;
        min-width: 3px;
    }

    .dw-stack-seg:first-child { border-radius: 4px 0 0 4px; }
    .dw-stack-seg:last-child { border-radius: 0 4px 4px 0; }
    .dw-stack-seg:only-child { border-radius: 4px; }

    .dw-stack-seg.is-empty {
        width: 100%;
        background: var(--dw-grid);
    }

    .dw-legend {
        list-style: none;
        margin: 10px 0 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .dw-legend li {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.8rem;
    }

    .dw-legend-label {
        flex: 1 1 auto;
        color: var(--dsh-ink-2);
        min-width: 0;
    }

    .dw-legend-value {
        font-weight: 700;
        color: var(--dsh-ink);
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .dw-legend-pct {
        width: 44px;
        text-align: right;
        color: var(--dsh-muted);
        font-variant-numeric: tabular-nums;
    }

    .dw-legend.is-inline {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 6px 16px;
    }

    /* Lists */
    .dw-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .dw-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-top: 1px solid var(--dsh-line-soft);
        min-width: 0;
    }

    .dw-list > .dw-item:first-child {
        border-top: 0;
    }

    a.dw-item {
        color: inherit;
    }

    a.dw-item:hover {
        text-decoration: none;
        color: inherit;
        background: var(--dsh-surface-2);
        margin: 0 -10px;
        padding-left: 10px;
        padding-right: 10px;
        border-radius: 8px;
    }

    .dw-item-lead {
        flex: 0 0 auto;
        min-width: 44px;
        text-align: center;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--dsh-ink-2);
        font-variant-numeric: tabular-nums;
    }

    .dw-item-lead small {
        display: block;
        font-size: 0.66rem;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--dsh-faint);
    }

    .dw-avatar {
        flex: 0 0 34px;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: var(--dsh-accent-soft);
        color: var(--dsh-accent);
        font-size: 0.75rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dw-item-icon {
        flex: 0 0 32px;
        width: 32px;
        height: 32px;
        border-radius: 9px;
        background: var(--dsh-surface-2);
        border: 1px solid var(--dsh-line-soft);
        color: var(--dsh-ink-2);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dw-item-icon .feather {
        width: 16px;
        height: 16px;
    }

    .dw-item-main {
        flex: 1 1 auto;
        min-width: 0;
    }

    .dw-item-title {
        font-size: 0.86rem;
        font-weight: 600;
        color: var(--dsh-ink);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .dw-item-meta {
        font-size: 0.75rem;
        color: var(--dsh-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .dw-item-aside {
        flex: 0 0 auto;
        text-align: right;
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--dsh-ink);
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    .dw-count {
        min-width: 26px;
        height: 24px;
        padding: 0 8px;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--dsh-surface-2);
        border: 1px solid var(--dsh-line);
        color: var(--dsh-ink-2);
    }

    .dw-count.is-critical { background: var(--dw-critical-soft); border-color: var(--dw-critical-line); color: var(--dw-critical-ink); }
    .dw-count.is-warning { background: var(--dw-warning-soft); border-color: var(--dw-warning-line); color: var(--dw-warning-ink); }
    .dw-count.is-info { background: var(--dsh-accent-soft); border-color: var(--dw-info-line); color: var(--dsh-accent); }

    /* Status pill */
    .dw-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 9px;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
        border: 1px solid var(--dsh-line);
        background: var(--dsh-surface-2);
        color: var(--dsh-ink-2);
    }

    .dw-pill .feather {
        width: 12px;
        height: 12px;
    }

    .dw-pill.is-good { background: var(--dw-good-soft); border-color: var(--dw-good-line); color: var(--dw-good-ink); }
    .dw-pill.is-warning { background: var(--dw-warning-soft); border-color: var(--dw-warning-line); color: var(--dw-warning-ink); }
    .dw-pill.is-critical { background: var(--dw-critical-soft); border-color: var(--dw-critical-line); color: var(--dw-critical-ink); }
    .dw-pill.is-info { background: var(--dsh-accent-soft); border-color: var(--dw-info-line); color: var(--dsh-accent); }

    /* Segmented control (widget-level filter) */
    .dw-seg {
        display: inline-flex;
        padding: 3px;
        border-radius: 10px;
        background: var(--dsh-surface-2);
        border: 1px solid var(--dsh-line);
    }

    .dw-seg label {
        margin: 0;
        cursor: pointer;
    }

    .dw-seg input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .dw-seg span {
        display: block;
        padding: 4px 11px;
        border-radius: 7px;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--dsh-muted);
        white-space: nowrap;
    }

    .dw-seg input:checked + span {
        background: var(--dsh-surface);
        color: var(--dsh-ink);
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.1);
    }

    .dw-seg input:focus-visible + span {
        outline: 2px solid var(--dsh-accent);
    }

    .dw-select {
        height: 32px;
        padding: 0 28px 0 10px;
        border-radius: 9px;
        border: 1px solid var(--dsh-line);
        background-color: var(--dsh-surface);
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--dsh-ink-2);
        max-width: 180px;
    }

    /* Tabs + table (Buku Menu) */
    .dw-tabs {
        display: flex;
        gap: 4px;
        border-bottom: 1px solid var(--dsh-line);
        margin-bottom: 10px;
    }

    .dw-tabs a {
        padding: 8px 12px;
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--dsh-muted);
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
    }

    .dw-tabs a:hover {
        color: var(--dsh-ink);
        text-decoration: none;
    }

    .dw-tabs a.active {
        color: var(--dsh-accent);
        border-bottom-color: var(--dsh-accent);
    }

    .dw-search {
        position: relative;
        margin-bottom: 10px;
    }

    .dw-search .feather {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        width: 15px;
        height: 15px;
        color: var(--dsh-faint);
        pointer-events: none;
    }

    .dw-search input {
        width: 100%;
        height: 36px;
        padding: 0 12px 0 34px;
        border-radius: 10px;
        border: 1px solid var(--dsh-line);
        font-size: 0.85rem;
        background: var(--dsh-surface);
        color: var(--dsh-ink);
    }

    .dw-search input:focus {
        outline: none;
        border-color: var(--dw-info-line);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .dw table.dw-table {
        width: 100% !important;
        margin: 0 !important;
        font-size: 0.82rem;
        border-collapse: collapse !important;
    }

    .dw table.dw-table thead th {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--dsh-faint);
        border: 0 !important;
        border-bottom: 1px solid var(--dsh-line) !important;
        padding: 6px 8px !important;
        background: transparent;
    }

    .dw table.dw-table tbody td {
        border: 0 !important;
        border-bottom: 1px solid var(--dsh-line-soft) !important;
        padding: 8px !important;
        vertical-align: top;
        color: var(--dsh-ink-2);
    }

    .dw table.dw-table td.dw-num {
        text-align: right;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
        font-weight: 600;
        color: var(--dsh-ink);
    }

    .dw .dataTables_info,
    .dw .dataTables_paginate {
        font-size: 0.76rem;
        padding-top: 8px !important;
    }

    .dw .dataTables_paginate .page-link {
        padding: 3px 9px;
        font-size: 0.76rem;
    }

    /* Chart */
    .dw-chart {
        min-height: 180px;
        margin: 4px -8px 0;
    }

    /* Empty / error */
    .dw-empty {
        text-align: center;
        padding: 24px 12px;
        color: var(--dsh-muted);
    }

    .dw-empty-icon {
        width: 40px;
        height: 40px;
        margin: 0 auto 8px;
        border-radius: 12px;
        background: var(--dsh-surface-2);
        border: 1px solid var(--dsh-line);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--dsh-faint);
    }

    .dw-empty-icon .feather {
        width: 18px;
        height: 18px;
    }

    .dw-empty-title {
        font-weight: 600;
        font-size: 0.88rem;
        color: var(--dsh-ink-2);
    }

    .dw-empty-text {
        font-size: 0.8rem;
        margin: 2px 0 0;
    }

    .dw-note {
        font-size: 0.76rem;
        color: var(--dsh-muted);
        margin-top: 10px;
    }

    /* Executive tables */
    .dw-table-wrap {
        overflow-x: auto;
        margin: 0 -4px;
    }

    .dw-grid-table {
        width: 100%;
        min-width: 640px;
        border-collapse: collapse;
        font-size: 0.82rem;
    }

    .dw-grid-table th {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--dsh-faint);
        padding: 6px 8px;
        border-bottom: 1px solid var(--dsh-line);
        white-space: nowrap;
    }

    .dw-grid-table td {
        padding: 10px 8px;
        border-bottom: 1px solid var(--dsh-line-soft);
        vertical-align: top;
        color: var(--dsh-ink-2);
    }

    .dw-grid-table tfoot td {
        border-bottom: 0;
        border-top: 1px solid var(--dsh-line);
        background: var(--dsh-surface-2);
    }

    .dw-cell-title {
        font-weight: 700;
        color: var(--dsh-ink);
    }

    .dw-cell-num {
        font-weight: 700;
        color: var(--dsh-ink);
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    .dw-delta {
        margin-top: 2px;
        font-size: 0.72rem;
        white-space: nowrap;
    }

    .dw-delta .feather {
        width: 12px;
        height: 12px;
    }

    .dw-meter {
        min-width: 120px;
    }

    .dw-meter-value {
        display: block;
        font-weight: 700;
        color: var(--dsh-ink);
        font-variant-numeric: tabular-nums;
        margin-bottom: 4px;
    }

    .dw-meter-track {
        position: relative;
        display: block;
        height: 8px;
        border-radius: 4px;
        background: var(--dw-grid);
    }

    .dw-meter-fill {
        display: block;
        height: 100%;
        border-radius: 4px;
        background: var(--dw-bar);
        min-width: 2px;
    }

    .dw-meter.is-done .dw-meter-fill { background: var(--dw-good); }
    .dw-meter.is-done .dw-meter-value { color: var(--dw-good-ink); }
    .dw-meter.is-behind .dw-meter-value { color: var(--dw-warning-ink); }

    /* Where the target should be by today */
    .dw-meter-pace {
        position: absolute;
        top: -3px;
        bottom: -3px;
        width: 2px;
        margin-left: -1px;
        border-radius: 1px;
        background: var(--dsh-ink);
    }

    /* Cohort heatmap: one hue, value always printed */
    .dw-cohort td.dw-heat {
        text-align: center;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        border-left: 2px solid var(--dsh-surface);
    }

    .dw-cohort td.dw-heat.is-na {
        color: var(--dsh-faint);
        font-weight: 500;
    }

    /* Widget still loading: first time a skeleton, on reload the old content dimmed */
    [data-widget-slot] {
        transition: opacity 0.15s ease;
    }

    [data-widget-slot].is-loading {
        opacity: 0.5;
        pointer-events: none;
    }

    .dw-skel-line,
    .dw-skel-block {
        border-radius: 6px;
        background: linear-gradient(90deg, var(--dsh-line-soft) 25%, var(--dsh-surface-2) 50%, var(--dsh-line-soft) 75%);
        background-size: 200% 100%;
        animation: dw-skel 1.2s ease-in-out infinite;
    }

    .dw-skel-line {
        height: 12px;
        margin-bottom: 12px;
    }

    .dw-skel-block {
        height: 120px;
        margin-bottom: 16px;
    }

    @keyframes dw-skel {
        from { background-position: 100% 0; }
        to { background-position: -100% 0; }
    }

    /* One clinic's section (x-dash.clinic): logo + name, the clinic color as a left stripe and a faint tint */
    .dw-clinics {
        display: grid;
        gap: 10px;
    }

    .dw-clinics.is-columns {
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 14px;
    }

    .dw-clinic {
        border: 1px solid var(--dsh-line-soft);
        border-left: 4px solid var(--dw-clinic);
        border-radius: 12px;
        background: linear-gradient(90deg, color-mix(in srgb, var(--dw-clinic) 7%, transparent), transparent 60%), var(--dsh-surface-2);
        padding: 10px 14px 12px;
        min-width: 0;
    }

    .dw-clinic-head {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }

    .dw-clinic-logo {
        width: 24px;
        height: 24px;
        flex: 0 0 24px;
        border-radius: 7px;
        object-fit: contain;
        background: #fff;
        padding: 2px;
    }

    .dw-clinic-logo.is-initial {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        background: var(--dw-clinic);
        color: #fff;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .dw-clinic-name {
        font-size: 0.86rem;
        font-weight: 700;
        color: var(--dw-clinic);
        white-space: nowrap;
    }

    html.theme-dark .dsh .dw-clinic-name {
        filter: brightness(1.15);
    }

    .dw-clinic-meta {
        font-size: 0.75rem;
        color: var(--dsh-muted);
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dw-clinic-aside {
        margin-left: auto;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        flex: 0 0 auto;
    }

    .dw-clinic .dw-stat {
        background: var(--dsh-surface);
        padding: 8px 12px;
    }

    .dw-clinic .dw-stat-value {
        font-size: 1.1rem;
    }

    .dw-clinic .dw-section-label {
        margin: 12px 0 6px;
    }

    .dw-clinic .dw-section-row {
        margin: 12px 0 6px;
    }

    /* Insurers under "Asuransi" (x-dash.metode-bayar) */
    .dw-bars-nested {
        padding-left: 14px;
        border-left: 2px solid var(--dsh-line);
    }

    .dw-clinics.is-compact {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }

    .dw-clinics.is-compact .dw-clinic-head {
        margin-bottom: 4px;
    }

    .dw-clinic-banner {
        padding-bottom: 10px;
    }

    .dw-clinic-banner .dw-clinic-head {
        margin-bottom: 0;
    }

    .dw-clinic-empty {
        font-size: 0.8rem;
        color: var(--dsh-faint);
    }

    /* "Lihat semua" modal (dashboard/index): the .dsh tokens on .modal-content, without the page padding */
    .dw-modal.dsh {
        padding: 0;
        background: var(--dsh-surface);
        border: 1px solid var(--dsh-line);
        border-radius: 14px;
    }

    .dw-modal .modal-header {
        border-bottom-color: var(--dsh-line);
    }

    .dw-modal .close {
        color: var(--dsh-ink);
        text-shadow: none;
    }

    .dw-modal [data-sort-col] {
        cursor: pointer;
        user-select: none;
    }

    .dw-modal [data-sort-col].is-sorted {
        color: var(--dsh-accent);
    }

        /* Small "Lihat semua" button next to a section label */
    .dw-section-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin: 16px 0 8px;
    }

    .dw-section-row .dw-section-label {
        margin: 0;
    }

    .dw-more-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 999px;
        border: 1px solid var(--dsh-line);
        background: var(--dsh-surface);
        color: var(--dsh-accent);
        font-size: 0.74rem;
        font-weight: 600;
        line-height: 1.4;
        cursor: pointer;
        white-space: nowrap;
    }

    .dw-more-btn:hover,
    .dw-more-btn:focus-visible {
        background: var(--dsh-accent-soft);
        border-color: var(--dsh-accent);
    }

    .dw-more-btn .feather {
        width: 13px;
        height: 13px;
    }

    @media (prefers-reduced-motion: reduce) {
        .dw-skel-line,
        .dw-skel-block {
            animation: none;
        }
    }

    @media (max-width: 575.98px) {
        .dw-head {
            flex-wrap: wrap;
        }

        .dw-head,
        .dw-body,
        .dw-foot {
            padding-left: 16px;
            padding-right: 16px;
        }
    }
</style>
