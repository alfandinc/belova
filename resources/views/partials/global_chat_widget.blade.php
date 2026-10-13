@php
    $chatLauncherBottom = max(24, (int) ($chatLauncherBottom ?? 24));
@endphp

<style>
    .belova-chat-widget {
        --belova-chat-accent-start: #2563eb;
        --belova-chat-accent-end: #3b82f6;
        --belova-chat-accent-deep: #1d4ed8;
        --belova-chat-accent-soft: rgba(37, 99, 235, 0.12);
        --belova-chat-accent-shadow: rgba(37, 99, 235, 0.28);
        --belova-chat-accent-shadow-hover: rgba(37, 99, 235, 0.32);
        --belova-chat-header-bg: linear-gradient(90deg, rgba(37, 99, 235, 0.12), rgba(96, 165, 250, 0.08));
        --belova-chat-messages-base: linear-gradient(180deg, rgba(239, 246, 255, 0.96), rgba(219, 234, 254, 0.80));
        --belova-chat-messages-pattern: radial-gradient(circle at 18px 18px, rgba(59, 130, 246, 0.18) 0 2px, transparent 2.5px);
        --belova-chat-messages-pattern-size: 28px 28px;
        --belova-chat-messages-pattern-opacity: 0.34;
        position: fixed;
        /* Bottom-left, next to the sidebar: --belova-chat-left is set by the script below (sidebar right edge + 24px) */
        left: var(--belova-chat-left, 24px);
        bottom: var(--belova-chat-bottom);
        z-index: 1200;
        font-family: inherit;
    }

    .belova-chat-launcher {
        position: relative;
        min-width: 54px;
        height: 50px;
        padding: 0 16px 0 14px;
        border: 0;
        border-radius: 16px;
        /* Clinic accent (same as the sidebar), not the chat panel theme: the launcher is part of the app chrome */
        background: linear-gradient(135deg, var(--chat-accent, #2563eb), var(--chat-accent-deep, #1d4ed8));
        color: #fff;
        box-shadow: 0 14px 30px -10px rgba(var(--chat-accent-rgb, 37, 99, 235), 0.6);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .belova-chat-launcher:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 36px -10px rgba(var(--chat-accent-rgb, 37, 99, 235), 0.7);
    }

    .belova-chat-launcher svg,
    .belova-chat-panel-settings svg,
    .belova-chat-panel-close svg {
        width: 24px;
        height: 24px;
        fill: none;
        stroke: currentColor;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-width: 2;
    }

    .belova-chat-launcher-text {
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
    }

    .belova-chat-launcher-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        border-radius: 999px;
        background: #ef4444;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 18px rgba(239, 68, 68, 0.3);
    }

    .belova-chat-panel {
        position: absolute;
        left: 0;
        bottom: 74px;
        width: min(860px, calc(100vw - var(--belova-chat-left, 24px) - 24px));
        height: min(620px, calc(100vh - 120px));
        background: rgba(255, 255, 255, 0.97);
        color: #0f172a;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 20px;
        box-shadow: 0 24px 64px rgba(15, 23, 42, 0.24);
        overflow: hidden;
        backdrop-filter: blur(18px);
        display: flex;
        flex-direction: column;
    }

    .theme-dark .belova-chat-panel,
    html.theme-dark .belova-chat-panel,
    html[data-theme="dark"] .belova-chat-panel,
    body.dark-sidenav .belova-chat-panel {
        background: rgba(15, 23, 42, 0.96);
        color: #e2e8f0;
        border-color: rgba(148, 163, 184, 0.16);
        box-shadow: 0 24px 64px rgba(2, 6, 23, 0.5);
    }

    .belova-chat-panel-header {
        padding: 16px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
        background: var(--belova-chat-header-bg);
        position: relative;
        z-index: 3;
        flex: 0 0 auto;
    }

    .belova-chat-panel-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .belova-chat-panel-title {
        font-size: 15px;
        font-weight: 700;
    }

    .belova-chat-panel-subtitle {
        font-size: 12px;
        opacity: 0.72;
    }

    .belova-chat-panel-settings,
    .belova-chat-panel-close {
        width: 36px;
        height: 36px;
        border: 0;
        border-radius: 12px;
        background: rgba(148, 163, 184, 0.12);
        color: inherit;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .belova-chat-settings-panel {
        padding: 14px 18px 16px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.14);
        background: rgba(148, 163, 184, 0.05);
        display: grid;
        gap: 14px;
        position: relative;
        z-index: 2;
        flex: 0 0 auto;
    }

    .belova-chat-settings-group {
        display: grid;
        gap: 8px;
    }

    .belova-chat-settings-label {
        font-size: 12px;
        font-weight: 700;
        opacity: 0.78;
    }

    .belova-chat-theme-options {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .belova-chat-theme-card {
        border: 0;
        border-radius: 14px;
        position: relative;
        overflow: hidden;
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.18);
        background: rgba(255, 255, 255, 0.72);
        padding: 6px;
        width: 92px;
        display: grid;
        gap: 6px;
        text-align: left;
    }

    .theme-dark .belova-chat-theme-card,
    html.theme-dark .belova-chat-theme-card,
    html[data-theme="dark"] .belova-chat-theme-card,
    body.dark-sidenav .belova-chat-theme-card {
        background: rgba(30, 41, 59, 0.72);
    }

    .belova-chat-theme-card-preview {
        width: 100%;
        height: 34px;
        border-radius: 10px;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.24);
    }

    .belova-chat-theme-card-label {
        font-size: 11px;
        font-weight: 700;
        line-height: 1.2;
        color: inherit;
    }

    .belova-chat-theme-card.is-active::after {
        content: '';
        position: absolute;
        inset: 3px;
        border: 2px solid var(--belova-chat-accent-start);
        border-radius: 11px;
    }

    .belova-chat-panel-body {
        display: grid;
        grid-template-columns: 280px minmax(0, 1fr);
        flex: 1 1 auto;
        min-height: 0;
    }

    .belova-chat-sidebar {
        border-right: 1px solid rgba(148, 163, 184, 0.14);
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .belova-chat-search-wrap {
        padding: 14px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
    }

    .belova-chat-search {
        width: 100%;
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        background: rgba(148, 163, 184, 0.08);
        color: inherit;
        padding: 10px 12px;
        outline: none;
    }

    .belova-chat-user-list,
    .belova-chat-messages {
        min-height: 0;
        overflow-y: auto;
    }

    .belova-chat-placeholder,
    .belova-chat-empty-state {
        padding: 20px;
        font-size: 13px;
        opacity: 0.72;
    }

    .belova-chat-empty-title {
        font-weight: 700;
        margin-bottom: 6px;
        color: inherit;
        opacity: 0.94;
    }

    .belova-chat-empty-state {
        position: relative;
        z-index: 1;
    }

    .belova-chat-user-item {
        width: 100%;
        padding: 13px 14px;
        border: 0;
        background: transparent;
        color: inherit;
        text-align: left;
        display: flex;
        gap: 12px;
        align-items: flex-start;
        border-bottom: 1px solid rgba(148, 163, 184, 0.08);
    }

    .belova-chat-user-item:hover,
    .belova-chat-user-item.is-active {
        background: var(--belova-chat-accent-soft);
    }

    .belova-chat-user-avatar {
        flex: 0 0 auto;
        width: 38px;
        height: 38px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--belova-chat-accent-start), var(--belova-chat-accent-end));
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13px;
        overflow: hidden;
    }

    .belova-chat-user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .belova-chat-user-main {
        min-width: 0;
        flex: 1;
    }

    .belova-chat-user-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 4px;
    }

    .belova-chat-user-name {
        font-size: 13px;
        font-weight: 700;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .belova-chat-user-role,
    .belova-chat-user-preview,
    .belova-chat-conversation-role,
    .belova-chat-message-time {
        font-size: 11px;
        opacity: 0.72;
    }

    .belova-chat-user-preview {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .belova-chat-user-unread {
        margin-left: auto;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: 999px;
        background: #ef4444;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .belova-chat-conversation {
        display: flex;
        flex-direction: column;
        min-width: 0;
        min-height: 0;
    }

    .belova-chat-conversation-header {
        padding: 16px 18px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.14);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .belova-chat-conversation-avatar {
        width: 40px;
        height: 40px;
        border-radius: 13px;
        flex: 0 0 auto;
        background: linear-gradient(135deg, var(--belova-chat-accent-start), var(--belova-chat-accent-end));
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 700;
        overflow: hidden;
    }

    .belova-chat-conversation-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .belova-chat-conversation-meta {
        min-width: 0;
    }

    .belova-chat-conversation-name {
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 2px;
    }

    .belova-chat-messages {
        flex: 1;
        padding: 18px;
        position: relative;
        background: var(--belova-chat-messages-base);
        isolation: isolate;
    }

    .belova-chat-messages::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image: var(--belova-chat-messages-pattern);
        background-size: var(--belova-chat-messages-pattern-size);
        background-repeat: repeat;
        background-position: center top;
        opacity: var(--belova-chat-messages-pattern-opacity);
        pointer-events: none;
        z-index: 0;
    }

    .belova-chat-message-row {
        display: flex;
        margin-bottom: 14px;
        position: relative;
        z-index: 1;
    }

    .belova-chat-message-row.is-mine {
        justify-content: flex-end;
    }

    .belova-chat-message-bubble {
        max-width: min(82%, 460px);
        border-radius: 16px;
        padding: 10px 12px 8px;
        background: #fff;
        color: #0f172a;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        display: inline-flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .theme-dark .belova-chat-message-bubble,
    html.theme-dark .belova-chat-message-bubble,
    html[data-theme="dark"] .belova-chat-message-bubble,
    body.dark-sidenav .belova-chat-message-bubble {
        background: rgba(30, 41, 59, 0.98);
        color: #e2e8f0;
        box-shadow: none;
    }

    .belova-chat-message-row.is-mine .belova-chat-message-bubble {
        background: linear-gradient(135deg, var(--belova-chat-accent-deep), var(--belova-chat-accent-start));
        color: #fff;
        box-shadow: 0 12px 28px color-mix(in srgb, var(--belova-chat-accent-deep) 34%, transparent);
    }

    .belova-chat-message-text {
        font-size: 13px;
        line-height: 1.35;
    }

    .belova-chat-message-time {
        margin-top: 0;
        align-self: flex-end;
        text-align: right;
        line-height: 1;
        opacity: 0.74;
    }

    .belova-chat-composer {
        padding: 14px 18px 18px;
        border-top: 1px solid rgba(148, 163, 184, 0.14);
        display: flex;
        gap: 12px;
        align-items: flex-end;
    }

    .belova-chat-input {
        flex: 1;
        resize: none;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        background: rgba(148, 163, 184, 0.08);
        color: inherit;
        padding: 11px 12px;
        min-height: 48px;
        max-height: 120px;
        outline: none;
    }

    .belova-chat-send {
        height: 48px;
        border: 0;
        border-radius: 14px;
        padding: 0 18px;
        background: linear-gradient(135deg, var(--belova-chat-accent-start), var(--belova-chat-accent-end));
        color: #fff;
        font-weight: 700;
    }

    .belova-chat-send:disabled,
    .belova-chat-input:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    @media (max-width: 991.98px) {
        .belova-chat-panel {
            width: min(680px, calc(100vw - 24px));
        }
    }

    @media (max-width: 767.98px) {
        .belova-chat-widget {
            left: 12px;
        }

        .belova-chat-launcher {
            padding-right: 16px;
            padding-left: 14px;
            gap: 8px;
        }

        .belova-chat-launcher-text {
            font-size: 12px;
        }

        .belova-chat-panel {
            left: 0;
            width: calc(100vw - 24px);
            height: min(78vh, 680px);
            bottom: 70px;
        }

        .belova-chat-panel-body {
            grid-template-columns: 1fr;
            grid-template-rows: 220px minmax(0, 1fr);
        }

        .belova-chat-sidebar {
            border-right: 0;
            border-bottom: 1px solid rgba(148, 163, 184, 0.14);
        }
    }

    /* Incoming-message popups: stacked above the launcher, answerable in place */
    .belova-chat-popups {
        position: absolute;
        left: 0;
        bottom: 64px;
        width: min(420px, calc(100vw - var(--belova-chat-left, 24px) - 24px));
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
    }

    .belova-chat-popup {
        pointer-events: auto;
        background: #fff;
        color: #0f172a;
        border: 1px solid rgba(148, 163, 184, 0.24);
        border-radius: 16px;
        box-shadow: 0 18px 44px rgba(15, 23, 42, 0.22);
        overflow: hidden;
        animation: belovaChatPopupIn 0.22s ease-out;
    }

    .theme-dark .belova-chat-popup,
    html.theme-dark .belova-chat-popup,
    html[data-theme="dark"] .belova-chat-popup,
    body.dark-sidenav .belova-chat-popup {
        background: #0f172a;
        color: #e2e8f0;
        border-color: rgba(148, 163, 184, 0.18);
        box-shadow: 0 18px 44px rgba(2, 6, 23, 0.55);
    }

    @keyframes belovaChatPopupIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .belova-chat-popup-head {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 12px 12px 14px;
        background: var(--belova-chat-header-bg);
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
    }

    .belova-chat-popup-head .belova-chat-user-avatar {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        font-size: 14px;
    }

    .belova-chat-popup-who {
        min-width: 0;
        flex: 1;
    }

    .belova-chat-popup-name {
        font-size: 15px;
        font-weight: 700;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .belova-chat-popup-sub {
        font-size: 12px;
        opacity: 0.72;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .belova-chat-popup-btn {
        width: 34px;
        height: 34px;
        flex: 0 0 auto;
        border: 0;
        border-radius: 10px;
        background: rgba(148, 163, 184, 0.14);
        color: inherit;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .belova-chat-popup-btn svg {
        width: 16px;
        height: 16px;
        fill: none;
        stroke: currentColor;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-width: 2;
    }

    .belova-chat-popup-messages {
        max-height: 300px;
        overflow-y: auto;
        padding: 14px 14px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        background: var(--belova-chat-messages-base);
    }

    .belova-chat-popup-msg {
        align-self: flex-start;
        max-width: 85%;
        background: #fff;
        color: #0f172a;
        border-radius: 12px;
        padding: 9px 12px 6px;
        font-size: 14px;
        line-height: 1.35;
        white-space: pre-wrap;
        word-break: break-word;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
    }

    .theme-dark .belova-chat-popup-msg,
    html.theme-dark .belova-chat-popup-msg,
    html[data-theme="dark"] .belova-chat-popup-msg,
    body.dark-sidenav .belova-chat-popup-msg {
        background: rgba(30, 41, 59, 0.98);
        color: #e2e8f0;
    }

    .belova-chat-popup-msg.is-mine {
        align-self: flex-end;
        background: linear-gradient(135deg, var(--belova-chat-accent-deep), var(--belova-chat-accent-start));
        color: #fff;
    }

    .belova-chat-popup-msg-time {
        display: block;
        text-align: right;
        font-size: 11px;
        opacity: 0.7;
        margin-top: 2px;
    }

    .belova-chat-popup-composer {
        display: flex;
        gap: 8px;
        align-items: flex-end;
        padding: 12px 14px 14px;
        border-top: 1px solid rgba(148, 163, 184, 0.14);
    }

    .belova-chat-popup-input {
        flex: 1;
        resize: none;
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, 0.26);
        background: rgba(148, 163, 184, 0.08);
        color: inherit;
        padding: 10px 12px;
        min-height: 44px;
        max-height: 120px;
        font-size: 14px;
        outline: none;
    }

    .belova-chat-popup-send {
        height: 44px;
        border: 0;
        border-radius: 12px;
        padding: 0 18px;
        background: linear-gradient(135deg, var(--belova-chat-accent-start), var(--belova-chat-accent-end));
        color: #fff;
        font-size: 13px;
        font-weight: 700;
    }

    .belova-chat-popup-send:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .belova-chat-popup-status {
        padding: 0 12px 10px;
        font-size: 11px;
        opacity: 0.75;
    }

    /* Own hidden state: must not depend on Bootstrap's .d-none being loaded yet */
    .belova-chat-widget .d-none,
    .belova-chat-panel[aria-hidden="true"] {
        display: none !important;
    }
</style>

<div class="belova-chat-widget" id="belovaChatWidget" style="--belova-chat-bottom: {{ $chatLauncherBottom }}px;">
    <button
        type="button"
        class="belova-chat-launcher"
        id="belovaChatLauncher"
        style="{{ \App\Support\ClinicTheme::cssVars('chat') }}"
        aria-label="Buka chat internal"
        title="Chat Internal"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M7 10.5h10M7 14h6M6.8 19.2l-2.6 1.7.9-3.3A7.9 7.9 0 0 1 4 13c0-4.4 3.6-8 8-8s8 3.6 8 8-3.6 8-8 8c-1.9 0-3.7-.7-5.2-1.8Z"></path>
        </svg>
        <span class="belova-chat-launcher-text">Chat Internal</span>
        <span class="belova-chat-launcher-badge d-none" id="belovaChatLauncherBadge">0</span>
    </button>

    <div class="belova-chat-popups" id="belovaChatPopups" aria-live="polite"></div>

    <div class="belova-chat-panel d-none" id="belovaChatPanel" aria-hidden="true">
        <div class="belova-chat-panel-header">
            <div>
                <div class="belova-chat-panel-title">Chat Internal</div>
                <div class="belova-chat-panel-subtitle">Komunikasi antar user</div>
            </div>
            <div class="belova-chat-panel-actions">
                <button type="button" class="belova-chat-panel-settings" id="belovaChatSettingsToggle" aria-label="Ubah tema chat" title="Ubah tema chat">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z"></path>
                        <path d="M19.4 15a1 1 0 0 0 .2 1.1l.1.1a1.2 1.2 0 0 1 0 1.7l-1.6 1.6a1.2 1.2 0 0 1-1.7 0l-.1-.1a1 1 0 0 0-1.1-.2 1 1 0 0 0-.6.9v.2a1.2 1.2 0 0 1-1.2 1.2h-2.2a1.2 1.2 0 0 1-1.2-1.2V20a1 1 0 0 0-.6-.9 1 1 0 0 0-1.1.2l-.1.1a1.2 1.2 0 0 1-1.7 0L4.3 18a1.2 1.2 0 0 1 0-1.7l.1-.1a1 1 0 0 0 .2-1.1 1 1 0 0 0-.9-.6h-.2a1.2 1.2 0 0 1-1.2-1.2v-2.2a1.2 1.2 0 0 1 1.2-1.2H3.8a1 1 0 0 0 .9-.6 1 1 0 0 0-.2-1.1l-.1-.1a1.2 1.2 0 0 1 0-1.7l1.6-1.6a1.2 1.2 0 0 1 1.7 0l.1.1a1 1 0 0 0 1.1.2 1 1 0 0 0 .6-.9v-.2a1.2 1.2 0 0 1 1.2-1.2h2.2a1.2 1.2 0 0 1 1.2 1.2v.2a1 1 0 0 0 .6.9 1 1 0 0 0 1.1-.2l.1-.1a1.2 1.2 0 0 1 1.7 0l1.6 1.6a1.2 1.2 0 0 1 0 1.7l-.1.1a1 1 0 0 0-.2 1.1 1 1 0 0 0 .9.6h.2a1.2 1.2 0 0 1 1.2 1.2v2.2a1.2 1.2 0 0 1-1.2 1.2h-.2a1 1 0 0 0-.9.6Z"></path>
                    </svg>
                </button>
                <button type="button" class="belova-chat-panel-close" id="belovaChatClose" aria-label="Tutup chat">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M6 6l12 12M18 6 6 18"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="belova-chat-settings-panel d-none" id="belovaChatSettingsPanel">
            <div class="belova-chat-settings-group">
                <div class="belova-chat-settings-label">Tema Chat</div>
                <div class="belova-chat-theme-options" id="belovaChatThemeOptions"></div>
            </div>
        </div>

        <div class="belova-chat-panel-body">
            <aside class="belova-chat-sidebar">
                <div class="belova-chat-search-wrap">
                    <input type="text" class="belova-chat-search" id="belovaChatSearch" placeholder="Cari user..." autocomplete="off">
                </div>
                <div class="belova-chat-user-list" id="belovaChatUserList">
                    <div class="belova-chat-placeholder">Memuat user...</div>
                </div>
            </aside>

            <section class="belova-chat-conversation">
                <div class="belova-chat-conversation-header" id="belovaChatConversationHeader">
                    <div class="belova-chat-conversation-name">Pilih user</div>
                    <div class="belova-chat-conversation-role">Pilih user di panel kiri untuk mulai chat.</div>
                </div>
                <div class="belova-chat-messages" id="belovaChatMessages">
                    <div class="belova-chat-empty-state">
                        <div class="belova-chat-empty-title">Belum ada percakapan aktif</div>
                        <div class="belova-chat-empty-text">Pilih salah satu user untuk membuka riwayat pesan.</div>
                    </div>
                </div>
                <div class="belova-chat-composer">
                    <textarea
                        class="belova-chat-input"
                        id="belovaChatInput"
                        rows="2"
                        placeholder="Tulis pesan..."
                        disabled
                    ></textarea>
                    <button type="button" class="belova-chat-send" id="belovaChatSend" disabled>Kirim</button>
                </div>
            </section>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.__belovaChatWidgetBooted) {
            return;
        }

        window.__belovaChatWidgetBooted = true;

        const widget = document.getElementById('belovaChatWidget');
        if (!widget || !window.jQuery) {
            return;
        }

        const $ = window.jQuery;

        // Bottom-left, just right of the sidebar while it is shown (hidden / collapsed / mobile: the screen edge)
        const placeChatWidget = function () {
            let left = 24;
            const sidenav = document.querySelector('.left-sidenav');
            if (sidenav) {
                const style = window.getComputedStyle(sidenav);
                const rect = sidenav.getBoundingClientRect();
                if (style.display !== 'none' && style.visibility !== 'hidden' && style.position === 'fixed' && rect.width > 0 && rect.right > 0) {
                    left = Math.round(rect.right) + 24;
                }
            }
            widget.style.setProperty('--belova-chat-left', left + 'px');
        };
        placeChatWidget();
        window.addEventListener('resize', placeChatWidget);
        // The menu toggle collapses the sidebar via a body class (and a .3s transition)
        new MutationObserver(function () {
            placeChatWidget();
            setTimeout(placeChatWidget, 350);
        }).observe(document.body, { attributes: true, attributeFilter: ['class'] });

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        const endpoints = {
            users: @json(route('chat.users')),
            // Plain URL, not route(): a stale route cache must not take down every page that includes the widget
            incoming: @json(url('/chat/incoming')),
            conversationTemplate: @json(url('/chat/conversations/__USER__')),
        };

        const state = {
            open: false,
            activeUserId: null,
            users: [],
            usersLoaded: false,
            search: '',
            searchTimer: null,
            conversationTimer: null,
            usersTimer: null,
            lastRenderedMessageId: null,
            lastTotalUnread: null,
        };

        const launcher = document.getElementById('belovaChatLauncher');
        const launcherBadge = document.getElementById('belovaChatLauncherBadge');
        const panel = document.getElementById('belovaChatPanel');
        const closeButton = document.getElementById('belovaChatClose');
        const settingsToggle = document.getElementById('belovaChatSettingsToggle');
        const settingsPanel = document.getElementById('belovaChatSettingsPanel');
        const themeOptions = document.getElementById('belovaChatThemeOptions');
        const searchInput = document.getElementById('belovaChatSearch');
        const userList = document.getElementById('belovaChatUserList');
        const header = document.getElementById('belovaChatConversationHeader');
        const messages = document.getElementById('belovaChatMessages');
        const input = document.getElementById('belovaChatInput');
        const sendButton = document.getElementById('belovaChatSend');
        const popupContainer = document.getElementById('belovaChatPopups');
        const authUserId = @json(auth()->id());

        const storageKeys = {
            theme: 'belova-chat-theme',
            // Per sender: highest message id the user closed the popup on, so it does not come back on the next page
            popupDismissed: 'belova-chat-popup-dismissed-' + authUserId,
            // Highest incoming message id already announced with a sound
            popupNotified: 'belova-chat-popup-notified-' + authUserId
        };

        const popups = {}; // senderId -> { el, list, input, send, status, maxId, closeTimer }
        const maxVisiblePopups = 3;
        let incomingTimer = null;
        let incomingInFlight = false;

        const themePresets = [
            {
                id: 'blue',
                label: 'Default',
                preview: 'linear-gradient(180deg, rgba(239, 246, 255, 0.96), rgba(219, 234, 254, 0.80)), linear-gradient(135deg, #2563eb, #3b82f6)',
                vars: {
                    '--belova-chat-accent-start': '#2563eb',
                    '--belova-chat-accent-end': '#3b82f6',
                    '--belova-chat-accent-deep': '#1d4ed8',
                    '--belova-chat-accent-soft': 'rgba(37, 99, 235, 0.12)',
                    '--belova-chat-accent-shadow': 'rgba(37, 99, 235, 0.28)',
                    '--belova-chat-accent-shadow-hover': 'rgba(37, 99, 235, 0.32)',
                    '--belova-chat-header-bg': 'linear-gradient(90deg, rgba(37, 99, 235, 0.12), rgba(96, 165, 250, 0.08))',
                    '--belova-chat-messages-bg': 'linear-gradient(180deg, rgba(239, 246, 255, 0.96), rgba(219, 234, 254, 0.80))'
                }
            },
            {
                id: 'heart',
                label: 'Lovely',
                preview: 'linear-gradient(180deg, rgba(255, 241, 242, 0.98), rgba(255, 228, 230, 0.86)), url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2780%27 height=%2780%27 viewBox=%270 0 80 80%27%3E%3Cpath fill=%27rgba(244,63,94,0.18)%27 d=%27M24 17c4.9 0 8.4 3.5 8.4 8.2 0-4.7 3.5-8.2 8.4-8.2 4.8 0 8.6 3.8 8.6 8.7 0 10.9-17 20.6-17 20.6S15.4 36.6 15.4 25.7c0-4.9 3.8-8.7 8.6-8.7Z%27/%3E%3Cpath fill=%27rgba(251,113,133,0.15)%27 d=%27M54 43c3.4 0 5.8 2.4 5.8 5.6 0-3.2 2.4-5.6 5.8-5.6 3.3 0 5.9 2.6 5.9 5.9 0 7.4-11.7 14-11.7 14S48.1 56.3 48.1 48.9c0-3.3 2.6-5.9 5.9-5.9Z%27/%3E%3C/svg%3E") center/120px 120px repeat',
                vars: {
                    '--belova-chat-accent-start': '#e11d48',
                    '--belova-chat-accent-end': '#fb7185',
                    '--belova-chat-accent-deep': '#be123c',
                    '--belova-chat-accent-soft': 'rgba(225, 29, 72, 0.12)',
                    '--belova-chat-accent-shadow': 'rgba(225, 29, 72, 0.28)',
                    '--belova-chat-accent-shadow-hover': 'rgba(225, 29, 72, 0.34)',
                    '--belova-chat-header-bg': 'linear-gradient(90deg, rgba(225, 29, 72, 0.12), rgba(251, 113, 133, 0.10))',
                    '--belova-chat-messages-bg': 'linear-gradient(180deg, rgba(255, 241, 242, 0.98), rgba(255, 228, 230, 0.86)), url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2780%27 height=%2780%27 viewBox=%270 0 80 80%27%3E%3Cpath fill=%27rgba(244,63,94,0.18)%27 d=%27M24 17c4.9 0 8.4 3.5 8.4 8.2 0-4.7 3.5-8.2 8.4-8.2 4.8 0 8.6 3.8 8.6 8.7 0 10.9-17 20.6-17 20.6S15.4 36.6 15.4 25.7c0-4.9 3.8-8.7 8.6-8.7Z%27/%3E%3Cpath fill=%27rgba(251,113,133,0.15)%27 d=%27M54 43c3.4 0 5.8 2.4 5.8 5.6 0-3.2 2.4-5.6 5.8-5.6 3.3 0 5.9 2.6 5.9 5.9 0 7.4-11.7 14-11.7 14S48.1 56.3 48.1 48.9c0-3.3 2.6-5.9 5.9-5.9Z%27/%3E%3C/svg%3E") center/120px 120px repeat'
                }
            },
            {
                id: 'cat',
                label: 'Cuttie Cat',
                preview: 'linear-gradient(180deg, rgba(255, 251, 235, 0.98), rgba(254, 243, 199, 0.86)), url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2790%27 height=%2790%27 viewBox=%270 0 90 90%27%3E%3Cg fill=%27none%27 fill-rule=%27evenodd%27%3E%3Cpath fill=%27rgba(245,158,11,0.20)%27 d=%27M20 22l6-10 8 8 8-8 6 10v16c0 9.9-8.1 18-18 18s-18-8.1-18-18V22Z%27/%3E%3Ccircle cx=%2730%27 cy=%2735%27 r=%272.4%27 fill=%27rgba(120,53,15,0.55)%27/%3E%3Ccircle cx=%2738%27 cy=%2735%27 r=%272.4%27 fill=%27rgba(120,53,15,0.55)%27/%3E%3Cpath stroke=%27rgba(120,53,15,0.45)%27 stroke-linecap=%27round%27 stroke-width=%272%27 d=%27M30 42c2.6 2.2 5.4 2.2 8 0M18 36h8M18 42h9M50 36h-8M51 42h-9%27/%3E%3Ccircle cx=%2765%27 cy=%2764%27 r=%276%27 fill=%27rgba(245,158,11,0.16)%27/%3E%3Ccircle cx=%2758%27 cy=%2756%27 r=%276%27 fill=%27rgba(245,158,11,0.16)%27/%3E%3Ccircle cx=%2772%27 cy=%2756%27 r=%276%27 fill=%27rgba(245,158,11,0.16)%27/%3E%3Ccircle cx=%2760%27 cy=%2772%27 r=%276%27 fill=%27rgba(245,158,11,0.16)%27/%3E%3Ccircle cx=%2770%27 cy=%2772%27 r=%276%27 fill=%27rgba(245,158,11,0.16)%27/%3E%3C/g%3E%3C/svg%3E") center/128px 128px repeat',
                vars: {
                    '--belova-chat-accent-start': '#d97706',
                    '--belova-chat-accent-end': '#f59e0b',
                    '--belova-chat-accent-deep': '#b45309',
                    '--belova-chat-accent-soft': 'rgba(217, 119, 6, 0.12)',
                    '--belova-chat-accent-shadow': 'rgba(217, 119, 6, 0.28)',
                    '--belova-chat-accent-shadow-hover': 'rgba(217, 119, 6, 0.34)',
                    '--belova-chat-header-bg': 'linear-gradient(90deg, rgba(217, 119, 6, 0.12), rgba(251, 191, 36, 0.10))',
                    '--belova-chat-messages-bg': 'linear-gradient(180deg, rgba(255, 251, 235, 0.98), rgba(254, 243, 199, 0.86)), url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2790%27 height=%2790%27 viewBox=%270 0 90 90%27%3E%3Cg fill=%27none%27 fill-rule=%27evenodd%27%3E%3Cpath fill=%27rgba(245,158,11,0.20)%27 d=%27M20 22l6-10 8 8 8-8 6 10v16c0 9.9-8.1 18-18 18s-18-8.1-18-18V22Z%27/%3E%3Ccircle cx=%2730%27 cy=%2735%27 r=%272.4%27 fill=%27rgba(120,53,15,0.55)%27/%3E%3Ccircle cx=%2738%27 cy=%2735%27 r=%272.4%27 fill=%27rgba(120,53,15,0.55)%27/%3E%3Cpath stroke=%27rgba(120,53,15,0.45)%27 stroke-linecap=%27round%27 stroke-width=%272%27 d=%27M30 42c2.6 2.2 5.4 2.2 8 0M18 36h8M18 42h9M50 36h-8M51 42h-9%27/%3E%3Ccircle cx=%2765%27 cy=%2764%27 r=%276%27 fill=%27rgba(245,158,11,0.16)%27/%3E%3Ccircle cx=%2758%27 cy=%2756%27 r=%276%27 fill=%27rgba(245,158,11,0.16)%27/%3E%3Ccircle cx=%2772%27 cy=%2756%27 r=%276%27 fill=%27rgba(245,158,11,0.16)%27/%3E%3Ccircle cx=%2760%27 cy=%2772%27 r=%276%27 fill=%27rgba(245,158,11,0.16)%27/%3E%3Ccircle cx=%2770%27 cy=%2772%27 r=%276%27 fill=%27rgba(245,158,11,0.16)%27/%3E%3C/g%3E%3C/svg%3E") center/128px 128px repeat'
                }
            },
            {
                id: 'flower',
                label: 'Flower Bloom',
                preview: 'linear-gradient(180deg, rgba(240, 253, 244, 0.98), rgba(220, 252, 231, 0.86)), url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2790%27 height=%2790%27 viewBox=%270 0 90 90%27%3E%3Cg fill=%27none%27 fill-rule=%27evenodd%27%3E%3Ccircle cx=%2730%27 cy=%2730%27 r=%276%27 fill=%27rgba(251,191,36,0.20)%27/%3E%3Ccircle cx=%2730%27 cy=%2718%27 r=%278%27 fill=%27rgba(74,222,128,0.20)%27/%3E%3Ccircle cx=%2730%27 cy=%2742%27 r=%278%27 fill=%27rgba(74,222,128,0.20)%27/%3E%3Ccircle cx=%2718%27 cy=%2730%27 r=%278%27 fill=%27rgba(74,222,128,0.20)%27/%3E%3Ccircle cx=%2742%27 cy=%2730%27 r=%278%27 fill=%27rgba(74,222,128,0.20)%27/%3E%3Cpath fill=%27rgba(34,197,94,0.18)%27 d=%27M61 53c7.7 0 14 6.3 14 14s-6.3 14-14 14-14-6.3-14-14 6.3-14 14-14Z%27/%3E%3Cpath stroke=%27rgba(255,255,255,0.94)%27 stroke-linecap=%27round%27 stroke-width=%273.2%27 d=%27M61 60v14M54 67h14%27/%3E%3C/g%3E%3C/svg%3E") center/128px 128px repeat',
                vars: {
                    '--belova-chat-accent-start': '#16a34a',
                    '--belova-chat-accent-end': '#4ade80',
                    '--belova-chat-accent-deep': '#15803d',
                    '--belova-chat-accent-soft': 'rgba(22, 163, 74, 0.12)',
                    '--belova-chat-accent-shadow': 'rgba(22, 163, 74, 0.28)',
                    '--belova-chat-accent-shadow-hover': 'rgba(22, 163, 74, 0.34)',
                    '--belova-chat-header-bg': 'linear-gradient(90deg, rgba(22, 163, 74, 0.12), rgba(134, 239, 172, 0.10))',
                    '--belova-chat-messages-bg': 'linear-gradient(180deg, rgba(240, 253, 244, 0.98), rgba(220, 252, 231, 0.86)), url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2790%27 height=%2790%27 viewBox=%270 0 90 90%27%3E%3Cg fill=%27none%27 fill-rule=%27evenodd%27%3E%3Ccircle cx=%2730%27 cy=%2730%27 r=%276%27 fill=%27rgba(251,191,36,0.20)%27/%3E%3Ccircle cx=%2730%27 cy=%2718%27 r=%278%27 fill=%27rgba(74,222,128,0.20)%27/%3E%3Ccircle cx=%2730%27 cy=%2742%27 r=%278%27 fill=%27rgba(74,222,128,0.20)%27/%3E%3Ccircle cx=%2718%27 cy=%2730%27 r=%278%27 fill=%27rgba(74,222,128,0.20)%27/%3E%3Ccircle cx=%2742%27 cy=%2730%27 r=%278%27 fill=%27rgba(74,222,128,0.20)%27/%3E%3Cpath fill=%27rgba(34,197,94,0.18)%27 d=%27M61 53c7.7 0 14 6.3 14 14s-6.3 14-14 14-14-6.3-14-14 6.3-14 14-14Z%27/%3E%3Cpath stroke=%27rgba(255,255,255,0.94)%27 stroke-linecap=%27round%27 stroke-width=%273.2%27 d=%27M61 60v14M54 67h14%27/%3E%3C/g%3E%3C/svg%3E") center/128px 128px repeat'
                }
            },
            {
                id: 'coffee',
                label: 'Coffee Time',
                preview: 'linear-gradient(180deg, rgba(250, 245, 239, 0.98), rgba(237, 224, 212, 0.86)), url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2796%27 height=%2796%27 viewBox=%270 0 96 96%27%3E%3Cg fill=%27none%27 fill-rule=%27evenodd%27%3E%3Cpath fill=%27rgba(120,53,15,0.18)%27 d=%27M19 31h25v13c0 7-5.7 12.7-12.7 12.7S19 51 19 44V31Z%27/%3E%3Cpath stroke=%27rgba(120,53,15,0.18)%27 stroke-width=%274%27 d=%27M44 35h6.5a8 8 0 0 1 0 16H44%27/%3E%3Cpath stroke=%27rgba(255,255,255,0.92)%27 stroke-linecap=%27round%27 stroke-width=%273.2%27 d=%27M28 17c0 6.6-4.6 6.6-4.6 13.2M37 14c0 6.6-4.6 6.6-4.6 13.2%27/%3E%3Cellipse cx=%2767%27 cy=%2765%27 rx=%279%27 ry=%276%27 fill=%27rgba(120,53,15,0.14)%27/%3E%3Cpath fill=%27rgba(120,53,15,0.10)%27 d=%27M66 56c6 0 11 5 11 11 0 6-5 11-11 11S55 73 55 67s5-11 11-11Zm0 3.5c-4 0-7.2 3.1-7.2 7.5s3.2 7.5 7.2 7.5 7.2-3.1 7.2-7.5-3.2-7.5-7.2-7.5Z%27/%3E%3C/g%3E%3C/svg%3E") center/132px 132px repeat',
                vars: {
                    '--belova-chat-accent-start': '#92400e',
                    '--belova-chat-accent-end': '#c08457',
                    '--belova-chat-accent-deep': '#78350f',
                    '--belova-chat-accent-soft': 'rgba(146, 64, 14, 0.12)',
                    '--belova-chat-accent-shadow': 'rgba(146, 64, 14, 0.28)',
                    '--belova-chat-accent-shadow-hover': 'rgba(146, 64, 14, 0.34)',
                    '--belova-chat-header-bg': 'linear-gradient(90deg, rgba(146, 64, 14, 0.12), rgba(194, 132, 87, 0.10))',
                    '--belova-chat-messages-bg': 'linear-gradient(180deg, rgba(250, 245, 239, 0.98), rgba(237, 224, 212, 0.86)), url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2796%27 height=%2796%27 viewBox=%270 0 96 96%27%3E%3Cg fill=%27none%27 fill-rule=%27evenodd%27%3E%3Cpath fill=%27rgba(120,53,15,0.18)%27 d=%27M19 31h25v13c0 7-5.7 12.7-12.7 12.7S19 51 19 44V31Z%27/%3E%3Cpath stroke=%27rgba(120,53,15,0.18)%27 stroke-width=%274%27 d=%27M44 35h6.5a8 8 0 0 1 0 16H44%27/%3E%3Cpath stroke=%27rgba(255,255,255,0.92)%27 stroke-linecap=%27round%27 stroke-width=%273.2%27 d=%27M28 17c0 6.6-4.6 6.6-4.6 13.2M37 14c0 6.6-4.6 6.6-4.6 13.2%27/%3E%3Cellipse cx=%2767%27 cy=%2765%27 rx=%279%27 ry=%276%27 fill=%27rgba(120,53,15,0.14)%27/%3E%3Cpath fill=%27rgba(120,53,15,0.10)%27 d=%27M66 56c6 0 11 5 11 11 0 6-5 11-11 11S55 73 55 67s5-11 11-11Zm0 3.5c-4 0-7.2 3.1-7.2 7.5s3.2 7.5 7.2 7.5 7.2-3.1 7.2-7.5-3.2-7.5-7.2-7.5Z%27/%3E%3C/g%3E%3C/svg%3E") center/132px 132px repeat'
                }
            },
            {
                id: 'starry-night',
                label: 'Starry Night',
                preview: 'linear-gradient(180deg, rgba(245, 243, 255, 0.98), rgba(233, 213, 255, 0.86)), url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2794%27 height=%2794%27 viewBox=%270 0 94 94%27%3E%3Cg fill=%27none%27 fill-rule=%27evenodd%27%3E%3Cpath fill=%27rgba(167,139,250,0.18)%27 d=%27M24 16l4.1 9 9.9 1.2-7.3 6.7 1.9 9.8L24 37.8l-8.6 4.9 1.9-9.8L10 26.2l9.9-1.2z%27/%3E%3Ccircle cx=%2765%27 cy=%2727%27 r=%2711%27 fill=%27rgba(196,181,253,0.20)%27/%3E%3Ccircle cx=%2769%27 cy=%2724%27 r=%2711%27 fill=%27rgba(245,243,255,0.96)%27/%3E%3Ccircle cx=%2757%27 cy=%2757%27 r=%272.5%27 fill=%27rgba(255,255,255,0.92)%27/%3E%3Ccircle cx=%2773%27 cy=%2764%27 r=%273%27 fill=%27rgba(255,255,255,0.88)%27/%3E%3Ccircle cx=%2763%27 cy=%2776%27 r=%272.2%27 fill=%27rgba(255,255,255,0.86)%27/%3E%3C/g%3E%3C/svg%3E") center/132px 132px repeat',
                vars: {
                    '--belova-chat-accent-start': '#8b5cf6',
                    '--belova-chat-accent-end': '#a78bfa',
                    '--belova-chat-accent-deep': '#7c3aed',
                    '--belova-chat-accent-soft': 'rgba(139, 92, 246, 0.12)',
                    '--belova-chat-accent-shadow': 'rgba(139, 92, 246, 0.28)',
                    '--belova-chat-accent-shadow-hover': 'rgba(139, 92, 246, 0.34)',
                    '--belova-chat-header-bg': 'linear-gradient(90deg, rgba(139, 92, 246, 0.12), rgba(216, 180, 254, 0.10))',
                    '--belova-chat-messages-bg': 'linear-gradient(180deg, rgba(245, 243, 255, 0.98), rgba(233, 213, 255, 0.86)), url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2794%27 height=%2794%27 viewBox=%270 0 94 94%27%3E%3Cg fill=%27none%27 fill-rule=%27evenodd%27%3E%3Cpath fill=%27rgba(167,139,250,0.18)%27 d=%27M24 16l4.1 9 9.9 1.2-7.3 6.7 1.9 9.8L24 37.8l-8.6 4.9 1.9-9.8L10 26.2l9.9-1.2z%27/%3E%3Ccircle cx=%2765%27 cy=%2727%27 r=%2711%27 fill=%27rgba(196,181,253,0.20)%27/%3E%3Ccircle cx=%2769%27 cy=%2724%27 r=%2711%27 fill=%27rgba(245,243,255,0.96)%27/%3E%3Ccircle cx=%2757%27 cy=%2757%27 r=%272.5%27 fill=%27rgba(255,255,255,0.92)%27/%3E%3Ccircle cx=%2773%27 cy=%2764%27 r=%273%27 fill=%27rgba(255,255,255,0.88)%27/%3E%3Ccircle cx=%2763%27 cy=%2776%27 r=%272.2%27 fill=%27rgba(255,255,255,0.86)%27/%3E%3C/g%3E%3C/svg%3E") center/132px 132px repeat'
                }
            }
        ];

        const themeBackgrounds = {
            blue: {
                base: 'linear-gradient(180deg, rgba(239, 246, 255, 0.96), rgba(219, 234, 254, 0.80))',
                pattern: 'radial-gradient(circle at 18px 18px, rgba(59, 130, 246, 0.18) 0 2px, transparent 2.5px)',
                size: '28px 28px',
                opacity: '0.28'
            },
            heart: {
                base: 'linear-gradient(180deg, rgba(255, 241, 242, 0.98), rgba(255, 228, 230, 0.86))',
                pattern: 'url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2780%27 height=%2780%27 viewBox=%270 0 80 80%27%3E%3Cpath fill=%27rgba(244,63,94,0.30)%27 d=%27M24 17c4.9 0 8.4 3.5 8.4 8.2 0-4.7 3.5-8.2 8.4-8.2 4.8 0 8.6 3.8 8.6 8.7 0 10.9-17 20.6-17 20.6S15.4 36.6 15.4 25.7c0-4.9 3.8-8.7 8.6-8.7Z%27/%3E%3Cpath fill=%27rgba(251,113,133,0.24)%27 d=%27M54 43c3.4 0 5.8 2.4 5.8 5.6 0-3.2 2.4-5.6 5.8-5.6 3.3 0 5.9 2.6 5.9 5.9 0 7.4-11.7 14-11.7 14S48.1 56.3 48.1 48.9c0-3.3 2.6-5.9 5.9-5.9Z%27/%3E%3C/svg%3E")',
                size: '120px 120px',
                opacity: '0.36'
            },
            cat: {
                base: 'linear-gradient(180deg, rgba(255, 251, 235, 0.98), rgba(254, 243, 199, 0.86))',
                pattern: 'url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2790%27 height=%2790%27 viewBox=%270 0 90 90%27%3E%3Cg fill=%27none%27 fill-rule=%27evenodd%27%3E%3Cpath fill=%27rgba(245,158,11,0.28)%27 d=%27M20 22l6-10 8 8 8-8 6 10v16c0 9.9-8.1 18-18 18s-18-8.1-18-18V22Z%27/%3E%3Ccircle cx=%2730%27 cy=%2735%27 r=%272.8%27 fill=%27rgba(120,53,15,0.72)%27/%3E%3Ccircle cx=%2738%27 cy=%2735%27 r=%272.8%27 fill=%27rgba(120,53,15,0.72)%27/%3E%3Cpath stroke=%27rgba(120,53,15,0.56)%27 stroke-linecap=%27round%27 stroke-width=%272.4%27 d=%27M30 42c2.6 2.2 5.4 2.2 8 0M18 36h8M18 42h9M50 36h-8M51 42h-9%27/%3E%3Ccircle cx=%2765%27 cy=%2764%27 r=%276.5%27 fill=%27rgba(245,158,11,0.22)%27/%3E%3Ccircle cx=%2758%27 cy=%2756%27 r=%276.5%27 fill=%27rgba(245,158,11,0.22)%27/%3E%3Ccircle cx=%2772%27 cy=%2756%27 r=%276.5%27 fill=%27rgba(245,158,11,0.22)%27/%3E%3Ccircle cx=%2760%27 cy=%2772%27 r=%276.5%27 fill=%27rgba(245,158,11,0.22)%27/%3E%3Ccircle cx=%2770%27 cy=%2772%27 r=%276.5%27 fill=%27rgba(245,158,11,0.22)%27/%3E%3C/g%3E%3C/svg%3E")',
                size: '128px 128px',
                opacity: '0.32'
            },
            flower: {
                base: 'linear-gradient(180deg, rgba(240, 253, 244, 0.98), rgba(220, 252, 231, 0.86))',
                pattern: 'url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2790%27 height=%2790%27 viewBox=%270 0 90 90%27%3E%3Cg fill=%27none%27 fill-rule=%27evenodd%27%3E%3Ccircle cx=%2730%27 cy=%2730%27 r=%276.5%27 fill=%27rgba(251,191,36,0.26)%27/%3E%3Ccircle cx=%2730%27 cy=%2718%27 r=%279%27 fill=%27rgba(74,222,128,0.28)%27/%3E%3Ccircle cx=%2730%27 cy=%2742%27 r=%279%27 fill=%27rgba(74,222,128,0.28)%27/%3E%3Ccircle cx=%2718%27 cy=%2730%27 r=%279%27 fill=%27rgba(74,222,128,0.28)%27/%3E%3Ccircle cx=%2742%27 cy=%2730%27 r=%279%27 fill=%27rgba(74,222,128,0.28)%27/%3E%3Cpath fill=%27rgba(34,197,94,0.24)%27 d=%27M61 53c7.7 0 14 6.3 14 14s-6.3 14-14 14-14-6.3-14-14 6.3-14 14-14Z%27/%3E%3Cpath stroke=%27rgba(255,255,255,0.96)%27 stroke-linecap=%27round%27 stroke-width=%273.4%27 d=%27M61 60v14M54 67h14%27/%3E%3C/g%3E%3C/svg%3E")',
                size: '128px 128px',
                opacity: '0.32'
            },
            coffee: {
                base: 'linear-gradient(180deg, rgba(250, 245, 239, 0.98), rgba(237, 224, 212, 0.86))',
                pattern: 'url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2796%27 height=%2796%27 viewBox=%270 0 96 96%27%3E%3Cg fill=%27none%27 fill-rule=%27evenodd%27%3E%3Cpath fill=%27rgba(120,53,15,0.26)%27 d=%27M19 31h25v13c0 7-5.7 12.7-12.7 12.7S19 51 19 44V31Z%27/%3E%3Cpath stroke=%27rgba(120,53,15,0.26)%27 stroke-width=%274.4%27 d=%27M44 35h6.5a8 8 0 0 1 0 16H44%27/%3E%3Cpath stroke=%27rgba(255,255,255,0.96)%27 stroke-linecap=%27round%27 stroke-width=%273.4%27 d=%27M28 17c0 6.6-4.6 6.6-4.6 13.2M37 14c0 6.6-4.6 6.6-4.6 13.2%27/%3E%3Cellipse cx=%2767%27 cy=%2765%27 rx=%279.5%27 ry=%276.5%27 fill=%27rgba(120,53,15,0.18)%27/%3E%3Cpath fill=%27rgba(120,53,15,0.14)%27 d=%27M66 56c6 0 11 5 11 11 0 6-5 11-11 11S55 73 55 67s5-11 11-11Zm0 3.5c-4 0-7.2 3.1-7.2 7.5s3.2 7.5 7.2 7.5 7.2-3.1 7.2-7.5-3.2-7.5-7.2-7.5Z%27/%3E%3C/g%3E%3C/svg%3E")',
                size: '132px 132px',
                opacity: '0.30'
            },
            'starry-night': {
                base: 'linear-gradient(180deg, rgba(245, 243, 255, 0.98), rgba(233, 213, 255, 0.86))',
                pattern: 'url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2794%27 height=%2794%27 viewBox=%270 0 94 94%27%3E%3Cg fill=%27none%27 fill-rule=%27evenodd%27%3E%3Cpath fill=%27rgba(167,139,250,0.28)%27 d=%27M24 16l4.1 9 9.9 1.2-7.3 6.7 1.9 9.8L24 37.8l-8.6 4.9 1.9-9.8L10 26.2l9.9-1.2z%27/%3E%3Ccircle cx=%2765%27 cy=%2727%27 r=%2711%27 fill=%27rgba(196,181,253,0.26)%27/%3E%3Ccircle cx=%2769%27 cy=%2724%27 r=%2711%27 fill=%27rgba(245,243,255,0.96)%27/%3E%3Ccircle cx=%2757%27 cy=%2757%27 r=%273%27 fill=%27rgba(255,255,255,0.96)%27/%3E%3Ccircle cx=%2773%27 cy=%2764%27 r=%273.5%27 fill=%27rgba(255,255,255,0.92)%27/%3E%3Ccircle cx=%2763%27 cy=%2776%27 r=%272.7%27 fill=%27rgba(255,255,255,0.90)%27/%3E%3C/g%3E%3C/svg%3E")',
                size: '132px 132px',
                opacity: '0.34'
            }
        };

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function initialFor(name) {
            return String(name || '')
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 2)
                .map(function (part) { return part.charAt(0).toUpperCase(); })
                .join('') || 'U';
        }

        function renderAvatar(user, className) {
            const initials = escapeHtml(user.avatar_initials || initialFor(user.name));
            return '<span class="' + className + '">' + initials + '</span>';
        }

        function applyThemePreset(themeId) {
            const preset = themePresets.find(function (item) { return item.id === themeId; }) || themePresets[0];
            const background = themeBackgrounds[preset.id] || themeBackgrounds.blue;

            Object.keys(preset.vars).forEach(function (key) {
                if (key !== '--belova-chat-messages-bg') {
                    widget.style.setProperty(key, preset.vars[key]);
                }
            });

            widget.style.setProperty('--belova-chat-messages-base', background.base);
            widget.style.setProperty('--belova-chat-messages-pattern', background.pattern);
            widget.style.setProperty('--belova-chat-messages-pattern-size', background.size);
            widget.style.setProperty('--belova-chat-messages-pattern-opacity', background.opacity);

            window.localStorage.setItem(storageKeys.theme, preset.id);

            if (themeOptions) {
                Array.prototype.forEach.call(themeOptions.querySelectorAll('[data-theme-id]'), function (button) {
                    button.classList.toggle('is-active', button.getAttribute('data-theme-id') === preset.id);
                });
            }
        }

        function renderCustomizer() {
            if (themeOptions) {
                themeOptions.innerHTML = themePresets.map(function (preset) {
                    return ''
                        + '<button type="button" class="belova-chat-theme-card" data-theme-id="' + preset.id + '" title="' + preset.label + '">'
                        + '  <span class="belova-chat-theme-card-preview" style="background:' + preset.preview + ';"></span>'
                        + '  <span class="belova-chat-theme-card-label">' + preset.label + '</span>'
                        + '</button>';
                }).join('');
            }

            applyThemePreset(window.localStorage.getItem(storageKeys.theme) || 'blue');
        }

        function formatTime(value) {
            if (!value) {
                return '';
            }

            if (window.moment) {
                return window.moment(value).calendar(null, {
                    sameDay: 'HH:mm',
                    lastDay: '[Kemarin]',
                    lastWeek: 'DD MMM',
                    sameElse: 'DD MMM',
                });
            }

            return new Date(value).toLocaleString();
        }

        function conversationUrl(userId) {
            return endpoints.conversationTemplate.replace('__USER__', String(userId));
        }

        function setLauncherBadge(totalUnread) {
            const count = Number(totalUnread || 0);
            launcherBadge.textContent = count > 99 ? '99+' : String(count);
            launcherBadge.classList.toggle('d-none', count < 1);
        }

        function setComposerEnabled(enabled) {
            input.disabled = !enabled;
            sendButton.disabled = !enabled;
        }

        function renderUsers(payload) {
            const items = payload && Array.isArray(payload.data) ? payload.data : [];
            state.users = items;
            setLauncherBadge(payload && payload.meta ? payload.meta.total_unread : 0);

            if (!items.length) {
                userList.innerHTML = '<div class="belova-chat-placeholder">User tidak ditemukan.</div>';
                return;
            }

            userList.innerHTML = items.map(function (user) {
                const preview = user.last_message ? escapeHtml(user.last_message.replace(/\s+/g, ' ').trim()) : 'Belum ada pesan';
                const unread = Number(user.unread_count || 0);
                const activeClass = Number(state.activeUserId) === Number(user.id) ? ' is-active' : '';
                const positionLabel = user.position_label ? escapeHtml(user.position_label) : '';

                return ''
                    + '<button type="button" class="belova-chat-user-item' + activeClass + '" data-user-id="' + user.id + '">'
                    + '  ' + renderAvatar(user, 'belova-chat-user-avatar')
                    + '  <span class="belova-chat-user-main">'
                    + '      <span class="belova-chat-user-meta">'
                    + '          <span class="belova-chat-user-name">' + escapeHtml(user.name) + '</span>'
                    + (unread > 0 ? '<span class="belova-chat-user-unread">' + unread + '</span>' : '')
                    + '      </span>'
                        + (positionLabel ? '<div class="belova-chat-user-role">' + positionLabel + '</div>' : '')
                    + '      <div class="belova-chat-user-preview">' + preview + '</div>'
                    + '  </span>'
                    + '</button>';
            }).join('');
        }

        function renderConversationHeader(user) {
            if (!user) {
                header.innerHTML = ''
                    + '<div class="belova-chat-conversation-meta">'
                    + '  <div class="belova-chat-conversation-name">Pilih user</div>'
                    + '  <div class="belova-chat-conversation-role">Pilih user di panel kiri untuk mulai chat.</div>'
                    + '</div>';
                return;
            }

            const positionLabel = user.position_label ? escapeHtml(user.position_label) : '';
            header.innerHTML = ''
                    + renderAvatar(user, 'belova-chat-conversation-avatar')
                    + '<div class="belova-chat-conversation-meta">'
                    + '  <div class="belova-chat-conversation-name">' + escapeHtml(user.name) + '</div>'
                + (positionLabel ? '  <div class="belova-chat-conversation-role">' + positionLabel + '</div>' : '')
                    + '</div>';
        }

        function renderMessages(items, forceScroll) {
            if (!Array.isArray(items) || !items.length) {
                state.lastRenderedMessageId = null;
                messages.innerHTML = ''
                    + '<div class="belova-chat-empty-state">'
                    + '  <div class="belova-chat-empty-title">Belum ada pesan</div>'
                    + '  <div class="belova-chat-empty-text">Mulai percakapan dengan mengirim pesan pertama.</div>'
                    + '</div>';
                return;
            }

            const latestId = items[items.length - 1].id;
            const previousId = state.lastRenderedMessageId;
            const shouldScroll = forceScroll || previousId !== latestId;
            state.lastRenderedMessageId = latestId;

            // The open conversation marks messages read on fetch, so the incoming poll never sees them: announce here.
            // Only on the background refresh (not when the conversation is first opened).
            if (!forceScroll && previousId) {
                items.forEach(function (message) {
                    if (!message.is_mine && Number(message.id) > Number(previousId)) {
                        announceIncoming(message.id);
                    }
                });
            }

            messages.innerHTML = items.map(function (message) {
                const rowClass = message.is_mine ? 'belova-chat-message-row is-mine' : 'belova-chat-message-row';
                return ''
                    + '<div class="' + rowClass + '">'
                    + '  <div class="belova-chat-message-bubble">'
                    + '      <div class="belova-chat-message-text">' + escapeHtml(message.body) + '</div>'
                    + '      <div class="belova-chat-message-time">' + escapeHtml(formatTime(message.created_at)) + '</div>'
                    + '  </div>'
                    + '</div>';
            }).join('');

            if (shouldScroll) {
                messages.scrollTop = messages.scrollHeight;
            }
        }

        function fetchUsers() {
            if (!state.open) {
                return;
            }

            $.get(endpoints.users, { q: state.search })
                .done(function (response) {
                    state.usersLoaded = true;
                    renderUsers(response);
                });
        }

        function fetchConversation(forceScroll) {
            if (!state.activeUserId) {
                return;
            }

            $.get(conversationUrl(state.activeUserId))
                .done(function (response) {
                    renderConversationHeader(response.user || null);
                    renderMessages(response.messages || [], forceScroll);
                    setComposerEnabled(true);
                    // Refresh unread counts only when a conversation is opened; the 10s timer covers the rest
                    if (forceScroll) {
                        fetchUsers();
                    }
                });
        }

        function setSettingsPanelOpen(open) {
            if (!settingsPanel || !settingsToggle) {
                return;
            }

            settingsPanel.classList.toggle('d-none', !open);
            settingsToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function readStore(key, fallback) {
            try {
                const raw = window.localStorage.getItem(key);
                return raw ? JSON.parse(raw) : fallback;
            } catch (e) {
                return fallback;
            }
        }

        function writeStore(key, value) {
            try {
                window.localStorage.setItem(key, JSON.stringify(value));
            } catch (e) {
                // Storage unavailable: popups still work for this page
            }
        }

        const incomingSound = new Audio(@json(asset('sounds/notif-chat.mp3')));
        incomingSound.preload = 'auto';

        function playIncomingSound() {
            try {
                incomingSound.currentTime = 0;
                const playing = incomingSound.play();
                if (playing && playing.catch) {
                    // Autoplay blocked before any user interaction: the popup itself is enough
                    playing.catch(function () {});
                }
            } catch (e) {
                // Audio unsupported: ignore
            }
        }

        function popupMessageHtml(message, mine) {
            return ''
                + '<div class="belova-chat-popup-msg' + (mine ? ' is-mine' : '') + '">'
                + escapeHtml(message.body)
                + '<span class="belova-chat-popup-msg-time">' + escapeHtml(formatTime(message.created_at)) + '</span>'
                + '</div>';
        }

        function removePopup(senderId) {
            const popup = popups[senderId];
            if (!popup) {
                return;
            }
            clearTimeout(popup.closeTimer);
            popup.el.remove();
            delete popups[senderId];
        }

        function dismissPopup(senderId) {
            const popup = popups[senderId];
            if (popup) {
                const dismissed = readStore(storageKeys.popupDismissed, {});
                dismissed[senderId] = Math.max(Number(dismissed[senderId] || 0), popup.maxId);
                writeStore(storageKeys.popupDismissed, dismissed);
            }
            removePopup(senderId);
        }

        function createPopup(user) {
            const el = document.createElement('div');
            el.className = 'belova-chat-popup';
            el.setAttribute('data-popup-user', user.id);
            const positionLabel = user.position_label ? escapeHtml(user.position_label) : 'Pesan baru';
            el.innerHTML = ''
                + '<div class="belova-chat-popup-head">'
                + '  ' + renderAvatar(user, 'belova-chat-user-avatar')
                + '  <div class="belova-chat-popup-who">'
                + '      <div class="belova-chat-popup-name">' + escapeHtml(user.name) + '</div>'
                + '      <div class="belova-chat-popup-sub">' + positionLabel + '</div>'
                + '  </div>'
                + '  <button type="button" class="belova-chat-popup-btn" data-popup-action="open" title="Buka di Chat Internal" aria-label="Buka di Chat Internal">'
                + '      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 4h6v6M20 4l-8 8M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"></path></svg>'
                + '  </button>'
                + '  <button type="button" class="belova-chat-popup-btn" data-popup-action="dismiss" title="Tutup" aria-label="Tutup">'
                + '      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"></path></svg>'
                + '  </button>'
                + '</div>'
                + '<div class="belova-chat-popup-messages"></div>'
                + '<div class="belova-chat-popup-composer">'
                + '  <textarea class="belova-chat-popup-input" rows="1" placeholder="Balas pesan..."></textarea>'
                + '  <button type="button" class="belova-chat-popup-send">Kirim</button>'
                + '</div>'
                + '<div class="belova-chat-popup-status d-none"></div>';

            const popup = {
                el: el,
                list: el.querySelector('.belova-chat-popup-messages'),
                input: el.querySelector('.belova-chat-popup-input'),
                send: el.querySelector('.belova-chat-popup-send'),
                status: el.querySelector('.belova-chat-popup-status'),
                maxId: 0,
                closeTimer: null,
            };

            el.addEventListener('click', function (event) {
                const action = event.target.closest('[data-popup-action]');
                if (!action) {
                    return;
                }
                if (action.getAttribute('data-popup-action') === 'dismiss') {
                    dismissPopup(user.id);
                } else {
                    removePopup(user.id);
                    openPanel();
                    selectUser(user.id);
                }
            });

            popup.send.addEventListener('click', function () {
                sendPopupReply(user.id);
            });

            popup.input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    sendPopupReply(user.id);
                }
            });

            // Keep the card while the user is writing a follow-up
            popup.input.addEventListener('input', function () {
                clearTimeout(popup.closeTimer);
                popup.status.classList.add('d-none');
            });

            popups[user.id] = popup;
            // Newest conversation sits closest to the launcher
            popupContainer.appendChild(el);
            return popup;
        }

        function sendPopupReply(senderId) {
            const popup = popups[senderId];
            if (!popup) {
                return;
            }
            const body = popup.input.value.trim();
            if (!body || popup.send.disabled) {
                return;
            }

            popup.send.disabled = true;

            $.ajax({
                url: conversationUrl(senderId),
                type: 'POST',
                data: { _token: csrfToken, body: body },
            }).done(function (response) {
                popup.input.value = '';
                popup.list.insertAdjacentHTML('beforeend', popupMessageHtml(response.message || { body: body }, true));
                popup.list.scrollTop = popup.list.scrollHeight;
                popup.status.textContent = 'Terkirim ✓';
                popup.status.classList.remove('d-none');
                clearTimeout(popup.closeTimer);
                popup.closeTimer = setTimeout(function () { removePopup(senderId); }, 4000);
                pollIncoming();
            }).fail(function (xhr) {
                const message = xhr && xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Pesan gagal dikirim.';
                popup.status.textContent = message;
                popup.status.classList.remove('d-none');
            }).always(function () {
                popup.send.disabled = false;
            });
        }

        function renderIncoming(groups) {
            const dismissed = readStore(storageKeys.popupDismissed, {});
            const notifiedMax = Number(readStore(storageKeys.popupNotified, 0)) || 0;
            let newestId = notifiedMax;
            const unreadSenders = {};

            groups
                .map(function (group) {
                    const ids = group.messages.map(function (m) { return Number(m.id); });
                    group.latestId = ids.length ? Math.max.apply(null, ids) : 0;
                    return group;
                })
                .sort(function (a, b) { return a.latestId - b.latestId; })
                .forEach(function (group) {
                    const senderId = Number(group.user.id);
                    unreadSenders[senderId] = true;

                    // Already visible in the open panel: no card, but still announce it
                    if (state.open && Number(state.activeUserId) === senderId) {
                        newestId = Math.max(newestId, group.latestId);
                        return;
                    }

                    const fresh = group.messages.filter(function (m) {
                        return Number(m.id) > Number(dismissed[senderId] || 0);
                    });
                    if (!fresh.length) {
                        return;
                    }

                    const popup = popups[senderId] || createPopup(group.user);
                    fresh.forEach(function (message) {
                        if (Number(message.id) > popup.maxId) {
                            popup.list.insertAdjacentHTML('beforeend', popupMessageHtml(message, false));
                            popup.maxId = Number(message.id);
                            // A new message keeps the card open
                            clearTimeout(popup.closeTimer);
                            popup.status.classList.add('d-none');
                        }
                    });
                    popup.list.scrollTop = popup.list.scrollHeight;
                    newestId = Math.max(newestId, popup.maxId);
                });

            // Drop cards whose messages were read elsewhere (another tab, the panel), unless mid-reply or just answered
            Object.keys(popups).forEach(function (senderId) {
                const popup = popups[senderId];
                if (!unreadSenders[senderId] && !popup.input.value.trim() && popup.status.classList.contains('d-none')) {
                    removePopup(senderId);
                }
            });

            // Cap the stack: the oldest cards go first (they stay reachable through the badge)
            const ids = Object.keys(popups).sort(function (a, b) { return popups[a].maxId - popups[b].maxId; });
            while (ids.length > maxVisiblePopups) {
                removePopup(ids.shift());
            }

            announceIncoming(newestId);
        }

        // Plays the sound once per incoming message id, across pages and across the popup/panel paths
        function announceIncoming(messageId) {
            const notifiedMax = Number(readStore(storageKeys.popupNotified, 0)) || 0;
            if (Number(messageId) > notifiedMax) {
                writeStore(storageKeys.popupNotified, Number(messageId));
                playIncomingSound();
            }
        }

        function pollIncoming() {
            if (incomingInFlight) {
                return;
            }
            incomingInFlight = true;

            $.get(endpoints.incoming)
                .done(function (response) {
                    const totalUnread = response && response.meta ? Number(response.meta.total_unread || 0) : 0;
                    setLauncherBadge(totalUnread);
                    renderIncoming(response && Array.isArray(response.data) ? response.data : []);
                    // Refresh the open panel's list only when something actually changed
                    if (state.open && totalUnread !== state.lastTotalUnread) {
                        fetchUsers();
                    }
                    state.lastTotalUnread = totalUnread;
                })
                .always(function () {
                    incomingInFlight = false;
                });
        }

        function openPanel() {
            state.open = true;
            panel.classList.remove('d-none');
            panel.setAttribute('aria-hidden', 'false');
            popupContainer.classList.add('d-none');
            fetchUsers();
        }

        function closePanel() {
            state.open = false;
            setSettingsPanelOpen(false);
            panel.classList.add('d-none');
            panel.setAttribute('aria-hidden', 'true');
            popupContainer.classList.remove('d-none');
            pollIncoming();
        }

        function selectUser(userId) {
            removePopup(Number(userId));
            state.lastRenderedMessageId = null;
            state.activeUserId = Number(userId);
            renderUsers({ data: state.users, meta: { total_unread: Number(launcherBadge.textContent) || 0 } });
            fetchConversation(true);
            input.focus();
        }

        function sendMessage() {
            const body = input.value.trim();
            if (!state.activeUserId || !body) {
                return;
            }

            sendButton.disabled = true;

            $.ajax({
                url: conversationUrl(state.activeUserId),
                type: 'POST',
                data: {
                    _token: csrfToken,
                    body: body,
                },
            }).done(function () {
                input.value = '';
                fetchConversation(true);
            }).fail(function (xhr) {
                const message = xhr && xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Pesan gagal dikirim.';
                if (window.Swal) {
                    window.Swal.fire({ icon: 'error', title: 'Chat', text: message });
                }
            }).always(function () {
                sendButton.disabled = false;
                setComposerEnabled(true);
            });
        }

        launcher.addEventListener('click', function () {
            if (state.open) {
                closePanel();
                return;
            }

            openPanel();
        });

        closeButton.addEventListener('click', closePanel);

        if (settingsToggle && settingsPanel) {
            settingsToggle.setAttribute('aria-expanded', 'false');

            settingsToggle.addEventListener('click', function () {
                setSettingsPanelOpen(settingsPanel.classList.contains('d-none'));
            });
        }

        if (themeOptions) {
            themeOptions.addEventListener('click', function (event) {
                const button = event.target.closest('[data-theme-id]');
                if (!button) {
                    return;
                }

                applyThemePreset(button.getAttribute('data-theme-id'));
                setSettingsPanelOpen(false);
            });
        }

        userList.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-user-id]');
            if (!trigger) {
                return;
            }

            selectUser(trigger.getAttribute('data-user-id'));
        });

        searchInput.addEventListener('input', function () {
            state.search = this.value.trim();
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(fetchUsers, 250);
        });

        sendButton.addEventListener('click', sendMessage);

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        });

        renderCustomizer();

        state.usersTimer = window.setInterval(function () {
            if (state.open && !document.hidden) {
                fetchUsers();
            }
        }, 10000);
        state.conversationTimer = window.setInterval(function () {
            if (state.open && state.activeUserId && !document.hidden) {
                fetchConversation(false);
            }
        }, 4000);

        // Incoming popups run on every page, panel open or not
        pollIncoming();
        incomingTimer = window.setInterval(function () {
            if (!document.hidden) {
                pollIncoming();
            }
        }, 5000);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                pollIncoming();
            }
        });
    });
</script>