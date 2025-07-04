<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;

class EmployeeContract extends Model
{
    protected $table = 'hrd_employee_contracts';
    
    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'duration_months',
        'status',
        'notes',
        'contract_document',
        'created_by'
    ];
    
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
    
    // Relationship to Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
    
    // Relationship to User (who created the contract)
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
    
    // Check if contract is expired
    public function isExpired()
    {
        return $this->end_date->isPast();
    }
    
    // Get days remaining in the contract
    public function daysRemaining()
    {
        $today = now()->startOfDay();
        $endDate = $this->end_date->startOfDay();
        
        if ($today > $endDate) {
            return 0;
        }
        
        return $today->diffInDays($endDate);
    }
    
    // Format remaining time in a human-readable way
    public function remainingTimeFormatted()
    {
        $days = $this->daysRemaining();
        
        if ($days <= 0) {
            return 'Kontrak Berakhir';
        }
        
        $years = floor($days / 365);
        $days %= 365;
        
        $months = floor($days / 30);
        $days %= 30;
        
        $parts = [];
        
        if ($years > 0) {
            $parts[] = $years . ' thn';
        }
        
        if ($months > 0) {
            $parts[] = $months . ' bln';
        }
        
        if ($days > 0 || empty($parts)) {
            $parts[] = $days . ' hari';
        }
        
        return implode(' ', $parts);
    }
}
