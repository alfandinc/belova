<style>
    /* Gender badge: rounded rectangle around the icon */
    .gender-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        padding: 0;
        border-radius: 5px;
        border: 1px solid rgba(0,0,0,0.06);
        background: #f8f9fa;
        line-height: 1;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    .gender-badge .fa-mars, .gender-badge .fa-venus { color: #fff; font-size: 0.95rem; }
    .gender-badge.gender-male {
        background: #0d6efd; /* bootstrap primary */
        border-color: rgba(13,110,253,0.3);
    }
    .gender-badge.gender-female {
        background: #ff6fb3; /* soft pink */
        border-color: rgba(255,111,179,0.28);
    }
    /* Patient name + id styles */
    .patient-label { display:inline-flex; align-items:center; }
    .patient-name { font-weight:600; margin-left:8px; color:#0b1220; text-transform:uppercase; }
    .patient-id { font-weight:600; color:#2b6cb0; margin-left:8px; }
    .patient-meta { color:#6c757d; }
    .patient-age { color:#6c757d; font-weight:600; margin-left:8px; }
    /* Data Pasien card improvements */
    .data-pasien {
        border-radius: 6px;
    }
    .data-pasien .card-body {
        padding: 0.8rem 1rem;
    }
    .data-pasien .table {
        margin-bottom: 0;
    }
    .data-pasien .table td {
        padding: 0.32rem 0.5rem;
        vertical-align: middle;
    }
    .data-pasien .table td.label {
        width: 140px;
        font-weight: 600;
        color: #343a40;
        white-space: nowrap;
    }
    .data-pasien .invoice-number {
        font-weight: 700;
        color: #0d6efd;
    }
    .data-pasien .small-note { margin-top: .25rem; color: #6c757d; }

    @keyframes stockWarnBlink {
        0% { opacity: 1; }
        50% { opacity: 0.2; }
        100% { opacity: 1; }
    }
    .stock-warning-blink {
        animation: stockWarnBlink 1.1s ease-in-out infinite;
        will-change: opacity;
    }

    .page-loading-overlay {
        position: fixed;
        inset: 0;
        z-index: 2000; /* above modals backdrop (Bootstrap uses 1040/1050) */
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.92);
    }
    .page-loading-overlay.is-hidden {
        opacity: 0;
        pointer-events: none;
        transition: opacity 160ms ease;
    }
</style>
