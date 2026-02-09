@extends('layouts.public')

@section('title', 'Belova Premiere Run - Ticket')

@section('content')
    <div class="d-flex justify-content-center align-items-center" style="min-height:100vh;">
        @include('running.ticket_fragment')
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            try {
                var codeEl = document.getElementById('modal-unique-code');
                var svgEl  = document.getElementById('modal-barcode');
                if (!codeEl || !svgEl) return;
                var codeText = (codeEl.textContent || '').trim();
                if (!codeText) return;

                // Render barcode the same way as in the running index flow
                JsBarcode(svgEl, codeText, {
                    format: 'CODE128',
                    displayValue: false,
                    width: 2.5,
                    height: 100,
                    margin: 2
                });

                // After barcode is rendered, capture the ticket and trigger download automatically
                setTimeout(function(){
                    var page = document.querySelector('.ticket-page');
                    if (!page) return;

                    html2canvas(page, { scale: 2 }).then(function(canvas){
                        var dataUrl = canvas.toDataURL('image/png');
                        var link = document.createElement('a');
                        link.href = dataUrl;
                        // use unique code as part of filename if available
                        var fname = (codeText ? ('ticket-' + codeText) : 'ticket') + '.png';
                        link.download = fname;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }).catch(function(err){
                        console.error('html2canvas error', err);
                    });
                }, 400);
            } catch (e) {
                console.error('Ticket render error', e);
            }
        });
    </script>
@endpush
