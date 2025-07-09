@extends('layouts.workdoc.app')

@section('title', 'Dashboard | WorkDoc Belova')
@section('navbar')
    @include('layouts.workdoc.navbar')
@endsection  

@section('content')
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Dashboard</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Workdoc</a></li>
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Document Management System</h4>
                </div><!--end card-header-->
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 col-lg-3">
                            <div class="card report-card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center">
                                        <div class="col">
                                            <p class="text-dark mb-0 font-weight-semibold">Documents</p>
                                            @php
                                                $user = Auth::user();
                                                $userDivision = $user->employee->division_id ?? null;
                                                $isAdmin = $user->hasRole('Admin');

                                                // Filtered document count
                                                $documentQuery = \App\Models\Workdoc\Document::query();
                                                $folderQuery = \App\Models\Workdoc\Folder::query();
                                                if (!$isAdmin && $userDivision) {
                                                    $documentQuery->where('division_id', $userDivision);
                                                    $folderQuery->where('division_id', $userDivision);
                                                }
                                                $documentCount = $documentQuery->count();
                                                $folderCount = $folderQuery->count();
                                                $myDocumentCount = \App\Models\Workdoc\Document::where('created_by', $user->id)->count();
                                                $totalSize = $documentQuery->sum('file_size');
                                                $totalSizeFormatted = "0 B";
                                                if ($totalSize >= 1073741824) {
                                                    $totalSizeFormatted = number_format($totalSize / 1073741824, 2) . ' GB';
                                                } elseif ($totalSize >= 1048576) {
                                                    $totalSizeFormatted = number_format($totalSize / 1048576, 2) . ' MB';
                                                } elseif ($totalSize >= 1024) {
                                                    $totalSizeFormatted = number_format($totalSize / 1024, 2) . ' KB';
                                                } else {
                                                    $totalSizeFormatted = $totalSize . ' bytes';
                                                }
                                                // Recent documents filtered by division (unless admin)
                                                $recentDocs = \App\Models\Workdoc\Document::with(['creator', 'division'])
                                                    ->when(!$isAdmin && $userDivision, function($query) use ($userDivision) {
                                                        $query->where('division_id', $userDivision);
                                                    })
                                                    ->latest()
                                                    ->take(5)
                                                    ->get();
                                            @endphp
                                            <h3 class="m-0">{{ $documentCount }}</h3>
                                            <p class="mb-0 text-truncate text-muted"><span class="text-success"><i class="mdi mdi-file-document"></i></span> Total Documents</p>
                                        </div>
                                        <div class="col-auto align-self-center">
                                            <div class="report-main-icon bg-light-alt">
                                                <i data-feather="file-text" class="align-self-center text-muted icon-sm"></i>  
                                            </div>
                                        </div>
                                    </div>
                                </div><!--end card-body--> 
                            </div><!--end card--> 
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="card report-card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center">
                                        <div class="col">
                                            <p class="text-dark mb-0 font-weight-semibold">Folders</p>
                                            <h3 class="m-0">{{ $folderCount }}</h3>
                                            <p class="mb-0 text-truncate text-muted"><span class="text-success"><i class="mdi mdi-folder"></i></span> Total Folders</p>
                                        </div>
                                        <div class="col-auto align-self-center">
                                            <div class="report-main-icon bg-light-alt">
                                                <i data-feather="folder" class="align-self-center text-muted icon-sm"></i>  
                                            </div>
                                        </div>
                                    </div>
                                </div><!--end card-body--> 
                            </div><!--end card--> 
                        </div>
                        
                        <div class="col-md-6 col-lg-3">
                            <div class="card report-card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center">
                                        <div class="col">
                                            <p class="text-dark mb-0 font-weight-semibold">My Documents</p>
                                            <h3 class="m-0">{{ $myDocumentCount }}</h3>
                                            <p class="mb-0 text-truncate text-muted"><span class="text-success"><i class="mdi mdi-account-multiple"></i></span> Your Uploads</p>
                                        </div>
                                        <div class="col-auto align-self-center">
                                            <div class="report-main-icon bg-light-alt">
                                                <i data-feather="user" class="align-self-center text-muted icon-sm"></i>  
                                            </div>
                                        </div>
                                    </div>
                                </div><!--end card-body--> 
                            </div><!--end card--> 
                        </div>
                        
                        <div class="col-md-6 col-lg-3">
                            <div class="card report-card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center">
                                        <div class="col">
                                            <p class="text-dark mb-0 font-weight-semibold">Storage Used</p>
                                            <h3 class="m-0">{{ $totalSizeFormatted }}</h3>
                                            <p class="mb-0 text-truncate text-muted"><span class="text-success"><i class="mdi mdi-database"></i></span> Total Storage</p>
                                        </div>
                                        <div class="col-auto align-self-center">
                                            <div class="report-main-icon bg-light-alt">
                                                <i data-feather="hard-drive" class="align-self-center text-muted icon-sm"></i>  
                                            </div>
                                        </div>
                                    </div>
                                </div><!--end card-body--> 
                            </div><!--end card--> 
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Recent Documents</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Type</th>
                                                    <th>Size</th>
                                                    <th>Uploaded By</th>
                                                    <th>Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $user = Auth::user();
                                                    $userDivision = $user->employee->division_id ?? null;
                                                    $isAdmin = $user->hasRole('Admin');

                                                    $recentDocs = \App\Models\Workdoc\Document::when(!$isAdmin, function($query) use ($userDivision) {
                                                            $query->where('division_id', $userDivision);
                                                        })
                                                        ->with(['creator', 'division'])
                                                        ->latest()
                                                        ->take(5)
                                                        ->get();
                                                @endphp
                                                
                                                @forelse($recentDocs as $doc)
                                                    <tr>
                                                        <td>{{ $doc->name }}</td>
                                                        <td>
                                                            @php
                                                                $fileExtension = pathinfo($doc->name, PATHINFO_EXTENSION);
                                                                $iconClass = 'mdi-file-outline';
                                                                
                                                                // Determine file icon based on type
                                                                if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
                                                                    $iconClass = 'mdi-file-image';
                                                                } elseif (in_array($fileExtension, ['pdf'])) {
                                                                    $iconClass = 'mdi-file-pdf';
                                                                } elseif (in_array($fileExtension, ['doc', 'docx'])) {
                                                                    $iconClass = 'mdi-file-word';
                                                                } elseif (in_array($fileExtension, ['xls', 'xlsx'])) {
                                                                    $iconClass = 'mdi-file-excel';
                                                                } elseif (in_array($fileExtension, ['ppt', 'pptx'])) {
                                                                    $iconClass = 'mdi-file-powerpoint';
                                                                } elseif (in_array($fileExtension, ['zip', 'rar', '7z'])) {
                                                                    $iconClass = 'mdi-zip-box';
                                                                }
                                                            @endphp
                                                            <i class="mdi {{ $iconClass }} mr-1"></i> {{ strtoupper($fileExtension) }}
                                                        </td>
                                                        <td>{{ $doc->file_size_for_humans }}</td>
                                                        <td>{{ $doc->creator->name }}</td>
                                                        <td>{{ $doc->created_at->format('d M Y') }}</td>
                                                        <td>
                                                            <a href="{{ route('workdoc.documents.download', $doc->id) }}" class="btn btn-sm btn-outline-primary">
                                                                <i class="mdi mdi-download"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-center">No documents found</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mt-3 text-right">
                                        <a href="{{ route('workdoc.documents.index') }}" class="btn btn-primary">Go to Document Manager</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div><!--end col-->
    </div><!--end row-->
@endsection
