<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ERM\Gudang;
use App\Models\ERM\GudangMapping;
use App\Models\ERM\MetodeBayar;
use App\Models\ERM\Spesialisasi;

/**
 * Combined admin page for Obat Mapping and Gudang Mapping.
 * Data and CRUD are still served by ERM\ObatMappingController and ERM\GudangMappingController.
 */
class ObatGudangMappingController extends Controller
{
    public function index()
    {
        $metodeBayars = MetodeBayar::all();
        $gudangs = Gudang::orderBy('nama')->get();
        $transactionTypes = GudangMapping::getTransactionTypes();
        $spesialisasis = Spesialisasi::orderBy('nama')->get();
        $billingContexts = GudangMapping::getBillingContextOptions();

        return view('admin.obat_gudang_mapping.index', compact(
            'metodeBayars', 'gudangs', 'transactionTypes', 'spesialisasis', 'billingContexts'
        ));
    }
}
