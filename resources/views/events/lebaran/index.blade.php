@extends('layouts.erm.app')

@section('title', 'Lebaran')

@section('navbar')
    @include('layouts.erm.navbar-ngaji')
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="row align-items-end">
        <div class="col-lg-4 col-md-12 mb-3 mb-lg-0">
            <h3>Lebaran</h3>
            <p class="text-muted mb-0">Daftar data pasien untuk event Lebaran.</p>
        </div>
        <div class="col-lg-8 col-md-12">
            <div class="lebaran-import-box">
                <form method="POST" action="{{ route('events.lebaran.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-row align-items-end">
                        <div class="col-md-8 mb-2 mb-md-0">
                            <label for="file" class="small text-muted mb-1">Import Excel/Spreadsheet</label>
                            <div class="custom-file">
                                <input type="file" name="file" id="file" class="custom-file-input" accept=".xlsx,.xls,.csv" required>
                                <label class="custom-file-label" for="file">Pilih file</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary btn-block">Import</button>
                        </div>
                    </div>
                    <small class="form-text text-muted mb-0">Format kolom: nama pasien, pasien id atau no rm, dan nohp. Baris akan diabaikan jika pasien ID tidak ditemukan.</small>
                </form>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="row mt-3">
            <div class="col-12">
                <div class="alert alert-success mb-0">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="row mt-3">
            <div class="col-12">
                <div class="alert alert-danger mb-0">{{ session('error') }}</div>
            </div>
        </div>
    @endif

    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="form-row align-items-end mb-3">
                        <div class="col-md-3 col-lg-2">
                            <label for="statusFilter" class="small text-muted mb-1">Filter Status</label>
                            <select id="statusFilter" class="form-control form-control-sm">
                                <option value="pending" selected>Pending</option>
                                <option value="sent">Sent</option>
                                <option value="all">All</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered w-100" id="lebaran-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Pasien</th>
                                    <th>Pasien ID</th>
                                    <th>No HP</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div class="modal fade" id="lebaranPreviewModal" tabindex="-1" role="dialog" aria-labelledby="lebaranPreviewModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-xl lebaran-preview-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="lebaranPreviewModalLabel">Preview Ucapan Lebaran</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div id="lebaranPreviewMessage" class="alert alert-danger d-none mb-3"></div>
                                    <div class="row align-items-start">
                                        <div class="col-lg-7 mb-3 mb-lg-0">
                                            <div class="lebaran-preview-wrapper text-center">
                                                <canvas id="lebaranPreviewCanvas" class="d-none"></canvas>
                                                <img id="lebaranPreviewImage" class="lebaran-preview-canvas d-none" alt="Preview Ucapan Lebaran" draggable="true">
                                            </div>
                                            <small class="text-muted d-block text-center mt-3">Drag gambar dari preview ini untuk menyalin atau kirim ke aplikasi lain.</small>
                                        </div>
                                        <div class="col-lg-5">
                                            <div class="lebaran-wa-panel">
                                                <label for="lebaranWaMessage" class="small text-muted mb-1">Template Ucapan WhatsApp</label>
                                                <textarea id="lebaranWaMessage" class="form-control" rows="14" readonly></textarea>
                                                <div class="mt-3 d-flex flex-wrap lebaran-wa-actions">
                                                    <button type="button" class="btn btn-outline-primary mr-2 mb-2" id="copyLebaranImage">Copy Image</button>
                                                    <button type="button" class="btn btn-outline-secondary mr-2 mb-2" id="copyLebaranWaMessage">Copy Ucapan</button>
                                                    <a href="#" target="_blank" rel="noopener noreferrer" class="btn btn-success mb-2 disabled" id="openLebaranWaLink" aria-disabled="true">Buka di WhatsApp</a>
                                                </div>
                                                <small id="lebaranWaHelp" class="text-muted d-block">Nomor WhatsApp pasien akan dipakai untuk tombol `wa.me`.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-success" id="markLebaranSentBtn">Check</button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
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

