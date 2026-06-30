<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Merchandise;
use App\Models\ERM\MerchandiseKartuStok;
use App\Models\ERM\PasienMerchandise;
use Illuminate\Support\Facades\DB;

class MasterMerchandiseController extends Controller
{
    protected function getCurrentStock(int $merchandiseId): int
    {
        return MerchandiseKartuStok::getLatestCurrentStock($merchandiseId);
    }

    protected function getCurrentMonthNetUsedQty(int $merchandiseId): int
    {
        return (int) PasienMerchandise::where('merchandise_id', $merchandiseId)
            ->whereBetween('given_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('quantity');
    }

    protected function getRemainingMonthlyStock(Merchandise $merchandise): ?int
    {
        if ($merchandise->monthly_limit_stock === null) {
            return null;
        }

        return max(0, (int) $merchandise->monthly_limit_stock - $this->getCurrentMonthNetUsedQty($merchandise->id));
    }

    public function index(Request $request)
    {
        return view('marketing.master_merchandise.index');
    }

    // Data for yajra DataTable
    public function data(Request $request)
    {
        $query = Merchandise::query();

        return datatables()->of($query)
            ->addColumn('current_stock', function ($row) {
                return $this->getCurrentStock($row->id);
            })
            ->addColumn('used_this_month', function ($row) {
                return $this->getCurrentMonthNetUsedQty($row->id);
            })
            ->addColumn('remaining_monthly_stock', function ($row) {
                return $this->getRemainingMonthlyStock($row);
            })
            ->addColumn('actions', function ($row) {
                return '<button class="btn btn-sm btn-info btn-history mr-1" data-id="'.$row->id.'" data-name="'.e($row->name).'">Kartu Stok</button> '
                     . '<button class="btn btn-sm btn-success btn-stock mr-1" data-id="'.$row->id.'" data-name="'.e($row->name).'">Add Stock</button> '
                     . '<button class="btn btn-sm btn-primary btn-edit" data-id="'.$row->id.'">Edit</button> '
                     . '<button class="btn btn-sm btn-danger btn-delete" data-id="'.$row->id.'">Delete</button>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function edit($id)
    {
        $m = Merchandise::find($id);
        if (!$m) return response()->json(['error' => 'Not found'], 404);
        return response()->json($m);
    }

    public function stockHistory($id)
    {
        $m = Merchandise::find($id);
        if (!$m) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $items = MerchandiseKartuStok::query()
            ->where('merchandise_id', $m->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'tanggal' => optional($row->tanggal)->format('Y-m-d H:i:s') ?: $row->tanggal,
                    'type' => $row->type,
                    'qty' => (int) $row->qty,
                    'current_stock' => (int) ($row->current_stock ?? 0),
                    'notes' => $row->notes,
                ];
            });

        return response()->json([
            'success' => true,
            'merchandise' => [
                'id' => $m->id,
                'name' => $m->name,
            ],
            'data' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'monthly_limit_stock' => 'nullable|integer|min:0',
        ]);

        $m = Merchandise::create($data);

        return response()->json(['success' => true, 'data' => $m]);
    }

    public function update(Request $request, $id)
    {
        $m = Merchandise::find($id);
        if (!$m) return response()->json(['error' => 'Not found'], 404);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'monthly_limit_stock' => 'nullable|integer|min:0',
        ]);

        $m->update($data);

        return response()->json(['success' => true, 'data' => $m]);
    }

    public function destroy($id)
    {
        $m = Merchandise::find($id);
        if (!$m) return response()->json(['error' => 'Not found'], 404);
        $m->delete();
        return response()->json(['success' => true]);
    }

    public function addStock(Request $request, $id)
    {
        $m = Merchandise::find($id);
        if (!$m) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $data = $request->validate([
            'qty' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($m, $data) {
            MerchandiseKartuStok::create([
                'merchandise_id' => $m->id,
                'tanggal' => now(),
                'type' => 'in',
                'qty' => (int) $data['qty'],
                'current_stock' => MerchandiseKartuStok::calculateCurrentStock($m->id, 'in', (int) $data['qty']),
                'notes' => $data['notes'] ?? 'Penambahan stok dari master merchandise',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Stock added successfully',
            'remaining_monthly_stock' => $this->getRemainingMonthlyStock($m->fresh()),
        ]);
    }
}
