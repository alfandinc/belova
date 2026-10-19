<?php

namespace App\Http\Controllers\BCL;
use App\Http\Controllers\Controller;

use App\Models\BCL\renter;
use App\Models\BCL\renter_document;
use App\Models\BCL\tr_renter;
use App\Services\BCL\RentalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class RenterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(\Illuminate\Http\Request $request)
    {
        // $renter = renter::leftjoin('renter_document', function ($join) {
        //     $join->on('renter.id', '=', 'renter_document.id_renter')
        //         ->where('renter_document.document_type', '=', 'PHOTO');
        // })->select('renter.*', 'renter_document.img')
        //     ->get();
    // default to showing active renters
    $status = $request->query('status', 'active');

        $query = renter::with('document')->with('current_room');
        if ($status === 'active') {
            // renters with active current_room
            $query = $query->whereHas('current_room');
        } elseif ($status === 'inactive') {
            // renters without active current_room
            $query = $query->whereDoesntHave('current_room');
        }

        $renter = $query->get();
        // return response()->json($renter);
        return view('bcl.renter.renter')->with(['renter' => $renter, 'status' => $status]);
    }

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
                return redirect()->back()->withErrors($validator)->withInput()->with('error', $validator->errors()->first());
            }
            // store uploaded files on the public disk (storage/app/public/renter)
            $img_photo_name = $this->generateRandomString(10) . '.' . $request->img_photo->extension();
            $img_identitas_name = $this->generateRandomString(10) . '.' . $request->img_identitas->extension();
            $request->img_photo->storeAs('renter', $img_photo_name, 'public');
            $request->img_identitas->storeAs('renter', $img_identitas_name, 'public');
            if (!empty($request->input_lain)) {
                $img_lain_name = $this->generateRandomString(10) . '.' . $request->input_lain->extension();
                $request->input_lain->storeAs('renter', $img_lain_name, 'public');
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
                'kendaraan' => (string) $request->kendaraan,
                'nopol' => (string) $request->nopol,

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
     * Quick add from the Sewa Kamar form (AJAX). Only name and phone are required; documents and the
     * remaining identity data can be completed later on Daftar Penyewa.
     */
    public function quickStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:150',
            'phone' => 'required|string|max:30',
            'phone2' => 'nullable|string|max:30',
            'alamat' => 'nullable|string|max:255',
            'identitas' => 'nullable|in:KTP,SIM,Paspor',
            'nomor_identitas' => 'nullable|string|max:50',
            'birthday' => 'nullable|date',
            'img_identitas' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $stored = null;
        try {
            $renter = DB::transaction(function () use ($request, &$stored) {
                $renter = renter::create([
                    'nama' => trim($request->nama),
                    'alamat' => (string) $request->alamat,
                    'phone' => trim($request->phone),
                    'phone2' => $request->phone2,
                    'birthday' => $request->birthday ? Carbon::parse($request->birthday)->format('Y-m-d') : null,
                    'identitas' => (string) $request->identitas,
                    'no_identitas' => (string) $request->nomor_identitas,
                    'kendaraan' => '',
                    'nopol' => '',
                ]);
                if ($request->hasFile('img_identitas')) {
                    $stored = $this->generateRandomString(10) . '.' . $request->img_identitas->extension();
                    $request->img_identitas->storeAs('renter', $stored, 'public');
                    renter_document::create(['id_renter' => $renter->id, 'document_type' => 'IDENTITAS', 'img' => $stored]);
                }

                return $renter;
            });
        } catch (\Throwable $th) {
            if ($stored) {
                Storage::disk('public')->delete('renter/' . $stored);
            }

            return response()->json(['message' => $th->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Penyewa ' . $renter->nama . ' ditambahkan.',
            'renter' => ['id' => $renter->id, 'nama' => $renter->nama, 'deposit_balance' => 0],
        ]);
    }

    public function edit(Request $request)
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

    public function detail($id, RentalService $rental)
    {
        $renter = renter::with(['document', 'current_room'])->findOrFail($id);
        $rows = tr_renter::with(['room', 'tambahan'])
            ->where('id_renter', $renter->id)
            ->orderByDesc('tgl_mulai')
            ->limit(12)
            ->get();
        $paid = $rental->paidByDoc($rows->pluck('trans_id')->merge($rows->flatMap->tambahan->pluck('kode')));
        $transactions = $rows
            ->map(function ($transaction) use ($paid, $rental) {
                $paidTotal = (float) ($paid[$transaction->trans_id] ?? 0) + (float) $transaction->tambahan->sum(fn ($e) => $paid[$e->kode] ?? 0);
                $extraTotal = (float) $transaction->tambahan->sum(fn ($e) => $rental->extraTotal($e));
                $grandTotal = (float) $transaction->harga + $extraTotal;

                return [
                    'trans_id' => $transaction->trans_id,
                    'room_name' => optional($transaction->room)->room_name,
                    'tgl_mulai' => $transaction->tgl_mulai,
                    'tgl_selesai' => $transaction->tgl_selesai,
                    'harga' => (float) $transaction->harga,
                    'extra_total' => $extraTotal,
                    'paid_total' => $paidTotal,
                    'remaining_total' => max($grandTotal - $paidTotal, 0),
                    'notes' => $transaction->catatan,
                ];
            })
            ->values();

        return response()->json([
            'renter' => [
                'id' => $renter->id,
                'nama' => $renter->nama,
                'alamat' => $renter->alamat,
                'phone' => $renter->phone,
                'phone2' => $renter->phone2,
                'birthday' => $renter->birthday,
                'identitas' => $renter->identitas,
                'no_identitas' => $renter->no_identitas,
                'kendaraan' => $renter->kendaraan,
                'nopol' => $renter->nopol,
                'deposit_balance' => $renter->deposit_balance,
                'current_room_name' => optional($renter->current_room)->room_name,
                'current_room_end' => optional($renter->current_room)->tgl_selesai,
            ],
            'documents' => $renter->document->map(function ($document) {
                return [
                    'id' => $document->id,
                    'type' => $document->document_type,
                    'img' => $document->img,
                    'url' => $document->img ? asset('storage/renter/' . $document->img) : null,
                ];
            })->values(),
            'transactions' => $transactions,
            'deposit_ledger' => $rental->depositLedger($renter->id)->reverse()->values(),
        ]);
    }

    public function rulesPreview($id)
    {
        $renter = renter::with(['current_room', 'document'])->findOrFail($id);

        $latestTransaction = tr_renter::with('room')
            ->where('id_renter', $renter->id)
            ->orderByDesc('tgl_mulai')
            ->first();

        $roomTransaction = $renter->current_room ?: $latestTransaction;

        return view('bcl.renter.rules_preview')->with([
            'renter' => $renter,
            'roomTransaction' => $roomTransaction,
        ]);
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
                return redirect()->back()->withErrors($validator)->withInput()->with('error', $validator->errors()->first());
            }
            // handle new uploaded files and remove previous files if present
            if (isset($request->img_photo)) {
                $img_photo_name = $this->generateRandomString(10) . '.' . $request->img_photo->extension();
                $request->img_photo->storeAs('renter', $img_photo_name, 'public');
                // delete old file if exists
                $photo = renter_document::where('id_renter', $request->id)->where('document_type', 'PHOTO')->first();
                if ($photo && $photo->img) {
                    Storage::disk('public')->delete('renter/' . $photo->img);
                }
            }
            if (isset($request->img_identitas)) {
                $img_identitas_name = $this->generateRandomString(10) . '.' . $request->img_identitas->extension();
                $request->img_identitas->storeAs('renter', $img_identitas_name, 'public');
                $ident = renter_document::where('id_renter', $request->id)->where('document_type', 'IDENTITAS')->first();
                if ($ident && $ident->img) {
                    Storage::disk('public')->delete('renter/' . $ident->img);
                }
            }
            if (isset($request->input_lain)) {
                $img_lain_name = $this->generateRandomString(10) . '.' . $request->input_lain->extension();
                $request->input_lain->storeAs('renter', $img_lain_name, 'public');
                $lain = renter_document::where('id_renter', $request->id)->where('document_type', 'LAINNYA')->first();
                if ($lain && $lain->img) {
                    Storage::disk('public')->delete('renter/' . $lain->img);
                }
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
                'kendaraan' => (string) $request->kendaraan,
                'nopol' => (string) $request->nopol,
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
    public function destroy(Request $request)
    {
        $renter = renter::find($request->id);
        if (!$renter) {
            return redirect()->route('bcl.renter.index')->with('error', 'Data tidak ditemukan');
        }
        if (tr_renter::where('id_renter', $renter->id)->exists()) {
            return redirect()->route('bcl.renter.index')->with('error', $renter->nama . ' memiliki riwayat transaksi sewa, data tidak dapat dihapus.');
        }
        if ((float) $renter->deposit_balance > 0) {
            return redirect()->route('bcl.renter.index')->with('error', $renter->nama . ' masih memiliki saldo deposit, data tidak dapat dihapus.');
        }

        try {
            DB::transaction(function () use ($renter) {
                foreach (renter_document::where('id_renter', $renter->id)->get() as $document) {
                    if ($document->img) {
                        Storage::disk('public')->delete('renter/' . $document->img);
                    }
                    $document->delete();
                }
                $renter->delete();
            });

            return redirect()->route('bcl.renter.index')->with('success', 'Data ' . $renter->nama . ' berhasil dihapus!');
        } catch (\Throwable $th) {
            return redirect()->route('bcl.renter.index')->with('error', 'Data gagal dihapus!');
        }
    }
}
