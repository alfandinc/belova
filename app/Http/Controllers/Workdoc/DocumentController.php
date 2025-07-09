<?php

namespace App\Http\Controllers\Workdoc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workdoc\Folder;
use App\Models\Workdoc\Document;
use App\Models\HRD\Division;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    /**
     * Display the document manager dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $userDivision = null;
        
        // Get the user's division
        if ($user->employee) {
            $userDivision = $user->employee->division_id;
        }
        
        // Check if root folder exists, if not redirect to dashboard
        $rootFolder = Folder::where('name', 'Root')->where('parent_id', null)->first();
        if (!$rootFolder) {
            return redirect()->route('workdoc.dashboard')->with('error', 'Root folder not found. Please contact administrator.');
        }
        
        // Get current folder ID from the request or use root
        $currentFolderId = $request->folder_id ?? $rootFolder->id;
        $currentFolder = null;
        
        if ($currentFolderId) {
            $currentFolder = Folder::findOrFail($currentFolderId);
            
            // Check permission to access this folder
            if ($currentFolder->is_private && $currentFolder->division_id != $userDivision && $currentFolder->created_by != $user->id && !$user->hasRole(['admin', 'super admin'])) {
                return redirect()->route('workdoc.documents.index')->with('error', 'You do not have permission to access this folder.');
            }
        }
        
        // Get folders
        $foldersQuery = Folder::where('parent_id', $currentFolderId);
        
        // Filter folders by permission
        if (!$user->hasRole(['admin', 'super admin'])) {
            $foldersQuery->where(function($query) use ($user, $userDivision) {
                $query->where('created_by', $user->id)
                      ->orWhere('is_private', false)
                      ->orWhere(function($q) use ($userDivision) {
                          $q->where('is_private', true)
                            ->where('division_id', $userDivision);
                      });
            });
        }
        
        $folders = $foldersQuery->get();
        
        // Get documents
        $documentsQuery = Document::where('folder_id', $currentFolderId);
        
        // Filter documents by permission
        if (!$user->hasRole(['admin', 'super admin'])) {
            $documentsQuery->where(function($query) use ($user, $userDivision) {
                $query->where('created_by', $user->id)
                      ->orWhere('is_private', false)
                      ->orWhere(function($q) use ($userDivision) {
                          $q->where('is_private', true)
                            ->where('division_id', $userDivision);
                      });
            });
        }
        
        $documents = $documentsQuery->get();
        
        // Get divisions for dropdown
        $divisions = Division::all();
        
        // Get breadcrumbs
        $breadcrumbs = $this->getBreadcrumbs($currentFolder);
        
        return view('workdoc.index', compact('folders', 'documents', 'currentFolder', 'divisions', 'breadcrumbs'));
    }
    
    /**
     * Store a new document
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // Max 10MB
            'folder_id' => 'required|exists:workdoc_folders,id',
            'division_id' => 'nullable|exists:hrd_division,id',
            'description' => 'nullable|string|max:255',
            'is_private' => 'sometimes|boolean',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        // Get the folder
        $folder = Folder::findOrFail($request->folder_id);
        
        // Check permission to upload to this folder
        $user = Auth::user();
        $userDivision = $user->employee->division_id ?? null;
        
        if ($folder->is_private && $folder->division_id != $userDivision && $folder->created_by != $user->id) {
            return redirect()->back()->with('error', 'You do not have permission to upload to this folder.');
        }
        
        // Handle file upload
        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $fileType = $file->getClientMimeType();
        $fileSize = $file->getSize();
        
        // Create path: workdoc/folderId/filename
        $path = $file->store('workdoc/' . $request->folder_id, 'public');
        
        // Create document record
        $document = Document::create([
            'name' => $fileName,
            'file_path' => $path,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'description' => $request->description,
            'folder_id' => $request->folder_id,
            'created_by' => Auth::id(),
            'division_id' => $request->division_id,
            'is_private' => $request->has('is_private') ? true : false,
        ]);
        
        return redirect()->back()->with('success', 'Document uploaded successfully.');
    }
    
    /**
     * Download a document
     */
    public function download($id)
    {
        $document = Document::findOrFail($id);
        
        // Check permission
        $user = Auth::user();
        $userDivision = $user->employee->division_id ?? null;
        
        if ($document->is_private && $document->division_id != $userDivision && $document->created_by != $user->id && !$user->hasRole(['admin', 'super admin'])) {
            return redirect()->back()->with('error', 'You do not have permission to download this document.');
        }
        
        return response()->download(storage_path('app/public/' . $document->file_path), $document->name);
    }
    
    /**
     * Delete a document
     */
    public function destroy($id)
    {
        $document = Document::findOrFail($id);
        // Check permission
        $user = Auth::user();
        if ($document->created_by != $user->id && !$user->hasRole(['admin', 'super admin'])) {
            if (request()->ajax()) {
                return response()->json(['error' => 'You do not have permission to delete this document.'], 403);
            }
            return redirect()->back()->with('error', 'You do not have permission to delete this document.');
        }
        // Delete the file
        Storage::disk('public')->delete($document->file_path);
        // Delete the record
        $document->delete();
        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Document deleted successfully.']);
        }
        return redirect()->back()->with('success', 'Document deleted successfully.');
    }
    
    /**
     * Generate breadcrumbs for navigation
     */
    private function getBreadcrumbs($folder)
    {
        $breadcrumbs = [];
        
        if ($folder) {
            $current = $folder;
            $breadcrumbs[] = $current;
            
            while ($current->parent) {
                $current = $current->parent;
                array_unshift($breadcrumbs, $current);
            }
        }
        
        return $breadcrumbs;
    }

    /**
     * Get folder contents via AJAX
     */
    public function getFolderContents(Request $request, $folderId)
    {
        $user = Auth::user();
        $userDivision = $user->employee->division_id ?? null;
        
        // Get the folder
        $folder = Folder::findOrFail($folderId);
        
        // Check permission to access this folder
        if ($folder->is_private && $folder->division_id != $userDivision && $folder->created_by != $user->id && !$user->hasRole(['admin', 'super admin'])) {
            return response()->json(['error' => 'You do not have permission to access this folder.'], 403);
        }
        
        // Get subfolders
        $foldersQuery = Folder::where('parent_id', $folderId);
        
        // Filter folders by permission
        if (!$user->hasRole(['admin', 'super admin'])) {
            $foldersQuery->where(function($query) use ($user, $userDivision) {
                $query->where('created_by', $user->id)
                      ->orWhere('is_private', false)
                      ->orWhere(function($q) use ($userDivision) {
                          $q->where('is_private', true)
                            ->where('division_id', $userDivision);
                      });
            });
        }
        
        $folders = $foldersQuery->get();
        
        // Get documents
        $documentsQuery = Document::where('folder_id', $folderId);
        
        // Filter documents by permission
        if (!$user->hasRole(['admin', 'super admin'])) {
            $documentsQuery->where(function($query) use ($user, $userDivision) {
                $query->where('created_by', $user->id)
                      ->orWhere('is_private', false)
                      ->orWhere(function($q) use ($userDivision) {
                          $q->where('is_private', true)
                            ->where('division_id', $userDivision);
                      });
            });
        }
        
        $documents = $documentsQuery->get();
        
        // Get breadcrumbs
        $breadcrumbs = $this->getBreadcrumbs($folder);
        
        // Convert breadcrumbs to simple array for JSON
        $breadcrumbsData = [];
        foreach ($breadcrumbs as $crumb) {
            $breadcrumbsData[] = [
                'id' => $crumb->id,
                'name' => $crumb->name,
                'parent_id' => $crumb->parent_id
            ];
        }
        
        // Format data for JSON response
        $folderData = [
            'id' => $folder->id,
            'name' => $folder->name,
            'description' => $folder->description,
            'parent_id' => $folder->parent_id,
            'is_private' => $folder->is_private,
            'division_id' => $folder->division_id,
            'created_by' => $folder->created_by,
            'created_at' => $folder->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $folder->updated_at->format('Y-m-d H:i:s')
        ];
        
        // Format folders for JSON
        $foldersData = [];
        foreach ($folders as $subfolder) {
            $foldersData[] = [
                'id' => $subfolder->id,
                'name' => $subfolder->name,
                'description' => $subfolder->description,
                'parent_id' => $subfolder->parent_id,
                'is_private' => $subfolder->is_private,
                'division_id' => $subfolder->division_id,
                'created_by' => $subfolder->created_by,
                'creator_name' => $subfolder->creator ? $subfolder->creator->name : 'Unknown',
                'created_at' => $subfolder->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $subfolder->updated_at->format('Y-m-d H:i:s')
            ];
        }
        
        // Format documents for JSON
        $documentsData = [];
        foreach ($documents as $document) {
            $documentsData[] = [
                'id' => $document->id,
                'name' => $document->name,
                'description' => $document->description,
                'file_path' => $document->file_path,
                'file_type' => $document->file_type,
                'file_size' => $document->file_size,
                'file_size_formatted' => $this->formatBytes($document->file_size),
                'folder_id' => $document->folder_id,
                'is_private' => $document->is_private,
                'division_id' => $document->division_id,
                'created_by' => $document->created_by,
                'creator_name' => $document->creator ? $document->creator->name : 'Unknown',
                'created_at' => $document->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $document->updated_at->format('Y-m-d H:i:s'),
                'download_url' => route('workdoc.documents.download', $document->id)
            ];
        }
        
        // Get the divisions for dropdowns
        $divisions = Division::all()->map(function($division) {
            return [
                'id' => $division->id,
                'name' => $division->name
            ];
        });
        
        return response()->json([
            'folder' => $folderData,
            'folders' => $foldersData,
            'documents' => $documentsData,
            'breadcrumbs' => $breadcrumbsData,
            'divisions' => $divisions,
            'html' => view('workdoc.partials.folder-contents', compact('folder', 'folders', 'documents', 'breadcrumbs'))->render()
        ]);
    }
    
    /**
     * Helper function to format file size
     */
    private function formatBytes($bytes, $precision = 2) { 
        $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
       
        $bytes = max($bytes, 0); 
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1); 
       
        $bytes /= pow(1024, $pow);
       
        return round($bytes, $precision) . ' ' . $units[$pow]; 
    }

    /**
     * Rename a document (AJAX)
     */
    public function rename(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        $document = Document::findOrFail($id);
        $user = Auth::user();
        if ($document->created_by != $user->id && !$user->hasRole(['admin', 'super admin'])) {
            return response()->json(['error' => 'You do not have permission to rename this file.'], 403);
        }
        $document->name = $request->name;
        $document->save();
        return response()->json(['success' => true, 'message' => 'File renamed successfully.']);
    }
}
