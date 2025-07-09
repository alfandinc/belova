@extends('layouts.workdoc.app')

@section('title', 'Document Manager | WorkDoc Belova')

@section('navbar')
    @include('layouts.workdoc.navbar')
@endsection

@section('css')
<style>
    /* Main Container Styles */
    .file-manager-container {
        background-color: #f8f9fa;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 0 10px rgba(0,0,0,0.03);
    }
    
    .file-manager-header {
        padding: 15px 20px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #fff;
    }
    
    .dark-mode .file-manager-container {
        background-color: #2d3548;
    }
    
    .dark-mode .file-manager-header {
        background-color: #1e2430;
        border-color: #384256;
    }
    
    /* Sidebar Styles - Dastone inspired */
    .file-manager-sidebar {
        height: calc(100vh - 250px);
        overflow-y: auto;
        background-color: #fff;
    }
    
    .dark-mode .file-manager-sidebar {
        background-color: #1e2430;
        border-color: #384256;
    }
    
    .files-nav .nav-link {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        margin-bottom: 5px;
        border-radius: 8px;
        color: #7081b9;
        transition: all 0.3s ease;
    }
    
    .files-nav .nav-link:hover {
        background-color: rgba(80, 165, 241, 0.1);
        color: #50a5f1;
        transform: translateX(3px);
    }
    
    .dark-mode .files-nav .nav-link:hover {
        background-color: rgba(80, 165, 241, 0.1);
    }
    
    .files-nav .nav-link.active {
        background-color: rgba(80, 165, 241, 0.2);
        color: #50a5f1;
        font-weight: 500;
        box-shadow: 0 2px 6px rgba(80, 165, 241, 0.2);
    }
    
    .files-nav .nav-link i {
        font-size: 24px;
        margin-right: 12px;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background-color: rgba(80, 165, 241, 0.1);
        transition: all 0.3s ease;
    }
    
    .files-nav .nav-link:hover i {
        transform: scale(1.1);
        background-color: rgba(80, 165, 241, 0.15);
    }
    
    .files-nav .nav-link.active i {
        background-color: #50a5f1;
        color: #ffffff;
        box-shadow: 0 4px 6px rgba(80, 165, 241, 0.3);
    }
    
    .storage-info {
        margin-top: 20px;
        padding: 15px;
    }
    
    .dark-mode .storage-info {
        border-color: #384256;
    }
    
    .progress {
        height: 5px;
        margin-top: 10px;
    }
    
    /* File List Styles - Dastone file-box style */
    .file-manager-content {
        padding: 20px;
        height: calc(100vh - 250px);
        overflow-y: auto;
        background-color: #f8f9fa;
    }
    
    .dark-mode .file-manager-content {
        background-color: #2d3548;
    }
    
    .section-title {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .dark-mode .section-title {
        border-color: #384256;
    }
    
    /* Dastone-inspired file box */
    .file-box-content {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }
    
    .file-box {
        position: relative;
        width: calc(25% - 20px);
        margin: 0 10px 20px;
        background: #fff;
        border-radius: 5px;
        padding: 20px 15px;
        box-shadow: 0 0 3px rgba(0,0,0,0.05);
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .dark-mode .file-box {
        background: #1e2430;
        box-shadow: 0 0 3px rgba(0,0,0,0.15);
    }
    
    .file-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .file-download-icon {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 16px;
        opacity: 0;
        transition: all 0.3s ease;
        color: #8997bd;
    }
    
    .file-box:hover .file-download-icon {
        opacity: 1;
    }
    
    /* Enhanced file box icon styles */
    .file-box i.mdi {
        font-size: 42px;
        margin-bottom: 15px;
        background-color: rgba(80, 165, 241, 0.1);
        height: 72px;
        width: 72px;
        line-height: 72px;
        border-radius: 50%;
        display: inline-block;
        transition: all 0.3s ease;
        box-shadow: 0 0 0 3px rgba(80, 165, 241, 0.1);
    }
    
    .file-box:hover i.mdi {
        transform: scale(1.1);
        box-shadow: 0 0 0 5px rgba(80, 165, 241, 0.15);
    }
    
    /* File type specific icon colors */
    .file-box i.file-icon-pdf,
    .file-box i.mdi-file-pdf-outline {
        color: #FF5722;
        background-color: rgba(255, 87, 34, 0.1);
        box-shadow: 0 0 0 3px rgba(255, 87, 34, 0.1);
    }
    
    .file-box i.file-icon-word,
    .file-box i.mdi-file-word-outline,
    .file-box i.mdi-file-document-outline {
        color: #1565C0;
        background-color: rgba(21, 101, 192, 0.1);
        box-shadow: 0 0 0 3px rgba(21, 101, 192, 0.1);
    }
    
    .file-box i.file-icon-excel,
    .file-box i.mdi-file-excel-outline {
        color: #2E7D32;
        background-color: rgba(46, 125, 50, 0.1);
        box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
    }
    
    .file-box i.file-icon-powerpoint,
    .file-box i.mdi-file-powerpoint-outline {
        color: #E65100;
        background-color: rgba(230, 81, 0, 0.1);
        box-shadow: 0 0 0 3px rgba(230, 81, 0, 0.1);
    }
    
    .file-box i.file-icon-image,
    .file-box i.mdi-file-image-outline {
        color: #0277BD;
        background-color: rgba(2, 119, 189, 0.1);
        box-shadow: 0 0 0 3px rgba(2, 119, 189, 0.1);
    }
    
    .file-box i.file-icon-folder,
    .file-box i.mdi-folder {
        color: #FFB300;
        background-color: rgba(255, 179, 0, 0.1);
        box-shadow: 0 0 0 3px rgba(255, 179, 0, 0.1);
    }
    
    .file-box i.file-icon-archive,
    .file-box i.mdi-zip-box-outline {
        color: #6D4C41;
        background-color: rgba(109, 76, 65, 0.1);
        box-shadow: 0 0 0 3px rgba(109, 76, 65, 0.1);
    }
    
    .file-box i.file-icon-default {
        color: #546E7A;
        background-color: rgba(84, 110, 122, 0.1);
        box-shadow: 0 0 0 3px rgba(84, 110, 122, 0.1);
    }
    
    /* Breadcrumbs */
    .breadcrumb-container {
        padding: 10px 0;
        margin-bottom: 15px;
    }
    
    .breadcrumb {
        margin-bottom: 0;
        background-color: transparent;
        padding: 0;
    }
    
    .breadcrumb-item a {
        color: #50a5f1;
    }
    
    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 10px;
    }
    
    /* Enhanced button styles */
    .btn-soft-primary {
        background-color: rgba(80, 165, 241, 0.15);
        color: #50a5f1;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }
    
    .btn-soft-primary:hover {
        background-color: #50a5f1;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 2px 6px rgba(80, 165, 241, 0.3);
    }
    
    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
    }
    
    /* Dastone card header styles */
    .card-header {
        padding: 1rem;
        background-color: transparent;
        border-bottom: 1px solid rgba(0,0,0,.05);
    }
    
    .dark-mode .card-header {
        border-color: rgba(255,255,255,.05);
    }
    
    .card-title {
        margin-bottom: 0;
        color: #384256;
        font-size: 15px;
        font-weight: 600;
    }
    
    .dark-mode .card-title {
        color: #e9ecef;
    }
    
    /* Responsive adjustments */
    @media (max-width: 992px) {
        .file-box {
            width: calc(33.33% - 20px);
        }
    }
    
    @media (max-width: 768px) {
        .file-box {
            width: calc(50% - 20px);
        }
    }
    
    @media (max-width: 576px) {
        .file-box {
            width: calc(100% - 20px);
        }
    }
</style>
@endsection

@section('content')
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Document Manager</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('workdoc.dashboard') }}">Workdoc</a></li>
                            <li class="breadcrumb-item active">Document Manager</li>
                        </ol>
                    </div><!--end col-->

                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

        <!-- Main Content Area -->
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">                      
                                <h4 class="card-title">Categories</h4>                      
                            </div><!--end col-->
                            <div class="col-auto"> 
                                <button class="btn btn-sm btn-outline-primary" id="create-category-btn" data-toggle="modal" data-target="#createFolderModal">
                                    <i class="mdi mdi-folder-plus mr-2"></i> Create Category
                                </button>
                            </div><!--end col-->
                        </div>  <!--end row-->                                  
                    </div><!--end card-header-->
                    <div class="card-body">
                        <div class="files-nav">                                     
                            <div class="nav flex-column nav-pills" id="folder-tabs" role="tablist">
                                @php
                                    // Get all folders where parent_id = 1
                                    $categoryFolders = \App\Models\Workdoc\Folder::where('parent_id', 1)->get();
                                @endphp
                                @php $first = true; @endphp
                                @foreach($categoryFolders as $folder)
                                    <a class="nav-link {{ ($currentFolder && $currentFolder->id == $folder->id) || (!$currentFolder && $first) ? 'active' : '' }}"
                                       id="folder-tab-{{ $folder->id }}"
                                       data-toggle="pill"
                                       href="#folder-content-{{ $folder->id }}"
                                       role="tab"
                                       data-folder-id="{{ $folder->id }}"
                                       aria-selected="{{ ($currentFolder && $currentFolder->id == $folder->id) || (!$currentFolder && $first) ? 'true' : 'false' }}">
                                        <i class="mdi mdi-folder align-self-center"></i>
                                        <div class="d-inline-block align-self-center">
                                            <h5 class="m-0">{{ $folder->name }}</h5>
                                            <small>{{ $folder->description ?? 'Folder' }}</small>
                                        </div>
                                    </a>
                                    @php $first = false; @endphp
                                @endforeach
                            </div>
                        </div>
                    </div><!--end card-body-->
                </div><!--end card-->

                <!-- Storage Info -->
                <div class="card">
                    <div class="card-body">
                        @php
                            $totalSize = \App\Models\Workdoc\Document::sum('file_size');
                            $totalSizeFormatted = "0 B";
                            $usedPercent = 0;
                            $maxStorage = 1073741824; // 1GB max storage
                            
                            if ($totalSize > 0) {
                                $usedPercent = min(($totalSize / $maxStorage) * 100, 100);
                                
                                if ($totalSize >= 1073741824) {
                                    $totalSizeFormatted = number_format($totalSize / 1073741824, 2) . ' GB';
                                } elseif ($totalSize >= 1048576) {
                                    $totalSizeFormatted = number_format($totalSize / 1048576, 2) . ' MB';
                                } elseif ($totalSize >= 1024) {
                                    $totalSizeFormatted = number_format($totalSize / 1024, 2) . ' KB';
                                } else {
                                    $totalSizeFormatted = $totalSize . ' bytes';
                                }
                            }
                        @endphp
                        <small class="float-end">{{ $usedPercent }}%</small>
                        <h6 class="mt-0">{{ $totalSizeFormatted }} / 1GB Used</h6>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $usedPercent }}%;" 
                                 aria-valuenow="{{ $usedPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div><!--end card-body-->
                </div><!--end card-->
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <div>
                    <!-- Alerts -->
                    <div id="alert-container">
                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            
                            <div class="tab-content" id="folder-content-tabContent">
                                <div class="d-flex justify-content-end mb-3" id="folder-actions">
                                <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#createFolderModal">
                                    <i class="mdi mdi-folder-plus"></i> Create Folder
                                </button>
                                <button class="btn btn-sm btn-primary ml-2" data-toggle="modal" data-target="#uploadFileModal">
                                    <i class="mdi mdi-upload"></i> Upload File
                                </button>
                            </div>
                                <!-- Tab pane for Home (Root) -->
                                <!-- Tab panes for each category folder -->
                                @foreach($categoryFolders as $folder)
                                    <div class="tab-pane fade {{ $currentFolder && $currentFolder->id == $folder->id ? 'show active' : '' }}"
                                        id="folder-content-{{ $folder->id }}"
                                        role="tabpanel"
                                        aria-labelledby="folder-tab-{{ $folder->id }}">
                                        
                                        <!-- Content will be loaded via AJAX -->
                                        <div class="ajax-loading text-center py-5">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="sr-only">Loading...</span>
                                            </div>
                                            <p class="mt-2">Loading content...</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        <script>
                            // This script will be executed on page load
                            document.addEventListener('DOMContentLoaded', function() {
                                // Clone the folder content template and populate it with initial data
                                var template = document.getElementById('folder-content-template').cloneNode(true);
                                template.id = '';
                                
                                var foldersSection = template.querySelector('.folders-section');
                                var foldersContainer = template.querySelector('.folders-container');
                                var filesContainer = template.querySelector('.files-container');
                                var emptyState = template.querySelector('.empty-state');
                                var emptyStateAll = template.querySelector('.empty-state-all');
                                
                                // Check if we have folders and files to show
                                var hasFolders = {{ count($folders) > 0 ? 'true' : 'false' }};
                                var hasFiles = {{ count($documents) > 0 ? 'true' : 'false' }};
                                
                                if (!hasFolders) {
                                    foldersSection.style.display = 'none';
                                } else {
                                    // Add folders
                                    @foreach($folders as $folder)
                                        foldersContainer.innerHTML += `
                                            <div class="file-box">
                                                <!-- Actions dropdown -->
                                                <a href="#" class="download-icon-link" data-toggle="dropdown" aria-expanded="false">
                                                    <i class="mdi mdi-dots-vertical file-download-icon"></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a href="#" class="dropdown-item" 
                                                       data-toggle="modal" 
                                                       data-target="#editFolderModal" 
                                                       data-folder-id="{{ $folder->id }}" 
                                                       data-folder-name="{{ $folder->name }}"
                                                       data-folder-description="{{ $folder->description }}"
                                                       data-folder-division="{{ $folder->division_id }}"
                                                       data-folder-private="{{ $folder->is_private }}">
                                                        <i class="mdi mdi-pencil mr-2"></i> Edit
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <a href="#" class="dropdown-item text-danger folder-delete-link" data-folder-id="{{ $folder->id }}">
                                                        <i class="mdi mdi-trash-can mr-2"></i> Delete
                                                    </a>
                                                </div>
                                                
                                                <!-- Folder content -->
                                                <a href="#" class="folder-link text-decoration-none" data-folder-id="{{ $folder->id }}">
                                                    <div class="text-center">
                                                        <i class="mdi mdi-folder text-warning"></i>
                                                        @if($folder->is_private)
                                                            <span class="badge badge-danger position-absolute" style="top:8px; left:8px;">Private</span>
                                                        @endif
                                                        <h6 class="text-truncate">{{ $folder->name }}</h6>
                                                        <small class="text-muted">
                                                            {{ $folder->created_at->format('d M Y') }}
                                                            @if($folder->division)
                                                                / {{ $folder->division->name }}
                                                            @endif
                                                        </small>
                                                    </div>
                                                </a>
                                            </div>
                                        `;
                                    @endforeach
                                }
                                
                                if (!hasFiles) {
                                    if (!hasFolders) {
                                        emptyStateAll.style.display = 'block';
                                    } else {
                                        emptyState.style.display = 'block';
                                    }
                                } else {
                                    // Add files
                                    @foreach($documents as $document)
                                        @php
                                            $fileExtension = pathinfo($document->name, PATHINFO_EXTENSION);
                                            $iconClass = 'mdi-file-outline';
                                            $iconColor = '#50a5f1';
                                            
                                            // Determine file icon based on type
                                            if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
                                                $iconClass = 'mdi-file-image';
                                                $iconColor = '#4CAF50';
                                            } elseif (in_array($fileExtension, ['pdf'])) {
                                                $iconClass = 'mdi-file-pdf-box';
                                                $iconColor = '#F44336';
                                            } elseif (in_array($fileExtension, ['doc', 'docx'])) {
                                                $iconClass = 'mdi-file-word';
                                                $iconColor = '#2196F3';
                                            } elseif (in_array($fileExtension, ['xls', 'xlsx'])) {
                                                $iconClass = 'mdi-file-excel';
                                                $iconColor = '#4CAF50';
                                            } elseif (in_array($fileExtension, ['ppt', 'pptx'])) {
                                                $iconClass = 'mdi-file-powerpoint';
                                                $iconColor = '#FF9800';
                                            } elseif (in_array($fileExtension, ['zip', 'rar', '7z'])) {
                                                $iconClass = 'mdi-zip-box';
                                                $iconColor = '#795548';
                                            }
                                        @endphp
                                        
                                        filesContainer.innerHTML += `
                                            <div class="file-box">
                                                <!-- Actions dropdown -->
                                                <a href="#" class="download-icon-link" data-toggle="dropdown" aria-expanded="false">
                                                    <i class="mdi mdi-dots-vertical file-download-icon"></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a href="{{ route('workdoc.documents.download', $document->id) }}" class="dropdown-item">
                                                        <i class="mdi mdi-download mr-2"></i> Download
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <a href="#" class="dropdown-item text-danger document-delete-link" data-document-id="{{ $document->id }}">
                                                        <i class="mdi mdi-trash-can mr-2"></i> Delete
                                                    </a>
                                                </div>
                                                
                                                <!-- File content -->
                                                <div class="text-center">
                                                    <i class="mdi {{ $iconClass }}" style="color: {{ $iconColor }}; font-size: 32px;"></i>
                                                    @if($document->is_private)
                                                        <span class="badge badge-danger position-absolute" style="top:8px; left:8px;">Private</span>
                                                    @endif
                                                    <h6 class="text-truncate">{{ $document->name }}</h6>
                                                    <small class="text-muted">{{ $document->created_at->format('d M Y') }} / {{ $document->file_size_for_humans }}</small>
                                                </div>
                                                
                                                <!-- Hidden download link at the bottom that appears on hover -->
                                                <a href="{{ route('workdoc.documents.download', $document->id) }}" class="btn btn-sm btn-soft-primary download-icon-link" style="position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); opacity: 0; transition: opacity 0.2s;">
                                                    <i class="mdi mdi-download"></i> Download
                                                </a>
                                            </div>
                                        `;
                                    @endforeach
                                }
                                
                            });
                        </script>
                                </div> <!-- end tab pane -->
                            </div> <!-- end tab content -->
                        </div> <!-- end card body -->
                    </div> <!-- end card -->
                </div>
            </div>
        </div>
    </div>

    <!-- Create Folder Modal -->
    <div class="modal fade" id="createFolderModal" tabindex="-1" role="dialog" aria-labelledby="createFolderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="create-folder-form" action="{{ route('workdoc.folders.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="parent_id" id="create-folder-parent-id" value="{{ $currentFolder ? $currentFolder->id : '' }}">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="createFolderModalLabel">Create New Folder</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Alert for errors -->
                        <div class="alert alert-danger d-none" role="alert"></div>
                        
                        <div class="form-group">
                            <label for="folder_name">Folder Name</label>
                            <input type="text" class="form-control" id="folder_name" name="name" required>
                        </div>
                        <div class="form-group mt-3">
                            <label for="folder_description">Description (Optional)</label>
                            <textarea class="form-control" id="folder_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="form-group mt-3">
                            <label for="division_id">Division (Optional)</label>
                            <select class="form-control" id="division_id" name="division_id">
                                <option value="">None (Available to All)</option>
                                @foreach($divisions as $division)
                                    <option value="{{ $division->id }}">{{ $division->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-check mt-3">
                            <input type="checkbox" class="form-check-input" id="is_private" name="is_private">
                            <label class="form-check-label" for="is_private">Private (Only visible to you and selected division)</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Folder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Folder Modal -->
    <div class="modal fade" id="editFolderModal" tabindex="-1" role="dialog" aria-labelledby="editFolderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="edit-folder-form" action="{{ route('workdoc.folders.rename', $currentFolder ? $currentFolder->id : 0) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="editFolderModalLabel">Edit Folder</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Alert for errors -->
                        <div class="alert alert-danger d-none" role="alert"></div>
                        
                        <div class="form-group">
                            <label for="edit_folder_name">Folder Name</label>
                            <input type="text" class="form-control" id="edit_folder_name" name="name" required>
                        </div>
                        <div class="form-group mt-3">
                            <label for="edit_folder_description">Description (Optional)</label>
                            <textarea class="form-control" id="edit_folder_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="form-group mt-3">
                            <label for="edit_division_id">Division (Optional)</label>
                            <select class="form-control" id="edit_division_id" name="division_id">
                                <option value="">None (Available to All)</option>
                                @foreach($divisions as $division)
                                    <option value="{{ $division->id }}">{{ $division->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="edit_is_private" name="is_private">
                            <label class="form-check-label" for="edit_is_private">Private (Only visible to you and selected division)</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Upload File Modal -->
    <div class="modal fade" id="uploadFileModal" tabindex="-1" role="dialog" aria-labelledby="uploadFileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="upload-file-form" action="{{ route('workdoc.documents.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="upload-file-folder-id" name="folder_id" value="{{ $currentFolder ? $currentFolder->id : '' }}">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadFileModalLabel">Upload File</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Alert for errors -->
                        <div class="alert alert-danger d-none" role="alert"></div>
                        
                        <div class="form-group">
                            <label for="file">Select File</label>
                            <input type="file" class="form-control" id="file" name="file" required>
                            <small class="form-text text-muted">Maximum file size: 10MB</small>
                        </div>
                        <div class="form-group mt-3">
                            <label for="description">Description (Optional)</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="form-group mt-3">
                            <label for="upload_division_id">Division (Optional)</label>
                            <select class="form-control" id="upload_division_id" name="division_id">
                                <option value="">None (Available to All)</option>
                                @foreach($divisions as $division)
                                    <option value="{{ $division->id }}">{{ $division->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-check mt-3">
                            <input type="checkbox" class="form-check-input" id="upload_is_private" name="is_private">
                            <label class="form-check-label" for="upload_is_private">Private (Only visible to you and selected division)</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Rename Folder Modal -->
    <div class="modal fade" id="renameFolderModal" tabindex="-1" role="dialog" aria-labelledby="renameFolderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="rename-folder-form" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="renameFolderModalLabel">Rename Folder</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="rename-folder-name">New Folder Name</label>
                            <input type="text" class="form-control" id="rename-folder-name" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Rename</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Rename File Modal -->
    <div class="modal fade" id="renameFileModal" tabindex="-1" role="dialog" aria-labelledby="renameFileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="rename-file-form" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="renameFileModalLabel">Rename File</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="rename-file-name">New File Name</label>
                            <input type="text" class="form-control" id="rename-file-name" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Rename</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Preview File Modal -->
    <div class="modal fade" id="previewFileModal" tabindex="-1" role="dialog" aria-labelledby="previewFileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewFileModalLabel">Preview</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center" id="preview-file-content">
                    <!-- Content will be injected by JS -->
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Function to show an alert message using SweetAlert2
        function showAlert(message, type = 'success') {
            Swal.fire({
                icon: type,
                title: type === 'success' ? 'Success' : 'Error',
                html: message,
                timer: 2500,
                showConfirmButton: false,
                timerProgressBar: true,
                position: 'top-end',
                toast: true
            });
        }
        
        // Enhance icons throughout the application
        function enhanceIcons() {
            // Replace sidebar icons with enhanced versions
            $('.files-nav .nav-link i.mdi-home').parent().find('.d-inline-block').text('Home');
            $('.files-nav .nav-link i.mdi-folder-open').parent().find('.d-inline-block').text('Public Documents');
            $('.files-nav .nav-link i.mdi-file-document-outline').parent().find('.d-inline-block').text('Templates');
            $('.files-nav .nav-link i.mdi-star').parent().find('.d-inline-block').text('Starred');
            $('.files-nav .nav-link i.mdi-archive').parent().find('.d-inline-block').text('Archive');
            
            // Add the create folder and upload file labels if they're empty
            $('.button-items button[data-target="#createFolderModal"]').html('<i class="mdi mdi-folder-plus mr-1"></i> Create Folder');
            $('.button-items button[data-target="#uploadFileModal"]').html('<i class="mdi mdi-cloud-upload mr-1"></i> Upload File');
            
            // Add background circles to file icons
            $('.file-box i.mdi').each(function() {
                let iconClass = $(this).attr('class');
                
                // Add specific icon styling based on the icon class
                if (iconClass.includes('mdi-file-pdf')) {
                    $(this).addClass('file-icon-pdf');
                } else if (iconClass.includes('mdi-file-word')) {
                    $(this).addClass('file-icon-word');
                } else if (iconClass.includes('mdi-file-excel')) {
                    $(this).addClass('file-icon-excel');
                } else if (iconClass.includes('mdi-file-powerpoint')) {
                    $(this).addClass('file-icon-powerpoint');
                } else if (iconClass.includes('mdi-file-image')) {
                    $(this).addClass('file-icon-image');
                } else if (iconClass.includes('mdi-folder')) {
                    $(this).addClass('file-icon-folder');
                } else if (iconClass.includes('mdi-zip-box') || iconClass.includes('mdi-archive')) {
                    $(this).addClass('file-icon-archive');
                } else {
                    $(this).addClass('file-icon-default');
                }
            });
            
            // Replace file icons with better alternatives
            $('.file-box i.mdi-file-pdf-box').removeClass('mdi-file-pdf-box').addClass('mdi-file-pdf-outline');
            $('.file-box i.mdi-file-word').removeClass('mdi-file-word').addClass('mdi-file-word-outline');
            $('.file-box i.mdi-file-excel').removeClass('mdi-file-excel').addClass('mdi-file-excel-outline');
            $('.file-box i.mdi-file-powerpoint').removeClass('mdi-file-powerpoint').addClass('mdi-file-powerpoint-outline');
            $('.file-box i.mdi-file-image').removeClass('mdi-file-image').addClass('mdi-file-image-outline');
            $('.file-box i.mdi-zip-box').removeClass('mdi-zip-box').addClass('mdi-zip-box-outline');
            $('.file-box i.mdi-file-outline').removeClass('mdi-file-outline').addClass('mdi-file-document-outline');
        }
        
        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Get file icon based on file extension
        function getFileIcon(filePath) {
            const extension = filePath.split('.').pop().toLowerCase();
            
            const icons = {
                'pdf': 'mdi-file-pdf-outline',
                'doc': 'mdi-file-word-outline',
                'docx': 'mdi-file-word-outline',
                'xls': 'mdi-file-excel-outline',
                'xlsx': 'mdi-file-excel-outline',
                'ppt': 'mdi-file-powerpoint-outline',
                'pptx': 'mdi-file-powerpoint-outline',
                'jpg': 'mdi-file-image-outline',
                'jpeg': 'mdi-file-image-outline',
                'png': 'mdi-file-image-outline',
                'gif': 'mdi-file-image-outline',
                'bmp': 'mdi-file-image-outline',
                'svg': 'mdi-file-image-outline',
                'zip': 'mdi-zip-box-outline',
                'rar': 'mdi-zip-box-outline',
                '7z': 'mdi-zip-box-outline',
                'txt': 'mdi-file-document-outline',
                'csv': 'mdi-file-delimited-outline',
                'json': 'mdi-code-json',
                'html': 'mdi-language-html5',
                'css': 'mdi-language-css3',
                'js': 'mdi-language-javascript',
                'php': 'mdi-language-php',
                'mp3': 'mdi-file-music-outline',
                'mp4': 'mdi-file-video-outline',
                'mov': 'mdi-file-video-outline',
                'avi': 'mdi-file-video-outline'
            };
            
            return icons[extension] || 'mdi-file-outline';
        }
        
        // Initialize folder tab click handlers
        function initFolderTabs() {
            $('.nav-link[data-folder-id]').off('click').on('click', function(e) {
                e.preventDefault();
                
                const folderId = $(this).data('folder-id');
                const tabId = $(this).attr('href');
                
                // If folder ID is empty, don't proceed
                if (!folderId) return;
                
                // Show loading indicator
                $(tabId).html(`
                    <div class="ajax-loading text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Loading content...</p>
                    </div>
                `);
                
                // Load folder contents via AJAX
                $.ajax({
                    url: '/workdoc/documents/folder/' + folderId + '/contents',
                    type: 'GET',
                    success: function(response) {
                        // Update the tab content
                        $(tabId).html(response.html);
                        
                        // Initialize folder click handlers within the loaded content
                        initFolderClickHandlers();
                        
                        // Re-initialize action buttons
                        initActionButtons(folderId);
                        
                        // Re-enhance icons
                        enhanceIcons();
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while loading the folder contents.';
                        
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        }
                        
                        $(tabId).html(`
                            <div class="alert alert-danger m-3">
                                <i class="fas fa-exclamation-triangle mr-2"></i> ${errorMessage}
                            </div>
                        `);
                    }
                });
            });
        }
        
        // Initialize folder click handlers within the loaded content
        function initFolderClickHandlers() {
            $('.folder-link').off('click').on('click', function(e) {
                e.preventDefault();
                
                const folderId = $(this).data('folder-id');
                const tabSelector = `#folder-tab-${folderId}`;
                
                // If the tab exists, activate it
                if ($(tabSelector).length) {
                    $(tabSelector).tab('show');
                } else {
                    // Otherwise, load the folder contents directly
                    const currentTabPane = $(this).closest('.tab-pane');
                    
                    // Show loading indicator
                    currentTabPane.html(`
                        <div class="ajax-loading text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-2">Loading content...</p>
                        </div>
                    `);
                    
                    // Load folder contents via AJAX
                    $.ajax({
                        url: '/workdoc/documents/folder/' + folderId + '/contents',
                        type: 'GET',
                        success: function(response) {
                            // Update the tab content
                            currentTabPane.html(response.html);
                            
                            // Initialize folder click handlers within the loaded content
                            initFolderClickHandlers();
                            
                            // Re-initialize action buttons
                            initActionButtons(folderId);
                            
                            // Re-enhance icons
                            enhanceIcons();
                        },
                        error: function(xhr) {
                            let errorMessage = 'An error occurred while loading the folder contents.';
                            
                            if (xhr.responseJSON && xhr.responseJSON.error) {
                                errorMessage = xhr.responseJSON.error;
                            }
                            
                            currentTabPane.html(`
                                <div class="alert alert-danger m-3">
                                    <i class="fas fa-exclamation-triangle mr-2"></i> ${errorMessage}
                                </div>
                            `);
                        }
                    });
                }
            });
        }
        
        // Initialize action buttons for a folder
        function initActionButtons(folderId) {
            // Set folder ID for upload modal
            $('#uploadFileModal').data('folder-id', folderId);
            $('#upload-file-folder-id').val(folderId);
            
            // Set parent ID for create folder modal
            $('#createFolderModal').data('parent-id', folderId);
            $('#create-folder-parent-id').val(folderId);
        }
        
        // Initialize everything
        function init() {
            initFolderTabs();
            initFolderClickHandlers();
            enhanceIcons();
            
            // Trigger click on the active tab to load its content
            if ($('.nav-link.active[data-folder-id]').length) {
                $('.nav-link.active[data-folder-id]').trigger('click');
            } else {
                $('.nav-link[data-folder-id]').first().trigger('click');
            }
            
            // Handle form submissions via AJAX
            $('#upload-file-form, #create-folder-form, #edit-folder-form, #edit-document-form').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const formData = new FormData(form[0]);
                const submitButton = form.find('button[type="submit"]');
                const originalButtonText = submitButton.html();
                
                // Disable submit button and show loading
                submitButton.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
                
                $.ajax({
                    url: form.attr('action'),
                    type: form.attr('method'),
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        // Hide modal
                        form.closest('.modal').modal('hide');
                        
                        // Show success message
                        showAlert(response.message || 'Operation completed successfully.');
                        
                        // Reload current folder content
                        $('.nav-link.active[data-folder-id]').trigger('click');
                        
                        // Reset form
                        form[0].reset();
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while processing your request.';
                        
                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseJSON.error) {
                                errorMessage = xhr.responseJSON.error;
                            } else if (xhr.responseJSON.errors) {
                                errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            }
                        }
                        
                        // Show error message
                        showAlert(errorMessage, 'error');
                    },
                    complete: function() {
                        // Re-enable submit button
                        submitButton.html(originalButtonText).prop('disabled', false);
                    }
                });
            });
            
            // Handle create folder form submission via AJAX (prevent double binding)
            $('#create-folder-form').off('submit').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var formData = form.serialize();
                var submitBtn = form.find('button[type="submit"]');
                submitBtn.prop('disabled', true);
                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#createFolderModal').modal('hide');
                        showAlert('Folder created successfully!', 'success');
                        form[0].reset();
                        // Reload sidebar
                        $.get(window.location.href, function(data) {
                            var newSidebar = $(data).find('#folder-tabs').html();
                            $('#folder-tabs').html(newSidebar);
                            enhanceIcons();
                            initFolderTabs();
                            // Reload the parent folder's tab content (not just the active tab)
                            var parentId = $('#create-folder-parent-id').val();
                            if (parentId && $('#folder-tab-' + parentId).length) {
                                $('#folder-tab-' + parentId).tab('show');
                                setTimeout(function() {
                                    $('#folder-tab-' + parentId).trigger('click');
                                }, 100); // Ensure tab is shown before triggering click
                            } else {
                                // Fallback: reload the first tab
                                $('.nav-link[data-folder-id]').first().trigger('click');
                            }
                        });
                    },
                    error: function(xhr) {
                        var errorMsg = 'Failed to create category.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMsg = Object.values(xhr.responseJSON.errors).join('<br>');
                        }
                        showAlert(errorMsg, 'error');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                    }
                });
            });
        }
        
        // Initialize everything
        init();
        
        // Ensure Create Category in sidebar always sets parent_id = 1
        $('#create-category-btn').on('click', function() {
            $('#create-folder-parent-id').val(1);
        });
        
        // Preview file button
        $(document).on('click', '.preview-file-btn', function(e) {
            e.preventDefault();
            var url = $(this).data('file-url');
            var type = $(this).data('file-type').toLowerCase();
            var name = $(this).data('file-name');
            var content = '';
            if(['jpg','jpeg','png','gif','bmp','webp'].includes(type)) {
                content = '<img src="'+url+'" alt="'+name+'" class="img-fluid">';
            } else if(type === 'pdf') {
                content = '<iframe src="'+url+'#toolbar=0" style="width:100%;height:70vh;" frameborder="0"></iframe>';
            } else {
                content = '<div class="alert alert-info">Preview not available for this file type.</div>';
            }
            $('#previewFileModalLabel').text('Preview: ' + name);
            $('#preview-file-content').html(content);
            $('#previewFileModal').modal('show');
        });

        // Rename folder button
        $(document).on('click', '.rename-folder-btn', function(e) {
            e.preventDefault();
            var folderId = $(this).data('folder-id');
            var folderName = $(this).data('folder-name');
            $('#rename-folder-name').val(folderName);
            // Use the correct route for AJAX rename
            $('#rename-folder-form').attr('action', '{{ url('/workdoc/folders') }}/' + folderId);
            $('#renameFolderModal').modal('show');
        });
        // Rename file button
        $(document).on('click', '.rename-file-btn', function(e) {
            e.preventDefault();
            var docId = $(this).data('document-id');
            var docName = $(this).data('document-name');
            $('#rename-file-name').val(docName);
            $('#rename-file-form').attr('action', '/workdoc/documents/' + docId);
            $('#renameFileModal').modal('show');
        });
        // AJAX rename folder
        $('#rename-folder-form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var formData = form.serialize();
            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: formData,
                success: function(response) {
                    $('#renameFolderModal').modal('hide');
                    showAlert('Folder renamed successfully!','success');
                    // Reload current folder content
                    $('.nav-link.active[data-folder-id]').trigger('click');
                },
                error: function(xhr) {
                    showAlert('Failed to rename folder.','error');
                }
            });
        });
        // AJAX rename file
        $('#rename-file-form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var formData = form.serialize();
            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: formData,
                success: function(response) {
                    $('#renameFileModal').modal('hide');
                    showAlert('File renamed successfully!','success');
                    // Reload current folder content
                    $('.nav-link.active[data-folder-id]').trigger('click');
                },
                error: function(xhr) {
                    showAlert('Failed to rename file.','error');
                }
            });
        });
        
        // Folder delete AJAX
        $(document).on('click', '.delete-folder', function(e) {
            e.preventDefault();
            var folderId = $(this).data('folder-id');
            Swal.fire({
                title: 'Are you sure?',
                text: 'This will permanently delete the folder and all its contents!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.value) {
                    var form = $('#delete-folder-' + folderId);
                    $.ajax({
                        url: form.attr('action'),
                        type: 'POST',
                        data: form.serialize(),
                        success: function(response) {
                            showAlert('Folder deleted successfully!', 'success');
                            // Reload current folder content
                            $('.nav-link.active[data-folder-id]').trigger('click');
                        },
                        error: function(xhr) {
                            showAlert('Failed to delete folder.', 'error');
                        }
                    });
                }
            });
        });
        // Document delete AJAX
        $(document).on('click', '.delete-document', function(e) {
            e.preventDefault();
            var docId = $(this).data('document-id');
            Swal.fire({
                title: 'Are you sure?',
                text: 'This will permanently delete the file!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.value) {
                    var form = $('#delete-document-' + docId);
                    $.ajax({
                        url: form.attr('action'),
                        type: 'POST',
                        data: form.serialize(),
                        success: function(response) {
                            showAlert('File deleted successfully!', 'success');
                            // Reload current folder content
                            $('.nav-link.active[data-folder-id]').trigger('click');
                        },
                        error: function(xhr) {
                            showAlert('Failed to delete file.', 'error');
                        }
                    });
                }
            });
        });
    });
