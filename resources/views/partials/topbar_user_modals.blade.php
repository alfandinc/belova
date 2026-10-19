{{-- Topbar extras (moved from the old main menu): online users + mood, mood picker, profile editor, update info. --}}
<div class="modal fade" id="emotionPickerModal" tabindex="-1" role="dialog" aria-labelledby="emotionPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emotionPickerModalLabel">Ganti Mood</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Pilih mood terbaru untuk ditampilkan di topbar.</p>
                <div class="emotion-picker-grid" id="emotion-picker-grid">
                    @foreach($emotionOptions as $emotionKey => $emotionOption)
                        <button type="button" class="emotion-picker-option {{ $currentUserEmotion === $emotionKey ? 'active' : '' }}" data-emotion="{{ $emotionKey }}">
                            <span class="emoji">{{ $emotionOption['emoji'] }}</span>
                            <span class="label">{{ $emotionOption['label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="save-emotion-btn">Simpan Mood</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" id="profileModalContent"></div>
    </div>
</div>

@include('partials.system_update_modal')

<script>window.BelovaTopbarUserConfig = {!! json_encode(['currentUserId' => (int) Auth::id(), 'currentUserEmotion' => $currentUserEmotion, 'hasEmployeeProfile' => (bool) (Auth::user() && Auth::user()->employee), 'profileModalUrl' => route('hrd.employee.profile.modal'), 'heartbeatUrl' => route('user-emotions.heartbeat'), 'updateEmotionUrl' => route('user-emotions.update'), 'csrf' => csrf_token(), 'activeUserEmotions' => array_values($activeUserEmotions)], 15) !!};</script>
<script src="{{ asset('js/belova-topbar-user.js') }}?v={{ filemtime(public_path('js/belova-topbar-user.js')) }}"></script>
