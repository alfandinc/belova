<?php

namespace App\Http\Controllers\BCL;
use App\Http\Controllers\Controller;
use App\Models\BCL\Room_Category_image;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Image;
// use Intervention\Image\Facades\Image as Image;

class RoomCategoryImageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    function generateRandomString($length = 20)
    {
        return substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyz', ceil($length / strlen($x)))), 1, $length) . time();
    }
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            if (isset($request->receipt)) {
                foreach ($request->receipt as $key => $value) {
                    $image = $request->file('receipt')[$key];
                    $filename = 'belova_' . Carbon::now()->format('Y-m-d') . '_' . $this->generateRandomString(8) . '.' . $image->getClientOriginalExtension();
                    $path = public_path('assets/images/rooms/' . $filename);
                    $img = Image::make($request->file('receipt')[$key]);
                    $img->save($path);
                    Room_Category_image::create([
                        'room_category_id' => $request->room,
                        'image' => $filename,
                        'tag' => $request->tag
                    ]);
                }
            }
            DB::commit();
            return back()->with('success', 'Data berhasil ditambahkan');
        } catch (\Throwable $th) {
            DB::rollback();
            return back()->with('error', $th->getMessage());
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(Room_Category_image $room_Category_image)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Room_Category_image $room_Category_image)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Room_Category_image $room_Category_image)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Room_Category_image $room_Category_image, Request $request)
    {
        DB::beginTransaction();
        try {
            $images = Room_Category_image::find($request->id);
            $path = public_path('assets/images/rooms/' . $images->image);
            if (file_exists($path)) {
                unlink($path);
            }
            $images->delete();
            DB::commit();
            return back()->with('success', 'Data berhasil dihapus');
        } catch (\Throwable $th) {
            DB::rollback();
            return back()->with('error', $th->getMessage());
        }
    }
}
