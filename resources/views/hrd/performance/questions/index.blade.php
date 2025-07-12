@extends('layouts.hrd.app')
@section('title', 'HRD | Pertanyaan Evaluasi')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Performance Evaluation Questions</h2>
        </div>
        {{-- <div class="col-md-4 text-right">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" id="addQuestionBtn">
                    <i class="fa fa-plus"></i> New Question
                </button>
                <button type="button" class="btn btn-secondary" id="addCategoryBtn">
                    <i class="fa fa-plus"></i> New Category
                </button>
            </div>
        </div> --}}
    </div>

    <!-- Tabs for Categories and Questions -->
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="performanceTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="categories-tab" data-toggle="tab" href="#categories" role="tab">Categories</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="questions-tab" data-toggle="tab" href="#questions" role="tab">Questions by Category</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="performanceTabsContent">
                <!-- Categories Tab -->
                <div class="tab-pane fade show active" id="categories" role="tabpanel">
                    <div class="mb-3">
                        <button type="button" class="btn btn-primary" id="categoriesTabAddBtn">
                            <i class="fa fa-plus"></i> Add New Category
                        </button>
                    </div>
                    <table class="table table-bordered" id="categoriesTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
                
                <!-- Questions Grouped by Category Tab -->
                <div class="tab-pane fade" id="questions" role="tabpanel">
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-primary" id="questionsTabAddBtn">
                            <i class="fa fa-plus"></i> Add New Question
                        </button>
                        <div class="form-group mb-0">
                            <input type="text" class="form-control" id="questionSearch" placeholder="Search questions...">
                        </div>
                    </div>
                    <div id="questionsGrouped">
                        <!-- Categories and their questions will be loaded here dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalTitle">Add Category</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <input type="hidden" name="category_id" id="category_id_hidden">
                    <div class="form-group">
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback" id="name-error"></div>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        <div class="invalid-feedback" id="description-error"></div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" checked>
                            <label class="custom-control-label" for="is_active">Active</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveCategoryBtn">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Question Modal -->
