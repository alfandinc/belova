<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Finance\FinanceDanaApprover;
use Yajra\DataTables\DataTables;

class FinanceApproverController extends Controller
{
    public function index()
    {
        return view('finance.approver.index');
    }

    public function data(Request $request)
    {
        $query = FinanceDanaApprover::with('user');
        return DataTables::of($query)
            ->addColumn('aktif_label', function ($row) {
                return $row->aktif ? 'Ya' : 'Tidak';
            })
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
            'aktif' => 'nullable|boolean',
        ]);

        $approver->update($data);
        return response()->json(['success' => true, 'data' => $approver]);
    }

    public function destroy($id)
    {
        $approver = FinanceDanaApprover::findOrFail($id);
        $approver->delete();
        return response()->json(['success' => true]);
    }
}
