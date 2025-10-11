@extends('layouts.admin.app')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('title', 'WhatsApp Message Templates')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <!-- Page Title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">WhatsApp Message Templates</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.whatsapp.index') }}">WhatsApp</a></li>
                            <li class="breadcrumb-item active">Templates</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Templates Grid -->
        <div class="row">
            @foreach($templates as $key => $template)
            <div class="col-lg-6 col-xl-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-comment-dots mr-2"></i>{{ $template->name }}
                        </h5>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <p class="text-muted mb-3">{{ $template->description }}</p>
                        
                        <!-- Variables List -->
                        @if($template->variables && count($template->variables) > 0)
                        <div class="mb-3">
                            <h6 class="font-weight-bold text-dark">Available Variables:</h6>
                            <div class="bg-light p-2 rounded">
                                @foreach($template->variables as $variable => $description)
                                <div class="mb-1">
                                    <small class="text-muted">
                                        <code class="text-primary">{{ $variable }}</code> - {{ $description }}
                                    </small>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        
                        <!-- Template Preview -->
                        <div class="mb-3 flex-grow-1">
                            <h6 class="font-weight-bold text-dark">Template Preview:</h6>
                            <div class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto; font-size: 13px; white-space: pre-line; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">{{ $template->content }}</div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-auto">
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-primary btn-edit-template" 
                                        data-template-key="{{ $template->key }}"
                                        data-template-name="{{ $template->name }}"
                                        data-template-content="{{ htmlspecialchars($template->content) }}">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button type="button" class="btn btn-info btn-preview-template" 
                                        data-template-key="{{ $template->key }}"
                                        data-template-content="{{ htmlspecialchars($template->content) }}">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Edit Template Modal -->
<div class="modal fade" id="editTemplateModal" tabindex="-1" role="dialog" aria-labelledby="editTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editTemplateModalLabel">
                    <i class="fas fa-edit mr-2"></i>Edit Template
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editTemplateForm">
                    <div class="form-group">
                        <label for="templateContent" class="font-weight-bold">Template Content</label>
                        <textarea class="form-control" id="templateContent" rows="12" 
                                  placeholder="Enter your WhatsApp message template..."
                                  style="font-family: 'Courier New', monospace; font-size: 14px;"></textarea>
                        <small class="form-text text-muted mt-2">
                            <i class="fas fa-info-circle"></i> Use <code>\n</code> for line breaks. Variables will be replaced automatically when sending messages.
                        </small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-info btn-block" id="previewTemplateBtn">
                                <i class="fas fa-eye mr-1"></i> Preview with Sample Data
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fas fa-save mr-1"></i> Save Template
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="previewModalLabel">
                    <i class="fas fa-eye mr-2"></i>Template Preview
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>This preview shows how the message will look with sample data.
                </div>
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fab fa-whatsapp text-success mr-2"></i>WhatsApp Message Preview</h6>
                    </div>
                    <div class="card-body bg-light p-3" style="white-space: pre-wrap; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; line-height: 1.4;" id="previewContent">
                        <!-- Preview content will be loaded here -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let currentTemplateKey = '';

    // Edit Template Button
    $('.btn-edit-template').click(function() {
        currentTemplateKey = $(this).data('template-key');
        const templateName = $(this).data('template-name');
        const templateContent = $(this).data('template-content');
        
        $('#editTemplateModalLabel').text('Edit Template: ' + templateName);
        $('#templateContent').val(templateContent.replace(/\\n/g, '\n'));
        $('#editTemplateModal').modal('show');
    });

    // Preview Template Button (from card)
    $('.btn-preview-template').click(function() {
        const templateKey = $(this).data('template-key');
        const templateContent = $(this).data('template-content');
        
        previewTemplate(templateKey, templateContent);
    });

    // Preview Template Button (from modal)
    $('#previewTemplateBtn').click(function() {
        const content = $('#templateContent').val();
        previewTemplate(currentTemplateKey, content);
    });

    // Save Template Form
    $('#editTemplateForm').submit(function(e) {
        e.preventDefault();
        
        const content = $('#templateContent').val();
        const btn = $(this).find('button[type="submit"]');
        const originalText = btn.html();
        
        btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        
        $.ajax({
            url: '/admin/whatsapp/templates/' + currentTemplateKey,
            method: 'PUT',
            data: {
                content: content,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire('Success', 'Template updated successfully!', 'success');
                    $('#editTemplateModal').modal('hide');
                    
                    // Update the card data attribute
                    $('.btn-edit-template[data-template-key="' + currentTemplateKey + '"]')
                        .data('template-content', content);
                    $('.btn-preview-template[data-template-key="' + currentTemplateKey + '"]')
                        .data('template-content', content);
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                console.error('Template save failed:', xhr.responseText);
                Swal.fire('Error', 'Failed to save template', 'error');
            },
            complete: function() {
                btn.html(originalText);
            }
        });
    });

    function previewTemplate(templateKey, content) {
        $.ajax({
            url: '/admin/whatsapp/templates/' + templateKey + '/preview',
            method: 'POST',
            data: {
                content: content,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#previewContent').text(response.preview);
                    $('#previewModal').modal('show');
                } else {
                    Swal.fire('Error', 'Failed to generate preview', 'error');
                }
            },
            error: function(xhr) {
                console.error('Preview failed:', xhr.responseText);
                Swal.fire('Error', 'Failed to generate preview', 'error');
            }
        });
    }
});
</script>
@endsection