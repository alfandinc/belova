<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Finance\InvoiceController;
use App\Models\ERM\Klinik;
use App\Models\ERM\Pasien;
use App\Models\ERM\Visitation;
use App\Models\Marketing\MarketingEvent;
use App\Models\Marketing\Promo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Events module (main menu "Events"): event list, patients per event and the entry point to event billing.
 * Open to every logged-in user.
 */
class EventsController extends Controller
{
    public function index()
    {
        $kliniks = Klinik::select('id', 'nama')->orderBy('nama')->get();
        $promos = $this->activePromos();

        return view('events.index', compact('kliniks', 'promos'));
    }

    public function data()
    {
        $events = MarketingEvent::query()
            ->with(['klinik:id,nama', 'promos:id,name'])
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->get();

        foreach ($events as $event) {
            $event->setAttribute('patients_count', $this->eventVisitsQuery($event)->count());
        }

        return response()->json(['data' => $events]);
    }

    /**
     * Event detail page: event info, its patients and event billing.
     */
    public function show(MarketingEvent $event)
    {
        $event->load(['klinik:id,nama', 'promos:id,name']);

        return view('events.show', compact('event'));
    }

    /**
     * Event fields for the edit form.
     */
    public function editData(MarketingEvent $event)
    {
        return response()->json(['item' => $event->load('promos:id,name')]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        $promoIds = $data['promo_ids'] ?? [];
        unset($data['promo_ids']);

        if ($request->hasFile('dokumen_proposal')) {
            $data['dokumen_proposal'] = $request->file('dokumen_proposal')->store('marketing/events/proposal', 'public');
        }

        if ($request->hasFile('dokumen_laporan')) {
            $data['dokumen_laporan'] = $request->file('dokumen_laporan')->store('marketing/events/laporan', 'public');
        }

        $event = MarketingEvent::create($data);
        $event->promos()->sync($promoIds);

        return response()->json(['success' => true, 'item' => $event->load('promos:id,name')]);
    }

    public function update(Request $request, MarketingEvent $event)
    {
        $data = $this->validateRequest($request);
        $promoIds = $data['promo_ids'] ?? [];
        unset($data['promo_ids']);

        if ($request->hasFile('dokumen_proposal')) {
            if ($event->dokumen_proposal) {
                Storage::disk('public')->delete($event->dokumen_proposal);
            }
            $data['dokumen_proposal'] = $request->file('dokumen_proposal')->store('marketing/events/proposal', 'public');
        }

        if ($request->hasFile('dokumen_laporan')) {
            if ($event->dokumen_laporan) {
                Storage::disk('public')->delete($event->dokumen_laporan);
            }
            $data['dokumen_laporan'] = $request->file('dokumen_laporan')->store('marketing/events/laporan', 'public');
        }

        $event->update($data);
        $event->promos()->sync($promoIds);

        return response()->json(['success' => true, 'item' => $event->load('promos:id,name')]);
    }

    public function destroy(MarketingEvent $event)
    {
        // Visits and invoices point at the event; keep it once patients are recorded (set it to "Selesai" instead).
        if ($this->eventVisitsQuery($event)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Event sudah memiliki pasien dan tidak bisa dihapus. Ubah status menjadi Selesai.',
            ], 422);
        }

        if ($event->dokumen_proposal) {
            Storage::disk('public')->delete($event->dokumen_proposal);
        }

        if ($event->dokumen_laporan) {
            Storage::disk('public')->delete($event->dokumen_laporan);
        }

        $event->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Patients (visits) of an event with their billing status.
     */
    public function patients(MarketingEvent $event)
    {
        $eventMorph = $event->getMorphClass();
        $canBill = $event->status === 'aktif';

        $visits = $this->eventVisitsQuery($event)
            ->with([
                'pasien:id,nama,no_hp,referral_type,referral_detail,referralable_type,referralable_id',
                'invoice:id,visitation_id,invoice_number,total_amount,amount_paid,payment_method,status',
            ])
            ->orderByDesc('tanggal_visitation')
            ->orderByDesc('waktu_kunjungan')
            ->get(['id', 'pasien_id', 'tanggal_visitation', 'waktu_kunjungan', 'jenis_kunjungan']);

        $rows = $visits->map(function (Visitation $visit) use ($event, $eventMorph, $canBill) {
            $pasien = $visit->pasien;
            $invoice = $visit->invoice;

            // The patient was first registered through this event
            $isNewPatient = $pasien
                && (string) $pasien->referral_type === Pasien::REFERRAL_TYPE_EVENT
                && (
                    ((string) $pasien->referralable_type === $eventMorph && (string) $pasien->referralable_id === (string) $event->id)
                    || (!empty($event->kode_event) && (string) $pasien->referral_detail === (string) $event->kode_event)
                );

            return [
                'visitation_id' => $visit->id,
                'tanggal' => $visit->tanggal_visitation ? \Carbon\Carbon::parse($visit->tanggal_visitation)->format('Y-m-d') : null,
                'waktu' => $visit->waktu_kunjungan ? substr((string) $visit->waktu_kunjungan, 0, 5) : null,
                'pasien_id' => $pasien->id ?? null,
                'nama' => $pasien->nama ?? '-',
                'no_hp' => $pasien->no_hp ?? null,
                'is_new_patient' => (bool) $isNewPatient,
                'invoice_number' => $invoice->invoice_number ?? null,
                'total' => $invoice ? (float) ($invoice->total_amount ?? 0) : null,
                'paid' => $invoice ? (float) ($invoice->amount_paid ?? 0) : null,
                'status' => $this->billingStatus($invoice),
                // event billing only handles event-type visits (others are billed in Finance)
                'billing_url' => $canBill && (int) $visit->jenis_kunjungan === 4
                    ? route('events.billing.create', ['event' => $event->id, 'visitation_id' => $visit->id])
                    : null,
                'print_url' => $invoice && (int) $visit->jenis_kunjungan === 4
                    ? route('events.billing.print-nota', $invoice->id)
                    : null,
            ];
        });

        return response()->json([
            'data' => $rows->values(),
            'summary' => [
                'patients' => $rows->count(),
                'new_patients' => $rows->where('is_new_patient', true)->count(),
                'total' => $rows->sum('total'),
                'paid' => $rows->sum('paid'),
            ],
        ]);
    }

