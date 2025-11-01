<?php
namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\PrSlipGajiDokter;
use App\Models\ERM\Dokter;

class PrSlipGajiDokterController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->get('bulan') ?? date('Y-m');
        $dokters = Dokter::orderBy('id')->get();
        return view('hrd.payroll.slip_gaji_dokter.index', compact('dokters', 'bulan'));
    }

    public function data(Request $request)
    {
        $bulan = $request->get('bulan');
    // eager load dokter.user so we can display dokter's user name in the table
    $query = PrSlipGajiDokter::with(['dokter.user'])->orderBy('id', 'desc');
        if ($bulan) {
            $query->where('bulan', $bulan);
        }
        $rows = $query->get();
        return response()->json(['data' => $rows]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'dokter_id' => 'nullable|integer',
            'bulan' => 'required|string',
            'jasa_konsultasi' => 'nullable|numeric',
            'jasa_tindakan' => 'nullable|numeric',
            'uang_duduk' => 'nullable|numeric',
            'bagi_hasil' => 'nullable|numeric',
            'pot_pajak' => 'nullable|numeric',
            'status_gaji' => 'nullable|string',
        ]);

        $data = array_merge([
            'jasa_konsultasi' => 0,
            'jasa_tindakan' => 0,
            'uang_duduk' => 0,
            'bagi_hasil' => 0,
            'pot_pajak' => 0,
            'total_pendapatan' => 0,
            'total_potongan' => 0,
            'total_gaji' => 0,
            'status_gaji' => $data['status_gaji'] ?? 'draft',
        ], $data);

        // calculate totals
        $data['total_pendapatan'] = ($data['jasa_konsultasi'] + $data['jasa_tindakan'] + $data['uang_duduk'] + $data['bagi_hasil']);
        $data['total_potongan'] = $data['pot_pajak'];
        $data['total_gaji'] = $data['total_pendapatan'] - $data['total_potongan'];

        $slip = PrSlipGajiDokter::create($data);
        return response()->json(['success' => true, 'data' => $slip]);
    }

    public function show($id)
    {
        $slip = PrSlipGajiDokter::with('dokter')->findOrFail($id);
        return response()->json(['data' => $slip]);
    }

    public function update(Request $request, $id)
    {
        $slip = PrSlipGajiDokter::findOrFail($id);
        $data = $request->only([
            'jasa_konsultasi', 'jasa_tindakan', 'uang_duduk', 'bagi_hasil', 'pot_pajak', 'status_gaji', 'bulan', 'dokter_id'
        ]);

        foreach ($data as $k => $v) {
            if ($v !== null) $slip->{$k} = $v;
        }

        // recalc
        $slip->total_pendapatan = ($slip->jasa_konsultasi + $slip->jasa_tindakan + $slip->uang_duduk + $slip->bagi_hasil);
        $slip->total_potongan = $slip->pot_pajak;
        $slip->total_gaji = $slip->total_pendapatan - $slip->total_potongan;

        $slip->save();
        return response()->json(['success' => true, 'data' => $slip]);
    }

    public function destroy($id)
    {
        $slip = PrSlipGajiDokter::findOrFail($id);
        $slip->delete();
        return response()->json(['success' => true]);
    }

    public function print($id)
    {
        $slip = PrSlipGajiDokter::with('dokter')->findOrFail($id);
        $html = view('hrd.payroll.slip_gaji_dokter.print', compact('slip'))->render();
        // try use mPDF if present (project already uses it elsewhere)
        if (class_exists('\\Mpdf\\Mpdf')) {
            $mpdf = new \Mpdf\Mpdf(['format' => 'A4', 'margin_top' => 5, 'margin_bottom' => 5]);
            $mpdf->WriteHTML($html);
            $filename = 'slip-gaji-dokter-' . ($slip->dokter && $slip->dokter->user ? $slip->dokter->user->name : 'dokter') . '-' . $slip->bulan . '.pdf';
            $pdfString = $mpdf->Output($filename, 'S');
            return response($pdfString, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Content-Length' => strlen($pdfString),
            ]);
        }

        return response($html);
    }
}
