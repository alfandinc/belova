<?php

namespace App\Http\Controllers\BCL;
use App\Http\Controllers\Controller;
use App\Models\BCL\room_category;
use App\Models\BCL\Room_Category_image;
use App\Models\BCL\Rooms;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RoomCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $images = Room_Category_image::with('category')->get();
        $categories = room_category::with('images')->withTrashed()->get();
        $rooms = Rooms::with('category')->get();
        // return response()->json($images);
        return view('bcl.category.index', compact('categories', 'images', 'rooms'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // use the actual DB table name (bcl_room_category) in unique validation
            $this->validate($request, [
                'nama_kategori' => 'required|unique:bcl_room_category,category_name',
            ]);
            $data = new room_category;
            $data->category_name = $request->nama_kategori;
            $data->notes = $request->notes;
            $data->slug = Str::slug($request->nama_kategori);
            $data->save();
            return back()->with('success', 'Data berhasil ditambahkan');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            // return validation errors so the UI can show the reason
            return back()->withErrors($ve->validator)->withInput();
        } catch (\Throwable $th) {
            // log the exception and return a helpful message in debug mode
            Log::error('RoomCategory store error: ' . $th->getMessage());
            $msg = config('app.debug') ? $th->getMessage() : 'Data gagal ditambahkan';
            return back()->with('error', $msg);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(room_category $room_category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(room_category $room_category, Request $request)
    {
        $data = room_category::with('images')->find($request->id);
        return response()->json($data);
    }

    public function restore($id)
    {
        try {
            $data = room_category::withTrashed()->find($id);
            $data->restore();
            return back()->with('success', 'Data berhasil dikembalikan');
        } catch (\Throwable $th) {
            return back()->with('error', 'Data gagal dikembalikan');
        }
    }
    public function forcedelete($id)
    {
        try {
            $data = room_category::withTrashed()->find($id);
            $data->forceDelete();
            return back()->with('success', 'Data berhasil dihapus permanen');
        } catch (\Throwable $th) {
            return back()->with('error', 'Data gagal dihapus permanen');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, room_category $room_category)
    {
        try {
            $data = room_category::find($request->id);
            $data->category_name = $request->nama_kategori;
            $data->notes = $request->notes;
            $data->slug = Str::slug($request->nama_kategori);
            $data->save();
            return back()->with('success', 'Data berhasil diubah');
        } catch (\Throwable $th) {
            return back()->with('error', 'Data gagal diubah');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
            $data = room_category::find($request->id);
            $data->delete();
            return back()->with('success', 'Data berhasil dihapus');
        } catch (\Throwable $th) {
            return back()->with('error', 'Data gagal dihapus');
        }
    }
}
