@extends('layouts.hrd.app')
@section('title', 'HRD | Pertanyaan Evaluasi')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container">
    <h2>Performance Evaluation Results</h2>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="periods-table" class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Period Name</th>
                            <th>Date Range</th>
                            <th>Evaluations</th>
                            <th>Actions</th>
                            <th>Download</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#periods-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('hrd.performance.results.data') }}",
            columns: [
                { data: 'name', name: 'name' },
                { data: 'date_range', name: 'date_range', orderable: false, searchable: false },
                { data: 'evaluations', name: 'evaluations', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'download', name: 'download', orderable: false, searchable: false }
            ],
            order: [[0, 'asc']],
            responsive: true,
            language: {
                paginate: {
                    previous: "<i class='mdi mdi-chevron-left'>",
                    next: "<i class='mdi mdi-chevron-right'>"
                },
                emptyTable: "No completed evaluation periods found. Once periods are marked as completed, their results will appear here."
            },
            drawCallback: function() {
                $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
            }
        });
        // Event delegation for download button
        $('#periods-table').on('click', '.btn-download-score', function() {
            var periodId = $(this).data('period-id');
            window.location.href = '/hrd/performance/results/periods/' + periodId + '/download-score';
        });
    });
</script>
@endpush
@endsection