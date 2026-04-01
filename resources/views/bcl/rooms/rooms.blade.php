@extends('layouts.bcl.app')

@section('content')
<!-- Page-Title -->
<style>
    :root {
        --room-action-button-size: 34px;
        --room-action-gap: 0.35rem;
        --room-action-row-width: calc((var(--room-action-button-size) * 4) + (var(--room-action-gap) * 3));
    }

    .floor-section + .floor-section {
        margin-top: 2rem;
    }

    .rooms-toolbar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }

    .rooms-toolbar__group {
        display: inline-flex;
        align-items: stretch;
        overflow: hidden;
        border-radius: 0.35rem;
    }

    .rooms-toolbar .btn,
    .rooms-toolbar .dropdown-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.28rem 0.6rem;
        font-size: 0.72rem;
        line-height: 1.2;
    }

    .rooms-toolbar__icon {
        min-width: 2rem;
        height: 2rem;
        padding: 0.28rem 0.45rem;
        border-radius: 0;
    }

    .rooms-toolbar__group .btn + .btn,
    .rooms-toolbar__group .btn + .dropdown-toggle,
    .rooms-toolbar__group .dropdown-toggle + .btn {
        border-left: 1px solid rgba(255, 255, 255, 0.16);
    }

    .rooms-toolbar .btn i,
    .rooms-toolbar .dropdown-toggle i {
        font-size: 0.82rem;
    }

    .rooms-action-button-badge {
        position: relative;
        overflow: visible;
    }

    .rooms-action-button-badge__count {
        position: absolute;
        top: -0.42rem;
        right: -0.45rem;
        min-width: 1.15rem;
        height: 1.15rem;
        padding: 0 0.26rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #dc3545;
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        line-height: 1;
        box-shadow: 0 0 0 2px #fff;
        transform: scale(0);
        transform-origin: center;
        transition: transform 0.16s ease;
    }

    .rooms-action-button-badge__count.is-visible {
        transform: scale(1);
    }

    .room-stats-title {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.55rem;
        margin: 0;
    }

    .room-stats-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.28rem 0.65rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 600;
        line-height: 1;
        cursor: pointer;
        transition: transform 0.16s ease, box-shadow 0.16s ease, filter 0.16s ease;
    }

    .room-stats-pill:hover {
        transform: translateY(-1px);
        filter: brightness(1.04);
    }

    .room-stats-pill:focus {
        outline: none;
        box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.18);
    }

    .room-stats-pill.is-active {
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.28), 0 0 0 2px rgba(255, 255, 255, 0.08);
        filter: brightness(1.08);
    }

    .room-stats-pill strong {
        font-size: 0.82rem;
        font-weight: 700;
    }

    .room-stats-pill--occupied {
        background: rgba(137, 216, 160, 0.22);
        color: #daf7e2;
    }

    .room-stats-pill--vacant {
        background: rgba(255, 153, 153, 0.2);
        color: #ffe1e1;
    }

    .room-stats-pill--pending {
        background: rgba(255, 215, 82, 0.22);
        color: #fff3c2;
    }

    .floor-heading {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr) auto;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .floor-line {
        height: 2px;
        border-radius: 999px;
        background: linear-gradient(90deg, rgba(36, 59, 83, 0.18) 0%, rgba(36, 59, 83, 0.55) 100%);
        width: 100%;
    }

    .floor-meta {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .floor-count-wrap {
        flex: 0 0 auto;
    }

    .floor-title {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #243b53;
    }

    .floor-count {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        background: #e9f2ff;
        color: #1f4b99;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .rooms-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 320px));
        gap: 1rem;
        align-items: start;
    }

    .room-card {
        position: relative;
        overflow: visible;
        width: 100%;
        min-height: 0;
        padding: 0.9rem 0.85rem 0.95rem 0.95rem;
        border: 0;
        border-radius: 18px;
        background: linear-gradient(135deg, #ffd95c 0%, #ffe680 45%, #ffd752 100%);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        align-self: start;
        font-family: inherit;
    }

    @keyframes room-warning-blink {
        0%, 100% {
            opacity: 1;
            transform: scale(1);
        }
        50% {
            opacity: 0.45;
            transform: scale(0.9);
        }
    }

    .room-card.room-card--occupied {
        background: linear-gradient(135deg, #edf9f0 0%, #dff2e4 100%);
    }

    .room-card.room-card--vacant {
        background: linear-gradient(135deg, #fdf0f0 0%, #f7dddd 100%);
    }

    .room-card.room-card--queued {
        background: linear-gradient(135deg, #fff7d6 0%, #ffeaa7 100%);
    }

    .room-card--editable {
        cursor: pointer;
        transition: transform 0.16s ease, box-shadow 0.16s ease;
    }

    .room-card--editable:hover {
        transform: none;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.1);
    }

    .room-card--editable:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(31, 75, 153, 0.12), 0 10px 22px rgba(15, 23, 42, 0.1);
    }

    .room-card__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.6rem;
        margin-bottom: 0.65rem;
        padding-right: 0;
    }

    .room-card__header-side {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.45rem;
        flex: 0 0 var(--room-action-row-width);
        width: var(--room-action-row-width);
        max-width: var(--room-action-row-width);
    }

    .room-card__name {
        margin: 0;
        font-size: clamp(2.1rem, 3vw, 3rem);
        line-height: 0.95;
        font-weight: 700;
        letter-spacing: normal;
        color: #050505;
    }

    .room-card__category {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        padding: 0.5rem 0.85rem;
        border-radius: 10px;
        background: linear-gradient(135deg, #64748b 0%, #475569 100%);
        color: #fff;
        font-size: 0.68rem;
        font-weight: 700;
        text-align: center;
        line-height: 1;
        width: var(--room-action-row-width);
        min-width: var(--room-action-row-width);
        max-width: var(--room-action-row-width);
        box-sizing: border-box;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        box-shadow: none;
        align-self: flex-end;
        cursor: pointer;
    }

    .room-card__category:hover,
    .room-card__category:focus {
        filter: brightness(1.03);
        outline: none;
    }

    .room-card__category.room-card__category--green {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: #fff;
    }

    .room-card__category.room-card__category--blue {
        background: linear-gradient(135deg, #a78bfa 0%, #7c3aed 100%);
        color: #fff;
    }

    .room-card__category.room-card__category--amber {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: #fff;
    }

    .room-card__category.room-card__category--orange {
        background: linear-gradient(135deg, #fb923c 0%, #ea580c 100%);
        color: #fff;
    }

    .room-card__category.room-card__category--purple {
        background: linear-gradient(135deg, #a78bfa 0%, #7c3aed 100%);
        color: #fff;
    }

    .room-card__category.room-card__category--teal {
        background: linear-gradient(135deg, #2dd4bf 0%, #0f766e 100%);
        color: #fff;
    }

    .room-card__divider {
        display: none;
    }

    .room-card__tenant {
        display: flex;
        align-items: flex-start;
        gap: 0.4rem;
        margin: 0 0 0.22rem;
        font-size: 0.82rem;
        font-weight: 600;

    .room-card__tenant-name {
        border: 0;
        background: transparent;
        padding: 0;
        margin: 0;
        font: inherit;
        color: inherit;
        text-align: left;
        cursor: pointer;
        text-decoration: underline;
        text-decoration-color: rgba(79, 70, 229, 0.25);
        text-underline-offset: 0.12rem;
    }

    .room-card__tenant-name:hover,
    .room-card__tenant-name:focus {
        color: #4338ca;
        outline: none;
    }
        letter-spacing: normal;
        text-transform: none;
        color: #0b0b0b;
    }

    .room-card__period {
        display: flex;
        align-items: flex-start;
        gap: 0.4rem;
        margin: 0 0 0.22rem;
        font-size: 0.76rem;
        color: #334155;
    }

    .room-card__icon {
        flex: 0 0 14px;
        width: 13px;
        height: 13px;
        margin-top: 0.15rem;
        color: rgba(15, 23, 42, 0.62);
    }

    .room-card__text {
        min-width: 0;
    }

    .room-card__period-link {
        display: inline-flex;
        align-items: flex-start;
        gap: 0.3rem;
        min-width: 0;
        color: inherit;
        text-decoration: none;
    }

    .room-card__period-link:hover,
    .room-card__period-link:focus {
        color: #1d4ed8;
        text-decoration: none;
        outline: none;
    }

    .room-card__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        margin-bottom: 0.7rem;
    }

    .room-card__pill {
        display: inline-flex;
        align-items: center;
        padding: 0.28rem 0.58rem;
        border-radius: 999px;
        background: rgba(0, 0, 0, 0.1);
        color: #111;
        font-size: 0.72rem;
        font-weight: 600;
    }

    .room-card__pill.room-card__pill--danger {
        background: #ce3d2f;
        color: #fff;
    }

    .room-card__notes {
        display: flex;
        align-items: flex-start;
        gap: 0.4rem;
        margin: 0;
        font-size: 0.76rem;
        color: #334155;
    }

    .room-card__quick-actions {
        display: flex;
        align-items: center;
        gap: 0.3rem;
        flex-wrap: nowrap;
        margin-top: 0;
        justify-content: flex-end;
        width: var(--room-action-row-width);
    }

    .room-card__quick-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 32px;
        width: 32px;
        height: 32px;
        border: 0;
        border-radius: 9px;
        background: linear-gradient(135deg, #5b7cff 0%, #3563ff 100%);
        color: #fff;
        box-shadow: 0 4px 10px rgba(53, 99, 255, 0.18);
        transition: transform 0.16s ease, box-shadow 0.16s ease, opacity 0.16s ease;
    }

    .room-card__quick-btn:hover,
    .room-card__quick-btn:focus {
        transform: none;
        box-shadow: 0 6px 12px rgba(53, 99, 255, 0.22);
        outline: none;
    }

    .room-card__quick-btn .feather {
        width: 14px;
        height: 14px;
    }

    .room-card__quick-btn--history {
        background: linear-gradient(135deg, #5b7cff 0%, #3563ff 100%);
        box-shadow: 0 4px 10px rgba(53, 99, 255, 0.18);
    }

    .room-card__quick-btn--history.room-card__quick-btn--warning {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        box-shadow: 0 6px 14px rgba(220, 38, 38, 0.24);
        animation: room-warning-blink 1.1s ease-in-out infinite;
    }

    .room-card__quick-btn--booking {
        position: relative;
        flex: 0 0 32px;
        width: 32px;
        min-width: 32px;
        padding: 0;
        font-size: 0.7rem;
        font-weight: 700;
        background: linear-gradient(135deg, #5b7cff 0%, #3563ff 100%);
        box-shadow: 0 4px 10px rgba(53, 99, 255, 0.18);
    }

    .room-card__quick-dropdown {
        position: relative;
        flex: 0 0 32px;
        width: 32px;
        min-width: 32px;
    }

    .room-card__quick-dropdown .dropdown-toggle::after {
        display: none;
    }

    .room-card__quick-menu {
        min-width: 11rem;
        padding: 0.35rem;
        border: 0;
        border-radius: 12px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.16);
    }

    .room-card__quick-menu .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-radius: 8px;
        padding: 0.48rem 0.6rem;
        font-size: 0.78rem;
        font-weight: 600;
        color: #1f2937;
    }

    .room-card__quick-menu .dropdown-item:hover,
    .room-card__quick-menu .dropdown-item:focus {
        background: #eef4ff;
        color: #1d4ed8;
    }

    .room-card__quick-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        min-width: 16px;
        height: 16px;
        padding: 0 4px;
        border-radius: 999px;
        background: #ef4444;
        color: #fff;
        font-size: 0.62rem;
        font-weight: 700;
        line-height: 16px;
        text-align: center;
        box-shadow: 0 4px 10px rgba(239, 68, 68, 0.28);
    }

    .room-card__quick-btn--disabled,
    .room-card__quick-btn:disabled {
        opacity: 1;
        cursor: pointer;
        pointer-events: auto;
        box-shadow: 0 4px 10px rgba(53, 99, 255, 0.18);
    }

    .room-modal-label {
        display: block;
        margin-bottom: 0.25rem;
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #6b7280;
    }

    .room-modal-value {
        font-size: 0.95rem;
        color: #111827;
        word-break: break-word;
    }

    .room-history-empty {
        padding: 1rem;
        text-align: center;
        color: #6b7280;
    }

    .room-history-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.35rem 0.65rem;
        border-radius: 8px;
        background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
        color: #fff;
        font-size: 0.74rem;
        font-weight: 700;
        text-decoration: none;
        white-space: nowrap;
    }

    .room-history-action:hover,
    .room-history-action:focus {
        color: #fff;
        text-decoration: none;
        outline: none;
        box-shadow: 0 6px 14px rgba(37, 99, 235, 0.22);
    }

    .room-booking-empty {
        padding: 1rem;
        text-align: center;
        color: #6b7280;
    }

    .room-pricelist-empty {
        padding: 1rem;
        text-align: center;
        color: #6b7280;
    }

    .room-modal-feedback {
        display: none;
        margin-top: 0.85rem;
        padding: 0.7rem 0.85rem;
        border-radius: 12px;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .room-modal-feedback--error {
        display: block;
        background: #fee2e2;
        color: #b91c1c;
    }

    .room-modal-feedback--success {
        display: block;
        background: #dcfce7;
        color: #166534;
    }

    .renter-detail-photo {
        width: 88px;
        height: 88px;
        border-radius: 18px;
        object-fit: cover;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
        background: #eef2ff;
    }

    .renter-detail-photo-placeholder {
        width: 88px;
        height: 88px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eef2ff;
        color: #4f46e5;
        font-size: 1.35rem;
        font-weight: 700;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
    }

    .renter-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.9rem 1rem;
    }

    .renter-detail-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(300px, 0.95fr);
        gap: 1.25rem;
        align-items: start;
    }

    .renter-detail-panel {
        min-width: 0;
        background: #f8fafc;
        border-radius: 20px;
        padding: 1rem 1rem 1.05rem;
    }

    .renter-detail-panel--history {
        background: linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%);
    }

    .renter-detail-section-title {
        margin: 0 0 0.85rem;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }

    .renter-detail-docs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .renter-detail-doc {
        display: inline-flex;
        align-items: center;
        padding: 0.45rem 0.7rem;
        border-radius: 999px;
        background: #eef4ff;
        color: #1d4ed8;
        font-size: 0.78rem;
        font-weight: 600;
        text-decoration: none;
    }

    .renter-detail-doc:hover,
    .renter-detail-doc:focus {
        color: #1e3a8a;
        text-decoration: none;
        outline: none;
    }

    .renter-detail-doc-empty {
        color: #6b7280;
        font-size: 0.88rem;
    }

    .renter-history-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        max-height: 28rem;
        overflow-y: auto;
        padding-right: 0.2rem;
    }

    .renter-history-item {
        background: #ffffff;
        border-radius: 16px;
        padding: 0.85rem 0.9rem;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
    }

    .renter-history-item__top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.55rem;
    }

    .renter-history-item__id {
        margin: 0;
        font-size: 0.86rem;
        font-weight: 700;
        color: #111827;
        word-break: break-word;
    }

    .renter-history-item__room {
        margin-top: 0.18rem;
        font-size: 0.78rem;
        font-weight: 600;
        color: #4b5563;
    }

    .renter-history-item__status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.3rem 0.6rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .renter-history-item__status--paid {
        background: #dcfce7;
        color: #166534;
    }

    .renter-history-item__status--unpaid {
        background: #fee2e2;
        color: #b91c1c;
    }

    .renter-history-item__period {
        font-size: 0.78rem;
        color: #475569;
        margin-bottom: 0.65rem;
    }

    .renter-history-item__stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.55rem;
        margin-bottom: 0.65rem;
    }

    .renter-history-item__stat {
        background: #f8fafc;
        border-radius: 12px;
        padding: 0.55rem 0.6rem;
    }

    .renter-history-item__stat-label {
        display: block;
        margin-bottom: 0.2rem;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #94a3b8;
    }

    .renter-history-item__stat-value {
        display: block;
        font-size: 0.8rem;
        font-weight: 700;
        color: #0f172a;
    }

    .renter-history-item__notes {
        font-size: 0.78rem;
        color: #475569;
        line-height: 1.45;
    }

    .renter-history-empty {
        padding: 1.35rem 1rem;
        text-align: center;
        color: #64748b;
        background: rgba(255, 255, 255, 0.78);
        border-radius: 16px;
    }

    .room-card__empty {
        color: rgba(0, 0, 0, 0.62);
        font-style: italic;
    }

    @media (max-width: 767.98px) {
        .floor-heading {
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            gap: 0.6rem;
        }

        .floor-meta {
            gap: 0.4rem;
        }

        .floor-count-wrap {
            grid-column: 1 / -1;
            justify-self: end;
        }

        .room-card {
            min-height: auto;
            padding: 0.8rem 0.75rem 0.82rem 0.8rem;
            border-radius: 16px;
        }

        .room-card__header {
            flex-direction: column;
            padding-right: 0;
            gap: 0.4rem;
            margin-bottom: 0.38rem;
        }

        .room-card__header-side {
            align-items: flex-start;
            gap: 0.28rem;
            flex: 0 0 auto;
            width: auto;
            max-width: 100%;
        }

        .room-card__category {
            width: auto;
            min-width: 0;
            max-width: calc(var(--room-action-row-width) + 0.75rem);
            font-size: 0.68rem;
            align-self: flex-start;
            padding: 0.42rem 0.72rem;
        }

        .room-card__quick-actions {
            justify-content: flex-start;
            width: auto;
            gap: 0.25rem;
        }

        .room-card__tenant {
            font-size: 0.8rem;
        }

        .room-card__period,
        .room-card__notes {
            font-size: 0.74rem;
        }

        .renter-detail-layout {
            grid-template-columns: 1fr;
        }

        .renter-detail-grid {
            grid-template-columns: 1fr;
        }

        .renter-history-item__stats {
            grid-template-columns: 1fr;
        }
    }
