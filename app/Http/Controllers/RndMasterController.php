<?php

namespace App\Http\Controllers;

use App\Models\Rnd\BaseRndMaster;
use App\Models\Rnd\RndMasterBahanAktif;
use App\Models\Rnd\RndMasterBrand;
use App\Models\Rnd\RndMasterKemasan;
use App\Models\Rnd\RndMasterSediaan;
use App\Models\Rnd\RndMasterVendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RndMasterController extends Controller
{
    public function data(Request $request, string $master): JsonResponse
    {
        abort_unless($request->ajax(), 404);

        $modelClass = $this->resolveModelClass($master);
        $query = $modelClass::query()->select(array_merge(['id'], (new $modelClass())->getFillable()));

        return DataTables::of($query)
            ->addColumn('actions', function ($row) use ($master) {
                return '<div class="btn-group btn-group-sm" role="group">'
                    . '<button type="button" class="btn btn-info js-edit-master" data-master="' . e($master) . '" data-id="' . e($row->id) . '" title="Edit" aria-label="Edit">'
                    . '<i class="fas fa-edit"></i>'
                    . '</button>'
                    . '<button type="button" class="btn btn-danger js-delete-master" data-master="' . e($master) . '" data-id="' . e($row->id) . '" title="Hapus" aria-label="Hapus">'
                    . '<i class="fas fa-trash-alt"></i>'
                    . '</button>'
                    . '</div>';
            })
            ->rawColumns(['actions'])
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

        $record->delete();

        return response()->json([
            'success' => true,
            'message' => $modelClass::label() . ' berhasil dihapus.',
        ]);
    }

    private function resolveModelClass(string $master): string
    {
        return match ($master) {
            'brand' => RndMasterBrand::class,
            'kemasan' => RndMasterKemasan::class,
            'sediaan' => RndMasterSediaan::class,
            'vendor' => RndMasterVendor::class,
            'bahan-aktif' => RndMasterBahanAktif::class,
            default => abort(404),
        };
    }
}