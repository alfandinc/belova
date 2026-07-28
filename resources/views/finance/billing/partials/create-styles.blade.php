<style>
    .event-billing-header-row {
        gap: 1rem;
    }
    .event-billing-header-main,
    .event-billing-header-date,
    .event-billing-header-action {
        flex: 1 1 0;
    }
    .event-billing-header-action {
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .event-header-reset-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 38px;
        padding: 0.375rem 1rem;
        border-radius: 0.25rem;
        color: #fff;
        background-color: #28a745;
        border-color: #28a745;
        font-weight: 500;
        white-space: nowrap;
    }
    .event-header-reset-btn:hover,
    .event-header-reset-btn:focus {
        color: #fff;
        background-color: #218838;
        border-color: #1e7e34;
        text-decoration: none;
    }
    .event-billing-header-date {
        text-align: right;
    }
    @media (max-width: 767.98px) {
        .event-billing-header-date {
            text-align: left;
        }
    }
    .event-patient-row {
        margin-bottom: -0.5rem;
    }
    .event-patient-tabs .nav-link {
        font-weight: 600;
    }
    .event-patient-field {
        display: flex;
        flex-direction: column;
        margin-bottom: 1rem;
    }
    .event-patient-field label {
        margin-bottom: 0.5rem;
        line-height: 1.2;
    }
    .event-patient-field .form-control {
        min-height: 38px;
    }
    .event-gender-group {
        display: inline-flex;
        width: auto;
        height: 38px;
        align-self: flex-start;
    }
    .event-gender-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 48px;
        width: 48px;
        height: 38px;
        padding: 0;
        line-height: 1;
        flex: 0 0 auto;
    }
    .event-gender-btn i {
        font-size: 0.95rem;
    }
    .event-patient-action {
        justify-content: flex-end;
    }
    .event-patient-action-label {
        visibility: hidden;
    }
    .event-create-billing-btn {
        width: 100%;
        min-height: 38px;
        white-space: nowrap;
    }
    @media (max-width: 767.98px) {
        .event-patient-action-label {
            display: none;
        }
        .event-create-billing-btn {
            width: 100%;
        }
    }
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
