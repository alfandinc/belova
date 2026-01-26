<div id="conversationMeta" style="display:none" data-client-label="{{ isset($clientLabel) ? e($clientLabel) : '' }}" data-pasien-name="{{ isset($pasienName) ? e($pasienName) : '' }}"></div>

<div class="chat-modal" style="max-height:60vh; overflow:auto; padding: 1rem;">
    <div class="chat-list">
        @foreach($messages as $m)
            @if($m->direction == 'out')
                <div style="display:flex; justify-content:flex-end; margin-bottom:8px;">
                    <div style="background:#007bff; color:#fff; padding:8px 12px; border-radius:16px; max-width:75%;">{!! nl2br(e($m->body)) !!}
                        <div style="font-size:10px; opacity:0.8; text-align:right; margin-top:6px;">{{ $m->created_at->format('d M H:i') }}</div>
                    </div>
                </div>
            @else
                <div style="display:flex; justify-content:flex-start; margin-bottom:8px;">
                    <div style="background:#f1f0f0; color:#000; padding:8px 12px; border-radius:16px; max-width:75%;">{!! nl2br(e($m->body)) !!}
                        <div style="font-size:10px; opacity:0.8; margin-top:6px;">{{ $m->created_at->format('d M H:i') }}</div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>