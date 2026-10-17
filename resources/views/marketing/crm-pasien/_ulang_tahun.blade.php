{{-- Tab "Ulang Tahun": patient birthdays + WhatsApp greeting card; a sent greeting is also logged as a follow up --}}
<div class="card">
    <div class="card-body">
        <div class="crm-toolbar mb-3">
            <div class="crm-field crm-field-chips">
                <label>Periode</label>
                <div class="crm-chips" id="birthdayQuickRange">
                    <button type="button" class="btn btn-sm btn-primary" data-range="today">Hari ini</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-range="tomorrow">Besok</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-range="week">7 hari ke depan</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-range="month">Bulan ini</button>
                </div>
            </div>
            <div class="crm-field">
                <label for="date-range">Atau pilih tanggal</label>
                <input type="text" class="form-control" id="date-range" readonly>
            </div>
            <div class="crm-field">
                <label for="birthday-status-filter">Status ucapan</label>
                <select id="birthday-status-filter" class="form-control">
                    <option value="">Semua</option>
                    <option value="belum">Belum diucapkan</option>
                    <option value="sudah">Sudah diucapkan</option>
                </select>
            </div>
            <div class="crm-field">
                <label for="klinik-filter">Pernah ke klinik</label>
                <select id="klinik-filter" class="form-control">
                    <option value="">Semua klinik</option>
                    @foreach($kliniks as $klinik)
                        <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="crm-actions">
                <button type="button" class="btn btn-outline-primary" id="cardTemplateBtn"><i class="mdi mdi-image-edit-outline mr-1"></i>Template Kartu</button>
            </div>
        </div>

        <table id="birthday-table" class="table table-hover crm-table w-100">
            <thead>
                <tr>
                    <th>Ulang Tahun</th>
                    <th>Pasien</th>
                    <th>Tanggal Lahir</th>
                    <th>Usia</th>
                    <th>No HP</th>
                    <th>Klinik Terakhir</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@push('crm-modals')
