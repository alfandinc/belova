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
            ])->orderBy('created_at', 'desc');

            return DataTables::of($query)
                ->addColumn('lampiran_link', function($row){
                    if ($row->lampiran) {
                        return '<a href="'.route('workdoc.surat-keluar.download', $row->id).'">Unduh</a>';
                    }
                    return '';
                })
                ->addColumn('action', function($row){
                    $edit = '<button class="btn btn-sm btn-info btn-edit" data-id="'.$row->id.'">Edit</button>';
                    $del = '<button class="btn btn-sm btn-danger btn-delete" data-id="'.$row->id.'">Hapus</button>';
                    return $edit.' '.$del;
                })
                ->rawColumns(['lampiran_link','action'])
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

        // default status to 'waiting' when creating
        if (empty($data['status'])) {
            $data['status'] = 'waiting';
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
}
