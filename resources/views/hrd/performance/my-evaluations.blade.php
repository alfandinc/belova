@extends('layouts.hrd.app')
@section('title', 'HRD | Evaluasi Kinerja')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection


@section('content')
<div class="container">
    <h2>My Performance Evaluations</h2>
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    
    <ul class="nav nav-tabs mb-4" id="evaluationTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="pending-tab" data-toggle="tab" href="#pending" role="tab">
                Pending Evaluations <span class="badge badge-warning" id="pending-count">0</span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="completed-tab" data-toggle="tab" href="#completed" role="tab">
                Completed Evaluations <span class="badge badge-success" id="completed-count">0</span>
            </a>
        </li>
    </ul>
    
    <div class="tab-content" id="evaluationTabContent">
        <div class="tab-pane fade show active" id="pending" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="pending-evaluations-table" class="table table-bordered table-hover" width="100%">
                            <thead>
                                <tr>
                                    <th>Evaluation Period</th>
                                    <th>Evaluatee</th>
                                    <th>Position</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="tab-pane fade" id="completed" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="completed-evaluations-table" class="table table-bordered" width="100%">
                            <thead>
                                <tr>
                                    <th>Evaluation Period</th>
                                    <th>Evaluatee</th>
                                    <th>Position</th>
                                    <th>Completed On</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Set up CSRF token for all Ajax requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Initialize DataTables
        let pendingTable = $('#pending-evaluations-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('hrd.performance.my-evaluations') }}",
                data: function(d) {
                    d.type = 'pending';
                }
            },
            columns: [
                {data: 'period_name', name: 'period_id'},
                {data: 'evaluatee_name', name: 'evaluatee_id'},
                {data: 'position', name: 'position', orderable: false},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ],
            drawCallback: function(settings) {
                $('#pending-count').text(settings.json.recordsTotal);
            }
        });

        let completedTable = $('#completed-evaluations-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('hrd.performance.my-evaluations') }}",
                data: function(d) {
                    d.type = 'completed';
                }
            },
            columns: [
                {data: 'period_name', name: 'period_id'},
                {data: 'evaluatee_name', name: 'evaluatee_id'},
                {data: 'position', name: 'position', orderable: false},
                {data: 'completed_at', name: 'completed_at'}
            ],
            drawCallback: function(settings) {
                $('#completed-count').text(settings.json.recordsTotal);
            }
        });

        // Refresh tables when switching tabs
        $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
            const target = $(e.target).attr('href');
            if (target === '#pending') {
                pendingTable.ajax.reload();
            } else if (target === '#completed') {
                completedTable.ajax.reload();
            }
        });
    });
</script>
@endpush