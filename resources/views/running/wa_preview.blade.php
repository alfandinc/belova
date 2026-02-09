@extends('layouts.erm.app')

@section('title', 'Email Message Template')

@section('navbar')
    @include('layouts.erm.navbar-ngaji')
@endsection

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8">
            <h4 class="mb-3 text-center">Email Message Template</h4>

            <div class="card mb-3 shadow-sm">
                <div class="card-body">
                    <div class="mb-3">
                        <strong>To:</strong>
                        <div class="d-flex align-items-center mt-1">
                            <div class="text-monospace flex-grow-1">
                                @if(!empty($name))
                                    {{ $name }}
                                @endif
                                @if(!empty($email))
                                    @if(!empty($name))
                                        &lt;{{ $email }}&gt;
                                    @else
                                        {{ $email }}
                                    @endif
                                @endif
                            </div>
                            @if(!empty($email))
                                <button id="copyEmailBtn" type="button" class="btn btn-outline-primary btn-sm ml-2">Copy Email</button>
                                <input type="text" id="email_address" class="d-none" value="{{ $email }}">
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Subject:</strong>
                        <div class="d-flex mt-1">
                            <input type="text" id="email_subject" class="form-control" readonly value="{{ $subject }}">
                            <button id="copySubjectBtn" type="button" class="btn btn-outline-primary btn-sm ml-2">Copy Subject</button>
                        </div>
                    </div>

                    <div class="mb-2">
                        <strong>Body:</strong>
                        <textarea id="email_body" class="form-control mt-1" rows="10" readonly style="white-space:pre-wrap;word-break:break-word;">{{ $message }}</textarea>
                        <div class="mt-2 text-right">
                            <button id="copyBodyBtn" type="button" class="btn btn-primary btn-sm">Copy Body</button>
                        </div>
                    </div>
                </div>
            </div>

            @if($image)
            <div class="card mb-3 shadow-sm">
                <div class="card-body text-center">
                    <p class="mb-2">Ticket image to attach to the email (you can drag this into your email composer):</p>
                    <div class="d-inline-block" style="border:1px dashed #ddd;padding:6px;background:#fafafa;">
                        <img src="{{ $image }}" alt="Ticket Image" class="img-fluid" style="max-height:420px;cursor:grab;">
                    </div>
                </div>
            </div>
            @endif

            <p class="text-center mt-3 text-muted">Step 1: Open your email client (Gmail, Outlook, etc.).<br>Step 2: Start a new email to the participant, paste the subject and body, then attach the ticket image.</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function(){
        function copyFromElement(id, successMsg) {
            var el = document.getElementById(id);
            if (!el) return;
            var text = el.value || el.innerText || '';
            (async function(){
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
                    // silently succeed; no popup on success
                } catch (e) {
                    alert('Copy failed. Please select and copy manually.');
                }
            })();
        }

        var copySubjectBtn = document.getElementById('copySubjectBtn');
        if (copySubjectBtn) {
            copySubjectBtn.addEventListener('click', function(){
                copyFromElement('email_subject', 'Subject copied. Paste it into your email.');
            });
        }

        var copyBodyBtn = document.getElementById('copyBodyBtn');
        if (copyBodyBtn) {
            copyBodyBtn.addEventListener('click', function(){
                copyFromElement('email_body', 'Body copied. Paste it into your email.');
            });
        }

        var copyEmailBtn = document.getElementById('copyEmailBtn');
        if (copyEmailBtn) {
            copyEmailBtn.addEventListener('click', function(){
                copyFromElement('email_address', 'Email address copied. Paste it into your email client.');
            });
        }

        // Keyboard shortcuts: Q = copy email, W = copy subject, E = copy body
        document.addEventListener('keydown', function(ev){
            // ignore when user is typing inside an input/textarea with modifiers (Ctrl, Alt, Meta)
            if (ev.ctrlKey || ev.altKey || ev.metaKey) return;
            var tag = (ev.target && ev.target.tagName) ? ev.target.tagName.toLowerCase() : '';
            if (tag === 'input' || tag === 'textarea') {
                // still allow shortcuts to work while focused in the subject/body fields
            }

            var key = (ev.key || '').toLowerCase();
            if (key === 'q' && copyEmailBtn) {
                ev.preventDefault();
                copyFromElement('email_address', 'Email address copied. Paste it into your email client.');
            } else if (key === 'w' && copySubjectBtn) {
                ev.preventDefault();
                copyFromElement('email_subject', 'Subject copied. Paste it into your email.');
            } else if (key === 'e' && copyBodyBtn) {
                ev.preventDefault();
                copyFromElement('email_body', 'Body copied. Paste it into your email.');
            }
        });
    })();
</script>
@endpush
