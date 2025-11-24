<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobList extends Model
{
    use HasFactory;

    protected $table = 'hrd_joblists';

    protected $fillable = [
        'title', 'description', 'notes', 'status', 'priority', 'division_id', 'due_date', 'created_by', 'updated_by', 'for_manager', 'documents', 'dibaca_by', 'dibaca_at'
    ];

    protected $casts = [
        'all_divisions' => 'boolean',
        'for_manager' => 'boolean',
        'documents' => 'array',
        'dibaca_at' => 'datetime',
    ];

    public function division()
    {
        return $this->belongsTo(\App\Models\HRD\Division::class, 'division_id');
    }

    /**
     * Many-to-many relationship: a joblist can be assigned to multiple divisions.
     */
    public function divisions()
    {
        return $this->belongsToMany(\App\Models\HRD\Division::class, 'hrd_joblist_division', 'joblist_id', 'division_id');
    }

    /**
     * Convenience: assign divisions (accepts single id, array of ids, or null)
     */
    public function assignDivisions($divisionIds)
    {
        if ($divisionIds === null) {
            $this->divisions()->detach();
            $this->all_divisions = false;
            $this->save();
            return $this;
        }

        if ($divisionIds === 'all' || $divisionIds === true) {
            // mark as all divisions and detach any specific attachments
            $this->divisions()->detach();
            $this->all_divisions = true;
            $this->save();
            return $this;
        }

        $ids = is_array($divisionIds) ? $divisionIds : [$divisionIds];
        $this->divisions()->sync($ids);
        $this->all_divisions = false;
        $this->save();
        return $this;
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Who last updated the job
     */
    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * User who marked the job as read
     */
    public function dibacaBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'dibaca_by');
    }
}
