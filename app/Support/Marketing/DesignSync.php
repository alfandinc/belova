<?php

namespace App\Support\Marketing;

use App\Models\Marketing\ContentPlan;
use App\Models\Marketing\DesignRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Content made by the designer ("Butuh designer" in Penugasan) has ONE flow and ONE approval:
 * the designer's job (DesignRequest, jenis Konten Sosmed, content_plan_id set) follows the content plan.
 *
 *   designer takes the job            → content Produksi
 *   designer "Selesai dikerjakan"     → content Review   (results are shared in the WA group)
 *   creator records "Revisi masuk"    → job Revisi
 *   creator records "Approved"        → job Selesai
 *   content cancelled                 → job Dibatalkan
 */
class DesignSync
{
    public static function enabled(): bool
    {
        static $ok = null;

        return $ok ??= Schema::hasTable('marketing_design_requests');
    }

    /** The running designer job of a content plan (not finished / cancelled), if any. */
    public static function activeJob(ContentPlan $plan): ?DesignRequest
    {
        if (!self::enabled()) {
            return null;
        }

        return DesignRequest::where('content_plan_id', $plan->id)
            ->whereIn('status', DesignRequest::ACTIVE_STATUSES)
            ->orderByDesc('id')->first();
    }

    /** Latest designer job, also finished ones (approval / revisi after Selesai). */
    public static function latestJob(ContentPlan $plan): ?DesignRequest
    {
        if (!self::enabled()) {
            return null;
        }

        return DesignRequest::where('content_plan_id', $plan->id)
            ->where('status', '!=', DesignRequest::STATUS_DIBATALKAN)
            ->orderByDesc('id')->first();
    }

    /**
     * Save the "Siapa yang mengerjakan?" choice of Penugasan: create / update / cancel the designer job.
     * A job the designer already started can't be switched off here.
     */
    public static function applyChoice(ContentPlan $plan, bool $needsDesigner, ?int $designerId): void
    {
        if (!self::enabled()) {
            return;
        }
        $job = self::activeJob($plan);

        if (!$needsDesigner) {
            if ($job && $job->status === DesignRequest::STATUS_BARU) {
                $job->update(['status' => DesignRequest::STATUS_DIBATALKAN]);
                $job->logStatus('cancel', DesignRequest::STATUS_BARU, DesignRequest::STATUS_DIBATALKAN, 'Konten dikerjakan content creator saja');
            }

            return;
        }

        // the designer reads the content's own Brief, so the job carries no separate note
        if (!$job) {
            $job = DesignRequest::create([
                'jenis' => 'Konten Sosmed',
                'judul' => 'Desain konten - ' . $plan->judul,
                'deadline' => optional($plan->tanggal_produksi)->toDateString() ?: optional($plan->tanggal_publish)->toDateString(),
                'status' => DesignRequest::STATUS_BARU,
                'requester_id' => Auth::id(),
                'designer_id' => $designerId,
                'content_plan_id' => $plan->id,
            ]);
            $job->logStatus('created', null, DesignRequest::STATUS_BARU, 'Dari Content Plan #' . $plan->id);
            $plan->logStatus('design', null, null, 'Dikerjakan designer' . ($job->designer ? ': ' . $job->designer->name : ' (menunggu diambil)'));

            return;
        }

        // keep the job in step with the plan: designer and deadline
        $update = [
            'deadline' => optional($plan->tanggal_produksi)->toDateString() ?: $job->deadline,
            'judul' => 'Desain konten - ' . $plan->judul,
        ];
        if ($job->status === DesignRequest::STATUS_BARU && (int) $job->designer_id !== (int) $designerId) {
            $update['designer_id'] = $designerId;
            $job->logStatus('assign', null, null, 'Designer: ' . (optional(\App\Models\User::find($designerId))->name ?? 'diambil sendiri'));
        }
        $job->update($update);
    }

    /** The content moved on the board (drag): the designer's deadline follows the production date. */
    public static function syncDeadline(ContentPlan $plan): void
    {
        if (!self::enabled() || !($job = self::activeJob($plan)) || !$plan->tanggal_produksi) {
            return;
        }
        if (optional($job->deadline)->toDateString() !== $plan->tanggal_produksi->toDateString()) {
            $job->update(['deadline' => $plan->tanggal_produksi->toDateString()]);
        }
    }

    /**
     * The content is deleted: an open designer job is cancelled (it would otherwise stay in the queue as a
     * loose job), and every job keeps no link to the deleted content.
     */
    public static function planDeleted(ContentPlan $plan): void
    {
        if (!self::enabled()) {
            return;
        }
        if ($job = self::activeJob($plan)) {
            $from = $job->status;
            $job->update(['status' => DesignRequest::STATUS_DIBATALKAN]);
            $job->logStatus('cancel', $from, DesignRequest::STATUS_DIBATALKAN, 'Konten "' . $plan->judul . '" dihapus dari Content Plan');
        }
        DesignRequest::where('content_plan_id', $plan->id)->update(['content_plan_id' => null]);
    }

    /** The designer moved the job: carry it over to the content plan. */
    public static function fromDesign(DesignRequest $job, string $action): void
    {
        if (!$job->content_plan_id || !($plan = ContentPlan::find($job->content_plan_id))) {
            return;
        }
        $designer = optional($job->designer)->name ?? optional(Auth::user())->name;

        if ($action === 'take' && in_array($plan->status, ['Draft', 'Scheduled'], true)) {
            self::movePlan($plan, 'start', 'Produksi', 'Diambil designer: ' . $designer);
        } elseif ($action === 'submit_review' && in_array($plan->status, ['Produksi', 'Revisi'], true)) {
            self::movePlan($plan, 'submit_review', 'Review', 'Selesai dikerjakan designer: ' . $designer);
        }
    }

    /** The creator recorded a decision on the content plan: carry it over to the designer job. */
    public static function fromPlan(ContentPlan $plan, string $action, ?string $notes): void
    {
        if (!self::enabled()) {
            return;
        }
        $map = [
            'revise' => DesignRequest::STATUS_REVISI,
            'approve' => DesignRequest::STATUS_SELESAI,
            'cancel' => DesignRequest::STATUS_DIBATALKAN,
        ];
        if (!isset($map[$action])) {
            return;
        }
        $job = $action === 'cancel' ? self::activeJob($plan) : self::latestJob($plan);
        if (!$job || $job->status === $map[$action]) {
            return;
        }

        $from = $job->status;
        $job->update([
            'status' => $map[$action],
            'completed_at' => $action === 'approve' ? now() : null,
        ]);
        $job->logStatus($action, $from, $map[$action], trim('Dari Content Plan' . ($notes ? ": \n" . $notes : '')));
    }

    protected static function movePlan(ContentPlan $plan, string $action, string $to, string $note): void
    {
        $from = $plan->status;
        $plan->update(['status' => $to]);
        $plan->logStatus($action, $from, $to, $note);
    }

    /** Plan transitions the designer drives while a job is running. */
    public static function hiddenPlanActions(ContentPlan $plan): array
    {
        return self::activeJob($plan) ? ['start', 'submit_review'] : [];
    }
}
