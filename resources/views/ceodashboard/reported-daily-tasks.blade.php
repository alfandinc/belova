@extends('layouts.erm.app')

@section('title', 'CEO Dashboard - Daily Task Report')

@section('navbar')
    @include('layouts.ceodashboard.navbar')
@endsection

@section('content')
    <div class="container-fluid mt-3">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                            <div>
                                <h4 class="card-title mb-1">Daily Task Report</h4>
                                <p class="text-muted mb-0">Menampilkan task harian dengan status report = true.</p>
                            </div>
                            <div class="d-flex align-items-center" style="gap:8px;">
                                <div class="form-group mb-0">
                                    <input type="text" id="filter-daterange" class="form-control form-control-sm" placeholder="Pilih rentang tanggal" autocomplete="off">
                                </div>
                                <div class="form-group mb-0">
                                    <select id="filter-division" class="form-control form-control-sm">
                                        <option value="">Semua Divisi</option>
                                        @foreach($divisions as $division)
                                            <option value="{{ $division->id }}">{{ $division->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <button id="filter-clear" class="btn btn-sm btn-light">Reset</button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped w-100" id="reported-daily-tasks-table">
                                <thead>
                                    <tr>
                                        <th>Divisi</th>
                                        <th>Title</th>
                                        <th>Note</th>
                                        <th>Status</th>
                                        <th>Updated</th>
                                    </tr>
                                </thead>
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
        $(document).ready(function () {
            function escapeHtml(value) {
                return $('<div>').text(value ?? '').html();
            }

            // initialize date range picker (requires daterangepicker & moment to be loaded in layout)
            if ($.fn.daterangepicker) {
                $('#filter-daterange').daterangepicker({
                    autoApply: true,
                    locale: { format: 'YYYY-MM-DD', cancelLabel: 'Clear' },
                    opens: 'left'
                });

                $('#filter-daterange').on('apply.daterangepicker', function(ev, picker) {
                    $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                    if (window.reportedTable) window.reportedTable.ajax.reload();
                });

                $('#filter-daterange').on('cancel.daterangepicker', function() {
                    $(this).val('');
                    if (window.reportedTable) window.reportedTable.ajax.reload();
                });
            }

            var tableConfig = {
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('ceo-dashboard.daily-tasks.index') }}",
                    data: function(d) {
                        var dr = $('#filter-daterange').val();
                        if (dr && dr.indexOf(' - ') !== -1) {
                            var parts = dr.split(' - ');
                            d.start = parts[0];
                            d.end = parts[1];
                        }
                        d.division_id = $('#filter-division').val();
                    }
                },
                order: [[4, 'desc'], [0, 'desc']],
                columns: [
                    { data: 'division_name', name: 'division_name', defaultContent: '-' },
                    {
                        data: 'title',
                        name: 'title',
                        render: function (data, type, row) {
                            if (type !== 'display') {
                                return data;
                            }

                            var taskDate = row.task_date || '-';
                            var deadlineDate = row.deadline_date || '-';

                            return '' +
                                '<div>' +
                                    '<div class="font-weight-bold text-dark">' + escapeHtml(data || '-') + '</div>' +
                                    '<div class="mt-2 d-flex flex-wrap" style="gap:8px;">' +
                                        '<span class="badge badge-soft-primary"><i class="far fa-calendar-alt mr-1"></i>' + escapeHtml(taskDate) + '</span>' +
                                        '<span class="badge badge-soft-warning"><i class="fas fa-hourglass-half mr-1"></i>' + escapeHtml(deadlineDate) + '</span>' +
                                    '</div>' +
                                '</div>';
                        }
                    },
                    { data: 'note', name: 'note', defaultContent: '-' },
                    { data: 'status', name: 'status', orderable: false, searchable: false },
                    { data: 'updated_at_display', name: 'updated_at' }
                ],
                language: {
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ task',
                    zeroRecords: 'Tidak ada task yang ditemukan',
                    processing: 'Memuat data...'
                }
            };

            window.reportedTable = $('#reported-daily-tasks-table').DataTable(tableConfig);

            // auto apply when division changes
            $('#filter-division').on('change', function() {
                if (window.reportedTable) window.reportedTable.ajax.reload();
            });

            // reset filters
            $('#filter-clear').on('click', function() {
                $('#filter-daterange').val('');
                $('#filter-division').val('');
                if (window.reportedTable) window.reportedTable.ajax.reload();
            });

        });
    </script>
@endsection