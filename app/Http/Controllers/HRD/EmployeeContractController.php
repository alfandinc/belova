<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployeeContractController extends Controller
{
    /**
     * Display a listing of contracts for a specific employee
     */
    public function index($employeeId)
    {
        $employee = \App\Models\HRD\Employee::findOrFail($employeeId);
        $contracts = $employee->contracts()->orderBy('start_date', 'desc')->get();
        
        return view('hrd.employee.contracts.index', compact('employee', 'contracts'));
    }
    
    /**
     * Show the form for creating a new contract (renewal)
     * Now redirects to index with message as we're using modals
     */
    public function create($employeeId)
    {
        return redirect()->route('hrd.employee.contracts.index', $employeeId)
            ->with('info', 'Gunakan tombol "Perpanjang Kontrak" untuk membuat kontrak baru');
    }
    
    /**
     * Store a newly created contract
     */
    public function store(Request $request, $employeeId)
    {
        $employee = \App\Models\HRD\Employee::findOrFail($employeeId);
        
        // Validate input
        $data = $request->validate([
            'start_date' => 'required|date',
            'duration_months' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'contract_document' => 'nullable|file|max:2048',
        ]);
        
        // Ensure duration_months is cast to integer
        $data['duration_months'] = (int)$data['duration_months'];
        
        // Calculate end date based on duration
        $startDate = \Carbon\Carbon::parse($data['start_date']);
        $endDate = (clone $startDate)->addMonths((int)$data['duration_months']);
        
        // Handle file upload
        if ($request->hasFile('contract_document')) {
            $data['contract_document'] = $request->file('contract_document')
                ->store('documents/employees/contracts', 'public');
        }
        
        // If this is a renewal, mark previous active contract as renewed
        $activeContract = $employee->activeContract()->first();
        if ($activeContract) {
            $activeContract->update(['status' => 'renewed']);
        }
        
        // Create new contract record
        $contract = $employee->contracts()->create([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'duration_months' => $data['duration_months'],
            'status' => 'active',
            'notes' => $data['notes'],
            'contract_document' => $data['contract_document'] ?? null,
            'created_by' => \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::id() : null,
        ]);
        
        // Update employee's contract end date
        $employee->update([
            'kontrak_berakhir' => $endDate,
            'status' => 'kontrak',
        ]);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Kontrak berhasil diperpanjang',
                'redirect' => route('hrd.employee.index')
            ]);
        }
        
        return redirect()->route('hrd.employee.contracts.index', $employee->id)
            ->with('success', 'Kontrak berhasil diperpanjang');
    }
    
    /**
     * Display the specified contract
     * Now redirects to index as we're using modals
     */
    public function show($employeeId, $contractId)
    {
        return redirect()->route('hrd.employee.contracts.index', $employeeId)
            ->with('info', 'Detail kontrak dapat dilihat dengan mengklik tombol lihat pada tabel');
    }
    
    /**
     * Terminate a contract early
     */
    public function terminate(Request $request, $employeeId, $contractId)
    {
        $contract = \App\Models\HRD\EmployeeContract::where('employee_id', $employeeId)
            ->findOrFail($contractId);
            
        $request->validate([
            'termination_notes' => 'required|string',
        ]);
        
        $contract->update([
            'status' => 'terminated',
            'notes' => $contract->notes . "\n\nTermination: " . $request->termination_notes,
        ]);
        
        // Update employee status if this is the active contract
        $employee = \App\Models\HRD\Employee::findOrFail($employeeId);
        if ($employee->kontrak_berakhir && $employee->kontrak_berakhir->format('Y-m-d') == $contract->end_date->format('Y-m-d')) {
            $employee->update([
                'status' => 'tidak aktif',
            ]);
        }
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Kontrak berhasil dihentikan',
            ]);
        }
        
        return redirect()->route('hrd.employee.contracts.index', $employeeId)
            ->with('success', 'Kontrak berhasil dihentikan');
    }
    
    /**
     * Get contract creation form via AJAX
     */
    public function getCreateModal($employeeId)
    {
        $employee = \App\Models\HRD\Employee::findOrFail($employeeId);
        $lastContract = $employee->lastContract()->first();
        
        return response()->json([
            'success' => true,
            'html' => view('hrd.employee.contracts.partials.create_modal', compact('employee', 'lastContract'))->render()
        ]);
    }
    
    /**
     * Get contract details via AJAX
     */
    public function getShowModal($employeeId, $contractId)
    {
        $employee = \App\Models\HRD\Employee::findOrFail($employeeId);
        $contract = \App\Models\HRD\EmployeeContract::where('employee_id', $employeeId)
            ->findOrFail($contractId);
        
        return response()->json([
            'success' => true,
            'html' => view('hrd.employee.contracts.partials.show_modal', compact('employee', 'contract'))->render()
        ]);
    }
}
