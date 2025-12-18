<?php

namespace App\Http\Controllers\Workdoc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Workdoc\SuratKeluar;
use App\Models\Workdoc\SuratJenis;
use App\Models\User;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class SuratKeluarController extends Controller
{
    public function index()
    {
        return view('workdoc.surat_keluar.index');
    }

    // AJAX: list (DataTables server-side)
    public function list(Request $request)
    {
        if ($request->ajax()) {
            $query = SuratKeluar::select([
                'id','no_surat','instansi','jenis_surat','deskripsi','status','diajukan_for','created_by','tgl_dibuat','lampiran','tgl_diajukan','tgl_disetujui','disetujui_by','created_at'
            ]);

            return DataTables::of($query)
                ->addColumn('person_info', function($row){
                    $diajukan = '';
                    $created = '';
                    if ($row->diajukan_for) {
                        $u = User::find($row->diajukan_for);
                        $diajukan = $u ? $u->name : $row->diajukan_for;
                    }
                    if ($row->created_by) {
                        $u2 = User::find($row->created_by);
                        $created = $u2 ? $u2->name : $row->created_by;
                    }
                    $html = '';
                    if ($diajukan) $html .= '<div><strong>Diajukan:</strong> '.$diajukan.'</div>';
                    if ($created) $html .= '<div><strong>Dibuat:</strong> '.$created.'</div>';
                    return $html ?: '';
                })
                ->addColumn('action', function($row){
                    $group = '<div class="btn-group" role="group">';
                    if ($row->lampiran) {
                        $group .= '<a class="btn btn-sm btn-secondary" href="'.route('workdoc.surat-keluar.download', $row->id).'" target="_blank" rel="noopener">Lampiran</a>';
                    }
                    $currentUser = auth()->id();
                    // show Ajukan button for creator when status is draft or revisi
                    $status = strtolower(trim($row->status ?? ''));
                    if ($currentUser && $row->created_by && $currentUser == $row->created_by) {
                        if (in_array($status, ['draft','revisi'])) {
                            $group .= '<button class="btn btn-sm btn-success btn-ajukan" data-id="'.$row->id.'">Ajukan</button>';
                        }
                        $group .= '<button class="btn btn-sm btn-info btn-edit" data-id="'.$row->id.'">Edit</button>';
                        $group .= '<button class="btn btn-sm btn-danger btn-delete" data-id="'.$row->id.'">Hapus</button>';
                    }
                    // show Approve button for the user assigned in diajukan_for when status is diajukan
                    if ($status === 'diajukan' && $row->diajukan_for && $currentUser && $currentUser == $row->diajukan_for) {
                        $group .= '<button class="btn btn-sm btn-success btn-approve" data-id="'.$row->id.'">Approve</button>';
                        $group .= '<button class="btn btn-sm btn-danger btn-revisi" data-id="'.$row->id.'">Revisi</button>';
                    }
                    $group .= '</div>';
                    return $group;
                })
                ->rawColumns(['action','person_info'])
                ->make(true);
        }
        return abort(404);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'no_surat' => 'nullable|string|max:255',
            'instansi' => 'nullable|string|max:255',
            'jenis_surat' => 'nullable|string|max:255',
            'deskripsi' => 'nullable|string',
            'status' => 'nullable|string|max:50',
            'diajukan_for' => 'nullable|integer',
            'created_by' => 'nullable|integer',
            'tgl_dibuat' => 'nullable|date',
            'tgl_diajukan' => 'nullable|date',
            'tgl_disetujui' => 'nullable|date',
            'disetujui_by' => 'nullable|integer',
            'lampiran' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        if ($request->hasFile('lampiran')) {
            $path = $request->file('lampiran')->store('workdoc/surat_keluar', 'public');
            $data['lampiran'] = $path;
        }

        // default status to 'draft' when creating
        if (empty($data['status'])) {
            $data['status'] = 'draft';
        }

        // set created_by to currently authenticated user
        if ($request->user()) {
            $data['created_by'] = $request->user()->id;
        }

        $model = SuratKeluar::create($data);

        return response()->json(['success' => true, 'data' => $model]);
    }

    public function show($id)
    {
        $model = SuratKeluar::findOrFail($id);
        return response()->json(['data' => $model]);
    }

    public function update(Request $request, $id)
    {
        $model = SuratKeluar::findOrFail($id);

        $data = $request->validate([
            'no_surat' => 'nullable|string|max:255',
            'instansi' => 'nullable|string|max:255',
            'jenis_surat' => 'nullable|string|max:255',
            'deskripsi' => 'nullable|string',
            'status' => 'nullable|string|max:50',
            'diajukan_for' => 'nullable|integer',
            'created_by' => 'nullable|integer',
            'tgl_dibuat' => 'nullable|date',
            'tgl_diajukan' => 'nullable|date',
            'tgl_disetujui' => 'nullable|date',
            'disetujui_by' => 'nullable|integer',
            'lampiran' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        if ($request->hasFile('lampiran')) {
            // delete old
            if ($model->lampiran) {
                Storage::disk('public')->delete($model->lampiran);
            }
            $path = $request->file('lampiran')->store('workdoc/surat_keluar', 'public');
            $data['lampiran'] = $path;
        }

        // Do not allow changing creator via update form - preserve original created_by
        if (array_key_exists('created_by', $data)) {
            unset($data['created_by']);
        }

        $model->update($data);

        return response()->json(['success' => true, 'data' => $model]);
    }

    public function destroy($id)
    {
        $model = SuratKeluar::findOrFail($id);
        if ($model->lampiran) {
            Storage::disk('public')->delete($model->lampiran);
        }
        $model->delete();
        return response()->json(['success' => true]);
    }

    public function download($id)
    {
        $model = SuratKeluar::findOrFail($id);
        if (!$model->lampiran || !Storage::disk('public')->exists($model->lampiran)) {
            abort(404);
        }
        // Serve PDFs inline so browsers open them in a new tab instead of forcing download
        try {
            $path = Storage::disk('public')->path($model->lampiran);
            $mime = Storage::disk('public')->mimeType($model->lampiran);
        } catch (\Exception $e) {
            return Storage::disk('public')->download($model->lampiran);
        }

        if ($mime === 'application/pdf' && file_exists($path)) {
            return response()->file($path);
        }

        return Storage::disk('public')->download($model->lampiran);
    }

    // Generate next no_surat based on singkatan, last number, instansi code, month(Roman) and year
    public function generateNumber(Request $request)
    {
        $data = $request->validate([
            'instansi' => 'required|string',
            'jenis_surat' => 'required|string',
            'tgl_dibuat' => 'required|date',
        ]);

        $instansi = $data['instansi'];
        $jenisNama = $data['jenis_surat'];
        $tgl = $data['tgl_dibuat'];

        $jenis = SuratJenis::where('nama', $jenisNama)->first();
        $singkatan = $jenis && $jenis->singkatan ? strtoupper($jenis->singkatan) : strtoupper(substr($jenisNama, 0, 3));
        // ensure first 3 chars
        $singkatan = substr($singkatan, 0, 3);

        // instansi code mapping
        $instMap = [
            'Belova Skincare' => 'BL-KP',
            'Premiere Belova' => 'BL-UP',
            'BCL' => 'BC-L',
        ];
        $instCode = $instMap[$instansi] ?? strtoupper(str_replace(' ', '-', $instansi));

        $dt = Carbon::parse($tgl);
        $month = (int)$dt->format('m');
        $year = $dt->format('Y');
        $roman = [1=>'I',2=>'II',3=>'III',4=>'IV',5=>'V',6=>'VI',7=>'VII',8=>'VIII',9=>'IX',10=>'X',11=>'XI',12=>'XII'][$month];

        // Find max existing sequence for same year + same jenis + same instansi
        $rows = SuratKeluar::whereYear('tgl_dibuat', $year)
            ->where('jenis_surat', $jenisNama)
            ->where('instansi', $instansi)
            ->get(['no_surat']);

        $max = 0;
        foreach ($rows as $r) {
            if (!$r->no_surat) continue;
            $parts = explode('.', $r->no_surat);
            if (count($parts) < 2) continue;
            $afterDot = $parts[1];
            $numStr = explode('/', $afterDot)[0];
            $num = intval($numStr);
            if ($num > $max) $max = $num;
        }

        $next = $max + 1;
        $numFormatted = sprintf('%04d', $next);

        $no = sprintf('%s.%s/%s/%s/%s', $singkatan, $numFormatted, $instCode, $roman, $year);

        return response()->json(['data' => ['no_surat' => $no]]);
    }

    // AJAX: list jenis surat for select options
    public function jenisList()
    {
        $list = SuratJenis::orderBy('nama')->get(['id','nama','singkatan','kode']);
        return response()->json(['data' => $list]);
    }

    // AJAX: list users who have role Ceo
    public function diajukanForList()
    {
        $users = User::role('Ceo')->orderBy('name')->get(['id','name']);
        return response()->json(['data' => $users]);
    }

    // Change status to 'diajukan' (submit) — only creator can ajukan
    public function ajukan(Request $request, $id)
    {
        $model = SuratKeluar::findOrFail($id);
        $userId = $request->user() ? $request->user()->id : null;
        if (!$userId || $model->created_by != $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $status = strtolower(trim($model->status ?? ''));
        if (!in_array($status, ['draft','revisi'])) {
            return response()->json(['success' => false, 'message' => 'Invalid status'], 400);
        }
        $model->status = 'diajukan';
        $model->tgl_diajukan = now();
        $model->save();
        return response()->json(['success' => true, 'data' => $model]);
    }

    // Approve the surat: set status to 'disetujui' and record approver
    public function approve(Request $request, $id)
    {
        $model = SuratKeluar::findOrFail($id);
        $userId = $request->user() ? $request->user()->id : null;
        // only the user assigned in diajukan_for can approve
        if (!$userId || $model->diajukan_for != $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        if (strtolower(trim($model->status ?? '')) !== 'diajukan') {
            return response()->json(['success' => false, 'message' => 'Invalid status'], 400);
        }
        $model->status = 'disetujui';
        $model->tgl_disetujui = now();
        $model->disetujui_by = $userId;
        $model->save();
        return response()->json(['success' => true, 'data' => $model]);
    }

    // Mark surat as 'revisi' (request revision) — only allowed for diajukan_for user
    public function revisi(Request $request, $id)
    {
        $model = SuratKeluar::findOrFail($id);
        $userId = $request->user() ? $request->user()->id : null;
        // only the user assigned in diajukan_for can request revisi
        if (!$userId || $model->diajukan_for != $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        if (strtolower(trim($model->status ?? '')) !== 'diajukan') {
            return response()->json(['success' => false, 'message' => 'Invalid status'], 400);
        }
        $model->status = 'revisi';
        $model->save();
        return response()->json(['success' => true, 'data' => $model]);
    }
}