</style>
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="row">
                <div class="col">
                    <h4 class="page-title">Daftar Kamar</h4>
                    <span>{{config('app.name')}}</span>
                </div><!--end col-->
                <div class="col-auto align-self-center">
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-sm btn-success waves-effect waves-light dropdown-toggle" type="button" id="bt_tambah_sewa" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="mdi mdi-plus"></i> Tambah Sewa
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <button class="dropdown-item" type="button" data-toggle="modal" data-target="#md_sewa" id="bt_sewa">
                                <i class="mdi mdi-check-all mr-1"></i> Sewa Kamar
                            </button>
                            <button class="dropdown-item" type="button" data-toggle="modal" data-target="#md_extra" id="bt_extra">
                                <i class="mdi mdi-plus mr-1"></i> Tambahan Sewa
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-warning waves-effect waves-light ml-2 rooms-action-button-badge" data-toggle="modal" data-target="#md_unpaid_transactions" id="bt_belum_lunas">
                        <i class="mdi mdi-cash-clock mr-1"></i> Belum Lunas
                        <span class="rooms-action-button-badge__count" id="bt_belum_lunas_count">0</span>
                    </button>
                </div><!--end col-->
            </div><!--end row-->
        </div><!--end page-title-box-->
    </div><!--end col-->
</div><!--end row-->
{{-- <div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-dark">
                <div class="row align-self-center">
                    <div class="col align-self-center">
                        <h4 class="card-title text-white">Drawing Kamar</h4>
                    </div>
                    <div class="col-auto align-self-center">
                        <a href="#" class="btn btn-sm btn-light waves-effect waves-light dropdown-toggle" data-toggle="dropdown">
                            <i class="far fa-file-alt"></i> Export <i class="las la-angle-down "></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-bottom side-color side-color-dark">
                            <a class="dropdown-item btn_exls" href="#">Excel</a>
                            <a class="dropdown-item btn_epdf" href="#">PDF</a>
                            <a class="dropdown-item btn_eprint" href="#">Print</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="minimap">
                    <!-- Example rooms -->
                    <div class="room" style="top: 20px; left: 5px;"></div> <!-- Room 1 -->
                    <div class="room" style="top: 20px; left: 35px;"></div> <!-- Room 2 -->
                    <div class="room" style="top: 20px; left: 65px;"></div> <!-- Room 3 -->
                    <div class="room" style="top: 60px; left: 5px;"></div> <!-- Room 4 -->
                    <div class="room" style="top: 60px; left: 35px;"></div> <!-- Room 5 -->
                    <div class="room" style="top: 60px; left: 65px;"></div> <!-- Room 6 -->
                    <div class="room" style="top: 100px; left: 5px;"></div> <!-- Room 7 -->
                    <div class="room" style="top: 100px; left: 35px;"></div> <!-- Room 8 -->
                    <div class="room" style="top: 100px; left: 65px;"></div> <!-- Room 9 -->
                </div>
                <div class="tab-content" id="files-tabContent">
                    <div class="tab-pane fade show active" id="files-projects">
                        <h4 class="card-title mt-0 mb-3">Lantai 1</h4>
                        <div class="file-box-content">
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information  file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="fas fa-home text-warning"></i>
                                    <h6 class="text-truncate">C3</h6>
                                    <small class="text-muted">Kosong</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="fas fa-home text-warning"></i>
                                    <h6 class="text-truncate">C2</h6>
                                    <small class="text-muted">Kosong</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="fas fa-home text-warning"></i>
                                    <h6 class="text-truncate">C1</h6>
                                    <small class="text-muted">Kosong</small>
                                </div>
                            </div>
                        </div>
                        <br class="mb-5">
                        <h4 class="card-title mt-0 mb-3">Lantai 2</h4>
                        <div class="file-box-content">
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information  file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="fas fa-home text-warning"></i>
                                    <h6 class="text-truncate">C3</h6>
                                    <small class="text-muted">Kosong</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="fas fa-home text-warning"></i>
                                    <h6 class="text-truncate">C2</h6>
                                    <small class="text-muted">Kosong</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="fas fa-home text-warning"></i>
                                    <h6 class="text-truncate">C1</h6>
                                    <small class="text-muted">Kosong</small>
                                </div>
                            </div>
                        </div>
                        <br class="mb-5">
                        <div class="file-box-content">
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information  file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="fas fa-home text-warning"></i>
                                    <h6 class="text-truncate">C3</h6>
                                    <small class="text-muted">Kosong</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="fas fa-home text-warning"></i>
                                    <h6 class="text-truncate">C2</h6>
                                    <small class="text-muted">Kosong</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="fas fa-home text-warning"></i>
                                    <h6 class="text-truncate">C1</h6>
                                    <small class="text-muted">Kosong</small>
                                </div>
                            </div>
                        </div>
                    </div><!--end tab-pane-->

                    <div class="tab-pane fade" id="files-pdf">
                        <h4 class="mt-0 card-title mb-3">PDF Files</h4>
                        <div class="file-box-content">
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-info"></i>
                                    <h6 class="text-truncate">Admin_Panel</h6>
                                    <small class="text-muted">06 March 2019 / 5MB</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-danger"></i>
                                    <h6 class="text-truncate">Ecommerce.pdf</h6>
                                    <small class="text-muted">15 March 2019 / 8MB</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-warning"></i>
                                    <h6 class="text-truncate">Payment_app.zip</h6>
                                    <small class="text-muted">11 April 2019 / 10MB</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-secondary"></i>
                                    <h6 class="text-truncate">App_landing_001.pdf</h6>
                                    <small class="text-muted">06 March 2019 / 5MB</small>
                                </div>
                            </div>
                        </div>
                    </div><!--end tab-pane-->

                    <div class="tab-pane fade" id="files-documents">
                        <h4 class="mt-0 card-title mb-3">Documents</h4>
                        <div class="file-box-content">
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-info"></i>
                                    <h6 class="text-truncate">Adharcard_update</h6>
                                    <small class="text-muted">06 March 2019 / 5MB</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-danger"></i>
                                    <h6 class="text-truncate">Pancard</h6>
                                    <small class="text-muted">15 March 2019 / 8MB</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-warning"></i>
                                    <h6 class="text-truncate">ICICI_statment</h6>
                                    <small class="text-muted">11 April 2019 / 10MB</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-secondary"></i>
                                    <h6 class="text-truncate">March_Invoice</h6>
                                    <small class="text-muted">06 March 2019 / 5MB</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <h4 class="card-title my-3">Company Documents</h4>
                            </div>
                        </div>
                        <div class="file-box-content">
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-download file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-success"></i>
                                    <h6 class="text-truncate">Adharcard_update</h6>
                                    <small class="text-muted">06 March 2019 / 5MB</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-download file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-pink"></i>
                                    <h6 class="text-truncate">Pancard</h6>
                                    <small class="text-muted">15 March 2019 / 8MB</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-download file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-purple"></i>
                                    <h6 class="text-truncate">ICICI_statment</h6>
                                    <small class="text-muted">11 April 2019 / 10MB</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <h4 class="card-title my-3">Personal Documents</h4>
                            </div>
                        </div>
                        <div class="file-box-content">
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-download file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-blue"></i>
                                    <h6 class="text-truncate">Adharcard_update</h6>
                                    <small class="text-muted">06 March 2019 / 5MB</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-download file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-dark"></i>
                                    <h6 class="text-truncate">Pancard</h6>
                                    <small class="text-muted">15 March 2019 / 8MB</small>
                                </div>
                            </div>
                        </div>
                    </div><!--end tab-pen-->

                    <div class="tab-pane fade" id="files-hide">
                        <h4 class="mt-0 card-title mb-3">Hide</h4>
                    </div><!--end tab-pane-->
                </div>
            </div>
        </div>
    </div>
</div> --}}
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-dark">
                <div class="row align-self-center">
                    <div class="col align-self-center">
                        <h4 class="card-title text-white room-stats-title">
                            <span class="room-stats-pill room-stats-pill--occupied js-room-stats-filter" data-filter="occupied" tabindex="0" role="button" aria-pressed="false" title="Filter kamar terisi">Terisi <strong id="rooms_stat_occupied">0</strong></span>
                            <span class="room-stats-pill room-stats-pill--vacant js-room-stats-filter" data-filter="vacant" tabindex="0" role="button" aria-pressed="false" title="Filter kamar kosong">Kosong <strong id="rooms_stat_vacant">0</strong></span>
                            <span class="room-stats-pill room-stats-pill--pending js-room-stats-filter" data-filter="pending" tabindex="0" role="button" aria-pressed="false" title="Filter kamar pending">Pending <strong id="rooms_stat_pending">0</strong></span>
                        </h4>
                    </div>
                    <div class="col-auto align-self-center rooms-toolbar">
                        <div class="rooms-toolbar__group">
                            <button class="btn btn-sm btn-danger waves-effect waves-light rooms-toolbar__icon" data-toggle="modal" data-target="#md_filter" id="bt_filter" title="Tambah Kamar" aria-label="Tambah Kamar">
                                <i class="mdi mdi-plus"></i>
                            </button>
                            <button class="btn btn-sm btn-secondary waves-effect waves-light rooms-toolbar__icon" data-toggle="modal" data-target="#md_deleted" title="Kamar Dihapus" aria-label="Kamar Dihapus">
                                <i class="mdi mdi-trash-can"></i>
                            </button>
                            <div class="dropdown">
                                <a href="#" class="btn btn-sm btn-light waves-effect waves-light dropdown-toggle rooms-toolbar__icon" data-toggle="dropdown" title="Export" aria-label="Export">
                                    <i class="far fa-file-alt"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right dropdown-menu-bottom side-color side-color-dark">
                                    <a class="dropdown-item btn_exls" href="#">Excel</a>
                                    <a class="dropdown-item btn_epdf" href="#">PDF</a>
                                    <a class="dropdown-item btn_eprint" href="#">Print</a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="rooms_sections">
                    <div class="alert alert-outline-secondary mb-0">Memuat data kamar...</div>
                </div>

                <div class="d-none">
                    <div id="button_export"></div>
                    <table class="table table-sm table-hover mb-0 dataTable no-footer" id="tb_kamar">
                        <thead class="thead-info bg-info">
                            <tr class="text-white">
                                <th class="text-center text-white">No</th>
                                <th class="text-center text-white">Nomor Kamar</th>
                                <th class="text-center text-white">Lantai</th>
                                <th class="text-white">Tipe Kamar</th>
                                <th class="text-white">Penyewa</th>
                                <th class="text-white">Periode</th>
                                <th class="text-white">Durasi</th>
                                <th class="text-white">Catatan</th>
                                <th class="text-white hidden">Status</th>
                                <th class="text-white hidden">Order</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="tb_kamar_body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="md_sewa" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white">Sewa Kamar</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{ route('bcl.rooms.sewa') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 col-sm-12">
                            <label class="">Penyewa</label>
                            <select class="mb-3 select2" name="renter" id="sewa_renter" required style="width: 100%" data-placeholder="Pilih Penyewa">
                                <option value="">Memuat penyewa...</option>
                            </select>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <label class="">No/Nama Kamar</label>
                            <select class="mb-3 select2" id="kamar" name="kamar" required style="width: 100%" data-placeholder="Pilih Kamar">
                                <option value="">Memuat kamar...</option>
                            </select>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <label class="">Durasi Sewa</label>
                            <select class="mb-3 select2" id="pricelist" name="pricelist" required style="width: 100%" data-placeholder="Pilih Durasi">
                                <option value=""></option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-4 col-sm-12">
                            <label class="">Tanggal Rencana Masuk</label>
                            <input type="text" id="tgl_masuk" required name="tgl_masuk" class="form-control datePicker">
                        </div>
                        <div class="col-md-8 col-sm-12">
                            <label class="">Catatan</label>
                            <input type="text" id="catatan" name="catatan" class="form-control">
                        </div>
                    </div>
                    <hr class="hr-dashed">
                    <div class="row mt-3">
                        <div class="col-md-4 col-sm-12">
                            <label class="">Tanggal Terima Pembayaran</label>
                            <input type="text" id="tgl_bayar" required name="tgl_bayar" class="form-control datePicker">
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <label class="">Nominal</label>
                            <input type="text" id="nominal" required name="nominal" class="form-control inputmask">
                            <small class="form-text text-muted">*Jika pembayaran kurang dari harga, maka akan dianggap sebagai DP</small>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <label class="">Deposit Penyewa</label>
                            <div class="input-group mb-2">
                                <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                <input type="text" id="renter_deposit_display" class="form-control text-right" readonly value="0">
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="use_deposit" name="use_deposit">
                                <label class="form-check-label" for="use_deposit">Gunakan deposit untuk pembayaran</label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" value="1" id="overpay_to_deposit" name="overpay_to_deposit" style="display:none;">
                                <label class="form-check-label" for="overpay_to_deposit" id="overpay_to_deposit_label" style="display:none; margin-left:6px;">Simpan kelebihan pembayaran ke deposit</label>
                            </div>
                            <div class="input-group mt-2" id="deposit_amount_row" style="display:none;">
                                <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                <input type="text" id="deposit_amount" name="deposit_amount" class="form-control inputmask text-right" value="0">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="btn_topup_deposit">Top-up</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="md_extra" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white">Tambahan Sewa</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{ route('bcl.extrarent.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <label class="">Item</label>
                            <select class="mb-3 select2" name="pricelist" id="pricelist_extra" required style="width: 100%" data-placeholder="Pilih">
                                <option value="">Memuat item...</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Tanggal Sewa</label>
                            <input type="text" id="tgl_sewa" required name="tgl_sewa" class="form-control datePicker">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6 col-sm-12">
                            <label class="">Jumlah Item</label>
                            <input type="text" id="jml_item" required name="jml_item" class="form-control inputmask">
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Lama Sewa</label>
                            <input type="text" id="lama_sewa" required name="lama_sewa" data-inputmask-suffix="" class="form-control inputmask">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-sm-12">
                            <label class="">Transaksi Penyewa</label>
                            <select class="mb-3 select2" name="trans_id" id="trans_id" required style="width: 100%" data-placeholder="Pilih">
                                <option value="">Memuat transaksi...</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="md_unpaid_transactions" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h6 class="modal-title m-0 text-white">Transaksi Belum Lunas</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times text-white"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive-sm">
                    <table class="table table-sm mb-0 table-hover">
                        <thead class="thead-secondary bg-light">
                            <tr>
                                <th width="25">No</th>
                                <th>Tanggal</th>
                                <th>Nomor</th>
                                <th>Tipe</th>
                                <th>Catatan</th>
                                <th class="text-right">Nominal</th>
                                <th class="text-right">Kurang</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="unpaid_transactions_body">
                            <tr>
                                <td colspan="8" class="text-center text-muted">Memuat transaksi belum lunas...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="md_room_income_payment" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h6 class="modal-title m-0 text-white">Penerimaan Pembayaran</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times text-white"></i></span>
                </button>
            </div>
            <form method="POST" action="{{ route('bcl.income.store') }}">
                @csrf
                <input type="hidden" id="room_income_section" name="section" value="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-6 col-xs-12">
                            <div class="form-group">
                                <label for="room_income_transaksi">Pilih Transaksi</label>
                                <select class="select2" name="transaksi" id="room_income_transaksi" required style="width: 100%">
                                    <option value="" selected>Memuat transaksi...</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xs-12">
                            <div class="form-group">
                                <label for="room_income_tgl_transaksi">Tanggal</label>
                                <input id="room_income_tgl_transaksi" name="tgl_transaksi" autocomplete="off" required type="text" class="form-control datePicker">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 col-xs-12">
                            <div class="form-group">
                                <label for="room_income_nominal">Nominal</label>
                                <input id="room_income_nominal" name="nominal" autocomplete="off" required type="text" class="form-control inputmask">
                            </div>
                        </div>
                        <div class="col-sm-6 col-xs-12">
                            <div class="form-group">
                                <label for="room_income_keterangan">Keterangan</label>
                                <input id="room_income_keterangan" name="keterangan" required autocomplete="off" type="text" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="md_filter" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog " role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Tambah Kamar</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{route('bcl.rooms.store')}}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <label class="">No/Nama Kamar</label>
                            <input type="text" name="no_kamar" required class="form-control">
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Tipe Kamar</label>
                            <select class="mb-3 form-control select2 " name="kategori" id="create_kategori" required style="width: 100%" data-placeholder="Pilih Kategori">
                                <option value="">Memuat kategori...</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Lantai</label>
                            <select name="floor" required class="form-control select2" style="width: 100%" data-placeholder="Pilih Lantai">
                                <option value=""></option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                            </select>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <label class="">Catatan</label>
                            <input type="text" name="catatan" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="md_edit" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog " role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Edit Kamar</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{route('bcl.rooms.update')}}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <input type="hidden" name="id" id="id_kamar">
                        <div class="col-md-6 col-sm-12">
                            <label class="">No/Nama Kamar</label>
                            <input type="text" name="no_kamar" id="no_kamar" required class="form-control">
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Tipe Kamar</label>
                            <select class="mb-3 form-control select2 " id="kategori" name="kategori" required style="width: 100%" data-placeholder="Pilih Kategori">
                                <option value="">Memuat kategori...</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Lantai</label>
                            <select name="floor" id="floor" required class="form-control select2" style="width: 100%" data-placeholder="Pilih Lantai">
                                <option value=""></option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                            </select>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <label class="">Catatan</label>
                            <input type="text" name="catatan" id="notes" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="md_deleted" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Kamar Dihapus</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <div class="model-body">
                <div class="row">
                    <div class="col-sm-12">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kamar</th>
                                    <th>Lantai</th>
                                    <th>Catatan</th>
                                    <th>Dihapus pada</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="deleted_rooms_body">
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Memuat data kamar dihapus...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
<div class="modal fade" id="md_room_wifi" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white">Informasi WiFi Kamar</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form id="room_wifi_form">
                @csrf
                <input type="hidden" id="wifi_record_id" name="id">
                <input type="hidden" id="wifi_room_id" name="room_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <span class="room-modal-label">Kamar</span>
                        <div class="room-modal-value" id="wifi_room_name">-</div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="wifi_ssid_input">SSID</label>
                            <input type="text" class="form-control" id="wifi_ssid_input" name="ssid" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="wifi_password_input">Password</label>
                            <input type="text" class="form-control" id="wifi_password_input" name="password">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="wifi_notes_input">Catatan</label>
                        <textarea class="form-control" id="wifi_notes_input" name="notes" rows="3"></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="wifi_active_input" name="active" checked>
                            <label class="form-check-label" for="wifi_active_input">WiFi aktif</label>
                        </div>
                    </div>
                    <div id="wifi_form_feedback" class="room-modal-feedback"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="wifi_save_btn">Simpan WiFi</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="md_room_history" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white">Riwayat Transaksi Kamar</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3 room-modal-value" id="history_room_name">-</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Transaksi</th>
                                <th>Penyewa</th>
                                <th>Periode</th>
                                <th>Total</th>
                                <th>Dibayar</th>
                                <th>Catatan</th>
                                <th class="text-right">Nota</th>
                            </tr>
                        </thead>
                        <tbody id="history_body">
                            <tr>
                                <td colspan="7" class="room-history-empty">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="md_room_booking" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white">Antrian Booking Kamar</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3 room-modal-value" id="booking_room_name">-</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Transaksi</th>
                                <th>Penyewa</th>
                                <th>Rentang Booking</th>
                                <th>Total</th>
                                <th>Dibayar</th>
                                <th>Catatan</th>
                                <th class="text-right">Nota</th>
                            </tr>
                        </thead>
                        <tbody id="booking_body">
                            <tr>
                                <td colspan="7" class="room-booking-empty">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="md_room_refund" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white">Refund Transaksi Kamar</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{route('bcl.transaksi.refund')}}" method="POST">
                @csrf
                <input type="hidden" name="kode_trans" id="refund_trans_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <span class="room-modal-label">Kamar</span>
                        <div class="room-modal-value" id="refund_room_name">-</div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="refund_date">Tgl. Refund</label>
                            <input type="date" id="refund_date" name="tgl_refund" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="refund_amount">Nominal Refund</label>
                            <input type="number" min="0" step="0.01" id="refund_amount" name="nominal_refund" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="refund_checkout_date">Tanggal Keluar</label>
                            <input type="date" id="refund_checkout_date" name="tgl_keluar" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="refund_reason">Alasan</label>
                            <input type="text" id="refund_reason" name="alasan" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-sm">Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="md_change_room" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h6 class="modal-title m-0 text-white">Pindah Kamar</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{ route('bcl.transaksi.change_room') }}" method="POST" id="form_change_room">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="trans_id" id="change_room_trans_id">
                    <input type="hidden" name="current_room_id" id="current_room_id">
                    <input type="hidden" name="payment_amount" id="payment_amount_hidden">
                    <input type="hidden" name="payment_type" id="payment_type_hidden">
                    <input type="hidden" name="payment_total_due" id="payment_total_due_hidden">
                    <input type="hidden" name="remaining_due" id="remaining_due_hidden" value="0">
                    <input type="hidden" name="pay_now_hidden" id="pay_now_hidden" value="0">

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <label for="new_room_id">Pilih Kamar Baru</label>
                            <select name="new_room_id" id="new_room_id" class="form-control" required>
                                <option value="">-- Loading opsi kamar --</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <label for="effective_date">Tanggal Pindah Kamar</label>
                            <input type="text" name="effective_date" id="effective_date" class="form-control" required placeholder="Pilih Tanggal">
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <label for="payment_date">Tanggal Pembayaran/Refund</label>
                            <input type="text" name="payment_date" id="payment_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <div class="form-group row" id="payment_input_row">
                        <div class="col-lg-12">
                            <label for="pay_now">Bayar Sekarang</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input type="number" min="0" step="0.01" name="pay_now" id="pay_now" class="form-control text-right" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info payment-info mb-0">
                        <p class="mb-1">Perhitungan berdasarkan paket penuh dan proporsi sisa durasi.</p>
                        <table class="table table-sm mb-2">
                            <tr><td width="40%">Paket Lama</td><td id="old_package_price">Rp 0</td></tr>
                            <tr><td>Paket Baru</td><td id="new_package_price">Rp 0</td></tr>
                            <tr><td>Selisih Paket</td><td id="diff_full">Rp 0</td></tr>
                            <tr><td>Durasi Total</td><td id="total_units">-</td></tr>
                            <tr><td>Sudah Terpakai</td><td id="elapsed_units">-</td></tr>
                            <tr><td>Sisa</td><td id="remaining_units">-</td></tr>
                            <tr class="font-weight-bold"><td>Proporsi Sisa</td><td id="remaining_percent">0%</td></tr>
                        </table>

                        <h6 class="mb-2 text-primary">Status Pembayaran Saat Ini:</h6>
                        <table class="table table-sm mb-2">
                            <tr><td width="40%">Total Paket Lama</td><td id="old_total_package_text">Rp 0</td></tr>
                            <tr><td>Sudah Dibayar</td><td id="already_paid_text" class="text-success">Rp 0</td></tr>
                            <tr class="font-weight-bold"><td>Kurang (Belum Lunas)</td><td id="outstanding_old_text" class="text-danger">Rp 0</td></tr>
                        </table>

                        <h6 class="mb-2 text-warning">Tagihan Pindah Kamar:</h6>
                        <table class="table table-sm mb-2">
                            <tr class="font-weight-bold"><td width="40%">Tagihan / Refund</td><td id="payment_amount_text">Rp 0</td></tr>
                            <tr><td>Bayar Sekarang</td><td id="pay_now_text" class="text-success">Rp 0</td></tr>
                            <tr class="font-weight-bold"><td>Sisa Tagihan</td><td id="remaining_due_text" class="text-danger">Rp 0</td></tr>
                            <tr class="font-weight-bold border-top"><td>Total Yang Harus Dibayar</td><td id="total_due_now_text" class="text-primary">Rp 0</td></tr>
                        </table>

                        <div id="payment_type_text" class="mt-1 font-weight-bold"></div>
                        <div id="refund_options_row" style="display:none;">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" value="1" id="refund_to_deposit" name="refund_to_deposit">
                                <label class="form-check-label" for="refund_to_deposit">Tambahkan refund ke deposit penyewa</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="md_topup_deposit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white">Top-up Deposit</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{ route('bcl.deposit.topup') }}" method="POST">
                @csrf
                <input type="hidden" name="renter" id="topup_renter_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Jumlah Top-up (Rp)</label>
                        <input type="text" name="amount" id="topup_amount" class="form-control inputmask text-right" required>
                    </div>
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="text" name="tgl_transaksi" value="{{ date('Y-m-d') }}" class="form-control datePicker" required>
                    </div>
                    <div class="form-group">
                        <label>Catatan (opsional)</label>
                        <input type="text" name="note" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Top-up</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="md_room_pricelist" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white">Pricelist Kamar</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3 room-modal-value" id="pricelist_room_category">-</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Durasi</th>
                                <th class="text-right">Harga</th>
                            </tr>
                        </thead>
                        <tbody id="pricelist_body">
                            <tr>
                                <td colspan="2" class="room-pricelist-empty">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="md_renter_detail" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white">Detail Penyewa</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="renter-detail-layout">
                    <div class="renter-detail-panel">
                        <h6 class="renter-detail-section-title">Identitas Penyewa</h6>
                        <div class="d-flex align-items-center mb-4">
                            <div id="renter_detail_photo_wrap" class="mr-3">
                                <div class="renter-detail-photo-placeholder">?</div>
                            </div>
                            <div>
                                <div class="room-modal-label">Nama Penyewa</div>
                                <div class="room-modal-value" id="renter_detail_name">-</div>
                            </div>
                        </div>
                        <div class="renter-detail-grid mb-4">
                            <div>
                                <div class="room-modal-label">Alamat</div>
                                <div class="room-modal-value" id="renter_detail_address">-</div>
                            </div>
                            <div>
                                <div class="room-modal-label">Tanggal Lahir</div>
                                <div class="room-modal-value" id="renter_detail_birthday">-</div>
                            </div>
                            <div>
                                <div class="room-modal-label">No. HP</div>
                                <div class="room-modal-value" id="renter_detail_phone">-</div>
                            </div>
                            <div>
                                <div class="room-modal-label">No. HP Cadangan</div>
                                <div class="room-modal-value" id="renter_detail_phone2">-</div>
                            </div>
                            <div>
                                <div class="room-modal-label">Jenis Identitas</div>
                                <div class="room-modal-value" id="renter_detail_identity_type">-</div>
                            </div>
                            <div>
                                <div class="room-modal-label">Nomor Identitas</div>
                                <div class="room-modal-value" id="renter_detail_identity_number">-</div>
                            </div>
                            <div>
                                <div class="room-modal-label">Kendaraan</div>
                                <div class="room-modal-value" id="renter_detail_vehicle">-</div>
                            </div>
                            <div>
                                <div class="room-modal-label">No. Polisi</div>
                                <div class="room-modal-value" id="renter_detail_nopol">-</div>
                            </div>
                            <div>
                                <div class="room-modal-label">Kamar Aktif</div>
                                <div class="room-modal-value" id="renter_detail_room">-</div>
                            </div>
                            <div>
                                <div class="room-modal-label">Deposit</div>
                                <div class="room-modal-value" id="renter_detail_deposit">-</div>
                            </div>
                        </div>
                        <div>
                            <div class="room-modal-label mb-2">Dokumen</div>
                            <div class="renter-detail-docs" id="renter_detail_documents">
                                <span class="renter-detail-doc-empty">Belum ada dokumen.</span>
                            </div>
                        </div>
                    </div>
                    <div class="renter-detail-panel renter-detail-panel--history">
                        <h6 class="renter-detail-section-title">Riwayat Transaksi</h6>
                        <div class="renter-history-list" id="renter_history_list">
                            <div class="renter-history-empty">Memuat riwayat transaksi...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('pagescript')