    /**
     * Nota print for an event visit's invoice (route is limited to event visits by the event.visitation middleware).
     */
    public function printNota($invoice)
    {
        return app(InvoiceController::class)->printNota($invoice);
    }

    /**
     * Visits that belong to an event (cancelled visits excluded):
     * - the visit's own referral points to the event (by id or event code), or
     * - an event-type visit (jenis_kunjungan 4) at the event's klinik during the event, not tied to another event
     *   (same fallback event billing uses for such visits).
     */
    private function eventVisitsQuery(MarketingEvent $event): Builder
    {
        $eventMorph = $event->getMorphClass();

        return Visitation::query()
            ->where('status_kunjungan', '!=', 7)
            ->where(function ($q) use ($event, $eventMorph) {
                $q->where(function ($r) use ($event, $eventMorph) {
                    $r->where('referral_type', Pasien::REFERRAL_TYPE_EVENT)
                        ->where(function ($x) use ($event, $eventMorph) {
                            $x->where(function ($y) use ($event, $eventMorph) {
                                $y->where('referralable_type', $eventMorph)
                                    ->where('referralable_id', (string) $event->id);
                            });
                            if (!empty($event->kode_event)) {
                                $x->orWhere('referral_detail', (string) $event->kode_event);
                            }
                        });
                });

                if ($event->klinik_id && $event->tanggal_mulai) {
                    $q->orWhere(function ($r) use ($event) {
                        $r->where('jenis_kunjungan', 4)
                            ->where('klinik_id', $event->klinik_id)
                            ->where('tanggal_visitation', '>=', $event->tanggal_mulai->toDateString())
                            ->when($event->tanggal_selesai, function ($w) use ($event) {
                                $w->where('tanggal_visitation', '<=', $event->tanggal_selesai->toDateString());
                            })
                            ->where(function ($w) {
                                $w->whereNull('referral_type')
                                    ->orWhere('referral_type', '!=', Pasien::REFERRAL_TYPE_EVENT);
                            });
                    });
                }
            });
    }

    /**
     * Billing status label of a visit's invoice (same labels as the Finance billing list).
     */
    private function billingStatus($invoice): string
    {
        if (!$invoice) {
            return 'Belum Transaksi';
        }

        $total = (float) ($invoice->total_amount ?? 0);
        $paid = (float) ($invoice->amount_paid ?? 0);

        if (($total > 0 && $paid >= $total) || ($total <= 0 && $invoice->status === 'paid')) {
            return 'Lunas';
        }
        if (strtolower((string) $invoice->payment_method) === 'piutang') {
            return 'Piutang';
        }
        if ($paid > 0) {
            return 'Belum Lunas';
        }

        return 'Belum Transaksi';
    }

    private function activePromos()
    {
        $today = now()->toDateString();

        return Promo::query()
            ->where(function ($query) use ($today) {
                $query->where(function ($q) use ($today) {
                    $q->whereNotNull('start_date')
                        ->whereNotNull('end_date')
                        ->where('start_date', '<=', $today)
                        ->where('end_date', '>=', $today);
                })->orWhere(function ($q) use ($today) {
                    $q->whereNotNull('start_date')
                        ->whereNull('end_date')
                        ->where('start_date', '<=', $today);
                })->orWhere(function ($q) use ($today) {
                    $q->whereNull('start_date')
                        ->whereNotNull('end_date')
                        ->where('end_date', '>=', $today);
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'start_date', 'end_date']);
    }

    private function validateRequest(Request $request)
    {
        return $request->validate([
            'kode_event' => 'required|string|max:100',
            'nama_event' => 'required|string|max:255',
            'deskripsi_event' => 'nullable|string',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'klinik_id' => 'required|exists:erm_klinik,id',
            'lokasi' => 'nullable|string|max:255',
            'target_market' => 'nullable|string|max:255',
            'status' => 'required|in:aktif,selesai',
            'promo_ids' => 'nullable|array',
            'promo_ids.*' => 'integer|exists:marketing_promos,id',
            'dokumen_proposal' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            'dokumen_laporan' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);
    }
}
