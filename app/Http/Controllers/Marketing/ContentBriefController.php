<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Marketing\ContentBrief;

class ContentBriefController extends Controller
{
    /**
     * Store a newly created content brief.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|integer|exists:marketing_content_briefs,id',
            'content_plan_id' => 'required|integer|exists:marketing_content_plans,id',
            'headline' => 'nullable|string|max:255',
            'sub_headline' => 'nullable|string|max:255',
            'isi_konten' => 'nullable|string',
            'visual_references.*' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'remove_visual_references' => 'nullable|array',
            'remove_visual_references.*' => 'nullable|string',
        ]);

        $paths = [];
        if ($request->hasFile('visual_references')) {
            foreach ($request->file('visual_references') as $file) {
                if (!$file->isValid()) continue;
                $path = $file->store('marketing/content_briefs', 'public');
                $paths[] = $path;
            }
        }

        // If id provided, update existing brief, otherwise create new
        if (!empty($data['id'])) {
            $brief = ContentBrief::find($data['id']);
            if (! $brief) {
                return response()->json(['success' => false, 'message' => 'Brief not found'], 404);
            }

            $brief->headline = $data['headline'] ?? $brief->headline;
            $brief->sub_headline = $data['sub_headline'] ?? $brief->sub_headline;
            $brief->isi_konten = $data['isi_konten'] ?? $brief->isi_konten;

            // merge existing visual references with newly uploaded ones,
            // but first handle any removals requested by the client
            $existing = is_array($brief->visual_references) ? $brief->visual_references : [];
            $removed = $request->input('remove_visual_references', []);
            $removed = is_array($removed) ? $removed : [$removed];
            $normalizedRemoved = [];
            foreach ($removed as $r) {
                if (! $r) continue;
                $nr = $r;
                // normalize '/storage/...' or absolute URLs to storage relative path
                if (strpos($nr, '/storage/') !== false) {
                    $nr = substr($nr, strpos($nr, '/storage/') + strlen('/storage/'));
                } elseif (strpos($nr, 'http') === 0) {
                    $pos = strpos($nr, '/storage/');
                    if ($pos !== false) $nr = substr($nr, $pos + strlen('/storage/'));
                } else {
                    // trim leading slash
                    $nr = ltrim($nr, '/');
                }
                $normalizedRemoved[] = $nr;
                // attempt to delete file from storage
                try {
                    if (Storage::disk('public')->exists($nr)) {
                        Storage::disk('public')->delete($nr);
                    }
                } catch (\Throwable $e) {
                    // log and continue; do not block the request
                    report($e);
                }
            }
            if (!empty($normalizedRemoved)) {
                $existing = array_values(array_filter($existing, function($v) use ($normalizedRemoved) {
                    // normalize stored value as well
                    $vv = ltrim($v, '/');
                    return ! in_array($vv, $normalizedRemoved);
                }));
            }
            if (!empty($paths)) {
                $brief->visual_references = array_values(array_merge($existing, $paths));
            } else {
                $brief->visual_references = $existing;
            }

            $brief->save();

            return response()->json(['success' => true, 'data' => $brief], 200);
        }

        $brief = new ContentBrief();
        $brief->content_plan_id = $data['content_plan_id'];
        $brief->user_id = $request->user() ? $request->user()->id : null;
        $brief->headline = $data['headline'] ?? null;
        $brief->sub_headline = $data['sub_headline'] ?? null;
        $brief->isi_konten = $data['isi_konten'] ?? null;
        $brief->visual_references = $paths;
        $brief->save();

        return response()->json(['success' => true, 'data' => $brief], 201);
    }

    /**
     * Return the latest brief for a given content plan.
     */
    public function latestByPlan($contentPlanId)
    {
        $brief = ContentBrief::where('content_plan_id', $contentPlanId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $brief) {
            return response()->json(null, 204);
        }

        return response()->json($brief, 200);
    }
}