<script>
    const roomsDataUrl = "{{ route('bcl.rooms.data') }}";
    const roomFormDataUrl = "{{ route('bcl.rooms.form_data') }}";
    const roomDeletedDataUrl = "{{ route('bcl.rooms.deleted_data') }}";
    const roomUnpaidDataUrl = "{{ route('bcl.rooms.unpaid_data') }}";
    const roomWifiUrlTemplate = "{{ route('bcl.rooms.wifi', ':id') }}";
    const roomHistoryUrlTemplate = "{{ route('bcl.rooms.history', ':id') }}";
    const roomBookingUrlTemplate = "{{ route('bcl.rooms.booking_queue', ':id') }}";
    const roomChangeOptionsUrlTemplate = "{{ route('bcl.transaksi.change_room.options', ':id') }}";
    const roomPricelistUrlTemplate = "{{ route('bcl.pricelist.get_pl_room', ':id') }}";
    const renterDetailUrlTemplate = "{{ route('bcl.renter.detail', ':id') }}";
    const roomWifiStoreUrl = "{{ route('bcl.roomwifi.store') }}";
    const roomWifiUpdateUrlTemplate = "{{ route('bcl.roomwifi.update', ':id') }}";
    const transactionPrintUrlTemplate = "{{ route('bcl.transaksi.cetak', ':id') }}";
    const transactionShowUrlTemplate = "{{ route('bcl.transaksi.show', ':id') }}";
    const roomCategoryBadgeClasses = {
        'standard room': 'room-card__category--green',
        'superior room': 'room-card__category--blue',
        'deluxe room': 'room-card__category--amber',
        'sunset view deluxe room': 'room-card__category--orange',
        'suite room': 'room-card__category--purple',
        'family room': 'room-card__category--teal',
    };

    let roomsDashboardPayload = null;
    let table_bb = null;
    let roomsDashboardPromise = null;
    let roomFormDataPromise = null;
    let roomDeletedDataPromise = null;
    let roomUnpaidDataPromise = null;
    let roomExportReady = false;
    let activeRoomFilter = null;
    let roomUnpaidPayload = null;

    function roomActionUrl(template, id) {
        return template.replace(':id', id);
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function roomNameDisplay(roomName) {
        const value = (roomName || '').toString().trim();

        if (!value) {
            return '-';
        }

        if (value.length <= 3) {
            return value.toUpperCase();
        }

        const parts = value.split(/\s+/).filter(Boolean);

        if (parts.length > 1) {
            return parts.map(function(part) {
                return part.charAt(0).toUpperCase();
            }).join('').slice(0, 3);
        }

        return value.slice(0, 3).toUpperCase();
    }

    function getCategoryBadgeClass(categoryName) {
        return roomCategoryBadgeClasses[((categoryName || '').toString().trim().toLowerCase())] || 'room-card__category--green';
    }

    function getRoomStatusMeta(room) {
        const isOccupied = !!room.is_occupied;
        const hasBookingQueue = !!room.has_booking_queue;
        const tenantName = room.nama || 'Kosong';
        const kurang = parseFloat(room.kurang || 0) > 0;
        let status = 'Kosong';
        let order = 1;
        let badgeClass = 'warning';
        let periodText = '';
        let periodHtml = 'Belum ada penyewa aktif';
        let durationText = '';
        let alertHtml = '';

        if (isOccupied) {
            status = 'Terisi';
            order = 2;
            badgeClass = 'success';
            durationText = [room.lama_sewa, room.jangka_sewa].filter(Boolean).join(' ');
            periodText = [room.tgl_mulai || '-', 's/d', room.tgl_selesai || '-'].join(' ');
            periodHtml = escapeHtml(periodText + (durationText ? ' (' + durationText + ')' : ''));

            if (room.tgl_selesai && typeof moment === 'function') {
                const selesai = moment(room.tgl_selesai, 'YYYY-MM-DD');
                const now = moment();
                const daysLeft = selesai.diff(now, 'days');

                if (daysLeft < 0) {
                    alertHtml = ' <i class="fas fa-exclamation-triangle faa faa-flash animated text-danger" title="Periode berakhir"></i>';
                } else if (daysLeft <= 7) {
                    alertHtml = ' <i class="fas fa-exclamation-triangle faa faa-flash animated text-warning" title="Periode hampir berakhir"></i>';
                }
            }
        }

        return {
            isOccupied: isOccupied,
            hasBookingQueue: hasBookingQueue,
            tenantName: tenantName,
            kurang: kurang,
            status: status,
            order: order,
            badgeClass: badgeClass,
            periodText: periodText,
            periodHtml: periodHtml,
            durationText: durationText,
            alertHtml: alertHtml,
        };
    }

    function renderRoomStats(stats) {
        $('#rooms_stat_occupied').text(stats && stats.occupied ? stats.occupied : 0);
        $('#rooms_stat_vacant').text(stats && stats.vacant ? stats.vacant : 0);
        $('#rooms_stat_pending').text(stats && stats.pending ? stats.pending : 0);
    }

    function getFilteredRooms(rooms) {
        const items = rooms || [];

        if (!activeRoomFilter) {
            return items;
        }

        return items.filter(function(room) {
            if (activeRoomFilter === 'occupied') {
                return !!room.is_occupied;
            }

            if (activeRoomFilter === 'pending') {
                return !!room.has_booking_queue;
            }

            if (activeRoomFilter === 'vacant') {
                return !room.is_occupied && !room.has_booking_queue;
            }

            return true;
        });
    }

    function updateRoomFilterState() {
        $('.js-room-stats-filter').each(function() {
            const isActive = $(this).data('filter') === activeRoomFilter;
            $(this)
                .toggleClass('is-active', isActive)
                .attr('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function applyRoomFilter(filterKey) {
        activeRoomFilter = activeRoomFilter === filterKey ? null : filterKey;
        updateRoomFilterState();
        renderRoomSections(getFilteredRooms((roomsDashboardPayload || {}).rooms || []));
    }

    function renderRoomSections(rooms) {
        const grouped = {};
        const sortedRooms = (rooms || []).slice().sort(function(left, right) {
            const leftFloor = left.floor == null ? 99 : parseInt(left.floor, 10);
            const rightFloor = right.floor == null ? 99 : parseInt(right.floor, 10);

            if (leftFloor !== rightFloor) {
                return leftFloor - rightFloor;
            }

            return (left.room_name || '').localeCompare(right.room_name || '');
        });

        sortedRooms.forEach(function(room) {
            const key = room.floor == null ? 'Tanpa Lantai' : String(room.floor);
            if (!grouped[key]) {
                grouped[key] = [];
            }
            grouped[key].push(room);
        });

        const floorKeys = Object.keys(grouped);

        if (!floorKeys.length) {
            $('#rooms_sections').html('<div class="alert alert-outline-warning mb-0">Belum ada data kamar.</div>');
            return;
        }

        const sectionsHtml = floorKeys.map(function(floor) {
            const floorRooms = grouped[floor];
            const floorTitle = /^\d+$/.test(floor) ? 'Lantai ' + floor : floor;
            const cardsHtml = floorRooms.map(function(room) {
                const statusMeta = getRoomStatusMeta(room);
                const categoryBadgeClass = getCategoryBadgeClass(room.category_name);
                const categoryLabel = (room.category_name || '-').length > 20 ? (room.category_name || '-').slice(0, 20) + '...' : (room.category_name || '-');
                const nextBookingRenter = statusMeta.hasBookingQueue ? (room.next_booking_renter || 'Booking') : '';
                const nextBookingDate = statusMeta.hasBookingQueue ? ((room.next_booking_start || '-') + ' s/d ' + (room.next_booking_end || '-')).trim() : '';
                const printUrl = room.trans_id ? roomActionUrl(transactionPrintUrlTemplate, room.trans_id) : '#';
                const bookingBadge = room.booking_count > 0 ? '<span class="room-card__quick-badge">' + escapeHtml(room.booking_count) + '</span>' : '';
                const moveDisabled = statusMeta.isOccupied ? '' : 'room-card__quick-btn--disabled';
                const historyWarning = statusMeta.kurang ? 'room-card__quick-btn--warning' : '';
                const historyTitle = statusMeta.kurang ? 'Riwayat Transaksi - Belum Lunas' : 'Riwayat Transaksi';
                const tenantHtml = statusMeta.isOccupied
                    ? '<p class="room-card__tenant">' +
                        '<i data-feather="user" class="room-card__icon"></i>' +
                        '<button type="button" class="room-card__tenant-name js-room-renter" data-renter-id="' + escapeHtml(room.id_renter) + '" data-renter-name="' + escapeHtml(statusMeta.tenantName) + '" title="Lihat detail penyewa ' + escapeHtml(statusMeta.tenantName) + '"><span class="room-card__text">' + escapeHtml(statusMeta.tenantName) + '</span></button>' +
                    '</p>' +
                    '<p class="room-card__period">' +
                        '<i data-feather="calendar" class="room-card__icon"></i>' +
                        '<a href="' + escapeHtml(printUrl) + '" target="_blank" rel="noopener noreferrer" class="room-card__period-link js-room-print" title="Cetak nota transaksi kamar ' + escapeHtml(room.room_name) + '">' +
                            '<span class="room-card__text">' + statusMeta.periodHtml + statusMeta.alertHtml + '</span>' +
                        '</a>' +
                    '</p>'
                    : '';
                const bookingHtml = statusMeta.hasBookingQueue
                    ? '<p class="room-card__period">' +
                        '<i data-feather="clock" class="room-card__icon"></i>' +
                        '<span class="room-card__text">' +
                            escapeHtml(room.booking_count) + ' Antrian Booking' +
                            (nextBookingRenter ? '<br><span class="text-muted">Terdekat: ' + escapeHtml(nextBookingRenter) + '</span>' : '') +
                            (nextBookingDate ? '<br><span class="text-muted">' + escapeHtml(nextBookingDate) + '</span>' : '') +
                        '</span>' +
                    '</p>'
                    : '';

                return '<article class="room-card room-card--editable ' + (statusMeta.isOccupied ? 'room-card--occupied' : (statusMeta.hasBookingQueue ? 'room-card--queued' : 'room-card--vacant')) + ' edit_kamar" data-id="' + escapeHtml(room.id) + '" tabindex="0" role="button" aria-label="Edit kamar ' + escapeHtml(room.room_name) + '">' +
                    '<div class="room-card__header">' +
                        '<h2 class="room-card__name" title="' + escapeHtml(room.room_name) + '">' + escapeHtml(roomNameDisplay(room.room_name)) + '</h2>' +
                        '<div class="room-card__header-side">' +
                            '<button type="button" class="room-card__category ' + categoryBadgeClass + ' js-room-pricelist" data-category-id="' + escapeHtml(room.room_category) + '" data-category-name="' + escapeHtml(room.category_name || '-') + '" title="Lihat pricelist ' + escapeHtml(room.category_name || '-') + '">' + escapeHtml(categoryLabel) + '</button>' +
                            '<div class="room-card__quick-actions">' +
                                '<button type="button" class="room-card__quick-btn js-room-action" data-action="wifi" data-room-id="' + escapeHtml(room.id) + '" data-room-name="' + escapeHtml(room.room_name) + '" title="Lihat WiFi"><i data-feather="wifi"></i></button>' +
                                '<div class="dropdown room-card__quick-dropdown js-room-menu">' +
                                    '<button type="button" class="room-card__quick-btn ' + moveDisabled + ' js-room-menu-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Aksi Kamar"><i data-feather="log-in"></i></button>' +
                                    '<div class="dropdown-menu dropdown-menu-right room-card__quick-menu">' +
                                        '<button type="button" class="dropdown-item js-room-action" data-action="move" data-room-id="' + escapeHtml(room.id) + '" data-room-name="' + escapeHtml(room.room_name) + '" data-trans-id="' + escapeHtml(room.trans_id || '') + '"><i data-feather="log-in"></i><span>Pindah Kamar</span></button>' +
                                        '<button type="button" class="dropdown-item js-room-action" data-action="refund" data-room-id="' + escapeHtml(room.id) + '" data-room-name="' + escapeHtml(room.room_name) + '" data-trans-id="' + escapeHtml(room.trans_id || '') + '"><i data-feather="corner-up-left"></i><span>Refund</span></button>' +
                                    '</div>' +
                                '</div>' +
                                '<button type="button" class="room-card__quick-btn room-card__quick-btn--history ' + historyWarning + ' js-room-action" data-action="history" data-room-id="' + escapeHtml(room.id) + '" data-room-name="' + escapeHtml(room.room_name) + '" title="' + escapeHtml(historyTitle) + '"><i data-feather="dollar-sign"></i></button>' +
                                '<button type="button" class="room-card__quick-btn room-card__quick-btn--booking js-room-action" data-action="booking" data-room-id="' + escapeHtml(room.id) + '" data-room-name="' + escapeHtml(room.room_name) + '" title="Antrian Booking"><i data-feather="clock"></i>' + bookingBadge + '</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="room-card__divider"></div>' +
                    tenantHtml +
                    bookingHtml +
                    '<p class="room-card__notes ' + (room.notes ? '' : 'room-card__empty') + '">' +
                        '<i data-feather="file-text" class="room-card__icon"></i>' +
                        '<span class="room-card__text">' + escapeHtml(room.notes || 'Belum ada catatan untuk kamar ini.') + '</span>' +
                    '</p>' +
                '</article>';
            }).join('');

            return '<section class="floor-section">' +
                '<div class="floor-heading">' +
                    '<div class="floor-line"></div>' +
                    '<div class="floor-meta"><h5 class="floor-title">' + escapeHtml(floorTitle) + '</h5></div>' +
                    '<div class="floor-line"></div>' +
                    '<div class="floor-count-wrap"><span class="floor-count">' + escapeHtml(floorRooms.length) + ' kamar</span></div>' +
                '</div>' +
                '<div class="rooms-grid">' + cardsHtml + '</div>' +
            '</section>';
        }).join('');

        $('#rooms_sections').html(sectionsHtml);

        if (window.feather && typeof window.feather.replace === 'function') {
            window.feather.replace();
        }
    }

    function renderExportRows(rooms) {
        const rowsHtml = (rooms || []).map(function(room, index) {
            const statusMeta = getRoomStatusMeta(room);
            const kurangHtml = statusMeta.kurang ? ' <span class="text-danger">(Belum Lunas)</span>' : '';
            const floor = room.floor == null ? '-' : room.floor;

            return '<tr>' +
                '<td class="text-center">' + (index + 1) + '</td>' +
                '<td class="text-center"><span class="badge badge-' + statusMeta.badgeClass + '">' + escapeHtml(room.room_name) + '</span></td>' +
                '<td class="text-center">' + escapeHtml(floor) + '</td>' +
                '<td>' + escapeHtml(room.category_name || '-') + '</td>' +
                '<td>' + escapeHtml(room.nama || '') + kurangHtml + '</td>' +
                '<td>' + escapeHtml(statusMeta.periodText) + statusMeta.alertHtml + '</td>' +
                '<td>' + escapeHtml(statusMeta.durationText) + '</td>' +
                '<td>' + escapeHtml(room.notes || '') + '</td>' +
                '<td class="hidden">' + escapeHtml(statusMeta.status) + '</td>' +
                '<td class="hidden">' + escapeHtml(statusMeta.order) + '</td>' +
                '<td></td>' +
            '</tr>';
        }).join('');

        $('#tb_kamar_body').html(rowsHtml);
    }

    function renderCategoryOptions(categories) {
        const options = ['<option value=""></option>'].concat((categories || []).map(function(category) {
            return '<option value="' + escapeHtml(category.id) + '">' + escapeHtml(category.name) + '</option>';
        }));

        $('#create_kategori').html(options.join(''));
        $('#kategori').html(options.join(''));
    }

    function renderRenters(renters) {
        const options = ['<option value=""></option>'].concat((renters || []).map(function(renter) {
            return '<option value="' + escapeHtml(renter.id) + '" data-deposit="' + escapeHtml(renter.deposit_balance || 0) + '">' + escapeHtml(renter.nama) + '</option>';
        }));

        $('#sewa_renter').html(options.join(''));
    }

    function renderBaseRooms(baseRooms) {
        const sewaOptions = ['<option value=""></option>'];
        const transactionOptions = ['<option value=""></option>'];

        (baseRooms || []).forEach(function(room) {
            const roomLabel = (room.floor ? 'Lantai ' + room.floor + ' - ' : '') + (room.room_name || '-') + ' ' + (room.category_name || '');
            sewaOptions.push('<option value="' + escapeHtml(room.id) + '" data-room_category="' + escapeHtml(room.room_category || '') + '">' + escapeHtml(roomLabel.trim()) + '</option>');

            if (room.renter && room.renter.trans_id) {
                transactionOptions.push('<option value="' + escapeHtml(room.renter.trans_id) + '">' + escapeHtml((room.room_name || '-') + ' (' + (room.renter.nama || '-') + ')') + '</option>');
            }
        });

        $('#kamar').html(sewaOptions.join(''));
        $('#trans_id').html(transactionOptions.join(''));
    }

    function renderExtraPricelistOptions(extraPricelist) {
        const options = ['<option value=""></option>'].concat((extraPricelist || []).map(function(item) {
            return '<option data-lama="' + escapeHtml(item.jangka_sewa) + '" value="' + escapeHtml(item.id) + '">' + escapeHtml(item.nama + ' (' + $.number(item.harga || 0, 2) + '/' + (item.jangka_sewa || '-') + ')') + '</option>';
        }));

        $('#pricelist_extra').html(options.join(''));
    }

    function renderDeletedRooms(deletedRooms) {
        if (!deletedRooms || !deletedRooms.length) {
            $('#deleted_rooms_body').html('<tr><td colspan="6" class="text-center text-muted">Belum ada kamar yang dihapus.</td></tr>');
            return;
        }

        const rows = deletedRooms.map(function(room, index) {
            return '<tr>' +
                '<td>' + (index + 1) + '</td>' +
                '<td>' + escapeHtml(room.room_name || '-') + '</td>' +
                '<td>' + escapeHtml(room.floor == null ? '-' : room.floor) + '</td>' +
                '<td>' + escapeHtml(room.notes || '') + '</td>' +
                '<td>' + escapeHtml(room.deleted_at || '-') + '</td>' +
                '<td class="text-right"><a href="' + escapeHtml(room.restore_url) + '" class="btn btn-xs btn-success">Aktifkan</a></td>' +
            '</tr>';
        }).join('');

        $('#deleted_rooms_body').html(rows);
    }

    function renderUnpaidTransactions(items) {
        if (!items || !items.length) {
            $('#unpaid_transactions_body').html('<tr><td colspan="8" class="text-center text-muted">Tidak ada transaksi belum lunas.</td></tr>');
            return;
        }

        const rows = items.map(function(item, index) {
            return '<tr>' +
                '<td>' + (index + 1) + '</td>' +
                '<td>' + escapeHtml(item.tanggal || '-') + '</td>' +
                '<td>' + escapeHtml(item.nomor || '-') + '</td>' +
                '<td>' + escapeHtml(item.tipe || '-') + '</td>' +
                '<td>' + escapeHtml(item.catatan || '-') + '</td>' +
                '<td class="text-right">Rp ' + $.number(item.jumlah || 0, 2) + '</td>' +
                '<td class="text-right text-danger font-weight-bold">Rp ' + $.number(item.kurang || 0, 2) + '</td>' +
                '<td class="text-right"><button type="button" class="btn btn-xs btn-success js-open-unpaid-payment" data-transaksi="' + escapeHtml(item.transaksi || '') + '" data-section="' + escapeHtml(item.section || '') + '" data-kurang="' + escapeHtml(item.kurang || 0) + '"><i class="mdi mdi-check"></i> Terima Pembayaran</button></td>' +
            '</tr>';
        }).join('');

        $('#unpaid_transactions_body').html(rows);
    }

    function renderUnpaidBadgeCount(items) {
        const count = (items || []).length;

        $('#bt_belum_lunas_count')
            .text(count)
            .toggleClass('is-visible', count > 0);
    }

    function populateUnpaidPaymentOptions(items) {
        const options = ['<option value=""></option>'].concat((items || []).map(function(item) {
            return '<option value="' + escapeHtml(item.transaksi || '') + '" data-section="' + escapeHtml(item.section || '') + '" data-kurang="' + escapeHtml(item.kurang || 0) + '">' + escapeHtml(item.option_label || item.transaksi || '') + '</option>';
        }));

        $('#room_income_transaksi').html(options.join(''));
    }

    function populateUnpaidTransactions(payload) {
        roomUnpaidPayload = payload || {};
        renderUnpaidBadgeCount(roomUnpaidPayload.items || []);
        renderUnpaidTransactions(roomUnpaidPayload.items || []);
        populateUnpaidPaymentOptions(roomUnpaidPayload.items || []);
    }

    function populateRoomsDashboard(payload) {
        roomsDashboardPayload = payload || {};
        renderRoomStats(roomsDashboardPayload.stats || {});
        updateRoomFilterState();
        renderRoomSections(getFilteredRooms(roomsDashboardPayload.rooms || []));
    }

    function populateRoomFormData(payload) {
        renderCategoryOptions(payload.categories || []);
        renderRenters(payload.renters || []);
        renderBaseRooms(payload.base_rooms || []);
        renderExtraPricelistOptions(payload.extra_pricelist || []);
    }

    function ensureRoomFormData() {
        if (roomFormDataPromise) {
            return roomFormDataPromise;
        }

        roomFormDataPromise = $.getJSON(roomFormDataUrl)
            .done(function(response) {
                populateRoomFormData(response || {});
            })
            .fail(function() {
                showRoomToast('Gagal memuat data form kamar.', 'error');
            });

        return roomFormDataPromise;
    }

    function ensureDeletedRoomsData() {
        if (roomDeletedDataPromise) {
            return roomDeletedDataPromise;
        }

        roomDeletedDataPromise = $.getJSON(roomDeletedDataUrl)
            .done(function(response) {
                renderDeletedRooms((response || {}).deleted || []);
            })
            .fail(function() {
                $('#deleted_rooms_body').html('<tr><td colspan="6" class="text-center text-danger">Gagal memuat data kamar dihapus.</td></tr>');
                showRoomToast('Gagal memuat data kamar dihapus.', 'error');
            });

        return roomDeletedDataPromise;
    }

    function ensureUnpaidTransactionsData(forceReload) {
        if (forceReload) {
            roomUnpaidDataPromise = null;
        }

        if (roomUnpaidDataPromise) {
            return roomUnpaidDataPromise;
        }

        roomUnpaidDataPromise = $.getJSON(roomUnpaidDataUrl)
            .done(function(response) {
                populateUnpaidTransactions(response || {});
            })
            .fail(function() {
                $('#unpaid_transactions_body').html('<tr><td colspan="8" class="text-center text-danger">Gagal memuat transaksi belum lunas.</td></tr>');
                showRoomToast('Gagal memuat transaksi belum lunas.', 'error');
            });

        return roomUnpaidDataPromise;
    }

    function ensureRoomExportTable() {
        if (roomExportReady) {
            return $.Deferred().resolve().promise();
        }

        const ensureDashboard = roomsDashboardPromise || loadRoomsDashboardData();

        return ensureDashboard.then(function() {
            renderExportRows((roomsDashboardPayload || {}).rooms || []);
            initializeRoomExportTable();
            roomExportReady = true;
        });
    }

    function initializeRoomExportTable() {
        if ($.fn.DataTable.isDataTable('#tb_kamar')) {
            $('#tb_kamar').DataTable().destroy();
            $('#button_export').empty();
        }

        table_bb = $('#tb_kamar').DataTable({
            order: [[9, 'asc']],
            paging: false,
            info: false,
            language: {
                emptyTable: 'Tidak ada data untuk ditampilkan, silakan gunakan filter',
            },
            rowGroup: {
                dataSrc: [function(row) {
                    return '<i class="fas fa-chevron-down"></i> ' + row[8];
                }],
                endRender: function(rows) {
                    return 'Total <span class="highlight text-dark">' + $.number(Math.ceil(rows.count()), 0) + ' Kamar</span>';
                }
            }
        });

        table_bb.on('order.dt search.dt', function() {
            let index = 1;

            table_bb.cells(null, 0, {
                search: 'applied',
                order: 'applied'
            }).every(function() {
                this.data(index++);
            });
        }).draw();

        const buttonCommon = {
            exportOptions: {
                format: {
                    body: function(data, row, column) {
                        if (column === 0) {
                            return data;
                        }

                        return column >= 7 && column <= 8
                            ? data.replace(/[(Rp ,)]|(&nbsp;|<([^>]+)>)/g, '')
                            : data.replace(/(&nbsp;|<([^>]+)>)/ig, '');
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(table_bb, {
            buttons: [
                $.extend(true, {}, buttonCommon, {
                    extend: 'excelHtml5',
                    filename: function() {
                        return 'Laporan Kamar ' + moment().format('YYYY-MM-DD');
                    },
                    title: function() {
                        return "{{config('app.name')}} \n Laporan Kamar";
                    },
                    messageTop: function() {
                        return '#Tgl Cetak: ' + moment().format('YYYY-MM-DD, HH:mm') + ' [{{Auth::user()->name}}]';
                    },
                    pageSize: 'A4',
                }),
                $.extend(true, {}, buttonCommon, {
                    extend: 'pdfHtml5',
                    filename: function() {
                        return 'Laporan Kamar ' + moment().format('YYYY-MM-DD');
                    },
                    title: "{{config('app.name')}} \n Laporan Kamar",
                    messageTop: '#Tgl Cetak: ' + moment().format('YYYY-MM-DD, HH:mm') + ' [{{Auth::user()->name}}]',
                    pageSize: 'A4',
                }),
                $.extend(true, {}, buttonCommon, {
                    extend: 'print',
                    title: '<span class="text-center"><h3 class="m-0 p-0">Belova</h3><h4 class="m-0 p-0">Laporan Kamar</h4></span>',
                    messageTop: '<b>#Tgl Cetak: ' + moment().format('YYYY-MM-DD, HH:mm') + ' [{{Auth::user()->name}}]</b><hr>',
                    pageSize: 'A4',
                })
            ]
        }).container().appendTo($('#button_export'));

        $('.btn_epdf').off('click').on('click', function() {
            $('.buttons-pdf').click();
        });
        $('.btn_exls').off('click').on('click', function() {
            $('.buttons-excel').click();
        });
        $('.btn_eprint').off('click').on('click', function() {
            $('.buttons-print').click();
        });

        table_bb.on('click', 'tbody tr:not(".dtrg-group")', function(e) {
            let classList = e.currentTarget.classList;

            if (!classList.contains('selected')) {
                table_bb.rows('.selected').nodes().each(function(row) {
                    row.classList.remove('selected');
                });
                classList.add('selected');
            }
        });
    }

    function loadRoomsDashboardData() {
        if (roomsDashboardPromise) {
            return roomsDashboardPromise;
        }

        roomsDashboardPromise = $.getJSON(roomsDataUrl)
            .done(function(response) {
                populateRoomsDashboard(response);
            })
            .fail(function() {
                $('#rooms_sections').html('<div class="alert alert-outline-danger mb-0">Gagal memuat data kamar.</div>');
                showRoomToast('Gagal memuat data halaman kamar.', 'error');
            });

        return roomsDashboardPromise;
    }

    function showRoomToast(message, icon) {
        if (typeof $.toast === 'function') {
            $.toast({
                text: message,
                heading: 'Info',
                position: 'top-center',
                hideAfter: 3500,
                icon: icon || 'info',
            });
            return;
        }

        alert(message);
    }

    function setWifiFormFeedback(message, type) {
        const $feedback = $('#wifi_form_feedback');

        if (!message) {
            $feedback.removeClass('room-modal-feedback--error room-modal-feedback--success').text('');
            return;
        }

        $feedback
            .removeClass('room-modal-feedback--error room-modal-feedback--success')
            .addClass(type === 'success' ? 'room-modal-feedback--success' : 'room-modal-feedback--error')
            .text(message);
    }

    function setWifiFormLoading(isLoading) {
        $('#wifi_ssid_input, #wifi_password_input, #wifi_notes_input, #wifi_active_input, #wifi_save_btn').prop('disabled', isLoading);
        $('#wifi_save_btn').text(isLoading ? 'Menyimpan...' : 'Simpan WiFi');
    }

    function setRefundDefaults(roomName, transId) {
        const today = new Date().toISOString().split('T')[0];

        $('#refund_room_name').text(roomName || '-');
        $('#refund_trans_id').val(transId || '');
        $('#refund_date').val(today);
        $('#refund_checkout_date').val(today);
        $('#refund_amount').val('');
        $('#refund_reason').val('');
    }

    function normalizeDatePickerValue(value) {
        if (!value) {
            return value;
        }

        if (typeof moment === 'function') {
            let parsed = moment(value, ['YYYY-MM-DD', 'MM/DD/YYYY', 'DD/MM/YYYY', moment.ISO_8601], true);

            if (!parsed.isValid()) {
                parsed = moment(value);
            }

            if (parsed.isValid()) {
                return parsed.format('YYYY-MM-DD');
            }
        }

        const parts = value.split(/[-\/]/);

        if (parts.length === 3) {
            if (parts[0].length === 4) {
                return parts[0] + '-' + parts[1].padStart(2, '0') + '-' + parts[2].padStart(2, '0');
            }

            return parts[2] + '-' + parts[0].padStart(2, '0') + '-' + parts[1].padStart(2, '0');
        }

        return value;
    }

    let changeRoomData = { transaction: null, options: [] };

    function loadChangeRoomOptions(transId) {
        return $.getJSON(roomActionUrl(roomChangeOptionsUrlTemplate, transId));
    }

    function loadTransaction(transId) {
        return $.getJSON(roomActionUrl(transactionShowUrlTemplate, transId));
    }

    function sumPaidJurnal(trx) {
        if (!trx || !trx.jurnal) {
            return 0;
        }

        return trx.jurnal
            .filter(function(item) {
                return (item.identity || '').toLowerCase().match(/sewa kamar|upgrade kamar/);
            })
            .reduce(function(total, item) {
                return total + (parseFloat(item.kredit) || 0);
            }, 0);
    }

    function populateRoomSelect() {
        const $select = $('#new_room_id');

        $select.empty().append('<option value="">-- Pilih Kamar --</option>');

        changeRoomData.options.forEach(function(option) {
            if (!option || !option.price) {
                return;
            }

            let roomLabel = option.room.name;

            if (option.is_current_room) {
                roomLabel += ' [Kamar Saat Ini]';
            } else if (option.is_occupied) {
                roomLabel += ' [Terisi]';
            }

            $select.append(
                '<option value="' + option.room.id + '" data-price="' + option.price + '" data-occupied="' + option.is_occupied + '" data-current="' + option.is_current_room + '">' +
                    roomLabel + ' - ' + (option.room.category_name || '-') + ' (Rp ' + formatNumber(option.price) + ')' +
                '</option>'
            );
        });
    }

    function unformatNumber(value) {
        return value ? value.toString().replace(/[^\d.-]/g, '') : '0';
    }

    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function initRoomIncomePaymentModal() {
        if ($.fn.select2) {
            $('#room_income_transaksi').select2({
                width: '100%',
                dropdownParent: $('#md_room_income_payment')
            });
        }

        if ($.fn.daterangepicker && !$('#room_income_tgl_transaksi').data('daterangepicker')) {
            $('#room_income_tgl_transaksi').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                locale: { format: 'YYYY-MM-DD' },
                autoApply: true,
            });
        }

        if ($.fn.inputmask && !$('#room_income_nominal').data('inputmask')) {
            $('#room_income_nominal').inputmask({
                min: 0,
                autoUnmask: true,
                unmaskAsNumber: true,
                removeMaskOnSubmit: true,
                alias: 'decimal',
                groupSeparator: ',',
            });
        }
    }

    function syncRoomIncomePaymentSelection() {
        const $selected = $('#room_income_transaksi').find(':selected');
        const kurang = parseFloat($selected.data('kurang') || 0);

        $('#room_income_section').val($selected.data('section') || '');
        $('#room_income_nominal').attr('data-inputmask-max', kurang || 0);

        if ($.fn.inputmask && $('#room_income_nominal').data('inputmask')) {
            $('#room_income_nominal').inputmask('option', 'max', kurang || 0);
        }

        if (kurang > 0) {
            $('#room_income_nominal').val(kurang);
        }
    }

    function openRoomIncomePaymentModal(transaksi, section, kurang) {
        ensureUnpaidTransactionsData().done(function() {
            initRoomIncomePaymentModal();
            $('#room_income_tgl_transaksi').val(moment().format('YYYY-MM-DD'));
            $('#room_income_keterangan').val('');
            $('#room_income_nominal').val(kurang || '');
            $('#room_income_section').val(section || '');
            $('#room_income_transaksi').val(transaksi || '').trigger('change');
            syncRoomIncomePaymentSelection();
            $('#md_unpaid_transactions').modal('hide');
            $('#md_room_income_payment').modal('show');
        });
    }

    function checkOccupiedRoomWarning() {
        const selectedOption = $('#new_room_id option:selected');
        const isOccupied = selectedOption.attr('data-occupied') === 'true';
        const isCurrent = selectedOption.attr('data-current') === 'true';

        $('.occupied-room-warning').remove();

        if (isOccupied && !isCurrent && selectedOption.val()) {
            $('#new_room_id').after(
                '<div class="alert alert-warning occupied-room-warning mt-2">' +
                    '<i class="fas fa-exclamation-triangle mr-2"></i>' +
                    '<strong>Perhatian:</strong> Kamar ini sedang terisi. Perubahan kamar bisa menyebabkan konflik jadwal.' +
                '</div>'
            );
        }
    }

    function recalcPayment() {
        if (!changeRoomData.transaction) {
            return;
        }

        const trx = changeRoomData.transaction;
        const lama = parseInt(trx.lama_sewa, 10) || 0;
        const jangka = (trx.jangka_sewa || '').toLowerCase();
        const effective = moment($('#effective_date').val(), 'YYYY-MM-DD');
        const start = moment(trx.tgl_mulai, 'YYYY-MM-DD');

        if (!effective.isValid()) {
            return;
        }

        let elapsed = 0;

        if (effective.isSameOrAfter(start)) {
            switch (jangka) {
                case 'bulan':
                    elapsed = effective.diff(start, 'months');
                    break;
                case 'minggu':
                    elapsed = effective.diff(start, 'weeks');
                    break;
                case 'tahun':
                    elapsed = effective.diff(start, 'years');
                    break;
                case 'hari':
                default:
                    elapsed = effective.diff(start, 'days');
                    break;
            }
        }

        if (elapsed < 0) {
            elapsed = 0;
        }

        if (elapsed > lama) {
            elapsed = lama;
        }

        const remaining = lama - elapsed;
        const remainingPercent = lama > 0 ? remaining / lama : 0;
        const oldFull = parseFloat(trx.harga) || 0;
        const alreadyPaid = sumPaidJurnal(trx);
        const outstandingOld = Math.max(oldFull - alreadyPaid, 0);
        const selected = $('#new_room_id').find(':selected');
        const newFull = parseFloat(selected.data('price')) || 0;
        const diffFull = newFull - oldFull;
        const payable = Math.round(diffFull * remainingPercent);
        let payNow = parseFloat(unformatNumber($('#pay_now').val())) || 0;

        if (payable > 0 && payNow > payable) {
            payNow = payable;
            $('#pay_now').val(payNow);
        }

        const remainingDue = payable > 0 ? Math.max(0, payable - payNow) : 0;

        $('#old_package_price').text('Rp ' + formatNumber(oldFull.toFixed(2)));
        $('#new_package_price').text('Rp ' + formatNumber(newFull.toFixed(2)));
        $('#diff_full').text('Rp ' + formatNumber(diffFull.toFixed(2)));
        $('#total_units').text(lama + ' ' + (trx.jangka_sewa || ''));
        $('#elapsed_units').text(elapsed + ' ' + (trx.jangka_sewa || ''));
        $('#remaining_units').text(remaining + ' ' + (trx.jangka_sewa || ''));
        $('#remaining_percent').text((remainingPercent * 100).toFixed(1) + '%');
        $('#payment_amount_text').text('Rp ' + formatNumber(Math.abs(payable).toFixed(2)));
        $('#old_total_package_text').text('Rp ' + formatNumber(oldFull.toFixed(0)));
        $('#already_paid_text').text('Rp ' + formatNumber(alreadyPaid.toFixed(0)));
        $('#outstanding_old_text').text('Rp ' + formatNumber(outstandingOld.toFixed(0)));
        $('#pay_now_text').text('Rp ' + formatNumber(payNow));
        $('#remaining_due_text').text('Rp ' + formatNumber(remainingDue));

        let totalDueNow = 0;

        if (payable > 0) {
            totalDueNow = outstandingOld + payable - payNow;
        } else if (payable < 0) {
            totalDueNow = -Math.min(alreadyPaid, Math.abs(payable));
        } else {
            totalDueNow = outstandingOld;
        }

        const totalDueNowAbs = Math.abs(totalDueNow);

        $('#total_due_now_text').text((totalDueNow < 0 ? '- ' : '') + 'Rp ' + formatNumber(totalDueNowAbs.toFixed(2)));

        if (payable > 0) {
            $('#payment_type_text').html('<span class="text-primary">Upgrade: bayar tambahan' + (remainingDue > 0 ? ' (akan tercatat di transaksi belum lunas)' : '') + '</span>');
            $('#payment_type_hidden').val('charge');
            $('#payment_input_row').show();
            $('#refund_options_row').hide();
            $('#refund_to_deposit').prop('checked', false);
        } else if (payable < 0) {
            $('#payment_type_text').html('<span class="text-success">Downgrade: kemungkinan refund' + (outstandingOld > 0 ? ' (dikurangi tunggakan)' : '') + '</span>');
            $('#payment_type_hidden').val('refund');
            $('#payment_input_row').hide();
            $('#pay_now').val(0);
            $('#refund_options_row').show();
        } else {
            $('#payment_type_text').html('<span class="text-muted">Tidak ada selisih paket' + (outstandingOld > 0 ? ' (hanya tunggakan lama)' : '') + '</span>');
            $('#payment_type_hidden').val('none');
            $('#payment_input_row').hide();
            $('#pay_now').val(0);
            $('#refund_options_row').hide();
            $('#refund_to_deposit').prop('checked', false);
        }

        $('#payment_amount_hidden').val(Math.abs(payable));
        $('#payment_total_due_hidden').val(totalDueNow);
        $('#pay_now_hidden').val(payNow);
        $('#remaining_due_hidden').val(remainingDue);
    }

    $(document).ready(function() {
        loadRoomsDashboardData();

        $(document).on('click', '.js-room-stats-filter', function() {
            applyRoomFilter($(this).data('filter'));
        });

        $(document).on('keydown', '.js-room-stats-filter', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                applyRoomFilter($(this).data('filter'));
            }
        });

        ensureUnpaidTransactionsData();

        $('#md_sewa, #md_extra, #md_filter').on('show.bs.modal', function() {
            ensureRoomFormData();
        });

        $('#md_deleted').on('show.bs.modal', function() {
            ensureDeletedRoomsData();
        });

        $('#md_unpaid_transactions').on('show.bs.modal', function() {
            ensureUnpaidTransactionsData();
        });

        $('#md_room_income_payment').on('shown.bs.modal', function() {
            initRoomIncomePaymentModal();
        });

        $('#room_income_transaksi').on('select2:select', function() {
            syncRoomIncomePaymentSelection();
        });

        $(document).on('click', '.js-open-unpaid-payment', function() {
            openRoomIncomePaymentModal(
                $(this).data('transaksi'),
                $(this).data('section'),
                $(this).data('kurang')
            );
        });

        $('.btn_exls').on('click', function(e) {
            e.preventDefault();
            ensureRoomExportTable().done(function() {
                $('.buttons-excel').click();
            });
        });

        $('.btn_epdf').on('click', function(e) {
            e.preventDefault();
            ensureRoomExportTable().done(function() {
                $('.buttons-pdf').click();
            });
        });

        $('.btn_eprint').on('click', function(e) {
            e.preventDefault();
            ensureRoomExportTable().done(function() {
                $('.buttons-print').click();
            });
        });

        $('#pricelist_extra').on('select2:select', function() {
            var data = $(this).find(':selected');
            var lamaSewa = data.data('lama');

            $('#lama_sewa').attr('data-inputmask-suffix', ' ' + lamaSewa);
        });

        $(document).on('click', '.js-room-menu-toggle, .room-card__quick-menu', function(e) {
            e.stopPropagation();
        });

        $(document).on('click', '.js-room-action', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const action = $(this).data('action');
            const roomId = $(this).data('room-id');
            const roomName = $(this).data('room-name');
            const transId = $(this).data('trans-id');

            if (action === 'wifi') {
                $('#wifi_room_name').text(roomName || '-');
                $('#wifi_record_id').val('');
                $('#wifi_room_id').val(roomId || '');
                $('#wifi_ssid_input').val('');
                $('#wifi_password_input').val('');
                $('#wifi_notes_input').val('');
                $('#wifi_active_input').prop('checked', true);
                setWifiFormFeedback('', 'error');
                setWifiFormLoading(true);
                $('#md_room_wifi').modal('show');

                $.getJSON(roomActionUrl(roomWifiUrlTemplate, roomId))
                    .done(function(response) {
                        const wifi = response.wifi;

                        $('#wifi_room_id').val(response.room && response.room.id ? response.room.id : roomId);

                        if (wifi) {
                            $('#wifi_record_id').val(wifi.id || '');
                            $('#wifi_ssid_input').val(wifi.ssid || '');
                            $('#wifi_password_input').val(wifi.password || '');
                            $('#wifi_notes_input').val(wifi.notes || '');
                            $('#wifi_active_input').prop('checked', !!wifi.active);
                            setWifiFormFeedback('Data WiFi aktif ditemukan. Anda bisa langsung mengubahnya.', 'success');
                        } else {
                            setWifiFormFeedback('Belum ada WiFi aktif untuk kamar ini. Isi data untuk menambahkan WiFi baru.', 'error');
                        }

                        setWifiFormLoading(false);
                    })
                    .fail(function() {
                        setWifiFormLoading(false);
                        setWifiFormFeedback('Gagal memuat data WiFi kamar.', 'error');
                    });
                return;
            }

            if (action === 'history') {
                $('#history_room_name').text('Kamar ' + (roomName || '-'));
                $('#history_body').html('<tr><td colspan="7" class="room-history-empty">Memuat data...</td></tr>');
                $('#md_room_history').modal('show');

                $.getJSON(roomActionUrl(roomHistoryUrlTemplate, roomId))
                    .done(function(response) {
                        if (!response.transactions || !response.transactions.length) {
                            $('#history_body').html('<tr><td colspan="7" class="room-history-empty">Belum ada riwayat transaksi untuk kamar ini.</td></tr>');
                            return;
                        }

                        const rows = response.transactions.map(function(item) {
                            const period = (item.tgl_mulai || '-') + ' s/d ' + (item.tgl_selesai || '-');
                            const total = 'Rp ' + $.number((item.harga || 0) + (item.extra_total || 0), 0);
                            const paid = 'Rp ' + $.number(item.paid_total || 0, 0);
                            const printButton = item.trans_id
                                ? '<a href="' + roomActionUrl(transactionPrintUrlTemplate, item.trans_id) + '" target="_blank" rel="noopener noreferrer" class="room-history-action">Nota</a>'
                                : '-';

                            return '<tr>' +
                                '<td>' + (item.trans_id || '-') + '</td>' +
                                '<td>' + (item.renter_name || '-') + '</td>' +
                                '<td>' + period + '</td>' +
                                '<td>' + total + '</td>' +
                                '<td>' + paid + '</td>' +
                                '<td>' + (item.notes || '-') + '</td>' +
                                '<td class="text-right">' + printButton + '</td>' +
                            '</tr>';
                        }).join('');

                        $('#history_body').html(rows);
                    })
                    .fail(function() {
                        $('#history_body').html('<tr><td colspan="7" class="room-history-empty">Gagal memuat riwayat transaksi.</td></tr>');
                    });
                return;
            }

            if (action === 'move') {
                if (!transId) {
                    showRoomToast('Kamar ini belum memiliki penyewa aktif untuk dipindahkan.', 'warning');
                    return;
                }

                changeRoomData = { transaction: null, options: [] };
                $('#change_room_trans_id').val(transId);
                $('#current_room_id').val(roomId || '');
                $('#new_room_id').html('<option value="">Loading...</option>');
                $('#pay_now').val(0);
                $('#refund_to_deposit').prop('checked', false);
                $('.occupied-room-warning').remove();
                $('#effective_date').val(moment().format('YYYY-MM-DD'));
                $('#payment_date').val(moment().format('YYYY-MM-DD'));
                $('#md_change_room').modal('show');

                if ($.fn.daterangepicker && !$('#effective_date').data('daterangepicker')) {
                    $('#effective_date').daterangepicker({
                        singleDatePicker: true,
                        showDropdowns: true,
                        locale: { format: 'YYYY-MM-DD' },
                        autoApply: true,
                    });
                }

                if ($.fn.daterangepicker && !$('#payment_date').data('daterangepicker')) {
                    $('#payment_date').daterangepicker({
                        singleDatePicker: true,
                        showDropdowns: true,
                        locale: { format: 'YYYY-MM-DD' },
                        autoApply: true,
                    });
                }

                $.when(loadTransaction(transId), loadChangeRoomOptions(transId))
                    .done(function(transactionResponse, optionsResponse) {
                        changeRoomData.transaction = transactionResponse[0];
                        changeRoomData.options = optionsResponse[0].rooms || [];
                        populateRoomSelect();
                        recalcPayment();
                    })
                    .fail(function() {
                        $('#new_room_id').html('<option value="">Gagal memuat opsi kamar</option>');
                    });
                return;
            }

            if (action === 'refund') {
                if (!transId) {
                    showRoomToast('Kamar ini belum memiliki penyewa aktif untuk direfund.', 'warning');
                    return;
                }

                setRefundDefaults(roomName, transId);
                $('#md_room_refund').modal('show');

                $.getJSON(roomActionUrl(transactionShowUrlTemplate, transId))
                    .done(function(response) {
                        $('#refund_amount').val(response && response.harga ? response.harga : '');
                        $('#refund_checkout_date').val(response && response.tgl_selesai ? response.tgl_selesai : $('#refund_checkout_date').val());
                    });
                return;
            }

            if (action === 'booking') {
                $('#booking_room_name').text('Kamar ' + (roomName || '-'));
                $('#booking_body').html('<tr><td colspan="7" class="room-booking-empty">Memuat data...</td></tr>');
                $('#md_room_booking').modal('show');

                $.getJSON(roomActionUrl(roomBookingUrlTemplate, roomId))
                    .done(function(response) {
                        if (!response.transactions || !response.transactions.length) {
                            $('#booking_body').html('<tr><td colspan="7" class="room-booking-empty">Belum ada antrian booking untuk kamar ini.</td></tr>');
                            return;
                        }

                        const rows = response.transactions.map(function(item) {
                            const period = (item.tgl_mulai || '-') + ' s/d ' + (item.tgl_selesai || '-');
                            const total = 'Rp ' + $.number((item.harga || 0) + (item.extra_total || 0), 0);
                            const paid = 'Rp ' + $.number(item.paid_total || 0, 0);
                            const printButton = item.trans_id
                                ? '<a href="' + roomActionUrl(transactionPrintUrlTemplate, item.trans_id) + '" target="_blank" rel="noopener noreferrer" class="room-history-action">Nota</a>'
                                : '-';

                            return '<tr>' +
                                '<td>' + (item.trans_id || '-') + '</td>' +
                                '<td>' + (item.renter_name || '-') + '</td>' +
                                '<td>' + period + '</td>' +
                                '<td>' + total + '</td>' +
                                '<td>' + paid + '</td>' +
                                '<td>' + (item.notes || '-') + '</td>' +
                                '<td class="text-right">' + printButton + '</td>' +
                            '</tr>';
                        }).join('');

                        $('#booking_body').html(rows);
                    })
                    .fail(function() {
                        $('#booking_body').html('<tr><td colspan="7" class="room-booking-empty">Gagal memuat antrian booking.</td></tr>');
                    });
                return;
            }
        });

        $(document).on('change', '#new_room_id', function() {
            checkOccupiedRoomWarning();
            recalcPayment();
        });

        $(document).on('change', '#effective_date', recalcPayment);
        $(document).on('input', '#pay_now', recalcPayment);

        $('#md_change_room').on('hidden.bs.modal', function() {
            changeRoomData = { transaction: null, options: [] };
            $('.occupied-room-warning').remove();
        });

        $(document).on('change', '#sewa_renter', function() {
            var selected = $(this).find(':selected');
            var deposit = parseFloat(selected.data('deposit') || 0).toFixed(2);

            $('#renter_deposit_display').val(deposit.replace(/\B(?=(\d{3})+(?!\d))/g, ','));
            $('#topup_renter_id').val(selected.val());
        });

        $(document).on('change', '#use_deposit', function() {
            if ($(this).is(':checked')) {
                $('#deposit_amount_row').show();

                var selected = $('#sewa_renter').find(':selected');
                var deposit = parseFloat(selected.data('deposit') || 0).toFixed(2);
                $('#deposit_amount').val(deposit);
                return;
            }

            $('#deposit_amount_row').hide();
            $('#deposit_amount').val('0');
        });

        $(document).on('click', '#btn_topup_deposit', function() {
            var renterId = $('#sewa_renter').val();

            if (!renterId) {
                alert('Pilih penyewa dulu');
                return;
            }

            $('#topup_renter_id').val(renterId);
            $('#md_topup_deposit').modal('show');
        });

        $(document).on('click', '.js-room-pricelist', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const categoryId = $(this).data('category-id');
            const categoryName = $(this).data('category-name');

            $('#pricelist_room_category').text(categoryName || '-');
            $('#pricelist_body').html('<tr><td colspan="2" class="room-pricelist-empty">Memuat data...</td></tr>');
            $('#md_room_pricelist').modal('show');

            $.getJSON(roomActionUrl(roomPricelistUrlTemplate, categoryId))
                .done(function(response) {
                    if (!response || !response.length) {
                        $('#pricelist_body').html('<tr><td colspan="2" class="room-pricelist-empty">Belum ada pricelist untuk kategori ini.</td></tr>');
                        return;
                    }

                    const rows = response.map(function(item) {
                        const duration = (item.jangka_waktu || '-') + ' ' + (item.jangka_sewa || '');
                        const price = 'Rp ' + $.number(item.price || 0, 0);

                        return '<tr>' +
                            '<td>' + duration + '</td>' +
                            '<td class="text-right">' + price + '</td>' +
                        '</tr>';
                    }).join('');

                    $('#pricelist_body').html(rows);
                })
                .fail(function() {
                    $('#pricelist_body').html('<tr><td colspan="2" class="room-pricelist-empty">Gagal memuat pricelist.</td></tr>');
                });
        });

        $(document).on('click', '.js-room-renter', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const renterId = $(this).data('renter-id');
            const renterName = $(this).data('renter-name');

            if (!renterId) {
                showRoomToast('Detail penyewa tidak tersedia untuk kamar ini.', 'warning');
                return;
            }

            $('#renter_detail_name').text(renterName || '-');
            $('#renter_detail_address, #renter_detail_birthday, #renter_detail_phone, #renter_detail_phone2, #renter_detail_identity_type, #renter_detail_identity_number, #renter_detail_vehicle, #renter_detail_nopol, #renter_detail_room, #renter_detail_deposit').text('Memuat...');
            $('#renter_detail_documents').html('<span class="renter-detail-doc-empty">Memuat dokumen...</span>');
            $('#renter_history_list').html('<div class="renter-history-empty">Memuat riwayat transaksi...</div>');
            $('#renter_detail_photo_wrap').html('<div class="renter-detail-photo-placeholder">' + escapeHtml((renterName || '?').toString().trim().charAt(0).toUpperCase() || '?') + '</div>');
            $('#md_renter_detail').modal('show');

            $.getJSON(roomActionUrl(renterDetailUrlTemplate, renterId))
                .done(function(response) {
                    const renter = response.renter || {};
                    const documents = response.documents || [];
                    const transactions = response.transactions || [];
                    const activeRoom = renter.current_room_name
                        ? renter.current_room_name + (renter.current_room_end ? ' s/d ' + renter.current_room_end : '')
                        : '-';

                    $('#renter_detail_name').text(renter.nama || '-');
                    $('#renter_detail_address').text(renter.alamat || '-');
                    $('#renter_detail_birthday').text(renter.birthday || '-');
                    $('#renter_detail_phone').text(renter.phone || '-');
                    $('#renter_detail_phone2').text(renter.phone2 || '-');
                    $('#renter_detail_identity_type').text(renter.identitas || '-');
                    $('#renter_detail_identity_number').text(renter.no_identitas || '-');
                    $('#renter_detail_vehicle').text(renter.kendaraan || '-');
                    $('#renter_detail_nopol').text(renter.nopol || '-');
                    $('#renter_detail_room').text(activeRoom);
                    $('#renter_detail_deposit').text('Rp ' + $.number(renter.deposit_balance || 0, 2));

                    const photo = documents.find(function(item) {
                        return (item.type || '').toUpperCase() === 'PHOTO' && item.url;
                    });

                    if (photo && photo.url) {
                        $('#renter_detail_photo_wrap').html('<img src="' + escapeHtml(photo.url) + '" alt="' + escapeHtml(renter.nama || 'Penyewa') + '" class="renter-detail-photo">');
                    } else {
                        const initial = ((renter.nama || renterName || '?').toString().trim().charAt(0) || '?').toUpperCase();
                        $('#renter_detail_photo_wrap').html('<div class="renter-detail-photo-placeholder">' + escapeHtml(initial) + '</div>');
                    }

                    const documentLinks = documents
                        .filter(function(item) {
                            return !!item.url;
                        })
                        .map(function(item) {
                            return '<a href="' + escapeHtml(item.url) + '" target="_blank" rel="noopener noreferrer" class="renter-detail-doc">' +
                                escapeHtml(item.type || 'Dokumen') +
                            '</a>';
                        });

                    $('#renter_detail_documents').html(
                        documentLinks.length
                            ? documentLinks.join('')
                            : '<span class="renter-detail-doc-empty">Belum ada dokumen.</span>'
                    );

                    if (!transactions.length) {
                        $('#renter_history_list').html('<div class="renter-history-empty">Belum ada riwayat transaksi untuk penyewa ini.</div>');
                        return;
                    }

                    const historyHtml = transactions.map(function(item) {
                        const total = (item.harga || 0) + (item.extra_total || 0);
                        const isPaid = (item.remaining_total || 0) <= 0;
                        const statusClass = isPaid ? 'renter-history-item__status--paid' : 'renter-history-item__status--unpaid';
                        const statusLabel = isPaid ? 'Lunas' : 'Belum Lunas';
                        const roomLabel = item.room_name || '-';
                        const period = (item.tgl_mulai || '-') + ' s/d ' + (item.tgl_selesai || '-');

                        return '<div class="renter-history-item">' +
                            '<div class="renter-history-item__top">' +
                                '<div>' +
                                    '<div class="renter-history-item__id">' + escapeHtml(item.trans_id || '-') + '</div>' +
                                    '<div class="renter-history-item__room">Kamar ' + escapeHtml(roomLabel) + '</div>' +
                                '</div>' +
                                '<span class="renter-history-item__status ' + statusClass + '">' + statusLabel + '</span>' +
                            '</div>' +
                            '<div class="renter-history-item__period">' + escapeHtml(period) + '</div>' +
                            '<div class="renter-history-item__stats">' +
                                '<div class="renter-history-item__stat">' +
                                    '<span class="renter-history-item__stat-label">Total</span>' +
                                    '<span class="renter-history-item__stat-value">Rp ' + $.number(total || 0, 0) + '</span>' +
                                '</div>' +
                                '<div class="renter-history-item__stat">' +
                                    '<span class="renter-history-item__stat-label">Dibayar</span>' +
                                    '<span class="renter-history-item__stat-value">Rp ' + $.number(item.paid_total || 0, 0) + '</span>' +
                                '</div>' +
                                '<div class="renter-history-item__stat">' +
                                    '<span class="renter-history-item__stat-label">Sisa</span>' +
                                    '<span class="renter-history-item__stat-value">Rp ' + $.number(item.remaining_total || 0, 0) + '</span>' +
                                '</div>' +
                            '</div>' +
                            '<div class="renter-history-item__notes">' + escapeHtml(item.notes || 'Tanpa catatan.') + '</div>' +
                        '</div>';
                    }).join('');

                    $('#renter_history_list').html(historyHtml);
                })
                .fail(function() {
                    $('#renter_detail_address, #renter_detail_birthday, #renter_detail_phone, #renter_detail_phone2, #renter_detail_identity_type, #renter_detail_identity_number, #renter_detail_vehicle, #renter_detail_nopol, #renter_detail_room, #renter_detail_deposit').text('-');
                    $('#renter_detail_documents').html('<span class="renter-detail-doc-empty">Gagal memuat dokumen.</span>');
                    $('#renter_history_list').html('<div class="renter-history-empty">Gagal memuat riwayat transaksi.</div>');
                    $('#renter_detail_photo_wrap').html('<div class="renter-detail-photo-placeholder">!</div>');
                });
        });

        $('#room_wifi_form').on('submit', function(e) {
            e.preventDefault();

            const wifiId = $('#wifi_record_id').val();
            const payload = {
                _token: $('meta[name="csrf-token"]').attr('content'),
                room_id: $('#wifi_room_id').val(),
                ssid: $('#wifi_ssid_input').val(),
                password: $('#wifi_password_input').val(),
                notes: $('#wifi_notes_input').val(),
                active: $('#wifi_active_input').is(':checked') ? 1 : 0,
            };
            const url = wifiId ? roomActionUrl(roomWifiUpdateUrlTemplate, wifiId) : roomWifiStoreUrl;

            setWifiFormFeedback('', 'error');
            setWifiFormLoading(true);

            $.post(url, payload)
                .done(function(response) {
                    const savedWifi = response.data || {};

                    $('#wifi_record_id').val(savedWifi.id || wifiId || '');
                    $('#wifi_active_input').prop('checked', !!savedWifi.active);
                    setWifiFormLoading(false);
                    setWifiFormFeedback('Data WiFi berhasil disimpan.', 'success');
                    showRoomToast('Data WiFi kamar berhasil disimpan.', 'success');
                })
                .fail(function(xhr) {
                    setWifiFormLoading(false);

                    const errors = xhr.responseJSON && xhr.responseJSON.errors
                        ? Object.values(xhr.responseJSON.errors).flat().join(' ')
                        : null;
                    const message = errors || (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan data WiFi kamar.';

                    setWifiFormFeedback(message, 'error');
                });
        });
    });
    $(document).on('click', '.edit_kamar', function(e) {
        if ($(e.target).closest('.js-room-action, .js-room-pricelist, .js-room-renter, .js-room-print, .js-room-menu, .js-room-menu-toggle, .room-card__quick-menu').length) {
            return;
        }
        var id = $(this).data('id');
        $.ajax({
            url: "{{route('bcl.rooms.edit', ':id')}}",
            type: "GET",
            data: {
                id: id
            },
            success: function(data) {
                ensureRoomFormData().always(function() {
                    $('#id_kamar').val(data.id);
                    $('#no_kamar').val(data.room_name);
                    $('#floor').val(data.floor).trigger('change');
                    $('#notes').val(data.notes);
                    $('#kategori').val(data.room_category).trigger('change');
                    $('#md_edit').modal('show');
                });
            }
        });
    });

    $(document).on('keydown', '.edit_kamar', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            if ($(e.target).closest('.js-room-action, .js-room-pricelist, .js-room-renter, .js-room-print, .js-room-menu, .js-room-menu-toggle, .room-card__quick-menu').length) {
                return;
            }
            e.preventDefault();
            $(this).trigger('click');
        }
    });

    function deletes(e) {
        e.preventDefault();
        var url = e.currentTarget.getAttribute('href');
        $.confirm({
            title: 'Hapus data ini?',
            content: 'Aksi ini tidak dapat diurungkan',
            buttons: {
                confirm: {
                    text: 'Ya',
                    btnClass: 'btn-red',
                    keys: ['enter'],
                    action: function() {
                        window.location.href = url;
                    },
                },
                cancel: {
                    text: 'Batal',
                    action: function() {}
                }
            }
        });
    };
    $('#kamar').on('select2:select', function() {
        var id = $(this).find(':selected').data('room_category');
        $.ajax({
            url: "{{route('bcl.pricelist.get_pl_room', ':id')}}",
            type: "GET",
            data: {
                id: id
            },
            success: function(data) {
                // console.log(data);
                $('#pricelist').empty();
                $('#pricelist').append('<option value=""></option>');
                $.each(data, function(index, value) {
                    $('#pricelist').append('<option data-harga="' + value.price + '" value="' + value.id + '">' + value.jangka_waktu + ' ' + value.jangka_sewa + ' ' + $.number(value.price) + '</option>');
                });
            }
        });
    });
    $('#pricelist').on('select2:select', function() {
        var harga = $(this).find(':selected').data('harga');
        $('#nominal').inputmask({
            min: 0,
            max: parseInt(harga),
            autoUnmask: "true",
            unmaskAsNumber: "true",
            'removeMaskOnSubmit': true,
            alias: 'decimal',
            groupSeparator: ',',
        });
        $('#nominal').data('price', harga);
    });

    $(document).on('input', '#nominal', function() {
        var raw = $(this).val();
        var price = parseFloat($(this).data('price') || 0);
        var value = parseFloat(unformatNumber(raw)) || 0;

        if (price > 0 && value > price) {
            $('#overpay_to_deposit').show();
            $('#overpay_to_deposit_label').show();
            return;
        }

        $('#overpay_to_deposit').hide().prop('checked', false);
        $('#overpay_to_deposit_label').hide();
    });

    $(document).on('submit', 'form[action="{{ route('bcl.rooms.sewa') }}"]', function() {
        var $nominal = $(this).find('#nominal');

        if ($nominal.length) {
            $nominal.val(unformatNumber($nominal.val()));
        }

        $(this).find('input.inputmask').each(function() {
            var $input = $(this);
            $input.val(unformatNumber($input.val()));
        });

        $(this).find('.datePicker').each(function() {
            var $date = $(this);
            $date.val(normalizeDatePickerValue($date.val()));
        });

        return true;
    });

    $(document).on('submit', 'form', function() {
        $(this).find('.datePicker').each(function() {
            var $date = $(this);
            $date.val(normalizeDatePickerValue($date.val()));
        });

        $(this).find('input.inputmask').each(function() {
            var $input = $(this);
            $input.val(unformatNumber($input.val()));
        });

        return true;
    });
</script>
@stop