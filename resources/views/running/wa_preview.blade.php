@extends('layouts.erm.app')

@section('title', 'WA Message Preview')

@section('navbar')
    @include('layouts.erm.navbar-ngaji')
@endsection

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h4 class="mb-3 text-center">Chat on WhatsApp with {{ $phone }}</h4>

            <div class="card mb-3">
                <div class="card-body">
                    <pre id="wa_preview_text" style="white-space:pre-wrap;word-break:break-word;">{{ $message }}</pre>
                </div>
            </div>

            @if($image)
            <div class="text-center mb-3">
                <a href="{{ $image }}" target="_blank" class="btn btn-sm btn-outline-secondary">Open Ticket Image</a>
            </div>
            @endif

            <div class="d-flex justify-content-center">
                <button id="copyBtn" class="btn btn-primary mr-2">Copy Message</button>
                <a id="openWaBtn" href="https://wa.me/{{ $phone }}" target="_blank" class="btn btn-success">Open WhatsApp</a>
            </div>

            <p class="text-center mt-3 text-muted">Message is shown above. After opening WhatsApp, paste the message (Ctrl+V / long press) into the chat and send.</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function(){
        var copyBtn = document.getElementById('copyBtn');
        var text = document.getElementById('wa_preview_text').innerText || '';
        copyBtn.addEventListener('click', async function(){
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(text);
                } else {
                    var ta = document.createElement('textarea');
                    ta.style.position = 'fixed'; ta.style.left = '-9999px';
                    ta.value = text;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                }
                alert('Message copied to clipboard. Open WhatsApp and paste into chat.');
            } catch (e) {
                alert('Copy failed. Please select and copy manually.');
            }
        });
    })();
</script>
@endpush
