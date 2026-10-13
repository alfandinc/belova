{{-- Tab "Follow Up": follow up log with stats; its form is also opened from the Pasien tab and the page header --}}
@php
    $followUpKategori = ['Produk' => 'primary', 'Perawatan' => 'pink', 'Reseller' => 'warning', 'Slimming' => 'purple', 'Ulang Tahun' => 'info'];
@endphp
<div class="card">
    <div class="card-body">
        <div class="crm-toolbar mb-3">
            <div class="crm-field crm-field-wide">
                <label for="dateRange">Tanggal follow up</label>
                <input type="text" id="dateRange" class="form-control" autocomplete="off" readonly>
            </div>
            <div class="crm-field crm-field-wide">
                <label for="kategoriFilter">Kategori</label>
                <select id="kategoriFilter" class="form-control" multiple="multiple" style="width:100%">
                    @foreach($followUpKategori as $kategori => $color)
                        <option value="{{ $kategori }}">{{ $kategori }}</option>
                    @endforeach
                </select>
            </div>
            <div class="crm-field">
                <label for="statusResponFilter">Status respon</label>
                <select id="statusResponFilter" class="form-control">
                    <option value="">Semua</option>
                    <option value="Direspon">Direspon</option>
                    <option value="Tidak Direspon">Tidak Direspon</option>
                    <option value="Menunggu">Menunggu respon</option>
                </select>
            </div>
            <div class="crm-field">
                <label for="statusBookingFilter">Status booking</label>
                <select id="statusBookingFilter" class="form-control">
                    <option value="">Semua</option>
                    <option value="Sukses">Sukses</option>
                    <option value="Menunggu">Menunggu</option>
                    <option value="Batal">Batal</option>
                </select>
            </div>
            <div class="crm-actions">
                <button type="button" class="btn btn-outline-secondary" id="followupResetFilter"><i class="fas fa-undo mr-1"></i>Reset</button>
            </div>
        </div>

        {{-- Stats of the chosen dates; clicking one filters the table --}}
        <div class="row mb-3 crm-stats-row">
            <div class="col-6 col-md-4 col-xl">
                <div class="card crm-stat"><div class="card-body">
                    <div class="crm-stat-label">Total follow up</div>
                    <div class="crm-stat-value text-primary" id="followUpCount">0</div>
                    <div class="crm-stat-sub" id="followUpRangeLabel">hari ini</div>
                </div></div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="card crm-stat" data-filter="status_respon" data-value="Direspon"><div class="card-body">
                    <div class="crm-stat-label">Direspon</div>
                    <div class="crm-stat-value text-success" id="followUpDirespon">0</div>
                    <div class="crm-stat-sub" id="followUpDiresponPercent">0%</div>
                </div></div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="card crm-stat" data-filter="status_respon" data-value="Tidak Direspon"><div class="card-body">
                    <div class="crm-stat-label">Tidak direspon</div>
                    <div class="crm-stat-value text-danger" id="followUpTidakDirespon">0</div>
                    <div class="crm-stat-sub" id="followUpTidakDiresponPercent">0%</div>
                </div></div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="card crm-stat" data-filter="status_respon" data-value="Menunggu"><div class="card-body">
                    <div class="crm-stat-label">Menunggu respon</div>
                    <div class="crm-stat-value text-warning" id="followUpMenungguRespon">0</div>
                    <div class="crm-stat-sub" id="followUpMenungguResponPercent">0%</div>
                </div></div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="card crm-stat" data-filter="status_booking" data-value="Sukses"><div class="card-body">
                    <div class="crm-stat-label">Booking sukses</div>
                    <div class="crm-stat-value text-success" id="bookingSukses">0</div>
                    <div class="crm-stat-sub">Menunggu <b id="bookingMenunggu">0</b> · Batal <b id="bookingBatal">0</b></div>
                </div></div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="card crm-stat"><div class="card-body">
                    <div class="crm-stat-label mb-1">Per kategori</div>
                    <div class="crm-chips" id="kategoriStats"></div>
                </div></div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="card crm-stat"><div class="card-body">
                    <div class="crm-stat-label mb-1">Top sales</div>
                    <ul id="topSalesList" class="list-unstyled mb-0 small crm-top-sales"></ul>
                </div></div>
            </div>
        </div>

        <table class="table table-hover crm-table w-100" id="followupTable">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Pasien</th>
                    <th>Kategori</th>
                    <th>Sales</th>
                    <th>Respon</th>
                    <th>Booking</th>
                    <th>Rencana / Catatan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@push('crm-modals')