</script>
<style>
    /* Enhanced icon styles */
    .file-box i.mdi {
        font-size: 42px;
        margin-bottom: 15px;
        background-color: rgba(80, 165, 241, 0.1);
        height: 72px;
        width: 72px;
        line-height: 72px;
        border-radius: 50%;
        display: inline-block;
        transition: all 0.3s ease;
        box-shadow: 0 0 0 3px rgba(80, 165, 241, 0.1);
    }
    
    .file-box:hover i.mdi {
        transform: scale(1.1);
        box-shadow: 0 0 0 5px rgba(80, 165, 241, 0.15);
    }
    
    /* File type specific icon colors */
    .file-box i.file-icon-pdf,
    .file-box i.mdi-file-pdf-outline {
        color: #FF5722;
        background-color: rgba(255, 87, 34, 0.1);
        box-shadow: 0 0 0 3px rgba(255, 87, 34, 0.1);
    }
    
    .file-box i.file-icon-word,
    .file-box i.mdi-file-word-outline,
    .file-box i.mdi-file-document-outline {
        color: #1565C0;
        background-color: rgba(21, 101, 192, 0.1);
        box-shadow: 0 0 0 3px rgba(21, 101, 192, 0.1);
    }
    
    .file-box i.file-icon-excel,
    .file-box i.mdi-file-excel-outline {
        color: #2E7D32;
        background-color: rgba(46, 125, 50, 0.1);
        box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
    }
    
    .file-box i.file-icon-powerpoint,
    .file-box i.mdi-file-powerpoint-outline {
        color: #E65100;
        background-color: rgba(230, 81, 0, 0.1);
        box-shadow: 0 0 0 3px rgba(230, 81, 0, 0.1);
    }
    
    .file-box i.file-icon-image,
    .file-box i.mdi-file-image-outline {
        color: #0277BD;
        background-color: rgba(2, 119, 189, 0.1);
        box-shadow: 0 0 0 3px rgba(2, 119, 189, 0.1);
    }
    
    .file-box i.file-icon-folder,
    .file-box i.mdi-folder {
        color: #FFB300;
        background-color: rgba(255, 179, 0, 0.1);
        box-shadow: 0 0 0 3px rgba(255, 179, 0, 0.1);
    }
    
    .file-box i.file-icon-archive,
    .file-box i.mdi-zip-box-outline {
        color: #6D4C41;
        background-color: rgba(109, 76, 65, 0.1);
        box-shadow: 0 0 0 3px rgba(109, 76, 65, 0.1);
    }
    
    .file-box i.file-icon-default {
        color: #546E7A;
        background-color: rgba(84, 110, 122, 0.1);
        box-shadow: 0 0 0 3px rgba(84, 110, 122, 0.1);
    }
    
    /* Enhanced navigation icons */
    .files-nav .nav-link i {
        font-size: 24px;
        margin-right: 12px;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background-color: rgba(80, 165, 241, 0.1);
        transition: all 0.3s ease;
    }
    
    .files-nav .nav-link:hover i {
        transform: scale(1.1);
        background-color: rgba(80, 165, 241, 0.15);
    }
    
    .files-nav .nav-link.active i {
        background-color: #50a5f1;
        color: #ffffff;
        box-shadow: 0 4px 6px rgba(80, 165, 241, 0.3);
    }
    
    /* Action button enhancements */
    .btn-soft-primary {
        background-color: rgba(80, 165, 241, 0.15);
        color: #50a5f1;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }
    
    .btn-soft-primary:hover {
        background-color: #50a5f1;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 2px 6px rgba(80, 165, 241, 0.3);
    }
    
    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
    }
    
    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .dropdown-item i {
        font-size: 16px;
    }
</style>
@endsection
