<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Finance\FinancePengajuanDana;
use App\Models\Finance\FinancePengajuanDanaItem;
use App\Models\Finance\FinancePengajuanDanaApproval;
use App\Models\Finance\FinanceDanaApprover;
use App\Models\ERM\FakturBeli;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class FinancePengajuanDanaController extends Controller
{
    public function index()
    {
        return view('finance.pengajuan.index');
    }

    // Generate a kode_pengajuan (simple server-side generator)
    public function generateKode()
    {
        // Example format: PJYYYYMMDD0001
        $date = date('Ymd');
        $maxId = FinancePengajuanDana::max('id') ?? 0;
        $next = $maxId + 1;
        $kode = sprintf('PJ%s%04d', $date, $next);
        return response()->json(['kode' => $kode]);
    }

    public function data(Request $request)
    {
        $query = FinancePengajuanDana::with(['employee.user', 'division', 'approvals.approver.user']);
        // apply optional date range filter (tanggal_pengajuan)
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if ($startDate || $endDate) {
            try {
                $start = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
                $end = $endDate ? Carbon::parse($endDate)->endOfDay() : null;
                if ($start && $end) {
                    $query->whereBetween('tanggal_pengajuan', [$start, $end]);
                } elseif ($start) {
                    $query->where('tanggal_pengajuan', '>=', $start);
                } elseif ($end) {
                    $query->where('tanggal_pengajuan', '<=', $end);
                }
            } catch (\Exception $e) {
                // if parsing fails, ignore the filter
            }
        }
        $currentUser = Auth::user();
        $currentApprover = null;
        if ($currentUser) {
            $currentApprover = FinanceDanaApprover::where('user_id', $currentUser->id)->first();
        }

        return DataTables::of($query)
            ->addColumn('employee_display', function($row) {
                // show employee name and division under it
                $name = '';
                if ($row->employee) {
                    if ($row->employee->user) $name = $row->employee->user->name;
                    else $name = $row->employee->nama ?? '';
                }
                $division = $row->division ? $row->division->name : '';
                $html = '<div class="employee-display">' . '<div>' . e($name) . '</div>';
                if ($division) {
                    $html .= '<div><small class="text-muted">' . e($division) . '</small></div>';
                }
                $html .= '</div>';
                return $html;
            })

            ->addColumn('items_list', function($row) {
                // render a compact list of items (name and qty)
                $parts = [];
                foreach ($row->items as $it) {
                    $itemName = $it->nama_item ?? ($it->name ?? '');
                    $qty = $it->jumlah ?? ($it->qty ?? 0);
                    $parts[] = '<div class="item-line"><div>' . e($itemName) . ' <small class="text-muted">x' . e($qty) . '</small></div></div>';
                }
                return implode('', $parts);
            })

            ->addColumn('actions', function ($row) {
                $btns = '<div class="btn-group" role="group">';

                // View (PDF) button - opens PDF in new tab
                $btns .= '<a class="btn btn-sm btn-secondary" href="/finance/pengajuan-dana/' . $row->id . '/pdf" target="_blank" title="Lihat">View</a>';

                // Only show edit/delete to the employee who created the pengajuan
                $user = Auth::user();
                $currentEmployeeId = null;
                if ($user && isset($user->employee) && $user->employee) {
                    $currentEmployeeId = $user->employee->id;
                }

                if ($currentEmployeeId !== null && $row->employee_id == $currentEmployeeId) {
                    $btns .= '<button class="btn btn-sm btn-primary edit-pengajuan" data-id="' . $row->id . '">Edit</button>';
                    $btns .= '<button class="btn btn-sm btn-danger delete-pengajuan" data-id="' . $row->id . '">Delete</button>';
                }

                // render approve button only if current user is an approver and hasn't approved
                if ($user) {
                    $approver = FinanceDanaApprover::where('user_id', $user->id)->first();
                    if ($approver) {
                        $already = FinancePengajuanDanaApproval::where('pengajuan_id', $row->id)->where('approver_id', $approver->id)->exists();
                        if (!$already) {
                            $btns .= '<button class="btn btn-sm btn-success approve-pengajuan ms-1" data-id="' . $row->id . '">Approve</button>';
                        }
                    }
                }

                $btns .= '</div>';
                return $btns;
            })
            ->addColumn('approvals_list', function($row) {
                $items = [];
                foreach ($row->approvals as $ap) {
                    if ($ap->approver && $ap->approver->user) {
                        $name = $ap->approver->user->name;
                        $date = $ap->tanggal_approve ? Carbon::parse($ap->tanggal_approve)->format('d M Y') : '';
                        // escape values to avoid injecting raw user input, but we return HTML container
                        $items[] = '<div class="approval-item mb-1">'
                            . '<div>' . e($name) . '</div>'
                            . '<div><small class="text-muted">' . e($date) . '</small></div>'
                            . '</div>';
                    }
                }
                return implode('', $items);
            })
            // actions, approvals_list, employee_display and items_list contain HTML, mark them as raw so they are not escaped
            ->rawColumns(['actions', 'approvals_list', 'employee_display', 'items_list'])
            ->make(true);
    }

    // Approve an individual pengajuan (called by approver)
    public function approve(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $approver = FinanceDanaApprover::where('user_id', $user->id)->first();
        if (!$approver) {
            return response()->json(['success' => false, 'message' => 'You are not configured as an approver'], 403);
        }

        $pengajuan = FinancePengajuanDana::findOrFail($id);
        // check if already approved by this approver
        $existing = FinancePengajuanDanaApproval::where('pengajuan_id', $pengajuan->id)->where('approver_id', $approver->id)->first();
        if ($existing) {
            return response()->json(['success' => false, 'message' => 'You have already approved this pengajuan']);
        }

        $approval = FinancePengajuanDanaApproval::create([
            'pengajuan_id' => $pengajuan->id,
            'approver_id' => $approver->id,
            'status' => 'approved',
            'tanggal_approve' => Carbon::now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Pengajuan approved', 'data' => $approval]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode_pengajuan' => 'required|string|unique:finance_pengajuan_dana,kode_pengajuan',
            'employee_id' => 'nullable|integer',
            'division_id' => 'nullable|integer',
            'tanggal_pengajuan' => 'nullable|date',
            'jenis_pengajuan' => 'nullable|string',
            'status' => 'nullable|string',
            'rekening_id' => 'nullable|integer|exists:finance_rekening,id',
            'items_json' => 'nullable|json',
            'bukti_transaksi' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle file upload if present
        if ($request->hasFile('bukti_transaksi')) {
            $path = $request->file('bukti_transaksi')->store('finance/pengajuan', 'public');
            $data['bukti_transaksi'] = $path;
        }

        // persist pengajuan and its items in a transaction
        $pengajuan = null;
        $pengajuanId = null;
        DB::transaction(function() use (&$pengajuan, &$pengajuanId, $data, $request) {
            // prepare grand_total from items_json if provided
            $grandTotal = 0;
            $itemsJson = $request->input('items_json');
            if ($itemsJson) {
                $items = json_decode($itemsJson, true);
                if (is_array($items)) {
                    foreach ($items as $it) {
                        $qty = isset($it['qty']) ? intval($it['qty']) : 1;
                        $price = isset($it['price']) ? floatval($it['price']) : 0;
                        $grandTotal += ($qty * $price);
                    }
                }
            }

            // set grand_total into data so it is persisted atomically with pengajuan
            $data['grand_total'] = $grandTotal;

            $pengajuan = FinancePengajuanDana::create($data);
            $pengajuanId = $pengajuan->id;

            // persist items if present
            if (!empty($items) && is_array($items)) {
                foreach ($items as $it) {
                    // guard and normalize
                    $name = isset($it['desc']) ? $it['desc'] : (isset($it['name']) ? $it['name'] : null);
                    $qty = isset($it['qty']) ? intval($it['qty']) : 1;
                    $price = isset($it['price']) ? floatval($it['price']) : 0;

                    // faktur-type item: server-side authoritative snapshot
                    $fakturbeliId = isset($it['fakturbeli_id']) ? intval($it['fakturbeli_id']) : null;
                    if ($fakturbeliId) {
                        $faktur = FakturBeli::find($fakturbeliId);
                        if ($faktur) {
                            // preserve frontend-provided description (which includes full item list)
                            // only fallback to a default label when no description was provided
                            if (!$name || trim($name) === '') {
                                $name = 'Faktur: ' . ($faktur->no_faktur ?? '');
                            }
                            // snapshot faktur total to harga_total_snapshot and use as price
                            $price = floatval($faktur->total ?? $price);
                            FinancePengajuanDanaItem::create([
                                'pengajuan_id' => $pengajuan->id,
                                'nama_item' => $name,
                                'jumlah' => 1,
                                'harga_satuan' => $price,
                                'fakturbeli_id' => $fakturbeliId,
                                'is_faktur' => true,
                                'harga_total_snapshot' => $price,
                            ]);
                            continue; // next item
                        }
                        // if faktur not found, fall back to normal item creation (but still record provided data)
                    }

                    FinancePengajuanDanaItem::create([
                        'pengajuan_id' => $pengajuan->id,
                        'nama_item' => $name,
                        'jumlah' => $qty,
                        'harga_satuan' => $price,
                    ]);
                }
            }
        });

    // reload from DB with items for response (avoid null/type issues)
    $pengajuan = FinancePengajuanDana::with('items')->find($pengajuanId);
    return response()->json(['success' => true, 'data' => $pengajuan]);
    }

    public function show($id)
    {
        $pengajuan = FinancePengajuanDana::with(['items', 'approvals', 'employee.user', 'division'])->findOrFail($id);
        return response()->json($pengajuan);
    }

    /**
     * Generate PDF view for a pengajuan dana
     */
    public function pdf($id)
    {
        $pengajuan = FinancePengajuanDana::with(['items', 'approvals.approver.user', 'employee.user', 'division', 'rekening'])->findOrFail($id);
        // collect linked faktur IDs from items
        $fakturIds = collect($pengajuan->items)->pluck('fakturbeli_id')->filter()->unique()->values()->all();
        $fakturs = [];
        if (!empty($fakturIds)) {
            $fakturs = FakturBeli::with(['items.obat', 'items.gudang', 'pemasok'])->whereIn('id', $fakturIds)->get();
        }
        // locate a logo asset if present
        $logoCandidates = [
            'img/logo-belovacorp.png',
            'img/logo-belova-klinik.png',
            'img/logo-belovaskin.png',
            'img/logo-premiere.png',
            'img/logo-belovacorp-bw.png'
        ];
        $logoPath = null;
        foreach ($logoCandidates as $c) {
            $p = public_path($c);
            if ($p && file_exists($p)) { $logoPath = $p; break; }
        }
        // build signature QR codes for approvals and creator (tanda tangan)
        $signatures = [];
        try {
            // creator / pembuat - determine a robust name fallback
            $creatorUser = ($pengajuan->employee && $pengajuan->employee->user) ? $pengajuan->employee->user : $pengajuan->employee;
            $creatorName = $creatorUser ? ($creatorUser->name ?? $creatorUser->nama ?? '') : '';
            $creatorDate = $pengajuan->tanggal_pengajuan ? Carbon::parse($pengajuan->tanggal_pengajuan)->format('d M Y') : '';
            $creatorQr = null;
            if (!empty($creatorName)) {
                try {
                    $png = QrCode::format('png')->size(160)->generate($creatorName);
                    if (!empty($png)) {
                        $creatorQr = 'data:image/png;base64,' . base64_encode($png);
                    }
                } catch (\Exception $e) {
                    // fallback to svg if png generation fails
                    try {
                        $svg = QrCode::format('svg')->size(160)->generate($creatorName);
                        if (!empty($svg)) {
                            $creatorQr = 'data:image/svg+xml;base64,' . base64_encode($svg);
                        }
                    } catch (\Exception $e) {
                        // ignore, leave qr null
                    }
                }
            }
            // try to get creator jabatan/position
            $creatorJabatan = '';
            if ($pengajuan->employee) {
                if (isset($pengajuan->employee->position) && !empty($pengajuan->employee->position->name)) {
                    $creatorJabatan = $pengajuan->employee->position->name;
                } elseif (!empty($pengajuan->employee->jabatan)) {
                    $creatorJabatan = $pengajuan->employee->jabatan;
                } elseif (!empty($creatorUser->jabatan)) {
                    $creatorJabatan = $creatorUser->jabatan;
                }
            }

            $signatures[] = [
                'label' => 'Dibuat oleh',
                'name' => $creatorName,
                'jabatan' => $creatorJabatan,
                'date' => $creatorDate,
                'qr' => $creatorQr,
            ];

            // approvals
            foreach ($pengajuan->approvals as $ap) {
                $approverName = '';
                if ($ap->approver && $ap->approver->user) {
                    $approverName = $ap->approver->user->name;
                }
                $approveDate = $ap->tanggal_approve ? Carbon::parse($ap->tanggal_approve)->format('d M Y') : '';
                $qr = null;
                if (!empty($approverName)) {
                    try {
                        $png = QrCode::format('png')->size(160)->generate($approverName);
                        if (!empty($png)) {
                            $qr = 'data:image/png;base64,' . base64_encode($png);
                        }
                    } catch (\Exception $e) {
                        // fallback svg
                        try {
                            $svg = QrCode::format('svg')->size(160)->generate($approverName);
                            if (!empty($svg)) {
                                $qr = 'data:image/svg+xml;base64,' . base64_encode($svg);
                            }
                        } catch (\Exception $e) {
                            // ignore
                        }
                    }
                }
                // approver jabatan (from FinanceDanaApprover.jabatan)
                $approverJabatan = '';
                if ($ap->approver) {
                    $approverJabatan = $ap->approver->jabatan ?? '';
                }
                $signatures[] = [
                    'label' => 'Disetujui',
                    'name' => $approverName,
                    'jabatan' => $approverJabatan,
                    'date' => $approveDate,
                    'qr' => $qr,
                ];
            }
        } catch (\Exception $e) {
            // QR generation failed for some reason; proceed without QR images
        }

        // load blade and render to PDF
        try {
            $pdf = PDF::loadView('finance.pengajuan.pdf', compact('pengajuan', 'fakturs', 'logoPath', 'signatures'));
            $pdf->setPaper('a4', 'portrait');
            return $pdf->stream('pengajuan_' . $pengajuan->id . '.pdf');
        } catch (\Exception $e) {
            // fallback to HTML view if PDF generation fails
            return view('finance.pengajuan.pdf', compact('pengajuan', 'fakturs', 'logoPath', 'signatures'));
        }
    }

    public function update(Request $request, $id)
    {
        $pengajuan = FinancePengajuanDana::findOrFail($id);
        $data = $request->validate([
            'employee_id' => 'nullable|integer',
            'division_id' => 'nullable|integer',
            'tanggal_pengajuan' => 'nullable|date',
            'jenis_pengajuan' => 'nullable|string',
            'status' => 'nullable|string',
            'rekening_id' => 'nullable|integer|exists:finance_rekening,id',
            'items_json' => 'nullable|json',
            'bukti_transaksi' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle file upload: delete old file if exists and replace
        if ($request->hasFile('bukti_transaksi')) {
            // delete existing file
            if ($pengajuan->bukti_transaksi) {
                Storage::disk('public')->delete($pengajuan->bukti_transaksi);
            }
            $path = $request->file('bukti_transaksi')->store('finance/pengajuan', 'public');
            $data['bukti_transaksi'] = $path;
        }

        DB::transaction(function() use ($pengajuan, $data, $request) {
            // compute grand_total from incoming items_json (if present)
            $grandTotal = 0;
            $itemsJson = $request->input('items_json');
            $items = [];
                if ($itemsJson !== null) {
                $items = json_decode($itemsJson, true) ?: [];
                if (is_array($items)) {
                    foreach ($items as $it) {
                        // handle faktur items too
                        if (isset($it['fakturbeli_id']) && $it['fakturbeli_id']) {
                            $fakt = FakturBeli::find(intval($it['fakturbeli_id']));
                            if ($fakt) {
                                $grandTotal += floatval($fakt->total ?? 0);
                                continue;
                            }
                        }
                        $qty = isset($it['qty']) ? intval($it['qty']) : 1;
                        $price = isset($it['price']) ? floatval($it['price']) : 0;
                        $grandTotal += ($qty * $price);
                    }
                }
                // assign computed grand_total to data for update
                $data['grand_total'] = $grandTotal;
            }

            $pengajuan->update($data);

            // update items: delete existing and recreate from items_json
                if ($itemsJson !== null) {
                $pengajuan->items()->delete();
                if (is_array($items)) {
                    foreach ($items as $it) {
                        $name = isset($it['desc']) ? $it['desc'] : (isset($it['name']) ? $it['name'] : null);
                        $qty = isset($it['qty']) ? intval($it['qty']) : 1;
                        $price = isset($it['price']) ? floatval($it['price']) : 0;
                        $fakturbeliId = isset($it['fakturbeli_id']) ? intval($it['fakturbeli_id']) : null;
                        if ($fakturbeliId) {
                            $faktur = FakturBeli::find($fakturbeliId);
                            if ($faktur) {
                                // keep provided description (includes item list) when available
                                if (!$name || trim($name) === '') {
                                    $name = 'Faktur: ' . ($faktur->no_faktur ?? '');
                                }
                                $price = floatval($faktur->total ?? $price);
                                FinancePengajuanDanaItem::create([
                                    'pengajuan_id' => $pengajuan->id,
                                    'nama_item' => $name,
                                    'jumlah' => 1,
                                    'harga_satuan' => $price,
                                    'fakturbeli_id' => $fakturbeliId,
                                    'is_faktur' => true,
                                    'harga_total_snapshot' => $price,
                                ]);
                                continue;
                            }
                        }
                        FinancePengajuanDanaItem::create([
                            'pengajuan_id' => $pengajuan->id,
                            'nama_item' => $name,
                            'jumlah' => $qty,
                            'harga_satuan' => $price,
                        ]);
                    }
                }
            }
        });

    // reload from DB with items for response
    $pengajuan = FinancePengajuanDana::with('items')->find($pengajuan->id);
    return response()->json(['success' => true, 'data' => $pengajuan]);
    }

    public function destroy($id)
    {
        $pengajuan = FinancePengajuanDana::findOrFail($id);
        $pengajuan->delete();
        return response()->json(['success' => true]);
    }
}
