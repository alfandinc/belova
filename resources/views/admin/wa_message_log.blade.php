<?php /* Blade template for Message Log */ ?>
@extends('layouts.admin.app')

@section('title', 'Message Log')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Message Log</h4>
                    <table id="wa-messages-table" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>Session</th>
                                <th>Pasien</th>
                                <th>Last Message</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>

                    <!-- Chat modal -->
                    <div class="modal fade" id="chatModal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Conversation</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body" id="chatModalBody">
                                    <div class="text-center">Loading...</div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    (function(){
        const table = $('#wa-messages-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("admin.wa_messages.data") }}',
                type: 'GET'
            },
            columns: [
                { data: 'session_display', orderable: false, searchable: false },
                { data: 'pasien', orderable: false, searchable: false },
                // last message timestamp (visible)
                { data: 'created_at', visible: true, searchable: false, render: function(data, type, row){
                    if(!data) return '';
                    try{
                        var dt = moment(data).locale('id').format('DD MMM YYYY HH:mm');
                        var dir = (row.direction || '').toLowerCase();
                        var cls = 'badge badge-pill badge-success';
                        var text = 'IN';
                        if (dir === 'out' || dir === 'o' || dir === 'sent') { cls = 'badge badge-pill badge-danger'; text = 'OUT'; }
                        return '<div style="display:flex;align-items:center;justify-content:space-between;gap:8px">'
                            + '<div style="white-space:nowrap">'+dt+'</div>'
                            + '<div><span class="'+cls+'">'+text+'</span></div>'
                            + '</div>';
                    }catch(e){ return data; }
                } },
                { data: null, orderable: false, searchable: false, render: function(data, type, row){
                    var $tmp = $('<div>').html(row.pasien || '');
                    var id = $tmp.find('a').data('pasien-id');
                    if (!id) {
                        var href = $tmp.find('a').attr('href');
                        if (href) {
                            var m = href.match(/\/pasien\/(.+)$/);
                            if (m) id = m[1];
                        }
                    }
                    id = id || row.pasien_id || '';
                    var session = row.session_client_id || '';
                    var btn = '<button class="btn btn-sm btn-primary btn-view-chat" data-pasien="'+id+'" data-session="'+session+'">View Chat</button>';
                    return btn;
                } }
            ],
            order: [[2, 'desc']],
            pageLength: 25
        });

        // refresh every 5 seconds without resetting pagination
        setInterval(function(){ table.ajax.reload(null, false); }, 5000);

        // Handle view chat button (passes session id as query param)
        $(document).on('click', '.btn-view-chat', function(){
            var pasien = $(this).data('pasien');
            var session = $(this).data('session');
            if (!pasien) return alert('No pasien id');
            $('#chatModalBody').html('<div class="text-center">Loading...</div>');
            $('#chatModal').modal('show');
            var url = '{{ url('/admin/wa-messages-log/pasien') }}/' + pasien + '/partial';
            if (typeof session !== 'undefined') url += '?session=' + encodeURIComponent(session);
            $.get(url, function(html){
                $('#chatModalBody').html(html);
                // set modal title from meta if present
                var meta = $('#chatModalBody').find('#conversationMeta');
                var clientLabel = meta.data('client-label') || '';
                var pasienName = meta.data('pasien-name') || '';
                var title = 'Conversation';
                if (clientLabel) title += ' ' + clientLabel;
                if (pasienName) title += ' - ' + pasienName;
                $('#chatModal .modal-title').text(title);
                // scroll to bottom
                var container = $('#chatModalBody .chat-modal');
                if (container && container.length) container.scrollTop(container.prop('scrollHeight'));
            }).fail(function(){
                $('#chatModalBody').html('<div class="text-danger">Failed to load conversation</div>');
            });
        });
    })();
</script>
@endsection
