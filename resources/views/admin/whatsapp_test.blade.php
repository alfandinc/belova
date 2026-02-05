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

                    <form method="POST" action="{{ route('admin.whatsapp_test.send') }}" enctype="multipart/form-data">
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
                            <label>Recipients</label>
                            <div id="recipients">
                                <div class="recipient-row mb-2 row" data-index="0">
                                    <div class="col-md-3">
                                        <label>Pasien (optional)</label>
                                        <select name="pasien_id[]" class="form-control pasien_select" style="width:100%"></select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Phone</label>
                                        <input type="text" name="to[]" class="form-control" placeholder="62812xxxx">
                                    </div>
                                    <div class="col-md-4">
                                        <label>Message</label>
                                        <textarea name="message[]" rows="2" class="form-control">Test message from Belova system</textarea>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Image</label>
                                        <input type="file" name="image[]" accept="image/*" class="form-control-file">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button type="button" id="add_recipient" class="btn btn-sm btn-secondary">Add recipient</button>
                                <button type="button" id="remove_recipient" class="btn btn-sm btn-danger">Remove last</button>
                            </div>
                            <small class="form-text text-muted">You can add multiple recipients with different message/image per row.</small>
                        </div>
                        <div class="form-group form-inline">
                            <div class="form-check mr-3">
                                <input class="form-check-input" type="checkbox" id="schedule_check_all">
                                <label class="form-check-label" for="schedule_check_all">Schedule all messages</label>
                            </div>
                            <div class="">
                                <input type="datetime-local" name="schedule_at" class="form-control" id="schedule_at_input_all" style="max-width:300px;" disabled>
                                <small class="form-text text-muted">Optional. Use local datetime to schedule all messages. Per-row schedule not implemented.</small>
                            </div>
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
                            if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
                                return;
                            }

                            function initPasienSelect($el) {
                                $el.select2({
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
                                $el.on('select2:select', function(e){
                                    var data = e.params.data;
                                    if (data && data.phone) {
                                        $(this).closest('.recipient-row').find('input[name="to[]"]').val(data.phone);
                                    }
                                });
                                $el.on('select2:clear', function(){ $(this).closest('.recipient-row').find('input[name="to[]"]').val(''); });
                            }

                            // init first pasien select
                            initPasienSelect($('.pasien_select'));

                            // add/remove recipient rows
                            var idx = 1;
                            $('#add_recipient').on('click', function(){
                                var $row = $('<div class="recipient-row mb-2 row" data-index="'+idx+'">'
                                    + '<div class="col-md-3">'
                                      + '<label>Pasien (optional)</label>'
                                      + '<select name="pasien_id[]" class="form-control pasien_select" style="width:100%"></select>'
                                    + '</div>'
                                    + '<div class="col-md-3">'
                                      + '<label>Phone</label>'
                                      + '<input type="text" name="to[]" class="form-control" placeholder="62812xxxx">'
                                    + '</div>'
                                    + '<div class="col-md-4">'
                                      + '<label>Message</label>'
                                      + '<textarea name="message[]" rows="2" class="form-control"></textarea>'
                                    + '</div>'
                                    + '<div class="col-md-2">'
                                      + '<label>Image</label>'
                                      + '<input type="file" name="image[]" accept="image/*" class="form-control-file">'
                                    + '</div>'
                                  + '</div>');
                                $('#recipients').append($row);
                                initPasienSelect($row.find('.pasien_select'));
                                idx++;
                            });

                            $('#remove_recipient').on('click', function(){
                                var $rows = $('#recipients .recipient-row');
                                if ($rows.length > 1) $rows.last().remove();
                            });

                            // schedule all toggle
                            $('#schedule_check_all').on('change', function(){
                                var enabled = $(this).is(':checked');
                                $('#schedule_at_input_all').prop('disabled', !enabled);
                                if (!enabled) $('#schedule_at_input_all').val('');
                            });
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
