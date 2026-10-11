{{-- Retur ke Supplier actions shared by the Retur page and the Faktur Pembelian page.
     window.ReturActions.init(onChanged) wires the form submit and the approve / reject buttons
     rendered by erm.fakturretur.create and erm.fakturretur.show. --}}
<script>
window.ReturActions = (function() {
    var CSRF = '{{ csrf_token() }}';
    var BASE = '{{ url('/erm/fakturretur') }}';
    var bound = false;

    function esc(v) {
        return String(v === undefined || v === null ? '' : v).replace(/[&<>"']/g, function(c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function message(xhr, fallback) {
        var json = xhr && xhr.responseJSON;
        if (json && json.errors) return Object.values(json.errors).map(function(m) { return m[0]; }).join('\n');
        return (json && json.message) || fallback;
    }

    function init(onChanged) {
        if (bound) return;
        bound = true;
        onChanged = onChanged || function() {};

        $(document).on('submit', '#formRetur', function(e) {
            e.preventDefault();
            var form = $(this);
            var total = 0;
            form.find('.retur-qty:enabled').each(function() { total += parseInt($(this).val() || 0, 10); });
            if (total <= 0) {
                Swal.fire({ icon: 'warning', title: 'Belum ada qty', text: 'Isi qty retur minimal untuk satu obat.' });
                return;
            }
            var $btn = form.find('[type=submit]').prop('disabled', true);
            $.post(form.attr('action'), form.serialize()).done(function(res) {
                $('#modalRetur').modal('hide');
                onChanged();
                Swal.fire({ icon: 'success', title: 'Retur diajukan', text: res.message });
            }).fail(function(xhr) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: message(xhr, 'Retur gagal diajukan.') });
            }).always(function() { $btn.prop('disabled', false); });
        });

        $(document).on('click', '#btn-approve-retur', function(e) {
            e.preventDefault();
            var $b = $(this);
            Swal.fire({
                icon: 'warning',
                title: 'Setujui retur ' + esc($b.data('nomor')) + '?',
                text: 'Stok batch yang diretur langsung dikurangi dari gudang dan tercatat di kartu stok.',
                showCancelButton: true,
                confirmButtonText: 'Ya, setujui',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#28a745',
                reverseButtons: true
            }).then(function(result) {
                if (!result.value) return;
                $b.prop('disabled', true);
                $.post(BASE + '/' + $b.data('id') + '/approve', { _token: CSRF }).done(function(res) {
                    $('#modalDetailRetur').modal('hide');
                    onChanged();
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message });
                }).fail(function(xhr) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: message(xhr, 'Retur gagal disetujui.') });
                }).always(function() { $b.prop('disabled', false); });
            });
        });

        $(document).on('click', '#btn-reject-retur', function(e) {
            e.preventDefault();
            var $b = $(this);
            Swal.fire({
                icon: 'warning',
                title: 'Tolak retur ' + esc($b.data('nomor')) + '?',
                text: 'Stok tidak berubah.',
                input: 'textarea',
                inputPlaceholder: 'Alasan penolakan...',
                inputAttributes: { maxlength: 500 },
                inputValidator: function(value) { if (!value || !value.trim()) return 'Alasan penolakan wajib diisi.'; },
                showCancelButton: true,
                confirmButtonText: 'Tolak',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
                reverseButtons: true
            }).then(function(result) {
                if (!result.value) return;
                $.post(BASE + '/' + $b.data('id') + '/reject', { _token: CSRF, reason: result.value.trim() }).done(function(res) {
                    $('#modalDetailRetur').modal('hide');
                    onChanged();
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message });
                }).fail(function(xhr) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: message(xhr, 'Retur gagal ditolak.') });
                });
            });
        });
    }

    return { init: init };
})();
</script>
