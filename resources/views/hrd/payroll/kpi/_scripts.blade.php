<script>
$(function() {
    var table = $('#kpiTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('hrd.payroll.kpi.data') }}',
        columns: [
            { data: 'nama_poin', name: 'nama_poin' },
            { data: 'initial_poin', name: 'initial_poin' },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
        ]
    });

    $('#btnAdd').click(function() {
        $('#formKpi')[0].reset();
        $('#id').val('');
        $('#modalKpi').modal('show');
    });

    $('#formKpi').submit(function(e) {
        e.preventDefault();
        var id = $('#id').val();
        var url = id ? '{{ url('hrd/payroll/kpi') }}/' + id : '{{ url('hrd/payroll/kpi') }}';
        var method = id ? 'PUT' : 'POST';
        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            success: function(res) {
                if(res.success) {
                    Swal.fire('Sukses', 'Data berhasil disimpan!', 'success');
                    $('#modalKpi').modal('hide');
                    table.ajax.reload();
                }
            },
            error: function(xhr) {
                Swal.fire('Error', 'Terjadi kesalahan!', 'error');
            }
        });
    });

    $('#kpiTable').on('click', '.btn-edit', function() {
        var data = table.row($(this).parents('tr')).data();
        $('#id').val(data.id);
        $('#nama_poin').val(data.nama_poin);
        $('#initial_poin').val(data.initial_poin);
        $('#modalKpi').modal('show');
    });

    $('#kpiTable').on('click', '.btn-delete', function() {
        var data = table.row($(this).parents('tr')).data();
        Swal.fire({
            title: 'Hapus Data?',
            text: 'Data akan dihapus secara permanen!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if(result.isConfirmed) {
                $.ajax({
                    url: '{{ url('hrd/payroll/kpi') }}/' + data.id,
                    type: 'DELETE',
                    data: {_token: '{{ csrf_token() }}'},
                    success: function(res) {
                        if(res.success) {
                            Swal.fire('Sukses', 'Data berhasil dihapus!', 'success');
                            table.ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Terjadi kesalahan!', 'error');
                    }
                });
            }
        });
    });
});
</script>
