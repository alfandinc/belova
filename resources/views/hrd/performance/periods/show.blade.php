@extends('layouts.hrd.app')
@section('title', 'HRD | Tambah Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Period: {{ $period->name }}</h2>
            <p>
                <span class="badge badge-{{ $period->status == 'pending' ? 'warning' : ($period->status == 'active' ? 'primary' : 'success') }}">
                    {{ ucfirst($period->status) }}
                </span>
                <span class="ml-3">{{ $period->start_date->format('d M Y') }} - {{ $period->end_date->format('d M Y') }}</span>
            </p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('hrd.performance.periods.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Periods
            </a>
            
            @if($period->status == 'pending')
                <form action="{{ route('hrd.performance.periods.initiate', $period) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to initiate this evaluation period?')">
                        <i class="fa fa-play"></i> Initiate Evaluations
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Progress Overview</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Evaluations:</span>
                        <strong>{{ count($evaluations) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Completed:</span>
                        <strong>{{ $completedCount }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Pending:</span>
                        <strong>{{ $pendingCount }}</strong>
                    </div>
                    <div class="progress mt-3">
                        @php
                            $progressPercent = count($evaluations) > 0 ? round(($completedCount / count($evaluations)) * 100) : 0;
                        @endphp
                        <div class="progress-bar" role="progressbar" style="width: {{ $progressPercent }}%" aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100">
                            {{ $progressPercent }}%
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Actions</h5>
                </div>
                <div class="card-body">
                    @if($period->status == 'completed')
                        <a href="{{ route('hrd.performance.results.period', $period) }}" class="btn btn-info btn-block mb-2">
                            <i class="fa fa-chart-bar"></i> View Results
                        </a>
                    @endif
                    
                    @if($period->status == 'active')
                        <form id="completeForm" action="{{ route('hrd.performance.periods.update', $period) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="name" value="{{ $period->name }}">
                            <input type="hidden" name="start_date" value="{{ $period->start_date->format('Y-m-d') }}">
                            <input type="hidden" name="end_date" value="{{ $period->end_date->format('Y-m-d') }}">
                            <input type="hidden" name="status" value="completed">
                            <button type="button" id="markCompleteBtn" class="btn btn-success btn-block mb-2">
                                <i class="fa fa-check-circle"></i> Mark as Completed
                            </button>
                        </form>
                    @endif
                    
                    <button type="button" id="showQuestionsBtn" class="btn btn-primary btn-block">
                        <i class="fa fa-question-circle"></i> Detail Pertanyaan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Evaluation Assignments</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped" id="evaluationsTable">
                <thead>
                    <tr>
                        <th>Evaluator</th>
                        <th>Evaluatee</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($evaluations as $eval)
                    <tr>
                        <td>{{ $eval->evaluator->nama }}</td>
                        <td>{{ $eval->evaluatee->nama }}</td>
                        <td>
                            @php
                                $evaluatorDivision = $eval->evaluator->division->name ?? 'Unknown';
                                $evaluateeDivision = $eval->evaluatee->division->name ?? 'Unknown';
                                $isEvaluatorManager = $eval->evaluator->isManager();
                                $isEvaluateeManager = $eval->evaluatee->isManager();
                                $isEvaluatorHRD = strpos(strtolower($evaluatorDivision), 'hrd') !== false;
                                $isEvaluateeHRD = strpos(strtolower($evaluateeDivision), 'hrd') !== false;
                                
                                if ($isEvaluatorHRD && $isEvaluateeManager) {
                                    echo "HRD to Manager";
                                } elseif ($isEvaluatorManager && !$isEvaluateeManager && !$isEvaluateeHRD) {
                                    echo "Manager to Employee";
                                } elseif (!$isEvaluatorManager && !$isEvaluatorHRD && $isEvaluateeManager) {
                                    echo "Employee to Manager";
                                } elseif ($isEvaluatorManager && $isEvaluateeHRD) {
                                    echo "Manager to HRD";
                                } else {
                                    echo "Other";
                                }
                            @endphp
                        </td>
                        <td>
                            <span class="badge badge-{{ $eval->status == 'pending' ? 'warning' : 'success' }}">
                                {{ ucfirst($eval->status) }}
                            </span>
                            @if($eval->completed_at)
                                <small class="d-block text-muted">{{ $eval->completed_at->format('d M Y') }}</small>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Questions Modal -->
<div class="modal fade" id="questionsModal" tabindex="-1" role="dialog" aria-labelledby="questionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="questionsModalLabel">Detail Pertanyaan Evaluasi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4" id="loadingQuestions">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading questions...</p>
                </div>
                <div id="questionsContainer" style="display: none;">
                    <!-- Questions will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#evaluationsTable').DataTable({
            responsive: true,
            pageLength: 25,
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                emptyTable: "No evaluations found."
            },
            columnDefs: [
                { className: "align-middle", targets: '_all' },
                { className: "text-center", targets: [2, 3] }
            ]
        });

        // Mark as completed button handler
        $('#markCompleteBtn').on('click', function() {
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to mark this evaluation period as completed. This will finalize all evaluations.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, mark as completed!'
            }).then((result) => {
                if (result.value) {
                    // Submit the form
                    $('#completeForm').submit();
                }
            });
        });
        
        // Show Questions button handler
        $('#showQuestionsBtn').on('click', function() {
            // Show modal with loading indicator
            $('#questionsModal').modal('show');
            $('#loadingQuestions').show();
            $('#questionsContainer').hide();
            
            // Load questions via AJAX
            $.ajax({
                url: "{{ route('hrd.performance.questions.getAll') }}",
                method: "GET",
                success: function(response) {
                    // Process and display questions
                    displayQuestions(response);
                },
                error: function(xhr) {
                    // Handle error
                    $('#loadingQuestions').hide();
                    $('#questionsContainer').html('<div class="alert alert-danger">Failed to load questions. Please try again.</div>').show();
                }
            });
        });
        
        // Function to display questions grouped by category
        function displayQuestions(data) {
            if (!data || data.length === 0) {
                $('#loadingQuestions').hide();
                $('#questionsContainer').html('<div class="alert alert-info">No questions found for this evaluation period.</div>').show();
                return;
            }
            
            // Group questions by category
            var questionsByCategory = {};
            
            // First pass: collect all categories
            data.forEach(function(question) {
                if (!questionsByCategory[question.category_name]) {
                    questionsByCategory[question.category_name] = {
                        id: question.category_id,
                        name: question.category_name,
                        description: question.category_description || '',
                        questions: []
                    };
                }
                
                // Add question to its category
                questionsByCategory[question.category_name].questions.push({
                    id: question.id,
                    text: question.question_text,
                    evaluationType: question.evaluation_type,
                    isActive: question.is_active
                });
            });
            
            // Build HTML for categories and questions
            var html = '<div class="accordion" id="questionsAccordion">';
            
            // For each category
            var categoryIndex = 0;
            for (var categoryName in questionsByCategory) {
                var category = questionsByCategory[categoryName];
                var categoryId = 'category-' + category.id;
                var headingId = 'heading-' + category.id;
                var collapseId = 'collapse-' + category.id;
                
                html += '<div class="card mb-2">';
                html += '  <div class="card-header" id="' + headingId + '">';
                html += '    <h5 class="mb-0">';
                html += '      <button class="btn btn-link' + (categoryIndex === 0 ? '' : ' collapsed') + '" type="button" data-toggle="collapse" data-target="#' + collapseId + '" aria-expanded="' + (categoryIndex === 0 ? 'true' : 'false') + '" aria-controls="' + collapseId + '">';
                html += '        ' + category.name + ' (' + category.questions.length + ' pertanyaan)';
                html += '      </button>';
                html += '    </h5>';
                html += '  </div>';
                
                html += '  <div id="' + collapseId + '" class="collapse' + (categoryIndex === 0 ? ' show' : '') + '" aria-labelledby="' + headingId + '" data-parent="#questionsAccordion">';
                html += '    <div class="card-body">';
                
                if (category.description) {
                    html += '    <p class="text-muted">' + category.description + '</p>';
                }
                
                // Add table of questions for this category
                html += '    <table class="table table-sm">';
                html += '      <thead>';
                html += '        <tr>';
                html += '          <th>#</th>';
                html += '          <th>Question</th>';
                html += '          <th>Type</th>';
                html += '          <th>Status</th>';
                html += '        </tr>';
                html += '      </thead>';
                html += '      <tbody>';
                
                // Add each question row
                category.questions.forEach(function(question, index) {
                    html += '    <tr>';
                    html += '      <td>' + (index + 1) + '</td>';
                    html += '      <td>' + question.text + '</td>';
                    html += '      <td>' + formatEvaluationType(question.evaluationType) + '</td>';
                    html += '      <td>' + (question.isActive ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>') + '</td>';
                    html += '    </tr>';
                });
                
                html += '      </tbody>';
                html += '    </table>';
                html += '    </div>';
                html += '  </div>';
                html += '</div>';
                
                categoryIndex++;
            }
            
            html += '</div>';
            
            // Update the container
            $('#loadingQuestions').hide();
            $('#questionsContainer').html(html).show();
        }
        
        // Helper function to format evaluation type
        function formatEvaluationType(type) {
            switch(type) {
                case 'hrd_to_manager':
                    return 'HRD to Manager';
                case 'manager_to_employee':
                    return 'Manager to Employee';
                case 'employee_to_manager':
                    return 'Employee to Manager';
                case 'manager_to_hrd':
                    return 'Manager to HRD';
                default:
                    return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            }
        }
    });
</script>
@endsection