@push('scripts')
<script>
    $(function () {
        var templateWidth = 1080;
        var templateHeight = 1920;
        var previewConfigs = {
            preview1: {
                title: 'Preview Ucapan Lebaran',
                templateUrl: '{{ asset('img/templates/lebaran_wa.png') }}',
                templatePath: 'public/img/templates/lebaran_wa.png',
                filenamePrefix: 'lebaran',
                textColor: '#6f217c',
                waMessageTemplate: [
                    'Yth. Bapak/Ibu __PASIEN_NAME__',
                    'Dengan penuh rasa syukur dan kebahagiaan, kami segenap keluarga besar Klinik Pratama Belova Skin & Beauty Center mengucapkan:',
                    '',
                    'Selamat Hari Raya Idul Fitri 1 Syawal 1447 H',
                    '',
                    'Taqabbalallahu minna wa minkum, taqabbal yaa karim.',
                    'Mohon Maaf Lahir & Batin',
                    'Semoga di hari yang fitri ini, kita semua diberikan kesehatan, kebahagiaan, serta dipertemukan kembali dengan Ramadan berikutnya dalam keadaan yang lebih baik.',
                    '',
                    'Terima kasih atas kepercayaan Anda kepada kami. Semoga kami senantiasa dapat memberikan pelayanan terbaik untuk kesehatan dan kecantikan Anda.',
                    '',
                    'Aamiin Ya Rabbal ‘Alamin.',
                    '',
                    'Wassalamu’alaikum warahmatullahi wabarakatuh.'
                ].join('\n')
            },
            preview2: {
                title: 'Preview Ucapan Lebaran 2',
                templateUrl: '{{ asset('img/templates/lebaran_wa_2.png') }}',
                templatePath: 'public/img/templates/lebaran_wa_2.png',
                filenamePrefix: 'lebaran-2',
                textColor: '#0b2c5f',
                waMessageTemplate: [
                    'Yth. Bapak/Ibu __PASIEN_NAME__',
                    'Dengan penuh rasa syukur dan kebahagiaan, kami segenap keluarga besar Klinik Utama Premiere Belova mengucapkan:',
                    '',
                    'Selamat Hari Raya Idul Fitri 1 Syawal 1447 H',
                    '',
                    'Taqabbalallahu minna wa minkum, taqabbal yaa karim.',
                    'Mohon Maaf Lahir & Batin',
                    'Semoga di hari yang fitri ini, kita semua diberikan kesehatan, kebahagiaan, serta dipertemukan kembali dengan Ramadan berikutnya dalam keadaan yang lebih baik.',
                    '',
                    'Terima kasih atas kepercayaan Anda kepada kami. Semoga kami senantiasa dapat memberikan pelayanan terbaik untuk kesehatan Anda.',
                    '',
                    'Aamiin Ya Rabbal ‘Alamin.',
                    '',
                    'Wassalamu’alaikum warahmatullahi wabarakatuh.'
                ].join('\n')
            }
        };
        var templateImages = {};
        var activeLebaranId = null;
        var activePreviewVariant = 'preview1';
        var refreshScrollTop = null;
        var refreshTableScrollTop = null;
        var waPatientPlaceholder = '__PASIEN_NAME__';

        $('#file').on('change', function () {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').text(fileName || 'Pilih file');
        });

        function showPreviewError(message) {
            $('#lebaranPreviewMessage').removeClass('d-none').text(message);
            $('#lebaranPreviewImage').addClass('d-none').attr('src', '');
        }

        function clearPreviewError() {
            $('#lebaranPreviewMessage').addClass('d-none').text('');
        }

        function updateMarkSentButtonState(isLoading) {
            $('#markLebaranSentBtn')
                .prop('disabled', isLoading || !activeLebaranId)
                .text(isLoading ? 'Menyimpan...' : 'Check');
        }

        function normalizeWhatsAppNumber(phoneNumber) {
            var digits = (phoneNumber || '').toString().replace(/[^0-9]/g, '');

            if (!digits) {
                return '';
            }

            if (digits.indexOf('0') === 0) {
                return '62' + digits.substring(1);
            }

            if (digits.indexOf('62') === 0) {
                return digits;
            }

            return digits;
        }

        function decodeHtmlEntities(value) {
            var textarea = document.createElement('textarea');
            textarea.innerHTML = value || '';
            return textarea.value;
        }

        function getPreviewConfig(variant) {
            return previewConfigs[variant] || previewConfigs.preview1;
        }

        function buildWaMessage(patientName, variant) {
            var config = getPreviewConfig(variant);
            return config.waMessageTemplate.replace(waPatientPlaceholder, decodeHtmlEntities(patientName || '-'));
        }

        function updateWaPanel(patientName, phoneNumber, variant) {
            var config = getPreviewConfig(variant);
            var message = buildWaMessage(patientName, variant);
            var normalizedPhone = normalizeWhatsAppNumber(phoneNumber);
            var waUrl = normalizedPhone ? 'https://wa.me/' + normalizedPhone + '?text=' + encodeURIComponent(message) : '#';

            $('#lebaranPreviewModalLabel').text(config.title);

            $('#lebaranWaMessage').val(message);

            $('#openLebaranWaLink')
                .attr('href', waUrl)
                .toggleClass('disabled', !normalizedPhone)
                .attr('aria-disabled', normalizedPhone ? 'false' : 'true');

            $('#lebaranWaHelp').text(
                normalizedPhone
                    ? 'Nomor tujuan dari data Lebaran: ' + normalizedPhone
                    : 'Nomor WhatsApp pada data Lebaran belum tersedia, jadi link wa.me belum bisa dibuka.'
            );
        }

        function dataUrlToBlob(dataUrl) {
            var parts = dataUrl.split(',');
            var mimeMatch = parts[0].match(/:(.*?);/);
            var mime = mimeMatch ? mimeMatch[1] : 'image/png';
            var binary = atob(parts[1]);
            var length = binary.length;
            var bytes = new Uint8Array(length);

            for (var i = 0; i < length; i++) {
                bytes[i] = binary.charCodeAt(i);
            }

            return new Blob([bytes], { type: mime });
        }

        function copyPreviewImageToClipboard() {
            var previewImage = document.getElementById('lebaranPreviewImage');
            if (!previewImage || !previewImage.src) {
                return Promise.resolve({ ok: false, message: 'Preview gambar belum tersedia.' });
            }

            if (!navigator.clipboard || typeof window.ClipboardItem === 'undefined' || typeof navigator.clipboard.write !== 'function') {
                return Promise.resolve({ ok: false, message: 'Browser ini tidak mendukung copy gambar otomatis ke clipboard.' });
            }

            try {
                var blob = dataUrlToBlob(previewImage.src);
                var item = new ClipboardItem({ 'image/png': blob });

                return navigator.clipboard.write([item]).then(function () {
                    return { ok: true, message: 'Gambar berhasil disalin ke clipboard. Anda bisa langsung paste di WhatsApp.' };
                }).catch(function () {
                    return { ok: false, message: 'Gagal menyalin gambar otomatis ke clipboard. Gunakan drag gambar sebagai alternatif.' };
                });
            } catch (error) {
                return Promise.resolve({ ok: false, message: 'Gagal memproses gambar untuk clipboard.' });
            }
        }

        function updatePreviewImageFromCanvas(patientName, variant) {
            var config = getPreviewConfig(variant);
            var canvas = document.getElementById('lebaranPreviewCanvas');
            var previewImage = document.getElementById('lebaranPreviewImage');
            var dataUrl = canvas.toDataURL('image/png');

            previewImage.src = dataUrl;
            previewImage.classList.remove('d-none');
            previewImage.setAttribute('data-filename', config.filenamePrefix + '-' + (patientName || 'preview').toString().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '') + '.png');
            fitCanvasToModal();
        }

        function loadTemplate(variant) {
            var config = getPreviewConfig(variant);
            if (templateImages[variant]) {
                return Promise.resolve(templateImages[variant]);
            }

            return new Promise(function (resolve, reject) {
                var img = new Image();
                img.onload = function () {
                    templateImages[variant] = img;
                    resolve(img);
                };
                img.onerror = function () {
                    reject(new Error('Template image not found at ' + config.templateUrl));
                };
                img.src = config.templateUrl + '?v=' + Date.now();
            });
        }

        function buildFittedText(ctx, text, maxWidth, maxHeight) {
            var content = (text || '').trim() || '-';
            var fontSize = 44;
            var minFontSize = 14;
            var fontFamily = 'Georgia, "Times New Roman", serif';
            var lineHeight = 1.2;
            var lines = [content];

            while (fontSize >= minFontSize) {
                ctx.font = 'bold ' + fontSize + 'px ' + fontFamily;

                if (ctx.measureText(content).width <= maxWidth && (fontSize * lineHeight) <= maxHeight) {
                    break;
                }

                fontSize -= 2;
            }

            return {
                fontSize: fontSize,
                lines: lines,
                lineHeight: fontSize * lineHeight,
                fontFamily: fontFamily
            };
        }

        function renderLebaranPreview(patientName, phoneNumber, variant) {
            var config = getPreviewConfig(variant);
            patientName = decodeHtmlEntities(patientName || '-');
            clearPreviewError();
            updateWaPanel(patientName, phoneNumber, variant);

            var canvas = document.getElementById('lebaranPreviewCanvas');
            var ctx = canvas.getContext('2d');

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            return loadTemplate(variant).then(function (img) {
                canvas.width = templateWidth;
                canvas.height = templateHeight;

                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                var box = {
                    x: canvas.width * 0.10,
                    y: canvas.height * 0.718,
                    width: canvas.width * 0.80,
                    height: canvas.height * 0.075
                };
                var displayName = patientName ? 'Bapak/Ibu ' + patientName : 'Bapak/Ibu -';
                var textColor = config.textColor || '#6f217c';
                var fitted = buildFittedText(ctx, displayName, box.width - 40, box.height - 20);
                var totalTextHeight = fitted.lines.length * fitted.lineHeight;
                var startY = box.y + ((box.height - totalTextHeight) / 2) + (fitted.fontSize * 0.9);

                ctx.font = 'bold ' + fitted.fontSize + 'px ' + fitted.fontFamily;
                ctx.fillStyle = textColor;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'alphabetic';

                fitted.lines.forEach(function (line, index) {
                    ctx.fillText(line, box.x + (box.width / 2), startY + (index * fitted.lineHeight));
                });

                updatePreviewImageFromCanvas(displayName || 'preview', variant);
            }).catch(function () {
                showPreviewError('Template image `' + config.templatePath + '` belum ditemukan. Tambahkan file tersebut agar preview bisa ditampilkan.');
            });
        }

        function fitCanvasToModal() {
            var previewImage = document.getElementById('lebaranPreviewImage');
            var $modalBody = $('#lebaranPreviewModal .modal-body');
            var availableWidth = Math.max($('#lebaranPreviewModal .col-lg-7').innerWidth() - 20, 260);
            var availableHeight = Math.max(window.innerHeight * 0.72, 320);
            var scale = Math.min(availableWidth / templateWidth, availableHeight / templateHeight, 1);

            previewImage.style.width = Math.floor(templateWidth * scale) + 'px';
            previewImage.style.height = Math.floor(templateHeight * scale) + 'px';
        }

        function reloadTablePreservingScroll() {
            var tableWrapper = document.querySelector('#lebaran-table_wrapper .table-responsive') || document.querySelector('.table-responsive');

            refreshScrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
            refreshTableScrollTop = tableWrapper ? tableWrapper.scrollTop : null;

            table.ajax.reload(function () {
                window.scrollTo(0, refreshScrollTop || 0);

                if (tableWrapper && refreshTableScrollTop !== null) {
                    tableWrapper.scrollTop = refreshTableScrollTop;
                }
            }, false);
        }

        var table = $('#lebaran-table').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '{{ route('events.lebaran.data') }}',
                data: function (d) {
                    d.status = $('#statusFilter').val() || 'pending';
                },
                dataSrc: 'data'
            },
            paging: false,
            columns: [
                { data: 'id', name: 'event_lebarans.id' },
                { data: 'nama_pasien', name: 'nama_pasien', defaultContent: '-' },
                { data: 'pasien_id', name: 'pasien_id', defaultContent: '-' },
                { data: 'nohp', name: 'nohp', defaultContent: '-' },
                {
                    data: 'status',
                    name: 'event_lebarans.status',
                    render: function (data) {
                        if (!data) {
                            return '<span class="badge badge-secondary">-</span>';
                        }

                        var normalized = String(data).toLowerCase();
                        var badgeClass = normalized === 'sent' ? 'success' : 'info';
                        return '<span class="badge badge-' + badgeClass + '">' + data + '</span>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        var patientName = row.nama_pasien || '-';
                        var phoneNumber = row.event_nohp || '';
                        var encodedPatientName = encodeURIComponent(patientName);
                        var encodedPhoneNumber = encodeURIComponent(phoneNumber);
                        return ''
                            + '<button type="button" class="btn btn-sm js-preview-lebaran mr-1 mb-1" style="background:#e83e8c;border:1px solid #e83e8c;color:#fff;border-radius:4px;padding:4px 10px;line-height:1.5;" data-variant="preview1" data-id="' + row.id + '" data-name="' + encodedPatientName + '" data-phone="' + encodedPhoneNumber + '">Preview</button>'
                            + '<button type="button" class="btn btn-sm js-preview-lebaran mb-1" style="background:#0d6efd;border:1px solid #0d6efd;color:#fff;border-radius:4px;padding:4px 10px;line-height:1.5;" data-variant="preview2" data-id="' + row.id + '" data-name="' + encodedPatientName + '" data-phone="' + encodedPhoneNumber + '">Preview 2</button>';
                    }
                }
            ],
            order: [[0, 'asc']]
        });

        $('#statusFilter').on('change', function () {
            reloadTablePreservingScroll();
        });

        $('#lebaran-table').on('click', '.js-preview-lebaran', function () {
            activeLebaranId = $(this).data('id') || null;
            activePreviewVariant = $(this).data('variant') || 'preview1';
            var patientName = decodeHtmlEntities(decodeURIComponent($(this).attr('data-name') || '-'));
            var phoneNumber = decodeURIComponent($(this).attr('data-phone') || '');
            updateMarkSentButtonState(false);
            $('#lebaranPreviewModal').modal('show');
            renderLebaranPreview(patientName, phoneNumber, activePreviewVariant);
        });

        $('#lebaranPreviewModal').on('hidden.bs.modal', function () {
            activeLebaranId = null;
            updateMarkSentButtonState(false);
            clearPreviewError();
            $('#lebaranPreviewImage').addClass('d-none').attr('src', '');
            $('#lebaranWaMessage').val('');
        });

        $('#copyLebaranWaMessage').on('click', function () {
            var textarea = document.getElementById('lebaranWaMessage');
            if (!textarea || !textarea.value) {
                return;
            }

            textarea.focus();
            textarea.select();
            document.execCommand('copy');
        });

        $('#copyLebaranImage').on('click', function () {
            copyPreviewImageToClipboard().then(function (result) {
                $('#lebaranWaHelp').text(result.message);
            });
        });

        $('#openLebaranWaLink').on('click', function (event) {
            var href = $(this).attr('href');

            if ($(this).hasClass('disabled') || !href || href === '#') {
                event.preventDefault();
                return;
            }

            event.preventDefault();

            var popup = window.open('about:blank', '_blank');

            copyPreviewImageToClipboard().then(function (result) {
                $('#lebaranWaHelp').text(result.message);

                if (popup) {
                    popup.location.href = href;
                } else {
                    window.open(href, '_blank');
                }
            });
        });

        $('#lebaranPreviewModal').on('shown.bs.modal', function () {
            fitCanvasToModal();
        });

        $('#lebaranPreviewImage').on('dragstart', function (event) {
            var nativeEvent = event.originalEvent;
            var src = this.src;
            var filename = $(this).attr('data-filename') || 'lebaran-preview.png';

            if (!src || !nativeEvent || !nativeEvent.dataTransfer) {
                return;
            }

            nativeEvent.dataTransfer.effectAllowed = 'copy';
            nativeEvent.dataTransfer.setData('text/uri-list', src);
            nativeEvent.dataTransfer.setData('text/plain', src);
            nativeEvent.dataTransfer.setData('DownloadURL', 'image/png:' + filename + ':' + src);
        });

        $('#markLebaranSentBtn').on('click', function () {
            if (!activeLebaranId) {
                return;
            }

            updateMarkSentButtonState(true);

            $.ajax({
                url: '{{ url('/events/lebaran') }}/' + activeLebaranId + '/mark-sent',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            }).done(function () {
                $('#lebaranPreviewModal').modal('hide');
                reloadTablePreservingScroll();
            }).fail(function () {
                showPreviewError('Gagal mengubah status menjadi sent. Coba lagi.');
            }).always(function () {
                updateMarkSentButtonState(false);
            });
        });

        $(window).on('resize', function () {
            if ($('#lebaranPreviewModal').hasClass('show') && !$('#lebaranPreviewImage').hasClass('d-none')) {
                fitCanvasToModal();
            }
        });

        setInterval(function () {
            reloadTablePreservingScroll();
        }, 5000);
    });
