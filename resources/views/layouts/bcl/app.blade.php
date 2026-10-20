{{--
    Kos BCL uses the clinic layout (layouts.erm.app) so head, theme, topbar, sidebar, footer and base
    scripts are identical to the clinic modules. BCL pages fill "bcl-content" and "pagescript"; this file
    only adds the plugins BCL needs on top of the clinic layout.
--}}
@extends('layouts.erm.app')

@section('title', 'Kos BCL')

@section('navbar')
    @include('layouts.shared.sidebar')
@endsection

@section('styles')
    {{--
        Pages that declare @section('bcl-lite') (Daftar Kamar, Inventaris) skip the heavy plugins: the full
        DataTables build (2.6 MB, Buttons export + RowGroup; the clinic layout already has core DataTables),
        dropify, lightbox and the drag-drop uploader.
    --}}
    @unless (View::hasSection('bcl-lite'))
        <link href="{{ asset('dastone/vendor/datatable/datatables.min.css') }}" rel="stylesheet" />
        <link href="{{ asset('dastone/plugins/dropify/css/dropify.min.css') }}" rel="stylesheet">
        <link href="{{ asset('dastone/plugins/lightbox/magnific-popup.css') }}" rel="stylesheet" />
        <link href="{{ asset('dastone/plugins/custom-drag-drop-file-upload/fileUpload/fileUpload.css') }}" rel="stylesheet">
    @endunless
    <link href="{{ asset('dastone/plugins/jquery-toast/dist/jquery.toast.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('dastone/plugins/jquery-confirm/jquery-confirm.min.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/bcl/bcl.css') }}?v={{ filemtime(public_path('css/bcl/bcl.css')) }}">
    @yield('pagecss')
@endsection

