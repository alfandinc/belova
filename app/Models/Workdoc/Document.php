<?php

namespace App\Models\Workdoc;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HRD\Division;
use App\Models\User;

class Document extends Model
{
    use HasFactory;

    protected $table = 'workdoc_documents';

    protected $fillable = [
        'name',
        'file_path',
        'file_type',
        'file_size',
        'description',
        'folder_id',
        'created_by',
        'division_id',
        'is_private'
    ];

    /**
     * Get the folder that owns the document
     */
    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Get the division that owns the document
     */
    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Get the user who created the document
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the file size in a human-readable format
     */
    public function getFileSizeForHumansAttribute()
    {
        $bytes = $this->file_size;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