<div class="modal fade" id="greetingModal" tabindex="-1" role="dialog" aria-labelledby="greetingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="greetingModalLabel">Ucapkan Selamat Ulang Tahun</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <form id="greetingForm" onsubmit="return false;">
                            <input type="hidden" id="pasien-id">
                            <input type="hidden" id="pasien-age">
                            <div class="form-group">
                                <label for="patient-name">Nama Pasien</label>
                                <input type="text" class="form-control" id="patient-name" readonly>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-6">
                                    <label for="greeting-prefix">Sapaan</label>
                                    <select class="form-control" id="greeting-prefix">
                                        <option value="Bapak">Bapak</option>
                                        <option value="Ibu">Ibu</option>
                                        <option value="Kakak">Kakak</option>
                                        <option value="Adik">Adik</option>
                                        <option value="">Tanpa sapaan</option>
                                    </select>
                                </div>
                                <div class="form-group col-6">
                                    <label for="phone-number">Nomor WhatsApp</label>
                                    <input type="text" class="form-control" id="phone-number" placeholder="08123456789">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="greeting-message">Pesan Ucapan</label>
                                <textarea class="form-control" id="greeting-message" rows="5"></textarea>
                            </div>
                            <div class="form-group mb-0">
                                <label for="greeting-klinik">Template kartu (klinik)</label>
                                <select class="form-control" id="greeting-klinik">
                                    <option value="">Pilih klinik</option>
                                    @foreach($kliniks as $klinik)
                                        <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6 mt-3 mt-md-0">
                        <div class="border rounded p-3 text-center h-100 d-flex flex-column justify-content-center">
                            <div id="image-preview" class="mb-3">
                                <p class="text-muted mb-0">Kartu ucapan (opsional): pilih template lalu klik Generate.</p>
                            </div>
                            <div class="d-flex justify-content-center flex-wrap" style="gap:8px;">
                                <button type="button" class="btn btn-outline-info" id="generate-image">
                                    <i class="mdi mdi-image mr-1"></i>Generate Kartu
                                </button>
                                <a href="#" class="btn btn-outline-success" id="download-image" target="_blank" style="display:none;">
                                    <i class="mdi mdi-download mr-1"></i>Download
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <small class="text-muted mr-auto">Ucapan yang dikirim otomatis tercatat di Follow Up.</small>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="send-whatsapp">
                    <i class="fab fa-whatsapp mr-1"></i>Kirim via WhatsApp
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Template Kartu: the card image per klinik and where the name / age / date go on it --}}
<div class="modal fade" id="cardTemplateModal" tabindex="-1" role="dialog" aria-labelledby="cardTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cardTemplateModalLabel">Template Kartu Ulang Tahun</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="crm-chips mb-3" id="tplTabs" role="tablist"></div>
                <div class="row">
                    <div class="col-lg-7 mb-3 mb-lg-0">
                        <div class="crm-tpl-preview" id="tplPreview" title="Klik untuk menaruh nama di titik ini">
                            <div class="text-muted"><i class="mdi mdi-loading mdi-spin"></i> Memuat...</div>
                        </div>
                        <small class="text-muted d-block mt-1">Preview dengan contoh nama. Klik pada gambar untuk memindahkan posisi nama.</small>
                    </div>
                    <div class="col-lg-5">
                        <div class="alert alert-info py-2 small d-none" id="tplUsesDefault">
                            Klinik ini belum punya template sendiri, kartunya memakai template <b>Default</b>.
                            Upload gambar untuk membuat template khusus klinik ini.
                        </div>
                        <div class="small text-muted mb-2" id="tplStatus"></div>
                        <div class="form-group">
                            <label class="d-block">Gambar template</label>
                            <input type="file" id="tplImage" accept="image/jpeg,image/png,image/webp" class="d-none">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="tplImageBtn"><i class="mdi mdi-upload mr-1"></i>Upload / ganti gambar</button>
                            <small class="text-muted d-block mt-1" id="tplImageInfo">JPG / PNG, maks 5 MB. Ukuran ideal 1600 × 900 px.</small>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-4">
                                <label for="tplColor">Warna teks</label>
                                <input type="color" class="form-control p-1" id="tplColor" value="#ffffff">
                            </div>
                            <div class="form-group col-8">
                                <label class="d-block">Rata teks</label>
                                <div class="btn-group btn-group-toggle w-100" data-toggle="buttons" id="tplAlign">
                                    <label class="btn btn-outline-secondary btn-sm"><input type="radio" name="tplAlign" value="left">Kiri</label>
                                    <label class="btn btn-outline-secondary btn-sm"><input type="radio" name="tplAlign" value="center">Tengah</label>
                                    <label class="btn btn-outline-secondary btn-sm"><input type="radio" name="tplAlign" value="right">Kanan</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="tplX" class="d-flex">Posisi kiri–kanan <span class="ml-auto text-muted" id="tplXVal"></span></label>
                            <input type="range" class="custom-range" id="tplX" min="0" max="100" step="0.5">
                        </div>
                        <div class="form-group">
                            <label for="tplY" class="d-flex">Posisi atas–bawah <span class="ml-auto text-muted" id="tplYVal"></span></label>
                            <input type="range" class="custom-range" id="tplY" min="0" max="100" step="0.5">
                        </div>
                        <div class="form-group">
                            <label for="tplSize" class="d-flex">Ukuran nama <span class="ml-auto text-muted" id="tplSizeVal"></span></label>
                            <input type="range" class="custom-range" id="tplSize" min="16" max="200" step="1">
                        </div>
                        <div class="d-flex flex-wrap" style="gap:16px;">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="tplShowAge">
                                <label class="custom-control-label" for="tplShowAge">Tampilkan usia</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="tplShowDate">
                                <label class="custom-control-label" for="tplShowDate">Tampilkan tanggal</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger mr-auto" id="tplReset"><i class="mdi mdi-restore mr-1"></i>Kembalikan ke bawaan</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="tplSave">Simpan template</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