<div class="modal fade" id="followupModal" tabindex="-1" role="dialog" aria-labelledby="followupModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form id="followupForm" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="followupModalLabel">Tambah Follow Up</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body" style="max-height:70vh; overflow-y:auto;">
          <input type="hidden" id="followup_id" name="followup_id">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="pasienSelect">Pasien <span class="text-danger">*</span></label>
                <select id="pasienSelect" name="pasien_id" class="form-control" style="width:100%"></select>
                <small class="form-text text-muted">Ketik minimal 2 huruf nama atau No RM.</small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="d-block">Kategori <span class="text-danger">*</span> <small class="text-muted">(boleh lebih dari satu)</small></label>
                <div class="btn-group-toggle crm-chips" data-toggle="buttons" id="kategoriGroup">
                  @foreach($followUpKategori as $kategori => $color)
                      <label class="btn btn-sm crm-chip-toggle" style="--chip: {{ ['primary' => '#1e88e5', 'pink' => '#e83e8c', 'warning' => '#f5a623', 'purple' => '#6f42c1', 'info' => '#17a2b8'][$color] }};">
                          <input type="checkbox" name="kategori[]" value="{{ $kategori }}" autocomplete="off">{{ $kategori }}
                      </label>
                  @endforeach
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="salesSelect">Sales</label>
                <select id="salesSelect" name="sales_id" class="form-control" style="width:100%"></select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="d-block">Status Respon <span class="text-danger">*</span></label>
                <div class="btn-group btn-group-toggle w-100" data-toggle="buttons" id="statusResponGroup">
                  <label class="btn btn-outline-success"><input type="radio" name="status_respon" value="Direspon" autocomplete="off">Direspon</label>
                  <label class="btn btn-outline-danger"><input type="radio" name="status_respon" value="Tidak Direspon" autocomplete="off">Tidak Direspon</label>
                  <label class="btn btn-outline-warning"><input type="radio" name="status_respon" value="Menunggu" autocomplete="off">Menunggu</label>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="d-block">Status Booking</label>
                <div class="btn-group btn-group-toggle w-100" data-toggle="buttons" id="statusBookingGroup">
                  <label class="btn btn-outline-success"><input type="radio" name="status_booking" value="Sukses" autocomplete="off">Sukses</label>
                  <label class="btn btn-outline-warning"><input type="radio" name="status_booking" value="Menunggu" autocomplete="off">Menunggu</label>
                  <label class="btn btn-outline-danger"><input type="radio" name="status_booking" value="Batal" autocomplete="off">Batal</label>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="bukti_respon">Bukti Respon (screenshot)</label>
                {{-- Paste (Ctrl+V anywhere in the form), drop, or click to pick; the image ends up in the file input --}}
                <input type="file" class="d-none" name="bukti_respon" id="bukti_respon" accept="image/*">
                <div class="crm-paste-zone" id="buktiZone" tabindex="0" role="button" aria-label="Tempel, seret, atau pilih gambar bukti">
                  <div id="buktiEmpty">
                    <i class="fas fa-paste mb-1" style="font-size:1.3rem;opacity:.6;"></i>
                    <div><b>Tempel gambar (Ctrl+V)</b></div>
                    <div class="small text-muted">atau seret ke sini / klik untuk pilih file</div>
                  </div>
                  <div id="buktiPreview" class="d-none"></div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                  <small class="text-muted" id="buktiInfo"></small>
                  <button type="button" class="btn btn-link btn-sm text-danger p-0 d-none" id="buktiClear"><i class="fas fa-times mr-1"></i>Hapus gambar</button>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group mb-md-0">
                <label for="rencana_tindak_lanjut">Rencana Tindak Lanjut</label>
                <textarea class="form-control" name="rencana_tindak_lanjut" id="rencana_tindak_lanjut" rows="3"></textarea>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group mb-0">
                <label for="catatan">Catatan</label>
                <textarea class="form-control" name="catatan" id="catatan" rows="3"></textarea>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="followupSubmit">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endpush

