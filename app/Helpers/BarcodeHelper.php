<?php
// This helper can be used to generate barcodes using milon/barcode
// Usage: BarcodeHelper::generateBarcodeBase64('data', 'C128')
namespace App\Helpers;

use Milon\Barcode\DNS1D;

class BarcodeHelper
{
    public static function generateBarcodeBase64($data, $type = 'C128', $width = 2, $height = 80)
    {
        $barcode = new DNS1D();
        $barcode->setStorPath(public_path('cache/'));
        $base64 = $barcode->getBarcodePNG($data, $type, $width, $height);
        return $base64;
    }
}
