@extends('layouts.admin.app')

@section('title', 'WhatsApp Test')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">WhatsApp Test</h4>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('admin.whatsapp_test.send') }}">
                        @csrf
                        <div class="form-group">
                            <label>From (session)</label>
                            <div class="d-flex">
                                <select name="from" class="form-control mr-2">
                                    @if(!empty($sessions))
                                        @foreach($sessions as $s)
                                            <option value="{{ $s['id'] }}">{{ $s['id'] }} {{ $s['label'] ? '- ' . $s['label'] : '' }} ({{ $s['status'] }})</option>
                                        @endforeach
                                    @else
                                        <option value="">(default)</option>
                                    @endif
                                </select>
                                <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#addSessionModal">Add</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Select Pasien (optional)</label>
                            <select id="pasien_select" class="form-control" style="width:100%"></select>
                            <small class="form-text text-muted">Choosing a pasien will autofill the phone number below.</small>
                        </div>
                        <div class="form-group">
                            <label>Phone Number (international, no +, e.g. 628123...)</label>
                            <input type="text" name="to" class="form-control" placeholder="62812xxxx" required>
                        </div>
                        <div class="form-group">
                            <label>Message</label>
                            <textarea name="message" rows="4" class="form-control" required>Test message from Belova system</textarea>
                        </div>
                        <button class="btn btn-primary">Send</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
                @section('scripts')
                    <script>
                        (function(){
                            // initialize Select2 for pasien search
                            if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
                                return;
                            }

                            $('#pasien_select').select2({
                                placeholder: 'Search pasien by name or phone',
                                allowClear: true,
                                ajax: {
                                    url: '{{ route('admin.whatsapp_test.pasien_search') }}',
                                    dataType: 'json',
                                    delay: 250,
                                    data: function(params){ return { q: params.term }; },
                                    processResults: function(data){ return { results: data.results }; }
                                }
                            });

                            $('#pasien_select').on('select2:select', function(e){
                                var data = e.params.data;
                                if (data && data.phone) {
                                    $('input[name=to]').val(data.phone);
                                }
                            });
                            $('#pasien_select').on('select2:clear', function(){ $('input[name=to]').val(''); });
                        })();
                    </script>
                @endsection
<!-- Add Session Modal -->
<div class="modal fade" id="addSessionModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.wa_sessions.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add WA Session</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Client ID (unique)</label>
                        <input name="client_id" class="form-control" placeholder="belova-bot-2" required>
                    </div>
                    <div class="form-group">
                        <label>Label (optional)</label>
                        <input name="label" class="form-control" placeholder="Second phone">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Session</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