@push('scripts')
<script>
if (!$('link[href*="select2.min.css"]').length) {
    $('head').append('<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />');
}

window.crmPasien.kategoriColors = @json($followUpKategori);

// Follow up form: set up on page load because the Pasien tab and the page header open it too
$(function () {
    var crm = window.crmPasien;
    var defaultSales = @json($defaultSales);

    $('#pasienSelect').select2({
        placeholder: 'Cari pasien',
        dropdownParent: $('#followupModal'),
        ajax: {
            url: "{{ route('marketing.followup.pasien-search') }}",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { search: params.term };
            },
            processResults: function (data) {
                return {
                    results: $.map(data, function (item) {
                        return { id: item.id, text: item.nama + ' (' + item.id + ')' };
                    })
                };
            }
        },
        minimumInputLength: 2
    });

    $('#salesSelect').select2({
        placeholder: 'Cari sales',
        allowClear: true,
        dropdownParent: $('#followupModal'),
        ajax: {
            url: '/api/hrd/employees',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                // EmployeeController.searchForSelect2 expects the query param 'q'
                return { q: params.term };
            },
            processResults: function (data) {
                return {
                    results: $.map(data, function (item) {
                        return { id: item.id, text: item.nama };
                    })
                };
            }
        },
        minimumInputLength: 2
    });

    // Replaces the select's options with one selected option (or none)
    function setSelectOption($select, id, text) {
        $select.empty();
        if (id && text) {
            $select.append(new Option(text, id, true, true));
        }
        $select.trigger('change');
    }

    // Checks the radios/checkboxes of a btn-group-toggle whose value is in `values` (a value or a list)
    function setToggle($group, values) {
        values = [].concat(values || []);
        $group.find('input').each(function () {
            var on = values.indexOf(this.value) !== -1;
            $(this).prop('checked', on).closest('label').toggleClass('active', on);
        });
    }

    function resetForm() {
        $('#followupForm')[0].reset();
        $('#followup_id').val('');
        clearBukti();
        setToggle($('#kategoriGroup'), null);
    }

    // ---- Bukti respon: paste / drop / pick ----
    var buktiInput = document.getElementById('bukti_respon');
    var buktiObjectUrl = null;

    // Shows an image (File or URL) in the zone; `info` is the caption
    function showBukti(src, info) {
        if (buktiObjectUrl) { URL.revokeObjectURL(buktiObjectUrl); buktiObjectUrl = null; }
        if (src instanceof File) src = buktiObjectUrl = URL.createObjectURL(src);
        $('#buktiPreview').html($('<img alt="Bukti respon">').attr('src', src)).removeClass('d-none');
        $('#buktiEmpty').addClass('d-none');
        $('#buktiInfo').text(info || '');
    }

    function clearBukti() {
        if (buktiObjectUrl) { URL.revokeObjectURL(buktiObjectUrl); buktiObjectUrl = null; }
        buktiInput.value = '';
        $('#buktiPreview').empty().addClass('d-none');
        $('#buktiEmpty').removeClass('d-none');
        $('#buktiInfo').text('');
        $('#buktiClear').addClass('d-none');
    }

    // Puts the image into the file input so it is sent with the form
    function setBuktiFile(file) {
        if (!file || !/^image\//.test(file.type)) {
            Swal.fire('Bukan gambar', 'Bukti respon harus berupa gambar (JPG / PNG / GIF).', 'warning');
            return;
        }
        // Pasted screenshots come without a real name
        if (!file.name || file.name === 'image.png') {
            var ext = (file.type.split('/')[1] || 'png').replace('jpeg', 'jpg');
            file = new File([file], 'bukti-' + moment().format('YYYYMMDD-HHmmss') + '.' + ext, { type: file.type });
        }
        var transfer = new DataTransfer();
        transfer.items.add(file);
        buktiInput.files = transfer.files;
        showBukti(file, file.name + ' (' + Math.max(1, Math.round(file.size / 1024)) + ' KB)');
        $('#buktiClear').removeClass('d-none');
    }

    function imageFrom(items) {
        for (var i = 0; i < (items || []).length; i++) {
            var item = items[i];
            var file = item.getAsFile ? (item.kind === 'file' ? item.getAsFile() : null) : item;
            if (file && /^image\//.test(file.type)) return file;
        }
        return null;
    }

    // Ctrl+V anywhere in the open form (text pastes into textareas still work normally)
    $(document).on('paste', function (e) {
        if (!$('#followupModal').hasClass('show')) return;
        var data = e.originalEvent.clipboardData;
        var file = data && imageFrom(data.items);
        if (!file) return;
        e.preventDefault();
        setBuktiFile(file);
    });

    $('#buktiZone')
        .on('click', function () { buktiInput.click(); })
        .on('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); buktiInput.click(); } })
        .on('dragover dragenter', function (e) { e.preventDefault(); $(this).addClass('is-over'); })
        .on('dragleave dragend drop', function () { $(this).removeClass('is-over'); })
        .on('drop', function (e) {
            e.preventDefault();
            var file = imageFrom(e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files);
            if (file) setBuktiFile(file);
        });
    $(buktiInput).on('change', function () {
        if (this.files && this.files[0]) setBuktiFile(this.files[0]);
    });
    $('#buktiClear').on('click', clearBukti);

    // New follow up, optionally for a given pasien ({id, nama})
    crm.openFollowUp = function (pasien) {
        resetForm();
        setSelectOption($('#pasienSelect'), pasien && pasien.id, pasien && (pasien.nama + ' (' + pasien.id + ')'));
        setSelectOption($('#salesSelect'), defaultSales && defaultSales.id, defaultSales && defaultSales.nama);
        setToggle($('#statusResponGroup'), 'Menunggu'); // just contacted, reply not in yet
        setToggle($('#statusBookingGroup'), 'Menunggu');
        $('#followupModalLabel').text(pasien ? 'Follow Up: ' + pasien.nama : 'Tambah Follow Up');
        $('#followupModal').modal('show');
    };

    crm.editFollowUp = function (id) {
        $.get('/marketing/followup/' + id, function (data) {
            resetForm();
            $('#followup_id').val(data.id);
            setSelectOption($('#pasienSelect'), data.pasien_id, data.pasien ? data.pasien.nama + ' (' + data.pasien_id + ')' : data.pasien_id);
            setSelectOption($('#salesSelect'), data.sales_id, data.sales && data.sales.nama);
            var kategori = data.kategori;
            if (kategori && !Array.isArray(kategori)) {
                try { kategori = JSON.parse(kategori); } catch (e) { kategori = [kategori]; }
            }
            setToggle($('#kategoriGroup'), kategori);
            setToggle($('#statusResponGroup'), data.status_respon);
            setToggle($('#statusBookingGroup'), data.status_booking);
            $('#rencana_tindak_lanjut').val(data.rencana_tindak_lanjut);
            $('#catatan').val(data.catatan);
            if (data.bukti_respon) {
                showBukti(data.bukti_respon, 'Bukti tersimpan. Tempel / pilih gambar lain untuk mengganti.');
            }
            $('#followupModalLabel').text('Edit Follow Up' + (data.pasien ? ': ' + data.pasien.nama : ''));
            $('#followupModal').modal('show');
        }).fail(function () {
            Swal.fire('Error', 'Gagal memuat data follow up', 'error');
        });
    };

    $('#followupForm').on('submit', function (e) {
        e.preventDefault();
        // The same checks the server makes, without a round trip
        var missing = [];
        if (!$('#pasienSelect').val()) missing.push('Pasien');
        if (!$('#kategoriGroup input:checked').length) missing.push('Kategori');
        if (!$('input[name=status_respon]:checked').length) missing.push('Status Respon');
        if (missing.length) {
            Swal.fire('Belum lengkap', 'Isi dulu: ' + missing.join(', '), 'warning');
            return;
        }

        var id = $('#followup_id').val();
        var formData = new FormData(this);
        if (id) formData.append('_method', 'PUT');
        var $submit = $('#followupSubmit').prop('disabled', true).text('Menyimpan...');
        $.ajax({
            url: id ? '/marketing/followup/' + id : '/marketing/followup',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                var booked = $('input[name=status_booking]:checked').val() === 'Sukses';
                var pasien = $('#pasienSelect').select2('data')[0] || {};
                $('#followupModal').modal('hide');
                crm.reload('followup');
                crm.reload('pasien'); // FU 7 hari column
                if (!booked || !res.id) {
                    Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
                    return;
                }
                // Booking Sukses: register the visit right away, referral = this follow up
                Swal.fire({
                    icon: 'success',
                    title: res.message,
                    text: 'Booking sukses. Daftarkan kunjungan pasien sekarang?',
                    showCancelButton: true,
                    confirmButtonText: 'Daftarkan Kunjungan',
                    cancelButtonText: 'Nanti'
                }).then(function (result) {
                    if (!result.value) return;
                    crm.registerVisit({
                        id: res.id,
                        pasien_id: $('#pasienSelect').val() || pasien.id,
                        pasien_nama: (pasien.text || '').replace(/\s*\([^)]*\)\s*$/, ''),
                        label: moment().format('DD/MM/YYYY') + ' (baru)'
                    });
                });
            },
            error: function (xhr) {
                var msg = 'Terjadi kesalahan';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).join('<br>');
                }
                Swal.fire('Error', msg, 'error');
            },
            complete: function () {
                $submit.prop('disabled', false).text('Simpan');
            }
        });
    });
});

