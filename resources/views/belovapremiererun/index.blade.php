@extends('layouts.public')

@section('title', 'Belova Premiere Run - Peserta')

@push('styles')
<style>
    .belova-public-card {
        background-image: url('{{ asset('img/templates/website_bg.jpg') }}');
        background-size: cover;
        background-position: center center;
        background-repeat: no-repeat;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.35);
        border: none;
        color: #ffffff;
    }

    .belova-public-card .card-body {
        background-color: rgba(0,0,0,0.25);
        border-radius: 12px;
    }

    .belova-public-card label,
    .belova-public-card .dataTables_info,
    .belova-public-card .dataTables_paginate,
    .belova-public-card .dataTables_length,
    .belova-public-card .dataTables_filter {
        color: #ffffff;
    }
</style>
@endpush

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h3>Belova Premiere Run - Peserta</h3>
            <p class="text-muted">Daftar peserta (public view).</p>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card belova-public-card">
                <div class="card-body">
                    <form id="public-search-form" class="form-inline mb-3">
                        <div class="form-group mr-2">
                            <input type="text" id="input_nama" class="form-control" placeholder="Nama" required>
                        </div>
                        <div class="form-group mr-2">
                            <input type="text" id="input_no_hp" class="form-control" placeholder="No. HP" required>
                        </div>
                        <div class="form-group mr-2">
                            <input type="email" id="input_email" class="form-control" placeholder="Email" required>
                        </div>
                        <button id="btnSearch" type="button" class="btn btn-primary">Search</button>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="public-peserta-table" style="width:100%">
                            <thead>
                                <tr>
                                            <th>Nama</th>
                                            <th>No. HP</th>
                                            <th>Kategori</th>
                                            <th>Ticket</th>
                                        </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script>
    $(function(){
        // Initialize DataTable in server-side mode but defer initial load until search
        var table = $('#public-peserta-table').DataTable({
            processing: true,
            serverSide: true,
            deferLoading: 0,
            ajax: {
                url: '{{ route("belovapremiererun.data") }}',
                type: 'GET',
                data: function(d){
                    d.status = 'all';
                    d.sent = 'all';
                    d.nama = $('#input_nama').val();
                    d.no_hp = $('#input_no_hp').val();
                    d.email = $('#input_email').val();
                    // indicate controller should require all inputs before returning results
                    d.require_all_inputs = 1;
                }
            },
            columns: [
                { data: 'nama_peserta', name: 'nama_peserta' },
                { data: 'no_hp', name: 'no_hp' },
                { data: 'kategori', name: 'kategori' },
                { data: 'id', name: 'id', orderable: false, searchable: false, render: function(data, type, row) {
                        return '<button type="button" class="btn btn-sm btn-primary btn-public-download-ticket" data-id="' + data + '">Download Ticket</button>';
                    }
                }
            ],
            order: [[0,'asc']],
            responsive: true
        });

        function validateInputs() {
            var n = $('#input_nama').val().trim();
            var p = $('#input_no_hp').val().trim();
            var e = $('#input_email').val().trim();
            if (!n || !p || !e) return false;
            return true;
        }

        $('#btnSearch').on('click', function(){
            if (!validateInputs()) {
                alert('Please fill Nama, No. HP and Email to search.');
                return;
            }
            table.ajax.reload();
        });

        // optional: trigger search on Enter when focused inside the form
        $('#public-search-form input').on('keypress', function(e){ if (e.which === 13) { e.preventDefault(); $('#btnSearch').click(); } });

        // offscreen container for ticket rendering
        var $off = $('<div id="public-ticket-offscreen" style="position:fixed;left:-9999px;top:0;"></div>');
        $('body').append($off);

        // Handle public Download Ticket button: generate image offscreen and download, no new tab
        $('#public-peserta-table').on('click', '.btn-public-download-ticket', function(){
            var id = $(this).data('id');
            if (!id) return;
            var $btn = $(this);
            var originalText = $btn.text();
            $btn.prop('disabled', true).text('Generating...');

            var url = '{{ route('belovapremiererun.ticket_html', ['id' => '__id__']) }}'.replace('__id__', id);
            $.get(url).done(function(html){
                $off.html(html);

                try {
                    var codeEl = $off.find('#modal-unique-code').get(0);
                    var svgEl  = $off.find('#modal-barcode').get(0);
                    if (codeEl && svgEl) {
                        var codeText = (codeEl.textContent || '').trim();
                        if (codeText) {
                            JsBarcode(svgEl, codeText, {
                                format: 'CODE128',
                                displayValue: false,
                                width: 2.5,
                                height: 100,
                                margin: 2
                            });
                        }
                    }
                } catch(e) {
                    console.error('Barcode render error', e);
                }

                setTimeout(function(){
                    var page = $off.find('.ticket-page').get(0);
                    if (!page) {
                        $btn.prop('disabled', false).text(originalText);
                        alert('Failed to prepare ticket.');
                        return;
                    }
                    html2canvas(page, { scale: 2 }).then(function(canvas){
                        var dataUrl = canvas.toDataURL('image/png');
                        var link = document.createElement('a');

                        // Try to use code as filename if available
                        var codeEl2 = $off.find('#modal-unique-code').get(0);
                        var codeText2 = codeEl2 ? (codeEl2.textContent || '').trim() : '';
                        var fname = (codeText2 ? ('ticket-' + codeText2) : 'ticket') + '.png';
                        link.href = dataUrl;
                        link.download = fname;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);

                        // Also store on server so WA bot/admin can reuse image
                        $.ajax({
                            url: '{{ route('running.store_ticket_image') }}',
                            method: 'POST',
                            data: {
                                peserta_id: id,
                                image_data: dataUrl,
                                _token: $('meta[name="csrf-token"]').attr('content')
                            }
                        });

                        $btn.prop('disabled', false).text(originalText);
                    }).catch(function(err){
                        console.error('html2canvas error', err);
                        $btn.prop('disabled', false).text(originalText);
                        alert('Failed to generate ticket image.');
                    });
                }, 300);
            }).fail(function(){
                $btn.prop('disabled', false).text(originalText);
                alert('Failed to load ticket data.');
            });
        });
    });
</script>
@endpush
