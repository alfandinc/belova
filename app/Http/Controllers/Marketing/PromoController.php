<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Obat;
use App\Models\ERM\Tindakan;
use App\Models\Marketing\Promo;
use App\Models\Marketing\PromoItem;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class PromoController extends Controller
{
    public function index()
    {
        return view('marketing.promo.index');
    }

    public function data(Request $request)
    {
        $query = Promo::with('promoItems');

        $start = $request->get('start_date');
        $end = $request->get('end_date');
        if ($start && $end) {
            // filter promos that overlap the selected period
            $query->where(function($q) use ($start, $end){
                $q->where(function($q2) use ($start, $end){
                    $q2->whereNotNull('start_date')->whereNotNull('end_date')
                        ->where('start_date','<=',$end)
                        ->where('end_date','>=',$start);
                })
                ->orWhere(function($q2) use ($start, $end){
                    $q2->whereNotNull('start_date')->whereNull('end_date')
                        ->whereBetween('start_date', [$start, $end]);
                })
                ->orWhere(function($q2) use ($start, $end){
                    $q2->whereNull('start_date')->whereNotNull('end_date')
                        ->whereBetween('end_date', [$start, $end]);
                });
            });
        }
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('actions', function ($row) {
                $edit = '<button class="btn btn-sm btn-primary btn-edit" data-id="'.$row->id.'">Edit</button>';
                $del = '<button class="btn btn-sm btn-danger btn-delete" data-id="'.$row->id.'">Delete</button>';
                return $edit.' '.$del;
            })
            ->addColumn('date_range', function($row){
                // Use translated month names if available
                try {
                    $start = $row->start_date ? $row->start_date->translatedFormat('j F Y') : '';
                } catch (\Exception $e) {
                    $start = $row->start_date ? $row->start_date->format('j F Y') : '';
                }
                try {
                    $end = $row->end_date ? $row->end_date->translatedFormat('j F Y') : '';
                } catch (\Exception $e) {
                    $end = $row->end_date ? $row->end_date->format('j F Y') : '';
                }
                if ($start && $end) return $start . ' - ' . $end;
                return $start ?: $end ?: '';
            })
            ->editColumn('name', function($row){
                $title = e($row->name);
                $desc = $row->description ? '<br><small class="text-muted">'.e($row->description).'</small>' : '';
                return '<strong>'.$title.'</strong>'.$desc;
            })
            ->addColumn('items', function($row){
                $itemsHtml = '';
                try {
                    $items = $row->promoItems ?: [];
                    if (!empty($items) && count($items) > 0) {
                        $parts = [];
                        foreach ($items as $it) {
                            $itemInfo = $this->getPromoItemInfo($it->item_type, (int) $it->item_id);
                            $iname = $itemInfo['name'] ?? ($it->item_type . ':' . $it->item_id);
                            $icon = ($it->item_type === 'tindakan') ? '<i class="fas fa-stethoscope text-primary mr-2"></i>' : '<i class="fas fa-pills text-success mr-2"></i>';
                            $parts[] = '<li style="list-style:none;margin:0;padding:0;">' . $icon . e($iname) . ' (<strong>'.$this->formatDiscountLabel($it->resolved_discount_type, $it->resolved_discount_value).'</strong>)</li>';
                        }
                        $itemsHtml = '<ul class="mb-0" style="padding-left:0;">'.implode('', $parts).'</ul>';
                    }
                } catch (\Exception $e) {
                    $itemsHtml = '';
                }
                return $itemsHtml;
            })
            ->editColumn('status', function($row){
                $s = strtolower($row->status);
                if ($s === 'active') {
                    $class = 'badge badge-success';
                } elseif ($s === 'inactive') {      
                    $class = 'badge badge-secondary';
                } elseif ($s === 'draft') {
                    $class = 'badge badge-warning';
                } else {
                    $class = 'badge badge-light';
                }
                return '<span class="'.$class.'">'.ucfirst($s).'</span>';
            })
            ->rawColumns(['actions','status','name','items'])
            ->make(true);
    }

    public function show(Promo $promo)
    {
        $promo->load('promoItems');
        $data = $promo->toArray();
        $data['start_date'] = $promo->start_date ? $promo->start_date->format('Y-m-d') : null;
        $data['end_date'] = $promo->end_date ? $promo->end_date->format('Y-m-d') : null;
        // include items with readable info (name will be fetched client-side if needed)
        $data['items'] = collect($promo->promoItems)->map(function($it){
            $itemInfo = $this->getPromoItemInfo($it->item_type, (int) $it->item_id);
            $discountType = $it->resolved_discount_type;
            $discountValue = $it->resolved_discount_value;
            $basePrice = round((float) ($itemInfo['base_price'] ?? 0), 2);
            $discountAmount = $it->calculateDiscountAmount($basePrice);
            return [
                'id' => $it->id,
                'item_type' => $it->item_type,
                'item_id' => $it->item_id,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_percent' => $discountType === 'percent' ? $discountValue : 0,
                'discount_label' => $this->formatDiscountLabel($discountType, $discountValue),
                'discount_amount' => $discountAmount,
                'base_price' => $basePrice,
                'discounted_price' => $it->calculateDiscountedPrice($basePrice),
                'name' => $itemInfo['name'] ?? null,
            ];
        })->values();

        return response()->json($data);
    }

    public function checkItemConflict(Request $request)
    {
        $data = $request->validate([
            'item_type' => 'required|string|in:tindakan,obat',
            'item_id' => 'required|integer',
            'promo_id' => 'nullable|integer',
        ]);

        $conflictPromo = $this->findActivePromoConflict(
            $data['item_type'],
            (int) $data['item_id'],
            isset($data['promo_id']) ? (int) $data['promo_id'] : null
        );

        if ($conflictPromo) {
            return response()->json([
                'success' => false,
                'message' => 'Item ini sudah ada di promo aktif lain (' . $conflictPromo->name . ').',
                'promo_name' => $conflictPromo->name,
                'promo_id' => $conflictPromo->id,
            ], 409);
        }

        return response()->json(['success' => true]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $promo = Promo::create($data);

        // handle items submitted as JSON string
        $itemsJson = $request->input('items');
        $items = [];
        if ($itemsJson) {
            $items = json_decode($itemsJson, true) ?: [];
            [$items, $validationError] = $this->normalizePromoItemsPayload($items);
            if ($validationError !== null) {
                return $validationError;
            }

            $inactiveItemError = $this->validatePromoItemsAreActive($items);
            if ($inactiveItemError !== null) {
                return response()->json(['success' => false, 'message' => $inactiveItemError], 422);
            }

            $promoConflictError = $this->validatePromoItemsDoNotConflictWithActivePromos($items, null);
            if ($promoConflictError !== null) {
                return response()->json(['success' => false, 'message' => $promoConflictError], 422);
            }

            foreach ($items as $it) {
                PromoItem::create([
                    'promo_id' => $promo->id,
                    'item_type' => $it['item_type'],
                    'item_id' => $it['item_id'],
                    'discount_type' => $it['discount_type'],
                    'discount_value' => $it['discount_value'],
                    'discount_percent' => $it['discount_percent'],
                ]);
            }
        }

        return response()->json(['success' => true, 'data' => $promo]);
    }

    public function update(Request $request, Promo $promo)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $promo->update($data);

        // update items: decode JSON string and replace
        if ($request->has('items')) {
            $itemsJson = $request->input('items');
            $items = $itemsJson ? (json_decode($itemsJson, true) ?: []) : [];
            [$items, $validationError] = $this->normalizePromoItemsPayload($items);
            if ($validationError !== null) {
                return $validationError;
            }

            $inactiveItemError = $this->validatePromoItemsAreActive($items);
            if ($inactiveItemError !== null) {
                return response()->json(['success' => false, 'message' => $inactiveItemError], 422);
            }

            $promoConflictError = $this->validatePromoItemsDoNotConflictWithActivePromos($items, (int) $promo->id);
            if ($promoConflictError !== null) {
                return response()->json(['success' => false, 'message' => $promoConflictError], 422);
            }

            $promo->promoItems()->delete();
            if (!empty($items)) {
                foreach ($items as $it) {
                    PromoItem::create([
                        'promo_id' => $promo->id,
                        'item_type' => $it['item_type'],
                        'item_id' => $it['item_id'],
                        'discount_type' => $it['discount_type'],
                        'discount_value' => $it['discount_value'],
                        'discount_percent' => $it['discount_percent'],
                    ]);
                }
            }
        }

        return response()->json(['success' => true, 'data' => $promo]);
    }

    // status is computed from start/end dates; no toggle method required

    public function destroy(Promo $promo)
    {
        $promo->delete();
        return response()->json(['success' => true]);
    }

    private function validatePromoItemsAreActive(array $items): ?string
    {
        foreach ($items as $item) {
            $itemType = $item['item_type'] ?? null;
            $itemId = $item['item_id'] ?? null;

            if ($itemType === 'tindakan') {
                if (!Tindakan::where('id', $itemId)->where('is_active', true)->exists()) {
                    return 'Promo hanya bisa memakai tindakan yang aktif.';
                }
            }

            if ($itemType === 'obat') {
                if (!Obat::where('id', $itemId)->where('status_aktif', 1)->exists()) {
                    return 'Promo hanya bisa memakai obat yang aktif.';
                }
            }
        }

        return null;
    }

    private function validatePromoItemsDoNotConflictWithActivePromos(array $items, ?int $ignorePromoId): ?string
    {
        $seenKeys = [];

        foreach ($items as $item) {
            $itemType = (string) ($item['item_type'] ?? '');
            $itemId = (int) ($item['item_id'] ?? 0);
            if ($itemType === '' || $itemId <= 0) {
                continue;
            }

            $key = $itemType . '|' . $itemId;
            if (isset($seenKeys[$key])) {
                continue;
            }
            $seenKeys[$key] = true;

            $conflictPromo = $this->findActivePromoConflict($itemType, $itemId, $ignorePromoId);
            if ($conflictPromo) {
                $itemInfo = $this->getPromoItemInfo($itemType, $itemId);
                $itemName = $itemInfo['name'] ?? ($itemType . ' #' . $itemId);
                return 'Item ' . $itemName . ' sudah ada di promo aktif lain (' . $conflictPromo->name . ').';
            }
        }

        return null;
    }

    private function findActivePromoConflict(string $itemType, int $itemId, ?int $ignorePromoId = null): ?Promo
    {
        $today = now()->toDateString();

        return Promo::query()
            ->when($ignorePromoId, function ($query) use ($ignorePromoId) {
                $query->where('id', '!=', $ignorePromoId);
            })
            ->whereHas('promoItems', function ($query) use ($itemType, $itemId) {
                $query->where('item_type', $itemType)
                    ->where('item_id', $itemId);
            })
            ->where(function ($query) use ($today) {
                $query->where(function ($q) use ($today) {
                    $q->whereNotNull('start_date')
                        ->whereNotNull('end_date')
                        ->where('start_date', '<=', $today)
                        ->where('end_date', '>=', $today);
                })->orWhere(function ($q) use ($today) {
                    $q->whereNotNull('start_date')
                        ->whereNull('end_date')
                        ->where('start_date', '<=', $today);
                })->orWhere(function ($q) use ($today) {
                    $q->whereNull('start_date')
                        ->whereNotNull('end_date')
                        ->where('end_date', '>=', $today);
                });
            })
            ->orderBy('id')
            ->first();
    }

    private function normalizePromoItemsPayload(array $items): array
    {
        $validator = Validator::make(['items' => $items], [
            'items.*.item_type' => 'required|string|in:tindakan,obat',
            'items.*.item_id' => 'required|integer',
            'items.*.discount_type' => 'required|string|in:percent,nominal',
            'items.*.discount_value' => 'required|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return [[], response()->json(['success' => false, 'errors' => $validator->errors()], 422)];
        }

        $normalized = [];
        foreach ($items as $item) {
            $discountType = strtolower(trim((string) ($item['discount_type'] ?? 'percent')));
            $discountValue = round((float) ($item['discount_value'] ?? 0), 2);
            if ($discountType === 'percent' && $discountValue > 100) {
                return [[], response()->json(['success' => false, 'message' => 'Diskon persen tidak boleh lebih dari 100%.'], 422)];
            }

            $normalized[] = [
                'item_type' => $item['item_type'],
                'item_id' => (int) $item['item_id'],
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_percent' => $discountType === 'percent' ? $discountValue : 0,
            ];
        }

        return [$normalized, null];
    }

    private function getPromoItemInfo(string $itemType, int $itemId): array
    {
        try {
            if ($itemType === 'tindakan') {
                $tindakan = Tindakan::find($itemId);
                if ($tindakan) {
                    return [
                        'name' => (string) $tindakan->nama,
                        'base_price' => (float) ($tindakan->harga ?? $tindakan->harga_diskon ?? 0),
                    ];
                }
            }

            if ($itemType === 'obat') {
                $obat = Obat::withInactive()->find($itemId);
                if ($obat) {
                    return [
                        'name' => (string) $obat->nama,
                        'base_price' => (float) ($obat->harga_nonfornas ?? $obat->harga_net ?? 0),
                    ];
                }
            }
        } catch (\Exception $e) {
            return [];
        }

        return [];
    }

    private function formatDiscountLabel(string $discountType, float $discountValue): string
    {
        if ($discountType === 'nominal') {
            return 'Rp ' . number_format($discountValue, 0, ',', '.');
        }

        return number_format($discountValue, 2) . '%';
    }
}
