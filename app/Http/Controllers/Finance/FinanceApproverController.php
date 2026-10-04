<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Finance\FinanceDanaApprover;
use App\Models\Finance\FinancePengajuanDanaApproval;
use Yajra\DataTables\DataTables;

class FinanceApproverController extends Controller
{
    public function data(Request $request)
    {
        // managed from the Kelola Approver modal on the pengajuan page (Admin only, see routes)
        $query = FinanceDanaApprover::with('user:id,name');
        return DataTables::of($query)
            ->addColumn('aktif_label', function ($row) {
                return $row->aktif ? 'Ya' : 'Tidak';
            })
            ->addColumn('tingkat', function($row) { return $row->tingkat ?? 1; })
            ->addColumn('jenis', function($row) { return $row->jenis ?? ''; })
            ->addColumn('actions', function ($row) {
                $btns = '<div class="btn-group" role="group">';
                $btns .= '<button class="btn btn-sm btn-primary edit-approver" data-id="' . $row->id . '">Edit</button>';
                $btns .= '<button class="btn btn-sm btn-danger delete-approver" data-id="' . $row->id . '">Delete</button>';
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'jabatan' => 'nullable|string',
            'tingkat' => 'nullable|integer|min:1',
            'jenis' => 'nullable|string',
            'aktif' => 'nullable|boolean',
        ]);

        $approver = FinanceDanaApprover::create($data);
        return response()->json(['success' => true, 'data' => $approver]);
    }

    public function show($id)
    {
        $approver = FinanceDanaApprover::with('user')->findOrFail($id);
        return response()->json($approver);
    }

    public function update(Request $request, $id)
    {
        $approver = FinanceDanaApprover::findOrFail($id);
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'jabatan' => 'nullable|string',
            'tingkat' => 'nullable|integer|min:1',
            'jenis' => 'nullable|string',
            'aktif' => 'nullable|boolean',
        ]);

        $approver->update($data);
        return response()->json(['success' => true, 'data' => $approver]);
    }

    public function destroy($id)
    {
        $approver = FinanceDanaApprover::findOrFail($id);
        // approvals cascade on delete, so removing an approver who already acted would erase that history
        if (FinancePengajuanDanaApproval::where('approver_id', $approver->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Approver ini sudah pernah menyetujui/menolak pengajuan. Nonaktifkan saja (Aktif = Tidak) agar riwayat persetujuan tetap tersimpan.',
            ], 422);
        }
        $approver->delete();
        return response()->json(['success' => true]);
    }
}