window.crmPasien.inits['ulang-tahun'] = function () {
    var crm = window.crmPasien;
    var today = moment(@json($today), 'YYYY-MM-DD');
    var range = { start: today.clone(), end: today.clone() };
    var current = null; // row being greeted

    var quickRanges = {
        today: function () { return [today.clone(), today.clone()]; },
        tomorrow: function () { return [today.clone().add(1, 'day'), today.clone().add(1, 'day')]; },
        week: function () { return [today.clone(), today.clone().add(6, 'days')]; },
        month: function () { return [today.clone().startOf('month'), today.clone().endOf('month')]; }
    };

    function setRange(start, end, quickKey) {
        range = { start: start, end: end };
        var picker = $('#date-range').data('daterangepicker');
        picker.setStartDate(start);
        picker.setEndDate(end);
        $('#birthdayQuickRange .btn').each(function () {
            var on = $(this).data('range') === quickKey;
            $(this).toggleClass('btn-primary', on).toggleClass('btn-outline-primary', !on);
        });
        birthdayTable.ajax.reload();
    }

    $('#date-range').daterangepicker({
        startDate: range.start,
        endDate: range.end,
        opens: 'left',
        autoApply: true,
        locale: { format: 'DD/MM/YYYY', firstDay: 1 }
    }, function (start, end) {
        setRange(start, end, null);
    });

    var birthdayTable = $('#birthday-table').DataTable({
        processing: true,
        serverSide: true,
        order: [], // server order: next birthday first
        pageLength: 25,
        language: { search: 'Search:', searchPlaceholder: 'Nama / No RM', processing: 'Memuat...', emptyTable: 'Tidak ada pasien yang berulang tahun di periode ini', zeroRecords: 'Pasien tidak ditemukan' },
        ajax: {
            url: "{{ route('marketing.birthday.data') }}",
            data: function (d) {
                d.start_date = range.start.format('YYYY-MM-DD');
                d.end_date = range.end.format('YYYY-MM-DD');
                d.klinik_id = $('#klinik-filter').val();
                d.status = $('#birthday-status-filter').val();
            }
        },
        // Server fields are HTML-escaped
        columns: [
            { data: 'kapan', orderable: false, className: 'crm-nowrap', render: function (data, type, row) {
                return row.is_today ? '<span class="badge badge-danger"><i class="fas fa-birthday-cake mr-1"></i>Hari ini</span>' : data;
            } },
            { data: 'nama', orderable: false, render: function (data, type, row) {
                return '<div class="crm-person-name">' + (data || '-') + '</div><div class="crm-muted-id">' + row.id + '</div>';
            } },
            { data: 'tanggal', orderable: false, searchable: false, className: 'crm-nowrap' },
            { data: 'usia', orderable: false, searchable: false, className: 'crm-nowrap', render: function (data) { return data >= 0 ? data + ' th' : '<span class="text-muted" title="Tanggal lahir tidak valid">-</span>'; } },
            { data: 'no_hp', orderable: false, className: 'crm-nowrap', render: function (data) { return crm.waLink(data); } },
            { data: 'klinik', orderable: false, searchable: false, className: 'crm-wrap' },
            { data: 'greeted', orderable: false, searchable: false, render: function (data, type, row) {
                if (!data) return '<span class="badge badge-warning">Belum diucapkan</span>';
                return '<span class="badge badge-success">Sudah diucapkan</span>'
                    + '<div class="small text-muted">' + (row.greeted_by || '-') + ', ' + (row.greeted_at || '') + '</div>';
            } },
            { data: null, orderable: false, searchable: false, className: 'crm-actions-cell', render: function (data, type, row) {
                var greet = row.greeted
                    ? '<button type="button" class="btn btn-sm btn-outline-secondary" disabled><i class="fas fa-check mr-1"></i>Sudah</button>'
                    : '<button type="button" class="btn btn-sm btn-success send-greeting"><i class="fas fa-gift mr-1"></i>Ucapkan</button>';
                return greet + ' <button type="button" class="btn btn-sm btn-outline-primary bday-followup"><i class="fas fa-phone-alt mr-1"></i>Follow Up</button>';
            } }
        ]
    });

    $('#birthdayQuickRange').on('click', '.btn', function () {
        var key = $(this).data('range');
        var r = quickRanges[key]();
        setRange(r[0], r[1], key);
    });
    $('#klinik-filter, #birthday-status-filter').on('change', function () {
        birthdayTable.ajax.reload();
    });

    function plain(html) {
        return $('<div>').html(html || '').text();
    }

    function resetImagePreview() {
        $('#image-preview').html('<p class="text-muted mb-0">Kartu ucapan (opsional): pilih template lalu klik Generate.</p>');
        $('#download-image').hide().attr('href', '#').removeAttr('download');
    }

    function updateGreetingMessage() {
        var prefix = $('#greeting-prefix').val();
        var name = $('#patient-name').val();
        $('#greeting-message').val('Selamat Ulang Tahun, ' + (prefix ? prefix + ' ' : '') + name
            + '! Semoga panjang umur, sehat selalu, dan bahagia. Terima kasih telah menjadi bagian dari keluarga kami.');
    }

    $('#birthday-table').on('click', '.send-greeting', function () {
        current = birthdayTable.row($(this).closest('tr')).data();
        if (!current) return;
        resetImagePreview();
        $('#pasien-id').val(current.id);
        $('#pasien-age').val(current.usia);
        var nama = plain(current.nama);
        $('#patient-name').val(nama);
        $('#phone-number').val(plain(current.no_hp));
        // A name that already carries a title ("NY. IDA", "BP. FENDY") gets no extra sapaan
        $('#greeting-prefix').val(/^(NY|NN|NONA|TN|BP|BPK|BAPAK|IBU|IB|AN|SDR|SDRI|KAK|DR|DRG|APT|HJ|H)\.?\s/i.test(nama) ? '' : current.prefix);
        // Template follows the last visit's clinic, still changeable
        $('#greeting-klinik').val(current.klinik_id || '');
        updateGreetingMessage();
        $('#greetingModal').modal('show');
    });

    $('#birthday-table').on('click', '.bday-followup', function () {
        var row = birthdayTable.row($(this).closest('tr')).data();
        if (row) crm.openFollowUp({ id: row.id, nama: plain(row.nama) });
    });

    $('#greeting-prefix').on('change', updateGreetingMessage);
    $('#greeting-klinik').on('change', resetImagePreview);

    $('#send-whatsapp').on('click', function () {
        var phoneNumber = crm.waNumber($('#phone-number').val());
        var message = $('#greeting-message').val();
        if (phoneNumber.length < 9) {
            Swal.fire('Nomor belum valid', 'Isi nomor WhatsApp pasien terlebih dahulu.', 'warning');
            return;
        }
        // Opened right in the click so the browser does not block it as a popup
        window.open('https://wa.me/' + phoneNumber + '?text=' + encodeURIComponent(message), '_blank');

        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: "{{ route('marketing.birthday.mark-sent') }}",
            type: 'POST',
            data: { pasien_id: $('#pasien-id').val(), message: message },
            success: function () {
                $('#greetingModal').modal('hide');
                if (current && current.is_today) {
                    var $badge = $('#crmBirthdayBadge');
                    var left = (parseInt($badge.text(), 10) || 0) - 1;
                    left > 0 ? $badge.text(left) : $badge.remove();
                }
                birthdayTable.ajax.reload(null, false);
                // The greeting is logged as a follow up
                crm.reload('followup');
                crm.reload('pasien');
            },
            error: function () {
                Swal.fire('Gagal mencatat', 'WhatsApp sudah dibuka, tapi ucapan belum tercatat. Coba klik Kirim lagi.', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });

    $('#generate-image').on('click', function () {
        var name = $('#patient-name').val();
        var klinikId = $('#greeting-klinik').val();
        if (!klinikId) {
            Swal.fire('Pilih template', 'Pilih klinik untuk template kartu terlebih dahulu.', 'info');
            return;
        }

        var $btn = $(this).prop('disabled', true);
        $('#image-preview').html('<div class="text-muted"><i class="mdi mdi-loading mdi-spin" style="font-size:2rem;"></i><p class="mb-0">Membuat kartu...</p></div>');
        $.ajax({
            url: "{{ route('marketing.birthday.generate-image') }}",
            type: 'POST',
            data: {
                name: name,
                prefix: $('#greeting-prefix').val(),
                age: $('#pasien-age').val(),
                klinik_id: klinikId
            },
            success: function (response) {
                if (!response.success) return;
                $('#image-preview').html($('<img class="img-fluid rounded" alt="Kartu ucapan">').attr('src', response.image_url));
                $('#download-image').show()
                    .attr('href', response.image_url)
                    .attr('download', 'ulang_tahun_' + name.replace(/\s+/g, '_') + '.jpg');
            },
            error: function () {
                $('#image-preview').html('<p class="text-danger mb-0">Gagal membuat kartu. Silakan coba lagi.</p>');
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });

    // ---- Template Kartu editor ----
    var tpl = { data: null, key: 'default', file: null, timer: null, request: null };

    function tplSettings() {
        return {
            color: $('#tplColor').val(),
            x: $('#tplX').val(),
            y: $('#tplY').val(),
            size: $('#tplSize').val(),
            align: $('input[name=tplAlign]:checked').val() || 'center',
            show_age: $('#tplShowAge').is(':checked') ? 1 : 0,
            show_date: $('#tplShowDate').is(':checked') ? 1 : 0
        };
    }

    function tplLabels() {
        $('#tplXVal').text($('#tplX').val() + '%');
        $('#tplYVal').text($('#tplY').val() + '%');
        $('#tplSizeVal').text($('#tplSize').val());
    }

    function tplTabs() {
        var tabs = [{ id: 'default', html: '<i class="mdi mdi-star-outline mr-1"></i>Default' }].concat(tpl.data.kliniks.map(function (k) {
            return { id: k.id, html: crm.klinikLabel(crm.esc(k.nama), k.color, k.logo) };
        }));
        $('#tplTabs').html(tabs.map(function (t) {
            var on = t.id === tpl.key;
            return '<button type="button" class="btn btn-sm ' + (on ? 'btn-primary' : 'btn-outline-secondary') + '" data-key="' + crm.esc(t.id) + '" role="tab" aria-selected="' + on + '">' + t.html + '</button>';
        }).join(''));
    }

    // Loads the chosen template into the form; a klinik without its own starts from "default"
    function tplSelect(key) {
        tpl.key = key;
        tpl.file = null;
        $('#tplImage').val('');
        var own = tpl.data.templates[key];
        var t = own || tpl.data.templates['default'];
        var s = t.settings;
        $('#tplColor').val(s.color);
        $('#tplX').val(s.x);
        $('#tplY').val(s.y);
        $('#tplSize').val(s.size);
        $('#tplAlign input').each(function () {
            var on = this.value === s.align;
            $(this).prop('checked', on).closest('label').toggleClass('active', on);
        });
        $('#tplShowAge').prop('checked', !!s.show_age);
        $('#tplShowDate').prop('checked', !!s.show_date);
        $('#tplUsesDefault').toggleClass('d-none', !!own || key === 'default');
        $('#tplStatus').text(own ? (own.custom ? 'Template yang sudah diatur.' : 'Template bawaan sistem.') : '');
        $('#tplReset').toggle(!!(own && own.custom));
        $('#tplImageInfo').text('JPG / PNG, maks 5 MB. Ukuran ideal 1600 × 900 px.');
        tplLabels();
        tplTabs();
        tplPreview();
    }

    // Redraws the preview (debounced; a newer change cancels the running request)
    function tplPreview(delay) {
        clearTimeout(tpl.timer);
        tpl.timer = setTimeout(function () {
            if (tpl.request) tpl.request.abort();
            var form = new FormData();
            $.each(tplSettings(), function (k, v) { form.append(k, v); });
            form.append('key', tpl.key);
            if (tpl.file) form.append('image', tpl.file);
            $('#tplPreview').addClass('is-loading');
            tpl.request = $.ajax({
                url: "{{ route('marketing.birthday.templates.preview') }}",
                type: 'POST', data: form, processData: false, contentType: false,
                success: function (res) {
                    $('#tplPreview').html($('<img alt="Preview kartu">').attr('src', res.image));
                },
                error: function (xhr) {
                    if (xhr.statusText === 'abort') return;
                    $('#tplPreview').html($('<div class="text-danger small">').text((xhr.responseJSON && xhr.responseJSON.message) || 'Preview gagal dibuat.'));
                },
                complete: function () { $('#tplPreview').removeClass('is-loading'); }
            });
        }, delay || 0);
    }

    $('#cardTemplateBtn').on('click', function () {
        $('#cardTemplateModal').modal('show');
        $('#tplPreview').html('<div class="text-muted"><i class="mdi mdi-loading mdi-spin"></i> Memuat...</div>');
        $.get("{{ route('marketing.birthday.templates') }}", function (res) {
            tpl.data = res;
            tplSelect('default');
        }).fail(function () {
            Swal.fire('Error', 'Gagal memuat template kartu', 'error');
        });
    });

    $('#tplTabs').on('click', '.btn', function () { tplSelect(String($(this).data('key'))); });
    $('#tplX, #tplY, #tplSize').on('input', function () { tplLabels(); tplPreview(350); });
    $('#tplColor, #tplShowAge, #tplShowDate').on('change', function () { tplPreview(150); });
    $('#tplAlign').on('change', 'input', function () { tplPreview(150); });
    $('#tplImageBtn').on('click', function () { $('#tplImage').trigger('click'); });
    $('#tplImage').on('change', function () {
        var file = this.files && this.files[0];
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) {
            Swal.fire('Terlalu besar', 'Gambar maksimal 5 MB.', 'warning');
            this.value = '';
            return;
        }
        tpl.file = file;
        $('#tplImageInfo').text('Gambar baru: ' + file.name + ' (belum disimpan)');
        tplPreview();
    });

    // Click on the preview = put the name there
    $('#tplPreview').on('click', 'img', function (e) {
        var rect = this.getBoundingClientRect();
        $('#tplX').val(((e.clientX - rect.left) / rect.width * 100).toFixed(1));
        $('#tplY').val(((e.clientY - rect.top) / rect.height * 100).toFixed(1));
        tplLabels();
        tplPreview();
    });

    $('#tplSave').on('click', function () {
        if (!tpl.data) return;
        if (!tpl.data.templates[tpl.key] && !tpl.file) {
            Swal.fire('Upload gambar', 'Klinik ini belum punya gambar template. Upload gambar dulu.', 'info');
            return;
        }
        var form = new FormData();
        $.each(tplSettings(), function (k, v) { form.append(k, v); });
        if (tpl.file) form.append('image', tpl.file);
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: "{{ url('marketing/birthday/templates') }}/" + encodeURIComponent(tpl.key),
            type: 'POST', data: form, processData: false, contentType: false,
            success: function (res) {
                Swal.fire({ icon: 'success', title: res.message, timer: 1300, showConfirmButton: false });
                $.get("{{ route('marketing.birthday.templates') }}", function (data) {
                    tpl.data = data;
                    tplSelect(tpl.key);
                });
            },
            error: function (xhr) {
                var errors = xhr.responseJSON && xhr.responseJSON.errors;
                Swal.fire('Gagal menyimpan', errors ? Object.values(errors).join('<br>') : ((xhr.responseJSON && xhr.responseJSON.message) || 'Terjadi kesalahan'), 'error');
            },
            complete: function () { $btn.prop('disabled', false); }
        });
    });

    $('#tplReset').on('click', function () {
        Swal.fire({
            title: 'Kembalikan ke bawaan?',
            text: tpl.key === 'default' ? 'Template Default kembali ke gambar bawaan sistem.' : 'Template klinik ini dihapus; kartunya kembali ke template bawaan.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, kembalikan', cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.value) return;
            $.ajax({
                url: "{{ url('marketing/birthday/templates') }}/" + encodeURIComponent(tpl.key),
                type: 'DELETE',
                success: function (res) {
                    Swal.fire({ icon: 'success', title: res.message, timer: 1300, showConfirmButton: false });
                    $.get("{{ route('marketing.birthday.templates') }}", function (data) {
                        tpl.data = data;
                        tplSelect(tpl.key);
                    });
                },
                error: function () { Swal.fire('Error', 'Gagal mengembalikan template', 'error'); }
            });
        });
    });

    return birthdayTable;
};
</script>
@endpush
