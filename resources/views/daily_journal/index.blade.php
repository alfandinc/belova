@extends('layouts.daily_journal.app')

@section('title', 'My Daily Journal')

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

        .pill-link,
        .icon-link,
        .open-composer {
            border: 0;
            background: none;
            padding: 0;
            cursor: pointer;
        }

        .pill-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 8px 12px;
            background: linear-gradient(135deg, #ff748d, #ff9db2);
            color: #fff;
            font-weight: 600;
            font-size: 13px;
            box-shadow: 0 12px 24px rgba(255, 107, 138, 0.25);
            min-height: 38px;
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
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px;
            min-width: 108px;
        }

        .header-stat {
            min-width: 48px;
            padding: 7px 8px;
            border-radius: 14px;
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
            transform: translateY(-1px);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.12);
            border-width: 2px;
        }

        .header-stat.stat-todo.is-active {
            border-color: rgba(59, 130, 246, 0.55);
            background: linear-gradient(135deg, #eaf3ff, #f4f8ff);
        }

        .header-stat.stat-progress.is-active {
            border-color: rgba(245, 158, 11, 0.55);
            background: linear-gradient(135deg, #fff5db, #fff9ec);
        }

        .header-stat.stat-done.is-active {
            border-color: rgba(16, 185, 129, 0.55);
            background: linear-gradient(135deg, #e7fbf1, #f3fdf8);
        }

        .header-stat.stat-skip.is-active {
            border-color: rgba(239, 68, 68, 0.5);
            background: linear-gradient(135deg, #ffebeb, #fff5f5);
        }

        .header-stat-count {
            display: block;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.1;
            color: var(--text-main);
        }

        .header-stat-label {
            display: block;
            margin-top: 2px;
            font-size: 10px;
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

        .welcome-copy p {
            margin: 0;
            color: var(--text-muted);
            font-size: 13px;
        }

        .week-strip {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 3px;
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

        .task-stack {
            display: grid;
            gap: 14px;
        }

        .task-card {
            position: relative;
            border-radius: 22px;
            padding: 12px 12px 10px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
            overflow: visible;
            transition: box-shadow 0.18s ease, transform 0.18s ease, opacity 0.18s ease;
        }

        .task-card.is-deletable {
            cursor: grab;
            touch-action: none;
            user-select: none;
            -webkit-user-select: none;
        }

        .task-card.is-deletable:active {
            cursor: grabbing;
        }

        .task-card.is-dragging {
            position: fixed;
            margin: 0;
            z-index: 60;
            pointer-events: none;
            transform: rotate(2deg) scale(0.98);
            box-shadow: 0 24px 44px rgba(15, 23, 42, 0.2);
        }

        .task-card-placeholder {
            border-radius: 22px;
        }

        body.task-delete-dragging {
            overflow: hidden;
            user-select: none;
        }

        .drag-trash-zone {
            position: fixed;
            left: 50%;
            bottom: 96px;
            width: 92px;
            height: 92px;
            border-radius: 28px;
            background: linear-gradient(135deg, #ff7a7a, #e03131);
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            box-shadow: 0 18px 34px rgba(224, 49, 49, 0.28);
            transform: translate(-50%, 18px) scale(0.92);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
            z-index: 50;
        }

        .drag-trash-zone.active {
            opacity: 1;
            transform: translate(-50%, 0) scale(1);
        }

        .drag-trash-zone.is-over {
            transform: translate(-50%, -4px) scale(1.08);
            box-shadow: 0 22px 42px rgba(224, 49, 49, 0.36);
        }

        .drag-trash-zone i {
            font-size: 28px;
        }

        .drag-trash-zone span {
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
        }

        .assigned-manager-badge {
            position: absolute;
            top: -12px;
            right: -10px;
            width: 42px;
            height: 42px;
            border-radius: 999px;
            border: 2px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.12);
            background: rgba(255, 255, 255, 0.8);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .assigned-manager-badge:hover {
            transform: scale(1.08);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.18);
        }

        .assigned-manager-alert {
            position: absolute;
            top: -4px;
            right: -6px;
            width: 20px;
            height: 20px;
            border-radius: 999px;
            background: #fff;
            color: #ef4444;
            border: 2px solid rgba(255, 255, 255, 0.95);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.14);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }

        .assigned-manager-label {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 10px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.92);
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            line-height: 1;
            white-space: nowrap;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.18);
            opacity: 0;
            pointer-events: none;
            transform: translateY(-4px);
            transition: opacity 0.18s ease, transform 0.18s ease;
        }

        .assigned-manager-label::before {
            content: '';
            position: absolute;
            top: -5px;
            right: 14px;
            width: 10px;
            height: 10px;
            background: rgba(15, 23, 42, 0.92);
            transform: rotate(45deg);
            border-radius: 2px;
        }

        .assigned-manager-badge:hover .assigned-manager-label {
            opacity: 1;
            transform: translateY(0);
        }

        .assigned-manager-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 999px;
        }

        .assigned-manager-fallback {
            width: 100%;
            height: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            letter-spacing: 0.02em;
        }

        .task-card.theme-rose { background: var(--rose); }
        .task-card.theme-lavender { background: var(--lavender); }
        .task-card.theme-mint { background: var(--mint); }
        .task-card.theme-sky { background: var(--sky); }
        .task-card.theme-peach { background: var(--peach); }

        .task-header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .task-main {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            min-width: 0;
        }

        .task-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.55);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .task-copy {
            min-width: 0;
            padding-right: 54px;
        }

        .task-title {
            font-size: 17px;
            font-weight: 600;
            line-height: 1.2;
            margin: 0 0 4px;
            word-break: break-word;
        }

        .task-meta {
            margin: 0;
            color: rgba(17, 24, 39, 0.72);
            font-size: 13px;
            line-height: 1.35;
        }

        .status-form {
            flex-shrink: 0;
            min-width: 98px;
        }

        .status-control {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 0 9px;
            min-height: 32px;
            border-radius: 11px;
            box-shadow: 0 8px 16px rgba(15, 23, 42, 0.08);
            color: #fff;
        }

        .status-icon {
            width: 16px;
            text-align: center;
            font-size: 13px;
            flex-shrink: 0;
            color: currentColor;
        }

        .status-control.status-control-todo {
            background: linear-gradient(135deg, #4b8dff, #2f6dff);
        }

        .status-control.status-control-in_progress {
            background: linear-gradient(135deg, #f7b733, #f18f01);
        }

        .status-control.status-control-done {
            background: linear-gradient(135deg, #20c997, #0fa968);
        }

        .status-control.status-control-skipped {
            background: linear-gradient(135deg, #ff6b6b, #e03131);
        }

        .status-select {
            width: 100%;
            border: 0;
            border-radius: 14px;
            background: transparent;
            padding: 7px 0;
            font-size: 11px;
            font-weight: 700;
            color: inherit;
            box-shadow: none;
        }

        .status-select option {
            color: #111827;
        }

        .task-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 3px;
            margin-top: 8px;
        }

        .task-footer-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 3px;
            margin-left: auto;
        }

        .task-date-stack {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .task-date-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 7px 11px;
            background: rgba(255, 255, 255, 0.6);
            color: #4b5563;
            font-size: 12px;
            font-weight: 600;
        }

        .deadline-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 7px 11px;
            background: rgba(255, 236, 240, 0.92);
            color: #b4234f;
            font-size: 12px;
            font-weight: 700;
        }

        .deadline-label.is-urgent {
            background: linear-gradient(135deg, #ff8fa3, #ff5d7a);
            color: #fff;
            box-shadow: 0 10px 20px rgba(255, 93, 122, 0.24);
            animation: urgentDeadlinePulse 1.15s ease-in-out infinite;
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

        .delete-task-form {
            display: none;
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

        .open-composer {
            position: fixed;
            right: 18px;
            bottom: 18px;
            width: 62px;
            height: 62px;
            border-radius: 22px;
            background: linear-gradient(135deg, #ff6b8a, #ff93ac);
            color: #fff;
            box-shadow: 0 18px 36px rgba(255, 107, 138, 0.32);
            font-size: 24px;
            z-index: 30;
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

        .composer-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.38);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
            z-index: 35;
            will-change: opacity;
        }

        .composer-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .composer-sheet {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 40;
            background: #fff;
            border-radius: 30px 30px 0 0;
            box-shadow: 0 -18px 42px rgba(15, 23, 42, 0.12);
            transform: translate3d(0, 105%, 0);
            transition: transform 0.25s ease;
            padding: 16px 16px 26px;
            max-width: 520px;
            margin: 0 auto;
            will-change: transform;
            backface-visibility: hidden;
        }

        .composer-sheet.active {
            transform: translate3d(0, 0, 0);
        }

        .sheet-handle {
            width: 54px;
            height: 5px;
            background: #e5e7eb;
            border-radius: 999px;
            margin: 0 auto 14px;
        }

        .sheet-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .sheet-title {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .close-sheet {
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 12px;
            background: #f3f4f6;
            font-size: 20px;
        }

        .journal-form {
            display: grid;
            gap: 12px;
        }

        .form-label {
            font-size: 12px;
            font-weight: 700;
            color: #4b5563;
            margin-bottom: 6px;
            display: block;
        }

        .journal-input,
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
            min-height: 88px;
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

        .theme-option {
            position: relative;
        }

        .theme-option input {
            position: absolute;
            opacity: 0;
            inset: 0;
        }

        .theme-preview {
            width: 100%;
            height: 42px;
            border-radius: 14px;
            border: 2px solid transparent;
            display: block;
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
            border-radius: 18px;
            padding: 14px 16px;
            background: linear-gradient(135deg, #ff6b8a, #ff93ac);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            box-shadow: 0 14px 32px rgba(255, 107, 138, 0.24);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 18px;
            color: #6b7280;
            font-size: 13px;
            font-weight: 600;
        }

        .field-error {
            font-size: 12px;
            color: #b91c1c;
            margin-top: 6px;
        }

        @media (min-width: 768px) {
            .journal-shell {
                max-width: 980px;
                width: 100%;
                padding: 28px 24px 120px;
            }

            .journal-frame {
                padding: 28px;
            }

            .journal-topbar {
                margin-bottom: 24px;
            }

            .week-strip {
                gap: 7px;
            }

            .task-stack {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 18px;
            }

            .open-composer {
                right: max(24px, calc((100vw - 980px) / 2 + 24px));
            }

            .composer-sheet {
                right: max(24px, calc((100vw - 980px) / 2 + 24px));
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
                min-width: 58px;
                padding: 9px 8px;
            }

            .week-strip {
                gap: 8px;
                margin-bottom: 24px;
            }

            .day-pill {
                min-height: 96px;
                padding: 12px 10px;
            }

            .day-name {
                font-size: 12px;
            }

            .day-number {
                font-size: 30px;
            }

            .task-card {
                padding: 22px 22px 18px;
            }

            .assigned-manager-badge {
                top: -14px;
                right: -12px;
                width: 46px;
                height: 46px;
            }

            .assigned-manager-alert {
                width: 22px;
                height: 22px;
                font-size: 12px;
                top: -5px;
                right: -7px;
            }

            .task-title {
                font-size: 18px;
            }

            .task-meta {
                font-size: 14px;
            }

            .open-composer {
                right: max(28px, calc((100vw - 1180px) / 2 + 28px));
            }

            .composer-sheet {
                right: max(28px, calc((100vw - 1180px) / 2 + 28px));
            }
        }

        @media (min-width: 1440px) {
            .journal-shell {
                max-width: 1260px;
            }

            .task-stack {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .open-composer {
                right: max(32px, calc((100vw - 1260px) / 2 + 32px));
            }

            .composer-sheet {
                right: max(32px, calc((100vw - 1260px) / 2 + 32px));
            }
        }

        @media (max-width: 420px) {
            .journal-topbar {
                grid-template-columns: auto 1fr auto;
                grid-template-areas:
                    "filter . stats"
                    "title title stats";
                align-items: start;
                row-gap: 12px;
            }

            .pill-link {
                grid-area: filter;
            }

            .page-title {
                grid-area: title;
                justify-self: start;
                text-align: left;
                font-size: 24px;
            }

            .header-summary {
                grid-area: stats;
                justify-self: end;
                align-self: start;
                min-width: 96px;
            }

            .assigned-manager-badge {
                top: -10px;
                right: -8px;
                width: 38px;
                height: 38px;
            }

            .assigned-manager-label {
                font-size: 10px;
                padding: 6px 9px;
            }

            .assigned-manager-alert {
                width: 18px;
                height: 18px;
                font-size: 10px;
                top: -3px;
                right: -4px;
            }

            .task-copy {
                padding-right: 46px;
            }

            .status-form {
                min-width: 96px;
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
        ];
    @endphp

    <div id="myDailyJournalRoot" data-success="{{ session('success') ? e(session('success')) : '' }}">
    <div class="journal-shell">
        <div class="journal-frame">
            <div class="journal-topbar">
                <button type="button" class="pill-link" id="filterToggleBtn">
                    <span>{{ $filterLabel }}</span>
                    <i class="fas fa-chevron-down" style="font-size:12px;"></i>
                </button>
                <h1 class="page-title">My Journal</h1>
                <div class="header-summary">
                    <a href="{{ route('daily-journal.index', array_filter($selectedStatus === 'todo' ? $statusFilterParams : array_merge($statusFilterParams, ['status' => 'todo']), fn ($value) => $value !== null && $value !== '')) }}" class="header-stat stat-todo {{ $selectedStatus === 'todo' ? 'is-active' : '' }}">
                        <span class="header-stat-count">{{ $statusCounts['todo'] }}</span>
                        <span class="header-stat-label">To Do</span>
                    </a>
                    <a href="{{ route('daily-journal.index', array_filter($selectedStatus === 'in_progress' ? $statusFilterParams : array_merge($statusFilterParams, ['status' => 'in_progress']), fn ($value) => $value !== null && $value !== '')) }}" class="header-stat stat-progress {{ $selectedStatus === 'in_progress' ? 'is-active' : '' }}">
                        <span class="header-stat-count">{{ $statusCounts['in_progress'] }}</span>
                        <span class="header-stat-label">Prog</span>
                    </a>
                    <a href="{{ route('daily-journal.index', array_filter($selectedStatus === 'done' ? $statusFilterParams : array_merge($statusFilterParams, ['status' => 'done']), fn ($value) => $value !== null && $value !== '')) }}" class="header-stat stat-done {{ $selectedStatus === 'done' ? 'is-active' : '' }}">
                        <span class="header-stat-count">{{ $statusCounts['done'] }}</span>
                        <span class="header-stat-label">Done</span>
                    </a>
                    <a href="{{ route('daily-journal.index', array_filter($selectedStatus === 'skipped' ? $statusFilterParams : array_merge($statusFilterParams, ['status' => 'skipped']), fn ($value) => $value !== null && $value !== '')) }}" class="header-stat stat-skip {{ $selectedStatus === 'skipped' ? 'is-active' : '' }}">
                        <span class="header-stat-count">{{ $statusCounts['skipped'] }}</span>
                        <span class="header-stat-label">Skip</span>
                    </a>
                </div>
            </div>

            <div class="filter-panel" id="filterPanel">
                <div class="filter-grid">
                    <a href="{{ route('daily-journal.index', ['filter' => 'today', 'date' => now()->toDateString()]) }}" class="filter-choice {{ $filter === 'today' ? 'active' : '' }}">Today</a>
                    <a href="{{ route('daily-journal.index', ['filter' => 'week', 'date' => now()->toDateString()]) }}" class="filter-choice {{ $filter === 'week' ? 'active' : '' }}">This Week</a>
                    <a href="{{ route('daily-journal.index', ['filter' => 'month', 'date' => now()->toDateString()]) }}" class="filter-choice {{ $filter === 'month' ? 'active' : '' }}">This Month</a>
                    <a href="{{ route('daily-journal.index', ['filter' => 'year', 'date' => now()->toDateString()]) }}" class="filter-choice {{ $filter === 'year' ? 'active' : '' }}">This Year</a>
                </div>

                <form method="GET" action="{{ route('daily-journal.index') }}" class="custom-filter-form">
                    <input type="hidden" name="filter" value="custom">
                    <input type="hidden" name="date" value="{{ $rangeStart->toDateString() }}">
                    <div class="custom-filter-row">
                        <div>
                            <label class="form-label" for="start_date">Start date</label>
                            <input type="date" id="start_date" name="start_date" class="journal-input" value="{{ $rangeStart->toDateString() }}">
                        </div>
                        <div>
                            <label class="form-label" for="end_date">End date</label>
                            <input type="date" id="end_date" name="end_date" class="journal-input" value="{{ $rangeEnd->toDateString() }}">
                        </div>
                    </div>
                    <button type="submit" class="apply-filter-button">Apply Custom Range</button>
                </form>
            </div>

            <div class="welcome-copy">
                <h1>My Daily Journal</h1>
            </div>

            <div class="week-strip">
                @foreach($weekDays as $day)
                    <a href="{{ route('daily-journal.index', ['date' => $day->toDateString(), 'filter' => 'today']) }}" class="day-pill {{ $selectedDate->isSameDay($day) && $filter === 'today' ? 'active' : '' }}">
                        <span class="day-name">{{ $day->translatedFormat('D') }}</span>
                        <span class="day-number">{{ $day->format('d') }}</span>
                    </a>
                @endforeach
            </div>

            @if($tasks->isEmpty())
                <div class="empty-state">
                    <span class="emoji">📔</span>
                    <h2>Belum ada task</h2>
                    <p>Tambah task baru untuk mulai menyusun agenda harian Anda.</p>
                </div>
            @else
                <div class="task-stack">
                    @foreach($tasks as $task)
                        @php
                            $isUrgentDeadline = $task->deadline_date
                                && $task->deadline_date->lte(now()->startOfDay())
                                && $task->status !== 'done';
                        @endphp
                        <article
                            class="task-card js-note-card theme-{{ $task->color_theme }} {{ $task->status !== 'done' ? 'is-deletable' : '' }}"
                            data-note-form-id="note-task-{{ $task->id }}"
                            data-task-title="{{ $task->title }}"
                            @if($task->status !== 'done')
                                data-delete-form-id="delete-task-{{ $task->id }}"
                            @endif
                        >
                            @if($task->fromUser)
                                @php
                                    $managerPhoto = optional(optional($task->fromUser)->employee)->photo;
                                    $managerPhotoUrl = $managerPhoto ? asset('storage/' . ltrim($managerPhoto, '/')) : null;
                                    $managerInitials = collect(explode(' ', trim($task->fromUser->name ?? '')))
                                        ->filter()
                                        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                                        ->take(2)
                                        ->join('');
                                @endphp
                                <span class="assigned-manager-badge" title="Task from {{ $task->fromUser->name }}">
                                    @if($managerPhotoUrl)
                                        <img src="{{ $managerPhotoUrl }}" alt="{{ $task->fromUser->name }}" class="assigned-manager-photo">
                                    @else
                                        <span class="assigned-manager-fallback">{{ $managerInitials ?: 'M' }}</span>
                                    @endif
                                    <span class="assigned-manager-alert" aria-hidden="true">
                                        <i class="fas fa-comment-dots"></i>
                                    </span>
                                    <span class="assigned-manager-label">
                                        From {{ $task->fromUser->name }}
                                    </span>
                                </span>
                            @endif

                            <div class="task-header">
                                <div class="task-main">
                                    <div class="task-icon">{{ $task->icon ?: '📝' }}</div>
                                    <div class="task-copy">
                                        <h3 class="task-title">{{ $task->title }}</h3>
                                        <p class="task-meta">
                                            @if($task->task_date)
                                                {{ $task->task_date->format('d/m/Y') }}
                                            @endif
                                            @if($task->task_date && $task->note)
                                                ·
                                            @endif
                                            {{ $task->note ?: 'Catatan belum ditambahkan.' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="task-footer">
                                @if($task->deadline_date)
                                    <div class="task-date-stack">
                                        <span class="deadline-label {{ $isUrgentDeadline ? 'is-urgent' : '' }}">
                                            <i class="fas fa-hourglass-end"></i>
                                            {{ $task->deadline_date->format('d/m/Y') }}
                                        </span>
                                    </div>
                                @endif

                                <div class="task-footer-actions">
                                    <form method="POST" action="{{ route('daily-journal.update', $task) }}" class="status-form">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">
                                        <input type="hidden" name="filter" value="{{ $filter }}">
                                        <input type="hidden" name="start_date" value="{{ $rangeStart->toDateString() }}">
                                        <input type="hidden" name="end_date" value="{{ $rangeEnd->toDateString() }}">
                                        <div class="status-control status-control-{{ $task->status }}">
                                            <span class="status-icon status-{{ $task->status }}">
                                                <i class="{{ $statusIcons[$task->status] ?? 'far fa-circle' }}"></i>
                                            </span>
                                            <select name="status" class="status-select">
                                                @foreach($statusOptions as $statusOption)
                                                    <option value="{{ $statusOption }}" {{ $task->status === $statusOption ? 'selected' : '' }}>{{ $statusLabels[$statusOption] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            @if($task->status !== 'done')
                                <form method="POST" action="{{ route('daily-journal.destroy', $task) }}" class="delete-task-form" id="delete-task-{{ $task->id }}" data-task-title="{{ $task->title }}">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">
                                    <input type="hidden" name="filter" value="{{ $filter }}">
                                    <input type="hidden" name="start_date" value="{{ $rangeStart->toDateString() }}">
                                    <input type="hidden" name="end_date" value="{{ $rangeEnd->toDateString() }}">
                                </form>
                            @endif

                            <form method="POST" action="{{ route('daily-journal.update', $task) }}" class="note-task-form" id="note-task-{{ $task->id }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">
                                <input type="hidden" name="filter" value="{{ $filter }}">
                                <input type="hidden" name="start_date" value="{{ $rangeStart->toDateString() }}">
                                <input type="hidden" name="end_date" value="{{ $rangeEnd->toDateString() }}">
                                <input type="hidden" name="note" value="{{ $task->note ?? '' }}">
                            </form>
                        </article>
                    @endforeach
                </div>
            @endif

            {{-- <a href="/" class="back-link">
                <i class="fas fa-arrow-left"></i>
                <span>Kembali ke Main Menu</span>
            </a> --}}
        </div>
    </div>

    <div class="drag-trash-zone" id="dragTrashZone" aria-hidden="true">
        <i class="fas fa-trash-alt"></i>
        <span>Drop Here</span>
    </div>

    <button type="button" class="open-composer js-open-composer" aria-label="Open add task form">
        <i class="fas fa-plus"></i>
    </button>

    <div class="journal-request-loader" id="journalRequestLoader" aria-hidden="true">
        <div class="journal-request-card">
            <span class="journal-request-spinner" aria-hidden="true"></span>
            <span class="journal-request-text" id="journalRequestText">Processing...</span>
        </div>
    </div>

    <div class="composer-overlay" id="composerOverlay"></div>

    <div class="composer-sheet {{ $errors->any() ? 'active' : '' }}" id="composerSheet">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Tambah Task</h3>
            <button type="button" class="close-sheet" id="closeComposer">×</button>
        </div>

        <form method="POST" action="{{ route('daily-journal.store') }}" class="journal-form">
            @csrf
            <input type="hidden" name="redirect_filter" value="{{ $filter }}">
            <input type="hidden" name="redirect_start_date" value="{{ $rangeStart->toDateString() }}">
            <input type="hidden" name="redirect_end_date" value="{{ $rangeEnd->toDateString() }}">

            <div class="form-row">
                <div>
                    <label class="form-label" for="task_date">Tanggal</label>
                    <input type="date" id="task_date" name="task_date" class="journal-input" value="{{ old('task_date', $selectedDate->toDateString()) }}" required>
                    @error('task_date')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="deadline_date">Deadline</label>
                    <input type="date" id="deadline_date" name="deadline_date" class="journal-input" value="{{ old('deadline_date') }}">
                    @error('deadline_date')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div>
                <label class="form-label" for="title">Nama Task</label>
                <input type="text" id="title" name="title" class="journal-input" value="{{ old('title') }}" placeholder="Contoh: Follow up pasien" maxlength="120" required>
                @error('title')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="form-label" for="note">Catatan Ringkas</label>
                <textarea id="note" name="note" class="journal-textarea" placeholder="Jam, target, atau detail kecil lainnya">{{ old('note') }}</textarea>
                @error('note')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div>
                    <label class="form-label" for="scheduled_time">Jam</label>
                    <input type="time" id="scheduled_time" name="scheduled_time" class="journal-input" value="{{ old('scheduled_time') }}">
                    @error('scheduled_time')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="journal-select">
                        @foreach($statusOptions as $statusOption)
                            <option value="{{ $statusOption }}" {{ old('status', 'todo') === $statusOption ? 'selected' : '' }}>{{ $statusLabels[$statusOption] }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label class="form-label" for="icon">Emoji</label>
                    <input type="text" id="icon" name="icon" class="journal-input" value="{{ old('icon') }}" placeholder="📝" maxlength="16">
                    @error('icon')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label">Warna Card</label>
                    <input type="hidden" name="color_theme" value="{{ old('color_theme', 'rose') }}" id="selectedThemeInput">
                    <div class="theme-grid">
                        @foreach(['rose', 'lavender', 'mint', 'sky', 'peach'] as $theme)
                            <label class="theme-option">
                                <input type="radio" name="theme_picker" value="{{ $theme }}" {{ old('color_theme', 'rose') === $theme ? 'checked' : '' }}>
                                <span class="theme-preview {{ $theme }}"></span>
                            </label>
                        @endforeach
                    </div>
                    @error('color_theme')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <button type="submit" class="submit-button">Simpan Task</button>
        </form>
    </div>
    </div>

@endsection

@section('scripts')
    <script>
        (function () {
            let isRequestPending = false;

            function setBusyState(isBusy, message) {
                const root = document.getElementById('myDailyJournalRoot');
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
                const formData = options && options.body instanceof FormData ? options.body : null;

                if (method === 'GET') {
                    return 'Loading...';
                }

                if (typeof url === 'string' && /daily-journal\/\d+\/report$/i.test(url)) {
                    return 'Sending report...';
                }

                if (typeof url === 'string' && /daily-journal\/\d+$/i.test(url) && formData && formData.get('_method') === 'DELETE') {
                    return 'Deleting task...';
                }

                if (typeof url === 'string' && /daily-journal\/\d+$/i.test(url)) {
                    return 'Saving changes...';
                }

                return 'Saving task...';
            }

            async function replaceRootFromResponse(response) {
                const html = await response.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const nextRoot = doc.getElementById('myDailyJournalRoot');
                const currentRoot = document.getElementById('myDailyJournalRoot');

                if (!nextRoot || !currentRoot) {
                    window.location.assign(response.url || window.location.href);
                    return;
                }

                currentRoot.replaceWith(nextRoot);
                window.history.replaceState({}, '', response.url || window.location.href);
                initMyDailyJournalAjax();
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
                function requestDelete(form) {
                    const message = 'Task yang dihapus tidak bisa dikembalikan.';

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Hapus task?',
                            text: message,
                            showCancelButton: true,
                            confirmButtonText: 'Ya, hapus',
                            cancelButtonText: 'Batal',
                            reverseButtons: true
                        }).then(function (result) {
                            if (result.value) {
                                ajaxVisit(form.action, {
                                    method: 'POST',
                                    body: new FormData(form)
                                });
                            }
                        });

                        return;
                    }

                    if (window.confirm(message)) {
                        ajaxVisit(form.action, {
                            method: 'POST',
                            body: new FormData(form)
                        });
                    }
                }

                function bindDragDelete() {
                    const trashZone = root.querySelector('#dragTrashZone');

                    if (!trashZone || !window.PointerEvent) {
                        return;
                    }

                    let pendingPress = null;
                    let activeDrag = null;

                    function clearPendingPress() {
                        if (!pendingPress) {
                            return;
                        }

                        if (pendingPress.captureOwner && typeof pendingPress.captureOwner.releasePointerCapture === 'function') {
                            try {
                                pendingPress.captureOwner.releasePointerCapture(pendingPress.pointerId);
                            } catch (error) {
                            }
                        }

                        window.clearTimeout(pendingPress.timer);
                        window.removeEventListener('pointermove', pendingPress.onMove);
                        window.removeEventListener('pointerup', pendingPress.onEnd);
                        window.removeEventListener('pointercancel', pendingPress.onEnd);
                        pendingPress = null;
                    }

                    function resetDragState() {
                        if (!activeDrag) {
                            return;
                        }

                        window.removeEventListener('pointermove', activeDrag.onMove);
                        window.removeEventListener('pointerup', activeDrag.onEnd);
                        window.removeEventListener('pointercancel', activeDrag.onEnd);

                        activeDrag.card.classList.remove('is-dragging');
                        activeDrag.card.style.left = '';
                        activeDrag.card.style.top = '';
                        activeDrag.card.style.width = '';

                        if (activeDrag.captureOwner && typeof activeDrag.captureOwner.releasePointerCapture === 'function') {
                            try {
                                activeDrag.captureOwner.releasePointerCapture(activeDrag.pointerId);
                            } catch (error) {
                            }
                        }

                        if (activeDrag.placeholder && activeDrag.placeholder.parentNode) {
                            activeDrag.placeholder.parentNode.removeChild(activeDrag.placeholder);
                        }

                        document.body.classList.remove('task-delete-dragging');
                        trashZone.classList.remove('active', 'is-over');
                        root.dataset.suppressNoteClickUntil = String(Date.now() + 250);
                        activeDrag = null;
                    }

                    function isPointerOverTrash(clientX, clientY) {
                        const rect = trashZone.getBoundingClientRect();

                        return clientX >= rect.left && clientX <= rect.right && clientY >= rect.top && clientY <= rect.bottom;
                    }

                    function updateDragPosition(clientX, clientY) {
                        if (!activeDrag) {
                            return;
                        }

                        activeDrag.card.style.left = (clientX - activeDrag.offsetX) + 'px';
                        activeDrag.card.style.top = (clientY - activeDrag.offsetY) + 'px';
                        trashZone.classList.toggle('is-over', isPointerOverTrash(clientX, clientY));
                    }

                    function startDrag(card, pointerId, clientX, clientY) {
                        const formId = card.dataset.deleteFormId;
                        const form = formId ? root.querySelector('#' + formId) : null;

                        if (!form) {
                            clearPendingPress();
                            return;
                        }

                        const rect = card.getBoundingClientRect();
                        const placeholder = document.createElement('div');
                        placeholder.className = 'task-card-placeholder';
                        placeholder.style.height = rect.height + 'px';

                        card.insertAdjacentElement('afterend', placeholder);
                        card.classList.add('is-dragging');
                        card.style.width = rect.width + 'px';
                        card.style.left = rect.left + 'px';
                        card.style.top = rect.top + 'px';

                        document.body.classList.add('task-delete-dragging');
                        trashZone.classList.add('active');

                        const onMove = function (event) {
                            if (!activeDrag || event.pointerId !== activeDrag.pointerId) {
                                return;
                            }

                            event.preventDefault();
                            updateDragPosition(event.clientX, event.clientY);
                        };

                        const onEnd = function (event) {
                            if (!activeDrag || event.pointerId !== activeDrag.pointerId) {
                                return;
                            }

                            const shouldDelete = isPointerOverTrash(event.clientX, event.clientY);
                            const deleteForm = activeDrag.form;

                            resetDragState();

                            if (shouldDelete) {
                                requestDelete(deleteForm);
                            }
                        };

                        activeDrag = {
                            card: card,
                            form: form,
                            placeholder: placeholder,
                            captureOwner: card,
                            pointerId: pointerId,
                            offsetX: clientX - rect.left,
                            offsetY: clientY - rect.top,
                            onMove: onMove,
                            onEnd: onEnd
                        };

                        clearPendingPress();
                        window.addEventListener('pointermove', onMove, { passive: false });
                        window.addEventListener('pointerup', onEnd);
                        window.addEventListener('pointercancel', onEnd);
                        updateDragPosition(clientX, clientY);
                    }

                    root.querySelectorAll('.task-card.is-deletable').forEach(function (card) {
                        card.addEventListener('pointerdown', function (event) {
                            if (event.button !== undefined && event.button !== 0) {
                                return;
                            }

                            if (event.target.closest('select, input, textarea, button, a, label')) {
                                return;
                            }

                            clearPendingPress();

                            const pointerId = event.pointerId;
                            const startX = event.clientX;
                            const startY = event.clientY;

                            if (typeof card.setPointerCapture === 'function') {
                                try {
                                    card.setPointerCapture(pointerId);
                                } catch (error) {
                                }
                            }

                            const onMove = function (moveEvent) {
                                if (!pendingPress || moveEvent.pointerId !== pendingPress.pointerId) {
                                    return;
                                }

                                if (Math.abs(moveEvent.clientX - pendingPress.startX) > 10 || Math.abs(moveEvent.clientY - pendingPress.startY) > 10) {
                                    clearPendingPress();
                                }
                            };

                            const onEnd = function (endEvent) {
                                if (!pendingPress || endEvent.pointerId !== pendingPress.pointerId) {
                                    return;
                                }

                                clearPendingPress();
                            };

                            pendingPress = {
                                card: card,
                                captureOwner: card,
                                pointerId: pointerId,
                                startX: startX,
                                startY: startY,
                                onMove: onMove,
                                onEnd: onEnd,
                                timer: window.setTimeout(function () {
                                    if (!pendingPress || pendingPress.pointerId !== pointerId) {
                                        return;
                                    }

                                    startDrag(card, pointerId, startX, startY);
                                }, 180)
                            };

                            window.addEventListener('pointermove', onMove, { passive: true });
                            window.addEventListener('pointerup', onEnd);
                            window.addEventListener('pointercancel', onEnd);
                        });
                    });
                }

                function bindNoteEditing() {
                    root.querySelectorAll('.js-note-card').forEach(function (card) {
                        card.addEventListener('click', function (event) {
                            if (Date.now() < Number(root.dataset.suppressNoteClickUntil || 0)) {
                                return;
                            }

                            if (event.target.closest('select, input, textarea, button, a, label, .assigned-manager-badge')) {
                                return;
                            }

                            const formId = card.dataset.noteFormId;
                            const form = formId ? root.querySelector('#' + formId) : null;

                            if (!form) {
                                return;
                            }

                            const noteInput = form.querySelector('input[name="note"]');
                            const initialValue = noteInput ? noteInput.value : '';
                            const taskTitle = card.dataset.taskTitle || 'Task';

                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: 'Edit note',
                                    text: taskTitle,
                                    input: 'textarea',
                                    inputValue: initialValue,
                                    inputAttributes: {
                                        maxlength: '180',
                                        autocapitalize: 'off'
                                    },
                                    inputPlaceholder: 'Tulis catatan task',
                                    showCancelButton: true,
                                    confirmButtonText: 'Simpan',
                                    cancelButtonText: 'Batal',
                                    reverseButtons: true,
                                    inputValidator: function (value) {
                                        if (value && value.length > 180) {
                                            return 'Catatan maksimal 180 karakter.';
                                        }

                                        return null;
                                    }
                                }).then(function (result) {
                                    if (!result.value) {
                                        return;
                                    }

                                    if (noteInput) {
                                        noteInput.value = result.value;
                                    }

                                    ajaxVisit(form.action, {
                                        method: 'POST',
                                        body: new FormData(form)
                                    });
                                });

                                return;
                            }

                            const updatedNote = window.prompt('Edit note', initialValue);

                            if (updatedNote === null) {
                                return;
                            }

                            if (noteInput) {
                                noteInput.value = updatedNote;
                            }

                            ajaxVisit(form.action, {
                                method: 'POST',
                                body: new FormData(form)
                            });
                        });
                    });
                }

                root.querySelectorAll('.filter-choice, .week-strip a, .header-summary a').forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        ajaxVisit(link.href, { method: 'GET' });
                    });
                });

                root.querySelectorAll('form.custom-filter-form').forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        event.preventDefault();
                        ajaxVisit(buildGetUrl(form), { method: 'GET' });
                    });
                });

                root.querySelectorAll('form.status-form, form.journal-form').forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        event.preventDefault();
                        ajaxVisit(form.action, {
                            method: 'POST',
                            body: new FormData(form)
                        });
                    });
                });

                root.querySelectorAll('.status-form .status-select').forEach(function (select) {
                    select.addEventListener('change', function () {
                        const form = select.closest('form');

                        if (!form) {
                            return;
                        }

                        ajaxVisit(form.action, {
                            method: 'POST',
                            body: new FormData(form)
                        });
                    });
                });

                root.querySelectorAll('.delete-task-form').forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        event.preventDefault();

                        requestDelete(form);
                    });
                });

                bindDragDelete();
                bindNoteEditing();
            }

            window.initMyDailyJournalAjax = function () {
                const root = document.getElementById('myDailyJournalRoot');

                if (!root) {
                    return;
                }

                const sheet = root.querySelector('#composerSheet');
                const overlay = root.querySelector('#composerOverlay');
                const openButtons = root.querySelectorAll('.js-open-composer');
                const closeButton = root.querySelector('#closeComposer');
                const themeInput = root.querySelector('#selectedThemeInput');
                const themeRadios = root.querySelectorAll('input[name="theme_picker"]');
                const filterToggleBtn = root.querySelector('#filterToggleBtn');
                const filterPanel = root.querySelector('#filterPanel');
                let openSheetFrame = null;

                function openSheet() {
                    if (filterPanel) {
                        filterPanel.classList.remove('active');
                    }

                    if (openSheetFrame !== null) {
                        window.cancelAnimationFrame(openSheetFrame);
                    }

                    openSheetFrame = window.requestAnimationFrame(function () {
                        if (sheet) {
                            sheet.classList.add('active');
                        }
                        if (overlay) {
                            overlay.classList.add('active');
                        }
                        openSheetFrame = null;
                    });
                }

                function closeSheet() {
                    if (openSheetFrame !== null) {
                        window.cancelAnimationFrame(openSheetFrame);
                        openSheetFrame = null;
                    }

                    if (sheet) {
                        sheet.classList.remove('active');
                    }
                    if (overlay) {
                        overlay.classList.remove('active');
                    }
                }

                openButtons.forEach(function (button) {
                    button.addEventListener('click', openSheet);
                });

                if (closeButton) {
                    closeButton.addEventListener('click', closeSheet);
                }

                if (overlay) {
                    overlay.addEventListener('click', closeSheet);
                }

                if (filterToggleBtn && filterPanel) {
                    filterToggleBtn.addEventListener('click', function () {
                        filterPanel.classList.toggle('active');
                    });
                }

                themeRadios.forEach(function (radio) {
                    radio.addEventListener('change', function () {
                        if (themeInput) {
                            themeInput.value = this.value;
                        }
                    });
                });

                if (sheet && sheet.classList.contains('active') && overlay) {
                    overlay.classList.add('active');
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

            window.initMyDailyJournalAjax();
        })();
    </script>
@endsection