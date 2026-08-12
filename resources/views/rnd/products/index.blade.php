@extends('layouts.admin.app')

@section('title', 'RND Produk')

@section('navbar')
    @include('layouts.rnd.navbar')
@endsection

@section('styles')
<style>
    .rnd-products-card .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        background: linear-gradient(135deg, #0f766e, #14b8a6);
        color: #fff;
        border-bottom: 0;
    }

    .rnd-products-card .card-title {
        color: inherit;
        margin: 0;
        font-weight: 700;
    }

    .rnd-products-card .btn-light {
        color: #0f172a;
        font-weight: 600;
    }

    .produk-page-tabs {
        border-bottom: 0;
        gap: 10px;
        margin-bottom: 18px;
    }

    .produk-page-tabs .nav-link {
        border: 1px solid #cbd5e1;
        border-radius: 999px;
        color: #334155;
        font-size: 13px;
        font-weight: 700;
        padding: 0.65rem 1rem;
        background: #fff;
    }

    .produk-page-tabs .nav-link.active,
    .produk-page-tabs .nav-link:hover,
    .produk-page-tabs .nav-link:focus {
        color: #0f766e;
        border-color: rgba(20, 184, 166, 0.35);
        background: rgba(20, 184, 166, 0.12);
    }

    .produk-page-pane {
        min-width: 0;
    }

    .produk-status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.3rem 0.55rem;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        background: rgba(37, 99, 235, 0.12);
        color: #1d4ed8;
        border: 1px solid rgba(29, 78, 216, 0.16);
    }

    .produk-status-badge-done {
        background: rgba(34, 197, 94, 0.14);
        color: #15803d;
        border-color: rgba(34, 197, 94, 0.24);
    }

    .produk-status-badge-warning {
        background: rgba(250, 204, 21, 0.22);
        color: #a16207;
        border-color: rgba(202, 138, 4, 0.28);
    }

    .produk-status-badge-danger {
        background: rgba(239, 68, 68, 0.14);
        color: #b91c1c;
        border-color: rgba(220, 38, 38, 0.24);
    }

    .produk-status-badge-empty {
        background: rgba(148, 163, 184, 0.12);
        color: #64748b;
        border-color: rgba(100, 116, 139, 0.2);
    }

    #produkTable th,
    #produkTable td {
        vertical-align: top;
        white-space: normal;
    }

    .rnd-products-card .card-body {
        padding: 1rem;
    }

    #produkTable_wrapper .row {
        align-items: center;
    }

    #produkTable_wrapper .dataTables_length label,
    #produkTable_wrapper .dataTables_filter label {
        margin-bottom: 0;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    #produkTable_wrapper .dataTables_filter input,
    #produkTable_wrapper .dataTables_length select {
        max-width: 100%;
    }

    #produkTable tbody tr:nth-child(even) {
        background: #f8fafc;
    }

    #produkTable tbody tr:nth-child(odd) {
        background: #ffffff;
    }

    #produkTable tbody tr:hover {
        background: #eef6ff;
    }

    #produkTable tbody td {
        border-bottom: 2px solid #dbe7f3;
    }

    .produk-status-trigger {
        border: 0;
        background: transparent;
        padding: 0;
        cursor: pointer;
        line-height: 1;
    }

    .produk-action-link {
        border: 0;
        background: rgba(15, 118, 110, 0.12);
        color: #0f766e;
        border-radius: 999px;
        padding: 0.35rem 0.7rem;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
        cursor: pointer;
        width: fit-content;
    }

    .produk-action-link:hover,
    .produk-action-link:focus {
        background: rgba(15, 118, 110, 0.18);
        outline: none;
    }

    .produk-action-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .produk-history-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        text-align: left;
        margin-top: 8px;
    }

    .produk-history-item {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px;
        background: #fff;
    }

    .produk-history-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 6px;
    }

    .produk-history-time {
        font-size: 12px;
        color: #64748b;
    }

    .produk-history-status {
        display: flex;
        justify-content: flex-end;
        margin-top: 8px;
    }

    .produk-history-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 8px;
    }

    .produk-modal-layout {
        display: flex;
        gap: 24px;
        align-items: flex-start;
    }

    .produk-form-pane {
        flex: 1 1 auto;
        min-width: 0;
    }

    .produk-log-pane {
        flex: 0 0 340px;
        max-width: 340px;
        border-left: 1px solid #e2e8f0;
        padding-left: 24px;
    }

    .produk-log-pane.is-hidden {
        display: none;
    }

    .produk-log-card {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #f8fafc;
        padding: 16px;
    }

    .produk-log-title {
        margin: 0 0 12px;
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
    }

    .produk-form-tabs {
        border-bottom: 1px solid #e2e8f0;
        gap: 8px;
        flex-wrap: nowrap;
        overflow-x: auto;
        padding-bottom: 4px;
        margin-bottom: 20px;
    }

    .produk-form-tabs .nav-item {
        margin-bottom: 0;
    }

    .produk-form-tabs .nav-link {
        border: 1px solid #dbe4f0;
        border-radius: 999px;
        color: #475569;
        font-size: 13px;
        font-weight: 700;
        padding: 0.55rem 0.95rem;
        white-space: nowrap;
        background: #fff;
    }

    .produk-form-tabs .nav-link.active,
    .produk-form-tabs .nav-link:hover,
    .produk-form-tabs .nav-link:focus {
        color: #0f766e;
        border-color: rgba(20, 184, 166, 0.35);
        background: rgba(20, 184, 166, 0.1);
    }

    .produk-tab-pane {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #fff;
        padding: 20px;
    }

    .produk-tab-pane + .produk-tab-pane {
        margin-top: 16px;
    }

    .produk-tab-caption {
        margin-bottom: 16px;
    }

    .produk-tab-caption h6 {
        margin: 0 0 4px;
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
    }

    .produk-tab-caption p {
        margin: 0;
        font-size: 12px;
        color: #64748b;
    }

    .produk-section-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .produk-section-card {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #f8fafc;
        padding: 16px;
    }

    .produk-section-card.is-disabled {
        opacity: 0.6;
    }

    .produk-section-head {
        margin-bottom: 14px;
    }

    .produk-section-head h6 {
        margin: 0 0 4px;
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
    }

    .produk-section-head p {
        margin: 0;
        font-size: 12px;
        color: #64748b;
    }

    .produk-section-card .form-group:last-child {
        margin-bottom: 0;
    }

    .produk-upload-panel {
        margin-top: 16px;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        background: #f8fafc;
        padding: 16px;
    }

    .produk-upload-toolbar {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .produk-upload-button {
        min-width: 148px;
    }

    .produk-upload-summary {
        font-size: 12px;
        color: #475569;
    }

    .produk-upload-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .produk-upload-submit {
        min-width: 132px;
    }

    .produk-timeline-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .produk-timeline-calendar-shell {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(280px, 0.9fr);
        gap: 18px;
        align-items: start;
    }

    .produk-timeline-calendar-card,
    .produk-timeline-detail-card {
        border: 1px solid #dbe7f3;
        border-radius: 16px;
        background: #fff;
        padding: 16px;
    }

    #productTimelineCalendar {
        max-width: 100%;
        min-height: 640px;
    }

    .produk-timeline-detail-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }

    .produk-timeline-detail-title {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
    }

    .produk-timeline-detail-caption {
        margin: 4px 0 0;
        font-size: 12px;
        color: #64748b;
    }

    .produk-timeline-detail-count {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: rgba(15, 118, 110, 0.12);
        color: #0f766e;
        font-size: 11px;
        font-weight: 700;
        padding: 0.35rem 0.65rem;
        white-space: nowrap;
    }

    .produk-timeline-selected-date {
        font-size: 13px;
        font-weight: 700;
        color: #0f766e;
    }

    .produk-timeline-calendar-shell .fc {
        font-size: 13px;
    }

    .produk-timeline-calendar-shell .fc .fc-toolbar-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #0f172a;
    }

    .produk-timeline-calendar-shell .fc .fc-button {
        background: #0f766e;
        border-color: #0f766e;
        box-shadow: none;
    }

    .produk-timeline-calendar-shell .fc .fc-button:hover,
    .produk-timeline-calendar-shell .fc .fc-button:focus,
    .produk-timeline-calendar-shell .fc .fc-button.fc-button-active {
        background: #0d9488;
        border-color: #0d9488;
    }

    .produk-timeline-calendar-shell .fc .fc-daygrid-event {
        border: 0;
        border-radius: 8px;
        padding: 2px 4px;
    }

    .produk-timeline-calendar-shell .fc .fc-day-today {
        background: rgba(20, 184, 166, 0.08) !important;
    }

    .produk-timeline-item {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #f8fafc;
        padding: 14px 16px;
    }

    .produk-timeline-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 8px;
    }

    .produk-timeline-date {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
    }

    .produk-timeline-product {
        font-size: 14px;
        font-weight: 700;
        color: #0f766e;
    }

    .produk-timeline-created {
        font-size: 11px;
        color: #64748b;
        white-space: nowrap;
    }

    .produk-timeline-notes {
        font-size: 12px;
        color: #334155;
        line-height: 1.6;
        white-space: pre-wrap;
    }

    .produk-timeline-meta {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }

    .produk-document-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 14px;
    }

    .produk-document-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #fff;
    }

    .produk-document-link {
        color: #0f766e;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        word-break: break-word;
    }

    .produk-document-link:hover,
    .produk-document-link:focus {
        color: #0d9488;
        text-decoration: underline;
        outline: none;
    }

    .produk-document-meta {
        flex: 0 0 auto;
        font-size: 11px;
        color: #64748b;
        white-space: nowrap;
    }

    .produk-document-main {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .produk-document-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 0 0 auto;
    }

    .produk-document-delete {
        border: 0;
        border-radius: 999px;
        background: rgba(220, 38, 38, 0.12);
        color: #b91c1c;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        padding: 0.4rem 0.65rem;
        cursor: pointer;
    }

    .produk-document-delete:hover,
    .produk-document-delete:focus {
        background: rgba(220, 38, 38, 0.18);
        outline: none;
    }

    .select2-container .select2-selection.is-invalid {
        border-color: #dc3545;
    }

    .produk-table-actions {
        display: flex;
        justify-content: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .produk-action-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 76px;
        padding: 0.45rem 0.75rem;
        border: 0;
        border-radius: 999px;
        background: #0f766e;
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
        cursor: pointer;
    }

    .produk-action-button:hover,
    .produk-action-button:focus {
        background: #0d9488;
        color: #fff;
        outline: none;
    }

    .produk-action-button-secondary {
        background: rgba(15, 118, 110, 0.12);
        color: #0f766e;
    }

    .produk-action-button-secondary:hover,
    .produk-action-button-secondary:focus {
        background: rgba(15, 118, 110, 0.18);
        color: #0f766e;
    }

    .produk-log-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        max-height: 420px;
        overflow-y: auto;
    }

    .produk-log-item {
        border: 1px solid #dbeafe;
        border-radius: 12px;
        background: #fff;
        padding: 12px;
    }

    .produk-log-item-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 6px;
    }

    .produk-log-item-status {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: #0f766e;
    }

    .produk-log-item-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .produk-log-source {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.55rem;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .produk-log-source-user {
        background: rgba(37, 99, 235, 0.14);
        color: #1d4ed8;
    }

    .produk-log-source-system {
        background: rgba(15, 118, 110, 0.14);
        color: #0f766e;
    }

    .produk-log-item-time {
        font-size: 11px;
        color: #64748b;
        white-space: nowrap;
    }

    .produk-log-item-notes {
        font-size: 12px;
        color: #334155;
        line-height: 1.5;
        white-space: pre-wrap;
    }

    .produk-log-empty {
        font-size: 12px;
        color: #64748b;
        text-align: center;
        padding: 16px 0;
    }

    @media (max-width: 1199.98px) {
        .produk-modal-layout {
            flex-direction: column;
        }

        .produk-timeline-calendar-shell {
            grid-template-columns: minmax(0, 1fr);
        }

        .produk-log-pane {
            flex: 1 1 auto;
            max-width: none;
            width: 100%;
            border-left: 0;
            border-top: 1px solid #e2e8f0;
            padding-left: 0;
            padding-top: 24px;
        }
    }

    .swal2-radio {
        display: flex !important;
        flex-direction: column;
        gap: 10px;
        margin: 1rem 0 0;
    }

    .swal2-radio label {
        display: flex !important;
        align-items: center;
        gap: 10px;
        width: 100%;
        margin: 0;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        color: #0f172a;
        font-size: 14px;
        font-weight: 600;
        text-align: left;
    }

    .swal2-radio label:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .swal2-radio input {
        margin: 0;
    }

    .produk-meta {
        font-size: 12px;
        color: #64748b;
    }

    .produk-inline-meta {
        font-size: 12px;
        color: #64748b;
        white-space: nowrap;
    }

    .produk-inline-link {
        font-size: 12px;
        font-weight: 700;
        color: #0f766e;
        text-decoration: none;
        white-space: nowrap;
    }

    .produk-inline-link:hover,
    .produk-inline-link:focus {
        color: #0d9488;
        text-decoration: underline;
    }

    .produk-bahan-list {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.02em;
        color: #64748b;
        line-height: 1.5;
        text-transform: uppercase;
    }

    .produk-name-stack {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .produk-name-trigger {
        display: inline-flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        border: 0;
        background: transparent;
        padding: 0;
        text-align: left;
        cursor: pointer;
        color: inherit;
    }

    .produk-name-trigger:hover strong,
    .produk-name-trigger:focus strong {
        color: #0f766e;
        text-decoration: underline;
    }

    .produk-name-trigger:focus {
        outline: none;
    }

    .produk-kemasan-trigger {
        border: 0;
        background: transparent;
        padding: 0;
        text-align: left;
        color: inherit;
        cursor: pointer;
        font: inherit;
    }

    .produk-kemasan-trigger:hover,
    .produk-kemasan-trigger:focus {
        color: #0f766e;
        text-decoration: underline;
        outline: none;
    }

    .produk-kemasan-picker-search {
        margin-bottom: 16px;
    }

    .produk-kemasan-picker-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-height: 320px;
        overflow-y: auto;
    }

    .produk-kemasan-picker-item {
        width: 100%;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        color: #0f172a;
        padding: 12px 14px;
        text-align: left;
        font-weight: 600;
        transition: background-color 0.15s ease, border-color 0.15s ease;
    }

    .produk-kemasan-picker-item:hover,
    .produk-kemasan-picker-item:focus {
        background: #f8fafc;
        border-color: #cbd5e1;
        outline: none;
    }

    .produk-kemasan-picker-item.is-active {
        background: #ecfeff;
        border-color: #14b8a6;
        color: #0f766e;
    }

    .produk-kemasan-picker-item-meta {
        display: block;
        margin-top: 4px;
        font-size: 12px;
        font-weight: 500;
        color: #64748b;
    }

    .produk-kemasan-picker-empty {
        font-size: 13px;
        color: #64748b;
        text-align: center;
        padding: 16px 0;
    }

    .produk-cell-stack {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 180px;
    }

    .produk-cell-title {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.02em;
        color: #64748b;
        text-transform: uppercase;
    }

    .produk-cell-value {
        color: #0f172a;
    }

    .produk-cell-value strong {
        font-weight: 700;
    }

    .produk-cell-divider {
        width: 100%;
        height: 1px;
        background: #e2e8f0;
    }

    .produk-kemasan-stack {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 180px;
    }

    .produk-kemasan-name {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        color: #0f172a;
        font-weight: 700;
    }

    .produk-kemasan-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .produk-kemasan-line {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .produk-kemasan-design-text {
        font-size: 12px;
        color: #475569;
    }

    .produk-kemasan-compact {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .produk-kemasan-compact-section {
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #f8fafc;
    }

    .produk-name-stack {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .produk-progress-stack {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .produk-progress-meta {
        flex: 0 0 auto;
        min-width: 82px;
        text-align: right;
        font-size: 16px;
        font-weight: 700;
        line-height: 1;
        color: #0f172a;
    }

    .produk-progress-track {
        flex: 1 1 auto;
        height: 8px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
    }

    .produk-progress-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #0f766e 0%, #14b8a6 100%);
        transition: width 0.2s ease;
    }

    #produkTable_wrapper .btn-group .btn {
        min-width: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    @media (max-width: 991.98px) {
        .rnd-products-card .card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .rnd-products-card .btn-light {
            width: 100%;
        }

        .table-responsive {
            overflow-x: visible;
        }

        #produkTable_wrapper > .row:first-child > div,
        #produkTable_wrapper > .row:last-child > div {
            width: 100%;
            max-width: 100%;
            flex: 0 0 100%;
        }

        #produkTable_wrapper .dataTables_length,
        #produkTable_wrapper .dataTables_filter,
        #produkTable_wrapper .dataTables_paginate,
        #produkTable_wrapper .dataTables_info {
            text-align: left;
        }

        #produkTable_wrapper .dataTables_filter {
            margin-top: 12px;
        }

        #produkTable_wrapper .dataTables_filter label {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        #produkTable_wrapper .dataTables_filter input {
            margin-left: 0;
            width: 100%;
        }

        .produk-kemasan-line {
            flex-direction: column;
            align-items: flex-start;
        }

        .produk-kemasan-badges,
        .produk-action-group {
            width: 100%;
        }

        .produk-progress-stack,
        .produk-history-head,
        .produk-history-footer {
            flex-direction: column;
            align-items: flex-start;
        }

        .produk-progress-meta {
            min-width: 0;
            text-align: left;
        }

        .produk-form-tabs {
            margin-bottom: 16px;
        }

        .produk-tab-pane {
            padding: 16px;
        }

        .produk-section-grid {
            grid-template-columns: 1fr;
        }

        .produk-document-item,
        .produk-upload-toolbar,
        .produk-timeline-head {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    @media (max-width: 575.98px) {
        .container-fluid {
            padding-left: 12px;
            padding-right: 12px;
        }

        .rnd-products-card .card-body {
            padding: 0.75rem;
        }

        #produkTable th,
        #produkTable td {
            padding: 0.75rem;
        }

        .produk-cell-stack,
        .produk-kemasan-stack {
            min-width: 0;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="page-title-box">
        <div class="row align-items-center">
            <div class="col">
                <h4 class="page-title mb-1">Produk</h4>
                <p class="text-muted mb-0">Kelola data produk RND beserta referensi brand, kemasan, dan sediaan.</p>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs produk-page-tabs" id="produkPageTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="produk-list-tab" data-toggle="tab" href="#produk-list-pane" role="tab" aria-controls="produk-list-pane" aria-selected="true">Daftar Produk</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="produk-timeline-index-tab" data-toggle="tab" href="#produk-timeline-index-pane" role="tab" aria-controls="produk-timeline-index-pane" aria-selected="false">Timeline</a>
        </li>
    </ul>

    <div class="tab-content" id="produkPageTabContent">
        <div class="tab-pane fade show active produk-page-pane" id="produk-list-pane" role="tabpanel" aria-labelledby="produk-list-tab">
            <div class="card rnd-products-card shadow-sm">
                <div class="card-header">
                    <h4 class="card-title">Daftar Produk</h4>
                    <button type="button" class="btn btn-light btn-sm" id="createNewProduk">Tambah Produk</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered dt-responsive nowrap w-100" id="produkTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Status Sample</th>
                                    <th>Kemasan</th>
                                    <th>Status Administrasi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade produk-page-pane" id="produk-timeline-index-pane" role="tabpanel" aria-labelledby="produk-timeline-index-tab">
            <div class="card rnd-products-card shadow-sm">
                <div class="card-header">
                    <h4 class="card-title">Timeline Produk</h4>
                </div>
                <div class="card-body">
                    <div class="produk-timeline-calendar-shell">
                        <div class="produk-timeline-calendar-card">
                            <div id="productTimelineCalendar"></div>
                        </div>
                        <div class="produk-timeline-detail-card">
                            <div class="produk-timeline-detail-header">
                                <div>
                                    <h6 class="produk-timeline-detail-title">Detail Timeline</h6>
                                    <p class="produk-timeline-detail-caption" id="productTimelineDetailCaption">Klik tanggal atau event pada kalender untuk melihat detail.</p>
                                </div>
                                <span class="produk-timeline-detail-count" id="productTimelineDetailCount">0 item</span>
                            </div>
                            <div class="produk-timeline-selected-date" id="productTimelineSelectedDate">Belum ada tanggal dipilih</div>
                            <div class="produk-timeline-list mt-3" id="productTimelineIndexList">
                                <div class="produk-log-empty">Belum ada timeline produk.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="produkModal" tabindex="-1" role="dialog" aria-labelledby="produkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="produkModalLabel">Tambah Produk</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="produkForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="produk_id" value="">
                    <div class="produk-modal-layout">
                        <div class="produk-form-pane">
                            <ul class="nav nav-tabs produk-form-tabs" id="produkFormTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" id="produk-base-tab" data-toggle="tab" href="#produk-base-pane" role="tab" aria-controls="produk-base-pane" aria-selected="true">Informasi Produk</a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="produk-kemasan-tab" data-toggle="tab" href="#produk-kemasan-pane" role="tab" aria-controls="produk-kemasan-pane" aria-selected="false">Informasi Kemasan</a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="produk-administrasi-tab" data-toggle="tab" href="#produk-administrasi-pane" role="tab" aria-controls="produk-administrasi-pane" aria-selected="false">Administrasi</a>
                                </li>
                            </ul>

                            <div class="tab-content" id="produkFormTabContent">
                                <div class="tab-pane fade show active" id="produk-base-pane" role="tabpanel" aria-labelledby="produk-base-tab">
                                    <div class="produk-tab-pane">
                                        <div class="produk-tab-caption">
                                            <h6>Informasi dasar produk</h6>
                                            <p>Kelola identitas utama produk, sediaan, produsen, dan bahan aktif.</p>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="brand_id">Brand</label>
                                                <select class="form-control select2-produk" id="brand_id" name="brand_id" required>
                                                    <option value="">Pilih Brand</option>
                                                    @foreach($brands as $brand)
                                                        <option value="{{ $brand->id }}">{{ $brand->nama_brand }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="nama_produk">Nama Produk</label>
                                                <input type="text" class="form-control" id="nama_produk" name="nama_produk" required>
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label for="netto">Netto</label>
                                                <input type="text" class="form-control" id="netto" name="netto" placeholder="Contoh: 15 ml">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="sediaan_id">Sediaan</label>
                                                <select class="form-control select2-produk" id="sediaan_id" name="sediaan_id" required>
                                                    <option value="">Pilih Sediaan</option>
                                                    @foreach($sediaans as $sediaan)
                                                        <option value="{{ $sediaan->id }}">{{ $sediaan->nama_sediaan }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="produsen_vendor_id">Produsen Vendor</label>
                                                <select class="form-control select2-produk" id="produsen_vendor_id" name="produsen_vendor_id">
                                                    <option value="">Pilih Produsen Vendor</option>
                                                    @foreach($produsenVendors as $vendor)
                                                        <option value="{{ $vendor->id }}">{{ $vendor->nama_vendor }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-12 mb-0">
                                                <label for="bahan_aktif_ids">Bahan Aktif</label>
                                                <select class="form-control select2-produk" id="bahan_aktif_ids" name="bahan_aktif_ids[]" multiple>
                                                    @foreach($bahanAktifs as $bahanAktif)
                                                        <option value="{{ $bahanAktif->id }}">{{ $bahanAktif->nama_bahan_aktif }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="produk-kemasan-pane" role="tabpanel" aria-labelledby="produk-kemasan-tab">
                                    <div class="produk-tab-pane">
                                        <div class="produk-tab-caption">
                                            <h6>Informasi kemasan</h6>
                                            <p>Fokuskan data primer terlebih dulu. Isi sekunder hanya jika produk memang memakai kemasan tambahan.</p>
                                        </div>

                                        <div class="produk-section-grid">
                                            <section class="produk-section-card">
                                                <div class="produk-section-head">
                                                    <h6>Kemasan primer</h6>
                                                    <p>Data utama kemasan produk.</p>
                                                </div>

                                                <div class="form-group">
                                                    <label for="kemasan_premier_id">Jenis Kemasan</label>
                                                    <select class="form-control select2-produk" id="kemasan_premier_id" name="kemasan_premier_id" required>
                                                        <option value="">Pilih Kemasan Primer</option>
                                                        @foreach($kemasanPrimerOptions as $kemasan)
                                                            <option value="{{ $kemasan->id }}">{{ $kemasan->nama_kemasan }}{{ $kemasan->ukuran ? ' (' . $kemasan->ukuran . ')' : '' }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label for="kemasan_primer_vendor_id">Vendor Kemasan</label>
                                                    <select class="form-control select2-produk" id="kemasan_primer_vendor_id" name="kemasan_primer_vendor_id">
                                                        <option value="">Pilih Vendor Kemasan Primer</option>
                                                        @foreach($kemasanVendors as $vendor)
                                                            <option value="{{ $vendor->id }}">{{ $vendor->nama_vendor }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label for="desain_kemasan_primer_id">Vendor Desain</label>
                                                    <select class="form-control select2-produk" id="desain_kemasan_primer_id" name="desain_kemasan_primer_id">
                                                        <option value="">Pilih Vendor Desain Primer</option>
                                                        @foreach($desainVendors as $vendor)
                                                            <option value="{{ $vendor->id }}">{{ $vendor->nama_vendor }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="form-row mb-0">
                                                    <div class="form-group col-md-6">
                                                        <label for="status_kemasan_primer">Status Kemasan</label>
                                                        <select class="form-control" id="status_kemasan_primer" name="status_kemasan_primer">
                                                            <option value="">Pilih Status</option>
                                                            @foreach($statusKemasanOptions as $status)
                                                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="form-group col-md-6 mb-0">
                                                        <label for="status_desain_kemasan_primer">Status Desain</label>
                                                        <select class="form-control" id="status_desain_kemasan_primer" name="status_desain_kemasan_primer">
                                                            <option value="">Pilih Status</option>
                                                            @foreach($statusDesainOptions as $status)
                                                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </section>

                                            <section class="produk-section-card" id="produkSekunderCard">
                                                <div class="produk-section-head">
                                                    <h6>Kemasan sekunder</h6>
                                                    <p>Opsional. Aktifkan hanya jika produk memakai kemasan tambahan.</p>
                                                </div>

                                                <div class="form-group">
                                                    <label for="kemasan_sekunder_id">Jenis Kemasan</label>
                                                    <select class="form-control select2-produk" id="kemasan_sekunder_id" name="kemasan_sekunder_id">
                                                        <option value="">Tanpa Kemasan Sekunder</option>
                                                        @foreach($kemasanSekunderOptions as $kemasan)
                                                            <option value="{{ $kemasan->id }}">{{ $kemasan->nama_kemasan }}{{ $kemasan->ukuran ? ' (' . $kemasan->ukuran . ')' : '' }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label for="kemasan_sekunder_vendor_id">Vendor Kemasan</label>
                                                    <select class="form-control select2-produk js-kemasan-sekunder-dependent" id="kemasan_sekunder_vendor_id" name="kemasan_sekunder_vendor_id">
                                                        <option value="">Pilih Vendor Kemasan Sekunder</option>
                                                        @foreach($kemasanVendors as $vendor)
                                                            <option value="{{ $vendor->id }}">{{ $vendor->nama_vendor }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label for="desain_kemasan_sekunder_id">Vendor Desain</label>
                                                    <select class="form-control select2-produk js-kemasan-sekunder-dependent" id="desain_kemasan_sekunder_id" name="desain_kemasan_sekunder_id">
                                                        <option value="">Pilih Vendor Desain Sekunder</option>
                                                        @foreach($desainVendors as $vendor)
                                                            <option value="{{ $vendor->id }}">{{ $vendor->nama_vendor }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="form-row mb-0">
                                                    <div class="form-group col-md-6">
                                                        <label for="status_kemasan_sekunder">Status Kemasan</label>
                                                        <select class="form-control js-kemasan-sekunder-dependent" id="status_kemasan_sekunder" name="status_kemasan_sekunder">
                                                            <option value="">Pilih Status</option>
                                                            @foreach($statusKemasanOptions as $status)
                                                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="form-group col-md-6 mb-0">
                                                        <label for="status_desain_kemasan_sekunder">Status Desain</label>
                                                        <select class="form-control js-kemasan-sekunder-dependent" id="status_desain_kemasan_sekunder" name="status_desain_kemasan_sekunder">
                                                            <option value="">Pilih Status</option>
                                                            @foreach($statusDesainOptions as $status)
                                                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </section>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="produk-administrasi-pane" role="tabpanel" aria-labelledby="produk-administrasi-tab">
                                    <div class="produk-tab-pane">
                                        <div class="produk-tab-caption">
                                            <h6>Status administrasi</h6>
                                            <p>Ubah progres dokumen administrasi produk langsung dari form edit.</p>
                                        </div>

                                        <div class="form-row mb-0">
                                            <div class="form-group col-md-4">
                                                <label for="status_administrasi_fpp">Status FPP</label>
                                                <select class="form-control" id="status_administrasi_fpp" name="status_administrasi_fpp">
                                                    <option value="">Pilih Status</option>
                                                    @foreach($statusAdministrasiFppOptions as $status)
                                                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="status_administrasi_spk">Status SPK</label>
                                                <select class="form-control" id="status_administrasi_spk" name="status_administrasi_spk">
                                                    <option value="">Pilih Status</option>
                                                    @foreach($statusAdministrasiSpkOptions as $status)
                                                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group col-md-4 mb-0">
                                                <label for="status_administrasi_notif">Status NOTIF</label>
                                                <select class="form-control" id="status_administrasi_notif" name="status_administrasi_notif">
                                                    <option value="">Pilih Status</option>
                                                    @foreach($statusAdministrasiNotifOptions as $status)
                                                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="produk-upload-panel">
                                            <div class="produk-section-head mb-0">
                                                <h6>Dokumen tambahan</h6>
                                                <p>Unggah dokumen pendukung produk. Bisa pilih beberapa file sekaligus.</p>
                                            </div>

                                            <input type="file" id="additional_documents" name="additional_documents[]" class="d-none" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">

                                            <div class="produk-upload-toolbar mt-3">
                                                <button type="button" class="produk-action-button produk-upload-button" id="pickAdditionalDocumentsBtn">Upload Dokumen</button>
                                                <div class="produk-upload-actions">
                                                    <button type="button" class="btn btn-outline-primary btn-sm produk-upload-submit" id="submitAdditionalDocumentsBtn" disabled>Proses Upload</button>
                                                </div>
                                                <div class="produk-upload-summary" id="additionalDocumentsSummary">Belum ada file dipilih.</div>
                                            </div>

                                            <div class="produk-meta mt-2">Format yang didukung: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG. Maksimum 10 MB per file.</div>

                                            <div class="produk-document-list" id="additionalDocumentsList">
                                                <div class="produk-log-empty">Belum ada dokumen tambahan.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <aside class="produk-log-pane is-hidden" id="produkLogPane">
                            <div class="produk-log-card">
                                <h6 class="produk-log-title">Riwayat Produk</h6>
                                <div class="produk-log-list" id="produkLogList">
                                    <div class="produk-log-empty">Pilih produk untuk melihat log.</div>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger mr-auto d-none" id="deleteProdukBtn">Hapus</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="saveProdukBtn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="kemasanPickerModal" tabindex="-1" role="dialog" aria-labelledby="kemasanPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="kemasanPickerModalLabel">Pilih Kemasan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control produk-kemasan-picker-search" id="kemasanPickerSearch" placeholder="Cari nama kemasan...">
                <div class="produk-kemasan-picker-list" id="kemasanPickerList"></div>
                <div class="form-row mt-3">
                    <div class="form-group col-md-6">
                        <label for="kemasanPickerVendorId">Vendor Kemasan</label>
                        <select class="form-control select2-kemasan-modal" id="kemasanPickerVendorId">
                            <option value="">Pilih Vendor Kemasan</option>
                            @foreach($kemasanVendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->nama_vendor }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6 mb-0">
                        <label for="kemasanPickerDesainVendorId">Vendor Desain</label>
                        <select class="form-control select2-kemasan-modal" id="kemasanPickerDesainVendorId">
                            <option value="">Pilih Vendor Desain</option>
                            @foreach($desainVendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->nama_vendor }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="saveKemasanPickerBtn">Simpan</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src='{{ asset('fullcalendar/dist/index.global.js') }}'></script>
<script>
    $(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        var inlineStatusOptions = {
            status_kemasan_primer: @json($statusKemasanOptions),
            status_kemasan_sekunder: @json($statusKemasanOptions),
            status_desain_kemasan_primer: @json($statusDesainOptions),
            status_desain_kemasan_sekunder: @json($statusDesainOptions),
            status_administrasi_fpp: @json($statusAdministrasiFppOptions),
            status_administrasi_spk: @json($statusAdministrasiSpkOptions),
            status_administrasi_notif: @json($statusAdministrasiNotifOptions),
            status_sample: @json($statusSampleOptions)
        };

        var kemasanOptions = @json($kemasanPickerOptions);

        var activeKemasanPicker = {
            productId: null,
            field: null,
            currentValue: '',
            selectedValue: '',
            kemasanVendorValue: '',
            desainVendorValue: ''
        };
        var productTimelineEntries = [];
        var productTimelineCalendar = null;

        function getKemasanRelationConfig(field) {
            if (field === 'kemasan_premier_id') {
                return {
                    title: 'Edit Kemasan Primer',
                    vendorField: 'kemasan_primer_vendor_id',
                    desainVendorField: 'desain_kemasan_primer_id'
                };
            }

            return {
                title: 'Edit Kemasan Sekunder',
                vendorField: 'kemasan_sekunder_vendor_id',
                desainVendorField: 'desain_kemasan_sekunder_id'
            };
        }

        function normalizeStatus(value) {
            return $.trim(String(value || '')).toLowerCase();
        }

        function statusBadge(value) {
            if (!value) {
                return '-';
            }

            var normalized = normalizeStatus(value);
            var badgeClass = '';

            if (normalized === 'done') {
                badgeClass = ' produk-status-badge-done';
            } else if (normalized === 'revisi') {
                badgeClass = ' produk-status-badge-danger';
            } else if (normalized === 'review' || normalized === 'in progress' || normalized === 'progress') {
                badgeClass = ' produk-status-badge-warning';
            }

            return '<span class="produk-status-badge' + badgeClass + '">' + $('<div>').text(value).html() + '</span>';
        }

        function escapeHtml(value) {
            return $('<div>').text(value || '-').html();
        }

        function renderStatusTrigger(productId, field, value) {
            var badgeHtml = value
                ? statusBadge(value)
                : '<span class="produk-status-badge produk-status-badge-empty">set status</span>';

            return '<button type="button" class="produk-status-trigger js-inline-status" data-id="' + productId + '" data-field="' + field + '" data-value="' + escapeHtml(value || '') + '">' + badgeHtml + '</button>';
        }

        function calculateProgress(row) {
            var fields = [
                row.status_administrasi_fpp,
                row.status_administrasi_spk,
                row.status_administrasi_notif,
                row.latest_sample_status,
                row.status_kemasan_primer,
                row.status_desain_kemasan_primer
            ];
            var hasKemasanSekunder = !!row.kemasan_sekunder_id;

            if (hasKemasanSekunder) {
                fields.push(row.status_kemasan_sekunder);
                fields.push(row.status_desain_kemasan_sekunder);
            }

            var completed = fields.filter(function (value) {
                return normalizeStatus(value) === 'done';
            }).length;
            var total = fields.length;
            var percent = total ? Math.round((completed / total) * 100) : 0;

            return {
                completed: completed,
                total: total,
                percent: percent
            };
        }

        function renderNamaColumn(row) {
            var brandName = $.trim(row.brand_name || '');
            var productName = $.trim(row.nama_produk || '');
            var netto = $.trim(row.netto || '');
            var sediaan = $.trim(row.sediaan_name || '');
            var fullName = $.trim((brandName + ' ' + productName + ' ' + netto + ' ' + sediaan).replace(/\s+/g, ' '));
            var bahanAktif = row.bahan_aktif_names === '-' ? '' : '<div class="produk-bahan-list">' + escapeHtml(row.bahan_aktif_names) + '</div>';
            var progress = calculateProgress(row);

            return '<div class="produk-name-stack">'
                + '<button type="button" class="produk-name-trigger js-edit-product" data-id="' + row.id + '"><strong>' + escapeHtml(fullName || '-') + '</strong>' + bahanAktif + '</button>'
                + '<div class="produk-progress-stack">'
                + '<div class="produk-progress-track"><div class="produk-progress-fill" style="width: ' + progress.percent + '%;"></div></div>'
                + '<div class="produk-progress-meta">' + progress.percent + '%</div>'
                + '</div>'
                + '</div>';
        }

        function renderSampleColumn(row) {
            if (!row.has_sample_log) {
                return '<div class="produk-cell-stack">'
                    + '<div class="produk-action-group">'
                    + '<button type="button" class="produk-action-link js-add-sample" data-id="' + row.id + '">Add Sample</button>'
                    + '</div>'
                    + '</div>';
            }

            var noProduksi = escapeHtml(row.latest_sample_no_produksi || '-');
            var produsenVendor = '<div class="produk-bahan-list">Produsen Vendor: ' + escapeHtml(row.produsen_vendor_name || '-') + '</div>';
            var statusHtml = renderStatusTrigger(row.id, 'status_sample', row.latest_sample_status);

            return '<div class="produk-cell-stack">'
                + '<div class="produk-kemasan-line"><div class="produk-cell-value"><strong>' + noProduksi + '</strong></div><div class="produk-kemasan-badges">' + statusHtml + '</div></div>'
                + produsenVendor
                + '<div class="produk-cell-divider"></div>'
                + '<div class="produk-action-group">'
                + '<button type="button" class="produk-action-link js-add-sample" data-id="' + row.id + '">Add Sample</button>'
                + '<button type="button" class="produk-action-link js-sample-history" data-id="' + row.id + '">History</button>'
                + '</div>'
                + '</div>';
        }

        function formatSampleDate(value) {
            if (!value) {
                return '-';
            }

            var date = new Date(value);

            if (isNaN(date.getTime())) {
                return escapeHtml(value);
            }

            return escapeHtml(date.toLocaleString('id-ID', {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            }));
        }

        function renderSampleHistory(logs) {
            if (!logs || !logs.length) {
                return '<div class="produk-meta text-center">Belum ada sample.</div>';
            }

            return '<div class="produk-history-list">' + logs.map(function (log) {
                var encodedNotes = encodeURIComponent(log.notes || '');

                return '<div class="produk-history-item">'
                    + '<div class="produk-history-head">'
                    + '<div class="produk-cell-value"><strong>' + escapeHtml(log.no_produksi || '-') + '</strong></div>'
                    + '<div class="produk-history-time">' + formatSampleDate(log.created_at) + '</div>'
                    + '</div>'
                    + (log.notes ? '<div class="produk-meta">' + escapeHtml(log.notes) + '</div>' : '<div class="produk-meta">-</div>')
                    + '<div class="produk-history-footer">'
                    + '<div class="produk-history-status">' + statusBadge(log.status_sample || '-') + '</div>'
                    + '<div class="produk-action-group">'
                    + '<button type="button" class="produk-action-link js-edit-sample-notes" data-product-id="' + log.produk_id + '" data-sample-id="' + log.id + '" data-notes="' + encodedNotes + '" data-reopen-history="1">Edit Notes</button>'
                    + '<button type="button" class="produk-action-link js-delete-sample" data-product-id="' + log.produk_id + '" data-sample-id="' + log.id + '" data-reopen-history="1">Delete</button>'
                    + '</div>'
                    + '</div>'
                    + '</div>';
            }).join('') + '</div>';
        }

        function openSampleHistory(productId) {
            $.get('{{ url('/rnd/produk') }}/' + productId, function (response) {
                var data = response.data || {};
                var sampleLogs = data.sample_logs || [];

                Swal.fire({
                    title: 'History Sample',
                    html: renderSampleHistory(sampleLogs),
                    width: 640,
                    showConfirmButton: false,
                    showCloseButton: true
                });
            }).fail(function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal mengambil history sample.';
                Swal.fire('Error', message, 'error');
            });
        }

        function updateSampleNotes(sampleId, notes, done) {
            $.ajax({
                url: '{{ url('/rnd/produk/sample-log') }}/' + sampleId,
                type: 'POST',
                data: {
                    _method: 'PUT',
                    notes: notes
                },
                success: function (response) {
                    table.ajax.reload(null, false);
                    if (typeof done === 'function') {
                        done();
                        return;
                    }
                    Swal.fire('Sukses', response.message || 'Catatan sample berhasil diperbarui.', 'success');
                },
                error: function (xhr) {
                    var message = 'Terjadi kesalahan saat memperbarui catatan sample.';

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    Swal.fire('Validasi gagal', message, 'warning');
                }
            });
        }

        function deleteSample(sampleId, done) {
            $.ajax({
                url: '{{ url('/rnd/produk/sample-log') }}/' + sampleId,
                type: 'POST',
                data: {
                    _method: 'DELETE'
                },
                success: function (response) {
                    table.ajax.reload(null, false);
                    if (typeof done === 'function') {
                        done();
                        return;
                    }
                    Swal.fire('Sukses', response.message || 'Sample berhasil dihapus.', 'success');
                },
                error: function (xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal menghapus sample.';
                    Swal.fire('Error', message, 'error');
                }
            });
        }

        function stackedField(label, value, allowBadge) {
            var safeValue = value;

            if (!safeValue) {
                safeValue = '-';
            } else if (!allowBadge) {
                safeValue = escapeHtml(safeValue);
            }

            return '<div class="produk-cell-stack">'
                + '<div class="produk-cell-title">' + escapeHtml(label) + '</div>'
                + '<div class="produk-cell-value">' + safeValue + '</div>'
                + '</div>';
        }

        function mergedSection(sections) {
            return sections
                .map(function(section, index) {
                    return stackedField(section.label, section.value, section.allowBadge) + (index < sections.length - 1 ? '<div class="produk-cell-divider"></div>' : '');
                })
                .join('');
        }

        function renderKemasanColumn(productId, kemasanField, kemasanId, statusField, designField, name, status, desain, kemasanVendorId, desainVendorId, kemasanVendorName, desainVendorName) {
            var statusHtml = renderStatusTrigger(productId, statusField, status);
            var desainHtml = renderStatusTrigger(productId, designField, desain);
            var kemasanVendorField = kemasanField === 'kemasan_premier_id' ? 'kemasan_primer_vendor_id' : 'kemasan_sekunder_vendor_id';
            var desainVendorField = kemasanField === 'kemasan_premier_id' ? 'desain_kemasan_primer_id' : 'desain_kemasan_sekunder_id';
            var kemasanName = '<button type="button" class="produk-kemasan-trigger js-inline-kemasan" data-id="' + productId + '" data-field="' + kemasanField + '" data-value="' + escapeHtml(kemasanId || '') + '" data-kemasan-vendor-field="' + kemasanVendorField + '" data-kemasan-vendor-value="' + escapeHtml(kemasanVendorId || '') + '" data-desain-vendor-field="' + desainVendorField + '" data-desain-vendor-value="' + escapeHtml(desainVendorId || '') + '">' + escapeHtml(name || '-') + '</button>';
            var kemasanVendorText = kemasanVendorName
                ? '<div class="produk-bahan-list">Vendor: ' + escapeHtml(kemasanVendorName) + '</div>'
                : '';
            var desainVendorText = 'Design by ' + escapeHtml(desainVendorName || '-');

            return '<div class="produk-cell-stack">'
                + '<div class="produk-kemasan-line">'
                + '<div class="produk-kemasan-name">' + kemasanName + kemasanVendorText + '</div>'
                + '<div class="produk-kemasan-badges">' + statusHtml + '</div>'
                + '</div>'
                + '<div class="produk-cell-divider"></div>'
                + '<div class="produk-kemasan-line"><div class="produk-cell-title">' + desainVendorText + '</div><div class="produk-kemasan-badges">' + desainHtml + '</div></div>'
                + '</div>';
        }

        function renderCombinedKemasanColumn(row) {
            var sections = [];

            sections.push('<div class="produk-kemasan-compact-section">'
                + '<div class="produk-cell-title">Kemasan Primer</div>'
                + renderKemasanColumn(
                    row.id,
                    'kemasan_premier_id',
                    row.kemasan_premier_id,
                    'status_kemasan_primer',
                    'status_desain_kemasan_primer',
                    row.kemasan_premier_name,
                    row.status_kemasan_primer,
                    row.status_desain_kemasan_primer,
                    row.kemasan_primer_vendor_id,
                    row.desain_kemasan_primer_id,
                    row.kemasan_primer_vendor_name,
                    row.desain_kemasan_primer_vendor_name
                )
                + '</div>');

            if (row.kemasan_sekunder_id) {
                sections.push('<div class="produk-kemasan-compact-section">'
                    + '<div class="produk-cell-title">Kemasan Sekunder</div>'
                    + renderKemasanColumn(
                        row.id,
                        'kemasan_sekunder_id',
                        row.kemasan_sekunder_id,
                        'status_kemasan_sekunder',
                        'status_desain_kemasan_sekunder',
                        row.kemasan_sekunder_name,
                        row.status_kemasan_sekunder,
                        row.status_desain_kemasan_sekunder,
                        row.kemasan_sekunder_vendor_id,
                        row.desain_kemasan_sekunder_id,
                        row.kemasan_sekunder_vendor_name,
                        row.desain_kemasan_sekunder_vendor_name
                    )
                    + '</div>');
            } else {
                sections.push('<div class="produk-kemasan-compact-section">'
                    + '<div class="produk-cell-title">Kemasan Sekunder</div>'
                    + '<div class="produk-cell-value">-</div>'
                    + '</div>');
            }

            return '<div class="produk-kemasan-compact">' + sections.join('') + '</div>';
        }

        function renderAdministrasiColumn(row) {
            var parts = [];

            function formatInlineDate(value) {
                if (!value) {
                    return '';
                }

                var date = new Date(value + 'T00:00:00');

                if (isNaN(date.getTime())) {
                    return escapeHtml(value);
                }

                return escapeHtml(date.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                }));
            }

            function partHtml(label, field, value) {
                var extraMeta = '';

                if (field === 'status_administrasi_notif' && value === 'done' && row.latest_notif_tanggal_selesai) {
                    extraMeta = '<span class="produk-inline-meta">' + formatInlineDate(row.latest_notif_tanggal_selesai) + '</span>';

                    if (row.latest_notif_document_url) {
                        extraMeta += '<a class="produk-inline-link" href="' + encodeURI(row.latest_notif_document_url) + '" target="_blank" rel="noopener noreferrer">View Document</a>';
                    }
                }

                var badgeHtml = '<div class="produk-kemasan-badges">' + extraMeta + renderStatusTrigger(row.id, field, value) + '</div>';
                return '<div class="produk-kemasan-line">'
                    + '<div class="produk-cell-title">' + escapeHtml(label) + '</div>'
                    + badgeHtml
                    + '</div>';
            }

            parts.push(partHtml('FPP', 'status_administrasi_fpp', row.status_administrasi_fpp));
            parts.push('<div class="produk-cell-divider"></div>');
            parts.push(partHtml('SPK', 'status_administrasi_spk', row.status_administrasi_spk));
            parts.push('<div class="produk-cell-divider"></div>');
            parts.push(partHtml('NOTIF', 'status_administrasi_notif', row.status_administrasi_notif));

            return '<div class="produk-cell-stack">' + parts.join('') + '</div>';
        }

        function buildInlineStatusOptions(field) {
            var options = {};

            (inlineStatusOptions[field] || []).forEach(function (option) {
                options[option] = option;
            });

            options.__EMPTY__ = 'Kosongkan status';

            return options;
        }

        function filterKemasanOptions(field, keyword) {
            var tipe = field === 'kemasan_premier_id' ? 'primer' : 'sekunder';
            var search = $.trim(String(keyword || '')).toLowerCase();

            return kemasanOptions.filter(function (option) {
                if (option.tipe_kemasan !== tipe) {
                    return false;
                }

                if (!search) {
                    return true;
                }

                return option.label.toLowerCase().indexOf(search) !== -1;
            });
        }

        function renderKemasanPickerList() {
            var field = activeKemasanPicker.field;
            var productId = activeKemasanPicker.productId;
            var selectedValue = String(activeKemasanPicker.selectedValue || '');
            var keyword = $('#kemasanPickerSearch').val();
            var items = filterKemasanOptions(field, keyword);
            var html = [];

            if (field === 'kemasan_sekunder_id') {
                html.push('<button type="button" class="produk-kemasan-picker-item js-select-kemasan-option' + (selectedValue === '' ? ' is-active' : '') + '" data-id="' + productId + '" data-field="' + field + '" data-value="">Tanpa Kemasan Sekunder<span class="produk-kemasan-picker-item-meta">Kosongkan pilihan sekunder</span></button>');
            }

            items.forEach(function (option) {
                var isActive = String(option.id) === selectedValue;
                var currentMeta = isActive ? '<span class="produk-kemasan-picker-item-meta">Dipilih</span>' : '';
                html.push('<button type="button" class="produk-kemasan-picker-item js-select-kemasan-option' + (isActive ? ' is-active' : '') + '" data-id="' + productId + '" data-field="' + field + '" data-value="' + option.id + '">' + escapeHtml(option.label) + currentMeta + '</button>');
            });

            if (!html.length) {
                html.push('<div class="produk-kemasan-picker-empty">Tidak ada kemasan yang cocok.</div>');
            }

            $('#kemasanPickerList').html(html.join(''));
        }

        function openKemasanPicker(productId, field, value, kemasanVendorValue, desainVendorValue) {
            var config = getKemasanRelationConfig(field);

            activeKemasanPicker = {
                productId: productId,
                field: field,
                currentValue: value || '',
                selectedValue: value || '',
                kemasanVendorValue: kemasanVendorValue || '',
                desainVendorValue: desainVendorValue || ''
            };

            $('#kemasanPickerModalLabel').text(config.title);
            $('#kemasanPickerSearch').val('');
            $('#kemasanPickerVendorId').val(activeKemasanPicker.kemasanVendorValue || '');
            $('#kemasanPickerDesainVendorId').val(activeKemasanPicker.desainVendorValue || '');
            renderKemasanPickerList();
            $('#kemasanPickerModal').modal('show');
        }

        function formatProdukLogDate(value) {
            if (!value) {
                return '-';
            }

            var date = new Date(value);

            if (isNaN(date.getTime())) {
                return escapeHtml(value);
            }

            return escapeHtml(date.toLocaleString('id-ID', {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            }));
        }

        function renderProdukLogs(logs) {
            if (!logs || !logs.length) {
                return '<div class="produk-log-empty">Belum ada log untuk produk ini.</div>';
            }

            return logs.map(function (log) {
                var isUserLog = String(log.status_activity || '').toLowerCase() === 'user-note';
                var sourceLabel = isUserLog ? 'User' : 'System';
                var sourceClass = isUserLog ? ' produk-log-source-user' : ' produk-log-source-system';

                return '<div class="produk-log-item">'
                    + '<div class="produk-log-item-head">'
                    + '<div class="produk-log-item-meta"><div class="produk-log-item-status">' + escapeHtml(log.status_activity || '-') + '</div><span class="produk-log-source' + sourceClass + '">' + sourceLabel + '</span></div>'
                    + '<div class="produk-log-item-time">' + formatProdukLogDate(log.log_date_time) + '</div>'
                    + '</div>'
                    + '<div class="produk-log-item-notes">' + escapeHtml(log.notes || '-') + '</div>'
                    + '</div>';
            }).join('');
        }

        function setProdukLogPanel(logs, isEdit) {
            $('#produkLogPane').toggleClass('is-hidden', !isEdit);
            $('#produkLogList').html(isEdit ? renderProdukLogs(logs || []) : '<div class="produk-log-empty">Pilih produk untuk melihat log.</div>');
        }

        function submitInlineStatus(id, field, value, extraData) {
            var isFormData = extraData instanceof FormData;
            var requestData;

            if (isFormData) {
                requestData = extraData;
                requestData.append('_method', 'PUT');
                requestData.append('inline_status_update', '1');
                requestData.append('field', field);
                requestData.append('value', value);
            } else {
                requestData = $.extend({
                    _method: 'PUT',
                    inline_status_update: 1,
                    field: field,
                    value: value
                }, extraData || {});
            }

            $.ajax({
                url: '{{ url('/rnd/produk') }}/' + id,
                type: 'POST',
                data: requestData,
                processData: !isFormData,
                contentType: isFormData ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
                success: function (response) {
                    table.ajax.reload(null, false);
                    Swal.close();
                    Swal.fire({
                        title: 'Sukses',
                        text: response.message || 'Status berhasil diperbarui.',
                        icon: 'success',
                        timer: 1400,
                        showConfirmButton: false
                    });
                },
                error: function (xhr) {
                    var message = 'Terjadi kesalahan saat memperbarui status.';

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    Swal.fire('Validasi gagal', message, 'warning');
                }
            });
        }

        function submitInlineRelation(id, field, value, extraData) {
            $.ajax({
                url: '{{ url('/rnd/produk') }}/' + id,
                type: 'POST',
                data: $.extend({
                    _method: 'PUT',
                    inline_relation_update: 1,
                    field: field,
                    value: value
                }, extraData || {}),
                success: function (response) {
                    table.ajax.reload(null, false);
                    Swal.close();
                    Swal.fire({
                        title: 'Sukses',
                        text: response.message || 'Kemasan berhasil diperbarui.',
                        icon: 'success',
                        timer: 1400,
                        showConfirmButton: false
                    });
                },
                error: function (xhr) {
                    var message = 'Terjadi kesalahan saat memperbarui kemasan.';

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    Swal.fire('Validasi gagal', message, 'warning');
                }
            });
        }

        function initProdukSelect2() {
            $('.select2-produk').each(function () {
                var $select = $(this);
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }

                $select.select2({
                    width: '100%',
                    dropdownParent: $('#produkModal')
                });
            });
        }

        function initKemasanModalSelect2() {
            $('.select2-kemasan-modal').each(function () {
                var $select = $(this);
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }

                $select.select2({
                    width: '100%',
                    dropdownParent: $('#kemasanPickerModal'),
                    allowClear: true,
                    placeholder: $select.find('option:first').text()
                });
            });
        }

        function clearProdukFieldError($field) {
            $field.removeClass('is-invalid');

            if ($field.hasClass('select2-hidden-accessible')) {
                $field.next('.select2-container').find('.select2-selection').removeClass('is-invalid');
            }
        }

        function markProdukFieldError($field) {
            $field.addClass('is-invalid');

            if ($field.hasClass('select2-hidden-accessible')) {
                $field.next('.select2-container').find('.select2-selection').addClass('is-invalid');
            }
        }

        function showProdukValidationTab(tabSelector) {
            if (tabSelector) {
                $(tabSelector).tab('show');
            }
        }

        function focusProdukField($field) {
            if ($field.hasClass('select2-hidden-accessible')) {
                $field.next('.select2-container').find('.select2-selection').trigger('focus');
                return;
            }

            $field.trigger('focus');
        }

        function validateProdukForm() {
            var isEdit = $.trim($('#produk_id').val() || '') !== '';
            var requiredFields = [
                { selector: '#nama_produk', label: 'Nama Produk', tab: '#produk-base-tab' }
            ];

            if (!isEdit) {
                requiredFields.unshift({ selector: '#brand_id', label: 'Brand', tab: '#produk-base-tab' });
                requiredFields.push({ selector: '#sediaan_id', label: 'Sediaan', tab: '#produk-base-tab' });
                requiredFields.push({ selector: '#kemasan_premier_id', label: 'Jenis Kemasan Primer', tab: '#produk-kemasan-tab' });
            }

            var firstInvalid = null;

            requiredFields.forEach(function (fieldConfig) {
                var $field = $(fieldConfig.selector);
                var value = $field.val();
                var isEmpty = Array.isArray(value) ? value.length === 0 : $.trim(String(value || '')) === '';

                clearProdukFieldError($field);

                if (isEmpty && !firstInvalid) {
                    firstInvalid = {
                        field: $field,
                        label: fieldConfig.label,
                        tab: fieldConfig.tab
                    };
                }
            });

            if (!firstInvalid) {
                return true;
            }

            markProdukFieldError(firstInvalid.field);
            showProdukValidationTab(firstInvalid.tab);

            setTimeout(function () {
                focusProdukField(firstInvalid.field);
            }, 150);

            Swal.fire('Validasi gagal', firstInvalid.label + ' wajib diisi.', 'warning');

            return false;
        }

        function toggleKemasanSekunderFields() {
            var hasSekunder = $.trim($('#kemasan_sekunder_id').val() || '') !== '';
            var $card = $('#produkSekunderCard');
            var $fields = $('.js-kemasan-sekunder-dependent');

            $card.toggleClass('is-disabled', !hasSekunder);
            $fields.prop('disabled', !hasSekunder);

            $fields.each(function () {
                var $field = $(this);

                if ($field.hasClass('select2-hidden-accessible')) {
                    $field.trigger('change.select2');
                }
            });
        }

        function resetProdukTabs() {
            $('#produk-base-tab').tab('show');
        }

        function formatDocumentFileSize(sizeBytes) {
            if (!sizeBytes) {
                return '';
            }

            if (sizeBytes >= 1024 * 1024) {
                return (sizeBytes / (1024 * 1024)).toFixed(1) + ' MB';
            }

            return Math.max(1, Math.round(sizeBytes / 1024)) + ' KB';
        }

        function formatTimelineDate(value) {
            if (!value) {
                return '-';
            }

            var date = new Date(value + 'T00:00:00');

            if (isNaN(date.getTime())) {
                return escapeHtml(value);
            }

            return escapeHtml(date.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            }));
        }

        function buildProductTimelineEvents(entries) {
            return (entries || []).map(function (entry) {
                return {
                    id: String(entry.id || ''),
                    title: entry.produk_name || 'Produk',
                    start: entry.timeline_date,
                    allDay: true,
                    backgroundColor: '#0f766e',
                    borderColor: '#0f766e',
                    extendedProps: {
                        produkName: entry.produk_name || '-',
                        notes: entry.notes || '-',
                        createdAt: entry.created_at || '',
                        timelineDate: entry.timeline_date || ''
                    }
                };
            });
        }

        function filterTimelineEntriesByDate(dateString) {
            return productTimelineEntries.filter(function (entry) {
                return String(entry.timeline_date || '') === String(dateString || '');
            });
        }

        function updateProductTimelineDetail(entries, dateLabel) {
            var items = entries || [];
            $('#productTimelineSelectedDate').text(dateLabel || 'Belum ada tanggal dipilih');
            $('#productTimelineDetailCount').text(items.length + ' item');
            $('#productTimelineDetailCaption').text(items.length ? 'Daftar timeline pada tanggal yang dipilih.' : 'Tidak ada timeline pada tanggal yang dipilih.');
            setProductTimelineIndexList(items);
        }

        function openTimelineDetailByDate(dateString) {
            updateProductTimelineDetail(filterTimelineEntriesByDate(dateString), formatTimelineDate(dateString));
        }

        function ensureProductTimelineCalendar() {
            var calendarEl = document.getElementById('productTimelineCalendar');

            if (!calendarEl || typeof FullCalendar === 'undefined') {
                return;
            }

            if (!productTimelineCalendar) {
                productTimelineCalendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    locale: 'id',
                    height: 680,
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,listMonth'
                    },
                    buttonText: {
                        today: 'Hari ini',
                        month: 'Bulan',
                        list: 'Daftar'
                    },
                    noEventsContent: 'Belum ada timeline produk.',
                    eventClick: function (info) {
                        openTimelineDetailByDate(info.event.startStr);
                    },
                    dateClick: function (info) {
                        openTimelineDetailByDate(info.dateStr);
                    }
                });
                productTimelineCalendar.render();
            }

            productTimelineCalendar.removeAllEvents();
            productTimelineCalendar.addEventSource(buildProductTimelineEvents(productTimelineEntries));
            productTimelineCalendar.updateSize();
        }

        function renderProductTimelineIndex(entries) {
            if (!entries || !entries.length) {
                return '<div class="produk-log-empty">Belum ada timeline untuk bagian ini.</div>';
            }

            return entries.map(function (entry) {
                return '<div class="produk-timeline-item">'
                    + '<div class="produk-timeline-head">'
                    + '<div class="produk-timeline-meta">'
                    + '<div class="produk-timeline-product">' + escapeHtml(entry.produk_name || '-') + '</div>'
                    + '<div class="produk-timeline-date">' + formatTimelineDate(entry.timeline_date) + '</div>'
                    + '</div>'
                    + '<div class="produk-timeline-created">' + formatProdukLogDate(entry.created_at) + '</div>'
                    + '</div>'
                    + '<div class="produk-timeline-notes">' + escapeHtml(entry.notes || '-') + '</div>'
                    + '</div>';
            }).join('');
        }

        function setProductTimelineIndexList(entries) {
            $('#productTimelineIndexList').html(renderProductTimelineIndex(entries || []));
        }

        function loadProductTimelineIndex() {
            $.get('{{ route('rnd.products.timeline-data') }}', function (response) {
                productTimelineEntries = response.data || [];
                ensureProductTimelineCalendar();

                if (productTimelineEntries.length) {
                    updateProductTimelineDetail([productTimelineEntries[0]], formatTimelineDate(productTimelineEntries[0].timeline_date));
                } else {
                    updateProductTimelineDetail([], 'Belum ada tanggal dipilih');
                }
            }).fail(function () {
                productTimelineEntries = [];
                ensureProductTimelineCalendar();
                updateProductTimelineDetail([], 'Belum ada tanggal dipilih');
            });
        }

        function renderAdditionalDocumentList(documents) {
            if (!documents || !documents.length) {
                return '<div class="produk-log-empty">Belum ada dokumen tambahan.</div>';
            }

            return documents.map(function (documentItem) {
                var meta = [];

                if (documentItem.size_bytes) {
                    meta.push(formatDocumentFileSize(documentItem.size_bytes));
                }

                return '<div class="produk-document-item">'
                    + '<div class="produk-document-main">'
                    + '<a class="produk-document-link" href="' + encodeURI(documentItem.url) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(documentItem.original_name || 'Dokumen tambahan') + '</a>'
                    + '<div class="produk-document-meta">' + escapeHtml(meta.join(' | ') || 'File tersimpan') + '</div>'
                    + '</div>'
                    + '<div class="produk-document-actions">'
                    + '<button type="button" class="produk-document-delete js-delete-additional-document" data-id="' + documentItem.id + '">Hapus</button>'
                    + '</div>'
                    + '</div>';
            }).join('');
        }

        function deleteAdditionalDocument(documentId) {
            $.ajax({
                url: '{{ url('/rnd/produk/documents') }}/' + documentId,
                type: 'POST',
                data: {
                    _method: 'DELETE'
                },
                success: function (response) {
                    setAdditionalDocumentList(response.data && response.data.additional_documents ? response.data.additional_documents : []);
                    table.ajax.reload(null, false);
                    Swal.fire({
                        title: 'Sukses',
                        text: response.message || 'Dokumen tambahan berhasil dihapus.',
                        icon: 'success',
                        timer: 1400,
                        showConfirmButton: false
                    });
                },
                error: function (xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal menghapus dokumen tambahan.';
                    Swal.fire('Error', message, 'error');
                }
            });
        }

        function setAdditionalDocumentList(documents) {
            $('#additionalDocumentsList').html(renderAdditionalDocumentList(documents || []));
        }

        function updateAdditionalDocumentsSummary() {
            var input = $('#additional_documents')[0];
            var files = input && input.files ? Array.prototype.slice.call(input.files) : [];
            var canUpload = !!$.trim($('#produk_id').val() || '') && files.length > 0;

            $('#submitAdditionalDocumentsBtn').prop('disabled', !canUpload);

            if (!files.length) {
                $('#additionalDocumentsSummary').text($.trim($('#produk_id').val() || '') ? 'Belum ada file dipilih.' : 'Simpan produk dulu sebelum upload dokumen.');
                return;
            }

            if (files.length === 1) {
                $('#additionalDocumentsSummary').text(files[0].name);
                return;
            }

            $('#additionalDocumentsSummary').text(files.length + ' file dipilih');
        }

        function uploadAdditionalDocuments() {
            var productId = $.trim($('#produk_id').val() || '');
            var input = $('#additional_documents')[0];
            var files = input && input.files ? Array.prototype.slice.call(input.files) : [];

            if (!productId) {
                Swal.fire('Informasi', 'Simpan produk terlebih dulu sebelum upload dokumen tambahan.', 'info');
                return;
            }

            if (!files.length) {
                Swal.fire('Informasi', 'Pilih minimal satu file untuk diunggah.', 'info');
                return;
            }

            var formData = new FormData();

            files.forEach(function (file) {
                formData.append('additional_documents[]', file);
            });

            $.ajax({
                url: '{{ url('/rnd/produk') }}/' + productId + '/documents',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $('#additional_documents').val('');
                    setAdditionalDocumentList(response.data && response.data.additional_documents ? response.data.additional_documents : []);
                    updateAdditionalDocumentsSummary();
                    table.ajax.reload(null, false);
                    Swal.fire({
                        title: 'Sukses',
                        text: response.message || 'Dokumen tambahan berhasil diunggah.',
                        icon: 'success',
                        timer: 1400,
                        showConfirmButton: false
                    });
                },
                error: function (xhr) {
                    var message = 'Gagal mengunggah dokumen tambahan.';

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    Swal.fire('Validasi gagal', message, 'warning');
                }
            });
        }

        function resetForm() {
            $('#produkForm')[0].reset();
            $('#produk_id').val('');
            $('.select2-produk').val(null).trigger('change');
            $('#additional_documents').val('');
            setProdukLogPanel([], false);
            setAdditionalDocumentList([]);
            updateAdditionalDocumentsSummary();
            resetProdukTabs();
            toggleKemasanSekunderFields();
        }

        function toggleProdukFormMode(isEdit) {
            $('#deleteProdukBtn').toggleClass('d-none', !isEdit).attr('data-id', '');
            $('#produkLogPane').toggleClass('is-hidden', !isEdit);
        }

        function renderActionColumn(row) {
            return '<div class="produk-table-actions">'
                + '<button type="button" class="produk-action-button js-edit-product" data-id="' + row.id + '">Edit</button>'
                + '<button type="button" class="produk-action-button produk-action-button-secondary js-add-product-timeline" data-id="' + row.id + '">Timeline</button>'
                + '</div>';
        }

        function submitProductTimeline(productId, timelineDate, notes) {
            $.ajax({
                url: '{{ url('/rnd/produk') }}/' + productId + '/timelines',
                type: 'POST',
                data: {
                    timeline_date: timelineDate,
                    notes: notes
                },
                success: function (response) {
                    var currentProductId = $.trim($('#produk_id').val() || '');

                    loadProductTimelineIndex();

                    $('#produk-timeline-index-tab').tab('show');

                    if (String(currentProductId) === String(productId)) {
                        $.get('{{ url('/rnd/produk') }}/' + productId, function (detailResponse) {
                            var detailData = detailResponse.data || {};
                            setProdukLogPanel(detailData.product_logs || [], true);
                        });
                    }

                    Swal.fire({
                        title: 'Sukses',
                        text: response.message || 'Timeline produk berhasil ditambahkan.',
                        icon: 'success',
                        timer: 1400,
                        showConfirmButton: false
                    });
                },
                error: function (xhr) {
                    var message = 'Gagal menambahkan timeline produk.';

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    Swal.fire('Validasi gagal', message, 'warning');
                }
            });
        }

        function openEditProdukModal(id) {
            resetForm();
            toggleProdukFormMode(true);

            $.get('{{ url('/rnd/produk') }}/' + id, function (response) {
                var data = response.data || {};
                $('#produkModalLabel').text('Edit Produk');
                $('#produk_id').val(data.id || '');
                $('#nama_produk').val(data.nama_produk || '');
                $('#netto').val(data.netto || '');
                $('#brand_id').val(data.brand_id || '').trigger('change');
                $('#produsen_vendor_id').val(data.produsen_vendor_id || '').trigger('change');
                $('#bahan_aktif_ids').val(data.bahan_aktif_ids || []).trigger('change');
                $('#kemasan_premier_id').val(data.kemasan_premier_id || '').trigger('change');
                $('#kemasan_sekunder_id').val(data.kemasan_sekunder_id || '').trigger('change');
                $('#kemasan_primer_vendor_id').val(data.kemasan_primer_vendor_id || '').trigger('change');
                $('#kemasan_sekunder_vendor_id').val(data.kemasan_sekunder_vendor_id || '').trigger('change');
                $('#desain_kemasan_primer_id').val(data.desain_kemasan_primer_id || '').trigger('change');
                $('#desain_kemasan_sekunder_id').val(data.desain_kemasan_sekunder_id || '').trigger('change');
                $('#sediaan_id').val(data.sediaan_id || '').trigger('change');
                $('#status_administrasi_fpp').val(data.status_administrasi_fpp || '');
                $('#status_administrasi_spk').val(data.status_administrasi_spk || '');
                $('#status_administrasi_notif').val(data.status_administrasi_notif || '');
                $('#status_kemasan_primer').val(data.status_kemasan_primer || '');
                $('#status_kemasan_sekunder').val(data.status_kemasan_sekunder || '');
                $('#status_desain_kemasan_primer').val(data.status_desain_kemasan_primer || '');
                $('#status_desain_kemasan_sekunder').val(data.status_desain_kemasan_sekunder || '');
                $('#deleteProdukBtn').attr('data-id', data.id || '');
                setProdukLogPanel(data.product_logs || [], true);
                setAdditionalDocumentList(data.additional_documents || []);
                updateAdditionalDocumentsSummary();
                toggleKemasanSekunderFields();
                resetProdukTabs();
                $('#produkModal').modal('show');
            });
        }

        function confirmDeleteProduk(id) {
            Swal.fire({
                title: 'Hapus produk?',
                text: 'Data produk yang dihapus tidak bisa dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (!result.value) {
                    return;
                }

                $.ajax({
                    url: '{{ url('/rnd/produk') }}/' + id,
                    type: 'POST',
                    data: { _method: 'DELETE' },
                    success: function (response) {
                        $('#produkModal').modal('hide');
                        table.ajax.reload(null, false);
                        Swal.fire('Sukses', response.message || 'Produk berhasil dihapus.', 'success');
                    },
                    error: function (xhr) {
                        var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal menghapus produk.';
                        Swal.fire('Error', message, 'error');
                    }
                });
            });
        }

        var table = $('#produkTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('rnd.products.data') }}',
            columns: [
                { data: null, orderable: false, searchable: false, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                {
                    data: null,
                    name: 'nama_produk',
                    render: function(data, type, row) {
                        return renderNamaColumn(row);
                    }
                },
                {
                    data: null,
                    name: 'status_sample',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return renderSampleColumn(row);
                    }
                },
                {
                    data: null,
                    name: 'kemasanPremier.nama_kemasan',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return renderCombinedKemasanColumn(row);
                    }
                },
                {
                    data: null,
                    name: 'status_administrasi_fpp',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return renderAdministrasiColumn(row);
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: function(data, type, row) {
                        return renderActionColumn(row);
                    }
                }
            ]
        });

        initProdukSelect2();
        initKemasanModalSelect2();
        toggleKemasanSekunderFields();
        loadProductTimelineIndex();

        $('a[data-toggle="tab"][href="#produk-timeline-index-pane"]').on('shown.bs.tab', function () {
            ensureProductTimelineCalendar();
        });

        $('#pickAdditionalDocumentsBtn').on('click', function () {
            if (!$.trim($('#produk_id').val() || '')) {
                Swal.fire('Informasi', 'Simpan produk terlebih dulu sebelum upload dokumen tambahan.', 'info');
                return;
            }

            $('#additional_documents').trigger('click');
        });

        $('#additional_documents').on('change', function () {
            updateAdditionalDocumentsSummary();
        });

        $('#submitAdditionalDocumentsBtn').on('click', function () {
            uploadAdditionalDocuments();
        });

        $('body').on('click', '.js-delete-additional-document', function () {
            var documentId = $(this).data('id');

            Swal.fire({
                title: 'Hapus dokumen?',
                text: 'Dokumen tambahan yang dihapus tidak bisa dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (!result.value) {
                    return;
                }

                deleteAdditionalDocument(documentId);
            });
        });

        $('#kemasan_sekunder_id').on('change', function () {
            if (!$.trim($(this).val() || '')) {
                $('.js-kemasan-sekunder-dependent').val('').trigger('change');
            }

            toggleKemasanSekunderFields();
        });

        $('#createNewProduk').on('click', function () {
            resetForm();
            toggleProdukFormMode(false);
            $('#produkModalLabel').text('Tambah Produk');
            $('#produkModal').modal('show');
        });

        $('body').on('click', '.js-edit-product', function () {
            openEditProdukModal($(this).data('id'));
        });

        $('body').on('click', '.js-add-product-timeline', function () {
            var productId = $(this).data('id');

            Swal.fire({
                title: 'Tambah Timeline',
                html: ''
                    + '<input type="date" id="swal-timeline-date" class="swal2-input">'
                    + '<textarea id="swal-timeline-notes" class="swal2-textarea" placeholder="Tulis catatan timeline produk"></textarea>',
                showCancelButton: true,
                confirmButtonText: 'Simpan Timeline',
                cancelButtonText: 'Batal',
                focusConfirm: false,
                didOpen: function () {
                    $('#swal-timeline-date').val(new Date().toISOString().slice(0, 10));
                },
                preConfirm: function () {
                    var timelineDate = $.trim($('#swal-timeline-date').val() || '');
                    var notes = $.trim($('#swal-timeline-notes').val() || '');

                    if (!timelineDate) {
                        Swal.showValidationMessage('Tanggal timeline wajib diisi.');
                        return false;
                    }

                    if (!notes) {
                        Swal.showValidationMessage('Catatan timeline wajib diisi.');
                        return false;
                    }

                    return {
                        timelineDate: timelineDate,
                        notes: notes
                    };
                }
            }).then(function (result) {
                if (!result.value) {
                    return;
                }

                submitProductTimeline(productId, result.value.timelineDate, result.value.notes);
            });
        });

        $('#produkForm').on('submit', function (e) {
            e.preventDefault();

            if (!validateProdukForm()) {
                return;
            }

            var id = $('#produk_id').val();
            var formData = new FormData(this);

            formData.delete('additional_documents[]');
            formData.delete('additional_documents');

            if (id) {
                formData.append('_method', 'PUT');
            }

            if (!$.trim($('#kemasan_sekunder_id').val() || '')) {
                ['kemasan_sekunder_vendor_id', 'desain_kemasan_sekunder_id', 'status_kemasan_sekunder', 'status_desain_kemasan_sekunder'].forEach(function (field) {
                    formData.append(field, '');
                });
            }

            $.ajax({
                url: id ? '{{ url('/rnd/produk') }}/' + id : '{{ route('rnd.products.store') }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $('#produkModal').modal('hide');
                    table.ajax.reload(null, false);
                    Swal.fire('Sukses', response.message || 'Produk berhasil disimpan.', 'success');
                },
                error: function (xhr) {
                    var message = 'Terjadi kesalahan saat menyimpan data.';
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    Swal.fire('Validasi gagal', message, 'warning');
                }
            });
        });

        $('#deleteProdukBtn').on('click', function () {
            var id = $(this).data('id');

            if (!id) {
                return;
            }

            confirmDeleteProduk(id);
        });

        $('#produkForm').on('input change', 'input, select, textarea', function () {
            clearProdukFieldError($(this));
        });

        $('body').on('click', '.js-inline-status', function () {
            var id = $(this).data('id');
            var field = $(this).data('field');
            var value = $(this).data('value') || '';

            function openNotifDoneDialog() {
                Swal.fire({
                    title: 'Lengkapi Notif',
                    html: ''
                        + '<input type="date" id="swal-notif-tanggal-mulai" class="swal2-input" placeholder="Tanggal Mulai">'
                        + '<input type="date" id="swal-notif-tanggal-selesai" class="swal2-input" placeholder="Tanggal Selesai">'
                        + '<input type="file" id="swal-notif-doc" class="swal2-file" accept="application/pdf">'
                        + '<textarea id="swal-log-notes" class="swal2-textarea" placeholder="Catatan log (opsional)"></textarea>',
                    showCancelButton: true,
                    confirmButtonText: 'Simpan',
                    cancelButtonText: 'Batal',
                    focusConfirm: false,
                    preConfirm: function () {
                        var tanggalMulai = $('#swal-notif-tanggal-mulai').val();
                        var tanggalSelesai = $('#swal-notif-tanggal-selesai').val();
                        var logNotes = $('#swal-log-notes').val();
                        var fileInput = document.getElementById('swal-notif-doc');
                        var file = fileInput && fileInput.files ? fileInput.files[0] : null;

                        if (!tanggalMulai) {
                            Swal.showValidationMessage('Tanggal mulai wajib diisi.');
                            return false;
                        }

                        if (!tanggalSelesai) {
                            Swal.showValidationMessage('Tanggal selesai wajib diisi.');
                            return false;
                        }

                        if (tanggalSelesai < tanggalMulai) {
                            Swal.showValidationMessage('Tanggal selesai tidak boleh lebih kecil dari tanggal mulai.');
                            return false;
                        }

                        if (!file) {
                            Swal.showValidationMessage('Dokumen PDF wajib diupload.');
                            return false;
                        }

                        if (!/\.pdf$/i.test(file.name)) {
                            Swal.showValidationMessage('Dokumen harus berupa PDF.');
                            return false;
                        }

                        return {
                            tanggal_mulai: tanggalMulai,
                            tanggal_selesai: tanggalSelesai,
                            file: file,
                            log_notes: $.trim(logNotes || '')
                        };
                    }
                }).then(function (notifResult) {
                    if (!notifResult.value) {
                        return;
                    }

                    var formData = new FormData();
                    formData.append('tanggal_mulai', notifResult.value.tanggal_mulai);
                    formData.append('tanggal_selesai', notifResult.value.tanggal_selesai);
                    formData.append('notif_doc', notifResult.value.file);
                    formData.append('log_notes', notifResult.value.log_notes || '');

                    submitInlineStatus(id, field, 'done', formData);
                });
            }

            function openLogNotesDialog(nextValue) {
                Swal.fire({
                    title: 'Catatan Log',
                    input: 'textarea',
                    inputPlaceholder: 'Tambahkan catatan untuk perubahan status ini (opsional)',
                    inputAttributes: {
                        'aria-label': 'Catatan log'
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Simpan',
                    cancelButtonText: 'Batal'
                }).then(function (noteResult) {
                    if (typeof noteResult.value === 'undefined') {
                        return;
                    }

                    submitInlineStatus(id, field, nextValue, {
                        log_notes: $.trim(noteResult.value || '')
                    });
                });
            }

            Swal.fire({
                title: 'Ubah status',
                input: 'radio',
                inputOptions: buildInlineStatusOptions(field),
                inputValue: value || '__EMPTY__',
                showConfirmButton: true,
                confirmButtonText: 'Simpan',
                showCancelButton: false,
                showCloseButton: true,
                allowOutsideClick: true,
                allowEscapeKey: true,
                inputValidator: function (selectedValue) {
                    if (typeof selectedValue === 'undefined' || selectedValue === null) {
                        return 'Pilih salah satu status.';
                    }
                }
            }).then(function (result) {
                if (!result.value) {
                    return;
                }

                var nextValue = result.value === '__EMPTY__' ? '' : result.value;

                if (field === 'status_administrasi_notif' && nextValue === 'done') {
                    openNotifDoneDialog();
                    return;
                }

                openLogNotesDialog(nextValue);
            });
        });

        $('#kemasanPickerSearch').on('input', function () {
            renderKemasanPickerList();
        });

        $('body').on('click', '.js-inline-kemasan', function () {
            openKemasanPicker(
                $(this).data('id'),
                $(this).data('field'),
                $(this).data('value') || '',
                $(this).data('kemasan-vendor-value') || '',
                $(this).data('desain-vendor-value') || ''
            );
        });

        $('body').on('click', '.js-select-kemasan-option', function () {
            activeKemasanPicker.selectedValue = String($(this).data('value') || '');
            renderKemasanPickerList();
        });

        $('#saveKemasanPickerBtn').on('click', function () {
            var field = activeKemasanPicker.field;
            var id = activeKemasanPicker.productId;
            var value = activeKemasanPicker.selectedValue;
            var config = getKemasanRelationConfig(field);

            if (!field || !id) {
                return;
            }

            if (field === 'kemasan_premier_id' && !String(value || '').length) {
                Swal.fire('Validasi gagal', 'Kemasan primer wajib dipilih.', 'warning');
                return;
            }

            $('#kemasanPickerModal').modal('hide');
            submitInlineRelation(id, field, value, {
                [config.vendorField]: $('#kemasanPickerVendorId').val() || '',
                [config.desainVendorField]: $('#kemasanPickerDesainVendorId').val() || ''
            });
        });

        $('body').on('click', '.js-add-sample', function () {
            var id = $(this).data('id');

            Swal.fire({
                title: 'Add Sample',
                html: ''
                    + '<input type="text" id="swal-sample-no-produksi" class="swal2-input" placeholder="No Produksi">'
                    + '<textarea id="swal-sample-notes" class="swal2-textarea" placeholder="Notes"></textarea>',
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
                focusConfirm: false,
                preConfirm: function () {
                    var noProduksi = $('#swal-sample-no-produksi').val();
                    var notes = $('#swal-sample-notes').val();

                    if (!$.trim(noProduksi || '')) {
                        Swal.showValidationMessage('No Produksi wajib diisi.');
                        return false;
                    }

                    return {
                        no_produksi: $.trim(noProduksi),
                        notes: $.trim(notes || '')
                    };
                }
            }).then(function (result) {
                if (!result.value || !result.value) {
                    return;
                }

                $.ajax({
                    url: '{{ url('/rnd/produk') }}/' + id,
                    type: 'POST',
                    data: {
                        _method: 'PUT',
                        add_sample_log: 1,
                        no_produksi: result.value.no_produksi,
                        notes: result.value.notes
                    },
                    success: function (response) {
                        table.ajax.reload(null, false);
                        Swal.fire('Sukses', response.message || 'Sample berhasil ditambahkan.', 'success');
                    },
                    error: function (xhr) {
                        var message = 'Terjadi kesalahan saat menambahkan sample.';

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }

                        Swal.fire('Validasi gagal', message, 'warning');
                    }
                });
            });
        });

        $('body').on('click', '.js-sample-history', function () {
            var id = $(this).data('id');

            openSampleHistory(id);
        });

        $('body').on('click', '.js-edit-sample-notes', function () {
            var productId = $(this).data('product-id');
            var sampleId = $(this).data('sample-id');
            var notes = decodeURIComponent($(this).data('notes') || '');
            var reopenHistory = String($(this).data('reopen-history') || '') === '1';

            Swal.fire({
                title: 'Edit Notes Sample',
                input: 'textarea',
                inputValue: notes,
                inputPlaceholder: 'Notes',
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    if (reopenHistory) {
                        openSampleHistory(productId);
                    }
                    return;
                }

                updateSampleNotes(sampleId, $.trim(result.value || ''), function () {
                    if (reopenHistory) {
                        openSampleHistory(productId);
                        return;
                    }

                    Swal.fire('Sukses', 'Catatan sample berhasil diperbarui.', 'success');
                });
            });
        });

        $('body').on('click', '.js-delete-sample', function () {
            var productId = $(this).data('product-id');
            var sampleId = $(this).data('sample-id');
            var reopenHistory = String($(this).data('reopen-history') || '') === '1';

            Swal.fire({
                title: 'Hapus sample?',
                text: 'Sample yang dihapus tidak bisa dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    if (reopenHistory) {
                        openSampleHistory(productId);
                    }
                    return;
                }

                deleteSample(sampleId, function () {
                    if (reopenHistory) {
                        openSampleHistory(productId);
                        return;
                    }

                    Swal.fire('Sukses', 'Sample berhasil dihapus.', 'success');
                });
            });
        });
    });
</script>
@endsection