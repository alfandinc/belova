<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Marketing\ContentInsight;
use App\Models\Marketing\SocialAccount;
use App\Support\Marketing\InstagramInsights;
use App\Support\Marketing\MarketingAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Instagram insights on the Content Plan: connected accounts, "Update insight" (one snapshot per
 * content per day) and the daily series of one content.
 */
class ContentInsightController extends Controller
{
    protected function authorizeMarketing(): void
    {
        abort_unless(MarketingAccess::isMarketing(Auth::user()), 403, 'Hanya tim Marketing.');
    }

    public function accounts()
    {
        $this->authorizeMarketing();
        $accounts = SocialAccount::where('platform', 'instagram')->orderBy('username')->get();

        return response()->json([
            'data' => $accounts->map(fn (SocialAccount $a) => [
                'id' => $a->id,
                'username' => $a->username,
                'brand' => $a->brand,
                'is_active' => (bool) $a->is_active,
                'token_expires_at' => optional($a->token_expires_at)->toIso8601String(),
                'last_synced_at' => optional($a->last_synced_at)->toIso8601String(),
                'last_error' => $a->last_error,
            ])->values(),
            'last_synced_at' => optional($accounts->max('last_synced_at'))->toIso8601String(),
            'eligible' => InstagramInsights::eligiblePlans()->count(),
        ]);
    }

    public function connect(Request $request)
    {
        $this->authorizeMarketing();
        $data = $request->validate(['token' => 'required|string|min:20|max:2000', 'brand' => 'nullable|string|max:100'], [], ['token' => 'token akses']);

        try {
            $account = InstagramInsights::connect($data['token'], $data['brand'] ?? null, Auth::id());
        } catch (RuntimeException $e) {
            return response()->json(['message' => 'Akun gagal dihubungkan: ' . $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'username' => $account->username]);
    }

    /** Check an Instagram post link while it is typed (format + found in a connected account). */
    public function checkLink(Request $request)
    {
        $this->authorizeMarketing();
        [$status, $message, $username] = InstagramInsights::checkLink((string) $request->input('url'));

        return response()->json(['status' => $status, 'message' => $message, 'username' => $username]);
    }

    /** Choose / change the clinic of a connected account. */
    public function updateAccount(Request $request, $id)
    {
        $this->authorizeMarketing();
        $data = $request->validate(['brand' => 'nullable|string|max:100']);
        SocialAccount::findOrFail($id)->update(['brand' => $data['brand'] ?: null]);

        return response()->json(['success' => true]);
    }

    public function disconnect($id)
    {
        $this->authorizeMarketing();
        SocialAccount::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    /** "Update insight": all published Instagram content of the last days, or one content (plan_id). */
    public function sync(Request $request)
    {
        $this->authorizeMarketing();
        $planId = $request->integer('plan_id') ?: null;

        try {
            $summary = InstagramInsights::sync(InstagramInsights::eligiblePlans($planId), Auth::id(), !$planId);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage(), 'no_account' => true], 422);
        }

        return response()->json($summary + ['success' => true]);
    }

    /** Daily numbers of one content, oldest first, with the growth against the previous snapshot. */
    public function series($planId)
    {
        $this->authorizeMarketing();
        $rows = ContentInsight::with('user:id,name')->where('content_plan_id', $planId)->orderBy('tanggal')->get();

        $prev = null;
        $out = $rows->map(function (ContentInsight $row) use (&$prev) {
            $item = ['tanggal' => $row->tanggal->toDateString(), 'by' => optional($row->user)->name, 'fetched_at' => optional($row->fetched_at)->toIso8601String()];
            foreach (ContentInsight::METRICS as $m) {
                $item[$m] = $row->{$m};
                $item[$m . '_delta'] = $prev && $row->{$m} !== null && $prev->{$m} !== null ? $row->{$m} - $prev->{$m} : null;
            }
            $prev = $row;

            return $item;
        });

        return response()->json(['data' => $out->values()]);
    }
}
