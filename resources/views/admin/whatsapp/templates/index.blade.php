<?php /* WhatsApp templates UI removed. Integration disabled and view cleaned. */ ?>

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