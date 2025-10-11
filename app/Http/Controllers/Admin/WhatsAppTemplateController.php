<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppTemplateController extends Controller
{
    /**
     * Display template management page
     */
    public function index()
    {
        $templates = WhatsAppTemplate::active()->get()->keyBy('key');
        
        return view('admin.whatsapp.templates.index', compact('templates'));
    }

    /**
     * Update a specific template
     */
    public function update(Request $request, $templateKey)
    {
        $request->validate([
            'content' => 'required|string|max:4000'
        ]);

        try {
            $template = WhatsAppTemplate::where('key', $templateKey)->first();
            
            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template not found'
                ], 404);
            }

            $template->update([
                'content' => $request->content
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update WhatsApp template', [
                'template_key' => $templateKey,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview a template with sample data
     */
    public function preview(Request $request, $templateKey)
    {
        $content = $request->input('content');
        
        // Sample data for different template types
        $sampleData = [
            'visitation' => [
                'pasien_nama' => 'John Doe',
                'pasien_id' => 'RM001234',
                'jenis_kunjungan' => 'Konsultasi Umum',
                'tanggal_visitation' => '2025-10-11',
                'waktu_kunjungan' => '10:00',
                'no_antrian' => '15',
                'dokter_nama' => 'dr. Jane Smith',
                'klinik_nama' => 'Belova Clinic'
            ],
            'appointment_reminder' => [
                'pasien_nama' => 'John Doe',
                'tanggal_visitation' => '2025-10-11',
                'waktu_kunjungan' => '10:00',
                'dokter_nama' => 'dr. Jane Smith',
                'klinik_nama' => 'Belova Clinic'
            ],
            'payment_reminder' => [
                'pasien_nama' => 'John Doe',
                'invoice_number' => 'INV-2025-001234',
                'amount' => '500,000',
                'due_date' => '2025-10-15'
            ],
            'lab_results' => [
                'pasien_nama' => 'John Doe',
                'test_type' => 'Tes Darah Lengkap',
                'test_date' => '2025-10-09',
                'dokter_nama' => 'dr. Jane Smith'
            ],
            'birthday_greeting' => [
                'pasien_nama' => 'John Doe',
                'age' => '35'
            ]
        ];

        $data = $sampleData[$templateKey] ?? [];
        
        // Replace variables in content
        $previewContent = $content;
        foreach ($data as $key => $value) {
            $previewContent = str_replace('{' . $key . '}', $value, $previewContent);
        }

        return response()->json([
            'success' => true,
            'preview' => $previewContent
        ]);
    }
}