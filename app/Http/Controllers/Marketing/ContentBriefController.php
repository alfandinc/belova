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
            'content_plan_id' => 'required|integer|exists:marketing_content_plans,id',
            'headline' => 'nullable|string|max:255',
            'sub_headline' => 'nullable|string|max:255',
            'isi_konten' => 'nullable|string',
            'visual_references.*' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ]);

        $paths = [];
        if ($request->hasFile('visual_references')) {
            foreach ($request->file('visual_references') as $file) {
                if (!$file->isValid()) continue;
                $path = $file->store('marketing/content_briefs', 'public');
                $paths[] = $path;
            }
        }

        $brief = new ContentBrief();
        $brief->content_plan_id = $data['content_plan_id'];
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
