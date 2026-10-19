@php
    $chatLauncherBottom = max(24, (int) ($chatLauncherBottom ?? 24));
@endphp

<link rel="stylesheet" href="{{ asset('css/belova-chat-widget.css') }}?v={{ filemtime(public_path('css/belova-chat-widget.css')) }}">

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


<script>window.BelovaChatConfig = {!! json_encode(['incomingUrl' => url('/chat/incoming'), 'conversationTemplate' => url('/chat/conversations/__USER__'), 'authUserId' => auth()->id(), 'soundUrl' => asset('sounds/notif-chat.mp3'), 'routes' => ['chat.users' => route('chat.users')]], 15) !!};</script>
<script src="{{ asset('js/belova-chat-widget.js') }}?v={{ filemtime(public_path('js/belova-chat-widget.js')) }}"></script>