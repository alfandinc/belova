@extends('layouts.admin.app')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('title', 'WhatsApp Management')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <!-- Page Title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="page-title mb-0 font-size-18">WhatsApp Management</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
                            <li class="breadcrumb-item active">WhatsApp</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Status Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="media">
                            <div class="media-body">
                                <p class="text-muted fw-medium">Service Status</p>
                                <h4 class="mb-0" id="service-status">
                                    <span class="badge badge-secondary">Checking...</span>
                                </h4>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-server text-primary font-size-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="media">
                            <div class="media-body">
                                <p class="text-muted fw-medium">WhatsApp Connection</p>
                                <h4 class="mb-0" id="whatsapp-connection">
                                    <span class="badge badge-secondary">Checking...</span>
                                </h4>
                            </div>
                            <div class="align-self-center">
                                <i class="fab fa-whatsapp text-success font-size-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="media">
                            <div class="media-body">
                                <p class="text-muted fw-medium">Service Enabled</p>
                                <h4 class="mb-0" id="service-enabled">
                                    <span class="badge badge-secondary">Checking...</span>
                                </h4>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-toggle-on text-info font-size-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="media">
                            <div class="media-body">
                                <p class="text-muted fw-medium">Last Updated</p>
                                <h6 class="mb-0" id="last-updated">
                                    <small class="text-muted">Never</small>
                                </h6>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock text-warning font-size-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Service Management -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Service Management</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <button class="btn btn-success btn-block mb-3" id="start-service">
                                    <i class="fas fa-play"></i> Start Service
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button class="btn btn-danger btn-block mb-3" id="stop-service">
                                    <i class="fas fa-stop"></i> Stop Service
                                </button>
                            </div>
                        </div>

                        <!-- Service Logs/Output -->
                        <div class="mt-4">
                            <h5>Service Information</h5>
                            <div class="alert alert-info" id="service-info">
                                <strong>Service URL:</strong> <span id="service-url">{{ config('whatsapp.service_url') }}</span><br>
                                <strong>Enabled:</strong> <span id="config-enabled">{{ config('whatsapp.enabled') ? 'Yes' : 'No' }}</span><br>
                                <strong>Status:</strong> <span id="detailed-status">Loading...</span>
                            </div>
                        </div>

                        <!-- QR Code Display Area -->
                        <div class="mt-4" id="qr-code-section" style="display: none;">
                            <h5>QR Code for WhatsApp Authentication</h5>
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle"></i>
                                <strong>Instructions:</strong>
                                <ol class="mb-0 mt-2">
                                    <li>Open WhatsApp on your smartphone</li>
                                    <li>Go to <strong>Settings</strong> > <strong>Linked Devices</strong></li>
                                    <li>Tap <strong>Link a Device</strong></li>
                                    <li>Scan the QR code below</li>
                                </ol>
                            </div>
                            <div class="text-center p-4 border rounded" id="qr-code-display">
                                <div class="spinner-border" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2">Generating QR Code...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings & Test -->
            <div class="col-lg-4">
                <!-- Settings -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Settings</h4>
                    </div>
                    <div class="card-body">
                        <form id="whatsapp-settings-form">
                            <div class="form-group">
                                <label>Service Enabled</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="setting-enabled" {{ config('whatsapp.enabled') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="setting-enabled">Enable WhatsApp Service</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Service URL</label>
                                <input type="url" class="form-control" id="setting-service-url" value="{{ config('whatsapp.service_url') }}">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Message Template -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Message Template</h4>
                    </div>
                    <div class="card-body">
                        <form id="template-form">
                            <div class="form-group">
                                <label for="template-visitation">Visitation Notification Template</label>
                                <textarea class="form-control" id="template-visitation" rows="12" 
                                          placeholder="Enter your WhatsApp message template...">{{ str_replace('\\n', "\n", config('whatsapp.templates.visitation')) }}</textarea>
                                <small class="form-text text-muted">
                                    <strong>Available Variables:</strong><br>
                                    • <code>{pasien_nama}</code> - Patient name<br>
                                    • <code>{pasien_id}</code> - Patient ID/No. RM<br>
                                    • <code>{jenis_kunjungan}</code> - Visit type<br>
                                    • <code>{tanggal_visitation}</code> - Visit date<br>
                                    • <code>{waktu_kunjungan}</code> - Visit time<br>
                                    • <code>{no_antrian}</code> - Queue number<br>
                                    • <code>{dokter_nama}</code> - Doctor name<br>
                                    • <code>{klinik_nama}</code> - Clinic name
                                </small>
                            </div>

                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fas fa-save"></i> Save Template
                            </button>
                            
                            <button type="button" class="btn btn-info btn-block mt-2" id="preview-template">
                                <i class="fas fa-eye"></i> Preview Template
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Test Message -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Test Message</h4>
                    </div>
                    <div class="card-body">
                        <form id="test-message-form">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" class="form-control" id="test-phone" placeholder="628123456789" required>
                                <small class="form-text text-muted">Format: 628xxxxxxxxx</small>
                            </div>
                            <div class="form-group">
                                <label>Message</label>
                                <textarea class="form-control" id="test-message" rows="3" required>🏥 BELOVA CLINIC TEST

Halo! Ini adalah pesan test dari sistem Belova Clinic.

Sistem WhatsApp berfungsi dengan baik! ✅

_Pesan test otomatis_</textarea>
                            </div>
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fas fa-paper-plane"></i> Send Test Message
                            </button>
                        </form>
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
    // Debug info
    console.log('WhatsApp Admin Interface loaded');
    console.log('CSRF Token:', $('meta[name="csrf-token"]').attr('content'));
    console.log('jQuery version:', $.fn.jquery);
    console.log('Start service button element:', $('#start-service')[0]);
    console.log('Start service button count:', $('#start-service').length);
    
    // Test basic click binding
    $('#start-service').on('click', function() {
        console.log('Start service button clicked');
        const btn = $(this);
        btn.html('<i class="fas fa-spinner fa-spin"></i> Starting...');
        
        $.post('{{ route("admin.whatsapp.start") }}')
            .done(function(data) {
                console.log('Start service response:', data);
                if (data.success) {
                    Swal.fire('Success', 'WhatsApp service started successfully! Check the terminal for QR code.', 'success');
                    // Add delay before refreshing to allow service to fully start
                    setTimeout(function() {
                        refreshStatus();
                    }, 3000);
                } else {
                    Swal.fire({
                        title: 'Service Start Failed',
                        html: '<p>' + data.message + '</p>',
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .fail(function(xhr) {
                console.error('Start service request failed:', xhr.responseText);
                Swal.fire('Error', 'Failed to start service: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'), 'error');
            })
            .always(function() {
                btn.html('<i class="fas fa-play"></i> Start Service');
            });
    });

    // Stop Service Button
    $('#stop-service').click(function() {
        const btn = $(this);
        btn.html('<i class="fas fa-spinner fa-spin"></i> Stopping...');
        
        $.post('{{ route("admin.whatsapp.stop") }}')
            .done(function(data) {
                console.log('Stop service response:', data);
                if (data.success) {
                    Swal.fire('Success', 'WhatsApp service stopped successfully!', 'success');
                    // Add delay before refreshing to allow service to fully stop
                    setTimeout(function() {
                        refreshStatus();
                    }, 2000);
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .fail(function(xhr) {
                console.error('Stop service request failed:', xhr.responseText);
                Swal.fire('Error', 'Failed to stop service: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'), 'error');
            })
            .always(function() {
                btn.html('<i class="fas fa-stop"></i> Stop Service');
            });
    });
    
    // Auto-refresh status every 10 seconds
    let statusInterval;
    
    function startStatusPolling() {
        statusInterval = setInterval(refreshStatus, 10000);
    }
    
    function stopStatusPolling() {
        if (statusInterval) {
            clearInterval(statusInterval);
        }
    }
    
    // Initial status load and start polling
    refreshStatus();
    startStatusPolling();
    
    // Refresh status function
    function refreshStatus() {
        $.get('{{ route("admin.whatsapp.status") }}')
            .done(function(data) {
                updateStatusDisplay(data);
                $('#last-updated').html('<small class="text-muted">' + new Date().toLocaleTimeString() + '</small>');
            })
            .fail(function(xhr, status, error) {
                console.error('Status request failed:', xhr.responseText || error);
                $('#service-status').html('<span class="badge badge-danger">Error</span>');
                $('#whatsapp-connection').html('<span class="badge badge-danger">Error</span>');
                $('#service-enabled').html('<span class="badge badge-danger">Error</span>');
                $('#detailed-status').text('Failed to load: ' + (xhr.responseText || error));
            });
    }
    
    function updateStatusDisplay(data) {
        // Service Status
        if (data.status && data.status.status === 'running') {
            $('#service-status').html('<span class="badge badge-success">Running</span>');
        } else {
            $('#service-status').html('<span class="badge badge-danger">Stopped</span>');
        }
        
        // WhatsApp Connection
        if (data.connected) {
            $('#whatsapp-connection').html('<span class="badge badge-success">Connected</span>');
        } else {
            $('#whatsapp-connection').html('<span class="badge badge-warning">Disconnected</span>');
        }
        
        // Service Enabled
        if (data.enabled) {
            $('#service-enabled').html('<span class="badge badge-success">Enabled</span>');
        } else {
            $('#service-enabled').html('<span class="badge badge-secondary">Disabled</span>');
        }
        
        // Update detailed info
        $('#service-url').text(data.service_url);
        $('#config-enabled').text(data.enabled ? 'Yes' : 'No');
        $('#detailed-status').text(data.status ? JSON.stringify(data.status) : 'No data');
    }
    
    // Refresh Status Button
    $('#refresh-status').click(function() {
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Refreshing...');
        refreshStatus();
        setTimeout(() => {
            $(this).html('<i class="fas fa-sync-alt"></i> Refresh Status');
        }, 1000);
    });
    
    // Get QR Code Button
    $('#get-qr-code').click(function() {
        const btn = $(this);
        btn.html('<i class="fas fa-spinner fa-spin"></i> Getting QR...');
        
        $.post('{{ route("admin.whatsapp.qr") }}')
            .done(function(data) {
                if (data.success) {
                    $('#qr-code-section').show();
                    $('#qr-code-display').html('<div class="alert alert-info">' + data.message + '</div>');
                    Swal.fire('Info', 'Please check the WhatsApp service terminal/logs for the QR code.', 'info');
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .fail(function(xhr) {
                console.error('QR Code request failed:', xhr.responseText);
                Swal.fire('Error', 'Failed to get QR code: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'), 'error');
            })
            .always(function() {
                btn.html('<i class="fas fa-qrcode"></i> Get QR Code');
            });
    });
    
    // Start Service Button - ORIGINAL (TEMPORARILY COMMENTED)
    /*
    $('#start-service').click(function(e) {
        e.preventDefault();
        console.log('Start service button clicked');
        
        const btn = $(this);
        btn.html('<i class="fas fa-spinner fa-spin"></i> Starting...');
        
        console.log('Sending request to:', '{{ route("admin.whatsapp.restart") }}');
        
        $.post('{{ route("admin.whatsapp.restart") }}')
            .done(function(data) {
                console.log('Start service response:', data);
                if (data.success) {
                    Swal.fire('Success', 'WhatsApp service started successfully!', 'success');
                    refreshStatus();
                } else {
                    Swal.fire({
                        title: 'Service Start Failed',
                        html: '<p>' + data.message + '</p><ol class="text-left">' + 
                              (data.instructions ? data.instructions.map(i => '<li>' + i + '</li>').join('') : '') + '</ol>',
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .fail(function(xhr) {
                console.error('Start service request failed:', xhr.responseText);
                Swal.fire('Error', 'Failed to start service: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'), 'error');
            })
            .always(function() {
                btn.html('<i class="fas fa-play"></i> Start Service');
            });
    });
    */

    // Stop Service Button
    $('#stop-service').click(function() {
        const btn = $(this);
        btn.html('<i class="fas fa-spinner fa-spin"></i> Stopping...');
        
        $.post('{{ route("admin.whatsapp.stop") }}')
            .done(function(data) {
                if (data.success) {
                    Swal.fire('Success', 'WhatsApp service stopped successfully!', 'success');
                    refreshStatus();
                } else {
                    Swal.fire('Warning', data.message, 'warning');
                }
            })
            .fail(function(xhr) {
                console.error('Stop service request failed:', xhr.responseText);
                Swal.fire('Error', 'Failed to stop service: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'), 'error');
            })
            .always(function() {
                btn.html('<i class="fas fa-stop"></i> Stop Service');
            });
    });

    // Restart Service Button
    $('#restart-service').click(function() {
        const btn = $(this);
        btn.html('<i class="fas fa-spinner fa-spin"></i> Restarting...');
        
        $.post('{{ route("admin.whatsapp.restart") }}')
            .done(function(data) {
                if (data.success) {
                    Swal.fire('Success', data.message, 'success');
                } else {
                    Swal.fire({
                        title: 'Manual Restart Required',
                        html: '<p>' + data.message + '</p><ol class="text-left">' + 
                              data.instructions.map(i => '<li>' + i + '</li>').join('') + '</ol>',
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .fail(function(xhr) {
                console.error('Restart request failed:', xhr.responseText);
                Swal.fire('Error', 'Failed to restart service: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'), 'error');
            })
            .always(function() {
                btn.html('<i class="fas fa-redo"></i> Restart Service');
            });
    });
    
    // Test Connection Button
    $('#test-connection').click(function() {
        refreshStatus();
        Swal.fire('Info', 'Connection status refreshed!', 'info');
    });
    
    // Settings Form
    $('#whatsapp-settings-form').submit(function(e) {
        e.preventDefault();
        
        const data = {
            enabled: $('#setting-enabled').is(':checked'),
            service_url: $('#setting-service-url').val(),
            template_visitation: $('#template-visitation').val()
        };
        
        $.post('{{ route("admin.whatsapp.settings") }}', data)
            .done(function(response) {
                if (response.success) {
                    Swal.fire('Success', response.message, 'success');
                    refreshStatus();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            })
            .fail(function(xhr) {
                console.error('Settings request failed:', xhr.responseText);
                Swal.fire('Error', 'Failed to update settings: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'), 'error');
            });
    });

    // Template form submission
    $('#template-form').submit(function(e) {
        e.preventDefault();
        
        const data = {
            enabled: $('#setting-enabled').is(':checked'),
            service_url: $('#setting-service-url').val(),
            template_visitation: $('#template-visitation').val()
        };
        
        $.post('{{ route("admin.whatsapp.settings") }}', data)
            .done(function(response) {
                if (response.success) {
                    Swal.fire('Success', 'Template saved successfully!', 'success');
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            })
            .fail(function(xhr) {
                console.error('Template save failed:', xhr.responseText);
                Swal.fire('Error', 'Failed to save template', 'error');
            });
    });

    // Preview template
    $('#preview-template').click(function() {
        const template = $('#template-visitation').val();
        
        // Sample data for preview
        const sampleData = {
            '{pasien_nama}': 'John Doe',
            '{pasien_id}': 'RM001234',
            '{jenis_kunjungan}': 'Konsultasi Dokter',
            '{tanggal_visitation}': '10/10/2025',
            '{waktu_kunjungan}': '14:30',
            '{no_antrian}': '12',
            '{dokter_nama}': 'Dr. Ahmad Wijaya',
            '{klinik_nama}': 'Klinik Umum'
        };
        
        let preview = template;
        Object.keys(sampleData).forEach(key => {
            preview = preview.replace(new RegExp(key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), sampleData[key]);
        });
        
        // Convert \n to <br> for HTML display
        preview = preview.replace(/\\n/g, '\n').replace(/\n/g, '<br>');
        
        Swal.fire({
            title: 'Template Preview',
            html: '<div style="text-align: left; white-space: pre-wrap; font-family: monospace; border: 1px solid #ddd; padding: 10px; background-color: #f8f9fa;">' + preview + '</div>',
            width: '600px',
            confirmButtonText: 'Close'
        });
    });
    
    // Test Message Form
    $('#test-message-form').submit(function(e) {
        e.preventDefault();
        
        const btn = $(this).find('button[type="submit"]');
        const originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> Sending...');
        
        const data = {
            phone: $('#test-phone').val(),
            message: $('#test-message').val()
        };
        
        $.post('{{ route("admin.whatsapp.test") }}', data)
            .done(function(response) {
                console.log('Test message response:', response);
                if (response.success) {
                    Swal.fire('Success', 'Test message sent successfully!', 'success');
                } else {
                    Swal.fire('Error', response.message || response.error || 'Unknown error', 'error');
                }
            })
            .fail(function(xhr) {
                console.error('Test message request failed:', xhr);
                console.error('Response text:', xhr.responseText);
                
                let errorMessage = 'Failed to send test message';
                if (xhr.responseJSON) {
                    errorMessage = xhr.responseJSON.message || xhr.responseJSON.error || errorMessage;
                } else if (xhr.responseText) {
                    try {
                        const parsed = JSON.parse(xhr.responseText);
                        errorMessage = parsed.message || parsed.error || errorMessage;
                    } catch (e) {
                        errorMessage = xhr.responseText;
                    }
                }
                
                Swal.fire({
                    title: 'Error Sending Message',
                    html: '<p><strong>Error:</strong> ' + errorMessage + '</p>' +
                          '<p><strong>Suggestion:</strong> Try stopping and starting the service again to get a new QR code.</p>',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            })
            .always(function() {
                btn.html(originalText);
            });
    });
    
    // Stop polling when page is unloaded
    $(window).on('beforeunload', function() {
        stopStatusPolling();
    });
});
</script>
@endsection