{{-- Folder content partial for AJAX loading --}}
<div class="folder-content-wrapper">
    {{-- Breadcrumbs --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">{{ $folder->name }}</h4>
                        <ol class="breadcrumb">
                            @foreach($breadcrumbs as $breadcrumb)
                                @if($loop->last)
                                    <li class="breadcrumb-item active">{{ $breadcrumb['name'] }}</li>
                                @else
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);" class="folder-link" data-folder-id="{{ $breadcrumb['id'] }}">
                                            {{ $breadcrumb['name'] }}
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ol>
                    </div>
                    <div class="col-auto">
                        <div class="button-items">
                            @if(auth()->user()->can('create', \App\Models\Workdoc\Document::class))
                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#uploadFileModal" 
                                    data-folder-id="{{ $folder->id }}">
                                    <i class="fas fa-upload mr-2"></i>Upload File
                                </button>
                            @endif
                            @if(auth()->user()->can('create', \App\Models\Workdoc\Folder::class))
                                <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#createFolderModal"
                                    data-parent-id="{{ $folder->id }}">
                                    <i class="fas fa-folder-plus mr-2"></i>New Folder
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if($folders->count() == 0 && $documents->count() == 0)
                        <div class="text-center py-5">
                            <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">This folder is empty</h5>
                            <p class="text-muted mb-0">Upload files or create folders to organize your documents</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="border-top-0">Name</th>
                                        <th class="border-top-0">Owner</th>
                                        <th class="border-top-0">Last Modified</th>
                                        <th class="border-top-0">Size</th>
                                        <th class="border-top-0 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($folders as $subfolder)
                                        <tr class="folder-row">
                                            <td>
                                                <a href="javascript:void(0);" class="folder-link" data-folder-id="{{ $subfolder->id }}">
                                                    <i class="fas fa-folder text-warning mr-2"></i>{{ $subfolder->name }}
                                                </a>
                                            </td>
                                            <td>{{ $subfolder->creator->name ?? 'Unknown' }}</td>
                                            <td>{{ $subfolder->updated_at->format('M d, Y') }}</td>
                                            <td>--</td>
                                            <td class="text-right">
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary rename-folder-btn" data-folder-id="{{ $subfolder->id }}" data-folder-name="{{ $subfolder->name }}" title="Rename">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger delete-folder" data-folder-id="{{ $subfolder->id }}" title="Delete">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                                <form id="delete-folder-{{ $subfolder->id }}" action="{{ route('workdoc.folders.destroy', $subfolder->id) }}" method="POST" style="display:none;">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    
                                    @foreach($documents as $document)
                                        <tr>
                                            <td>
                                                <a href="{{ route('workdoc.documents.download', $document->id) }}" target="_blank">
                                                    <i class="fas {{ getFileIcon($document->file_path) }} mr-2"></i>{{ $document->name }}
                                                </a>
                                            </td>
                                            <td>{{ $document->creator->name ?? 'Unknown' }}</td>
                                            <td>{{ $document->updated_at->format('M d, Y') }}</td>
                                            <td>{{ formatBytes($document->file_size) }}</td>
                                            <td class="text-right">
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-info preview-file-btn" data-file-url="{{ asset('storage/' . $document->file_path) }}" data-file-type="{{ pathinfo($document->file_path, PATHINFO_EXTENSION) }}" data-file-name="{{ $document->name }}" title="Preview">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <a class="btn btn-sm btn-outline-success" href="{{ route('workdoc.documents.download', $document->id) }}" target="_blank" title="Download">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-primary rename-file-btn" data-document-id="{{ $document->id }}" data-document-name="{{ $document->name }}" title="Rename">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger delete-document" data-document-id="{{ $document->id }}" title="Delete">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                                <form id="delete-document-{{ $document->id }}" action="{{ route('workdoc.documents.destroy', $document->id) }}" method="POST" style="display:none;">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@php
function getFileIcon($filePath) {
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    
    switch (strtolower($extension)) {
        case 'pdf':
            return 'fa-file-pdf text-danger';
        case 'doc':
        case 'docx':
            return 'fa-file-word text-primary';
        case 'xls':
        case 'xlsx':
            return 'fa-file-excel text-success';
        case 'ppt':
        case 'pptx':
            return 'fa-file-powerpoint text-warning';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'bmp':
            return 'fa-file-image text-info';
        case 'zip':
        case 'rar':
        case '7z':
            return 'fa-file-archive text-secondary';
        case 'txt':
            return 'fa-file-alt text-secondary';
        default:
            return 'fa-file text-muted';
    }
}

function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
   
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
   
    $bytes /= pow(1024, $pow);
   
    return round($bytes, $precision) . ' ' . $units[$pow]; 
}
@endphp
