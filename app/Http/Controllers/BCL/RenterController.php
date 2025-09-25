<?php

namespace App\Http\Controllers\BCL;
use App\Http\Controllers\Controller;

use App\Models\BCL\renter;
use App\Models\BCL\renter_document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RenterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $renter = renter::leftjoin('renter_document', function ($join) {
        //     $join->on('renter.id', '=', 'renter_document.id_renter')
        //         ->where('renter_document.document_type', '=', 'PHOTO');
        // })->select('renter.*', 'renter_document.img')
        //     ->get();
        $renter = renter::with('document')->with('current_room')->get();
        // return response()->json($renter);
        return view('bcl.renter.renter')->with('renter', $renter);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    function generateRandomString($length = 20)
    {
        return substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyz', ceil($length / strlen($x)))), 1, $length) . time();
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'img_photo' => 'required|image|mimes:jpeg,png,jpg|max:5120',
                'img_identitas' => 'required|image|mimes:jpeg,png,jpg|max:5120',
                'input_lain' => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:5120',
                'nama' => 'required',
                'alamat' => 'required',
                'phone' => 'required',
                'birthday' => 'required',
                'phone2' => 'required',
                'identitas' => 'required',
                'nomor_identitas' => 'required'
            ]);

            // Ensure birthday is a valid date
            $validator->after(function ($validator) use ($request) {
                if (!empty($request->birthday)) {
                    try {
                        Carbon::parse($request->birthday);
                    } catch (\Exception $e) {
                        $validator->errors()->add('birthday', 'Format tanggal lahir tidak valid');
                    }
                }
            });

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            $img_photo_name = $this->generateRandomString(10) . '.' . $request->img_photo->extension();
            $img_identitas_name = $this->generateRandomString(10) . '.' . $request->img_identitas->extension();
            $request->img_photo->move(public_path('assets/images/renter/'), $img_photo_name);
            $request->img_identitas->move(public_path('assets/images/renter/'), $img_identitas_name);
            if (!empty($request->input_lain)) {
                $img_lain_name = $this->generateRandomString(10) . '.' . $request->input_lain->extension();
                $request->input_lain->move(public_path('assets/images/renter/'), $img_lain_name);
            } else {
                $img_lain_name = null;
            }
            DB::beginTransaction();
            $ins_renter = renter::create([
                'nama' => $request->nama,
                'alamat' => $request->alamat,
                'phone' => $request->phone,
                'phone2' => $request->phone2,
                'birthday' => !empty($request->birthday) ? Carbon::parse($request->birthday)->format('Y-m-d') : null,
                'identitas' => $request->identitas,
                'no_identitas' => $request->nomor_identitas,
                'kendaraan' => $request->kendaraan,
                'nopol' => $request->nopol,

            ]);
            $ins_document = renter_document::create([
                'id_renter' => $ins_renter->id,
                'document_type' => 'PHOTO',
                'img' => $img_photo_name
            ]);
            $ins_document = renter_document::create([
                'id_renter' => $ins_renter->id,
                'document_type' => 'IDENTITAS',
                'img' => $img_identitas_name
            ]);
            if (!empty($request->input_lain)) {
                $ins_document = renter_document::create([
                    'id_renter' => $ins_renter->id,
                    'document_type' => 'LAINNYA',
                    'img' => $img_lain_name
                ]);
            }
            DB::commit();
            return redirect()->route('bcl.renter.index')->with(['success' => 'Data ' . $request->nama . ' berhasil ditambahkan!']);
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->route('bcl.renter.index')->with(['error' => $th->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(renter $renter)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(renter $renter, Request $request)
    {
        try {
            $renter = renter::findorfail($request->id);
            $renter_document = renter_document::where('id_renter', $request->id)->get();
            $data = [$renter, $renter_document];
            return response()->json($data);
            // return view('renter.edit')->with(['renter' => $renter, 'renter_document' => $renter_document]);
        } catch (\Throwable $th) {
            return redirect()->route('bcl.renter.index')->with(['error' => 'Data gagal dihapus!']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, renter $renter)
    {
        try {
            $validator = Validator::make($request->all(), [
                'img_photo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:5120',
                'img_identitas' => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:5120',
                'input_lain' => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:5120',
                'nama' => 'required',
                'alamat' => 'required',
                'phone' => 'required',
                'phone2' => 'required',
                'birthday' => 'required',
                'identitas' => 'required',
                'nomor_identitas' => 'required'
            ]);

            $validator->after(function ($validator) use ($request) {
                if (!empty($request->birthday)) {
                    try {
                        Carbon::parse($request->birthday);
                    } catch (\Exception $e) {
                        $validator->errors()->add('birthday', 'Format tanggal lahir tidak valid');
                    }
                }
            });

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            if (isset($request->img_photo)) {
                $img_photo_name = $this->generateRandomString(10) . '.' . $request->img_photo->extension();
                $request->img_photo->move(public_path('assets\images\renter'), $img_photo_name);
            }
            if (isset($request->img_identitas)) {
                $img_identitas_name = $this->generateRandomString(10) . '.' . $request->img_identitas->extension();
                $request->img_identitas->move(public_path('assets\images\renter'), $img_identitas_name);
            }
            if (isset($request->input_lain)) {
                $img_lain_name = $this->generateRandomString(10) . '.' . $request->input_lain->extension();
                $request->input_lain->move(public_path('assets\images\renter'), $img_lain_name);
            }
            DB::beginTransaction();
            $renter = renter::findorfail($request->id);
            $renter->update([
                'nama' => $request->nama,
                'alamat' => $request->alamat,
                'phone' => $request->phone,
                'phone2' => $request->phone2,
                'birthday' => !empty($request->birthday) ? Carbon::parse($request->birthday)->format('Y-m-d') : null,
                'identitas' => $request->identitas,
                'no_identitas' => $request->nomor_identitas,
                'kendaraan' => $request->kendaraan,
                'nopol' => $request->nopol,
            ]);
            if (isset($request->img_photo)) {
                $photo = renter_document::where('id_renter', $request->id)->where('document_type', 'PHOTO')->first();
                if ($photo == null) {
                    renter_document::create([
                        'id_renter' => $request->id,
                        'document_type' => 'PHOTO',
                        'img' => $img_photo_name
                    ]);
                } else {
                    $photo->update([
                        'img' => $img_photo_name
                    ]);
                }
            }
            if (isset($request->img_identitas)) {
                $identitas = renter_document::where('id_renter', $request->id)->where('document_type', 'IDENTITAS')->first();
                if ($identitas == null) {
                    renter_document::create([
                        'id_renter' => $request->id,
                        'document_type' => 'IDENTITAS',
                        'img' => $img_identitas_name
                    ]);
                } else {
                    $identitas->update([
                        'img' => $img_identitas_name
                    ]);
                }
            }
            if (isset($request->input_lain)) {
                $lain = renter_document::where('id_renter', $request->id)->where('document_type', 'LAINNYA')->first();
                if ($lain == null) {
                    renter_document::create([
                        'id_renter' => $request->id,
                        'document_type' => 'LAINNYA',
                        'img' => $img_lain_name
                    ]);
                } else {
                    $lain->update([
                        'img' => $img_lain_name
                    ]);
                }
            }
            DB::commit();
            return redirect()->route('bcl.renter.index')->with(['success' => 'Data ' . $request->nama . ' berhasil diubah!']);
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->route('bcl.renter.index')->with(['error' => $th->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(renter $renter, Request $request)
    {
        try {
            DB::beginTransaction();
            $renter = renter::findorfail($request->id);
            $result = $renter->delete();
            $renter_document = renter_document::where('id_renter', $request->id)->get();
            foreach ($renter_document as $key => $value) {
                $result = $value->delete();
            }
            DB::commit();
            return redirect()->route('bcl.renter.index')->with(['success' => 'Data ' . $renter->nama . ' berhasil dihapus!']);
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->route('bcl.renter.index')->with(['error' => 'Data gagal dihapus!']);
        }
    }
}
