<?php

namespace App\Http\Controllers\BCL;

use App\Http\Controllers\Controller;
use App\Models\BCL\ChatTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class ChatTemplateController extends Controller
{
    public function index()
    {
        $contextSuggestions = [
            'Room Facility',
            'Masa Sewa Hampir Habis',
            'Konfirmasi Sewa',
            'Isi Data Penyewa',
            'Follow Up Pembayaran',
            'Pengingat Deposit',
        ];

        $placeholders = [
            '{{nama_penyewa}}',
            '{{nomor_kamar}}',
            '{{fasilitas_kamar}}',
            '{{tanggal_mulai}}',
            '{{tanggal_selesai}}',
            '{{jumlah_hari}}',
            '{{total_tagihan}}',
            '{{link_form}}',
            '{{catatan}}',
        ];

        return view('bcl.chat_template.index', compact('contextSuggestions', 'placeholders'));
    }

    public function data(Request $request)
    {
        $query = ChatTemplate::query()->select('bcl_chat_templates.*')->latest('updated_at');

        return DataTables::of($query)
            ->addColumn('content_preview', function ($row) {
                return nl2br(e(Str::limit($row->content, 180)));
            })
            ->editColumn('context', function ($row) {
                return $row->context ?: '-';
            })
            ->editColumn('updated_at', function ($row) {
                return optional($row->updated_at)->format('d/m/Y H:i');
            })
            ->rawColumns(['content_preview'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $template = ChatTemplate::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Template chat berhasil ditambahkan.',
            'data' => $template,
        ]);
    }

    public function edit($id)
    {
        $template = ChatTemplate::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $template,
        ]);
    }

    public function update(Request $request, $id)
    {
        $template = ChatTemplate::findOrFail($id);
        $data = $this->validated($request);

        $template->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Template chat berhasil diperbarui.',
            'data' => $template,
        ]);
    }

    public function destroy($id)
    {
        $template = ChatTemplate::findOrFail($id);
        $template->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Template chat berhasil dihapus.',
        ]);
    }

    protected function validated(Request $request)
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'context' => 'nullable|string|max:100',
            'content' => 'required|string',
        ]);
    }
}