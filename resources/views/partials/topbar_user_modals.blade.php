{{-- Topbar extras (moved from the old main menu): online users + mood, mood picker, update info. Profile editing lives on Profil Saya. --}}
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

{{-- Foto profil user online (klik avatar orang lain di topbar) --}}
<div class="modal fade" id="userPhotoModal" tabindex="-1" role="dialog" aria-labelledby="userPhotoName" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content user-photo-modal">
            <button type="button" class="close user-photo-close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            <div class="user-photo-frame" id="userPhotoFrame"></div>
            <div class="modal-body text-center pt-3">
                <h5 class="mb-1" id="userPhotoName"></h5>
                <div class="small text-muted" id="userPhotoMood"></div>
            </div>
        </div>
    </div>
</div>

{{-- Ganti password: every account; opened from the topbar menu and the Profil Saya page --}}
<div class="modal fade" id="passwordChangeModal" tabindex="-1" role="dialog" aria-labelledby="passwordChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form class="modal-content" id="passwordChangeForm" action="{{ route('hrd.employee.password.update') }}" method="POST" novalidate>
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title" id="passwordChangeModalLabel"><i data-feather="key" class="icon-xs mr-1"></i>Ganti Password</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="pw-current">Password saat ini <span class="text-danger">*</span></label>
                    <input type="password" id="pw-current" name="current_password" class="form-control" autocomplete="current-password" required>
                </div>
                <div class="form-group">
                    <label for="pw-new">Password baru <span class="text-danger">*</span></label>
                    <input type="password" id="pw-new" name="password" class="form-control" minlength="8" autocomplete="new-password" required>
                    <small class="form-text text-muted">Minimal 8 karakter, berbeda dari password saat ini.</small>
                </div>
                <div class="form-group">
                    <label for="pw-confirm">Ulangi password baru <span class="text-danger">*</span></label>
                    <input type="password" id="pw-confirm" name="password_confirmation" class="form-control" minlength="8" autocomplete="new-password" required>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="pw-show">
                    <label class="custom-control-label" for="pw-show">Tampilkan password</label>
                </div>
                <div class="alert alert-danger py-2 small mt-3 mb-0" id="pw-error" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="pw-save">Ganti Password</button>
            </div>
        </form>
    </div>
</div>

@include('partials.system_update_modal')

<script>window.BelovaTopbarUserConfig = {!! json_encode(['currentUserId' => (int) Auth::id(), 'currentUserEmotion' => $currentUserEmotion, 'heartbeatUrl' => route('user-emotions.heartbeat'), 'updateEmotionUrl' => route('user-emotions.update'), 'csrf' => csrf_token(), 'activeUserEmotions' => array_values($activeUserEmotions)], 15) !!};</script>
<script src="{{ asset('js/belova-topbar-user.js') }}?v={{ filemtime(public_path('js/belova-topbar-user.js')) }}"></script>
