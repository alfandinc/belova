<script>
$(function() {
    // Omset Bulanan per penghasil (insentif omset): typed by hand or pulled from Laporan Omset
    var OMSET_SOURCE_KEY = 'slipGaji.omsetSource.';
    function rememberSource(id, key) {
        try { localStorage.setItem(OMSET_SOURCE_KEY + id, key || ''); } catch (e) {}
    }
    function rememberedSource(id) {
        try { return localStorage.getItem(OMSET_SOURCE_KEY + id); } catch (e) { return null; }
    }
    function applySource($row, key) {
        var $select = $row.find('.omset-source');
        if (!key || !$select.find('option[value="' + key + '"]').length) {
            $select.val('');
            return false;
        }
        $select.val(key);
        $row.find('.omset-nominal').val($select.find('option:selected').data('nominal'));
        $row.find('.omset-hint').text('Ditarik dari sistem: ' + $select.find('option:selected').text());
        return true;
    }
    function loadOmsetInputs() {
        var bulan = $('#bulan').val();
        var $box = $('#omsetBulananInputs');
        if (!bulan) { $box.html(''); return; }
        $box.html('<div class="text-muted small py-2"><i class="fas fa-spinner fa-spin mr-1"></i> Memuat omset bulanan…</div>');
        $.get('{{ url('hrd/payroll/slip-gaji/omset-bulanan') }}', { bulan: bulan })
            .done(function(html) {
                if ($('#bulan').val() !== bulan) return; // month changed meanwhile
                $box.html(html);
                // rows never saved for this month: preselect the source used last time (or the name match)
                $box.find('.omset-row').each(function() {
                    var $row = $(this);
                    var saved = $row.find('.omset-nominal').val() !== '';
                    var key = rememberedSource($row.data('id'));
                    if (key === null) key = $row.data('suggest') || '';
                    if (saved) {
                        $row.find('.omset-source').val('');
                    } else {
                        applySource($row, key);
                    }
                });
            })
            .fail(function() {
                $box.html('<div class="alert alert-warning small mb-0">Gagal memuat omset bulanan.</div>');
            });
    }
    $('#bulan').on('change', loadOmsetInputs);
    $('#omsetBulananInputs').on('change', '.omset-source', function() {
        var $row = $(this).closest('.omset-row');
        var key = $(this).val();
        rememberSource($row.data('id'), key);
        if (key) applySource($row, key);
        else $row.find('.omset-hint').text('Isi manual');
    });
    $('#omsetBulananInputs').on('input', '.omset-nominal', function() {
        var $row = $(this).closest('.omset-row');
        if ($row.find('.omset-source').val()) {
            $row.find('.omset-source').val('');
            rememberSource($row.data('id'), '');
            $row.find('.omset-hint').text('Isi manual');
        }
    });
    $('#omsetBulananInputs').on('click', '#btnTarikSemuaOmset', function() {
        var pulled = 0;
        $('#omsetBulananInputs .omset-row').each(function() {
            var $row = $(this);
            var key = $row.find('.omset-source').val() || rememberedSource($row.data('id')) || $row.data('suggest');
            if (applySource($row, key)) {
                rememberSource($row.data('id'), key);
                pulled++;
            }
        });
        if (!pulled) {
            Swal.fire('Info', 'Pilih dulu sumber omset di tiap baris.', 'info');
        }
    });

    // Show modal on button click
    $('#btnBuatSlipGaji').click(function() {
        $('#formBuatSlipGaji')[0].reset();
        $('#omsetBulananInputs').html('');
        // default to the month currently shown in the table
        $('#bulan').val($('#filterBulan').val());
        loadOmsetInputs();
        $('#modalBuatSlipGaji').modal('show');
    });

    $('#bulan').attr('required', true);

    // Save slip gaji for all employees
    $('#formBuatSlipGaji').submit(function(e) {
        e.preventDefault();
        // Client-side validation: ensure form is valid
        var form = this;
        if (!form.checkValidity()) {
            // Let browser show validation UI
            form.reportValidity();
            return;
        }
        var bulan = $('#bulan').val();
        var $btn = $(form).find('button[type="submit"]');
        $btn.prop('disabled', true);
        $.ajax({
            url: '{{ url('hrd/payroll/slip-gaji/store-all') }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if(res.success) {
                    Swal.fire('Sukses', res.message || 'Slip gaji berhasil dibuat untuk semua pegawai!', 'success');
                    $('#modalBuatSlipGaji').modal('hide');
                    // Show the month that was just generated
                    if (bulan && $('#filterBulan').val() !== bulan) {
                        $('#filterBulan').val(bulan).trigger('change');
                    } else {
                        $('#slipGajiTable').DataTable().ajax.reload();
                    }
                } else {
                    Swal.fire('Error', res.message || 'Gagal membuat slip gaji.', 'error');
                }
            },
            error: function(xhr) {
                var msg = 'Terjadi kesalahan!';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire('Error', msg, 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
