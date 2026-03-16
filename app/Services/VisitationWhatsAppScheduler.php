<?php

namespace App\Services;

use App\Models\ERM\Visitation;
use App\Models\WaScheduledMessage;
use App\Models\WaVisitationTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VisitationWhatsAppScheduler
{
    private const SCHEDULE_GAP_MINUTES = 3;

    public function queueForVisitation(Visitation $visitation): array
    {
        $visitation->loadMissing(['pasien', 'dokter.user', 'klinik']);

        if ((int) ($visitation->jenis_kunjungan ?? 0) !== 1) {
            Log::info('Visitation WhatsApp skipped: jenis_kunjungan is not eligible', [
                'visitation_id' => $visitation->id,
                'jenis_kunjungan' => $visitation->jenis_kunjungan,
            ]);
            return $this->result(false, 'ineligible_visit_type', 'Pesan WhatsApp tidak dijadwalkan karena jenis kunjungan bukan konsultasi.');
        }

        $templateConfig = $this->resolveTemplateConfig($visitation);
        if (!$templateConfig || !$templateConfig->session) {
            Log::info('Visitation WhatsApp skipped: no active template/session mapping', [
                'visitation_id' => $visitation->id,
                'klinik_id' => $visitation->klinik_id,
            ]);
            return $this->result(false, 'missing_template', 'Pesan WhatsApp tidak dijadwalkan karena belum ada template/session aktif untuk klinik ini.');
        }

        $sessionInfo = $this->fetchSessionInfo($templateConfig->session->client_id);

        $phone = $this->normalizePhone(optional($visitation->pasien)->no_hp ?: optional($visitation->pasien)->no_hp2);
        if (!$phone) {
            Log::info('Visitation WhatsApp skipped: pasien has no valid phone', [
                'visitation_id' => $visitation->id,
                'pasien_id' => $visitation->pasien_id,
            ]);
            return $this->result(false, 'missing_phone', 'Pesan WhatsApp tidak dijadwalkan karena pasien tidak memiliki nomor telepon yang valid.', [
                'client_id' => $templateConfig->session->client_id,
                'session_status' => $sessionInfo['status'],
                'session_note' => $sessionInfo['note'],
            ]);
        }

        $message = trim($this->renderTemplate($templateConfig->template, $visitation));
        if ($message === '') {
            Log::warning('Visitation WhatsApp skipped: rendered template empty', [
                'visitation_id' => $visitation->id,
                'wa_session_id' => $templateConfig->wa_session_id,
            ]);
            return $this->result(false, 'empty_message', 'Pesan WhatsApp tidak dijadwalkan karena template menghasilkan pesan kosong.', [
                'client_id' => $templateConfig->session->client_id,
                'session_status' => $sessionInfo['status'],
                'session_note' => $sessionInfo['note'],
            ]);
        }

        $scheduleAt = $this->resolveNextScheduleAt($templateConfig->session->client_id);

        WaScheduledMessage::create([
            'client_id' => $templateConfig->session->client_id,
            'pasien_id' => $visitation->pasien_id,
            'visitation_id' => $visitation->id,
            'to' => $phone,
            'message' => $message,
            'schedule_at' => $scheduleAt,
            'status' => 'pending',
        ]);

        Log::info('Visitation WhatsApp queued', [
            'visitation_id' => $visitation->id,
            'pasien_id' => $visitation->pasien_id,
            'client_id' => $templateConfig->session->client_id,
            'to' => $phone,
            'schedule_at' => $scheduleAt->toDateTimeString(),
        ]);

        return $this->result(true, 'queued', 'Pesan WhatsApp berhasil dijadwalkan.', [
            'client_id' => $templateConfig->session->client_id,
            'schedule_at' => $scheduleAt->toDateTimeString(),
            'session_status' => $sessionInfo['status'],
            'session_note' => $sessionInfo['note'],
        ]);
    }

    public function renderTemplate(string $template, Visitation $visitation): string
    {
        $dokterName = optional(optional($visitation->dokter)->user)->name ?: '-';
        $pasienName = optional($visitation->pasien)->nama ?: '-';
        $klinikName = optional($visitation->klinik)->nama ?: '-';
        $tanggalVisitation = $visitation->tanggal_visitation
            ? Carbon::parse($visitation->tanggal_visitation)->locale('id')->translatedFormat('l, d F Y')
            : '-';

        $jamVisitation = '-';
        if (!empty($visitation->waktu_kunjungan)) {
            $jamVisitation = substr((string) $visitation->waktu_kunjungan, 0, 5);
        }

        return strtr($template, [
            '{Nama Dokter}' => $dokterName,
            '{Nama Pasien}' => $pasienName,
            '{Tanggal Visitation}' => $tanggalVisitation,
            '{Jam Visitation}' => $jamVisitation,
            '{No Antrian}' => (string) ($visitation->no_antrian ?: '-'),
            '{Nama Klinik}' => $klinikName,
        ]);
    }

    private function resolveTemplateConfig(Visitation $visitation): ?WaVisitationTemplate
    {
        if (!empty($visitation->klinik_id)) {
            $mappedTemplate = WaVisitationTemplate::with('session')
                ->active()
                ->where('klinik_id', $visitation->klinik_id)
                ->first();

            if ($mappedTemplate && $mappedTemplate->session) {
                return $mappedTemplate;
            }
        }

        $activeTemplates = WaVisitationTemplate::with('session')
            ->active()
            ->get()
            ->filter(function ($template) {
                return $template->session !== null;
            })
            ->values();

        if ($activeTemplates->count() === 1) {
            return $activeTemplates->first();
        }

        return null;
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if (!$digits) {
            return null;
        }

        if (strpos($digits, '62') === 0) {
            return $digits;
        }

        if (strpos($digits, '0') === 0) {
            return '62' . substr($digits, 1);
        }

        if (strpos($digits, '8') === 0) {
            return '62' . $digits;
        }

        return $digits;
    }

    private function resolveNextScheduleAt(string $clientId): Carbon
    {
        $baseTime = Carbon::now();

        $lastScheduledAt = WaScheduledMessage::query()
            ->where('client_id', $clientId)
            ->whereIn('status', ['pending', 'sent'])
            ->whereNotNull('schedule_at')
            ->max('schedule_at');

        if (!$lastScheduledAt) {
            return $baseTime;
        }

        $nextSlot = Carbon::parse($lastScheduledAt)->addMinutes(self::SCHEDULE_GAP_MINUTES);

        return $nextSlot->greaterThan($baseTime) ? $nextSlot : $baseTime;
    }

    private function result(bool $queued, string $reason, string $message, array $extra = []): array
    {
        return array_merge([
            'queued' => $queued,
            'reason' => $reason,
            'message' => $message,
        ], $extra);
    }

    private function fetchSessionInfo(string $clientId): array
    {
        $serviceUrl = rtrim(config('app.wa_bot_url', 'http://localhost:3000'), '/');

        try {
            $response = Http::timeout(5)->get($serviceUrl . '/sessions');
            if (!$response->successful()) {
                return ['status' => 'unreachable', 'note' => 'Status session WhatsApp tidak dapat dicek.'];
            }

            $sessions = collect($response->json());
            $session = $sessions->firstWhere('id', $clientId);
            if (!$session) {
                return ['status' => 'missing', 'note' => 'Session WhatsApp belum terdaftar di service bot.'];
            }

            $status = $session['status'] ?? 'unknown';
            if ($status === 'ready') {
                return ['status' => $status, 'note' => 'Session WhatsApp siap mengirim pesan.'];
            }

            if ($status === 'authenticated') {
                return ['status' => $status, 'note' => 'Session WhatsApp sudah login tetapi belum sepenuhnya ready.'];
            }

            if ($status === 'qr') {
                return ['status' => $status, 'note' => 'Session WhatsApp masih menunggu scan QR.'];
            }

            return ['status' => $status, 'note' => 'Status session WhatsApp saat ini: ' . $status . '.'];
        } catch (\Exception $e) {
            return ['status' => 'unreachable', 'note' => 'Status session WhatsApp tidak dapat dicek.'];
        }
    }
}