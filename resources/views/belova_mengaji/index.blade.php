@extends('layouts.erm.app')
@section('title', 'Belova Mengaji')
@section('navbar')
    @include('layouts.erm.navbar-ngaji')
@endsection  

@push('scripts')
<script>
    $(function() {
        // Initialize employees DataTable (server-side via Yajra)
        var empTable = $('#employees-table').DataTable({
            processing: true,
            serverSide: false, // switch to client-side so we can show all rows on first load
            ajax: {
                url: "{{ route('belova.mengaji.employees.data') }}",
                data: function(d) {
                    d.date = $('#filter-date').val();
                }
            },
            // show all rows on first load; user can change to 10/25/50 via the length menu
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
            pageLength: -1,
            columns: [
                { data: 'nama', name: 'nama' },
                { data: 'nilai_makhroj', name: 'nilai_makhroj', orderable: false, searchable: false },
                { data: 'nilai_tajwid', name: 'nilai_tajwid', orderable: false, searchable: false },
                { data: 'nilai_panjang_pendek', name: 'nilai_panjang_pendek', orderable: false, searchable: false },
                { data: 'nilai_kelancaran', name: 'nilai_kelancaran', orderable: false, searchable: false },
                    { 
                    data: 'total_nilai', 
                    name: 'total_nilai', 
                    orderable: false, 
                    searchable: false,
                    render: function(data, type, row) {
                        // show empty as empty
                        if (data === null || data === '') return '';
                        // try to parse number
                        var n = parseFloat(data);
                        if (isNaN(n)) return data;
                        var out;
                        // if integer, show without decimals
                        if (Math.floor(n) === n) out = n.toString();
                        else out = n.toFixed(2).replace(/\.00$/,'');
                        // wrap in strong to ensure bold even if other rules override
                        return '<strong class="total-value">' + out + '</strong>';
                    }
                },
                { data: 'catatan', name: 'catatan', orderable: false, searchable: false }
            ],
            responsive: true,
            order: [[0, 'asc']]
        });

        // redraw table when date filter changes
        $('#filter-date').on('change', function() {
            empTable.ajax.reload();
        });

        // Auto-save when any ngaji select or catatan changes
        $(document).on('change', '.ngaji-select, .ngaji-catatan', function() {
            var $row = $(this);
            var employeeId = $(this).data('employee');
            var date = $('#filter-date').val() || new Date().toISOString().slice(0,10);

            // collect values for the row (select inputs by data-employee attribute)
            var nilai_makhroj = $('select[data-employee="' + employeeId + '"][data-field="nilai_makhroj"]').val();
            var nilai_tajwid = $('select[data-employee="' + employeeId + '"][data-field="nilai_tajwid"]').val();
            var nilai_panjang_pendek = $('select[data-employee="' + employeeId + '"][data-field="nilai_panjang_pendek"]').val();
            var nilai_kelancaran = $('select[data-employee="' + employeeId + '"][data-field="nilai_kelancaran"]').val();
            var catatan = $('input.ngaji-catatan[data-employee="' + employeeId + '"]').val();

            var payload = {
                _token: '{{ csrf_token() }}',
                employee_id: employeeId,
                date: date,
                nilai_makhroj: nilai_makhroj,
                nilai_tajwid: nilai_tajwid,
                nilai_panjang_pendek: nilai_panjang_pendek,
                nilai_kelancaran: nilai_kelancaran,
                catatan: catatan
            };

            $.post("{{ route('belova.mengaji.store') }}", payload)
                .done(function(res) {
                    if (res && res.ok) {
                        // update total cell in the row
                        // find the cell that contains total for this employee (closest row)
                        // simplest way: reload the row via table draw
                        empTable.ajax.reload(null, false);
                    }
                })
                .fail(function(xhr) {
                    Swal.fire({ icon: 'error', title: 'Gagal menyimpan', text: 'Terjadi kesalahan saat menyimpan nilai.' });
                });
        });
    });
</script>
@endpush

@push('styles')
<style>
    /* Make table use fixed layout so columns with explicit widths stay consistent */
    #employees-table {
        table-layout: fixed !important;
        width: 100% !important;
    }

    /* Only the four score columns (2..5) should have equal fixed widths.
       Other columns (Nama, Total, Catatan) will remain flexible. */
    /* Apply width to header and body cells for the specific columns */
    #employees-table thead th:nth-child(2),
    #employees-table tbody td:nth-child(2),
    #employees-table thead th:nth-child(3),
    #employees-table tbody td:nth-child(3),
    #employees-table thead th:nth-child(4),
    #employees-table tbody td:nth-child(4),
    #employees-table thead th:nth-child(5),
    #employees-table tbody td:nth-child(5) {
        width: 12% !important; /* equal width for the 4 score columns */
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap; /* keep selects inline and consistent */
        vertical-align: middle;
    }

    /* Let Nama (1), Total (6), and Catatan (7) size naturally */
    #employees-table thead th:nth-child(1),
    #employees-table tbody td:nth-child(1),
    #employees-table thead th:nth-child(6),
    #employees-table tbody td:nth-child(6),
    #employees-table thead th:nth-child(7),
    #employees-table tbody td:nth-child(7) {
        white-space: normal;
    }

    /* Make selects and inputs full width inside their table cells */
    #employees-table .form-control {
        width: 100%;
        box-sizing: border-box;
    }

    /* Improve spacing for the catatan input to avoid cutting off text */
    #employees-table .ngaji-catatan {
        padding: .375rem .5rem;
    }

    /* Make Total column (6th) values bold */
    #employees-table tbody td:nth-child(6) {
        font-weight: 700 !important;
    }

    /* Ensure strong.total-value inside the Total column is bold (extra specificity) */
    #employees-table tbody td:nth-child(6) strong.total-value {
        font-weight: 700 !important;
        display: inline-block;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Penilaian Mengaji Pegawai</h4>
                    <div class="d-flex align-items-center">
                        <label for="filter-date" class="mr-2 mb-0">Tanggal</label>
                        <input id="filter-date" type="date" class="form-control" value="{{ request('date', date('Y-m-d')) }}">
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <table id="employees-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                        <colgroup>
                            <col>
                            <col style="width:12%">
                            <col style="width:12%">
                            <col style="width:12%">
                            <col style="width:12%">
                            <col>
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Makhroj</th>
                                <th>Tajwid</th>
                                <th>Panjang / Pendek</th>
                                <th>Kelancaran</th>
                                <th>Total</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- single table only; ngaji records are input inline above -->
    </div>
    </div>
@endsection