</script>
@endpush

@push('styles')
<style>
    .lebaran-import-box {
        padding: 0.9rem 1rem;
        border: 1px solid #e9ecef;
        border-radius: 0.35rem;
        background: #fff;
    }

    .lebaran-import-box .form-text {
        font-size: 0.8rem;
        line-height: 1.35;
    }

    .lebaran-import-box .custom-file-label {
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .lebaran-import-box .btn {
        min-height: calc(1.5em + 0.75rem + 2px);
    }

    #lebaran-table .btn-lebaran-preview,
    #lebaran-table_wrapper .btn-lebaran-preview {
        display: inline-block !important;
        color: #fff !important;
        background-color: #e83e8c !important;
        border: 1px solid #e83e8c !important;
        border-radius: 0.2rem !important;
        padding: 0.25rem 0.5rem !important;
        line-height: 1.5 !important;
        text-align: center !important;
        text-decoration: none !important;
    }

    #lebaran-table .btn-lebaran-preview:hover,
    #lebaran-table .btn-lebaran-preview:focus,
    #lebaran-table .btn-lebaran-preview:active,
    #lebaran-table_wrapper .btn-lebaran-preview:hover,
    #lebaran-table_wrapper .btn-lebaran-preview:focus,
    #lebaran-table_wrapper .btn-lebaran-preview:active {
        color: #fff !important;
        background-color: #d63384 !important;
        border-color: #d63384 !important;
    }

    #lebaran-table .btn-lebaran-preview-2,
    #lebaran-table_wrapper .btn-lebaran-preview-2 {
        display: inline-block !important;
        color: #fff !important;
        background-color: #0d6efd !important;
        border: 1px solid #0d6efd !important;
        border-radius: 0.2rem !important;
        padding: 0.25rem 0.5rem !important;
        line-height: 1.5 !important;
        text-align: center !important;
        text-decoration: none !important;
    }

    #lebaran-table .btn-lebaran-preview-2:hover,
    #lebaran-table .btn-lebaran-preview-2:focus,
    #lebaran-table .btn-lebaran-preview-2:active,
    #lebaran-table_wrapper .btn-lebaran-preview-2:hover,
    #lebaran-table_wrapper .btn-lebaran-preview-2:focus,
    #lebaran-table_wrapper .btn-lebaran-preview-2:active {
        color: #fff !important;
        background-color: #0b5ed7 !important;
        border-color: #0b5ed7 !important;
    }

    .lebaran-import-box .custom-file,
    .lebaran-import-box .btn {
        margin-bottom: 0;
    }

    #lebaran-table_wrapper .dataTables_length,
    #lebaran-table_wrapper .dataTables_filter {
        margin-bottom: 1rem;
    }

    #lebaran-table_wrapper .dataTables_filter {
        text-align: right;
    }

    #lebaran-table_wrapper .dataTables_filter input {
        min-width: 220px;
    }

    .lebaran-preview-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        min-height: 200px;
        max-height: 72vh;
        overflow: hidden;
    }

    .lebaran-preview-canvas {
        display: block;
        width: auto;
        max-width: 100%;
        height: auto;
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.15);
        background: #f8f9fa;
    }

    .lebaran-preview-dialog {
        max-width: 1200px;
    }

    #lebaranPreviewModal .modal-body {
        overflow: hidden;
        padding: 1rem;
    }

    .lebaran-wa-panel {
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    #lebaranWaMessage {
        min-height: 520px;
        resize: none;
    }

    .lebaran-wa-actions .btn,
    .lebaran-wa-actions a.btn {
        white-space: nowrap;
    }

    @media (max-width: 767.98px) {
        .lebaran-preview-dialog {
            max-width: calc(100vw - 1rem);
            margin: 0.5rem auto;
        }

        .lebaran-preview-wrapper {
            max-height: 65vh;
        }

        #lebaranWaMessage {
            min-height: 260px;
        }

        .lebaran-preview-canvas {
            max-width: 100%;
        }
    }

    @media (max-width: 575.98px) {
        .lebaran-import-box {
            padding: 0.75rem;
        }

        .lebaran-import-box .form-text {
            margin-top: 0.5rem;
        }

        #lebaran-table_wrapper .dataTables_filter {
            text-align: left;
        }

        #lebaran-table_wrapper .dataTables_filter input {
            width: 100%;
            min-width: 0;
            margin-left: 0;
        }
    }
</style>
@endpush