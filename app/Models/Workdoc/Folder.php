<?php

namespace App\Models\Workdoc;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HRD\Division;
use App\Models\User;

class Folder extends Model
{
    use HasFactory;

    protected $table = 'workdoc_folders';

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'division_id',
        'created_by',
        'is_private'
    ];

    /**
     * Get the division that owns the folder
     */
    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Get the user who created the folder
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the parent folder
     */
    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    /**
     * Get the subfolders
     */
    public function subfolders()
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    /**
     * Get all documents in the folder
     */
    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
