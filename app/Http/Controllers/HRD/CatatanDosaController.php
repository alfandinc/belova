<?php
namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\CatatanDosa;
use App\Models\HRD\Employee;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CatatanDosaController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $catatan = CatatanDosa::with('employee')->latest()->get();
            return response()->json(['data' => $catatan]);
        }
        $employees = Employee::all();
        return view('hrd.catatan_dosa.index', compact('employees'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:hrd_employee,id',
            'jenis_pelanggaran' => 'required|string',
            'kategori' => 'required|string',
            'deskripsi' => 'required|string',
            'status_tindaklanjut' => 'required|string',
            'tindakan' => 'required|string',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $data = $request->except('bukti');
        if ($request->hasFile('bukti')) {
            $data['bukti'] = $request->file('bukti')->store('catatan_dosa_bukti', 'public');
        }
        $catatan = CatatanDosa::create($data);
        return response()->json(['success' => true, 'data' => $catatan]);
    }

    public function update(Request $request, $id)
    {
        $catatan = CatatanDosa::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:hrd_employee,id',
            'jenis_pelanggaran' => 'required|string',
            'kategori' => 'required|string',
            'deskripsi' => 'required|string',
            'status_tindaklanjut' => 'required|string',
            'tindakan' => 'required|string',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $data = $request->except('bukti');
        if ($request->hasFile('bukti')) {
            if ($catatan->bukti) Storage::disk('public')->delete($catatan->bukti);
            $data['bukti'] = $request->file('bukti')->store('catatan_dosa_bukti', 'public');
        }
        $catatan->update($data);
        return response()->json(['success' => true, 'data' => $catatan]);
    }

    public function destroy($id)
    {
        $catatan = CatatanDosa::findOrFail($id);
        if ($catatan->bukti) Storage::disk('public')->delete($catatan->bukti);
        $catatan->delete();
        return response()->json(['success' => true]);
    }

    public function show($id)
    {
        $catatan = CatatanDosa::with('employee')->findOrFail($id);
        return response()->json(['data' => $catatan]);
    }
}
