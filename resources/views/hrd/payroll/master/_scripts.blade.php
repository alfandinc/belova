$(document).ready(function() {
    // Set CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    // DataTable init for each tab
    var tables = {
        gajipokok: $('#tableGajiPokok').DataTable({
            ajax: '/hrd/payroll/master/gajipokok',
            columns: [
                { data: 'id' },
                { data: 'golongan' },
                { data: 'nominal' },
                { data: 'aksi', orderable: false, searchable: false }
            ]
        }),
        tunjangan_jabatan: $('#tableTunjanganJabatan').DataTable({
            ajax: '/hrd/payroll/master/tunjangan-jabatan',
            columns: [
                { data: 'id' },
                { data: 'golongan' },
                { data: 'nominal' },
                { data: 'aksi', orderable: false, searchable: false }
            ]
        }),
        tunjangan_lain: $('#tableTunjanganLain').DataTable({
            ajax: '/hrd/payroll/master/tunjangan-lain',
            columns: [
                { data: 'id' },
                { data: 'nama_tunjangan' },
                { data: 'nominal' },
                { data: 'aksi', orderable: false, searchable: false }
            ]
        }),
        benefit: $('#tableBenefit').DataTable({
            ajax: '/hrd/payroll/master/benefit',
            columns: [
                { data: 'id' },
                { data: 'nama_benefit' },
                { data: 'nominal' },
                { data: 'aksi', orderable: false, searchable: false }
            ]
        }),
        potongan: $('#tablePotongan').DataTable({
            ajax: '/hrd/payroll/master/potongan',
            columns: [
                { data: 'id' },
                { data: 'nama_potongan' },
                { data: 'nominal' },
                { data: 'aksi', orderable: false, searchable: false }
            ]
        })
    };

    // Add button click handlers
    $('#addGajiPokokBtn').click(function() {
        showPayrollModal('gajipokok');
    });
    $('#addTunjanganJabatanBtn').click(function() {
        showPayrollModal('tunjangan_jabatan');
    });
    $('#addTunjanganLainBtn').click(function() {
        showPayrollModal('tunjangan_lain');
    });
    $('#addBenefitBtn').click(function() {
        showPayrollModal('benefit');
    });
    $('#addPotonganBtn').click(function() {
        showPayrollModal('potongan');
    });

    // Show modal and set fields
    function showPayrollModal(type, data = null) {
        $('#payrollMasterType').val(type);
        $('#payrollMasterId').val(data ? data.id : '');
        var fields = '';
        function parseNominal(nom) {
            if (!nom) return '';
            if (typeof nom === 'string') {
                return nom.replace(/\D/g, '');
            }
            return nom;
        }
        if(type === 'gajipokok' || type === 'tunjangan_jabatan') {
            fields += '<div class="form-group"><label>Golongan</label><input type="text" name="golongan" class="form-control" value="'+(data ? data.golongan : '')+'" required></div>';
            fields += '<div class="form-group"><label>Nominal</label><input type="number" name="nominal" class="form-control" value="'+(data ? parseNominal(data.nominal) : '')+'" required></div>';
        } else if(type === 'tunjangan_lain') {
            fields += '<div class="form-group"><label>Nama Tunjangan</label><input type="text" name="nama_tunjangan" class="form-control" value="'+(data ? data.nama_tunjangan : '')+'" required></div>';
            fields += '<div class="form-group"><label>Nominal</label><input type="number" name="nominal" class="form-control" value="'+(data ? parseNominal(data.nominal) : '')+'" required></div>';
        } else if(type === 'benefit') {
            fields += '<div class="form-group"><label>Nama Benefit</label><input type="text" name="nama_benefit" class="form-control" value="'+(data ? data.nama_benefit : '')+'" required></div>';
            fields += '<div class="form-group"><label>Nominal</label><input type="number" name="nominal" class="form-control" value="'+(data ? parseNominal(data.nominal) : '')+'" required></div>';
        } else if(type === 'potongan') {
            fields += '<div class="form-group"><label>Nama Potongan</label><input type="text" name="nama_potongan" class="form-control" value="'+(data ? data.nama_potongan : '')+'" required></div>';
            fields += '<div class="form-group"><label>Nominal</label><input type="number" name="nominal" class="form-control" value="'+(data ? parseNominal(data.nominal) : '')+'" required></div>';
        }
        $('#payrollMasterFields').html(fields);
        $('#payrollMasterModal').modal('show');
    }

    // Submit form via AJAX
    $('#payrollMasterForm').submit(function(e) {
        e.preventDefault();
        var type = $('#payrollMasterType').val();
        var id = $('#payrollMasterId').val();
        // Map type to correct endpoint
        var endpointMap = {
            gajipokok: 'gajipokok',
            tunjangan_jabatan: 'tunjangan-jabatan',
            tunjangan_lain: 'tunjangan-lain',
            benefit: 'benefit',
            potongan: 'potongan'
        };
        var endpoint = endpointMap[type] || type;
        var url = '/hrd/payroll/master/' + endpoint + (id ? '/' + id : '');
        var method = id ? 'PUT' : 'POST';
        var formData = $(this).serialize();
        $.ajax({
            url: url,
            type: method,
            data: formData,
            success: function(res) {
                Swal.fire('Sukses', res.message, 'success');
                $('#payrollMasterModal').modal('hide');
                tables[type].ajax.reload();
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan', 'error');
            }
        });
    });

    // Edit and delete actions (delegated)
    $('table').on('click', '.edit-btn', function() {
        var tableId = $(this).closest('table').attr('id');
        var typeMap = {
            'tableGajiPokok': 'gajipokok',
            'tableTunjanganJabatan': 'tunjangan_jabatan',
            'tableTunjanganLain': 'tunjangan_lain',
            'tableBenefit': 'benefit',
            'tablePotongan': 'potongan'
        };
        var type = typeMap[tableId];
        var rowData = tables[type]?.row($(this).closest('tr')).data();
        if (!rowData) {
            Swal.fire('Error', 'Data tidak ditemukan.', 'error');
            return;
        }
        showPayrollModal(type, rowData);
    });
    $('table').on('click', '.delete-btn', function() {
        var tableId = $(this).closest('table').attr('id');
        var typeMap = {
            'tableGajiPokok': 'gajipokok',
            'tableTunjanganJabatan': 'tunjangan_jabatan',
            'tableTunjanganLain': 'tunjangan_lain',
            'tableBenefit': 'benefit',
            'tablePotongan': 'potongan'
        };
        var type = typeMap[tableId];
        var rowData = tables[type]?.row($(this).closest('tr')).data();
        if (!rowData) {
            Swal.fire('Error', 'Data tidak ditemukan.', 'error');
            return;
        }
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
                    url: '/hrd/payroll/master/' + (type === 'tunjangan_jabatan' ? 'tunjangan-jabatan' : type.replace('_', '-')) + '/' + rowData.id,
                    type: 'DELETE',
                    success: function(res) {
                        Swal.fire('Sukses', res.message, 'success');
                        tables[type].ajax.reload();
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan', 'error');
                    }
                });
            }
        });
    });
});
