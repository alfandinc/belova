@extends('layouts.hrd.app')
@section('title', 'HRD | Pertanyaan Evaluasi')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<!-- This is the PERIOD results view -->
<!-- Variables available: $period, $averageScores -->
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Evaluation Results</h2>
            <p>Period: {{ $period->name }} ({{ $period->start_date->format('d M Y') }} - {{ $period->end_date->format('d M Y') }})</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('hrd.performance.results.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Results
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Employee Performance Results</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="employee-scores-table" class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Employee Name</th>
                            <th>Position</th>
                            <th>Division</th>
                            <th>Overall Score</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#employee-scores-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('hrd.performance.results.period.data', $period) }}",
            columns: [
                { data: 'name', name: 'name' },
                { data: 'position', name: 'position' },
                { data: 'division', name: 'division' },
                { data: 'score', name: 'score', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [[0, 'asc']],
            responsive: true,
            language: {
                paginate: {
                    previous: "<i class='mdi mdi-chevron-left'>",
                    next: "<i class='mdi mdi-chevron-right'>"
                },
                emptyTable: "No completed evaluations found for this period."
            },
            drawCallback: function() {
                $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
            }
        });
    });
</script>
@endpush

</div>

@endsection