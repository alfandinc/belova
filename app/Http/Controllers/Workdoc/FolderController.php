<?php

namespace App\Http\Controllers\Workdoc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workdoc\Folder;
use App\Models\HRD\Division;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FolderController extends Controller
{
    /**
     * Store a new folder
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:workdoc_folders,id',
            'division_id' => 'nullable|exists:hrd_division,id',
            'description' => 'nullable|string|max:255',
            'is_private' => 'sometimes|boolean',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        // If there's a parent folder, check permission
        if ($request->parent_id) {
            $parentFolder = Folder::findOrFail($request->parent_id);
            $user = Auth::user();
            $userDivision = $user->employee->division_id ?? null;
            
            if ($parentFolder->is_private && $parentFolder->division_id != $userDivision && $parentFolder->created_by != $user->id) {
                return redirect()->back()->with('error', 'You do not have permission to create folders here.');
            }
        }
        
        // Create folder
        $folder = Folder::create([
            'name' => $request->name,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'division_id' => $request->division_id,
            'created_by' => Auth::id(),
            'is_private' => $request->has('is_private') ? true : false,
        ]);
        
        return redirect()->back()->with('success', 'Folder created successfully.');
    }
    
    /**
     * Update folder details
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'division_id' => 'nullable|exists:hrd_division,id',
            'description' => 'nullable|string|max:255',
            'is_private' => 'sometimes|boolean',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        $folder = Folder::findOrFail($id);
        
        // Check permission
        $user = Auth::user();
        
        if ($folder->created_by != $user->id && !$user->hasRole(['admin', 'super admin'])) {
            return redirect()->back()->with('error', 'You do not have permission to update this folder.');
        }
        
        // Update folder
        $folder->update([
            'name' => $request->name,
            'description' => $request->description,
            'division_id' => $request->division_id,
            'is_private' => $request->has('is_private') ? true : false,
        ]);
        
        return redirect()->back()->with('success', 'Folder updated successfully.');
    }
    
    /**
     * Delete a folder
     */
    public function destroy($id)
    {
        $folder = Folder::findOrFail($id);
        
        // Check permission
        $user = Auth::user();
        
        if ($folder->created_by != $user->id && !$user->hasRole(['admin', 'super admin'])) {
            return redirect()->back()->with('error', 'You do not have permission to delete this folder.');
        }
        
        // Delete the folder (cascade will delete subfolders and documents)
        $folder->delete();
        
        return redirect()->route('workdoc.index')->with('success', 'Folder deleted successfully.');
    }
}
