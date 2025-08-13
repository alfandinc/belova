<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Permintaan;
use App\Models\ERM\PermintaanItem;
use App\Models\ERM\Obat;
use App\Models\ERM\Pemasok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermintaanController extends Controller
{
    public function edit($id)
    {
        $permintaan = Permintaan::with('items')->findOrFail($id);
        $obats = Obat::all();
        $pemasoks = Pemasok::all();
        return view('erm.permintaan.create', compact('permintaan', 'obats', 'pemasoks'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'request_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => 'required|exists:erm_obat,id',
            'items.*.pemasok_id' => 'required|exists:erm_pemasok,id',
            'items.*.jumlah_box' => 'required|integer|min:1',
            'items.*.qty_total' => 'required|integer|min:1',
        ]);
        DB::transaction(function () use ($request, $id) {
            $permintaan = Permintaan::findOrFail($id);
            $permintaan->update([
                'request_date' => $request->request_date,
            ]);
            // Delete old items
            PermintaanItem::where('permintaan_id', $permintaan->id)->delete();
            // Insert new items
            foreach ($request->items as $item) {
                PermintaanItem::create([
                    'permintaan_id' => $permintaan->id,
                    'obat_id' => $item['obat_id'],
                    'pemasok_id' => $item['pemasok_id'],
                    'jumlah_box' => $item['jumlah_box'],
                    'qty_total' => $item['qty_total'],
                ]);
            }
        });
        return response()->json(['success' => true, 'message' => 'Permintaan updated!']);
    }
    public function index()
    {
        // Just return the view, DataTables will fetch data via AJAX
        return view('erm.permintaan.index');
    }

    // DataTables AJAX endpoint
    public function data(Request $request)
    {
        $query = Permintaan::with('items')->orderBy('created_at', 'desc');
        $total = $query->count();
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $search = $request->input('search.value');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%$search%")
                  ->orWhere('status', 'like', "%$search%")
                  ->orWhere('request_date', 'like', "%$search%");
            });
        }
        $filtered = $query->count();
        $data = $query->skip($start)->take($length)->get()->values()->map(function($p, $i) use ($start) {
            $aksi = '';
            // Get comma-separated list of obat names
            $obatList = $p->items->map(function($item) {
                return optional($item->obat)->nama;
            })->filter()->unique()->implode(', ');
            // Get pemasok name (should be the same for all items in this permintaan)
            $pemasokName = optional($p->items->first() ? $p->items->first()->pemasok : null)->nama ?? '-';
            // Get approved_by user name if approved
            $approved_by_name = null;
            if ($p->status === 'approved' && $p->approved_by) {
                $user = \App\Models\User::find($p->approved_by);
                $approved_by_name = $user ? $user->name : null;
            }
            if ($p->status === 'approved') {
                $aksi .= '<a href="/erm/permintaan/'.$p->id.'/print" target="_blank" class="btn btn-secondary btn-sm mr-1"><i class="fa fa-print"></i> Print</a>';
            }
            if ($p->status === 'waiting_approval') {
                $aksi .= '<a href="/erm/permintaan/'.$p->id.'/edit" class="btn btn-info btn-sm mr-1">Edit</a>';
                $aksi .= '<button class="btn btn-success btn-sm btn-approve" data-id="'.$p->id.'">Approve</button>';
            }
            return [
                'no' => $start + $i + 1,
                'no_permintaan' => $p->no_permintaan,
                'pemasok' => $pemasokName,
                'obats' => $obatList,
                'request_date' => $p->request_date,
                'status' => $p->status,
                'approved_by_name' => $approved_by_name,
                'jumlah_item' => $p->items->count(),
                'aksi' => $aksi,
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    public function create()
    {
        $obats = Obat::all();
        $pemasoks = Pemasok::all();
        return view('erm.permintaan.create', compact('obats', 'pemasoks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'request_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => 'required|exists:erm_obat,id',
            'items.*.pemasok_id' => 'required|exists:erm_pemasok,id',
            'items.*.jumlah_box' => 'required|integer|min:1',
            'items.*.qty_total' => 'required|integer|min:1',
        ]);


        DB::transaction(function () use ($request) {
            if ($request->has('id') && $request->id) {
                // Update existing (keep as is)
                $permintaan = Permintaan::findOrFail($request->id);
                $permintaan->update([
                    'request_date' => $request->request_date,
                ]);
                PermintaanItem::where('permintaan_id', $permintaan->id)->delete();
                foreach ($request->items as $item) {
                    PermintaanItem::create([
                        'permintaan_id' => $permintaan->id,
                        'obat_id' => $item['obat_id'],
                        'pemasok_id' => $item['pemasok_id'],
                        'jumlah_box' => $item['jumlah_box'],
                        'qty_total' => $item['qty_total'],
                    ]);
                }
            } else {
                // Group items by pemasok_id
                $grouped = collect($request->items)->groupBy('pemasok_id');
                foreach ($grouped as $pemasok_id => $items) {
                    $no_permintaan = $this->generateNoPermintaan();
                    $permintaan = Permintaan::create([
                        'no_permintaan' => $no_permintaan,
                        'request_date' => $request->request_date,
                        'status' => 'waiting_approval',
                    ]);
                    foreach ($items as $item) {
                        PermintaanItem::create([
                            'permintaan_id' => $permintaan->id,
                            'obat_id' => $item['obat_id'],
                            'pemasok_id' => $item['pemasok_id'],
                            'jumlah_box' => $item['jumlah_box'],
                            'qty_total' => $item['qty_total'],
                        ]);
                    }
                }
            }
        });

        return response()->json(['success' => true, 'message' => 'Permintaan saved & grouped by pemasok!']);

    }

    /**
     * Generate unique no_permintaan in format: PRYYYYMMDD-XXX
     * Example: PR20230809-001
     */
    protected function generateNoPermintaan()
    {
        $date = date('Ymd');
        $prefix = 'PR' . $date . '-';
        $last = \App\Models\ERM\Permintaan::where('no_permintaan', 'like', $prefix . '%')
            ->orderByDesc('no_permintaan')
            ->first();
        if ($last && preg_match('/-(\d{3})$/', $last->no_permintaan, $m)) {
            $num = intval($m[1]) + 1;
        } else {
            $num = 1;
        }

        return $prefix . str_pad($num, 3, '0', STR_PAD_LEFT);
    }

        public function getMasterFaktur(Request $request)
    {
        $request->validate([
            'obat_id' => 'required|exists:erm_obat,id',
            'pemasok_id' => 'required|exists:erm_pemasok,id',
        ]);
        $master = \App\Models\ERM\MasterFaktur::where('obat_id', $request->obat_id)
            ->where('pemasok_id', $request->pemasok_id)
            ->first();
        if (!$master) {
            return response()->json(['found' => false]);
        }
        return response()->json([
            'found' => true,
            'harga' => $master->harga,
            'qty_per_box' => $master->qty_per_box,
            'diskon' => $master->diskon,
            'diskon_type' => $master->diskon_type,
        ]);
    }
        public function approve($id)
    {
        $permintaan = Permintaan::with('items')->findOrFail($id);
        if ($permintaan->status !== 'waiting_approval') {
            return redirect()->back()->with('error', 'Permintaan sudah diproses.');
        }
        $userId = auth()->id();
        $now = now();
        DB::transaction(function () use ($permintaan, $userId, $now) {
            // Group items by pemasok
            $grouped = $permintaan->items->groupBy('pemasok_id');
            foreach ($grouped as $pemasokId => $items) {
                $faktur = \App\Models\ERM\FakturBeli::create([
                    'pemasok_id' => $pemasokId,
                    'no_faktur' => null,
                    'no_permintaan' => $permintaan->no_permintaan,
                    'requested_date' => $permintaan->request_date,
                    'status' => 'diminta',
                ]);
                foreach ($items as $item) {
                    // Get harga, diskon, diskon_type from master faktur
                    $master = \App\Models\ERM\MasterFaktur::where('obat_id', $item->obat_id)
                        ->where('pemasok_id', $item->pemasok_id)
                        ->first();
                        $harga = $master ? $master->harga : 0;
                        $diskon = $master ? $master->diskon : 0;
                        $diskon_type = $master ? ($master->diskon_type == 'percent' ? 'persen' : $master->diskon_type) : 'nominal';
                        \App\Models\ERM\FakturBeliItem::create([
                            'fakturbeli_id' => $faktur->id,
                            'obat_id' => $item->obat_id,
                            'qty' => $item->qty_total,
                            'sisa' => $item->qty_total,
                            'harga' => $harga,
                            'diskon' => $diskon,
                            'diskon_type' => $diskon_type,
                            'diminta' => $item->qty_total,
                        ]);
                }
            }
            $permintaan->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_date' => $now,
            ]);
        });
        return redirect()->route('erm.permintaan.index')->with('success', 'Permintaan disetujui dan faktur berhasil dibuat!');
    }

    
// ...existing code...

    /**
     * Print Surat Permintaan as PDF using mPDF
     */
    public function printSuratPermintaan($id)
    {
        $permintaan = \App\Models\ERM\Permintaan::with(['items', 'items.obat', 'items.pemasok'])->findOrFail($id);
        $mpdf = new \Mpdf\Mpdf(['format' => 'A4']);
        $html = view('erm.permintaan.print', compact('permintaan'))->render();
        $mpdf->WriteHTML($html);
        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="SuratPermintaan-'.$permintaan->no_permintaan.'.pdf"'
        ]);
    }
}
