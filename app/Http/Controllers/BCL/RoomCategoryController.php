<?php

namespace App\Http\Controllers\BCL;

use App\Http\Controllers\Controller;
use App\Models\BCL\Pricelist;
use App\Models\BCL\room_category;
use App\Models\BCL\Room_Category_image;
use App\Models\BCL\Rooms;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoomCategoryController extends Controller
{
    public function index()
    {
        $images = Room_Category_image::with('category')->get();
        $categories = room_category::with('images')->withTrashed()->get();

        return view('bcl.category.index', compact('categories', 'images'));
    }

    public function store(Request $request)
    {
        return $this->save($request, new room_category(), 'Data berhasil ditambahkan');
    }

    public function edit(Request $request)
    {
        return response()->json(room_category::with('images')->find($request->id));
    }

    public function update(Request $request)
    {
        $category = room_category::find($request->id);
        if (!$category) {
            return back()->with('error', 'Data tidak ditemukan');
        }

        return $this->save($request, $category, 'Data berhasil diubah');
    }

    public function destroy(Request $request)
    {
        $category = room_category::find($request->id);
        if (!$category) {
            return back()->with('error', 'Data tidak ditemukan');
        }
        $roomCount = Rooms::where('room_category', $category->id_category)->count();
        if ($roomCount > 0) {
            return back()->with('error', 'Kategori masih dipakai ' . $roomCount . ' kamar. Pindahkan kamar ke kategori lain dulu.');
        }
        $category->delete();

        return back()->with('success', 'Data berhasil dihapus');
    }

    public function restore($id)
    {
        $category = room_category::withTrashed()->find($id);
        if (!$category) {
            return back()->with('error', 'Data tidak ditemukan');
        }
        $category->restore();

        return back()->with('success', 'Data berhasil dikembalikan');
    }

    public function forcedelete($id)
    {
        $category = room_category::withTrashed()->find($id);
        if (!$category) {
            return back()->with('error', 'Data tidak ditemukan');
        }
        if (Rooms::withTrashed()->where('room_category', $category->id_category)->exists()) {
            return back()->with('error', 'Kategori masih tercatat di data kamar (termasuk kamar yang dihapus), tidak bisa dihapus permanen.');
        }

        foreach ($category->images as $image) {
            $path = public_path('assets/images/rooms/' . $image->image);
            if (is_file($path)) {
                unlink($path);
            }
            $image->delete();
        }
        Pricelist::where('room_category', $category->id_category)->delete();
        $category->forceDelete();

        return back()->with('success', 'Data berhasil dihapus permanen');
    }

    protected function save(Request $request, room_category $category, string $message)
    {
        try {
            $this->validate($request, [
                'nama_kategori' => ['required', 'max:150', Rule::unique('bcl_room_category', 'category_name')->ignore($category->id_category, 'id_category')],
            ]);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        $category->category_name = $request->nama_kategori;
        $category->notes = $request->notes;
        $category->slug = Str::slug($request->nama_kategori);
        $category->save();

        return back()->with('success', $message);
    }
}
