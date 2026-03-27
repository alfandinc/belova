@extends('layouts.daily_journal.app')

@section('title', 'Division Journal')

@section('navbar')
    @include('layouts.daily_journal.navbar')
@endsection

@section('styles')
    <style>
        :root {
            --page-bg: linear-gradient(180deg, #fff9f6 0%, #fffdfb 45%, #f7fafc 100%);
            --shell-bg: rgba(255, 255, 255, 0.96);
            --shell-border: rgba(148, 163, 184, 0.28);
            --text-main: #111827;
            --text-muted: #6b7280;
            --accent: #ff6b8a;
            --accent-soft: #ffe4ec;
            --shadow-soft: 0 30px 80px rgba(15, 23, 42, 0.14);
            --rose: linear-gradient(135deg, #fee7ee 0%, #fce0e8 100%);
            --lavender: linear-gradient(135deg, #efedff 0%, #ede8ff 100%);
            --mint: linear-gradient(135deg, #e7f4ec 0%, #e2f1e8 100%);
            --sky: linear-gradient(135deg, #e5f2ff 0%, #dcecff 100%);
            --peach: linear-gradient(135deg, #fff1e4 0%, #ffe6d0 100%);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            background: var(--page-bg);
            color: var(--text-main);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .journal-shell {
            max-width: 520px;
            margin: 0 auto;
            min-height: 100vh;
            padding: 18px 16px 110px;
            position: relative;
        }

        .journal-frame {
            background: var(--shell-bg);
            border: 1px solid var(--shell-border);
            border-radius: 30px;
            box-shadow: var(--shadow-soft);
            padding: 18px 14px 24px;
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .journal-topbar {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
        }

        .pill-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 0;
            border-radius: 999px;
            padding: 8px 12px;
            background: linear-gradient(135deg, #ff748d, #ff9db2);
            color: #fff;
            font-weight: 600;
            font-size: 13px;
            box-shadow: 0 12px 24px rgba(255, 107, 138, 0.25);
            min-height: 38px;
            cursor: pointer;
        }

        .assign-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 0;
            border-radius: 18px;
            padding: 14px 16px;
            background: linear-gradient(135deg, #ff6b8a, #ff93ac);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            box-shadow: 0 18px 36px rgba(255, 107, 138, 0.32);
            cursor: pointer;
            position: fixed;
            right: 18px;
            bottom: 18px;
            z-index: 30;
        }

        .assign-panel {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 40;
            background: #fff;
            border-radius: 30px 30px 0 0;
            box-shadow: 0 -18px 42px rgba(15, 23, 42, 0.12);
            transform: translate3d(0, 105%, 0);
            visibility: hidden;
            pointer-events: none;
            transition: transform 0.25s ease;
            padding: 16px 16px 26px;
            max-width: 520px;
            margin: 0 auto;
            will-change: transform;
            backface-visibility: hidden;
        }

        .assign-panel.active {
            transform: translate3d(0, 0, 0);
            visibility: visible;
            pointer-events: auto;
        }

        .assign-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.38);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
            z-index: 35;
            will-change: opacity;
        }

        .assign-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .assign-sheet-handle {
            width: 54px;
            height: 5px;
            background: #e5e7eb;
            border-radius: 999px;
            margin: 0 auto 14px;
        }

        .assign-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .assign-panel-title {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        .assign-panel-copy {
            margin: 4px 0 0;
            font-size: 13px;
            color: #64748b;
        }

        .close-assign-panel {
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 12px;
            background: #f3f4f6;
            font-size: 20px;
            cursor: pointer;
        }

        .journal-form {
            display: grid;
            gap: 12px;
        }

        .journal-select,
        .journal-textarea {
            width: 100%;
            border: 1px solid rgba(15, 23, 42, 0.1);
            border-radius: 16px;
            padding: 13px 14px;
            font-size: 14px;
            color: #111827;
            background: #fff;
        }

        .journal-textarea {
            min-height: 90px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .form-row > div {
            min-width: 0;
        }

        .theme-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
        }

        .theme-option input {
            display: none;
        }

        .theme-preview {
            display: block;
            width: 100%;
            height: 36px;
            border-radius: 12px;
            border: 2px solid transparent;
            cursor: pointer;
        }

        .theme-option input:checked + .theme-preview {
            border-color: #111827;
        }

        .theme-preview.rose { background: var(--rose); }
        .theme-preview.lavender { background: var(--lavender); }
        .theme-preview.mint { background: var(--mint); }
        .theme-preview.sky { background: var(--sky); }
        .theme-preview.peach { background: var(--peach); }

        .submit-button {
            border: 0;
            border-radius: 16px;
            padding: 13px 16px;
            background: linear-gradient(135deg, #111827, #334155);
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .field-error {
            margin-top: 6px;
            font-size: 12px;
            color: #dc2626;
        }

        .filter-panel {
            display: none;
            margin-bottom: 18px;
            padding: 14px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.82);
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.06);
            border: 1px solid rgba(15, 23, 42, 0.05);
        }

        .filter-panel.active {
            display: block;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .filter-choice {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            color: #374151;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            padding: 10px 12px;
        }

        .filter-choice.active {
            background: linear-gradient(135deg, #ff748d, #ff9db2);
            color: #fff;
        }

        .member-filter-card {
            margin-bottom: 12px;
            padding: 12px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(15, 23, 42, 0.05);
        }

        .member-filter-label,
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-size: 12px;
            font-weight: 700;
            color: #4b5563;
        }

        .member-filter-select,
        .journal-input {
            width: 100%;
            border: 1px solid rgba(15, 23, 42, 0.1);
            border-radius: 16px;
            padding: 13px 14px;
            font-size: 14px;
            color: #111827;
            background: #fff;
        }

        .custom-filter-form {
            display: grid;
            gap: 10px;
            padding-top: 8px;
            border-top: 1px solid rgba(15, 23, 42, 0.06);
        }

        .custom-filter-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .apply-filter-button {
            border: 0;
            border-radius: 16px;
            padding: 12px 14px;
            background: #111827;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
        }

        .page-title {
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 0.01em;
            margin: 0;
            text-align: center;
        }

        .header-summary {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
            min-width: 520px;
        }

        .header-stat {
            min-width: 88px;
            padding: 12px 14px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            text-align: center;
            border: 1px solid transparent;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }

        .header-stat:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
        }

        .header-stat.is-active {
            box-shadow: 0 18px 32px rgba(15, 23, 42, 0.14);
            transform: translateY(-1px);
            border-width: 2px;
        }

        .header-stat.stat-all.is-active {
            background: linear-gradient(135deg, #e8eef7, #f3f7fc);
            border-color: rgba(71, 85, 105, 0.55);
        }

        .header-stat.stat-todo.is-active {
            background: linear-gradient(135deg, #ffefc7, #fff7df);
            border-color: rgba(245, 158, 11, 0.6);
        }

        .header-stat.stat-progress.is-active {
            background: linear-gradient(135deg, #dbeafe, #eff6ff);
            border-color: rgba(59, 130, 246, 0.62);
        }

        .header-stat.stat-done.is-active {
            background: linear-gradient(135deg, #d9fbe8, #ecfdf5);
            border-color: rgba(16, 185, 129, 0.58);
        }

        .header-stat.stat-skip.is-active {
            background: linear-gradient(135deg, #ffe0e0, #fff1f1);
            border-color: rgba(239, 68, 68, 0.52);
        }

        .header-stat.stat-todo {
            background: linear-gradient(135deg, #fff7e8, #fffbf2);
            border-color: rgba(245, 158, 11, 0.22);
        }

        .header-stat.stat-all {
            background: linear-gradient(135deg, #f4f7fb, #fbfdff);
            border-color: rgba(100, 116, 139, 0.2);
        }

        .header-stat.stat-progress {
            background: linear-gradient(135deg, #ecf4ff, #f5f9ff);
            border-color: rgba(59, 130, 246, 0.22);
        }

        .header-stat.stat-done {
            background: linear-gradient(135deg, #ebfbf4, #f4fdf8);
            border-color: rgba(16, 185, 129, 0.22);
        }

        .header-stat.stat-skip {
            background: linear-gradient(135deg, #fff0f0, #fff8f8);
            border-color: rgba(239, 68, 68, 0.18);
        }

        .header-stat.stat-todo .header-stat-count,
        .header-stat.stat-todo .header-stat-label {
            color: #b45309;
        }

        .header-stat.stat-all .header-stat-count,
        .header-stat.stat-all .header-stat-label {
            color: #475569;
        }

        .header-stat.stat-progress .header-stat-count,
        .header-stat.stat-progress .header-stat-label {
            color: #2563eb;
        }

        .header-stat.stat-done .header-stat-count,
        .header-stat.stat-done .header-stat-label {
            color: #059669;
        }

        .header-stat.stat-skip .header-stat-count,
        .header-stat.stat-skip .header-stat-label {
            color: #dc2626;
        }

        .header-stat-count {
            display: block;
            font-size: 22px;
            font-weight: 700;
            line-height: 1.1;
            color: var(--text-main);
        }

        .header-stat-label {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            line-height: 1.1;
        }

        .welcome-copy {
            margin-bottom: 18px;
        }

        .welcome-copy h1 {
            display: none;
        }

        .week-strip {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 18px;
        }

        .day-pill {
            min-height: 82px;
            border-radius: 16px;
            padding: 10px 8px;
            text-align: center;
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(17, 24, 39, 0.04);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 6px;
        }

        .day-pill.active {
            background: linear-gradient(180deg, #ffe8ef, #fff2f6);
            border-color: rgba(255, 107, 138, 0.18);
            color: #7c2941;
        }

        .day-name {
            font-size: 11px;
            color: var(--text-muted);
        }

        .day-pill.active .day-name {
            color: #7c2941;
            font-weight: 600;
        }

        .day-number {
            font-size: 20px;
            font-weight: 700;
        }

        .list-summary {
            display: flex;
            align-items: center;
            justify-content: stretch;
            gap: 14px;
            margin-bottom: 16px;
            padding: 0;
            border-radius: 0;
            background: transparent;
            border: 0;
            box-shadow: none;
        }

        .summary-copy {
            display: none;
        }

        .topbar-filter-form {
            min-width: 260px;
        }

        .topbar-filter-label {
            display: block;
            margin-bottom: 8px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #64748b;
            line-height: 1.35;
        }

        .topbar-filter-select {
            width: 100%;
            min-height: 42px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 16px;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
            background: #fff;
        }

        .summary-filter-label {
            display: block;
            margin-bottom: 8px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
        }

        .summary-filter-form {
            min-width: 0;
        }

        .summary-filter-select {
            width: 100%;
            min-height: 48px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 16px;
            padding: 12px 14px;
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
            background: #fff;
        }

        .list-summary .header-summary {
            width: 100%;
            min-width: 0;
            max-width: 100%;
        }

        .task-table {
            overflow: visible;
            border-radius: 24px;
            border: 1px solid rgba(15, 23, 42, 0.06);
            background: rgba(255, 255, 255, 0.88);
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.05);
        }

        .task-table-head,
        .task-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 230px 150px;
            gap: 10px;
            align-items: center;
        }

        .task-table-head {
            padding: 14px 18px;
            background: #f8fafc;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
        }

        .head-cell {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #64748b;
        }

        .task-row {
            padding: 16px 18px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
            position: relative;
            overflow: visible;
        }

        .task-row:last-child {
            border-bottom: 0;
        }

        .task-row.theme-rose {
            background: var(--rose);
        }

        .task-row.theme-lavender {
            background: var(--lavender);
        }

        .task-row.theme-mint {
            background: var(--mint);
        }

        .task-row.theme-sky {
            background: var(--sky);
        }

        .task-row.theme-peach {
            background: var(--peach);
        }

        .task-row:hover {
            background: rgba(248, 250, 252, 0.85);
        }

        .task-main-cell {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            min-width: 0;
        }

        .corner-status-badge {
            position: absolute;
            top: -12px;
            right: -10px;
            width: 42px;
            height: 42px;
            border-radius: 999px;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.12);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
            color: #fff;
        }

        .corner-status-badge:hover {
            transform: scale(1.08);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.18);
        }

        .corner-status-badge i {
            font-size: 18px;
        }

        .corner-status-badge.status-todo {
            background: linear-gradient(135deg, #4b8dff, #2f6dff);
        }

        .corner-status-badge.status-in_progress {
            background: linear-gradient(135deg, #f7b733, #f18f01);
        }

        .corner-status-badge.status-done {
            background: linear-gradient(135deg, #20c997, #0fa968);
        }

        .corner-status-badge.status-skipped {
            background: linear-gradient(135deg, #ff6b6b, #e03131);
        }

        .task-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid rgba(15, 23, 42, 0.06);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .task-copy {
            min-width: 0;
            padding-right: 54px;
        }

        .task-title {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.35;
            color: #0f172a;
            word-break: break-word;
        }

        .task-note {
            margin: 6px 0 0;
            font-size: 13px;
            line-height: 1.5;
            color: #64748b;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .task-user-cell,
        .task-date-cell {
            min-width: 0;
        }

        .task-user-badge,
        .task-date-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0 10px;
            border-radius: 11px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
            min-height: 32px;
        }

        .task-user-badge {
            background: #f8fafc;
            color: #334155;
            border: 1px solid rgba(15, 23, 42, 0.06);
            max-width: 100%;
            padding: 0 10px 0 6px;
        }

        .task-user-avatar {
            width: 20px;
            height: 20px;
            border-radius: 999px;
            object-fit: cover;
            flex-shrink: 0;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: #e2e8f0;
        }

        .task-user-name {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .task-date-label {
            background: #f8fafc;
            color: #334155;
            border: 1px solid rgba(15, 23, 42, 0.06);
        }

        .task-date-stack {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .deadline-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0 10px;
            border-radius: 11px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
            min-height: 32px;
            background: rgba(255, 236, 240, 0.92);
            color: #b4234f;
            border: 1px solid rgba(180, 35, 79, 0.12);
        }

        .deadline-label.is-urgent {
            background: linear-gradient(135deg, #ff8fa3, #ff5d7a);
            color: #fff;
            border-color: rgba(255, 93, 122, 0.28);
            box-shadow: 0 10px 20px rgba(255, 93, 122, 0.24);
            animation: urgentDeadlinePulse 1.15s ease-in-out infinite;
        }

        .deadline-label i {
            width: 16px;
            text-align: center;
            font-size: 13px;
            flex-shrink: 0;
        }

        @keyframes urgentDeadlinePulse {
            0%,
            100% {
                transform: scale(1);
                box-shadow: 0 10px 20px rgba(255, 93, 122, 0.18);
            }

            50% {
                transform: scale(1.05);
                box-shadow: 0 14px 28px rgba(255, 93, 122, 0.3);
            }
        }

        .empty-state {
            text-align: center;
            padding: 40px 18px;
            border-radius: 26px;
            background: rgba(255, 255, 255, 0.82);
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.05);
        }

        .empty-state .emoji {
            font-size: 38px;
            display: block;
            margin-bottom: 10px;
        }

        .empty-state h2 {
            font-size: 20px;
            margin: 0 0 6px;
        }

        .empty-state p {
            margin: 0;
            color: var(--text-muted);
            font-size: 13px;
        }

        .journal-request-loader {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 249, 246, 0.62);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.18s ease, visibility 0.18s ease;
            z-index: 80;
        }

        .journal-request-loader.active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .journal-request-card {
            min-width: 168px;
            padding: 16px 18px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.14);
            border: 1px solid rgba(148, 163, 184, 0.2);
            display: grid;
            justify-items: center;
            gap: 10px;
        }

        .journal-request-spinner {
            width: 30px;
            height: 30px;
            border-radius: 999px;
            border: 3px solid rgba(255, 107, 138, 0.2);
            border-top-color: #ff6b8a;
            animation: journalSpin 0.7s linear infinite;
        }

        .journal-request-text {
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            text-align: center;
        }

        body.journal-request-busy {
            overflow: hidden;
        }

        @keyframes journalSpin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (min-width: 768px) {
            .journal-shell {
                max-width: 980px;
                width: 100%;
                padding: 28px 24px 60px;
            }

            .journal-frame {
                padding: 28px;
            }

            .journal-topbar {
                margin-bottom: 24px;
            }

            .task-table-head,
            .task-row {
                grid-template-columns: minmax(0, 1fr) 230px 150px;
                gap: 12px;
            }

            .task-user-cell,
            .task-date-cell {
                justify-content: flex-start;
            }

            .assign-toggle-btn {
                right: 24px;
                bottom: 24px;
            }

            .assign-panel {
                right: 24px;
                left: auto;
                width: 420px;
                border-radius: 28px;
                bottom: 24px;
            }
        }

        @media (min-width: 1200px) {
            .journal-shell {
                max-width: 1180px;
                padding-top: 36px;
            }

            .journal-frame {
                padding: 34px 36px 30px;
                border-radius: 34px;
            }

            .page-title {
                font-size: 34px;
            }

            .pill-link {
                padding: 10px 16px;
                font-size: 14px;
            }

            .header-summary {
                min-width: 132px;
                gap: 8px;
            }

            .header-stat {
                min-width: 96px;
                padding: 13px 14px;
            }

            .assign-toggle-btn {
                right: max(28px, calc((100vw - 1180px) / 2 + 28px));
                bottom: 28px;
            }

            .assign-panel {
                right: max(28px, calc((100vw - 1180px) / 2 + 28px));
            }

            .task-title {
                font-size: 16px;
            }

            .task-note {
                font-size: 14px;
            }

            .corner-status-badge {
                top: -14px;
                right: -12px;
                width: 46px;
                height: 46px;
            }

            .corner-status-badge i {
                font-size: 20px;
            }

            .task-table-head,
            .task-row {
                grid-template-columns: minmax(0, 1fr) 260px 165px;
            }
        }

        @media (min-width: 1440px) {
            .journal-shell {
                max-width: 1260px;
            }

            .assign-toggle-btn {
                right: max(32px, calc((100vw - 1260px) / 2 + 32px));
            }

            .assign-panel {
                right: max(32px, calc((100vw - 1260px) / 2 + 32px));
            }
        }

        @media (max-width: 767px) {
            .journal-topbar {
                grid-template-columns: 1fr;
                grid-template-areas:
                    "filter"
                    "title"
                    "employee";
                gap: 14px;
                align-items: stretch;
            }

            .pill-link {
                grid-area: filter;
                justify-self: start;
            }

            .page-title {
                grid-area: title;
                justify-self: start;
                text-align: left;
                font-size: 24px;
                line-height: 1.2;
            }

            .topbar-filter-form {
                grid-area: employee;
                width: 100%;
                min-width: 0;
                justify-self: stretch;
            }

            .assign-toggle-btn {
                justify-content: center;
            }

            .list-summary {
                flex-direction: column;
                align-items: stretch;
            }

            .theme-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: 8px;
            }

            .theme-preview {
                height: 32px;
                border-radius: 10px;
            }

            .list-summary .header-summary {
                grid-template-columns: repeat(5, minmax(0, 1fr));
                max-width: none;
                gap: 8px;
            }

            .task-table {
                background: transparent;
                border: 0;
                box-shadow: none;
            }

            .task-table-head {
                display: none;
            }

            .task-row {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr);
                gap: 12px;
                padding: 16px;
                margin-bottom: 12px;
                border: 1px solid rgba(15, 23, 42, 0.06);
                border-radius: 20px;
                background: rgba(255, 255, 255, 0.88);
                box-shadow: 0 12px 26px rgba(15, 23, 42, 0.05);
            }

            .task-main-cell,
            .task-user-cell,
            .task-date-cell {
                display: flex;
                justify-content: flex-start;
                gap: 12px;
                align-items: center;
            }

            .task-main-cell {
                grid-column: 1 / -1;
                align-items: flex-start;
            }

            .task-user-cell,
            .task-date-cell {
                min-width: 0;
            }

            .task-user-cell {
                grid-column: 1;
            }

            .task-date-cell {
                grid-column: 2;
                justify-content: flex-start;
            }

            .task-user-cell::before,
            .task-date-cell::before {
                display: none;
            }

            .task-user-badge,
            .task-date-label {
                max-width: 100%;
                min-height: 32px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .task-user-name {
                max-width: 100px;
            }

            .corner-status-badge {
                top: -10px;
                right: -8px;
                width: 38px;
                height: 38px;
            }

            .corner-status-badge i {
                font-size: 16px;
            }

            .task-copy {
                padding-right: 46px;
            }

            .task-user-avatar {
                display: none;
            }

            .task-user-badge {
                padding-left: 10px;
            }

            .task-date-label {
                width: auto;
                justify-content: flex-start;
            }

            .header-stat {
                min-width: 0;
                padding: 10px 8px;
                border-radius: 16px;
            }

            .header-stat-count {
                font-size: 18px;
            }

            .header-stat-label {
                font-size: 11px;
            }
        }

        @media (max-width: 420px) {
            .page-title {
                font-size: 22px;
            }

            .topbar-filter-form {
                min-width: 0;
            }

            .topbar-filter-label {
                font-size: 10px;
            }

            .filter-grid,
            .custom-filter-row {
                grid-template-columns: 1fr;
            }

            .task-row {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1.35fr);
                gap: 10px;
            }

            .list-summary .header-summary {
                gap: 6px;
            }

            .header-stat {
                padding: 8px 6px;
                border-radius: 14px;
            }

            .header-stat-count {
                font-size: 16px;
            }

            .header-stat-label {
                font-size: 10px;
            }

            .theme-grid {
                gap: 6px;
            }

            .theme-preview {
                height: 28px;
                border-radius: 9px;
            }

            .task-user-badge,
            .task-date-label {
                font-size: 11px;
                padding: 0 10px;
                min-height: 32px;
            }

            .task-user-name {
                max-width: 84px;
            }

            .assigned-manager-label {
                font-size: 10px;
                padding: 6px 9px;
            }

            .assign-panel-head {
                align-items: flex-start;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $statusLabels = [
            'todo' => 'To Do',
            'in_progress' => 'Progress',
            'done' => 'Done',
            'skipped' => 'Skipped',
        ];
        $statusIcons = [
            'todo' => 'far fa-file-alt',
            'in_progress' => 'far fa-clock',
            'done' => 'fas fa-check-circle',
            'skipped' => 'fas fa-forward',
        ];
        $statusFilterParams = [
            'filter' => $filter,
            'date' => $selectedDate->toDateString(),
            'start_date' => $rangeStart->toDateString(),
            'end_date' => $rangeEnd->toDateString(),
            'user_id' => $selectedUserId,
        ];
        $themeOptions = ['rose', 'lavender', 'mint', 'sky', 'peach'];
        $assignFormHasErrors = $errors->hasAny([
            'user_id',
            'task_date',
            'deadline_date',
            'status',
            'title',
            'note',
            'scheduled_time',
            'icon',
            'color_theme',
        ]);
    @endphp

    <div id="divisionJournalRoot" data-success="{{ session('success') ? e(session('success')) : '' }}">
    <div class="journal-shell">
        <div class="journal-frame">
            <div class="journal-topbar">
                <button type="button" class="pill-link" id="filterToggleBtn">
                    <span>{{ $filterLabel }}</span>
                    <i class="fas fa-chevron-down" style="font-size:12px;"></i>
                </button>
                <h1 class="page-title">Division Journal</h1>
                @if($divisionName)
                    <form method="GET" action="{{ route('daily-journal.division.index') }}" class="topbar-filter-form">
                        <label class="topbar-filter-label" for="topbar_division_user_id">
                            Employee Filter {{ $divisionName ? '· ' . $divisionName : '' }}
                        </label>
                        <input type="hidden" name="filter" value="{{ $filter }}">
                        <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">
                        <input type="hidden" name="start_date" value="{{ $rangeStart->toDateString() }}">
                        <input type="hidden" name="end_date" value="{{ $rangeEnd->toDateString() }}">
                        <select id="topbar_division_user_id" name="user_id" class="topbar-filter-select">
                            <option value="">All Employees</option>
                            @foreach($divisionMembers as $member)
                                <option value="{{ $member->user_id }}" {{ (int) $selectedUserId === (int) $member->user_id ? 'selected' : '' }}>
                                    {{ $member->user->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>

            <div class="filter-panel" id="filterPanel">
                <div class="filter-grid">
                    <a href="{{ route('daily-journal.division.index', ['filter' => 'today', 'date' => now()->toDateString(), 'user_id' => $selectedUserId]) }}" class="filter-choice {{ $filter === 'today' ? 'active' : '' }}">Today</a>
                    <a href="{{ route('daily-journal.division.index', ['filter' => 'week', 'date' => now()->toDateString(), 'user_id' => $selectedUserId]) }}" class="filter-choice {{ $filter === 'week' ? 'active' : '' }}">This Week</a>
                    <a href="{{ route('daily-journal.division.index', ['filter' => 'month', 'date' => now()->toDateString(), 'user_id' => $selectedUserId]) }}" class="filter-choice {{ $filter === 'month' ? 'active' : '' }}">This Month</a>
                    <a href="{{ route('daily-journal.division.index', ['filter' => 'year', 'date' => now()->toDateString(), 'user_id' => $selectedUserId]) }}" class="filter-choice {{ $filter === 'year' ? 'active' : '' }}">This Year</a>
                </div>

                <form method="GET" action="{{ route('daily-journal.division.index') }}" class="custom-filter-form">
                    <input type="hidden" name="filter" value="custom">
                    <input type="hidden" name="date" value="{{ $rangeStart->toDateString() }}">
                    <input type="hidden" name="user_id" value="{{ $selectedUserId }}">
                    <div class="custom-filter-row">
                        <div>
                            <label class="form-label" for="division_start_date">Start date</label>
                            <input type="date" id="division_start_date" name="start_date" class="journal-input" value="{{ $rangeStart->toDateString() }}">
                        </div>
                        <div>
                            <label class="form-label" for="division_end_date">End date</label>
                            <input type="date" id="division_end_date" name="end_date" class="journal-input" value="{{ $rangeEnd->toDateString() }}">
                        </div>
                    </div>
                    <button type="submit" class="apply-filter-button">Apply Custom Range</button>
                </form>
            </div>

            <div class="welcome-copy">
                <h1>Division Journal</h1>
            </div>

            @if($divisionName)
                <div class="list-summary">
                    <div class="header-summary">
                        <a href="{{ route('daily-journal.division.index', array_filter($statusFilterParams, fn ($value) => $value !== null && $value !== '')) }}" class="header-stat stat-all {{ $selectedStatus === null ? 'is-active' : '' }}">
                            <span class="header-stat-count">{{ $totalCount }}</span>
                            <span class="header-stat-label">All</span>
                        </a>
                        <a href="{{ route('daily-journal.division.index', array_filter($selectedStatus === 'todo' ? $statusFilterParams : array_merge($statusFilterParams, ['status' => 'todo']), fn ($value) => $value !== null && $value !== '')) }}" class="header-stat stat-todo {{ $selectedStatus === 'todo' ? 'is-active' : '' }}">
                            <span class="header-stat-count">{{ $statusCounts['todo'] }}</span>
                            <span class="header-stat-label">To Do</span>
                        </a>
                        <a href="{{ route('daily-journal.division.index', array_filter($selectedStatus === 'in_progress' ? $statusFilterParams : array_merge($statusFilterParams, ['status' => 'in_progress']), fn ($value) => $value !== null && $value !== '')) }}" class="header-stat stat-progress {{ $selectedStatus === 'in_progress' ? 'is-active' : '' }}">
                            <span class="header-stat-count">{{ $statusCounts['in_progress'] }}</span>
                            <span class="header-stat-label">Prog</span>
                        </a>
                        <a href="{{ route('daily-journal.division.index', array_filter($selectedStatus === 'done' ? $statusFilterParams : array_merge($statusFilterParams, ['status' => 'done']), fn ($value) => $value !== null && $value !== '')) }}" class="header-stat stat-done {{ $selectedStatus === 'done' ? 'is-active' : '' }}">
                            <span class="header-stat-count">{{ $statusCounts['done'] }}</span>
                            <span class="header-stat-label">Done</span>
                        </a>
                        <a href="{{ route('daily-journal.division.index', array_filter($selectedStatus === 'skipped' ? $statusFilterParams : array_merge($statusFilterParams, ['status' => 'skipped']), fn ($value) => $value !== null && $value !== '')) }}" class="header-stat stat-skip {{ $selectedStatus === 'skipped' ? 'is-active' : '' }}">
                            <span class="header-stat-count">{{ $statusCounts['skipped'] }}</span>
                            <span class="header-stat-label">Skip</span>
                        </a>
                    </div>
                </div>
            @endif

            @if(!$divisionName)
                <div class="empty-state">
                    <span class="emoji">🏢</span>
                    <h2>Divisi belum terhubung</h2>
                    <p>User manager ini belum memiliki divisi pada data karyawan.</p>
                </div>
            @elseif($tasks->isEmpty())
                <div class="empty-state">
                    <span class="emoji">📔</span>
                    <h2>Belum ada task divisi</h2>
                    <p>Tidak ada Daily Journal pada periode dan filter user yang dipilih.</p>
                </div>
            @else
                <div class="task-table">
                    <div class="task-table-head">
                        <div class="head-cell">Task</div>
                        <div class="head-cell">User</div>
                        <div class="head-cell">Date</div>
                    </div>

                    @foreach($tasks as $task)
                        @php
                            $isUrgentDeadline = $task->deadline_date
                                && $task->deadline_date->lte(now()->startOfDay())
                                && $task->status !== 'done';
                        @endphp
                        <article class="task-row theme-{{ $task->color_theme }}">
                            <span class="corner-status-badge status-{{ $task->status }}" aria-hidden="true">
                                <i class="{{ $statusIcons[$task->status] ?? 'far fa-circle' }}"></i>
                            </span>

                            <div class="task-main-cell">
                                    <div class="task-icon">{{ $task->icon ?: '📝' }}</div>
                                    <div class="task-copy">
                                        <h3 class="task-title">{{ $task->title }}</h3>
                                        <p class="task-note">
                                            @if($task->task_date)
                                                {{ $task->task_date->translatedFormat('d M Y') }}
                                                @if($task->note)
                                                    ·
                                                @endif
                                            @endif
                                            {{ $task->note ?: 'Catatan belum ditambahkan.' }}
                                        </p>
                                    </div>
                            </div>

                            <div class="task-user-cell">
                                @php
                                    $employeePhoto = optional(optional($task->user)->employee)->photo;
                                    $employeePhotoUrl = $employeePhoto ? asset('storage/' . ltrim($employeePhoto, '/')) : null;
                                @endphp
                                <span class="task-user-badge">
                                    @if($employeePhotoUrl)
                                        <img src="{{ $employeePhotoUrl }}" alt="{{ $task->user->name ?? 'User' }}" class="task-user-avatar">
                                    @else
                                        <i class="fas fa-user"></i>
                                    @endif
                                    <span class="task-user-name">{{ $task->user->name ?? 'User' }}</span>
                                </span>
                            </div>

                            <div class="task-date-cell">
                                @if($task->deadline_date)
                                    <div class="task-date-stack">
                                        <span class="deadline-label {{ $isUrgentDeadline ? 'is-urgent' : '' }}">
                                            <i class="fas fa-hourglass-end"></i>
                                            {{ $task->deadline_date->translatedFormat('d M Y') }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @if($divisionName)
        <div class="journal-request-loader" id="journalRequestLoader" aria-hidden="true">
            <div class="journal-request-card">
                <span class="journal-request-spinner" aria-hidden="true"></span>
                <span class="journal-request-text" id="journalRequestText">Processing...</span>
            </div>
        </div>
        @if($canAssignTasks)
        <div class="assign-overlay{{ $assignFormHasErrors ? ' active' : '' }}" id="assignOverlay"></div>
        <button type="button" class="assign-toggle-btn" id="assignToggleBtn">
            <i class="fas fa-plus"></i>
            <span>Give Task to Employee</span>
        </button>
        <div class="assign-panel {{ $assignFormHasErrors ? 'active' : '' }}" id="assignPanel">
            <div class="assign-sheet-handle"></div>
            <div class="assign-panel-head">
                <div>
                    <h2 class="assign-panel-title">Give Task</h2>
                    <p class="assign-panel-copy">Manager dapat membuat task untuk employee dalam divisi yang sama.</p>
                </div>
                <button type="button" class="close-assign-panel" id="closeAssignPanel">×</button>
            </div>

            <form method="POST" action="{{ route('daily-journal.store') }}" class="journal-form">
                @csrf
                <input type="hidden" name="redirect_filter" value="{{ $filter }}">
                <input type="hidden" name="redirect_date" value="{{ $selectedDate->toDateString() }}">
                <input type="hidden" name="redirect_start_date" value="{{ $rangeStart->toDateString() }}">
                <input type="hidden" name="redirect_end_date" value="{{ $rangeEnd->toDateString() }}">
                <input type="hidden" name="redirect_user_id" value="{{ $selectedUserId }}">
                <input type="hidden" name="redirect_status" value="{{ $selectedStatus }}">

                <div>
                    <label class="form-label" for="assign_user_id">Employee</label>
                    <select id="assign_user_id" name="user_id" class="journal-select" required>
                        <option value="">Pilih employee</option>
                        @foreach($divisionMembers as $member)
                            <option value="{{ $member->user_id }}" {{ (string) old('user_id', $selectedUserId) === (string) $member->user_id ? 'selected' : '' }}>
                                {{ $member->user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-row">
                    <div>
                        <label class="form-label" for="assign_task_date">Tanggal</label>
                        <input type="date" id="assign_task_date" name="task_date" class="journal-input" value="{{ old('task_date', $selectedDate->toDateString()) }}" required>
                        @error('task_date')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label" for="assign_deadline_date">Deadline</label>
                        <input type="date" id="assign_deadline_date" name="deadline_date" class="journal-input" value="{{ old('deadline_date') }}">
                        @error('deadline_date')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-row">
                    <div>
                        <label class="form-label" for="assign_time">Jam</label>
                        <input type="time" id="assign_time" name="scheduled_time" class="journal-input" value="{{ old('scheduled_time') }}">
                        @error('scheduled_time')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label" for="assign_status">Status</label>
                        <select id="assign_status" name="status" class="journal-select">
                            @foreach($statusOptions as $statusOption)
                                <option value="{{ $statusOption }}" {{ old('status', 'todo') === $statusOption ? 'selected' : '' }}>{{ $statusLabels[$statusOption] }}</option>
                            @endforeach
                        </select>
                        @error('status')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div>
                    <label class="form-label" for="assign_title">Nama Task</label>
                    <input type="text" id="assign_title" name="title" class="journal-input" value="{{ old('title') }}" maxlength="120" placeholder="Contoh: Follow up client" required>
                    @error('title')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label" for="assign_note">Catatan</label>
                    <textarea id="assign_note" name="note" class="journal-textarea" placeholder="Arahan singkat untuk employee">{{ old('note') }}</textarea>
                    @error('note')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-row">
                    <div>
                        <label class="form-label" for="assign_icon">Emoji</label>
                        <input type="text" id="assign_icon" name="icon" class="journal-input" value="{{ old('icon') }}" maxlength="16" placeholder="📝">
                        @error('icon')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label">Warna Card</label>
                        <input type="hidden" name="color_theme" value="{{ old('color_theme', 'rose') }}" id="assignThemeInput">
                        <div class="theme-grid">
                            @foreach($themeOptions as $theme)
                                <label class="theme-option">
                                    <input type="radio" name="assign_theme_picker" value="{{ $theme }}" {{ old('color_theme', 'rose') === $theme ? 'checked' : '' }}>
                                    <span class="theme-preview {{ $theme }}"></span>
                                </label>
                            @endforeach
                        </div>
                        @error('color_theme')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <button type="submit" class="submit-button">Give Task</button>
            </form>
        </div>
        @endif
    @endif
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            let isRequestPending = false;

            function setBusyState(isBusy, message) {
                const root = document.getElementById('divisionJournalRoot');
                const loader = root ? root.querySelector('#journalRequestLoader') : null;
                const text = root ? root.querySelector('#journalRequestText') : null;

                document.body.classList.toggle('journal-request-busy', isBusy);

                if (text && message) {
                    text.textContent = message;
                }

                if (loader) {
                    loader.classList.toggle('active', isBusy);
                    loader.setAttribute('aria-hidden', isBusy ? 'false' : 'true');
                }
            }

            function getRequestMessage(url, options) {
                const method = String(options && options.method ? options.method : 'GET').toUpperCase();

                if (method === 'GET') {
                    return 'Loading...';
                }

                return 'Saving task...';
            }

            async function replaceRootFromResponse(response) {
                const html = await response.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const nextRoot = doc.getElementById('divisionJournalRoot');
                const currentRoot = document.getElementById('divisionJournalRoot');

                if (!nextRoot || !currentRoot) {
                    window.location.assign(response.url || window.location.href);
                    return;
                }

                currentRoot.replaceWith(nextRoot);
                window.history.replaceState({}, '', response.url || window.location.href);
                initDivisionJournalAjax();
            }

            function buildGetUrl(form) {
                const url = new URL(form.action, window.location.origin);
                const formData = new FormData(form);

                url.search = '';
                formData.forEach(function (value, key) {
                    if (value !== null && value !== '') {
                        url.searchParams.set(key, value);
                    }
                });

                return url.toString();
            }

            async function ajaxVisit(url, options) {
                if (isRequestPending) {
                    return;
                }

                isRequestPending = true;
                setBusyState(true, getRequestMessage(url, options || {}));

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html, application/xhtml+xml'
                        },
                        credentials: 'same-origin',
                        ...options
                    });

                    if (!response.ok) {
                        throw new Error('Request failed');
                    }

                    await replaceRootFromResponse(response);
                } catch (error) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Terjadi masalah',
                            text: 'Halaman akan dimuat ulang untuk menjaga data tetap sinkron.',
                            confirmButtonText: 'OK'
                        }).then(function () {
                            window.location.reload();
                        });
                    } else {
                        window.location.reload();
                    }
                } finally {
                    isRequestPending = false;
                    setBusyState(false);
                }
            }

            function bindAjaxInteractions(root) {
                root.querySelectorAll('.filter-choice, .header-summary a').forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        ajaxVisit(link.href, { method: 'GET' });
                    });
                });

                root.querySelectorAll('form.custom-filter-form, form.topbar-filter-form').forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        event.preventDefault();
                        ajaxVisit(buildGetUrl(form), { method: 'GET' });
                    });
                });

                root.querySelectorAll('.topbar-filter-form .topbar-filter-select').forEach(function (select) {
                    select.addEventListener('change', function () {
                        const form = select.closest('form');

                        if (!form) {
                            return;
                        }

                        ajaxVisit(buildGetUrl(form), { method: 'GET' });
                    });
                });

                root.querySelectorAll('form.journal-form').forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        event.preventDefault();
                        ajaxVisit(form.action, {
                            method: 'POST',
                            body: new FormData(form)
                        });
                    });
                });
            }

            window.initDivisionJournalAjax = function () {
                const root = document.getElementById('divisionJournalRoot');

                if (!root) {
                    return;
                }

                const filterToggleBtn = root.querySelector('#filterToggleBtn');
                const filterPanel = root.querySelector('#filterPanel');
                const assignToggleBtn = root.querySelector('#assignToggleBtn');
                const assignPanel = root.querySelector('#assignPanel');
                const assignOverlay = root.querySelector('#assignOverlay');
                const closeAssignPanel = root.querySelector('#closeAssignPanel');
                const assignThemeInput = root.querySelector('#assignThemeInput');
                const assignThemeRadios = root.querySelectorAll('input[name="assign_theme_picker"]');
                let openAssignFrame = null;

                function openAssignPanel() {
                    if (filterPanel) {
                        filterPanel.classList.remove('active');
                    }

                    if (openAssignFrame !== null) {
                        window.cancelAnimationFrame(openAssignFrame);
                    }

                    openAssignFrame = window.requestAnimationFrame(function () {
                        if (assignPanel) {
                            assignPanel.classList.add('active');
                        }
                        if (assignOverlay) {
                            assignOverlay.classList.add('active');
                        }
                        openAssignFrame = null;
                    });
                }

                function closeAssignSheet() {
                    if (openAssignFrame !== null) {
                        window.cancelAnimationFrame(openAssignFrame);
                        openAssignFrame = null;
                    }

                    if (assignPanel) {
                        assignPanel.classList.remove('active');
                    }
                    if (assignOverlay) {
                        assignOverlay.classList.remove('active');
                    }
                }

                if (filterToggleBtn && filterPanel) {
                    filterToggleBtn.addEventListener('click', function () {
                        filterPanel.classList.toggle('active');
                    });
                }

                if (assignToggleBtn && assignPanel) {
                    assignToggleBtn.addEventListener('click', openAssignPanel);
                }

                if (closeAssignPanel && assignPanel) {
                    closeAssignPanel.addEventListener('click', closeAssignSheet);
                }

                if (assignOverlay) {
                    assignOverlay.addEventListener('click', closeAssignSheet);
                }

                assignThemeRadios.forEach(function (radio) {
                    radio.addEventListener('change', function () {
                        if (assignThemeInput) {
                            assignThemeInput.value = this.value;
                        }
                    });
                });

                if (assignPanel && assignPanel.classList.contains('active') && assignOverlay) {
                    assignOverlay.classList.add('active');
                }

                bindAjaxInteractions(root);

                if (root.dataset.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: root.dataset.success,
                        confirmButtonText: 'OK'
                    });
                    root.dataset.success = '';
                }
            };

            window.initDivisionJournalAjax();
        })();
    </script>
@endsection
