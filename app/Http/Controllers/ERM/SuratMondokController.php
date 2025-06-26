<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\SuratMondok;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class SuratMondokController extends Controller
{
    /**
     * Print Surat Mondok PDF
     * This method is kept for PDF generation only
     */
    public function suratMondok($id)
    {
        try {
            $suratMondok = SuratMondok::with(['dokter.user', 'pasien'])->findOrFail($id);

            $dob = Carbon::parse($suratMondok->pasien->tanggal_lahir);
            $now = Carbon::now();
            $difference = $dob->diff($now);
            $umur = sprintf(
                '%d Tahun %d Bulan %d Hari',
                $difference->y, // Years
                $difference->m, // Months
                $difference->d  // Days
            );

            // Handle TTD (signature) image path
            $ttdPath = null;
            if ($suratMondok->dokter->ttd) {
                $rawTtdPath = $suratMondok->dokter->ttd;
                $ttdRelativePath = ltrim($rawTtdPath, '/\\');
                
                if (!str_starts_with($ttdRelativePath, 'img/qr/')) {
                    $ttdRelativePath = 'img/qr/' . basename($ttdRelativePath);
                }
                
                $ttdFullPath = public_path($ttdRelativePath);
                
                if (file_exists($ttdFullPath) && is_readable($ttdFullPath)) {
                    $ttdPath = $ttdRelativePath;
                }
            }

            $data = [
                'nama' => $suratMondok->pasien->nama ?? '-',
                'pekerjaan' => $suratMondok->pasien->pekerjaan ?? '-',
                'alamat' => $suratMondok->pasien->alamat ?? '-',
                'nama_dokter' => $suratMondok->dokter->user->name ?? '-',
                'ttd' => $ttdPath,
                'tanggal_surat' => Carbon::now()->translatedFormat('d F Y'),
                'umur' => $umur,
                'tujuan_igd' => $suratMondok->tujuan_igd,
                'diagnosa' => $suratMondok->diagnosa,
                'instruksi_terapi' => $suratMondok->instruksi_terapi,
            ];

            $pdf = PDF::loadView('erm.suratmondok.print-simple', $data)
                ->setPaper('a5', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => true,
                    'defaultFont' => 'Arial',
                    'debugKeepTemp' => false,
                    'isRemoteEnabled' => false
                ]);

            return $pdf->stream('surat-mondok.pdf');
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan saat membuat PDF. Silakan coba lagi.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
