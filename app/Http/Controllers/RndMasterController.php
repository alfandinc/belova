<?php

namespace App\Http\Controllers;

use App\Models\Rnd\BaseRndMaster;
use App\Models\Rnd\RndMasterBrand;
use App\Models\Rnd\RndMasterKemasan;
use App\Models\Rnd\RndMasterVendor;
use App\Models\Rnd\RndProduk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/**
 * Brand, Kemasan and Vendor masters, managed from the "Kelola ..." modals on the Produk R&D page.
 */
class RndMasterController extends Controller
{
    /** Master key => model class, in the order the Produk R&D page shows the buttons. */
    public const MODELS = [
        'brand' => RndMasterBrand::class,
        'kemasan' => RndMasterKemasan::class,
        'vendor' => RndMasterVendor::class,
    ];

    /** rnd_produk columns that point at each master (used to count and protect usage). */
    private const PRODUK_COLUMNS = [
        'brand' => ['brand_id'],
        'kemasan' => ['kemasan_premier_id', 'kemasan_sekunder_id'],
        'vendor' => [
            'produsen_vendor_id',
            'kemasan_primer_vendor_id',
            'kemasan_sekunder_vendor_id',
            'desain_kemasan_primer_id',
            'desain_kemasan_sekunder_id',
        ],
    ];

    /** Label, fields and columns of every master, for the page that renders the modals. */
    public static function definitions(): array
    {
        $definitions = [];

        foreach (self::MODELS as $key => $modelClass) {
            $definitions[] = [
                'key' => $key,
                'label' => preg_replace('/^Master\s+/i', '', $modelClass::label()),
                'fields' => $modelClass::fields(),
                'columns' => $modelClass::columns(),
            ];
        }

        return $definitions;
    }

    public function data(Request $request, string $master): JsonResponse
    {
        abort_unless($request->ajax(), 404);

        $modelClass = $this->resolveModelClass($master);
        $query = $modelClass::query()->select(array_merge(['id'], (new $modelClass())->getFillable()));

        return DataTables::of($query)
            ->addColumn('dipakai', fn ($row) => $this->usageCount($master, $row->id))
            ->make(true);
    }

    public function show(string $master, int $id): JsonResponse
    {
        $modelClass = $this->resolveModelClass($master);
        $record = $modelClass::find($id);

        abort_unless($record, 404);

        return response()->json(['data' => $record]);
    }

    public function store(Request $request, string $master): JsonResponse
    {
        $modelClass = $this->resolveModelClass($master);
        $payload = $request->validate($modelClass::validationRules());

        if ($duplicate = $this->duplicateMessage($modelClass, $payload)) {
            return response()->json(['message' => $duplicate], 422);
        }

        $record = $modelClass::create($payload);

        return response()->json([
            'success' => true,
            'message' => $modelClass::label() . ' berhasil disimpan.',
            'data' => $record,
        ]);
    }

    public function update(Request $request, string $master, int $id): JsonResponse
    {
        $modelClass = $this->resolveModelClass($master);
        $record = $modelClass::find($id);

        abort_unless($record, 404);

        $payload = $request->validate($modelClass::validationRules());

        if ($duplicate = $this->duplicateMessage($modelClass, $payload, $record->id)) {
            return response()->json(['message' => $duplicate], 422);
        }

        // A kemasan used by a product must keep its tipe (a primer kemasan stays on the primer side)
        if ($master === 'kemasan' && $payload['tipe_kemasan'] !== $record->tipe_kemasan) {
            $column = $record->tipe_kemasan === 'primer' ? 'kemasan_premier_id' : 'kemasan_sekunder_id';
            $used = RndProduk::query()->where($column, $record->id)->count();
            if ($used > 0) {
                return response()->json([
                    'message' => "Tipe kemasan tidak bisa diubah: dipakai sebagai kemasan {$record->tipe_kemasan} oleh {$used} produk.",
                ], 422);
            }
        }

        $record->update($payload);

        return response()->json([
            'success' => true,
            'message' => $modelClass::label() . ' berhasil diperbarui.',
            'data' => $record->fresh(),
        ]);
    }

    public function destroy(string $master, int $id): JsonResponse
    {
        $modelClass = $this->resolveModelClass($master);
        $record = $modelClass::find($id);

        abort_unless($record, 404);

        // Brand / kemasan primer are restricted by FK (SQL error) and vendors would silently vanish from products
        $used = $this->usageCount($master, $record->id);
        if ($used > 0) {
            return response()->json([
                'message' => $modelClass::label() . " ini masih dipakai {$used} produk. Ganti dulu di produknya sebelum menghapus.",
            ], 422);
        }

        $record->delete();

        return response()->json([
            'success' => true,
            'message' => $modelClass::label() . ' berhasil dihapus.',
        ]);
    }

    private function usageCount(string $master, int $id): int
    {
        return RndProduk::query()
            ->where(function ($query) use ($master, $id) {
                foreach (self::PRODUK_COLUMNS[$master] as $column) {
                    $query->orWhere($column, $id);
                }
            })
            ->count();
    }

    /** Same name (case-insensitive) already exists; null when it does not. */
    private function duplicateMessage(string $modelClass, array $payload, ?int $ignoreId = null): ?string
    {
        /** @var class-string<BaseRndMaster> $modelClass */
        $columns = $modelClass::duplicateColumns();
        $query = $modelClass::query()->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId));

        foreach ($columns as $column) {
            $query->whereRaw('LOWER(TRIM(' . $column . ')) = ?', [mb_strtolower(trim((string) ($payload[$column] ?? '')))]);
        }

        return $query->exists()
            ? preg_replace('/^Master\s+/i', '', $modelClass::label()) . ' dengan nama yang sama sudah ada.'
            : null;
    }

    private function resolveModelClass(string $master): string
    {
        return self::MODELS[$master] ?? abort(404);
    }
}
