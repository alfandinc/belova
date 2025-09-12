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
            // Date filter
            $tanggalStart = $request->input('tanggal_start');
            $tanggalEnd = $request->input('tanggal_end');

            $klinikId = $request->input('klinik_id');
            $query = SpkTindakan::with([
                'riwayatTindakan.tindakan',
                'riwayatTindakan.visitation.pasien',
                'riwayatTindakan.visitation.dokter.user'
            ]);

            // If date filter provided, filter by tanggal_tindakan
            if ($tanggalStart && $tanggalEnd) {
                $query->whereBetween('tanggal_tindakan', [$tanggalStart, $tanggalEnd]);
            } else {
                // Default: today
                $today = now()->format('Y-m-d');
                $query->whereDate('tanggal_tindakan', $today);
            }

            // Klinik filter
            if ($klinikId) {
                $query->whereHas('riwayatTindakan.visitation', function($q) use ($klinikId) {
                    $q->where('klinik_id', $klinikId);
                });
            }

            $spkTindakans = $query->get();

            // Group by visitation
            $visitationGroups = $spkTindakans
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
                        $overallStatus = 'completed';
                    } elseif ($completedCount > 0 || $inProgressCount > 0) {
                        $overallStatus = 'in_progress';
                    } else {
                        $overallStatus = 'pending';
                    }

                    return (object) [
                        'visitation_id' => $firstSpk->riwayatTindakan->visitation_id,
                        'spk_ids' => $spkIds,
                        'pasien_nama' => $firstSpk->riwayatTindakan->visitation->pasien->nama ?? '-',
                        'rm' => $firstSpk->riwayatTindakan->visitation->pasien_id ?? '-',
                        'dokter_nama' => $firstSpk->riwayatTindakan->visitation->dokter->user->name ?? '-',
                        'tindakan_names' => $tindakanList,
                        'tindakan_count' => count($tindakanList),
                        'tanggal_tindakan' => $firstSpk->tanggal_tindakan,
                        'status' => $overallStatus
                    ];
                })
                ->values();

            return DataTables::of($visitationGroups)
                ->addColumn('tindakan_nama', function($row) {
                    return implode('<br>', $row->tindakan_names);
                })
                ->addColumn('tanggal_tindakan', function($row) {
                    if ($row->tanggal_tindakan) {
                        \Carbon\Carbon::setLocale('id');
                        return \Carbon\Carbon::parse($row->tanggal_tindakan)->translatedFormat('j F Y');
                    }
                    return '-';
                })
                ->addColumn('status_badge', function($row) {
                    $spkGroup = SpkTindakan::whereIn('id', $row->spk_ids)->get();
                    $statuses = $spkGroup->pluck('status')->toArray();
                    $totalTindakan = count($statuses);
                    $completedCount = count(array_filter($statuses, function($status) {
                        return $status === 'completed';
                    }));
                    $statusColors = [
                        'pending' => 'warning',
                        /* Lines 86-89 omitted */
                    ];
                    $color = $statusColors[$row->status] ?? 'secondary';
                    $statusText = ucfirst(str_replace('_', ' ', $row->status));
                    if ($row->status === 'completed') {
                        $statusText = "✅ Completed ({$completedCount}/{$totalTindakan})";
                    } elseif ($row->status === 'in_progress') {
                        $statusText = "⏳ In Progress ({$completedCount}/{$totalTindakan})";
                    } else {/* Lines 100-101 omitted */}
                    return '<span class="badge badge-' . $color . '">' . $statusText . '</span>';
                })
                ->addColumn('action', function($row) {
                    $spkIdsString = implode(',', $row->spk_ids);
                    return '<button type="button" class="btn btn-primary btn-sm" onclick="showSpkItems([' . $spkIdsString . '])">Input SPK Tindakan</button>';
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
