<script>
$(function() {
    var table = $('#insentifOmsetTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('hrd.payroll.insentif_omset.data') }}',
        columns: [
            { data: 'nama_penghasil', name: 'nama_penghasil' },
            { data: 'omset_min', name: 'omset_min' },
            { data: 'omset_max', name: 'omset_max' },
            { data: 'insentif_normal', name: 'insentif_normal' },
            { data: 'insentif_up', name: 'insentif_up' },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
        ]
    });

    $('#btnAdd').click(function() {
        $('#formInsentifOmset')[0].reset();
        $('#id').val('');
        $('#modalInsentifOmset').modal('show');
    });

    $('#formInsentifOmset').submit(function(e) {
        e.preventDefault();
        var id = $('#id').val();
        var url = id ? '{{ url('hrd/payroll/insentif-omset') }}/' + id : '{{ url('hrd/payroll/insentif-omset') }}';
        var method = id ? 'PUT' : 'POST';
        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            success: function(res) {
                if(res.success) {
                    Swal.fire('Sukses', 'Data berhasil disimpan!', 'success');
                    $('#modalInsentifOmset').modal('hide');
                    table.ajax.reload();
                }
            },
            error: function(xhr) {
                Swal.fire('Error', 'Terjadi kesalahan!', 'error');
            }
        });
    });

    $('#insentifOmsetTable').on('click', '.btn-edit', function() {
        var data = table.row($(this).parents('tr')).data();
        $('#id').val(data.id);
        $('#nama_penghasil').val(data.nama_penghasil);
        $('#omset_min').val(data.omset_min);
        $('#omset_max').val(data.omset_max);
        $('#insentif_normal').val(data.insentif_normal);
        $('#insentif_up').val(data.insentif_up);
        $('#modalInsentifOmset').modal('show');
    });

    $('#insentifOmsetTable').on('click', '.btn-delete', function() {
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
                    url: '{{ url('hrd/payroll/insentif-omset') }}/' + data.id,
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