<div class="modal fade" id="questionModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="questionModalTitle">Add Question</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="questionForm">
                    <input type="hidden" name="question_id" id="question_id">
                    <div class="form-group">
                        <label for="question_text">Question <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
                        <div class="invalid-feedback" id="question_text-error"></div>
                    </div>
                    <div class="form-group">
                        <label for="category_id">Category <span class="text-danger">*</span></label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                        </select>
                        <div class="invalid-feedback" id="category_id-error"></div>
                    </div>
                    <div class="form-group">
                        <label for="question_type">Question Type <span class="text-danger">*</span></label>
                        <select class="form-control" id="question_type" name="question_type" required>
                            <option value="score">Score (1-5)</option>
                            <option value="text">Text Answer</option>
                        </select>
                        <div class="invalid-feedback" id="question_type-error"></div>
                    </div>
                    <div class="form-group">
                        <label for="evaluation_type">Evaluation Type <span class="text-danger">*</span></label>
                        <select class="form-control" id="evaluation_type" name="evaluation_type" required>
                            <option value="">Select Type</option>
                            @foreach($evaluationTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="evaluation_type-error"></div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="question_is_active" name="is_active" value="1" checked>
                            <label class="custom-control-label" for="question_is_active">Active</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveQuestionBtn">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Helper function for consistent Select2 initialization
        function initSelect2(selector) {
            try {
                // Check if element exists
                if ($(selector).length === 0) {
                    console.warn('Element not found:', selector);
                    return false;
                }
                
                // First remove any previous Select2 instances
                if ($(selector).hasClass("select2-hidden-accessible")) {
                    $(selector).select2('destroy');
                }
                
                // Initialize with minimal options to avoid compatibility issues
                $(selector).select2({
                    width: '100%',
                    // Add minimumResultsForSearch to prevent inputData error
                    minimumResultsForSearch: 10
                });
                console.log('Select2 initialized for', selector);
                return true;
            } catch (e) {
                console.error('Error initializing Select2 for', selector, e);
                return false;
            }
        }
        
        // Initialize DataTables for categories
        var categoriesTable = $('#categoriesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('hrd.performance.categories.data') }}",
            columns: [
                { data: 'name', name: 'name' },
                { data: 'description', name: 'description', defaultContent: '-' },
                { data: 'status', name: 'status', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });
        
        // Preload categories for dropdown to ensure they're available when needed
        $.ajax({
            url: "{{ route('hrd.performance.categories.active') }}",
            method: 'GET',
            success: function(response) {
                console.log('Categories preloaded successfully:', response.length, 'categories');
            },
            error: function(xhr) {
                console.error('Failed to preload categories:', xhr.status, xhr.statusText);
            }
        });
        
        // Load questions grouped by category
        function loadGroupedQuestions() {
            $.ajax({
                url: "{{ route('hrd.performance.questions.grouped') }}",
                method: 'GET',
                success: function(categories) {
                    var html = '';
                    
                    if (categories.length === 0) {
                        html = '<div class="card"><div class="card-body text-center"><p>No question categories found.</p></div></div>';
                    } else {
                        categories.forEach(function(category) {
                            html += `
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">${category.name}</h5>
                                    <div class="btn-group">
                                        <button data-id="${category.id}" class="edit-category btn btn-sm btn-outline-primary">
                                            <i class="fa fa-edit"></i> Edit Category
                                        </button>
                                        <button data-id="${category.id}" class="delete-category btn btn-sm btn-outline-danger">
                                            <i class="fa fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    ${category.description ? `<p class="text-muted">${category.description}</p>` : ''}
                                    <table class="table table-bordered" id="category-${category.id}-table">
                                        <thead>
                                            <tr>
                                                <th width="40%">Question</th>
                                                <th>Evaluation Type</th>
                                                <th>Question Type</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
                                        
                            if (category.questions && category.questions.length > 0) {
                                category.questions.forEach(function(question) {
                                    var evaluationType = '';
                                    switch(question.evaluation_type) {
                                        case 'hrd_to_manager': evaluationType = 'HRD to Manager'; break;
                                        case 'manager_to_employee': evaluationType = 'Manager to Employee'; break;
                                        case 'employee_to_manager': evaluationType = 'Employee to Manager'; break;
                                        case 'manager_to_hrd': evaluationType = 'Manager to HRD'; break;
                                        default: evaluationType = 'Unknown';
                                    }
                                    
                                    var status = question.is_active ? 
                                        '<span class="badge badge-success">Active</span>' : 
                                        '<span class="badge badge-secondary">Inactive</span>';
                                        
                                    var questionType = question.question_type ? 
                                        (question.question_type === 'score' ? 'Score (1-5)' : 'Text Answer') : 
                                        'Score (1-5)';

                                    html += `
                                        <tr>
                                            <td>${question.question_text}</td>
                                            <td>${evaluationType}</td>
                                            <td>${questionType}</td>
                                            <td>${status}</td>
                                            <td>
                                                <button data-id="${question.id}" class="edit-question btn btn-sm btn-primary">
                                                    <i class="fa fa-edit"></i> Edit
                                                </button>
                                                <button data-id="${question.id}" class="delete-question btn btn-sm btn-danger">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>`;
                                });
                            } else {
                                html += '<tr><td colspan="5" class="text-center">No questions in this category.</td></tr>';
                            }
                            
                            html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>`;
                        });
                    }
                    
                    $('#questionsGrouped').html(html);
                    
                    // Don't initialize DataTables for the dynamically created tables
                    // as it's causing issues with cell indexing
                    // Instead, we'll use basic tables for the questions view
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load questions by category.'
                    });
                }
            });
        }

        // Load categories for dropdown with Select2
        function loadCategories(selectedCategoryId = null) {
            console.log('loadCategories function called');
            
            $.ajax({
                url: "{{ route('hrd.performance.categories.active') }}",
                method: 'GET',
                beforeSend: function() {
                    console.log('Sending request to load categories');
                },
                success: function(response) {
                    console.log('Categories loaded:', response);
                    // Clear and populate dropdown
                    $('#category_id').empty();
                    $('#category_id').append('<option value="">Select Category</option>');
                    $.each(response, function(index, category) {
                        $('#category_id').append('<option value="' + category.id + '">' + category.name + '</option>');
                    });
                    
                    console.log('Category options added:', $('#category_id option').length);
                    
                    // Use our helper function to initialize Select2
                    initSelect2('#category_id');
                    
                    // Set selected category if provided
                    if (selectedCategoryId) {
                        $('#category_id').val(selectedCategoryId).trigger('change');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Failed to load categories. Status:', status);
                    console.error('Error:', error);
                    console.error('Response:', xhr.responseText);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load categories. Please try again.'
                    });
                }
            });
        }

        // Category Modal Handlers
        $('#addCategoryBtn, #categoriesTabAddBtn').click(function() {
            $('#categoryForm').trigger('reset');
            $('#category_id_hidden').val('');
            $('#categoryModalTitle').text('Add Category');
            $('#categoryModal').modal('show');
        });

        // Edit Category
        $(document).on('click', '.edit-category', function() {
            var categoryId = $(this).data('id');
            $('#categoryModalTitle').text('Edit Category');
            
            // Reset form and clear previous errors
            $('#categoryForm').trigger('reset');
            clearFormErrors('#categoryForm');
            
            // Fetch category data
            $.ajax({
                url: "{{ route('hrd.performance.categories.get', '') }}/" + categoryId,
                method: 'GET',
                success: function(response) {
                    $('#category_id_hidden').val(response.id);
                    $('#name').val(response.name);
                    $('#description').val(response.description);
                    $('#is_active').prop('checked', response.is_active == 1);
                    $('#categoryModal').modal('show');
                }
            });
        });

        // Save Category
        $('#saveCategoryBtn').click(function() {
            var categoryId = $('#category_id_hidden').val();
            var formData = {
                name: $('#name').val(),
                description: $('#description').val(),
                is_active: $('#is_active').is(':checked') ? 1 : 0
            };
            var method = categoryId ? 'PUT' : 'POST';
            var url = categoryId ? 
                "{{ route('hrd.performance.categories.update', '') }}/" + categoryId : 
                "{{ route('hrd.performance.categories.store') }}";
            
            // Clear previous errors
            clearFormErrors('#categoryForm');
            
            $.ajax({
                url: url,
                method: method,
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#categoryModal').modal('hide');
                    loadCategories(); // Reload categories for dropdown
                    
                    // Trigger custom event
                    $(document).trigger(categoryId ? 'categoryUpdated' : 'categoryCreated');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            $('#' + key).addClass('is-invalid');
                            $('#' + key + '-error').text(value[0]);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Something went wrong!'
                        });
                    }
                }
            });
        });

        // Delete Category
        $(document).on('click', '.delete-category', function() {
            var categoryId = $(this).data('id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('hrd.performance.categories.destroy', '') }}/" + categoryId,
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            loadCategories(); // Reload categories for dropdown
                            
                            // Trigger custom event
                            $(document).trigger('categoryDeleted');
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON.message || 'Something went wrong!'
                            });
                        }
                    });
                }
            });
        });

        // Question Modal Handlers
        $('#addQuestionBtn, #questionsTabAddBtn').click(function() {
            $('#questionForm').trigger('reset');
            $('#question_id').val('');
            $('#questionModalTitle').text('Add Question');
            
            // Show modal first, then initialize Select2 and load categories
            $('#questionModal').modal('show');
            
            // Wait a moment for modal to show before initializing Select2
            setTimeout(function() {
                // Initialize Select2 for evaluation_type using our helper
                initSelect2('#evaluation_type');
                
                // Load categories
                loadCategories();
            }, 200);
        });

        // Edit Question
        $(document).on('click', '.edit-question', function() {
            var questionId = $(this).data('id');
            $('#questionModalTitle').text('Edit Question');
            
            // Reset form and clear previous errors
            $('#questionForm').trigger('reset');
            clearFormErrors('#questionForm');
            
            // Show modal first, then initialize Select2
            $('#questionModal').modal('show');
            
            // Fetch question data
            $.ajax({
                url: "{{ route('hrd.performance.questions.get', '') }}/" + questionId,
                method: 'GET',
                success: function(response) {
                    $('#question_id').val(response.id);
                    $('#question_text').val(response.question_text);
                    
                    // Wait for modal to show before initializing Select2 and loading data
                    setTimeout(function() {
                        // Initialize evaluation_type Select2
                        initSelect2('#evaluation_type');
                        
                        // Load categories with the selected ID
                        loadCategories(response.category_id);
                        
                        // Set evaluation type, question type and active status
                        $('#evaluation_type').val(response.evaluation_type).trigger('change');
                        $('#question_type').val(response.question_type || 'score').trigger('change');
                        $('#question_is_active').prop('checked', response.is_active == 1);
                        
                        console.log('Question data loaded and dropdowns initialized');
                    }, 200);
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load question data!'
                    });
                }
            });
        });

        // Save Question
        $('#saveQuestionBtn').click(function() {
            var questionId = $('#question_id').val();
            var formData = {
                question_text: $('#question_text').val(),
                category_id: $('#category_id').val(),
                question_type: $('#question_type').val(),
                evaluation_type: $('#evaluation_type').val(),
                is_active: $('#question_is_active').is(':checked') ? 1 : 0
            };
            var method = questionId ? 'PUT' : 'POST';
            var url = questionId ? 
                "{{ route('hrd.performance.questions.update', '') }}/" + questionId : 
                "{{ route('hrd.performance.questions.store') }}";
            
            // Clear previous errors
            clearFormErrors('#questionForm');
            
            $.ajax({
                url: url,
                method: method,
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#questionModal').modal('hide');
                    
                    // Trigger custom event
                    $(document).trigger(questionId ? 'questionUpdated' : 'questionCreated');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            $('#' + key).addClass('is-invalid');
                            $('#' + key + '-error').text(value[0]);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Something went wrong!'
                        });
                    }
                }
            });
        });

        // Delete Question
        $(document).on('click', '.delete-question', function() {
            var questionId = $(this).data('id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('hrd.performance.questions.destroy', '') }}/" + questionId,
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            // Trigger custom event
                            $(document).trigger('questionDeleted');
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON.message || 'Something went wrong!'
                            });
                        }
                    });
                }
            });
        });

        // Helper function to clear form errors
        function clearFormErrors(formSelector) {
            $(formSelector + ' .is-invalid').removeClass('is-invalid');
            $(formSelector + ' .invalid-feedback').text('');
        }

        // Handle tab changes - load data when a tab is activated
        $('#performanceTabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var target = $(e.target).attr("href");
            if (target === '#categories') {
                categoriesTable.ajax.reload();
            } else if (target === '#questions') {
                loadGroupedQuestions();
            }
        });
        
        // Initial load of grouped questions if that tab is active
        if ($('#questions-tab').hasClass('active')) {
            loadGroupedQuestions();
        }
        
        // Simple search functionality for questions
        $('#questionSearch').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $("#questionsGrouped tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
            
            // Hide empty categories (categories with no visible questions)
            $("#questionsGrouped .card").each(function() {
                var visibleRows = $(this).find('tbody tr:visible').length;
                var hasEmptyMessage = $(this).find('td[colspan="4"]').length > 0;
                
                if (visibleRows === 0 && !hasEmptyMessage) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
        });
        
        // Refresh the grouped questions after CRUD operations
        function refreshQuestions() {
            if ($('#questions-tab').hasClass('active')) {
                loadGroupedQuestions();
            }
        }
        
        function refreshCategories() {
            categoriesTable.ajax.reload();
        }
        
        // Override the success handlers to refresh data
        $(document).on('categoryCreated categoryUpdated categoryDeleted', function() {
            refreshCategories();
            // Always reload the questions data since category changes affect them
            loadGroupedQuestions();
        });
        
        $(document).on('questionCreated questionUpdated questionDeleted', function() {
            refreshQuestions();
        });
    });
</script>
@endsection