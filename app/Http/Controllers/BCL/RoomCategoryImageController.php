<?php

namespace App\Http\Controllers\BCL;

use App\Http\Controllers\Controller;
use App\Models\BCL\Room_Category_image;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Laravel\Facades\Image;

class RoomCategoryImageController extends Controller
{
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'tag' => 'required|in:room,public',
                'room' => 'nullable|exists:bcl_room_category,id_category',
                'receipt' => 'required|array|min:1',
                'receipt.*' => 'image|max:10240',
            ]);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        $saved = [];
        try {
            DB::transaction(function () use ($request, &$saved) {
                foreach ($request->file('receipt') as $file) {
                    $filename = 'belova_' . Carbon::now()->format('Y-m-d') . '_' . Str::random(12) . '.' . $file->getClientOriginalExtension();
                    Image::read($file)->scaleDown(1600, 1600)->save(public_path('assets/images/rooms/' . $filename));
                    $saved[] = $filename;
                    Room_Category_image::create([
                        'room_category_id' => $request->room,
                        'image' => $filename,
                        'tag' => $request->tag,
                    ]);
                }
            });

            return back()->with('success', 'Foto berhasil ditambahkan');
        } catch (\Throwable $th) {
            foreach ($saved as $filename) {
                @unlink(public_path('assets/images/rooms/' . $filename));
            }

            return back()->with('error', $th->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        $image = Room_Category_image::find($request->id);
        if (!$image) {
            return back()->with('error', 'Data tidak ditemukan');
        }

        $path = public_path('assets/images/rooms/' . $image->image);
        if (is_file($path)) {
            unlink($path);
        }
        $image->delete();

        return back()->with('success', 'Data berhasil dihapus');
    }
}
