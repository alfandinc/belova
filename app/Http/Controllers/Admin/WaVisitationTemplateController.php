<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ERM\Klinik;
use App\Models\WaSession;
use App\Models\WaVisitationTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WaVisitationTemplateController extends Controller
{
    public function index()
    {
        $sessions = WaSession::with(['visitationTemplate.klinik'])
            ->orderByRaw('COALESCE(label, client_id) asc')
            ->get();

        $kliniks = Klinik::orderBy('nama')->get(['id', 'nama']);

        return view('admin.wa_visitation_templates', [
            'sessions' => $sessions,
            'kliniks' => $kliniks,
            'defaultTemplate' => $this->defaultTemplate(),
            'availableVariables' => [
                '{Nama Dokter}',
                '{Nama Pasien}',
                '{Tanggal Visitation}',
                '{Jam Visitation}',
                '{No Antrian}',
                '{Nama Klinik}',
            ],
        ]);
    }

    public function update(Request $request, WaSession $waSession)
    {
        $existingTemplate = WaVisitationTemplate::where('wa_session_id', $waSession->id)->first();

        $data = $request->validate([
            'klinik_id' => [
                'nullable',
                'exists:erm_klinik,id',
                Rule::unique('wa_visitation_templates', 'klinik_id')->ignore(optional($existingTemplate)->id),
            ],
            'template' => 'required|string',
        ]);

        WaVisitationTemplate::updateOrCreate(
            ['wa_session_id' => $waSession->id],
            [
                'klinik_id' => $data['klinik_id'] ?? null,
                'template' => $data['template'],
                'is_active' => $request->boolean('is_active'),
            ]
        );

        return back()->with('success', 'Visitation template updated for session ' . ($waSession->label ?: $waSession->client_id));
    }

    private function defaultTemplate(): string
    {
        return implode("\n", [
            'Selamat Malam Bpk/Ibu',
            'Jadwal Kontrol {Nama Dokter}',
            '',
            'Nama pasien : {Nama Pasien}',
            'Hari, tgl : {Tanggal Visitation}',
            'Jam : {Jam Visitation}',
            'Nomor Antrian : {No Antrian}',
            '',
            'Mohon konfirmasi ulang jika BATAL PERIKSA/ RESCHEDULE ULANG kami tunggu sampai besok jam 13.00',
            '',
            'Terima kasih, semoga sehat selalu',
            '',
            '*Nomor Antrian dapat berubah sewaktu-waktu',
            '*Pesan ini dikirimkan secara otomatis dari SIM Klinik Utama Premiere Belova',
        ]);
    }
}