window.crmPasien.inits.followup = function () {
    var crm = window.crmPasien;
    var range = { start: moment(), end: moment() };

    function dateParams() {
        return { start_date: range.start.format('YYYY-MM-DD'), end_date: range.end.format('YYYY-MM-DD') };
    }

    function rangeLabel() {
        var start = range.start.format('DD/MM/YYYY'), end = range.end.format('DD/MM/YYYY');
        if (start === end) return start === moment().format('DD/MM/YYYY') ? 'hari ini' : start;
        return start + ' - ' + end;
    }

    // Stats follow the date range only, so a stat stays readable while it filters the table
    function updateStats() {
        $.get("{{ route('marketing.followup.count-today') }}", dateParams(), function (res) {
            $('#followUpCount').text(res.count);
            $('#followUpRangeLabel').text(rangeLabel());
            $('#followUpDirespon').text(res.direspon);
            $('#followUpTidakDirespon').text(res.tidak_direspon);
            $('#followUpDiresponPercent').text(res.percent_direspon + '%');
            $('#followUpTidakDiresponPercent').text(res.percent_tidak_direspon + '%');
            $('#followUpMenungguRespon').text(res.menunggu_respon ?? 0);
            $('#followUpMenungguResponPercent').text((res.percent_menunggu_respon ?? 0) + '%');
            $('#bookingSukses').text(res.sukses ?? 0);
            $('#bookingMenunggu').text(res.menunggu ?? 0);
            $('#bookingBatal').text(res.batal ?? 0);

            var perKategori = {
                'Produk': res.kategori_produk, 'Perawatan': res.kategori_perawatan, 'Reseller': res.kategori_reseller,
                'Slimming': res.kategori_slimming, 'Ulang Tahun': res.kategori_ulang_tahun
            };
            $('#kategoriStats').html($.map(perKategori, function (count, kategori) {
                return '<span class="badge badge-' + crm.kategoriColors[kategori] + '">' + crm.esc(kategori) + ' ' + (count || 0) + '</span>';
            }).join(''));

            var $list = $('#topSalesList').empty();
            (res.top_sales || []).forEach(function (sale, i) {
                $list.append($('<li>')
                    .append($('<span class="crm-top-sales-name">').text((i + 1) + '. ' + sale.nama).attr('title', sale.nama))
                    .append($('<b>').text(sale.jumlah)));
            });
            if (!$list.children().length) $list.append('<li class="text-muted">Belum ada</li>');
        });
    }

    crm.dateRangePicker($('#dateRange'), range.start, range.end, function (start, end) {
        range = { start: start, end: end };
        table.ajax.reload();
    });

    function badge(value, colors) {
        if (!value) return '<span class="text-muted">-</span>';
        return '<span class="badge badge-' + (colors[value] || 'secondary') + '">' + value + '</span>';
    }

    var table = $('#followupTable').DataTable({
        processing: true,
        serverSide: true,
        order: [], // server default: newest first
        language: { search: 'Search:', searchPlaceholder: 'Pasien / sales / catatan', processing: 'Memuat...', emptyTable: 'Belum ada follow up di tanggal ini', zeroRecords: 'Follow up tidak ditemukan' },
        ajax: {
            url: "{{ route('marketing.followup.index') }}",
            data: function (d) {
                $.extend(d, dateParams());
                var kategori = $('#kategoriFilter').val();
                if (kategori && kategori.length) d.kategori = kategori;
                d.status_respon = $('#statusResponFilter').val();
                d.status_booking = $('#statusBookingFilter').val();
            }
        },
        // Server fields are HTML-escaped
        columns: [
            { data: 'tanggal', name: 'marketing_follow_ups.created_at', searchable: false, className: 'text-nowrap' },
            { data: 'pasien_nama', name: 'pasien_nama', render: function (data, type, row) {
                if (type !== 'display') return data;
                return '<div class="crm-person-name">' + (data || '-') + '</div><div class="crm-muted-id">' + row.pasien_id + '</div>';
            } },
            { data: 'kategori', name: 'marketing_follow_ups.kategori', orderable: false, searchable: false, render: function (data) {
                var items = Array.isArray(data) ? data : (data ? [data] : []);
                return items.map(function (item) {
                    return '<span class="badge badge-' + (crm.kategoriColors[$('<div>').html(item).text()] || 'secondary') + ' mr-1">' + item + '</span>';
                }).join('') || '<span class="text-muted">-</span>';
            } },
            { data: 'sales_nama', name: 'sales_nama', defaultContent: '-' },
            { data: 'status_respon', name: 'marketing_follow_ups.status_respon', render: function (data) {
                return badge(data, { 'Direspon': 'success', 'Tidak Direspon': 'danger', 'Menunggu': 'warning' });
            } },
            { data: 'status_booking', name: 'marketing_follow_ups.status_booking', render: function (data) {
                return badge(data, { 'Sukses': 'success', 'Menunggu': 'warning', 'Batal': 'danger' });
            } },
            { data: 'rencana_tindak_lanjut', name: 'marketing_follow_ups.rencana_tindak_lanjut', orderable: false, className: 'crm-wrap', render: function (data, type, row) {
                if (type !== 'display') return data;
                var parts = [];
                if (data) parts.push('<div>' + data + '</div>');
                if (row.catatan) parts.push('<div class="small text-muted">' + row.catatan + '</div>');
                return parts.join('') || '<span class="text-muted">-</span>';
            } },
            { data: null, orderable: false, searchable: false, className: 'crm-actions-cell', render: function (data, type, row) {
                // Booked visit: show its date; otherwise offer to register one (referral = this follow up)
                var visit = row.visit_date
                    ? '<span class="badge badge-success mr-1" title="Kunjungan terdaftar dari follow up ini"><i class="fas fa-calendar-check mr-1"></i>Terdaftar ' + row.visit_date + '</span><br class="d-xl-none">'
                    : (row.status_booking === 'Batal' ? '' : '<button type="button" class="btn btn-sm btn-success registerVisitBtn"><i class="fas fa-calendar-plus mr-1"></i>Daftarkan</button> ');
                return visit
                    + '<button type="button" class="btn btn-sm btn-outline-primary editBtn" data-id="' + row.id + '"><i class="fas fa-pen mr-1"></i>Edit</button> '
                    + '<button type="button" class="btn btn-sm btn-outline-danger deleteBtn" data-id="' + row.id + '"><i class="fas fa-trash mr-1"></i>Hapus</button>';
            } },
            // Searchable only: lets the search box match catatan too
            { data: 'catatan', name: 'marketing_follow_ups.catatan', visible: false, orderable: false }
        ],
        drawCallback: function () {
            updateStats();
            syncStatHighlight();
        }
    });

    // Clicking a stat toggles that filter
    function syncStatHighlight() {
        $('#tab-followup .crm-stat[data-filter]').each(function () {
            var $stat = $(this);
            var $select = $stat.data('filter') === 'status_respon' ? $('#statusResponFilter') : $('#statusBookingFilter');
            $stat.toggleClass('is-active', $select.val() === $stat.data('value'));
        });
    }
    $('#tab-followup').on('click', '.crm-stat[data-filter]', function () {
        var $stat = $(this);
        var $select = $stat.data('filter') === 'status_respon' ? $('#statusResponFilter') : $('#statusBookingFilter');
        $select.val($select.val() === $stat.data('value') ? '' : $stat.data('value')).trigger('change');
    });

    $('#kategoriFilter').select2({ placeholder: 'Semua kategori', allowClear: true, width: '100%' });
    $('#kategoriFilter, #statusResponFilter, #statusBookingFilter').on('change', function () {
        table.ajax.reload();
    });

    $('#followupResetFilter').on('click', function () {
        range = { start: moment(), end: moment() };
        $('#dateRange').data('daterangepicker').setStartDate(range.start);
        $('#dateRange').data('daterangepicker').setEndDate(range.end);
        $('#kategoriFilter').val(null).trigger('change.select2');
        $('#statusResponFilter, #statusBookingFilter').val('');
        table.search('').ajax.reload();
    });

    $('#followupTable').on('click', '.registerVisitBtn', function () {
        var row = table.row($(this).closest('tr')).data();
        if (!row) return;
        var nama = $('<div>').html(row.pasien_nama || '').text();
        crm.registerVisit({
            id: row.id,
            pasien_id: $('<div>').html(row.pasien_id).text(),
            pasien_nama: nama,
            label: $('<div>').html(row.tanggal + (row.sales_nama ? ' · ' + row.sales_nama : '')).text()
        });
    });

    $('#followupTable').on('click', '.editBtn', function () {
        crm.editFollowUp($(this).data('id'));
    });

    $('#followupTable').on('click', '.deleteBtn', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus follow up ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.value) return;
            $.ajax({
                url: '/marketing/followup/' + id,
                type: 'DELETE',
                success: function (res) {
                    table.ajax.reload(null, false);
                    crm.reload('pasien');
                    Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
                },
                error: function () {
                    Swal.fire('Error', 'Gagal menghapus', 'error');
                }
            });
        });
    });

    return table;
};
</script>
@endpush
