<?php

namespace App\Http\Controllers;

use App\Models\Rnd\RndMasterBahanAktif;
use App\Models\Rnd\RndMasterBrand;
use App\Models\Rnd\RndMasterKemasan;
use App\Models\Rnd\RndMasterSediaan;
use App\Models\Rnd\RndMasterVendor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class RndDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user || !$user->hasAnyRole(['Admin', 'Rnd', 'rnd', 'RND'])) {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $masters = [];

        foreach ($this->masterModels() as $key => $modelClass) {
            $model = new $modelClass();
            $table = $model->getTable();

            $masters[] = [
                'key' => $key,
                'label' => $modelClass::label(),
                'table' => $table,
                'fields' => $modelClass::fields(),
                'columns' => $modelClass::columns(),
                'count' => Schema::hasTable($table) ? $modelClass::query()->count() : 0,
            ];
        }

        return view('rnd.dashboard', compact('masters'));
    }

    private function masterModels(): array
    {
        return [
            'brand' => RndMasterBrand::class,
            'kemasan' => RndMasterKemasan::class,
            'sediaan' => RndMasterSediaan::class,
            'vendor' => RndMasterVendor::class,
            'bahan-aktif' => RndMasterBahanAktif::class,
        ];
    }
}