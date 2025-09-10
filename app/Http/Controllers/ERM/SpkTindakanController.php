<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\SpkTindakan;
use App\Models\ERM\SpkTindakanItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class SpkTindakanController extends Controller
{
    /**
     * Display a listing of the SPK Tindakan.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Group SPK Tindakan by visitation
            $visitationGroups = SpkTindakan::with([
                'riwayatTindakan.tindakan',
                'riwayatTindakan.visitation.pasien',
                'riwayatTindakan.visitation.dokter.user' // Add user relationship for dokter
            ])
            ->get()
            ->groupBy('riwayatTindakan.visitation_id')
            ->map(function($spkGroup) {
                $firstSpk = $spkGroup->first();
                $tindakanList = $spkGroup->pluck('riwayatTindakan.tindakan.nama')->filter()->toArray();
                $spkIds = $spkGroup->pluck('id')->toArray();
                
                // Determine overall status based on completion logic
                $statuses = $spkGroup->pluck('status')->toArray();
                $totalTindakan = count($statuses);
                $completedCount = count(array_filter($statuses, function($status) {
                    return $status === 'completed';
                }));
                $inProgressCount = count(array_filter($statuses, function($status) {
                    return $status === 'in_progress';
                }));
                
                // Status logic:
                // - completed: ALL tindakan are completed
                // - in_progress: NOT ALL completed but at least one is completed or in_progress
                // - pending: NO tindakan is completed or in_progress
                if ($completedCount === $totalTindakan) {
                    $overallStatus = 'completed'; // All completed
                } elseif ($completedCount > 0 || $inProgressCount > 0) {
                    $overallStatus = 'in_progress'; // Some completed or in progress
                } else {
                    $overallStatus = 'pending'; // None completed or in progress
                }
                
                return (object) [
                    'visitation_id' => $firstSpk->riwayatTindakan->visitation_id,
                    'spk_ids' => $spkIds,
                    'pasien_nama' => $firstSpk->riwayatTindakan->visitation->pasien->nama ?? '-',
                    'rm' => $firstSpk->riwayatTindakan->visitation->pasien_id ?? '-', // Use pasien_id as RM
                    'dokter_nama' => $firstSpk->riwayatTindakan->visitation->dokter->user->name ?? '-', // Get name through user relationship
                    'tindakan_names' => $tindakanList,
                    'tindakan_count' => count($tindakanList),
                    'tanggal_tindakan' => $firstSpk->tanggal_tindakan,
                    'status' => $overallStatus
                ];
            })
            ->values();

            return DataTables::of($visitationGroups)
                ->addColumn('tindakan_nama', function($row) {
                    // Display all tindakan names, each on a new line
                    return implode('<br>', $row->tindakan_names);
                })
                ->addColumn('status_badge', function($row) {
                    // Get detailed status information
                    $spkGroup = SpkTindakan::whereIn('id', $row->spk_ids)->get();
                    $statuses = $spkGroup->pluck('status')->toArray();
                    $totalTindakan = count($statuses);
                    $completedCount = count(array_filter($statuses, function($status) {
                        return $status === 'completed';
                    }));
                    
                    $statusColors = [
                        'pending' => 'warning',
                        'in_progress' => 'primary', 
                        'completed' => 'success',
                        'cancelled' => 'danger'
                    ];
                    
                    $color = $statusColors[$row->status] ?? 'secondary';
                    $statusText = ucfirst(str_replace('_', ' ', $row->status));
                    
                    // Add completion progress for better visibility
                    if ($row->status === 'completed') {
                        $statusText = "✅ Completed ({$completedCount}/{$totalTindakan})";
                    } elseif ($row->status === 'in_progress') {
                        $statusText = "⏳ In Progress ({$completedCount}/{$totalTindakan})";
                    } else {
                        $statusText = "⏸️ Pending (0/{$totalTindakan})";
                    }
                    
                    return '<span class="badge badge-' . $color . '">' . $statusText . '</span>';
                })
                ->addColumn('action', function($row) {
                    $spkIdsString = implode(',', $row->spk_ids);
                    return '<button type="button" class="btn btn-primary btn-sm" onclick="showSpkItems([' . $spkIdsString . '])">
                                <i class="mdi mdi-eye"></i> Detail (' . $row->tindakan_count . ')
                            </button>';
                })
                ->rawColumns(['tindakan_nama', 'status_badge', 'action'])
                ->make(true);
        }

        return view('erm.spk-tindakan.index');
    }

    /**
     * Show SPK Tindakan items in modal
     */
    public function showItems($id)
    {
        // Handle both single ID and comma-separated IDs
        $ids = is_string($id) ? explode(',', $id) : [$id];
        
        $spkTindakans = SpkTindakan::with([
            'items.kodeTindakan',
            'riwayatTindakan.tindakan',
            'riwayatTindakan.visitation.pasien',
            'riwayatTindakan.visitation.dokter.user' // Add user relationship for dokter name
        ])->whereIn('id', $ids)->get();

        if ($spkTindakans->isEmpty()) {
            abort(404);
        }

        // Get only users with Dokter and Beautician roles for penanggung jawab selection
        $users = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['Dokter', 'Beautician']);
        })->get();

        return view('erm.spk-tindakan.items-modal', compact('spkTindakans', 'users'));
    }

    /**
     * Update SPK Tindakan items
     */
    public function updateItems(Request $request, $id)
    {
        $spkTindakan = SpkTindakan::findOrFail($id);
        
        // Log incoming data for debugging
        Log::info('SPK Update Request', [
            'spk_id' => $id,
            'request_data' => $request->all()
        ]);
        
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:erm_spk_tindakan_items,id',
            'items.*.penanggung_jawab' => 'nullable|string',
            'items.*.sbk' => 'nullable|in:1',
            'items.*.sba' => 'nullable|in:1',
            'items.*.sdc' => 'nullable|in:1',
            'items.*.sdk' => 'nullable|in:1',
            'items.*.sdl' => 'nullable|in:1',
            'items.*.notes' => 'nullable|string',
            'waktu_mulai' => 'nullable|date_format:H:i',
            'waktu_selesai' => 'nullable|date_format:H:i'
        ]);

        // Update SPK time fields if provided
        $updateData = [];
        if ($request->filled('waktu_mulai')) {
            // Combine time with current date
            $currentDate = now()->format('Y-m-d');
            $updateData['waktu_mulai'] = $currentDate . ' ' . $request->waktu_mulai . ':00';
        }
        if ($request->filled('waktu_selesai')) {
            // Combine time with current date  
            $currentDate = now()->format('Y-m-d');
            $updateData['waktu_selesai'] = $currentDate . ' ' . $request->waktu_selesai . ':00';
        }
        
        if (!empty($updateData)) {
            $spkTindakan->update($updateData);
            Log::info('SPK time fields updated', ['spk_id' => $id, 'data' => $updateData]);
        }

        foreach ($request->items as $itemData) {
            Log::info('Processing item data', ['item_data' => $itemData]);
            
            $item = SpkTindakanItem::find($itemData['id']);
            Log::info('Before update', [
                'item_id' => $item->id,
                'current_data' => $item->toArray()
            ]);
            
            $updated = SpkTindakanItem::where('id', $itemData['id'])
                ->update([
                    'penanggung_jawab' => $itemData['penanggung_jawab'] ?? null,
                    'sbk' => isset($itemData['sbk']) ? 1 : 0,
                    'sba' => isset($itemData['sba']) ? 1 : 0,
                    'sdc' => isset($itemData['sdc']) ? 1 : 0,
                    'sdk' => isset($itemData['sdk']) ? 1 : 0,
                    'sdl' => isset($itemData['sdl']) ? 1 : 0,
                    'notes' => $itemData['notes'] ?? null
                ]);
                
            Log::info('Update result', ['rows_affected' => $updated]);
            
            $item->refresh();
            Log::info('After update', [
                'item_id' => $item->id,
                'updated_data' => $item->toArray()
            ]);
        }

        // Refresh the SPK to get updated items
        $spkTindakan->refresh();
        
        // Auto-update SPK status based on penanggung jawab completion
        $totalItems = $spkTindakan->items()->count();
        $filledItems = $spkTindakan->items()->whereNotNull('penanggung_jawab')
                                           ->where('penanggung_jawab', '!=', '')
                                           ->count();
        
        $newStatus = 'pending'; // Default status
        
        if ($filledItems == $totalItems && $totalItems > 0) {
            // All items have penanggung jawab filled
            $newStatus = 'completed';
        } elseif ($filledItems > 0) {
            // Some items have penanggung jawab filled
            $newStatus = 'in_progress';
        }
        
        // Update SPK status automatically
        $spkTindakan->update(['status' => $newStatus]);

        // Override with manual status if provided
        if ($request->has('status')) {
            $spkTindakan->update(['status' => $request->status]);
        }

        return response()->json([
            'success' => true,
            'message' => 'SPK Tindakan items berhasil diupdate',
            'new_status' => $spkTindakan->fresh()->status
        ]);
    }

    /**
     * Update SPK Tindakan status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled'
        ]);

        $spkTindakan = SpkTindakan::findOrFail($id);
        $spkTindakan->update([
            'status' => $request->status,
            'waktu_mulai' => $request->status === 'in_progress' ? now() : $spkTindakan->waktu_mulai,
            'waktu_selesai' => $request->status === 'completed' ? now() : null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status SPK berhasil diupdate'
        ]);
    }
}
