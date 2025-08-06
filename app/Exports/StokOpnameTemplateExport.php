<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class StokOpnameTemplateExport implements FromView
{
    protected $obats;
    public function __construct($obats)
    {
        $this->obats = $obats;
    }
    public function view(): View
    {
        return view('erm.stokopname.export_template', [
            'obats' => $this->obats
        ]);
    }
}
