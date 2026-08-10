@extends('layouts.erm.app')
@section('title', 'ERM | Master Faktur Pembelian')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection  

@section('content')
<div class="container-fluid">
    <style>
        #permintaan-table td {
            vertical-align: top;
        }

        #permintaan-table tbody tr:nth-child(odd) {
            background-color: #fbfcff;
        }

        #permintaan-table tbody tr:nth-child(even) {
            background-color: #f3f7ff;
        }

        #permintaan-table tbody tr:hover {
            background-color: #eaf2ff;
        }

        #permintaan-table .permintaan-col-obat {
            min-width: 340px;
        }

        #permintaan-table .permintaan-col-qty {
            min-width: 110px;
        }

        #permintaan-table .permintaan-stack-row {
            min-height: 48px;
            padding: 0.35rem 0;
            border-bottom: 1px solid #e9edf4;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.15rem;
            box-sizing: border-box;
        }

        #permintaan-table .permintaan-stack-row:last-child {
            border-bottom: 0;
        }

        #permintaan-table .font-weight-medium {
            font-weight: 500;
        }

        #permintaan-table .permintaan-request-cell {
            min-width: 150px;
        }

        #permintaan-table .btn-lihat-nilai-pembelian {
            padding: 0.1rem 0.45rem;
            line-height: 1.2;
        }

        #permintaan-table .badge {
            font-size: 0.72rem;
        }

        #nilaiPembelianModal .badge {
            font-size: 0.72rem;
        }

        #nilaiPembelianModal tfoot th {
            font-weight: 700 !important;
        }

        #permintaanTableFilters {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-right: 0.75rem;
        }

        #permintaan-table_filter {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        #permintaan-table_filter label {
            margin-bottom: 0;
        }

        .permintaan-total-summary {
            display: flex;
            justify-content: flex-end;
            margin-top: 1rem;
        }

        .permintaan-total-summary-card {
            min-width: 320px;
            max-width: 100%;
            padding: 0.9rem 1rem;
            border: 1px solid #dbe4f0;
            border-radius: 0.5rem;
            background: #f8fbff;
        }

        .permintaan-total-summary-label {
            display: block;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6c7a92;
            margin-bottom: 0.25rem;
        }

        .permintaan-total-summary-value {
            font-size: 1.35rem;
            font-weight: 700;
            color: #1f3b73;
            line-height: 1.2;
        }

        @media (max-width: 767.98px) {
            #permintaan-table_filter {
                justify-content: flex-start;
            }

            #permintaanTableFilters {
                margin-right: 0;
            }

            .permintaan-total-summary {
                justify-content: stretch;
            }

            .permintaan-total-summary-card {
                width: 100%;
                min-width: 0;
            }
        }
    </style>
        <!-- Page-Title -->
    <!-- Title and Button Row -->
    <div class="row mt-3 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0">Daftar Permintaan Pembelian</h2>
        </div>
        <div class="col-md-6 d-flex justify-content-md-end mt-3 mt-md-0">
            <div id="permintaanTableFilters" class="d-none">
                <select id="principalFilter" class="form-control form-control-sm" style="width: 220px;">
                    <option value="">Semua Principal</option>
                    @foreach(($principals ?? collect()) as $principal)
                        <option value="{{ $principal->id }}">{{ $principal->nama }}</option>
                    @endforeach
                </select>
                <select id="statusFilter" class="form-control form-control-sm" style="width: 180px;">
                    <option value="">Semua Status</option>
                    <option value="waiting_approval">Waiting Approval</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <div class="input-group input-group-sm" style="width: 280px;">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                    </div>
                    <input type="text" id="requestDateRange" class="form-control" autocomplete="off" placeholder="Tanggal permintaan">
                </div>
                <button type="button" class="btn btn-secondary btn-sm" id="resetRequestDateRange">Reset</button>
            </div>
            <div>
                <a href="{{ route('erm.permintaan.create') }}" class="btn btn-primary btn-sm mb-2 mb-md-0">Buat Permintaan</a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Farmasi</li>
                            <li class="breadcrumb-item active">Permintaan Pembelian</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->
    
    @if(session('success'))
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Sukses',
                text: @json(session('success')),
                timer: 2000,
                showConfirmButton: false
            });
        });
        </script>
    @endif
    <table class="table table-bordered" id="permintaan-table">
        <thead>
            <tr>
                <th>No</th>
                <th>No Permintaan</th>
                <th class="permintaan-col-obat">Nama Items</th>
                <th class="permintaan-col-qty text-right">Diminta</th>
                <th class="permintaan-col-qty text-right">Terpenuhi</th>
                <th class="text-right">Tanggal</th>
                <th class="text-right">Total Nilai Pembelian</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
    <div class="permintaan-total-summary">
        <div class="permintaan-total-summary-card">
            <span class="permintaan-total-summary-label">Total Nilai Pembelian</span>
            <div class="permintaan-total-summary-value" id="permintaanTotalNilaiPembelian">Rp 0</div>
        </div>
    </div>

    <div class="modal fade" id="nilaiPembelianModal" tabindex="-1" role="dialog" aria-labelledby="nilaiPembelianModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="nilaiPembelianModalLabel">Nilai Pembelian</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <div><strong>No Permintaan:</strong> <span id="nilaiPembelianNoPermintaan">-</span></div>
                        <div><strong>Tanggal Permintaan:</strong> <span id="nilaiPembelianTanggal">-</span></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Obat Diminta</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Harga</th>
                                    <th class="text-right">Diskon</th>
                                    <th class="text-right">Setelah Diskon</th>
                                    <th class="text-right">PPN</th>
                                    <th class="text-right">Total Harga</th>
                                </tr>
                            </thead>
                            <tbody id="nilaiPembelianTableBody"></tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-right font-weight-bold">Total Nilai Permintaan Pembelian</th>
                                    <th class="text-right font-weight-bold" id="nilaiPembelianSubtotalDiskon">Rp 0</th>
                                    <th class="text-right font-weight-bold" id="nilaiPembelianPpn">Rp 0</th>
                                    <th class="text-right font-weight-bold" id="nilaiPembelianGrandTotal">Rp 0</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    function alignPermintaanStackRows() {
        $('#permintaan-table tbody tr').each(function() {
            var $cells = $(this).children('td');
            var groups = [
                $cells.eq(2).find('.permintaan-stack-row'),
                $cells.eq(3).find('.permintaan-stack-row'),
                $cells.eq(4).find('.permintaan-stack-row')
            ];

            groups.forEach(function($rows) {
                $rows.css('min-height', '48px');
            });

            var maxRows = Math.max(groups[0].length, groups[1].length, groups[2].length);

            for (var index = 0; index < maxRows; index++) {
                var maxHeight = 48;

                groups.forEach(function($rows) {
                    var $row = $rows.eq(index);
                    if ($row.length) {
                        maxHeight = Math.max(maxHeight, $row.outerHeight());
                    }
                });

                groups.forEach(function($rows) {
                    var $row = $rows.eq(index);
                    if ($row.length) {
                        $row.css('min-height', maxHeight + 'px');
                    }
                });
            }
        });
    }

    function formatRupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }

    function formatTanggalIndonesia(value) {
        if (!value) {
            return '-';
        }

        var parts = value.split('-');
        if (parts.length !== 3) {
            return value;
        }

        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    var defaultStartDate = moment().subtract(3, 'months');
    var defaultEndDate = moment();

    $('#requestDateRange').daterangepicker({
        startDate: defaultStartDate,
        endDate: defaultEndDate,
        autoUpdateInput: true,
        locale: {
            format: 'YYYY-MM-DD',
            cancelLabel: 'Clear'
        }
    });

    $('#requestDateRange').val(defaultStartDate.format('YYYY-MM-DD') + ' - ' + defaultEndDate.format('YYYY-MM-DD'));

    var table = $('#permintaan-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('erm.permintaan.data') }}',
            data: function(d) {
                d.request_date_range = $('#requestDateRange').val();
                d.principal_id = $('#principalFilter').val();
                d.status = $('#statusFilter').val();
            }
        },
        search: {
            return: true
        },
        columns: [
            { data: 'no', name: 'no', orderable: false, searchable: false },
            { data: 'no_permintaan', name: 'no_permintaan' },
            { data: 'obats', name: 'obats', orderable: false },
            { data: 'qty_diminta', name: 'qty_diminta', orderable: false, searchable: false },
            { data: 'qty_terpenuhi', name: 'qty_terpenuhi', orderable: false, searchable: false },
            {
                data: 'request_date',
                name: 'request_date',
                render: function(data, type) {
                    if (type !== 'display' || !data) {
                        return data;
                    }

                    return formatTanggalIndonesia(data);
                }
            },
            {
                data: 'total_nilai_pembelian',
                name: 'total_nilai_pembelian',
                searchable: false,
                render: function(data, type, row) {
                    if (type !== 'display') {
                        return data;
                    }

                    return '<div class="d-flex align-items-center justify-content-end">'
                        + '<span>' + formatRupiah(data) + '</span>'
                        + '<button type="button" class="btn btn-outline-secondary btn-sm ml-2 btn-nilai-pembelian btn-lihat-nilai-pembelian" data-id="' + row.id + '" title="Lihat Nilai Pembelian">'
                        + '<i class="fa fa-eye"></i>'
                        + '</button>'
                        + '</div>';
                },
                className: 'text-right font-weight-medium'
            },
            { 
                data: 'status', 
                name: 'status',
                render: function(data, type, row) {
                    if (data === 'waiting_approval' || data === 'waiting' || data === 'menunggu') {
                        return '<span class="badge badge-warning text-dark">Waiting Approval</span>';
                    } else if (data === 'approved' || data === 'disetujui') {
                        let html = '<span class="badge badge-success">Approved</span>';
                        if (row.approved_by_name) {
                            html += '<br><small class="text-muted">by: ' + row.approved_by_name + '</small>';
                        }
                        return html;
                    } else if (data === 'rejected' || data === 'ditolak') {
                        return '<span class="badge badge-danger">Rejected</span>';
                    } else {
                        return '<span class="badge badge-secondary">'+data+'</span>';
                    }
                }
            },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false },
        ],
        language: {
            search: "Cari:",
            searchPlaceholder: "Cari no permintaan, pemasok, obat, dll...",
            processing: "Sedang memproses...",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(difilter dari _MAX_ total data)",
            paginate: {
                first: "Pertama",
                last: "Terakhir", 
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        },
        drawCallback: function() {
            alignPermintaanStackRows();
        }
    });

    table.on('xhr.dt', function(e, settings, json) {
        $('#permintaanTotalNilaiPembelian').text(formatRupiah(json && json.total_nilai_pembelian_filtered ? json.total_nilai_pembelian_filtered : 0));
    });

    $('#permintaanTableFilters').prependTo('#permintaan-table_filter').removeClass('d-none');

    $(window).on('resize', function() {
        alignPermintaanStackRows();
    });

    $('#requestDateRange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        table.ajax.reload();
    });

    $('#requestDateRange').on('cancel.daterangepicker', function() {
        $(this).val('');
        table.ajax.reload();
    });

    $('#principalFilter, #statusFilter').on('change', function() {
        table.ajax.reload();
    });

    $('#resetRequestDateRange').on('click', function() {
        $('#requestDateRange').data('daterangepicker').setStartDate(defaultStartDate);
        $('#requestDateRange').data('daterangepicker').setEndDate(defaultEndDate);
        $('#requestDateRange').val(defaultStartDate.format('YYYY-MM-DD') + ' - ' + defaultEndDate.format('YYYY-MM-DD'));
        $('#principalFilter').val('');
        $('#statusFilter').val('');
        table.ajax.reload();
    });

    // Approve button AJAX
    $('#permintaan-table').on('click', '.btn-approve', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Approve permintaan ini?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Approve',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '/erm/permintaan/' + id + '/approve',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: 'Permintaan berhasil diapprove!',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        let msg = 'Gagal approve permintaan!';
                        if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: msg,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
            }
        });
    });

    $('#permintaan-table').on('click', '.btn-reject', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Reject permintaan ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Reject',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '/erm/permintaan/' + id + '/reject',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.message || 'Permintaan berhasil direject!',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        let msg = 'Gagal reject permintaan!';
                        if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: msg,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
            }
        });
    });

    $('#permintaan-table').on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus permintaan ini?',
            text: 'Data permintaan dan itemnya akan dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '/erm/permintaan/' + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.message || 'Permintaan berhasil dihapus!',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        let msg = 'Gagal menghapus permintaan!';
                        if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: msg,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
            }
        });
    });

    $('#permintaan-table').on('click', '.btn-nilai-pembelian', function() {
        var id = $(this).data('id');

        $.ajax({
            url: '/erm/permintaan/' + id + '/nilai-pembelian',
            type: 'GET',
            success: function(res) {
                if (!res.success) {
                    Swal.fire('Gagal', 'Data nilai pembelian tidak tersedia.', 'error');
                    return;
                }

                $('#nilaiPembelianNoPermintaan').text(res.no_permintaan || '-');
                $('#nilaiPembelianTanggal').text(formatTanggalIndonesia(res.request_date));

                var rows = '';
                (res.items || []).forEach(function(item) {
                    var diskonLabel = item.diskon_type === 'percent' || item.diskon_type === '%' || item.diskon_type === 'persen'
                        ? item.diskon + '%'
                        : formatRupiah(item.diskon);
                    var principalBadge = item.principal_name && item.principal_name !== '-'
                        ? '<span class="badge badge-info mr-1">' + item.principal_name + '</span>'
                        : '';
                    var jenisBadgeClass = item.jenis_obat === 'Generik' ? 'badge-success' : 'badge-primary';
                    var jenisBadge = item.jenis_obat
                        ? '<span class="badge ' + jenisBadgeClass + '">' + item.jenis_obat + '</span>'
                        : '';

                    rows += '<tr>' +
                        '<td>' + (item.nama_obat || '-') + '<br><div class="mt-1">' + principalBadge + jenisBadge + '</div></td>' +
                        '<td class="text-right">' + Number(item.qty || 0).toLocaleString('id-ID') + ' (' + Number(item.box || 0).toLocaleString('id-ID') + ' Box)</td>' +
                        '<td class="text-right">' + formatRupiah(item.harga) + '</td>' +
                        '<td class="text-right">' + diskonLabel + '</td>' +
                        '<td class="text-right">' + formatRupiah(item.setelah_diskon) + '</td>' +
                        '<td class="text-right">' + formatRupiah(item.ppn) + '<br><small class="text-muted">' + item.ppn_rate + '%</small></td>' +
                        '<td class="text-right font-weight-bold">' + formatRupiah(item.total_harga) + '</td>' +
                        '</tr>';
                });

                if (!rows) {
                    rows = '<tr><td colspan="7" class="text-center text-muted">Belum ada item.</td></tr>';
                }

                $('#nilaiPembelianTableBody').html(rows);
                $('#nilaiPembelianSubtotalDiskon').text(formatRupiah(res.summary?.setelah_diskon));
                $('#nilaiPembelianPpn').text(formatRupiah(res.summary?.ppn));
                $('#nilaiPembelianGrandTotal').text(formatRupiah(res.summary?.total_harga));
                $('#nilaiPembelianModal').modal('show');
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal mengambil data nilai pembelian.';
                Swal.fire('Gagal', msg, 'error');
            }
        });
    });
});
</script>
@endsection

