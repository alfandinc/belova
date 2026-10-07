<script>
// ---------- pasien ganda (Admin only, checked again on the server) ----------
$(function () {
    const URLS = {
        duplicates: '{{ route('erm.pasiens.duplicates') }}',
        merge: '{{ route('erm.pasiens.merge') }}',
        select2: '{{ route('erm.pasiens.select2') }}'
    };
    const PAGE_SIZE = 20;
    const $modal = $('#pasienGandaModal');
    let groups = [];
    let shown = PAGE_SIZE;

    function esc(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function errorText(xhr, fallback) {
        const res = xhr.responseJSON || {};
        if (res.errors) return Object.values(res.errors).flat().join('\n');
        return res.message || fallback;
    }

    function confirmMerge(text) {
        return Swal.fire({
            icon: 'warning',
            title: 'Gabungkan pasien?',
            text: text,
            showCancelButton: true,
            confirmButtonText: 'Ya, gabungkan',
            cancelButtonText: 'Batal'
        }).then(function (r) { return r.isConfirmed || r.value === true; });
    }

    function postMerge(targetId, sourceIds, $btn) {
        const label = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menggabungkan...');
        return $.post(URLS.merge, { _token: $('meta[name="csrf-token"]').attr('content'), target_id: targetId, source_ids: sourceIds })
            .done(function (res) {
                Swal.fire({ icon: 'success', title: 'Digabungkan', text: res.message });
                if ($.fn.DataTable.isDataTable('#pasiens-table')) $('#pasiens-table').DataTable().ajax.reload(null, false);
                if (typeof window.refreshPasienStats === 'function') window.refreshPasienStats();
            })
            .fail(function (xhr) { Swal.fire({ icon: 'error', title: 'Gagal menggabungkan', text: errorText(xhr, 'Terjadi kesalahan.') }); })
            .always(function () { $btn.prop('disabled', false).html(label); });
    }

    // ---------- duplicate list ----------
    let loaded = false;

    function loadGroups() {
        $('#pgList').html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin mr-1"></i> Mencari pasien ganda...</div>');
        $('#pgSummary').text('');
        $('#pgMore').addClass('d-none');
        return $.get(URLS.duplicates)
            .done(function (res) { groups = res.data || []; loaded = true; shown = PAGE_SIZE; render(); })
            .fail(function (xhr) {
                $('#pgList').html('<div class="alert alert-danger">' + esc(errorText(xhr, 'Gagal memuat data.')) + '</div>');
                $('#pasienGandaCount').text('-');
            });
    }

    // Number of duplicate groups on the Duplikasi Pasien card
    function updateCount() {
        $('#pasienGandaCount').text(new Intl.NumberFormat('id-ID').format(groups.length));
    }

    function filteredGroups() {
        const level = $('#pgLevel').val();
        const term = ($('#pgSearch').val() || '').trim().toLowerCase();
        return groups.filter(function (g) {
            if (level && g.level !== level) return false;
            if (!term) return true;
            return g.patients.some(function (p) {
                return [p.id, p.nama, p.identity, p.no_hp].some(function (v) { return v && String(v).toLowerCase().indexOf(term) !== -1; });
            });
        });
    }

    function render() {
        updateCount();
        const list = filteredGroups();
        const total = list.reduce(function (n, g) { return n + g.patients.length; }, 0);
        $('#pgSummary').text(groups.length
            ? list.length + ' kelompok pasien ganda (' + total + ' pasien)' + (list.length !== groups.length ? ' dari ' + groups.length + ' kelompok' : '')
            : '');
        if (!list.length) {
            $('#pgList').html('<div class="text-center text-muted py-4">' + (groups.length ? 'Tidak ada yang cocok.' : '<i class="fas fa-check-circle text-success mr-1"></i> Tidak ditemukan pasien ganda.') + '</div>');
            $('#pgMore').addClass('d-none');
            return;
        }
        $('#pgList').html(list.slice(0, shown).map(renderGroup).join(''));
        $('#pgMore').toggleClass('d-none', list.length <= shown);
    }

    function renderGroup(g) {
        const levelBadge = g.level === 'tinggi'
            ? '<span class="badge badge-danger">Kemungkinan tinggi</span>'
            : '<span class="badge badge-warning">Perlu dicek</span>';
        const reasons = g.reasons.map(function (r) { return '<span class="badge badge-light border">' + esc(r) + '</span>'; }).join('');
        const target = g.patients.find(function (p) { return p.id === g.suggested_target_id; }) || g.patients[0];

        const rows = g.patients.map(function (p) {
            const isTarget = p.id === target.id;
            const diff = function (field, html) {
                return !isTarget && (p[field] || '') !== (target[field] || '') ? '<span class="pg-diff">' + html + '</span>' : html;
            };
            return '<tr data-id="' + esc(p.id) + '" class="' + (isTarget ? 'pg-target' : '') + '">' +
                '<td class="text-center"><input type="radio" name="pg-target-' + esc(g.key) + '" class="pg-target-radio" value="' + esc(p.id) + '"' + (isTarget ? ' checked' : '') + '></td>' +
                '<td class="text-center"><input type="checkbox" class="pg-source-check" value="' + esc(p.id) + '"' + (isTarget ? ' disabled' : ' checked') + '></td>' +
                '<td>' + esc(p.id) + '</td>' +
                '<td>' + diff('nama', esc(p.nama)) + (p.status_pasien && p.status_pasien !== 'Regular' ? ' <span class="badge badge-dark">' + esc(p.status_pasien) + '</span>' : '') + '</td>' +
                '<td class="text-nowrap">' + diff('tanggal_lahir', esc(p.tanggal_lahir || '-')) + '</td>' +
                '<td>' + diff('identity', esc(p.identity || '-')) + '</td>' +
                '<td>' + diff('no_hp', esc(p.no_hp || '-')) + '</td>' +
                '<td><div class="pg-alamat" title="' + esc(p.alamat) + '">' + esc(p.alamat || '-') + '</div></td>' +
                '<td class="text-center">' + p.visit_count + (p.last_visit ? '<div class="text-muted small">' + esc(p.last_visit) + '</div>' : '') + '</td>' +
                '<td class="text-nowrap">' + esc(p.created_at || '-') + '</td>' +
                '</tr>';
        }).join('');

        return '<div class="pg-group" data-key="' + esc(g.key) + '">' +
            '<div class="pg-group-head">' + levelBadge + reasons + '</div>' +
            '<div class="table-responsive"><table class="table table-sm">' +
            '<thead><tr><th class="text-center">Utama</th><th class="text-center">Gabung</th><th>RM</th><th>Nama</th><th>Tgl Lahir</th><th>Identitas</th><th>No HP</th><th>Alamat</th><th class="text-center">Kunjungan</th><th>Terdaftar</th></tr></thead>' +
            '<tbody>' + rows + '</tbody></table></div>' +
            '<div class="pg-group-foot"><small class="text-muted mr-auto">Utama disarankan: kunjungan terbanyak.</small>' +
            '<button type="button" class="btn btn-sm btn-primary pg-merge"><i class="fas fa-code-branch mr-1"></i> Gabungkan ke utama</button></div>' +
            '</div>';
    }

    $modal.on('change', '.pg-target-radio', function () {
        const $group = $(this).closest('.pg-group');
        const targetId = $(this).val();
        $group.find('tbody tr').each(function () {
            const isTarget = $(this).data('id').toString() === targetId;
            $(this).toggleClass('pg-target', isTarget);
            $(this).find('.pg-source-check').prop('disabled', isTarget).prop('checked', !isTarget);
        });
        $group.find('tbody tr').removeClass('pg-skip');
    });

    $modal.on('change', '.pg-source-check', function () {
        $(this).closest('tr').toggleClass('pg-skip', !this.checked);
    });

    $modal.on('click', '.pg-merge', function () {
        const $group = $(this).closest('.pg-group');
        const targetId = $group.find('.pg-target-radio:checked').val();
        const sourceIds = $group.find('.pg-source-check:checked:not(:disabled)').map(function () { return this.value; }).get();
        if (!targetId || !sourceIds.length) {
            Swal.fire({ icon: 'warning', title: 'Pilih pasien', text: 'Pilih pasien utama dan minimal satu pasien yang digabung.' });
            return;
        }
        const name = function (id) { return $group.find('tr[data-id="' + id + '"] td:eq(3)').text().trim(); };
        const $btn = $(this);
        confirmMerge('RM ' + sourceIds.join(', ') + ' akan digabung ke "' + name(targetId) + '" (RM ' + targetId + ') lalu dihapus.').then(function (ok) {
            if (!ok) return;
            postMerge(targetId, sourceIds, $btn).done(function () {
                const key = $group.data('key').toString();
                groups = groups.filter(function (g) { return g.key !== key; });
                render();
            });
        });
    });

    // ---------- manual merge ----------
    function initPasienSelect($el, placeholder) {
        $el.select2({
            width: '100%',
            dropdownParent: $modal,
            placeholder: placeholder,
            minimumInputLength: 2,
            ajax: {
                url: URLS.select2,
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (res) { return { results: res.results || [] }; }
            }
        });
    }

    initPasienSelect($('#pgManualSource'), 'Cari nama / RM / NIK pasien ganda...');
    initPasienSelect($('#pgManualTarget'), 'Cari nama / RM / NIK pasien utama...');

    $('#pgToggleManual').on('click', function (e) {
        e.preventDefault();
        $('#pgManualPanel').toggleClass('d-none');
    });

    $('#pgManualSave').on('click', function () {
        const sourceId = $('#pgManualSource').val();
        const targetId = $('#pgManualTarget').val();
        if (!sourceId || !targetId) {
            Swal.fire({ icon: 'warning', title: 'Pilih pasien', text: 'Pilih pasien ganda dan pasien utama.' });
            return;
        }
        if (sourceId === targetId) {
            Swal.fire({ icon: 'warning', title: 'Pasien sama', text: 'Pasien utama harus berbeda.' });
            return;
        }
        const $btn = $(this);
        confirmMerge('"' + $('#pgManualSource option:selected').text() + '" akan digabung ke "' + $('#pgManualTarget option:selected').text() + '" lalu dihapus.').then(function (ok) {
            if (!ok) return;
            postMerge(targetId, [sourceId], $btn).done(function () {
                $('#pgManualSource, #pgManualTarget').val(null).trigger('change');
                loadGroups();
            });
        });
    });

    // ---------- open / filter ----------
    let searchTimer = null;
    $('#pgSearch').on('input search', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { shown = PAGE_SIZE; render(); }, 250);
    });
    $('#pgLevel').on('change', function () { shown = PAGE_SIZE; render(); });
    $('#pgMore').on('click', function () { shown += PAGE_SIZE; render(); });
    $('#pgReload').on('click', loadGroups);

    // The list is already loaded for the card count; "Muat ulang" fetches it again
    $('#btnPasienGanda').on('click', function () {
        $modal.modal('show');
        if (!loaded) loadGroups();
    });

    loadGroups();
});
</script>