@section('content')
    <div class="container-fluid mt-3 bcl">
        @yield('bcl-content')
        <span id="button_export" class="m-0 p-0 hidden"></span>

        {{-- Print preview: links with class "js-print-preview" open their document here (?embed=1) instead of a new tab --}}
        <div class="modal fade bcl-print-modal" id="bcl_print_modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bcl_print_title">Preview</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body p-0 bcl-print-body">
                        <div class="bcl-print-loading" id="bcl_print_loading">Memuat dokumen...</div>
                        <iframe id="bcl_print_frame" title="Preview dokumen"></iframe>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light mr-auto" id="bcl_print_tab" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i> Buka di tab baru</a>
                        <button type="button" class="btn btn-success" id="bcl_print_download" style="display:none"><i class="fas fa-download"></i> Download</button>
                        <button type="button" class="btn btn-primary" id="bcl_print_print" disabled><i class="fas fa-print"></i> Cetak</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @unless (View::hasSection('bcl-lite'))
        <script src="{{ asset('dastone/vendor/datatable/datatables.min.js') }}"></script>
        <script src="{{ asset('dastone/plugins/dropify/js/dropify.min.js') }}"></script>
        <script src="{{ asset('dastone/plugins/lightbox/jquery.magnific-popup.js') }}"></script>
        <script src="{{ asset('dastone/plugins/custom-drag-drop-file-upload/fileUpload/fileUpload.js') }}"></script>
    @endunless
    <script src="{{ asset('dastone/plugins/jquery-toast/dist/jquery.toast.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/jquery-confirm/jquery-confirm.min.js') }}"></script>
    <script src="{{ asset('js/bcl/paste-image.js') }}?v={{ filemtime(public_path('js/bcl/paste-image.js')) }}"></script>
    <script>
        // Delete/refund/correction actions need the bcl.supervisor permission (server enforces it too).
        window.BCL_CAN_SUPERVISE = @json(Gate::allows('bcl-supervise'));
        window.BCL_METHODS = @json(\App\Services\BCL\RentalService::METHODS);

        // Fallback when jquery.number is missing: Indonesian grouping.
        if (typeof $.number !== 'function') {
            $.number = function(number, decimals) {
                var d = typeof decimals === 'number' ? decimals : 0;
                return new Intl.NumberFormat('id-ID', { minimumFractionDigits: d, maximumFractionDigits: d }).format(Number(number || 0));
            };
        }

        // Submit a state-changing link (delete/restore) as POST with the CSRF token.
        window.bclSubmitPost = function(url) {
            var $form = $('<form method="POST" style="display:none"></form>').attr('action', url);
            $form.append($('<input type="hidden" name="_token">').val($('meta[name="csrf-token"]').attr('content')));
            $('body').append($form);
            $form.trigger('submit');
        };

        // Ask for confirmation, then POST to the link's href. Usage: onclick="bclConfirmPost(event, 'Hapus data ini?')"
        window.bclConfirmPost = function(e, title, content) {
            e.preventDefault();
            var url = e.currentTarget.getAttribute('href');
            $.confirm({
                title: title || 'Hapus data ini?',
                content: content || 'Aksi ini tidak dapat diurungkan',
                theme: document.documentElement.classList.contains('theme-dark') ? 'dark' : 'light',
                buttons: {
                    confirm: { text: 'Ya', btnClass: 'btn-red', keys: ['enter'], action: function() { bclSubmitPost(url); } },
                    cancel: { text: 'Batal', action: function() {} }
                }
            });
        };
        window.deletes = window.deletes || function(e) { bclConfirmPost(e); };

        window.bclToast = function(message, icon) {
            $.toast({ text: $('<div>').text(message || '').html(), heading: icon === 'error' ? 'Gagal' : 'Berhasil', position: 'top-center', hideAfter: icon === 'error' ? 8000 : 4000, icon: icon || 'success' });
        };

        // (Re)initialise select2 and single date pickers on elements added after page load.
        window.init_component = function() {
            $('select.select2').each(function() {
                var $el = $(this);
                if ($el.hasClass('select2-hidden-accessible')) {
                    return;
                }
                var $modal = $el.closest('.modal');
                $el.select2({ placeholder: 'Pilih', width: '100%', dropdownParent: $modal.length ? $modal : $(document.body) });
            });
            if ($.fn.daterangepicker) {
                $('.datePicker').each(function() {
                    if (!$(this).data('daterangepicker')) {
                        $(this).daterangepicker({ singleDatePicker: true, showDropdowns: true, autoApply: true, locale: { format: 'YYYY-MM-DD' } });
                    }
                });
            }
        };

        // Switching from one modal to another: keep the body scroll lock while a modal is still open.
        $(document).on('hidden.bs.modal', '.modal', function() {
            if ($('.modal.show').length) $('body').addClass('modal-open');
        });

        // Print preview modal (nota, refund): the document loads bare (?embed=1) in an iframe; Cetak prints only
        // the iframe, Download uses the document's own download_receipt() when it has one.
        window.bclPrintPreview = function(url, title) {
            var frame = document.getElementById('bcl_print_frame');
            var embedUrl = url + (url.indexOf('?') === -1 ? '?' : '&') + 'embed=1';
            $('#bcl_print_title').text(title || 'Preview');
            $('#bcl_print_tab').attr('href', url);
            $('#bcl_print_print').prop('disabled', true);
            $('#bcl_print_download').hide();
            $('#bcl_print_loading').show();
            frame.onload = function() {
                $('#bcl_print_loading').hide();
                $('#bcl_print_print').prop('disabled', false);
                try { $('#bcl_print_download').toggle(typeof frame.contentWindow.download_receipt === 'function'); } catch (e) {}
            };
            frame.src = embedUrl;
            $('#bcl_print_modal').modal('show');
        };
        $(document).on('click', 'a.js-print-preview', function(e) {
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.which === 2) return; // keep "open in new tab" gestures
            e.preventDefault();
            bclPrintPreview(this.href, $(this).data('title') || 'Nota ' + $.trim($(this).data('label') || ''));
        });
        $('#bcl_print_print').on('click', function() {
            var win = document.getElementById('bcl_print_frame').contentWindow;
            win.focus();
            win.print();
        });
        $('#bcl_print_download').on('click', function() {
            document.getElementById('bcl_print_frame').contentWindow.download_receipt();
        });
        // Opened on top of another modal (room panel): stack it and its backdrop above that one.
        $('#bcl_print_modal').on('show.bs.modal', function() {
            $(this).css('z-index', 1070);
            setTimeout(function() { $('.modal-backdrop').last().css('z-index', 1065); }, 0);
        }).on('hidden.bs.modal', function() {
            document.getElementById('bcl_print_frame').src = 'about:blank';
        });

        // Prevent double submits on every classic form: disable the submit button once.
        $(document).on('submit', 'form', function(e) {
            if (e.isDefaultPrevented() || this.method.toLowerCase() === 'get' || this.target === '_blank') return;
            var $btn = $(this).find('button[type="submit"]');
            setTimeout(function() { $btn.prop('disabled', true).addClass('disabled'); }, 0);
        });
    </script>
    @yield('pagescript')
    <script>
        $(document).ready(function() {
            init_component();
            if (!window.BCL_CAN_SUPERVISE) {
                $('.bcl a[href*="/delete/"], .bcl a[href*="/forcedelete/"], .bcl .js-supervisor').remove();
            }
            @if($message = Session::get('success'))
            bclToast(@json($message), 'success');
            @endif
            @if($error = Session::get('error'))
            bclToast(@json($error), 'error');
            @endif
            if (window.feather) feather.replace();
        });
    </script>
@endsection
